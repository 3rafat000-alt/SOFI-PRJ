# SAKK Wallet — Security Posture

How the hardening already in this codebase maps to the **OWASP Top 10 (2021)**.
Every control below cites `file:line` and was verified by reading the source —
not assumed. Where a control is partial or carries accepted risk, it is stated
plainly in §"Known accepted risks". Paths are relative to the Laravel app root
`projects/carda-wallet/backend/`.

> Public repo. This document describes *controls*, never secrets. All keys are
> referenced by name; values live only in the server `.env`.

---

## A01 — Broken Access Control

**Ownership checks on every money/resource path.**
Resources are scoped to the authenticated user before any state change:

- Wallet ops verify `wallet->user_id === request->user()->id` and 404 on
  mismatch — `app/Http/Controllers/API/WalletController.php` (deposit guard,
  withdraw guard, convert wallet resolution; see the repeated
  `if ($wallet->user_id !== $request->user()->id)` blocks).
- Card ops apply the same owner gate — `app/Http/Controllers/API/CardController.php`
  (`load`, `unload`, `details`: `if ($card->user_id !== $request->user()->id)`).
- Savings goals are owner-gated centrally —
  `app/Http/Controllers/API/SavingsController.php:273-275`
  (`authorizeGoal()` → `abort_unless($savings->user_id === $request->user()->id, 403)`),
  invoked on show/deposit/withdraw/close (lines 103, 128, 163, 193).
- Payment requests block self-pay and enforce pending state —
  `app/Http/Controllers/API/PaymentRequestController.php` `pay()` (the
  `$paymentRequest->user_id === $payer->id` and `!isPending()` guards).
- Transfers reject sending to self —
  `app/Http/Controllers/API/TransferController.php:39-44`.

**Admin surface.**
The entire `/api/v1/admin/*` group is gated by the `admin` middleware alias —
`bootstrap/app.php:17` (`'admin' => AdminMiddleware::class`) applied in
`routes/api.php:340`. `AdminMiddleware` returns 403 (JSON) / login-redirect
(web) unless `auth()->user()->is_admin` —
`app/Http/Middleware/AdminMiddleware.php:13`.

> ⚠️ The admin check is a **single centralized seam** keyed on the boolean
> `is_admin` flag. There is no granular role/permission model yet — any user
> with `is_admin = true` has the full back-office. See §Known accepted risks.

---

## A02 — Cryptographic Failures

- **Passwords** are bcrypt-hashed via Laravel's `hashed` cast —
  `app/Models/User.php` casts block (`'password' => 'hashed'`). Login compares
  with `Hash::check` — `app/Http/Controllers/API/AuthController.php:45`.
- **Transaction PIN** is stored hashed (`Hash::make($request->pin)`) —
  `AuthController.php:186`; verified via `User::verifyPin()` (constant-time
  `Hash::check` under the hood), never compared in plaintext.
- **Biometric second factor** uses real public-key cryptography with **no new
  runtime dependency** — `app/Http/Controllers/Concerns/VerifiesTransactionAuth.php`:
  PEM RSA/EC verified via `ext-openssl` (`openssl_verify`, lines 149-159) with a
  raw-Ed25519 fallback via `ext-sodium` (`sodium_crypto_sign_verify_detached`,
  lines 163-173). The signed value is a fresh, single-use, server-issued
  challenge (5-min TTL) consumed on success (line 105) so a captured signature
  cannot be replayed.
- **Sensitive fields never serialize.** `app/Models/User.php` `$hidden`
  excludes `password`, the PIN, and 2FA secret/recovery codes from JSON output;
  API responses build from `UserResource`, not raw models.
- **HSTS / TLS** is emitted only over HTTPS (see A05) and enforced at the edge
  by the nginx vhost (`ssl.conf` include, HTTP→HTTPS 301).

---

## A03 — Injection

- **No raw SQL on user input in the money paths.** Wallet/transfer/savings
  access uses the Eloquent query builder with bound parameters
  (`Wallet::where('user_id', ...)->where('currency', ...)->lockForUpdate()`) —
  `app/Services/TransferService.php:107-129`,
  `app/Http/Controllers/API/SavingsController.php:84-90, 145-148`.
- **Validation on every write.** Each money endpoint validates types/bounds
  before touching state: transfer amount `0.01..100000` + currency `in:USD,SYP`
  (`app/Http/Requests/Transfer/TransferRequest.php` rules), wallet deposit
  `numeric|min:1|max:100000` (`WalletController.php` deposit), card load
  `numeric|min:1|max:10000` (`CardController.php` load), savings
  `numeric|min:0.01` (`SavingsController.php` deposit/withdraw).
- **File-upload typing.** KYC uploads are constrained by Laravel's `image` /
  `mimes:pdf,jpg,jpeg,png` + `max:5120` rules —
  `app/Http/Controllers/API/KycController.php` (`submitIdDocument`,
  `submitSelfie`, `submitAddressProof`).
- **Output encoding.** API responses are JSON (`shouldRenderJsonWhen` for
  `api/*` — `bootstrap/app.php:25`); the admin Blade UI relies on Blade's
  default `{{ }}` escaping.

---

## A04 — Insecure Design

- **Money mutations are atomic + race-safe.** Every balance change runs inside
  `DB::transaction` with the affected wallet rows pessimistically locked via
  `lockForUpdate()` — preventing double-spend / TOCTOU under concurrency:
  - P2P transfer: `app/Services/TransferService.php:107` (transaction),
    `:111`, `:121`, `:129` (sender + recipient wallet locks).
  - Card / Stripe Issuing authorization + capture + reversal:
    `app/Services/StripeIssuingService.php:425, 427` (auth),
    `:498, 502, 510` (capture), `:546, 548, 555` (reversal) — each locks the
    wallet row inside a transaction.
  - Savings deposit/withdraw/open: `app/Http/Controllers/API/SavingsController.php:68`
    (transaction) with the USD wallet locked at `:84-90` (open) and `:145-148`
    (deposit).
- **Second factor fails closed.** `VerifiesTransactionAuth::verifyTransactionFactor`
  returns `false` unless exactly one valid factor (PIN or verified biometric)
  is presented — `VerifiesTransactionAuth.php:37-55`. The historical bypass
  (any non-empty `biometric_token` skipping the check) is documented and
  removed in the file header (lines 11-27).
- **New-device hold.** A newly linked device cannot move money for 48h and not
  at all while pending/rejected — `app/Http/Middleware/EnsureDeviceCanTransact.php`
  (`isPending` → `device_pending`, `STATUS_REJECTED` → `device_rejected`,
  `isTransactionLocked` → `device_locked` with `locked_until`). Wired onto
  withdraw, transfer, card load/unload, savings deposit/withdraw,
  payment-request pay, CCPayment withdraw (`routes/api.php` — eight
  `->middleware(EnsureDeviceCanTransact::class)` attachments: wallet withdraw,
  transfer, card load, card unload, savings deposit, savings withdraw,
  payment-request pay, CCPayment withdraw).

---

## A05 — Security Misconfiguration

**Edge headers (authoritative in production): nginx.**
The deployed vhost includes `security-headers.conf` and emits HSTS, CSP, frame
options, etc. at the edge for every response —
`projects/carda-wallet/deploy/nginx/sakk-site.conf`
(`include /etc/nginx/conf.d/security-headers.conf;`), plus `server_tokens off`,
`autoindex off`, `merge_slashes off`, dotfile/`.git`/backup-extension denial,
and a per-IP connection cap.

**Application middleware: `SecurityHeaders` IS registered.**
`app/Http/Middleware/SecurityHeaders.php` is a complete, correct middleware
(CSP baseline `default-src 'self'`, `X-Frame-Options: DENY`,
`X-Content-Type-Options: nosniff`, `Referrer-Policy`, `Permissions-Policy`,
HTTPS-only HSTS — lines 27-64). Registered in `bootstrap/app.php:45` via
`$middleware->append(\App\Http\Middleware\SecurityHeaders::class)`. In
production these headers are also delivered by nginx at the edge; the
app-level fallback covers `artisan serve` / non-nginx paths.

**Session hardening** is env-driven and listed in `.env.example`:
`SESSION_SECURE_COOKIE`, `SESSION_HTTP_ONLY`, `SESSION_SAME_SITE`,
`SESSION_ENCRYPT` — set secure/strict in production (DEPLOY.md §3).

**Debug off in prod.** `APP_DEBUG=false`, `APP_ENV=production` (DEPLOY.md §3).
Dev-only endpoints self-guard: CCPayment test/info endpoints return 403 unless
`app()->environment(['local','development','testing'])` —
`app/Http/Controllers/Webhooks/CCPaymentWebhookController.php:120, 168, 211`.

---

## A06 — Vulnerable and Outdated Components

- **No vendored crypto/HTTP reinvention** in the trust-critical path: biometric
  verification uses bundled PHP extensions (`ext-openssl`, `ext-sodium`), not a
  third-party library — `VerifiesTransactionAuth.php:25-27`.
- Stripe signature verification uses the official `Stripe\Webhook` SDK —
  `app/Services/StripeIssuingService.php:710` (`\Stripe\Webhook::constructEvent`).
- Dependency currency is enforced by the Security office (Hamza) at the merge
  gate: `composer audit` / SCA must be clean before any merge to a protected
  branch (collective policy; out of scope for this app's own source).

---

## A07 — Identification and Authentication Failures

- **Sanctum bearer tokens** for the API — issued on register/login
  (`AuthController.php:28, 87`), rotated on `refresh` (`:121-122`), revoked on
  logout (`:102`) and on password change (`:158`, revokes all *other* tokens).
- **TOTP 2FA** with recovery codes, gated at login: if
  `two_factor_enabled` and no code, login returns a non-error 2FA challenge
  (`AuthController.php:60-70`); a wrong code returns 422 (`:73-78`).
- **Brute-force throttling** on auth: the auth group is rate-limited by
  Laravel's `throttle:auth` and the API by `throttle:api` —
  `routes/api.php:38, 41`. nginx adds coarse edge zones for login/otp/transfer
  (DEPLOY.md §7).
- **Disabled accounts** are refused at login —
  `AuthController.php:52-57` (`!$user->is_active` → 403).
- **PIN strength.** Transaction PIN is constrained to exactly 6 digits at set
  and change (`AuthController.php:169, 219`) and required (size 6) on withdraw
  (`WalletController.php` withdraw rules).

---

## A08 — Software and Data Integrity Failures

**Webhooks fail closed — unsigned/forged events never mutate balances.**

- **Stripe Issuing** verifies `Stripe-Signature` before doing anything; a
  missing/invalid signature **or an unconfigured secret** returns 401 and the
  event is dropped —
  `app/Http/Controllers/Webhooks/StripeIssuingWebhookController.php:32-35`,
  backed by `StripeIssuingService::verifyWebhookSignature`
  (`StripeIssuingService.php:702-716`: returns `false` when
  `$this->webhookSecret` is empty (line 705-707) and on any constructEvent
  exception (line 713-714) — fail-closed by construction).
- **CCPayment** double-gates: source IP must be whitelisted **and** the HMAC
  `Sign` + `Timestamp` headers must verify, else 403 / 401 before processing —
  `app/Http/Controllers/Webhooks/CCPaymentWebhookController.php:33-42` (deposit),
  `:66-75` (withdraw); `verifySignature` returns `false` when `Sign`/`Timestamp`
  are absent (`:95-97`).

> Reconciliation note: webhook *idempotency* (rejecting duplicate event IDs) is
> a tracked hardening item — see §Known accepted risks / follow-ups.

---

## A09 — Security Logging and Monitoring Failures

- **Audit trail on money movement.** Successful transfers and failures are
  written to the audit log — `app/Http/Controllers/API/TransferController.php:79-90`
  (`auditLog->logTransfer`) and `:105-115` (`logFailure`), via
  `app/Services/AuditLogService.php`.
- **Security-relevant webhook events are logged.** Invalid Stripe signatures
  (`StripeIssuingWebhookController.php:33`), rejected CCPayment IPs/signatures
  and processing errors (`CCPaymentWebhookController.php:34, 40, 48, 67, 73`),
  and disputes (`StripeIssuingWebhookController.php:241` — `Log::warning`).
- **Structured app logging** via the `daily` stack channel with rotation
  (DEPLOY.md §11); `LOG_LEVEL` tunable per environment.

---

## A10 — Server-Side Request Forgery (SSRF)

- The application does not take arbitrary user-supplied URLs and fetch them.
  Outbound HTTP is to **fixed, configured** provider endpoints only — Stripe
  (SDK base URL), CCPayment (`PAY_URL_BASE` from env), FCM — not to
  request-controlled hosts.
- The CCPayment webhook additionally pins acceptance to a configured IP
  whitelist (`CCPAYMENT_IP_WHITELIST`), narrowing the inbound trust boundary —
  `CCPaymentWebhookController.php:105-109`.

---

## Mass-assignment hardening (cross-cutting, A01/A03)

`$guarded = []` was **removed** from the User model and replaced with an
explicit `$fillable` allow-list — `app/Models/User.php:22-54` (the comment at
line 55 marks this as `SEC-002`). Privileged columns are therefore not blindly
mass-assignable except where deliberately listed.

> ⚠️ `is_admin` **is** present in `$fillable` (`User.php:47`). That is tolerable
> only because no public/self-service endpoint binds raw `$request->all()` into
> `User::create/update` for these fields — registration goes through
> `RegisterRequest` (`app/Http/Requests/Auth/RegisterRequest.php`) whose rules
> do not include `is_admin`, and profile update is a scoped controller path.
> Keeping `is_admin` out of any future request that mass-assigns user input is
> an invariant to preserve (see follow-ups).

---

## Defense-in-depth summary (edge ↔ app)

| Layer | Control | Source |
|-------|---------|--------|
| nginx | TLS, HSTS/CSP/frame headers, rate-limit zones, dotfile/backup denial, conn cap, private-storage internal-only | `deploy/nginx/sakk-site.conf` |
| middleware | admin gate, new-device 48h hold | `AdminMiddleware`, `EnsureDeviceCanTransact` |
| controller | ownership checks, validation, dev-endpoint env guards | API + Webhook controllers |
| service | atomic + `lockForUpdate` money mutations, signed webhooks | `TransferService`, `StripeIssuingService`, `CCPaymentService` |
| crypto | bcrypt passwords/PIN, openssl/sodium biometric, single-use challenge | `User`, `VerifiesTransactionAuth` |
| model | `$fillable` allow-list, `$hidden` secrets | `User` |

---

## Known accepted risks / follow-ups

These are **stated honestly**, not silently fixed. They are documented decisions
or owner-run backfills, not regressions introduced here.

1. **Device middleware fails OPEN on a missing `X-Device-Id` header.**
   `EnsureDeviceCanTransact::handle` returns `$next($request)` (no block) when
   the request carries no device header or an unrecognized device —
   `app/Http/Middleware/EnsureDeviceCanTransact.php:22-29`. This is a
   **deliberate** compatibility choice (legacy / web clients that never send the
   header are not locked out). **Accepted risk:** a client that simply omits the
   header bypasses the new-device 48h hold entirely. The second-factor
   requirement (PIN/biometric) still applies on those routes, so this weakens
   the *device-binding* layer, not the *authorization* layer. Follow-up:
   consider requiring the header for app (non-web) clients once the mobile
   fleet is fully migrated.

2. **RBAC is a single boolean seam.** Authorization for the entire back-office
   is `auth()->user()->is_admin` — `AdminMiddleware.php:13`. There is no
   role/permission matrix; any `is_admin = true` account is a full admin. A
   real role model (granular permissions, least-privilege admin tiers) is
   future work. Until then, treat `is_admin` grants as high-trust and keep
   `is_admin` out of any mass-assignable user-input path (see mass-assignment
   note above).

3. **(RESOLVED) `SecurityHeaders` middleware now registered globally.**  
   `bootstrap/app.php:45` appends `SecurityHeaders` to the global middleware
   stack, covering both `api` and `web` groups.

4. **KYC public-file backfill pending (owner-run).** KYC documents are correctly
   written to the **private** disk going forward —
   `app/Services/KycService.php:447, 448, 474, 502` (`->store(..., 'private')`),
   and served only via the internal `X-Accel-Redirect` location in the vhost.
   Any historical KYC artifacts that predate the private-disk convention must be
   migrated off any public location by the data owner; this backfill is not
   performed by application code and is tracked as an operational task.
