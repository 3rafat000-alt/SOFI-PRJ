# Staging Access — TaskSync Pro (SAAS-001)

> **URL:** https://staging.tasksyncpro.com
> **Server:** Ubuntu 24.04, 2 vCPU, 4GB RAM, 50GB SSD
> **Deployed via:** Docker Compose (7 services)
> **Last updated:** 2026-06-25

---

## 1. SSH Access

```bash
# Connect to staging server
ssh -i ~/.ssh/tasksync-staging.pem deploy@staging.tasksyncpro.com

# Once connected, project lives at:
cd /opt/tasksync
```

### SSH Key Setup

Add your public key to `~deploy/.ssh/authorized_keys` on the staging server:

```bash
ssh-copy-id -i ~/.ssh/id_ed25519.pub deploy@staging.tasksyncpro.com
```

---

## 2. Docker Compose Commands

All commands run from `/opt/tasksync` on the staging server.

### View running services

```bash
docker compose ps
```

### Start all services

```bash
docker compose up -d
```

### Stop all services

```bash
docker compose down
```

### Stop and remove volumes (⚠️ destroys data)

```bash
docker compose down -v
```

### Restart a single service

```bash
docker compose restart nginx
docker compose restart php-fpm
docker compose restart reverb
```

### Rebuild a service

```bash
docker compose build php-fpm
docker compose up -d php-fpm --force-recreate
```

---

## 3. Viewing Logs

### All services

```bash
docker compose logs --tail=100 -f
```

### Specific service

```bash
docker compose logs --tail=50 -f php-fpm
docker compose logs --tail=50 -f nginx
docker compose logs --tail=50 -f reverb
docker compose logs --tail=50 -f horizon
docker compose logs --tail=50 -f postgres
docker compose logs --tail=50 -f redis
```

### Laravel log (inside container)

```bash
docker compose exec php-fpm tail -f /var/www/storage/logs/laravel.log
```

### Deployment logs

```bash
ls -la /opt/tasksync/deploy-*.log
tail -f /opt/tasksync/deploy-$(ls -t /opt/tasksync/deploy-*.log | head -1 | xargs basename)
```

---

## 4. Running Artisan Commands

```bash
# Run migrations
docker compose run --rm php-fpm php artisan migrate --force

# Rollback last migration batch
docker compose run --rm php-fpm php artisan migrate:rollback --force

# Clear cache
docker compose run --rm php-fpm php artisan optimize:clear

# Cache config/routes/views (production)
docker compose run --rm php-fpm php artisan optimize

# Run seeder (staging only)
docker compose run --rm php-fpm php artisan db:seed --force

# Create storage symlink
docker compose run --rm php-fpm php artisan storage:link

# List routes
docker compose run --rm php-fpm php artisan route:list

# Tinker (interactive shell)
docker compose exec php-fpm php artisan tinker
```

---

## 5. Horizon Dashboard

Laravel Horizon monitors queue workers. Access restricted to admin users.

### Check Horizon status

```bash
docker compose logs horizon --tail=20
```

### Horizon metrics (via API if enabled)

```
GET https://staging.tasksyncpro.com/horizon/status
```

### Restart Horizon

```bash
docker compose restart horizon
```

---

## 6. WebSocket (Laravel Reverb)

Reverb runs on port 6001 and is proxied through Nginx at `/broadcasting/`.

### Check Reverb is running

```bash
docker compose ps reverb
docker compose logs reverb --tail=10
```

### Test WebSocket connection

Use `wscat` or a browser console:

```bash
# From the server
npm install -g wscat
wscat -c wss://staging.tasksyncpro.com/broadcasting/ -H "Host: staging.tasksyncpro.com"
```

### Expected connection flow

1. Client connects to `wss://staging.tasksyncpro.com/broadcasting/`
2. Nginx upgrades connection and proxies to Reverb on port 6001
3. Client authenticates via `echo` private channel handshake
4. Connection stays open (up to 24h with heartbeat every 10s)

---

## 7. Database Access

### From the server

```bash
# Direct psql access (inside container)
docker compose exec postgres psql -U tasksync -d tasksync

# Run a query
docker compose exec postgres psql -U tasksync -d tasksync -c "SELECT count(*) FROM users;"
```

### Database backup

```bash
# Manual backup
docker compose exec -T postgres pg_dump -U tasksync tasksync > /opt/tasksync/backups/manual-$(date +%Y%m%d-%H%M%S).sql

# Restore from backup
cat /opt/tasksync/backups/backup-file.sql | docker compose exec -T postgres psql -U tasksync -d tasksync
```

---

## 8. S3 Storage (MinIO)

MinIO Console is accessible internally (not exposed publicly).

### Access MinIO Console

```bash
# Port-forward from local machine
ssh -L 9001:localhost:9001 deploy@staging.tasksyncpro.com
# Then open http://localhost:9001 in browser
```

### MinIO credentials

Set via environment variables `MINIO_ROOT_USER` and `MINIO_ROOT_PASSWORD` in `.env`.

---

## 9. Health Checks

| Service | Endpoint | Expected |
|---------|----------|----------|
| Laravel API | `GET /api/v1/health` | `{"status":"ok","timestamp":"..."}` |
| Nginx | `GET /nginx-health` | `healthy` |
| PostgreSQL | internal `pg_isready` | accepting connections |
| Redis | internal `redis-cli ping` | `PONG` |
| PHP-FPM | internal port 9000 `/ping` | 200 OK |

### Quick health check from server

```bash
curl -s http://localhost/api/v1/health | jq .
curl -s http://localhost/nginx-health
```

---

## 10. SSL Certificate (Let's Encrypt)

### Check expiry

```bash
docker compose run --rm certbot certificates
```

### Manual renewal

```bash
docker compose run --rm certbot renew
docker compose exec nginx nginx -s reload
```

### Auto-renewal

Cron job runs daily at 3:00 AM:

```bash
0 3 * * * docker compose run --rm certbot renew && docker compose exec nginx nginx -s reload
```

---

## 11. Troubleshooting

### Container won't start

```bash
# Check logs
docker compose logs <service>

# Inspect container
docker compose ps -a

# Rebuild and force recreate
docker compose build <service>
docker compose up -d <service> --force-recreate
```

### Health check failing

```bash
# Check if PHP-FPM is responding
curl -s http://localhost:9000/ping

# Check Nginx config syntax
docker compose exec nginx nginx -t

# Restart Nginx
docker compose restart nginx
```

### Database connection issues

```bash
# Verify PostgreSQL is accepting connections
docker compose exec postgres pg_isready -U tasksync

# Check PostgreSQL logs
docker compose logs postgres --tail=30
```

### Out of disk space

```bash
# Check disk usage
df -h

# Prune unused Docker objects
docker system prune -af

# Remove old deploy logs
rm /opt/tasksync/deploy-*.log
```
