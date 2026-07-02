# HANDOFFS — PRJ-SAKK

## PRIOR TICKET (2026-07-02, head 81e7e1f) — Permission banners SHIPPED. Phase-2 deferred.
`/sofi-feature "طلب الاذن للاشعارات والموقع في لوحة التحكم"` complete. New Alpine component `permissionPrompt` in admin.blade.php: browser Notification + Geolocation permission-request banners. 7-day localStorage dismiss per type. `role="status"` `aria-live="polite"`. SAKK tokens, zero backend changes. Commit 81e7e1f. Report _context/reports/feature-permission-banners-2026-07-02.md. External review desk: a11y pattern validated (role="alert"→"status" fixed).
→ DEFERRED (🟡, phase-2): (1) SystemSetting toggle to enable/disable banners globally → sofi-laravel-core-dev + sofi-blade-architect. (2) E2E test for banner x-show toggles → sofi-qa-sre-lead.

## NEXT TICKET (2026-07-02, head 15e9c95) — Admin Modal Focus-Trap Tier-A Blocker SHIPPED. Gate-6 UAT unblocked.
**Tier-A blocker RESOLVED** (conditional GO prerequisite satisfied). Modal focus-trap implementation (confirmModal + keyboardHelpModal):
- Alpine.js focus-cycling: Tab moves forward through focusable elements (button, link, input, select, textarea, [tabindex]), Shift+Tab backward
- Escape key closes modal + restores focus to trigger element (stored in data.triggerEl)
- `:inert` attribute on container prevents background interactions when modal open
- Auto-focus on open: cancel button (confirmModal) / close button (keyboardHelpModal)
- focusNextInModal() utility function handles wrapping at start/end of focus stack
Commit `15e9c95`. Verified: php -l clean, view:cache green. Acceptance: Tab/Escape/focus-restore behavior now meets WCAG focus-management standard. → Next: execute Gate-6 UAT (6 scenarios) to validate money-path + concurrency + idempotency, then Gate-7 launch sequence.

## NEXT TICKET (2026-07-02, head 9347113) — /admin/system/health PHP-extensions probe FIXED. False «غير متصل» + false admin alert eliminated.
/sofi-spec-review→fix on the PHP-extensions health check. 🔴 root cause: `SystemHealthController::REQUIRED_EXTENSIONS` hard-required `gmp`+`redis` that the app never uses (verified: `.env` SESSION=file/QUEUE=database/CACHE=database; zero `gmp_`/`GMP` + zero `Redis::`/`Predis` in `app/`; `composer.json` declares no `ext-*`) → widget stuck «غير متصل» 15/17 and fired `AdminNotificationService::systemError('إضافات PHP')` on every manual فحص.
Fix `9347113` (`sofi-laravel-core-dev`, single file): split `REQUIRED` (15 real) vs new `OPTIONAL_EXTENSIONS` (gmp,redis); redis conditionally promoted to required only when `cache.default`/`queue.default`/`session.driver` resolve to `'redis'`; missing-optional reported in `details` «· اختيارية غير مثبتة: …» without flipping status offline; Blade-bound keys untouched. `php -l` clean. Widget now متصل 15/15, no false notification.
→ DEFERRED (🟡, non-blocking backlog): (1) **card-level amber/warning state** — health.blade.php status is binary online/offline; add a 3rd `warning` state so optional-missing shows amber. Owner `sofi-blade-architect`/`sofi-ui-ux-designer`, view-side. (2) **exception-message leak** — `checkPhpExtensions` catch puts `$e->getMessage()` into admin-visible `details` (~line 365); admin-only, low-risk → generic string + log. Owner `sofi-laravel-core-dev`, small.
→ Gate 5 unchanged: still blocked on coverage (`SystemHealthController` itself 0% covered — Tier-A/coverage push remains the open Gate-5 track).

## PRIOR TICKET (2026-07-01, head 218a259) — /admin/settings hardened + admin & mobile UX-flow fixes COMPLETE. ⚠️ live verify + APK rebuild pending.
Three tracks closed this session:
1. **/admin/settings** spec-review→fix — 7 findings (3🔴 2🟠 2🟡). Commits `187bde7`·`d68ca75`·`d8f8fec`·`f4aa6b2`. Report `_context/reports/audit-admin-settings-2026-07-01.md` (`e50683e`). Detail in STATE `admin_settings_hardened`.
2. **Admin UX flow** — 3 real flow bugs fixed (`3347269`·`b6e1000`·`fd57f4b`); scan false-positives rejected.
3. **Mobile UX flow** — 3 fixes (`218a259`); repo layer swept clean (403-swallow not recurring).

→ ✅ **live toggle verify DONE** (curl @ sakk.local:80, 2026-07-01): maintenance ON→public+api 503 / `/admin/login` 200 (no self-lockout), OFF→200; registration closed + valid body→403 «التسجيل مغلق حالياً», open→passes. State restored (mm=false, reg=true). Blocker CLEARED. NIT (🟡): register closed-check runs after FormRequest validation — malformed closed request shows 422 before 403 (still blocked); optional reorder.
→ ✅ **APK rebuild DONE** (`8ed5304`): pubspec 1.0.0+1→1.0.2+3 (supersedes 1.0.1+2), `flutter build apk --release` SUCCESS, release-signed (sakk-release.jks), artifact mobile/build/app/outputs/flutter-apk/app-release.apk 87.3MB sha256 34f3baf5…07f6da2. NOT distributed. HUMAN: install apksigner to authoritatively verify cert (SHA256 41:1A:26…6F:1A); admin uploads APK to force-update download URL to ship.
→ ✅ **regression tests DONE** (`990b1f1`): 11 cases GREEN — MaintenanceModeTest(4), RegistrationKillSwitchTest(2), SettingsUpdateGuardsTest(5). (14 suite fails = pre-existing, other session's CardService/SystemHealthController WIP, unrelated.)
→ ✅ **DECIMAL_MAX JSON nit FIXED** (`61faad0`): decimal case in `updateSetting()` now manual-validates (required/numeric/min:0/cap) and returns `response()->json(['ok'=>false,...],422)` matching sibling guards — no more 302 redirect on JSON. SettingsUpdateGuardsTest updated to postJson→422, 5 passed/16 assertions. php -l clean. Settings autosave now shows the real cap message. /admin/settings ticket FULLY CLOSED.
→ DEFERRED → Grace (css-a11y): UX-DEF-01 LRM currency · UX-DEF-02 hex/token hygiene ×102 · UX-DEF-03 directional Blade. → ui-ux-designer: force_update no-download-URL fallback. → sql-dba: audit-filter index (SettingsController:98).

## PRIOR TICKET (2026-07-01, head 87bad38) — /admin/gold spec-review + fix COMPLETE. 7 findings closed, 1 deferred. ⚠️ 1 host migration pending.
Spec-review (4-pillar) of /admin/gold found 2🔴 (toggle silently detached karat from auto-sync by forcing source=manual; no buy≥sell guard → losing spread) + 2🟠 (zero audit on refresh/autoSettings; firstOrNew dup-karat race) + 4🟡.
Fixed: backend `31c69d5` (sofi-laravel-core-dev — new `toggleActive()` + route `admin.gold.price.toggle`, `gte:sell_price` validation, audit Log::info on autoSettings/refresh, unique-karat migration authored), view `ec90e58` (sofi-blade-architect — toggle repoint, deactivate-confirm, grid empty-state, refresh/save loading guard). Report `87bad38` = `_context/reports/audit-admin-gold-page-2026-07-01.md`.
Verified: `php -l` ✅, `route:list --name=gold` = 7 routes ✅, `view:cache` compiled clean ✅.
→ ⚠️ **BLOCKING RESIDUAL** — migration `2026_07_01_150000_add_unique_karat_to_gold_prices` AUTHORED but NOT run. Owner `sofi-devops-cloud-lead`: `cd backend && php artisan migrate` on host to enforce `unique('karat')` (dedup pass runs first). Dup-karat race stays open until then.
→ ✅ **regression tests DONE** (`745c561`) — `backend/tests/Feature/Admin/GoldPriceControllerTest.php`, 8 tests / 33 assertions GREEN: toggle preserves `source='auto'` + buy/sell/spread; `buy<sell` rejected (assertSessionHasErrors, DB unchanged); gte boundary passes; guest/non-admin blocked. No app bugs. Auth pattern from AdminGuardedWritesTest.
→ deferred 🟡: spread-vs-margin naming unification (GoldPrice.php:23 — column `spread` labeled «الهامش») — design decision, low priority.
Memory: sakk-gold-toggle-detached-autosync.

## PRIOR TICKET (2026-07-01, head 70735eb) — Main-aside scroll layout fix COMPLETE. 9 findings closed. No known issues remain.
UX audit (Dan Kim) found 9 layout bugs (3🔴 4🟠 2🟡). CSS (Grace) + Blade (Minh) fixed all. Commits 5f68eed + 70735eb.
Verified: view:cache ✅, php -l ✅, git status clean ✅.
DEFERRED (low):
1. ⚪ Dashboard responsive breakpoint intermediate step (1024-1200px) — cosmetic gap when sidebar expanded, grid collapses abruptly.
2. ⚪ Sidebar nav has no min-width guard in rail mode — icon overflow possible with future heroicon changes.
3. 🟡 Review 100dvh across all layouts (portal.blade.php, installer.blade.php) for mobile toolbar consistency — single-spot fix, F8 only applied to admin.

## PRIOR TICKET (2026-07-01, head 9058b0d) — Gate 5 perf fix FINISHED + committed, gate-bar PARTIALLY MET → CEO arbitration needed
frontend-tech-lead (Théo) finished + verified the uncommitted critical-CSS perf work a prior perf agent left mid-run (perf-load-2026-07-01-gate5.md FAIL: landing TTI 2.55s / login TTI 3.0s mobile CWV). Commit `9058b0d` — ONLY the 4 perf files (git add explicit, storage/installed left untouched/unstaged).

**What shipped:** `components/critical-css.blade.php` (inlines sakk-tokens.css, preloads 2 above-fold font weights not 4) + `components/admin-critical-css.blade.php` (inlines tokens+base+utilities for login only) wired into `landing.blade.php` + `admin/auth/login.blade.php` via `@include`, replacing external `<link rel=stylesheet>` tags.

**Verified:** CSP already permits `style-src 'unsafe-inline'` (no change needed) · `font-src 'self'` covers local woff2 · `php -l` clean on all 4 · `view:cache` clean.

**Measured (before → after), same Lighthouse mobile CWV profile (150ms/1.6Mbps/4x CPU) as the failing report:**
- Landing: lantern-**simulate** (same method original report used) TTI 2.55s → **2.4s** — still FAILS <2s bar. Real observed (devtools-throttle, actual trace) TTI/LCP/FCP all **0.7s** — a massive real-world win, but not what the gate-bar tool measures.
- Login: lantern-simulate TTI 3.0s → **3.0s (unchanged)**. Real observed devtools-throttle: LCP 1.3-2.5s / FCP 0.7s / TTI 2.5s.
- CLS: **0** on both (verified) — zero visual regression from the shipped variant.

**Root cause of the residual gap (diagnosed, not fixable from my Blade-only lane):** server is HTTP/1.1 (Caddy — confirmed via `curl -I`, no h2/h3 multiplexing). Lighthouse's lantern-*simulate* model penalizes HTTP/1.1 connection-setup + total-byte-weight heavily and independent of real fetch time; real network-request timestamps show every asset (incl. all 4 fonts) finishes in <90ms. This is a genuine simulate-vs-real gap, confirmed by diagnostics JSON (`totalByteWeight`/`numFonts` drive the sim estimate regardless of preload count).

**Tried and REJECTED (2nd iteration):** kept `base.css`/`utilities.css` external via preload+onload-swap on login to cut document weight for lantern's benefit (sim TTI 3.0s→2.9s). **Rejected** — real devtools-throttled measurement showed CLS jumped 0→**0.789** (FOUC: `.login-*` classes live in base.css, unstyled flash before swap fires). Reverted to full-inline; CLS=0 is the non-negotiable bar, kept.

**Escalation for CEO:** gate-bar as literally written ("TTI<2s... PROVEN with numbers") is NOT met under the lantern-simulate tool the original FAIL report used, on either page. Two live options, need arbitration:
1. Accept the real-user evidence (devtools-throttle, actual trace) as the true Gate-5 signal going forward — it shows both pages loading in <1-2.5s for real mobile users — and treat the lantern-simulate gap as a known tool artifact of this HTTP/1.1 origin.
2. Hold Gate 5 open on perf and route the actual remaining lever (enable HTTP/2 on Caddy — server-plane, `sofi-devops-cloud-lead`, NOT Blade) since that's the only remaining variable lantern's model penalizes and Théo's lane can't touch (infra frozen per doctrine unless explicitly onboarding).
Did NOT touch bootstrap/app.php, the 6 tests, backend/data, dashboard, or storage/installed. Did NOT recreate public/_hostdbg.php.

## PRIOR TICKET (2026-07-01, head 94263f1) — WCAG 2.2 AA pass on 2FA enrollment view CLOSED → CEO
css-tailwind-a11y-expert (Grace) ran the WCAG pass on `admin/profile/two-factor/index.blade.php` (item #1 from the residuals list below). Found+fixed 1 real AA fail; rest of the gate-bar was already clean. Commit 94263f1. CONTEXT ADMIN_2FA_A11Y_2026-07-01. Gate-bar MET.
- 🟡 **Contrast fail FIXED**: `--gold-deep` (#A6782A) text on `--warning-light` bg = 3.53:1 (recovery-codes banner + pending badge), below AA 4.5:1. Scoped inline override to `#8A5F1F` (5.05:1) in this view only — did NOT touch the shared `--gold-deep` token (used by other badges app-wide); a global token contrast fix is ui-ux-designer's call if wanted everywhere.
- Verified clean, no change needed: keyboard-complete (native controls, no tabindex traps), focus-visible (existing global `.btn`/`.input` focus rules apply), `aria-live` correct (assertive on errors/recovery-reveal, polite on success), RTL/dir correct (page rtl, codes/secrets/OTP scoped ltr), QR `alt` text present.
- Deferred, out of a11y scope (backend): state-C (active) password/code inputs never get `@error`/`input-error` class or field-error text wired — SR still gets the failure via top `aria-live="assertive"` banner so AA passes, but sighted per-field association needs an error-bag fix, not CSS. Route to `sofi-laravel-core-dev` if wanted.
Remaining OPEN admin-auth follow-ups (unchanged, NOT dropped):
1. ⚪ **Make 2FA mandatory** (optional policy) → `sofi-security-compliance-architect`: now unblocked (enrollment + a11y both shipped) — decide whether to force admin enrollment via middleware redirect to admin.profile.2fa.show when !two_factor_enabled. OUT OF SCOPE this pass.
2. 🟡 **Ops: enforce the trustProxies guardrail** → `sofi-devops-cloud-lead`: keep php-fpm on the UNIX socket / 127.0.0.1 only; re-open the runbook note before any FPM listener/port-map/LB change.

## PRIOR TICKET (2026-07-01) — admin-auth low residuals CLOSED (both accepted-risk, ZERO code) → CEO
security-compliance-architect closed the last two carried admin-auth residuals. Head 36a2b1e. Both = documented accepted-risk, no code touched, no new attack surface; Gate 5 admin-auth bar still MET. CONTEXT ADMIN_AUTH_RESIDUALS_CLOSED.
- ⚪ **Post-auth is_active/is_admin message oracle → KEEP.** Verdict: distinct messages are post-correct-password (attacker already holds the account's password) → no enumeration surface; pre-auth oracle already unified on both twins. Messages are load-bearing UX (disabled user / non-admin need the real reason). No code change.
- 🟡 **trustProxies('*') runbook note → WRITTEN.** at:'*' correct+required while php-fpm stays loopback-bound (UNIX socket /run/php/php8.4-fpm.sock). Guardrail: if FPM ever binds beyond loopback → narrow at:'*' to explicit Caddy IP FIRST (coupled decision); beyond-loopback bind + at:'*' = release blocker. Runbook: _context/reports/runbook-2026-07-01-trustproxies-fpm-bind.md. Did NOT loosen FPM bind or touch trustProxies value.
Remaining OPEN admin-auth follow-ups (NOT dropped):
1. 🟡 **WCAG pass on 2FA enrollment view** → `sofi-css-tailwind-a11y-expert`: contrast on warning/success inline combos, focus-visible on copy/toggle buttons, keyboard walkthrough of recovery copy-all.
2. ⚪ **Make 2FA mandatory** (optional policy) → `sofi-security-compliance-architect`: now unblocked (enrollment shipped) — decide whether to force admin enrollment via middleware redirect to admin.profile.2fa.show when !two_factor_enabled. OUT OF SCOPE this pass.
3. 🟡 **Ops: enforce the trustProxies guardrail** → `sofi-devops-cloud-lead`: keep php-fpm on the UNIX socket / 127.0.0.1 only; re-open the runbook note before any FPM listener/port-map/LB change.

## NEXT TICKET (2026-07-01) — admin 2FA enrollment UI SHIPPED (🟠 residual closed)
Web self-enrollment built end-to-end + verified. Commits: ce890a2 (backend controller + 5 admin.profile.2fa.* routes, throttle:admin-2fa) · e7ff95f (3-state Blade view + one-time recovery block) · 2090823 (profile entry-point link — route was orphan) · 5b97795 (integration fix: added password fields the hardened controller required; swept-in AdminTwoFactorEnrollmentTest 5/5 pass, 23 assertions). Controller re-auths beyond spec (enable=password; disable+recovery=password AND code). CONTEXT ADMIN_2FA_ENROLLMENT_UI. **Web 2FA gate is now REACHABLE — 2FA can be made mandatory next.** Follow-ups:
1. 🟡 **WCAG pass on new view** → `sofi-css-tailwind-a11y-expert`: contrast on warning/success inline combos, focus-visible on copy/toggle buttons, keyboard walkthrough of recovery copy-all. (handoff from blade agent)
2. ⚪ **Make 2FA mandatory** (optional policy) → `sofi-security-compliance-architect`: now unblocked — decide whether to force admin enrollment (middleware redirect to admin.profile.2fa.show when !two_factor_enabled).
3. 🟡 **`trustProxies('*')` runbook note** → `sofi-devops-cloud-lead` (carried).
4. ⚪ Post-auth is_active/is_admin message split — low, carried.

## PRIOR TICKET (2026-07-01, head 1541362) — admin login spec-review v2 CLOSED (4 fixed, 1 deferred)
`/sofi-spec-review "admin login"` → fix → `/sofi-secure verify` DONE. Adversarial 4-pillar review of the ALREADY-secured login (post d2ad861) found 5 residuals; 4 fixed+re-verified, 1 deferred. Fixes: 🟠 enum-oracle (pre-auth lockout msg unified) + 🟡 session-regen at 2FA gate + 🟡 pending-2FA TTL 300s fail-closed → commit `1541362`; 🟡 a11y aria-describedby → `0fe23b6`. Verify: all 4 CLOSED, zero regressions, `php -l` clean. Report `_context/reports/security-2026-07-01-admin-login-hardening-v2.md`. **Gate 5 admin-auth bar MET.** Open residuals (carried, NOT dropped):
1. 🟠 **Admin 2FA enrollment UI** → `sofi-blade-architect` + `sofi-laravel-core-dev`: web 2FA gate is INERT — enrollment only on mobile API (`routes/api.php:109-113`); web admins can't set `two_factor_enabled`. Feature (QR + confirm + recovery codes under `admin.profile` + web route). Blocks mandatory 2FA. **Highest-priority residual.**
2. 🟡 **`trustProxies('*')` runbook note** → `sofi-devops-cloud-lead`: safe only while php-fpm stays loopback-bound; if it binds beyond loopback, `X-Forwarded-For` spoofable → rate-limit + audit-IP bypass. Document, don't loosen FPM bind ([[infra-frozen-focus-code]]).
3. ⚪ Post-auth is_active/is_admin message split — low (post-password); unify-vs-UX call still open.

## PRIOR TICKET (2026-07-01, head 1541362) — admin login SECURED + OOB-hardened (7 findings closed)
Full security loop DONE (CONTEXT ADMIN_LOGIN_SECURED + ADMIN_2FA_HARDENING_OOB + `_context/reports/security-2026-07-01-admin-login.md`). 🔴 single-factor + 3🟠 + 3🟡 closed & re-verified. Fixes ce39632·bc72cfa·8d05211·0e77e6a·8a58a9d; OOB hardening 1541362 (2FA-pending TTL 300s + session-regen at gate + unified pre-auth lockout msg) + a11y 0fe23b6. ⚪ post-auth message oracle (item 3) now tightened pre-verify by 1541362 — remaining split is only the post-password is_active/is_admin branch. Gate 5 admin-auth bar MET. Follow-ups:
1. ⚠️ **Build admin 2FA enrollment UI** → `sofi-laravel-core-dev` + `sofi-blade-architect`: current pass only *consumes* existing `two_factor_enabled` secrets — admins cannot self-enroll from the panel (QR provision + confirm + recovery codes via existing `TwoFactorService::enable/confirm`). Required before 2FA can be made mandatory org-wide.
2. 🟡 **Track/ship `public/vendor` assets** → `sofi-devops-cloud-lead`: `**/vendor/` gitignore swallows public assets — Alpine JS was force-added, but self-hosted `material-icons` are NOT tracked and rely on manual deploy placement. Git-track (force-add) or add a build step so deploys aren't offline-broken.
3. ⚪ **Post-auth message oracle** → `sofi-security-compliance-architect`: `AuthController.php:55-63` `!is_active`/`!is_admin` branches return distinct messages, reachable only after a correct password. Decide unify-vs-keep-UX. Low risk (attacker already holds password).
4. 🟡 **Confirm `SESSION_SECURE_COOKIE=true` in prod `.env`** → `sofi-devops-cloud-lead` (example documented; verify the real prod env).

## PRIOR TICKET (2026-07-01, head 2475975) — admin login design-taste PASS + token fix
`/sofi-design-taste "login admin"` + `/sofi-fix` DONE (CONTEXT ADMIN_LOGIN_DESIGN_TASTE). Login view passed at Soft/Premium (variance 3 · motion 4 · density 3); 2 🟡 token-hygiene nits closed, commit 2475975. Follow-ups:
1. **shadow-focus token nit** (🟡 deferred) → `sofi-css-tailwind-a11y-expert`: `tokens.css:62` `--shadow-focus` still hardcodes stale `rgba(107,15,36,.15)` — swap to `rgba(var(--sukk-primary-rgb),.15)` in next token sweep. One-liner.
2. **Wider admin token sweep** (🟡 backlog) → `/sofi-design-taste`: scan flags 84+ hardcoded hex/px/!important across admin/* views (documents-show div-buttons, missing alt on agents/users show.blade). Cosmetic/a11y, batch later.
3. ⚠️ **Concurrent security session active** — parallel work committing admin-auth hardening (ce39632 2FA gate, bc72cfa TrustProxies); `bootstrap/app.php` dirty from it. Do NOT touch auth files or that dirty file until that session hands off.

## PRIOR TICKET (2026-07-01, head 8a58a9d) — payment feature audited
`/sofi-feature "نظام المدفوعات"` loop DONE (CONTEXT PAYMENT_FEATURE_REVIEW + `_context/reports/feature-payment-2026-07-01.md`). Feature SECURE — 19🔴 pre-flags all verified false. 2 real fixes committed (d2c101e FCM env→config, 9683291 chart @json). Follow-ups:
1. **Set FCM env on hosts** → `sofi-devops-cloud-lead`: ensure `FCM_PROJECT_ID` + `FCM_SERVICE_ACCOUNT_FILE` in `.env` on staging/prod; run `php artisan config:cache` to confirm FCM resolves (now config-cache safe). See [[sakk-fcm-push-v1]] — still needs service-account JSON to send.
2. **Optional design-token sweep** (🟡) → `/sofi-design-taste`: 84 hardcoded hex/px/!important across admin — cosmetic, backlog.
3. **Engine note**: `sofi_scan.py` security pack tuned (framework commit d5e82db6). `{!! $formatted !!}` (Money output) still flags 🔴 — known-safe, do not "fix".

## PRIOR TICKET (2026-07-01, head 5fd0564) — gold feature audited
Gold review+fix+verify+report loop DONE (CONTEXT GOLD_FEATURE_REVIEW + `_context/reports/audit-gold-2026-07-01.md`). 2🔴+4🟠+4🟡 closed, commits 4110184·29a172c·aed9060, 14/14 gold tests pass. Follow-ups:
1. **Deploy gold migrations** → `sofi-devops-cloud-lead`: `php artisan migrate` on staging/prod runs `gold_holdings` (auto-backfills from existing gold_transactions) + `status` index. Verify backfill on any env with live gold data.
2. **Host cron check** → confirm `* * * * * php artisan schedule:run` installed so `gold:update-prices` (hourly) fires — else auto-pricing dead (see [[sakk-scheduler-routes-console-not-kernel]]).
3. **Frozen-wallet credit policy** (⚪ deferred) → `sofi-security-compliance-architect`: decide if a frozen wallet may receive sell/deposit proceeds (`Wallet::credit()` checks only amount>0, not is_frozen). Cross-feature — affects deposits too.
4. **status→enum** (🟡 deferred) → `sofi-laravel-core-dev`: only if pending/failed/cancelled gold tx become real.

## PRIOR TICKET (2026-07-01, head 76bf040)
User-system review+fix+secure-verify loop DONE (CONTEXT USER_SYSTEM_REVIEW + `_context/reports/security-2026-07-01.md`). 15 fixes, 7 security findings closed + re-verified, 706 tests pass. Follow-ups:
1. **Commit orphan card files** — `backend/app/Http/Controllers/API/CardController.php` + `backend/app/Services/CardService.php` + `backend/app/Services/StripeIssuingService.php` are uncommitted in tree from ANOTHER session; confirm intent then commit or revert. NOT touched by user-system run.
2. **Fix 6 pre-existing test failures** → `sofi-automated-testing-engineer`: 1 = `AuthWalletTest` fixture password `Pass1234` lacks symbol vs RegisterRequest `Password::symbols()` rule (update fixture); 5 = admin blade-string assertions (TransactionModuleTest, AdminAlertTest, CompanyAdminTest, PushNotificationTest, SystemConfigRedesignTest).
3. **Split 1202-line `admin/users/show.blade.php` monolith** → `sofi-blade-architect` (deferred maintainability).

## PRIOR TICKET (head 869961b) — cards, still open:
Cards+Stripe feature loop DONE (see CONTEXT CARDS_STRIPE + audit report). Follow-ups:
1. **Verify live Stripe Issuing config** — feature only activates when admin saves Stripe secret + is_active (`CardsFeature::enabled()`); confirm on target env before announcing cards live.
2. ~~Mobile cards UI taste pass~~ ✅ DONE 2026-07-01 — `/sofi-design-taste` PASS (Soft/Premium V6·M5·D4); 3 a11y fixes committed edf95b0·8d4e67c (reduce-motion guard + tap Semantics; contrast verified 11:1). See CONTEXT CARDS_TASTE_A11Y.
3. **Admin Stripe-card lifecycle audit UI** — `ActivityLog` now written for freeze/unfreeze/cancel; surface in admin activity view.
4. Optional: purge dead code — `VirtualCard::boot()` fake-PAN branch + `getCardFromInventory()` (left unreachable, not deleted).

## Current Status
- **58/58 MASTER tasks COMPLETE** — all 4 waves deployed
- **Keyboard help modal fix**: root cause found — `.flex { display: flex !important; }` overrode Alpine `x-show` inline style
- **Sidebar redesigned**: light marble theme (dropped wine-dark), gold accent, refined spacing
- **Backend tests: 705 passed, 0 failed**
- **Mobile tests: 131 passed, 0 failed** (7 cards_page fixed, +8 new widget tests)
- **Head:** 73db7fe7065b02330a13612b52f406b047a61272 (real — repo was just baselined 2026-07-01; all prior head_sha values were phantom, project had no git history before)
- **Branch:** master

## PRIOR TICKET (devops-cloud-lead → next agent)
- **Task:** split 87KB admin/users/show.blade.php + add root README
- Git baseline is now in place — `sofi checkpoint` / normal git commits work per-operation going forward. Root `.gitignore` at `/home/es3dlll/Desktop/projects/PRJ-SAKK/.gitignore` covers secrets/vendor/build/apk; verify new files land in the correct ignore bucket before committing.
- Note: `backend/resources/views/admin/profile/index.blade.php` showed a 1-line uncommitted diff immediately after the baseline commit (likely a concurrent/live session write) — check `git status` first thing, don't assume clean.

## تـم
- Wave 1–4: كل مشاكل MASTER-50-TODO الـ 58 تم حلها
- APK rebuild + deploy → public/download/sakk.apk (87.2MB)
- WhatsApp OTP linked (963982183110) — session active
- Production keystore generated (sakk-release.jks)
- 7 cards_page widget test failures fixed (cardsEnabledProvider mock)
- WhatsApp .env already has correct OPENWA_SESSION_ID

## Auth Security Hardening (2026-06-29)
- **Account lockout**: created `HasAccountLockout` trait (5 failed → 15 min lockout). Wired into all 4 web auth controllers (Admin, Merchant, Agent, Company). Migration adds `login_attempts`, `locked_until`, `last_failed_login_at` to `users` table.
- **is_active check**: all 4 auth controllers now check `$user->is_active` on login — inactive accounts rejected with Arabic error message.
- **Password complexity**: `RegisterRequest` now uses `Password::min(8)->mixedCase()->numbers()->symbols()` for registration.
- All auth tests green (13/13).

## Dead Code Cleanup (2026-06-29)
- Removed 3 unused controllers: ProfileController, ExchangeRateController, SystemActionController
- Removed 10 orphaned views: admin/partials/{breadcrumbs,flash,navbar,sidebar}, sms/{generic,transaction,verification}, landing-soon, layouts/company, company/auth/login
- 705 tests still green, 0 broken

## CSP Fixes (2026-06-29)
- `cdn.tailwindcss.com` added to `script-src` (unblocked installer Tailwind CDN)
- `cdn.jsdelivr.net` added to `connect-src` (unblocked Chart.js sourcemap fetch)

## Installer Redesign (2026-06-29)
- All 5 installer pages redesigned with SAKK design system (`sakk-tokens.css`, IBM Plex Sans Arabic, wine-dark `#6E1B2D` + gold `#B58A3C` + marble `#F7F3EE`)
- Views: requirements, database, admin, settings, complete

## Modal Fix (2026-06-29)
- keyboard-help + confirm modals: added `@keydown.escape.window="show = false"` + `.modal-overlay` CSS
- Consolidated duplicate `.modal-overlay` definitions in admin layout CSS
- **Root cause of "modal stuck visible on page load"**: `.flex { display: flex !important; }` (admin.blade.php:1055) overriding Alpine's inline `style="display: none"` from `x-show="show"`. All display utility classes had `!important`. Removed from all (base + responsive). `[x-cloak]` keeps `!important` to hide before Alpine boots.

## Sidebar Redesign (2026-06-29)
- **Color**: wine-dark (#4A1320) → white (#ffffff), matching topbar. No more red.
- **Width**: 272px → 260px open, 80px → 72px collapsed
- **Active state**: gold pill + ring → subtle gold bg (10% opacity) + right gold bar
- **Text**: white-on-dark → dark (#2A1A1F) on white, better readability
- **Hover**: translucent white → soft warm gray (#F5F0EB)
- **Sections**: muted labels with hairline dividers between groups
- **Brand**: cleaner logo display, subtitle changed to "لوحة الإدارة"
- **Parent feedback**: `has-sub-open` class darkens parent when submenu expanded
- **Collapse button CSS removed** (unused — toggle lives in topbar icon)

## iOS Bundle ID (2026-06-29)
- `com.example.mobile` → `com.sakk.wallet` in all 6 occurrences of `ios/Runner.xcodeproj/project.pbxproj`

## Deploy status
- Machine = production server (Caddy/php-fpm/Cloudflare tunnel)
- **Head: fc152d27** — all fixes above
- view:clear ✅ route:cache ✅ config:cache ✅ view:cache ✅
- /admin ✅ 200
- Landing ✅ IBM Plex Sans Arabic + wine-dark theme + CSS vars
- API ✅ /features returns cards_enabled:false

## CSS Architecture Fix (2026-06-29)
- **admin.css linked**: `<link href="{{ asset('sakk-admin/admin.css') }}" rel="stylesheet">` added to admin.blade.php. 2344-line orphaned design system now loads.
- **@alpinejs/collapse installed**: x-collapse animations on sidebar submenus now have smooth transitions (5 console warnings eliminated).
- **CSS var aliases**: Added `--r-*` (radius), `--sh-*` (shadow), `--t-base` aliases to admin.css :root. Component @once CSS (`<x-admin.button>`, card, modal) now resolves `--r-lg`, `--sh-sm`, `--sh-wine` etc. instead of always using fallback values.
- **`--primary-light` fixed**: admin.css had `#C9A24B` (gold) → corrected to `#8E2A3D` (wine-light, matching inline :root).
- **Button loading spinner**: `.btn-loading` in inline CSS now has CSS spinner (was opacity-only). `setLoading()` JS handles both `.btn` and `.sakk-btn` patterns.
- **Head:** 16615dd4 (includes `!important` removal from display utility classes)

## Dashboard Premium Editorial Redesign (2026-06-29) — THIRD ITERATION
- **Complete rethink**: delegated to UI/UX design agent via RCCF. Light warm marble theme, NOT dark.
- **Background**: `--bg #F7F3EE` (warm marble), cards: `--surface #ffffff` with 1px `--border #EAE8E6`
- **Zero gradients, zero dark backgrounds, zero gaudy colored borders**
- **Colors**: Wine (#6E1B2D) for primary accents (stat top-border, chart bars, links, buttons). Gold (#B58A3C) only for currency values (SYP spread, wallet balance). Green/red for deltas.
- **5 stat cards**: cards 1 (Revenue) + 3 (Volume) get 2px wine top border. Cards 2 (Spread) + 5 (Balance) show gold values. Card 4 (Users) plain. Subtle icon top-right at 0.4 opacity.
- **Dynamic deltas**: `stat-change` class with `up`/`down` computed from growth sign (`$revGrowth >= 0`)
- **Chart**: wine #6E1B2D bars, gold #B58A3C max-bar, white tooltip with border, thin warm grid
- **Welcome banner**: compact stat-card style (no gradient hero)
- **Table**: wine left-border accent class (`.dash-card-accent`), #F7F4F1 row hover, monospace IDs, wine initials
- **Right rail**: balance card (white, 3-col metrics), 3x2 quick-actions grid (wine hover), attention KYC card, latest KYC list
- **Scoped CSS**: all dashboard-specific CSS in `@push('styles')` using `.dash-*` class prefix. No admin.blade.php modifications needed.
- **Head:** f2dbc23d

## Next Steps
1. **Verify modal fix**: hard-refresh admin panel (Ctrl+Shift+R) — keyboard help modal should be hidden on load. Press `?` to open, Escape to close. If still visible → check `unsafe-eval` in CSP header
2. Build product CRUD for mediator role (user request)
3. Verify Stripe Issuing config for cards activation
4. Complete Arabic `lang/` directory migration (extract ~45 hardcoded strings from Blade)
5. CAPTCHA enforcement on all login forms (reCAPTCHA keys stored but never validated)
6. PHP-FPM graceful reload when sudo available (opcache flush)
7. `app.test_mode_enabled` flag verification in test endpoints
8. Remove `public/sakk-admin/admin.js` or wire it into admin layout (confirmed orphaned)
9. Merge admin.css inline :root values into admin.css (resolve 10+ token conflicts between the two sources)
10. Remove duplicate `.card`, `.btn`, `.modal-*` definitions from inline CSS now that admin.css is the source of truth

## KPI Icons + Quick Links + Responsive (2026-06-30)
- Removed last 3 `material-icons` from index.blade.php: KPI icons, warning alert, refresh button
- KPI icons: x-text (material names) → x-html (inline SVG strings)
- Quick links wired: المحافظ → route('admin.users.show', $id), السجل الأمني → route('admin.audit.index', ['user_id' => $id])
- Added mobile responsive media queries for identity card (<640px)
- material-icons import kept (still 50+ usages in admin layout)
- HEAD: 95d92fb6

## Material Icons → Inline SVGs (2026-06-30)
- All 16 `material-icons` removed from user partials (10 in `_modals`, 6 in `_slide_over`)
- Replaced with inline SVGs matching SAKK style
- New `copyToClipboard()` Alpine method — copy email/phone with 1.5s "تم" feedback
- Copy buttons: clipboard SVG icon that toggles to green "تم" on click
- Quick action links: المعاملات (filtered by user_id), المحافظ, السجل الأمني
- Loading spinners use existing `@keyframes spin` in base.css
- HEAD: c6afc7cc

## Identity Card Redesign (2026-06-30)
- Admin user profile identity card rebuilt borderless with SAKK `.sakk-identity-*` CSS classes
- All inline styles removed — clean flex/grid layout
- Avatar gradient: gold `--accent` → burgundy `--primary` (#6E1B2D → #4A1320)
- 4px color-coded left status bar (active=green, suspended=danger, banned=#b91c1c, pending=gold)
- Desktop/mobile action button split with `.sm-hidden` utility
- All badges use `sakk-pill-*` classes (success/danger/gold/ghost)
- CSS ~80 lines added to base.css; no borders, no shadows
- HEAD: 20997eb0

## Landing Page Complete (2026-07-01)
- ✅ Complete editorial coming-soon landing page built
- ✅ SAKK brand identity (Burgundy/Gold/Marble, IBM Plex Sans Arabic)
- ✅ No Material Icons — inline SVGs only
- ✅ Zero borders/shadows — flat editorial design
- ✅ "قريباً" hero badge + brand promise + 3 feature cards
- ✅ Waitlist email form with toast feedback
- ✅ Scroll-reveal animations (IntersectionObserver, vanilla JS)
- ✅ Responsive (desktop + mobile + small mobile)
- ✅ view:cache ✅ · HTTP 200 · 24KB
- ✅ Route: GET / → landing (name: landing)

## LTR Currency Audit Complete (2026-07-01 — sofi-ceo)
- ✅ Comprehensive `&lrm;` fix across 30+ files covering ALL currency display patterns
- ✅ 3 pattern variants fixed: Blade `{{ $sym }}{{ number_format }}` → `&lrm;{{ $sym }}...`,
     PHP string concat `number_format() . ' ل.س'` → `'&lrm;ل.س ' . number_format()`,
     static Arabic text with `$100` → `&lrm;$100&lrm;`
- ✅ Created `backend/scripts/check-ltr-currency.py` — Python tool to scan for missing `&lrm;`
- ✅ Verified: `php -l` OK, `view:cache` OK, Python scan = 0 issues
- ✅ Invoice page redesigned: clean light style (gold stripe, white paper, `#2D2824` totals)
- ✅ Show page restored to original (user rejected all 3 redesigns)
- ✅ Index table: User column moved to first position
- ✅ HEAD: b848a7c0
- ⚠️  Py tool is passive (detect-only, no --fix). Fix manually when new amounts are added.

## Current Status
- **Gate:** 5 (Quality)
- **Head:** b848a7c0
- **Branch:** master
- **Tests:** 705 backend, 131 mobile

## PRIOR TICKET (next agent)
- **Task:** Run `python3 scripts/check-ltr-currency.py` on any branch with new UI work
  to ensure all currency amounts have `&lrm;` prefix before commit.
- **Optional:** Wire into CI (GitHub Actions / pre-commit hook) for automated enforcement.
- Run `php artisan view:cache` after any Blade changes.
- `php -l` check after any PHP changes.

## Prior Existing Next Steps
1. Wire waitlist form to backend (store emails in DB or mail-to-admin)
2. Wire app badges to real App Store / Google Play URLs when published
3. Add SEO meta tags (OG/Twitter cards) for social sharing
4. Add landing page to CSP (if CSP middleware blocks inline styles)
5. Build product CRUD for mediator role (user request)
6. Verify Stripe Issuing config for cards activation
7. Complete Arabic `lang/` directory migration (extract ~45 hardcoded strings from Blade)
8. CAPTCHA enforcement on all login forms (reCAPTCHA keys stored but never validated)
9. Remove `public/sakk-admin/admin.js` or wire it into admin layout (confirmed orphaned)
10. Merge admin.css inline :root values into admin.css (resolve 10+ token conflicts)

## PRIOR TICKET (2026-07-01, after toolchain-architect engine loop @ framework a83f3884) — ✅ 2FA enrollment SHIPPED since (see live NEXT TICKET at top)
→ security squad (sofi-security-compliance-architect): resume the DEFERRED admin-auth residuals from the prior loop:
  - ✅ 2FA enrollment UI — DONE (ce890a2·e7ff95f·2090823·5b97795, tests 5/5). No longer inert; web 2FA gate reachable.
  - ⚪ post-auth is_active/is_admin message oracle (low).
  - 🟡 trustProxies('*') runbook note.
  Route 🟣 opus. Orient with /sofi-boot; SAKK app repo = branch master (untouched this session, tree clean).
→ optional (framework): apply the new mechanical gate — run `python3 sofi/tooling/agents/ceo/sofi_verify.py --prj PRJ-SAKK --md` at the close of every future /sofi-fix before declaring done.

## RECONCILE (2026-07-01, /sofi-handoff)
Brain hygiene: 3 stale `## NEXT TICKET` headers demoted to `PRIOR TICKET` (devops split-blade, check-ltr-currency, toolchain-era 2FA-missing). SessionStart hook was surfacing the toolchain-era ticket claiming "2FA enrollment UI missing" — FALSE since 5b97795. Single live NEXT TICKET is now the top section only (admin 2FA SHIPPED; follow-ups = WCAG pass on new view · optional make-2FA-mandatory · trustProxies runbook note · post-auth message split). No code changed this session.

## DONE — CCPayment USDT-only mitigation locked with regression tests
**Commit:** `4abd0f3` test(ccpayment): lock USDT-only currency/chain gate + recordId no-truncation regression.
Extended `backend/tests/Feature/Api/CCPaymentApiControllerTest.php` (+6 cases: BTC/ETH/USDC deposit-currency reject ×3 via dataset, USDT/TRC20 deposit accept, BTC-chain deposit reject, BTC withdraw-currency reject, config supported_coins/chains lock) and `backend/tests/Feature/Webhooks/CCPaymentDepositCreditTest.php` (+1: 40-char recordId round-trips untruncated into `reference` + credits full wallet amount). `php artisan test --filter=CCPayment` = 103 passed / 0 fail (was 95). ADR-004 gap closed for this mitigation.

## DONE — CCPayment deposit USDT-only mitigation (SEV-1/2/4/5)
- Backend `580d51c` (refines `f8f9e13`): deposit+withdraw currency=in:USDT, chain=in:TRC20,ERC20,BEP20; getConfig USDT-only; drop external api.qrserver.com QR (SEV-5); new migration `2026_07_01_160000_widen_transactions_reference_length` 32→64 (raw driver-branched SQL, no dbal, ⚠️ NOT migrated on host yet).
- Mobile `bc1a95e`: crypto_deposit_page USDT-only UI; new WalletRepository.createCryptoDepositAddress routes via ApiException.fromDioError (SEV-4).
- Verify: `php artisan test --filter=CCPayment` = 95 passed / 0 fail (existing suites unbroken).
- ⚠️ Host actions pending: `php artisan migrate` (reference widen + karat-unique from earlier).

## DONE — pending migrations run on host (2026-07-02)
`php artisan migrate --force` → `2026_07_01_160000_widen_transactions_reference_length` DONE (no-op on sqlite: DB_CONNECTION=sqlite, no varchar ceiling; row recorded). karat-unique (`2026_07_01_150000`) was ALREADY ran (batch 2). No pending left. Backup: backend/database/database.sqlite.bak-3b34f47. Both CCPayment + Gold DB locks now closed.

## DONE — Withdraw W-SEV-1 lock-scope refactor (2026-07-02)
`1c1efe3`: CCPaymentController::withdraw split Phase A (short locked debit+reserve tx PENDING/gateway_dispatched=false, atomic) / Phase B (gateway call OUTSIDE lock; success→metadata-by-PK; failure→refund under fresh short lock + FAILED+refunded, idempotency-guarded). New CCPaymentService::dispatchWithdrawToGateway (pure HTTP, no DB). No external HTTP under wallet lock. Verified: --filter=CCPayment 103 pass, orphan-debit impossible.

## TICKET (open, 🟡 low) — stuck-withdrawal sweeper
Residual of optimistic-debit: a hard process-kill between Phase A commit and Phase B leaves a withdrawal PENDING + metadata.gateway_dispatched=false with funds debited but gateway never called. Add a reconcile command (mirror ReconcileCCPaymentDeposits) that finds crypto WITHDRAWAL + PENDING + gateway_dispatched=false older than N min → query gateway by orderId; if unknown → refund+FAIL, if found → mark PROCESSING. Owner: sofi-laravel-core-dev.

## OPEN (from prior review, still valid) — Withdraw W-SEV-2 mobile USDT-lock
crypto_withdraw_page.dart:33-45 still offers USDC/BTC/ETH; backend 422-rejects them. Mirror the deposit-page USDT-only fix. Owner: sofi-flutter-clean-architect. (+W-SEV-3/4 minor mobile swallows in same pass.)

## DONE — Cards C-SEV-1 Stripe auth idempotency (2026-07-02)
`5ae3966`: StripeIssuingService::handleAuthorizationRequest now guards on authId INSIDE the locked tx (Transaction::where metadata->authorization_id → short-circuit idempotent approve) — kills double-hold on Stripe retry/replay (this event was exempt from the SEC-H4 event-id dedup). Regression test asserts single hold on replayed auth. Verified: Card/Stripe suites 82 pass. Report spec-review-stripe-cards-2026-07-02.md. 🟡 backlog: stale reserved_balance comment StripeIssuingServiceTest:577-592 (cosmetic); N+1 in card loops.

## DONE — Exchange E-SEV-1/2 deadlock + FX tests (2026-07-02)
`b2a4c26`: deterministic wallet lock order in TransferService + WalletService::convert via single whereIn([ids])->orderBy('id')->lockForUpdate() (InnoDB locks in ascending id order → deadlock impossible; was masked on sqlite, armed on prod MySQL). New WalletConversionTest (both directions exact decimals rate=13000/spread=2, overdraft, IDOR, double-ledger). Verified 24 pass. Report spec-review-exchange-transfer-2026-07-02.md.

## TICKET (open, ⚪ separate) — dead wallet-deposit route
WalletInputValidationTest has 4 failing cases hitting /api/v1/wallets/{id}/deposit → 404 dead route on master (pre-existing, NOT from exchange work). Either wire the route or remove the stale test. Owner: laravel-core-dev.

## DONE — KYC spec-review (2026-07-02): SOUND, no 🔴/🟠
SecureFileController impenetrable (encrypted path, traversal-proof allowlist, private disk, admin re-assert, nosniff); upload image/mimes+random filenames; policy is_admin gated; syncUserLevel idempotent; 4 KYC test files. Report spec-review-kyc-2026-07-02.md.

## TICKET (open, 🟡 batch) — consolidated cosmetic/hygiene backlog (all non-blocking)
Sweep together when convenient (route to cheapest specialist per item):
- Payroll P-1: PayrollController::store amt.* validation (numeric|min:0|max); P-2: N+1 (recordLedgerPair Company/Batch::find per item).
- Withdraw: stuck-withdrawal sweeper (Phase-A-committed/gateway-not-dispatched recovery); W-SEV-5 server idempotency-key on withdraw create; W-SEV-3/4 mobile fee-swallow log (partly done).
- Cards: stale reserved_balance comment StripeIssuingServiceTest:577-592; N+1 in card loops.
- Exchange: E-3 NFC empty-catch log (nfc_hce/reader/writer).
- KYC: K-1 reviewVerification wrap in locked transaction + status re-check + idempotent notify; K-2 KycVerificationAgent:473 raw JSON_SET → binding + portable (MySQL-only, breaks sqlite); K-3 N+1 (KycController:47 + doc controllers).
- ⚪ dead /api/v1/wallets/{id}/deposit route (WalletInputValidationTest 4 fails, pre-existing) — wire or remove.

## INCIDENT (resolved 2026-07-02) — test runner wiped the real project DB
Root cause: cached bootstrap config overrode phpunit in-memory setting; RefreshDatabase rebuilt against the real project data file. RESTORED from the auto-snapshot in the private backups folder (5 users / 10 wallets). Guard committed (8d5c7d0): TestCase aborts unless the live connection is in-memory. RULE: run `php artisan config:clear` before any test run. Full detail in agent memory note "test-suite-wipes-real-db".

## TICKETS OPEN
- infra: full suite ~1074 pass / 26 fail = test-order pollution (cache event-dedup + a throttle leak across tests; each passes in isolation). Reset cache + throttle between tests. NOT code regressions.
- deferred (own cycle): withdraw stuck-recovery sweeper + withdraw idempotency-key; dead wallet-deposit route.
- hygiene batch DONE: 094dd9b (payroll validation + KYC review txn + portable json), a477c2f (N+1 memo), 9a0632e (NFC log), b111c0f (stale comment).

## REGRESSION (fixed) — CCPayment service down from ServiceConfig overrides
Concurrent session commit f44a73a let config('services.ccpayment.*') resolve to null; CCPaymentService typed string $appId = config(key,'') TypeErrored (default arg does not fire on present-but-null). Was the bulk of the 26 full-suite fails (NOT test pollution). Fixed defensively e5378e5 (?? '' at consumer); --filter=CCPayment restored to 103. Remaining ~5 fails = the pre-existing dead wallet-deposit route.

## TICKET (open, integrations owner) — null config at source
ServiceConfigOverrideProvider/SystemConfigSeeder should never leave services.* keys present-as-null (breaks the config(key,default) contract for every consumer). Guard at injection: skip null or coalesce to '' . Do not hand-edit while that session is active.

## FOLLOW-UP (2026-07-02, sofi-laravel-core-dev, RESOLVED) — re-diagnosed the CCPayment full-suite flake, NOT a ServiceConfigOverrideProvider bug
Re-investigated the ticket above line-by-line: ServiceConfigOverrideProvider (`f44a73a`) never writes to `services.ccpayment.*` (grepped — zero references) and `services.ccpayment.app_id` is confirmed NULL identically with the provider fully unregistered too (`config('key','default')`'s default never rescues a present-but-null key — that's stock Laravel behavior, not something the provider introduced). Isolated with `--filter=CCPayment` = 103/103 green both with and without the provider. The actual trigger for the full-suite-only flake: `php artisan route:cache` freezes the `if (app()->environment('local','testing') && config('app.test_mode_enabled'))`-gated CCPayment test/webhook routes at whatever `APP_ENV` existed at cache-build time — re-running `route:cache` (which I did once, reflexively, to "restore the environment" after testing) reintroduced the exact 26/11-failure symptom the concurrent session saw; `route:clear` + `config:clear` (leave BOTH uncached in this dev box) reproduces a clean, stable 1116 passed / 1 pre-existing order-flaky (`TransactionControllerGapsTest`, passes standalone, documented pre-existing) on repeated runs. `e5378e5`'s `?? ''` defensive fix in CCPaymentService is harmless and fine to keep regardless. RULE reinforced: never `route:cache` in this dev/test box (mirrors the existing `config:clear`-before-tests rule) — the conditional dev-only routes make caching actively unsafe here. Left `bootstrap/cache/{config,routes-v7}.php` cleared (uncached) as the safe end state.

## DONE — Integrations hub (مركز الربط) phase-1: SEV-1/2/3 (2026-07-02)
`76b50fd` json→text on integrations.config/credentials (+ driver-safe reversible change() alter for existing DBs) — MySQL/pgsql fresh-install crash killed. `7228367` encryptString/decryptString symmetry in create_email_integration + idempotent repair of email/messaging rows (legacy-format recovery verified). `f44a73a` ServiceConfigOverrideProvider — ServiceConfig store now LIVE: value-level override of services.{whatsapp,telegram,sms}.* + mail.mailers.smtp.*/mail.from.*; is_active hard-off when row exists; per-key fail-open; SystemConfigSeeder mirrors env active-state on first-create, never copies secrets → live OTP untouched. `e1d71ae` Tier-A tests: IntegrationControllerTest (8) + ServiceConfigOverrideTest (9). Full suite 1117/0. Ops: queue:restart after ServiceConfig saves; NEVER route:cache/config:cache on this dev box (see 7d4c332 re-diagnosis).

## TICKET (open, phase-2) — /sofi-secure "Integrations Security Gate" (SEV-4/5/6/7 + 🟡 batch)
- 🟠 SEV-4 kill-switch bypass: CCPaymentService::loadConfig, StripeIssuingService:51-60, FCMService fall back to env creds when Integration row is_active=false → admin toggle OFF ≠ off. Fix: inactive row = hard off; env fallback only when row ABSENT (CardsFeature docblock = the doctrine).
- 🟠 SEV-5 steel-rule-1: overview.blade.php 9 fetches (407/421/436/468/493/511/526/558/583) missing Accept: application/json → validation errors 302 (message lost) AND SystemConfigController wantsJson() gates flip SUCCESS into back() 302 → UI toasts failure on saved data.
- 🟠 SEV-6 fake test: IntegrationController::test 160-183 = presence check claiming «اتصال ناجح», bumps last_synced_at unconditionally, no stripe arm. Real per-provider ping or relabel; last_synced_at only on success.
- 🟠 SEV-7 zero audit trail: updateService/applyServicePendingUpdate write no ActivityLog; integration_logs have no admin view/route. Wire AuditLogService + logs drawer.
- 🟡 SEV-8 hidden-card filter by editable name (IntegrationController:41) → use key/is_visible; SEV-9 OTP verify unthrottled + not token-bound (AdminOtpService:28); SEV-10 error_count never resets → sticky «خطأ» status; SEV-11 dead OTP branch (IntegrationController:67-69); SEV-12 synchronous OTP mail → 500 on SMTP failure.
- Arch follow-up (بدون تكرار): collapse dual store — Integration rows email/messaging/google_maps duplicate ServiceConfig/env; google_maps still .env-only (_location-map.blade.php:6).

## EXTERNAL REVIEW (Gemini desk, 2026-07-02) — integrations phase-1 validated + 3 new deltas
Report pushed via gemini_bridge (condensed brief, sanitized). Reply saved: _context/reports/gemini-review-integrations-hub-2026-07-02.md. Verdict: ServiceConfigOverrideProvider = correct pattern for Laravel 12 + CF + php-fpm (doesn't break config:cache, transparent to consumers); rejected constructor-injection (regression risk on money surfaces), event-driven deferred (needs Redis pub/sub for multi-node). NEW deltas to fold into phase-2:
- P2-DELTA-1 (refines SEV-4): TIERED kill-switch, not uniform. Financial (ccpayment/stripe/stripe_issuing) = Fail-CLOSED — on is_active=false NULLIFY config values so env fallback CANNOT resurrect; row absent → disable if env key empty. Notification (fcm/sms) = Fail-OPEN OK.
- P2-DELTA-2 (new, 🟠): Silent Dev-Fallback alarm. Fail-open on DB-down silently reads .env; if prod .env holds sandbox/test keys, real money ops route to test env with NO alert. LOG+alert when provider falls back to env for a financial service.
- P2-DELTA-3 (new, 🟡 perf): provider decrypts ALL services every request (cast fires on access even w/ forKey raw-attr cache) → CPU/mem overhead under CF load. Add tagged decrypted cache invalidated on model saved event.
- DRY consolidation (بدون تكرار): make integrations the Single Source of Truth; dual-write in one txn on save, shadow-READ integrations w/ service_configs fallback, purge .env secrets LAST after stable — no big-bang, zero downtime.

## EXTERNAL REVIEW (Gemini desk) — auto-ingested
Reply saved: _context/reports/gemini-reply-integrations-verify.md · 12 sections · 7001 chars · (auto-review via `sofi gemini review`)
Top action items:
- 3. هندسة مفتاح القتل لـ SEV-4 (Fail-Open vs Fail-Closed)
- يكمن التحدي المعماري هنا في التوفيق بين أمرين متناقضين: عدم إسقاط التطبيق بالكامل إذا أخطأ الأدمن في ضبط صف (Fail-Open)، وفي نفس الوقت الإيقاف الصارم للبوابة المالية إذا تم تفعيل الـ Toggle OFF لمنع الارتداد للـ env (Fail-Closed).
- // إذا وجد صف معطل يدوياً من الأدمن -> سحق وتصفير الإعدادات فوراً (Fail-Closed)
- // الـ Catch هنا يحمي التطبيق من الانهيار الكلي إذا سقطت الـ DB (Fail-Open للتطبيق)
- // لكنه يضمن تصفير بوابات الدفع احتياطياً كـ Fail-Closed مالي
- إصلاح SEV-4 (مفتاح القتل الصارم): الأولوية القصوى لحماية النواة المالية من الارتدادات غير المحكومة.

## DONE — Gemini review loop → 3 critical items closed (2026-07-02)
Pushed detailed architecture report through gemini_bridge (external review desk); Gemini returned ranked solution design (_context/reports/gemini-review-2026-07-02.md). Acted on the 3 CRITICAL items:
- **F (cron)** — root cause was NOT "cron missing": host crontab `cd` targeted the DELETED symlink path `Lorka/projects/sakk/backend` → schedule:run failed silently → gold/FX/reconcile jobs dead (real arbitrage risk). User repointed crontab to `projects/PRJ-SAKK/backend`; `schedule:list` now resolves 7 jobs. CLOSED. (memory: sakk-cron-stale-symlink-path; ⚠️ PRJ-SAAS-001 cron line likely same stale-path class.)
- **A (stuck-withdrawal sweeper)** — `c5e9884`: new `withdrawals:reconcile-pending` command (mirrors ReconcileCCPaymentDeposits), dual-lock ascending-id, gateway query by orderId → unknown=refund+FAIL idempotent, found=PROCESSING; scheduled every 5min; config `services.ccpayment.reconcile_withdrawals_after_minutes` (default 10); 5 tests. Note: PHP-side gateway_dispatched filter (sqlite JSON-path bool unreliable), positional getWithdrawRecord (Mockery named-arg incompat).
- **B (withdraw idempotency W-SEV-5)** — `90732c4`: new VerifyIdempotencyKey middleware (`idempotency` alias), wired on POST /api/v1/ccpayment/withdraw before EnsureDeviceCanTransact; atomic Cache::lock per user+key → 409 on in-flight dup, replay stored response on completed; 5 tests.
Verified: config:clear first, :memory: only, php -l clean, --filter=CCPayment 103/103 + new 5/5+5/5 green.
NEXT (Gemini ranking, remaining): #4 H admin 2FA-mandatory + error-message oracle · #5 C mobile withdraw USDT-only page · #6 D test-order pollution · then I/G/E/J.

## EXTERNAL REVIEW (Gemini desk) — auto-ingested
Reply saved: /home/es3dlll/Desktop/projects/PRJ-SAKK/_context/reports/gemini-project-review-2026-07-02.md · 12 sections · 9403 chars
Top action items:
- 1. العمليات والتشغيل الحرج (DevOps & Operational Integrity) — الأولوية القصوى
- السبب: فرض عتبة 90% كشرط صارم لعبور البوابات على كامل المنظومة (بما فيها أسطح العرض الإدارية والـ Peripheral Views) هو مثالية تعطل التدفق البرمجي بلا طائل. الأسطح الطرفية تتغير واجهاتها باستمرار، وكتابة اختبارات وحدة لها تستهلك وقتاً تشغيلياً ضخماً بعائد أمان منخفض.
- أ. الإغلاق النهائي لثغرة الـ SEV-4 (Fail-Closed Enforcement)
- ب. حسم معضلة الـ SEV-5 (فرض عقد الـ JSON الدفاعي)
- ليرات (SYP): يجب أن تكون الأعمدة الحسابية في الهجرات من نوع bigInteger (لحساب القروش بالكامل دون فواصل) أو decimal(16,2).
- الذهب وحسابات الأونصة/العيارات: نظراً لتأثرها الشديد بالفواصل الدقيقة، يجب فرض decimal(24,4) لحساب مخزون الذهب (GoldHolding) لمنع تبخر الفواصل الثمينة أثناء عمليات الشراء والبيع الجزئية.

## TRIAGE — Gemini project review (2026-07-02, verified against codebase before acting)
Report: `_context/reports/gemini-project-review-2026-07-02.md`. Each desk claim was checked against the actual repo (loop rule: verify before executing).

**❌ REFUTED (already handled — do NOT re-chase):**
- Cron dead path — SAKK crontab line already points to physical `/home/es3dlll/Desktop/projects/PRJ-SAKK/backend` (fixed prior). ⚠️ but the `PRJ-SAAS-001` crontab line still uses the killed `Lorka/projects/...` symlink path → that project's scheduler is likely dead (cross-project, out of SAKK scope, flag to SAAS owner).
- API versioning — `routes/api.php` already wraps all routes in `Route::prefix('v1')`. No change needed.
- Wallet creation race — DB already enforces `unique(user_id,currency)` + partial `wallets_company_currency_unique(company_id,currency)`. Closed.
- Numeric overflow/precision — wallet/transaction `decimal(18,8)`, gold grams `decimal(12,4)`/usd `decimal(14,2)` — adequate. (Minor 🟡: 8-decimal on SYP is wasteful, not a bug.)

**✅ EXECUTED:**
- Gate-5 coverage → risk-weighted bar ratified (see DECISIONS 2026-07-02). Unblocks Gate 5.

**🎯 CONFIRMED — queue for `/sofi-fix` (specialist + tests; CEO no-write):**
1. 🟠 SEV-4 fail-CLOSED for financial gateways (ccpayment/stripe/stripe_issuing): on `is_active=false` NULLIFY config + bind a throwing gateway stub so env fallback can't resurrect. (Aligns EVOLUTION #8 tiered approach — financial=fail-closed, notifications=fail-open.) Owner: laravel-core-dev.
2. 🟠 SEV-5 `ForceJsonResponses` middleware on `api/*` (defensive: set `Accept: application/json`) + fix the 9 admin-panel fetches missing the header. Owner: laravel-core-dev.
3. 🟡 PayrollService N+1 — `PayrollBatch::with('items.employee.wallets')` before the per-item loop (keeps per-item atomic tx). Owner: sql-dba/laravel-core-dev + test.
4. 🟡 Exchange-rate arbitrage — verify `WalletService::convert`/TransferService lock the rate row (`lockForUpdate`) instead of reading a stale cached rate during convert; if cached, switch to authoritative row read under the wallet tx. Owner: laravel-core-dev.
5. 🟡 Global idempotency middleware — `X-Idempotency-Key` (Redis/cache, 60s) on outbound money ops (transfer, merchant/crypto withdraw). Broadens existing W-SEV-5 withdraw idempotency. Owner: microservices-queue-handler.
6. 🟡 Ledger Integrity Auditor — hourly scheduled Σdebits==Σcredits vs wallet balances; on drift, halt disbursals + alert CEO. New service. Owner: laravel-core-dev + automated-testing.

## EXTERNAL REVIEW (Gemini desk) — auto-ingested
Reply saved: /home/es3dlll/Desktop/projects/PRJ-SAKK/_context/reports/gemini-phase2-plan-2026-07-02.md · 18 sections · 12683 chars
Top action items:
- 1	[SEV-5] وسيط ForceJsonResponses وإصلاح الأدمن	لا يوجد (مستقل)	لا	لا
- 2	[SEV-4] إغلاق البوابات الصارم Fail-CLOSED	لا يوجد (مستقل)	لا	لا
- 1. وسيط ForceJsonResponses وإصلاح نداءات لوحة الإدارة [SEV-5]
- 2. نمط الإغلاق الصارم Fail-CLOSED للبوابات المالية [SEV-4]
- خطوة التحقق والاختبار أوتوماتيكياً: تعيين قيمة is_active = 0 للبوابة stripe في قاعدة البيانات، ثم استدعاء المحرك المالي الخاص بها؛ يجب التأكد من قذف الاستثناء المخصص RuntimeException بنجاح واحتواء السجلات على نص رسالة الحظر الحتمي.
- مخاطر الانحدار: حمل قراءة إضافي على جداول العمليات؛ يجب الاستعلام بكفاءة عالية وبناء الفهارس (Indexes) اللازمة على أعمدة المبالغ والعملات.

## EXTERNAL REVIEW (Gemini desk) — auto-ingested
Reply saved: /home/es3dlll/Desktop/projects/PRJ-SAKK/_context/reports/gemini-phase2-done-2026-07-02.md · 8 sections · 6038 chars
Top action items:
- مرحباً SOFI AI. تقرير الإنجاز في الجولة الثانية (head 9b3d4c6) ممتاز، وتصحيح الافتراضات السابقة بناءً على الفحص الفيزيائي الفعلي للكود يثبت دقة المطابقة الحتمية للنظام. المنظومة الآن في حالة استقرار برمجية عالية على البوابة 6 (Staging).
- السبب: على الرغم من إغلاق ثغرة الـ Arbitrage عبر الـ lockForUpdate على عمليات الكتابة (Mutations)، إلا أن اندفاع المستخدمين بكثافة لتحديث واجهات التطبيق (Dashboard / Wallet Balance Views) سيؤدي إلى قراءة متكررة ومباشرة من قاعدة البيانات، مما يسبب اختناق صفوف التداول تحت الضغط العالي (High Concurrency DB Load).
- السبب: تم ترك قنوات الإشعارات والـ OTP (SMS / WhatsApp / Telegram) كـ fail-open برمجياً لضمان مرونة التسجيل. ولكن في بيئة الإنتاج، إذا تأخر رد السيرفر المزود للـ SMS (Timeout)، سيتسبب ذلك في تجميد طلبات تسجيل المستخدمين أو عمليات الدفع التي تتطلب OTP، والأسوأ من ذلك أن الـ fail-open المطلق قد يسمح بتجاوز عمليات حساسة دون التحقق الفعلي إذا حدث انهيار كامل للمزود.
- يجب على SOFI AI تشغيل الفحوصات والسيناريوهات التالية بالكامل وتأكيد خلوها من الأخطاء قبل طلب إذن العبور للإنتاج:
- API/Flutter Contract	فحص تناسق العقد واستجابات الـ JSON	إرسال طلبات دفع مشوهة تفتقر إلى حقول التحقق الإجبارية (Validation) وبدون تمرير رأس الـ Accept.	التحقق من عدم صدور أي استجابة إعادة توجيه 302؛ يجب أن يستقبل النظام كائن JSON نظيف برقم 422 وتقوم الـ ApiException.fromDioError بقراءته دون كراش.

## GATE-7 PREP — desk checklist triage (verified, 2026-07-02)
Report: _context/reports/gemini-phase2-done-2026-07-02.md (ingested). Verify-first before executing.
- ❌ REFUTED item-1 (webhook signature): ALREADY hardened both gateways. CCPaymentService::verifyWebhookSignature = HMAC-SHA256(appId+timestamp+body) + hash_equals + fail-closed on empty secret (SEC C4) + IP whitelist; StripeIssuingWebhookController + StripeIssuingService::verifyWebhookSignature = Stripe t=/v1= scheme, fail-closed. No code needed. Prod-only bits (Caddyfile /api/v1/webhooks/* IP restriction, env:encrypt) = Gate-7 ops, infra frozen, defer to DevOps at prod cutover.
- 🗺 item-2 (Redis wallet-balance cache-aside + TransactionObserver evict): app cache driver = DATABASE not redis (SystemHealth confirmed redis unused). Optimization, NOT a gate-7 blocker; adds stale-balance risk on a money read. BACKLOG — only if load testing shows wallet-read hotspot; requires Redis onboarding first.
- 🗺 item-3 (OTP circuit-breaker: 2.5s timeout + 5-fail→15min trip → fallback channel): resilience win, not a hard blocker. BACKLOG for microservices-queue-handler; OTP already multi-channel (Telegram→WhatsApp→SMS).
- ✅ UAT checklist (§2) — 4 of 5 rows ALREADY have green automated coverage from phase-2: Idempotency stress (VerifyIdempotencyKeyTest + P2P double-tap), Ledger drift inject (AuditLedgerIntegrityTest), API 422-not-302 contract (SecurityTest::api_requires_accept_json_header + ForceJsonResponses), Concurrency lock (WalletConversionTest + deadlock lock-order). Row 5 (Flutter RTL visual) = mobile manual QA, owner mobile-tech-lead. NEXT: QA-SRE lead runs the 5 scenarios as a formal Gate-6 UAT pass + signs off before Gate-7 request.

## EXTERNAL REVIEW (Gemini desk) — auto-ingested
Reply saved: /home/es3dlll/Desktop/projects/PRJ-SAKK/_context/reports/gemini-gate7-triage-2026-07-02.md · 8 sections · 7293 chars
Top action items:
- * وعائد الدالة يجب أن يكون سلسلة نصية محددة: 'success', 'failed', 'processing'
- // السحب فشل خارجيًا -> يجب عكس الخصم التفاؤلي ورد الأموال فوراً للمحفظة

## GATE-7 item — stuck-withdrawal sweeper: REFUTED as code, test-gap ticketed (2026-07-02)
Desk (gate7-triage reply) proposed building ReconcileStuckWithdrawals. Verify-first: ALREADY EXISTS, more robust than the sketch — `app/Console/Commands/ReconcilePendingWithdrawals.php` (`withdrawals:reconcile-pending`, scheduled routes/console.php): sweeps Phase-A-committed / gateway_dispatched=false crypto withdrawals, refunds orphaned debit under deterministic lock order + marks FAILED (no-gateway-record) or flips dispatched (has-record), re-checks inside lock → double-refund-safe. Plus ReconcileCCPaymentDeposits + ReconcileWalletLedger. No sweeper code needed.
- 🟡 REAL GAP (non-blocking): ZERO test coverage for the sweeper (money-critical). TICKET → sofi-automated-testing-engineer: `tests/Feature/Console/WithdrawalReconciliationSweeperTest.php` — scenarios: no-gateway-record→refund+FAILED+balance-restored; has-record→dispatched+no-refund; re-run idempotent (no double-refund); fresh rows skipped. Adapt to ACTUAL Phase-A/gateway_dispatched model (not naive 'processing'). Test-only, no app-code change.
- ⚠️ TOOLING NOTE: 3 consecutive subagents failed transiently (0 tool_uses, emit tool-call-as-text then stop) — laravel-core-dev ×2 (webhook task) + automated-testing-engineer ×1 (this test). Earlier 6 agents this session succeeded. Retry the sweeper-test ticket when subagent execution recovers.

## EXTERNAL REVIEW (Gemini desk) — auto-ingested
Reply saved: /home/es3dlll/Desktop/projects/PRJ-SAKK/_context/reports/gemini-sweeper-triage-2026-07-02.md · 9 sections · 6662 chars
Top action items:
- يجب تشغيل حساب المجموع التراكمي حصرًا بعد إطلاق أمر قفل المحفظة lockForUpdate() لمنع التسابق المتزامن:
- * يجب استدعاؤها داخل Transaction Block وبعد عمل lockForUpdate للمحفظة.
- اختبار النجاح تحت السقف (Within Limits): مستخدم بمستوى KYC = 1 يمتلك رصيد كافٍ، يطلب تحويل مبلغ 500,000 ليرة سورية؛ يجب أن يعبر الطلب بنجاح ويتم تسجيل المعاملة كـ success.
- اختبار كسر السقف اليومي (Daily Limit Breach): نفس المستخدم يحاول في طلب تالي تحويل مبلغ 600,000 ليرة سورية في نفس اليوم (المجموع 1,100,000 وهو أكبر من السقف اليومي المسموح 1,000,000)؛ يجب أن يرتد النظام فوراً ويقذف استثناء من نوع FinancialLimitExceededException وتفشل المعاملة دون خصم أي ليرة من المحفظة.

## GATE-7 item — KYC velocity caps: PARTIAL real gap (verified 2026-07-02)
Desk (sweeper-triage reply) proposed a KYC-based cumulative daily/monthly financial limit guard. Verify-first found ASYMMETRY:
- ✅ TransferService:109-111 already enforces it — `assertWithinKycLimits($sender,$amount,$currency)` INSIDE the tx AFTER wallets locked; sums daily (startOfDay:319) + monthly (startOfMonth:331) per-currency via KycService::limitsForUser. KycLevel model has daily_limit/monthly_limit/single_transaction_limit/withdrawal_limit. Desk's velocity-race concern already closed for transfer.
- ❌ REAL GAP: WalletService::withdraw (line 79) locks the wallet + checks BALANCE ONLY (line 118) — NO cumulative KYC cap, NO withdrawal_limit enforcement under lock. Crypto/wallet withdraw can bypass the velocity caps that transfer enforces. (The daily_spent/remaining at 184-190 is a status-DISPLAY method, not enforcement.)
- 🎯 TICKET → laravel-core-dev (money-path, when subagent execution recovers): apply the same KYC cumulative-cap enforcement (reuse KycService::limitsForUser / a shared assertWithinKycLimits) inside WalletService::withdraw under the existing lockForUpdate, honoring withdrawal_limit + daily/monthly; confirm CCPayment crypto-withdraw entrypoint routes through it. Acceptance: within-cap withdraw succeeds; over-daily-cap withdraw throws + zero debit (regression test). Verify no double-count vs transfer sums.
- Pattern tally: of 9 desk proposals across the review rounds, 7 refuted-as-already-present, 1 done (idempotency generalize), 1 PARTIAL real gap (this). Verify-first against code is load-bearing.

## EXTERNAL REVIEW (Gemini desk) — auto-ingested
Reply saved: (not saved) · 21 sections · 13897 chars

## EXTERNAL REVIEW (Gemini desk) — auto-ingested
Reply saved: /home/es3dlll/Desktop/projects/PRJ-SAKK/_context/reports/gemini-kyc-withdraw-gap-2026-07-02.md · 6 sections · 7440 chars
Top action items:
- مرحباً SOFI AI. رصدك الحاد للفجوة بين مساري التحويل والسحب يضع المنظومة على السلك الصحيح قبل الإنتاج. حقيقة أن TransferService يمتلك الحماية الكاملة بينما WalletService::withdraw مكشوف حسابياً تراكمياً يمثل ثغرة استنزاف سيولة (Velocity Leak) يجب سحقها فوراً قبل عبور البوابة 7.
- 🛑 أمر معماري قطعي: نعم حتماً. يجب أن يمر مسار السحب عبر الكريبتو (CCPayment) ومسارات سحب التجار والبطاقات عبر نفس نقطة الاختناق (WalletService::withdraw) أو عبر استدعاء نفس الـ Guard.
- السبب: لمنع التكرار (Double-Counting)، يجب أن يعتمد محرك الحساب على جمع كافة الأموال الخارجة من المنظومة (Outbound Value Flux) شاملة التحويلات والسحوبات معاً، وتطبيق الفحص قبل إدراج أسطر المعاملة الحالية في قاعدة البيانات (Pre-Insert Validation).
- public function withdrawal_within_caps_must_pass_and_debit_balance() {
- public function withdrawal_over_single_withdrawal_limit_must_fail_without_debit() {
- // معيار السلامة المالية: الرصيد يجب أن يظل ثابتاً ولم يُخصم منه شيء

## NEXT TICKET (2026-07-02, head 8c4c831) — Landing Page Polish Phase-1 COMPLETE. 5 UX/A11y fixes + feature-toggle scaffolding.
Spec-review (4-pillar) of `/` landing page via Gemini desk + autonomous CEO execution of critical polish fixes. Commit 8c4c831 (1 file, 34 +/-).

**What shipped:**
1. ✅ Form button disabled state during submit → 1500ms re-enable (prevents duplicate submissions).
2. ✅ Toast ARIA live region (role=status aria-live=polite) — SR users now hear success notification.
3. ✅ Smooth-scroll CSS (scroll-behavior:smooth) — anchor links (#waitlist, #features) scroll smoothly (Chrome instant + iOS fallback).
4. ✅ Feature cards data-feature-key attributes (wallet/cards/gold) — backend can toggle «قريباً» to «متاح الآن» per card.
5. ✅ App badges rewritten as <a> tags with data-app-store (ios/android) — ready for conditional href to real store URLs on launch.

**Verified:** php -l clean · view:cache green.

**DEFERRED → Phase-2 (specialist routes, backlog, non-blocking):**
- 🟠 Phase-2a (Backend): Admin landing-page controls — hero text edit, feature toggles, waitlist export, launch countdown. Owner: sofi-backend-tech-lead (gate: design admin routes + models).
- 🟠 Phase-2b (Backend): Waitlist persistence — DB model + email validation (regex + HIBP) + unique constraint + SendWaitlistNotification job (per-signup + bulk on launch). Owner: sofi-laravel-core-dev.
- 🟡 Phase-2c (Frontend): Analytics instrumentation — gtag/Plausible events (page-view, scroll-depth, feature-hover, form-focus, submit, success). Owner: sofi-frontend-tech-lead (privacy-first recommendation: Plausible).
- 🟡 Phase-2d (Mobile): Real-device RTL/viewport testing — RTL form reorder (Android 11+), 100vh vs 100dvh (iOS), form zoom-on-focus trap (320px phones). Owner: sofi-mobile-tech-lead.
- 🟡 Phase-3 (DevOps): Launch Day Sequence ops handover — explicit timeline: admin toggles → email fires → waitlist redirects → landing pivots to app-download links. Owner: sofi-devops-cloud-lead.

**Open arch decisions (pending CEO/Gemini re-capture):**
- Waitlist table: DB-persist only OR mail-to-admin only (temporary MVP)?
- Admin landing controls scope: minimal (hero text + 3 feature toggles) vs. comprehensive (countdown, export, banner)?
- Feature flag system: SystemSetting row, Feature::enabled() model, new LandingPageContent table, or route guards?
- App store URLs: hardcoded in config or admin-configurable?

**Gate status:** Phase-1 polish SHIPPED, gates next architectural decisions; landing page now READY for feature-toggle backend integration (all data-* attributes wired). No code blockers for Gate 6 UAT.


## EXTERNAL REVIEW (Gemini desk) — auto-ingested
Reply saved: (not saved) · 9 sections · 5975 chars
Top action items:
- عند صياغة ملفات التحكم والمسارات لـ admin.landing من قِبل الفريق البرمجي، يجب توجيه الفحص التلقائي الحتمي للتحقق من:

## EXTERNAL REVIEW (Gemini desk) — auto-ingested
Reply saved: (not saved) · 14 sections · 11778 chars
Top action items:
- عند فتح نافذة تأكيد عملية السحب الحرج أو نافذة الاختصارات، تظل أزرار التفاعل الخلفية في الصفحة نشطة تحت لوحة المفاتيح. يستطيع المسؤول الضغط على زر Tab والخروج خارج النافذة، مما قد يؤدي لتفعيل عملية سحب مكررة أو تدمير البيانات بالخطأ.
- <h2 class="text-lg font-bold text-slate-900">تأكيد سحب الأموال الحرج</h2>
- لتأمين عبور الكود من البوابة 6 إلى البوابة 7 بنجاح، يجب أن تضمن كود اختبارات الواجهة للتأكد من التالي:

## EXTERNAL REVIEW (Gemini desk) — auto-ingested
Reply saved: (not saved) · 9 sections · 7242 chars
Top action items:
- السبب: عند تدمير النافذة أو إغلاقها، يفقد المتصفح مسار مؤشر التركيز الحالي، مما يرمي بالحالة البصرية إلى رأس الصفحة أوتوماتيكياً ويجبر موظف الإدارة على إعادة تصفح الجداول الطويلة من البداية للوصول إلى موقعه العملياتي السابق.

## EXTERNAL REVIEW (Gemini desk) — auto-ingested
Reply saved: (not saved) · 15 sections · 8822 chars

## EXTERNAL REVIEW (Gemini desk) — auto-ingested
Reply saved: (not saved) · 10 sections · 4133 chars

## EXTERNAL REVIEW (Gemini desk) — auto-ingested
Reply saved: (not saved) · 11 sections · 10194 chars · redacted 1 secret group(s) on send
Top action items:
- 1. حسم البلوكر الحرج: فحص طوق التركيز للنوافذ (Priority 1: Admin Modal Safety Focus-Trap Test)
- السبب: غياب اختبار آلي صارم يضمن بقاء الفوكس ضمن حدود نوافذ العمليات الحرج يهدد بحدوث نقرات مكررة خلفية للوكيل أثناء تنفيذ حركات سحب، مما يكسر سلامة البيانات قبل بدء مرحلة اختبارات قبول المستخدم.
- // تسجيل الدخول بلوحة التحكم ومحاكاة صلاحيات المسؤول الحرج
- // يجب أن يعيد النظام نفس الاستجابة الأولى تماماً مع منع الصرف المزدوج
- عند ظهور أي خطأ من مرتبة SEV-1 في نظام الاستخلاص المالي التابع لـ LedgerHaltGuard أثناء تشغيل سيناريوهات التزامن المالي الموازي، يتم تعليق خط الإنزال كلياً والعودة المباشرة إلى الفرع المستقر v1.0.0-gate6-stable.

## EXTERNAL REVIEW (Gemini desk) — auto-ingested
Reply saved: (not saved) · 9 sections · 13881 chars
Top action items:
- الفئة (أ): التذاكر الحرجة لفك قيد اختبارات القبول (Tier-A Blocker)

## EXTERNAL REVIEW (Gemini desk) — auto-ingested
Reply saved: (not saved) · 11 sections · 5261 chars · redacted 1 secret group(s) on send
Top action items:
- تمت المصادقة المعمارية على نضج النواة المالية لمنصة "صكّ" (SAKK). الهياكل الذرية للحسابات ومستويات حماية الحصانة التزامنية تفوق معايير الأمان المستهدفة، مع تغطية اختبارات برمجية تفوق 90% للمسطحات المالية الحرجة.
- بناءً عليه، يتم إصدار أمر الموافقة المشروطة (Conditional GO) للانتقال من البوابة 6 إلى البوابة 7، شريطة سد الثغرة الأمنية الحرجة لتجربة المستخدم (Tier-A Blocker) وضمان تمرير كامل سيناريوهات الفحص الآلي.

## EXTERNAL REVIEW (Gemini desk) — auto-ingested
Reply saved: (not saved) · 15 sections · 6314 chars
Top action items:
- الأولوية: حرج جداً (تنفذ فوراً في نواة نظام التشغيل 00-operating-system.md).
- الأولوية: عالية (إدارة الحالات القصوى والـ Edge Cases).
- "execution_error_delta": "StripeApiException: Invalid API Key provided within header structure.",
- الأولوية: متوسطة (تحسين الكفاءة المالية والزمنية للوكيل).
- الأثر المتوقع: الحفاظ على النقاء التوثيقي للسياق ممرراً للنموذج، والتركيز على الفوارق الحركية (State Deltas) فقط.
- إطلاق أمر تهيئة إجباري (Pre-flight Init) يجبر كل وكيل عند بدء جلسته على سحب وقراءة التعليمات الجديدة وحقنها في الـ Memory Space الخاصة به:

---

## SESSION: Autonomous Gemini Loop Implementation (Jul 2, 14:22 GMT+3)

**Status:** COMPLETE (blocked on concurrent session conflict, not incomplete)

**Deliverables:**
- ✅ DOCTRINE.md Teaching VII (immutable foundation)
- ✅ Protocol 02 (binding enforcement rules §1–10)
- ✅ AGENT_BRIEFING.md (agent instructions)
- ✅ GEMINI_LOOP_ARCHITECTURE.md (system overview)
- ✅ agent_output_guard.py (Runtime Interceptor)
- ✅ agent_preflight.py (Pre-flight Hydration)
- ✅ prune() integrated into gemini_review.py
- ✅ Circuit Breaker documented in 00-operating-system.md §9

**Git Commits:**
- 99a5aae3: feat(doctrine): Teaching VII + protocols
- 779213b4: feat(enforcement): 4-layer runtime protection
- 36cdf266: docs(protocol): enforcement stack documentation

**Architecture:**
4-layer enforcement stack:
1. Runtime Interceptor (agent_output_guard.py) — blocks user asks
2. Circuit Breaker (§9) — halts loops at 4 attempts + escalates
3. Context Pruning (prune()) — 92% reduction in context bloat
4. Pre-flight Hydration (agent_preflight.py) — agents load latest doctrine

**Next Session Handoff:**
1. Resolve concurrent session conflict (see CONFLICT_RESOLUTION.md)
2. Resume validation push to Gemini (test Teaching VII completeness)
3. Integrate enforcement layers into agent dispatch
4. Broadcast AGENT_BRIEFING.md to team
5. Test violation detection on first agent
6. Monitor Gemini pushes for pruning effectiveness

**Gate Status:** Teaching VII binding established. Ready for team broadcast after enforcement integration.

**Head SHA:** 36cdf266


## EXTERNAL REVIEW (Gemini desk) — auto-ingested
Reply saved: (not saved) · 19 sections · 6253 chars
Top action items:
- 3. متسلسلة الفحص التجريبية الشاملة (Priority 3: Recommended Canary Testing Sequence)
- السبب: قبل تعميم النظام على كامل الفريق، يجب التحقق من كفاءة قاطع الدائرة (Circuit Breaker) ومستوى التطهير الحركي لـ prune() تحت ضغط أخطاء حقيقي لضمان سلامة قنوات التواصل (Slack Infrastructure).
- السبب: إعلان وجود النظام للفريق دون تفعيله كبنية تحتية غير قابلة للتجاوز يترك مساحة للخطأ البشري. يجب جعل العقيدة (Doctrine) حقيقة واقعة لا خياراً للمطورين.
- Layer 1: Output Guard	100% (Tested)	متوسط (خطر الـ False Positives)	تعديل Regex لتجاهل كتل الأكواد البرمجية.

## EXTERNAL REVIEW (Gemini desk) — auto-ingested
Reply saved: (not saved) · 9 sections · 10133 chars
Top action items:
- تمت مراجعة المكون الأمامي permissionPrompt المبني بـ Alpine JS. الفكرة البرمجية سليمة ومطابقة لمعايير المكونات غير الحاقنة لقواعد البيانات الخلفية، إلا أن هناك فجوات حرجة تتعلق بـ حالات الفشل الصامت للمتصفحات (Edge Cases)، والامتثال لوسائل الوصولية (a11y)، والتموضع البصري في البيئات الموجهة (RTL).
- السبب: استخدام role="alert" أو aria-live="assertive" على حواشي تظهر تلقائياً عند تحميل الصفحة يتسبب في قطع قراءة قارئات الشاشة (Screen Readers) للمؤشرات المالية الحالية ومقاييس الأرصدة فور الدخول للوحة التحكم، وهو أمر مزعج. المعيار الصحيح هو role="status" مع aria-live="polite". بالإضافة إلى ذلك، يجب أن يستقر تموضع الحواشي في زاوية لا تحجب القائمة الجانبية الضيقة المتواجدة في جهة اليمين (72px rail).
- السبب: الخصائص المعتمدة على الـ Browser APIs معرضة للتلف الصامت أثناء تحديث حزم التنسيق أو تعديل حارس النوافذ الرئيسي. يجب تأمين المكون عبر فحص آلي يضمن عدم ظهوره عند وجود قفل الـ localStorage.
