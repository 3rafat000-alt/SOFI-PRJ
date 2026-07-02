# Carda Wallet — Pre-Launch QA Report

Team-grade verification across all three tiers (backend API · admin panel · Flutter app), every operation
exercised like a human and recorded as **video + step screenshots + checks**. Findings logged in
[FINDINGS.md](FINDINGS.md); plan in [PLAN.md](PLAN.md); per-suite HTML reports under `reports/`.

## Verdict

**Conditional GO.** Core money-movement and security boundaries are solid; all blocker defects found were
fixed and re-verified. Two low/medium polish items remain (F-005 font CSP, F-008 reverse error-format) plus
one product question (O-1 cashback minting). No open blocker.

## Suite results

| suite | tier | result | what it proves |
|---|---|---|---|
| smoke | all | 21/21 | admin login + 20 pages render (no 5xx/crash), API health, anon→401 |
| admin-kyc | admin | 4/4 | KYC levels: render + create + edit + delete via real UI |
| admin-deep | admin | 3/3 | gold price edit + fee toggle (UI→DB, restored) + reverse-validation |
| api-authz | api | 7/7 | privilege boundary, IDOR read/write, auth, money invariants |
| e2e-transfer | cross | 3/3 | money moves API→DB (debit/credit/cashback/3 rows)→admin reflects |
| mobile | flutter | 3/3 | flutter analyze (0 errors) + wallet/user DTO↔API contract aligned |
| flow-gold | flow | 2/2 | buy (USD debit +1% fee → grams) / sell (grams → USD), DB-verified |
| flow-onboarding | flow | 1/1 | register → persist → token → /auth/me → cleanup |
| flow-payment-request | flow | 2/2 | create (pending) → owner-only cancel (IDOR-protected) → cancelled |
| flow-cards | flow | 2/2 | issue → freeze → unfreeze (DB status), IDOR-protected, cleaned up |

**48 scenarios** across 10 suites, every UI operation recorded to video. Backend unit suite: **596 passing**.
The deeper flows added live IDOR checks (cards/payment-requests) and money-path verification (gold buy/sell,
PIN-gated) — all clean, no new findings.

## Findings ledger

| id | sev | title | status |
|---|---|---|---|
| F-001 | 🔴 | `/admin/kyc/levels` 404 (route order vs `/{kyc}`) | fixed |
| F-002 | 🔴 | `admin.kyc.levels` Blade view missing → 500 | fixed (view built) |
| F-003 | 🟠 | `Fee::getByCode` caches Eloquent model → 500 (db cache) | fixed |
| F-004 | 🔴 | KYC level create/update 500 on blank description (NOT NULL) | fixed |
| F-005 | 🟡 | CSP blocks Cairo web font on all admin pages | open |
| F-006 | 🔵 | transfer logs FCM TypeError on null sender token | open |
| F-007 | — | (not a bug) +1.38 "leak" is intentional 1% cashback | resolved |
| F-008 | 🟡 | reverse endpoint: JSON 200 on success, 302 on validation error | open |
| O-1 | ⚪ | cashback minted with no treasury counter-entry | product Q |

## Security posture (verified, no findings)

- **Privilege boundary** — normal user denied on `/admin/*` (401/403).
- **IDOR** — user A cannot read/write user B's wallet; own-wallet positive control bites.
- **Auth** — sensitive endpoints 401 without a token.
- **Money invariants** — transfer rejects negative/zero/self/over-balance; gold/fx reject non-positive.

## How to run

```bash
node tests/run.cjs smoke admin-kyc api-authz e2e-transfer admin-deep mobile
# open tests/reports/<suite>.html  → per-operation video + steps + checks
# refresh flutter analyze report:  cd mobile && flutter analyze --no-pub > tests/artifacts/flutter-analyze.txt 2>&1
```

## Remaining (not yet built)

- **design** suite — RTL/responsive/contrast/a11y (WCAG) + visual regression. F-005 is the first finding here.
- Cross-tier register→KYC→fund flow (E2E covers fund→transfer; registration path still to script).
- Card issuance / gold buy-sell / payment-request deep flows.
