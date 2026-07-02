# Runbook — TaskSync Pro (SAAS-001)

> **Gate:** 8 · **Owner:** Naomi Brooks (Observability SRE) · **Date:** 2026-06-25
> **Purpose:** Standard operating procedures for common production incidents
> **Audience:** SRE on-call, DevOps lead, backend engineers

---

## Table of Contents

1. [503 Service Unavailable](#1-503-service-unavailable)
2. [High API Latency](#2-high-api-latency)
3. [Queue Backlog](#3-queue-backlog)
4. [WebSocket Disconnects](#4-websocket-disconnects)
5. [OOM Kill (Out of Memory)](#5-oom-kill-out-of-memory)
6. [DB Connection Pool Exhausted](#6-db-connection-pool-exhausted)
7. [Disk Full](#7-disk-full)
8. [High Error Rate (5xx)](#8-high-error-rate-5xx)
9. [SSL Certificate Expiry](#9-ssl-certificate-expiry)
10. [Redis Out of Memory](#10-redis-out-of-memory)

---

## 1. 503 Service Unavailable

### Detection
- Better Uptime → API Health check → 503
- Grafana alert: `http_requests_total{status="503"} > 0`
- Users report "Service Unavailable" on dashboard

### Diagnosis
```bash
# 1. Check Nginx is running
docker ps | grep tasksync-nginx
docker logs tasksync-nginx --tail 50

# 2. Check Nginx config
docker exec tasksync-nginx nginx -t

# 3. Check if Nginx can reach PHP-FPM
docker exec tasksync-nginx curl -f http://php-fpm-blue:9000/ping

# 4. Check PHP-FPM status
docker exec tasksync-php-fpm-blue php artisan health:check
docker logs tasksync-php-fpm-blue --tail 50

# 5. Check PHP-FPM pool
docker exec tasksync-php-fpm-blue sh -c "ps aux | grep php-fpm"

# 6. Check DB connectivity from PHP
docker exec tasksync-php-fpm-blue php artisan tinker --execute="DB::connection()->getPdo()"

# 7. Check Redis connectivity
docker exec tasksync-php-fpm-blue php artisan tinker --execute="Redis::connection()->ping()"
```

### Common Causes & Resolution

| Cause | Check | Resolution |
|-------|-------|------------|
| Nginx misconfig | `nginx -t` | Fix syntax error → reload: `docker exec tasksync-nginx nginx -s reload` |
| PHP-FPM crashed | `ps aux \| grep php-fpm` | Restart: `docker restart tasksync-php-fpm-blue` |
| PHP-FPM max_children exhausted | Check pool config | Increase `pm.max_children` in `docker/php.ini` → rebuild |
| DB connection refused | `pg_isready` from PHP container | Restart Postgres: `docker restart tasksync-postgres` |
| Disk full | `df -h` | Follow [Disk Full](#7-disk-full) runbook |
| Blue/Green switch failed | Check deploy script log | Switch traffic back: `docker exec tasksync-nginx nginx -s reload` |

### Verification
```bash
curl -f https://api.tasksyncpro.com/api/v1/health
# Expected: {"status":"ok","timestamp":"..."}
curl -f https://app.tasksyncpro.com
# Expected: 200 + SPA HTML
```

### Postmortem
- Capture Nginx access logs: `docker logs tasksync-nginx --tail 200 > /tmp/nginx-503-$(date +%s).log`
- Capture PHP-FPM slow log if `request_slowlog_timeout` is set
- Update runbook if root cause was undocumented

---

## 2. High API Latency

### Detection
- Grafana alert: `P95 latency >500ms for 5m`
- Sentry performance: transaction duration spike
- User complaints: "app feels slow"

### Diagnosis
```bash
# 1. Check slow queries in PostgreSQL
docker exec tasksync-postgres psql -U tasksync -c "
SELECT pid, now() - pg_stat_activity.query_start AS duration,
       query, state
FROM pg_stat_activity
WHERE state != 'idle'
  AND query NOT LIKE '%pg_stat%'
ORDER BY duration DESC
LIMIT 10;"

# 2. Check query execution plan (if slow query identified)
docker exec tasksync-postgres psql -U tasksync -c "EXPLAIN (ANALYZE, BUFFERS) <slow_query>;"

# 3. Check Redis cache hit rate
docker exec tasksync-redis redis-cli -a $REDIS_PASSWORD info stats | grep -E "(keyspace_hits|keyspace_misses)"

# 4. Check cache hit ratio in Grafana
# Panel: Redis Hit Rate = hits / (hits + misses) * 100

# 5. Check DB connection count
docker exec tasksync-postgres psql -U tasksync -c "SELECT count(*) FROM pg_stat_activity;"

# 6. Check CPU/memory usage
docker stats --no-stream tasksync-php-fpm-blue tasksync-postgres tasksync-redis

# 7. Check Nginx upstream response times
tail -100 /var/log/nginx/api.tasksyncpro.com.access.log | awk '{print $NF}' | sort -n | tail -10
```

### Common Causes & Resolution

| Cause | Check | Resolution |
|-------|-------|------------|
| Missing DB index | `EXPLAIN ANALYZE` shows seq scan | Add missing index from PERFORMANCE_REPORT.md recommendations |
| Cache miss storm | Redis hit rate <80% | Check cache invalidation logic; warm cache if needed |
| DB connection pool full | `pg_stat_activity > 50` | Follow [DB Connection Pool Exhausted](#6-db-connection-pool-exhausted) |
| N+1 queries | Sentry spans show repeated queries | Eager load relationships in controller |
| Lock contention | `pg_locks` shows waiting | Kill blocking session: `SELECT pg_terminate_backend(pid)` |
| PHP slow script | `request_slowlog_timeout` logs | Optimize service method; add caching |

### Resolution Steps

```bash
# Immediate: Kill long-running queries
docker exec tasksync-postgres psql -U tasksync -c "
SELECT pg_terminate_backend(pid)
FROM pg_stat_activity
WHERE state != 'idle'
  AND query_start < now() - interval '5 minutes'
  AND query NOT LIKE '%pg_stat%';"

# Short-term: Add missing index
docker exec tasksync-postgres psql -U tasksync -c "
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_tasks_assignee_status
ON tasks (assignee_id, status) WHERE assignee_id IS NOT NULL;"

# Add cache for hot endpoint
# In Laravel service:
$result = Cache::remember("endpoint:".md5($request->fullUrl()), 60, function () {
    return $data;
});

# Long-term: Scale resources
# Increase PHP-FPM max_children in docker/php.ini
# Add read replica if DB CPU >60%
```

### Verification
```bash
# Check latency after fix
curl -w "@curl-format.txt" -o /dev/null -s https://api.tasksyncpro.com/api/v1/tasks?per_page=1
# Target: <150ms P95
```

---

## 3. Queue Backlog

### Detection
- Grafana alert: `laravel_queue_size > 100 for 2m`
- Horizon dashboard shows backlog >100
- Users report: "notifications delayed" or "report not generated"

### Diagnosis
```bash
# 1. Check Horizon status
docker exec tasksync-horizon php artisan horizon:status

# 2. Check failed jobs
docker exec tasksync-horizon php artisan horizon:failed-list

# 3. Check queue sizes per connection
docker exec tasksync-redis redis-cli -a $REDIS_PASSWORD --scan --pattern 'queues:*'

# 4. Check queue length
docker exec tasksync-redis redis-cli -a $REDIS_PASSWORD LLEN queues:default

# 5. Check Horizon worker count
docker exec tasksync-horizon php artisan horizon:work --once  # Test worker processing

# 6. Check worker logs
docker logs tasksync-horizon --tail 50

# 7. Check Redis memory
docker exec tasksync-redis redis-cli -a $REDIS_PASSWORD info memory | grep used_memory_human
```

### Common Causes & Resolution

| Cause | Check | Resolution |
|-------|-------|------------|
| Workers crashed | `horizon:status` → inactive | Restart: `docker restart tasksync-horizon` |
| Too few workers | Horizon config `workers` | Increase workers per queue in `config/horizon.php` |
| Slow job processing | `horizon_job_processed_duration > 30s` | Optimize job code; offload to job chaining |
| Failed jobs blocking | `horizon:failed-list` has entries | Fix job error → retry: `php artisan horizon:retry <id>` |
| Redis queue full | `redis_memory_used_bytes > 80%` | Flush completed jobs; scale Redis memory |

### Resolution Steps

```bash
# 1. Inspect failed job details
docker exec tasksync-horizon php artisan horizon:failed-list

# 2. Retry failed jobs (after fixing the issue)
docker exec tasksync-horizon php artisan horizon:retry all

# 3. Purge failed jobs (if unrecoverable)
docker exec tasksync-horizon php artisan horizon:forget <id>

# 4. Scale workers (temporary)
docker exec tasksync-horizon php artisan horizon:work --queue=high,default,low --sleep=1

# 5. If Horizon supervisor needs restart
docker compose -f docker-compose.prod.yml restart horizon

# 6. Increase worker count permanently (config/horizon.php)
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['high', 'default', 'low'],
            'balance' => 'auto',
            'minProcesses' => 3,
            'maxProcesses' => 15,    // Increased from 10
            'tries' => 3,
            'timeout' => 60,
        ],
    ],
],
```

### Verification
```bash
# Check queue draining
watch -n 5 'docker exec tasksync-redis redis-cli -a $REDIS_PASSWORD LLEN queues:default'

# Check horizon status
docker exec tasksync-horizon php artisan horizon:status
# Expected: Horizon running, 0 failed jobs
```

---

## 4. WebSocket Disconnects

### Detection
- Grafana alert: `reverb_connections_active` drops suddenly
- Users report: "board not updating", "changes not syncing"
- Sentry: `WebSocket connection failed` errors

### Diagnosis
```bash
# 1. Check Reverb status
docker logs tasksync-reverb --tail 50

# 2. Check active connections
curl http://localhost:6001/health
# Expected: {"status":"ok","connections":XX}

# 3. Check Redis pub/sub
docker exec tasksync-redis redis-cli -a $REDIS_PASSWORD pubsub channels

# 4. Check client connection logs
docker exec tasksync-reverb php artisan reverb:status

# 5. Check Nginx WebSocket proxy
docker logs tasksync-nginx --tail 50 | grep -i websocket

# 6. Check for connection limits
docker stats tasksync-reverb --no-stream
```

### Common Causes & Resolution

| Cause | Check | Resolution |
|-------|-------|------------|
| Reverb crashed | `docker ps` → Reverb not running | `docker restart tasksync-reverb` |
| Redis pub/sub broken | `pubsub channels` empty | Restart Redis: `docker restart tasksync-redis` |
| Nginx proxy timeout | Proxy read timeout <24h | Increase `proxy_read_timeout` in nginx config |
| Connection limit hit | `reverb_connections_active` = max | Increase `reverb.config.max_connections` |
| Client auth failure | Check Echo `auth.endpoint` logs | Verify Sanctum token; check `BroadcastServiceProvider` |
| Network partition | Clients time out | Check server firewall; Cloudflare status |

### Resolution Steps

```bash
# 1. Restart Reverb
docker restart tasksync-reverb

# 2. Verify Reverb health
curl -f http://localhost:6001/health

# 3. Check Redis connection from Reverb
docker exec tasksync-reverb php artisan tinker --execute="
    \$redis = new Redis();
    \$redis->connect('redis', 6379);
    echo \$redis->ping();
"

# 4. Check Broadcast routes
docker exec tasksync-php-fpm-blue php artisan route:list | grep broadcasting

# 5. If persistent: increase Reverb workers
# config/reverb.php
'servers' => [
    'reverb' => [
        'host' => '0.0.0.0',
        'port' => 6001,
        'options' => [
            'max_connections' => 100,  // Increased from default
        ],
    ],
],
```

### Verification
```bash
# Test WebSocket connection
wscat -c wss://ws.tasksyncpro.com/app?auth_token=<token>

# Monitor connections
watch -n 5 'curl -s http://localhost:6001/health | jq .'
```

---

## 5. OOM Kill (Out of Memory)

### Detection
- Docker container exits with code 137
- `docker logs <container>` shows "Killed"
- Grafana: memory usage spike then flatline

### Diagnosis
```bash
# 1. Check which container was killed
docker ps -a | grep -E "(exited|oom)"

# 2. Check OOM score
dmesg | grep -i "oom\|killed" | tail -20

# 3. Check memory usage trend
docker stats --no-stream --all

# 4. Check container resource limits
docker inspect tasksync-php-fpm-blue | jq '.[].HostConfig.Memory'

# 5. Check PHP memory limit
docker exec tasksync-php-fpm-blue php -i | grep memory_limit
```

### Common Causes & Resolution

| Cause | Check | Resolution |
|-------|-------|------------|
| PHP-FPM memory leak | `pm.max_requests` unlimited | Set `pm.max_requests = 500` in php.ini |
| Memory limit too low | Container limit < PHP memory_limit | Increase container `memory: 512M` → `768M` |
| Memory leak in worker | Horizon worker memory grows | Set `--memory=128` in horizon worker config |
| PostgreSQL buffer full | `shared_buffers > available RAM` | Reduce `shared_buffers` or add swap |

### Resolution Steps

```bash
# 1. Restart the killed container
docker restart tasksync-php-fpm-blue

# 2. Check for memory leak
docker stats tasksync-php-fpm-blue --no-stream --format "{{.MemUsage}}"

# 3. Increase container memory limit
# Edit docker-compose.prod.yml for affected service
deploy:
  resources:
    limits:
      memory: 768M   # Increased from 512M
    reservations:
      memory: 384M

# 4. Add swap (temporary)
fallocate -l 2G /swapfile
chmod 600 /swapfile
mkswap /swapfile
swapon /swapfile

# 5. Apply changes
docker compose -f docker-compose.prod.yml up -d <service>
```

### Verification
```bash
docker stats --no-stream
# Check memory < 80% of limit
```

---

## 6. DB Connection Pool Exhausted

### Detection
- Grafana alert: `pg_stat_activity_count > 50 for 2m`
- Application logs: `PDOException: could not find driver` or `Connection refused`
- Sentry: `DATABASE` errors spike

### Diagnosis
```bash
# 1. Count active connections
docker exec tasksync-postgres psql -U tasksync -c "
SELECT count(*) FROM pg_stat_activity;"

# 2. Show connection detail
docker exec tasksync-postgres psql -U tasksync -c "
SELECT pid, state, application_name, query_start,
       wait_event_type, wait_event, query
FROM pg_stat_activity
WHERE state != 'idle'
ORDER BY query_start;"

# 3. Check max_connections setting
docker exec tasksync-postgres psql -U tasksync -c "SHOW max_connections;"

# 4. Check idle connections in transaction
docker exec tasksync-postgres psql -U tasksync -c "
SELECT count(*) FROM pg_stat_activity
WHERE state = 'idle in transaction';"

# 5. Check PHP-FPM pool connections
docker exec tasksync-php-fpm-blue php artisan tinker --execute="
    echo DB::connection()->getConfig('max_connections');"
```

### Common Causes & Resolution

| Cause | Check | Resolution |
|-------|-------|------------|
| Too many PHP-FPM children | `pm.max_children` > DB max_connections | Reduce `pm.max_children` to match DB pool |
| Connections not released | `idle in transaction` > 0 | Set `pgbouncer` transaction pooling |
| Long-running queries | `query_start > 5min` | Kill with `pg_terminate_backend` |
| Connection leak | `application_name` reveals culprit | Fix connection not returned in service code |

### Resolution Steps

```bash
# 1. Kill idle-in-transaction connections
docker exec tasksync-postgres psql -U tasksync -c "
SELECT pg_terminate_backend(pid)
FROM pg_stat_activity
WHERE state = 'idle in transaction'
  AND query_start < now() - interval '5 minutes';"

# 2. Kill long-running queries
docker exec tasksync-postgres psql -U tasksync -c "
SELECT pg_terminate_backend(pid)
FROM pg_stat_activity
WHERE state != 'idle'
  AND query_start < now() - interval '10 minutes';"

# 3. Reduce PHP-FPM max_children
# docker/php.ini
pm.max_children = 15   # Reduced from 20
pm.max_spare_servers = 8

# 4. Add PgBouncer connection pooler (long-term)
# docker-compose.prod.yml
services:
  pgbouncer:
    image: edoburu/pgbouncer:1.22
    environment:
      DB_USER: tasksync
      DB_PASSWORD: ${PRODUCTION_DB_PASSWORD}
      DB_HOST: postgres
      POOL_MODE: transaction
      MAX_CLIENT_CONN: 100
      DEFAULT_POOL_SIZE: 25
    depends_on:
      - postgres
```

### Verification
```bash
docker exec tasksync-postgres psql -U tasksync -c "SELECT count(*) FROM pg_stat_activity;"
# Expected: < 25 connections
```

---

## 7. Disk Full

### Detection
- Grafana alert: `node_filesystem_avail_bytes < 5GB`
- Docker: containers fail to start with "no space left on device"
- DB backup fails

### Diagnosis
```bash
# 1. Check disk usage
df -h

# 2. Find largest directories
du -sh /var/lib/docker 2>/dev/null
du -sh /var/log 2>/dev/null
du -sh /home/* 2>/dev/null

# 3. Check Docker disk usage
docker system df

# 4. Check PostgreSQL data size
docker exec tasksync-postgres psql -U tasksync -c "
SELECT pg_database_size('tasksync')/1024/1024 AS db_size_mb;"

# 5. Check log sizes
du -sh /var/lib/docker/containers/*/*-json.log 2>/dev/null | sort -rh | head -10
```

### Common Causes & Resolution

| Cause | Check | Resolution |
|-------|-------|------------|
| Docker container logs | `du -sh containers/*/*.log` | `docker system prune --all --volumes` or limit log size |
| Old Docker images | `docker images` | Remove unused: `docker image prune -a` |
| PostgreSQL WAL files | `pg_wal` directory size | Increase `wal_keep_size`; enable WAL archiving to S3 |
| Application logs | `storage/logs/*.log` | Compress/rotate: `logrotate` config |
| Uploads directory | `du -sh /var/www/storage/app` | Move old uploads to S3; set lifecycle policy |

### Resolution Steps

```bash
# 1. Clean Docker resources
docker system prune -a --volumes -f

# 2. Truncate Docker logs if log driver not set
truncate -s 0 /var/lib/docker/containers/*/*-json.log

# 3. Set log rotation on all services
# docker-compose.prod.yml — add to every service:
logging:
  driver: "json-file"
  options:
    max-size: "10m"
    max-file: "3"

# 4. Rotate Laravel logs
docker exec tasksync-php-fpm-blue php artisan log:clear
# or via logrotate:
cat > /etc/logrotate.d/tasksync << EOF
/var/www/storage/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    copytruncate
}
EOF

# 5. Move old DB backups to S3 (if on local disk)
# Add to deploy/production/backup.sh:
pg_dump tasksync | gzip > /tmp/backup-$(date +%Y%m%d).sql.gz
aws s3 cp /tmp/backup-*.sql.gz s3://tasksync-backups/

# 6. Increase storage (if persistent)
# Increase volume size in provider console → resize filesystem
df -h  # Verify new size
```

### Verification
```bash
df -h /
# Expected: usage <70%
```

---

## 8. High Error Rate (5xx)

### Detection
- Grafana alert: `error_rate > 1% for 5m`
- Sentry: error group spike
- Better Uptime: HTTP 5xx responses

### Diagnosis
```bash
# 1. Check Sentry for most frequent error
# Look at Sentry dashboard → Issues → sort by count

# 2. Check Nginx error rate
docker exec tasksync-nginx sh -c "tail -100 /var/log/nginx/error.log"

# 3. Check PHP-FPM error log
docker exec tasksync-php-fpm-blue sh -c "cat /var/log/php8.3-fpm.log"

# 4. Check Laravel error log
docker exec tasksync-php-fpm-blue sh -c "tail -50 /var/www/storage/logs/laravel.log"

# 5. Check for recent deploys
git log --oneline -5

# 6. Check 3rd party API health
# WhatsApp, Mailgun, FCM status pages
```

### Common Causes & Resolution

| Cause | Check | Resolution |
|-------|-------|------------|
| Recent deploy regression | Compare error rate before/after deploy | Rollback: `bash deploy/production/rollback.sh` |
| Broken 3rd party API | Integration error in logs | Disconnect integration → log error → re-enable after fix |
| Rate limit hit | `429 Too Many Requests` | Increase rate limit; check for abusive client |
| Validation error | `422 Unprocessable Entity` in logs | Fix frontend validation; add server-side handling |

### Resolution Steps

```bash
# 1. Rollback if deploy-related
bash deploy/production/rollback.sh

# 2. Disable failing integration (if 3rd party)
# Set env var to disable:
docker exec tasksync-php-fpm-blue php artisan tinker --execute="
    config(['services.whatsapp.enabled' => false]);"
# Or use feature flag

# 3. Increase rate limits temporarily
# config/limiter.php
RateLimiter::for('api', fn ($job) => Limit::perMinute(600)); // Temporarily 600
```

### Verification
```bash
# Check error rate in Grafana
# Expected: <1% of all requests
```

---

## 9. SSL Certificate Expiry

### Detection
- Better Uptime alert: "SSL cert expires in <14 days"
- Grafana alert: `certificate_expiry_days < 14`
- Browser warning: "Connection not secure"

### Diagnosis
```bash
# 1. Check cert expiry via OpenSSL
echo | openssl s_client -servername tasksyncpro.com -connect tasksyncpro.com:443 2>/dev/null \
  | openssl x509 -noout -enddate

# 2. Check cert expiry for all subdomains
for domain in tasksyncpro.com api.tasksyncpro.com app.tasksyncpro.com ws.tasksyncpro.com; do
  echo -n "$domain: "
  echo | openssl s_client -servername $domain -connect $domain:443 2>/dev/null \
    | openssl x509 -noout -enddate
done

# 3. Check cert provider
# Cloudflare: auto-renew (14d before expiry)
# Let's Encrypt: certbot renew --dry-run
```

### Resolution

#### Cloudflare (auto-managed)
```bash
# Cloudflare auto-renews SSL — verify settings:
# SSL/TLS → Edge Certificates → "Always Use HTTPS" ON
# Automatic HTTPS Rewrites ON
# Certificate Transparency Monitoring ON
```

#### Let's Encrypt (self-managed)
```bash
# Renew
certbot renew

# If renewal fails
certbot certonly --standalone -d tasksyncpro.com -d api.tasksyncpro.com -d app.tasksyncpro.com -d ws.tasksyncpro.com

# Reload Nginx
docker exec tasksync-nginx nginx -s reload

# Set up auto-renewal cron
echo "0 0 * * * certbot renew --quiet && docker exec tasksync-nginx nginx -s reload" | crontab -
```

### Verification
```bash
echo | openssl s_client -servername tasksyncpro.com -connect tasksyncpro.com:443 2>/dev/null \
  | openssl x509 -noout -enddate
# Expected: >30 days remaining
```

---

## 10. Redis Out of Memory

### Detection
- Grafana alert: `redis_memory_used_bytes > 85% of max`
- Redis evictions: `redis_evicted_keys_total` rising
- Application: cache misses, slow responses

### Diagnosis
```bash
# 1. Check Redis memory usage
docker exec tasksync-redis redis-cli -a $REDIS_PASSWORD info memory | grep -E "(used_memory_human|maxmemory_human|evicted_keys)"

# 2. Check largest keys
docker exec tasksync-redis redis-cli -a $REDIS_PASSWORD --bigkeys

# 3. Check TTL distribution
docker exec tasksync-redis redis-cli -a $REDIS_PASSWORD info keyspace

# 4. Check Horizon queue backlog (Redis memory consumer)
docker exec tasksync-redis redis-cli -a $REDIS_PASSWORD LLEN queues:default
```

### Resolution Steps

```bash
# 1. Evict old cache keys (if policy allows)
# Already set to allkeys-lru in docker-compose.prod.yml

# 2. Clear specific cache tags
docker exec tasksync-php-fpm-blue php artisan tinker --execute="
    Cache::tags(['report:*'])->flush();
    echo 'Report cache cleared' . PHP_EOL;
"

# 3. Flush Redis (if queue is empty — WARNING: clears all cache)
# Only if absolutely needed during low traffic
docker exec tasksync-redis redis-cli -a $REDIS_PASSWORD FLUSHALL

# 4. Increase Redis maxmemory
# docker-compose.prod.yml — redis service
command: redis-server --requirepass ${PRODUCTION_REDIS_PASSWORD} --maxmemory 768mb

# 5. Set more aggressive eviction
command: redis-server --requirepass ${PRODUCTION_REDIS_PASSWORD} --maxmemory-policy allkeys-lfu

# 6. Reduce cache TTLs in Laravel config
# config/cache.php: reduce default TTL from 3600 to 1800
```

### Verification
```bash
docker exec tasksync-redis redis-cli -a $REDIS_PASSWORD info memory | grep used_memory_human
# Expected: <80% of max
```

---

## Incident Response Template

For every incident, capture:

```
## Incident Report

**Date:** YYYY-MM-DD HH:MM UTC
**Duration:** X minutes
**Severity:** Critical/Warning
**Component:** API/Queue/DB/WebSocket/Redis
**Detected by:** Grafana Alert / Better Uptime / User Report
**Ticket:** #GH-XXXX

### Timeline
- HH:MM — Alert fired
- HH:MM — SRE acknowledged
- HH:MM — Root cause identified
- HH:MM — Fix applied
- HH:MM — Verified healthy
- HH:MM — Postmortem started

### Root Cause
Brief description of what failed and why.

### Resolution
Steps taken to fix.

### Prevention
- [ ] Action item 1
- [ ] Action item 2

### SLO Impact
- Error budget consumed: X%
- Any SLO violation? Yes/No
```

---

*Generated by Naomi Brooks (Observability SRE) · Gate 8 · 2026-06-25*
*Next: Update runbook with post-incident learnings*
