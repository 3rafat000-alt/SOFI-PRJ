# Security Re-Audit — carda-wallet (SAKK)

**Date:** 2026-06-24
**Author:** Dr. Ruth Goldberg (security-compliance-architect, Tier-1)
**Type:** TKT-003 fix verification + full codebase re-scan
**Scope:** Backend (Laravel 12/Sanctum) · Frontend (Next.js 16) · Mobile (Flutter 3.29/Riverpod)

---

## Executive Summary

Verified all 14 TKT-003 security fix categories. **12 PASS, 2 FAIL.** One regression (mobile biometric placeholder remains hardcoded — new string, same vulnerability class). Full codebase re-scan discovered **3 new critical issues** not addressed by TKT-003: wildcard abilities on every API token, 2FA bypass on unconfigured state, and plaintext password reset tokens in the database.

**Gate 5 verdict after re-audit:** CRITICAL issues remain open. Proceeding to production without fixing wildcard tokens and 2FA bypass is not recommended.

---

## TKT-003 Fix Verification Results

### 1. Sanctum Token Expiry
| Check | Expected | Actual | Verdict |
|-------|----------|--------|---------|
| `config/sanctum.php:53` | `'expiration' => env('SANCTUM_EXPIRATION', 1440)` | ✅ `'expiration' => env('SANCTUM_EXPIRATION', 1440)` | **PASS** |
| Migration exists | `*expire_existing_tokens.php` | ✅ File present | **PASS** |

### 2. PAN/CVV in API Responses
| Check | Expected | Actual | Verdict |
|-------|----------|--------|---------|
| CardController API response | Only `card_number_masked`, `last4`, `bin` | ✅ Returns `card_number_masked`, `last4`, `bin` — no raw `card_number` or `cvv` | **PASS** |
| `card_number`/`cvv` in `$hidden` | Yes | ✅ Both in `VirtualCard::$hidden` | **PASS** |
| `decrypted_*` accessors used in controllers | No | ✅ Zero references to `decrypted_card_number` or `decrypted_cvv` in any controller | **PASS** |

**Note:** `VirtualCard::getDecryptedCardNumberAttribute()` and `getDecryptedCvvAttribute()` exist but are NOT in `$appends` and are never referenced by any controller. They return `$this->card_number` without actual decryption (comment says decryption should be added in production). These accessors present a latent risk if a future developer adds them to an API response — recommend removing or implementing actual encryption.

### 3. lockForUpdate Coverage
| Service/Controller | Expected | Actual | Verdict |
|--------------------|----------|--------|---------|
| WalletService | ✅ | Lines 35, 75, 198-199 | **PASS** |
| CardService | ✅ | Lines 121, 225, 374-375, 470-471, 588-589 | **PASS** |
| TransferService | ✅ | Lines 112, 122, 130 | **PASS** |
| CCPaymentService | ✅ | Lines 411, 448, 473, 507 | **PASS** |
| GoldSavingsController | ✅ | Lines 119, 216, 220 | **PASS** |
| CCPaymentController | ✅ | Line 230 | **PASS** |
| PaymentRequestController | ✅ | Lines 128, 258 | **PASS** |
| SavingsController | ✅ | Lines 86, 145 | **PASS** |
| StripeIssuingService | ✅ | Lines 427, 502, 510, 548, 555 | **PASS** |
| ReferralService | ✅ | Lines 107, 115 | **PASS** |

### 4. KYC Auto-Approval
| Check | Expected | Actual | Verdict |
|-------|----------|--------|---------|
| Document submissions | `VerificationStatus::PENDING` | ✅ All 4 document upload methods use `PENDING` (lines 317, 373, 455, 481, 509) | **PASS** |
| Admin review | `VerificationStatus::APPROVED` | ✅ Only `reviewVerification()` uses `APPROVED` (lines 434, 556) | **PASS** |
| `requirementMet()` requires `reviewed_by` | Yes | ✅ Line 297 — requires `VerificationStatus::APPROVED` | **PASS** |

### 5. Mass Assignment — User Model
| Sensitive Field | In `$fillable`? | Verdict |
|-----------------|-----------------|---------|
| `kyc_status` | ❌ | **PASS** |
| `kyc_level` | ❌ | **PASS** |
| `status` | ❌ | **PASS** |
| `is_admin` | ❌ | **PASS** |
| `balance` | ❌ | **PASS** |
| `two_factor_enabled` | ❌ | **PASS** |
| `email_verified_at` | ❌ | **PASS** |
| `phone_verified_at` | ❌ | **PASS** |

### 6. Default PIN Removed
| Check | Expected | Actual | Verdict |
|-------|----------|--------|---------|
| `Hash::make('123456')` in AuthService | No match | ✅ Only comment remains at line 20 | **PASS** |
| `pin_code` set at registration | No | ✅ `pin_code` is `null` at registration | **PASS** |

### 7. Password Reset Token Gated
| Check | Expected | Actual | Verdict |
|-------|----------|--------|---------|
| `reset_token` returned in production | No | ✅ Gated behind `app()->environment('local')` at line 415 | **PASS** |
| Production response | Generic message only | ✅ Returns `'success' => true` + generic Arabic message | **PASS** |

### 8. Frontend Login Pages Call Real API
| Page | Calls `loginUser`? | Verdict |
|------|-------------------|---------|
| `merchant/login/page.tsx` | ✅ Line 11-12 | **PASS** |
| `agent/login/page.tsx` | ✅ Line 11-12 | **PASS** |
| `user/login/page.tsx` | ✅ Line 11-12 | **PASS** |
| `company/login/page.tsx` | ✅ Line 11-12 | **PASS** |

### 9. api.ts Exists with loginUser
| Check | Expected | Actual | Verdict |
|-------|----------|--------|---------|
| `frontend/src/lib/api.ts` | Exists | ✅ Exists (86 lines) | **PASS** |
| `loginUser` function | Calls `/auth/login` | ✅ POST to `${API_BASE}/auth/login` with phone+password | **PASS** |
| Error handling | No silent redirect | ✅ Returns error message on failure, no redirect | **PASS** |

### 10. Mobile API URL HTTPS
| Check | Expected | Actual | Verdict |
|-------|----------|--------|---------|
| Production URL | HTTPS, no LAN IP | ✅ `String.fromEnvironment('BASE_URL')` with HTTPS default: `'https://moccasin-otter-808407.hostingersite.com/api/v1'` | **PASS** |
| LAN IP removed | No hardcoded HTTP | ✅ LAN IP `http://192.168.10.158:8000` removed | **PASS** |
| Dev override | Via `--dart-define` | ✅ Comment says `--dart-define=BASE_URL=http://localhost:8000/api/v1` | **PASS** |

### 11. Mobile Biometric Token
| Check | Expected | Actual | Verdict |
|-------|----------|--------|---------|
| Hardcoded `'biometric'` string | Not present | ✅ Comment confirms removal (line 500) | **PASS** on original fix |
| Real biometric challenge token | Sent | ❌ **Still hardcoded** `'biometric_placeholder'` at line 505 | **FAIL** |

**This is a regression.** The original `'biometric'` string was replaced with `'biometric_placeholder'` — same vulnerability class, different constant. The comment at line 500-503 explicitly states: "Backend must implement real nonce-based biometric verification before this provides meaningful protection." This is an acknowledged incomplete fix.

### 12. Mobile Password Storage
| Check | Expected | Actual | Verdict |
|-------|----------|--------|---------|
| `remember_password` write | Removed | ✅ Line 95: `// 🔒 FIXED: Never persist passwords in storage` | **PASS** on write |
| `remember_password` read | Should not exist | ❌ **Still present** — `getRememberedCredentials()` at line 290 reads `'remember_password'`, `autoLoginIfRemembered()` at line 304 uses it | **FAIL** |

**Leftover code.** The write was removed but the read path persists. Mobile app still attempts to read a stored password that is no longer being written — dead code, but if a user updates from an older version that did store the password, the stale value will be used for auto-login.

---

## Full Codebase Re-Scan — New & Pre-Existing Issues

### Critical — NEW FINDINGS

#### C1: Wildcard `['*']` Abilities on Every API Token
**Location:** 6 endpoints across 4 controllers  
**Severity:** CRITICAL  
**Status:** NOT FIXED (was R1 in original report)

All token creation endpoints use `['*']` wildcard abilities, meaning any token has full API access:

| Location | Token Name | Line |
|----------|-----------|------|
| `AuthController::register()` | `auth_token` | 28 |
| `AuthController::login()` | `auth_token` | 87 |
| `AuthController::refreshToken()` | `auth_token` | 122 |
| `BiometricController::login()` | `biometric-auth` | 163 |
| `QrAuthController::approve()` | `qr_auth` | 91 |
| `AdminController::impersonate()` | `admin-impersonation` | 360 |

**Impact:** While Sanctum tokens now expire after 1440 minutes, any valid token can access every API endpoint. A leaked token from any user type (user, merchant, agent) grants full access to the entire API. The `admin-impersonation` token additionally bypasses all authorization for the impersonated user.

**Fix:** Define scoped ability lists per token type. Example:
- Auth tokens: `['wallet:read', 'wallet:write', 'card:read', 'card:write', 'transfer', 'profile']`
- QR auth tokens: `['payment-request:accept']`
- Biometric tokens: `['gold:buy', 'gold:sell']`
- Admin impersonation: Limited to specific read/audit operations with a short TTL (e.g. 15 min)

#### C2: Two-Factor Authentication Bypass
**Location:** `backend/app/Services/TwoFactorService.php:99-101`  
**Severity:** HIGH  
**Status:** NOT FIXED (was R7 in original report)

`verifyCode()` returns `true` unconditionally when `two_factor_enabled` is false or `two_factor_secret` is null. An attacker who can set `two_factor_enabled = false` (e.g., via race condition, stale admin session, or mass assignment before the fix) bypasses 2FA entirely.

**Fix:** `verifyCode()` should return `false` when 2FA is not configured. The caller should decide whether to enforce 2FA based on the user's `two_factor_enabled` flag. Never return `true` from an unconfigured security control.

#### C3: Password Reset Token Stored in Plaintext
**Location:** `backend/database/migrations/2026_06_13_125948_create_password_resets_table.php:13`  
**Severity:** HIGH  
**Status:** NOT FIXED

`$table->string('token')` stores the raw reset token in the database. Anyone with DB read access (SQL injection, leaked backup, compromised replica) can use any stored reset token to take over any account.

**Fix:** Store a SHA-256 hash of the token. Use `hash_equals(hash('sha256', $plaintextToken), $storedHash)` for comparison. Laravel's built-in password broker does this by default — the current implementation bypasses it.

---

### Pre-Existing Issues (unchanged from original)

#### P1: `$guarded = []` on Multiple Models
**Location:** 10 models  
**Severity:** MEDIUM  
**Status:** OPEN

Models `KycLevel`, `ActivityLog`, `CardPricing`, `ExchangeRate`, `ExchangeRateHistory`, `AdminNotification`, `KycDocument`, `SystemSetting`, `CardInventory`, `UserNotification` all still have `$guarded = []`. While many of these are low-risk utility models, `KycDocument` and `KycLevel` handle sensitive data and should have explicit `$fillable`.

#### P2: Path Traversal in `importCardsFromFile`
**Location:** `backend/app/Services/CardService.php:222-228`  
**Severity:** MEDIUM  
**Status:** ACCEPTED RISK (admin-only, documented R5)

#### P3: `.env` Injection in InstallerController
**Location:** `backend/app/Http/Controllers/InstallerController.php:95-99`  
**Severity:** MEDIUM  
**Status:** ACCEPTED RISK (installer not exposed in production, documented R6)

#### P4: Mobile Biometric Placeholder
**Location:** `mobile/lib/features/gold/presentation/pages/gold_page.dart:505`  
**Severity:** HIGH  
**Status:** REGRESSION — TKT-003 fix incomplete

#### P5: Plaintext PAN/CVV Accessors on VirtualCard
**Location:** `backend/app/Models/VirtualCard.php:320-329`  
**Severity:** MEDIUM  
**Status:** OPEN (latent risk)

`getDecryptedCardNumberAttribute()` and `getDecryptedCvvAttribute()` return plaintext values with a comment saying "In production, decrypt from encrypted storage." They currently do no decryption. While not in `$appends` and not called by any controller, these are a trap for future developers.

---

## Security Posture Summary

| Category | Count | Severity |
|----------|-------|----------|
| TKT-003 fixes holding | 12/14 | — |
| TKT-003 fixes regressed | 2/14 | HIGH |
| New critical findings | 3 | CRITICAL (C1) / HIGH (C2, C3) |
| Pre-existing open issues | 5 | MEDIUM-HIGH |

### Must-Fix Before Gate 5 Exit

1. **Wildcard token abilities** (C1) — Scope all 6 `createToken()` calls with minimal abilities
2. **2FA bypass** (C2) — `verifyCode()` must return `false` when 2FA is unconfigured
3. **Password reset token hashing** (C3) — Hash tokens before DB storage
4. **Mobile biometric placeholder** (P4) — Implement actual biometric challenge token flow
5. **Mobile password read cleanup** (TKT-003 #14) — Remove dead `remember_password` read code

### Should-Fix Before Production

6. **Decrypted accessors on VirtualCard** (P5) — Remove or implement actual encryption
7. **`$guarded = []` on KYC models** (P1) — Add explicit `$fillable` to `KycDocument` and `KycLevel`

---

## Files Examined

- `backend/config/sanctum.php`
- `backend/.env`, `backend/.env.example`, `backend/.env.vault`
- `backend/app/Http/Controllers/API/CardController.php`
- `backend/app/Http/Controllers/API/AuthController.php`
- `backend/app/Http/Controllers/API/GoldSavingsController.php`
- `backend/app/Http/Controllers/API/CCPaymentController.php`
- `backend/app/Http/Controllers/API/PaymentRequestController.php`
- `backend/app/Http/Controllers/API/WalletController.php`
- `backend/app/Http/Controllers/API/AdminController.php`
- `backend/app/Http/Controllers/API/QrAuthController.php`
- `backend/app/Http/Controllers/API/BiometricController.php`
- `backend/app/Services/AuthService.php`
- `backend/app/Services/KycService.php`
- `backend/app/Services/WalletService.php`
- `backend/app/Services/TransferService.php`
- `backend/app/Services/CardService.php`
- `backend/app/Services/CCPaymentService.php`
- `backend/app/Services/StripeIssuingService.php`
- `backend/app/Services/ReferralService.php`
- `backend/app/Services/TwoFactorService.php`
- `backend/app/Models/User.php`
- `backend/app/Models/VirtualCard.php`
- `backend/app/Models/KycVerification.php`
- `backend/app/Models/BankAccount.php`
- `backend/app/Models/PaymentRequest.php`
- `backend/app/Models/SavingsTransaction.php`
- `backend/app/Models/Merchant.php`
- `backend/app/Models/Agent.php`
- `backend/database/migrations/2026_06_13_125948_create_password_resets_table.php`
- `frontend/src/lib/api.ts`
- `frontend/src/app/*/login/page.tsx` (4 files)
- `mobile/lib/core/constants/api_constants.dart`
- `mobile/lib/features/gold/presentation/pages/gold_page.dart`
- `mobile/lib/features/auth/data/repositories/auth_repository.dart`

---

## Sign-off

**Re-audit by:** Dr. Ruth Goldberg (security-compliance-architect)  
**Date:** 2026-06-24  
**Overall verdict:** 12/14 TKT-003 fixes hold. 2 regressions (mobile biometric + password read). 3 new critical issues found. **Gate 5: NOT CLEAR. Must-fix items remain.**

> *"A security fix that doesn't fully close the vulnerability is not a fix — it's a diversion. The mobile biometric placeholder trades one hardcoded string for another, and wildcard tokens mean expiry is the only thing standing between a leaked token and full account compromise."*
