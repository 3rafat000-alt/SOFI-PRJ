#!/usr/bin/env bash
# =============================================================================
# TaskSync Pro — Blue/Green Production Deployment
# =============================================================================
# Strategy:
#   1. Build new image for the STANDBY target (e.g., if blue is active, deploy to green)
#   2. Run migrations against standby
#   3. Switch traffic via Nginx upstream (reload)
#   4. Health check the new active target
#   5. Keep old target running for rollback
#
# Usage: ./deploy/production/deploy-bluegreen.sh [blue|green]
#   Default: auto-detect — deploys to the *inactive* target
# =============================================================================

set -euo pipefail

PROJECT_DIR="/opt/tasksync"
COMPOSE_FILE="docker-compose.prod.yml"
LOGFILE="${PROJECT_DIR}/deploy-$(date +%Y%m%d-%H%M%S).log"
NGINX_CONF="/etc/nginx/conf.d/default.conf"
NGINX_BLUE_CONF="/etc/nginx/conf.d/bluegreen.conf"

log() { echo "[$(date '+%H:%M:%S')] $*" | tee -a "$LOGFILE"; }
fail() { log "❌ $*"; exit 1; }
success() { log "✅ $*"; }

# ── Determine active / standby targets ───────────────────────────────────
detect_targets() {
    if [ -n "${1:-}" ]; then
        TARGET="$1"
        case "$TARGET" in
            blue) OTHER="green";;
            green) OTHER="blue";;
            *) fail "Invalid target: $TARGET. Use 'blue' or 'green'.";;
        esac
    else
        # Auto-detect: check which upstream Nginx is routing to
        log "Auto-detecting active target from Nginx config..."
        local active_upstream
        active_upstream=$(docker compose exec nginx grep -oP 'server \Kphp-fpm-(blue|green):9000' /etc/nginx/conf.d/default.conf 2>/dev/null || true)

        if echo "$active_upstream" | grep -q "blue"; then
            TARGET="green"
            OTHER="blue"
            log "🟢 Active: blue → Deploying to: green"
        elif echo "$active_upstream" | grep -q "green"; then
            TARGET="blue"
            OTHER="green"
            log "🟢 Active: green → Deploying to: blue"
        else
            TARGET="blue"
            OTHER="green"
            log "ℹ️  No active target detected. Defaulting: deploy to green, blue active"
        fi
    fi

    TARGET_CONTAINER="php-fpm-${TARGET}"
    OTHER_CONTAINER="php-fpm-${OTHER}"
    log "Target: ${TARGET_CONTAINER} (standby) | Active: ${OTHER_CONTAINER} (current)"
}

# ── Pre-deploy checks ─────────────────────────────────────────────────────
pre_checks() {
    log "--- Pre-deploy checks ---"
    cd "$PROJECT_DIR"

    # Verify git state
    if ! git diff --quiet HEAD; then
        fail "Working directory has uncommitted changes. Commit or stash first."
    fi

    # Verify .env.production exists
    if [ ! -f ".env.production" ]; then
        fail ".env.production not found. Create it from .env.production.template"
    fi

    # Verify compose file
    if [ ! -f "${COMPOSE_FILE}" ]; then
        fail "${COMPOSE_FILE} not found"
    fi

    # Verify other target is healthy before deploying
    log "Checking current active target (${OTHER_CONTAINER}) health..."
    local other_health
    other_health=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/api/v1/health 2>/dev/null || echo "000")
    if [ "$other_health" != "200" ]; then
        log "⚠️  WARNING: Current active target returned HTTP ${other_health}. Deploying anyway..."
    else
        success "Current active target is healthy (HTTP 200)"
    fi

    # Pre-deploy DB backup
    log "--- Pre-deploy DB backup ---"
    mkdir -p "${PROJECT_DIR}/backups"
    docker compose exec -T postgres pg_dump -U tasksync tasksync \
        > "${PROJECT_DIR}/backups/pre-deploy-$(date +%Y%m%d-%H%M%S).sql" \
        || log "⚠️ Backup failed (non-fatal)"
}

# ── Build and deploy to standby ───────────────────────────────────────────
deploy_standby() {
    log "--- Building ${TARGET_CONTAINER} ---"
    docker compose -f "${COMPOSE_FILE}" build "${TARGET_CONTAINER}" 2>&1 | tee -a "$LOGFILE"

    log "--- Starting ${TARGET_CONTAINER} ---"
    docker compose -f "${COMPOSE_FILE}" up -d "${TARGET_CONTAINER}" 2>&1 | tee -a "$LOGFILE"

    log "--- Waiting for ${TARGET_CONTAINER} health ---"
    for i in $(seq 1 18); do
        local container_ip
        container_ip=$(docker compose -f "${COMPOSE_FILE}" inspect "${TARGET_CONTAINER}" \
            --format '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' 2>/dev/null || true)

        if [ -n "$container_ip" ]; then
            local hc_status
            hc_status=$(curl -s -o /dev/null -w "%{http_code}" "http://${container_ip}:9000/ping" 2>/dev/null || echo "000")
            if [ "$hc_status" = "200" ]; then
                success "PHP-FPM ping OK on ${TARGET_CONTAINER}"
                break
            fi
        fi
        log "  Waiting for PHP-FPM ping... (${i}/18)"
        sleep 5
    done

    # If we have a health check endpoint on the standby, use the API health route
    # This requires the standby to be reachable — test via the app itself
    log "--- Running migrations on ${TARGET_CONTAINER} ---"
    docker compose -f "${COMPOSE_FILE}" run --rm "${TARGET_CONTAINER}" \
        php artisan migrate --force 2>&1 | tee -a "$LOGFILE" || fail "Migration failed on ${TARGET_CONTAINER}"

    log "--- Optimizing on ${TARGET_CONTAINER} ---"
    docker compose -f "${COMPOSE_FILE}" run --rm "${TARGET_CONTAINER}" \
        php artisan optimize:clear 2>&1 | tee -a "$LOGFILE" || true
}

# ── Switch traffic ──────────────────────────────────────────────────────
switch_traffic() {
    log "=== Switching traffic to ${TARGET_CONTAINER} ==="

    # Update Nginx upstream (use env var or config replacement)
    if grep -q "ACTIVE_UPSTREAM" /etc/nginx/conf.d/ 2>/dev/null; then
        # If using env var approach via docker compose
        docker compose exec -T nginx env "ACTIVE_UPSTREAM=${TARGET}" nginx -s reload 2>&1 | tee -a "$LOGFILE"
    else
        # Inline config replacement approach — rewrite the upstream in nginx config
        # This approach uses sed to replace the upstream server line
        docker compose exec -T nginx sh -c "
            sed -i 's/server php-fpm-${OTHER}:9000/server php-fpm-${TARGET}:9000/' /etc/nginx/conf.d/default.conf && \
            nginx -s reload
        " 2>&1 | tee -a "$LOGFILE"
    fi

    success "Nginx reloaded — traffic now routing to ${TARGET_CONTAINER}"
}

# ── Health check after switch ─────────────────────────────────────────────
health_check() {
    log "--- Health check (post-switch) ---"
    for i in $(seq 1 12); do
        local status
        status=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/api/v1/health 2>/dev/null || echo "000")
        if [ "$status" = "200" ]; then
            success "Health check passed (HTTP 200) after switch to ${TARGET_CONTAINER}"
            return 0
        fi
        log "  Waiting for health... (${i}/12)"
        sleep 5
    done

    # Health check failed — rollback
    log "❌ Health check FAILED — rolling back to ${OTHER_CONTAINER}"
    docker compose exec -T nginx sh -c "
        sed -i 's/server php-fpm-${TARGET}:9000/server php-fpm-${OTHER}:9000/' /etc/nginx/conf.d/default.conf && \
        nginx -s reload
    "
    fail "Rolled back to ${OTHER_CONTAINER}"
}

# ── Cleanup old standby ──────────────────────────────────────────────────
cleanup() {
    log "--- Cleanup ---"
    if [ -f "${PROJECT_DIR}/backups/"* ]; then
        # Keep last 7 backups
        ls -t "${PROJECT_DIR}/backups/"*.sql | tail -n +8 | xargs -r rm
        log "Cleaned old backups (kept last 7)"
    fi

    # Stop the old standby (now the new standby)
    # Do NOT remove the volume — keep for instant rollback
    log "Old target (${OTHER_CONTAINER}) remains running for fast rollback"
    success "=== ✅ Blue/Green deploy complete: ${TARGET_CONTAINER} is now active ==="
}

# ── Main ──────────────────────────────────────────────────────────────────
main() {
    log "=== 🚀 TaskSync Pro Blue/Green Deploy ==="
    detect_targets "$@"
    pre_checks
    deploy_standby
    switch_traffic
    health_check
    cleanup
}

main "$@"
