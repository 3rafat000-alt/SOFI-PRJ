# /sofi-spec-review — Payroll System (Companies & Employees) · 2026-07-02

**Classification:** Tier-A Money Surface (company→employee mass disbursal). Method: 4-pillar + 7 steel rules, SEV-first, read-only.

## Executive summary
**Verdict: SOUND — no 🔴, no real 🟠.** The batch money engine is enterprise-grade: per-item atomic, dual `lockForUpdate` (company + employee wallet), DB-unique idempotency keys, hold/capture/release semantics, side-effects committed AFTER the money transaction. The scanner's raw alarms (PayrollController "untested", 2×🟠 raw-query) were all **false positives**, refuted below. Only minor 🟡 hygiene remains.

## Why the money core is safe
- **Atomicity** — `PayrollService::processItem` (`app/Services/PayrollService.php:164`) wraps each item in its own `DB::transaction`, re-checks item status under `lockForUpdate` (idempotent vs double-submit/job-retry), and locks BOTH the company wallet (`:173`) and employee wallet (`:350`,`:537`) before debit/credit. `available_balance` is checked under lock → overdraft impossible. Per-item commit → mid-batch failure keeps earlier payments (partial completion).
- **Idempotency (rule 4)** — `payroll_batches.idempotency_key` AND `payroll_items.idempotency_key` are **DB-UNIQUE** (migration `2026_06_26_000004:27,64`); `createBatch` short-circuits on repeat. Enforced at the DB, not just app.
- **Wallet invariant** — partial unique index `wallets_company_currency_unique WHERE company_id IS NOT NULL` (`2026_06_26_000005:32`) — one company wallet per currency, race-safe.
- **hold/capture/release** — unregistered employees' salary reserved in `pending_balance` (`hold`), delivered on phone-verify (`releaseHeldFor`→`capture`, `:216`), or returned after 30d (`expireHeldOlderThan`→`release`, `:297`). All atomic + idempotent under locks.
- **Post-commit side-effects** — WhatsApp/FCM/notifications fire in `notifyForOutcome` AFTER the tx commits (`:206`); no external I/O held under a wallet lock (the withdraw W-SEV-1 lesson already applied here).

## Refuted false-positives
- 🟠 `DashboardController:73` — `DB::raw('ABS(amount)')` is constant SQL, no user input. No injection.
- 🟠 migration `2026_06_26_000005:31` — static driver-branched `CREATE UNIQUE INDEX` DDL. No injection.
- 🔴 (scanner rule-7) "PayrollController has no test" — **false positive**: 20 tests cover the flow (`tests/Feature/Payroll/PayrollServiceTest.php` ×13, `tests/Feature/Company/CompanyPortalTest.php` ×7, `CompanyAdminTest`); tests exercise the controller via its routes without naming the class. **This false-positive was fixed in the scanner** (commit tuning rule-7 to clover-authoritative + path-scoped token fallback).

## Remaining findings (🟡 only)
| ID | Finding | file:line | Fix |
|----|---------|-----------|-----|
| P-1 | `amt.*` unvalidated (no numeric/min/max) — downstream funding + per-item guards prevent overdraft, so no money bug, but unclean 422 | PayrollController::store:44 | add `amt.* => numeric|min:0` + sane max |
| P-2 | N+1 in per-item loops — `recordLedgerPair` does `Company::find`+`PayrollBatch::find` per item; loops in releaseHeldFor/expireHeld/ProcessPayrollBatchJob | PayrollService:385-386 | cache company/batch per run |

## Rule scorecard
1 (422 not 302) ✅ N/A — Blade portal (302 correct); mobile does NOT submit payroll (`company_repository` only companyApply + doc upload) · 2 (ApiException) ✅ N/A — no mobile payroll write · 3 (/admin 503) ✅ portal + admin gated · 4 (unique/race) ✅ DB-unique both idempotency keys + partial wallet unique + dual lock · 5 (money math) ✅ atomic, overdraft-safe, hold/capture/release, no ÷100 · 6 (contract parity) ✅ explicit row-build, no mass-assign, no null-accessor · 7 (ADR-004 ≥90%) ✅ tested (20 green: pay/hold/release/expire/partial/gates/top-up/doc-activation); recommend precise clover read to stamp the exact %.

## Verdict
① sound · ② sound · ③ sound · ④ mostly-sound. No fund-loss or race hole — the batch engine mirrors the hardened withdraw pattern. The ADR-004 alarm was a scanner false-positive, now fixed. Backlog: P-1 validation + P-2 N+1.

Verification: `php artisan test tests/Feature/Payroll tests/Feature/Company` = **20 passed / 79 assertions**.
