# Carda Wallet — QA Findings Log

Aggressive reviewer mode. One finding = one defect, with evidence and the fix. No praise, no padding.

Severity: 🔴 blocker · 🟠 high · 🟡 medium · 🔵 low

---

## F-001 🔴 KYC Levels admin page unreachable — route ordering — **FIXED**
- **Where:** `backend/routes/web.php`, `kyc` group.
- **Symptom:** `GET /admin/kyc/levels` → 404.
- **Cause:** `Route::get('/{kyc}', 'show')` was declared *before* `Route::get('/levels', 'levels')`.
  Laravel matches top-down, so `levels` bound as the `{kyc}` param and the show path 404'd.
- **Fix:** moved the static `/levels` routes above the `/{kyc}` wildcard.
- **Verified:** `php artisan route:list` now resolves `admin/kyc/levels` before `admin/kyc/{kyc}`.
- **Caught by:** `smoke-admin-kyc-levels`.

## F-002 🔴 KYC Levels Blade view missing — page 500s — **FIXED**
- **Where:** `Admin\KycController@levels` → `return view('admin.kyc.levels', ...)`.
- **Symptom:** after F-001, `GET /admin/kyc/levels` → 500 `View [admin.kyc.levels] not found`.
- **Cause:** controller + routes + `KycLevel` model + 3 seeded rows all existed, but
  `resources/views/admin/kyc/levels.blade.php` did not (wiped/never-built).
- **Fix:** built `resources/views/admin/kyc/levels.blade.php` on the admin layout (Light-Minimal,
  burgundy `#6E1B2D`, Alpine, `card/input/btn/badge` tokens). Full CRUD: level cards with limits +
  permissions + requirements, inline edit form (PUT), create panel (POST), delete (DELETE) — wired to
  the existing `levels.store/update/destroy` routes.
- **Verified:** `kyc-levels-render/create/edit/delete` all green via real UI (with video).
- **Caught by:** `smoke-admin-kyc-levels`, deepened by suite `admin-kyc`.

## F-004 🔴 Create/Update KYC level 500s when description left blank — **FIXED**
- **Where:** `Admin\KycController@storeLevel` / `@updateLevel`.
- **Symptom:** `POST /admin/kyc/levels` → 500 `SQLSTATE[23000]: NOT NULL constraint failed:
  kyc_levels.description`.
- **Cause:** `description` (and `requirements`) are **NOT NULL** columns, but both methods validate
  them as `nullable`. Laravel's `ConvertEmptyStringsToNull` middleware turns a blank textarea into
  `null` → insert violates the constraint. Any admin creating a level without typing a description
  (both languages) hits a hard 500.
- **Fix:** coalesce in both methods — `description ?? ''` and `requirements` filtered to a real array.
  No migration (schema unchanged, NOT NULL preserved).
- **Verified:** UI create/edit now persist; 596 backend tests pass.
- **Caught by:** `admin-kyc/kyc-levels-create` (5xx capture).

## F-005 🟡 CSP blocks the Cairo web font on every admin page — **OPEN**
- **Where:** global Content-Security-Policy header vs. `layouts/admin` Google Fonts `<link>`.
- **Symptom (console):** `Loading the stylesheet 'https://fonts.googleapis.com/css2?family=Cairo...'
  violates ... style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net`.
- **Impact:** Cairo never loads; Arabic UI silently falls back to a system font — typography/brand
  regression on **all** admin pages. Functionality unaffected.
- **Recommended fix (pick one):** (a) self-host Cairo like Material Icons already are, or
  (b) add `https://fonts.googleapis.com` to `style-src` and `https://fonts.gstatic.com` to `font-src`.
  Prefer (a) for offline parity. **Not auto-fixed — touches global security header.**
- **Caught by:** console capture in `admin-kyc` scenarios.

## F-003 🟠 Fee::getByCode caches Eloquent model → 500 on cache hit — **FIXED**
- **Where:** `backend/app/Models/Fee.php:187` `getByCode()`.
- **Symptom (from log):** `App\Models\Fee::getByCode(): Return value must be of type ?App\Models\Fee,
  __PHP_Incomplete_Class returned at Fee.php:193`.
- **Cause:** `cache()->remember('fee:...', ..., fn() => ...->first())` cached a full Fee model. With
  `CACHE_STORE=database` the model is serialized; on read it deserializes as `__PHP_Incomplete_Class`,
  violating the `?self` return type → TypeError → 500. Same time-bomb fixed earlier in 4 other models;
  **Fee was missed.** Latent on every fee lookup (transfers, deposits, withdrawals) after a cache fill.
- **Fix:** cache raw `getAttributes()` and rehydrate via `(new self())->setRawAttributes($attrs, true)`.
- **Verified:** populate in process A, read in fresh process B under `database` driver → returns real
  `App\Models\Fee` with casts intact (`is_active=true`); no `__PHP_Incomplete_Class`. 596 backend tests pass.
- **Follow-up:** audit any remaining `cache()->remember(... ->first()/->get())` over Eloquent models.

## F-006 🔵 Transfer logs a TypeError every time the sender has no FCM token — **OPEN**
- **Where:** `app/Services/TransferService.php:396`.
- **Symptom (log):** `Transfer sender FCM failed: FCMService::send(): Argument #1 ($token) must be of
  type string, null given`.
- **Cause:** `$sender->fcm_token` is null for users with no registered device; `FCMService::send()`
  type-hints `string $token`, so it throws. It's caught and logged, so the transfer still succeeds —
  but every such transfer emits a spurious ERROR that masks genuine FCM failures in monitoring.
- **Recommended fix:** guard `if ($sender->fcm_token) { ...send... }`, or widen `send(?string $token)`
  and early-return on null. Low risk; reduces false-positive error noise.
- **Caught by:** log review during the api-authz run (not yet a dedicated scenario; E2E transfer suite will cover).

## F-008 🟡 Admin transaction-reverse: JSON on success, 302 redirect on validation error — **OPEN**
- **Where:** `Admin\TransactionController@reverse` (web route `POST /admin/transactions/{id}/reverse`).
- **Behavior (verified by curl with `Accept: application/json` + `X-Requested-With`):**
  - valid reason → `200 {success:true, reversal_reference:...}` (JSON).
  - empty / <3-char reason → **302 redirect** (HTML validation-redirect), not `422` JSON.
- **Why it matters:** the success path is a JSON/AJAX contract, but the validation-error path falls back
  to a web redirect. An AJAX caller gets an opaque redirect instead of structured field errors — the UI
  can't surface "reason too short". Inconsistent contract on a sensitive money-reversal action.
- **Note:** the underlying obs-583 *type error* on empty reason is GONE — the `reason` (`required|min:3`)
  validation now rejects cleanly (no 500). This finding is only the success/error format mismatch.
- **Recommended fix:** force JSON error rendering for this endpoint (e.g. `$request->wantsJson()` guard
  returning `response()->json($errors, 422)`, or move it under an API/JSON route group).
- **Caught by:** `admin-deep/admin-tx-reverse-validation`.

---

## Verified secure (no findings) ✅
Suite `api-authz` — 7/7. The backend enforces, at the API layer:
- **Privilege boundary:** ahmad (normal user) is 401/403 on `/admin/wallets`, `/admin/transactions`,
  `/admin/card-inventory`, `/admin/wallets/{id}/freeze` — no privilege escalation.
- **IDOR:** ahmad cannot read (`/wallets/{saraId}` + `/balance`/`/transactions`/`/stats`) or write
  (`/withdraw`, `/deposit`) sara's wallet; positive control (own wallet → 200) confirms the test bites.
- **Authentication:** `/auth/me`, `/wallets`, `/transactions`, `/transfer`, `/cards`, `/gold/wallet`
  all 401 without a token.
- **Money invariants:** transfer rejects negative (422), zero, self, and over-balance amounts;
  gold-buy / fx-convert / fee-calc reject non-positive amounts.

---

## Observations (product/ledger — confirm intent, not defects)

### O-1 ⚪ Transfer cashback is minted with no offsetting treasury debit
- **Where:** `TransferService::creditCashback()` (line ~230), called on every P2P transfer.
- **Behavior (verified):** transfer of 137.50 USD produced 3 ledger rows — `transfer_out -137.50`,
  `transfer_in +137.50` (conserved), and `reward +1.38` (1% cashback) credited straight into the
  **sender's** wallet. Net effect: platform liabilities grow by the cashback each transfer, with no
  matching debit from a rewards/treasury account.
- **Why it matters:** ledger integrity / unit economics — cashback is effectively minted. Fine if it's
  a funded promo, but there's no treasury counter-entry to reconcile against.
- **Action:** confirm with product/finance whether cashback should debit a funded rewards pool.
- **Caught by:** `e2e-transfer/e2e-transfer-money` (this is why a naive "money conserved" check fails —
  it's by-design cashback, NOT a leak; the suite now asserts the cashback-aware invariant).

---

### Open items
- F-002 — build the missing KYC Levels view (awaiting go-ahead).
- Sweep for other model-caching time-bombs beyond the 5 known (SystemSetting, PageMeta, ServiceConfig, NotificationChannel, Fee).
