# SOFI Spec-Review — Integrations Hub (مركز الربط)

**Project:** SAKK — Payment / Wallet Platform · **Date:** 2026-07-02
**Reviewer:** SOFI CEO / Lead Solution Architect (Fable 5 gate) · Read-only 4-pillar sweep
**Verdict at review:** DENIED · **Phase-1 fixes:** LANDED (suite 1117/0) · **Phase-2:** ticket open

---

## 0. What this feature is

The Integrations Hub is SAKK's single admin console for every external-service connection:
payment gateway (CCPayment), card issuing (Stripe Issuing), push (FCM), messaging (SMS/Email),
OTP channels (WhatsApp via self-hosted OpenWA, Telegram bot), and location (Google Maps).
Two backing stores exist: an `integrations` table (rich rows, encrypted credentials) and a
`service_configs` table (SMS/Mail/Firebase-OTP/reCAPTCHA, encrypted). The admin brief demanded
this be "professional, flexible, DRY (بدون تكرار), across design/code/data/security/mobile/admin."

## 1. Method (token-frugal, two-phase)

- Phase 1 — static Python scanners (0 model tokens): `feature_scan.py` matched 33 files + 12 pre-flags;
  `sofi_automator.py` (7 steel rules) emitted a 12🔴/11🟡 raw skeleton.
- Phase 2 — Fable-5 gate: opened every flag, confirmed or refuted, added semantic findings the
  heuristics can't see. Refuted 6 static flags as false positives (N+1 on flat collections,
  Stripe ÷100 correct for USD, karat unique guarded, mobile DioException already mapped).

## 2. Findings (severity-ranked)

### 🔴 SEV-1 — Dead credential stores (fake-card-paradox applied to config) — FIXED
The panel presented ~9 services as manageable, but only 3 were wired to the runtime
(`ccpayment`, `stripe`, `notifications`/FCM). `ServiceConfig::forKey()` had **zero callers**;
`Integration` rows `email`/`messaging`/`google_maps` were unconsumed. Runtime OTP/mail/SMS
channels read `.env` exclusively. Consequence: an admin rotating a leaked SMTP/WhatsApp key
through the panel changed **nothing** in production, while the test button reported success.
This is the root cause of the brief's "بدون تكرار" concern — mail credentials existed in three
places (`integrations.email` row, `service_configs.mail` row, `.env`) with only `.env` live.

### 🔴 SEV-2 — `encrypted:array` cast stored into `json` columns — FIXED
`integrations.config`/`credentials` were `json` columns, but the model casts them `encrypted:array`.
Encrypted ciphertext is not valid JSON → INSERT rejected by MySQL 5.7+/PostgreSQL `json` columns.
The installer supports `sqlite,mysql,pgsql`, and seeders write credentials at install → **fresh
install crashes on 2 of 3 supported drivers.** sqlite (current dev) masked it. This was the true
blocker for Gate 6/7 production readiness.

### 🟠 SEV-3 — Crypt format mismatch corrupts email/messaging credentials — FIXED
A data migration wrote `Crypt::encrypt(json_encode(...))` (PHP-serialized) while the model cast
reads `decryptString`+`json_decode` → null credentials; it also read the messaging row with the
wrong decryptor → exception → fallback rewrote both rows in the wrong format. Latent only because
SEV-1 made the rows dead.

### 🟠 SEV-4 — Toggle OFF ≠ off (env fallback resurrects gateways) — PHASE 2
`CCPaymentService`, `StripeIssuingService`, `FCMService` fall through to `.env` credentials when
the Integration row exists but `is_active=false`. The admin kill-switch is silently ineffective
whenever env vars are set. Violates the exact doctrine `CardsFeature` documents.

### 🟠 SEV-5 — Steel-rule-1 breach: JSON fetches without `Accept` header — PHASE 2
Nine `fetch()` calls in the overview view send `Content-Type` but no `Accept: application/json`, so
`expectsJson()`/`wantsJson()` are false. (a) Validation errors return 302 → specific 422 message
lost. (b) Worse, service update/test gate their **success** responses on `wantsJson()` → they return
302 even on success → UI toasts failure while the data saved.

### 🟠 SEV-6 — "Connection test" is presence-check theater — PHASE 2
The test makes no network call — checks credentials non-empty, reports «✅ اتصال ناجح», and bumps
`last_synced_at` unconditionally (fresh timestamp even on failure). No `stripe` arm (the one live
money integration always "fails").

### 🟠 SEV-7 — Service credential changes leave no audit trail — PHASE 2
`updateService`/`applyServicePendingUpdate` write no audit log (contrast: `/admin/settings` logs
before→after). The `integrations` path logs to `integration_logs`, but those logs have **no admin
route or view** — invisible to ops.

### 🟡 SEV-8..12 — PHASE 2
Hidden-card filtered by editable display-name instead of `key`/`is_visible`; OTP verify
unthrottled and not token-bound; `error_count` only increments (sticky red status forever);
unreachable dead OTP branch; synchronous OTP mail throws 500 on SMTP failure (circular trap:
SMTP creds "managed" behind an email OTP).

## 3. Phase-1 remediation (committed, verified)

| SEV | Commit | Change |
|-----|--------|--------|
| 🔴 SEV-2 | `76b50fd` | `integrations.config/credentials` json→text in create-migration + driver-safe reversible `change()` alter for already-migrated DBs |
| 🟠 SEV-3 | `7228367` | `encryptString`/`decryptString` symmetry in email migration + idempotent repair migration for email/messaging rows (legacy-format recovery verified with real values) |
| 🔴 SEV-1 | `f44a73a` | New `ServiceConfigOverrideProvider` — ServiceConfig store now LIVE: value-level override of `services.{whatsapp,telegram,sms}.*` + `mail.mailers.smtp.*`/`mail.from.*`; row `is_active` = hard-off only when a row exists; per-key fail-open isolation (missing table / wrong APP_KEY / corrupt row can't take the app down); `SystemConfigSeeder` mirrors env active-state on first-create only and never copies secrets → zero-downtime for live WhatsApp/Telegram OTP |
| Tier-A | `e1d71ae` | 17 tests: `IntegrationControllerTest` (8) + `ServiceConfigOverrideTest` (9) |

**Verification:** full suite **1117 passed / 0 failed** (3189 assertions); `migrate:fresh --seed`
green on a scratch sqlite DB (live DB never touched). Ops notes: queue workers need `queue:restart`
after a ServiceConfig save; NEVER `route:cache`/`config:cache` on this dev box (env-gated CCPayment
test routes freeze at cache-build APP_ENV → 26-fail full-suite flake; re-diagnosed and cleared).

## 4. Steel-rule scorecard

| # | Rule | Verdict |
|---|------|---------|
| 1 | 422-JSON not 302 | FAIL (SEV-5, phase-2) |
| 2 | ApiException.fromDioError | PASS |
| 3 | /admin isolation in 503 | PASS |
| 4 | Unique constraints vs races | PASS |
| 5 | Money math sound | PASS (n/a-lean) |
| 6 | API ⇔ Flutter parity | PASS |
| 7 | ADR-004 Tier-A ≥90% | Phase-1 added 17 tests; suite green; hub-specific coverage now real |

## 5. Per-pillar verdict (post-phase-1)

- **① Data & Logic** — was broken (SEV-2/3), now **sound**: correct column types, symmetric crypto,
  repaired rows, dual-store dedup path opened.
- **② Admin & Ops** — was broken (SEV-1), now **functional**: the panel actually drives the runtime
  for OTP/mail/SMS. Still at-risk on SEV-4/6/7 (phase-2).
- **③ UI/UX & Taste** — at-risk: SEV-5 toasts failure on success; hardcoded hex ties into open
  UX-DEF-02 token sweep.
- **④ Edge Cases & Gaps** — at-risk: unthrottled OTP, sticky error state, synchronous OTP mail.

## 6. The ask (for external review desk)

Requesting a **detailed, professional solution-design** critique of the phase-1 architecture and a
prioritized plan for phase-2, specifically:

1. Is the `ServiceConfigOverrideProvider` (runtime config-override at boot, value-level, fail-open,
   `is_active` hard-off) the right architectural pattern for a fintech admin-managed integration
   store — versus alternatives (a config repository injected at consumer sites, a cached settings
   service, an event-driven config-reload)? Trade-offs for a Laravel 12 app behind Cloudflare + php-fpm.
2. The **dual-store** problem (`integrations` vs `service_configs` vs `.env`): what is the cleanest
   DRY consolidation that preserves the encrypted-at-rest guarantee and the OTP-step credential flow,
   without a risky big-bang data migration on a live wallet platform?
3. SEV-4 kill-switch design: how to make "toggle OFF = hard off" safe across CCPayment/Stripe/FCM
   without stranding the app if the admin misconfigures a row (fail-open vs fail-closed tension on a
   payment gateway).
4. Phase-2 sequencing and any risks we've under-weighted for a Tier-A (money/credential) surface.
