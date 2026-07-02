# Feature Report — البطاقات الافتراضية وستريب (Virtual Cards + Stripe)

**Date:** 2026-07-01 · **Gate:** 5 (Quality) · **Command:** `/sofi-feature` · **Head:** `869961b`
**Reviewer:** CEO (4-pillar architect review) · **Fixer:** sofi-laravel-core-dev

## Executive summary
Cards feature was theater: gated ON only when Stripe is configured, yet the sole create
endpoint the mobile app uses (`POST /cards`) minted **fake local Luhn-valid PANs**, never
real Stripe cards. Decision (owner): **wire app → Stripe issue**. Remediated backend-only —
endpoint unchanged, `store()` internals swapped to real Stripe issuance, mobile untouched.

## 4-pillar verdict (pre-fix)
| Pillar | State |
|---|---|
| ① Data & Logic | at-risk — fake-card paradox 🔴, split limits 🟠, PAN inventory 🟠 |
| ② Admin & Ops | at-risk — Stripe lifecycle no audit 🟠 |
| ③ UI/UX | minor — off-brand default color 🟡 |
| ④ Edge Cases | solid — webhook 2s/locking/idempotent-reversal/IDOR all covered |

## Fixes applied (3 commits)
- `e9d0506` — `store()` now: `isConfigured()` guard → `CardService::chargePurchaseFee()` →
  `StripeIssuingService::issueVirtualCard()` → `refundPurchaseFee()` on failure (no charge
  survives a failed issue). Unconfigured Stripe → clean 422, no fake-card fallback. Response
  stays `CardResource`-shaped → mobile needs no change.
- `2ec1693` — Limits consolidated: count via `KycService::cardsLimitForUser` (config-driven,
  displaces Stripe's hardcoded `[0→1,2→3,3→10]`); spend via `CardService::DAILY_LIMIT=500` /
  `MONTHLY_LIMIT=5000` (tighter Stripe-side figures, authoritative for real money).
- `869961b` — Tests: `CardStripeIssuanceTest` (issue via Stripe / fee refund on failure /
  422 when unconfigured).
- Also in-commit: Stripe `freeze/unfreeze/cancelCard` now write `ActivityLog`
  (`card.freeze/unfreeze/cancel`); `importCardsFromFile()` DISABLED (PCI — no more plaintext
  PAN ingest from disk); off-brand `#6366f1` → SAKK burgundy `#7A1F2B` (now-dead local path).

## Verification
`php artisan test --filter=Card` → **60 passed, 170 assertions, 0 fail**. No existing tests weakened.

## Residual / deferred
- `VirtualCard::boot()` local fake-PAN branch left intact but unreachable from app path (kept
  for any non-provider path). `getCardFromInventory()` left as dead code.
- Not re-reviewed this pass: mobile cards UI taste (pillar ③ depth), admin Blade card views.

## Decisions (irreversible → DECISIONS.md)
- Card issuance is **Stripe-only** for the app; local PAN generation retired from the live path.
- Spend cap authoritative values: $500/day, $5,000/month.
- Manual card-inventory PAN import permanently disabled (PCI scope reduction).
