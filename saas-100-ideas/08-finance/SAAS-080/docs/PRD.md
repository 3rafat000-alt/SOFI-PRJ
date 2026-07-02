# PRD: BankMicro (SAAS-080)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** منصة إدارة حسابات بنكية صغيرة: إيداعات، سحوبات، تحويلات، كشوفات حساب، إدارة مالية للأفراد والشركات الصغيرة
- **Problem:** الشركات الصغيرة والأفراد في المناطق اللي محد فيها بنوك تقليدية strong> يفتقرون لخدمات بنكية رقمية سهلة، الرسوم البنكية مرتفعة، صعوبة إدارة التدفق النقدي
- **Solution:** Laravel API + React Dashboard (branch/admin) + Flutter App (customers)

## 2. Market & Opportunity
- **Target market:** 200M+ unbanked/underbanked adults in MENA; 20M+ micro-businesses; Digital banking growing 30% YoY
- **Customer segment:** B2B (microfinance institutions, cooperative banks, fintechs) + B2C (individuals, small businesses)
- **Competitors:** M-Pesa (mobile money), Binance (crypto), AlAhly Net, STC Pay, UrPay, traditional banks
- **Differentiation:** Micro-account focused (no minimum balance), group savings (Jameya/ROSCA management), Shariah-compliant options, QR code payments, debit card issuance via BIN sponsorship

## 3. User Personas

### صاحب المشروع الصغير — باسم (Primary)
- **Role:** صاحب متجر صغير، يحتاج حساب بنكي تجاري
- **Goals:** استقبال دفعات العملاء، دفع الموردين، تتبع التدفق النقدي
- **Pain points:** البنوك التقليدية تطلب أرصدة عالية، رسوم تحويل مرتفعة، صعوبة فتح حساب تجاري

### ربة المنزل — أمل (Secondary)
- **Role:** تحتاج حساب لإدارة ميزانية الأسرة والادخار
- **Goals:** تحويل راتب الزوج، دفع فواتير، مشاركة في جمعية
- **Pain points:** الفروع بعيدة، تطبيقات البنوك معقدة، رسوم سحب من الصراف

### مشرف المجموعة — جمال (Tertiary)
- **Role:** يدير جمعية ادخار بين مجموعة من الأصدقاء
- **Goals:** تنظيم الاشتراكات، تتبع المدفوعات، توزيع الجمعية
- **Pain points:** جمع المبالغ يدوياً، نزاعات على المواعيد، أخطاء في المحاسبة

### Admin — Dashboard Operator
- **Role:** مدير المنصة المالية يراقب الحسابات، المعاملات، الامتثال

## 4. Features by Platform

### Laravel API (Backend)
- Account management (savings, current, joint, business)
- Deposit & withdrawal processing
- Internal & external transfers
- Bill payment integration
- QR code payment generation & scanning
- Group savings (Jameya/ROSCA) management
- Transaction history & e-statements
- KYC/AML workflow (ID verification, selfie, proof of address)
- Interest/dividend calculation (conventional or Shariah-compliant)
- Agent/branch management (cash-in/cash-out points)

### React Dashboard (Web)
- Customer accounts overview
- Transaction monitoring & approval queue
- KYC verification queue
- Bill payment configuration
- Group savings management
- Agent/branch network management
- Reports (transaction volume, float, fee revenue, aging)
- Compliance & suspicious activity reports
- Settings (fee schedules, limits, interest rates)

### Flutter App (Mobile)
- **Customer App:** View balance & transactions, Transfer money (internal/external), Pay bills (electricity, water, mobile), QR pay (scan to pay/generate), Group savings management, Deposit/withdrawal request, ATM/branch locator, Push notifications for transactions, E-statement download
- **Agent App:** Customer onboarding (KYC capture), Cash-in/cash-out transactions, Transaction history, Float management, Commission tracking

## 5. Data Model (MVP)
- **Customer:** id, name, phone, email, id_type, id_number, date_of_birth, address, kyc_status (pending/verified/rejected), kyc_data (JSON)
- **Account:** id, customer_id, type (savings/current/business/joint), account_no, balance, currency, status, opened_at, interest_rate
- **Transaction:** id, account_id, type (deposit/withdrawal/transfer/payment/qr_payment), amount, fee, reference, counterparty, description, status, created_at
- **BillPayment:** id, customer_id, biller_code, biller_name, reference_no, amount, status, paid_at
- **QrPayment:** id, merchant_id, amount, reference, status, scanned_at
- **GroupSavings (Jameya):** id, name, admin_id, members (JSON), contribution_amount, frequency, total_cycles, current_cycle, start_date, next_payout_date, status
- **GroupContribution:** id, group_id, member_id, cycle_no, amount, status, paid_at
- **Agent:** id, name, location, float_balance, commission_rate, status
- **KycDocument:** id, customer_id, type (id/selfie/address_proof), file_url, status, verified_at, verified_by

## 6. API Endpoints (MVP)
- `POST /api/auth/register` — Customer signup (init KYC)
- `POST /api/auth/kyc` — Submit KYC documents
- `GET /api/accounts` — My accounts
- `GET /api/accounts/{id}/transactions` — Account statement
- `POST /api/transfers/internal` — Internal transfer
- `POST /api/transfers/external` — External transfer (to bank)
- `POST /api/bills/pay` — Pay bill
- `GET /api/bills/list` — Available billers
- `POST /api/qr/generate` — Generate QR payment code
- `POST /api/qr/scan` — Pay via scanning QR
- `POST /api/groups/create` — Create Jameya group
- `POST /api/groups/{id}/contribute` — Make contribution
- `GET /api/groups/{id}` — Group savings status
- `GET /api/reports/transactions` — Transaction report (admin)

## 7. User Interface (Screen List)
- **Dashboard screens:** Account overview, Transaction queue, KYC queue, Agent float, Reports, Billers
- **Mobile (Customer):** Home (balance, quick actions), Transactions, Transfer, Pay bills, QR pay, Group savings, Profile, Statements
- **Mobile (Agent):** Dashboard (today's transactions), Cash-in, Cash-out, Customer registration, Transaction history, Float top-up
- **Flow (Customer):** Login → Home (Balance) → Transfer → Select Recipient → Amount → Confirm → Done
- **Flow (Customer - Savings):** Login → Groups → My Jameya → View Cycle → Pay Contribution → Check Payout Date
- **Flow (Agent):** Login → Customer Requests Cash-out → Verify PIN → Dispense Cash → Confirm → Commission Updated

## 8. Business Model
- **Pricing:** Free for depositors; Agent commission on cash-in/cash-out (0.5%–1%); Transfer fee (~$0.30 internal, ~$1 external)
- **No subscription** — transaction-based revenue
- **Target revenue per 1000 active users:** $500–$2,000/month
- **Additional:** SME account management $9.99/mo, Premium debit card $5/issue, Bill payment commission ($0.10–$0.50 per bill)

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2):** API — Account management, Deposit/withdrawal, Internal transfers, Basic KYC
- **Phase 2 (Weeks 3-4):** React Dashboard — Account management, Transaction monitoring, KYC workflow, Reports
- **Phase 3 (Weeks 5-6):** Flutter App — Customer app (balance, transfer, bills, QR pay), Agent app (cash-in/out)
- **Phase 4 (Weeks 7-8):** Group savings module, External transfer integration (w/ partner bank), Bill payment partnerships, Shariah compliance module, QA

## 10. Risk & Mitigation
- **Regulatory risk:** Financial services regulated by central banks → Obtain EMI/MFI license or partner with licensed bank, compliance-first development
- **Fraud risk:** Identity theft, transaction fraud → Multi-factor auth, device fingerprinting, AI fraud detection, daily transaction limits
- **Liquidity risk:** Agent float management → Real-time float monitoring, automated agent top-up, balance alerts
- **Technology risk:** Transaction failures cause trust issues → Idempotent transaction handling, auto-retry, real-time status updates
- **Adoption risk:** Target users may have basic phones → USSD fallback, Arabic voice interface, agent-assisted transactions
