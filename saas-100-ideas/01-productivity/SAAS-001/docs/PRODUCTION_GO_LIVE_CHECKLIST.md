# PRODUCTION GO-LIVE CHECKLIST — TaskSync Pro (SAAS-001)

> **Owner:** Linda Schmidt (sofi-devops-cloud-lead) · **Date:** 2026-06-25
> **Domain:** tasksyncpro.com · **Server:** Ubuntu 24.04, 2 vCPU, 4GB RAM, 50GB NVMe SSD
> **Target Environment:** Production · **Gate:** 7

---

## 1. DNS Configuration

### 1.1 DNS Records (tasksyncpro.com)

Point your domain registrar's DNS to the production server IP or Cloudflare for proxied DNS.

| Record Type | Name | Value | TTL | Notes |
|-------------|------|-------|-----|-------|
| **A** | `@` (root) | `<PRODUCTION_SERVER_IP>` | 300s | Main application served via Nginx |
| **AAAA** | `@` (root) | `<PRODUCTION_IPv6>` | 300s | IPv6 support (if available) |
| **CNAME** | `www` | `tasksyncpro.com` | 300s | Redirect www → root |
| **CNAME** | `api` | `tasksyncpro.com` | 300s | API subdomain (same server) |
| **CNAME** | `ws` | `tasksyncpro.com` | 300s | WebSocket (Reverb via same Nginx) |
| **CNAME** | `cdn` | `tasksyncpro.com` | 300s | R2 public URL alias (or point to Cloudflare) |
| **MX** | `@` | `mx.mailgun.org` (or SES) | 3600s | Email delivery |
| **TXT** | `@` | `"v=spf1 include:mailgun.org ~all"` | 3600s | SPF for email |
| **TXT** | `_dmarc` | `"v=DMARC1; p=quarantine; rua=mailto:dmarc@tasksyncpro.com"` | 3600s | DMARC policy |

> **Important:** Use **Cloudflare proxied (orange cloud)** DNS for DDoS protection, WAF, and SSL termination. If not using Cloudflare, ensure TTL is low (300s) during cutover, then increase to 3600s post-launch.

### 1.2 Cloudflare Configuration

| Setting | Value |
|---------|-------|
| **SSL/TLS encryption** | Full (Strict) — requires valid cert on origin |
| **Always Use HTTPS** | ON |
| **HSTS** | ON — max-age=31536000, includeSubDomains, preload |
| **Minimum TLS Version** | 1.2 |
| **Brotli** | ON |
| **Auto Minify** | ON (HTML, CSS, JS) |
| **Caching Level** | Standard |
| **Edge Cache TTL** | 2 hours (default) |

### 1.3 Cloudflare WAF Rules

| Rule Name | Field | Operator | Value | Action | Priority |
|-----------|-------|----------|-------|--------|----------|
| Rate Limit — API | IP | Rate limit | 300 req/60s | Block with 429 | 1 |
| Rate Limit — Auth | IP | Rate limit | 20 req/60s | Block with 429 | 2 |
| Block SQLi | URI + Body | WAF Managed | SQLi | Block | 3 |
| Block XSS | URI + Body | WAF Managed | XSS | Block | 4 |
| Block path traversal | URI | Contains | `../` | Block | 5 |
| Allow health check | URI | Equals | `/api/v1/health` | Skip | 6 |
| Bot Fight Mode | — | — | — | ON | — |

---

## 2. SSL Certificate Setup

### 2.1 Let's Encrypt — Wildcard Certificate

```bash
# Install Certbot on production server
sudo apt update && sudo apt install -y certbot

# Request wildcard cert (requires DNS challenge — Cloudflare DNS plugin recommended)
sudo certbot certonly --manual --preferred-challenges dns \
  -d '*.tasksyncpro.com' -d tasksyncpro.com \
  --agree-tos --email admin@tasksyncpro.com \
  --non-interactive

# For automated renewal with Cloudflare DNS plugin:
# 1. Install certbot-dns-cloudflare
sudo apt install -y python3-certbot-dns-cloudflare

# 2. Create Cloudflare API credentials
mkdir -p ~/.secrets/certbot
cat > ~/.secrets/certbot/cloudflare.ini << 'EOF'
dns_cloudflare_api_token = <CLOUDFLARE_API_TOKEN>
EOF
chmod 600 ~/.secrets/certbot/cloudflare.ini

# 3. Request wildcard cert with DNS automation
sudo certbot certonly --dns-cloudflare \
  --dns-cloudflare-credentials ~/.secrets/certbot/cloudflare.ini \
  -d '*.tasksyncpro.com' -d tasksyncpro.com \
  --agree-tos --email admin@tasksyncpro.com \
  --non-interactive

# 4. Verify cert issuance
sudo certbot certificates
# Expected output:
#   Certificate Name: tasksyncpro.com
#     Domains: *.tasksyncpro.com tasksyncpro.com
#     Expiry Date: 2026-09-23 (90 days)
#     Certificate Path: /etc/letsencrypt/live/tasksyncpro.com/fullchain.pem
#     Private Key Path: /etc/letsencrypt/live/tasksyncpro.com/privkey.pem
```

### 2.2 Auto-Renewal Setup

```bash
# Test renewal
sudo certbot renew --dry-run

# Add cron for daily renewal check (certbot auto-renews within 30 days of expiry)
echo "0 3 * * * root certbot renew --quiet --deploy-hook 'docker exec tasksync-nginx nginx -s reload'" \
  | sudo tee /etc/cron.d/certbot-renew

# Verify the cron job
sudo cat /etc/cron.d/certbot-renew
```

### 2.3 Mount Certs in Nginx Container

The Nginx container expects certs at:
- `/etc/letsencrypt/live/tasksyncpro.com/fullchain.pem`
- `/etc/letsencrypt/live/tasksyncpro.com/privkey.pem`

Add to `docker-compose.prod.yml` → nginx service → volumes:
```yaml
volumes:
  - /etc/letsencrypt:/etc/letsencrypt:ro
```

---

## 3. Pre-Flight Checklist

To be completed **before** the production deployment window:

| # | Item | Check | Verified By |
|---|------|-------|-------------|
| P1 | DNS A record propagated (dig tasksyncpro.com returns server IP) | □ | |
| P2 | Reverse DNS (PTR) configured for server IP | □ | |
| P3 | SSL wildcard certificate issued and valid (`openssl s_client -connect tasksyncpro.com:443`) | □ | |
| P4 | Server provisioned: Ubuntu 24.04, 2 vCPU, 4GB RAM, 50GB SSD | □ | |
| P5 | Server security: UFW firewall (22/tcp from office IP, 80/tcp, 443/tcp, 6001/tcp from Cloudflare) | □ | |
| P6 | Server security: fail2ban installed, SSH key-only auth, root login disabled | □ | |
| P7 | Docker Engine + Compose plugin installed on server | □ | |
| P8 | Git repository cloned to `/opt/tasksync` on production server | □ | |
| P9 | `.env.production` populated with all real secrets (not placeholders) | □ | |
| P10 | R2 bucket `tasksync-prod` created, API token with read/write permissions | □ | |
| P11 | PostgreSQL database `tasksync` created, user `tasksync` granted all privileges | □ | |
| P12 | Redis accessible, password set, maxmemory configured | □ | |
| P13 | Database migrations run against fresh production DB (`php artisan migrate --force`) | □ | |
| P14 | Initial admin user seeded (`php artisan db:seed --class=AdminUserSeeder`) | □ | |
| P15 | Horizon config updated for production (3 queue tiers, balance=auto) | □ | |
| P16 | Sentry project created, DSN added to `.env.production` | □ | |
| P17 | Sentry release tracking configured | □ | |
| P18 | Slack webhook configured for deploy notifications + error alerts | □ | |
| P19 | Mailgun/SES domain verified, SPF/DKIM/DMARC records in DNS | □ | |
| P20 | WhatsApp Cloud API: phone number, template approved, webhook configured | □ | |
| P21 | Stripe webhook endpoint set: `https://api.tasksyncpro.com/stripe/webhook` | □ | |
| P22 | Uptime monitoring configured (e.g., UptimeRobot — check every 5min) | □ | |
| P23 | Database backup cron: `0 2 * * * pg_dump ... > /backups/daily-$(date +%Y%m%d).sql` | □ | |
| P24 | Backup retention policy: daily (7 days), weekly (4 weeks), monthly (12 months) | □ | |
| P25 | Log rotation configured (docker log via logrotate or `docker logs --max-size`) | □ | |
| P26 | Server monitoring: Prometheus Node Exporter or equivalent | □ | |
| P27 | Cloudflare WAF rules deployed (rate limiting, SQLi/XSS blocking) | □ | |
| P28 | UAT sign-off received from stakeholders (see docs/UAT_PLAN.md) | □ | |

---

## 4. Deployment Window

### 4.1 Deployment Steps

```bash
# ═══════════════════════════════════════════════════════════════════════
# PHASE 1: Server Access & Code
# ═══════════════════════════════════════════════════════════════════════

# 1. SSH into production server
ssh deploy@<PRODUCTION_SERVER_IP>

# 2. Navigate to project directory
cd /opt/tasksync

# 3. Verify git status (clean working tree)
git status
git log --oneline -3

# 4. Pull latest release tag
git fetch --tags
git checkout v1.0.0  # Replace with actual release tag

# ═══════════════════════════════════════════════════════════════════════
# PHASE 2: Environment & Secrets
# ═══════════════════════════════════════════════════════════════════════

# 5. Verify .env.production (all secrets present, none empty)
grep -c '=\${' .env.production  # Should output 0 (no placeholders)

# 6. Verify docker-compose.prod.yml is present
ls -la docker-compose.prod.yml

# ═══════════════════════════════════════════════════════════════════════
# PHASE 3: Database Setup
# ═══════════════════════════════════════════════════════════════════════

# 7. Start database and cache services
docker compose -f docker-compose.prod.yml up -d postgres redis

# 8. Wait for PostgreSQL health
until docker compose exec -T postgres pg_isready -U tasksync; do
  echo "Waiting for PostgreSQL..."; sleep 2
done

# 9. Run migrations
docker compose -f docker-compose.prod.yml run --rm php-fpm-blue \
  php artisan migrate --force

# 10. Seed initial data (admin user, plans)
docker compose -f docker-compose.prod.yml run --rm php-fpm-blue \
  php artisan db:seed --force

# ═══════════════════════════════════════════════════════════════════════
# PHASE 4: Production Deploy (Blue/Green)
# ═══════════════════════════════════════════════════════════════════════

# 11. Run blue/green deployment (deploys to standby, switches traffic)
bash deploy/production/deploy-bluegreen.sh

# 12. Alternatively, deploy green explicitly:
bash deploy/production/deploy-bluegreen.sh green

# 13. Verify deployment log
ls -la deploy-*.log | tail -1

# ═══════════════════════════════════════════════════════════════════════
# PHASE 5: Post-Deploy Verification
# ═══════════════════════════════════════════════════════════════════════

# 14. Health check
curl -s -w "\nHTTP %{http_code}\n" https://tasksyncpro.com/api/v1/health

# 15. WebSocket test
# Install wscat: npm install -g wscat
wscat -c wss://tasksyncpro.com/ws

# 16. API smoke test — register new user
curl -s -X POST https://api.tasksyncpro.com/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com","password":"Password123!","password_confirmation":"Password123!"}'

# 17. Verify Horizon dashboard
curl -s -o /dev/null -w "%{http_code}" https://tasksyncpro.com/horizon

# 18. Verify Sentry errors (should be 0)
curl -s -o /dev/null -w "%{http_code}" https://sentry.io/api/0/projects/tasksync/prod/

# 19. Check all container health
docker compose -f docker-compose.prod.yml ps
docker compose -f docker-compose.prod.yml top

# 20. Verify Redis is caching
docker compose exec -T redis redis-cli -a $REDIS_PASSWORD INFO stats | grep keyspace

# 21. Check Horizon queue processing
docker compose exec -T horizon php artisan horizon:status

# ═══════════════════════════════════════════════════════════════════════
# PHASE 6: Final Verification
# ═══════════════════════════════════════════════════════════════════════

# 22. Run full smoke test suite
docker compose -f docker-compose.prod.yml run --rm php-fpm-blue \
  php artisan test --testsuite=Feature --filter=SmokeTest

# 23. Verify CDN access
curl -s -o /dev/null -w "%{http_code}" https://cdn.tasksyncpro.com/assets/app.js

# 24. Check all email delivery (test route)
curl -s -X POST https://api.tasksyncpro.com/api/v1/test/mail \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -d '{"email":"admin@tasksyncpro.com"}'

# 25. Announce deployment in Slack
curl -s -X POST -H "Content-Type: application/json" \
  -d '{"text":"🚀 TaskSync Pro v1.0.0 deployed to production! Health check: ✅"}' \
  $SLACK_WEBHOOK_URL
```

### 4.2 Deployment Window Timeline

| Phase | Duration | Description |
|-------|----------|-------------|
| Pre-flight checks | 15 min | Final verification of P1-P28 |
| DB setup + migration | 10 min | Start postgres/redis, run migrations |
| Blue/Green deploy | 15 min | Build, start standby, switch traffic |
| Post-deploy smoke tests | 10 min | Health check, API tests, WS test |
| Monitoring soak | 30 min | Watch error rates, response times |
| **Total window** | **~80 min** | |

---

## 5. Rollback Criteria

Immediate rollback triggers (any one of the following):

| # | Criterion | Threshold | Action |
|---|-----------|-----------|--------|
| R1 | Health check failure | `GET /api/v1/health` != 200 after 3 retries | `bash deploy/production/rollback.sh` |
| R2 | API error rate | >1% of requests return 5xx in 5-min window | `bash deploy/production/rollback.sh` |
| R3 | API P95 latency | >500ms for any endpoint over 5-min window | `bash deploy/production/rollback.sh` |
| R4 | WebSocket error rate | >5% connection failures | `bash deploy/production/rollback.sh` |
| R5 | Horizon queue backlog | >1000 pending jobs for >5 minutes | Investigate; rollback if cannot clear |
| R6 | Failed deploy script | deploy-bluegreen.sh exits non-zero | Auto-rollback via rollback.sh |
| R7 | DB migration error | Migration fails or corrupts data | Restore pre-deploy backup + rollback |

### Rollback Commands

```bash
# Quick rollback (traffic switch to previous target)
bash deploy/production/rollback.sh

# Rollback with DB restore
bash deploy/production/rollback.sh --db

# Hard rollback (git revert + DB restore + rebuild)
bash deploy/production/rollback.sh --hard
```

### Rollback Success Criteria
- [ ] Health check returns 200 after rollback
- [ ] Previous version serving traffic normally
- [ ] Error rate returns to <0.1%
- [ ] WebSocket connections re-established
- [ ] Horizon processing backlog cleared

---

## 6. Monitoring & Observability Handoff (Gate 8)

After successful production deployment, hand off to SRE team for Gate 8 (Observe → loop).

| Monitoring Area | Tool | Configuration | Dashboard URL |
|-----------------|------|---------------|---------------|
| **API errors** | Sentry | DSN configured in `.env.production`, sample_rate=0.25 | https://sentry.io/tasksync/prod |
| **API performance** | Sentry Tracing | Traces sample rate 0.25, profiles 0.10 | https://sentry.io/tasksync/prod/traces |
| **Queue processing** | Laravel Horizon | Built-in dashboard at `/horizon` | https://tasksyncpro.com/horizon |
| **Server metrics** | Prometheus Node Exporter | Port 9100, scraped by Grafana | Grafana dashboard ID: 1860 |
| **Container logs** | Docker logs → Loki (optional) | docker-compose logging driver=json-file | Grafana Loki |
| **Uptime** | UptimeRobot | Check interval: 5min, alert via email+Slack | https://uptimerobot.com/dashboard |
| **SSL expiry** | Certbot cron + UptimeRobot SSL check | Daily renewal check + 14-day SSL alert | Certbot cron + UptimeRobot |
| **DB backups** | Cron + pg_dump | Daily 02:00, retention: 7d/4w/12m | Server cron |
| **Deploy alerts** | Slack webhook | Deploy start/fail/success notifications | #tasksync-prod-alerts |
| **Incident response** | PagerDuty (optional) | Critical alerts from Sentry + UptimeRobot | N/A |

### SRE Handoff Checklist

- [ ] Sentry project URL shared with SRE team
- [ ] Horizon dashboard URL + admin credentials shared
- [ ] UptimeRobot dashboard access granted
- [ ] Slack webhook `#tasksync-prod-alerts` channel created
- [ ] DB backup access + restore procedure documented
- [ ] Server SSH access (key-based) granted to on-call SRE
- [ ] Docker compose commands documented in STAGING_ACCESS.md
- [ ] Runbook created: `/opt/tasksync/docs/RUNBOOK.md`
- [ ] On-call rotation schedule confirmed
- [ ] Escalation path documented (DevOps → SRE Lead → CTO)

---

## 7. Post-Launch Verification (24h Soak)

| Check | Interval | Expected | Actual |
|-------|----------|----------|--------|
| Health endpoint | Every 5min | 200 OK | |
| Error rate (Sentry) | Every 15min | <0.1% | |
| P95 API latency | Every 15min | <200ms | |
| Horizon queue depth | Every 15min | <10 pending | |
| Disk usage | Every 1h | <70% | |
| Memory usage | Every 1h | <80% | |
| Redis memory | Every 1h | <70% of maxmemory | |
| New user signups | Every 1h | Functional | |
| Email delivery | Every 1h | <5min delay | |
| WebSocket connections | Every 1h | >0 connected | |

---

## 8. Communication Plan

| Timing | Message | Channel |
|--------|---------|---------|
| 30 min before deploy | "🔄 Production deploy starting for TaskSync Pro v1.0.0. Expected downtime: 0s (Blue/Green)." | #tasksync-prod-alerts |
| Deploy start | "▶️ Deploy in progress: building standby, running migrations..." | #tasksync-prod-alerts |
| Traffic switch | "🔀 Switching traffic to new version..." | #tasksync-prod-alerts |
| Success | "✅ Deploy complete. Health check: OK. Monitor for 30min." | #tasksync-prod-alerts |
| Rollback (if needed) | "🔴 Rollback triggered: {reason}. Switching to previous version." | #tasksync-prod-alerts |
| Rollback success | "🟡 Rollback complete. Previous version serving traffic. Investigating root cause." | #tasksync-prod-alerts |
| 30min post-deploy | "📊 Post-deploy soak: errors=0.0%, P95=120ms, horizon idle." | #tasksync-prod-alerts |
| 24h post-deploy | "✅ 24h soak complete. Handing off to SRE (Gate 8)." | #tasksync-prod-alerts |

---

*Prepared by Linda Schmidt (sofi-devops-cloud-lead) · Gate 7 Production Go-Live*
*Next: Execute deployment → Verify → Handoff to Gate 8 (Observe → loop)*
