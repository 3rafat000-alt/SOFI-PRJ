# Security Report — Admin Login Hardening (Pass v2)

**Project:** PRJ-SAKK — SAKK Payment / Wallet Platform
**Date:** 2026-07-01
**Gate:** 5 (Quality) · **Surface:** Admin authentication (`/admin/login`, 2FA challenge)
**Head at close:** `1541362`
**Method:** `/sofi-spec-review` (4-pillar architect review) → `/sofi-fix` (routed specialists) → `/sofi-secure verify` (adversarial re-test)
**Classification:** Internal · financial-platform admin auth · owner-authorized review of this project only.

---

## الملخص التنفيذي (Arabic)

مراجعة معمارية عدائية لصفحة دخول الإدارة بعد جولة الأمان الأولى (head `d2ad861`) كشفت 5 ثغرات متبقية: ثغرتان بخطورة متوسطة (🟠) وثلاث تحصينات (🟡). تم إصلاح 4 منها فوراً والتحقق منها عدائياً، وتأجيل واحدة (واجهة تفعيل 2FA) كميزة مستقلة. أبرز الإصلاحات: إعدام «مُخبِر وجود الحساب» (Enumeration Oracle) بتوحيد رسائل الخطأ، وتجديد مُعرّف الجلسة عند بوابة 2FA لمنع تثبيت الجلسة (Session Fixation)، وفرض صلاحية زمنية (TTL) على منحة 2FA المعلّقة مع منطق fail-closed. **معيار Gate 5 لأمان دخول الإدارة: مُستوفى.**

## Executive Summary (English)

An adversarial architect review of the admin login feature — run *after* the first security loop closed (head `d2ad861`) — surfaced 5 residual findings the first pass left standing: two medium-severity (🟠) and three hardening items (🟡). Four were fixed and adversarially re-verified in this pass; one (a web 2FA enrollment UI) is deferred as a standalone feature. **Gate 5 admin-auth bar: MET.** Zero regressions on re-test.

---

## Findings Matrix

| # | SEV | Finding | File:line (pre-fix) | Defect | Remediation | Commit | Status |
|---|:--:|---------|--------------------|--------|-------------|--------|:--:|
| 1 | 🟠 | Account-enumeration oracle via lockout message | `AuthController.php:44-47` | Pre-auth `checkLockout` returned a distinct "الحساب مقفل مؤقتاً…" string for existing+locked accounts vs the generic string for unknown emails — a differential that confirms account existence. Reintroduced the leak the first pass had closed on the failed-auth branch. | Pre-auth locked branch now returns the identical generic string `'بيانات الدخول غير صحيحة.'`; lockout is still **enforced** (attempt blocked, no `Auth::attempt`), only the response is unified. | `1541362` | ✅ Fixed & verified |
| 2 | 🟡 | Session fixation at password→2FA boundary | `AuthController.php:69-75` | On a 2FA-required login, code did `Auth::logout()` then stashed `2fa.pending_id` into the **same** session id; regeneration happened only after full login. A fixed/attacker-supplied pre-login session id survived into the pending-2FA state. | `session()->regenerate()` added immediately after stashing the pending grant (`:86`), before the redirect. `put()` precedes `regenerate()` so pending data migrates intact. | `1541362` | ✅ Fixed & verified |
| 3 | 🟡 | Pending-2FA grant had no TTL | `AuthController.php:72` | `2fa.pending_id` persisted for the entire session lifetime; a password-verified-but-2FA-abandoned session left the partial grant alive indefinitely. | `const TWO_FACTOR_PENDING_TTL_SECONDS = 300`; `2fa.pending_at` timestamp stashed; `isPendingTwoFactorExpired()` checked in **both** `showTwoFactor()` and `verifyTwoFactor()`; on expiry the grant is cleared and login restarts. **Fail-closed**: a missing timestamp (legacy session) is treated as expired. | `1541362` | ✅ Fixed & verified |
| 4 | 🟡 | Login field errors not linked to inputs (WCAG 2.2 AA) | `login.blade.php:51,68` | Inline error `<p hidden>` was not associated to its input; a screen reader announced the field as valid while a visible error sat beside it. | `aria-describedby` on each input ↔ unique `id` on each error paragraph; JS/layout/styling untouched. | `0fe23b6` | ✅ Fixed & verified |
| 5 | 🟠 | Web 2FA gate is inert — no enrollment path | `routes/api.php:109-113` (enrollment) vs `AuthController.php:77` (gate) | The 2FA challenge blocks login when `two_factor_enabled`, but enrollment (`TwoFactorService::enable/confirm`) is exposed **only on the mobile API**. A web-only admin can never set `two_factor_enabled=true`, so the console's headline defense cannot be turned on — and 2FA can never be made mandatory. | **Deferred** — this is a feature (QR enrollment page + confirm + recovery-code display under `admin.profile` + backend web route), not a patch. | — | ⏸ Deferred (ticketed) |

---

## Verification (adversarial re-test @ `1541362`)

Static logic re-test of each fix against the shipped code:

- **F1** — `AuthController.php:53-54` returns byte-identical generic string to the unknown-email (`:106`) and failed-auth paths. No message differential remains; lockout enforcement preserved. **CLOSED.**
- **F2** — `:86` `session()->regenerate()` fires after `put()`; session data migrates, pre-login id discarded. **CLOSED.**
- **F3** — TTL const + `pending_at` stamp checked in both 2FA entry points (`:118`, `:138`); fail-closed on missing stamp (`:199-201`). **CLOSED.**
- **F4** — Blade compiles (`view:cache` clean); `aria-describedby` wired both fields. **CLOSED.**

**Regression sweep — clean.** `verifyTwoFactor:158` still uses the distinct `lockedOutResponse`, but that path is *post-correct-password* (the attacker already holds valid credentials), so it opens no new enumeration surface. Non-2FA admins and in-window 2FA logins: behavior unchanged. `php -l` clean.

---

## Residual Risk & Next Actions

| Priority | Item | Owner | Note |
|:--:|------|-------|------|
| 🟠 High | Build admin **2FA enrollment UI** (QR + confirm + recovery codes) + web route | `sofi-blade-architect` + `sofi-laravel-core-dev` | Blocks making 2FA mandatory; the verified TTL/session hardening protects a flow admins still can't turn on from the web. |
| 🟡 Med | Track `public/vendor` assets (Alpine + icons) so deploys aren't offline-broken | `sofi-devops-cloud-lead` | Carried from first pass. |
| 🟡 Low | `trustProxies('*')` runbook note — safe only while php-fpm stays loopback-bound | `sofi-devops-cloud-lead` | If php-fpm ever binds beyond loopback, `X-Forwarded-For` becomes spoofable → rate-limit + audit-IP bypass. Infra-frozen; document, don't loosen the FPM bind. |
| ⚪ Info | Pre-existing auth-timing side-channel (`Auth::attempt` hashes for existing users, skips for unknown) | — | Standard Laravel behavior, low signal, out of this pass's scope. |

---

## Risk Posture

**Gate 5 admin-auth bar: MET.** All four shipped findings verified closed with zero regressions. The single open item (2FA enrollment UI) is a defense-*enablement* gap, not an active vulnerability — the existing gate and its now-hardened session/TTL handling are correct; they simply protect a factor that web admins cannot yet enable. Recommend prioritizing the enrollment UI before any policy that mandates admin 2FA.
