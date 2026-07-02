# SAKK Wallet — Production Deployment

Laravel 13 / PHP 8.4 fintech wallet. This guide takes a fresh Linux host to a
running, TLS-terminated, queue-backed production node. Commands are concrete.

> **No secrets in this repo.** `origin` is PUBLIC. Every key below is referenced
> by NAME only — real values live in the server's `.env`, never in git.

---

## 0. Topology

```
Internet ──TLS──▶ nginx (sakk-site.conf) ──FPM socket──▶ php-fpm 8.4 ──▶ Laravel
                        │                                      │
                        ├─ rate-limit zones (login/otp/api/transfer)
                        └─ X-Accel-Redirect ──▶ storage/app/private (KYC files)

           Redis ◀── cache + queue + session        supervisor ──▶ queue:work
           MySQL/Postgres ◀── primary datastore
```

The nginx vhost stub lives in the repo at
`projects/carda-wallet/deploy/nginx/sakk-site.conf` — it already encodes the
security partial includes, rate-limit zones, the private-storage internal
location, and the PHP-FPM passthrough. Deploy *that file*; do not hand-write a
new vhost.

---

## 1. Server prerequisites

### 1.1 PHP 8.4 + extensions

```bash
sudo apt-get update
sudo apt-get install -y \
  php8.4-fpm php8.4-cli \
  php8.4-mysql \
  php8.4-mbstring php8.4-xml php8.4-bcmath php8.4-curl \
  php8.4-zip php8.4-gd php8.4-intl \
  php8.4-redis \
  php8.4-opcache
```

Extension rationale (verified against the codebase):

| Extension | Why it is required |
|-----------|--------------------|
| `mbstring`, `xml`, `intl` | Laravel framework baseline |
| `bcmath` | money math precision |
| `curl` | Stripe SDK, CCPayment HTTP, FCM push |
| `pdo_mysql` (`php8.4-mysql`) | primary DB driver in production |
| `redis` | cache / queue / session driver |
| `gd` | KYC image handling (`image` validation rule on uploads) |
| `zip` | Composer + artifact handling |
| `openssl` (bundled) | **mandatory** — biometric RSA/EC signature verify in `VerifiesTransactionAuth` |
| `sodium` (bundled) | Ed25519 biometric signature fallback |
| `opcache` | production throughput |

> `ext-openssl` and `ext-sodium` ship with the standard PHP build — confirm with
> `php -m | grep -E 'openssl|sodium'`. Biometric transaction authorization fails
> closed without them.

Tune `php.ini` (production):

```ini
cgi.fix_pathinfo = 0          ; the vhost relies on this (anti phantom-script)
post_max_size = 20M           ; vhost client_max_body_size is 21M (headroom)
upload_max_filesize = 6M      ; KYC images are capped at 5 MB server-side
memory_limit = 256M
expose_php = Off
opcache.enable = 1
opcache.validate_timestamps = 0   ; deploy invalidates via fpm reload
opcache.max_accelerated_files = 20000
```

### 1.2 Composer, nginx, redis, supervisor

```bash
# Composer
sudo php -r "copy('https://getcomposer.org/installer','composer-setup.php');"
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php

# nginx + redis + supervisor + certbot
sudo apt-get install -y nginx redis-server supervisor certbot python3-certbot-nginx
sudo systemctl enable --now redis-server nginx supervisor
```

Harden Redis: bind to localhost and set a password.

```bash
sudo sed -i 's/^# *requirepass.*/requirepass CHANGE_ME_ON_SERVER/' /etc/redis/redis.conf
sudo sed -i 's/^bind .*/bind 127.0.0.1 ::1/'                       /etc/redis/redis.conf
sudo systemctl restart redis-server
```

The same password value goes into `.env` as `REDIS_PASSWORD` (name only here).

---

## 2. Code + dependencies

```bash
sudo mkdir -p /var/www
sudo chown -R deploy:deploy /var/www
cd /var/www
git clone https://github.com/3rafat000-alt/sofi-platform.git app
cd app/projects/carda-wallet/backend     # the Laravel app root

# Production-only install (no dev deps, optimized autoloader)
composer install --no-dev --optimize-autoloader --no-interaction
```

> Document root in the vhost is `/var/www/public`. Point it at the Laravel
> `public/` of this backend — either symlink it or adjust `root` in the vhost to
> `/var/www/app/projects/carda-wallet/backend/public`. The vhost ships with
> `root /var/www/public;` — change it to match your actual checkout path.

---

## 3. Environment (`.env`)

```bash
cp .env.example .env
php artisan key:generate          # writes APP_KEY into .env
```

Set production basics:

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain
```

### Required / used `.env` keys (NAMES ONLY — fill values on the server)

Sourced from `.env.example`. Do **not** commit real values.

**App / core**
`APP_NAME` · `APP_ENV` · `APP_KEY` · `APP_DEBUG` · `APP_URL`
`APP_LOCALE` · `APP_FALLBACK_LOCALE` · `APP_FAKER_LOCALE` · `APP_MAINTENANCE_DRIVER`

**Database**
`DB_CONNECTION` (set to `mysql` or `pgsql` in prod — dev default is sqlite)
plus the standard `DB_HOST` · `DB_PORT` · `DB_DATABASE` · `DB_USERNAME` · `DB_PASSWORD`
(add these to `.env`; they are not all pre-listed in `.env.example`).

**Cache / queue / session**
`CACHE_STORE` · `QUEUE_CONNECTION` (use `redis`) · `BROADCAST_CONNECTION`
`SESSION_DRIVER` · `SESSION_LIFETIME` · `SESSION_ENCRYPT` · `SESSION_DOMAIN`
`SESSION_PATH` · `SESSION_HTTP_ONLY` · `SESSION_SAME_SITE`
`SESSION_SECURE_COOKIE` (**set true** behind TLS) · `MEMCACHED_HOST`

**Redis**
`REDIS_CLIENT` · `REDIS_HOST` · `REDIS_PORT` · `REDIS_PASSWORD`

**Sanctum (API tokens)**
`SANCTUM_STATEFUL_DOMAINS` · `SANCTUM_TOKEN_PREFIX`

**Filesystem / object storage (KYC private disk + assets)**
`FILESYSTEM_DISK` · `AWS_ACCESS_KEY_ID` · `AWS_SECRET_ACCESS_KEY`
`AWS_DEFAULT_REGION` · `AWS_BUCKET` · `AWS_USE_PATH_STYLE_ENDPOINT`

**Mail**
`MAIL_MAILER` · `MAIL_HOST` · `MAIL_PORT` · `MAIL_USERNAME` · `MAIL_PASSWORD`
`MAIL_SCHEME` · `MAIL_FROM_ADDRESS` · `MAIL_FROM_NAME`

**Logging**
`LOG_CHANNEL` · `LOG_STACK` · `LOG_DEPRECATIONS_CHANNEL` · `LOG_LEVEL`

**Payments — Stripe (cards + Issuing)**
`STRIPE_KEY` · `STRIPE_SECRET` · `STRIPE_WEBHOOK_SECRET`
(the Issuing webhook signing secret is read from config `services.stripe.*`;
ensure the corresponding env var is present — see `config/services.php`).
`CARD_PROVIDER` selects the active issuing backend.

**Payments — Marqeta (alternate card provider)**
`MARQETA_API_KEY` · `MARQETA_API_SECRET` · `MARQETA_ENVIRONMENT`

**Payments — CCPayment (crypto)**
`CCPAYMENT_APP_ID` · `CCPAYMENT_APP_SECRET` · `CCPAYMENT_DEBUG_MODE`
`CCPAYMENT_IP_WHITELIST` (comma-separated provider IPs — the webhook fails
closed if the source IP is not in this list) · `PAY_URL_BASE`

**Frontend build**
`VITE_APP_NAME`

> Production checklist for env: `APP_DEBUG=false`, `APP_ENV=production`,
> `SESSION_SECURE_COOKIE=true`, `CCPAYMENT_DEBUG_MODE` off, real
> `STRIPE_WEBHOOK_SECRET` + `CCPAYMENT_IP_WHITELIST` set (webhooks are
> fail-closed and will reject everything if these are blank).

---

## 4. Database migration

> **PROD ONLY.** Development uses a seeded SQLite database; **never** run
> `migrate:fresh` or `db:seed` against a dev DB — it wipes seeded data.

On the **production** host, against the production MySQL/Postgres:

```bash
php artisan migrate --force        # --force is required in production env
```

`--force` skips the interactive "are you sure" guard that Laravel raises when
`APP_ENV=production`. Run forward migrations only. Do not `migrate:fresh` or
`migrate:rollback` on a live datastore — coordinate any destructive schema
change with the DB owner (expand-contract; never drop a column in the same
deploy that stops writing to it).

---

## 5. Storage + asset linking

```bash
# Public symlink: public/storage -> storage/app/public
php artisan storage:link
```

Private KYC files live under `storage/app/private/kyc/{user_id}/...` and are
**never** symlinked into `public/`. The vhost serves them only via an internal
`X-Accel-Redirect` from the admin secure-file controller:

```nginx
location ^~ /private-storage/ {
    internal;                                  # not directly reachable
    alias /var/www/storage/app/private/;
}
location ^~ /storage/ { deny all; return 404; } # block raw storage
```

Permissions:

```bash
sudo chown -R deploy:www-data storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;
sudo find storage bootstrap/cache -type f -exec chmod 664 {} \;
```

---

## 6. Optimize caches (production)

Run after every deploy, after `.env` is final:

```bash
php artisan config:cache     # merges + caches config (env() only read here)
php artisan route:cache      # caches the ~301 route definitions
php artisan view:cache       # precompiles Blade (admin panel)
php artisan event:cache      # optional: cache event listeners
```

> Order matters: `config:cache` first (so cached config is fresh), then routes,
> then views. **`config:cache` freezes `.env`** — any later `.env` edit requires
> re-running it. If you change `.env`, run `php artisan config:clear` then
> re-cache.

A clean redeploy sequence:

```bash
php artisan down --render="errors::503"     # maintenance window (optional)
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo systemctl reload php8.4-fpm            # clears opcache (validate_timestamps=0)
php artisan up
```

---

## 7. nginx vhost wiring

```bash
# 1. Place the repo vhost
sudo cp /var/www/app/projects/carda-wallet/deploy/nginx/sakk-site.conf \
        /etc/nginx/sites-available/sakk

# 2. Edit `root` to your real public path, and the FPM socket if your distro
#    names it differently (the stub expects unix:/run/php/php8.4-fpm.sock).
sudoedit /etc/nginx/sites-available/sakk

# 3. Provide the partial includes the vhost references, in /etc/nginx/conf.d/:
#      ssl.conf  security-headers.conf  compression.conf  path-traversal.conf
#    (security-headers.conf is what actually emits HSTS/CSP/X-Frame-Options at
#     the edge — see SECURITY.md.) If rate-limiting.conf already defines the
#    login/otp/api/transfer zones globally, remove the duplicate limit_req_zone
#    lines from the top of sakk-site.conf to avoid "duplicate zone" errors.

# 4. Enable + reload
sudo ln -sf /etc/nginx/sites-available/sakk /etc/nginx/sites-enabled/sakk
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
```

The vhost already enforces, per its own comments and blocks:
`server_tokens off`, `autoindex off`, `merge_slashes off`, dotfile/`.git`
denial, backup-extension denial (`.sql .log .bak .env-like`), per-IP connection
cap (`limit_conn addr 20`), and `client_max_body_size 21M`.

Rate-limit zones defined in the vhost (http context):

| Zone | Rate | Applied to |
|------|------|-----------|
| `login` | 5 r/min | `= /api/login` |
| `otp` | 3 r/min | `= /api/otp`, `= /api/auth/verify-otp` |
| `transfer` | 10 r/min | `^/api/(transfer\|payment\|send-money)` |
| `api` | 60 r/min | `^~ /api/` (everything else) |

> Path note: the application's real routes are **versioned** under `/api/v1/...`
> (e.g. `/api/v1/auth/login`, `/api/v1/transfer`). The vhost's exact-match
> zones target the unversioned legacy paths (`= /api/login`). The broad
> `transfer` regex `^/api/(transfer|...)` does **not** match `/api/v1/transfer`
> because of the `v1/` segment. Laravel's own `throttle:auth` / `throttle:api`
> middleware (declared in `routes/api.php`) is the authoritative rate limiter
> for the versioned API; treat the nginx zones as a coarse edge backstop and,
> if you want nginx to also throttle the versioned transfer path, broaden the
> regex to `^/api/(v1/)?(transfer|payment|send-money)`.

---

## 8. PHP-FPM pool

The vhost passes to `unix:/run/php/php8.4-fpm.sock`. Confirm the pool runs as a
least-privileged user and the socket owner matches nginx:

```ini
; /etc/php/8.4/fpm/pool.d/www.conf
user = www-data
group = www-data
listen = /run/php/php8.4-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660
pm = dynamic
pm.max_children = 20
pm.start_servers = 4
pm.min_spare_servers = 2
pm.max_spare_servers = 8
request_terminate_timeout = 90s     ; matches vhost fastcgi_read_timeout 90
```

```bash
sudo systemctl enable --now php8.4-fpm
sudo systemctl reload php8.4-fpm
```

---

## 9. Queue worker (supervisor)

Set `QUEUE_CONNECTION=redis`. Run the worker under supervisor so it restarts on
crash and on deploy.

```ini
; /etc/supervisor/conf.d/sakk-worker.conf
[program:sakk-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/app/projects/carda-wallet/backend/artisan queue:work redis \
        --sleep=3 --tries=3 --max-time=3600 --backoff=5
directory=/var/www/app/projects/carda-wallet/backend
autostart=true
autorestart=true
stopwaitsecs=120          ; let in-flight jobs (transfers, FCM) finish
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/sakk/worker.log
stdout_logfile_maxbytes=50MB
stdout_logfile_backups=5
```

```bash
sudo mkdir -p /var/log/sakk && sudo chown www-data:www-data /var/log/sakk
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start sakk-worker:*
# On every deploy, after migrating + caching:
php artisan queue:restart      # signals workers to reload fresh code
```

### Scheduler (cron)

If scheduled tasks exist (exchange-rate refresh, gold-price sync, KYC cleanup),
add the Laravel scheduler entry:

```cron
* * * * * cd /var/www/app/projects/carda-wallet/backend && php artisan schedule:run >> /dev/null 2>&1
```

---

## 10. HTTPS / Let's Encrypt

The vhost expects certs at `/etc/ssl/certs/sakk.crt` + `/etc/ssl/private/sakk.key`
and a `dhparam.pem`. Two paths:

**Option A — certbot manages nginx directly** (simplest):

```bash
sudo certbot --nginx -d your-domain -d www.your-domain
```

certbot will provision certs and rewrite the TLS paths. If you keep the repo
vhost verbatim, instead use webroot mode so certbot does not rewrite your
hand-tuned config:

**Option B — webroot (preserves the repo vhost):**

```bash
sudo mkdir -p /var/www/letsencrypt          # vhost serves ACME from here
sudo certbot certonly --webroot -w /var/www/letsencrypt \
     -d your-domain -d www.your-domain
# then point the vhost ssl_certificate / ssl_certificate_key at
# /etc/letsencrypt/live/your-domain/{fullchain,privkey}.pem
sudo openssl dhparam -out /etc/ssl/certs/dhparam.pem 2048
sudo nginx -t && sudo systemctl reload nginx
```

The vhost already redirects plain HTTP → HTTPS and whitelists
`/.well-known/acme-challenge/` for renewals.

Auto-renewal:

```bash
sudo systemctl enable --now certbot.timer
sudo certbot renew --dry-run
```

---

## 11. Log rotation

Laravel logs to `storage/logs/`. Use the daily channel and rotate nginx +
worker logs.

`.env`: `LOG_CHANNEL=stack`, `LOG_STACK=daily` (caps Laravel logs to N days
automatically via the `daily` channel's `days` config).

```conf
# /etc/logrotate.d/sakk
/var/www/app/projects/carda-wallet/backend/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    copytruncate
    su www-data www-data
}

/var/log/sakk/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    copytruncate
}
```

nginx access/error logs (`/var/log/nginx/sakk-*.log`) are covered by the
distro's default `/etc/logrotate.d/nginx`; confirm it includes the `sakk-*`
glob or add it.

---

## 12. Post-deploy smoke check

```bash
# liveness
curl -fsS https://your-domain/api/health | python3 -m json.tool

# the API is up + versioned base resolves (expects 401 unauthenticated)
curl -s -o /dev/null -w "%{http_code}\n" https://your-domain/api/v1/auth/me   # 401

# TLS + security headers present at the edge
curl -sI https://your-domain/ | grep -iE 'strict-transport|x-frame|x-content-type'

# queue worker alive
sudo supervisorctl status sakk-worker:*
```

A green deploy is: `health` returns `{"status":"ok"}`, unauthenticated
protected routes return 401, security headers are present, workers are
`RUNNING`, and `nginx -t` passes.

---

## 13. Rollback

```bash
php artisan down
cd /var/www/app && git checkout <previous-good-tag>
cd projects/carda-wallet/backend
composer install --no-dev --optimize-autoloader
# DB: only roll back schema if the previous release truly requires it AND the
# migration is reversible. Prefer forward-fix. Coordinate with the DB owner.
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo systemctl reload php8.4-fpm
php artisan queue:restart
php artisan up
```

Keep the previous release's image/checkout until the new one has baked.
