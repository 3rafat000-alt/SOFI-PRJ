# Audit Report — PRJ-SAKK — Gold Savings (ميزة الذهب) — 2026-07-01

## 1. Executive summary
Full 4-pillar spec-review of the gold buy/sell/valuation feature found **two 🔴 exploitable financial bugs, four 🟠 correctness bugs, and several 🟡 hardening gaps**. The sharpest: `GoldWallet.balance_grams` tracked grams **karat-blind**, so a user could buy cheap عيار18 grams and sell them declaring عيار24 at the higher price — risk-free arbitrage draining the platform. Also, a frozen wallet's ignored `debit()` return let gold be credited with no USD taken (free gold). **All 🔴🟠🟡 are now fixed, committed, and proven by regression tests (14/14 gold tests pass).** Two items deferred with reason (frozen-credit policy, status→enum). **Verdict: gold feature financially sound; quality bar met.**

### الملخص التنفيذي
مراجعة كاملة لميزة الذهب (شراء/بيع/تقييم) كشفت ثغرتين ماليتين حرجتين وأربع أخطاء صحّة. الأخطر: تتبّع الغرامات دون تمييز العيار، ما سمح بشراء عيار 18 الرخيص وبيعه كعيار 24 الأغلى (ربح بلا مخاطرة)، إضافةً إلى منح ذهب مجّاناً عند تجميد المحفظة. **جميع الثغرات الحرجة والمتوسطة أُصلحت وثُبّتت باختبارات (14/14 ناجحة).** بندان مؤجّلان بمبرّر.

## 2. Scope & method
- **Feature:** gold savings — `GoldSavingsController` (API buy/sell/wallet/stats/prices/transactions), `GoldWallet`/`GoldHolding`/`GoldTransaction`/`GoldPrice` models, `GoldPriceService`, admin `GoldPriceController`, `FeeService` gold path, migrations, mobile `gold_repository`/`gold_models`.
- **Method:** `/sofi-spec-review` — Python `feature_scan.py` located 52 files + static pre-flags (0 model tokens); manual read of 12 core files; 4-pillar matrix (Data&Logic · Admin&Ops · UI/UX · Edge Cases).
- **Remediation:** `/sofi-fix` → `sofi-laravel-core-dev` (two bounded passes). **Verify:** `/sofi-secure verify` — static path-trace + `php artisan test --filter=Gold`.

## 3. Findings

| SEV | file:line | Defect | Proof | Fix | Status |
|-----|-----------|--------|-------|-----|--------|
| 🔴 | GoldSavingsController.php:133 (orig) | `debit()` return ignored → frozen wallet credited gold with no USD taken | `Wallet::debit()` returns false on `is_frozen`; code proceeded to `creditGrams()` | capture bool → throw → roll back DB::transaction → 422 | ✅ 4110184 |
| 🔴 | GoldSavingsController.php:218 (orig) / GoldWallet.php debitGrams | karat arbitrage — `balance_grams` karat-blind; sell any karat from one grams pool | buy 18k, sell declaring 24k at higher sell_price | per-karat `gold_holdings`; sell scoped to karat holding | ✅ 4110184 |
| 🟠 | GoldWallet.php:51 (orig) / stats():340 | `total_invested_usd` never reduced on sell → wallet & stats P/L double-count cost basis | after selling all: profit ≈ totalSold − 2×totalBought | avg cost-basis reduced on sell at holding + wallet level; formulas corrected | ✅ 4110184 |
| 🟠 | GoldSavingsController.php:208 (orig) | sell with `netAmount<=0` (fee ≥ revenue) debits gold, credits no USD | `credit()` no-ops on amount<=0 | guard `netAmount<=0 → 422` before writes + check `credit()` return | ✅ 4110184 |
| 🟠 | GoldSavingsController.php:56 / stats() (orig) | valuation blended karats — total grams × avg(sell_price) | 1g 24k valued same as 1g 18k | `currentGoldValue()` sums per-karat grams × that karat's active sell_price | ✅ 29a172c |
| 🟠 | Admin/GoldPriceController.php:61 | synchronous 15s spot fetch on every stale admin page load → hang | `autoRefreshIfStale()` inline `Http::get` | removed inline refresh; hourly `gold:update-prices` cron only | ✅ 4110184 |
| 🟡 | FeeService.php:90 | gold fee silently 0 when Fee code row absent | fresh install → no fee, no signal | `Log::warning` on missing gold fee config | ✅ 4110184 |
| 🟡 | gold_transactions migration | no index on `status` (admin sums filter status=completed constantly) | only (user_id,type)+reference indexed | added `index('status')` migration | ✅ 4110184 |
| 🟡 | gold_transactions.usd_rate_at_time | column declared/cast/fillable but never written | always null | POPULATE via `ExchangeRateService::getRate('USD','SYP')` on buy+sell | ✅ 4110184 |
| 🟡 | Admin/GoldPriceController.php:90 | manual price edit has no audit trail | mistyped price mints arbitrage vs holders | `Log::info` actor id + karat + old→new buy/sell | ✅ 4110184 |
| ⚪ | Wallet.php credit() | sell credits a frozen wallet (`credit()` checks only amount>0, not is_frozen) | frozen wallet still receives sell proceeds | **deferred** — cross-feature policy | ⏸ |
| 🟡 | GoldTransaction.status | free-text string, no state machine (all tx born 'completed') | pending/failed/cancelled never set | **deferred** — design scope | ⏸ |

**Sound as-found (✅ no change):** `lockForUpdate()` on both wallets (no TOCTOU); PIN/biometric fail-closed (SEC C2); new-device guard on buy/sell (api.php:246); reversible migrations; admin lists use `->with('user')` (no N+1); mobile `gold_repository` already uses `ApiException.fromDioError` (spec-review preflag was stale); margin clamped 0–10.

## 4. Remediation
- **Commit `4110184`** — `fix(gold): close karat arbitrage + frozen-wallet leak + P/L basis` — 8 files, +409/−34. New: `GoldHolding.php`, `gold_holdings` migration (+ data-safe backfill from existing transactions), `status` index migration.
- **Commit `29a172c`** — `fix(gold): value holdings per-karat, not blended average` — 2 files, +108/−4.
- **Deferred (2):** ⚪ frozen-credit — blocking incoming credit to a frozen wallet is a product policy decision (frozen conventionally blocks debits, not deposits); changing shared `Wallet::credit()` risks breaking legitimate deposit crediting. 🟡 status→enum state machine — no pending/failed/cancelled path exists yet; out of remediation scope.

## 5. Risk posture / gate
- **Financial-integrity risk: CLOSED.** Both value-leak vectors (arbitrage, frozen free-gold) proven closed by dedicated adversarial regression tests.
- **Tests:** 14/14 gold feature tests pass (79 assertions); `migrate:fresh` clean through both new migrations. One unrelated pre-existing failure (`AuthWalletTest` password-policy) present on master before this work — untouched.
- **Gate 5 (Quality):** gold feature clears the bar. Deferred items are non-blocking (policy + design, not defects).

## 6. Next actions
1. ⏸ **Frozen-wallet credit policy** — decide whether a frozen wallet may receive sell/deposit proceeds → `sofi-security-compliance-architect` (policy) then `sofi-laravel-core-dev` if it changes.
2. ⏸ **status state machine** — if pending/failed gold tx become real, back `status` with an enum + explicit transitions → `sofi-laravel-core-dev`.
3. ⚠ **Host cron** — confirm `* * * * * php artisan schedule:run` is installed so `gold:update-prices` (hourly) actually fires ([[sakk-scheduler-routes-console-not-kernel]]).
4. **Deploy migrations** — `gold_holdings` + `status` index need `php artisan migrate` on staging/prod; backfill runs automatically → `sofi-devops-cloud-lead`.
