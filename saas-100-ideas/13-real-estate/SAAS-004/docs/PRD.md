# PRD: RentTrack (SAAS-004)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner**: نظام إدارة العقارات والإيجارات — عقود إيجار، تحصيل دفعات إلكتروني، تتبع طلبات الصيانة، وتواصل مباشر مع المستأجرين.
- **Problem statement**: ملاك العقارات والوسطاء يعانون من إدارة الدفعات الورقية، متابعة الصيانة يدوياً، وضعف التواصل مع المستأجرين.
- **Proposed solution**: Laravel API + React Dashboard + Flutter App — لوحة تحكم للعقارات، عقود رقمية، تذكير بالدفعات، منصة صيانة مدمجة.

## 2. Market & Opportunity
- **Target market size**: سوق PropTech العالمي ~$20B (2025)، الشرق الأوسط ~$1.2B نمو 16% CAGR.
- **Customer segment**: B2B/B2C — ملاك عقارات، شركات إدارة عقارات، وسطاء عقاريون.
- **Competitor landscape**:
  1. **Property Finder / Bayut**: إعلانات فقط، لا تدير العقود والإيجارات.
  2. **Buildium**: حل أمريكي قوي لكن مكلف ($50+/month) بدون دعم عربي.
  3. **AppFolio**: شامل لكن سعره عالي، موجه لشركات كبيرة.
  4. **Stessa**: مجاني لكن محدود بالتقارير.
  5. **بيوت**: بوابة عقارات فقط.
- **Differentiation**: دعم عربي كامل، تسعير منخفض، تكامل مع أنظمة الدفع المحلية (STC Pay، مدى)، عقود إلكترونية، تتبع صيانة ذكي.

## 3. User Personas

### Primary: عبدالله — مالك عقارات (10 وحدات)
- **الدور**: يملك 10 شقق للإيجار في الرياض، يديرها بنفسه.
- **الأهداف**: تحصيل الإيجارات في وقتها، متابعة الصيانة، حفظ العقود.
- **نقاط الألم**: بعض المستأجرين يتأخرون بالدفع، يفقد سجلات الصيانة.

### Secondary: هند — وسيطة عقارية
- **الدور**: تدير محفظة 25 عقاراً لعملاء مختلفين.
- **الأهداف**: عرض عقارات للزبائن، إصدار عقود، متابعة مع الملاك.
- **نقاط الألم**: تنسيق التواصل بين مالك ومستأجر مرهق.

### Admin: سعد — مدير شركة إدارة عقارات
- **الدور**: يدير فريق من 5 وسطاء ومحفظة 100 عقار.
- **الأهداف**: تقارير أداء، إدارة فريق، رؤية مالية موحدة.
- **نقاط الألم**: لا رؤية فورية لحالة كل عقار.

## 4. Features by Platform

### Laravel API (Backend)
- Core domain models: Property, Unit, Lease, Tenant, Payment, MaintenanceRequest, Document, Owner
- RESTful endpoints: full CRUD
- Auth: Sanctum multi-role (owner/agent/tenant)
- Contract generation: PDF from template with e-signature field
- Payment engine: recurring invoice generation, late fee calculation
- Payment gateway: Stripe, Moyasar, STC Pay
- Notifications: rent due reminder (3 days before), overdue alert, maintenance updates

### React Dashboard (Web)
- Admin panel: portfolio overview, occupancy rate, revenue
- Property management: add/edit units, floor plan upload
- Tenant management: profile, lease history, payment status
- Contract builder: template editor, send for signature
- Financial dashboard: rent collection rate, late payments, P&L report
- Maintenance board: requests list, assign contractor, track status

### Flutter App (Mobile)
- Owner app: portfolio stats, payment alerts, approve maintenance
- Tenant app: pay rent, submit maintenance request, message landlord
- Push notifications: rent reminder, receipt, maintenance update
- Offline: cached property list, pending actions sync later

## 5. Data Model (MVP)

| Entity | Fields | Relationships |
|---|---|---|
| Property | id, owner_id, name, type, address, coordinates | belongsTo Owner, hasMany Unit |
| Unit | id, property_id, unit_number, bedrooms, rent_amount, deposit, status | belongsTo Property |
| Tenant | id, name, phone, email, id_number, emergency_contact | hasMany Lease |
| Lease | id, unit_id, tenant_id, start_date, end_date, rent, deposit, payment_day | belongsTo Unit/Tenant |
| Payment | id, lease_id, amount, due_date, paid_date, status, method, receipt_url | belongsTo Lease |
| MaintenanceRequest | id, unit_id, tenant_id, category, description, priority, status, cost | belongsTo Unit/Tenant |
| Document | id, lease_id, type (contract/inspection), file_url, signed_at | morphTo |

## 6. API Endpoints (MVP)

| Method | Endpoint | Description |
|---|---|---|
| GET | /api/properties | List properties (with occupancy stats) |
| POST | /api/leases | Create lease |
| GET | /api/leases/{id}/payments | Payment schedule for lease |
| POST | /api/payments | Record payment |
| GET | /api/maintenance | Maintenance requests (filterable: status/unit) |
| POST | /api/maintenance | Submit request |
| GET | /api/reports/rent-collection | Rent collection rate (date range) |
| POST | /api/documents/contract | Generate & send contract |

## 7. User Interface (Screen List)

### Dashboard screens (React)
- Login → Portfolio dashboard (cards: total units, occupied, overdue)
- Property list → Property detail (units table, occupancy)
- Tenant directory (searchable)
- Lease management: active leases, expiring soon
- Payment ledger: monthly view, paid/pending/overdue
- Maintenance board: kanban style (new/assigned/in-progress/done)
- Documents: contracts, inspection reports
- Reports: occupancy trends, revenue chart, late payment analysis

### Mobile screens (Flutter)
- Owner: Dashboard → Properties → Tenant detail → Payments
- Tenant: My Lease → Pay Rent → Submit Maintenance → Contact Manager

### Screen flow (text)
```
Login (role-based redirect)

Owner/Agent:
Dashboard → Property List → Unit Detail
                ├── Leases (active/history)
                ├── Payments (ledger)
                └── Maintenance (requests)
Reports → Rent Collection / Occupancy / Revenue

Tenant:
Login → My Lease → Pay Rent (card/STC Pay) → Receipt
        └── Maintenance → New Request → Track Status
```

## 8. Business Model
- **Basic**: $19/month — up to 10 units, basic reports
- **Pro**: $39/month — up to 50 units, contracts, maintenance, payment gateway
- **Enterprise**: $79/month — unlimited units, API, multiple users, white-label
- **Free trial**: 14-day Pro trial

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2)**: Laravel API — Property, Unit, Tenant, Lease, Payment CRUD
- **Phase 2 (Weeks 3-4)**: React Dashboard — Portfolio view, Lease mgmt, Maintenance board
- **Phase 3 (Weeks 5-6)**: Flutter App — Owner + Tenant apps, Payment gateway
- **Phase 4 (Weeks 7-8)**: Contract generation (PDF), e-signature, Reports, Testing

## 10. Risk & Mitigation
- **Technical**: Payment reconciliation complexity — strategy: use payment gateway webhooks for auto-reconciliation.
- **Market**: Landlord/tenant trust in digital payments — strategy: educate with free trial, support local gateways.
- **Legal**: E-contract validity — strategy: align with Saudi E-Transaction Law, integrate with Nafith/ABSHR.
