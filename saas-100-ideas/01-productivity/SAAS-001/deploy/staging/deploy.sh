#!/usr/bin/env bash
# =============================================================================
# TaskSync Pro — Staging Deployment Script
# Usage: ./deploy/staging/deploy.sh [branch]
# Default: main
# =============================================================================
set -euo pipefail

STAGING_DIR="/opt/tasksync"
BRANCH="${1:-main}"
LOGFILE="${STAGING_DIR}/deploy-$(date +%Y%m%d-%H%M%S).log"

log() { echo "[$(date '+%H:%M:%S')] $*" | tee -a "$LOGFILE"; }
fail() { log "❌ $*"; exit 1; }

log "=== Deploying TaskSync Pro to staging (branch: $BRANCH) ==="

# 1. Git pull
log "--- Fetching ${BRANCH} ---"
cd "$STAGING_DIR"
git fetch origin "$BRANCH"
git reset --hard "origin/$BRANCH"

# 2. Build frontend
log "--- Building frontend ---"
docker compose run --rm node-build npm ci 2>&1 | tee -a "$LOGFILE"
docker compose run --rm node-build npm run build 2>&1 | tee -a "$LOGFILE"

# 3. Build backend
log "--- Building backend ---"
docker compose build php-fpm 2>&1 | tee -a "$LOGFILE"

# 4. Start infrastructure
log "--- Starting infrastructure ---"
docker compose up -d postgres redis minio 2>&1 | tee -a "$LOGFILE"

# 5. Wait for PostgreSQL
log "--- Waiting for PostgreSQL ---"
for i in $(seq 1 30); do
  if docker compose exec -T postgres pg_isready -U tasksync &>/dev/null; then
    log "PostgreSQL ready"
    break
  fi
  sleep 2
done

# 6. Database backup (pre-migration)
log "--- Pre-deploy DB backup ---"
docker compose exec -T postgres pg_dump -U tasksync tasksync \
  > "${STAGING_DIR}/backups/pre-deploy-$(date +%Y%m%d-%H%M%S).sql" \
  || log "⚠️ Backup failed (non-fatal)"

# 7. Run migrations
log "--- Running migrations ---"
docker compose run --rm php-fpm php artisan migrate --force 2>&1 | tee -a "$LOGFILE" \
  || fail "Migration failed"

# 8. Start app services
log "--- Starting app services ---"
docker compose up -d php-fpm nginx reverb horizon 2>&1 | tee -a "$LOGFILE"

# 9. Health check
log "--- Health check ---"
for i in $(seq 1 12); do
  STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/api/v1/health 2>/dev/null || echo "000")
  if [ "$STATUS" = "200" ]; then
    log "✅ Health check passed (attempt $i)"
    break
  fi
  log "  Waiting... ($i/12)"
  sleep 5
done

if [ "$STATUS" != "200" ]; then
  log "❌ Health check failed — initiating rollback"
  # Rollback: restart previous containers
  docker compose stop nginx php-fpm reverb horizon
  docker compose up -d nginx php-fpm reverb horizon
  fail "Deploy rolled back — previous version restored"
fi

# 10. Optimize
log "--- Optimizing ---"
docker compose run --rm php-fpm php artisan optimize:clear 2>&1 | tee -a "$LOGFILE" || true
docker compose run --rm php-fpm php artisan storage:link 2>&1 | tee -a "$LOGFILE" || true

log "=== ✅ Deployment complete (branch: $BRANCH) ==="
log "Log: $LOGFILE"
