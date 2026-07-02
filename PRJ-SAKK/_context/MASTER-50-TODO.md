# 🎯 MASTER 50-TASK HITLIST — PRJ-SAKK
> القائمة الشاملة لحل كل المشاكل — الخطة الحربية
> Date: 2026-06-29 · Gate: 3 (Architecture) · Branch: qa/testing-baseline · HEAD: 221a9ba6

---

## 🏛️ DOMAIN 1: CURRENCY + EXCHANGE RATES (7 tasks)
> مسائل العملات وأسعار الصرف — CRITICAL, money at stake

### T-001 [CRITICAL] Fix ExchangeRate `$guarded = []` mass assignment
- **Agent:** Security & Compliance Architect (Dr. Ruth Goldberg)
- **File:** `backend/app/Models/ExchangeRate.php:23` + `ExchangeRateHistory.php:23`
- **Fix:** Replace `$guarded = []` with explicit `$fillable` allowlist matching safe fields
- **Test:** Add mass-assignment protection test
- **Route:** 🟣 opus · high · full

### T-002 [CRITICAL] Fix GoldWallet value calc — multiplying by ALL karats
- **Agent:** Backend Tech Lead (Carlos Mendoza)
- **File:** `GoldSavingsController.php:56-58` + `stats():317`
- **Bug:** `balance_grams * sell_price` iterates ALL active karats → overstates value 4x
- **Fix:** Use single authoritative karat OR weight-per-karat breakdown
- **Test:** GoldSavingsTest assertions for `current_value_usd`
- **Route:** 🔵 sonnet · high · full

### T-003 [HIGH] WalletService::convert() bypasses ExchangeRateService cache
- **Agent:** Principal System Architect (Vikram Rao)
- **File:** `WalletService.php:233-263`
- **Fix:** Delegate to `ExchangeRateService::convert()` instead of direct DB read
- **Test:** WalletConvertMagnitudeTest update
- **Route:** 🟣 opus · high · full

### T-004 [HIGH] Spread calculation duplicated in 3 places
- **Agent:** Data & Schema Engineer (Elena Petrova)
- **File:** `ExchangeRateService.php`, `AdminController.php:1349-1352`, `ExchangeRateController.php`
- **Fix:** Extract `ExchangeRate::getBuyRate()` / `getSellRate()` accessors
- **Route:** 🔵 sonnet · high · full

### T-005 [MEDIUM] ExchangeRateController allows `rate: 0` + disjoint buy/sell rates
- **Agent:** API & Integration Specialist (Marcus Blackwood)
- **File:** `ExchangeRateController.php:18-21`
- **Fix:** Validation `min:0.0001`, enforce `buy_rate < rate < sell_rate`
- **Route:** 🔵 sonnet · medium · full

### T-006 [MEDIUM] AdminController::getExchangeRates() hardcodes supported_currencies=['USD']
- **Agent:** Laravel/PHP Core Dev (Aisha Rahman)
- **File:** `AdminController.php:832`
- **Fix:** Add SYP to supported_currencies or read from DB
- **Route:** 🔵 sonnet · medium · ultra

### T-007 [LOW] Fix SYP rate magnitude — historical migration leaves stale reverse row
- **Agent:** SQL/DBA Expert (Günther Weber)
- **File:** `migrations/2026_06_25_000001_fix_syp_exchange_rate_magnitude.php`
- **Fix:** Add cleanup of stale reverse-SYP rows + validation trigger
- **Route:** 🔵 sonnet · high · full

---

## 🏛️ DOMAIN 2: WALLET BALANCE INTEGRITY (6 tasks)
> سلامة أرصدة المحفظة — CRITICAL, financial invariant

### T-008 [CRITICAL] Wallet::capture() breaks available_balance invariant (hold→capture leak)
- **Agent:** Backend Tech Lead (Carlos Mendoza) + Laravel/PHP Core Dev (Aisha Rahman)
- **File:** `Wallet.php:166-178`, `PayrollService.php:252-254`
- **Bug:** `hold()` reduces `available_balance`, `capture()` reduces `balance`+`pending_balance` but never restores `available_balance` → slow leak
- **Fix:** `capture()` must restore `available_balance` OR redesign hold/capture invariant
- **Test:** WalletBalanceInvariantTest — assert `balance == available_balance + pending_balance`
- **Route:** 🟣 opus · max · full

### T-009 [HIGH] Wallet::credit() returns false when frozen — blocks payroll & deposits
- **Agent:** Backend Tech Lead (Carlos Mendoza)
- **File:** `Wallet.php:108-120`
- **Fix:** Frozen wallets should accept incoming (block only outbound/debit)
- **Route:** 🔵 sonnet · high · full

### T-010 [HIGH] Wallet::resetLimitsIfNeeded() writes saveQuietly() on EVERY canSpend() check
- **Agent:** SQL/DBA Expert (Günther Weber) + Laravel/PHP Core Dev (Aisha Rahman)
- **File:** `Wallet.php:182-221`
- **Fix:** Only save when limits actually change; use dirty check
- **Route:** 🔵 sonnet · high · full

### T-011 [MEDIUM] ensureUserWallets() casts string `daily_limit` comparison — may upgrade restricted wallets
- **Agent:** Laravel/PHP Core Dev (Aisha Rahman)
- **File:** `WalletService.php:203-204`
- **Fix:** Strict comparison with decimal cast; respect `daily_limit=0`
- **Route:** 🟢 haiku · medium · ultra

### T-012 [MEDIUM] WalletController::exchangeRates() duplicates ExchangeRateController
- **Agent:** API & Integration Specialist (Marcus Blackwood)
- **File:** `WalletController.php:265-273` + `routes/api.php`
- **Fix:** Remove redundant route or delegate to ExchangeRateController
- **Route:** 🟢 haiku · low · ultra

### T-013 [LOW] Registration creates only USD wallet — SYP wallet created on-demand, convert path may 404
- **Agent:** Laravel/PHP Core Dev (Aisha Rahman)
- **File:** `User.php:94-100`, `WalletService.php`
- **Fix:** Create both USD+SYP wallets on registration
- **Route:** 🔵 sonnet · medium · ultra

---

## 🏛️ DOMAIN 3: FEE + FINANCIAL CALCULATIONS (5 tasks)
> حسابات الرسوم والعمولات

### T-014 [HIGH] Withdrawal fee hardcoded 1% — bypasses FeeService
- **Agent:** Laravel/PHP Core Dev (Aisha Rahman)
- **File:** `WalletService.php:84`
- **Fix:** Use `FeeService::calculateFee('withdrawal', $amount, $currency)`
- **Route:** 🔵 sonnet · high · ultra

### T-015 [HIGH] Gold buy/sell fees hardcoded (1% / 0.5%) — not configurable
- **Agent:** Backend Tech Lead (Carlos Mendoza)
- **File:** `GoldSavingsController.php:108, 206`
- **Fix:** Use Fee model or SystemSettings for gold fee config
- **Route:** 🔵 sonnet · high · full

### T-016 [MEDIUM] PlatformRevenue amount rounded to 8 decimals for fiat
- **Agent:** Laravel/PHP Core Dev (Aisha Rahman)
- **File:** `WalletService.php:122`
- **Fix:** `round($fee, 2)` for SYP/USD, `round($fee, 8)` only for crypto
- **Route:** 🟢 haiku · low · ultra

### T-017 [LOW] FeeService::getFeePreview() returns "%" for zero percentage
- **Agent:** Laravel/PHP Core Dev (Aisha Rahman)
- **File:** `FeeService.php:212`
- **Fix:** Handle `percentage=0` edge case
- **Route:** 🟢 haiku · low · ultra

### T-018 [LOW] Fee model `name` (Arabic) vs `name_en` — inconsistent dual-language pattern
- **Agent:** Content Strategist (Margaret "Peg" O'Sullivan)
- **File:** `Fee.php:33-51` + codebase-wide audit
- **Fix:** Standardize to `name_ar`/`name_en` or `name`+`locale`
- **Route:** 🟢 haiku · low · full

---

## 🏛️ DOMAIN 4: TRANSACTION + CONCURRENCY (5 tasks)
> سباق الحالات والمعاملات

### T-019 [CRITICAL] GoldWallet creditGrams()/debitGrams() — NO lockForUpdate
- **Agent:** Backend Tech Lead (Carlos Mendoza)
- **File:** `GoldWallet.php:43-58`, `GoldSavingsController.php:111-183`
- **Bug:** No row lock on GoldWallet → concurrent buy/sell create lost update
- **Fix:** Add `lockForUpdate` inside DB::transaction for all gold operations
- **Test:** GoldSavingsConcurrencyTest
- **Route:** 🟣 opus · high · full

### T-020 [HIGH] GoldSavingsController::sell() creates USD wallet outside lockForUpdate — TOCTOU
- **Agent:** Security & Compliance Architect (Dr. Ruth Goldberg)
- **File:** `GoldSavingsController.php:216-222`
- **Fix:** Create wallet inside lock scope or pre-create
- **Route:** 🟣 opus · high · full

### T-021 [HIGH] Transaction::reverse() records ADJUSTMENT but never corrects wallet balance
- **Agent:** Backend Tech Lead (Carlos Mendoza)
- **File:** `Transaction.php:155-187`
- **Bug:** Reversal is ledger-only — wallet balance stays inflated
- **Fix:** `reverse()` must call `Wallet::credit()`/`debit()` + add regression guard
- **Route:** 🟣 opus · max · full

### T-022 [MEDIUM] PaymentRequestController notifications inside DB transaction
- **Agent:** Microservices & Queue Handler (Priya Nair)
- **File:** `PaymentRequestController.php:176-183, 325-333`
- **Fix:** Move notifications outside transaction boundary; use dispatch-after-commit
- **Route:** 🔵 sonnet · medium · ultra

### T-023 [LOW] GoldSavingsController::wallet() UPDATEs current_value_usd on every GET
- **Agent:** Performance & Load Analyst (Ahmed Farouk)
- **File:** `GoldSavingsController.php:67`
- **Fix:** Cache current_value_usd; update only on price change or daily cron
- **Route:** 🔵 sonnet · medium · full

---

## 🏛️ DOMAIN 5: PAYROLL SYSTEM (4 tasks)
> توزيع الرواتب — held balance, capture invariant, notifications

### T-024 [HIGH] Payroll releaseHeldFor() capture() breaks available_balance — linked to T-008
- **Agent:** Backend Tech Lead (Carlos Mendoza)
- **File:** `PayrollService.php:252-254`
- **Fix:** Must align with Wallet capture/balance invariant fix
- **Route:** 🟣 opus · high · full

### T-025 [MEDIUM] Held payroll items have no overdraw protection
- **Agent:** Microservices & Queue Handler (Priya Nair)
- **File:** `PayrollService.php:188-199`
- **Fix:** Prevent company from spending held balance; enforce `available_balance` guard
- **Route:** 🔵 sonnet · medium · ultra

### T-026 [MEDIUM] ProcessPayrollBatchJob silent failure if queue not configured
- **Agent:** Microservices & Queue Handler (Priya Nair)
- **File:** `ProcessPayrollBatchJob.php:44-55`
- **Fix:** Fallback to sync dispatch if queue unavailable; add batch status monitor
- **Route:** 🔵 sonnet · medium · ultra

### T-027 [LOW] payrol:expire-holds releases money silently — no company notification
- **Agent:** Microservices & Queue Handler (Priya Nair)
- **File:** `PayrollService.php:294-335`
- **Fix:** Add notification to company on hold expiry
- **Route:** 🟢 haiku · low · ultra

---

## 🏛️ DOMAIN 6: SECURITY + AUTH (7 tasks)
> الثغرات الأمنية والمصادقة

### T-028 [HIGH] AdminController::reverseTransaction() uses auth()->user() — wrong guard
- **Agent:** Security & Compliance Architect (Dr. Ruth Goldberg)
- **File:** `AdminController.php:556-562`
- **Fix:** Use `auth('admin')->user()` or inject admin from request
- **Route:** 🟣 opus · high · full

### T-029 [HIGH] PaymentRequestController::pay() — test mode endpoints gated by APP_ENV only
- **Agent:** Security & Compliance Architect (Dr. Ruth Goldberg)
- **File:** `routes/api.php:573-579`
- **Fix:** Add second factor (feature flag + IP whitelist) beyond APP_ENV
- **Route:** 🟣 opus · high · full

### T-030 [HIGH] AuthController login leaks `user_id` in 2FA response
- **Agent:** API & Integration Specialist (Marcus Blackwood)
- **File:** `AuthController.php:75`
- **Fix:** Remove user_id from 2FA-required response (enumeration leak)
- **Route:** 🔵 sonnet · medium · full

### T-031 [HIGH] AuthController::resetPassword() leaks registered emails via `exists` rule
- **Agent:** Laravel/PHP Core Dev (Aisha Rahman)
- **File:** `AuthController.php:460`
- **Fix:** Remove `exists:users,email` validation; return generic error
- **Route:** 🔵 sonnet · medium · ultra

### T-032 [MEDIUM] 2FA verifyCode() no rate limiting — brute-force 1M combinations
- **Agent:** API & Integration Specialist (Marcus Blackwood)
- **File:** `TwoFactorService.php:32-38`
- **Fix:** IP + account rate limiting on 2FA confirm endpoint (5 attempts/15min)
- **Route:** 🔵 sonnet · medium · full

### T-033 [MEDIUM] TwoFactorService recovery codes not one-time-use at DB level
- **Agent:** Data & Schema Engineer (Elena Petrova)
- **File:** `TwoFactorService.php:116-130`
- **Fix:** Consumed recovery codes should be removed from array before save; add unique constraint
- **Route:** 🔵 sonnet · high · full

### T-034 [MEDIUM] KycController::sendEmailCode() leaks OTP in response
- **Agent:** Laravel/PHP Core Dev (Aisha Rahman)
- **File:** `KycController.php:67`
- **Fix:** Gate `code` behind `!app()->environment('production')`
- **Route:** 🔵 sonnet · medium · ultra

---

## 🏛️ DOMAIN 7: DEAD CODE + ORPHANED ROUTES (3 tasks)
> كود ميت — مسارات لا تعمل

### T-035 [HIGH] Route `GET /wallets/{wallet}/deposit-address` calls non-existent method → 500
- **Agent:** API & Integration Specialist (Marcus Blackwood)
- **File:** `routes/api.php:165` → `WalletController::depositAddress()` missing
- **Fix:** Implement method OR remove route OR add 501 fallback
- **Route:** 🔵 sonnet · medium · full

### T-036 [MEDIUM] public/sakk-admin/admin.js (1629 lines) — orphaned? Verify inclusions
- **Agent:** Blade Architect (Nguyen Van Minh)
- **File:** `public/sakk-admin/admin.js` — check which layouts load it
- **Fix:** Wire into layout OR delete if dead code; verify via grep for `<script.*admin.js`
- **Route:** 🔵 sonnet · medium · full

### T-037 [MEDIUM] navbar.blade.php + admin.js rebrand artifacts "CARDA Wallet"
- **Agent:** Content Strategist (Margaret "Peg" O'Sullivan)
- **File:** `navbar.blade.php:2`, `admin.js` comments
- **Fix:** Replace "CARDA" → "SAKK"/"صكّ" in all comments+strings
- **Route:** 🟢 haiku · low · full

---

## 🏛️ DOMAIN 8: ADMIN PANEL + GOD CONTROLLER (4 tasks)
> لوحة الإدارة — إعادة هيكلة

### T-038 [MEDIUM] AdminController god class 55+ methods / ~2000 lines
- **Agent:** Principal System Architect (Vikram Rao) + Backend Tech Lead (Carlos Mendoza)
- **File:** `AdminController.php` — entire file
- **Fix:** Split into domain controllers: AdminUserController, AdminWalletController, AdminSystemController, AdminReportsController etc.
- **Route:** 🟣 opus · max · full

### T-039 [MEDIUM] AdminController::updateUser() may leak guarded fields through $validated
- **Agent:** Security & Compliance Architect (Dr. Ruth Goldberg)
- **File:** `AdminController.php:309`
- **Fix:** Audit UpdateUserRequest rules vs guarded fields; never allow `kyc_status`/`is_admin` override
- **Route:** 🟣 opus · high · full

### T-040 [MEDIUM] Admin views have inline CSS in every Blade file (~80+ files) — no caching
- **Agent:** CSS/Tailwind & A11y Expert (Grace Achieng)
- **File:** All `resources/views/admin/*.blade.php`
- **Fix:** Extract inline styles to compiled CSS file; use Laravel Mix/Vite
- **Route:** 🔵 sonnet · high · full

### T-041 [LOW] Admin RTL search inputs use hardcoded LTR padding
- **Agent:** CSS/Tailwind & A11y Expert (Grace Achieng)
- **File:** `navbar.blade.php:86,499`, `merchants/documents.blade.php:66`, `merchants/index.blade.php:81`, `transactions/index.blade.php:111`, `users/index.blade.php:139`
- **Fix:** Replace `padding-left`/`padding-right` with `padding-inline-start`/`padding-inline-end`
- **Route:** 🔵 sonnet · medium · full

---

## 🏛️ DOMAIN 9: MOBILE + FLUTTER (4 tasks)
> تطبيق المحمول

### T-042 [MEDIUM] pubspec name still "carda_wallet" — breaks app identity
- **Agent:** Mobile Tech Lead (Fatima Al-Sayed)
- **File:** `mobile/pubspec.yaml`
- **Fix:** Rename to `sakk_wallet`; update Android package + iOS bundle
- **Route:** 🔵 sonnet · high · full

### T-043 [MEDIUM] FCM gms-plugin disabled (offline build workaround) + FCMService uncommitted
- **Agent:** Native Performance Optimizer (Dmitri Volkov)
- **File:** `mobile/android/settings.gradle.kts`, `app/build.gradle.kts`, FCMService
- **Fix:** Re-enable GMS plugin; commit FCMService HTTP v1 rewrite; test notifications
- **Route:** 🔵 sonnet · high · full

### T-044 [MEDIUM] Mobile needs HTTPS-only enforcement + allowBackup=false
- **Agent:** Native Performance Optimizer (Dmitri Volkov)
- **File:** `mobile/android/app/src/main/AndroidManifest.xml`
- **Fix:** Set `android:allowBackup="false"`, add network security config for HTTPS-only
- **Route:** 🔵 sonnet · medium · full

### T-045 [LOW] Mobile cards tab shows "قريباً" via cardsEnabledProvider — needs full gate removal when Stripe ready
- **Agent:** Flutter Clean Architect (João Silva)
- **File:** `mobile/lib/...cards_page...`
- **Fix:** Cards feature gate design (stripe-ready toggle without app update)
- **Route:** 🔵 sonnet · medium · full

---

## 🏛️ DOMAIN 10: TESTING + CI/CD (4 tasks)
> الاختبارات والنشر المستمر

### T-046 [HIGH] Fix 24 pre-existing test failures (card-503 + N1 issues)
- **Agent:** Automated Testing Engineer (Kwame Mensah) + QA & SRE Lead (Barbara Jensen)
- **File:** All test suite
- **Scope:** Diagnose 24 failing tests (gated card features, SQLite limitations, rate changes)
- **Fix:** Update assertions; add CardsFeature::enabled() mock for card tests
- **Route:** 🔵 sonnet · high · full

### T-047 [MEDIUM] Mobile Flutter E2E tests missing — add integration test suite
- **Agent:** Automated Testing Engineer (Kwame Mensah) + Mobile Tech Lead (Fatima Al-Sayed)
- **File:** `mobile/test/` — currently limited unit tests
- **Build:** Add integration/widget tests for: auth flow, wallet dashboard, transfer, gold buy/sell, payroll
- **Route:** 🔵 sonnet · high · full

### T-048 [MEDIUM] CI/CD pipeline for SAKK — automated test runner + deploy
- **Agent:** CI/CD Pipeline Engineer (Tomás Herrera) + DevOps & Cloud Lead (Linda Schmidt)
- **Build:** GitHub Actions workflow: `composer install → phpunit → larastan → flutter test → flutter build apk`
- **Route:** 🔵 sonnet · high · full

### T-049 [MEDIUM] Performance/load test wallet operations under concurrent load
- **Agent:** Performance & Load Analyst (Ahmed Farouk)
- **Build:** K6/JMeter script: concurrent wallet transfers, gold buy/sell, payroll distribution
- **Target:** Identify race conditions + throughput limits
- **Route:** 🔵 sonnet · high · full

---

## 🏛️ DOMAIN 11: PENETRATION TEST + SECURITY AUDIT (3 tasks)
> اختبار الاختراق الشامل

### T-050 [CRITICAL] Full offensive pentest — OWASP API Security Top 10 + STRIDE
- **Agent:** Security & Compliance Architect (Dr. Ruth Goldberg) + Manual Exploratory Tester (Rosa Giménez)
- **Scope:** All API endpoints, webhook security, mass assignment, IDOR, rate limiting, auth bypass
- **Deliverable:** Updated `docs/SECURITY_PENTEST_2026-06-27.md` with new findings + regression
- **Route:** 🟣 opus · max · full

### T-051 [HIGH] Webhook Stripe Issuing authorization — 2-second timeout risk
- **Agent:** Performance & Load Analyst (Ahmed Farouk) + Security & Compliance Architect (Dr. Ruth Goldberg)
- **File:** `StripeIssuingWebhookController.php:97-111`
- **Fix:** Add async processing; monitor response time; add timeout guard
- **Route:** 🟣 opus · high · full

### T-052 [HIGH] CSP 'unsafe-eval' for Alpine.js → migrate to @alpinejs/csp build
- **Agent:** Security & Compliance Architect (Dr. Ruth Goldberg) + JS/Vue.js Engineer (Lars Eriksson)
- **File:** `SecurityHeaders.php`, admin layouts
- **Fix:** Migrate from standard Alpine CDN to @alpinejs/csp; register components; drop 'unsafe-eval'
- **Route:** 🟣 opus · max · full

---

## 🏛️ DOMAIN 12: GIT + INFRA + CLEANUP (3 tasks)
> تنظيف المستودع والبنية التحتية

### T-053 [MEDIUM] Purge sqlite from git history + rotate leaked secrets
- **Agent:** DevOps & Cloud Lead (Linda Schmidt)
- **Scope:** `git filter-repo` purge of `database.sqlite`, `.env.*`, secrets; rotate admin@sakk.com token
- **Route:** 🔵 sonnet · high · full

### T-054 [MEDIUM] Generate release keystore + update assetlinks.json SHA-256
- **Agent:** Containerization & Orchestration (Wei Chen)
- **Build:** Production keystore; update `assetlinks.json` for Android App Links
- **Route:** 🔵 sonnet · medium · ultra

### T-055 [MEDIUM] .env.example APP_NAME "صكك" missing shadda; add missing env vars
- **Agent:** Laravel/PHP Core Dev (Aisha Rahman)
- **File:** `.env.example`
- **Fix:** `APP_NAME="صَكّ"` proper orthography; document all new env vars (WhatsApp, Stripe, FCM, payroll)
- **Route:** 🟢 haiku · low · ultra

---

## 🏛️ DOMAIN 13: UX + DESIGN + CONTENT (3 tasks)
> تجربة المستخدم والتصميم

### T-056 [MEDIUM] Missing escrow/mediation system for fintech platform — MVP design
- **Agent:** Chief Product Strategist (Dr. Amara Okafor) + UI/UX Designer (Daniel Kim)
- **Scope:** Define escrow MVP: hold→release→arbitration flow; wireframes + user journey
- **Deliverable:** PRD + Figma mockups for escrow module (Gate 0 input)
- **Route:** 🟣 opus · high · lite

### T-057 [LOW] Privacy policy + ToS hardcoded in AuthController — move to DB/files
- **Agent:** Content Strategist (Margaret "Peg" O'Sullivan) + Laravel/PHP Core Dev (Aisha Rahman)
- **File:** `AuthController.php:720-862`
- **Fix:** Store in DB with versioning; admin editable; API returns current version
- **Route:** 🔵 sonnet · medium · full

### T-058 [LOW] config('app.pay_url_base') may be unset → broken payment URLs
- **Agent:** API & Integration Specialist (Marcus Blackwood)
- **File:** `PaymentRequestController.php:365`
- **Fix:** Add default + validation in config; document in .env.example
- **Route:** 🟢 haiku · low · full

---

## 📊 EXECUTION PLAN

### Wave 1 — CRITICAL/HIGH blockers (immediate)
T-001 T-002 T-003 T-008 T-009 T-019 T-020 T-021 T-028 T-029 T-030 T-035 T-046 T-050 T-052
→ 15 tasks, 7 agents: Security Architect, Backend Tech Lead, Principal Architect, API Specialist

### Wave 2 — MEDIUM bugs + structural
T-004 T-005 T-006 T-010 T-011 T-014 T-015 T-024 T-025 T-031 T-032 T-033 T-038 T-039
→ 14 tasks, 9 agents

### Wave 3 — Testing + CI/CD + Mobile
T-042 T-043 T-044 T-045 T-047 T-048 T-049 T-054 T-055
→ 9 tasks, 6 agents

### Wave 4 — Cleanup + UX + Low priority
T-007 T-012 T-013 T-016 T-017 T-018 T-022 T-023 T-026 T-027 T-034 T-036 T-037 T-040 T-041
T-053 T-056 T-057 T-058
→ 19 tasks, 10 agents

---

## 👑 CEO DIRECTIVE
- **Priority:** Wave 1 (15 tasks) → Wave 2 (14) → Wave 3 (9) → Wave 4 (19)
- **Total: 58 tasks** (exceeds 50 target — aggressive coverage)
- **Deadline:** Gate 3 exit requires Wave 1+2 closed
- **Verification gate:** All fixes must pass existing test suite + new regression tests
- **Coverage target:** ≥90% backend, ≥80% mobile before Gate 4
- **No blind merge:** Every fix via dedicated branch + PR review

---

*Created by SOFI CEO — 2026-06-29 · Next: Deploy Wave 1 via RCCF delegation blocks*
