# SAKK Wallet — API Design Document

## Base URL & Versioning

| Environment | Base URL |
|-------------|----------|
| Development | `http://localhost:8000/api/v1` |
| Staging | `https://staging-api.sakk.app/api/v1` |
| Production | `https://api.sakk.app/api/v1` |

**Versioning**: URL-based (`/api/v1/`). Breaking changes increment the version.

---

## Authentication

### Sanctum Token Authentication

All protected endpoints require a Bearer token in the `Authorization` header.

```
Authorization: Bearer {token}
```

**Headers** (optional but recommended for device security):
```
X-Device-Id: {unique-device-identifier}
Accept: application/json
Content-Type: application/json
Accept-Language: ar|en
```

**Token Management**:
- Tokens are issued on login/register
- Tokens can be revoked on logout
- Multiple tokens per user (one per device)
- No expiration by default (configurable via `SANCTUM_TOKEN_EXPIRATION`)

---

## Rate Limiting

| Scope | Limit | Window |
|-------|-------|--------|
| General API | 60 requests | 1 minute |
| Admin API | 30 requests | 1 minute |
| Auth (login/register) | 10 requests | 1 minute |
| OTP (send/verify) | 5 requests | 10 minutes |

Headers returned:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 58
X-RateLimit-Reset: 1623456789
```

---

## Pagination

All list endpoints support pagination with query parameters:
- `page` (default: 1)
- `per_page` (default: 20, max: 100)

Response format:
```json
{
    "data": [...],
    "meta": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 20,
        "total": 100,
        "from": 1,
        "to": 20
    },
    "links": {
        "first": "...",
        "last": "...",
        "prev": null,
        "next": "..."
    }
}
```

---

## Standard Response Envelope

### Success
```json
{
    "success": true,
    "data": { ... },
    "message": "Success"
}
```

### Error
```json
{
    "success": false,
    "message": "رسالة الخطأ",
    "code": "error_code",
    "errors": {
        "field_name": ["خطأ في الحقل"]
    }
}
```

---

## HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | OK |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Unprocessable Entity (validation error) |
| 429 | Too Many Requests |
| 500 | Internal Server Error |

---

## Endpoint Groups

### 1. Health Check

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/health` | Health check (public) |

**Response**:
```json
{
    "status": "ok",
    "version": "1.0.0",
    "timestamp": "2026-06-20T12:00:00+00:00"
}
```

---

### 2. Authentication — `/api/v1/auth`

#### 2.1 Public Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/register` | Register new user |
| POST | `/auth/login` | Login user |
| POST | `/auth/forgot-password` | Send password reset link |
| POST | `/auth/reset-password` | Reset password with token |
| POST | `/auth/verify-email` | Verify email with code |
| POST | `/auth/resend-verification` | Resend email verification |
| POST | `/auth/qr/generate` | Generate QR auth code |
| GET | `/auth/qr/poll/{token}` | Poll QR auth status |

**POST /auth/register**
```json
{
    "first_name": "أحمد",
    "last_name": "السوري",
    "email": "ahmed@example.com",
    "phone": "+963944123456",
    "password": "SecurePass123!",
    "password_confirmation": "SecurePass123!",
    "referral_code": "ABC12345",
    "language": "ar",
    "timezone": "Asia/Damascus"
}
```
**Response** (201):
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "first_name": "أحمد",
            "last_name": "السوري",
            "email": "ahmed@example.com",
            "phone": "+963944123456",
            "full_name": "أحمد السوري",
            "referral_code": "ABC12345",
            "is_admin": false,
            "kyc_level": 0,
            "kyc_status": "pending"
        },
        "token": "1|abc123def456..."
    },
    "message": "تم إنشاء الحساب بنجاح"
}
```

**POST /auth/login**
```json
{
    "email": "ahmed@example.com",
    "password": "SecurePass123!",
    "device_id": "uuid-device-identifier",
    "device_name": "iPhone 15 Pro",
    "fcm_token": "fcm-token-here"
}
```
**Response**:
```json
{
    "success": true,
    "data": {
        "user": { ... },
        "token": "2|xyz789...",
        "is_new_device": false
    }
}
```

#### 2.2 Protected Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/logout` | Logout (revoke token) |
| GET | `/auth/me` | Get current user profile |
| POST | `/auth/refresh` | Refresh token |
| PUT | `/auth/password` | Change password |
| POST | `/auth/pin` | Set transaction PIN |
| POST | `/auth/pin/verify` | Verify PIN |
| POST | `/auth/pin/change` | Change PIN |
| POST | `/auth/pin/disable` | Disable PIN (requires password) |
| POST | `/auth/2fa/setup` | Setup 2FA |
| POST | `/auth/2fa/confirm` | Confirm 2FA with code |
| POST | `/auth/2fa/disable` | Disable 2FA |
| GET | `/auth/2fa/status` | Get 2FA status |
| POST | `/auth/2fa/recovery-codes` | Get recovery codes |

**POST /auth/pin**
```json
{
    "pin": "123456",
    "pin_confirmation": "123456"
}
```

**POST /auth/pin/verify**
```json
{
    "pin": "123456"
}
```

**POST /auth/2fa/setup**
```json
{
    "success": true,
    "data": {
        "secret": "JBSWY3DPEHPK3PXP",
        "qr_code_url": "otpauth://totp/%D8%B5%D9%83%D9%83+Wallet:test@example.com?...",
        "recovery_codes": [
            "ABCD-EFGH-IJKL",
            "MNOP-QRST-UVWX",
            ...
        ]
    }
}
```

---

### 3. Biometric Authentication — `/api/v1/auth/biometric`

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/biometric/devices` | Register biometric device |
| GET | `/auth/biometric/devices` | List biometric devices |
| DELETE | `/auth/biometric/devices/{id}` | Remove biometric device |
| POST | `/auth/biometric/challenge` | Get biometric challenge |
| POST | `/auth/biometric/verify` | Verify biometric signature |

**POST /auth/biometric/register**
```json
{
    "device_id": "uuid",
    "device_name": "iPhone 15 Pro",
    "public_key": "base64-encoded-public-key"
}
```

**POST /auth/biometric/verify**
```json
{
    "challenge": "challenge-string",
    "signature": "base64-encoded-signature",
    "device_id": "uuid"
}
```

---

### 4. Wallets — `/api/v1/wallets`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/wallets` | List user wallets |
| POST | `/wallets` | Create new wallet |
| POST | `/wallets/convert` | Convert between currencies |
| GET | `/wallets/exchange-rates` | Get exchange rates |
| GET | `/wallets/{wallet}` | Get wallet details |
| GET | `/wallets/{wallet}/balance` | Get wallet balance |
| GET | `/wallets/{wallet}/transactions` | Get wallet transactions |
| GET | `/wallets/{wallet}/stats` | Get wallet statistics |
| GET | `/wallets/{wallet}/deposit-address` | Get crypto deposit address |
| DELETE | `/wallets/{wallet}` | Delete wallet |
| POST | `/wallets/{wallet}/deposit` | Deposit to wallet |
| POST | `/wallets/{wallet}/withdraw` | Withdraw from wallet (device-gated) |

**GET /wallets**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "currency": "USD",
            "balance": 1500.00,
            "available_balance": 1480.50,
            "pending_balance": 19.50,
            "is_default": true,
            "is_active": true,
            "is_frozen": false,
            "formatted_balance": "$1,500.00"
        },
        {
            "id": 2,
            "currency": "SYP",
            "balance": 500000.00,
            "available_balance": 500000.00,
            "pending_balance": 0,
            "is_default": false,
            "formatted_balance": "ل.س 500,000.00"
        }
    ]
}
```

**POST /wallets/convert**
```json
{
    "from_currency": "USD",
    "to_currency": "SYP",
    "amount": 100.00
}
```
**Response**:
```json
{
    "success": true,
    "data": {
        "from_currency": "USD",
        "to_currency": "SYP",
        "amount": 100.00,
        "converted_amount": 1300000.00,
        "rate": 13000.00,
        "transaction": { ... }
    }
}
```

---

### 5. P2P Transfer — `/api/v1/transfer`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/transfer/lookup` | Lookup recipient by email/phone/tag |
| POST | `/transfer` | Send money to another user (device-gated) |

**GET /transfer/lookup?q=ahmed@example.com**
```json
{
    "success": true,
    "data": {
        "id": 2,
        "name": "أحمد السوري",
        "initials": "أا",
        "tag": "ABC12345",
        "account_number": "SK00000002",
        "avatar": null
    }
}
```

**POST /transfer**
```json
{
    "recipient": "ahmed@example.com",
    "amount": 50.00,
    "currency": "USD",
    "note": "شكراً لك"
}
```
**Response**:
```json
{
    "success": true,
    "data": {
        "from_transaction": { ... },
        "to_transaction": { ... },
        "amount": 50.00,
        "currency": "USD",
        "note": "شكراً لك",
        "recipient": {
            "id": 2,
            "name": "أحمد السوري",
            "initials": "أا",
            "tag": "ABC12345",
            "account_number": "SK00000002"
        }
    }
}
```

---

### 6. Payment Requests — `/api/v1/payment-requests`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/payment-requests` | List sent payment requests |
| GET | `/payment-requests/received` | List received payment requests |
| POST | `/payment-requests` | Create payment request |
| GET | `/payment-requests/{uuid}` | Get payment request details |
| POST | `/payment-requests/{uuid}/pay` | Pay a payment request |
| POST | `/payment-requests/{uuid}/accept` | Accept payment request |
| POST | `/payment-requests/{uuid}/reject` | Reject payment request |
| POST | `/payment-requests/{uuid}/cancel` | Cancel payment request |

**POST /payment-requests**
```json
{
    "requestee_id": 2,
    "currency": "USD",
    "amount": 100.00,
    "note": "قسط الشهر"
}
```

---

### 7. Virtual Cards — `/api/v1/cards`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/cards` | List user cards |
| POST | `/cards` | Create virtual card |
| GET | `/cards/{card}` | Get card details |
| PUT | `/cards/{card}` | Update card settings |
| GET | `/cards/{card}/transactions` | Get card transactions |
| POST | `/cards/{card}/details` | Get full card details (requires PIN) |
| POST | `/cards/{card}/load` | Load funds to card (device-gated) |
| POST | `/cards/{card}/unload` | Unload funds from card (device-gated) |
| POST | `/cards/{card}/freeze` | Freeze card |
| POST | `/cards/{card}/unfreeze` | Unfreeze card |
| POST | `/cards/{card}/cancel` | Cancel card |
| POST | `/cards/stripe/issue` | Issue Stripe Issuing card |
| POST | `/cards/{card}/stripe/details` | Get Stripe card details |

**GET /cards**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "brand": "visa",
            "card_type": "virtual",
            "cardholder_name": "احمد السوري",
            "last4": "1234",
            "expiry": "06/29",
            "status": "active",
            "balance": 200.00,
            "is_active": true,
            "nickname": "بطاقتي",
            "color": "#6366f1"
        }
    ]
}
```

**POST /cards** (create local card)
```json
{
    "wallet_id": 1,
    "brand": "visa",
    "card_type": "virtual",
    "nickname": "مشترياتي",
    "color": "#6E1B2D"
}
```

**POST /cards/{card}/details** (requires PIN)
```json
{
    "pin": "123456"
}
```
**Response**:
```json
{
    "success": true,
    "data": {
        "card_number": "4111111111111111",
        "cvv": "123",
        "expiry_month": "06",
        "expiry_year": "2029",
        "cardholder_name": "احمد السوري"
    }
}
```

---

### 8. Transactions — `/api/v1/transactions`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/transactions` | List transactions (paginated) |
| GET | `/transactions/stats` | Get transaction statistics |
| GET | `/transactions/export` | Export transactions (CSV) |
| GET | `/transactions/reference/{reference}` | Get by reference |
| GET | `/transactions/{transaction}` | Get transaction details |
| GET | `/transactions/types` | List transaction types (public) |
| GET | `/transactions/categories` | List transaction categories (public) |

**GET /transactions?type=transfer_out&status=completed&from=2026-01-01&to=2026-06-20&page=1&per_page=20**

```json
{
    "success": true,
    "data": [
        {
            "id": 1001,
            "uuid": "...",
            "reference": "TXN-ABC123DEF456",
            "type": "transfer_out",
            "category": "p2p",
            "currency": "USD",
            "amount": -50.00,
            "fee": 0,
            "net_amount": -50.00,
            "balance_before": 1600.00,
            "balance_after": 1550.00,
            "status": "completed",
            "title": "تحويل إلى أحمد السوري",
            "description": "شكراً",
            "created_at": "2026-06-19T14:30:00+00:00",
            "formatted_amount": "-$50.00",
            "is_debit": true,
            "recipient": { ... },
            "wallet": { ... }
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 5,
        "total": 85,
        "per_page": 20
    }
}
```

---

### 9. Gold Savings — `/api/v1/gold`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/gold/prices` | Get current gold prices |
| GET | `/gold/wallet` | Get gold wallet details |
| POST | `/gold/buy` | Buy gold grams |
| POST | `/gold/sell` | Sell gold grams |
| GET | `/gold/transactions` | Get gold transactions |
| GET | `/gold/stats` | Get gold stats |

**GET /gold/prices**
```json
{
    "success": true,
    "data": [
        {
            "karat": "24",
            "buy_price": 85.50,
            "sell_price": 84.50,
            "spread": 1.00,
            "is_active": true,
            "label": "عيار 24",
            "purity": 100.0
        },
        {
            "karat": "21",
            "buy_price": 74.81,
            "sell_price": 73.94,
            "spread": 0.87,
            "is_active": true,
            "label": "عيار 21",
            "purity": 87.5
        },
        {
            "karat": "18",
            "buy_price": 64.13,
            "sell_price": 63.38,
            "spread": 0.75,
            "is_active": true,
            "label": "عيار 18",
            "purity": 75.0
        }
    ]
}
```

**POST /gold/buy**
```json
{
    "karat": "24",
    "grams": 10.00
}
```
**Response**:
```json
{
    "success": true,
    "data": {
        "karat": "24",
        "grams": 10.00,
        "price_per_gram_usd": 85.50,
        "total_usd": 855.00,
        "fee_usd": 8.55,
        "grand_total_usd": 863.55,
        "transaction": { ... },
        "gold_wallet": {
            "balance_grams": 25.50,
            "current_value_usd": 2180.00
        }
    }
}
```

**POST /gold/sell**
```json
{
    "karat": "24",
    "grams": 5.00
}
```
**Response**:
```json
{
    "success": true,
    "data": {
        "karat": "24",
        "grams": 5.00,
        "price_per_gram_usd": 84.50,
        "total_usd": 422.50,
        "fee_usd": 2.11,
        "net_usd": 420.39,
        "transaction": { ... }
    }
}
```

---

### 10. Savings Goals — `/api/v1/savings`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/savings/summary` | Get savings summary |
| GET | `/savings` | List savings goals |
| POST | `/savings` | Create savings goal |
| GET | `/savings/{savings}` | Get savings goal details |
| POST | `/savings/{savings}/deposit` | Deposit to goal (device-gated) |
| POST | `/savings/{savings}/withdraw` | Withdraw from goal (device-gated) |
| POST | `/savings/{savings}/close` | Close savings goal |

**POST /savings**
```json
{
    "name": "رحلة الصيف",
    "target_amount": 2000.00,
    "currency": "USD",
    "icon": "airplane",
    "color": "#4F46E5",
    "target_date": "2026-09-01"
}
```

---

### 11. KYC (Identity Verification) — `/api/v1/kyc`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/kyc/levels` | Get KYC levels configuration |
| GET | `/kyc/status` | Get user's KYC status |
| GET | `/kyc/submissions` | Get submission status |
| POST | `/kyc/email/send` | Send email verification code |
| POST | `/kyc/email/verify` | Verify email code |
| POST | `/kyc/phone/update` | Update phone number |
| POST | `/kyc/phone/send` | Send phone verification code |
| POST | `/kyc/phone/verify` | Verify phone code |
| POST | `/kyc/id-document` | Submit ID document |
| POST | `/kyc/selfie` | Submit selfie photo |
| POST | `/kyc/address-proof` | Submit proof of address |

**GET /kyc/status**
```json
{
    "success": true,
    "data": {
        "current_level": 0,
        "is_verified": false,
        "status": "pending",
        "status_label_ar": "غير موثّق",
        "level_name": "Unverified",
        "level_name_ar": "غير موثّق",
        "limits": {
            "USD": { "daily": 100, "monthly": 300, "single": 100 },
            "SYP": { "daily": 1300000, "monthly": 3900000, "single": 1300000 }
        },
        "balance_limit": { "USD": 500, "SYP": 6500000 },
        "cards_limit": 0,
        "permissions": {
            "can_transfer": true,
            "can_withdraw": false,
            "can_create_card": false
        },
        "verifications": {
            "email": { "status": "not_started" },
            "phone": { "status": "not_started" },
            "id_document": { "status": "not_started" },
            "selfie": { "status": "not_started" }
        },
        "next_level": {
            "level": 1,
            "name": "Standard KYC",
            "name_ar": "موثّق أساسي",
            "requirements": ["email", "phone", "id_document"]
        },
        "missing_requirements": ["email", "phone", "id_document"]
    }
}
```

---

### 12. CCPayment (Crypto) — `/api/v1/ccpayment`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/ccpayment/config` | Get CCPayment configuration |
| GET | `/ccpayment/supported-coins` | List supported coins |
| POST | `/ccpayment/deposit/address` | Create deposit address |
| GET | `/ccpayment/deposit/{reference}/status` | Get deposit status |
| GET | `/ccpayment/deposits` | Get deposit history |
| POST | `/ccpayment/withdraw` | Withdraw to crypto address (device-gated) |
| GET | `/ccpayment/withdraw/fee` | Get withdrawal fee |
| GET | `/ccpayment/withdraw/{reference}/status` | Get withdrawal status |
| GET | `/ccpayment/withdrawals` | Get withdrawal history |
| GET | `/ccpayment/assets` | List crypto assets |
| GET | `/ccpayment/assets/{coinId}` | Get asset details |

---

### 13. Exchange Rates — `/api/v1/exchange-rates`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/exchange-rates` | Get all rates |
| GET | `/exchange-rates/rate?from=USD&to=SYP` | Get specific rate |
| POST | `/exchange-rates/convert` | Convert amount |
| GET | `/exchange-rates/history?days=30` | Get rate history |
| GET | `/exchange-rates/configured` | Check if configured |

**POST /exchange-rates/convert**
```json
{
    "amount": 100,
    "from": "USD",
    "to": "SYP",
    "direction": "sell"
}
```
**Response**:
```json
{
    "success": true,
    "data": {
        "original_amount": 100,
        "original_currency": "USD",
        "converted_amount": 1298700.00,
        "target_currency": "SYP",
        "rate_used": 12987.00,
        "mid_rate": 13000.00,
        "spread": 2.0,
        "direction": "sell"
    }
}
```

---

### 14. Fees — `/api/v1/fees`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/fees` | Get all fee configurations |
| POST | `/fees/calculate` | Calculate fee for amount |

**POST /fees/calculate**
```json
{
    "fee_code": "withdraw_usdt",
    "amount": 500.00
}
```

---

### 15. Agents — `/api/v1/agents`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/agents` | List nearby agents |
| GET | `/agents/{id}` | Get agent details |

**GET /agents?lat=33.5138&lng=36.2765&radius=10&service=cash_in**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "uuid": "...",
            "name": "وكالة الأمانة",
            "agent_code": "AG-1234",
            "address": "شارع بغداد، دمشق",
            "city": "دمشق",
            "governorate": "دمشق",
            "latitude": 33.5138,
            "longitude": 36.2765,
            "services": ["cash_in", "cash_out"],
            "commission_rate": 1.5,
            "min_amount": 1000,
            "max_amount": 500000,
            "rating": 4.5,
            "reviews_count": 23,
            "distance_km": 0.5,
            "is_featured": true
        }
    ]
}
```

---

### 16. Notifications — `/api/v1/notifications`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/notifications` | List notifications |
| PUT | `/notifications/{id}/read` | Mark notification as read |
| PUT | `/notifications/read-all` | Mark all as read |
| GET | `/notifications/unread-count` | Get unread count |

---

### 17. Partners — `/api/v1/partner`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/partner/application` | Get partner application status |
| POST | `/partner/apply` | Submit partner application |
| POST | `/partner/documents` | Upload partner document |

---

### 18. Contacts & Referral — `/api/v1`

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/contacts/match` | Match phone contacts |
| GET | `/referral/info` | Get referral info |
| GET | `/cashback` | Get cashback history |
| GET | `/profile` | Get profile (alias for /auth/me) |
| PUT | `/profile` | Update profile |
| POST | `/profile/avatar` | Update avatar |
| DELETE | `/profile/avatar` | Delete avatar |
| DELETE | `/profile` | Delete account |

---

### 19. Devices — `/api/v1/devices`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/devices` | List connected devices |
| POST | `/devices/register` | Register new device |
| POST | `/devices/{id}/approve` | Approve device |
| POST | `/devices/{id}/reject` | Reject device |
| DELETE | `/devices/{id}` | Remove device |

**GET /devices**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "device_id": "uuid-...",
            "device_name": "iPhone 15 Pro",
            "device_type": "mobile",
            "is_trusted": true,
            "status": "approved",
            "approved_at": "2026-06-15T10:00:00Z",
            "transactions_locked_until": "2026-06-17T10:00:00Z",
            "last_active_at": "2026-06-20T08:30:00Z",
            "can_transact": true
        },
        {
            "id": 2,
            "device_id": "uuid-...",
            "device_name": "Samsung Galaxy S25",
            "status": "pending",
            "can_transact": false
        }
    ]
}
```

---

### 20. Admin — `/api/v1/admin`

#### Dashboard
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/dashboard` | Dashboard stats |

#### Users
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/users` | List all users |
| GET | `/admin/users/{id}` | User details |
| PUT | `/admin/users/{id}` | Update user |
| DELETE | `/admin/users/{id}` | Delete user |
| POST | `/admin/users/{id}/impersonate` | Impersonate user |

#### KYC
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/kyc-documents` | List KYC documents |
| POST | `/admin/kyc/{userId}/approve` | Approve KYC |
| POST | `/admin/kyc/{userId}/reject` | Reject KYC |

#### Transactions
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/transactions` | List all transactions |
| GET | `/admin/transactions/{id}` | Transaction details |
| POST | `/admin/transactions/{id}/reverse` | Reverse transaction |

#### Wallets
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/wallets` | List all wallets |
| POST | `/admin/wallets/{id}/freeze` | Freeze wallet |
| POST | `/admin/wallets/{id}/unfreeze` | Unfreeze wallet |

#### Cards
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/cards` | List all cards |
| POST | `/admin/cards/{id}/freeze` | Freeze card |
| POST | `/admin/cards/{id}/unfreeze` | Unfreeze card |
| POST | `/admin/cards/{id}/cancel` | Cancel card |
| PUT | `/admin/cards/{id}/limits` | Update card limits |

#### Settings
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/settings` | List settings |
| GET | `/admin/settings/all` | All settings |
| GET | `/admin/settings/group/{group}` | Settings by group |
| POST | `/admin/settings` | Update setting |
| DELETE | `/admin/settings/{key}` | Delete setting |

#### Fees & Limits
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/fees` | List fees |
| PUT | `/admin/fees` | Update fees |
| GET | `/admin/fees/all` | All fee configurations |
| POST | `/admin/fees/update` | Update specific fee |
| GET | `/admin/limits` | Get limits |
| PUT | `/admin/limits` | Update limits |

#### KYC Levels
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/kyc-levels` | List KYC levels |
| POST | `/admin/kyc-levels` | Update KYC level |
| GET | `/admin/kyc-verifications` | Pending verifications |
| POST | `/admin/kyc-verifications/{id}/review` | Review verification |

#### Card Inventory & Pricing
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/card-inventory` | List card inventory |
| POST | `/admin/card-inventory/import` | Import cards from CSV |
| GET | `/admin/card-pricing` | List card pricing |
| POST | `/admin/card-pricing` | Update card pricing |

#### Other
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/activity-logs` | Activity logs |
| GET | `/admin/notifications` | Admin notifications |
| POST | `/admin/notifications/send` | Send push notification |
| POST | `/admin/maintenance/enable` | Enable maintenance mode |
| POST | `/admin/maintenance/disable` | Disable maintenance mode |
| GET | `/admin/currencies` | Currency settings |
| PUT | `/admin/currencies` | Update currencies |
| GET | `/admin/referrals` | Referral list |
| GET | `/admin/referrals/stats` | Referral stats |
| PUT | `/admin/referrals/config` | Update referral config |
| GET | `/admin/reports` | Reports |
| GET | `/admin/export/{type}` | Export data (CSV) |
| GET | `/admin/exchange-rates` | Exchange rates |
| POST | `/admin/exchange-rates` | Update exchange rate |

---

## Webhook Endpoints

### Stripe Issuing (Public)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/webhooks/stripe/issuing` | Stripe Issuing webhook |

**Events handled**:
- `issuing_authorization.request` — Real-time auth (2s timeout)
- `issuing_authorization.capture` — Settlement
- `issuing_authorization.reversal` — Void/refund

### CCPayment (Development)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/webhooks/ccpayment/info` | Webhook info |
| POST | `/api/webhooks/ccpayment/test/deposit` | Test deposit webhook |
| POST | `/api/webhooks/ccpayment/test/withdraw` | Test withdraw webhook |

---

## Error Codes Reference

| Code | Description | HTTP Status |
|------|-------------|-------------|
| `device_pending` | Device not yet approved | 403 |
| `device_rejected` | Device was rejected | 403 |
| `device_locked` | Device in 48h security hold | 403 |
| `insufficient_balance` | Not enough funds | 400 |
| `exchange_rate_not_found` | Rate not configured | 400 |
| `pin_required` | PIN not set | 400 |
| `pin_invalid` | Wrong PIN | 400 |
| `two_factor_required` | 2FA code needed | 401 |
| `kyc_level_insufficient` | KYC level too low | 403 |
| `limit_exceeded` | Daily/monthly limit exceeded | 400 |
| `wallet_frozen` | Wallet is frozen | 403 |
| `self_transfer` | Cannot transfer to self | 400 |
| `user_not_found` | Recipient not found | 404 |
| `invalid_currency` | Unsupported currency | 400 |

---

## Transaction Types & Categories (Enums)

### TransactionType (11 values)
| Value | Label (EN) | Label (AR) | Is Credit? | Is Debit? |
|-------|------------|------------|------------|-----------|
| `deposit` | Deposit | إيداع | Yes | No |
| `withdrawal` | Withdrawal | سحب | No | Yes |
| `card_load` | Card Load | شحن البطاقة | No | Yes |
| `card_unload` | Card Unload | تفريغ البطاقة | Yes | No |
| `card_payment` | Card Payment | دفع بالبطاقة | No | Yes |
| `card_refund` | Card Refund | استرداد | Yes | No |
| `fee` | Fee | رسوم | No | Yes |
| `reward` | Reward | مكافأة | Yes | No |
| `adjustment` | Adjustment | تعديل | — | — |
| `exchange` | Currency Exchange | تحويل عملة | — | — |
| `transfer_out` | Transfer Sent | تحويل صادر | No | Yes |
| `transfer_in` | Transfer Received | تحويل وارد | Yes | No |

### TransactionCategory (10 values)
| Value | Label (EN) | Label (AR) |
|-------|------------|------------|
| `wallet` | Wallet | المحفظة |
| `card` | Card | البطاقة |
| `crypto` | Crypto | عملات رقمية |
| `exchange` | Currency Exchange | تحويل عملات |
| `p2p` | Peer to Peer | تحويل بين الأشخاص |
| `fee` | Fee | رسوم |
| `reward` | Reward | مكافأة |
| `adjustment` | Adjustment | تعديل |
| `investment` | Investment | استثمار |
| `savings` | Savings | ادخار |

### TransactionStatus (7 values)
| Value | Label | Color | Is Final? |
|-------|-------|-------|-----------|
| `pending` | Pending | yellow | No |
| `processing` | Processing | yellow | No |
| `completed` | Completed | green | Yes |
| `failed` | Failed | red | Yes |
| `cancelled` | Cancelled | red | Yes |
| `reversed` | Reversed | blue | Yes |
| `refunded` | Refunded | blue | Yes |

### CardStatus (5 values)
| Value | Label | Color |
|-------|-------|-------|
| `active` | Active | green |
| `frozen` | Frozen | blue |
| `expired` | Expired | gray |
| `cancelled` | Cancelled | red |
| `pending` | Pending Activation | yellow |

### CardType (2 values)
| Value | Label |
|-------|-------|
| `virtual` | Virtual Card |
| `physical` | Physical Card |

### CardBrand (2 values)
| Value | Label |
|-------|-------|
| `visa` | Visa |
| `mastercard` | Mastercard |

### UserStatus (4 values)
| Value | Label | Color |
|-------|-------|-------|
| `active` | Active | green |
| `suspended` | Suspended | yellow |
| `banned` | Banned | red |
| `pending` | Pending Verification | gray |

### KycStatus (4 values)
| Value | Label | Color |
|-------|-------|-------|
| `pending` | Unverified | gray |
| `submitted` | Under Review | yellow |
| `verified` | Verified | green |
| `rejected` | Rejected | red |

### VerificationStatus (3 values)
| Value | Label | Color |
|-------|-------|-------|
| `pending` | Pending | yellow |
| `approved` | Approved | green |
| `rejected` | Rejected | red |
