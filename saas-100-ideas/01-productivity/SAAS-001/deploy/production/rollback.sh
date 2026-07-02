#!/usr/bin/env bash
# =============================================================================
# TaskSync Pro — Production Rollback
# =============================================================================
# Reverts Nginx traffic to the previous green target.
# Assumes the old deployment containers are still running.
#
# Usage:
#   ./deploy/production/rollback.sh          # auto-detect (revert to other target)
#   ./deploy/production/rollback.sh blue      # explicitly rollback to blue
#   ./deploy/production/rollback.sh green     # explicitly rollback to green
#   ./deploy/production/rollback.sh --db      # rollback + restore last DB dump
#   ./deploy/production/rollback.sh --hard    # rollback git + DB restore + rebuild
# =============================================================================

set -euo pipefail

PROJECT_DIR="/opt/tasksync"
COMPOSE_FILE="docker-compose.prod.yml"
LOGFILE="${PROJECT_DIR}/rollback-$(date +%Y%m%d-%H%M%S).log"

log() { echo "[$(date '+%H:%M:%S')] $*" | tee -a "$LOGFILE"; }
fail() { log "❌ $*"; exit 1; }
success() { log "✅ $*"; }

# ── Determine current active target ───────────────────────────────────────
detect_active() {
    local upstream
    upstream=$(docker compose exec nginx grep -oP 'server \Kphp-fpm-(blue|green):9000' /etc/nginx/conf.d/default.conf 2>/dev/null || true)

    if echo "$upstream" | grep -q "blue"; then
        CURRENT="blue"
        TARGET="green"
    elif echo "$upstream" | grep -q "green"; then
        CURRENT="green"
        TARGET="blue"
    else
        log "⚠️ Could not detect active upstream. Defaulting: current=blue, target=green"
        CURRENT="blue"
        TARGET="green"
    fi
    log "Current active: ${CURRENT} | Rollback target: ${TARGET}"
}

# ── Basic rollback (traffic switch only) ──────────────────────────────────
rollback_traffic() {
    log "--- Switching traffic from ${CURRENT} to ${TARGET} ---"
    docker compose exec -T nginx sh -c "
        sed -i 's/server php-fpm-${CURRENT}:9000/server php-fpm-${TARGET}:9000/' /etc/nginx/conf.d/default.conf && \
        nginx -s reload
    " 2>&1 | tee -a "$LOGFILE"
    success "Nginx reloaded — traffic now routing to ${TARGET}"
}

# ── Health check ─────────────────────────────────────────────────────────
health_check() {
    log "--- Health check (post-rollback) ---"
    for i in $(seq 1 12); do
        local status
        status=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/api/v1/health 2>/dev/null || echo "000")
        if [ "$status" = "200" ]; then
            success "Health check passed (HTTP 200) after rollback to ${TARGET}"
            return 0
        fi
        log "  Waiting for health... (${i}/12)"
        sleep 5
    done
    fail "Health check FAILED after rollback. Manual intervention required."
}

# ── DB restore ──────────────────────────────────────────────────────────
restore_db() {
    log "--- Restoring last DB backup ---"
    local latest_backup
    latest_backup=$(ls -t "${PROJECT_DIR}/backups/"pre-deploy-*.sql 2>/dev/null | head -1)

    if [ -z "$latest_backup" ]; then
        fail "No DB backups found in ${PROJECT_DIR}/backups/"
    fi

    log "Restoring from: ${latest_backup}"
    docker compose exec -T postgres psql -U tasksync -d tasksync -c "DROP SCHEMA public CASCADE; CREATE SCHEMA public;" 2>&1 | tee -a "$LOGFILE"
    docker compose exec -T postgres psql -U tasksync -d tasksync < "$latest_backup" 2>&1 | tee -a "$LOGFILE"
    success "DB restored from ${latest_backup}"
}

# ── Git revert + rebuild ─────────────────────────────────────────────────
hard_rollback() {
    log "--- Hard rollback: reverting git ---"
    cd "$PROJECT_DIR"
    git revert HEAD --no-edit 2>&1 | tee -a "$LOGFILE"
    log "Git revert complete. Rebuilding ${TARGET}..."
    docker compose -f "${COMPOSE_FILE}" build "php-fpm-${TARGET}" 2>&1 | tee -a "$LOGFILE"
    docker compose -f "${COMPOSE_FILE}" up -d "php-fpm-${TARGET}" 2>&1 | tee -a "$LOGFILE"
}

# ── Main ──────────────────────────────────────────────────────────────────
main() {
    log "=== 🔄 TaskSync Pro Rollback ==="

    if [ -n "${1:-}" ] && [ "${1#--}" != "$1" ]; then
        # Flag-based mode
        if [ "$1" = "--db" ]; then
            detect_active
            restore_db
            rollback_traffic
            health_check
        elif [ "$1" = "--hard" ]; then
            detect_active
            hard_rollback
            restore_db
            rollback_traffic
            health_check
        else
            fail "Unknown flag: $1. Use --db, --hard, or a target name (blue/green)."
        fi
    elif [ -n "${1:-}" ]; then
        # Explicit target
        TARGET="$1"
        case "$TARGET" in
            blue) CURRENT="green";;
            green) CURRENT="blue";;
            *) fail "Invalid target: $TARGET. Use 'blue' or 'green'.";;
        esac
        log "Explicit rollback: ${CURRENT} → ${TARGET}"
        rollback_traffic
        health_check
    else
        # Auto-detect
        detect_active
        rollback_traffic
        health_check
    fi

    success "=== ✅ Rollback complete: ${TARGET} is now active ==="
}

main "$@"
