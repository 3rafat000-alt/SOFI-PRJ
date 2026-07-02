# /sofi-spec-review — Stripe Virtual Cards System (Issuing & Funding) · 2026-07-02

**Classification:** Tier-A Money Surface, gated behind Stripe. Method: 4-pillar + 7 steel rules, SEV-first, read-only → surgical fix.

## Executive summary
The Stripe seam was well-hardened (webhook signature fail-closed, SEC-H4 event idempotency, dual-lock funding, idempotent capture/reversal, consistent cents math, 7 test files) with **one real hole**: the real-time authorization path lacked an idempotency guard. Fixed + regression-tested. No 🔴 (the failure over-*reserved* the user's own funds, recoverable — not a leak).

## The finding

| ID | Sev | Finding | file:line | Fix | Commit |
|----|-----|---------|-----------|-----|--------|
| C-SEV-1 | 🟠 HIGH | `handleAuthorizationRequest` had NO idempotency guard on Stripe `authId` — unconditional `hold()` + `daily/monthly_spent` increment + PROCESSING tx. This event type is deliberately EXEMPT from the controller's event-id `Cache::add` dedup (`StripeIssuingWebhookController.php:55`, must answer synchronously in 2s), so a Stripe re-delivery or signed-payload replay double-held funds, double-counted limits, and left an orphan PROCESSING tx. | StripeIssuingService.php:384-502 | Guard inside the locked `DB::transaction` (after `Wallet::lockForUpdate`, before hold): `Transaction::where('metadata->authorization_id',$authId)->first()` → if found, return the same `['approved'=>true,'transaction_id'=>...,'idempotent_replay'=>true]` without a second hold/increment/row. Atomic under the wallet lock → concurrent deliveries serialize, only the first passes. | `5ae3966` |

## Refuted preflags
- `StripeIssuingService:22` "swallowed status" = a `use Cache;` import. Not a swallow.
- `card_repository.dart:70` "swallowed status" = `cardsEnabled()` `catch(_)→false` — **intentional fail-closed** on a public feature flag (correct); `getCards` uses `ApiException.fromDioError` ✅.
- cents math `/100` (`:424,521,567`) + `*100` for limits (`:244,248`) — **consistent**, cards are USD, no SYP ÷100 trap.

## What was already sound (untouched)
- Webhook signature **fail-closed 401** (`Controller:35`); non-auth events dedup via atomic `Cache::add` (SEC-H4) then queue; 2s-timeout respected.
- Funding/issuance: `DB::transaction` + dual `lockForUpdate` (wallet+card), balance re-check under lock, refund-on-issuance-failure.
- Capture idempotent (PROCESSING filter + lock, `:548`); reversal idempotent (SEC-H4 already-REFUNDED guard).

## Rule scorecard (post-fix)
1 (422) ✅ · 2 (ApiException) ✅ (getCards; cardsEnabled fail-closed intentional) · 3 (/admin 503) ✅ `EnsureCardsEnabled` · 4 (unique/race) ✅ **now incl. auth.request** · 5 (cents math) ✅ consistent · 6 (webhook shape) ✅ signature verified · 7 (Tier-A ≥90%) ✅ 7 test files + new double-auth idempotency regression.

## Verification
`php artisan test tests/Feature/Card tests/Unit/Services/StripeIssuingServiceTest.php tests/Feature/Webhooks/StripeIssuingWebhookControllerTest.php` = **82 passed / 234 assertions** (Stripe/Card filter full-run 155 pass). `php -l` clean. Tree clean.

## Backlog (🟡)
- Stale `reserved_balance` comment block in `StripeIssuingServiceTest.php:577-592` describes a bug that no longer matches current code (which uses `Wallet::hold()`/`pending_balance` correctly). Cosmetic cleanup, tech-lead. Non-blocking.
- N+1 preflags (CardService/ProcessStripeIssuingWebhook `->get()` loops) — perf, low value.

## Verdict
① sound (post-fix) · ② sound · ③ sound · ④ sound. The real-time authorization was the one money path in the Stripe seam without an idempotency guard — now closed atomically under the wallet lock, regression-locked. Stripe cards front is enterprise-grade.
