# SAKK Wallet — System Design Document

## Table of Contents

1. [System Architecture Overview](#1-system-architecture-overview)
2. [Authentication & Authorization Flow](#2-authentication--authorization-flow)
3. [Device Security System](#3-device-security-system)
4. [KYC Verification System](#4-kyc-verification-system)
5. [Wallet & Transaction Flows](#5-wallet--transaction-flows)
6. [P2P Transfer System](#6-p2p-transfer-system)
7. [Virtual Card System](#7-virtual-card-system)
8. [Gold Savings System](#8-gold-savings-system)
9. [Cash Savings Goals System](#9-cash-savings-goals-system)
10. [CCPayment Crypto Integration](#10-ccpayment-crypto-integration)
11. [Exchange Rate System](#11-exchange-rate-system)
12. [Fee Structure System](#12-fee-structure-system)
13. [Notification System](#13-notification-system)
14. [Referral Program System](#14-referral-program-system)
15. [Payment Request System](#15-payment-request-system)
16. [Admin Panel Architecture](#16-admin-panel-architecture)
17. [Webhook Architecture](#17-webhook-architecture)
18. [Middleware Chain](#18-middleware-chain)
19. [Security Considerations](#19-security-considerations)
20. [Performance & Caching Strategy](#20-performance--caching-strategy)

---

## 1. System Architecture Overview

### 1.1 Three-Tier Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                        MOBILE CLIENTS                               │
│  ┌─────────────────────┐  ┌─────────────────────┐                   │
│  │   Flutter App (iOS) │  │  Flutter App (Android)│                 │
│  │   Riverpod + GoRouter│  │  Riverpod + GoRouter│                 │
│  └─────────┬───────────┘  └──────────┬──────────┘                   │
│            │                         │                              │
│            └──────────┬──────────────┘                              │
│                       │ HTTPS (TLS 1.3)                             │
└───────────────────────┼────────────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────────────┐
│                     API GATEWAY (Laravel)                           │
│                                                                     │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │
│  │ Rate Limiter  │  │  Middleware   │  │   Sanctum Auth Guard    │  │
│  │ (60/min)     │  │  Pipeline    │  │   (Token-based)         │  │
│  └──────────────┘  └──────────────┘  └──────────────────────────┘  │
│                                                                     │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │                    ROUTING LAYER                              │   │
│  │  /api/v1/auth  │  /api/v1/wallets  │  /api/v1/transfer  ...  │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                     │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │                   CONTROLLER LAYER                            │   │
│  │  21 API Controllers  │  18 Admin Controllers                │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                     │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │                    SERVICE LAYER (14 Services)               │   │
│  │                                                              │   │
│  │  AuthService    │  WalletService    │  TransferService      │   │
│  │  TransactionSvc │  CardService      │  KycService           │   │
│  │  StripeIssuing  │  CCPaymentSvc     │  ExchangeRateSvc     │   │
│  │  FeeService     │  FCMService       │  NotificationSvc     │   │
│  │  ReferralSvc    │  TwoFactorService │                       │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                     │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │                    MODEL LAYER (36 Models)                    │   │
│  │  User │ Wallet │ Transaction │ VirtualCard │ Device │ ...   │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                     │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │                    DATABASE LAYER                             │   │
│  │  SQLite (Dev) / MySQL 8.4 / PostgreSQL 17 (Production)     │   │
│  └─────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    EXTERNAL SERVICES                                │
│                                                                     │
│  ┌─────────────┐  ┌──────────────┐  ┌────────────────────────┐    │
│  │  Stripe      │  │  CCPayment   │  │  FCM (Firebase Cloud   │    │
│  │  Issuing API │  │  Crypto      │  │  Messaging)            │    │
│  └─────────────┘  │  Gateway     │  └────────────────────────┘    │
│                    └──────────────┘                                │
└─────────────────────────────────────────────────────────────────────┘
```

### 1.2 Technology Stack

| Layer | Technology | Version |
|-------|-----------|---------|
| Backend Framework | Laravel | 11.x |
| PHP | PHP | 8.4 |
| Database (Dev) | SQLite | 3.x |
| Database (Prod) | MySQL / PostgreSQL | 8.4 / 17 |
| Cache | Laravel Cache (file/database) | — |
| Auth | Laravel Sanctum | 4.x |
| Encryption | Laravel Crypt (AES-256-CBC) | — |
| 2FA | pragmarx/google2fa | — |
| QR Codes | simplesoftwareio/simple-qrcode | — |
| API Client | Laravel HTTP Client | — |
| Stripe SDK | stripe/stripe-php | 16.x |
| Mobile | Flutter + Dart | 3.29 / 3.7 |

### 1.3 Core Design Principles

1. **API-First**: All features expose RESTful APIs consumed by the Flutter mobile app
2. **Dual-Currency**: Every financial operation supports USD and SYP with dynamic exchange rates
3. **Security by Default**: Device approval with 48-hour transaction hold, KYC-gated limits, encrypted card data
4. **Race-Condition Safe**: All money movements use `DB::transaction()` with `lockForUpdate()` on wallet rows
5. **Auto-Approval with Audit Trail**: KYC documents are auto-approved on submission but flagged for admin review

---

## 2. Authentication & Authorization Flow

### 2.1 Registration Flow

```
User → POST /api/v1/auth/register
  │
  ├── AuthService::register()
  │     ├── Create User record (first_name, last_name, email, phone, password)
  │     ├── Hash password (bcrypt, cost=12)
  │     ├── Create default PIN (hashed '123456')
  │     ├── Set language (default: 'ar') and timezone (default: 'Asia/Riyadh')
  │     ├── Link referrer if referral_code provided → ReferralService::attachReferrer()
  │     └── Return user (triggers Sanctum token)
  │
  ├── WalletService::ensureUserWallets()
  │     ├── Create USD wallet (daily_limit: $10,000, monthly_limit: $100,000)
  │     └── Create SYP wallet (daily_limit: 130M SYP, monthly_limit: 1.3B SYP)
  │
  ├── Issue Sanctum API token
  │
  └── Response: { user, token, wallets: [USD, SYP] }
```

### 2.2 Login Flow

```
User → POST /api/v1/auth/login
  │
  ├── Validate credentials (email + password)
  ├── Check user status (not suspended/banned)
  ├── Issue Sanctum token (device-aware)
  ├── Record login activity
  └── Response: { user, token, kyc_status }
```

### 2.3 2FA (Two-Factor Authentication)

```
Flow:
  1. User enables 2FA → POST /auth/2fa/setup
     ├── Generate secret via Google2FA
     ├── Generate QR code for authenticator app
     ├── Store encrypted secret on user record
     └── Return QR code + recovery codes (10 codes)

  2. User confirms 2FA → POST /auth/2fa/confirm
     ├── Verify OTP from authenticator app
     ├── Enable 2FA on user record (two_factor_enabled = true)
     └── Confirm recovery codes stored properly

  3. User logs in with 2FA → POST /auth/login
     ├── Return '2fa_required' flag
     ├── User submits OTP → POST /auth/2fa/verify
     └── Issue token only after OTP verification

  4. Recovery flow → POST /auth/2fa/recovery-codes
     ├── Each code can be used exactly once
     └── Generates new codes after all used
```

### 2.4 PIN Authentication

- Stored as bcrypt hash on user record
- Used for sensitive operations (card details, high-value transactions)
- Change flow: verify old PIN → set new PIN
- PIN required before viewing full card number/CVV

### 2.5 Biometric Authentication

```
POST /auth/biometric/challenge → Server issues random challenge string
POST /auth/biometric/verify → Client signs challenge with device key, server verifies

Device registration:
  POST /auth/biometric/devices → Register device public key
  GET /auth/biometric/devices → List registered devices
  DELETE /auth/biometric/devices/{id} → Remove device
```

Key points:
- Challenge is single-use (stored in DB, consumed on verify)
- Device key pairs are generated on the mobile client
- Server never stores the private key

---

## 3. Device Security System

### 3.1 Device Registration & Approval Flow

```
Every API request may include header: X-Device-Id

┌──────────────────────────────────────────────┐
│            DEVICE LIFECYCLE                   │
│                                               │
│  1. First Launch                              │
│     └── Device registers: POST /devices/register
│         ├── Store: device_id, name, platform, os_version
│         ├── Status: pending
│         └── Notify existing devices for approval
│                                               │
│  2. Approval Required                         │
│     └── Existing device approves: POST /devices/{id}/approve
│         ├── Status → approved
│         ├── transactions_locked_until → now + 48 hours
│         └── 48-hour security hold begins
│                                               │
│  3. 48-Hour Security Hold                     │
│     └── isTransactionLocked() returns true
│         ├── All money-movement endpoints blocked
│         ├── View-only access allowed
│         └── Response: 403 device_locked
│                                               │
│  4. Active                                     │
│     └── 48 hours elapsed → full access granted
│                                               │
│  5. Rejected (optional)                        │
│     └── POST /devices/{id}/reject
│         ├── Status → rejected
│         └── Permanent block on this device
└──────────────────────────────────────────────┘
```

### 3.2 EnsureDeviceCanTransact Middleware

Applied to all money-movement endpoints:
- `POST /wallets/{wallet}/withdraw`
- `POST /transfer`
- `POST /cards/{card}/load`
- `POST /cards/{card}/unload`
- `POST /savings/{savings}/deposit`
- `POST /savings/{savings}/withdraw`
- `POST /payment-requests/{paymentRequest}/pay`
- `POST /ccpayment/withdraw`

Logic flow:
```
X-Device-Id present? ─No──→ Allow (legacy/web compatibility)
        │
       Yes
        │
        ▼
Device exists for user? ─No──→ Allow (will register on next launch)
        │
       Yes
        │
        ▼
┌─────────────────┐
│ Device Status    │
├─────────────────┤
│ Pending  ───→ 403: "الجهاز بانتظار الموافقة"       │
│ Rejected ───→ 403: "تم رفض هذا الجهاز"              │
│ Locked   ───→ 403: "48 ساعة أمنية" + locked_until   │
│ Active   ───→ Allow                                 │
└─────────────────┘
```

---

## 4. KYC Verification System

### 4.1 Three-Level System

| Level | Key | Name (AR) | Requirements | Daily Limit (USD) | Max Cards |
|-------|-----|-----------|-------------|-------------------|-----------|
| 0 | unverified | غير موثّق | None | $100 | 0 |
| 1 | standard | موثّق أساسي | email + phone + id_document | $2,500 | 3 |
| 2 | verified | موثّق كامل | email + phone + id_document + selfie | $10,000 | 10 |

### 4.2 KYC Flow Diagram

```
                    ┌──────────────┐
                    │  Registration│
                    │  (Level 0)   │
                    └──────┬───────┘
                           │
                           ▼
                    ┌──────────────┐
                    │ Email Verify │ ← POST /kyc/email/send → verify
                    └──────┬───────┘
                           │
                           ▼
                    ┌──────────────┐
                    │ Phone Verify │ ← POST /kyc/phone/send → verify
                    └──────┬───────┘
                           │
                           ▼
                    ┌──────────────┐
                    │ Upload ID    │ ← POST /kyc/id-document (national_id/passport/drivers_license)
                    │ Document     │     Auto-approved, flagged for admin review
                    └──────┬───────┘
                           │
                           ▼
                    ┌──────────────────┐
                    │  LEVEL 1 (Basic) │ → can_transfer, can_withdraw, 3 cards, $2,500/day
                    └──────┬───────────┘
                           │
                           ▼
                    ┌──────────────┐
                    │ Upload Selfie│ ← POST /kyc/selfie
                    └──────┬───────┘
                           │
                           ▼
                    ┌──────────────────┐
                    │  LEVEL 2 (Full)  │ → $10,000/day, 10 cards, full access
                    └──────────────────┘
```

### 4.3 Document Auto-Approval with Admin Review Flag

```
User submits document
       │
       ▼
Store in kyc_documents table (status = 'approved')
       │
       ▼
Create kyc_verifications row (status = 'approved', reviewed_by = null)
       │
       ▼
Run syncUserLevel() → upgrade user if all requirements met
       │
       ▼
[Admin reviews later]
       │
       ├── Approve → set reviewed_by = admin_id (clear flag)
       └── Reject → set status = 'rejected', provide reason
                     └── syncUserLevel() → downgrade user to highest level still met
```

### 4.4 Level Synchronization

The `syncUserLevel()` method is the single source of truth for a user's KYC level:

1. Iterates through all levels from highest to lowest
2. For each level, checks if ALL requirements are met
3. Sets user's level to the highest fully-met level
4. Handles both upgrades AND downgrades
5. Triggers notifications on level changes

---

## 5. Wallet & Transaction Flows

### 5.1 Wallet Model

Each user has exactly two wallets created on registration:

| Field | USD Wallet | SYP Wallet |
|-------|-----------|------------|
| currency | USD | SYP |
| daily_limit | $10,000 | 130,000,000 SYP |
| monthly_limit | $100,000 | 1,300,000,000 SYP |
| min_balance | 0 | 0 |
| is_frozen | false | false |

### 5.2 Deposit Flow

```
POST /wallets/{wallet}/deposit
  │
  ├── Validate amount > 0
  ├── WalletService::deposit()
  │     ├── DB::transaction()
  │     │     ├── wallet->credit($amount)
  │     │     ├── wallet->increment('total_deposits', $amount)
  │     │     └── Create Transaction record
  │     │           type: deposit
  │     │           status: completed
  │     │           balance_before, balance_after
  │     └── Return transaction
  └── Response: { success, transaction, new_balance }
```

### 5.3 Withdrawal Flow

```
POST /wallets/{wallet}/withdraw
  ── Middleware: EnsureDeviceCanTransact ──
  │
  ├── Validate amount > 0
  ├── Check KYC permission: can_withdraw?
  ├── Check daily/monthly limits
  ├── WalletService::withdraw()
  │     ├── DB::transaction()
  │     │     ├── Calculate fee (1% example)
  │     │     ├── wallet->debit($amount)
  │     │     ├── wallet->increment('total_withdrawals', $amount)
  │     │     └── Create Transaction
  │     │           type: withdrawal
  │     │           status: processing
  │     │           fee, net_amount
  │     └── Return transaction
  └── Response: { success, transaction, fee }
```

### 5.4 Currency Conversion Flow

```
POST /wallets/convert
  │
  ├── Validate: from_wallet, to_wallet, amount, direction (usd_to_syp / syp_to_usd)
  ├── Fetch active ExchangeRate
  ├── WalletService::convert()
  │     ├── DB::transaction()
  │     │     ├── Fetch sell_rate or buy_rate based on direction
  │     │     ├── Calculate converted = amount × rate
  │     │     ├── fromWallet->debit(amount)
  │     │     ├── toWallet->credit(converted)
  │     │     ├── Update totals (total_sent, total_received)
  │     │     └── Create Transaction
  │     │           type: exchange
  │     │           status: completed
  │     │           metadata: { direction, rate, from_amount, to_amount }
  │     └── Return transaction
  └── Response: { success, transaction, from_amount, to_amount, rate }
```

### 5.5 Transaction Types (Enum)

| Type | Code | Direction |
|------|------|-----------|
| Deposit | `deposit` | Credit |
| Withdrawal | `withdrawal` | Debit |
| Transfer Out | `transfer_out` | Debit |
| Transfer In | `transfer_in` | Credit |
| Card Payment | `card_payment` | Debit |
| Card Load | `card_load` | Debit |
| Card Unload | `card_unload` | Credit |
| Exchange | `exchange` | Both |
| Fee | `fee` | Debit |
| Reward | `reward` | Credit |
| Refund | `refund` | Credit |

### 5.6 Transaction Statuses (Enum)

| Status | Description |
|--------|-------------|
| `pending` | Initial state for async operations |
| `processing` | In progress (withdrawals, card payments) |
| `completed` | Successfully settled |
| `failed` | Failed permanently |
| `refunded` | Reversed/voided |
| `cancelled` | Cancelled by user or system |

### 5.7 Transaction Categories (Enum)

| Category | Purpose |
|----------|---------|
| `wallet` | Direct wallet operations |
| `p2p` | Peer-to-peer transfers |
| `card` | Card transactions |
| `crypto` | Crypto deposits/withdrawals (CCPayment) |
| `exchange` | USD ↔ SYP conversion |
| `fee` | System fees |
| `reward` | Cashback and referral rewards |
| `gold` | Gold buy/sell |
| `savings` | Savings goal deposits/withdrawals |

### 5.8 Race Condition Prevention

All money movements follow this pattern:

```
DB::transaction(function () {
    $wallet = Wallet::lockForUpdate()->find($walletId);
    // or: Wallet::where('user_id', $uid)->where('currency', $c)->lockForUpdate()->first();

    // Read balance under lock
    $balanceBefore = $wallet->balance;

    // Check sufficient funds (for debits)
    if ($wallet->available_balance < $amount) {
        throw new \RuntimeException('Insufficient balance');
    }

    // Execute credit/debit
    $wallet->debit($amount); // or $wallet->credit($amount)

    // Create transaction record
    Transaction::create([...]);
});
```

The `lockForUpdate()` ensures no two concurrent requests can simultaneously read the same balance and both proceed with insufficient funds.

---

## 6. P2P Transfer System

### 6.1 Recipient Resolution

Users can send money to other SAKK users resolved by:

| Method | Example | Priority |
|--------|---------|----------|
| Account Number | `SK00000042` | 1 (checked first) |
| Email | `user@example.com` | 2 |
| SAKK Tag | `@username` or `#tag` | 3 |
| Phone | `+963912345678` | 4 |

### 6.2 Transfer Flow

```
POST /transfer
  ── Middleware: EnsureDeviceCanTransact ──
  │
  ├── Validate: recipient_identifier, amount, currency, note (optional)
  ├── TransferService::resolveRecipient(identifier)
  ├── Validate:
  │     ├── sender !== recipient
  │     ├── amount > 0
  │     ├── currency in [USD, SYP]
  │     ├── sender not suspended/banned
  │     └── KYC limits check (single, daily, monthly, permission)
  │
  ├── TransferService::transfer()
  │     ├── DB::transaction()
  │     │     ├── lockForUpdate: sender wallet
  │     │     ├── lockForUpdate (or create): recipient wallet
  │     │     ├── Check frozen status
  │     │     ├── senderWallet->debit(amount)
  │     │     ├── recipientWallet->credit(amount)
  │     │     ├── Create OUTGOING transaction (sender's ledger, -amount)
  │     │     ├── Create INCOMING transaction (recipient's ledger, +amount)
  │     │     ├── Notify recipient (in-app + FCM push)
  │     │     ├── Notify sender (in-app + FCM push)
  │     │     └── Credit cashback to sender (1% of transfer amount)
  │     └── Return { from_transaction, to_transaction, amount, currency, note, recipient }
  └── Response: transfer result
```

### 6.3 Cashback on Transfers

Each P2P transfer automatically rewards the sender with 1% cashback:

```
Cashback is non-critical — failures are logged and silently ignored
the transfer is never rolled back due to cashback failure.

Cashback transaction:
  type: reward
  category: reward
  amount: amount × 0.01
  title: 'كاش باك على تحويل'
```

---

## 7. Virtual Card System

### 7.1 Card Architecture

SAKK supports two card provisioning methods:

| Method | Provider | Description |
|--------|----------|-------------|
| Local Cards | Self-issued | Card numbers generated in-app with Luhn validation, stored encrypted |
| Stripe Issuing | Stripe | Real cards issued via Stripe Issuing API, real-time authorization |

### 7.2 Local Card Flow

```
Create Card → POST /cards
  │
  ├── KYC check (requires Level 2 for default pricing)
  ├── Verify USD wallet has sufficient balance for purchase price
  ├── Charge purchase fee from USD wallet
  ├── Create VirtualCard record:
  │     ├── card_number: Generated (Luhn-valid) or from CardInventory
  │     ├── cvv: Generated (3 digits)
  │     ├── expiry: now + 3 years
  │     ├── cardholder_name: User's full name (uppercase)
  │     ├── balance: 0
  │     ├── status: active
  │     ├── spending_limit: $5,000 (default)
  │     ├── daily_limit: $1,000
  │     ├── monthly_limit: $10,000
  │     └── features: online, international, contactless enabled; ATM disabled
  │
  └── Return card summary: id, uuid, brand, last4, expiry, status

Load Card → POST /cards/{card}/load
  ── Middleware: EnsureDeviceCanTransact ──
  │
  ├── Verify card ownership
  ├── Check card status (active, not frozen)
  ├── Validate amount within pricing limits (min/max load)
  ├── Calculate load fee (percentage + fixed)
  ├── DB::transaction():
  │     ├── Debit wallet (amount + fee)
  │     ├── Credit card balance
  │     ├── Create wallet transaction (type: card_load)
  │     └── Return result
  └── Response: { amount_loaded, fee, total_debited, card_balance, wallet_balance }

Unload Card → POST /cards/{card}/unload
  ── Middleware: EnsureDeviceCanTransact ──
  │
  ├── Reverse flow: debit card → credit wallet
  └── No fee for unloading

Freeze/Unfreeze → POST /cards/{card}/freeze | /unfreeze
  ├── Toggle card status between 'active' and 'frozen'
  └── Frozen cards block: loads, unloads, and stripe authorizations

Cancel Card → POST /cards/{card}/cancel
  ├── DB::transaction():
  │     ├── If balance > 0: refund to wallet
  │     ├── Set status: cancelled
  │     └── Set balance: 0
  └── Response: { refunded, message }
```

### 7.3 Card Details Security

```
GET (via POST) /cards/{card}/details
  │
  ├── Verify PIN (required before revealing sensitive data)
  ├── CardService::getCardDetails()
  │     └── Return: card_number, cvv, expiry_month, expiry_year, cardholder_name
  └── Response: full card details (encrypted in transit)
```

### 7.4 Stripe Issuing Flow

```
Issue Stripe Card → POST /cards/stripe/issue
  │
  ├── Check KYC Level ≥ 2
  ├── Ensure Stripe Cardholder exists (create if not)
  │     └── POST /issuing/cardholders → stores stripe_cardholder_id on User
  ├── Check card limit for KYC level
  ├── DB::transaction():
  │     ├── POST /issuing/cards → creates card via Stripe API
  │     ├── Local VirtualCard record with provider = 'stripe'
  │     ├── Store provider_card_id, masked number, expiry
  │     └── Return card summary
  └── Response: { id, uuid, last4, brand, expiry, status }

Get Stripe Card Details → POST /cards/{card}/stripe/details
  ├── Retrieve from Stripe: GET /issuing/cards/{id} (expand: ['number', 'cvc'])
  └── WARNING: Sensitive data, only returned to verified user
```

### 7.5 Stripe Real-Time Authorization (Webhook)

Critical path: 2-second timeout for `issuing_authorization.request`:

```
Stripe → POST /webhooks/stripe/issuing
  │
  ├── Verify webhook signature (Webhook::constructEvent)
  ├── Determine event type:
  │
  ├── issuing_authorization.request (2s timeout):
  │     ├── Find local card by provider_card_id
  │     ├── Check: card active? frozen?
  │     ├── Check: wallet balance ≥ amount
  │     ├── Check: spending limits (daily/monthly/per-transaction)
  │     ├── Check: merchant category blocked? (adult, gambling, money transfer)
  │     ├── Check: international allowed?
  │     ├── APPROVE:
  │     │     ├── lockForUpdate wallet
  │     │     ├── increment reserved_balance (hold funds)
  │     │     ├── Create pending Transaction (status: processing)
  │     │     ├── Increment card daily/monthly spent
  │     │     └── Return { approved: true }
  │     └── DECLINE:
  │           └── Return { approved: false, reason: '...' }
  │
  ├── issuing_authorization.capture (settlement):
  │     ├── Find pending Transaction by authorization_id
  │     ├── Decrement reserved_balance
  │     ├── Actually debit wallet
  │     └── Mark transaction completed
  │
  └── issuing_authorization.reversal (void/refund):
        ├── Find Transaction by authorization_id
        ├── Release reserved funds or refund to wallet
        ├── Reverse card spending counters
        └── Mark transaction refunded
```

### 7.6 Card Inventory System

Cards can be imported in bulk for pre-provisioned issuance:

```
CSV Format: card_number,cvv,expiry_month,expiry_year,brand
Example: 4111111111111111,123,12,2028,visa

Import Flow:
  1. Parse CSV line by line
  2. Validate card number (Luhn check)
  3. Check for duplicates (SHA-256 hash of card number)
  4. Encrypt card number (Crypt::encryptString) and CVV
  5. Store encrypted in card_inventory table
  6. Mark as unassigned

Assignment:
  - When creating a local card, optionally pull from inventory
  - Mark is_assigned = true, link to user
```

---

## 8. Gold Savings System

### 8.1 Gold Wallet

Each user has a dedicated gold wallet tracking grams of gold owned:

```
GoldWallet:
  user_id (FK → users)
  balance (decimal, in grams)
  total_bought (decimal, lifetime grams bought)
  total_sold (decimal, lifetime grams sold)
```

### 8.2 Gold Price Feed

```
GET /gold/prices
  │
  ├── Fetch current gold price per gram from GoldPrice model
  ├── Returns buy_price and sell_price (with spread)
  └── Prices are in USD (displayed as $/g)

Price sources:
  - GoldPrice entries (configurable by admin)
  - buy_price: user buys gold at this price
  - sell_price: user sells gold at this price (lower, due to spread)
```

### 8.3 Gold Buy/Sell Flow

```
Buy Gold → POST /gold/buy
  │
  ├── Input: gram_amount, wallet_id (USD wallet)
  ├── Fetch current buy_price per gram
  ├── Calculate total_cost = gram_amount × buy_price
  ├── DB::transaction():
  │     ├── Check USD wallet balance ≥ total_cost
  │     ├── Debit USD wallet
  │     ├── Credit gold wallet (increment balance in grams)
  │     ├── Create Transaction (type: fee/gold purchase)
  │     └── Create GoldTransaction (type: buy, grams, price_per_gram, total)
  └── Response: { gold_balance, grams, cost }

Sell Gold → POST /gold/sell
  │
  ├── Input: gram_amount, wallet_id (USD wallet to receive)
  ├── Fetch current sell_price per gram
  ├── Calculate total_return = gram_amount × sell_price
  ├── DB::transaction():
  │     ├── Check gold wallet balance ≥ gram_amount
  │     ├── Debit gold wallet
  │     ├── Credit USD wallet
  │     ├── Create Transaction (type: gold sale)
  │     └── Create GoldTransaction (type: sell)
  └── Response: { gold_balance, grams_sold, total_return }
```

---

## 9. Cash Savings Goals System

### 9.1 Savings Goal Model

```
SavingsGoal:
  user_id (FK → users)
  name (e.g., "New Car", "Emergency Fund")
  target_amount (decimal)
  current_amount (decimal)
  currency (USD or SYP)
  target_date (nullable date)
  status: active | completed | closed
  auto_deduct (boolean, future feature)
  auto_deduct_amount (nullable)
  auto_deduct_frequency (nullable)
```

### 9.2 Savings Goal Flow

```
Create Goal → POST /savings
  │
  ├── Validate: name, target_amount, currency, target_date (optional)
  └── Create SavingsGoal record (current_amount = 0)

Deposit → POST /savings/{savings}/deposit
  ── Middleware: EnsureDeviceCanTransact ──
  │
  ├── Input: amount, wallet_id
  ├── Validate goal is active
  ├── DB::transaction():
  │     ├── Debit wallet
  │     ├── Increment savings current_amount
  │     ├── Create SavingsTransaction
  │     └── If deposit reaches target → auto-complete goal
  └── Response: { current_amount, target_amount, progress_percent }

Withdraw → POST /savings/{savings}/withdraw
  ── Middleware: EnsureDeviceCanTransact ──
  │
  ├── Reverse flow (debit savings → credit wallet)
  └── Creates withdrawal SavingsTransaction

Close Goal → POST /savings/{savings}/close
  ├── If current_amount > 0: refund to wallet
  └── Set status: closed
```

---

## 10. CCPayment Crypto Integration

### 10.1 Architecture

```
Mobile App                     Backend                      CCPayment API
    │                            │                              │
    │  POST /ccpayment/deposit   │                              │
    │ ─────────────────────────► │                              │
    │                            │  POST /getOrCreateAppDepositAddress
    │                            │ ────────────────────────────► │
    │                            │                              │
    │  { address, memo }         │  { address, memo }           │
    │ ◄───────────────────────── │ ◄──────────────────────────── │
    │                            │                              │
    │  User sends crypto to      │                              │
    │  the address               │                              │
    │ ──────────────────────────────────────────────────────────►│
    │                            │                              │
    │                            │  Webhook: deposit.success    │
    │                            │ ◄──────────────────────────── │
    │                            │                              │
    │                            │  Credit wallet, create       │
    │                            │  transaction (completed)     │
    │                            │                              │
    │  GET /ccpayment/deposit/   │                              │
    │      {reference}/status    │                              │
    │ ◄───────────────────────── │                              │
```

### 10.2 HMAC-SHA256 Authentication

```
Every CCPayment API request:
  1. timestamp = current_time_ms (milliseconds)
  2. body = JSON.stringify(payload)
  3. signText = appId + timestamp + body
  4. signature = HMAC-SHA256(signText, appSecret)

Headers:
  Appid: {appId}
  Sign: {signature}
  Timestamp: {timestamp}
```

### 10.3 Supported Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | `/getOrCreateAppDepositAddress` | Get deposit address for user |
| POST | `/createAppOrderDepositAddress` | Create order-specific deposit |
| POST | `/getAppDepositRecord` | Query single deposit |
| POST | `/getAppDepositRecordList` | List deposit records |
| POST | `/applyAppWithdrawToNetwork` | Withdraw to external address |
| POST | `/applyAppWithdrawToCwallet` | Withdraw to CWallet user |
| POST | `/getAppWithdrawRecord` | Query withdrawal status |
| POST | `/getAppCoinAssetList` | Merchant coin balances |
| POST | `/getWithdrawFee` | Get withdrawal network fee |

### 10.4 Coin Mapping

| Symbol | Chain | Coin ID |
|--------|-------|---------|
| USDT | TRC20 | 1280 |
| USDT | ERC20 | 1 |
| USDT | BEP20 | 1027 |
| BTC | BTC | 1027 |
| ETH | ERC20 | 1 |

### 10.5 Webhook Handling

```
CCPayment → POST /webhooks/ccpayment (via webhook callback URL)
  │
  ├── Verify HMAC-SHA256 signature
  ├── Verify webhook IP against whitelist (CIDR supported)
  ├── Determine event type (deposit / withdrawal)
  │
  ├── deposit webhook:
  │     ├── Find Transaction by reference
  │     ├── Update status based on CCPayment status
  │     │     success → completed
  │     │     failed/cancelled → failed
  │     ├── If completed: credit wallet with amount
  │     └── Return 200 OK
  │
  └── withdrawal webhook:
        ├── Find Transaction by orderId
        ├── Update status
        ├── If failed: refund wallet (credit back)
        └── Return 200 OK
```

---

## 11. Exchange Rate System

### 11.1 Single-Row Design

The exchange rate system uses a simplified single-row approach:

- Only USD/SYP pair is tracked
- Single base rate with spread for buy/sell differential
- Configurable by admin via admin panel

```
ExchangeRate (single active row):
  from_currency: 'USD'
  to_currency: 'SYP'
  rate: 13000.00          # 1 USD = 13000 SYP (mid-rate)
  buy_rate: 12935.00      # User buys USD (sells SYP) = worse rate
  sell_rate: 13065.00     # User sells USD (buys SYP) = worse rate
  spread: 2.0             # 2% total spread
  source: 'manual'
```

### 11.2 Spread Calculation

```
spread = 2.0%
half_spread = 2.0 / 200 = 0.01 (converted to decimal, split evenly)

buy_rate = rate × (1 - half_spread)  = 13000 × 0.99 = 12870
sell_rate = rate × (1 + half_spread) = 13000 × 1.01 = 13130

When user converts USD → SYP:
  Uses sell_rate (worse for user, they get less SYP per USD)
When user converts SYP → USD:
  Uses buy_rate inverted (worse for user, they pay more SYP per USD)
```

### 11.3 Rate History

All rate changes are recorded in `exchange_rate_history` for audit and charting:

```
ExchangeRateHistory:
  from_currency, to_currency, rate, buy_rate, sell_rate, source, recorded_at
```

### 11.4 Caching

```
Cache key: exchange_rate_usd_syp
Cache TTL: 300 seconds (5 minutes)
Cache cleared on rate update → immediate consistency for admin changes
```

---

## 12. Fee Structure System

### 12.1 Dynamic Fee Model

Fees are stored in the `fees` table with configurable parameters:

```
Fee:
  code: string (unique)
  type: deposit | withdrawal | card | transfer
  name: string (human-readable, English)
  name_ar: string (human-readable, Arabic)
  currency: USD | SYP
  fixed_amount: decimal (fixed fee per transaction)
  percentage: decimal (percentage of amount)
  min_fee: decimal (minimum fee, if percentage is used)
  max_fee: decimal (maximum fee, if percentage is used)
  min_amount: decimal (minimum transaction amount)
  max_amount: decimal (maximum transaction amount)
  is_active: boolean
  sort_order: integer
```

### 12.2 Predefined Fee Codes

| Code | Type | Purpose |
|------|------|---------|
| `deposit_usdt` | deposit | USDT deposit via CCPayment |
| `withdraw_usdt` | withdrawal | USDT withdrawal via CCPayment |
| `card_fund` | card | Card loading fee |
| `card_creation` | card | One-time card creation fee |

### 12.3 Fee Calculation Logic

```
Fee = fixed_amount + (amount × percentage / 100)
Fee = clamp(Fee, min_fee, max_fee)  // if min/max are set

If amount < min_amount or (max_amount AND amount > max_amount):
  Return error: 'amount_out_of_range'
```

---

## 13. Notification System

### 13.1 Two-Channel Notification

| Channel | Mechanism | Purpose |
|---------|-----------|---------|
| In-App | `user_notifications` table | In-app notification center |
| Push | FCM (Firebase Cloud Messaging) | Push notification to mobile device |

### 13.2 Notification Models

```
UserNotification:
  user_id (FK → users)
  uuid (unique identifier)
  template_code (e.g., 'p2p_received', 'kyc_level_up')
  channel: 'in_app' | 'push' | 'both'
  title (string)
  body (text)
  data (JSON, additional payload)
  read_at (nullable timestamp)
  sent_at (timestamp)
  status: 'sent' | 'delivered' | 'read'
```

### 13.3 Notification Triggers

| Event | Template Code | Recipient |
|-------|--------------|-----------|
| P2P money received | `p2p_received` | Recipient |
| P2P money sent | `p2p_sent` | Sender |
| KYC level upgrade | `kyc_level_up` | User |
| KYC document received | `kyc_document_received` | User |
| KYC document rejected | `kyc_rejected` | User |
| New device registration | `device_registered` | User (existing devices) |
| Payment request received | `payment_request_received` | Payer |
| Payment request paid | `payment_request_paid` | Requester |
| Card transaction | `card_transaction` | Card owner |
| Savings goal reached | `savings_goal_completed` | User |

### 13.4 FCM Integration

```
FCMService::send(fcm_token, title, body, data)
  │
  ├── Build Firebase Cloud Messaging payload
  ├── Send via Laravel HTTP Client → POST https://fcm.googleapis.com/fcm/send
  ├── Handle response
  └── Log success/failure (non-critical, never blocks main operation)
```

---

## 14. Referral Program System

### 14.1 Flow

```
1. User A shares their referral code (@username or referral_code)
2. User B registers with User A's code
3. User B completes KYC verification (Level 2)
4. User B deposits ≥ $100 USD
5. System grants reward to User A (idempotent, once per referred user)
6. User A receives in-app notification + wallet credit
```

### 14.2 Reward Configuration

```
Referral reward:
  Default: $5.00 USD
  Configurable by admin (system_settings key: 'referral_bonus_referrer')
  Currency: USD (always)
  Payout: wallet credit on referrer's USD wallet

Referred user must:
  - Be KYC-verified (kyc_status = verified)
  - Have deposited ≥ $100 USD (cumulative, across all deposits)
```

### 14.3 Idempotency

The `maybeGrant()` method ensures each referred user generates at most one reward:
- Checks `ReferralReward` table for existing entry
- If exists → return (no double payouts)
- If both conditions met (KYC + deposit) → grant once

---

## 15. Payment Request System

### 15.1 Flow

```
Request Money:
  User A → POST /payment-requests
    ├── Input: amount, currency, description, payer_identifier (optional)
    ├── Create PaymentRequest (status: pending)
    ├── Generate payment link URL
    └── Share link/QR code with payer

Pay Request:
  User B → POST /payment-requests/{id}/pay
    ── Middleware: EnsureDeviceCanTransact ──
    ├── Transfer funds from payer to requester
    ├── Same-currency, uses P2P transfer logic
    └── Mark request as paid

Other Actions:
  Accept/Reject (by payee): POST /payment-requests/{id}/accept|reject
  Cancel (by requester): POST /payment-requests/{id}/cancel
```

---

## 16. Admin Panel Architecture

### 16.1 Admin Endpoints (18 Controllers, 40+ Endpoints)

All admin routes are prefixed with `/api/v1/admin` and protected by two middlewares:

1. `auth:sanctum` — User must be authenticated
2. `admin` — Custom middleware checking `user.is_admin === true`
3. `throttle:admin` — Stricter rate limiting (30 req/min)

### 16.2 Admin Capabilities

| Category | Endpoints |
|----------|-----------|
| Dashboard | `GET /dashboard` — Overview stats |
| Users | CRUD + impersonation |
| KYC | List documents, approve/reject verifications |
| Transactions | List, detail, reverse |
| Wallets | List, freeze/unfreeze |
| Cards | Full lifecycle + limit updates |
| System Settings | Read/write all settings |
| Fees & Limits | Read/update fee structures |
| Exchange Rates | Read/update USD/SYP rate |
| Activity Logs | View all user activity |
| Notifications | View + send push notifications |
| Maintenance | Enable/disable maintenance mode |
| Currencies | Manage supported currencies |
| Referrals | Stats + config |
| Reports | Generate reports |
| Export | CSV export by type |
| Card Inventory | View/import card inventory |
| Card Pricing | Update card pricing tiers |

### 16.3 Impersonation

```
POST /admin/users/{id}/impersonate
  ├── Admin temporarily logs in as user
  ├── Used for debugging and support
  ├── Logged in activity logs
  └── Clear impersonation on logout
```

---

## 17. Webhook Architecture

### 17.1 Stripe Issuing Webhook

```
Endpoint: POST /api/webhooks/stripe/issuing
Auth: Stripe-Signature header (HMAC-SHA256 with webhook secret)
Timeout: 2 seconds for authorization.request

Events handled:
  - issuing_authorization.request → approve/decline in real-time
  - issuing_authorization.capture → settle pending transaction
  - issuing_authorization.reversal → void/refund
```

### 17.2 CCPayment Webhook

```
Endpoint: POST /api/webhooks/ccpayment (dynamic URL from Integration model)
Auth: HMAC-SHA256 signature + IP whitelist (CIDR supported)

Events handled:
  - deposit webhook → credit wallet on success
  - withdrawal webhook → finalize or refund on failure

Dev endpoints:
  - GET /webhooks/ccpayment/info → show webhook configuration
  - POST /webhooks/ccpayment/test/deposit → simulate deposit webhook
  - POST /webhooks/ccpayment/test/withdraw → simulate withdrawal webhook
```

### 17.3 Webhook Security

```
Stripe:
  - Verify using Webhook::constructEvent()
  - Secret key from services.stripe.issuing_webhook_secret

CCPayment:
  - Verify HMAC-SHA256(appId + timestamp + body, appSecret)
  - IP whitelist check with CIDR support
  - Optional debug mode bypasses IP check (for development)
```

---

## 18. Middleware Chain

### 18.1 Global Middleware (applied to all routes)

```
1. EncryptCookies
2. AddQueuedCookiesToResponse
3. StartSession
4. ShareErrorsFromSession
5. SubstituteBindings
```

### 18.2 API Route Middleware

```
Request → throttle:api → auth:sanctum → [per-route middleware] → Controller
                              │
                              ▼
                    EnsureDeviceCanTransact  (money-movement routes only)
```

### 18.3 Admin Route Middleware

```
Request → auth:sanctum → admin → throttle:admin → AdminController
```

The `admin` middleware:
```
class AdminMiddleware {
    handle($request, $next) {
        if (!$request->user()?->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }
        return $next($request);
    }
}
```

### 18.4 Route-Specific Middleware Chain Examples

```
Deposit:     throttle:api → auth:sanctum → Controller
Withdraw:    throttle:api → auth:sanctum → EnsureDeviceCanTransact → Controller
Transfer:    throttle:api → auth:sanctum → EnsureDeviceCanTransact → Controller
Card Load:   throttle:api → auth:sanctum → EnsureDeviceCanTransact → Controller
Admin:       throttle:api → auth:sanctum → admin → throttle:admin → Controller
```

---

## 19. Security Considerations

### 19.1 Data Encryption

| Data Type | Method | Location |
|-----------|--------|----------|
| Passwords | bcrypt (cost=12) | `users.password` |
| PIN Codes | bcrypt | `users.pin_code` |
| Card Numbers | AES-256-CBC (Crypt::encryptString) | `card_inventory.card_number_encrypted` |
| CVV | AES-256-CBC (Crypt::encryptString) | `card_inventory.cvv_encrypted` |
| 2FA Secret | Encrypted (Crypt) | `users.two_factor_secret` |
| Card Number Hash | SHA-256 (for duplicate check) | `card_inventory.card_number_hash` |
| API Tokens | Laravel Sanctum (hashed) | `personal_access_tokens` |
| Transport | TLS 1.3 | All API endpoints |

### 19.2 Race Condition Prevention

- All monetary transactions use `DB::transaction()` with `lockForUpdate()`
- Wallet balances are always read and written within the same transaction
- Card inventory assignment uses `lockForUpdate()` to prevent double-assignment

### 19.3 Abuse Prevention

| Measure | Implementation |
|---------|---------------|
| Rate Limiting | `throttle:api` (60/min), `throttle:admin` (30/min) |
| OTP Rate Limit | 5 requests per 10 minutes per type |
| Device Security | 48-hour transaction hold on new devices |
| KYC Gating | Transaction limits, withdrawal permission, card limits |
| Account Status | Suspended/banned users cannot transact |
| Wallet Freeze | Admin can freeze wallets (prevents all operations) |
| Card Freeze | User can freeze cards (prevents spending) |
| Webhook IP Whitelist | CCPayment webhook restricted to known IPs |
| Webhook Signature | Both Stripe and CCPayment use HMAC verification |

### 19.4 Audit Trail

- Every money movement creates a Transaction record with balance_before and balance_after
- Admin actions logged to ActivityLog
- KYC verifications tracked per document
- Webhook events logged with full payload
- User login/logout tracked

---

## 20. Performance & Caching Strategy

### 20.1 Caching Layers

| Cache Key | TTL | Purpose |
|-----------|-----|---------|
| `exchange_rate_usd_syp` | 5 min | Rate lookup (cleared on admin update) |
| `fee:{code}` | 60 min | Fee configuration |
| `user:{id}:balance` | On-demand | Wallet balance (cleared on transaction) |
| `kyc_levels` | 60 min | Level definitions (cleared on admin update) |

### 20.2 Database Indexing Strategy

Key indexes for query performance:

| Table | Indexes |
|-------|---------|
| users | email (unique), phone (unique), referral_code (unique) |
| wallets | user_id + currency (unique composite) |
| transactions | user_id + created_at, reference (unique), type + status |
| virtual_cards | user_id, uuid (unique), wallet_id |
| kyc_verifications | user_id + verification_type |
| devices | user_id + device_id (unique) |
| payment_requests | user_id, status |

### 20.3 N+1 Query Prevention

All list endpoints use eager loading:
```php
$transactions->with(['wallet', 'card', 'recipient']);
$cards->with(['user', 'wallet']);
```
