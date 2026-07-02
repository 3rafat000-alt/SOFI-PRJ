# Carda Wallet — Master QA Plan

Pre-launch, team-grade verification across all three tiers. Every operation is exercised like a
human would, recorded as **video + step screenshots + checks**, and rolled up into an HTML report.

- Backend: Laravel, API prefix `/api/v1`, ~193 API routes, admin web under `/admin`.
- Admin: Blade (≈20 sections).
- Mobile: Flutter, 18 features.
- Seeded accounts: `admin@sakk.com` / `ahmad@test.com` (L2 $500) / `sara@test.com` (L1 $100) — all `password`.

## Harness

```
tests/
  run.cjs              # node tests/run.cjs <suite> [suite2 ...]
  lib/
    config.cjs         # base URL, creds, paths, viewport, locale (ar-SY)
    runner.cjs         # engine: per-scenario context, video, trace, console/net capture, step shots
    report.cjs         # HTML (embedded video) + Markdown + JSON
    api.cjs            # backend API client for cross-tier verification
  suites/<name>.cjs    # each exports async (runner, cfg) and calls runner.scenario(...)
  artifacts/{video,shots,trace,state}/
  reports/<suite>.{html,md,json}
```

Principle: **verify every operation from 3 angles** — the API request, its effect in the DB, and its
reflection in the admin panel. Soft checks (`check`) record findings without aborting; hard checks
(`must`) stop the scenario. A 5xx, page error, or failed check fails the scenario.

## Suites

| # | suite | tier | scope | status |
|---|-------|------|-------|--------|
| 1 | `smoke` | all | admin login + every section opens, API health, authz negative | ✅ 21/21 |
| 2 | `admin-kyc` | admin | KYC levels render + create + edit + delete via real UI | ✅ 4/4 |
| 3 | `admin-deep` | admin | gold price edit + fee toggle (UI→DB, restored) + reverse-validation guard | ✅ 3/3 |
| 4 | `api-authz` | backend | privilege escalation, IDOR read/write, auth, money invariants | ✅ 7/7 |
| 5 | `e2e-transfer` | cross | fund → transfer → DB (debit/credit/cashback/rows) → admin reflects | ✅ 3/3 |
| 6 | `mobile` | flutter | `flutter analyze` (0 errors) + wallet/user DTO↔API contract diff | ✅ 3/3 |
| 7 | `flow-gold` | flow | gold buy (USD debit +1% fee → grams) / sell, DB-verified, PIN-gated | ✅ 2/2 |
| 8 | `flow-onboarding` | flow | register → persist → token → `/auth/me` → cleanup | ✅ 1/1 |
| 9 | `flow-payment-request` | flow | create (pending) → owner-only cancel (IDOR) → cancelled | ✅ 2/2 |
| 10 | `flow-cards` | flow | issue → freeze → unfreeze (DB), IDOR-protected, cleaned up | ✅ 2/2 |
| 11 | `design` | admin/web | visual/pattern audit: RTL, responsive, contrast/a11y, empty/loading/error states | ⏳ not built (F-005 is the first finding) |

**Total: 48/48 scenarios green** across 10 suites. Run all:
`node tests/run.cjs smoke admin-kyc admin-deep api-authz e2e-transfer mobile flow-gold flow-onboarding flow-payment-request flow-cards` → `reports/index.html`.

Still to script: the `design` suite (a11y/RTL/responsive), payment-request *pay* money path (requestee resolution), gold savings goals.

## Cross-tier coverage matrix (operation → 3 angles)

| operation | API | DB | Admin reflect |
|---|---|---|---|
| register user | POST /auth/register | users row | appears in /admin/users |
| login + me | POST /auth/login, GET /auth/me | last_login fields | user activity |
| KYC submit/approve | api kyc + admin approve | kyc_status flips | /admin/kyc list updates |
| wallet fund | api | balance ↑ | /admin/transactions |
| transfer | POST transfer | sender ↓ / receiver ↑, fee applied | /admin/transactions + fee |
| gold buy/convert | api gold | gold balance, rate | /admin/gold/transactions |
| card issue/freeze | api/admin cards | card status | /admin/cards |
| fee change | admin fees | fees row + cache | next transfer uses new fee |

## Run

```bash
node tests/run.cjs smoke            # one suite
node tests/run.cjs smoke admin api  # several
HEADED=1 node tests/run.cjs smoke   # watch the browser
# open tests/reports/<suite>.html  -> per-operation video + steps + checks
```

Findings are logged in [FINDINGS.md](FINDINGS.md).
