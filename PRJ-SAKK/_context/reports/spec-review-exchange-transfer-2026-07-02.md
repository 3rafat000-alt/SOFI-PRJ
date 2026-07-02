# /sofi-spec-review — Currency Exchange & P2P Transfer System · 2026-07-02

**Classification:** Tier-A Money Surface (FX conversion + wallet-to-wallet P2P). Method: 4-pillar + 7 steel rules, SEV-first, read-only → fix.

## Executive summary
Rate math was already correct (spread always favors the platform, customer gets the worse side). Two 🟠, no 🔴: a latent **deadlock** (non-deterministic wallet lock order — masked on sqlite, arms on prod MySQL) and a **zero-coverage FX engine**. Both fixed + regression-locked.

## Findings & remediation

| ID | Sev | Finding | file:line | Fix | Commit |
|----|-----|---------|-----------|-----|--------|
| E-SEV-1 | 🟠 | Non-deterministic wallet lock order → lock-order deadlock under concurrent bidirectional transfers/converts (MySQL/pgsql; masked on sqlite) | TransferService.php:110,120 · WalletService.php:243-244 | Lock both wallets in a single `whereIn([ids])->orderBy('id')->lockForUpdate()` → InnoDB acquires row locks in ascending id order deterministically. TransferService: resolve/create recipient wallet id FIRST (unlocked), then ordered-lock both. | `b2a4c26` |
| E-SEV-2 | 🟠 | FX conversion engine (`WalletService::convert` + `ExchangeRateService`) had ZERO tests — rule-5 arena unlocked (ADR-004) | — | New `tests/Feature/Wallet/WalletConversionTest.php` — both directions (buy/sell rate exact decimals, pinned rate=13000/spread=2 → buy=12870/sell=13130), overdraft rejection, IDOR guard, double-sided ledger reconciliation | `b2a4c26` |

## Rate math — verified SOUND (untouched)
`ExchangeRate:37-44` buy=rate*(1-spread/200), sell=rate*(1+spread/200) → sell>buy. `convert:274-284` usd→syp pays customer the low buy rate, syp→usd charges the high sell rate — customer always worse side, platform keeps spread, round(6), decimal:8, no reverse-round in customer's favor. IDOR guard (`:251`), TOCTOU balance re-check under lock (`:287`), double-sided ledger (`:314`).

## Refuted preflags
- 3 transfer Dio repos (transfer/payment_request/contacts) all use `ApiException.fromDioError` — scanner "swallowed status" = false positives.
- ExchangeRate admin update validates `rate`+`spread` explicitly; buy/sell are derived accessors (no mass-assignment); model has only `spread` (no `margin` field → no conflation).

## Rule scorecard (post-fix)
1 (422) ✅ Convert/TransferRequest FormRequests · 2 (ApiException) ✅ all money repos (🟡 NFC empty-catch, non-network) · 3 (/admin 503) ✅ · 4 (unique/race) ✅ **now deterministic lock order** · 5 (money math) ✅ spread correct, no margin conflation · 6 (contract) ✅ · 7 (Tier-A ≥90%) ✅ P2P + FX convert now both tested.

## Verification
`php artisan test tests/Feature/Wallet/P2PTransferTest.php tests/Feature/Wallet/WalletConversionTest.php tests/Feature/Transfer` = **24 passed / 61 assertions**. `php -l` clean. Tree clean.

## Backlog
- 🟡 E-3: NFC empty catches (nfc_hce:64/reader:39/writer:49) — best-effort hardware, should `debugPrint` not swallow.
- ⚪ NEW (pre-existing, unrelated): `WalletInputValidationTest` has 4 failing cases against `/api/v1/wallets/{id}/deposit` — a **dead/404 route** on `master` (not caused by this work). Separate ticket: either wire the route or remove the test.

## Verdict
① sound (post-fix) · ② sound · ③ sound · ④ sound. The deadlock mine is defused (deterministic ordered locking) and the FX engine now has a regression net. No fund-loss existed; both 🟠 were Gate-6/7 blockers, now cleared.
