# SAKK Swarm Audit — card_wallet

> 193-inspector parallel swarm (sonnet inspect → opus adversarial verify → synthesis). Date: 2026-06-24.
> Scope: Laravel backend + Next.js web + Flutter mobile + deploy configs.
> **Verify status: partial.** 106 of ~270 critical/high findings reached adversarial verification before the run was stopped (to cap token cost). Verdicts: **42 real**, **17 false-positive (removed below)**, **3 uncertain**. Items marked `•unverified` are inspector-reported only — triage before acting. Re-running full verify was declined as too token-expensive.

## ⚡ Grep ground-truth corrections (zero-token static verification)

After the swarm, a free `ripgrep` pass over the backend cross-checked the loudest themes against actual code. The sonnet inspectors over-flagged heavily because each saw a single file with no cross-file context (couldn't see migrations, model casts, `Crypt`/`Hash` calls, route middleware, or the `verifySignature` methods). **Read this before triaging the lists below.**

### ✅ CONFIRMED REAL by grep (fix these)
| Finding | Proof | Severity |
|---------|-------|----------|
| **Sanctum tokens never expire** | `config/sanctum.php:53` → `'expiration' => null` | High |
| **Full PAN + CVV returned in API response** | `CardService.php:494-495` `getCardDetails()` returns `card_number` + `cvv` in cleartext (storage is encrypted, but decrypted to client = PCI-DSS) | High |
| **WalletService deposit/withdraw race** | `WalletService.php:33,68` wrap in `DB::transaction` but **no `lockForUpdate()`** → insufficient-balance check is TOCTOU | Medium–High |
| **Float casts on money (narrow)** | `Merchant.php:60-62`, `Agent.php:53-55` cast `balance`/`total_earned`/`commission_rate`/min-max to `'float'` — real but only **2 models, 6 fields** (NOT the 127 the swarm claimed) | Medium |
| **`$guarded=[]` on sensitive models** | 11 models still open; sensitive: `KycDocument`, `KycVerification`, `KycLevel` | Medium |

### ❌ REFUTED by grep (ignore — swarm noise)
| Swarm theme | Reality |
|-------------|---------|
| Float/double money in DB (127 hits) | **All 92 money columns are `decimal`** — 0 float/double in migrations |
| Webhook signature missing (11 hits) | **Both** webhook controllers verify (`verifySignature`/`verifyWebhookSignature` → 401) |
| PIN stored plaintext | `PinService.php:19,27` → `Hash::make` / `Hash::check` |
| PAN/CVV stored plaintext | `CardService.php:278-280` → `Crypt::encryptString` + `card_number_hash` |
| Race conditions everywhere (90 hits) | 14 `lockForUpdate` + 29 `DB::transaction`; Transfer/Card/Stripe/Referral/Savings already locked. Real gap = **only WalletService** (above) |
| Mass assignment on Wallet/User/Transaction/VirtualCard | **Already fixed** (`SEC-002` comment, `$guarded` removed, explicit `$fillable`) |
| Login/OTP brute-force, no rate limit | `throttle:auth` / `throttle:otp` / `throttle:admin` present in `routes/api.php` |
| CardController IDOR / no ownership check | `$card->user_id !== $request->user()->id` guard on **all 10+ card methods** |

**Takeaway:** of the swarm's 12 "systemic patterns", grep confirmed ~3 real (token expiry, PAN exposure, WalletService lock) and refuted ~7 outright. The 569 `•unverified` items below skew heavily toward the same single-file blind spots — prioritize the ✅ table, then the 42 opus-verified-real items, and treat the rest as leads.

---

## Executive summary

- **Units inspected:** 193 (49 controllers · 44 models · 18 services · 18 requests · 7 middleware · 54 migrations · routes · frontend · mobile · deploy).
- **Findings kept (false-positives removed):** 614  →  Critical **83** · High **235** · Medium **234** · Low **62**.
- **Adversarially confirmed real:** 42  ·  **Removed as false-positive:** 17  ·  **Uncertain:** 3.
- **By category:** money 138 · auth 96 · validation 83 · data-exposure 77 · logic 75 · kyc 38 · idor 34 · idempotency 25 · config 19 · quality 9 · injection 9 · webhook 7 · crypto 4

### Top systemic patterns (fix the root, not each instance)

| # | Pattern | Hits | C/H/M/L |
|---|---------|-----:|---------|
| 1 | Float/double used for money | 124 | 22/49/47/6 |
| 2 | Non-atomic balance / race / TOCTOU | 86 | 31/31/21/3 |
| 3 | Weak input validation | 66 | 10/20/24/12 |
| 4 | Sensitive data exposure | 57 | 2/19/28/8 |
| 5 | Mass assignment (fillable/guarded) | 52 | 10/33/6/3 |
| 6 | Missing idempotency / replay | 36 | 7/22/6/1 |
| 7 | Ownership check / IDOR | 35 | 7/18/8/2 |
| 8 | Plaintext sensitive storage | 33 | 8/16/9/0 |
| 9 | Auth bypass / token expiry / 2FA | 28 | 9/13/6/0 |
| 10 | Brute-force (no rate limit) | 24 | 0/14/8/2 |
| 11 | KYC bypass / auto-approval | 13 | 6/5/1/1 |
| 12 | Webhook signature / verify | 11 | 3/6/2/0 |

### Must-fix first
1. **Money as float/double** → integer minor-units or decimal/BCMath (wallet, card, gold, savings, fees).
2. **Non-atomic balance mutations** → `DB::transaction` + `lockForUpdate()` on every debit/credit; current check-then-write races enable double-spend.
3. **Mass assignment open** → explicit `$fillable`; never `$guarded=[]`. (`user_id`,`balance`,`kyc_status`,`is_admin`,`api_secret`).
4. **Webhooks unsigned/replayable** → verify provider signature + timestamp + idempotency key before crediting.
5. **No idempotency** on deposit/withdraw/charge/capture → duplicate = double money.
6. **Auth gaps** → Sanctum tokens never expire; 2FA/biometric bypass; PIN/OTP no brute-force lockout; admin actions missing role/ownership checks.
7. **KYC auto-approval + plaintext PIN/PAN/PII** → regulatory + breach risk.

## Critical (83 kept, 82 deduped)

### ✅ verified-real · [auth] Impersonation token grants full wildcard abilities ('*') with no audit trail linkage
- **Evidence:** $token = $user->createToken('admin-impersonation', ['*'])->plainTextToken;
- **Verifier:** Two of the three claims hold. CONFIRMED: line 360 `$user->createToken('admin-impersonation', ['*'])` mints a token with full wildcard abilities and no expiry, giving the holder complete account access as the impersonated user. CONFIRMED audit gap: every other sensitive action in AdminController.php 
- **Fix:** Issue a scoped, time-limited token (e.g. ability list restricted to read-only or specific ops). Log the impersonation event via ActivityLog immediately after token creation, including which admin initiated it and for which user. Also verify that the policy check at line 353 is not a no-op when $admin is null (the guard wraps in `if ($admin && ...)`, meaning an unauthenticated caller that somehow reaches this method would skip the policy and fall through to token creation).
- **Location(s):** `backend/app/Http/Controllers/API/AdminController.php:360`

### ✅ verified-real · [auth] PIN verification bypass — conditional check allows skipping PIN entirely
- **Evidence:** if ($request->has('pin') && !$user->verifyPin($request->pin)) — PIN is only verified if present in the request. A caller that omits 'pin' AND omits 'biometric_token' (or sends an empty biometric_token) passes validation (required_without means pin is required only if biometric_token is absent) but the actual verifyPin call is skipped if 'pin' key is simply not sent. Same pattern at line 196-198 in sell().
- **Verifier:** Confirmed real and exploitable. In GoldSavingsController::buy() (line 106) and sell() (line 196) the PIN is only checked when `$request->has('pin')` is true, and `biometric_token` is never verified anywhere in this controller. The validation `'pin' => 'required_without:biometric_token|string'` makes
- **Fix:** Always verify the authentication factor. After validation passes, unconditionally call verifyPin or biometric verification depending on which was supplied. Never gate the verify call on request->has().
- **Location(s):** `backend/app/Http/Controllers/API/GoldSavingsController.php:106-108`

### ✅ verified-real · [data-exposure] Password reset token returned in API response (sensitive data exposure)
- **Evidence:** The `forgotPassword` method returns the raw `reset_token` and `reset_url` (containing the token) directly in the JSON response body. A comment says "For development, return the token directly" but this code is in the production controller with no environment guard. Any attacker who can intercept the API response (e.g. via a MITM, logging middleware, or an XSS that reads the response) gets a valid password-reset token without needing access to the victim's email inbox.
- **Verifier:** Confirmed real and worse than described. In AuthController.php lines 414-421, forgotPassword returns the raw reset_token and a reset_url containing it directly in the JSON response. The route (routes/api.php line 44, /v1/auth/forgot-password) is public and has no authentication, and there is no app(
- **Fix:** Remove `reset_token` and `reset_url` from the response entirely. Send the token only via email. Gate any debug output behind `app()->environment('local')` at minimum.
- **Location(s):** `backend/app/Http/Controllers/API/AuthController.php:414-421`

### ✅ verified-real · [money] Balance check before DB transaction — TOCTOU race condition on buy ×2
- **Evidence:** $usdWallet = Wallet::where('user_id', $user->id)->where('currency', 'USD')->first(); if (!$usdWallet || $usdWallet->available_balance < $grandTotal) { ... } — balance check happens OUTSIDE the DB::transaction block. A concurrent request can pass this check simultaneously, and both proceed to debit, draining the wallet below zero.
- **Verifier:** Real TOCTOU race on the USD wallet debit. In GoldSavingsController::buy() the wallet is loaded at line 101 with Wallet::where(...)->first() (no lock) and the balance is checked at line 102 OUTSIDE the DB::transaction that begins at line 110; the same un-locked object is then passed into the closure 
- **Fix:** Move the balance check inside the DB::transaction with a pessimistic lock: Wallet::where(...)->lockForUpdate()->first(), then re-check balance after acquiring the lock.
- **Location(s):** `backend/app/Http/Controllers/API/GoldSavingsController.php:101-104`, `backend/app/Http/Controllers/API/GoldSavingsController.php:187-189`

### ✅ verified-real · [money] Unload response always returns success regardless of service result
- **Evidence:** After calling `$this->cardService->unloadCard(...)`, the response is unconditionally returned with `success: true` and HTTP 200 — `$result['success']` is never checked. If the service fails (e.g., DB error, Stripe error), the caller is told the operation succeeded.
- **Verifier:** Verified in CardController.php: unload() (lines 276-286) calls cardService->unloadCard() and unconditionally returns success:true with HTTP 200, never checking $result['success'] — in clear contrast to load() which guards at lines 231-236. The service (CardService.php unloadCard, lines 418-484) real
- **Fix:** Check `$result['success']` before returning the success response, mirroring the pattern used in `load()` at line 231-236.
- **Location(s):** `backend/app/Http/Controllers/API/CardController.php:278-286`

### ✅ verified-real · [money] TOCTOU race condition on card unload — balance check not atomic
- **Evidence:** Balance is read at line 269 (`$request->amount > $card->balance`) outside any DB transaction or row-level lock. A second concurrent request can pass the same check before either deduction commits, allowing double-spend and negative balance.
- **Verifier:** Confirmed real TOCTOU/double-spend defect, though the claim's pinpoint at CardController.php:269 is only the surface. That controller check ($request->amount > $card->balance) runs outside any transaction/lock, but the authoritative balance guard lives in CardService::unloadCard (app/Services/CardSe
- **Fix:** Perform the balance check and deduction inside a DB transaction with a pessimistic lock (`VirtualCard::lockForUpdate()->find($card->id)`) so the read and write are atomic.
- **Location(s):** `backend/app/Http/Controllers/API/CardController.php:269-276`

### ✅ verified-real · [money] Card cancel silently loses funds if balance transfer fails
- **Evidence:** `$this->cardService->unloadCard(...)` result is never checked at line 350. If the transfer fails, execution continues to `$card->cancel()` at line 353, permanently cancelling the card while the balance remains unrecovered — funds are lost.
- **Verifier:** Confirmed real defect. In CardController::cancel() (lines 348-353), unloadCard() returns a status array (['success'=>bool,...]) but the result is discarded, and execution unconditionally falls through to $card->cancel() which permanently sets status CANCELLED/is_active=false. unloadCard() (CardServi
- **Fix:** Check the result of `unloadCard()`; if it fails, abort and return an error before calling `$card->cancel()`.
- **Location(s):** `backend/app/Http/Controllers/API/CardController.php:349-353`

### ✅ verified-real · [money] Double-spend race condition on pay(): transfer executes before status is locked
- **Evidence:** pay() checks isPending() (line 256), then calls transferService->transfer() (line 271), then updates status to 'paid' (line 282). There is no DB-level lock (SELECT ... FOR UPDATE) between the check and the transfer. Two concurrent requests for the same PaymentRequest UUID will both pass the isPending() check and both execute the transfer, charging the payer twice and paying the requester twice.
- **Verifier:** Confirmed real and exploitable. In pay() (PaymentRequestController.php:252-296) the row is read via route-model binding, isPending() is checked at line 256, transfer() runs at line 271, and status is flipped to 'paid' at line 282 — with no lock on the PaymentRequest row, no DB transaction wrapping t
- **Fix:** Wrap the entire pay() flow in a DB transaction with a pessimistic lock: DB::transaction(fn() => PaymentRequest::lockForUpdate()->findOrFail($id) then re-check isPending(), transfer, and update status atomically).
- **Location(s):** `backend/app/Http/Controllers/API/PaymentRequestController.php:252-296`

### ✅ verified-real · [money] Double-spend race condition on accept(): same issue as pay()
- **Evidence:** accept() checks isPending() at line 124 then calls transfer at line 132, then marks 'paid' at line 143 — no DB lock. Concurrent accept calls from the same requestee (or network retry) will both pass the check and double-charge.
- **Verifier:** Confirmed real after tracing accept() (PaymentRequestController.php:117-168) into TransferService::transfer() (TransferService.php:86-226) and the PaymentRequest model. accept() reads isPending() at line 124 on an UNLOCKED PaymentRequest row, executes the transfer at line 132, then marks 'paid' at l
- **Fix:** Same fix as pay(): DB::transaction with lockForUpdate() on the PaymentRequest row before re-checking status and executing the transfer.
- **Location(s):** `backend/app/Http/Controllers/API/PaymentRequestController.php:117-168`

### ✅ verified-real · [money] KYC daily/monthly limit check is outside the DB transaction (TOCTOU race condition)
- **Evidence:** `assertWithinKycLimits($sender, $amount, $currency)` is called at line 103, before `DB::transaction(...)` starts at line 107. The daily/monthly sums are read outside the lock, so two concurrent transfers from the same user can both pass the limit check simultaneously and both execute, allowing the user to exceed KYC limits by a factor equal to concurrency.
- **Verifier:** Confirmed real TOCTOU race. In TransferService::transfer(), assertWithinKycLimits($sender,$amount,$currency) is called at line 103, fully before DB::transaction() opens at line 107. Inside that method the daily and monthly spent totals (lines 296-300 and 308-312) are computed as plain SUM queries ov
- **Fix:** Move `assertWithinKycLimits` inside the `DB::transaction` closure, after the `lockForUpdate` on the sender wallet, so the limit check and the debit are serialized under the same lock.
- **Location(s):** `backend/app/Services/TransferService.php:103-107`

### ✅ verified-real · [money] Non-atomic withdrawal: external transfer before local debit with no DB transaction
- **Evidence:** $result = $this->ccpayment->processWalletWithdraw(...); // funds leave the platform
$wallet->debit(floatval($validated['amount'])); // local balance decremented after
- **Verifier:** Confirmed in CCPaymentController::withdraw (lines 234-245): the external crypto transfer $this->ccpayment->processWalletWithdraw(...) executes first and only afterward is $wallet->debit() called, with no DB::transaction wrapping the two and no pessimistic row lock. Reading Wallet::debit (Wallet.php:
- **Fix:** Wrap both the external call submission and the local debit inside a DB::transaction with pessimistic locking. If the debit fails after the external withdrawal is submitted, money is sent but the balance is not reduced. Alternatively, create a pending transaction record first, submit externally, then confirm — all inside a compensating saga or at minimum a DB transaction with rollback.
- **Location(s):** `backend/app/Http/Controllers/API/CCPaymentController.php:234-245`

### ✅ verified-real · [money] TOCTOU race condition on balance check allows double-spend
- **Evidence:** if ($wallet->balance < floatval($validated['amount'])) { ... }
// No lockForUpdate() — concurrent requests both pass this check before either debits
- **Verifier:** Confirmed real and exploitable. In CCPaymentController::withdraw (lines 206-245) the wallet is loaded with a plain Wallet::where(...)->first() (no lockForUpdate), the balance is checked at line 227, the external CCPayment withdrawal API is called at line 235 (irreversible crypto send), and only afte
- **Fix:** Acquire a pessimistic row lock before the balance check: Wallet::where('id', ...)->lockForUpdate()->first() inside a DB::transaction. This prevents two concurrent withdrawals from both passing the balance check on the same wallet.
- **Location(s):** `backend/app/Http/Controllers/API/CCPaymentController.php:227-245`

### ✅ verified-real · [money] Race condition: balance check and debit are not atomic (double-spend possible)
- **Evidence:** In `withdraw()`, `canSpend()` is called at line 200 to check the available balance, then `walletService->withdraw()` is called at line 207. Between these two calls there is no row-level lock. Two concurrent withdrawal requests can both pass `canSpend()` with the same balance, both enter `walletService->withdraw()`, and both call `wallet->debit()`. The `debit()` method in Wallet.php operates on the in-memory `$this->available_balance` loaded before the transaction opened, so a second concurrent read sees the pre-debit value. No `lockForUpdate()` is acquired on the wallet row before the check inside `WalletService::withdraw()`.
- **Verifier:** Real, exploitable double-spend. WalletService::withdraw() (and convert()) open a DB::transaction but operate on the $wallet Eloquent instance bound by the controller route-model binding *before* the transaction — they never call lockForUpdate() to re-read the row under a lock. Wallet::debit() (Walle
- **Fix:** Inside `WalletService::withdraw()` (and `convert()`), reload the wallet with `Wallet::lockForUpdate()->find($wallet->id)` at the start of the DB::transaction closure before calling `canSpend()` or `debit()`. Remove the pre-transaction `canSpend()` call in the controller, or repeat it inside the lock.
- **Location(s):** `backend/app/Http/Controllers/API/WalletController.php:200-212`

### ✅ verified-real · [money] Same race condition in convert(): canSpend checked outside the DB transaction
- **Evidence:** `canSpend($request->amount)` is called at line 275 before `walletService->convert()` opens its DB transaction. Inside `WalletService::convert()` there is no `lockForUpdate` on either `$fromWallet` or `$toWallet`, so concurrent convert calls on the same wallet can both observe sufficient balance and both proceed to debit, resulting in a negative balance.
- **Verifier:** Confirmed real, not refuted. WalletController::convert() pre-checks fromWallet->canSpend() at line 275 outside any transaction (against an in-memory model hydrated at line 265). WalletService::convert() (app/Services/WalletService.php:182) opens DB::transaction but contains NO lockForUpdate on $from
- **Fix:** Lock both `$fromWallet` and `$toWallet` with `lockForUpdate()` at the very start of the `DB::transaction` closure in `WalletService::convert()`, then re-check `canSpend` (or check `available_balance` directly) after acquiring the lock.
- **Location(s):** `backend/app/Http/Controllers/API/WalletController.php:275-288`

### •unverified · [auth] Password reset token stored in plaintext — account takeover via DB read
- **Evidence:** $table->string('token'); — no hashing, token stored verbatim. Anyone with DB read access (compromised replica, SQL injection, leaked backup) can use any reset token to take over any account.
- **Fix:** Store only a SHA-256 hash of the token (Laravel's built-in password broker does this by default). Compare with hash_equals(hash('sha256', $plaintext), $stored).
- **Location(s):** `backend/database/migrations/2026_06_13_125948_create_password_resets_table.php:13`

### •unverified · [auth] Default PIN code hardcoded to '123456' for every new user
- **Evidence:** 'pin_code' => Hash::make('123456'),
- **Fix:** Do not set a PIN at registration time. Require the user to explicitly create their own PIN in a separate, authenticated step before any PIN-gated action (transfers, card operations) is allowed. Never supply a predictable default.
- **Location(s):** `backend/app/Services/AuthService.php:20`

### •unverified · [auth] Authentication bypassed — login redirects without API call
- **Evidence:** handleLogin is async but only calls router.push('/merchant/dashboard') — the TODO comment confirms no auth API is connected. Any user can navigate to the merchant dashboard without credentials.
- **Fix:** Implement the actual authentication API call in handleLogin; validate credentials server-side, receive a session token, store it securely, and only redirect on success. Guard the dashboard route with a server-side auth check.
- **Location(s):** `frontend/src/app/merchant/login/page.tsx:10-13`

### •unverified · [auth] Authentication bypassed — login navigates to dashboard without any API call or credential verification
- **Evidence:** handleLogin calls router.push('/agent/dashboard') immediately with a TODO comment and no API call, no token issuance, and no credential check. Any user who submits the login form is silently redirected to the agent dashboard.
- **Fix:** Implement the actual authentication API call, verify credentials server-side, store the returned token/session securely, and only redirect on a successful response. Remove the stub router.push.
- **Location(s):** `frontend/src/app/agent/login/page.tsx:10-13`

### •unverified · [auth] Authentication bypass: login handler navigates to dashboard without any API call or credential verification
- **Evidence:** const handleLogin = async () => {
    // TODO: Connect to auth API
    router.push("/user/dashboard")
  }
- **Fix:** Implement the actual authentication API call. Submit credentials to the backend auth endpoint, validate the response, store the returned token/session securely, and only navigate to the dashboard on a successful authenticated response. Any user who triggers onLogin will be unconditionally redirected to the dashboard without credentials being checked.
- **Location(s):** `frontend/src/app/user/login/page.tsx:10-13`

### •unverified · [auth] Mass assignment of security-sensitive fields: kyc_status, kyc_level, kyc_data, status, two_factor_enabled
- **Evidence:** Fields `kyc_status`, `kyc_level`, `kyc_data`, `kyc_verified_at`, `status`, `two_factor_enabled`, `email_verified_at`, `phone_verified_at` are all in `$fillable`. Any controller that passes user-supplied input to `$user->fill($request->all())` or `User::create($request->all())` allows a user to self-promote their KYC status to VERIFIED, disable 2FA, or set their own account status to ACTIVE.
- **Fix:** Remove `kyc_status`, `kyc_level`, `kyc_data`, `kyc_verified_at`, `status`, `two_factor_enabled`, `email_verified_at`, `phone_verified_at` from `$fillable`. These fields must only be set via explicit assignment (e.g. `$user->kyc_status = ...`) in trusted service classes, never from request data.
- **Location(s):** `backend/app/Models/User.php:34-40`

### •unverified · [auth] Device status defaults to 'approved' — bypasses pending/review flow
- **Evidence:** $table->string('status')->default('approved')->after('public_key'); // pending | approved | rejected
- **Fix:** Change the default to 'pending' so every newly linked device starts in a review state and cannot transact until explicitly approved. The comment itself says the expected lifecycle starts at 'pending'.
- **Location(s):** `backend/database/migrations/2026_06_19_140000_add_approval_to_devices_table.php:13`

### •unverified · [auth] Authentication bypass — login handler skips all credential verification
- **Evidence:** handleLogin() ignores any credentials submitted by AuthLoginForm and unconditionally redirects to /company/dashboard without calling any auth API, validating a token, or setting a session.
- **Fix:** Implement the auth API call inside handleLogin, verify the response contains a valid token/session before redirecting, and surface errors to the user on failure. Never redirect on login success without confirming server-side authentication.
- **Location(s):** `frontend/src/app/company/login/page.tsx:10-13`

### •unverified · [auth] Hardcoded biometric token — PIN/auth bypass on buy/sell
- **Evidence:** const token = 'biometric'; ... await repo.buy(karat: _karat, grams: grams, biometricToken: token);
- **Fix:** The biometric token must be a cryptographically unique, per-transaction value produced by the device's secure enclave after a successful biometric auth challenge (e.g., sign a server-issued nonce). A static string 'biometric' sent to the backend means any client — or a network-level attacker who replays this value — bypasses the auth requirement entirely. The backend must verify the token is a valid signed nonce, not just check that the field is non-empty.
- **Location(s):** `mobile/lib/features/gold/presentation/pages/gold_page.dart:500`

### •unverified · [auth] Plaintext password stored in secure storage via Remember Me
- **Evidence:** await _storage.write(key: 'remember_email', value: email);
await _storage.write(key: 'remember_password', value: password);
- **Fix:** Never persist passwords, even in secure storage. Store only a long-lived refresh token from the server. Implement a server-side 'remember me' token flow and exchange it for an access token on app launch.
- **Location(s):** `mobile/lib/features/auth/data/repositories/auth_repository.dart:94-95`

### •unverified · [auth] balance is a fillable field — arbitrary balance injection via mass assignment
- **Evidence:** `balance` is listed in $fillable. Any controller that calls VirtualCard::create($request->all()) or $card->fill($request->all()) will allow an attacker to set an arbitrary card balance via the request payload.
- **Fix:** Remove 'balance' from $fillable. Balance should only be mutated through dedicated methods (loadFunds, unload, spend, refund) that enforce business rules.
- **Location(s):** `backend/app/Models/VirtualCard.php:25`

### •unverified · [auth] api_key and api_secret mass-assignable — privilege escalation via HTTP input
- **Evidence:** Both 'api_key' and 'api_secret' appear in $fillable. If any controller passes request()->all() or fill($request->validated()) without explicitly excluding these fields, an attacker can set arbitrary API credentials on an existing merchant record.
- **Fix:** Remove 'api_key' and 'api_secret' from $fillable. Update them only through dedicated methods (regenerateApiKey) or direct column assignment, never through mass assignment.
- **Location(s):** `backend/app/Models/Merchant.php:35-36`

### •unverified · [auth] Any admin can promote any user to admin via is_admin field
- **Evidence:** 'is_admin' => ['sometimes', 'boolean'] — the rule allows any authenticated admin to set is_admin=true on any user account with no additional authorization check or super-admin gating.
- **Fix:** Remove is_admin from the updatable fields entirely, or gate it behind a stricter super-admin check separate from is_admin itself. Privilege escalation fields must never be self-service or admin-peer-service.
- **Location(s):** `backend/app/Http/Requests/Admin/UpdateUserRequest.php:25`

### •unverified · [auth] No authorization check — any authenticated user can access all transactions and PII
- **Evidence:** The class has no middleware() call, no policy check, no Gate::authorize(), and no role guard anywhere. All three actions (index, show, invoice) are reachable by any authenticated session that can resolve the route.
- **Fix:** Add middleware('role:admin') (or equivalent) in the constructor, or apply it at the route group level and verify it is actually enforced. Add a policy/Gate check per action for defense-in-depth.
- **Location(s):** `backend/app/Http/Controllers/Admin/TransactionController.php:10-88`

### •unverified · [auth] 2FA bypass when two_factor_enabled is false or secret is null
- **Evidence:** `verifyCode` returns `true` unconditionally when `two_factor_enabled` is false OR `two_factor_secret` is null. An attacker who manages to set `two_factor_enabled = false` (e.g., via a race or mass-assignment), or whose secret was cleared while a session is active, passes 2FA without any code.
- **Fix:** Treat a missing/disabled 2FA state as a failure when 2FA is required by the calling flow, rather than silently returning true. The enforcement decision should sit at the caller, but the service should not return true for an unconfigured state.
- **Location(s):** `backend/app/Services/TwoFactorService.php:99-101`

### •unverified · [config] Plaintext HTTP base URL hardcoded to local LAN IP — production HTTPS URL is commented out
- **Evidence:** static const String baseUrl = 'http://192.168.10.158:8000/api/v1'; // Local Network (Physical Device)
// static const String baseUrl = 'https://moccasin-otter-808407.hostingersite.com/api/v1'; // Production

The active baseUrl uses http:// (no TLS) pointing at a private LAN IP. Any release build ships with this value, so all API traffic — including auth tokens, PINs, KYC documents, card details, and money transfers — is sent in plaintext over the local network. The production HTTPS endpoint exists but is disabled.
- **Fix:** Swap the active line to the HTTPS production URL and remove or guard the LAN URL behind a debug/profile build flag (e.g. kDebugMode or a compile-time --dart-define). Also enforce certificate pinning and block cleartext traffic in AndroidManifest.xml (usesCleartextTraffic=false) and iOS ATS settings.
- **Location(s):** `mobile/lib/core/constants/api_constants.dart:7-8`

### •unverified · [crypto] PIN code stored in plaintext in users table
- **Evidence:** $table->string('pin_code', 6)->nullable(); // Transaction PIN — stored as plain string, no indication of hashing
- **Fix:** Transaction PINs must be hashed (bcrypt/Argon2) before storage, never stored as plaintext strings. Change the column comment to reflect hashing and enforce it at the model/service layer.
- **Location(s):** `backend/database/migrations/2026_06_13_000001_create_users_table.php:40`

### •unverified · [data-exposure] Missing ownership check in getCardDetails — full PAN, CVV, and expiry returned without verifying caller owns the card
- **Evidence:** `getCardDetails` returns raw `card_number`, `cvv`, `expiry_month`, `expiry_year` with zero ownership validation. If the controller resolves `VirtualCard` by ID without scoping to the authenticated user, full card credentials are exposed to any authenticated user.
- **Fix:** Accept the owning `User` as a parameter and assert `$card->user_id === $user->id` before returning sensitive fields. Also consider rate-limiting this endpoint.
- **Location(s):** `backend/app/Services/CardService.php:489-502`

### •unverified · [data-exposure] Card number and CVV stored in plaintext — PCI-DSS violation / sensitive data exposure
- **Evidence:** getDecryptedCardNumberAttribute() and getDecryptedCvvAttribute() both contain the comment 'In production, decrypt from encrypted storage' but simply return $this->card_number and $this->cvv directly. The fields are stored in plaintext in the database. $hidden only prevents JSON serialization, not DB reads.
- **Fix:** Encrypt card_number and cvv at rest using Laravel's encryption helpers or a HSM-backed vault before storing, and actually decrypt in these accessors.
- **Location(s):** `backend/app/Models/VirtualCard.php:318-328`

### •unverified · [idempotency] TOCTOU race condition: duplicate reward payout possible
- **Evidence:** The duplicate-reward guard (`ReferralReward::where('referred_id',...)->exists()`) at line 85 is checked OUTSIDE the DB transaction that begins at line 104. Two concurrent calls (e.g. simultaneous KYC webhook + deposit event) can both pass the exists() check before either inserts the ReferralReward row, then both proceed into the transaction and credit the referrer twice.
- **Fix:** Move the exists() check inside the DB::transaction block and use a SELECT ... FOR UPDATE on the referred_id or a unique DB constraint on referral_rewards.referred_id to make the guard atomic.
- **Location(s):** `backend/app/Services/ReferralService.php:85-104`

### •unverified · [idempotency] Deposit webhook credits wallet without idempotency guard — double-credit on replay
- **Evidence:** `handleDepositWebhook` checks no guard against processing the same `recordId` twice. `$transaction->update([status => COMPLETED])` runs even if the transaction is already COMPLETED, then `$wallet->credit(...)` runs again. A duplicate webhook (or replay) produces a double credit.
- **Fix:** Wrap the status-update and credit in a DB transaction with a row-level lock (`lockForUpdate`), and skip processing if `$transaction->status === TransactionStatus::COMPLETED`.
- **Location(s):** `backend/app/Services/CCPaymentService.php:419-437`

### •unverified · [idempotency] Withdrawal webhook refunds wallet without idempotency guard — double-refund on replay
- **Evidence:** Same pattern as above: `handleWithdrawWebhook` calls `$wallet->credit($transaction->amount)` whenever `newStatus === FAILED`, with no check that the transaction is not already in FAILED state. A duplicate failure webhook refunds the user twice.
- **Fix:** Check `$transaction->status === TransactionStatus::FAILED` before refunding; use a DB transaction with `lockForUpdate` on both the transaction and wallet rows.
- **Location(s):** `backend/app/Services/CCPaymentService.php:467-486`

### •unverified · [idempotency] No idempotency guard on deposit webhook — double-credit possible
- **Evidence:** handleDepositWebhook() does Transaction::where('reference', $referenceId)->first() and then unconditionally calls $wallet->credit($payload['amount']) whenever newStatus === COMPLETED. There is no check that the transaction was previously PENDING before crediting; a duplicate webhook (or the replay issue above) will call wallet->credit() again.
- **Fix:** Wrap the lookup and credit in a DB::transaction() with a pessimistic lock (lockForUpdate). Only credit if $transaction->status === PENDING before the update.
- **Location(s):** `backend/app/Services/CCPaymentService.php:407-436`

### •unverified · [idempotency] No idempotency guard on withdrawal webhook — double-refund possible
- **Evidence:** handleWithdrawWebhook() credits back $transaction->amount on 'failed' status without checking prior transaction state. A duplicate 'failed' webhook will call $wallet->credit($transaction->amount) a second time, doubling the refund.
- **Fix:** Same pattern: DB::transaction() + lockForUpdate() + guard on current status === PENDING before updating and refunding.
- **Location(s):** `backend/app/Services/CCPaymentService.php:455-486`

### •unverified · [idor] Missing ownership check in cancelCard — IDOR allows any authenticated user to cancel another user's card
- **Evidence:** `cancelCard(VirtualCard $card, Wallet $wallet)` performs no check that `$card->user_id === $wallet->user_id` or that either belongs to the authenticated user. Compare with `loadCard` which at least checks `$card->user_id !== $wallet->user_id`. An attacker can cancel any card by supplying their own wallet and a victim's card ID.
- **Fix:** Add `if ($card->user_id !== $wallet->user_id) return ['success' => false, 'error' => 'غير مصرح'];` at the top of `cancelCard`, matching the pattern used in `loadCard` and `unloadCard`.
- **Location(s):** `backend/app/Services/CardService.php:522-558`

### •unverified · [idor] Mass assignment allows user_id takeover on bank accounts
- **Evidence:** protected $fillable = ['user_id', 'bank_name', 'account_name', 'account_number_encrypted', 'account_number_last4', 'iban', 'swift_code', 'branch_code', 'status', 'is_default', 'verification_data']; — user_id is fillable
- **Fix:** Remove 'user_id' from $fillable. Set user_id explicitly in the controller from the authenticated session (e.g. auth()->id()), never from request input. Add it to $guarded or use $fillable without user_id.
- **Location(s):** `backend/app/Models/BankAccount.php:7`

### •unverified · [idor] No ownership check on toWallet in convert() — cross-user fund transfer possible
- **Evidence:** convert() accepts any Wallet $fromWallet and Wallet $toWallet without verifying that both belong to the authenticated user. A caller supplying a toWallet owned by another user will credit that user's wallet funded by the caller's fromWallet.
- **Fix:** Assert ownership before proceeding: if ($fromWallet->user_id !== $toWallet->user_id) { throw new \Exception('Wallet ownership mismatch'); }
- **Location(s):** `backend/app/Services/WalletService.php:182-233`

### •unverified · [idor] Mass-assignable `payer_id` and `transaction_id` allow tampering with payment ownership
- **Evidence:** 'payer_id' and 'transaction_id' are in $fillable. Attacker can link a payment request to a different user as payer or attach an arbitrary transaction record to the request.
- **Fix:** Remove 'payer_id' and 'transaction_id' from $fillable. Assign these only in trusted controller/service code after verification, never from raw request input.
- **Location(s):** `backend/app/Models/PaymentRequest.php:18-19`

### •unverified · [idor] authorize() only checks authentication, not card ownership (IDOR)
- **Evidence:** authorize() returns true for any authenticated user (`$this->user() !== null`). It does not verify that the card being unloaded belongs to the requesting user. Any authenticated user can trigger an unload against another user's card by supplying that card's ID.
- **Fix:** Resolve the target card from the route parameter and verify ownership: e.g. `$card = Card::findOrFail($this->route('card')); return $card->user_id === $this->user()->id;`
- **Location(s):** `backend/app/Http/Requests/Card/UnloadCardRequest.php:9-12`

### •unverified · [idor] Mass assignment of user_id and status allows privilege escalation and fraud
- **Evidence:** `user_id`, `status`, and `savings_goal_id` are all in `$fillable`. Any controller that does `SavingsTransaction::create($request->all())` lets an attacker supply an arbitrary `user_id` (creating transactions on another user's behalf) or set `status` directly to a terminal state (e.g. `completed`) without going through business logic.
- **Fix:** Remove `user_id` from `$fillable` and set it explicitly from the authenticated session (`$tx->user_id = auth()->id()`). Remove `status` from `$fillable` and manage state transitions through dedicated methods or service classes only.
- **Location(s):** `backend/app/Models/SavingsTransaction.php:11-20`

### •unverified · [idor] getCardDetails lacks ownership verification (IDOR)
- **Evidence:** getCardDetails(VirtualCard $card) retrieves full PAN and CVV from Stripe with no check that $card->user_id matches the authenticated user. Any caller that obtains a VirtualCard instance — e.g. via a controller that resolves card by ID without scoping to the current user — can retrieve another user's card secrets.
- **Fix:** Add an ownership assertion inside this method: if ($card->user_id !== auth()->id()) { return ['success' => false, 'error' => 'Unauthorized']; } — and ensure controllers also scope card lookups to the authenticated user.
- **Location(s):** `backend/app/Services/StripeIssuingService.php:314-348`

### •unverified · [idor] IDOR: wallet_id not validated against authenticated user ownership
- **Evidence:** The `wallet_id` rule only checks `exists:wallets,id` — it verifies the wallet exists in the database but never confirms it belongs to the authenticated user. Any authenticated user can supply any wallet_id and create a card against another user's wallet.
- **Fix:** Add a custom Rule or use `exists:wallets,id,user_id,{auth()->id()}` (e.g., `'exists:wallets,id,user_id,' . $this->user()->id`) to scope the existence check to the current user's wallets.
- **Location(s):** `backend/app/Http/Requests/Card/CreateCardRequest.php:17-22`

### •unverified · [injection] Path traversal in importCardsFromFile allows reading arbitrary files
- **Evidence:** `$filePath` is accepted directly and used in `file_exists($fullPath)` and `file_get_contents($fullPath)` without sanitisation. The fallback (line 227) uses `basename($filePath)` on the hardcoded path, but the primary path (line 223: `$fullPath = $filePath`) is used as-is, allowing `../../etc/passwd` style traversal to read any file accessible to the web process.
- **Fix:** Validate `$filePath` against an allowlist of safe base directories (e.g., `storage_path('card-imports/')`) and reject any path that resolves outside that directory using `realpath()`.
- **Location(s):** `backend/app/Services/CardService.php:222-228`

### •unverified · [injection] .env injection via unescaped DB credentials written to .env file
- **Evidence:** DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD are written directly into .env with no sanitization: `'DB_HOST=' . $validated['db_host']`. An attacker submitting `db_host=localhost\nAPP_KEY=attacker_key` injects arbitrary lines into .env, overwriting any key including APP_KEY, MAIL_PASSWORD, etc.
- **Fix:** Reject values containing newlines or `=` (validate with `regex:/^[^\n\r=]+$/`), or quote and escape each value before writing (wrap in double-quotes and escape embedded double-quotes and backslashes).
- **Location(s):** `backend/app/Http/Controllers/InstallerController.php:95-99`

### •unverified · [kyc] KYC status and reviewer fields are mass-assignable
- **Evidence:** Both `status` and `reviewed_by` are listed in `$fillable`. Combined with `$guarded = []`, any controller that does `KycVerification::create($request->all())` or `->update($request->all())` lets an attacker self-approve their KYC by submitting `status=approved` and `reviewed_by=<admin_id>`. This is a direct KYC bypass.
- **Fix:** Remove `status`, `reviewed_by`, and `reviewed_at` from `$fillable`. These fields must only be set through explicit model property assignment in trusted code paths (e.g., `$kyc->status = 'approved'; $kyc->reviewed_by = auth()->id();`), never from request data.
- **Location(s):** `backend/app/Models/KycVerification.php:10-22`

### •unverified · [kyc] KYC auto-approval on document submission bypasses verification intent
- **Evidence:** KycDocument::create sets `'status' => VerificationStatus::APPROVED->value` immediately on upload, and recordVerification() also creates a KycVerification row with APPROVED status. syncUserLevel() then runs and checks requirementMet(), which looks for APPROVED status — so the user is immediately promoted to the next KYC level without any admin review. The `reviewed_by = null` flag is purely cosmetic; the level upgrade is already granted.
- **Fix:** Document submissions must be created with PENDING status. syncUserLevel() or requirementMet() must exclude records where reviewed_by is null (i.e., not yet human-reviewed) from satisfying level requirements. Level upgrades for document-based requirements should only fire after an admin explicitly approves.
- **Location(s):** `backend/app/Services/KycService.php:457, 483, 511, 536`

### •unverified · [kyc] Agent KYC status defaults to 'approved' — KYC bypass on creation
- **Evidence:** $table->string('kyc_status', 20)->default('approved')->after('is_verified');
- **Fix:** Change the default to 'pending' (consistent with how merchants are seeded on line 67). Every agent added to the system will have kyc_status='approved' without ever submitting or passing a KYC review.
- **Location(s):** `backend/database/migrations/2026_06_20_400000_create_agent_documents_table.php:60`

### •unverified · [kyc] KYC status and verification flags are mass-assignable
- **Evidence:** `is_verified`, `kyc_status`, `kyc_approved_at`, `kyc_rejection_reason`, `is_active`, and `is_featured` are all listed in `$fillable`. Any controller that passes request input directly to `Agent::create()` or `$agent->update(request->all())` allows an unauthenticated or low-privilege caller to self-approve KYC, set their own `kyc_status` to `approved`, and mark themselves as `is_verified` without going through the approval workflow.
- **Fix:** Remove `is_verified`, `kyc_status`, `kyc_submitted_at`, `kyc_approved_at`, `kyc_rejection_reason`, `is_active`, `is_featured` from `$fillable`. Update these fields only through dedicated admin methods that enforce role checks, never through bulk assignment.
- **Location(s):** `backend/app/Models/Agent.php:17-45`

### •unverified · [kyc] KYC documents auto-approved on submission without review — instant level upgrade bypass
- **Evidence:** Comment states: "Documents are auto-approved on submission but flagged for later admin review (status = approved, reviewed_by = null)." Users immediately reach KYC level 1 or 2 (enabling withdrawals, card creation, $10k–$50k balance limits) the moment they upload any document file, before any human or automated check has verified it. reviewed_by = null confirms no reviewer has acted.
- **Fix:** Set document status to 'pending' on submission and only transition to 'approved' after a reviewer (human or automated check) explicitly approves it. Block level upgrades while status is 'pending'. The auto-approve shortcut must be removed; the comment itself acknowledges this is deferred review, not real KYC.
- **Location(s):** `backend/config/kyc.php:16-18`

### •unverified · [kyc] is_verified, kyc_status, kyc_approved_at mass-assignable — KYC bypass
- **Evidence:** 'is_verified', 'kyc_status', 'kyc_submitted_at', 'kyc_approved_at', 'kyc_rejection_reason' are all in $fillable. A controller that mass-assigns request data lets an authenticated merchant self-approve their own KYC by sending is_verified=true&kyc_status=approved.
- **Fix:** Remove all KYC gate fields from $fillable and only ever set them through admin-controlled service methods that enforce role checks.
- **Location(s):** `backend/app/Models/Merchant.php:45-49`

### •unverified · [kyc] KYC auto-approval race condition — non-atomic check-then-act
- **Evidence:** approve() reads pending/rejected counts (lines 54-55) and then conditionally sets kyc_status='approved' (line 59) in two separate, non-atomic queries with no DB transaction or row-level locking. Two admins approving the last two pending documents concurrently can both read pending===0 at the same instant and both trigger KYC approval — even if one of those documents should have been rejected first.
- **Fix:** Wrap the entire approve() body in DB::transaction() and use a SELECT FOR UPDATE / lockForUpdate() on the document row before reading the pending/rejected counts, so concurrent approvals serialize correctly.
- **Location(s):** `backend/app/Http/Controllers/Admin/MerchantDocumentController.php:45-64`

### •unverified · [logic] Silent swallowing of unique constraint failure masks referral double-pay
- **Evidence:** The unique constraint `unique_referral_per_trigger` that is meant to prevent double-paying a referral reward is wrapped in a bare `catch (\Exception $e)` that silently ignores ALL exceptions, including real DB errors unrelated to duplicate keys. If the constraint silently fails to be created (e.g., due to a deadlock, permissions error, or syntax variation), referral rewards will be double-payable with no signal to operators. The comment says it's for idempotency but the broad catch hides failures.
- **Fix:** Use `DB::statement` with a conditional `SHOW INDEX` check or Laravel's `Schema::hasIndex()` (Laravel 11+) before adding the index, rather than swallowing all exceptions. At minimum, catch only `\Illuminate\Database\QueryException` and check `$e->getCode() === '23000'` (duplicate key) before ignoring.
- **Location(s):** `backend/database/migrations/2026_06_24_000005_add_referral_constraints.php:44-53`

### •unverified · [logic] Mass-assignable `status` field allows client to set arbitrary payment request status
- **Evidence:** 'status' is listed in $fillable. A caller using PaymentRequest::create([...]) or $model->fill([...]) with user-supplied data can set status to 'paid', 'approved', or any value, bypassing business-logic state-machine guards.
- **Fix:** Remove 'status' from $fillable. Status must only be updated through explicit model methods (e.g., markAsPaid(), decline()) that enforce valid transitions.
- **Location(s):** `backend/app/Models/PaymentRequest.php:17`

### •unverified · [money] Non-atomic balance update without DB locking enables race condition / double-spend
- **Evidence:** deposit() and withdraw() do a read-modify-write on saved_amount in PHP (`$this->saved_amount += $amount`) then call $this->save(). No DB transaction wraps the read+write pair and no pessimistic lock (lockForUpdate) is used. Two concurrent requests can read the same stale saved_amount, both add their amount, and write back, causing one deposit to be silently lost.
- **Fix:** Use DB::transaction() wrapping a lockForUpdate() query, e.g.: SavingsGoal::where('id', $this->id)->lockForUpdate()->first(), then mutate and save inside the transaction. Alternatively use an atomic DB increment: $this->increment('saved_amount', $amount) inside a transaction.
- **Location(s):** `backend/app/Models/SavingsGoal.php:59-73`

### •unverified · [money] withdraw() balance check is a race condition — negative balance possible
- **Evidence:** The guard `if ($this->saved_amount < $amount) return false;` runs on a PHP-level value that is not locked in the DB. Two concurrent withdrawals can both pass the guard with the same saved_amount, then both decrement, resulting in a negative saved_amount.
- **Fix:** Use a pessimistic DB lock (SELECT … FOR UPDATE) inside a transaction before reading saved_amount, so only one withdrawal proceeds at a time. Also add a DB-level CHECK constraint (saved_amount >= 0) as a final safeguard.
- **Location(s):** `backend/app/Models/SavingsGoal.php:67-73`

### •unverified · [money] Non-atomic balance updates — classic double-spend / race condition
- **Evidence:** credit(), debit(), hold(), release(), capture() all read balance fields into PHP memory, mutate them, then call $this->save(). No SELECT ... FOR UPDATE or lockForUpdate(), and no wrapping DB transaction is enforced here. Two concurrent requests (e.g. two simultaneous debit calls) will both read the same available_balance, both pass the sufficiency check at line 107, and both save — resulting in a double-spend or negative balance.
- **Fix:** Replace arithmetic-on-model with atomic DB increments/decrements: DB::table('wallets')->where('id',$this->id)->where('available_balance','>=',$amount)->decrement('available_balance',$amount,...). Alternatively, wrap callers in DB::transaction() with $wallet->lockForUpdate() before reading the balance. The guard check at line 107 must happen inside the same lock scope.
- **Location(s):** `backend/app/Models/Wallet.php:87-157`

### •unverified · [money] balance and available_balance can go negative — no non-negative constraint enforced
- **Evidence:** debit() checks available_balance < amount at line 107, but due to the race condition above a concurrent path can bypass this check. capture() at line 151-152 decrements both pending_balance and balance without any guard that balance will remain non-negative. If pending_balance was funded by a hold but balance was separately debited between hold() and capture(), balance goes negative.
- **Fix:** Use SQL-level atomic decrement with a WHERE balance >= amount guard and check affected rows == 1. Add a DB CHECK constraint balance >= 0 as a last-resort safety net.
- **Location(s):** `backend/app/Models/Wallet.php:111-112, 151-152`

### •unverified · [money] Race condition on balance check before DB transaction in createCard and loadCard
- **Evidence:** In both `createCard` (line 109: `if ($wallet->available_balance < $purchasePrice)`) and `loadCard` (line 351: `if ($wallet->available_balance < $totalDebit)`), the balance check is performed OUTSIDE the `DB::transaction` block. The wallet row is not locked with `lockForUpdate()` at the time of the check. A concurrent request can pass the same balance check and both proceed into the transaction, allowing double-spend/overdraft.
- **Fix:** Move the balance sufficiency check inside the DB::transaction block and acquire a pessimistic lock on the wallet row first: `$wallet = Wallet::lockForUpdate()->find($wallet->id)` before the balance check.
- **Location(s):** `backend/app/Services/CardService.php:109-119, 351-360`

### •unverified · [money] Race condition on card balance check before DB transaction in unloadCard
- **Evidence:** `if ($card->balance < $amount)` is checked outside the `DB::transaction` at line 435. The card row is not locked, so concurrent unload requests can both pass this check and both decrement the balance, resulting in a negative card balance.
- **Fix:** Move the balance check inside the DB::transaction and add `VirtualCard::lockForUpdate()->find($card->id)` before checking `$card->balance`.
- **Location(s):** `backend/app/Services/CardService.php:427-433`

### •unverified · [money] Double-capture: both handleAuthorizationCreated and handleAuthorizationUpdated call handleAuthorizationCapture on status=closed
- **Evidence:** handleAuthorizationCreated calls handleAuthorizationCapture when status==='closed' (line 108-110). handleAuthorizationUpdated also calls handleAuthorizationCapture when status==='closed' (line 127-130). Stripe can fire both issuing_authorization.created and issuing_authorization.updated with status=closed for the same authorization. handleAuthorizationCapture in StripeIssuingService (line 498-534) only checks status=PROCESSING on the transaction, but if the first event already updated it to COMPLETED, the second call finds no PROCESSING row (returns early) — the reserved_balance is not double-decremented. However, the wallet.debit() is still called on the second event if the first event hasn't finished committing (race). More concretely: there is no idempotency guard (e.g. checking whether the transaction is already COMPLETED before doing debit), so concurrent delivery of both events can race inside two DB transactions and debit the wallet twice.
- **Fix:** Add an idempotency check at the top of handleAuthorizationCapture: if the transaction is already COMPLETED, return early immediately (before locking or debiting). Also deduplicate by storing the Stripe event ID and checking it before processing.
- **Location(s):** `backend/app/Http/Controllers/Webhooks/StripeIssuingWebhookController.php:108-110, 127-130`

### •unverified · [money] processWalletWithdraw does not debit wallet before submitting withdrawal to network
- **Evidence:** `processWalletWithdraw` submits the withdrawal to CCPayment (line 355-363) and records a PENDING transaction (line 366-382) without ever debiting the user's wallet. A user can call this repeatedly and drain funds from the payment gateway without their local balance ever being reduced. The refund path in `handleWithdrawWebhook` (line 477-486) then credits a balance that was never decremented.
- **Fix:** Debit the wallet atomically (inside a DB transaction with lock) before calling `withdrawToNetwork`. Roll back if the API call fails.
- **Location(s):** `backend/app/Services/CCPaymentService.php:340-389`

### •unverified · [money] Race condition / double-spend: no pessimistic lock before balance read-modify-write
- **Evidence:** deposit(), withdraw(), and convert() all call wallet->credit()/debit() which read available_balance into memory, check it, then save — with no SELECT ... FOR UPDATE. Two concurrent requests both read the same balance, both pass the sufficiency check, and both commit, allowing overdraft or double-spend.
- **Fix:** Before any balance mutation inside a DB::transaction, reload the wallet with a pessimistic lock: Wallet::lockForUpdate()->find($wallet->id). Only then perform the read-check-write cycle.
- **Location(s):** `backend/app/Services/WalletService.php:33-59, 67-99, 184-233`

### •unverified · [money] convert() ignores credit() return value on destination wallet — funds lost if toWallet is frozen
- **Evidence:** Line 199 debits fromWallet (funds leave). Line 204 calls $toWallet->credit($convertedAmount) without checking the bool result. If toWallet is frozen, credit() returns false and the converted amount is silently discarded — fromWallet loses money but toWallet receives nothing.
- **Fix:** if (!$toWallet->credit($convertedAmount)) { throw new \Exception('Destination wallet credit failed'); } — the exception will roll back the DB::transaction.
- **Location(s):** `backend/app/Services/WalletService.php:199-204`

### •unverified · [money] reverse() not atomic — wallet balance not updated, creates inconsistent state
- **Evidence:** reverse() marks the original transaction REVERSED and creates an ADJUSTMENT transaction record with a negative amount, but it never actually debits/credits the wallet balance. The balance_before/balance_after on the new reversal record are computed by the boot() hook reading the current wallet balance (line 87-91), but the wallet.balance column itself is never touched. The reversal record thus reflects an incorrect balance snapshot and the user's wallet balance remains unchanged.
- **Fix:** Wrap reverse() in a DB::transaction(), credit the wallet balance atomically (e.g. Wallet::lockForUpdate()->increment/decrement), and pass explicit balance_before/balance_after to the reversal Transaction::create() call so the boot hook does not read a stale balance.
- **Location(s):** `backend/app/Models/Transaction.php:153-185`

### •unverified · [money] reverse() has no concurrency guard — double-reversal race condition
- **Evidence:** The status check on line 155 (`if ($this->status !== TransactionStatus::COMPLETED) return null`) and the status update on line 167 (`$this->status = TransactionStatus::REVERSED; $this->save()`) are not inside a DB transaction with a pessimistic lock. Two concurrent requests can both read status=COMPLETED, both pass the guard, and both produce a reversal record — effectively double-crediting the wallet.
- **Fix:** Use DB::transaction() with `Transaction::lockForUpdate()->find($this->id)` to re-read and re-check the status inside the lock before proceeding.
- **Location(s):** `backend/app/Models/Transaction.php:155-168`

### •unverified · [money] KYC limit check races with transfer execution (TOCTOU double-spend)
- **Evidence:** `assertWithinKycLimits` reads daily/monthly `sum('amount')` outside the DB transaction (line 103 is called before `DB::transaction` starts at line 107). Two concurrent transfers can both pass the limit check against the same snapshot, then both execute, together exceeding the daily/monthly cap.
- **Fix:** Move `assertWithinKycLimits` inside the `DB::transaction` closure, after the wallet `lockForUpdate` is acquired. The SUM queries must run inside the same transaction and with a lock (e.g. `lockForUpdate` on a synthetic row or `SELECT ... FOR UPDATE` on a limits table) to prevent concurrent reads from seeing stale data.
- **Location(s):** `backend/app/Services/TransferService.php:103, 296-318`

### •unverified · [money] Non-atomic balance updates — race condition / double-spend on creditGrams and debitGrams
- **Evidence:** Both creditGrams and debitGrams read the current balance into PHP (via $this->balance_grams), mutate it in memory, then call $this->save(). Two concurrent requests will both read the same stale value, both pass the balance check (debitGrams line 53), and both write their individual delta — the second write silently overwrites the first, enabling a double-spend or double-credit with no locking.
- **Fix:** Use atomic DB increment/decrement with a pessimistic lock, e.g. GoldWallet::lockForUpdate()->find($this->id) inside a DB::transaction(), or use DB::table('gold_wallets')->where('id',$this->id)->decrement('balance_grams', $grams) combined with a WHERE balance_grams >= $grams constraint checked via affected-rows count.
- **Location(s):** `backend/app/Models/GoldWallet.php:43-59`

### •unverified · [money] Insufficient-balance check is not atomic and can result in negative balance
- **Evidence:** The guard `if ($this->balance_grams < $grams) return false;` operates on a PHP-side snapshot, not a locked DB row. A concurrent debitGrams call on the same wallet will pass the check simultaneously and both saves will succeed, pushing balance_grams below zero.
- **Fix:** Wrap in DB::transaction() with a SELECT ... FOR UPDATE lock so only one concurrent debit can read and validate the balance at a time.
- **Location(s):** `backend/app/Models/GoldWallet.php:53-54`

### •unverified · [money] Non-atomic balance update in loadFunds — double-spend / race condition
- **Evidence:** loadFunds() calls wallet->debit() then separately does `$this->balance += $amount; $this->save()`. There is no database transaction wrapping both operations. If the process dies between debit and save, the wallet is debited but the card balance is never incremented. Concurrent calls can also read the same stale balance and both pass the check.
- **Fix:** Wrap wallet->debit() and the card balance increment+save in a DB::transaction() with a SELECT ... FOR UPDATE lock on the card row.
- **Location(s):** `backend/app/Models/VirtualCard.php:205-218`

### •unverified · [money] Non-atomic balance update in unload — money can be created or lost
- **Evidence:** unload() calls wallet->credit() then `$this->balance -= $amount; $this->save()` without a wrapping DB transaction or row lock. If wallet->credit() succeeds but the subsequent save fails, money is credited to the wallet while the card balance is not reduced — money is created from nothing.
- **Fix:** Wrap both operations in DB::transaction() with a pessimistic lock (lockForUpdate) on the card row.
- **Location(s):** `backend/app/Models/VirtualCard.php:220-233`

### •unverified · [money] Non-atomic spend — race condition allows double-spend beyond limits
- **Evidence:** spend() calls canSpend() which reads $this->balance from the in-memory object, then decrements and saves. Two concurrent requests can both pass canSpend() with the same stale balance and both write a save, spending the same funds twice. There is no DB-level lock anywhere in this path.
- **Fix:** Wrap canSpend+decrement in a DB::transaction() and re-read the card with lockForUpdate() before checking balance/limits.
- **Location(s):** `backend/app/Models/VirtualCard.php:262-274`

### •unverified · [money] Race condition: spending limits checked outside DB transaction lock
- **Evidence:** checkSpendingLimits() reads daily_spent/monthly_spent at line 410 before the DB::transaction + lockForUpdate at line 425. Two concurrent authorization requests can both pass the limit check, then both increment inside their own transactions, exceeding the daily/monthly cap.
- **Fix:** Move checkSpendingLimits() inside the DB::transaction after lockForUpdate, or lock the card row (VirtualCard::lockForUpdate()->find()) before reading spending counters.
- **Location(s):** `backend/app/Services/StripeIssuingService.php:410-413, 425-463`

### •unverified · [money] Capture amount vs. reserved amount mismatch causes ledger corruption
- **Evidence:** handleAuthorizationCapture() releases abs($transaction->amount) (the original authorized amount) from reserved_balance, then calls $wallet->debit($amountDollars) where $amountDollars comes from $authorization['approved_amount']. Stripe allows partial captures (approved_amount < authorized amount). The difference is neither returned to available_balance nor properly reconciled, leaving reserved_balance permanently inflated.
- **Fix:** After releasing the full reserved amount, debit only the captured amount and credit back the difference: $wallet->decrement('reserved_balance', $originalAmount); $wallet->debit($capturedAmount); — or use a single atomic balance adjustment.
- **Location(s):** `backend/app/Services/StripeIssuingService.php:513-516`

### •unverified · [money] Race condition: balance read-modify-write without row locking
- **Evidence:** credit(), debit(), hold(), release(), capture() all read the current balance into PHP memory (e.g. $this->available_balance < $amount), then mutate and save(). No SELECT … FOR UPDATE / lockForUpdate() is used. Two concurrent requests can both pass the balance check and both debit, producing a negative balance (double-spend).
- **Fix:** Before calling any balance-mutating method, lock the row: Wallet::lockForUpdate()->find($id). Do this inside the DB::transaction() wrapper that already exists in TransactionService.
- **Location(s):** `backend/app/Models/Wallet.php:87-119`

### •unverified · [webhook] Webhook signature never verified before processing deposit/withdrawal
- **Evidence:** `handleDepositWebhook` and `handleWithdrawWebhook` accept a raw `$payload` array and immediately update transaction state and credit/refund wallets. `verifyWebhookSignature` exists (line 492) but is never called inside these handlers. Any unauthenticated caller that can POST to the webhook endpoint can credit arbitrary wallets or trigger refunds.
- **Fix:** Call `verifyWebhookSignature($rawBody, $sign, $timestamp)` at the top of both handlers (or in the controller before dispatching) and abort with a 401 if verification fails.
- **Location(s):** `backend/app/Services/CCPaymentService.php:394-438, 443-487`

### •unverified · [webhook] Test/development webhook endpoints exposed in production with no authentication
- **Evidence:** Route::get('/ccpayment/info', ...'info') and Route::post('/ccpayment/test/deposit', ...'testDeposit') / Route::post('/ccpayment/test/withdraw', ...'testWithdraw') are registered with no auth, no environment guard, and no webhook signature verification. Any unauthenticated actor on the internet can POST to /webhooks/ccpayment/test/deposit or /webhooks/ccpayment/test/withdraw to trigger fake deposit/withdrawal processing.
- **Fix:** Remove these routes entirely before production, or wrap them in a middleware that restricts to local/staging environments (e.g., abort_unless(app()->environment('local'), 403)) and add IP allowlisting.
- **Location(s):** `backend/routes/web.php:195-197`

### •unverified · [webhook] Webhook test endpoints exposed in production with no auth — crypto deposit/withdraw can be triggered freely
- **Evidence:** Route::prefix('webhooks/ccpayment')->group(function () { Route::post('/test/deposit', ...) Route::post('/test/withdraw', ...) }) — no authentication, no throttle, no environment guard. Any unauthenticated caller can POST fake deposit or withdraw webhook events to CCPaymentWebhookController, crediting or debiting balances without a real on-chain transaction.
- **Fix:** Wrap the block in an environment check (abort unless app()->environment('local', 'testing')) or remove the routes entirely before deploying to staging/production. At minimum add auth:sanctum + admin middleware.
- **Location(s):** `backend/routes/api.php:482-486`

### •unverified · [webhook] Stripe webhook endpoint missing signature verification middleware
- **Evidence:** Route::post('/stripe/issuing', [StripeIssuingWebhookController::class, 'handle']) has no middleware — no 'stripe.signature' or equivalent webhook-signature-verification middleware is applied at the route level. If the controller itself does not verify the Stripe-Signature header (which cannot be confirmed from this file alone), forged authorization.request events can approve or decline card transactions.
- **Fix:** Apply a middleware that calls \Stripe\Webhook::constructEvent() with the raw request body and STRIPE_WEBHOOK_SECRET before the controller handles the request, e.g. ->middleware('stripe.webhook').
- **Location(s):** `backend/routes/api.php:473-475`

## High (235 kept, 234 deduped)

### ✅ verified-real · [auth] PIN verification bypassed when biometric_token is supplied on deposit/withdraw
- **Evidence:** 'pin' => 'required_without:biometric_token|string' — if the caller sends biometric_token (any non-empty string), pin is not required AND the check `if ($request->filled('pin') && !$user->verifyPin(...))` is skipped entirely. There is no code that validates biometric_token, so an attacker passes biometric_token=x to bypass PIN on any deposit or withdrawal.
- **Verifier:** Confirmed real and exploitable. In SavingsController::deposit (lines 130-139) and ::withdraw (lines 165-185) the validation rule 'pin' => 'required_without:biometric_token|string' makes PIN optional whenever any non-empty biometric_token is supplied, and the only PIN check is the short-circuiting gu
- **Fix:** Either validate the biometric token before allowing it to substitute for PIN, or remove the required_without bypass and always require one verified credential. Never skip credential verification when the alternative is unvalidated.
- **Location(s):** `backend/app/Http/Controllers/API/SavingsController.php:133,137,165,183`

### ✅ verified-real · [auth] TOCTOU race condition allows challenge replay in concurrent biometric verify requests
- **Evidence:** verify() reads the challenge at line 133 via cache()->get() to check existence, then calls verifyBiometricToken() which reads it again (VerifiesTransactionAuth.php:90) and only then deletes it (line 105). Two concurrent requests from the same user both pass the existence check at line 135 before either consumes the key, giving both a chance to proceed through signature verification and mint a token.
- **Verifier:** The claim is accurate against the code. BiometricController::verify() does a non-destructive existence check at line 133 (cache()->get), then verifyBiometricToken() in VerifiesTransactionAuth.php re-reads the challenge at line 90 and only forgets it at line 105 after signature verification — a textb
- **Fix:** Use an atomic get-and-delete (cache()->pull()) in verifyBiometricToken so the challenge is consumed in a single operation. Remove the separate existence check in verify() and rely solely on the atomic pull inside the trait.
- **Location(s):** `backend/app/Http/Controllers/API/BiometricController.php:133-151`

### ✅ verified-real · [auth] Biometric device enrollment requires no second factor — any session token can register a trusted device
- **Evidence:** registerDevice() calls updateOrCreate with 'is_trusted' => true (line 38) after only validating that the request carries a valid session token. No PIN, no existing biometric proof, and no admin approval is required. An attacker who steals or forges a session token can immediately enroll their own device as trusted and use biometric auth to authorize money-moving operations.
- **Verifier:** Confirmed real and exploitable. BiometricController::registerDevice (lines 32-41) is mounted at POST /v1/biometric/devices behind only auth:sanctum (routes/api.php:66-88) — no PIN, no existing-biometric proof, no admin/trusted-device approval, and no EnsureDeviceCanTransact gate. It unconditionally 
- **Fix:** Require the user to present a valid PIN (or existing biometric proof) before a new device can be registered as trusted. Either call verifyTransactionFactor() at the start of registerDevice(), or set is_trusted: false and gate trust elevation behind a separate PIN-verified step.
- **Location(s):** `backend/app/Http/Controllers/API/BiometricController.php:32-41`

### ✅ verified-real · [auth] No brute-force / rate-limit protection on PIN verification endpoint
- **Evidence:** `verifyPin` accepts unlimited attempts with no lockout counter, no delay, and no throttle middleware visible in this controller. A 6-digit PIN has only 1 000 000 combinations; an attacker with a valid session token (e.g. from a stolen device) can enumerate all PINs in seconds. The same applies to `changePin` (line 216).
- **Verifier:** The claim's literal evidence ("no throttle middleware visible in this controller") is a misread of where Laravel applies throttling, but the underlying defect is real. Routes (backend/routes/api.php) show /pin/verify (line 75) and /pin/change (line 96) sit inside the protected auth prefix (line 69),
- **Fix:** Apply Laravel's `throttle` middleware (or a custom lock) to these routes — e.g. 5 failed attempts triggers a lockout — and track failed attempts on the user record so lockout persists across requests.
- **Location(s):** `backend/app/Http/Controllers/API/AuthController.php:194-214`

### ✅ verified-real · [auth] Race condition / token replay in poll(): auth token consumed only on first read but window exists for double-read
- **Evidence:** Cache::forget is called after the status check and response is built, but not atomically. Two concurrent poll requests can both pass the `$data['status'] === 'approved'` check before either calls Cache::forget, causing both to receive the auth_token in their response. The attacker who scanned a legitimate QR code can race the real user to steal the issued bearer token.
- **Verifier:** The claim is verified by direct inspection. In poll() (QrAuthController.php:38-58) the code does Cache::get on line 38, checks $data['status'] === 'approved' on line 47, then calls Cache::forget on line 48 — a classic non-atomic get-then-delete (TOCTOU). routes/api.php:51 shows /qr/poll/{token} is a
- **Fix:** Use an atomic Cache lock or a cache-get-and-delete primitive (e.g. Cache::pull) so the token is consumed exactly once. Replace the get+forget sequence with: `$data = Cache::pull("qr_auth:{$token}");` and handle the approved branch inline without a separate forget call.
- **Location(s):** `backend/app/Http/Controllers/API/QrAuthController.php:47-58`

### ✅ verified-real · [data-exposure] User enumeration via phone-suffix matching — unauthenticated or authenticated bulk scraping
- **Evidence:** The endpoint accepts up to 1000 phone numbers per call with no rate-limiting visible in this controller. An attacker can call the endpoint repeatedly with incrementally generated 9-digit suffixes and enumerate every registered user's phone number, first name, last name, and derived account number. The comment on line 13 claims the design is 'privacy-preserving' but the implementation leaks PII for every matched number without any throttle.
- **Verifier:** Partly mischaracterized but the core defect is real. The route (routes/api.php:161) sits inside `Route::middleware('auth:sanctum')` and the whole v1 group carries `throttle:api`, so the claim's "unauthenticated" path and "no rate-limiting" are both wrong: the endpoint requires a valid Sanctum token 
- **Fix:** Apply strict per-user rate limiting (e.g. max 3 requests/minute, max 200 numbers/day). Consider returning only a boolean match flag instead of name/initials when the caller is not already a confirmed contact.
- **Location(s):** `backend/app/Http/Controllers/API/ContactController.php:49-68`

### ✅ verified-real · [data-exposure] Full PAN and CVV returned with no step-up authentication
- **Evidence:** `details()` returns `decrypted_card_number` and `decrypted_cvv` in plaintext JSON (lines 142-143) protected only by the bearer token — no PIN verification, 2FA, or re-authentication is required. A stolen session token is sufficient to harvest full card credentials.
- **Verifier:** Confirmed exploitable. In /home/es3dlll/Desktop/Lorka/projects/carda-wallet/backend/app/Http/Controllers/API/CardController.php the details() method (lines 130-148) returns decrypted_card_number and decrypted_cvv (lines 142-143) in plaintext JSON after only an ownership check ($card->user_id === $re
- **Fix:** Require a verified second factor (PIN or TOTP) before returning sensitive card data. Consider using a short-lived, ephemeral token pattern (e.g., Stripe's ephemeral keys) instead of serving the PAN/CVV directly.
- **Location(s):** `backend/app/Http/Controllers/API/CardController.php:130-148`

### ✅ verified-real · [data-exposure] Maintenance secret also logged in plaintext via ActivityLog
- **Evidence:** ActivityLog::log('maintenance.enabled', null, null, null, $validated, 'Maintenance mode enabled');
- **Verifier:** Confirmed at AdminController.php:1223. The validation at line 1205-1208 places the user-supplied maintenance 'secret' into $validated, and line 1223 passes $validated as the 5th positional arg to ActivityLog::log, which (per app/Models/ActivityLog.php:44-63) maps it to 'new_values' — a column cast t
- **Fix:** $validated at this point contains 'secret'. Redact it before logging: unset($logData['secret']); and pass the sanitised array instead.
- **Location(s):** `backend/app/Http/Controllers/API/AdminController.php:1223`

### ✅ verified-real · [idempotency] No idempotency key — duplicate/retry requests cause double-spend
- **Evidence:** The `transfer` endpoint has no idempotency key mechanism. A client network timeout followed by a retry, or a double-tap in the mobile app, will create two separate complete transactions debiting the sender twice. There is no deduplication table, no unique constraint on a client-supplied request ID, and no check for a recent identical transfer.
- **Verifier:** Confirmed real. The transfer flow (TransferController::transfer -> TransferService::transfer) accepts only identifier/amount/currency/note (TransferRequest rules) with no client request-ID; the route at routes/api.php:139 attaches only throttle:api and EnsureDeviceCanTransact, and that middleware is
- **Fix:** Require an `Idempotency-Key` header (UUID). Store it in a `idempotency_keys` table keyed by `(user_id, key)` with the serialized response. On a duplicate key return the cached response; on conflict return 409.
- **Location(s):** `backend/app/Http/Controllers/API/TransferController.php:55-122`

### ✅ verified-real · [idempotency] Deposit has no idempotency key: duplicate requests create duplicate credits
- **Evidence:** The `deposit()` endpoint accepts any POST with a valid amount and immediately credits the wallet inside a DB transaction. There is no idempotency key field validated or stored. A network retry, double-tap, or client timeout-and-retry will produce two separate completed deposit transactions and credit the balance twice.
- **Verifier:** The claim is factually accurate: DepositRequest (app/Http/Requests/Wallet/DepositRequest.php) validates only `amount` (no idempotency_key); the controller deposit() at WalletController.php:135-167 immediately calls WalletService::deposit(), which inside DB::transaction() credits the wallet and write
- **Fix:** Require an `idempotency_key` (UUID) in the request, store it in a `transactions` unique index or a dedicated `idempotency_keys` table, and return the previously created transaction on duplicate submission.
- **Location(s):** `backend/app/Http/Controllers/API/WalletController.php:135-167`

### ✅ verified-real · [injection] Entire backup SQL file executed via PDO::exec — arbitrary SQL injection if backup is tampered
- **Evidence:** restore() reads the backup file content with Storage::get($path) and passes it directly to DB::getPdo()->exec($sql) inside a transaction. The backup lives on local disk; any actor who can write to the backups directory (misconfigured permissions, path traversal in another endpoint, server compromise) can inject arbitrary SQL that runs with full DB credentials.
- **Verifier:** Confirmed at line 228/235: restore() does `$sql = Storage::disk('local')->get($path)` then `DB::getPdo()->exec($sql)`, executing the entire backup file as raw multi-statement SQL with full DB credentials and zero integrity/content validation. So the technical core of the claim is accurate — there is
- **Fix:** Validate the backup file is a well-formed dump (checksum stored at creation time, verified before exec). Restrict filesystem permissions on the backups directory to be unwritable by the web server process. Consider using mysqldump/mysql CLI tools instead of PDO::exec for restore.
- **Location(s):** `backend/app/Http/Controllers/Admin/DatabaseBackupController.php:228-241`

### ✅ verified-real · [kyc] KYC approval race condition — non-atomic pending/rejected check
- **Evidence:** Two separate COUNT queries check `pending` and `rejected` after updating the document status. Between those queries and the agent KYC approval update, another concurrent request could change document state, causing the agent to be marked `kyc_status=approved` and `is_verified=true` while a pending or rejected document still exists.
- **Verifier:** The defect is real but its severity is overstated. In approve() (lines 47-63), the document status is updated, then two separate, non-transactional COUNT queries on the agent's documents decide whether to flip the agent to kyc_status=approved/is_verified=true. There is a genuine time-of-check-to-tim
- **Fix:** Wrap lines 47–63 in a DB::transaction with a SELECT ... FOR UPDATE lock on the agent row (or use a single atomic query) so the pending/rejected count and the KYC status update are evaluated and written atomically.
- **Location(s):** `backend/app/Http/Controllers/Admin/AgentDocumentController.php:53-63`

### ✅ verified-real · [kyc] New agents default to is_verified=true without any KYC process
- **Evidence:** $validated['is_verified'] ??= true; — every new agent is stamped verified by default unless the admin explicitly unchecks the field; a missing or unchecked checkbox sets is_verified to true.
- **Verifier:** Confirmed real. In AgentController::store() line 102, `$validated['is_verified'] ??= true;` stamps every newly created cash agent as KYC-verified whenever the verification checkbox is absent or unchecked (unchecked HTML checkboxes aren't submitted, so the validated value is null and gets coalesced t
- **Fix:** Default is_verified to false so agents start unverified and must be explicitly approved after a KYC review.
- **Location(s):** `backend/app/Http/Controllers/Admin/AgentController.php:102`

### ✅ verified-real · [logic] Race condition on duplicate application check — concurrent requests can create two Agent/Merchant rows for the same user
- **Evidence:** Agent::where('user_id', $user->id)->exists() check on line 53 and Agent::create() on line 71 are not wrapped in a DB transaction with a pessimistic lock. Two simultaneous requests both pass exists()=false and both insert, creating duplicate agent/merchant applications for the same user.
- **Verifier:** The defect is genuine. In applyAgent (lines 53/71) and applyMerchant (lines 97/114) the controller does a check-then-act: Agent::where('user_id',...)->exists() followed by Agent::create(), with no DB::transaction, no lockForUpdate, and no firstOrCreate. Crucially, the database provides no safety net
- **Fix:** Wrap the exists() + create() block in DB::transaction() and use firstOrCreate with a unique constraint on user_id, or use INSERT IGNORE / upsert with a unique index on (user_id) in the agents/merchants tables to enforce uniqueness atomically at the DB level.
- **Location(s):** `backend/app/Http/Controllers/API/PartnerApplicationController.php:53-87`

### ✅ verified-real · [logic] Unload endpoint missing card status check — frozen/cancelled cards can be unloaded
- **Evidence:** `unload()` has no check that `$card->status === CardStatus::ACTIVE`, unlike `load()` which checks at line 222-227. A frozen or cancelled card can have funds withdrawn through this endpoint.
- **Verifier:** The factual core of the claim holds at every layer: CardController::unload() (lines 252-287) performs only an ownership check (262) and a balance check (269) — no CardStatus::ACTIVE guard, unlike load() at 222-227 — and CardService::unloadCard() (lines 418-484) likewise checks only ownership (423) a
- **Fix:** Add a status guard identical to the one in `load()`: return 422 if `$card->status !== CardStatus::ACTIVE`.
- **Location(s):** `backend/app/Http/Controllers/API/CardController.php:252-287`

### ✅ verified-real · [logic] updateSetting allows creation of arbitrary keys including security-critical settings without validation
- **Evidence:** $setting = SystemSetting::updateOrCreate(
    ['key' => $validated['key']],
    $validated
);
- **Verifier:** The technical core of the claim is accurate: updateSetting (AdminController.php:894-918) uses SystemSetting::updateOrCreate keyed on a free-text 'key' validated only as required|string, with 'value' validated only as required — there is NO allowlist and NO per-key type/range validation, so an admin 
- **Fix:** Any admin (including a compromised low-privilege admin account) can upsert any setting key — including fee rates, referral bonuses, KYC limits, or internal toggles — with no allowlist. Restrict updatable keys to an explicit allowlist, or require a second-factor / confirmation for high-impact keys.
- **Location(s):** `backend/app/Http/Controllers/API/AdminController.php:894-918`

### ✅ verified-real · [logic] Race condition: two concurrent requests can both become the first trusted device
- **Evidence:** $isFirst = $user->devices()->count() === 0; followed by $user->devices()->create([...]) with no transaction or DB lock. Two simultaneous register calls from different devices for the same user can both observe count() === 0 and both be created with is_trusted=true and STATUS_APPROVED.
- **Verifier:** The claim is technically correct and not guarded elsewhere. In register() (DeviceController.php:52-67) the code does a read-then-write TOCTOU: `$isFirst = $user->devices()->count() === 0;` then `$user->devices()->create([...])` with `is_trusted => $isFirst` and `status => STATUS_APPROVED` for the fi
- **Fix:** Wrap the count-check and create in a DB::transaction() with a pessimistic lock (e.g. lockForUpdate on a user row or unique constraint + retry). Alternatively use an atomic upsert or a database-level unique constraint ensuring only one trusted device per user.
- **Location(s):** `backend/app/Http/Controllers/API/DeviceController.php:52-67`

### ✅ verified-real · [logic] Gold wallet value recalculated incorrectly — balance multiplied by every karat price and summed
- **Evidence:** $breakdown = $prices->map(function ($price) use ($goldWallet, &$currentValue) { $value = $goldWallet->balance_grams * $price->sell_price; $currentValue += $value; — the loop adds the wallet's gram balance multiplied by EACH karat's price, so if there are 4 karats the currentValue is 4x the real value. The wallet does not track per-karat holdings.
- **Verifier:** Confirmed real. In GoldSavingsController::wallet() lines 53-62, $prices->map() iterates every active GoldPrice (one row per karat: 24/22/21/18) and for each accumulates $goldWallet->balance_grams * $price->sell_price into $currentValue via the &$currentValue closure reference. The GoldWallet model (
- **Fix:** The wallet stores a single balance_grams without karat breakdown, so currentValue should use one reference price (e.g. 24k), not sum across all karats.
- **Location(s):** `backend/app/Http/Controllers/API/GoldSavingsController.php:53-56`

### ✅ verified-real · [logic] Agent dashboard returns fabricated random data instead of real financial figures
- **Evidence:** 'total_transactions' => Agent::where('id', $agent->id)->count() * 0 + rand(100, 500), 'total_cash_flow' => rand(50000, 300000), 'total_commission' => rand(2000, 15000) — all three KPIs are rand() values; the transaction list also uses rand() amounts and fabricated user names.
- **Verifier:** Confirmed verbatim in dashboard() at lines 117-134 of /home/es3dlll/Desktop/Lorka/projects/carda-wallet/backend/app/Http/Controllers/Admin/AgentController.php: total_transactions uses `count() * 0 + rand(100,500)` (the *0 nullifies the real query), total_cash_flow and total_commission are pure rand(
- **Fix:** Replace all rand() placeholders with real DB queries against the transactions/commissions tables filtered by agent_id. Displaying fake financial data to admins making operational decisions (e.g. commission payouts, fraud detection) is a critical logic defect in a fintech context.
- **Location(s):** `backend/app/Http/Controllers/Admin/AgentController.php:117-134`

### ✅ verified-real · [money] Race condition: savings balance checked outside transaction in withdraw()
- **Evidence:** The check `if ($savings->saved_amount < $validated['amount'])` (line 187) reads a stale snapshot of the SavingsGoal before the DB::transaction begins (line 191). Concurrent withdrawals both pass this check, then both call moveFromSavings(), allowing the saved_amount to go negative. The SavingsGoal row is never locked with lockForUpdate() before the check.
- **Verifier:** Confirmed real and money-impacting. In withdraw() (SavingsController.php:161-199) the SavingsGoal is route-model-bound and never re-fetched with lockForUpdate(); the pre-transaction check at line 187 reads a stale saved_amount, and the DB::transaction at 191 also takes no row lock. SavingsGoal::with
- **Fix:** Move the balance check inside the DB::transaction block and lock the SavingsGoal row with lockForUpdate() before reading saved_amount, the same pattern used in deposit().
- **Location(s):** `backend/app/Http/Controllers/API/SavingsController.php:187-192`

### ✅ verified-real · [money] moveFromSavings() does not lock the USD wallet before crediting
- **Evidence:** `$usdWallet = Wallet::where(...)->first()` (line 257) fetches the wallet without lockForUpdate(). Concurrent withdrawals (or concurrent wallet operations from another controller) can produce lost updates on the wallet balance when credit() runs on a stale row.
- **Verifier:** Confirmed real. SavingsController::moveFromSavings (line 257) fetches the USD wallet with a plain Wallet::where(...)->first() and no lockForUpdate(), then calls Wallet::credit() (Wallet.php:87-98), which is a read-modify-write in PHP ($this->available_balance += $amount; $this->save()), not an atomi
- **Fix:** Add ->lockForUpdate() to the wallet query inside moveFromSavings(), same as done in deposit().
- **Location(s):** `backend/app/Http/Controllers/API/SavingsController.php:257-258`

### ✅ verified-real · [money] Transfer and status update are not atomic — payment can succeed but record remain 'pending'
- **Evidence:** In pay(), the TransferService executes the money movement (line 271-277) and only afterwards calls $paymentRequest->update(['status'=>'paid',...]) (line 282-287). If the process crashes or the update throws between these two operations, money is deducted but the PaymentRequest stays 'pending', allowing it to be paid again.
- **Verifier:** Confirmed real. In pay() (PaymentRequestController.php:252-297), the isPending() guard (line 256) is a plain read with no row lock, the TransferService->transfer() call (271-277) commits its own internal DB::transaction independently, and only afterwards does $paymentRequest->update(['status'=>'paid
- **Fix:** Wrap both the transfer call and the status update inside a single DB::transaction so they commit or roll back together.
- **Location(s):** `backend/app/Http/Controllers/API/PaymentRequestController.php:271-287`

### ✅ verified-real · [money] Float arithmetic used for all balance mutations — precision loss on money
- **Evidence:** `$this->balance += $amount` and all balance fields are manipulated as PHP `float`. Amounts arrive as `(float) $validated['amount']` (TransferController line 73). PHP IEEE-754 floats cause rounding errors (e.g., 0.1 + 0.2 ≠ 0.3) that accumulate across millions of SYP transactions. The DB columns are `decimal:8` but the in-memory arithmetic is lossy before `save()`.
- **Verifier:** Real but overstated. All balance mutations in Wallet.php (credit/debit/hold/release/capture, lines 93-95, 111-115) use PHP float arithmetic via += / -=, and amounts flow in as (float)$validated['amount'] (TransferController L73) through float $amount signatures across TransferService. The decimal:8 
- **Fix:** Use `bcadd`/`bcsub` with a fixed scale (e.g., 8 decimal places) for all balance mutations, or store amounts as integer minor units. Remove the `(float)` cast in TransferController and pass the raw validated string to the service.
- **Location(s):** `backend/app/Models/Wallet.php:93-95, 111-115`

### ✅ verified-real · [money] Floating-point arithmetic used for currency amount comparison and debit
- **Evidence:** if ($wallet->balance < floatval($validated['amount']))
$wallet->debit(floatval($validated['amount']));
- **Verifier:** Confirmed real: CCPaymentController::withdraw() compares `$wallet->balance < floatval($validated['amount'])` (line 227) and calls `$wallet->debit(floatval($validated['amount']))` (line 245), and Wallet::debit()/credit() themselves are typed `float $amount` and do all arithmetic with native float ope
- **Fix:** Use BCMath string arithmetic (bccomp, bcsub) for all monetary comparisons and debits to avoid floating-point precision loss on crypto amounts (e.g., small BTC values).
- **Location(s):** `backend/app/Http/Controllers/API/CCPaymentController.php:227, 245`

### ✅ verified-real · [money] Wallet deletion allows negative-balance wallets to be deleted
- **Evidence:** `destroy()` only blocks deletion when `floatval($wallet->balance) > 0`. A wallet with `balance = -0.001` (which can occur if a bug or concurrent write drives the balance negative) passes the check and is deleted, permanently burying a negative balance and destroying the audit trail.
- **Verifier:** Confirmed in /home/es3dlll/Desktop/Lorka/projects/carda-wallet/backend/app/Http/Controllers/API/WalletController.php:319, where destroy() guards deletion only with `floatval($wallet->balance) > 0`. A wallet whose balance is negative passes the check and is permanently deleted at line 326. The owners
- **Fix:** Change the guard to `floatval($wallet->balance) != 0` (or `!= 0.0` with an epsilon comparison) so any non-zero balance — positive or negative — prevents deletion.
- **Location(s):** `backend/app/Http/Controllers/API/WalletController.php:319-324`

### ✅ verified-real · [validation] Unvalidated document_type allows arbitrary KYC document category injection
- **Evidence:** 'document_type' => 'required|string|max:40' — no in: constraint against AgentDocument::TYPES or MerchantDocument::TYPES, despite those constants being used in labelMap() on lines 28-30.
- **Verifier:** The code-level claim is accurate: PartnerApplicationController::uploadDocument (line 144) validates document_type only as 'required|string|max:40' with no in: whitelist, even though AgentDocument::TYPES and MerchantDocument::TYPES define the allowed set and getTypeLabelAttribute() silently falls bac
- **Fix:** Add 'in:' validation using the model constants: 'document_type' => 'required|string|in:' . implode(',', array_keys(AgentDocument::TYPES)) (branch on validated type). This prevents polluting the documents table with garbage types and bypassing review workflows that may key on document_type.
- **Location(s):** `backend/app/Http/Controllers/API/PartnerApplicationController.php:144`

### ✅ verified-real · [validation] Exchange rates allow zero value — rate=0 accepted
- **Evidence:** 'rate' => 'required|numeric|min:0', 'buy_rate' => 'required|numeric|min:0', 'sell_rate' => 'required|numeric|min:0' — min:0 permits 0, so an admin (or exploited admin session) can set any exchange rate to 0, causing division-by-zero in downstream conversion logic and potentially allowing users to convert/send currency at zero cost.
- **Verifier:** The validation at lines 19-21 does use min:0, which accepts a zero exchange rate — confirmed. A zero rate is economically invalid and is reachable: although the WalletService::convert() path (line 195) is multiplication ($amount * $sellRate) and so does NOT divide by zero (it silently credits 0, har
- **Fix:** Change min:0 to min:0.000001 (or a suitable non-zero minimum) for rate, buy_rate, and sell_rate to prevent a zero-rate being stored.
- **Location(s):** `backend/app/Http/Controllers/Admin/ExchangeRateController.php:19-21`

### ⚠️ uncertain · [data-exposure] Unbounded export query causes DoS / full-table PII dump
- **Evidence:** $logs = $query->latest()->get(); — no LIMIT applied before streaming CSV, so all matching rows are loaded into memory and written to the response. On a mature production system with millions of audit rows a single export request can exhaust PHP memory and expose every row of PII (emails, IPs, user agents).
- **Fix:** Add a hard row cap (e.g. ->limit(50000)) and/or paginate the export in chunks using ->chunk(1000, ...) with a running row counter that aborts when a safe maximum is exceeded.
- **Location(s):** `backend/app/Http/Controllers/Admin/AuditLogController.php:115`

### ⚠️ uncertain · [money] deleteUser hard-deletes wallets and transactions without checking non-zero balance
- **Evidence:** DB::transaction(function () use ($user) {
    $user->wallets()->delete();
    $user->cards()->delete();
    $user->transactions()->delete();
    $user->delete();
});
- **Fix:** Wallets with a non-zero balance are destroyed without any reconciliation. This causes irrecoverable financial discrepancy. Check that all wallets have zero balance (and zero pending balance) before proceeding, and reject if not.
- **Location(s):** `backend/app/Http/Controllers/API/AdminController.php:332-337`

### ⚠️ uncertain · [money] Cashback modifies wallet balance using stale in-memory object after debit
- **Evidence:** `creditCashback($sender, $senderWallet, ...)` is called at line 200 with the same `$senderWallet` instance on which `debit()` was already called. Inside `creditCashback`, `$before = (float) $senderWallet->balance` captures the post-debit balance, then `$senderWallet->credit($cb)` adds the cashback and saves. If `save()` in `debit()` reloads the model differently, or if any other code path refreshes the object between calls, the `balance_before` recorded in the cashback transaction will be wrong. Additionally, cashback itself is never checked against a rate cap or max-per-day limit, so a user sending 100k USD receives $1,000 USD cashback on every transfer with no ceiling.
- **Fix:** Reload the wallet with `$senderWallet->fresh()` before recording cashback to ensure accurate snapshot. Add a hard cap on per-transaction and daily cashback (e.g., max $5 USD). Move cashback to a separate post-commit job so it cannot interfere with the atomic debit/credit.
- **Location(s):** `backend/app/Services/TransferService.php:200, 229-261`

### •unverified · [auth] No PIN brute-force protection in the second-factor check ×2
- **Evidence:** `verifyTransactionFactor` calls `$user->verifyPin((string) $request->input('pin'))` with no rate-limiting, lockout, or attempt counter inside this trait. Any caller of this trait exposes unlimited PIN guessing attempts, reducing a 4-6 digit numeric PIN to ~10k-1M guesses with no throttle enforced at the authorization layer.
- **Fix:** Add a rate-limiter keyed to user ID and IP before calling verifyPin, e.g. using Laravel's RateLimiter::tooManyAttempts. Lock the account or require a cooldown after N (e.g. 5) failures. Ensure the attempt count is decremented only on success.
- **Location(s):** `backend/app/Http/Controllers/Concerns/VerifiesTransactionAuth.php:40-41`, `backend/app/Services/PinService.php:13-19`

### •unverified · [auth] saved_amount and completed_at can be mass-assigned, allowing status/balance tampering
- **Evidence:** `saved_amount`, `status`, and `completed_at` are all in `$fillable`. A controller passing `$request->all()` to create/update lets a client inject arbitrary balance values or force a goal into `completed` status without actually depositing money.
- **Fix:** Remove `saved_amount`, `status`, and `completed_at` from `$fillable`. These fields must only be mutated through the deposit/withdraw methods and refreshCompletion, not via mass assignment.
- **Location(s):** `backend/app/Models/SavingsGoal.php:15-26`

### •unverified · [auth] Password reset token has no unique constraint — silent collision possible
- **Evidence:** The `token` column has no UNIQUE index. Multiple rows for the same email can coexist without conflict, and two different users could theoretically receive the same token string if generation has low entropy. Combined with no primary key and no `used_at` flag there is no safe way to invalidate a token after use at the DB level.
- **Fix:** Add ->unique() to the token column (after hashing). Add a `used_at` timestamp column and enforce single-use at the application layer by deleting or nullifying the row immediately after a successful reset.
- **Location(s):** `backend/database/migrations/2026_06_13_125948_create_password_resets_table.php:13`

### •unverified · [auth] Flat boolean `is_admin` role — no RBAC granularity, trivially escalated via mass-assignment
- **Evidence:** $table->boolean('is_admin')->default(false). A single boolean flag controls all administrative capabilities with no permission granularity. If the users model does not guard this field via $guarded/$fillable, any user-update endpoint that accepts arbitrary JSON can flip it. Even with fillable guards, a single field controls full admin access with no audit trail on the flag itself.
- **Fix:** Introduce a roles/permissions table (e.g. Spatie laravel-permission pattern). At minimum add is_admin to the $guarded list explicitly and log all changes to this field in activity_logs.
- **Location(s):** `backend/database/migrations/2026_06_13_160000_add_is_admin_to_users_table.php:12`

### •unverified · [auth] Mass assignment of sensitive user fields including KYC level and status via update()
- **Evidence:** $user->update($validated) passes the entire validated array to update(). The validated array includes kyc_level and status — if the User model's $fillable is broad, or if validation is later relaxed, additional sensitive fields (e.g. password, role) could be mass-assigned. More critically, there is no CSRF or role-scope check beyond whatever middleware is on the route; no confirmation that the acting admin has sufficient privilege over this specific user.
- **Fix:** Use explicit field assignment ($user->kyc_level = $validated['kyc_level']; etc.) rather than passing a bulk array, and ensure role-based guards restrict which admins can alter KYC level vs. only status.
- **Location(s):** `backend/app/Http/Controllers/Admin/UserController.php:80`

### •unverified · [auth] Both $fillable and $guarded=[] set simultaneously, enabling full mass assignment
- **Evidence:** protected $fillable = [...] and protected $guarded = [] are both declared. In Laravel, $guarded = [] takes precedence and disables all guarding, making every column mass-assignable regardless of the $fillable list — including sensitive fields like admin_id, status, sent_count, and failed_count.
- **Fix:** Remove `protected $guarded = [];` entirely and rely solely on the $fillable whitelist, or remove $fillable and use $guarded with specific columns to block.
- **Location(s):** `backend/app/Models/AdminNotification.php:10-23`

### •unverified · [auth] PIN sent in plaintext over API request body
- **Evidence:** deposit() and withdraw() both send 'pin': pin directly as a plain string in the POST body. No hashing or encryption is applied client-side before transmission.
- **Fix:** Hash the PIN with a server-known salt (e.g., HMAC-SHA256) before sending, or use a dedicated secure PIN challenge/response flow. At minimum, ensure HTTPS-only enforcement is verified, but the PIN should never be transmitted in plaintext form even over TLS because it could appear in server logs, API gateways, or proxies.
- **Location(s):** `mobile/lib/features/savings/data/repositories/savings_repository.dart:73-75, 86-88`

### •unverified · [auth] close() endpoint sends no PIN or confirmation token — funds moved without authentication
- **Evidence:** close() calls `_dio.post(ApiConstants.savingsClose(id))` with no credentials beyond the bearer token. The UI flow (savings_page.dart line 501-504) shows only a UI confirmation dialog, no PIN is requested, yet closing a goal returns the full saved balance to the wallet — a money-moving operation.
- **Fix:** Require PIN verification for goal closure, identical to deposit/withdraw. Pass the PIN in the close request body and validate it server-side before moving funds.
- **Location(s):** `mobile/lib/features/savings/data/repositories/savings_repository.dart:97-103`

### •unverified · [auth] user_id, balance, daily_limit, monthly_limit are mass-assignable
- **Evidence:** $fillable includes 'user_id', 'balance', 'available_balance', 'pending_balance', 'daily_limit', 'monthly_limit', 'is_active', 'is_frozen'. If any controller passes unvalidated request input to Wallet::create() or $wallet->fill($request->all()), an attacker can set their own balance, raise their own limits, unfreeze their wallet, or reassign the wallet to another user_id.
- **Fix:** Remove 'user_id', 'balance', 'available_balance', 'pending_balance', 'daily_limit', 'monthly_limit', 'is_active', 'is_frozen' from $fillable. Set user_id explicitly in the controller, and update balance only through the credit/debit methods.
- **Location(s):** `backend/app/Models/Wallet.php:16-30`

### •unverified · [auth] Redis maxmemory-policy allkeys-lru will evict rate-limit and session keys under memory pressure
- **Evidence:** `maxmemory-policy allkeys-lru` evicts any key — including Laravel session keys, OTP attempt counters, and Sanctum tokens — when Redis reaches 256 MB. An attacker can flood Redis with large cache values to force eviction of OTP/rate-limit counters, effectively resetting brute-force protection.
- **Fix:** Use `volatile-lru` so only keys with an expiry are evicted, and ensure session/rate-limit keys have no TTL or are stored in a separate Redis instance/database. Alternatively raise maxmemory and alert before it is reached.
- **Location(s):** `deploy/redis/redis.conf:44`

### •unverified · [auth] OTP brute-force: no attempt limiting in confirmCode()
- **Evidence:** confirmCode() fetches the pending verification and checks the OTP via hash_equals(), but there is no attempt counter, lockout, or rate limiting. A 6-digit OTP has 1,000,000 possible values; an attacker can brute-force it in O(10^6) requests. The OTP is also stored in plaintext in extracted_data JSON column, making it readable if the DB is compromised.
- **Fix:** Track failed attempts on the KycVerification row and invalidate the OTP after N failures (e.g., 5). Apply rate limiting at the controller level. Store OTPs hashed (bcrypt/argon2) rather than plaintext.
- **Location(s):** `backend/app/Services/KycService.php:412-441`

### •unverified · [auth] Missing authorization check in reviewVerification() — any User object accepted as admin
- **Evidence:** reviewVerification(KycVerification $verification, User $admin, ...) accepts any User model as the admin and writes `reviewed_by => $admin->id`. The service itself never verifies that $admin actually has admin/reviewer privileges. If the caller forgets the authorization gate, any authenticated user can approve or reject KYC verifications.
- **Fix:** Add an explicit role/permission check inside the service method (e.g., `if (!$admin->hasRole('kyc_reviewer')) throw new AuthorizationException`), rather than relying solely on the caller.
- **Location(s):** `backend/app/Services/KycService.php:606-650`

### •unverified · [auth] Secure cookie flag has no safe default — session cookie sent over HTTP
- **Evidence:** 'secure' => env('SESSION_SECURE_COOKIE')
- **Fix:** Set a safe default of true: `'secure' => env('SESSION_SECURE_COOKIE', true)`. Without SESSION_SECURE_COOKIE set in .env, the value resolves to null, which Laravel treats as false. This means the session cookie is transmitted over plain HTTP, exposing it to network interception (session hijacking). A fintech app must enforce HTTPS-only cookies unconditionally.
- **Location(s):** `backend/config/session.php:172`

### •unverified · [auth] TOCTOU race: biometric challenge can be consumed concurrently, enabling double-use
- **Evidence:** cache()->get($cacheKey) at line 90 reads the challenge, then signature verification runs (line 100), then cache()->forget($cacheKey) deletes it at line 105. Two concurrent requests carrying the same captured biometric signature can both pass the cache->get check before either reaches cache->forget, allowing the same challenge+signature pair to authorize two separate money-moving operations. There is no atomic compare-and-delete (e.g. cache()->pull() or a Redis GETDEL).
- **Fix:** Replace the get/forget pair with a single atomic pull: `$challenge = cache()->pull($cacheKey);` immediately at line 90. This makes challenge retrieval and deletion a single atomic operation so a replayed concurrent request always finds an empty cache entry.
- **Location(s):** `backend/app/Http/Controllers/Concerns/VerifiesTransactionAuth.php:90-105`

### •unverified · [auth] Biometric lock bypass: authenticated users on /register or /forgot-password skip biometric gate
- **Evidence:** if (isAuthenticated && isAuthRoute) { final bioLock = await authRepo.isBiometricEnabled(); if (!bioLock) return '/dashboard'; return state.matchedLocation.startsWith('/login') ? null : '/login'; } — When biometric lock is enabled and an authenticated user lands on /register or /forgot-password (isAuthRoute=true but not /login), the redirect returns '/login'. However, nothing prevents a user from navigating directly to /register or /forgot-password via a deep link before the redirect fires; the redirect correctly bounces them, but the check only compares startsWith('/login'), so any future auth sub-route added (e.g. /login/sso) would silently pass null (no redirect) and bypass the biometric gate.
- **Fix:** Replace `state.matchedLocation.startsWith('/login') ? null : '/login'` with an explicit allowlist check: only return null if the current location is exactly '/login', otherwise always redirect to '/login'.
- **Location(s):** `mobile/lib/core/router/app_router.dart:79-83`

### •unverified · [auth] NFC payment fallback reads from global provider state, allowing payment recipient substitution
- **Evidence:** final payment = state.extra is NfcPayment ? state.extra as NfcPayment : ref.read(pendingNfcPaymentProvider); return QRSendPage(nfcPayment: payment); — When state.extra is not an NfcPayment (e.g. navigating to /nfc-pay via a deep link or programmatic push without extra), the router silently falls back to whatever NfcPayment is currently stored in pendingNfcPaymentProvider. An attacker or a race condition could pre-populate that provider with a different recipient before the legitimate NFC tap completes, causing funds to be sent to the wrong party.
- **Fix:** Remove the global-state fallback. If state.extra is not a valid NfcPayment, abort navigation (return an error page or pop) rather than reading from ambient provider state.
- **Location(s):** `mobile/lib/core/router/app_router.dart:304-309`

### •unverified · [auth] Biometric auth allows device PIN/password fallback for financial operations
- **Evidence:** `authenticateForCardDetails()` and `authenticateForTransaction()` both call `authenticate(biometricOnly: false)`. With `biometricOnly: false`, the OS-level dialog will fall back to the device PIN/password if biometric fails (including after a single failure). On Android this means an attacker with physical access who knows the device PIN can bypass the biometric gate and authorize financial transactions or view card details without any biometric match.
- **Fix:** Set `biometricOnly: true` for `authenticateForCardDetails` and `authenticateForTransaction`. If PIN fallback is required for accessibility, at minimum require a separate server-side re-authentication step (e.g. OTP/app PIN) instead of trusting the local device PIN as equivalent to biometric proof.
- **Location(s):** `mobile/lib/core/services/biometric_service.dart:71-72, 78-79`

### •unverified · [auth] PIN/password brute-force: no rate-limit or lockout on login form
- **Evidence:** handleSubmit calls onLogin with no submission throttle, attempt counter, or lockout state. A minimum password length of 4 characters (line 33) means the PIN space is tiny (10^4 = 10 000 combinations for digits). Combined with no client-side rate-limiting, an automated attacker can fire unlimited login attempts through the UI.
- **Fix:** Enforce server-side rate-limiting and account lockout (the backend must be the authoritative control), and add a client-side exponential back-off or disabled state after N failed attempts (track failures in component state) to prevent trivially scripted loops.
- **Location(s):** `frontend/src/components/auth/AuthLoginForm.tsx:41-54`

### •unverified · [auth] PIN verified in plaintext for legacy accounts — timing-safe comparison not used
- **Evidence:** `return $this->pin_code === $pin;` — plain string equality is used for legacy PINs. This (a) stores PINs in plaintext in the DB and (b) is vulnerable to timing attacks. Any DB read (logs, backups, admin panel) exposes raw PINs.
- **Fix:** Migrate all legacy PIN hashes on first successful login using `Hash::make($pin)` and save. Remove the plaintext fallback path. Until migration is complete, use `hash_equals($this->pin_code, $pin)` at minimum to prevent timing attacks, but plaintext storage must be eliminated.
- **Location(s):** `backend/app/Models/User.php:217`

### •unverified · [auth] AdminMiddleware::authorize() static method does not exist — defense-in-depth gate crashes or silently fails
- **Evidence:** AdminMiddleware::authorize('secure-file.view') is called as a static method, but AdminMiddleware only defines an instance method handle(). The class has no static authorize() method. This will throw a BadMethodCallException/fatal error at runtime, causing the endpoint to always return a 500 error for legitimate admins, or — depending on error handling configuration — silently skip the gate check entirely.
- **Fix:** Remove the non-existent static call. The route-level 'admin' middleware already enforces authentication. If granular ability checks are needed, use Laravel's Gate::authorize('secure-file.view') or $this->authorize() within the controller, backed by a registered policy/gate definition.
- **Location(s):** `backend/app/Http/Controllers/Admin/SecureFileController.php:49`

### •unverified · [auth] No PIN / 2FA / transaction password re-authentication required for transfer initiation
- **Evidence:** authorize() only checks that a user session exists ('return $this->user() !== null'). No PIN, 2FA code, or step-up auth field is validated in rules(). Any authenticated attacker who steals a bearer token (XSS, leaked token, session fixation) can drain the victim's wallet without any second factor.
- **Fix:** Add a 'pin' or 'otp' field to rules() and verify it against the stored hashed PIN / TOTP in authorize() or a dedicated middleware before the transfer is processed.
- **Location(s):** `backend/app/Http/Requests/Transfer/TransferRequest.php:9-12`

### •unverified · [auth] PIN accepted without rate limiting or brute-force protection at request layer
- **Evidence:** The `pin` field is validated only for format (6 numeric digits). There is no throttle rule, no attempt counter, and no lockout enforced here or in the authorize() method. A 6-digit PIN has 1,000,000 combinations; without rate limiting an attacker can brute-force it programmatically.
- **Fix:** Apply a named rate-limiter (e.g. `RateLimiter::tooManyAttempts`) in authorize() or a middleware keyed on user ID for PIN attempts, locking the account after N failures within a time window.
- **Location(s):** `backend/app/Http/Requests/Wallet/WithdrawRequest.php:24-29`

### •unverified · [auth] Sanctum tokens never expire
- **Evidence:** 'expiration' => null, — tokens have no expiry, so a stolen or leaked bearer token remains valid forever with no server-side revocation enforced by time.
- **Fix:** Set 'expiration' to a finite value in minutes (e.g. 1440 for 24 hours) appropriate for a fintech app, and ensure refresh / re-authentication flows exist.
- **Location(s):** `backend/config/sanctum.php:53`

### •unverified · [auth] Mass assignment of assigned_to allows privilege escalation to any support agent
- **Evidence:** 'assigned_to' is in $fillable — an authenticated user crafting a POST/PUT request can assign their ticket to any user ID, including admins, potentially leaking privileged conversation threads.
- **Fix:** Remove 'assigned_to' from $fillable and only allow it to be set via an admin/staff-only code path.
- **Location(s):** `backend/app/Models/SupportTicket.php:7`

### •unverified · [auth] Admin check trusts unauthenticated user object when auth()->check() short-circuits
- **Evidence:** if (!auth()->check() || !auth()->user()->is_admin) — the condition passes the admin check whenever auth()->check() is true AND is_admin is truthy. However, is_admin is a plain model attribute with no cast defined here; a falsy-but-set value (e.g. 0, '0', empty string) may or may not evaluate correctly depending on DB column type and Eloquent casts. More critically, there is no guard middleware (e.g. 'auth') enforced before this middleware in the route stack — if this middleware is applied without a prior auth middleware, auth()->check() may return true for a cached/session user whose token has been revoked, because no token validity check is performed here.
- **Fix:** Ensure the route group applies 'auth:sanctum' (or the project's guard) before AdminMiddleware so token revocation is enforced. Also cast is_admin to boolean in the User model ($casts = ['is_admin' => 'boolean']) to avoid truthy/falsy ambiguity.
- **Location(s):** `backend/app/Http/Middleware/AdminMiddleware.php:13`

### •unverified · [auth] IP whitelist silently bypassed when debug_mode is set to string 'true' or any truthy DB value
- **Evidence:** verifyWebhookIp() returns true (bypasses IP check) when $debugMode === 'true'. The debug_mode flag comes from integration->settings (user-controlled DB column) or config. If an operator mistakenly enables debug mode in a production integration record, all IP verification is silently skipped.
- **Fix:** Remove the debug_mode bypass from the IP verification path. Debug mode should not override a security control. Use environment-based checks (app()->environment('local')) instead.
- **Location(s):** `backend/app/Services/CCPaymentService.php:508-513`

### •unverified · [auth] Test endpoints unauthenticated — any user can trigger deposit/withdrawal processing in non-production environments
- **Evidence:** testDeposit() and testWithdraw() check app()->environment() but have no authentication middleware. The comment says 'requires authentication' but no auth guard is applied. On staging/testing environments (which often share real wallet data), any unauthenticated caller can submit arbitrary referenceId/orderId/amount and trigger handleDepositWebhook() / handleWithdrawWebhook() directly.
- **Fix:** Add auth:sanctum (or equivalent) middleware to the test routes, and restrict to admin role.
- **Location(s):** `backend/app/Http/Controllers/Webhooks/CCPaymentWebhookController.php:117-157`

### •unverified · [auth] Arbitrary attribute access allows role bypass via dynamic property lookup
- **Evidence:** The `default => $type` branch passes any unrecognized type string directly as an attribute name: `$user->{$attribute} ?? false`. Any User model attribute with a truthy value (e.g. `email`, `name`, `id`, `api_token`) would grant access if used as a middleware argument. There is no whitelist of valid role attributes, so a misconfigured route like `->middleware('check.user:email')` silently passes any authenticated user with a non-empty email.
- **Fix:** Replace the open `default => $type` with an explicit allowlist of valid role attributes. Throw an exception or return 403 for unknown types rather than falling through to dynamic lookup. Example: change the match to only map known roles ('admin' => 'is_admin', 'agent' => 'is_agent', etc.) and add a `default => throw new \InvalidArgumentException("Unknown user type: $type")` to fail closed on unrecognized types.
- **Location(s):** `backend/app/Http/Middleware/CheckUserType.php:33-39`

### •unverified · [auth] Biometric gate silently bypassed when hardware is absent
- **Evidence:** if (!supported) {
  // No biometric hardware — allow the action (no PIN fallback any more).
  return true;
}
- **Fix:** A missing-hardware result must NOT silently grant access to sensitive operations (transfers, card reveals, PIN changes). Either require the user to set up a biometric credential before such operations are accessible, or implement a PIN/password fallback. Returning `true` unconditionally when `isSupported()` is false means any device (emulator, low-end device without biometrics) can bypass the authentication gate entirely.
- **Location(s):** `mobile/lib/core/widgets/biometric_gate.dart:18-21`

### •unverified · [auth] Biometric unlock bypasses authentication — only checks local token presence
- **Evidence:** if (result.success) { context.go('/dashboard'); } — on successful biometric the app navigates to the dashboard without validating the stored token against the server. A revoked or expired token still grants access.
- **Fix:** After successful biometric authentication, call getCurrentUser() (or a lightweight token-validation endpoint) to verify the stored token is still valid before navigating to the dashboard.
- **Location(s):** `mobile/lib/features/auth/presentation/pages/login_page.dart:87-106`

### •unverified · [auth] 2FA dialog disposes controllers before reading the code
- **Evidence:** setSheet(() => isLoading2fa = true);
for (var c in controllers) { c.dispose(); }
for (var f in focusNodes) { f.dispose(); }
Navigator.pop(ctx);
_login(twoFactorCode: getCode()); // getCode() reads disposed controllers
- **Fix:** Capture the code before disposing controllers: final code = getCode(); then dispose, pop, and call _login(twoFactorCode: code).
- **Location(s):** `mobile/lib/features/auth/presentation/pages/login_page.dart:276-281`

### •unverified · [auth] spending_limit, daily_limit, monthly_limit, per_transaction_limit are fillable — limit bypass via mass assignment
- **Evidence:** All four limit fields are in $fillable. An attacker can pass these in a create/update request to set limits to arbitrarily high values, bypassing spending controls entirely.
- **Fix:** Remove limit fields from $fillable and enforce them server-side through dedicated, role-checked setters.
- **Location(s):** `backend/app/Models/VirtualCard.php:27-30`

### •unverified · [auth] Missing X-Device-Id header silently bypasses all device transaction controls
- **Evidence:** if (!$user || !$deviceId) { return $next($request); // no device context — don't break legacy/web }
- **Fix:** Remove or restrict the header-absent bypass. At minimum, require the header for all mobile API routes. If true legacy web clients exist, enforce the check via a separate middleware group rather than silently skipping device validation for everyone who omits the header. A rejected or pending-device user can trivially omit X-Device-Id to transact without restriction.
- **Location(s):** `backend/app/Http/Middleware/EnsureDeviceCanTransact.php:22-24`

### •unverified · [auth] Fabricated or unrecognized device ID bypasses all device transaction controls
- **Evidence:** $device = $user->devices()->where('device_id', $deviceId)->first(); if (!$device) { return $next($request); // unknown device; will register on next launch }
- **Fix:** An unknown device_id should be denied access to money-movement endpoints, not silently allowed through. Replace the pass-through with a 403 denial. Device registration should happen at login/session creation, not be deferred until the next app launch while transactions are permitted in the interim.
- **Location(s):** `backend/app/Http/Middleware/EnsureDeviceCanTransact.php:27-29`

### •unverified · [auth] No rate-limit or resend cooldown on OTP resend buttons
- **Evidence:** The 'إعادة إرسال الرمز' (resend OTP) TextButton for both email and phone has no cooldown, no counter, and no disable-after-N-attempts logic. The catch block silently swallows all errors (`catch (_) {}`), so even a server-side rate-limit error is invisible to the user and the button remains fully pressable, allowing unlimited OTP flood requests.
- **Fix:** Track a resend timestamp and remaining seconds; disable the button for 60 s after each send. Surface errors from the catch block so rate-limit responses are shown to the user.
- **Location(s):** `mobile/lib/features/kyc/presentation/pages/kyc_page.dart:541-543,618-620`

### •unverified · [auth] Device status defaults to 'approved' on missing/null API field
- **Evidence:** status: json['status']?.toString() ?? 'approved',
- **Fix:** Default should be 'pending' (or throw/reject on missing status) so that a device with a missing or null status field is never silently granted approved access. Using 'approved' as the fallback means a malformed API response or a stripped field could make a device appear trusted when it should be held pending.
- **Location(s):** `mobile/lib/features/settings/data/models/device_model.dart:39`

### •unverified · [auth] Dual redundant status columns on users table enable similar bypass
- **Evidence:** $table->enum('status', ['active', 'suspended', 'banned', 'pending'])->default('pending') alongside $table->boolean('is_active')->default(true) — a banned user can have is_active=true; if any auth guard checks only is_active, the ban is bypassed
- **Fix:** Consolidate to a single authoritative status field. Auth middleware must check the enum status column exclusively, not is_active.
- **Location(s):** `backend/database/migrations/2026_06_13_000001_create_users_table.php:38-39`

### •unverified · [auth] NFC payment URI accepted and routed without authentication check
- **Evidence:** _onWarmUri pushes '/nfc-pay' with a payment object immediately on any warm NFC tap without verifying the user is authenticated or past the lock screen. An attacker can tap the device with an NFC tag to trigger the payment flow before the user unlocks the wallet.
- **Fix:** Before calling router.push('/nfc-pay'), check that the user is currently authenticated and the app is in an unlocked state (e.g. ref.read(currentUserProvider) != null && !appLocked). If not authenticated, stash the payment in pendingNfcPaymentProvider (same as the cold-start path) so it is only consumed after the user authenticates.
- **Location(s):** `mobile/lib/main.dart:138-141`

### •unverified · [auth] has_api_access mass-assignable — unauthorized API access grant
- **Evidence:** 'has_api_access' is in $fillable. A merchant could enable their own API access by supplying has_api_access=true in a request that hits a mass-assigning update controller.
- **Fix:** Remove 'has_api_access' from $fillable; set it only through an admin-controlled action.
- **Location(s):** `backend/app/Models/Merchant.php:34`

### •unverified · [auth] Missing authorization on backup download — any admin can exfiltrate full DB
- **Evidence:** download() calls resolveSafePath() and serves the file but never calls AdminMiddleware::authorize(), unlike delete() (line 146) and restore() (line 178) which gate on 'db.delete'/'db.restore'. Any admin role without those specific permissions can still download the full database backup.
- **Fix:** Add AdminMiddleware::authorize('db.download') (or an equivalent permission check) as the first statement in download(), mirroring the pattern used in delete() and restore().
- **Location(s):** `backend/app/Http/Controllers/Admin/DatabaseBackupController.php:131-142`

### •unverified · [auth] PIN brute-force: no rate-limiting or lockout on PIN verification
- **Evidence:** `PinService::verify()` is called on every withdraw request with no attempt counter, no lockout, and no throttle specific to PIN failures. The withdraw endpoint is protected only by the generic API rate-limiter (if any). A 6-digit numeric PIN has only 1,000,000 combinations; an attacker with a valid auth token can enumerate all PINs without restriction.
- **Fix:** Track failed PIN attempts per user in cache (e.g., `pin_attempts:{user_id}`). After N failures (e.g., 5) within a window, lock the endpoint for that user for a cooldown period and return 429. Reset the counter on success.
- **Location(s):** `backend/app/Http/Controllers/API/WalletController.php:192-197`

### •unverified · [auth] PIN verify endpoint has no rate limiting — brute-force PINs
- **Evidence:** Route::post('/pin/verify', [AuthController::class, 'verifyPin']) is inside the auth:sanctum group but inherits only the generic 'throttle:api' rate limit. A 4–6 digit PIN has at most 1,000,000 combinations; at typical API rate limits (60/min) an attacker with a valid session can brute-force all PINs within hours. The card details sensitive endpoint at line 175 also relies on PIN verification.
- **Fix:** Apply a strict per-user rate limit (e.g. throttle:5,1 per minute, with exponential lockout after N failures) specifically on the PIN verify route, matching the same protection applied to OTP routes (throttle:otp).
- **Location(s):** `backend/routes/api.php:75`

### •unverified · [auth] Admin impersonate endpoint — no 2FA or re-authentication step
- **Evidence:** Route::post('/users/{id}/impersonate', [AdminController::class, 'impersonateUser']) is protected only by the 'admin' middleware. If an admin account is compromised (stolen Sanctum token), an attacker can immediately impersonate any user and perform financial operations (transfers, withdrawals) on their behalf with no additional challenge.
- **Fix:** Require a fresh password confirmation or TOTP step before granting impersonation. Log all impersonation sessions with the admin identity for audit. Consider requiring sudo-mode (re-authenticated within the last N minutes).
- **Location(s):** `backend/routes/api.php:354`

### •unverified · [auth] NFC payment dispatched without PIN/biometric re-authentication
- **Evidence:** _maybeHandlePendingNfc() reads pendingNfcPaymentProvider, clears it, and immediately pushes /nfc-pay with the raw NfcPayment object. No PIN prompt, biometric check, or step-up auth is performed at this layer before entering the payment flow.
- **Fix:** Require biometric/PIN re-authentication before dispatching the pending NFC payment to /nfc-pay. Treat NFC-initiated payments the same as any other payment initiation: gate them behind a step-up auth challenge at the shell level before handing off the payload.
- **Location(s):** `mobile/lib/shared/widgets/main_shell.dart:31-37`

### •unverified · [auth] TOTP code replay attack — no used-code invalidation
- **Evidence:** `verifyKey` is called with no window-state tracking. Google2FA's default allows a 30-second window on each side, and nothing records that a code has been used. The same TOTP code can be submitted multiple times within its validity window to authenticate repeatedly.
- **Fix:** Use `verifyKeyNewer` / `verifyKey` with a persisted `$oldTimestamp` argument (Google2FA supports this). Store the last accepted timestamp per user in the database and reject any code whose timestamp is not strictly newer than the stored value.
- **Location(s):** `backend/app/Services/TwoFactorService.php:32-38`

### •unverified · [config] Rate-limit zone redefined in sakk-site.conf — duplicate zone names cause nginx startup failure or silently discards one definition
- **Evidence:** sakk-site.conf lines 12-16 redefines limit_req_zone zones (login, otp, api, transfer, addr) that are already declared identically in rate-limiting.conf. If both files are included at http context, nginx will refuse to start or silently ignore one set, potentially disabling rate limiting entirely on affected endpoints.
- **Fix:** Remove the duplicate limit_req_zone declarations from sakk-site.conf (lines 12-16) and rely solely on rate-limiting.conf, or guard with a comment that only one file should be included.
- **Location(s):** `deploy/nginx/sakk-site.conf:12-16`

### •unverified · [config] iptables rate-limit rules after unconditional ACCEPT — HTTP/S limit rules are dead code
- **Evidence:** Lines 27-28 unconditionally ACCEPT all traffic on ports 80 and 443. Lines 43-44 then add limit rules for the same ports, but iptables processes rules top-to-bottom and the ACCEPT on lines 27-28 matches first, so the rate-limit rules (lines 43-44) are never reached. Every HTTP/S connection bypasses the firewall rate limit.
- **Fix:** Remove or move the unconditional ACCEPT rules (lines 27-28) to appear after the rate-limit rules, or combine them: add `--limit 100/minute --limit-burst 200` directly into the single ACCEPT rules on lines 27-28 and drop the duplicate rules on lines 43-44.
- **Location(s):** `deploy/firewall/iptables-rules.sh:27-28,43-44`

### •unverified · [config] Duplicate migration timestamp causes non-deterministic execution order
- **Evidence:** Both `2026_06_17_000001_add_dual_currency_limits_to_kyc_levels.php` and `2026_06_17_000001_create_payment_requests_table.php` share the identical timestamp prefix `2026_06_17_000001`. Laravel resolves migration order by filename; two files with the same prefix are sorted alphabetically and the `add_dual_currency_limits` migration modifies `kyc_levels` while `create_payment_requests` creates a new table — currently harmless ordering-wise, but any future migration that depends on `payment_requests` existing could fail if re-ordered, and the duplicate timestamp will cause confusion or breakage if either migration is ever rolled back and re-run.
- **Fix:** Rename one file to use a unique timestamp, e.g. `2026_06_17_000002_create_payment_requests_table.php`.
- **Location(s):** `backend/database/migrations/2026_06_17_000001_add_dual_currency_limits_to_kyc_levels.php:1`

### •unverified · [config] CSP script-src allows 'unsafe-inline', defeating XSS protection
- **Evidence:** "script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdn.jsdelivr.net; "
- **Fix:** Replace 'unsafe-inline' with a cryptographic nonce (generated per-request and injected into Blade templates) or precompute hashes of every inline script. The comment acknowledges 'unsafe-inline' was kept for convenience, but in a fintech app this allows any XSS payload to execute arbitrary scripts, bypassing the entire CSP. Similarly review style-src 'unsafe-inline'.
- **Location(s):** `backend/app/Http/Middleware/SecurityHeaders.php:31`

### •unverified · [config] FRONTEND_URL falls back to http://localhost:3000 in production if env var is unset
- **Evidence:** 'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:3000')]
- **Fix:** Remove the default fallback value so a misconfigured production deployment fails closed (empty allowed_origins) rather than silently allowing localhost. Any developer machine running on port 3000 could make credentialed cross-origin requests to the production API if FRONTEND_URL is accidentally omitted from the production environment.
- **Location(s):** `backend/config/cors.php:22`

### •unverified · [config] Stripe test mode defaults to true in production config
- **Evidence:** 'test_mode' => env('STRIPE_TEST_MODE', true),
- **Fix:** Default should be false so that a missing env var does not silently route live payment traffic through Stripe test mode. Change the default to false and ensure STRIPE_TEST_MODE=false is explicitly set in production.
- **Location(s):** `backend/config/services.php:50`

### •unverified · [config] ShamCash simulation mode defaults to true in production config
- **Evidence:** 'simulate' => env('SHAMCASH_SIMULATE', true),
- **Fix:** Default should be false. A missing env var causes all ShamCash deposit/withdrawal operations to run in simulation mode, meaning real funds are never actually moved while the system may record successful transactions.
- **Location(s):** `backend/config/services.php:59`

### •unverified · [crypto] Card number generated with rand() — cryptographically weak, predictable PANs
- **Evidence:** BIN suffix and the 9-digit card body use `rand()` which is not cryptographically secure in PHP. This makes generated PANs and CVVs (rand(100,999)) predictable given knowledge of the seed or timing, violating PCI-DSS requirements for card number generation.
- **Fix:** Use random_int() (CSPRNG) instead of rand() for all card number and CVV generation.
- **Location(s):** `backend/app/Models/VirtualCard.php:110-115`

### •unverified · [data-exposure] activity_logs stores old_values/new_values as JSON — PII and secrets written to logs without redaction
- **Evidence:** $table->json('old_values')->nullable(); $table->json('new_values')->nullable(); — applied to any entity (User, Wallet, Transaction). Password hashes, PIN hashes, KYC document paths, and financial balances will be captured verbatim whenever these entities are updated. The table has no encryption or redaction.
- **Fix:** Implement an allowlist of loggable fields per entity type in the application layer. Never log password, pin, secret, or token fields. For KYC data, log only document type and status — not file paths or extracted OCR data.
- **Location(s):** `backend/database/migrations/2026_06_13_140119_create_system_settings_table.php:31-32`

### •unverified · [data-exposure] system_settings `value` column is plaintext text — API keys and secrets stored unencrypted
- **Evidence:** $table->text('value')->nullable(); — system_settings holds fee rates, limits, and likely third-party API keys/secrets (payment provider, SMS gateway). Storing these in an unencrypted text column means any DB read access exposes all secrets.
- **Fix:** Add a boolean `is_secret` column and encrypt values marked as secret using Laravel's Crypt facade before persisting. Alternatively store secrets in environment variables or a vault service, not in the DB.
- **Location(s):** `backend/database/migrations/2026_06_13_140119_create_system_settings_table.php:14`

### •unverified · [data-exposure] OTP leaked in API response in debug mode — including on production if APP_DEBUG is misconfigured
- **Evidence:** `'code' => (config('app.debug') || app()->runningUnitTests()) ? $code : null` — if APP_DEBUG=true on a staging or production environment (common misconfiguration), the raw OTP is returned in the JSON response to the client, allowing account takeover without SMS/email access.
- **Fix:** Remove the debug code-in-response pattern entirely. Use a separate test seeder or a dedicated test helper that does not bleed into response payloads.
- **Location(s):** `backend/app/Services/KycService.php:335, 388`

### •unverified · [data-exposure] Session encryption disabled by default
- **Evidence:** 'encrypt' => env('SESSION_ENCRYPT', false)
- **Fix:** Change the default to true: `'encrypt' => env('SESSION_ENCRYPT', true)`. In a fintech wallet, session data (user ID, auth state, balance context) is stored in the database/Redis unencrypted unless SESSION_ENCRYPT is explicitly set in .env. If the session store is compromised or misconfigured, session contents are readable in plaintext.
- **Location(s):** `backend/config/session.php:50`

### •unverified · [data-exposure] Sensitive PII logged in plain text on every webhook call
- **Evidence:** `Log::info('CCPayment Deposit Webhook', $payload)` and `Log::info('CCPayment Withdraw Webhook', $payload)` dump the entire raw payload — which can include `fromAddress`, `toAddress`, `txId`, amounts, and any PII fields sent by CCPayment — to the application log without redaction.
- **Fix:** Log only the non-sensitive fields needed for debugging (e.g. recordId, status, chain) and redact addresses/amounts.
- **Location(s):** `backend/app/Services/CCPaymentService.php:396, 445`

### •unverified · [data-exposure] Screen security silently no-ops on plugin missing: card details not protected
- **Evidence:** When `MissingPluginException` is thrown (e.g. plugin not registered in the native project), `_enableSecureFlagFallback()` is called. The comment in that fallback explicitly states: `// This is a limited fallback - doesn't actually prevent screenshots`. The outer `catch (e)` at line 23-24 silently swallows all other errors. Combined, this means card numbers and sensitive financial data are displayed to the user with zero screenshot/recording protection and no error surfaced to the caller.
- **Fix:** Remove the silent `catch (e)` blocks and either throw/rethrow so the UI can block display of sensitive data, or have `enableSecureScreen` return a `bool` indicating whether protection is actually active. If not active, the screen showing card details should refuse to render the sensitive values.
- **Location(s):** `mobile/lib/core/services/screen_security_service.dart:19-24, 36-40`

### •unverified · [data-exposure] Internal exception messages leaked in health check API responses
- **Evidence:** Every check's catch block does `$details = $e->getMessage()` and then the full `$details` value is returned in the JSON response (line 46-49). For `checkDatabase()` this can expose DB hostname, port, username, and database name from PDO DSN errors. For `checkCache()` this can expose Redis connection strings including passwords. All other checks similarly return raw exception text.
- **Fix:** Replace `$details = $e->getMessage()` in catch blocks with a generic message like `$details = 'Service unavailable'` and log the real exception server-side via `Log::error(...)`. Never return raw exception messages in API responses.
- **Location(s):** `backend/app/Http/Controllers/Admin/SystemHealthController.php:77-79, 110-112, 155-157, 194-196, 280-282, 325-327`

### •unverified · [data-exposure] credentials field is mass-assignable, allowing overwrite of API secrets
- **Evidence:** protected $fillable = [..., 'credentials', ...]; — the credentials field (which holds encrypted API keys/secrets per line 26) is listed in $fillable. Any controller that passes unfiltered request input to Integration::create() or $integration->fill() allows an attacker to overwrite integration credentials.
- **Fix:** Remove 'credentials' from $fillable and handle credential writes through an explicit setter method that enforces authorization checks, or use $guarded instead and only allow specific safe fields via mass-assignment.
- **Location(s):** `backend/app/Models/Integration.php:13-18`

### •unverified · [data-exposure] Merchant api_key and api_secret stored in plaintext without index protection
- **Evidence:** $table->string('api_key')->nullable();
$table->string('api_secret')->nullable();
- **Fix:** api_secret must be stored hashed (bcrypt/argon2), never in plaintext. api_key should have a unique index to prevent collision. A plaintext secret in the DB means any SQL dump or read access to the merchants table fully compromises all merchant API credentials.
- **Location(s):** `backend/database/migrations/2026_06_20_000000_create_merchants_table.php:34-35`

### •unverified · [data-exposure] resolveStorageUrl accepts and returns http:// absolute URLs without upgrading to https://
- **Evidence:** if (path.startsWith('http://') || path.startsWith('https://')) return path;

Absolute http:// paths (e.g. avatar or document URLs returned by the server) are passed through unchanged. If the server returns an http:// asset URL the app will fetch avatars, KYC selfies, and address-proof documents over an unencrypted connection.
- **Fix:** Force-upgrade any absolute URL that starts with 'http://' to 'https://' before returning it: return path.startsWith('http://') ? path.replaceFirst('http://', 'https://') : path;
- **Location(s):** `mobile/lib/core/constants/api_constants.dart:17`

### •unverified · [data-exposure] payment_requests.transaction_id uses nullOnDelete — financial audit trail silently destroyed
- **Evidence:** `$table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();` — if a transaction row is ever deleted (soft or hard), the foreign key is set to NULL, permanently severing the link between a payment request and the transaction that fulfilled it. This destroys the audit trail required for financial dispute resolution.
- **Fix:** Change to `->restrictOnDelete()` (or `->cascadeOnDelete()` if cascading makes sense). Transactions in a fintech system should never be deletable; the FK constraint should enforce this.
- **Location(s):** `backend/database/migrations/2026_06_17_000001_create_payment_requests_table.php:24`

### •unverified · [data-exposure] KYC document_number and extracted_data stored and cast in plaintext — PII/sensitive data exposure
- **Evidence:** document_number (passport/ID number), ocr_data, and extracted_data are stored as raw plaintext columns with no encryption. These fields contain government-issued identity numbers and full OCR output (name, DOB, address, nationality). They are cast to array (lines 44–45) and returned directly to callers. If the database is compromised or these fields appear in API responses, logs, or exports, the PII is fully exposed.
- **Fix:** Encrypt these columns at rest using Laravel's built-in encrypted cast: `'document_number' => 'encrypted'`, `'ocr_data' => 'encrypted:array'`, `'extracted_data' => 'encrypted:array'`. This uses the application key for AES-256-CBC encryption transparently. Additionally, ensure these fields are excluded from any API resource serialization unless explicitly needed by a privileged reviewer.
- **Location(s):** `backend/app/Models/KycDocument.php:23,31,32`

### •unverified · [data-exposure] SMS/Email provider credentials stored unencrypted in JSON config column
- **Evidence:** `sms_providers` and `email_providers` tables store provider configuration in a plain `json` config column. These configs will contain API keys, passwords, and authentication tokens for SMS/email gateways stored in plaintext in the database.
- **Fix:** Store provider credentials in an encrypted column (e.g., `$table->text('config_encrypted')`) or reference secrets from a secrets manager. Never store third-party API keys as plaintext JSON in the database.
- **Location(s):** `backend/database/migrations/2026_06_14_100006_create_notification_tables.php:50,63`

### •unverified · [data-exposure] audit_logs stores old_values and new_values as JSON without masking sensitive fields
- **Evidence:** `old_values` and `new_values` are generic `json` columns that will capture full model state changes. For financial models (wallets, cards, users) these columns can contain PAN data, PIN hashes, KYC documents, or balance figures in plaintext, creating a persistent sensitive-data exposure in the audit log.
- **Fix:** Implement a field-level blocklist in the audit logging logic to mask or exclude sensitive attributes (card numbers, PINs, encrypted fields) before writing to `old_values`/`new_values`.
- **Location(s):** `backend/database/migrations/2026_06_14_100008_create_audit_logs_table.php:17-18`

### •unverified · [data-exposure] AuditLog fillable includes sensitive fields that enable mass assignment of audit identity
- **Evidence:** `$fillable` includes `user_id`, `ip_address`, `old_values`, and `new_values`. Any code path that calls `AuditLog::create($request->all())` or passes untrusted input directly will let an attacker forge the auditing user identity (`user_id`), spoof the source IP, or overwrite the recorded before/after values — silently corrupting the audit trail.
- **Fix:** Remove `user_id` and `ip_address` from `$fillable`; always set them explicitly in the service layer from `auth()->id()` and `$request->ip()` respectively, not from user-supplied data. `old_values` and `new_values` should also be excluded if they are derived internally rather than accepted from request input.
- **Location(s):** `backend/app/Models/AuditLog.php:13-24`

### •unverified · [data-exposure] NFC HCE emulation broadcasts user account number without authentication gate
- **Evidence:** final user = ref.read(currentUserProvider);
if (user == null) return;
await NfcHce.startEmulation(Money.accountNumber(user.id));
- **Fix:** Enabling NFC HCE emulation — which broadcasts the account number to any NFC reader in proximity — requires only a UI toggle with no biometric/PIN re-authentication. An attacker with physical access to an unlocked phone can silently enable it. Require biometric or PIN confirmation (same pattern used in _toggleBio) before starting emulation.
- **Location(s):** `mobile/lib/features/settings/presentation/pages/settings_page.dart:285-286`

### •unverified · [data-exposure] Integration credentials stored as unencrypted JSON
- **Evidence:** $table->json('credentials')->nullable(); // encrypted credentials — comment says encrypted but column type is plain json; no Laravel encrypted cast enforced at the schema level, making accidental plaintext storage likely
- **Fix:** Use Laravel's encrypted cast on the Eloquent model for the credentials column and add a DB-level CHECK or application test to confirm the value is never stored unencrypted. API keys and secrets in plaintext in the DB is a critical secret exposure risk.
- **Location(s):** `backend/database/migrations/2026_01_01_000001_create_integrations_table.php:26`

### •unverified · [data-exposure] Integration logs store full request payload and response — potential secret/PII leakage
- **Evidence:** $table->json('payload')->nullable() and $table->json('response')->nullable() — payment provider payloads often contain card numbers, CVVs, bank account details, or API secrets; logging them verbatim creates a secondary exposure surface
- **Fix:** Scrub sensitive fields (card numbers, CVV, credentials, tokens) from payload/response before inserting into integration_logs. Apply an allowlist of safe fields rather than logging everything.
- **Location(s):** `backend/database/migrations/2026_01_01_000004_create_integration_logs_table.php:17-18`

### •unverified · [data-exposure] Email provider credentials exposed in model serialization
- **Evidence:** The `config` field is cast to JSON and listed in `$fillable`, but the model declares no `$hidden` array. Any controller that returns an `EmailProvider` instance (or a collection) as JSON will include the raw `config` payload — which typically contains SMTP passwords, API keys, or other provider secrets — in the HTTP response.
- **Fix:** Add `protected $hidden = ['config'];` to the model, or create a dedicated API resource/transformer that omits or masks credential fields before sending the model to clients.
- **Location(s):** `backend/app/Models/EmailProvider.php:5-6`

### •unverified · [data-exposure] Sensitive card details (PAN + CVV) copied to system clipboard in plaintext
- **Evidence:** `Clipboard.setData(ClipboardData(text: _cardDetails!.cardNumber))` and `Clipboard.setData(ClipboardData(text: _cardDetails!.cvv))` write the full PAN and CVV to the OS clipboard. On Android the clipboard is readable by any app with CLIPBOARD permission; on iOS it is accessible to nearby devices via Universal Clipboard. The 'نسخ جميع التفاصيل' button at line 421 concatenates PAN + CVV + expiry into one string.
- **Fix:** Remove clipboard copy of CVV entirely. For the card number, apply a one-time auto-clear after a short TTL (e.g. using a Timer that calls Clipboard.setData with empty text) and warn the user.
- **Location(s):** `mobile/lib/features/cards/presentation/pages/card_details_page.dart:404, 411, 421`

### •unverified · [data-exposure] Recovery codes stored and compared in plaintext
- **Evidence:** `verifyRecoveryCode` loads raw codes from the database and compares with `array_search($code, $codes)` — a plain-text equality check. If the database is breached, all recovery codes are immediately usable.
- **Fix:** Hash recovery codes with `bcrypt` or `hash('sha256', ...)` at generation time, store the hashes, and use `hash_equals(hash('sha256', $code), $stored_hash)` at verification.
- **Location(s):** `backend/app/Services/TwoFactorService.php:112-114`

### •unverified · [idempotency] Double-submit possible on PayRequestPage: no guard against concurrent _pay() calls
- **Evidence:** Future<void> _pay() async {
  setState(() => _paying = true);
  try {
    await ref.read(paymentRequestRepositoryProvider).pay(widget.uuid);
The button is disabled when _paying==true (line 367: loading: _paying), but _paying is set via setState which is asynchronous — between the button tap and the first frame with loading:true, a second tap can pass through and call _pay() again, triggering a second POST to paymentRequestPay(uuid).
- **Fix:** Introduce a synchronous bool flag (e.g. _payInFlight) set before the first await and checked at the top of _pay() with an early return if already true: if (_payInFlight) return; _payInFlight = true;
- **Location(s):** `mobile/lib/features/transfer/presentation/pages/pay_request_page.dart:52-65`

### •unverified · [idempotency] Double-submit possible on ReceivedRequestsPage accept/reject: _busyUuid set via setState, not a synchronous guard
- **Evidence:** Future<void> _accept(Map<String, dynamic> r) async {
  final uuid = r['uuid'].toString();
  setState(() => _busyUuid = uuid);
  try {
    await ref.read(paymentRequestRepositoryProvider).accept(uuid);
Same async-setState race: a rapid double-tap calls accept() twice before the first setState() rebuild disables the button. Two money-moving POSTs are sent to the server.
- **Fix:** Use a synchronous lock: if (_busyUuid != null) return; _busyUuid = uuid; (without setState for the guard itself). Or use a Set<String> _inFlight and check before setState.
- **Location(s):** `mobile/lib/features/transfer/presentation/pages/received_requests_page.dart:78-93`

### •unverified · [idempotency] No idempotency / replay protection on any webhook event
- **Evidence:** The controller decodes the Stripe event (line 37-44) and dispatches it immediately. The Stripe event id (event['id']) is logged (line 43) but never stored or checked. Stripe guarantees at-least-once delivery, so any event (authorization.request, transaction.created, etc.) can be delivered multiple times. There is no processed-events table or cache check. For non-idempotent handlers (handleAuthorizationCapture, handleAuthorizationReversal) a replay causes double debit or double refund.
- **Fix:** Store the Stripe event id in a processed_webhook_events table inside the handler's DB transaction. Before processing, check if the event id already exists and return 200 early if so.
- **Location(s):** `backend/app/Http/Controllers/Webhooks/StripeIssuingWebhookController.php:37-71`

### •unverified · [idempotency] Referral reward granted inside model `updated` hook without DB transaction or idempotency guard
- **Evidence:** The `static::updated` observer calls `ReferralService::grantOnKycVerified($user)` whenever `kyc_status` changes to VERIFIED. There is no lock or idempotency check visible here. If the KYC status update is retried (e.g. webhook replay, double-save) or if `grantOnKycVerified` itself triggers another `save()`, the reward could be granted multiple times.
- **Fix:** Add an idempotency column (e.g. `referral_rewarded_at`) checked inside `grantOnKycVerified`, and wrap the reward grant in a DB transaction with a `lockForUpdate()` on the referrer's wallet row to prevent double-spend.
- **Location(s):** `backend/app/Models/User.php:103-109`

### •unverified · [idempotency] Gold transaction status defaults to 'completed' — no pending state, idempotency gap
- **Evidence:** $table->string('status', 20)->default('completed'); // pending, completed, failed, cancelled
- **Fix:** Defaulting to 'completed' means any insert that fails mid-flow (e.g., balance update fails after row insert) leaves a completed record with no actual settlement. Default should be 'pending'; application code transitions to 'completed' only after all balance mutations succeed atomically.
- **Location(s):** `backend/database/migrations/2026_06_20_300000_create_gold_transactions_table.php:23`

### •unverified · [idempotency] savings_transactions status defaults to 'completed' — same idempotency/consistency gap as gold_transactions
- **Evidence:** $table->string('status')->default('completed');
- **Fix:** Default to 'pending'. Mark completed only after saved_amount on savings_goals is updated atomically within the same DB transaction.
- **Location(s):** `backend/database/migrations/2026_06_21_100000_create_savings_goals_table.php:38`

### •unverified · [idempotency] Missing idempotency key — duplicate transfer submissions are not guarded at the request layer
- **Evidence:** rules() contains no idempotency_key field. Without it, network retries or double-clicks result in duplicate transfer rows being inserted; the only safeguard (if any) would need to live in the controller/service, which is not enforced here.
- **Fix:** Add an 'idempotency_key' => ['required','string','uuid'] rule and enforce uniqueness per user in the controller using a DB unique index or cache lock keyed on (user_id, idempotency_key).
- **Location(s):** `backend/app/Http/Requests/Transfer/TransferRequest.php:14-43`

### •unverified · [idempotency] No idempotency key — duplicate transfer requests cause double debits
- **Evidence:** The `transfer()` method has no idempotency mechanism. If a client retries on network timeout (common in mobile apps), or if the calling controller is invoked twice, two full transfers execute. There is no idempotency key parameter, no deduplication check, and no unique constraint on the transaction table to detect replays.
- **Fix:** Accept a client-supplied idempotency key, store it in the `transactions` table with a unique index, and return the existing result if a duplicate key is detected within a configurable window.
- **Location(s):** `backend/app/Services/TransferService.php:86-226`

### •unverified · [idempotency] No idempotency key on buy/sell — double-charge on network retry
- **Evidence:** _dio.post(ApiConstants.goldBuy, data: {'karat':karat,'grams':grams,'biometric_token':biometricToken}) — no idempotency-key header or request-id field
- **Fix:** Generate a UUID per submission attempt (before the POST) and include it as an Idempotency-Key header or body field. The backend must reject or de-duplicate duplicate submissions with the same key within a time window, preventing a user being charged twice if the connection drops after the server commits but before the client receives the response.
- **Location(s):** `mobile/lib/features/gold/data/repositories/gold_repository.dart:77-85, 96-104`

### •unverified · [idempotency] No idempotency key on crypto withdraw — duplicate submission on network retry
- **Evidence:** dio.post('/ccpayment/withdraw', data: {...}) sends no idempotency key. If the user taps the button while the network is slow, or if a timeout triggers a retry, the POST can be sent twice, resulting in duplicate withdrawals. The button is disabled by _isLoading but only after the first setState; a rapid double-tap before the first setState completes can slip through.
- **Fix:** Generate a UUID idempotency key when the page initialises (or when the form is first shown) and include it in every submit: 'idempotency_key': _idempotencyKey. The server must honour it. Also guard the button with a mounted-check before the second setState.
- **Location(s):** `mobile/lib/features/wallets/presentation/pages/crypto_withdraw_page.dart:100-107`

### •unverified · [idempotency] Webhook replay attack: no idempotency guard on authorization handling
- **Evidence:** handleAuthorizationRequest() does not check whether a transaction for this authorization_id already exists before creating one. A replayed or duplicate issuing_authorization.request webhook creates a second PROCESSING transaction and reserves funds a second time for the same authorization.
- **Fix:** At the start of the DB transaction, check Transaction::where('metadata->authorization_id', $authId)->exists() and decline/return early if a record already exists for this auth ID.
- **Location(s):** `backend/app/Services/StripeIssuingService.php:361-486`

### •unverified · [idempotency] No idempotency key on chargeFee / addReward — duplicate transactions on retry
- **Evidence:** Neither chargeFee() nor addReward() accepts or checks an idempotency key. A network timeout causing a caller retry will create duplicate fee/reward transactions and double-debit/credit the wallet.
- **Fix:** Accept an optional idempotency key, check for an existing Transaction with that key before proceeding, and return the existing record if found.
- **Location(s):** `backend/app/Services/TransactionService.php:18-70`

### •unverified · [idor] user_id is mass-assignable, enabling IDOR via forged ownership
- **Evidence:** `user_id` is listed in `$fillable`. Any controller that passes unfiltered request input to SavingsGoal::create() or $goal->fill() lets an attacker supply an arbitrary user_id, creating or re-assigning a goal to another user's account.
- **Fix:** Remove `user_id` from `$fillable` and set it explicitly in the controller: `$goal->user_id = auth()->id();` before saving.
- **Location(s):** `backend/app/Models/SavingsGoal.php:15-26`

### •unverified · [idor] Deposit and withdraw use integer goal ID (not UUID) in URL — potential IDOR if server trusts client ID without ownership check
- **Evidence:** deposit(int id, ...) and withdraw(int id, ...) pass a sequential integer `id` to ApiConstants.savingsDeposit(id) / savingsWithdraw(id). The model also contains a `uuid` field (line 12 of savings_models.dart) which is the safer reference. Using a sequential int exposes goal IDs to enumeration — an attacker can probe other users' goals if the server does not enforce ownership.
- **Fix:** Pass `uuid` instead of `id` to all mutating endpoints (deposit, withdraw, close). Update ApiConstants to accept the UUID. On the server, always verify that the authenticated user owns the referenced goal before performing any operation.
- **Location(s):** `mobile/lib/features/savings/data/repositories/savings_repository.dart:65, 84`

### •unverified · [idor] getModelAudit() exposes all users' audit records with no ownership or role check
- **Evidence:** getModelAudit(modelType, modelId) fetches all audit rows for any model without checking whether the caller owns that model or has an admin role. Any caller that can supply a modelType+modelId can read the full change history (old_values, new_values, user_id) of any other user's wallet, card, or transaction.
- **Fix:** Add an ownership check (verify the model belongs to the requesting user) or restrict this method to admin/internal roles only. Never expose cross-user audit data without authorization.
- **Location(s):** `backend/app/Services/AuditLogService.php:186-202`

### •unverified · [idor] No IDOR protection on KycVerification passed to reviewVerification()
- **Evidence:** The method receives a KycVerification model via route-model binding or direct instantiation without validating that the verification belongs to the target user or any scoping. If the controller resolves verifications by ID without ownership scope, an admin-level attacker (or a bug in the controller) could review any user's verification record.
- **Fix:** Scope the verification lookup to the owning user at the controller level, or assert ownership inside the service.
- **Location(s):** `backend/app/Services/KycService.php:606`

### •unverified · [idor] Gold price update has no authorization/ownership check — any admin can overwrite any GoldPrice record
- **Evidence:** update(Request $request, GoldPrice $goldPrice) performs no policy/gate check before calling $goldPrice->update($validated). If multiple admin roles exist (e.g., read-only admin vs. super-admin), a lower-privileged admin can POST to /admin/gold-prices/{any_id} and alter live gold prices, directly affecting all user buy/sell transactions.
- **Fix:** Add $this->authorize('update', $goldPrice) (Policy) or a middleware gate check scoped to the role that is permitted to change live prices.
- **Location(s):** `backend/app/Http/Controllers/Admin/GoldPriceController.php:28-44`

### •unverified · [idor] No wallet ownership check — any authenticated user can deposit to any wallet
- **Evidence:** `authorize()` only checks `$this->user() !== null` (line 11). If the route accepts a wallet ID parameter (e.g. `/wallets/{wallet}/deposit`), this request class performs no verification that the authenticated user owns that wallet. Any authenticated user can deposit to any wallet ID.
- **Fix:** In `authorize()`, resolve the target wallet from the route and verify ownership: `return $this->user()->id === $this->route('wallet')->user_id;`
- **Location(s):** `backend/app/Http/Requests/Wallet/DepositRequest.php:9-12`

### •unverified · [idor] QR/NFC scan accepts arbitrary UUID as pay-request without ownership or authenticity check
- **Evidence:** paymentUuidFrom() accepts any bare UUID or URL matching /pay/{uuid} and navigates to /pay-request/$payUuid (line 155). An attacker can craft a QR code with a victim's pay-request UUID (or enumerate sequential/predictable UUIDs) and cause the scanning user to pay into a payment request they did not intend. The scanner page returns the raw scanned string without validation, and no ownership/authentication check is shown here before routing.
- **Fix:** The /pay-request/:uuid route must verify on the server that the UUID belongs to a real, non-expired, uncompleted request before presenting it for payment. The client should also show the full originator identity before any confirm action.
- **Location(s):** `mobile/lib/features/qr/presentation/pages/qr_send_page.dart:132-158`

### •unverified · [idor] Agent detail fetched by internal integer ID — IDOR risk
- **Evidence:** agentDetailProvider takes a plain `int id` and calls `ApiConstants.agentById(id)` — the same integer the client received in the list response. Any authenticated user can increment/alter this ID to enumerate every agent record. The model already has a `uuid` field that should be used instead for public-facing lookups.
- **Fix:** Change the detail endpoint to accept the agent's UUID (`agentDetailProvider<String, uuid>`) and update `agentById` to use the UUID path segment. The server must enforce that the UUID lookup is authorised before returning sensitive fields (ownerName, phone, commissionRate).
- **Location(s):** `mobile/lib/features/agents/data/repositories/agent_repository.dart:27-29, 62-66`

### •unverified · [idor] authorize() only checks authentication, not wallet ownership
- **Evidence:** `authorize()` returns `true` for any authenticated user (`$this->user() !== null`). If the withdrawal controller accepts a wallet_id or account identifier from the request body, any authenticated user can attempt to withdraw from another user's wallet — this request class provides no ownership gate.
- **Fix:** Add an ownership check in authorize(): resolve the target wallet from the request, then verify `$wallet->user_id === $this->user()->id` before returning true.
- **Location(s):** `backend/app/Http/Requests/Wallet/WithdrawRequest.php:9-12`

### •unverified · [idor] Mass assignment of user_id allows ticket ownership spoofing
- **Evidence:** protected $fillable = ['uuid', 'user_id', ..., 'assigned_to', ...]  — both user_id and assigned_to are mass-assignable
- **Fix:** Remove 'user_id' and 'assigned_to' from $fillable. Set user_id explicitly in the controller from Auth::id(), and set assigned_to only through a dedicated privileged endpoint/service method, never from raw request input.
- **Location(s):** `backend/app/Models/SupportTicket.php:7`

### •unverified · [idor] user_id in $fillable enables mass-assignment of device ownership
- **Evidence:** 'user_id' is listed in $fillable (line 21). Any controller that calls Device::create($request->all()) or $device->fill($request->all()) allows an attacker to set an arbitrary user_id, reassigning a device to another user's account.
- **Fix:** Remove 'user_id' from $fillable. Set user_id only explicitly in controller logic (e.g., auth()->id()), never from user-supplied input.
- **Location(s):** `backend/app/Models/Device.php:21`

### •unverified · [idor] No ownership/wallet existence check — any authenticated user can request a conversion regardless of whether they hold the specified currency wallet
- **Evidence:** authorize() returns true for any non-null user. There is no check that the authenticated user actually owns a wallet with 'from_currency'. Ownership enforcement is deferred entirely to the controller/service, where it may be missing or inconsistent.
- **Fix:** Add an ownership validation rule or authorization check in authorize() that verifies the authenticated user has a wallet with the requested from_currency, e.g.: $wallet = $this->user()->wallets()->where('currency', $this->from_currency)->first(); return $wallet !== null;
- **Location(s):** `backend/app/Http/Requests/Wallet/ConvertRequest.php:9-12`

### •unverified · [idor] Mass assignment exposes user_id and gold_wallet_id — IDOR on transaction ownership
- **Evidence:** 'user_id' and 'gold_wallet_id' are both in $fillable. If a controller passes request()->all() or any unsanitized input to create/fill, an attacker can forge ownership and link a transaction to another user's wallet.
- **Fix:** Remove 'user_id' and 'gold_wallet_id' from $fillable. Always set them explicitly from the authenticated session (e.g. auth()->id()) inside the controller or service, never from request input.
- **Location(s):** `backend/app/Models/GoldTransaction.php:12-13`

### •unverified · [idor] user_id is mass-assignable — IDOR risk via bulk-assignment
- **Evidence:** `user_id` appears in $fillable (line 12). If any controller passes unvalidated request data to GoldWallet::create() or $wallet->fill(), an attacker can supply an arbitrary user_id and take ownership of another user's wallet or create one attributed to a victim.
- **Fix:** Remove user_id from $fillable and assign it explicitly: $wallet->user_id = auth()->id() (or equivalent) before save.
- **Location(s):** `backend/app/Models/GoldWallet.php:11-19`

### •unverified · [idor] provider_card_id and provider_data are fillable — external provider linkage can be overwritten
- **Evidence:** 'provider_card_id' and 'provider_data' are in $fillable. An attacker who can reach a mass-assignment code path could repoint a card's provider reference to another user's provider card, hijacking payment processing.
- **Fix:** Remove provider_card_id and provider_data from $fillable; set them only in trusted service-layer code.
- **Location(s):** `backend/app/Models/VirtualCard.php:41-42`

### •unverified · [idor] Mass-assignable `user_id` and `requestee_id` enable IDOR on payment request ownership
- **Evidence:** 'user_id' and 'requestee_id' are in $fillable. An attacker can create or update a payment request impersonating another user as requester or requestee if the controller passes request()->all() or similar to create()/fill().
- **Fix:** Remove 'user_id' and 'requestee_id' from $fillable. Set them explicitly in the controller from the authenticated session (auth()->id()) after authorization checks.
- **Location(s):** `backend/app/Models/PaymentRequest.php:12-13`

### •unverified · [idor] authorize() only checks authentication, not card ownership
- **Evidence:** public function authorize(): bool { return $this->user() !== null; } — any authenticated user is authorized. There is no check that the card being loaded belongs to the requesting user.
- **Fix:** In authorize(), resolve the target card from the route parameter and verify it belongs to the authenticated user: e.g. return $this->user()->cards()->where('id', $this->route('card'))->exists();
- **Location(s):** `backend/app/Http/Requests/Card/LoadCardRequest.php:9-12`

### •unverified · [idor] authorize() performs no ownership check — IDOR on card update
- **Evidence:** public function authorize(): bool { return $this->user() !== null; } — any authenticated user is authorized; no check that the card in the route belongs to the requesting user.
- **Fix:** Resolve the card from the route parameter and verify ownership: e.g. `return $this->user()->cards()->where('id', $this->route('card'))->exists();`
- **Location(s):** `backend/app/Http/Requests/Card/UpdateCardRequest.php:9-12`

### •unverified · [injection] Sort direction not validated — allows arbitrary SQL injection via ORDER BY
- **Evidence:** $sortDir = $request->get('dir', 'desc'); ... ->orderBy($sortField, $sortDir) — sortDir is passed directly to orderBy() without any whitelist check. Laravel's orderBy passes the direction verbatim to the SQL engine; passing a crafted string (e.g. `desc; DROP TABLE ...`) can cause SQL errors or, on some drivers, injection.
- **Fix:** Add a whitelist check: if (!in_array($sortDir, ['asc', 'desc'])) { $sortDir = 'desc'; } immediately after line 25.
- **Location(s):** `backend/app/Http/Controllers/Admin/MerchantController.php:25,45`

### •unverified · [injection] .env injection via unescaped APP_URL written to .env file
- **Evidence:** `preg_replace('/APP_URL=.*/', 'APP_URL=' . $validated['app_url'], $envContent)` — APP_URL passes Laravel's `url` rule but a value like `http://x.com\nMAIL_PASSWORD=hacked` injects a new .env line. The `url` rule does not reject embedded newlines.
- **Fix:** Strip or reject newlines from the validated URL before substitution, and wrap the value in quotes in the .env line.
- **Location(s):** `backend/app/Http/Controllers/InstallerController.php:192`

### •unverified · [kyc] KYC level limits stored as decimal(18,2) while transaction amounts use decimal(18,8) — precision mismatch enables limit bypass
- **Evidence:** daily_limit, monthly_limit, single_transaction_limit, withdrawal_limit are all decimal(18,2). Transaction amounts (transactions.amount, net_amount, balance_before, balance_after) are decimal(18,8). When application code compares an 18,8-precision transaction amount against a 2-decimal limit, rounding or truncation differences can allow amounts that exceed the limit by fractions to slip through the check.
- **Fix:** Use the same precision for all monetary columns — decimal(18,8) — so comparisons are exact. Alternatively enforce limits in the smallest indivisible unit (integer cents/satoshis) stored as BIGINT.
- **Location(s):** `backend/database/migrations/2026_06_14_100003_create_kyc_levels_tables.php:19-22`

### •unverified · [kyc] Admin can directly set KYC level without audit trail or validation of prior state
- **Evidence:** 'kyc_level' => 'required|integer|min:0|max:3' then $user->update($validated) — any admin can freely set kyc_level to any value (0–3) including downgrading from a verified level or upgrading to full KYC without any document verification check, gate check, or audit log entry.
- **Fix:** KYC level changes must verify that required documents/checks exist for the target level, must never allow direct downgrades that would bypass compliance, and must write an immutable audit log entry (who changed, from, to, when) before persisting.
- **Location(s):** `backend/app/Http/Controllers/Admin/UserController.php:61,80`

### •unverified · [kyc] $fillable and $guarded=[] conflict allows full mass assignment
- **Evidence:** The model defines both `$fillable` with specific fields AND `$guarded = []`. Setting `$guarded = []` disables all guarding entirely, overriding `$fillable`. This means every column on the table — including `status`, `reviewed_by`, `reviewed_at`, and `level` — can be mass-assigned from user-supplied input if create()/fill()/update() is called with request data.
- **Fix:** Remove the `protected $guarded = [];` line entirely. The `$fillable` array is the correct guard; having both is contradictory and `$guarded = []` wins, making `$fillable` ineffective. After removing it, also audit callers to ensure `status` and `reviewed_by` are never passed directly from request input.
- **Location(s):** `backend/app/Models/KycVerification.php:10-24`

### •unverified · [kyc] Non-atomic KYC approval in update/updateAjax: race condition allows double-upgrade
- **Evidence:** update() and updateAjax() check `$request->status === 'approved' && $oldStatus !== 'approved'` and then update both the KycVerification and the user's kyc_level/kyc_status, but all of this runs outside a DB transaction. Two concurrent admin requests can both read oldStatus='pending', both pass the check, and both elevate the user's KYC level.
- **Fix:** Wrap the entire read-check-update sequence in DB::transaction() with a pessimistic lock (KycVerification::lockForUpdate()->findOrFail($id)) — the same pattern already used in the dedicated approve() method.
- **Location(s):** `backend/app/Http/Controllers/Admin/KycController.php:98-120`

### •unverified · [kyc] KYC status check is not race-condition-safe in approve/approveAjax (no pessimistic lock)
- **Evidence:** approve() and approveAjax() check `$kyc->status !== 'pending'` before opening a DB::transaction(). Two simultaneous requests both read status='pending', both enter the transaction, and both call $kyc->update([…'approved'…]) and $kyc->user->update([kyc_level …]). The DB transaction prevents partial writes but does not prevent the double-promotion because the guard read happens outside the lock.
- **Fix:** Move the status guard inside the transaction and add a SELECT … FOR UPDATE: `$kyc = KycVerification::lockForUpdate()->findOrFail($kyc->id); if ($kyc->status !== 'pending') { … abort … }`
- **Location(s):** `backend/app/Http/Controllers/Admin/KycController.php:125-154, 237-263`

### •unverified · [kyc] AML flags cascade-delete on transaction delete destroys compliance audit trail
- **Evidence:** `$table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();` — if a transaction is ever deleted or soft-deleted and the FK is honoured, all associated AML flags are silently wiped. The migration comment itself says 'No soft-deletes: rejected flags must remain visible for compliance trails', directly contradicting `cascadeOnDelete`.
- **Fix:** Change to `->restrictOnDelete()` (block deletion of a transaction that has AML flags) or `->nullOnDelete()` with `transaction_id` made nullable, to preserve the compliance record.
- **Location(s):** `backend/database/migrations/2026_06_24_000002_create_aml_flags_table.php:34`

### •unverified · [kyc] AML flags user_id cascade-delete also destroys compliance records
- **Evidence:** `$table->foreignId('user_id')->constrained('users')->cascadeOnDelete();` — deleting or anonymising a user cascades and removes their AML flags. Compliance regulations (FATF, local AML law) require AML records to be retained for 5+ years regardless of account status.
- **Fix:** Change to `->restrictOnDelete()` or store a copy of the user identifier in a denormalised column before allowing user deletion. Never cascade-delete AML records.
- **Location(s):** `backend/database/migrations/2026_06_24_000002_create_aml_flags_table.php:35`

### •unverified · [kyc] extracted_data accepts arbitrary nested array without depth/size constraints — PII mass-assignment risk
- **Evidence:** 'extracted_data' => ['nullable', 'array'], — no max-size, no allowed-key whitelist, no depth limit. An admin (or a compromised admin token) can push arbitrary nested structures into the KYC record, potentially overwriting sensitive extracted PII fields (e.g. document numbers, face-match scores) with attacker-controlled values, bypassing the integrity of the automated KYC pipeline.
- **Fix:** Whitelist the specific keys allowed inside extracted_data (e.g. 'extracted_data.name', 'extracted_data.dob', etc.) with explicit type/format rules, and add a max:N rule on the array itself. Never accept a freeform nested array for a field that controls KYC trust data.
- **Location(s):** `backend/app/Http/Requests/Admin/UpdateKycRequest.php:20`

### •unverified · [kyc] Agent `is_verified` defaults to true — new agents bypass verification
- **Evidence:** `$table->boolean('is_verified')->default(true);` — every newly created agent record is immediately marked verified. Any code path that checks `is_verified` before allowing cash-in/cash-out will pass for unreviewed agents, effectively bypassing the agent vetting process.
- **Fix:** Change default to `false`: `$table->boolean('is_verified')->default(false);` so agents must be explicitly approved before being trusted.
- **Location(s):** `backend/database/migrations/2026_06_19_130000_create_agents_table.php:43`

### •unverified · [kyc] Mass assignment protection nullified by empty $guarded with $fillable already defined
- **Evidence:** protected $guarded = []; is set alongside a $fillable list. In Laravel, $guarded = [] disables all mass-assignment protection entirely — every column, including sensitive ones like status, verified_by, verified_at, rejection_reason, ocr_data, and extracted_data, becomes writable via mass assignment regardless of the $fillable array (Laravel resolves fillability as: if $guarded is empty, all fields are unguarded). An attacker who can reach any endpoint that creates/updates a KycDocument via a request payload can set status to 'approved', or overwrite verified_by / verified_at to spoof a completed review.
- **Fix:** Remove the `protected $guarded = [];` line entirely. With $fillable already defined, $guarded is redundant and actively harmful — its presence as an empty array overrides $fillable protection. Alternatively, move sensitive administrative fields (status, verified_by, verified_at, rejection_reason, ocr_data, extracted_data) out of $fillable and assign them only through explicit attribute setters or direct property assignment in trusted service code.
- **Location(s):** `backend/app/Models/KycDocument.php:35`

### •unverified · [kyc] KYC bypass: selfie submission not gated on prior id_document approval
- **Evidence:** actionable = !done || rejected — the selfie tile is tappable (actionable=true) whenever the selfie is not yet 'approved', regardless of whether id_document has been submitted or approved. A user can jump directly to submitting a selfie without completing the id_document step, potentially bypassing the document-verification requirement for the higher KYC level.
- **Fix:** Gate _selfieSheet() (and the selfie tile's onTap) so it is only actionable when _isDone('id_document') is true. Add a client-side guard: if (!_isDone('id_document')) { _snack(...); return; }
- **Location(s):** `mobile/lib/features/kyc/presentation/pages/kyc_page.dart:361`

### •unverified · [kyc] Mass assignment of KYC verification fields allows privilege escalation
- **Evidence:** `$fillable` includes `status`, `verified_by`, `verified_at`, and `rejection_reason`. Any controller that calls `AgentDocument::create($request->all())` or `$doc->fill($request->all())` lets an agent (or API caller) self-approve their own document by injecting these fields in the request payload.
- **Fix:** Remove `status`, `verified_by`, `verified_at`, and `rejection_reason` from `$fillable`. These fields must only be written by explicit, privilege-checked assignments in admin/back-office controllers, never through mass-assignment from user-supplied input.
- **Location(s):** `backend/app/Models/AgentDocument.php:13-29`

### •unverified · [kyc] KYC verification fields mass-assignable — status and verified_by can be set by untrusted input
- **Evidence:** Fields 'status', 'verified_by', and 'verified_at' are all listed in $fillable. Any controller that passes user-supplied input directly to MerchantDocument::create() or $doc->update($request->all()) allows an attacker to self-approve their own KYC document by supplying status=approved, verified_by=<admin_id>, verified_at=<timestamp> in the request payload.
- **Fix:** Remove 'status', 'verified_by', 'verified_at', and 'rejection_reason' from $fillable. These fields must only be written by dedicated admin/reviewer actions that set them explicitly (e.g., $doc->status = 'approved'; $doc->verified_by = auth()->id(); $doc->save()), never through bulk-assignment from request data.
- **Location(s):** `backend/app/Models/MerchantDocument.php:13-29`

### •unverified · [kyc] KYC sensitive data stored as raw JSON without encryption marker
- **Evidence:** $table->json('kyc_data')->nullable(); — KYC data (passport numbers, ID scans, DOB, addresses) stored as plain JSON with no encryption annotation or cast
- **Fix:** KYC PII must be encrypted at rest. Use Laravel's encrypted cast or application-level encryption. Add a clear schema comment and enforce an encrypted cast on the Eloquent model.
- **Location(s):** `backend/database/migrations/2026_06_13_000001_create_users_table.php:35`

### •unverified · [kyc] KYC bypassed when all documents are approved then re-rejected
- **Evidence:** approve() promotes the merchant to kyc_status='approved' / is_verified=true as soon as pending===0 && rejected===0. reject() later sets kyc_status='documents_required' but never resets is_verified to false (line 82-85). A merchant whose last document is approved (gaining is_verified=true) and then later rejected will retain is_verified=true indefinitely, bypassing KYC.
- **Fix:** In reject(), also set is_verified=false and clear kyc_approved_at on the merchant record alongside resetting kyc_status.
- **Location(s):** `backend/app/Http/Controllers/Admin/MerchantDocumentController.php:57-64`

### •unverified · [kyc] KYC status freely writable by any admin — bypasses KYC verification workflow
- **Evidence:** 'kyc_status' => ['sometimes', 'string', 'in:pending,submitted,verified,rejected'] — any admin can flip kyc_status to 'verified' with no audit trail or workflow gate enforced at this layer, allowing KYC bypass.
- **Fix:** Move kyc_status mutations to a dedicated KYC admin endpoint with explicit audit logging, required reason/notes fields, and ideally a separate permission (e.g. kyc_manager role). At minimum log the change with before/after values and actor ID.
- **Location(s):** `backend/app/Http/Requests/Admin/UpdateUserRequest.php:26`

### •unverified · [logic] Audit log status always recorded as 'completed' regardless of actual outcome
- **Evidence:** logWalletTransaction() and logCardTransaction() hardcode status: 'completed' — they never accept or forward a status parameter. If a caller invokes these helpers after a failed operation, the audit trail will falsely record the event as successful, defeating compliance forensics.
- **Fix:** Add a $status parameter to logWalletTransaction() and logCardTransaction() (defaulting to 'completed') and pass it through to log().
- **Location(s):** `backend/app/Services/AuditLogService.php:85, 111`

### •unverified · [logic] logTransfer() records modelId as 0 with status 'completed' before the transaction exists
- **Evidence:** modelId is hardcoded to 0 ('Will be updated later') but the audit record is never updated — there is no update path in this service. The log is also stamped status 'completed' at creation time, before the transfer is committed, so a subsequent failure leaves a false 'completed' record tied to no real transaction.
- **Fix:** Either pass the real transaction ID after the transfer is persisted, or write the log as 'pending' and update it on success/failure. Never emit a 'completed' record before the operation is confirmed.
- **Location(s):** `backend/app/Services/AuditLogService.php:128-139`

### •unverified · [logic] toggleFreeze compares backed enum to raw string — freeze/unfreeze logic is inverted
- **Evidence:** `$card->status === 'frozen'` compares a `CardStatus` backed enum instance to the string `'frozen'`. In PHP this comparison is always `false`, so a frozen card always becomes `'frozen'` again and an active card always becomes `'active'`, meaning the toggle never works and frozen cards can never be unfrozen.
- **Fix:** Compare against the enum: `$card->status === CardStatus::FROZEN ? CardStatus::ACTIVE : CardStatus::FROZEN`.
- **Location(s):** `backend/app/Services/CardService.php:509`

### •unverified · [logic] syncUserLevel() is not wrapped in a DB transaction — level and limit fields can be partially written
- **Evidence:** syncUserLevel() calls $user->forceFill([...])->save() after computing newLevel. Between the KycVerification read and the user save, a concurrent request could result in a different computed level. More critically, there is no DB::transaction() wrapping the status update, so a crash between the verification update (line 612) in reviewVerification() and the subsequent syncUserLevel() (line 633) leaves the verification row updated but the user's level stale.
- **Fix:** Wrap reviewVerification() and syncUserLevel() in DB::transaction() to ensure atomic consistency.
- **Location(s):** `backend/app/Services/KycService.php:552-599`

### •unverified · [logic] Missing status defaults to 'completed' — pending/failed transactions misclassified
- **Evidence:** status: _val(statusField, 'completed'),
- **Fix:** Default to 'unknown' or 'pending', not 'completed'. A transaction with a missing or null status field will be displayed and treated as successfully completed, hiding failed or in-flight transactions from the user.
- **Location(s):** `mobile/lib/features/transactions/data/models/transaction_model.dart:64`

### •unverified · [logic] Agent rating and review count are mass-assignable — rating manipulation
- **Evidence:** `rating` and `reviews_count` appear in `$fillable` (lines 35–36). Any endpoint that forwards user-supplied data to `Agent::create()` or `$agent->fill()` lets an agent owner set an arbitrary rating (e.g. 5.0) or review count, bypassing the real review aggregation logic.
- **Fix:** Remove `rating` and `reviews_count` from `$fillable`. Compute and write them only from trusted internal logic (e.g., a recalculation after a verified review is saved).
- **Location(s):** `backend/app/Models/Agent.php:35-36`

### •unverified · [logic] refund() does not check card ownership or transaction validity — arbitrary balance credit
- **Evidence:** refund() only checks amount > 0 then increments balance and decrements total_spent. There is no check that a matching original transaction exists, no maximum refund cap against original spend, and no idempotency guard. An attacker or bug could call refund() repeatedly to inflate a card balance indefinitely.
- **Fix:** Tie refunds to a specific transaction record, verify the refund amount does not exceed the original transaction amount, and enforce idempotency via a refund status flag on the transaction.
- **Location(s):** `backend/app/Models/VirtualCard.php:277-286`

### •unverified · [logic] Dashboard serves entirely fabricated financial data via rand()
- **Evidence:** total_transactions, transactions_this_month, earned_this_month, and all chart values are produced with rand(). Transaction descriptions embed rand() IDs. An admin relying on this dashboard makes decisions on random numbers, not real data.
- **Fix:** Replace all rand() stubs with real DB aggregates from the actual transactions/payments tables. This is a fintech dashboard — fake numbers constitute a data-integrity defect.
- **Location(s):** `backend/app/Http/Controllers/Admin/MerchantController.php:103-121`

### •unverified · [logic] Client-side balance check uses stale cached wallet balance — race condition allows overdraft
- **Evidence:** _ExchangeSheet._convert() reads widget.usdBalance / widget.sypBalance which are captured once when the sheet is opened (line 242-243) via ref.read(walletsProvider) — not re-fetched at submit time. A user who spends balance in another session or tab between opening and submitting the sheet will pass the client-side check and the actual overdraft guard is left entirely to the server.
- **Fix:** This guard is purely UX. It is acceptable only if the server enforces it atomically. The critical fix is to ensure the server never trusts the client's balance claim and uses a DB-level locking check. On the client, refresh balances immediately before the submit, or remove the local guard entirely and rely on the server error message.
- **Location(s):** `mobile/lib/features/wallets/presentation/pages/wallet_details_page.dart:710-718`

### •unverified · [logic] Dual redundant status columns enable inconsistent frozen/active state on virtual_cards
- **Evidence:** $table->enum('status', ['active', 'frozen', 'expired', 'cancelled', 'pending'])->default('pending') and $table->boolean('is_active')->default(true) — a card can have status='frozen' but is_active=true (or vice versa), creating an ambiguous authorization surface
- **Fix:** Remove is_active; derive active state solely from the status enum. Any authorization check that uses is_active independently of status can be bypassed by manipulating one field while leaving the other inconsistent.
- **Location(s):** `backend/database/migrations/2026_06_13_000003_create_virtual_cards_table.php:45-46`

### •unverified · [logic] Missing fee config silently grants zero-fee transactions
- **Evidence:** if (!$fee) { return ['success' => true, 'fee' => 0, 'net_amount' => $amount, ...]; } — any unconfigured or accidentally deleted fee code silently passes with zero fee applied
- **Fix:** Return success: false and block the transaction when a required fee code is missing. Only use the zero-fee fallback for explicitly optional fee codes.
- **Location(s):** `backend/app/Services/FeeService.php:73-84`

### •unverified · [logic] Race condition on recovery code consumption — double-spend possible
- **Evidence:** The read-check-delete of a recovery code is not atomic: `$codes = $user->two_factor_recovery_codes` (line 112) then `$user->update(...)` (line 121) with no database row lock. Two concurrent requests presenting the same recovery code can both pass the `array_search` check before either write completes, allowing a single code to authenticate twice.
- **Fix:** Wrap the operation in a database transaction with a pessimistic lock (`User::lockForUpdate()->find($user->id)`) so only one request can read-and-delete a given code at a time.
- **Location(s):** `backend/app/Services/TwoFactorService.php:110-123`

### •unverified · [money] Float arithmetic used for monetary amounts causes precision loss
- **Evidence:** deposit() and withdraw() accept `float $amount` and perform float arithmetic (`+=`, `-=`). PHP floats are IEEE 754 doubles and cannot represent most decimal fractions exactly, leading to cumulative rounding errors on financial balances (e.g. 0.1 + 0.2 ≠ 0.3).
- **Fix:** Use string/integer cents or a BCMath wrapper: `bcadd((string)$this->saved_amount, (string)$amount, 2)`. Store amounts as integer cents in the DB or use DECIMAL columns with BCMath in all arithmetic.
- **Location(s):** `backend/app/Models/SavingsGoal.php:59-73`

### •unverified · [money] Client-side-only balance check before withdrawal allows race-condition bypass
- **Evidence:** In `_convert()`, the balance is read from `widget.usdBalance` / `widget.sypBalance` which were captured when the bottom sheet was opened (`ref.read(walletsProvider).valueOrNull` at line 412-418). The check `if (backendAmount > available)` only blocks the request client-side against a stale snapshot. A user can open the exchange sheet, spend the balance via another concurrent action, and then complete the exchange — the client guard will pass because the snapshot is outdated. Backend must enforce this, but the comment at line 1482 explicitly positions this as the primary guard: 'Friendly client-side balance check — never surface a raw backend error.'
- **Fix:** Remove the special-casing that swallows backend insufficient-funds errors. Parse and display the backend response error for balance-related rejections rather than replacing backend enforcement with a stale client-side snapshot.
- **Location(s):** `mobile/lib/features/dashboard/presentation/pages/dashboard_page.dart:1483-1488`

### •unverified · [money] SYP amount multiplied by 100 before backend submission with no validation cap or overflow check
- **Evidence:** `final backendAmount = fromCurrency == 'SYP' ? rawAmount * 100 : rawAmount;` — the app silently multiplies the user-entered SYP figure by 100 before sending it. There is no upper bound enforced on `rawAmount` before this multiplication; a user entering 9999999999 would send 999999999900 to the backend. Additionally, the exchange rate display at line 1671 divides `_rate!` by 100 (`(_rate! / 100).toStringAsFixed(2)`) suggesting the rate itself is stored in sub-units, meaning the conversion preview at line 1465 (`amount * _rate!`) uses the raw un-divided rate, producing a figure 100× too large shown to the user.
- **Fix:** Clarify and centralise the unit convention. The display preview must use the same rate unit as the backend submission. Add a maximum amount validation before the ×100 multiplication. Consider sending raw user input and letting the backend handle unit conversion.
- **Location(s):** `mobile/lib/features/dashboard/presentation/pages/dashboard_page.dart:1480`

### •unverified · [money] Float arithmetic on money — precision loss and rounding errors
- **Evidence:** All method signatures use float $amount (e.g. public function credit(float $amount)). PHP float is IEEE 754 double precision; operations like 0.1 + 0.2 do not equal 0.3 exactly. Accumulated over many transactions, rounding errors will corrupt balance fields. The cast 'decimal:8' on the model field does not protect in-memory arithmetic.
- **Fix:** Accept string|int amounts and use bcadd/bcsub/bccomp with a fixed scale (e.g. 8 decimal places) for all arithmetic, or use a Money value object. Never use native float for monetary values.
- **Location(s):** `backend/app/Models/Wallet.php:87, 101, 121, 133, 145`

### •unverified · [money] balance and available_balance can silently diverge — no consistency invariant enforced
- **Evidence:** hold() decrements available_balance but not balance (correct). release() increments available_balance (correct). capture() decrements both pending_balance and balance. However, there is no check that balance - pending_balance - available_balance == 0 at any point. A bug or partial failure in any path leaves these three fields permanently inconsistent with no reconciliation.
- **Fix:** Enforce the invariant balance == available_balance + pending_balance on every mutation, either via a DB trigger or by asserting it at the top of each method and throwing on violation.
- **Location(s):** `backend/app/Models/Wallet.php:121-143`

### •unverified · [money] resetLimitsIfNeeded() resets and persists spend counters outside any transaction — TOCTOU on limit enforcement
- **Evidence:** canSpend() calls resetLimitsIfNeeded() which zeros daily_spent/monthly_spent and calls saveQuietly(), then canSpend() reads the now-zeroed daily_spent to evaluate the limit. A concurrent request on the same wallet sees the same stale daily_spent, passes the limit check, and both proceed. The limit is meant to cap cumulative spending but the check + increment are not atomic.
- **Fix:** Merge limit enforcement and balance deduction into a single atomic DB update with a row-level lock, so the read-check-write cycle cannot be interleaved.
- **Location(s):** `backend/app/Models/Wallet.php:184-200`

### •unverified · [money] Card cancellation refund uses stale in-memory balance — potential double-credit if balance changed between read and credit
- **Evidence:** `cancelCard` credits `$card->balance` (line 527) to the wallet, then sets `balance => 0` (line 548). However the card row is never locked with `lockForUpdate()` inside the transaction. A concurrent `unloadCard` or another `cancelCard` call can read the same balance before it is zeroed, resulting in the same funds being credited twice.
- **Fix:** Inside the transaction, reload and lock the card row: `$card = VirtualCard::lockForUpdate()->findOrFail($card->id);` before using `$card->balance`.
- **Location(s):** `backend/app/Services/CardService.php:524-556`

### •unverified · [money] Float arithmetic used for all monetary calculations — precision loss risk
- **Evidence:** `$purchasePrice = (float) $pricing['purchase_price']` (line 107); fee at line 348: `$fee = ($amount * $pricing['load_fee_percentage'] / 100) + $pricing['load_fee_fixed']`. IEEE 754 floating-point arithmetic is used throughout for money amounts, which can produce rounding errors (e.g., 0.1 + 0.2 ≠ 0.3). No `bcmath` or integer-cents approach is used.
- **Fix:** Use `bcmul`, `bcadd`, `bcdiv` with a fixed scale (e.g., 4 decimal places) for all monetary arithmetic, storing amounts as integers (cents) or using `bcmath` strings.
- **Location(s):** `backend/app/Services/CardService.php:107, 348-349`

### •unverified · [money] Money amounts stored as double — floating-point precision loss
- **Evidence:** final double amount; final double fee; ... amount: (json['amount'] as num).toDouble(), fee: (json['fee'] as num?)?.toDouble() ?? 0
- **Fix:** Use integer cents (int) or a Decimal/BigDecimal type for all monetary values. Never use double for money: 0.1 + 0.2 == 0.30000000000000004 in IEEE 754.
- **Location(s):** `mobile/lib/features/transactions/data/models/transaction_model.dart:12-13, 65-66`

### •unverified · [money] Receipt PDF total computed with double arithmetic — displayed amount can differ from ledger
- **Evidence:** final gross = tx.amount.abs(); final fee = tx.fee; final total = incoming ? gross - fee : gross + fee;
- **Fix:** Use integer cents or a Decimal type for all arithmetic. The computed total shown on the legal PDF receipt may not match the actual ledger value due to floating-point rounding.
- **Location(s):** `mobile/lib/features/transactions/data/receipt_service.dart:323-325`

### •unverified · [money] NFC payment amount parsed as double — floating-point precision loss on monetary values
- **Evidence:** amount: amountStr.isEmpty ? null : double.tryParse(amountStr),
Also in fromUri() line 213: amount: amountStr.isEmpty ? null : double.tryParse(amountStr),
In nfc_hce.dart line 42: amount.toStringAsFixed(2) — serializes fine but the round-trip through double.tryParse can introduce IEEE-754 rounding (e.g. 99.99 → 99.98999999...).
- **Fix:** Parse the amount string as a Decimal (package:decimal) or store as integer minor-units (cents). Never use double for monetary values that will be sent to the backend.
- **Location(s):** `mobile/lib/features/transfer/data/nfc_reader.dart:190`

### •unverified · [money] transfer_repository sends amount as Dart double — floating-point value may arrive at server with precision noise
- **Evidence:** final response = await _dio.post(ApiConstants.transfer, data: {
  'identifier': identifier,
  'amount': amount,   // Dart double, serialized by Dio as JSON number
  'currency': currency,
Dio serializes a Dart double directly to a JSON number. For values like 99.99, Dart's double representation may serialize as 99.98999999999999488... depending on the JSON encoder, causing server-side validation errors or off-by-one cent defects.
- **Fix:** Serialize amount as a string ('amount': amount.toStringAsFixed(2)) or as an integer minor-unit count, and instruct the server to parse accordingly. Same issue exists in payment_request_repository.dart lines 23-24 and 81-82.
- **Location(s):** `mobile/lib/features/transfer/data/repositories/transfer_repository.dart:40-44`

### •unverified · [money] reserved_balance can go negative: mismatch between reserved amount and actual captured amount
- **Evidence:** At authorization time the hold is: wallet->increment('reserved_balance', $amountDollars) (line 435) where $amountDollars = pending_request.amount / 100. At capture time reserved_balance is released using abs($transaction->amount) (line 513), but the actual debit uses $amountDollars = approved_amount / 100 (line 496, 516). Stripe allows the final captured amount to differ from the authorization amount (e.g. partial captures, tip adjustments). If approved_amount > pending_request_amount, the debit exceeds what was reserved; if less, reserved_balance is over-released (goes negative).
- **Fix:** Store the reserved amount on the transaction record at authorization time. At capture, release exactly that reserved amount, then debit the actual approved_amount.
- **Location(s):** `backend/app/Services/StripeIssuingService.php:512-516`

### •unverified · [money] Spending limit check and fund reservation are not in the same DB transaction — TOCTOU race allows overdraft
- **Evidence:** checkSpendingLimits (line 410) reads card.daily_spent and card.monthly_spent outside the DB::transaction block (line 425). Two concurrent authorization requests for the same card can both pass the spending-limit check before either increments daily_spent. Inside the transaction the wallet balance is re-checked under lock (line 430), but daily_spent and monthly_spent are not re-read under lock — they are incremented with separate increment() calls (lines 462-463) without a prior locked read, allowing both to succeed and exceed the daily/monthly limits.
- **Fix:** Move checkSpendingLimits inside the DB::transaction, and use lockForUpdate() on the VirtualCard row before reading and incrementing daily_spent / monthly_spent.
- **Location(s):** `backend/app/Services/StripeIssuingService.php:403-477`

### •unverified · [money] Balance update (wallet.credit) is outside any DB transaction — non-atomic with transaction record update
- **Evidence:** `$transaction->update(...)` and `$wallet->credit(...)` are two separate DB operations with no wrapping `DB::transaction()`. A crash or exception between them leaves the transaction marked COMPLETED but the wallet uncredited (or vice-versa).
- **Fix:** Wrap both operations in `DB::transaction(function() { ... })` with `lockForUpdate` on the wallet row.
- **Location(s):** `backend/app/Services/CCPaymentService.php:419-437, 477-486`

### •unverified · [money] Monetary amounts typed as float — binary floating-point precision errors
- **Evidence:** All public method signatures declare float $amount. PHP floats are IEEE 754 binary; repeated arithmetic (especially at SYP scale ~1.3 billion) accumulates rounding errors that silently corrupt balances and totals.
- **Fix:** Use string or integer (smallest currency unit / piastres) for all monetary values, or the bcmath/brick-money library. Cast to string before DB writes.
- **Location(s):** `backend/app/Services/WalletService.php:31, 65, 182`

### •unverified · [money] deposit() ignores return value of credit() — frozen wallet produces phantom COMPLETED transaction
- **Evidence:** Wallet::credit() returns false (without updating balance) when is_frozen is true (Wallet.php line 89). deposit() at line 37 calls $wallet->credit($amount) without checking the return value and then unconditionally creates a Transaction with status COMPLETED. The balance is not updated but a completed deposit record is written.
- **Fix:** Check the return value: if (!$wallet->credit($amount)) { throw new \Exception('Wallet is frozen or credit failed'); }
- **Location(s):** `backend/app/Services/WalletService.php:37, 43-58`

### •unverified · [money] withdraw() debits balance immediately but marks transaction PROCESSING — no reversal path
- **Evidence:** debit() on line 75 immediately reduces wallet.balance and available_balance, then the transaction is created with TransactionStatus::PROCESSING (line 94). If the external withdrawal is later rejected there is no compensating credit in this code, leaving the user permanently debited.
- **Fix:** Use hold() to move the amount to pending_balance instead of debit() at the time of initiation. Only call debit() (or releasePending()) once the external outcome is confirmed.
- **Location(s):** `backend/app/Services/WalletService.php:75-98`

### •unverified · [money] getOrCreateWallet race condition — concurrent requests can create duplicate wallets
- **Evidence:** `firstOrCreate(['currency' => $currency], ...)` is not atomic. Two concurrent requests for the same currency will both find no row, both insert, and create two wallets for the same user+currency. The wallet table likely has no unique constraint enforcing (user_id, currency) uniqueness.
- **Fix:** Add a unique database constraint on `(user_id, currency)` in the wallets table migration. Wrap `firstOrCreate` in a try/catch for `UniqueConstraintViolationException` and return the existing wallet on conflict.
- **Location(s):** `backend/app/Models/User.php:184-189`

### •unverified · [money] Merchant balance stored as decimal(12,2) without row-level locking hint in schema — double-spend risk
- **Evidence:** $table->decimal('balance', 12, 2)->default(0);
$table->decimal('total_earned', 14, 2)->default(0);
- **Fix:** The schema itself must enforce that balance cannot go negative: add an unsigned constraint (or CHECK balance >= 0). Without it, concurrent debit operations that read-then-write without SELECT FOR UPDATE can produce a negative balance. This is a schema-level gap that should be caught here.
- **Location(s):** `backend/database/migrations/2026_06_20_000000_create_merchants_table.php:40-41`

### •unverified · [money] Gold wallet balance_grams can go negative — no unsigned or CHECK constraint
- **Evidence:** $table->decimal('balance_grams', 12, 4)->default(0);
- **Fix:** Add an unsigned constraint or a database-level CHECK (balance_grams >= 0) to prevent the sell path from creating a negative gold balance if application-level checks are bypassed or race conditions occur.
- **Location(s):** `backend/database/migrations/2026_06_20_200000_create_gold_wallets_table.php:14`

### •unverified · [money] savings_transactions amount has no unsigned/positive constraint — negative deposit possible
- **Evidence:** $table->decimal('amount', 18, 2);
- **Fix:** Add an unsigned constraint or CHECK (amount > 0). A negative deposit amount would increment saved_amount downward without going through a withdrawal code path, bypassing any withdrawal limits or checks.
- **Location(s):** `backend/database/migrations/2026_06_21_100000_create_savings_goals_table.php:36`

### •unverified · [money] Fee amount used as float causes precision loss in financial calculations
- **Evidence:** calculateFee(float $amount) and all fee fields typed as float (lines 19-26). PHP float arithmetic causes binary rounding errors. E.g., 0.1 + 0.2 = 0.30000000000000004. Fee calculations on large amounts will drift. The `round($calculatedFee, 6)` on line 135 only masks last-digit drift, it doesn't fix cumulative error. BCMath or integer arithmetic (cents) is required for money.
- **Fix:** Use BCMath functions (bcadd, bcmul, bcdiv) with string inputs for all fee arithmetic, or store and operate in integer minor units. Change method signatures to accept string amounts.
- **Location(s):** `backend/app/Models/Fee.php:122-136`

### •unverified · [money] net_amount in getFeeBreakdown can go negative without validation
- **Evidence:** `$netAmount = $amount - $fee;` — if fee >= amount (e.g. a high fixed_amount on a small transaction that still passes isAmountAllowed), net_amount is zero or negative. This value is returned to callers and could be used as a transfer/withdrawal amount without a positivity guard.
- **Fix:** Assert or clamp net_amount to be non-negative before returning: if ($netAmount < 0) throw a domain exception (or return an error flag), so callers cannot accidentally process a zero/negative net amount.
- **Location(s):** `backend/app/Models/Fee.php:160`

### •unverified · [money] boot() balance_after computed by adding amount regardless of transaction direction
- **Evidence:** `$transaction->balance_after ??= $wallet->balance + $transaction->amount;` — for a debit transaction, amount is negative, so this arithmetic happens to work only if the wallet balance has already been decremented before the Transaction record is created. The comment on lines 81-84 says callers that mutate the wallet first MUST pass explicit values, but this is an undocumented, unenforced convention. Any caller that creates the Transaction before adjusting the wallet balance will record a wrong (pre-debit) balance_after, making the audit trail unreliable.
- **Fix:** Require callers to always pass explicit balance_before and balance_after, and throw an exception in the boot hook if they are missing, rather than silently computing them from the current wallet state.
- **Location(s):** `backend/app/Models/Transaction.php:90`

### •unverified · [money] Wallet credit called with raw string amount from external webhook payload — no numeric validation or precision control
- **Evidence:** $wallet->credit($payload['amount'] ?? 0) passes the raw string from the external webhook directly. If the payload contains a value like '1e10', '-100', or a value with excessive decimal places, the credit amount is undefined depending on how wallet->credit() handles it. No bcmath/Money-object sanitization is applied.
- **Fix:** Validate that $payload['amount'] is a non-negative numeric string with bounded decimal places before passing to credit(). Use bccomp/bcadd for arithmetic.
- **Location(s):** `backend/app/Services/CCPaymentService.php:435`

### •unverified · [money] Float arithmetic used for all money calculations — precision loss and rounding errors
- **Evidence:** The `$amount` parameter is typed `float`. Balance comparisons (`(float) $senderWallet->available_balance < $amount`), snapshot captures (`$senderBefore = (float) $senderWallet->balance`), KYC sum comparisons, and cashback calculation all use PHP float arithmetic. IEEE-754 double precision cannot represent many decimal currency values exactly, leading to penny-off errors, failed balance checks, and mismatched ledger records.
- **Fix:** Use BCMath (`bccomp`, `bcsub`, `bcadd`, `bcmul`) or store/compare amounts as integer minor units (cents/piastres). Change the method signature to accept a string or integer, validate the scale, and eliminate all `(float)` casts on monetary values.
- **Location(s):** `backend/app/Services/TransferService.php:86, 136, 140-141, 233-235, 283-285, 296-314`

### •unverified · [money] Cashback credit silently swallowed — balance updated without transaction record on DB error
- **Evidence:** `creditCashback` calls `$senderWallet->credit($cb)` (line 241) then `Transaction::create(...)` (line 243) inside a bare `try/catch \Throwable`. If `Transaction::create` throws (e.g. DB constraint), the wallet balance has already been incremented but the ledger entry is missing. The catch swallows the error silently. Additionally this runs inside the outer `DB::transaction`, so the whole transaction would actually roll back — but the comment says "never break the transfer", implying the intent was for it to be non-critical, which contradicts being inside the transaction.
- **Fix:** Either (a) wrap `creditCashback` in its own nested savepoint/transaction so a failure rolls back only the cashback and the outer transfer still commits, or (b) dispatch cashback as an async queued job after the transfer commits. Either way, `credit()` and `Transaction::create()` must be atomic.
- **Location(s):** `backend/app/Services/TransferService.php:229-261`

### •unverified · [money] Recipient wallet auto-provisioned without locking against concurrent creation (race condition)
- **Evidence:** If the recipient has no wallet, two concurrent transfers to that recipient will both find `lockForUpdate()->first()` returning null, then both call `$recipient->wallets()->create(...)`. Depending on DB constraints, this either creates a duplicate wallet or throws, potentially leaving one transfer in an inconsistent state.
- **Fix:** Use `firstOrCreate` with a unique index on `(user_id, currency)` and re-fetch with `lockForUpdate` after creation. The unique index on the wallets table must be confirmed to exist to make the constraint reliable.
- **Location(s):** `backend/app/Services/TransferService.php:119-129`

### •unverified · [money] Client-side balance/grams check is not authoritative — race condition on double-spend
- **Evidence:** _total > widget.wallet.usdBalance (line 477) and grams > widget.wallet.balanceGrams (line 480) — wallet snapshot passed in at sheet-open time, stale by the time _submit() runs
- **Fix:** These checks use the wallet state captured when the bottom sheet was opened (wallet is a final field passed at construction). Between opening the sheet and tapping confirm, the user could have spent funds via another session/device. The backend must enforce balance atomically with a DB-level lock; the client check is only a UX hint and must never be the sole guard.
- **Location(s):** `mobile/lib/features/gold/presentation/pages/gold_page.dart:476-483`

### •unverified · [money] Double floating-point arithmetic for all financial calculations
- **Evidence:** double get _subtotal => _grams * _unitPrice; double get _fee => widget.isBuy ? _subtotal * 0.01 : _subtotal * 0.005; double get _total => widget.isBuy ? _subtotal + _fee : _subtotal - _fee;
- **Fix:** IEEE-754 double multiplication of gram quantities by USD prices accumulates rounding error that is then sent to the backend as the authoritative amount. Use Dart's decimal package or integer fixed-point arithmetic (e.g., microdollars) for all monetary computations. The server should derive the canonical total independently rather than trust the client-computed value.
- **Location(s):** `mobile/lib/features/gold/presentation/pages/gold_page.dart:460-462`

### •unverified · [money] Float arithmetic used for monetary/gram values — precision loss
- **Evidence:** creditGrams(float $grams, float $usdSpent) and debitGrams(float $grams, float $usdReceived) use PHP native float parameters and arithmetic (+=, -=). IEEE 754 doubles cannot represent many decimal fractions exactly, causing cumulative rounding errors in gold gram balances and USD amounts over repeated transactions.
- **Fix:** Use BCMath (bcadd, bcsub, bccomp) with string-typed parameters for all monetary and gram arithmetic, consistent with the decimal:4 / decimal:2 database casts already defined.
- **Location(s):** `backend/app/Models/GoldWallet.php:43-59`

### •unverified · [money] resetLimitsIfNeeded writes stale daily/monthly_spent without a lock — TOCTOU on limit reset
- **Evidence:** resetLimitsIfNeeded() checks dates on the in-memory model, zeroes daily_spent/monthly_spent, and calls saveQuietly(). If two concurrent spend() calls both enter this function simultaneously they can both zero the counters, potentially allowing double the daily/monthly limit to be spent.
- **Fix:** Perform limit resets inside the same DB transaction that locks the card row for the spend operation.
- **Location(s):** `backend/app/Models/VirtualCard.php:288-304`

### •unverified · [money] Merchant hard-delete destroys financial records without soft-delete or balance check
- **Evidence:** $merchant->delete() is a hard delete with no check on merchant balance or linked transactions. Deleting a merchant with a non-zero balance or open transactions breaks referential integrity and makes financial reconciliation impossible.
- **Fix:** Check that merchant balance is zero and no pending transactions exist before deletion. Use soft-deletes (SoftDeletes trait + deleted_at) so related financial records remain traceable.
- **Location(s):** `backend/app/Http/Controllers/Admin/MerchantController.php:161-166`

### •unverified · [money] referral_rewards table has no unique constraint preventing duplicate reward credits
- **Evidence:** The `referral_rewards` table has no unique constraint on `(referrer_id, referred_id, trigger)`. A race condition or bug could insert multiple rows for the same referral event and trigger, crediting the referrer and/or referred user multiple times for the same action (e.g., `first_deposit`).
- **Fix:** Add a unique constraint: `$table->unique(['referrer_id', 'referred_id', 'trigger']);` to prevent duplicate reward records at the database level.
- **Location(s):** `backend/database/migrations/2026_06_14_100010_create_referral_rewards_table.php:11-24`

### •unverified · [money] Balance stored as IEEE 754 double — monetary precision loss
- **Evidence:** final double balance; final double availableBalance; final double pendingBalance; — all three monetary fields use Dart's double (IEEE 754 64-bit float). Values like 0.10 cannot be represented exactly, causing silent rounding on display and arithmetic. The model is also used directly as the source of truth for client-side balance checks before submitting transactions.
- **Fix:** Store monetary values as String or int (minor-unit integer, e.g. cents) from the API and convert only at the display layer. Dart's Decimal package or parsing to int cents avoids the representation error.
- **Location(s):** `mobile/lib/features/wallets/data/models/wallet_model.dart:8-10`

### •unverified · [money] orElse fallback silently uses wrong wallet ID for crypto deposit/withdraw
- **Evidence:** final usdWallet = wallets.firstWhere((w) => w.currency == 'USD', orElse: () => wallets.first); — if the user has no USD wallet, wallets.first (potentially a SYP wallet) is silently used as the deposit target, crediting crypto into the wrong wallet without any error shown to the user. The same pattern is in crypto_withdraw_page.dart line 94-97 and wallet_details_page.dart line 385 and 554.
- **Fix:** Remove the orElse fallback and handle the StateError explicitly: show an error message telling the user they need a USD wallet, rather than silently binding to the wrong wallet.
- **Location(s):** `mobile/lib/features/wallets/presentation/pages/crypto_deposit_page.dart:56-59`

### •unverified · [money] buy/sell rate direction logic is reversed for the user in `convert()`
- **Evidence:** Comment says `'sell' = user selling $from to get $to (worse rate for user)` and maps it to `$rateData['sell_rate']`. For USD→SYP the sell_rate is `baseRate * (1 + halfSpread)` — this is the *higher* SYP-per-USD number, which is *better* for a user selling USD, not worse. The buy/sell label assignment is internally inconsistent with the spread comments in `formatRateResponse` (lines 85-86: 'User buys USD cheaper' = buy_rate). A caller using `direction='sell'` when a user sells USD actually receives the more favourable rate, meaning the platform loses spread revenue on every USD sell transaction.
- **Fix:** Reconcile the direction terminology: define 'sell' from the platform's perspective (platform sells USD to user → user pays sell_rate, which is higher), or rewrite the comment and swap the rate selection so that the user always gets the rate that costs them more.
- **Location(s):** `backend/app/Services/ExchangeRateService.php:138`

### •unverified · [money] balance and available_balance stored with different precision than limits/spent columns — rounding inconsistency
- **Evidence:** balance/available_balance/pending_balance use decimal(18,8); daily_limit/monthly_limit/daily_spent/monthly_spent use decimal(18,2). Comparing or subtracting across these columns silently truncates 6 decimal places, producing incorrect limit enforcement for crypto currencies.
- **Fix:** Use a single consistent precision for all monetary columns (decimal(18,8) for crypto, or separate fiat/crypto wallet types). Never mix precisions within the same arithmetic path.
- **Location(s):** `backend/database/migrations/2026_06_13_000002_create_wallets_table.php:18-26`

### •unverified · [money] Comma replaced with decimal point destroys thousand-separator input
- **Evidence:** `.replaceAll(',', '.')` converts ALL commas to decimal points. A user typing "1,500" (thousand-separated) becomes "1.500", which parses as 1.5 USD or 150 SYP — silently losing 1498.5 USD or 149,850 SYP in display/transfer intent.
- **Fix:** Strip commas that act as thousand separators (i.e., remove them) rather than converting them to a decimal point. Only convert the Arabic decimal separator '٫' to '.'. The current logic cannot distinguish '1,500' (thousands) from '1,5' (European decimal) without locale context, but replacing with '.' is the wrong default and causes severe money mis-parsing.
- **Location(s):** `mobile/lib/core/utils/money_formatter.dart:52`

### •unverified · [money] balance and financial fields cast to float — precision loss on monetary values
- **Evidence:** 'balance' => 'float', 'total_earned' => 'float' — PHP float is IEEE 754 double precision, which cannot exactly represent most decimal currency values. Arithmetic like 0.1 + 0.2 produces rounding errors that accumulate over many transactions.
- **Fix:** Store balance and total_earned as integer cents in the database (BIGINT) or use a DECIMAL(20,4) column and cast via a custom Money cast or bcmath-backed value object; never cast monetary columns to float.
- **Location(s):** `backend/app/Models/Merchant.php:61-62`

### •unverified · [money] Floating-point arithmetic for currency amounts
- **Evidence:** $amountDollars = $amount / 100; then used in wallet balance comparisons and increments. PHP float division introduces precision errors (e.g. 1001 cents → 10.009999... dollars).
- **Fix:** Store and compute all amounts in integer cents. Convert to display strings only at the presentation layer using bcmath or integer arithmetic.
- **Location(s):** `backend/app/Services/StripeIssuingService.php:401, 495, 544`

### •unverified · [money] Non-atomic spending limit reset allows double-spend bypass
- **Evidence:** checkSpendingLimits() does: read daily_reset_at → compare → update(['daily_spent' => 0]) with no row lock. Two concurrent requests on a reset day both read the old date, both reset daily_spent to 0, then both pass the limit check.
- **Fix:** Perform the reset inside a locked DB transaction using UPDATE with a WHERE clause on daily_reset_at, or use the card row lock (lockForUpdate) before reading/resetting counters.
- **Location(s):** `backend/app/Services/StripeIssuingService.php:743-748`

### •unverified · [money] Reversal releases wrong reserved amount when partial reversal occurs
- **Evidence:** handleAuthorizationReversal() uses abs($transaction->amount) to release reserved_balance when the transaction is still PROCESSING, but the Stripe reversal may be partial ($authorization['amount_reversed'] < original). The full hold is released even though only part was reversed, freeing funds that should remain reserved.
- **Fix:** Release only $amountDollars (from amount_reversed) from reserved_balance, not the full transaction amount, unless this is a full reversal (compare amounts explicitly).
- **Location(s):** `backend/app/Services/StripeIssuingService.php:559-560`

### •unverified · [money] net_amount can go negative when fee exceeds amount
- **Evidence:** $fee = $this->calculateFee($amount); $netAmount = $amount - $fee; — min_fee or fixed_amount can exceed amount, producing a negative net_amount that is returned to callers and potentially used as the debit value
- **Fix:** Assert net_amount >= 0 and return an error (or clamp to 0) before returning the breakdown. Reject the transaction if the fee equals or exceeds the gross amount.
- **Location(s):** `backend/app/Models/Fee.php:159-165`

### •unverified · [money] Floating-point arithmetic used for all monetary amounts
- **Evidence:** `balance`, `spendingLimit`, `dailyLimit`, and `monthlyLimit` are all `double`. The fund page also uses `double amount = double.tryParse(...)` (fund_card_page.dart line 40) and sends `'amount': amount` as a float in the API body (card_repository.dart line 144). IEEE 754 doubles cannot represent many decimal values exactly, so amounts like $0.10 or $99.99 will carry rounding error across display, transfer, and limit comparisons.
- **Fix:** Store monetary values as integer cents (int) or use the `decimal` package. Parse server amounts via `(json['balance'] as num?)?.toStringAsFixed(2)` converted to a Decimal, and submit amounts as strings or integer minor-units.
- **Location(s):** `mobile/lib/features/cards/data/models/card_model.dart:17-20, 51-54`

### •unverified · [money] chargeFee() does not check return value of debit() — fee charged even when debit fails
- **Evidence:** Line 23: $wallet->debit($amount); — the boolean return value is silently discarded. debit() returns false (without throwing) when balance is insufficient or wallet is frozen (Wallet.php:101-119). The code then continues to record a COMPLETED fee transaction even though no money was actually moved.
- **Fix:** Capture the return value and throw an exception on false: if (!$wallet->debit($amount)) { throw new \RuntimeException('Debit failed'); }
- **Location(s):** `backend/app/Services/TransactionService.php:20-41`

### •unverified · [money] addReward() does not check return value of credit() — reward recorded even when credit fails
- **Evidence:** Line 51: $wallet->credit($amount); — return value ignored. credit() returns false when amount <= 0 or wallet is frozen (Wallet.php:87-99). A COMPLETED reward transaction is then written even though the balance was not actually increased.
- **Fix:** if (!$wallet->credit($amount)) { throw new \RuntimeException('Credit failed'); }
- **Location(s):** `backend/app/Services/TransactionService.php:48-70`

### •unverified · [validation] Caller-supplied MIME types bypass the blocked-MIME list
- **Evidence:** The $additionalMimes parameter is merged into $allowed (line 25) but is never checked against BLOCKED_MIMES. A caller can pass 'image/svg+xml' or 'text/html' as an additional MIME, which will be added to the allow-list. The blocked-MIME check on line 27 fires before the allow-list merge, but because $allowed already contains the blocked type the subsequent check on line 31 passes, allowing the file through. More critically, a caller could pass 'application/x-php' in $additionalMimes and the block check would still reject it, but types not in BLOCKED_MIMES (e.g. 'text/xml', 'application/xhtml+xml') with malicious content could be whitelisted by callers without any sanitisation guard.
- **Fix:** Validate $additionalMimes against BLOCKED_MIMES before merging, or remove the $additionalMimes parameter and centralise all allowed types in the constant.
- **Location(s):** `backend/app/Services/FileValidationService.php:22-33`

### •unverified · [validation] Mass assignment protection bypassed by empty $guarded overriding $fillable
- **Evidence:** protected $fillable = ['key','value','type','group','label','description','is_public']; protected $guarded = [];  — Laravel's mass-assignment guard: when $guarded is [] (empty array), ALL columns are unguarded regardless of $fillable. Any controller or service that calls SystemSetting::create($request->all()) or ::fill($data) will allow writing to any database column including id, timestamps, or any future sensitive column.
- **Fix:** Remove the $guarded = [] declaration entirely. $fillable alone is sufficient and correct. Having both is contradictory and the empty $guarded wins, voiding $fillable protection.
- **Location(s):** `backend/app/Models/SystemSetting.php:10-20`

### •unverified · [validation] Credentials array accepted with no key-level validation — arbitrary fields can overwrite payment secrets
- **Evidence:** 'credentials' => 'nullable|array' — the entire credentials array is replaced with whatever the client sends. No allowlist of permitted keys per integration type. A malicious or mistaken admin request can inject unexpected keys or silently null out existing secrets (Stripe key, CCPayment API key, FCM server key) with a single PUT.
- **Fix:** Validate credentials sub-keys against a per-integration allowlist. Merge only known/allowed keys rather than replacing the entire credentials object. At minimum add 'credentials.*' => 'string' to prevent nested arrays from being injected.
- **Location(s):** `backend/app/Http/Controllers/Admin/IntegrationController.php:41-55`

### •unverified · [validation] NFC URI parsed without scheme or host validation — deep-link injection via crafted NFC tag
- **Evidence:** static NfcPayment? fromUri(String? raw) {
  final uri = Uri.tryParse(raw?.trim() ?? '');
  if (uri == null || uri.scheme.toLowerCase() != 'sakk') return null;
  final q = uri.queryParameters;
  final account = (q['a'] ?? '').trim();
Only the scheme ('sakk') is checked, but host is not enforced. A malicious tag with sakk://evil-host?a=SK00000001&amt=9999 would parse successfully. Additionally, the account number from the NFC tap (controlled entirely by the tapped device) is passed directly to the send flow with no format validation.
- **Fix:** Enforce host == 'nfcpay' in fromUri(). Validate account format (regex against known SAKK account pattern) before populating the payment form. Never pre-fill the amount from an untrusted NFC source without explicit user re-confirmation.
- **Location(s):** `mobile/lib/features/transfer/data/nfc_reader.dart:202-217`

### •unverified · [validation] NFC payment amount and currency fully trusted from attacker-controlled NFC tag
- **Evidence:** NfcPayment.parse() reads account, amount, and currency directly from the raw NFC payload string (SAKKPAY|{account}|{amount}|{currency}|{name}). An attacker can write a rogue NFC tag or program an Android device to broadcast any amount/currency. The parsed amount is then set on _amountController (qr_send_page.dart:191-193) and the NFC confirm sheet at line 305 uses this attacker-supplied amount to call _confirm() with no server-side re-verification of the requested amount being legitimate.
- **Fix:** The amount embedded in an NFC payload is a suggestion only — the server must never trust it without the recipient's backend confirmation. At minimum, validate currency is in the allowed set (USD/SYP) before accepting, and verify on the backend that the recipient actually requested that amount.
- **Location(s):** `mobile/lib/features/transfer/data/nfc_reader.dart:176-198`

### •unverified · [validation] Deposit amount credited from untrusted webhook payload field without validation
- **Evidence:** `'amount' => $payload['amount'] ?? $transaction->amount` and `$wallet->credit($payload['amount'] ?? 0)` use the amount directly from the incoming webhook body. If webhook signature verification is not enforced (see finding #1), an attacker can send any amount. Even with verification, the amount should be re-fetched from CCPayment's API to confirm.
- **Fix:** After verifying the signature, re-fetch the deposit record via `getDepositRecord($recordId)` and use the API-confirmed amount rather than the payload amount.
- **Location(s):** `backend/app/Services/CCPaymentService.php:421, 435`

### •unverified · [validation] Both $fillable and $guarded=[] set simultaneously, defeating mass-assignment protection
- **Evidence:** protected $fillable = [...] is defined (lines 10-29) AND protected $guarded = [] is set on line 31. Setting $guarded = [] makes every column fillable regardless of $fillable, so even columns intentionally omitted from $fillable (e.g. 'level', 'id', 'created_at') can be mass-assigned. An attacker who can reach any create/update endpoint can set the 'level' field or any other column directly.
- **Fix:** Remove $guarded = [] entirely and rely solely on the $fillable whitelist, or remove $fillable and use a minimal, explicit $guarded list. Never set both.
- **Location(s):** `backend/app/Models/KycLevel.php:10-31`

### •unverified · [validation] Mass assignment bypass via empty $guarded overriding $fillable
- **Evidence:** protected $guarded = []; is declared alongside protected $fillable = [...]. In Laravel, $guarded = [] disables all mass-assignment protection entirely — $fillable is ignored when $guarded is an empty array. Every column in the table becomes mass-assignable, including columns not listed in $fillable (e.g. id, created_at, and any future columns).
- **Fix:** Remove the $guarded = [] declaration. When $fillable is defined, $guarded must not be set to [] simultaneously. Use only one approach: either a whitelist ($fillable) or a blacklist ($guarded), never both.
- **Location(s):** `backend/app/Models/CardInventory.php:31`

### •unverified · [validation] Mass assignment bypass via conflicting $fillable and $guarded = []
- **Evidence:** Both `protected $fillable` (lines 10-24) and `protected $guarded = []` (line 26) are declared. In Laravel, an empty `$guarded` array disables all mass-assignment protection entirely, overriding `$fillable`. This means every column — including `user_id`, `is_read`, `status`, and `failure_reason` — can be set via `create()`/`fill()`/`update()` from untrusted input, allowing an attacker to reassign a notification to another user (`user_id`), forge its read state, or tamper with its status.
- **Fix:** Remove `protected $guarded = [];` (line 26) entirely. `$fillable` alone is sufficient and correctly restricts mass assignment to the listed fields.
- **Location(s):** `backend/app/Models/UserNotification.php:10-26`

### •unverified · [validation] Mass assignment protection nullified: $fillable and $guarded=[] coexist
- **Evidence:** Both `protected $fillable = [...]` (lines 11-26) and `protected $guarded = []` (line 28) are declared. In Laravel, when `$guarded` is an empty array every attribute is unguarded, so `$fillable` is never consulted. Any `create()` or `fill()` call can set any column — including `kyc_level_required` and `is_active` — regardless of what is listed in `$fillable`.
- **Fix:** Remove `protected $guarded = [];` entirely and keep only `$fillable`, or remove `$fillable` and use `$guarded = ['id']` — but never declare both. The intent here is clearly an allowlist, so removing `$guarded = []` is the correct fix.
- **Location(s):** `backend/app/Models/CardPricing.php:11-28`

### •unverified · [validation] Mass assignment allows untrusted status override on gold transactions
- **Evidence:** 'status' is listed in $fillable. Any code path that passes user-controlled input to GoldTransaction::create() or $tx->fill() can set status to 'completed', 'approved', etc. without going through business logic.
- **Fix:** Remove 'status' from $fillable and set it only via explicit attribute assignment inside trusted service/repository classes (e.g. $tx->status = 'pending'; $tx->save()).
- **Location(s):** `backend/app/Models/GoldTransaction.php:22`

### •unverified · [validation] Crypto withdraw sends amount as raw text string — no numeric parsing or sanitisation
- **Evidence:** 'amount': _amountController.text — the raw TextField string is sent to the backend without parsing to a number. A value like '1e5', '1,000', or a string with trailing whitespace will pass the form validator (which only checks double.tryParse) but be sent as an untyped string. The backend may interpret it unexpectedly, and there is no minimum-amount check.
- **Fix:** Parse to a double/Decimal, validate minimum, and send the numeric value: 'amount': double.parse(_amountController.text.trim()). The same pattern occurs in _InlineUsdtWithdrawSheet (wallet_details_page.dart line 556).
- **Location(s):** `mobile/lib/features/wallets/presentation/pages/crypto_withdraw_page.dart:103`

### •unverified · [validation] No input validation on `rate` and `spread` in updateRate — zero or negative rate accepted
- **Evidence:** `public function updateRate(float $rate, float $spread = 2.0)` performs no validation. A rate of 0 causes division by zero in `formatRateResponse` (`1 / $baseRate`, line 94) whenever any SYP→USD conversion is attempted after the update. A negative rate or negative spread produces inverted/nonsensical buy/sell rates that are silently persisted to the database and used for all subsequent money conversions.
- **Fix:** Assert `$rate > 0` and `$spread >= 0 && $spread < 100` before proceeding; throw an `\InvalidArgumentException` or return a validation error otherwise.
- **Location(s):** `backend/app/Services/ExchangeRateService.php:187-205`

### •unverified · [validation] No card_id validation — missing required field for load-card operation
- **Evidence:** The rules() array only validates 'amount'. A card-load request must identify the target card. If card identity comes from the route, it is never validated here (existence, active status, currency match). If it comes from the request body, there is no rule for it at all.
- **Fix:** Add validation for the card identifier: confirm the card exists in the database, belongs to the authenticated user, and is in an active/loadable state. Use a route-model binding or an explicit rule such as 'card_id' => ['required','exists:cards,id'].
- **Location(s):** `backend/app/Http/Requests/Card/LoadCardRequest.php:15-25`

### •unverified · [validation] $fillable and $guarded both set — $guarded=[] makes $fillable redundant and allows all attributes to be mass-assigned
- **Evidence:** protected $fillable = [...]; protected $guarded = []; — when $guarded is empty, Eloquent treats ALL attributes as fillable regardless of $fillable, including any sensitive or system columns (e.g. id, created_at) not listed in $fillable.
- **Fix:** Remove $guarded = []; entirely and rely solely on $fillable, or remove $fillable and use $guarded to list protected fields. Having both with $guarded=[] silently negates the intended $fillable restriction.
- **Location(s):** `backend/app/Models/ExchangeRate.php:9-21`

### •unverified · [validation] Mass assignment fully disabled: $guarded=[] overrides $fillable
- **Evidence:** protected $guarded = []; set alongside $fillable neutralises all mass-assignment protection; every column including admin_id, entity_type, entity_id is freely assignable
- **Fix:** Remove the $guarded = []; line entirely. $fillable already provides the allowlist; having both is contradictory and $guarded=[] wins, disabling protection.
- **Location(s):** `backend/app/Models/ActivityLog.php:24`

### •unverified · [validation] updateFee passes unfiltered caller-supplied data directly to Eloquent update
- **Evidence:** $fee->update($data); — $data is passed through from the caller with no allowlist. The Fee model's $fillable includes all financial fields (percentage, fixed_amount, min_fee, max_fee, min_amount, max_amount, is_active). A controller that passes request()->all() or similar enables full mass-assignment of fee parameters.
- **Fix:** Inside updateFee, apply an explicit allowlist: $fee->update(Arr::only($data, ['name','name_en','description','fixed_amount','percentage','min_fee','max_fee','min_amount','max_amount','is_active','sort_order']));
- **Location(s):** `backend/app/Services/FeeService.php:160-171`

### •unverified · [validation] Incomplete MIME blocklist allows dangerous file uploads
- **Evidence:** BLOCKED_MIMES only contains ['image/svg+xml', 'text/html', 'application/x-php']. Missing critical MIME types: 'text/x-php', 'application/php', 'application/x-httpd-php', 'text/javascript', 'application/javascript', 'application/x-sh', 'text/x-shellscript', 'application/x-perl', 'application/x-python'. An attacker can upload a PHP webshell detected as 'text/x-php' or 'application/php' by finfo and bypass this middleware entirely.
- **Fix:** Replace the blocklist approach with an allowlist of explicitly permitted MIME types (e.g., only 'image/jpeg', 'image/png', 'image/gif', 'application/pdf') and validate both the MIME type and the file extension. Never rely on a blocklist for security-critical upload filtering.
- **Location(s):** `backend/app/Http/Middleware/BlockDangerousUploads.php:9-13`

### •unverified · [validation] getMimeType() relies on finfo content detection, bypassable with polyglot files
- **Evidence:** in_array($f->getMimeType(), self::BLOCKED_MIMES) — getMimeType() uses PHP finfo which reads file magic bytes. A PHP webshell prepended with a valid JPEG magic bytes (\xFF\xD8\xFF) will be detected as 'image/jpeg' by finfo, not as 'application/x-php', and will pass the blocklist check undetected. This is a well-known polyglot file bypass technique.
- **Fix:** Do not rely solely on finfo MIME detection. Also validate the file extension against an allowlist, and for image uploads use an image re-encoding step (e.g., GD/Imagick re-save) to strip embedded payloads.
- **Location(s):** `backend/app/Http/Middleware/BlockDangerousUploads.php:20`

### •unverified · [webhook] Webhook replay attack: no timestamp freshness check
- **Evidence:** `verifyWebhookSignature` validates the HMAC but never checks whether `$timestamp` is within an acceptable window (e.g. ±5 minutes). A captured valid webhook can be replayed indefinitely to re-credit a wallet or trigger repeated refunds.
- **Fix:** After HMAC check, verify `abs(time() * 1000 - (int)$timestamp) < 300000` (5-minute window) and return false if stale.
- **Location(s):** `backend/app/Services/CCPaymentService.php:492-496`

### •unverified · [webhook] No replay attack protection: webhook timestamp is never validated for staleness
- **Evidence:** verifyWebhookSignature() checks the HMAC but never compares the Timestamp header against the current time. An attacker who captures a valid signed webhook can replay it indefinitely — e.g. to replay a 'success' deposit and credit a wallet a second time.
- **Fix:** After verifying the HMAC, reject requests where abs(time()*1000 - (int)$timestamp) > 300000 (5 minutes).
- **Location(s):** `backend/app/Services/CCPaymentService.php:492-495`

### •unverified · [webhook] All webhook routes lack CSRF protection and appear to have no signature verification guard at the routing layer
- **Evidence:** The entire /webhooks prefix group has no middleware applied — no 'api' middleware (which excludes CSRF on web.php routes), no custom signature-verification middleware, and no rate-limiting. Laravel's web.php routes carry the VerifyCsrfToken middleware by default, which means either the controllers must exempt these URIs in $except[] (a common misconfiguration spot) or the app has globally disabled CSRF — either way the routing layer provides zero webhook authenticity enforcement.
- **Fix:** Apply a dedicated middleware (e.g., VerifyCCPaymentSignature) to all real webhook routes that validates the vendor's HMAC/signature header before dispatching. Additionally, apply throttle rate-limiting to prevent replay floods.
- **Location(s):** `backend/routes/web.php:189-198`

## Medium (234 kept) — rollup by category

<details><summary><b>logic</b> — 44</summary>

- •unverified **File stored to disk before ownership check — orphaned files on failed application lookup** — `backend/app/Http/Controllers/API/PartnerApplicationController.php:151-155`
- •unverified **Client-side withdrawal balance check using stale local model data** — `mobile/lib/features/savings/presentation/pages/savings_page.dart:451-453`
- •unverified **Deposit threshold only checks USD deposits — non-USD depositors never qualify** — `backend/app/Services/ReferralService.php:65-70`
- •unverified **unloadCard does not check card status — funds can be unloaded from frozen or cancelled cards** — `backend/app/Services/CardService.php:418-484`
- •unverified **sypFactor() falls back to hardcoded config value silently — limits enforcement uses stale rate** — `backend/app/Services/KycService.php:101-112`
- •unverified **Spread stored with inverted sign when buy_price < sell_price** — `backend/app/Http/Controllers/Admin/GoldPriceController.php:36-38`
- •unverified **is_active field absent from validation defaults to null/false on update — can silently deactivate a price** — `backend/app/Http/Controllers/Admin/GoldPriceController.php:33`
- •unverified **isIncoming logic: amount > 0 check overrides type-based classification** — `mobile/lib/features/transactions/data/models/transaction_model.dart:165-172`
- •unverified **Yesterday detection breaks at month boundaries** — `mobile/lib/features/transactions/presentation/pages/transactions_page.dart:517-519`
- •unverified **Race condition in toggle — non-atomic read-modify-write on is_active** — `backend/app/Http/Controllers/Admin/IntegrationController.php:90`
- •unverified **Connection test only checks credential presence, not actual connectivity — falsely logs success** — `backend/app/Http/Controllers/Admin/IntegrationController.php:68-79`
- •unverified **Ownership check in update() occurs after validation, card settings can be updated regardless of card status** — `backend/app/Http/Controllers/API/CardController.php:154-198`
- •unverified **Profile update applies before password verification — email/name changed even on wrong password** — `backend/app/Http/Controllers/Admin/ProfileController.php:39-50`
- •unverified **Balance check before NFC confirm is client-side only and not rechecked at submission** — `mobile/lib/features/qr/presentation/pages/qr_send_page.dart:210-212`
- •unverified **pay() does not check directed-request exclusivity — a third party can pay a request meant for a specific user** — `backend/app/Http/Controllers/API/PaymentRequestController.php:252-296`
- •unverified **Expiry check is performed only in presentation layer, not enforced at payment time** — `backend/app/Http/Controllers/API/PaymentRequestController.php:256-261`
- •unverified **agentCode displayed in list view to all authenticated users — potential financial abuse** — `mobile/lib/features/agents/presentation/widgets/agent_card.dart:176-178`
- •unverified **Mass assignment of `referred_by` allows referral fraud** — `backend/app/Models/User.php:44`
- •unverified **agent_code collision: random_int range is small and no uniqueness constraint enforced in PHP** — `backend/app/Models/Agent.php:72-74`
- •unverified **Silent swallow of markAsRead / markAllAsRead errors hides failed state updates** — `mobile/lib/features/notifications/presentation/pages/notifications_page.dart:114-121, 54-62`
- •unverified **`destination` field is nullable with no default — silent null may bypass destination-specific controls** — `backend/app/Http/Requests/Wallet/WithdrawRequest.php:30-35`
- •unverified **Stale cached Fee object used for financial decisions after admin update** — `backend/app/Models/Fee.php:186-191`
- •unverified **No guard against deleting the last trusted device or the caller's own active device** — `backend/app/Http/Controllers/API/DeviceController.php:157-165`
- •unverified **Recipient status not checked — transfers succeed to suspended/banned users** — `backend/app/Services/TransferService.php:99-101`
- •unverified **Notification status hardcoded as 'sent' before FCM delivery attempt** — `backend/app/Services/NotificationService.php:151-161`
- •unverified **fetched_at silently overwritten on every manual update — audit trail corrupted** — `backend/app/Http/Controllers/Admin/ExchangeRateController.php:40`
- •unverified **GoldTransactionModel defaults status to 'completed' when absent** — `mobile/lib/features/gold/data/models/gold_models.dart:120`
- •unverified **Deposit threshold check only covers USD — non-USD deposits silently excluded** — `backend/app/Services/ReferralService.php:65-70`
- •unverified **store() accepts 'environment' => 'production' with no privilege check** — `backend/app/Http/Controllers/Admin/MerchantController.php:83,88,90`
- •unverified **user daily_limit and monthly_limit default to very low values (100/500 USD) with no enforcement column** — `backend/database/migrations/2026_06_14_100011_add_user_limits_columns.php:14-15`
- •unverified **Expiry enforced only in PHP `isPending()`, not in DB — expired requests remain queryable as pending** — `backend/app/Models/PaymentRequest.php:50-59`
- •unverified **Stale cache object returned without type safety — cached ExchangeRate model may be a plain stdClass after deserialization** — `backend/app/Services/ExchangeRateService.php:48-50`
- •unverified **card_number column not enforced unique — duplicate card numbers possible** — `backend/database/migrations/2026_06_13_000003_create_virtual_cards_table.php:18`
- •unverified **Profile email/name persisted before current_password verification** — `backend/app/Http/Controllers/Admin/SettingsController.php:53-64`
- •unverified **Race condition: installer can be run concurrently before installed sentinel file is written** — `backend/app/Http/Controllers/InstallerController.php:220-223`
- •unverified **Duplicate wallet creation not prevented — no uniqueness check on (user, currency)** — `backend/app/Http/Requests/Wallet/CreateWalletRequest.php:17-22`
- •unverified **merchant_code uniqueness not guaranteed — collision possible under concurrent creation** — `backend/app/Models/Merchant.php:79-81`
- •unverified **No cross-field validation: daily_limit can exceed spending_limit; monthly_limit can be less than daily_limit** — `backend/app/Http/Requests/Card/UpdateCardRequest.php:28-48`
- •unverified **agent_code uniqueness check is a TOCTOU race condition** — `backend/app/Http/Controllers/Admin/AgentController.php:93-98`
- •unverified **Dead code: FROZEN status check is unreachable after ACTIVE check** — `backend/app/Services/StripeIssuingService.php:391-397`
- •unverified **getFeePreview computes net amount without checking sampleAmount against fee limits** — `backend/app/Services/FeeService.php:218-219`
- •unverified **Default spending and daily limits silently substituted when server omits them** — `mobile/lib/features/cards/data/models/card_model.dart:52-54`
- •unverified **Pending NFC payment payload irreversibly cleared before navigation succeeds** — `mobile/lib/shared/widgets/main_shell.dart:36-37`
- •unverified **Total cashback sum includes non-USD transactions in count but only USD in amount — data inconsistency exposes wrong balance** — `backend/app/Http/Controllers/API/CashbackController.php:24-25`

</details>

<details><summary><b>money</b> — 43</summary>

- •unverified **Amount allows up to 8 decimal places — precision mismatch risk for fiat currencies** — `backend/app/Http/Requests/Card/LoadCardRequest.php:22`, `backend/app/Http/Requests/Card/UnloadCardRequest.php:22`
- •unverified **Float arithmetic used for all money calculations risks precision loss** — `backend/app/Http/Controllers/API/SavingsController.php:82,152,192,210`
- •unverified **exchange_rates table has no `updated_at`-based staleness guard — stale rates can silently persist** — `backend/database/migrations/2026_06_14_100001_create_exchange_rates_tables.php:21`
- •unverified **transactions.softDeletes allows deleted transaction rows to disappear from audit trail** — `backend/database/migrations/2026_06_13_000004_create_transactions_table.php:99`
- •unverified **Money amounts transmitted as IEEE 754 double — precision loss risk** — `mobile/lib/features/savings/data/repositories/savings_repository.dart:58-59, 73, 86`
- •unverified **capture() does not decrement total_withdrawals / total_sent — accounting fields inconsistent** — `backend/app/Models/Wallet.php:145-157`
- •unverified **Decimal/financial settings cast to float causing binary precision loss** — `backend/app/Models/SystemSetting.php:76`
- •unverified **Sensitive financial values logged with float type — precision loss risk** — `backend/app/Services/AuditLogService.php:70, 97, 121`
- •unverified **KYC level financial limits accept numeric (float) — precision risk for money limits** — `backend/app/Http/Controllers/Admin/KycController.php:313-316, 340-343`
- •unverified **Gold price monetary fields cast to decimal:2 — precision loss on financial values** — `backend/app/Models/GoldPrice.php:20-22`
- •unverified **NFC amount formatting truncates 3+ decimal-place values silently** — `mobile/lib/features/transfer/data/nfc_hce.dart:39-43`
- •unverified **Spending limit reset (daily/monthly) happens outside the authorization DB transaction and is not atomic** — `backend/app/Services/StripeIssuingService.php:739-749`
- •unverified **handleAuthorizationReversal does not reverse card spending when the transaction is already COMPLETED** — `backend/app/Services/StripeIssuingService.php:559-569`
- •unverified **Floating-point arithmetic used for all money calculations** — `backend/app/Services/StripeIssuingService.php:401, 496, 544`
- •unverified **QR 'max send' button uses floating-point toStringAsFixed(2) which can exceed actual balance due to rounding** — `mobile/lib/features/qr/presentation/pages/qr_send_page.dart:799-802`
- •unverified **KYC limit fields cast to 'decimal:2' string, not a numeric type — arithmetic comparisons may silently fail** — `backend/app/Models/KycLevel.php:40-43`
- •unverified **Double-counting of sent amounts: debit() increments total_sent, withdraw() also increments total_withdrawals for same amount** — `backend/app/Services/WalletService.php:75, 80`
- •unverified **withdraw() fee calculation uses float multiplication — fee deducted from balance but not from wallet** — `backend/app/Services/WalletService.php:71-72, 75`
- •unverified **Floating-point double used for monetary total accumulation** — `mobile/lib/features/cashback/data/cashback_repository.dart:10, 43`
- •unverified **Recipient wallet created inside DB transaction without atomically preventing duplicate creation** — `backend/app/Services/TransferService.php:124-129`
- •unverified **Balance check for account deletion uses floating-point sum — can allow deletion with residual balance** — `backend/app/Http/Controllers/API/AuthController.php:574-575`
- •unverified **commission_rate stored and cast as float — floating-point precision risk for financial calculations** — `backend/app/Models/Agent.php:53`
- •unverified **Amount validation allows up to 8 decimal places — precision loss risk for fiat currencies** — `backend/app/Http/Requests/Transfer/TransferRequest.php:28`
- •unverified **Amount regex allows up to 8 decimal places — precision mismatch with likely monetary storage** — `backend/app/Http/Requests/Wallet/WithdrawRequest.php:22`
- •unverified **Float used for monetary amounts causes precision loss in notification body** — `backend/app/Services/NotificationService.php:16,21,27,32,38,43,49,54,60,65,126,130,137,142`
- •unverified **Monetary USD fields cast to decimal:2 — precision loss on gold pricing** — `backend/app/Models/GoldTransaction.php:30-32`
- •unverified **usd_rate_at_time cast to decimal:2 — exchange-rate precision loss** — `backend/app/Models/GoldTransaction.php:33`
- •unverified **debitGrams does not update current_value_usd; creditGrams does not update it either — stale financial field** — `backend/app/Models/GoldWallet.php:51-59`
- •unverified **balance_after in transaction log may be stale if credit() only mutates in-memory model** — `backend/app/Services/ReferralService.php:118-132`
- •unverified **referral_rewards rewards are plain decimal with no sign constraint — negative reward amounts possible** — `backend/database/migrations/2026_06_14_100010_create_referral_rewards_table.php:16-17`
- •unverified **No unique constraint on deposit_address — multiple wallets can share the same crypto address** — `backend/database/migrations/2026_06_13_000002_create_wallets_table.php:45-53`
- •unverified **Floating-point arithmetic used for financial exchange rate calculations** — `backend/app/Http/Controllers/Admin/SettingsController.php:130-132`
- •unverified **Total wallet balance summed as float — precision loss on large aggregate** — `backend/app/Http/Controllers/Admin/DashboardController.php:23`
- •unverified **SYP parseAmount multiplies by 100 on a floating-point double, introducing precision errors** — `mobile/lib/core/utils/money_formatter.dart:71`
- •unverified **amount cast to decimal:2 loses precision for non-base-10 currencies and truncates silently** — `backend/app/Models/SavingsTransaction.php:25`
- •unverified **commission_rate cast to float — same precision hazard for fee calculations** — `backend/app/Models/Merchant.php:60`
- •unverified **Wallet store endpoint has TOCTOU race: duplicate wallet creation possible** — `backend/app/Http/Controllers/API/WalletController.php:74-81`
- •unverified **Float arithmetic used for all monetary calculations — precision loss** — `backend/app/Services/WalletService.php:71, 195, 286`
- •unverified **Float arithmetic used throughout fee calculation without bcmath/Decimal, risking precision loss** — `backend/app/Models/Fee.php:125-135`
- •unverified **spending_limit allows up to 8 decimal places — money precision risk** — `backend/app/Http/Requests/Card/CreateCardRequest.php:45`
- •unverified **Floating-point cast on monetary sum risks precision loss** — `backend/app/Http/Controllers/API/CashbackController.php:24`
- •unverified **balance_after recorded before save() completes — stale snapshot on failure** — `backend/app/Services/TransactionService.php:34-35`
- •unverified **Float arithmetic used for money amounts — precision loss risk** — `backend/app/Services/TransactionService.php:18, 46`

</details>

<details><summary><b>data-exposure</b> — 41</summary>

- •unverified **PII (email, phone, full name) returned in paginated admin list with no field-level access control** — `backend/app/Http/Controllers/Admin/UserController.php:13,35-37`
- •unverified **Wrong field shown for pending balance — displays formattedBalance instead of formattedPendingBalance** — `mobile/lib/features/dashboard/presentation/widgets/balance_card.dart:214`
- •unverified **IP address trust: getClientIp() uses Laravel's default ip() which may trust attacker-controlled headers** — `backend/app/Services/AuditLogService.php:226`
- •unverified **gzip compression enabled on JSON API responses — BREACH attack surface for secrets in HTTPS responses** — `deploy/nginx/compression.conf:11`
- •unverified **Rejection reason logged to application log — PII / sensitive data exposure** — `backend/app/Http/Controllers/Admin/KycController.php:175-180`
- •unverified **Internal exception details exposed to users in SnackBar** — `mobile/lib/features/transactions/presentation/widgets/transaction_detail_sheet.dart:200`
- •unverified **debugLogDiagnostics enabled in production router — leaks route parameters including payment UUIDs to logs** — `mobile/lib/core/router/app_router.dart:54`
- •unverified **Whole device contact list uploaded to backend with no batching or size cap** — `mobile/lib/features/transfer/data/repositories/contacts_repository.dart:20-21`
- •unverified **Bulk PII export with no rate-limiting or audit trail of the export itself** — `backend/app/Http/Controllers/Admin/AuditLogController.php:85-153`
- •unverified **service_configs stores encrypted secrets in a TEXT column with no at-rest key rotation mechanism** — `backend/database/migrations/2026_06_22_100000_create_admin_config_tables.php:21`
- •unverified **CSV export leaks PII (phone, email, admin flag) with no rate-limit or row cap** — `backend/app/Http/Controllers/API/AdminController.php:1862-1875`
- •unverified **reverseTransaction error message leaks internal exception details to the client** — `backend/app/Http/Controllers/API/AdminController.php:620-622`
- •unverified **Raw exception `.toString()` exposed in UI — internal API errors leaked to users** — `mobile/lib/features/partner/presentation/pages/join_partner_page.dart:412-413, 539`
- •unverified **Agent phone number displayed and copyable without authentication gate** — `mobile/lib/features/agents/presentation/pages/agent_details_page.dart:354-355`
- •unverified **commissionRate and minAmount/maxAmount exposed in unauthenticated list response** — `mobile/lib/features/agents/data/models/agent_model.dart:19-21, 77-79`
- •unverified **2FA challenge leaks user_id and email before authentication completes** — `backend/app/Http/Controllers/API/AuthController.php:60-69`
- •unverified **.env file modification timestamp exposed in API response** — `backend/app/Http/Controllers/Admin/SystemHealthController.php:219-224`
- •unverified **settings field stored unencrypted while config and credentials are encrypted** — `backend/app/Models/Integration.php:27`
- •unverified **Integration log method persists raw payload and response arrays without sanitization, risking PII/secret leakage in logs** — `backend/app/Models/Integration.php:80-99`
- •unverified **user_id FK on agents/merchants is nullable with nullOnDelete — orphaned financial records** — `backend/database/migrations/2026_06_21_200000_add_user_id_to_partners.php:13-14`
- •unverified **Sensitive card fields not hidden: card_number_hash and cvv_encrypted partially exposed** — `backend/app/Models/CardInventory.php:33-36`
- •unverified **provider_response and failure_details in $fillable — internal provider data mass-assignable** — `backend/app/Models/Transaction.php:18-47`
- •unverified **payment_requests.payer_id uses nullOnDelete — who paid is erased when user is deleted** — `backend/database/migrations/2026_06_17_000001_create_payment_requests_table.php:23`
- •unverified **Full request body including payment payload logged at INFO level — sensitive financial data in logs** — `backend/app/Http/Controllers/Webhooks/CCPaymentWebhookController.php:26-30`
- •unverified **Sender full name exposed in recipient notification body and metadata (PII leak)** — `backend/app/Services/TransferService.php:336, 348-349`
- •unverified **Internal exception message leaked to API client** — `backend/app/Http/Controllers/API/CCPaymentController.php:120-121, 264-265`
- •unverified **Raw transaction metadata exposed in API response** — `backend/app/Http/Controllers/API/CCPaymentController.php:152, 329`
- •unverified **Error message from server exception exposed directly in UI** — `mobile/lib/features/gold/presentation/pages/gold_page.dart:518`
- •unverified **UserAvatar loads arbitrary URLs from server over plain HTTP without validation** — `mobile/lib/core/widgets/user_avatar.dart:59-65`
- •unverified **Sensitive data (exception message) shown directly to user in snackbars** — `mobile/lib/features/auth/presentation/pages/login_page.dart:140`
- •unverified **OTP value logged / exposed via generic e.toString() in SnackBar** — `mobile/lib/features/kyc/presentation/pages/kyc_page.dart:538,615`
- •unverified **card_inventory CVV stored encrypted but expiry stored in plaintext** — `backend/database/migrations/2026_06_14_100005_create_card_inventory_tables.php:16-17`
- •unverified **ticket_messages has no index on ticket_id for internal note isolation** — `backend/database/migrations/2026_06_14_100009_create_support_tables.php:37`
- •unverified **bank_accounts IBAN stored in plaintext** — `backend/database/migrations/2026_06_14_100007_create_bank_accounts_table.php:18`
- •unverified **Exception detail leaked to user in crypto withdraw error snackbar** — `mobile/lib/features/wallets/presentation/pages/crypto_withdraw_page.dart:195`
- •unverified **Recovery codes displayed in plaintext SelectableText with no copy-protection or screen capture prevention** — `mobile/lib/features/settings/presentation/pages/security_page.dart:356-370`
- •unverified **Database error message exposed verbatim to the browser** — `backend/app/Http/Controllers/InstallerController.php:111`
- •unverified **No date-range limit on export allows unbounded data extraction** — `backend/app/Http/Controllers/API/TransactionController.php:203-272`
- •unverified **Exception message from internal services leaked directly to API response** — `backend/app/Http/Controllers/API/WalletController.php:299-303`
- •unverified **auth()->id() unconditionally stored as admin_id — audit log integrity broken** — `backend/app/Models/ActivityLog.php:54`
- •unverified **Secret and plaintext recovery codes returned to caller and may be logged** — `backend/app/Services/TwoFactorService.php:67-71`

</details>

<details><summary><b>validation</b> — 38</summary>

- •unverified **Extension/MIME mismatch check omits .doc/.docx — Word files always fail validation** — `backend/app/Services/FileValidationService.php:36-44`
- •unverified **getMimeType() relies on PHP/Symfony detection — can be spoofed by polyglot files** — `backend/app/Services/FileValidationService.php:24`
- •unverified **Client-controlled MIME type stored as trusted metadata** — `backend/app/Http/Controllers/API/PartnerApplicationController.php:161`
- •unverified **USDT withdrawal address field has no format validation — any string is accepted** — `mobile/lib/features/dashboard/presentation/pages/dashboard_page.dart:1324`
- •unverified **Financial limits accept arbitrary-precision floats with no upper bound** — `backend/app/Http/Controllers/Admin/CardController.php:55-69`
- •unverified **documentType input from user passed to KycDocument without validation** — `backend/app/Services/KycService.php:445-461, 500-515`
- •unverified **getClientOriginalName() used unsanitized as stored file_name** — `backend/app/Services/KycService.php:454, 480, 508`
- •unverified **Hex-before-base64 decode order allows ambiguous signature interpretation** — `backend/app/Http/Controllers/Concerns/VerifiesTransactionAuth.php:120-130`
- •unverified **APDU response decoded with allowMalformed:true — malformed UTF-8 silently accepted as account number** — `mobile/lib/features/transfer/data/nfc_reader.dart:109`
- •unverified **public_key accepted as arbitrary string with no format or length validation** — `backend/app/Http/Controllers/API/BiometricController.php:16-28`
- •unverified **Unload amount has no upper bound — missing max validation** — `backend/app/Http/Controllers/API/CardController.php:255`
- •unverified **Unbounded per_page parameter allows full transaction history dump** — `backend/app/Http/Controllers/API/CardController.php:372`
- •unverified **No upper bound on payment request amount — allows arbitrarily large amounts** — `backend/app/Http/Controllers/API/PaymentRequestController.php:27-40`
- •unverified **FCM notification payload routed to navigation handler via unstructured toString()** — `mobile/lib/core/services/fcm_service.dart:122, 127-130`
- •unverified **Phone number prefix stripping is client-controlled and bypassable** — `frontend/src/components/auth/AuthLoginForm.tsx:27,47`
- •unverified **apiCalculate endpoint does not verify fee code exists before calculation** — `backend/app/Http/Controllers/Admin/FeeController.php:164-168`
- •unverified **fee_type taken from raw request input after validation, bypassing validated data** — `backend/app/Http/Controllers/Admin/FeeController.php:75-82`
- •unverified **$guarded = [] overrides $fillable, enabling unrestricted mass assignment** — `backend/app/Models/ExchangeRateHistory.php:21`
- •unverified **updateService: credentials stored and merged without sanitisation — nested array depth/size is unbounded** — `backend/app/Http/Controllers/Admin/SystemConfigController.php:43-59`
- •unverified **updateSeo: og_image and robots fields are stored without URL/format validation — arbitrary string injection into meta tags** — `backend/app/Http/Controllers/Admin/SystemConfigController.php:199-213`
- •unverified **No DB-level constraint prevents zero or negative payment request amounts** — `backend/database/migrations/2026_06_17_000001_create_payment_requests_table.php:17`
- •unverified **Transactions table type/category enforcement fully removed from DB layer** — `backend/database/migrations/2026_06_16_060000_relax_transactions_type_category_constraints.php:21-23`
- •unverified **User note content reflected in FCM push notification without sanitization** — `backend/app/Services/TransferService.php:336, 375`
- •unverified **Unfiltered 'type' and 'karat' filter parameters allow unvalidated DB queries** — `backend/app/Http/Controllers/API/GoldSavingsController.php:267-272`
- •unverified **getHistory: unvalidated integer input passed directly to service** — `backend/app/Http/Controllers/API/ExchangeRateController.php:96-98`
- •unverified **Phone number not validated as unique — duplicate account registration possible** — `backend/app/Http/Requests/Auth/RegisterRequest.php:20`
- •unverified **Amount regex allows up to 8 decimal places but min is 0.01 — precision mismatch enables dust amounts in intermediate conversions** — `backend/app/Http/Requests/Wallet/ConvertRequest.php:31-36`
- •unverified **Gram input formatter allows bypass — regex does not enforce maximum decimal places correctly** — `mobile/lib/features/gold/presentation/pages/gold_page.dart:609`
- •unverified **Phone number accepted with only length >= 8 — no format validation** — `mobile/lib/features/kyc/presentation/pages/kyc_page.dart:589`
- •unverified **Unwhitelisted 'type' and 'status' filter parameters** — `backend/app/Http/Requests/Admin/TransactionsIndexRequest.php:18-19`
- •unverified **min_fee / max_fee relationship not validated — max_fee can be less than min_fee** — `backend/app/Http/Controllers/Admin/SettingsController.php:79-80`
- •unverified **Unvalidated period parameter in stats() allows arbitrary scope** — `backend/app/Http/Controllers/API/TransactionController.php:140`
- •unverified **Regex allows phone-number branch to match near-empty strings (10+ whitespace/dash/paren chars)** — `backend/app/Http/Requests/Transfer/LookupRequest.php:21`
- •unverified **Unbounded file_get_contents / Storage::get on database files — OOM denial of service** — `backend/app/Http/Controllers/Admin/DatabaseBackupController.php:90, 205, 210, 228`
- •unverified **Spending/daily/monthly limit regex allows 8 decimal places — silent truncation by DB** — `backend/app/Http/Requests/Card/UpdateCardRequest.php:33,40,47`
- •unverified **agent_code supplied by caller is accepted without validation or uniqueness check** — `backend/app/Http/Controllers/Admin/AgentController.php:93`
- •unverified **No amount upper-bound validation before submitting fund/unload request** — `mobile/lib/features/cards/presentation/pages/fund_card_page.dart:40-48`
- •unverified **Uncontrolled per_page parameter allows unbounded result sets** — `backend/app/Services/TransactionService.php:120`

</details>

<details><summary><b>auth</b> — 26</summary>

- •unverified **Sessions table `user_id` has no foreign-key constraint — orphaned sessions for deleted users** — `backend/database/migrations/2026_06_13_145547_create_sessions_table.php:16`
- •unverified **No authentication/authorization middleware enforced in controller — relies entirely on route middleware** — `backend/app/Http/Controllers/Admin/UserController.php:9-99`
- •unverified **No minimum PIN length validation before sending to server** — `mobile/lib/features/savings/presentation/pages/savings_page.dart:455-456`
- •unverified **ModSecurity login rate-limit rule 1006 fires before rule 1005 increments the counter — first request always passes** — `deploy/modsecurity/owasp-crs.conf:37-45`
- •unverified **SSH iptables rate-limit race — unconditional ACCEPT after hitcount check allows excess connections** — `deploy/firewall/iptables-rules.sh:22-24`
- •unverified **transactionsProvider uses ref.read instead of ref.watch for dependency** — `mobile/lib/features/transactions/data/repositories/transaction_repository.dart:12-13`
- •unverified **Deep-link UUID path rewrite bypasses auth check ordering — unauthenticated pay links reach PayRequestPage** — `mobile/lib/core/router/app_router.dart:59-64`
- •unverified **handleCardUpdated updates VirtualCard status from untrusted webhook data without ownership verification** — `backend/app/Http/Controllers/Webhooks/StripeIssuingWebhookController.php:189-208`
- •unverified **Challenge cache key scoped to user only — multi-device users can cross-consume challenges** — `backend/app/Http/Controllers/API/BiometricController.php:109`
- •unverified **No rate-limiting on password check — brute-force current_password with profile update endpoint** — `backend/app/Http/Controllers/Admin/ProfileController.php:46`
- •unverified **DeviceService race condition: concurrent calls generate multiple device IDs** — `mobile/lib/core/services/device_service.dart:19-28`
- •unverified **Root/jailbreak check defaults to 'secure' on any exception** — `mobile/lib/core/services/screen_security_service.dart:68-71`
- •unverified **is_admin check relies on a single boolean flag with no role/permission granularity — privilege escalation if flag is flipped** — `backend/app/Http/Requests/Admin/UpdateKycRequest.php:11`
- •unverified **Previously-rejected device_id can be re-registered to create a new pending record** — `backend/app/Http/Controllers/API/DeviceController.php:34-67`
- •unverified **Rejected device record is updated (heartbeat accepted) on re-registration** — `backend/app/Http/Controllers/API/DeviceController.php:36-48`
- •unverified **Password has no complexity requirement — weak passwords accepted** — `backend/app/Http/Requests/Auth/RegisterRequest.php:21`
- •unverified **Email uniqueness check is race-condition prone — no database-level unique constraint enforcement at application layer** — `backend/app/Http/Requests/Auth/RegisterRequest.php:19`
- •unverified **No authorization check — any authenticated admin role can overwrite any ExchangeRate record (no ownership/role gate)** — `backend/app/Http/Controllers/Admin/ExchangeRateController.php:11-46`
- •unverified **isAuthenticated() only checks token existence, not validity** — `mobile/lib/features/auth/data/repositories/auth_repository.dart:225-228`
- •unverified **API key regeneration has no CSRF protection and no confirmation — irreversible action** — `backend/app/Http/Controllers/Admin/MerchantController.php:169-175`
- •unverified **2FA TOTP code field has no length or format validation before submission** — `mobile/lib/features/settings/presentation/pages/security_page.dart:175`
- •unverified **Silent auth exception swallows all errors including token theft/corruption** — `mobile/lib/main.dart:54`
- •unverified **QR auth poll endpoint unauthenticated and not rate-limited beyond generic throttle** — `backend/routes/api.php:51`
- •unverified **supports_credentials combined with wildcard allowed_methods and wildcard allowed_headers** — `backend/config/cors.php:20,27,32`
- •unverified **Card freeze action lacks biometric confirmation** — `mobile/lib/features/cards/presentation/pages/card_details_page.dart:488-492`
- •unverified **Recovery code comparison is not timing-safe** — `backend/app/Services/TwoFactorService.php:114`

</details>

<details><summary><b>kyc</b> — 12</summary>

- •unverified **KYC check on withdraw() is performed before PIN verification** — `backend/app/Http/Controllers/API/SavingsController.php:175-185`
- •unverified **KYC verifications `reviewed_by` FK has no nullOnDelete — referential integrity error if admin is deleted** — `backend/database/migrations/2026_06_14_100003_create_kyc_levels_tables.php:42`
- •unverified **Exchange sheet re-uses stale wallet balance snapshot — KYC-unverified users can still submit exchange** — `mobile/lib/features/dashboard/presentation/pages/dashboard_page.dart:1411-1414`
- •unverified **update/updateAjax bypass reviewed_by and reviewed_at — reviewer identity not recorded** — `backend/app/Http/Controllers/Admin/KycController.php:102, 214`
- •unverified **approveKyc and rejectKyc have no idempotency guard — re-approving an already-verified user silently overwrites state** — `backend/app/Http/Controllers/API/AdminController.php:407-426`
- •unverified **Already-uploaded document types remain tappable — KYC document re-upload not blocked client-side** — `mobile/lib/features/partner/presentation/pages/join_partner_page.dart:457-467`
- •unverified **rejection_reason not required when status is 'rejected' — KYC rejection can be recorded with no audit trail reason** — `backend/app/Http/Requests/Admin/UpdateKycRequest.php:19`
- •unverified **KYC bypass: `canMakeTransaction` allows PENDING/UNVERIFIED users to transact** — `backend/app/Models/User.php:197-201`
- •unverified **KYC pending_review flag uses contradictory condition (approved + no reviewer)** — `backend/app/Http/Controllers/API/KycController.php:49`
- •unverified **Daily/monthly KYC limit sums include cashback REWARD transactions via absolute value** — `backend/app/Services/TransferService.php:296-317`
- •unverified **No minimum age enforcement on date-of-birth picker** — `mobile/lib/features/auth/presentation/pages/register_page.dart:53-56`
- •unverified **KYC /levels route registered after {kyc} wildcard — route shadowing may allow unauthenticated KYC level manipulation** — `backend/routes/web.php:89-92`

</details>

<details><summary><b>config</b> — 8</summary>

- •unverified **rotate-keys.sh restarts php8.2-fpm but site config references php8.4-fpm socket — wrong service restarted** — `deploy/secrets/rotate-keys.sh:19`
- •unverified **CSP allows 'unsafe-inline' for scripts — XSS mitigations undermined** — `deploy/nginx/security-headers.conf:12`
- •unverified **Hardcoded developer path leaked into production code** — `backend/app/Services/CardService.php:227`
- •unverified **IP whitelist bypass when debug_mode is a truthy string from DB settings** — `backend/app/Services/CCPaymentService.php:508-513`
- •unverified **Empty SANCTUM_TOKEN_PREFIX allows tokens to bypass secret-scanning detection** — `backend/config/sanctum.php:68`
- •unverified **Localhost / loopback addresses hardcoded as stateful domains with no production override guard** — `backend/config/sanctum.php:21-26`
- •unverified **Wildcard allowed_methods exposes dangerous HTTP verbs to all origins** — `backend/config/cors.php:20`
- •unverified **CCPayment debug mode stored as config flag with empty IP whitelist default** — `backend/config/services.php:41-42`

</details>

<details><summary><b>idor</b> — 7</summary>

- •unverified **Mass assignment allows caller to set arbitrary user_id on integration logs** — `backend/app/Models/IntegrationLog.php:10-13`
- •unverified **User ID enumeration via SAKK account number in lookup endpoint** — `backend/app/Services/TransferService.php:42-47`
- •unverified **updateChannels: No ownership/existence guard — any admin can overwrite any NotificationChannel by raw ID** — `backend/app/Http/Controllers/Admin/SystemConfigController.php:98-110`
- •unverified **No authorization check — any admin can approve/reject any agent's documents (IDOR)** — `backend/app/Http/Controllers/Admin/AgentDocumentController.php:45,68`
- •unverified **scopeByModel skips model_id=0 due to falsy check, returning unfiltered records** — `backend/app/Models/AuditLog.php:49-56`
- •unverified **IDOR on notification mark-as-read — no ownership enforced at route level** — `backend/routes/api.php:337`
- •unverified **Route ID used directly in unique rule without type-cast — potential IDOR via type juggling** — `backend/app/Http/Requests/Admin/UpdateUserRequest.php:16,21,22`

</details>

<details><summary><b>idempotency</b> — 5</summary>

- •unverified **Double-tap / multiple submissions possible on withdraw and convert buttons** — `mobile/lib/features/dashboard/presentation/pages/dashboard_page.dart:1381, 1709`
- •unverified **Withdrawal orderId generated with only 12 random chars — collision risk under load** — `backend/app/Services/CCPaymentService.php:348`
- •unverified **Double-upload race condition: `_busy` guard set after async gap** — `mobile/lib/features/partner/presentation/pages/join_partner_page.dart:377-396`
- •unverified **Reference collision possible — Str::random(10) is not guaranteed unique** — `backend/app/Models/SavingsTransaction.php:34`
- •unverified **Card details fetched via HTTP POST with no request body / idempotency token — replay possible** — `mobile/lib/features/cards/data/repositories/card_repository.dart:110`

</details>

<details><summary><b>quality</b> — 5</summary>

- •unverified **Full user table scanned on every request — no index-assisted server-side suffix filter** — `backend/app/Http/Controllers/API/ContactController.php:49-68`
- •unverified **down() migration for referral constraints calls dropIndex on a UNIQUE index name** — `backend/database/migrations/2026_06_24_000005_add_referral_constraints.php:102`
- •unverified **down() for admin_config_tables unconditionally drops event_key/recipient columns** — `backend/database/migrations/2026_06_22_100000_create_admin_config_tables.php:79-81`
- •unverified **2FA disable dialog disposes controllers before async operation completes, then reads from them** — `mobile/lib/features/settings/presentation/pages/security_page.dart:253-258`
- •unverified **N+1 query loop in daily breakdown inside stats()** — `backend/app/Http/Controllers/API/TransactionController.php:168-179`

</details>

<details><summary><b>crypto</b> — 2</summary>

- •unverified **two_factor_secret and recovery codes stored as plain columns with no encryption marker** — `backend/database/migrations/2026_06_13_000001_create_users_table.php:44-45`
- •unverified **api_key generated with Str::random — not cryptographically suitable** — `backend/app/Models/Merchant.php:83-87`

</details>

<details><summary><b>injection</b> — 2</summary>

- •unverified **getFailedTransactions() accepts raw date strings with no validation — potential injection via whereBetween** — `backend/app/Services/AuditLogService.php:208-215`
- •unverified **APP_NAME value can break out of its double-quotes via embedded quote characters** — `backend/app/Http/Controllers/InstallerController.php:191`

</details>

## Low (62 kept) — rollup by category

<details><summary><b>validation</b> — 24</summary>

- •unverified **No minimum array-item count guard — empty or near-empty arrays waste DB chunking** — `backend/app/Http/Controllers/API/ContactController.php:22-27`
- •unverified **Unvalidated user_id query parameter used as a direct DB filter** — `backend/app/Http/Controllers/Admin/CardController.php:15`
- •unverified **Unguarded int.parse on path parameters — malformed deep links crash the app** — `mobile/lib/core/router/app_router.dart:130, 137, 172, 179, 186, 395`
- •unverified **KYC rejection reason not required when min-length enforced — empty string bypasses intent** — `backend/app/Http/Requests/Admin/KycReviewRequest.php:18`
- •unverified **Regex allows negative amounts, bypassing min:0.01 in some edge cases** — `backend/app/Http/Requests/Wallet/DepositRequest.php:22`
- •unverified **update() reads is_active from $request->boolean() bypassing the validated array — inconsistent validation** — `backend/app/Http/Controllers/Admin/IntegrationController.php:52`
- •unverified **Missing input validation on date_from / date_to allows malformed date injection** — `backend/app/Http/Controllers/Admin/AuditLogController.php:33-38, 99-104`
- •unverified **NFC UTF-16 text record decoding is incorrect (uses charCodes, not a proper UTF-16 decoder)** — `mobile/lib/features/transfer/data/nfc_reader.dart:144-145`
- •unverified **Unsanitized topic name allows FCM topic path injection** — `backend/app/Services/FCMService.php:130`
- •unverified **balance_limit cast to 'array' with no type enforcement — malformed input accepted silently** — `backend/app/Models/KycLevel.php:37`
- •unverified **Phone field accepts any non-empty string — no format validation** — `mobile/lib/features/partner/presentation/pages/join_partner_page.dart:603-609`
- •unverified **`uploadDocument` sends caller-supplied `type` string with no client-side enum guard** — `mobile/lib/features/partner/data/repositories/partner_repository.dart:86-108`
- •unverified **Search query sent to server on every keystroke with no debounce — potential enumeration / DoS** — `mobile/lib/features/agents/presentation/pages/agents_page.dart:392-393`
- •unverified **gold_prices table uses a unique constraint on karat string — no enum validation at DB level** — `backend/database/migrations/2026_06_20_100000_create_gold_prices_table.php:13`
- •unverified **distanceKmFrom crashes with null latitude/longitude** — `backend/app/Models/Agent.php:130-140`
- •unverified **Phone number regex accepts very short or malformed strings as valid identifiers** — `backend/app/Http/Requests/Transfer/TransferRequest.php:21`
- •unverified **Unvalidated per_page parameter allows arbitrary page sizes** — `backend/app/Http/Controllers/API/NotificationController.php:19`
- •unverified **Timezone field not validated against known timezone identifiers — arbitrary string accepted** — `backend/app/Http/Requests/Auth/RegisterRequest.php:23`
- •unverified **Date of birth sent as full ISO-8601 datetime instead of date-only string** — `mobile/lib/features/auth/presentation/pages/register_page.dart:163`
- •unverified **Rejection reason user input concatenated into KYC status field without sanitization** — `backend/app/Http/Controllers/Admin/AgentDocumentController.php:83`
- •unverified **Unsanitized `days` parameter passed directly to date arithmetic with no upper bound** — `backend/app/Services/ExchangeRateService.php:244`
- •unverified **per_page parameter from user input passed directly to paginate() without sanitization** — `backend/app/Http/Controllers/API/WalletController.php:127`
- •unverified **Unvalidated enum inputs for type/status filters allow internal value enumeration** — `backend/app/Http/Controllers/Admin/TransactionController.php:31,36`
- •unverified **Unvalidated date inputs passed to whereDate() may trigger DB errors leaking schema info** — `backend/app/Http/Controllers/Admin/TransactionController.php:41,44`

</details>

<details><summary><b>logic</b> — 11</summary>

- •unverified **suspend() and activate() have no idempotency guard or state transition validation** — `backend/app/Http/Controllers/Admin/UserController.php:86-98`
- •unverified **logFailure() passes context array into changes without 'before' key, causing newValues to capture the entire context blob** — `backend/app/Services/AuditLogService.php:152-158`
- •unverified **updateSettings silently ignores boolean fields absent from request, allowing partial toggle with no audit trail** — `backend/app/Http/Controllers/Admin/CardController.php:77-98`
- •unverified **Queue check marks system as online without verifying actual queue worker health** — `backend/app/Http/Controllers/Admin/SystemHealthController.php:135-153`
- •unverified **'Yesterday' date-group logic breaks at month and year boundaries** — `mobile/lib/features/notifications/presentation/pages/notifications_page.dart:225-228`
- •unverified **unreadCount failure is silently swallowed, permanently hiding the badge** — `mobile/lib/features/notifications/data/repositories/notification_repository.dart:40-42`
- •unverified **percentage_amount in breakdown not clamped to min/max fee, creating display mismatch** — `backend/app/Models/Fee.php:169`
- •unverified **show() returns inactive agents — no active() scope applied** — `backend/app/Http/Controllers/API/AgentController.php:78-84`
- •unverified **Wallet model Equatable props omit pendingBalance — equality check misses pending-balance changes** — `mobile/lib/features/wallets/data/models/wallet_model.dart:43`
- •unverified **No rate-limiting or concurrency guard on backup creation — repeated triggers exhaust disk** — `backend/app/Http/Controllers/Admin/DatabaseBackupController.php:76-129`
- •unverified **New agents default to rating=5.0 (inflated trust signal)** — `backend/app/Http/Controllers/Admin/AgentController.php:103`

</details>

<details><summary><b>data-exposure</b> — 8</summary>

- •unverified **referredUser->full_name PII written into transaction metadata and title without sanitisation** — `backend/app/Services/ReferralService.php:134-135`
- •unverified **response_note from reject() is exposed to all viewers of the payment request via present()** — `backend/app/Http/Controllers/API/PaymentRequestController.php:332`
- •unverified **forgotPassword leaks whether an email is registered (user enumeration)** — `backend/app/Http/Controllers/API/AuthController.php:399-403`
- •unverified **KYC documents served inline — sensitive PII cached in browser history** — `backend/app/Http/Controllers/Admin/SecureFileController.php:84`
- •unverified **createdAt falls back to DateTime.now() on missing/null field, corrupting grouping and display** — `mobile/lib/features/notifications/data/models/notification_model.dart:31-33`
- •unverified **Unsanitized KYC rejection reason interpolated into push notification body** — `backend/app/Services/NotificationService.php:93-101`
- •unverified **Misleading transliteration 'RUaA' for رؤى leaks mixed-case garbage onto card** — `mobile/lib/core/utils/arabic_latin.dart:34`
- •unverified **createdAt defaults to DateTime.now() when server field is absent, masking data integrity issues** — `mobile/lib/features/cards/data/models/card_model.dart:59-61`

</details>

<details><summary><b>money</b> — 5</summary>

- •unverified **Floating-point arithmetic used to compute and persist financial spread** — `backend/app/Http/Controllers/Admin/GoldPriceController.php:36-38`
- •unverified **getPurityAttribute uses integer division — precision loss** — `backend/app/Models/GoldPrice.php:44-46`
- •unverified **Float comparison for balance sufficiency check — precision mismatch risk** — `backend/app/Services/TransferService.php:136`
- •unverified **Financial amount typed and interpolated as float loses precision in admin notifications** — `backend/app/Services/AdminNotificationService.php:58,62`
- •unverified **verified_at update is a second separate UPDATE after $merchant->update() — non-atomic** — `backend/app/Http/Controllers/Admin/MerchantController.php:151-155`

</details>

<details><summary><b>quality</b> — 4</summary>

- •unverified **kpi_snapshots comment claims unique index on (kpi_name, computed_at) but no such unique constraint exists** — `backend/database/migrations/2026_06_24_000001_create_kpi_snapshots_table.php:18-19`
- •unverified **maintenance: DB::table($table)->count() called in a loop without caching — count on audit_logs/integration_logs can be expensive and block the page** — `backend/app/Http/Controllers/Admin/SystemConfigController.php:148-155`
- •unverified **GoldWallet created with no defaults in stats() — firstOrCreate with empty array can produce invalid record** — `backend/app/Http/Controllers/API/GoldSavingsController.php:297`
- •unverified **N+1 query pattern in getChartData produces 14 unbounded queries per page load** — `backend/app/Http/Controllers/Admin/DashboardController.php:54-60`

</details>

<details><summary><b>config</b> — 3</summary>

- •unverified **pre-commit hook iterates $STAGED_FILES without quoting — filenames with spaces bypass secret scanning** — `deploy/secrets/pre-commit-hook.sh:8,23`
- •unverified **path-traversal.conf blocks %2f (encoded slash) — breaks legitimate API paths containing URL-encoded characters** — `deploy/nginx/path-traversal.conf:12`
- •unverified **Artisan optimize exposes cached config/routes with embedded secrets** — `backend/app/Http/Controllers/Admin/SettingsController.php:173-174`

</details>

<details><summary><b>auth</b> — 2</summary>

- •unverified **getRate / getAllRates: no authentication or rate-limiting guard on exchange-rate endpoints** — `backend/app/Http/Controllers/API/ExchangeRateController.php:19-57`
- •unverified **AuthGuard renders SizedBox.shrink() (empty widget) before redirect on unauthenticated/error state** — `mobile/lib/shared/widgets/auth_guard.dart:19-23`

</details>

<details><summary><b>injection</b> — 2</summary>

- •unverified **Avatar upload endpoint constructed by string concatenation, allowing path traversal if ApiConstants.updateProfile is attacker-influenced** — `mobile/lib/features/settings/presentation/pages/profile_edit_page.dart:92`
- •unverified **Export filename constructed from unescaped user-supplied date values** — `backend/app/Http/Controllers/API/TransactionController.php:222`

</details>

<details><summary><b>idempotency</b> — 1</summary>

- •unverified **TXN reference generated with Str::random — not cryptographically unique under load** — `backend/app/Models/Transaction.php:78`

</details>

<details><summary><b>idor</b> — 1</summary>

- •unverified **Ownership check on wallet_id and card_id applied after filter, allowing timing difference / redundant query cost** — `backend/app/Http/Controllers/API/TransactionController.php:50-64`

</details>

<details><summary><b>kyc</b> — 1</summary>

- •unverified **Inconsistent KYC level count between comment (3-level) and progress bar (4 requirements / 2-level API)** — `mobile/lib/features/kyc/presentation/pages/kyc_page.dart:17,54,69`

</details>

## Appendix — removed false-positives (17)

Adversarial verifier judged these not exploitable at claimed severity:

- ~~[money] moveToSavings() re-fetches wallet without lockForUpdate, creating TOCTOU on the wallet balance~~ — `backend/app/Http/Controllers/API/SavingsController.php:222` — Not an exploitable TOCTOU. `moveToSavings()` is a `private` helper with exactly two callers (store() line 91, deposit() line 152), and BOTH acquire `lockForUpda
- ~~[idor] Account number is deterministically derived from user.id — IDOR vector~~ — `backend/app/Http/Controllers/API/ContactController.php:64` — The line 64 code is exactly as quoted: account_number = 'SK' + zero-padded user->id. But this is the intended public account identifier by design, not a leaked 
- ~~[idor] show() endpoint exposes payment request details to any authenticated user (IDOR)~~ — `backend/app/Http/Controllers/API/PaymentRequestController.php:243-249` — This is the documented, intended design of a shareable payment-link/QR feature, not an IDOR. The class docstring (lines 16-18) states "anyone with the link/QR (
- ~~[injection] Transaction sort field injected into ORDER BY without allowlist validation~~ — `backend/app/Http/Controllers/API/AdminController.php:468-470` — The claim is refuted by the validation layer. The controller's transactions() method (line 463) consumes $request->validated(), and TransactionsIndexRequest::ru
- ~~[data-exposure] Maintenance mode secret echoed back in API response~~ — `backend/app/Http/Controllers/API/AdminController.php:1228-1231` — The claim's core premise is incorrect. In enableMaintenance (AdminController.php:1203-1233) the "secret" is not a server-side generated value being leaked — it 
- ~~[auth] Admin fee API endpoint (apiIndex / apiCalculate) exposes fee configuration without authentication guard evidence in controller~~ — `backend/app/Http/Controllers/Admin/FeeController.php:137-157, 162-182` — Refuted by the route file. Both apiIndex (routes/api.php:320) and apiCalculate (api.php:321) are in the `fees` prefix group nested inside `Route::middleware('au
- ~~[data-exposure] OTP codes leaked in API response body~~ — `backend/app/Http/Controllers/API/KycController.php:64, 81` — The claim states the raw OTP is unconditionally returned in the JSON body, but it misreads the data flow. The controller's respond() passes $result['code'] thro
- ~~[validation] No maximum cap on grams — overflow/excessive purchase amount not validated~~ — `backend/app/Http/Controllers/API/GoldSavingsController.php:84-88` — The claimed exploit path does not exist. In buy() (GoldSavingsController.php:82-170) any large grams value flows into $totalCost/$grandTotal as a PHP float, and
- ~~[idempotency] Race condition allows double referral reward payout~~ — `backend/app/Services/ReferralService.php:85-104` — The claim correctly observes that the exists() guard at ReferralService.php:85 runs outside the DB::transaction (line 104), so two concurrent triggers (KYC webh
- ~~[idempotency] No database-level unique constraint backing idempotency check~~ — `backend/app/Services/ReferralService.php:85` — The claim's central assertion — "no database-level unique constraint backing the idempotency check" — is factually false. Migration backend/database/migrations/
- ~~[kyc] Approving an already-rejected document can silently set KYC to approved~~ — `backend/app/Http/Controllers/Admin/AgentDocumentController.php:45-66` — The claimed "silent KYC approval" is actually the intended workflow, not an exploitable defect. The approve route (routes/web.php:106) sits behind ['auth','admi
- ~~[auth] No authorization check — any authenticated user can reach admin dashboard~~ — `backend/app/Http/Controllers/Admin/DashboardController.php:17` — The claim assumes route-level guards "cannot be confirmed here," but they exist and are correctly enforced. In routes/web.php line 47, the dashboard route group
- ~~[data-exposure] KYC documents and PII exposed in admin response without field restriction~~ — `backend/app/Http/Controllers/Admin/DashboardController.php:36-40` — The claim conflates in-memory eager loading with client-side exposure. This is a server-rendered Blade endpoint (returns view('admin.dashboard', compact(...))),
- ~~[auth] poll() endpoint is unauthenticated and rate-limit-free — allows brute-force enumeration of valid QR tokens~~ — `backend/app/Http/Controllers/API/QrAuthController.php:36-65` — The claim's central evidence ("no throttling, No rate-limiting middleware is applied in this controller") is factually false. In routes/api.php the poll route (
- ~~[money] Referral reward race condition: check-then-insert without row lock~~ — `backend/app/Services/ReferralService.php:85-149` — The application-layer observation is literally true — `maybeGrant()` (line 85) does a check-then-insert with `ReferralReward::where('referred_id', ...)->exists(
- ~~[auth] Session regeneration happens after privilege check, enabling session fixation~~ — `backend/app/Http/Controllers/Admin/AuthController.php:33-41` — The claimed session-fixation defect does not hold. In Laravel, Auth::attempt() does NOT rotate the session ID on its own — the explicit $request->session()->reg
- ~~[auth] No rate limiting on admin login — brute-force unrestricted~~ — `backend/app/Http/Controllers/Admin/AuthController.php:20-47` — The claim is refuted. While the login() method in AuthController.php (lines 20-47) itself contains no inline RateLimiter call, the claim's scope ("admin login r

## Coverage
- Backend: 49 controllers, 44 models, 18 services, 18 requests, 7 middleware, 54 migrations, routes, security config.
- Frontend: 16 grouped inspectors (app routes, components, stores, hooks, lib).
- Mobile: 26 feature/core inspectors (auth, cards, wallets, transfer, gold, savings, kyc, NFC/scan, network).
- Deploy: nginx/php/mysql/redis/modsecurity/firewall/secrets.

## Method & cost note
- Inspect = sonnet (fast). Verify = opus adversarial skeptic, **stopped early to cap token burn** (~4.6M tokens spent on inspect + partial verify).
- 106 verdicts recovered from transcripts and folded in without re-spawning agents. Remaining crit/high are `•unverified` leads.
- Findings deduped by category+title stem; `×N` = merged recurring instances.