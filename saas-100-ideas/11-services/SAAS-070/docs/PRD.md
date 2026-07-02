# PRD: RepairPro (SAAS-070)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner**: منصة إدارة ورش الصيانة المنزلية — إدارة الطلبات، تتبع الأجهزة، قطع الغيار، فنيون متنقلون، وتواصل مع العملاء.
- **Problem statement**: شركات الصيانة المنزلية والفنيون المستقلون يعانون من فوضى إدارة طلبات العملاء، صعوبة تتبع حالة الأجهزة، ضعف إدارة المخزون من قطع الغيار، وغياب نظام موحد للفواتير والتواصل.
- **Proposed solution**: Laravel API + React Dashboard + Flutter App — نظام لإدارة طلبات الصيانة، تتبع الأجهزة (استلام → إصلاح → تسليم)، مخزون قطع الغيار، وجدولة الفنيين المتنقلين.

## 2. Market & Opportunity
- **Target market size**: سوق الصيانة المنزلية ~$50B عالمياً، الشرق الأوسط ~$3B (نمو 8% بسبب زيادة الملكية السكنية).
- **Customer segment**: B2B — شركات صيانة منزلية، ورش تصليح, B2C — فنيون مستقلون (سباكين، كهربائيين، مكيفات).
- **Competitor landscape**:
  1. **Urban Company**: منصة حجز خدمات منزلية (هند), لا تقدم إدارة ورش أو مخزون.
  2. **TaskRabbit**: مهام عامة، ليس مخصصاً للصيانة.
  3. **Fixpro**: تطبيق أمريكي، بدون دعم عربي أو إدارة مخزون.
  4. **منزلي**: تطبيق عربي لحجز خدمات لكن بدون نظام إدارة ورش.
  5. **تقوم**: منصة سعودية لكن تتوسط بين العميل والفني بدون أدوات إدارة.
- **Differentiation**: عربي كامل, نظام إدارة ورش متكامل (استلام → تشخيص → إصلاح → تسليم), تتبع الأجهزة بالباركود, إدارة مخزون قطع الغيار مع تنبيهات إعادة طلب, تطبيق فني متنقل مع تصوير وإثبات الإصلاح.

## 3. User Personas

### Primary: عمار — مدير ورشة صيانة منزلية
- **الدور**: يدير ورشة ب 5 فنيين، يستقبل 20-30 جهازاً أسبوعياً (مكيفات، ثلاجات، غسالات).
- **الأهداف**: تتبع حالة كل جهاز، توزيع العمل على الفنيين، إدارة قطع الغيار.
- **نقاط الألم**: الأجهزة تضيع في الورشة، العملاء يزعجون هاتفياً "جهازي جاهز؟", قطع الغيار تنفد بدون علمه.

### Secondary: فني — خالد (فني تكييف)
- **الدور**: يصلح مكيفات وثلاجات، يركب قطع غيار، أحياناً يزور المنازل.
- **الأهداف**: معرفة مهامه اليومية، تسجيل خطوات الإصلاح، طلب قطع غيار.
- **نقاط الألم**: لا يعرف قطع الغيار المتوفرة إلا بسؤال المخزن, صعوبة توثيق الإصلاح للعميل.

### Admin: هند — منسقة خدمة عملاء
- **الدور**: تستقبل اتصالات العملاء، تسجل الطلبات، تتابع حالة الإصلاح.
- **الأهداف**: تسجيل سريع للبلاغات، إبلاغ العملاء بحالة الجهاز، إنشاء فواتير.
- **نقاط الألم**: العملاء يعيدون الاتصال عدة مرات, لا تعرف وقت انتهاء الإصلاح بالضبط.

## 4. Features by Platform

### Laravel API (Backend)
- Core domain models: Company, Workshop, Technician, Customer, RepairRequest, Device, Diagnosis, RepairLog, SparePart, PartMovement, Invoice, Appointment
- RESTful endpoints: CRUD for all models
- Auth: Sanctum multi-role (admin/technician/customer_service/customer)
- Request management: customer call → create ticket → assign → diagnose → parts → repair → QC → deliver
- Device tracking: brand, model, serial number, barcode label per device
- Diagnosis & repair log: issue description, diagnosis notes, parts used, labor hours, photos
- Spare parts inventory: stock by workshop, supplier list, reorder alerts (low stock)
- Part movement: stock in (purchase), stock out (used in repair), transfer between workshops
- Appointment scheduling: customer pickup/delivery, on-site visit scheduling
- Barcode/label printing: device label with QR code for tracking
- Notifications: SMS/WhatsApp status updates to customers (received, diagnosing, repaired, ready)

### React Dashboard (Web)
- Dashboard: devices in workshop (received → in-progress → ready → delivered), today's stats
- Repair queue: Kanban board (received → diagnosed → parts-ordered → repairing → QC → ready → delivered)
- Device detail: customer info, device specs, diagnosis notes, photos, parts used, invoice
- Technician assignment: drag device to technician, workload view
- Inventory: parts list, stock levels, reorder alerts, supplier management
- Purchase orders: create PO for suppliers, receive stock
- Customer management: client list, device history, invoices
- Invoicing: parts + labor, tax, discount, print/email
- Reports: technician productivity, common repairs, parts usage, revenue
- Settings: workshop info, pricing, labels, notification templates

### Flutter App (Mobile)
- Technician app: today's tasks, scan device barcode → view details → add diagnosis → log repair → mark complete
- Customer app: submit repair request, track device status, view invoice
- Push notifications: device status changes, appointment reminders
- Photo capture: before/after repair photos, damage evidence
- Barcode scanner: scan device label for quick lookup
- On-site repair: GPS-tracked visit, customer signature capture
- Offline: task list cached, repair logs queued for sync

## 5. Data Model (MVP)

| Entity | Fields | Relationships |
|---|---|---|
| Company | id, name, cr_no, address, phone, logo | hasMany Workshop, Technician |
| Workshop | id, company_id, name, location, phone | belongsTo Company |
| Technician | id, company_id, workshop_id, name, phone, specialization, status | belongsTo Company/Workshop |
| Customer | id, company_id, name, phone, address, email | belongsTo Company |
| RepairRequest | id, workshop_id, customer_id, device_id, technician_id, status (received/diagnosing/repairing/QC/ready/delivered), received_at, diagnosis, estimated_cost, ready_at | belongsTo Workshop/Customer/Device/Technician |
| Device | id, customer_id, brand, model, serial_no, barcode, type (AC/fridge/washer/...), warranty_expiry, notes | belongsTo Customer |
| RepairLog | id, repair_request_id, technician_id, action, description, photos, logged_at | belongsTo RepairRequest |
| SparePart | id, company_id, name_ar, name_en, sku, manufacturer, compatible_brands, unit_price, stock_qty, min_stock | belongsTo Company |
| PartMovement | id, part_id, repair_request_id, type (in/out), quantity, unit_price, reference (purchase_order_no/sales_invoice) | belongsTo SparePart |
| Invoice | id, repair_request_id, parts_total, labor_total, tax, discount, grand_total, paid, method, issued_at | belongsTo RepairRequest |

## 6. API Endpoints (MVP)

| Method | Endpoint | Description |
|---|---|---|
| POST | /api/auth/login | Login (multi-role) |
| GET | /api/repair-requests | List requests (filterable: status, workshop, technician) |
| POST | /api/repair-requests | Create repair request |
| PATCH | /api/repair-requests/{id}/status | Update status (with diagnosis/notes) |
| POST | /api/repair-requests/{id}/assign | Assign technician |
| POST | /api/devices/scan | Scan barcode → device info + request history |
| GET | /api/inventory/parts | Parts list (searchable, filter by stock level) |
| POST | /api/inventory/parts/movement | Record part usage for repair |
| GET | /api/inventory/alerts | Low stock alerts |
| POST | /api/invoices | Generate invoice from repair |
| GET | /api/dashboard/workshop | Workshop stats (devices by status, revenue) |

## 7. User Interface (Screen List)

### Dashboard screens (React)
- Login
- Dashboard: device counts by status, alerts (low stock), today's revenue
- Repair Kanban: drag devices between columns (received → diagnosing → repairing → QC → ready)
- Device detail/Repair form: customer info, device specs, diagnosis, parts, labor, photos
- Technician workload: calendar/timeline, assignment
- Inventory: parts catalog, stock view, movement log, purchase orders
- Customer list → customer detail (history, devices)
- Invoicing: create, print, mark paid
- Reports: technician performance, repair duration, parts consumption
- Settings: workshop, pricing, labels, notifications

### Mobile screens (Flutter)
- Technician: Login → Today's Tasks → Scan Device → Diagnose → Log Repair → Complete
- Technician: Parts lookup, request parts from inventory
- Customer: Login → My Devices → Submit Request → Track Status → View Invoice → Rate
- Scanner: universal barcode scanner → device info + repair history

### Screen flow (text)
```
Login → Dashboard (Kanban: devices by status)
           ├── New Device → Create Request → Assign Technician → Print Label
           ├── Kanban → Drag to Diagnose → Add Diagnosis → Order Parts
           │           → Drag to Repair → Log Work → Add Parts Used
           │           → Drag to QC → Verify → Ready
           │           → Deliver → Close → Invoice
           ├── Inventory → Parts List → Add → Receive Stock → Set Threshold
           │            → Reorder Alerts → Create PO
           ├── Customers → Search → Detail → Device History
           └── Reports → Revenue / Technician / Parts / Duration
```

## 8. Business Model
- **Starter**: $49/month — up to 3 technicians, 50 devices/month
- **Pro**: $99/month — up to 10 technicians, unlimited devices, inventory management, customer app
- **Enterprise**: Custom — unlimited techs, multi-workshop, API access, white-label
- **Free trial**: 14-day free trial

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2)**: Laravel API — Company, Workshop, Customer, Device, RepairRequest CRUD, barcode generation
- **Phase 2 (Weeks 3-4)**: React Dashboard — Repair Kanban, Device management, Technician assignment
- **Phase 3 (Weeks 5-6)**: Flutter App — Technician scanner + repair log, Customer tracking app
- **Phase 4 (Weeks 7-8)**: Inventory management, Invoicing, Reports, WhatsApp notifications, Testing, Deploy

## 10. Risk & Mitigation
- **Technical**: Barcode hardware compatibility — strategy: support multiple label printers (Zebra, Brother generic ESC/P).
- **Technical**: Photo upload size — strategy: compress on device, limit to 5 photos per repair.
- **Market**: Small workshops prefer paper — strategy: offer basic free tier (paper replacement), show value through reports.
- **Competitive**: Generalist field service software — strategy: focus on repair workshop workflow (not field service), Arabic-first.
