# PRD: MicroFund (SAAS-053)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary

- **One-liner:** منصة متكاملة لإدارة التمويلات الصغيرة والقروض متناهية الصغر — تتبع العملاء، الجدولة، السداد، والتقارير — موجهة لمؤسسات التمويل والجمعيات.
- **Problem:** مؤسسات التمويل الصغير والجمعيات الخيرية تدير القروض يدوياً عبر الإكسل والسجلات الورقية مما يسبب أخطاء في الحسابات، تأخير في المتابعة، ضعف في التقارير. لا يوجد حل ميسور التكلفة بالعربية يلبي احتياجات هذا القطاع.
- **Proposed solution:** Laravel API (إدارة القروض والعملاء والمدفوعات) + React Dashboard (لوحة تحكم للمؤسسات المالية) + Flutter App (تطبيق للمقترضين والموظفين الميدانيين).

## 2. Market & Opportunity

- **Target market:** مؤسسات التمويل الصغير (MFIs)، الجمعيات الخيرية، التعاونيات المالية، صناديق الإقراض في العالم العربي. تشير التقديرات إلى وجود أكثر من 500 منظمة تمويل صغير في المنطقة.
- **Customer segment:** B2B — مؤسسات تمويل صغير، جمعيات إقراض، بنوك متناهية الصغر.
- **Competitor landscape:**
  1. MIS (Microfinance Information System) — حلول عالمية مثل Mifos X وOctopus.
  2. إكسل (Excel) — الحل الأكثر شيوعاً رغم عيوبه.
  3. أنظمة ERP محلية — مثل أكسيرا، حلا SYSTEMS لكنها غير متخصصة.
  4. Temenos — حل بنكي متكامل لكنه مكلف جداً.
  5. Odoo ERP — قابل للتخصيص لكن يحتاج خبرة فنية.
- **Differentiation:** حل مخصص للتمويل الصغير باللغة العربية، نموذج تسعير ميسور، يدعم القروض الجماعية والفردية، متوافق مع مبادئ الشريعة (مرابحة، قرض حسن)، تقارير KPI متخصصة في التمويل الصغير.

## 3. User Personas

### أساسي: مدير المؤسسة المالية — سامي
- **الدور:** مدير مؤسسة تمويل صغير
- **الأهداف:** إدارة محفظة القروض، متابعة السداد، تقييم المخاطر، إعداد التقارير للمانحين
- **نقاط الألم:** صعوبة متابعة مئات القروض، نقص التقارير الفورية، عدم معرفة حالات التعثر المبكر

### أساسي: موظف الإقراض الميداني — ليلى
- **الدور:** موظفة ميدانية تزور العميلات وتجمع الأقساط
- **الأهداف:** تسجيل العملاء الجدد، متابعة السداد، تحديث بيانات العملاء ميدانياً
- **نقاط الألم:** حمل الملفات الورقية، صعوبة تسجيل الدفعات في الميدان، عدم وجود تطبيق جوال

### ثانوي: المقترض — أم محمد
- **الدور:** سيدة أعمال صغيرة تستفيد من القرض
- **الأهداف:** معرفة جدول السداد، دفع الأقساط، متابعة رصيدها
- **نقاط الألم:** عدم معرفة موعد الدفع بدقة، صعوبة التواصل مع المؤسسة

### إداري: مشغل النظام — مشرف
- **الدور:** مسؤول المنصة
- **الأهداف:** إدارة المستخدمين، ضبط الإعدادات، مراقبة الأداء

## 4. Features by Platform

### Laravel API (Backend)

- Client management (individual & group)
- Loan product catalog (term, rate, fees)
- Loan origination & disbursement
- Repayment scheduling (flat, declining, Islamic)
- Payment collection & receipting
- Late payment tracking & penalty calculation
- Savings management (voluntary & compulsory)
- Collateral registration
- Credit scoring (basic rules engine)
- Reporting engine (portfolio, aging, collections)
- User roles (admin, loan_officer, client, accountant)
- SMS & push notification reminders
- Compliance & Sharia compliance flagging

### React Dashboard (Web)

- Dashboard: portfolio KPIs, PAR (Portfolio at Risk) charts, collection rate
- Client management: profiles, group associations, loan history
- Loan products: product definition, interest/rate settings
- Loan origination: application, approval workflow, disbursement
- Collections: repayment schedule, payment entry, overdue alerts
- Reports: aging analysis, cash flow, portfolio reports, donor reports
- Accounting: general ledger integration (future)
- Users & roles management
- Settings: interest rates, fees, grace periods

### Flutter App (Mobile)

- Loan officer app: client registration, payment collection, field visit logging
- Client app: loan balance, repayment schedule, payment history, notifications
- Offline capability for field operations (sync when online)
- Push notifications for payment reminders
- Receipt generation (digital & printable)
- QR code scanning for client identification

## 5. Data Model (MVP)

- **User:** id, name, email, phone, role, branch_id, created_at
- **Branch:** id, name, address, city, phone, manager_id
- **Client:** id, user_id, first_name, last_name, id_number, phone, address, city, birth_date, gender, marital_status, occupation, monthly_income, status, created_at
- **ClientGroup:** id, name, branch_id, created_date, status
- **GroupMember:** id, group_id, client_id, role (leader/member), joined_date
- **LoanProduct:** id, name, min_amount, max_amount, interest_rate, rate_type (flat/declining), repayment_period_min, repayment_period_max, grace_period, fees, is_sharia_compliant, status
- **Loan:** id, loan_product_id, client_id, group_id, amount, interest_rate, term_months, start_date, end_date, status (pending/approved/active/closed/written_off), disbursement_date, purpose
- **RepaymentSchedule:** id, loan_id, installment_number, due_date, principal_amount, interest_amount, total_amount, status (pending/paid/overdue)
- **Payment:** id, loan_id, client_id, repayment_schedule_id, amount, payment_date, method (cash/bank_transfer/mobile_money), receipt_number, collected_by, notes
- **SavingsAccount:** id, client_id, account_number, balance, opened_date, status
- **SavingsTransaction:** id, savings_account_id, type (deposit/withdrawal), amount, date, notes
- **Collateral:** id, loan_id, type, description, estimated_value, documents
- **Notification:** id, client_id, title, body, type, is_read, created_at

## 6. API Endpoints (MVP)

- `POST /api/login` — Auth
- `GET /api/clients` — List clients
- `POST /api/clients` — Create client
- `GET /api/clients/{id}` — Client details
- `PUT /api/clients/{id}` — Update client
- `GET /api/groups` — List groups
- `POST /api/groups` — Create group
- `GET /api/loan-products` — List products
- `POST /api/loan-products` — Create product
- `POST /api/loans` — Apply for loan
- `GET /api/loans` — List loans
- `GET /api/loans/{id}` — Loan details
- `PUT /api/loans/{id}/status` — Approve/reject/disburse
- `GET /api/loans/{id}/schedule` — Repayment schedule
- `GET /api/loans/{id}/payments` — Loan payments
- `POST /api/payments` — Record payment
- `GET /api/payments` — List payments
- `GET /api/savings-accounts` — List savings
- `POST /api/savings-transactions` — Deposit/withdrawal
- `GET /api/reports/portfolio` — Portfolio summary
- `GET /api/reports/aging` — Aging analysis
- `GET /api/reports/collections` — Collection performance
- `GET /api/reports/officer/{id}` — Officer performance
- `GET /api/notifications` — Notifications

## 7. User Interface (Screen List)

### Dashboard Screens (React)
- Login
- Dashboard: PAR 1-30, PAR 31-90, collection rate, active loans, disbursed amount
- Clients: CRUD with search, filter by branch/status, client detail with loan history
- Groups: group management, member assignment, group meetings
- Loan Products: product definition form, rate configuration
- Loans: application list, approval queue, active loans, closed loans
- Loan Detail: schedule table, payment history, collateral
- Payments: batch payment entry, receipt generation, daily collection sheet
- Savings: account list, transaction log, interest calculation
- Reports: configurable date range, export to PDF/Excel
- Users: staff management, role assignment, activity log
- Settings: organization info, fiscal year, Sharia compliance flags

### Mobile Screens (Flutter)
- Login (loan officer / client)
- Home (Officer): today's collections, overdue list, client visits scheduled
- Client Quick Search: ID/name search
- New Client Registration: form with ID capture
- Payment Collection: select client, view balance, enter amount, generate receipt
- Field Visit Log: GPS location, notes, photos
- Home (Client): loan balance, next payment, payment history
- Payment: make payment, view receipt
- Notifications: due date reminders, payment confirmations

### Screen Flow
```
Login → Dashboard (Officer/Admin)
  → Clients → Client Detail → Loan Applications → Approve/Disburse
  → Collections → Payment Entry → Receipt
  → Reports → Portfolio Analysis
  → Savings → Deposit/Withdrawal
```

## 8. Business Model

- **Pricing tiers:**
  - Starter $29/شهر: حتى 200 عميل، 1 فرع، موظف إقراض واحد
  - Professional $69/شهر: حتى 1000 عميل، 3 فروع، 5 موظفين، تقارير متقدمة
  - Enterprise $149/شهر: عملاء غير محدودين، فروع غير محدودة، تكامل ERP، SM API
- **Free trial:** 14 يوم تجربة مجانية كاملة
- **Target MRR per client:** $29-$149
- **Setup fee:** $99 رسوم تهيئة أولية للباقة Professional وما فوق

## 9. Implementation Plan

- **Phase 1 (Weeks 1-2):** Auth + Branch, Client, ClientGroup models + CRUD APIs
- **Phase 2 (Weeks 3-4):** LoanProduct, Loan, RepaymentSchedule, Payment APIs + Sharia compliance logic
- **Phase 3 (Weeks 5-6):** React Dashboard — all management panels + reports
- **Phase 4 (Weeks 7-8):** Flutter App — officer & client apps, offline collections
- **Phase 5 (Weeks 9-10):** Security audit (financial data), testing, deployment, training docs

## 10. Risk & Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| حساسية البيانات المالية | High | تشفير AES-256، SOC 2 compliance، audit trail كامل |
| عدم الدقة في حسابات الفائدة | High | اختبارات آلية لجميع سيناريوهات الفائدة، مراجعة خبيرة |
| تعقيد الامتثال للشريعة | Medium | إشراف استشاري في الشريعة، mode switch للربوي/الإسلامي |
| منافسة من Mifos X (مفتوح المصدر) | Medium | تركيز على UX عربي ممتاز، دعم فني، تسعير ميسور |
| ضعف التبني من الموظفين الميدانيين | Medium | تصميم تطبيق جوال سهل جداً، تدريب ميداني، دعم عبر واتساب |
