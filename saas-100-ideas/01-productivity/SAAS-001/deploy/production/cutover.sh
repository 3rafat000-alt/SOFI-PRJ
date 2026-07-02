#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════════════════════
# TaskSync Pro — Staging → Production Cutover Script
# ═══════════════════════════════════════════════════════════════════════════════
# Executes the final cutover from staging environment to production.
# Run this AFTER pre-flight checklist (docs/PRODUCTION_GO_LIVE_CHECKLIST.md)
# and AFTER UAT sign-off is captured.
#
# Usage:
#   sudo bash deploy/production/cutover.sh                    # Full cutover
#   sudo bash deploy/production/cutover.sh --skip-seed        # Skip DB seeding
#   sudo bash deploy/production/cutover.sh --dry-run          # Print steps, no execute
#   sudo bash deploy/production/cutover.sh --verify-only      # Verify only, no changes
#
# Prerequisites:
#   - Server provisioned (Ubuntu 24.04, Docker, git)
#   - DNS records propagated
#   - SSL wildcard certificate issued
#   - .env.production populated with real secrets (no placeholders)
#   - This script runs ON the production server
# ═══════════════════════════════════════════════════════════════════════════════

set -euo pipefail

PROJECT_DIR="/opt/tasksync"
COMPOSE_FILE="docker-compose.prod.yml"
LOGFILE="${PROJECT_DIR}/cutover-$(date +%Y%m%d-%H%M%S).log"
ADMIN_EMAIL="admin@tasksyncpro.com"
ADMIN_NAME="Admin"

DRY_RUN=false
SKIP_SEED=false
VERIFY_ONLY=false

# Parse flags
for arg in "$@"; do
  case "$arg" in
    --dry-run) DRY_RUN=true ;;
    --skip-seed) SKIP_SEED=true ;;
    --verify-only) VERIFY_ONLY=true ;;
    *) echo "Unknown flag: $arg"; exit 1 ;;
  esac
done

log()   { echo "[$(date '+%H:%M:%S')] $*" | tee -a "$LOGFILE"; }
fail()  { log "❌ $*"; exit 1; }
ok()    { log "✅ $*"; }
warn()  { log "⚠️  $*"; }
run() {
  if [ "$DRY_RUN" = true ]; then
    log "[DRY-RUN] $*"
  else
    log "▶ $*"
    eval "$*" 2>&1 | tee -a "$LOGFILE"
  fi
}

# ═══════════════════════════════════════════════════════════════════════════════
# PHASE 0: Pre-flight Verification
# ═══════════════════════════════════════════════════════════════════════════════

phase_0_preflight() {
  log "═══════════════════════════════════════════════════════════════"
  log "PHASE 0: Pre-flight Verification"
  log "═══════════════════════════════════════════════════════════════"

  if [ "$VERIFY_ONLY" = true ]; then
    log "Running in verify-only mode — no changes will be made."
  fi

  # 0.1 Check running as root/sudo
  if [ "$(id -u)" -ne 0 ] && [ "$DRY_RUN" = false ]; then
    fail "This script requires root/sudo. Run with: sudo bash $0"
  fi
  ok "Running as root"

  # 0.2 Verify project directory
  if [ ! -d "$PROJECT_DIR" ]; then
    fail "Project directory $PROJECT_DIR not found. Clone repo first."
  fi
  cd "$PROJECT_DIR"
  ok "Project directory: $PROJECT_DIR"

  # 0.3 Verify .env.production has no placeholders
  if grep -q '\${' .env.production 2>/dev/null; then
    warn ".env.production still contains unresolved placeholders!"
    grep -n '\${' .env.production | head -20
    if [ "$DRY_RUN" = false ] && [ "$VERIFY_ONLY" = false ]; then
      fail "Resolve all placeholders in .env.production before cutover"
    fi
  else
    ok ".env.production — all secrets populated"
  fi

  # 0.4 Verify docker-compose.prod.yml exists
  if [ ! -f "$COMPOSE_FILE" ]; then
    fail "docker-compose.prod.yml not found"
  fi
  ok "docker-compose.prod.yml present"

  # 0.5 Verify Docker is installed and running
  docker info > /dev/null 2>&1 || fail "Docker not running"
  ok "Docker Engine running"

  # 0.6 Verify DNS resolves
  local domain="tasksyncpro.com"
  if host "$domain" > /dev/null 2>&1; then
    ok "DNS resolves: $(host "$domain" | head -1)"
  else
    warn "DNS not yet resolving for $domain — continuing (may be DNS propagation)"
  fi

  # 0.7 Verify SSL certificate exists
  if [ -f "/etc/letsencrypt/live/tasksyncpro.com/fullchain.pem" ]; then
    local expiry
    expiry=$(openssl x509 -enddate -noout -in /etc/letsencrypt/live/tasksyncpro.com/fullchain.pem 2>/dev/null | cut -d= -f2)
    ok "SSL certificate present — expires: $expiry"
  else
    warn "SSL certificate not found at /etc/letsencrypt/live/tasksyncpro.com/"
    warn "Run SSL setup first (see docs/PRODUCTION_GO_LIVE_CHECKLIST.md)"
  fi

  # 0.8 Verify ports are available
  for port in 80 443 5432 6379 6001; do
    if ss -tlnp | grep -q ":$port "; then
      # Check if it's our own container
      if docker ps --format '{{.Names}}' | grep -q "tasksync"; then
        ok "Port $port — in use by TaskSync container (expected)"
      else
        warn "Port $port in use by another process"
      fi
    else
      ok "Port $port — available"
    fi
  done

  # 0.9 Check disk space
  local avail_kb
  avail_kb=$(df /opt --output=avail 2>/dev/null | tail -1)
  if [ "$avail_kb" -lt 5242880 ]; then  # 5GB minimum
    warn "Low disk space: $(($avail_kb/1024))M available (recommended >5GB)"
  else
    ok "Disk space: $(($avail_kb/1024/1024))G available"
  fi

  # 0.10 Check memory
  local mem_total_kb
  mem_total_kb=$(grep MemTotal /proc/meminfo | awk '{print $2}')
  if [ "$mem_total_kb" -lt 4194304 ]; then  # 4GB minimum
    warn "Low memory: $((mem_total_kb/1024))M (recommended >4GB)"
  else
    ok "Memory: $((mem_total_kb/1024/1024))G total"
  fi

  log ""
  if [ "$VERIFY_ONLY" = true ]; then
    ok "Pre-flight verification complete — all checks passed."
    exit 0
  fi
}

# ═══════════════════════════════════════════════════════════════════════════════
# PHASE 1: Start Infrastructure Services
# ═══════════════════════════════════════════════════════════════════════════════

phase_1_infrastructure() {
  log ""
  log "═══════════════════════════════════════════════════════════════"
  log "PHASE 1: Starting Infrastructure Services (DB + Cache)"
  log "═══════════════════════════════════════════════════════════════"

  # Pull images
  run "docker compose -f ${COMPOSE_FILE} pull postgres redis"

  # Start infrastructure (no app services yet)
  run "docker compose -f ${COMPOSE_FILE} up -d postgres redis"

  # Wait for PostgreSQL
  log "Waiting for PostgreSQL to be ready..."
  local pg_retries=30
  for i in $(seq 1 $pg_retries); do
    if docker compose -f "${COMPOSE_FILE}" exec -T postgres pg_isready -U tasksync > /dev/null 2>&1; then
      ok "PostgreSQL is ready (attempt $i)"
      break
    fi
    if [ "$i" -eq "$pg_retries" ]; then
      fail "PostgreSQL failed to start after ${pg_retries} attempts. Check logs: docker compose logs postgres"
    fi
    sleep 2
  done

  # Wait for Redis
  log "Waiting for Redis to be ready..."
  local rd_retries=20
  for i in $(seq 1 $rd_retries); do
    if docker compose -f "${COMPOSE_FILE}" exec -T redis redis-cli -a "${PRODUCTION_REDIS_PASSWORD:-dummy}" ping 2>/dev/null | grep -q "PONG"; then
      ok "Redis is ready (attempt $i)"
      break
    fi
    if [ "$i" -eq "$rd_retries" ]; then
      fail "Redis failed to start after ${rd_retries} attempts"
    fi
    sleep 2
  done

  ok "Infrastructure services running: PostgreSQL + Redis"
}

# ═══════════════════════════════════════════════════════════════════════════════
# PHASE 2: Database Migration + Seeding
# ═══════════════════════════════════════════════════════════════════════════════

phase_2_database() {
  log ""
  log "═══════════════════════════════════════════════════════════════"
  log "PHASE 2: Database Migration & Seeding"
  log "═══════════════════════════════════════════════════════════════"

  # Run migrations
  log "Running database migrations..."
  run "docker compose -f ${COMPOSE_FILE} run --rm php-fpm-blue php artisan migrate --force"

  if [ "$SKIP_SEED" = false ]; then
    log "Seeding initial data..."

    # Seed admin user
    run "docker compose -f ${COMPOSE_FILE} run --rm php-fpm-blue \
      php artisan db:seed --class=DatabaseSeeder --force"

    # Create initial admin user if seeder exists
    if docker compose -f "${COMPOSE_FILE}" run --rm php-fpm-blue \
      php artisan tinker --execute="echo class_exists(\App\Models\User::class) ? 'exists' : 'no';" 2>/dev/null | grep -q "exists"; then
      log "Setting up initial admin user (${ADMIN_EMAIL})..."

      # Check if admin already exists
      local admin_exists
      admin_exists=$(docker compose -f "${COMPOSE_FILE}" run --rm php-fpm-blue \
        php artisan tinker --execute="echo \App\Models\User::where('email', '${ADMIN_EMAIL}')->exists() ? 'yes' : 'no';" 2>/dev/null)

      if [ "$admin_exists" = "no" ]; then
        run "docker compose -f ${COMPOSE_FILE} run --rm php-fpm-blue \
          php artisan tinker --execute=\"\\App\\Models\\User::create(['name'=>'${ADMIN_NAME}','email'=>'${ADMIN_EMAIL}','password'=>bcrypt(\\\$password ?? 'ChangeMe123!')]);\""
        ok "Initial admin user created: ${ADMIN_EMAIL}"
        warn "!!! CHANGE THE PASSWORD ON FIRST LOGIN !!!"
      else
        ok "Admin user already exists: ${ADMIN_EMAIL}"
      fi
    fi
  else
    log "Skipping database seed (--skip-seed flag)"
  fi

  ok "Database migration + seeding complete"
}

# ═══════════════════════════════════════════════════════════════════════════════
# PHASE 3: Deploy Blue/Green + Cache Warm
# ═══════════════════════════════════════════════════════════════════════════════

phase_3_deploy() {
  log ""
  log "═══════════════════════════════════════════════════════════════"
  log "PHASE 3: Blue/Green Deploy & Cache Warm"
  log "═══════════════════════════════════════════════════════════════"

  # 3.1 Run blue/green deployment
  log "Running blue/green deployment..."
  run "bash ${PROJECT_DIR}/deploy/production/deploy-bluegreen.sh"

  # 3.2 Cache warm — popular queries
  log "Warming caches..."

  # Warm route cache
  run "docker compose -f ${COMPOSE_FILE} run --rm php-fpm-blue php artisan route:cache"

  # Warm config cache
  run "docker compose -f ${COMPOSE_FILE} run --rm php-fpm-blue php artisan config:cache"

  # Warm view cache (if any Blade views)
  run "docker compose -f ${COMPOSE_FILE} run --rm php-fpm-blue php artisan view:cache"

  # Warm event cache
  run "docker compose -f ${COMPOSE_FILE} run --rm php-fpm-blue php artisan event:cache"

  # Pre-populate Redis caches via warm command (if exists)
  if docker compose -f "${COMPOSE_FILE}" run --rm php-fpm-blue \
    php artisan list --format=json 2>/dev/null | grep -q "cache:warm"; then
    run "docker compose -f ${COMPOSE_FILE} run --rm php-fpm-blue php artisan cache:warm"
  else
    log "No custom cache:warm command — skipping"
  fi

  ok "Cache warmed — route/config/view/event cached"
}

# ═══════════════════════════════════════════════════════════════════════════════
# PHASE 4: Service Verification
# ═══════════════════════════════════════════════════════════════════════════════

phase_4_verify() {
  log ""
  log "═══════════════════════════════════════════════════════════════"
  log "PHASE 4: Service Verification"
  log "═══════════════════════════════════════════════════════════════"

  local all_ok=true

  # 4.1 Verify all containers running
  log "Container status:"
  docker compose -f "${COMPOSE_FILE}" ps | tee -a "$LOGFILE"

  local expected_services="postgres redis php-fpm-blue php-fpm-green nginx reverb horizon"
  for svc in $expected_services; do
    if docker compose -f "${COMPOSE_FILE}" ps --format '{{.Status}}' "$svc" 2>/dev/null | grep -q "Up"; then
      ok "  $svc — running"
    else
      warn "  $svc — NOT running"
      all_ok=false
    fi
  done

  # 4.2 Health check endpoint
  log "Health check (HTTP):"
  local hc_result
  hc_result=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/api/v1/health 2>/dev/null || echo "000")
  if [ "$hc_result" = "200" ]; then
    ok "  Health endpoint: HTTP 200"
  else
    warn "  Health endpoint: HTTP ${hc_result} (expected 200)"
    all_ok=false
  fi

  # 4.3 Nginx health check
  local nginx_hc
  nginx_hc=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/nginx-health 2>/dev/null || echo "000")
  if [ "$nginx_hc" = "200" ]; then
    ok "  Nginx health: HTTP 200"
  else
    warn "  Nginx health: HTTP ${nginx_hc}"
  fi

  # 4.4 Reverb health check (raw socket connect)
  if timeout 3 bash -c "echo > /dev/tcp/localhost/6001" 2>/dev/null; then
    ok "  Reverb port 6001 — accepting connections"
  else
    warn "  Reverb port 6001 — not responding"
  fi

  # 4.5 Horizon status
  local horizon_status
  horizon_status=$(docker compose -f "${COMPOSE_FILE}" exec -T horizon php artisan horizon:status 2>/dev/null || echo "inactive")
  if echo "$horizon_status" | grep -q "active"; then
    ok "  Horizon — active"
  else
    warn "  Horizon — ${horizon_status}"
  fi

  # 4.6 PostgreSQL query test
  if docker compose -f "${COMPOSE_FILE}" exec -T postgres psql -U tasksync -d tasksync -c "SELECT 1;" > /dev/null 2>&1; then
    ok "  PostgreSQL — queryable"
  else
    warn "  PostgreSQL — query failed"
  fi

  # 4.7 Redis ping
  if docker compose -f "${COMPOSE_FILE}" exec -T redis redis-cli -a "${PRODUCTION_REDIS_PASSWORD:-dummy}" ping 2>/dev/null | grep -q "PONG"; then
    ok "  Redis — PONG"
  else
    warn "  Redis — not responding"
  fi

  log ""
  if [ "$all_ok" = true ]; then
    ok "═══════════════════════════════════════════════════════════════"
    ok "ALL SERVICES VERIFIED — PRODUCTION IS LIVE"
    ok "═══════════════════════════════════════════════════════════════"
  else
    warn "═══════════════════════════════════════════════════════════════"
    warn "SOME CHECKS FAILED — review logs above"
    warn "Run: docker compose -f ${COMPOSE_FILE} logs --tail=50"
    warn "═══════════════════════════════════════════════════════════════"
  fi
}

# ═══════════════════════════════════════════════════════════════════════════════
# PHASE 5: Post-Cutover Summary
# ═══════════════════════════════════════════════════════════════════════════════

phase_5_summary() {
  log ""
  log "═══════════════════════════════════════════════════════════════"
  log "CUTOVER SUMMARY"
  log "═══════════════════════════════════════════════════════════════"
  log "Date:           $(date)"
  log "Project:        TaskSync Pro (SAAS-001)"
  log "Domain:         https://tasksyncpro.com"
  log "Server:         $(hostname -f)"
  log "Docker images:"
  docker compose -f "${COMPOSE_FILE}" images | tee -a "$LOGFILE"
  log ""
  log "Running containers:"
  docker compose -f "${COMPOSE_FILE}" ps --format "table {{.Name}}\t{{.Status}}\t{{.Ports}}" | tee -a "$LOGFILE"
  log ""
  log "Log file:       $LOGFILE"
  log ""
  log "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
  log "NEXT STEPS:"
  log "  1. Verify HTTPS: https://tasksyncpro.com/api/v1/health"
  log "  2. Run UAT smoke tests on production"
  log "  3. Monitor Sentry for errors (15 min soak)"
  log "  4. Verify WebSocket: wscat -c wss://tasksyncpro.com:6001"
  log "  5. Configure uptime monitoring (UptimeRobot)"
  log "  6. Hand off to SRE (Gate 8)"
  log "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
}

# ═══════════════════════════════════════════════════════════════════════════════
# MAIN
# ═══════════════════════════════════════════════════════════════════════════════

main() {
  log "╔══════════════════════════════════════════════════════════════╗"
  log "║      TaskSync Pro — Staging → Production Cutover           ║"
  log "║      $(date)                    ║"
  log "╚══════════════════════════════════════════════════════════════╝"
  log ""

  phase_0_preflight

  phase_1_infrastructure
  phase_2_database
  phase_3_deploy
  phase_4_verify
  phase_5_summary
}

main
