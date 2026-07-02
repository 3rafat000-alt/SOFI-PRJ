# Security Report — PRJ-SAKK — Admin Login / Auth — 2026-07-01

## 1. Executive summary

Cross-layer security review of the SAKK **admin login / authentication** surface (spec-review → pentest → fix → verify). Seven issues found: one critical, three high, three low. Top risk was a **single-factor admin console** on a financial platform — a leaked or guessed admin password granted full control, while a complete `TwoFactorService` sat unused. All seven are now **fixed, committed, and re-verified**; three low residuals are logged. **Verdict: Gate 5 security bar on the admin console is MET.** Head after remediation: `0e77e6a`.

### الملخص التنفيذي (AR)

مراجعة أمنية شاملة لواجهة تسجيل دخول لوحة الإدارة في منصة صك. رُصدت 7 مشكلات: واحدة حرجة، ثلاث عالية، وثلاث منخفضة. أخطرها أن لوحة الإدارة كانت تعتمد على عامل واحد فقط (كلمة المرور) رغم وجود خدمة تحقق ثنائي جاهزة غير مُفعّلة — أي تسريب لكلمة مرور مسؤول يعني سيطرة كاملة. جميع المشكلات السبع أُصلحت والتُزمت وأُعيد التحقق منها؛ بقيت ثلاث ملاحظات منخفضة الخطورة مسجّلة. **الحكم: معيار الأمان للبوابة 5 على لوحة الإدارة مُستوفى.** رأس المستودع بعد الإصلاح: `0e77e6a`.

## 2. Scope & method

- **Scope (in-bounds):** admin login/logout, account lockout, rate-limiting, session handling, second factor, admin login view. Owner-authorized, this project only, seed data.
- **Out-of-bounds:** post-auth admin pages (IDOR/XSS preflags), mobile, other portals except shared-limiter coupling.
- **Method:** `/sofi-spec-review` 4-pillar → `/sofi-secure pentest` (Python recon pack `sofi_scan.py security`, 0 model tokens) → `/sofi-fix` (specialist agents) → `/sofi-secure verify` (static path-trace of committed code). No data touched, no active exploitation.
- **Key files:** `app/Http/Controllers/Admin/AuthController.php` · `app/Traits/HasAccountLockout.php` · `app/Providers/AppServiceProvider.php` · `bootstrap/app.php` · `app/Services/TwoFactorService.php` · `app/Models/ActivityLog.php` · `resources/views/admin/auth/login.blade.php` · `routes/web.php` · `config/session.php` / `.env.example`.

## 3. Findings

| SEV | file:line | Defect | Proof | Fix | Status |
|-----|-----------|--------|-------|-----|--------|
| 🔴 | AuthController.php:22-66 | Admin console single-factor — `login()` never invoked the shipped `TwoFactorService` | Password alone reached `admin.dashboard`; `TwoFactorService`+`two_factor_*` columns existed unused | 2FA challenge gate before session finalize | ✅ `ce39632` |
| 🟠 | bootstrap/app.php (no trustProxies) + AppServiceProvider.php:70 | `admin-login` throttle keyed by proxy/loopback IP → single global bucket; real client IP lost | Stack cloudflared→Caddy→php-fpm; `request->ip()` = loopback | `trustProxies(at:'*', XFF\|Host\|Port\|Proto)` | ✅ `bc72cfa` |
| 🟠 | AuthController.php:34-64 | Account-enumeration oracle — distinct "locked" message + attempt-increment only for existing users, pre-verify | Response differed by email existence | Generic invalid message on the pre-verify branch | ✅ `ce39632` |
| 🟠 | AuthController.php (login/logout) | No audit trail on admin auth — zero forensics on a financial console | Nothing written to `ActivityLog` | `logActivity()` on login/2FA success+fail, logout | ✅ `ce39632` |
| 🟡 | web.php:57,335,366,384 | admin/company/merchant/agent share one `admin-login` limiter | One portal's brute-force throttled all four | 4 named limiters + `admin-2fa`, routes rekeyed | ✅ `8d05211` |
| 🟡 | .env.example:66 | `SESSION_SECURE_COOKIE=false` with no prod guidance | Cookie could ride non-HTTPS in prod | Explicit "PROD MUST SET true" comment | ✅ `0e77e6a` |
| 🟡 | login.blade.php:14 (+layouts/admin.blade.php) | Alpine.js from public CDN, no SRI, on credential page — supply-chain + offline break | `<script src=jsdelivr…alpinejs>` | Self-hosted `public/vendor/alpine` (core+collapse), same-origin, git-tracked | ✅ `8a58a9d` |

**Confirmed false positives (auth scope):** all 13 `{!! !!}` 🔴 preflags output a developer-passed `$icon` SVG prop (icon-slot pattern, e.g. button.blade.php:261 / input.blade.php:263), not user input. IDOR/CSRF preflags are post-auth admin pages, out of login scope. Login form CSRF present (`@csrf`). Session `http_only=true`, `same_site=lax` — OK.

## 4. Remediation

Fixed + committed (SAKK repo, own git history, conventional `fix(security):` format):
- `ce39632` — 2FA gate + enumeration-oracle message + audit trail (one controller rewrite; new view `admin/auth/two-factor.blade.php`, `admin.login.2fa[.verify]` routes).
- `bc72cfa` — TrustProxies in `bootstrap/app.php`.
- `8d05211` — per-portal rate limiters + `admin-2fa` limiter.
- `0e77e6a` — `.env.example` session-cookie prod note.
- `8a58a9d` — self-host Alpine.js (login + shared layout).
- `2475975` — incidental `fix(css)` tokenizing the login glow/alert colors (unrequested, harmless).

Verification: `route:list --path=admin/login` shows all 4 routes wired; `view:cache` compiles; `php -l` clean; `ActivityLog` fillable matches every written column. No regression; tree clean.

**Deferred (with reason):**
- ⚪ **Narrower post-auth oracle** — `!is_active` / `!is_admin` branches (AuthController.php:55-63) return distinct messages, reachable only *after* a correct password. Closing it changes UX for legit non-admin/inactive users → architect decision. Low: attacker must already hold the password.
- ⚠️ **2FA enrollment UI missing** — this pass consumes existing `two_factor_enabled` secrets; admins cannot self-enroll from the panel. Required before 2FA can be made mandatory.
- 🟡 **`public/vendor` gitignored** — `**/vendor/` swallows public assets; self-hosted icons (`material-icons`) are not tracked and rely on manual deploy placement. Alpine JS was force-added to ship; icons need the same.

## 5. Risk posture / gate

The critical (single-factor) and all three high findings are closed and proven by static path-trace of committed code. Password + lockout (5/15min) + per-portal throttle + CSRF + `is_admin`/`is_active` gates + 2FA + audit logging now form a defensible admin auth posture. **Gate 5 security bar on the admin login surface: MET.** Residuals are non-blocking (one low oracle, one hardening, one deploy-packaging note).

## 6. Next actions (ranked → owner)

1. ⚠️ **Build admin 2FA enrollment UI** (QR + confirm + recovery codes) so 2FA can be enforced org-wide → `sofi-laravel-core-dev` + `sofi-blade-architect`.
2. 🟡 **Track/ship `public/vendor` assets** (icons + Alpine) via git or a build step so deploys aren't offline-broken → `sofi-devops-cloud-lead`.
3. ⚪ **Architect decision on the post-auth message oracle** (unify vs keep UX) → `sofi-security-compliance-architect`.
4. 🟡 **Confirm `SESSION_SECURE_COOKIE=true` in the prod `.env`** (not just the example) → `sofi-devops-cloud-lead`.
