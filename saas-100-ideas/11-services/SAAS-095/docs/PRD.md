# PRD: MobileFix (SAAS-095)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary

**منصة متكاملة لإدارة ورش صيانة الجوالات: قطع غيار، فنيين، ضمان.** تهدف MobileFix إلى رقمنة إدارة محلات وورش صيانة الجوالات بالكامل — من استقبال الأجهزة وتشخيص الأعطال إلى إدارة قطع الغيار والفنيين وضمان ما بعد الخدمة.

- المشكلة: ورش صيانة الجوالات تعاني من سوء إدارة قطع الغيار (نفاد أو تكدس)، صعوبة تتبع حالة الأجهزة قيد الصيانة، ضعف التواصل مع العملاء، وفقدان سجلات الصيانة والضمان.
- الحل: Laravel API + React Dashboard + Flutter App.

## 2. Market & Opportunity

- السوق المستهدف: 50,000+ ورشة ومحل صيانة جوالات في الشرق الأوسط وشمال أفريقيا
- الفئة: B2B (ورش الصيانة، موزعو قطع غيار، فنيون)
- المنافسون:
  - **RepairDesk** — نظام إدارة ورش إصلاح (أجنبي، سعري $79-199/شهر).
  - **Shopify POS** — ليس متخصصاً (يستخدمه البعض كحل بديل).
  - **Mend** — إدارة ورش إلكترونيات (أمريكي، لا يدعم العربية).
  - **Teknom** — حل تركي (محدود الميزات).
- التمايز: دعم كامل للعربية، أسعار مخفضة للسوق العربي، إدارة متقدمة لقطع الغيار مع نظام تنبيهات، تكامل مع موردي قطع الغيار المحليين.

## 3. User Personas

### شخص أساسي: صاحب ورشة صيانة جوالات
- الاسم: أحمد
- الدور: يدير ورشة صيانة في وسط مدينة القاهرة مع 3 فنيين
- الأهداف: تتبع الأجهزة قيد الصيانة، إدارة المخزون من قطع الغيار، إرسال فواتير للعملاء
- نقاط الألم: ضياع الأجهزة بين الفنيين، عدم معرفة حالة قطعة الغيار المتوفرة، شكاوى العملاء من التأخير

### شخص أساسي: فني صيانة
- الاسم: كريم
- الدور: فني صيانة جوالات خبرة 5 سنوات
- الأهداف: معرفة الأجهزة المسندة إليه، تسجيل خطوات الإصلاح، طلب قطع الغيار بسرعة
- نقاط الألم: انتظار دور الموافقة على قطع الغيار، نسيان خطوات الإصلاح، عدم توثيق الأعطال

### Admin: مدير المحل (أو مالك السلسلة)
- إدارة الفنيين، تقارير الأداء، تحليل الربحية.

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Device, RepairOrder, Technician, SparePart, Supplier, Diagnosis, Warranty, Customer, Invoice, Inventory
- RESTful CRUD for all resources
- Role-based auth (Admin, Technician, Customer, Supplier)
- Device intake system — brand, model, IMEI, reported issue, condition photos
- Diagnosis workflow — initial assessment → detailed diagnosis → quote → approval → repair
- Inventory management — stock levels, reorder points, supplier links, barcode scanning
- Technician assignment — auto-assign by workload, skill matching
- Warranty tracking — warranty period, service history, customer notifications
- Notification engine: SMS (status updates), push (technician alerts), email (invoices)

### React Dashboard (Web)
- Admin dashboard: devices in progress, completed today, revenue, technician performance
- Repair order management: intake form, diagnosis notes, status tracking (received → diagnosed → quoted → approved → repaired → QC → ready → delivered)
- Device tracking: search by IMEI, customer name, order number
- Inventory management: spare parts catalog, stock alerts, purchase orders
- Supplier management: supplier directory, price lists, order history
- Customer management: device history, total spend, preferences
- Technician management: performance metrics, workload, skill matrix
- Financial reports: daily revenue, parts cost vs labor, profit margins
- Settings: shop info, tax rates, invoice templates, SMS/WhatsApp templates

### Flutter App (Mobile)
- Technician app: receive assigned repairs, view diagnosis, update status
- Device scanning: barcode/QR code scanner for quick intake
- Customer portal: track repair status, approve quotes, make payments
- Push notifications: new assignment, quote approved, part arrived, device ready
- Spare parts lookup: search catalog, check stock, request from admin
- Performance dashboard: completed repairs, average repair time, earnings

## 5. Data Model (MVP)

### RepairOrder
- id, device_id (FK), customer_id (FK), technician_id (FK), reported_issue, diagnosis, status (received/diagnosed/quoting/approved/repairing/qc_ready/completed/delivered/cancelled), estimated_cost, final_cost, estimated_completion, completed_at, notes, created_at

### Device
- id, brand, model, serial_number, imei, color, storage, condition_photos (JSON), password, accessories (JSON), created_at

### Customer
- id, user_id (FK), name, phone, email, total_visits, total_spent, created_at

### Technician
- id, user_id (FK), specialization (hardware/software/screen), skill_level (junior/mid/senior), active_repairs_count, completed_count, rating, created_at

### SparePart
- id, name, brand, compatible_models (JSON), category, cost_price, selling_price, current_stock, min_stock_level, supplier_id (FK), barcode, location_in_store, created_at

### Supplier
- id, name, phone, email, lead_time, payment_terms, min_order, created_at

### Diagnosis
- id, repair_order_id (FK), technician_id (FK), findings (JSON), recommended_parts (JSON), labor_cost, total_estimated_cost, status (pending/approved/rejected), customer_approved_at, created_at

### Warranty
- id, repair_order_id (FK), warranty_type (part/labor/both), duration_days, start_date, end_date, terms, created_at

### Invoice
- id, repair_order_id (FK), parts_cost, labor_cost, tax, discount, total, status (pending/paid/overdue), payment_method, created_at

### InventoryTransaction
- id, spare_part_id (FK), type (in/out/adjustment), quantity, reference_type (purchase/repair/return), reference_id, notes, created_at

## 6. API Endpoints (MVP)

```
POST   /api/auth/login
POST   /api/auth/register
GET    /api/auth/me

GET    /api/repair-orders
POST   /api/repair-orders
GET    /api/repair-orders/{id}
PUT    /api/repair-orders/{id}/status
GET    /api/repair-orders/queue          (technician view)
GET    /api/repair-orders/today          (admin dashboard)

POST   /api/repair-orders/{id}/diagnosis
PUT    /api/repair-orders/{id}/approve-quote
POST   /api/repair-orders/{id}/warranty

GET    /api/devices/lookup?imei=
GET    /api/customers
POST   /api/customers
GET    /api/customers/{id}/history

GET    /api/spare-parts
POST   /api/spare-parts
PUT    /api/spare-parts/{id}/stock
GET    /api/spare-parts/low-stock

GET    /api/suppliers
POST   /api/suppliers
POST   /api/suppliers/{id}/purchase-order

GET    /api/technicians
GET    /api/technicians/{id}/performance

GET    /api/invoices
POST   /api/invoices/{id}/pay

GET    /api/dashboard/admin
GET    /api/dashboard/technician
```

## 7. User Interface (Screen List)

### Dashboard Screens (React)
1. Login / Register
2. Admin Dashboard — KPI cards (in queue, in progress, completed today, revenue), device status board
3. Repair Orders — filterable/sortable table with status, search by IMEI/customer
4. Repair Detail — full timeline, diagnosis, parts used, technician notes
5. New Repair Intake — form with device info, customer info, issue description, photos
6. Inventory — spare parts list with stock levels, search, low-stock alerts
7. Spare Part Detail — stock history, compatible devices, supplier info, purchase orders
8. Suppliers — directory, price lists, order history
9. Customers — directory with device history, total spend
10. Technicians — workload view, performance metrics, skill matrix
11. Reports — daily revenue, parts vs labor, technician ranking

### Mobile Screens (Flutter)
1. Technician Login → Dashboard (assigned repairs, queue)
2. Repair Detail — device info, diagnosis, status update buttons
3. Scan Device — camera scan IMEI/QR for quick lookup
4. Diagnosis Form — issue selection, part selection, cost estimation
5. Part Lookup — search by name/model, check stock, request part
6. Customer View — customer can check repair status via tracking code
7. Notifications — new assignments, approvals, part arrivals
8. Profile — technician stats, shift info

### Screen Flow
```
Customer: Bring Device → Intake (photos, issue) → Diagnosis → Quote → Approve → Repair → QC → Pickup + Pay
Technician: Login → View Queue → Select Device → Diagnose → Request Parts → Repair → Complete → Notify Customer
Admin: Dashboard → Monitor Queue → Approve Quotes → Manage Inventory → View Reports
```

## 8. Business Model

- **باقة ورشة واحدة**: $29/شهر (ورشة واحدة، 3 فنيين، 100 طلب/شهر)
- **باقة متعددة الورش**: $59/شهر (حتى 3 ورش، 10 فنيين، غير محدود الطلبات)
- **باقة سلسلة**: $129/شهر (غير محدود ورش، تحليلات متقدمة، API)
- **رسوم إضافية**: 1% على مشتريات قطع الغيار عبر المنصة
- فترة تجربة مجانية: 14 يوماً
- MRR المستهدف لكل ورشة: $29-$129

## 9. Implementation Plan

- Phase 1 (Weeks 1-2): Laravel API — Auth, RepairOrder/Device/Customer CRUD, Sanctum roles
- Phase 2 (Weeks 3-4): Laravel API — Diagnosis workflow, inventory management, supplier integration
- Phase 3 (Weeks 5-6): React Dashboard — Admin dashboard, repair tracking, inventory, technician management
- Phase 4 (Weeks 7-8): Flutter App — Technician app, customer portal, device scanning, notifications
- Phase 5 (Weeks 9-10): Barcode/QR integration, warranty system, Arabic localization, testing

## 10. Risk & Mitigation

- **مخاطرة تقنية**: تكامل الباركود وقراءة IMEI — التخفيف: استخدام مكتبات OCR وQR مفتوحة المصدر، دعم الإدخال اليدوي كخيار احتياطي.
- **مخاطرة تشغيلية**: مقاومة الفنيين لاستخدام التطبيق — التخفيف: واجهة بسيطة بالعربية، تدريب مجاني، حوافز للأجهزة المسجلة.
- **مخاطرة مخزون**: صعوبة تتبع قطع الغيار الدقيقة — التخفيف: نظام باركود، نقاط إعادة طلب تلقائية، تكامل مع كبار الموردين.
- **مخاطرة قانونية**: مسؤولية الضمان على الإصلاحات — التخفيف: سياسة ضمان واضحة، توثيق الإصلاح بالصور، شروط وأحكام موحدة.
