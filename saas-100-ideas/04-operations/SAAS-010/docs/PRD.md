# PRD: GarageMaster (SAAS-010)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner**: نظام إدارة ورش السيارات ومراكز الصيانة — تتبع الإصلاحات، إدارة قطع الغيار، فواتير إلكترونية، تواصل مع العملاء، وتقارير ورشة العمل.
- **Problem statement**: ورش السيارات تدير الإصلاحات يدوياً — أوراج العمل تفقد الطلبات، محاسبة العملاء غير دقيقة، إدارة المخزون فوضوية.
- **Proposed solution**: Laravel API + React Dashboard + Flutter App — أمر شغل (Job Order) رقمي، إدارة المخزون، فواتير، إشعارات للعملاء بحالة السيارة.

## 2. Market & Opportunity
- **Target market size**: سوق إدارة ورش السيارات ~$4B (2025)، الشرق الأوسط ~$250M نمو 12% CAGR.
- **Customer segment**: B2B — ورش سيارات مستقلة، مراكز صيانة معتمدة، ورش سمكرة ودهان.
- **Competitor landscape**:
  1. **AutoFluent**: شامل لكن أمريكي، سعري ($100+)، بدون دعم عربي.
  2. **GaragePlug**: بريطاني، موجه لورش المملكة المتحدة.
  3. **ShopManager**: كندي، ثقيل، واجهة قديمة.
  4. **Mitchell 1**: شامل لكن موجه لسوق أمريكا فقط.
  5. **YouMech**: منصة تواصل وليس نظام إدارة ورش.
- **Differentiation**: عربي كامل، تسعير منخفض، دعم قطع الغيار المحلية، إشعارات واتساب للعملاء، مناسب لورش السيارات الصغيرة والمتوسطة في المنطقة.

## 3. User Personas

### Primary: أبو خالد — صاحب ورشة سيارات (6 رفعات)
- **الدور**: يدير ورشة ميكانيكا وسمكرة مع 8 فنيين في الدمام.
- **الأهداف**: تتبع أعمال الإصلاح، إدارة المخزون، إصدار فواتير.
- **نقاط الألم**: يضيع وقت في البحث عن قطع الغيار، العملاء يتصلون لمعرفة حالة السيارة.

### Secondary: كريم — فني ميكانيكي
- **الدور**: يعمل في الورشة، يقوم بالإصلاحات والصيانة الدورية.
- **الأهداف**: رؤية أمر العمل، تسجيل القطع المستخدمة، تحديث حالة الإصلاح.
- **نقاط الألم**: ينسى تسجيل القطع، صعوبة قراءة خط يد صاحب الورشة.

### Customer: ناصر — صاحب سيارة
- **الدور**: عميل دائم للورشة، يجلب سيارته للصيانة الدورية.
- **الأهداف**: معرفة حالة سيارته، تقدير التكلفة، إشعارات عند الانتهاء.
- **نقاط الألم**: ينتظر بدون تحديث، فواتير غير مفصلة.

## 4. Features by Platform

### Laravel API (Backend)
- Core domain models: Workshop, Technician, Vehicle, Customer, JobOrder, ServiceItem, Part, Inventory, Invoice, Expense
- RESTful endpoints: full CRUD
- Auth: Sanctum multi-role (owner/tech/cashier)
- Job order engine: create from customer request, assign tech, list services + parts
- Inventory: part catalog (OEM + aftermarket), stock tracking, supplier orders
- Pricing engine: labor rates (per tech or flat), part markup %
- Invoice: auto-calculate from job order items, tax, discount
- Notifications: WhatsApp status updates (car received → in progress → ready), invoice

### React Dashboard (Web)
- Admin panel: workshop profile, staff, service catalog, parts catalog
- Job board: kanban-style (Pending / In Progress / Done / Delivered), filter by tech
- Job order detail: services list, parts used, labor time, attachments (photos)
- Customer directory: vehicle history, total spend, visit frequency
- Inventory: stock table, reorder alerts, supplier list, purchase orders
- Financial dashboard: daily revenue, job profit margin, expenses, AR
- Reports: technician productivity, service frequency, profit by service

### Flutter App (Mobile)
- Tech app: assigned jobs, update status, scan part barcode, add labor time, photo attachment
- Customer app: my vehicles, job status tracking, service history, estimated cost approval
- Push notifications: job started, parts arrived, job done, payment reminder
- Offline: job list cached for garage with poor signal

## 5. Data Model (MVP)

| Entity | Fields | Relationships |
|---|---|---|
| Workshop | id, name, address, phone, license, vat_number | hasMany Technician, JobOrder, Customer |
| Vehicle | id, customer_id, make, model, year, plate, vin, mileage | belongsTo Customer, hasMany JobOrder |
| Customer | id, workshop_id, name, phone, email, total_visits | belongsTo Workshop |
| Technician | id, workshop_id, name, specialty, labor_rate, phone | belongsTo Workshop |
| JobOrder | id, workshop_id, vehicle_id, customer_id, tech_id, status, created_at, completed_at, total | belongsTo Workshop/Vehicle/Customer |
| ServiceItem | id, job_order_id, service_name, labor_time, labor_cost | belongsTo JobOrder |
| Part | id, job_order_id, part_name, sku, quantity, unit_price, supplier | belongsTo JobOrder |
| InventoryItem | id, workshop_id, name, sku, category, stock, min_stock, price, supplier | belongsTo Workshop |
| Invoice | id, job_order_id, total_amount, tax, discount, grand_total, status, paid_at | belongsTo JobOrder |
| Expense | id, workshop_id, category, amount, description, date | belongsTo Workshop |

## 6. API Endpoints (MVP)

| Method | Endpoint | Description |
|---|---|---|
| GET | /api/job-orders | List job orders (filter: status/tech/date) |
| POST | /api/job-orders | Create job order |
| PATCH | /api/job-orders/{id}/status | Update status |
| GET | /api/vehicles/{plate}/history | Vehicle service history by plate |
| GET | /api/inventory | Parts inventory (search: by name/sku) |
| POST | /api/inventory/adjust | Adjust stock (add/remove) |
| GET | /api/job-orders/{id}/invoice | Generate invoice |
| GET | /api/workshop/{id}/reports | Workshop analytics |

## 7. User Interface (Screen List)

### Dashboard screens (React)
- Login → Dashboard (active jobs, today's revenue, pending parts)
- Job board: kanban columns (Pending / In Progress / Quality Check / Done / Delivered)
- Job order form: vehicle info, customer, service list, parts, labor, total estimate
- Customer page: search → profile → vehicle list → job history
- Inventory: sortable/searchable table with stock levels and reorder buttons
- Reports: daily/weekly/monthly revenue chart, service frequency, top customers
- Expenses: add expense, categorize, view expense vs revenue
- Settings: workshop info, tax rate, labor rates, notification templates

### Mobile screens (Flutter)
- Tech: Login → My jobs → Job detail (services, parts, timer) → Update status
- Customer: Login → My vehicles → Job status → Approve estimate → Rate workshop

### Screen flow (text)
```
Dashboard → Job Board (Kanban)
                ├── New Job → Vehicle/Customer select → Services → Parts → Estimate
                ├── Job Detail → Timeline →Tech updates →Photos
                ├── Customers → Search → Profile → History
                ├── Inventory → Parts Table → Reorder → Supplier
                └── Reports → Revenue / Jobs / Profit

Tech App → Assigned Jobs → Job Detail → Scan Part → Add Labor → Mark Complete
```

## 8. Business Model
- **Starter**: $19/month — up to 100 job orders/month, 3 techs, basic inventory
- **Pro**: $39/month — unlimited job orders, 10 techs, inventory, customer app, WhatsApp
- **Premium**: $69/month — unlimited techs, multi-branch, API, priority support
- **Free trial**: 14-day Pro trial

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2)**: Laravel API — Workshop, Vehicle, Customer, JobOrder, ServiceItem CRUD
- **Phase 2 (Weeks 3-4)**: React Dashboard — Job board (kanban), Customer mgmt, Job form
- **Phase 3 (Weeks 5-6)**: Flutter App — Tech app (job updates, parts, timer), Customer app
- **Phase 4 (Weeks 7-8)**: Inventory, Invoicing, WhatsApp notifications, Testing

## 10. Risk & Mitigation
- **Technical**: Part number standardization — strategy: support both OEM and aftermarket SKUs, free-text fallback.
- **Market**: Garages prefer paper estimates — strategy: print PDF estimate from dashboard, digital optional.
- **Operational**: Car pickup/delivery handoff — strategy: photo attachment for each job for condition documentation.
