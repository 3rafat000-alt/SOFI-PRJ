# /sofi-spec-review — Crypto Withdrawal System (USDT-Only) · 2026-07-02

**Classification:** Tier-A Money Surface. Method: 4-pillar + 7 steel rules, SEV-first. Read-only review → routed fixes.

## Executive summary
The withdrawal *money core* was already safe (KYC fail-closed, overdraft-safe, USDT backend gate from `580d51c`). The review found one architectural flaw (lock scope) + three mobile UX/hygiene gaps. All fixed + committed. No 🔴 fund-loss (the deposit twin's SEV-1 doors were already shut on this path).

## Findings & remediation

| ID | Sev | Finding | file:line | Fix | Commit |
|----|-----|---------|-----------|-----|--------|
| W-SEV-1 | 🟠 | External CCPayment HTTP held inside wallet `lockForUpdate` (contention + partial-failure money window) | CCPaymentController::withdraw:236-288 | Optimistic-debit + async reconcile: Phase A short locked debit+reserve (`PENDING`/`gateway_dispatched=false`), Phase B gateway call OUTSIDE lock, failure→refund under fresh short lock+`FAILED`, idempotency-guarded. New `CCPaymentService::dispatchWithdrawToGateway` (pure HTTP). | `1c1efe3` |
| W-SEV-2 | 🟠 | Mobile withdraw UI offered USDC/BTC/ETH while backend 422-rejects | crypto_withdraw_page.dart:33-45 | Locked `_coins`/`_chains` to USDT only (mirror deposit page) | `ea47413` |
| W-SEV-3 | 🟡 | `_fetchFee` silent swallow | crypto_withdraw_page.dart:79-83 | `debugPrint` log the reason (fee stays optional) | `ea47413` |
| W-SEV-4 | 🟡 | Generic `catch (e)` leaked raw `e.toString()` | crypto_withdraw_page.dart:201-204 | Clean Arabic fallback constant | `ea47413` |

## Rule scorecard (post-fix)
1 (422-JSON) ✅ · 2 (ApiException) ✅ (`_withdraw` uses it; `_fetchFee` now logs) · 3 (/admin 503) ➖N/A · 4 (unique/race) ✅ (lockForUpdate + balance-in-lock; idempotency-key still 🟡 open) · 5 (money math) ✅ (USDT 1:1, overdraft-safe) · 6 (contract parity) ✅ (mobile now matches backend USDT gate) · 7 (Tier-A ≥90%) ✅ (currency gate tested `4abd0f3`; refund-path test recommended).

## Verification
`php artisan test --filter=CCPayment` = **103 passed / 259 assertions**. `dart analyze lib/features/wallets` = No issues. `php -l` clean. Working tree clean.

## Open follow-ups (in HANDOFFS)
- 🟡 **Stuck-withdrawal sweeper** — hard process-kill between Phase A commit and Phase B leaves funds reserved (`PENDING`+`gateway_dispatched=false`) with no gateway call. Add reconcile command (mirror `ReconcileCCPaymentDeposits`). Owner: laravel-core-dev.
- 🟡 **W-SEV-5 idempotency key** — no server-side dedup on withdraw create (random orderId per call); client `_isLoading` guard only.

## Verdict
① Data&Logic: **sound** (post W-SEV-1) · ② Admin&Ops: sound · ③ UI/UX: **sound** (post W-SEV-2/3/4) · ④ Edge/Gaps: mostly-sound (sweeper + idempotency-key deferred). Withdrawal money path is enterprise-grade: KYC fail-closed · overdraft-safe · USDT-locked both layers · no lock across external HTTP.
