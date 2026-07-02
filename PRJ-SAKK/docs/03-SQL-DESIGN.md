# SAKK Wallet — Database Design Document

## Technology
- **Development**: SQLite (`database/database.sqlite`)
- **Production**: MySQL 8.4 / PostgreSQL 17
- **ORM**: Laravel Eloquent
- **Migrations**: Laravel Migrations (45 migration files)

---

## Entity Relationship Diagram (Text-Based)

```
users ──┬── wallets                     gold_wallets ──┬── gold_transactions
        ├── virtual_cards                               └── users
        ├── transactions                                
        ├── kyc_documents                savings_goals ──┬── savings_transactions
        ├── kyc_verifications                            └── users
        ├── devices                                     
        ├── payment_requests ──┬── requestee (users)    agents ──┬── agent_documents
        ├── referrals (self)   └── payer (users)                  └── users
        ├── gold_wallets                                 
        ├── savings_goals                  merchants ──┬── merchant_documents
        ├── agents                                      └── users
        ├── merchants                                  
        ├── referral_rewards ──┬── referrer (users)    card_inventory ─── virtual_cards
        │                      └── referred (users)    
        ├── user_notifications          card_pricing
        ├── admin_notifications ──── admin (users)     
        ├── activity_logs               exchange_rates ──── exchange_rate_history
        ├── bank_accounts                               
        ├── support_tickets ──── ticket_messages        fees
        ├── integrations ──┬── integration_docs        
        │                   ├── integration_templates   notification_channels
        │                   └── integration_logs        
        ├── notification_templates                     
        ├── page_meta                     kyc_levels
        ├── service_configs ────┐                      
        ├── email_providers    ├── sms_providers        system_settings
        └── sham_cash_config ──┘                       
```

---

## Migrations List

| # | Migration | Purpose |
|---|-----------|---------|
| 1 | `0001_01_01_000001_create_cache_table` | Cache table for database cache driver |
| 2 | `0001_01_01_000002_create_jobs_table` | Queue jobs table |
| 3 | `2026_01_01_000001_create_integrations_table` | Third-party integrations |
| 4 | `2026_01_01_000002_create_integration_docs_table` | Integration documentation |
| 5 | `2026_01_01_000003_create_integration_templates_table` | Integration message templates |
| 6 | `2026_01_01_000004_create_integration_logs_table` | Integration API call logs |
| 7 | `2026_06_13_000001_create_users_table` | Users (core table) |
| 8 | `2026_06_13_000002_create_wallets_table` | Currency wallets |
| 9 | `2026_06_13_000003_create_virtual_cards_table` | Virtual card storage |
| 10 | `2026_06_13_000004_create_transactions_table` | Financial transactions |
| 11 | `2026_06_13_000005_create_kyc_documents_table` | KYC uploaded documents |
| 12 | `2026_06_13_113326_create_personal_access_tokens_table` | Sanctum tokens |
| 13 | `2026_06_13_125948_create_password_resets_table` | Password resets |
| 14 | `2026_06_13_140119_create_system_settings_table` | System configuration |
| 15 | `2026_06_13_145547_create_sessions_table` | Session storage |
| 16 | `2026_06_13_160000_add_is_admin_to_users_table` | Admin flag on users |
| 17 | `2026_06_14_100001_create_exchange_rates_tables` | Exchange rates + history |
| 18 | `2026_06_14_100003_create_kyc_levels_tables` | KYC level definitions |
| 19 | `2026_06_14_100005_create_card_inventory_tables` | Card inventory + pricing |
| 20 | `2026_06_14_100006_create_notification_tables` | Notification channels + templates |
| 21 | `2026_06_14_100007_create_bank_accounts_table` | Bank accounts |
| 22 | `2026_06_14_100008_create_audit_logs_table` | Audit logs |
| 23 | `2026_06_14_100009_create_support_tables` | Support tickets + messages |
| 24 | `2026_06_14_100010_create_referral_rewards_table` | Referral rewards |
| 25 | `2026_06_14_100011_add_user_limits_columns` | Limit columns on users |
| 26 | `2026_06_14_100012_add_system_settings_columns` | Extra system setting columns |
| 27 | `2026_06_14_100013_create_devices_table` | Connected devices |
| 28 | `2026_06_15_232600_add_stripe_cardholder_id_to_users_table` | Stripe cardholder ID |
| 29 | `2026_06_16_035910_create_fees_table` | Dynamic fee structure |
| 30 | `2026_06_16_060000_relax_transactions_type_category_constraints` | Relax constraints |
| 31 | `2026_06_17_000001_add_dual_currency_limits_to_kyc_levels` | Dual currency in KYC |
| 32 | `2026_06_17_000001_create_payment_requests_table` | Payment requests |
| 33 | `2026_06_17_120000_add_balance_and_cards_limits_to_kyc_levels` | Extra KYC level columns |
| 34 | `2026_06_19_000001_add_requestee_to_payment_requests` | Requestee column |
| 35 | `2026_06_19_120000_add_deletion_fields_to_users_table` | Account deletion fields |
| 36 | `2026_06_19_130000_create_agents_table` | Cash agents |
| 37 | `2026_06_19_140000_add_approval_to_devices_table` | Device approval flow |
| 38 | `2026_06_20_000000_create_merchants_table` | Merchant partners |
| 39 | `2026_06_20_100000_create_gold_prices_table` | Gold karat pricing |
| 40 | `2026_06_20_200000_create_gold_wallets_table` | Gold holdings per user |
| 41 | `2026_06_20_300000_create_gold_transactions_table` | Gold buy/sell records |
| 42 | `2026_06_20_400000_create_agent_documents_table` | Agent KYC documents |
| 43 | `2026_06_21_100000_create_savings_goals_table` | Savings goals |
| 44 | `2026_06_21_200000_add_user_id_to_partners` | Partner-user relationship |
| 45 | `2026_06_22_100000_create_admin_config_tables` | Additional admin config |

---

## Table Definitions

### 1. `users`

Core user account table. Uses Laravel's Authenticatable with Sanctum, SoftDeletes.

| Column | Type | Default | Nullable | Notes |
|--------|------|---------|----------|-------|
| id | bigint, auto-increment | | No | Primary key |
| uuid | uuid | | No | Unique identifier |
| first_name | varchar(255) | | No | |
| last_name | varchar(255) | | No | |
| email | varchar(255) | | No | Unique |
| phone | varchar(255) | | Yes | Unique |
| password | varchar(255) | | No | bcrypt hashed |
| avatar | varchar(255) | | Yes | URL/path |
| date_of_birth | date | | Yes | |
| gender | varchar(50) | | Yes | |
| country_code | varchar(10) | | Yes | e.g. +963 |
| language | varchar(10) | 'ar' | No | ar/en |
| timezone | varchar(100) | 'Asia/Damascus' | No | |
| is_active | tinyint(1) | 1 | No | |
| is_admin | tinyint(1) | 0 | No | Admin flag |
| kyc_level | int | 0 | No | 0=Unverified, 1=Standard, 2=Verified |
| kyc_status | varchar(50) | 'pending' | No | Enum: pending/verified/rejected |
| kyc_data | json | | Yes | Extra KYC metadata |
| kyc_verified_at | datetime | | Yes | |
| status | varchar(50) | 'active' | No | Enum: active/suspended/banned/pending |
| pin_code | varchar(255) | | Yes | bcrypt hashed |
| two_factor_enabled | tinyint(1) | 0 | No | |
| two_factor_secret | varchar(255) | | Yes | Google2FA secret |
| two_factor_recovery_codes | json | | Yes | Recovery codes array |
| google_id | varchar(255) | | Yes | Social login |
| apple_id | varchar(255) | | Yes | Social login |
| referral_code | varchar(50) | | No | Unique, 8-char uppercase |
| referred_by | bigint | | Yes | FK → users.id |
| fcm_token | text | | Yes | Firebase push token |
| device_id | varchar(255) | | Yes | Current device |
| stripe_cardholder_id | varchar(255) | | Yes | Stripe Issuing |
| stripe_customer_id | varchar(255) | | Yes | Stripe Connect |
| email_verified_at | datetime | | Yes | |
| phone_verified_at | datetime | | Yes | |
| daily_limit | decimal(15,2) | 0.00 | No | From KYC level |
| monthly_limit | decimal(15,2) | 0.00 | No | From KYC level |
| last_login_at | datetime | | Yes | |
| deletion_reason | text | | Yes | Account deletion |
| deleted_requested_at | datetime | | Yes | |
| deleted_at | datetime | | Yes | Soft delete |
| created_at | datetime | | Yes | |
| updated_at | datetime | | Yes | |

**Indexes**: PK (id), UNIQUE (email), UNIQUE (phone), UNIQUE (uuid), UNIQUE (referral_code), INDEX (referred_by), INDEX (kyc_status), INDEX (status)

---

### 2. `wallets`

Currency wallets — each user has 2 wallets (USD + SYP). Uses SoftDeletes.

| Column | Type | Default | Nullable | Notes |
|--------|------|---------|----------|-------|
| id | bigint | | No | |
| uuid | uuid | | No | |
| user_id | bigint | | No | FK → users.id |
| currency | varchar(10) | | No | USD or SYP |
| balance | decimal(18,8) | 0.00000000 | No | Current balance |
| available_balance | decimal(18,8) | 0.00000000 | No | Spendable (balance - pending) |
| pending_balance | decimal(18,8) | 0.00000000 | No | Held in pending |
| reserved_balance | decimal(18,8) | 0.00000000 | No | Reserved for card auths |
| daily_limit | decimal(15,2) | 0.00 | No | |
| daily_spent | decimal(15,2) | 0.00 | No | |
| daily_reset_at | date | | Yes | |
| monthly_limit | decimal(15,2) | 0.00 | No | |
| monthly_spent | decimal(15,2) | 0.00 | No | |
| monthly_reset_at | date | | Yes | |
| total_deposits | decimal(18,8) | 0.00000000 | No | Lifetime deposits |
| total_withdrawals | decimal(18,8) | 0.00000000 | No | Lifetime withdrawals |
| total_sent | decimal(18,8) | 0.00000000 | No | Lifetime sent (transfers) |
| total_received | decimal(18,8) | 0.00000000 | No | Lifetime received |
| transaction_count | bigint | 0 | No | |
| is_default | tinyint(1) | 0 | No | Default wallet |
| is_active | tinyint(1) | 1 | No | |
| is_frozen | tinyint(1) | 0 | No | Frozen by admin |
| frozen_reason | varchar(255) | | Yes | |
| network | varchar(50) | | Yes | Crypto network |
| deposit_address | text | | Yes | Crypto deposit |
| deleted_at | datetime | | Yes | |
| created_at | datetime | | Yes | |
| updated_at | datetime | | Yes | |

**Indexes**: PK (id), UNIQUE (uuid), INDEX (user_id), UNIQUE (user_id, currency), INDEX (currency)

---

### 3. `transactions`

All financial transactions. Uses SoftDeletes.

| Column | Type | Default | Nullable | Notes |
|--------|------|---------|----------|-------|
| id | bigint | | No | |
| uuid | uuid | | No | |
| reference | varchar(50) | | No | Unique, format: TXN-XXXXXXXXXXXX |
| user_id | bigint | | No | FK → users.id |
| wallet_id | bigint | | Yes | FK → wallets.id |
| card_id | bigint | | Yes | FK → virtual_cards.id |
| recipient_id | bigint | | Yes | FK → users.id (for transfers) |
| recipient_wallet_id | bigint | | Yes | FK → wallets.id |
| type | varchar(50) | | No | TransactionType enum |
| category | varchar(50) | | No | TransactionCategory enum |
| currency | varchar(10) | | No | USD or SYP |
| amount | decimal(18,8) | | No | Positive=credit, Negative=debit |
| fee | decimal(18,8) | 0.00000000 | No | |
| net_amount | decimal(18,8) | | No | amount - fee |
| balance_before | decimal(18,8) | | Yes | Wallet snapshot |
| balance_after | decimal(18,8) | | Yes | Wallet snapshot |
| original_currency | varchar(10) | | Yes | For exchange |
| original_amount | decimal(18,8) | | Yes | For exchange |
| exchange_rate | decimal(18,8) | | Yes | For exchange |
| status | varchar(50) | | No | TransactionStatus enum |
| title | varchar(255) | | Yes | |
| description | text | | Yes | |
| metadata | json | | Yes | Flexible metadata |
| external_reference | varchar(255) | | Yes | Provider reference |
| provider | varchar(50) | | Yes | stripe/ccpayment |
| provider_response | json | | Yes | Raw provider response |
| tx_hash | varchar(255) | | Yes | Blockchain TX hash |
| network | varchar(50) | | Yes | Crypto network |
| confirmations | int | 0 | No | Crypto confirmations |
| failure_reason | varchar(255) | | Yes | |
| failure_details | json | | Yes | |
| processed_at | datetime | | Yes | |
| completed_at | datetime | | Yes | |
| deleted_at | datetime | | Yes | |
| created_at | datetime | | Yes | |
| updated_at | datetime | | Yes | |

**Indexes**: PK (id), UNIQUE (uuid), UNIQUE (reference), INDEX (user_id), INDEX (wallet_id), INDEX (card_id), INDEX (recipient_id), INDEX (type), INDEX (category), INDEX (status), INDEX (created_at), INDEX (user_id, type, status)

---

### 4. `virtual_cards`

Virtual card records. Stores card metadata (PAN/CVV never stored in plaintext in production). Uses SoftDeletes.

| Column | Type | Default | Nullable | Notes |
|--------|------|---------|----------|-------|
| id | bigint | | No | |
| uuid | uuid | | No | |
| user_id | bigint | | No | FK → users.id |
| wallet_id | bigint | | No | FK → wallets.id |
| card_type | varchar(50) | 'virtual' | No | CardType enum |
| brand | varchar(50) | 'visa' | No | CardBrand enum |
| cardholder_name | varchar(255) | | No | |
| card_number | varchar(255) | | No | Encrypted in production |
| card_number_masked | varchar(50) | | Yes | **** **** **** 1234 |
| cvv | varchar(10) | | No | Encrypted |
| bin | varchar(10) | | Yes | First 6 digits |
| last4 | varchar(10) | | Yes | Last 4 digits |
| expiry_month | varchar(4) | | No | MM |
| expiry_year | varchar(4) | | No | YYYY |
| balance | decimal(15,2) | 0.00 | No | Current card balance |
| spending_limit | decimal(15,2) | 5000.00 | No | |
| daily_limit | decimal(15,2) | 1000.00 | No | |
| daily_spent | decimal(15,2) | 0.00 | No | |
| daily_reset_at | date | | Yes | |
| monthly_limit | decimal(15,2) | 10000.00 | No | |
| monthly_spent | decimal(15,2) | 0.00 | No | |
| monthly_reset_at | date | | Yes | |
| per_transaction_limit | decimal(15,2) | 500.00 | No | |
| total_spent | decimal(15,2) | 0.00 | No | |
| status | varchar(50) | 'active' | No | CardStatus enum |
| is_active | tinyint(1) | 1 | No | |
| frozen_reason | varchar(255) | | Yes | |
| online_enabled | tinyint(1) | 1 | No | |
| international_enabled | tinyint(1) | 1 | No | |
| contactless_enabled | tinyint(1) | 1 | No | |
| atm_enabled | tinyint(1) | 0 | No | |
| apple_pay_enabled | tinyint(1) | 0 | No | |
| google_pay_enabled | tinyint(1) | 0 | No | |
| samsung_pay_enabled | tinyint(1) | 0 | No | |
| nickname | varchar(255) | | Yes | User-friendly name |
| color | varchar(50) | '#6366f1' | Yes | Card color |
| provider | varchar(50) | 'local' | No | stripe/local |
| provider_card_id | varchar(255) | | Yes | Stripe card ID |
| provider_data | json | | Yes | Extra provider data |
| activated_at | datetime | | Yes | |
| expires_at | datetime | | Yes | |
| cancelled_at | datetime | | Yes | |
| deleted_at | datetime | | Yes | |
| created_at | datetime | | Yes | |
| updated_at | datetime | | Yes | |

**Indexes**: PK (id), UNIQUE (uuid), INDEX (user_id), INDEX (wallet_id), INDEX (status), INDEX (provider, provider_card_id)

---

### 5. `gold_wallets`

Each user has one gold wallet tracking gram balances.

| Column | Type | Default | Nullable | Notes |
|--------|------|---------|----------|-------|
| id | bigint | | No | |
| user_id | bigint | | No | FK → users.id |
| balance_grams | decimal(15,4) | 0.0000 | No | Current gold holdings |
| total_bought_grams | decimal(15,4) | 0.0000 | No | Lifetime bought |
| total_sold_grams | decimal(15,4) | 0.0000 | No | Lifetime sold |
| total_invested_usd | decimal(15,2) | 0.00 | No | Total USD spent |
| current_value_usd | decimal(15,2) | 0.00 | No | At current price |
| is_active | tinyint(1) | 1 | No | |
| created_at | datetime | | Yes | |
| updated_at | datetime | | Yes | |

**Indexes**: PK (id), UNIQUE (user_id), INDEX (user_id)

---

### 6. `gold_transactions`

Buy/sell gold transaction records.

| Column | Type | Default | Nullable | Notes |
|--------|------|---------|----------|-------|
| id | bigint | | No | |
| user_id | bigint | | No | FK → users.id |
| gold_wallet_id | bigint | | No | FK → gold_wallets.id |
| type | varchar(50) | | No | buy/sell |
| karat | varchar(10) | | No | 24/22/21/18 |
| grams | decimal(15,4) | | No | |
| price_per_gram_usd | decimal(15,2) | | No | |
| total_usd | decimal(15,2) | | No | grams × price |
| fee_usd | decimal(15,2) | 0.00 | No | 1% buy / 0.5% sell |
| usd_rate_at_time | decimal(15,2) | | Yes | USD→SYP rate |
| reference | varchar(50) | | No | Format: GLD-XXXXXXXXXXXXXX |
| status | varchar(50) | 'completed' | No | |
| notes | text | | Yes | |
| created_at | datetime | | Yes | |
| updated_at | datetime | | Yes | |

**Indexes**: PK (id), INDEX (user_id), INDEX (gold_wallet_id), INDEX (type), UNIQUE (reference)

---

### 7. `gold_prices`

Current gold karat prices (admin-configurable).

| Column | Type | Default | Nullable | Notes |
|--------|------|---------|----------|-------|
| id | bigint | | No | |
| karat | varchar(10) | | No | 24/22/21/18 |
| buy_price | decimal(15,2) | | No | Price per gram (USD) — user buys |
| sell_price | decimal(15,2) | | No | Price per gram (USD) — user sells |
| spread | decimal(15,2) | | No | buy - sell difference |
| is_active | tinyint(1) | 1 | No | |
| created_at | datetime | | Yes | |
| updated_at | datetime | | Yes | |

**Indexes**: PK (id), UNIQUE (karat), INDEX (is_active)

---

### 8. `savings_goals`

Cash savings goals with targets. Uses SoftDeletes.

| Column | Type | Default | Nullable | Notes |
|--------|------|---------|----------|-------|
| id | bigint | | No | |
| uuid | uuid | | No | |
| user_id | bigint | | No | FK → users.id |
| name | varchar(255) | | No | Goal name |
| target_amount | decimal(15,2) | | Yes | Target to reach |
| saved_amount | decimal(15,2) | 0.00 | No | Current saved |
| currency | varchar(10) | 'USD' | No | |
| status | varchar(50) | 'active' | No | active/completed/closed |
| icon | varchar(100) | | Yes | Emoji/icon identifier |
| color | varchar(50) | '#6366f1' | Yes | |
| target_date | date | | Yes | Optional target date |
| completed_at | datetime | | Yes | |
| deleted_at | datetime | | Yes | |
| created_at | datetime | | Yes | |
| updated_at | datetime | | Yes | |

**Indexes**: PK (id), UNIQUE (uuid), INDEX (user_id), INDEX (status)

---

### 9. `savings_transactions`

Savings goal deposits and withdrawals.

| Column | Type | Default | Nullable | Notes |
|--------|------|---------|----------|-------|
| id | bigint | | No | |
| reference | varchar(50) | | No | Format: SAV-XXXXXXXXXX |
| savings_goal_id | bigint | | No | FK → savings_goals.id |
| user_id | bigint | | No | FK → users.id |
| type | varchar(50) | | No | deposit/withdraw |
| amount | decimal(15,2) | | No | |
| currency | varchar(10) | | No | |
| status | varchar(50) | | No | |
| notes | text | | Yes | |
| created_at | datetime | | Yes | |
| updated_at | datetime | | Yes | |

**Indexes**: PK (id), UNIQUE (reference), INDEX (savings_goal_id), INDEX (user_id)

---

### 10. `devices`

Connected device management with security hold.

| Column | Type | Default | Nullable | Notes |
|--------|------|---------|----------|-------|
| id | bigint | | No | |
| user_id | bigint | | No | FK → users.id |
| device_id | varchar(255) | | No | Client-generated UUID |
| device_name | varchar(255) | | Yes | e.g. "iPhone 15 Pro" |
| device_type | varchar(50) | | Yes | mobile/tablet/desktop |
| public_key | text | | Yes | For biometric auth |
| is_trusted | tinyint(1) | 0 | No | |
| status | varchar(50) | 'pending' | No | pending/approved/rejected |
| approved_at | datetime | | Yes | |
| transactions_locked_until | datetime | | Yes | 48h hold |
| last_ip | varchar(50) | | Yes | |
| last_active_at | datetime | | Yes | |
| last_used_at | datetime | | Yes | |
| created_at | datetime | | Yes | |
| updated_at | datetime | | Yes | |

**Indexes**: PK (id), INDEX (user_id), UNIQUE (user_id, device_id), INDEX (status)

---

### 11. `payment_requests`

Request money from other users.

| Column | Type | Default | Nullable | Notes |
|--------|------|---------|----------|-------|
| id | bigint | | No | |
| uuid | uuid | | No | |
| user_id | bigint | | No | FK → users.id (requestor) |
| requestee_id | bigint | | Yes | FK → users.id (target) |
| currency | varchar(10) | | No | |
| amount | decimal(18,8) | | No | |
| note | varchar(500) | | Yes | |
| status | varchar(50) | 'pending' | No | pending/paid/accepted/rejected/cancelled/expired |
| payer_id | bigint | | Yes | FK → users.id |
| transaction_id | bigint | | Yes | FK → transactions.id |
| paid_at | datetime | | Yes | |
| responded_at | datetime | | Yes | |
| response_note | varchar(500) | | Yes | |
| expires_at | datetime | | Yes | |
| created_at | datetime | | Yes | |
| updated_at | datetime | | Yes | |

**Indexes**: PK (id), UNIQUE (uuid), INDEX (user_id), INDEX (requestee_id), INDEX (status)

---

### 12. `agents`

Cash agents for cash in/out services. Uses SoftDeletes.

| Column | Type | Default | Nullable | Notes |
|--------|------|---------|----------|-------|
| id | bigint | | No | |
| uuid | uuid | | No | |
| user_id | bigint | | Yes | FK → users.id |
| name | varchar(255) | | No | Agent trading name |
| agent_code | varchar(50) | | No | Unique, format: AG-XXXX |
| owner_name | varchar(255) | | Yes | |
| phone | varchar(50) | | Yes | |
| avatar | varchar(255) | | Yes | |
| address | text | | Yes | |
| city | varchar(100) | | Yes | |
| governorate | varchar(100) | | Yes | |
| latitude | decimal(10,8) | | Yes | |
| longitude | decimal(11,8) | | Yes | |
| services | json | | Yes | ["cash_in","cash_out"] |
| working_hours | text | | Yes | |
| commission_rate | decimal(5,2) | 0.00 | No | % |
| min_amount | decimal(15,2) | 0.00 | No | |
| max_amount | decimal(15,2) | 0.00 | No | |
| rating | decimal(3,2) | 0.00 | No | |
| reviews_count | int | 0 | No | |
| is_active | tinyint(1) | 1 | No | |
| is_featured | tinyint(1) | 0 | No | |
| is_verified | tinyint(1) | 0 | No | |
| kyc_status | varchar(50) | 'pending' | No | |
| kyc_submitted_at | datetime | | Yes | |
| kyc_approved_at | datetime | | Yes | |
| kyc_rejection_reason | text | | Yes | |
| notes | text | | Yes | |
| deleted_at | datetime | | Yes | |
| created_at | datetime | | Yes | |
| updated_at | datetime | | Yes | |

**Indexes**: PK (id), UNIQUE (uuid), UNIQUE (agent_code), INDEX (city), INDEX (governorate), INDEX (is_active), INDEX (is_featured)

---

### 13. `agent_documents`

Agent KYC documents. Uses SoftDeletes.

| Column | Type | Default | Nullable | Notes |
|--------|------|---------|----------|-------|
| id | bigint | | No | |
| agent_id | bigint | | No | FK → agents.id |
| document_type | varchar(50) | | No | license/id_card/commercial_record/tax_card/bank_account/contract/photo |
| file_path | varchar(255) | | No | |
| file_name | varchar(255) | | Yes | |
| file_type | varchar(50) | | Yes | MIME type |
| file_size | int | | Yes | Bytes |
| document_number | varchar(100) | | Yes | |
| issue_date | date | | Yes | |
| expiry_date | date | | Yes | |
| issuing_authority | varchar(255) | | Yes | |
| status | varchar(50) | 'pending' | No | pending/approved/rejected |
| rejection_reason | text | | Yes | |
| verified_by | bigint | | Yes | FK → users.id |
| verified_at | datetime | | Yes | |
| notes | text | | Yes | |
| deleted_at | datetime | | Yes | |
| created_at | datetime | | Yes | |
| updated_at | datetime | | Yes | |

---

### 14. `merchants`

Merchant partners with API access. Uses SoftDeletes.

| Column | Type | Default | Nullable | Notes |
|--------|------|---------|----------|-------|
| id | bigint | | No | |
| uuid | uuid | | No | |
| user_id | bigint | | Yes | FK → users.id |
| merchant_code | varchar(50) | | No | Format: MCH-XXXXXXXX |
| type | varchar(50) | | No | physical/ecommerce/both |
| store_name | varchar(255) | | No | |
| owner_name | varchar(255) | | Yes | |
| email | varchar(255) | | Yes | |
| phone | varchar(50) | | Yes | |
| description | text | | Yes | |
| logo | varchar(255) | | Yes | |
| address | text | | Yes | |
| city | varchar(100) | | Yes | |
| governorate | varchar(100) | | Yes | |
| latitude | decimal(10,8) | | Yes | |
| longitude | decimal(11,8) | | Yes | |
| website_url | varchar(255) | | Yes | |
| has_api_access | tinyint(1) | 0 | No | |
| api_key | varchar(100) | | Yes | |
| api_secret | varchar(100) | | Yes | |
| webhook_url | varchar(255) | | Yes | |
| environment | varchar(50) | 'sandbox' | No | |
| commission_rate | decimal(5,2) | 0.00 | No | |
| balance | decimal(15,2) | 0.00 | No | |
| total_earned | decimal(15,2) | 0.00 | No | |
| payment_methods | json | | Yes | |
| settings | json | | Yes | |
| is_active | tinyint(1) | 1 | No | |
| is_verified | tinyint(1) | 0 | No | |
| verified_at | datetime | | Yes | |
| kyc_status | varchar(50) | 'pending' | No | |
| kyc_submitted_at | datetime | | Yes | |
| kyc_approved_at | datetime | | Yes | |
| kyc_rejection_reason | text | | Yes | |
| notes | text | | Yes | |
| deleted_at | datetime | | Yes | |
| created_at | datetime | | Yes | |
| updated_at | datetime | | Yes | |

---

### 15. `merchant_documents`

Merchant KYC documents. Uses SoftDeletes.

| Column | Type | Default | Nullable | Notes |
|--------|------|---------|----------|-------|
| id | bigint | | No | |
| merchant_id | bigint | | No | FK → merchants.id |
| document_type | varchar(50) | | No | commercial_record/tax_card/bank_account/license/id_card/contract/ownership |
| file_path | varchar(255) | | No | |
| file_name | varchar(255) | | Yes | |
| file_type | varchar(50) | | Yes | |
| file_size | int | | Yes | |
| document_number | varchar(100) | | Yes | |
| issue_date | date | | Yes | |
| expiry_date | date | | Yes | |
| issuing_authority | varchar(255) | | Yes | |
| status | varchar(50) | 'pending' | No | |
| rejection_reason | text | | Yes | |
| verified_by | bigint | | Yes | |
| verified_at | datetime | | Yes | |
| notes | text | | Yes | |
| deleted_at | datetime | | Yes | |
| created_at | datetime | | Yes | |
| updated_at | datetime | | Yes | |

---

### 16. `kyc_documents`

User KYC document uploads. Uses SoftDeletes.

| Column | Type | Default | Nullable | Notes |
|--------|------|---------|----------|-------|
| id | bigint | | No | |
| uuid | uuid | | No | |
| user_id | bigint | | No | FK → users.id |
| document_type | varchar(50) | | No | national_id/passport/drivers_license/selfie/proof_of_address |
| file_path | varchar(255) | | No | |
| file_name | varchar(255) | | Yes | |
| file_type | varchar(50) | | Yes | |
| file_size | int | | Yes | |
| document_number | varchar(100) | | Yes | |
| issuing_country | varchar(10) | | Yes | |
| issue_date | date | | Yes | |
| expiry_date | date | | Yes | |
| status | varchar(50) | 'pending' | No | VerificationStatus |
| rejection_reason | text | | Yes | |
| verified_by | bigint | | Yes | FK → users.id |
| verified_at | datetime | | Yes | |
| ocr_data | json | | Yes | |
| extracted_data | json | | Yes | |
| deleted_at | datetime | | Yes | |
| created_at | datetime | | Yes | |
| updated_at | datetime | | Yes | |

---

### 17. `kyc_verifications`

Per-type verification records (email, phone, id_document, selfie, address_proof).

| Column | Type | Default | Nullable | Notes |
|--------|------|---------|----------|-------|
| id | bigint | | No | |
| user_id | bigint | | No | FK → users.id |
| level | int | | No | Which KYC level this satisfies |
| verification_type | varchar(50) | | No | email/phone/id_document/selfie/address_proof |
| status | varchar(50) | | No | VerificationStatus |
| document_path | varchar(255) | | Yes | |
| document_type | varchar(50) | | Yes | |
| extracted_data | json | | Yes | Includes OTP code & expiry |
| rejection_reason | text | | Yes | |
| reviewed_by | bigint | | Yes | FK → users.id |
| reviewed_at | datetime | | Yes | |
| expires_at | datetime | | Yes | |
| created_at | datetime | | Yes | |
| updated_at | datetime | | Yes | |

---

### 18. `kyc_levels`

KYC level definitions (seeded from config/kyc.php).

| Column | Type | Default | Nullable | Notes |
|--------|------|---------|----------|-------|
| id | bigint | | No | |
| level | int | | No | 0/1/2 |
| key | varchar(50) | | No | unverified/standard/verified |
| name | varchar(100) | | No | |
| name_ar | varchar(100) | | No | |
| description | text | | Yes | |
| description_ar | text | | Yes | |
| requirements | json | | Yes | Array of verification types needed |
| limits | json | | Yes | {USD: {daily,monthly,single}, SYP: {...}} |
| balance_limit | json | | Yes | {USD: max, SYP: max} |
| cards_limit | int | 0 | No | Max cards at this level |
| daily_limit | decimal(15,2) | 0.00 | No | |
| monthly_limit | decimal(15,2) | 0.00 | No | |
| single_transaction_limit | decimal(15,2) | 0.00 | No | |
| withdrawal_limit | decimal(15,2) | 0.00 | No | |
| can_transfer | tinyint(1) | 0 | No | |
| can_withdraw | tinyint(1) | 0 | No | |
| can_create_card | tinyint(1) | 0 | No | |
| is_active | tinyint(1) | 1 | No | |
| created_at | datetime | | Yes | |
| updated_at | datetime | | Yes | |

---

### 19. `fees`

Dynamic fee configuration with caching.

| Column | Type | Default | Nullable | Notes |
|--------|------|---------|----------|-------|
| id | bigint | | No | |
| code | varchar(50) | | No | Unique fee code |
| name | varchar(100) | | No | Arabic name |
| name_en | varchar(100) | | Yes | English name |
| description | text | | Yes | |
| type | varchar(50) | | No | deposit/withdrawal/card_fund/transfer |
| currency | varchar(10) | 'USD' | No | |
| payment_method | varchar(50) | | Yes | ccpayment/stripe/etc |
| fixed_amount | decimal(15,6) | 0.000000 | No | Fixed fee |
| percentage | decimal(8,4) | 0.0000 | No | Percentage fee |
| min_fee | decimal(15,6) | 0.000000 | No | Minimum fee |
| max_fee | decimal(15,6) | | Yes | Maximum fee |
| min_amount | decimal(15,6) | 0.000000 | No | Min transaction for this fee |
| max_amount | decimal(15,6) | | Yes | Max transaction for this fee |
| is_active | tinyint(1) | 1 | No | |
| sort_order | int | 0 | No | |
| metadata | json | | Yes | |
| created_at | datetime | | Yes | |
| updated_at | datetime | | Yes | |

**Fee codes**: deposit_usdt, withdraw_usdt, card_fund, card_creation

---

### 20. `exchange_rates`

Single row USD→SYP exchange rate.

| Column | Type | Default | Nullable | Notes |
|--------|------|---------|----------|-------|
| id | bigint | | No | |
| from_currency | varchar(10) | 'USD' | No | |
| to_currency | varchar(10) | 'SYP' | No | |
| rate | decimal(18,8) | | No | Mid rate |
| buy_rate | decimal(18,8) | | No | User buys USD at this |
| sell_rate | decimal(18,8) | | No | User sells USD at this |
| spread | decimal(8,4) | 2.0000 | No | Spread % |
| source | varchar(50) | 'manual' | No | |
| is_active | tinyint(1) | 1 | No | |
| fetched_at | datetime | | Yes | |
| created_at | datetime | | Yes | |
| updated_at | datetime | | Yes | |

---

### 21. `exchange_rate_history`

Historical records of exchange rate changes.

| Column | Type | Default | Nullable | Notes |
|--------|------|---------|----------|-------|
| id | bigint | | No | |
| from_currency | varchar(10) | | No | |
| to_currency | varchar(10) | | No | |
| rate | decimal(18,8) | | No | |
| buy_rate | decimal(18,8) | | No | |
| sell_rate | decimal(18,8) | | No | |
| source | varchar(50) | | Yes | |
| recorded_at | datetime | | Yes | |
| created_at | datetime | | Yes | |
| updated_at | datetime | | Yes | |

---

### 22. `referral_rewards`

Referral reward payout records.

| Column | Type | Default | Nullable | Notes |
|--------|------|---------|----------|-------|
| id | bigint | | No | |
| referrer_id | bigint | | No | FK → users.id |
| referred_id | bigint | | No | FK → users.id |
| transaction_id | bigint | | Yes | FK → transactions.id |
| referrer_reward | decimal(18,8) | 0.00000000 | No | |
| referred_reward | decimal(18,8) | 0.00000000 | No | |
| currency | varchar(10) | 'USD' | No | |
| trigger | varchar(50) | | No | kyc_verified/deposit |
| status | varchar(50) | 'credited' | No | |
| created_at | datetime | | Yes | |
| updated_at | datetime | | Yes | |

---

### 23. `card_inventory`

Pre-imported card pool for local card assignment.

| Column | Type | Default | Nullable | Notes |
|--------|------|---------|----------|-------|
| id | bigint | | No | |
| card_number_encrypted | text | | No | |
| card_number_hash | varchar(255) | | No | SHA-256 for dedup |
| cvv_encrypted | text | | No | |
| expiry_month | varchar(4) | | No | |
| expiry_year | varchar(4) | | No | |
| cardholder_name | varchar(255) | | Yes | |
| brand | varchar(50) | | No | |
| type | varchar(50) | | No | virtual/physical |
| bin | varchar(10) | | Yes | |
| source_file | varchar(255) | | Yes | |
| purchase_price | decimal(18,8) | 0.00000000 | No | |
| min_load | decimal(18,8) | 0.00000000 | No | |
| max_load | decimal(18,8) | 0.00000000 | No | |
| is_assigned | tinyint(1) | 0 | No | |
| assigned_to | bigint | | Yes | FK → virtual_cards.id |
| assigned_at | datetime | | Yes | |
| created_at | datetime | | Yes | |
| updated_at | datetime | | Yes | |

---

### 24. `card_pricing`

Card pricing configuration per brand/type.

| Column | Type | Default | Nullable | Notes |
|--------|------|---------|----------|-------|
| id | bigint | | No | |
| brand | varchar(50) | | No | visa/mastercard/all |
| type | varchar(50) | | No | virtual/physical/all |
| purchase_price | decimal(18,8) | 0.00000000 | No | |
| monthly_fee | decimal(18,8) | 0.00000000 | No | |
| min_load | decimal(18,8) | 0.00000000 | No | |
| max_load | decimal(18,8) | 0.00000000 | No | |
| load_fee_percentage | decimal(8,4) | 0.0000 | No | |
| load_fee_fixed | decimal(18,8) | 0.00000000 | No | |
| transaction_fee_percentage | decimal(8,4) | 0.0000 | No | |
| transaction_fee_fixed | decimal(18,8) | 0.00000000 | No | |
| atm_fee | decimal(18,8) | 0.00000000 | No | |
| international_fee_percentage | decimal(8,4) | 0.0000 | No | |
| kyc_level_required | int | 0 | No | |
| is_active | tinyint(1) | 1 | No | |
| created_at | datetime | | Yes | |
| updated_at | datetime | | Yes | |

---

### 25. `system_settings`

Key-value store for system configuration.

| Column | Type | Default | Nullable | Notes |
|--------|------|---------|----------|-------|
| id | bigint | | No | |
| key | varchar(255) | | No | Unique |
| value | text | | Yes | |
| type | varchar(50) | 'string' | No | string/integer/decimal/boolean/json |
| group | varchar(100) | 'general' | Yes | |
| label | varchar(255) | | Yes | |
| description | text | | Yes | |
| is_public | tinyint(1) | 0 | No | Exposed via API |
| created_at | datetime | | Yes | |
| updated_at | datetime | | Yes | |

---

### 26. `user_notifications`

In-app notifications for users.

| Column | Type | Default | Nullable | Notes |
|--------|------|---------|----------|-------|
| id | bigint | | No | |
| uuid | uuid | | No | |
| user_id | bigint | | No | FK → users.id |
| template_code | varchar(100) | | Yes | |
| channel | varchar(50) | | Yes | push/in_app/email/sms |
| title | varchar(255) | | Yes | |
| body | text | | Yes | |
| data | json | | Yes | |
| action_url | varchar(255) | | Yes | |
| is_read | tinyint(1) | 0 | No | |
| read_at | datetime | | Yes | |
| sent_at | datetime | | Yes | |
| status | varchar(50) | 'pending' | No | |
| failure_reason | varchar(255) | | Yes | |
| created_at | datetime | | Yes | |
| updated_at | datetime | | Yes | |

---

### 27. Additional Tables

**`bank_accounts`** — User bank accounts for withdrawals
**`audit_logs`** — Audit trail for admin actions
**`support_tickets`** — Customer support tickets
**`ticket_messages`** — Support ticket messages
**`integrations`** — 3rd party integrations (with encrypted credentials)
**`integration_docs`** — Integration API documentation
**`integration_templates`** — Integration message templates
**`integration_logs`** — Integration API call logs
**`notification_channels`** — Event→channel routing matrix
**`notification_templates`** — Reusable notification templates
**`page_meta`** — SEO metadata for marketing pages
**`service_configs`** — SMS/Email/Firebase OTP configs (encrypted)
**`email_providers`** — Email provider configurations
**`sms_providers`** — SMS provider configurations
**`sham_cash_config`** — Sham Cash integration config
**`sham_cash_transactions`** — Sham Cash transaction records
**`activity_logs`** — User activity tracking
**`personal_access_tokens`** — Sanctum API tokens
**`password_resets`** — Password reset tokens
**`sessions`** — Session storage
**`cache`** — Database cache
**`jobs`** — Queue jobs
**`failed_jobs`** — Failed queue jobs

---

## Key Relationships

| Parent | Child | Type | Foreign Key |
|--------|-------|------|-------------|
| users | wallets | 1:N | wallets.user_id |
| users | transactions | 1:N | transactions.user_id |
| users | virtual_cards | 1:N | virtual_cards.user_id |
| users | kyc_documents | 1:N | kyc_documents.user_id |
| users | kyc_verifications | 1:N | kyc_verifications.user_id |
| users | devices | 1:N | devices.user_id |
| users | payment_requests | 1:N | payment_requests.user_id |
| users | savings_goals | 1:N | savings_goals.user_id |
| users | gold_wallets | 1:1 | gold_wallets.user_id |
| users | agents | 1:1 | agents.user_id |
| users | merchants | 1:1 | merchants.user_id |
| users | user_notifications | 1:N | user_notifications.user_id |
| users | referral_rewards (as referrer) | 1:N | referral_rewards.referrer_id |
| users | referral_rewards (as referred) | 1:N | referral_rewards.referred_id |
| users | activity_logs | 1:N | activity_logs.user_id |
| wallets | transactions | 1:N | transactions.wallet_id |
| wallets | virtual_cards | 1:N | virtual_cards.wallet_id |
| virtual_cards | transactions | 1:N | transactions.card_id |
| gold_wallets | gold_transactions | 1:N | gold_transactions.gold_wallet_id |
| savings_goals | savings_transactions | 1:N | savings_transactions.savings_goal_id |
| agents | agent_documents | 1:N | agent_documents.agent_id |
| merchants | merchant_documents | 1:N | merchant_documents.merchant_id |
| integrations | integration_docs | 1:N | integration_docs.integration_id |
| integrations | integration_templates | 1:N | integration_templates.integration_id |
| integrations | integration_logs | 1:N | integration_logs.integration_id |
| card_inventory | virtual_cards | N:1 | card_inventory.assigned_to |
