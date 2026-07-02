# 🏛️ CARDA Wallet — SAKK SuperApp

> منصة تكنولوجيا مالية متكاملة بهوية العنابي الدمشقي — Laravel 12 + Blade + Flutter.

## الهوية البصرية
- **العنابي:** `#6E1B2D`  |  **ذهبي:** `#B58A3C`  |  **رخامي:** `#F7F3EE`
- **الخط:** IBM Plex Sans Arabic (مستضاف محلياً، 8 أوزان)
- **Light-only** — لا دارك مود
- **نمط:** Glassmorphism على فاتح، 3D Scroll، Card Flip

## المتطلبات
- PHP ≥ 8.4 + extensions: bcmath, gd, pdo_sqlite|pdo_mysql, mbstring, xml, curl, redis
- Composer 2.x
- Node.js 18+ (لتوليد الأصول)
- MySQL 8+ (production) أو SQLite (dev)
- Redis 7+ (cache, session, rate limiting)

## التثبيت السريع
```bash
cd projects/carda-wallet/backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve  # http://127.0.0.1:8000
```

## الهيكلة
```
app/
├── Http/Controllers/
│   ├── API/          # 21 API controller (Auth, Wallet, Card, Transfer, …)
│   ├── Admin/        # 23 Admin controller (Dashboard, Users, KYC, …)
│   ├── Webhooks/     # CCPayment + Stripe Issuing
│   └── Landing       # 6-قسم صفحة هبوط
├── Http/Middleware/  # 7 Middleware (auth, security, device, installer)
├── Services/         # 18 Service class (business logic layer)
├── Traits/           # ApiResponse.php — موحّد response envelope
├── Models/           # 44 Eloquent model (User, Wallet, Transaction, Card, …)
├── Enums/            # 9 PHP enums (CardStatus, KycStatus, TransactionType, …)
config/
├── cors.php          # CORS مقيّد
├── sanctum.php       # Token expiry 3 أيام (4320 min)
├── kyc.php           # 3 مستويات KYC مع الحدود
database/migrations/  # 54 migration
resources/views/
├── layouts/          # admin + landing + installer
├── admin/            # 49 صفحة لوحة تحكم
├── landing/          # 3 صفحات هبوط
├── components/admin/ # 25+ مكون Blade
routes/
├── web.php           # 276 مسار
├── api.php           # 494 مسار API
deploy/               # في projects/carda-wallet/deploy/
├── nginx/            # 6 ملفات: site, ssl, security-headers, rate-limiting, …
├── php/              # hardening.ini
├── mysql/            # my.cnf إعدادات محسّنة
├── redis/            # redis.conf آمن
├── firewall/         # iptables-rules.sh
├── modsecurity/      # owasp-crs.conf
└── secrets/          # pre-commit hook + rotate-keys.sh
```

## الأمان
- ✅ 16 P0 + 26 P1 ثغرة أصلحت
- ✅ لا secrets في الكود (Vault-ready + pre-commit hook + rotate-keys)
- ✅ Rate limiting على كل endpoints (Redis + nginx)
- ✅ CORS مقيّد، Security headers، CSP قوي
- ✅ Session encryption مفعل
- ✅ Path traversal محظور في nginx + app
- ✅ KYC documents على private disk (X-Accel-Redirect)
- ✅ SQL injection: parameterized queries فقط
- ✅ XSS: Blade escaping + CSP + SVG ممنوع
- ✅ IDOR: ownership verification على كل endpoint
- ✅ 2FA باستخدام Google2FA
- ✅ Biometric auth API متكامل
- ✅ Admin endpoints محمية بـ AdminMiddleware + نطاق VPN

## البنية التحتية
- **Web:** nginx + PHP 8.4-FPM
- **Database:** MySQL 8 (production) أو SQLite (dev)
- **Cache:** Redis (session, cache, rate limiting)
- **Queue:** database/redis driver عبر Supervisor
- **Monitoring:** Sentry + log rotation + audit_log
- **Backup:** daily database + storage rsync

## Run
```bash
php artisan serve
# Landing: http://127.0.0.1:8000/
# Admin:   http://127.0.0.1:8000/admin/login
```

## Tests
```bash
php artisan test
# أو:
vendor/bin/pest
```
34 ملف اختبار (Feature + Unit) تغطي Auth, Wallet, Card, Transfer, KYC, Gold, Security.

## Licence
Proprietary — SAKK Identity.
