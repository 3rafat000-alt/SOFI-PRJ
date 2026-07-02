# PRD: GasDistribute (SAAS-085)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** منصة لإدارة توزيع الغاز المنزلي تمكن الموزعين من تتبع أسطوانات الغاز، إدارة الطلبات، وتنظيم عمليات التوزيع بكفاءة.
- **Problem statement:** يعاني قطاع توزيع الغاز المنزلي من صعوبة تتبع أسطوانات الغاز (فارغة/ممتلئة)، تأخير في توصيل الطلبات، عدم دقة سجلات العملاء، ومخاطر سلامة بسبب عدم تتبع تاريخ فحص الأسطوانات.
- **Proposed solution:** Laravel API + React Dashboard + Flutter App

## 2. Market & Opportunity
- **Target market:** موزعو الغاز المنزلي، محلات بيع الغاز، شركات توزيع الغاز الكبرى. سوق الغاز المنزلي في العالم العربي يشمل ملايين الأسر ويعتمد على التوزيع المحلي.
- **Customer segment:** B2B
- **Competitor landscape:**
  1. **أنظمة شركات الغاز الكبرى** - أنظمة مغلقة لا تخدم الموزعين المستقلين
  2. **تطبيقات توصيل عامة** - مثل مرسول ونون لا تدير مخزون الأسطوانات
  3. **Excel وسجلات ورقية** - الطريقة السائدة حالياً
  4. **GasApp** - تطبيق تواجد محدود في بعض الدول
- **Differentiation:** حل متخصص في إدارة توزيع الغاز يغطي دورة حياة الأسطوانة (استلام ← تعبئة ← توزيع ← استرجاع ← فحص) مع تكامل مع أنظمة الدفع الرقمية.

## 3. User Personas

### الشخصية الأساسية: موزع غاز - أبو سعد
- **الدور:** يمتلك مستودع توزيع غاز ويخدم 500 عميل منزلي
- **الأهداف:** تتبع الأسطوانات (فارغة/ممتلئة)، تنظيم طلبات التوصيل، تحصيل المدفوعات
- **نقاط الألم:** العملاء يطلبون عبر الهاتف بشكل عشوائي، الأسطوانات تضيع، صعوبة تحصيل الديون

### الشخصية الثانوية: سائق توزيع - يوسف
- **الدور:** سائق شاحنة توزيع غاز
- **الأهداف:** معرفة طلبات التوصيل اليومية، أفضل طريق، تسليم واستلام الأسطوانات
- **نقاط الألم:** عدم وضوح قائمة التوصيل، العملاء غير موجودين، صعوبة إثبات التسليم

### الشخصية الثالثة: عميل منزلي - أم خالد
- **الدور:** ربة منزل تطلب أسطوانات غاز بانتظام
- **الأهداف:** طلب أسطوانة بسهولة، تتبع وقت التوصيل، الدفع إلكترونياً
- **نقاط الألم:** لا تعرف رقم الموزع، تنتظر ساعات، تدفع نقداً بصعوبة

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Distributor, Cylinder, CylinderType, Customer, Order, Delivery, Payment, Inspection
- RESTful endpoints
- Auth & roles: DistributorOwner, Driver, Customer, SuperAdmin
- Cylinder lifecycle tracking (new → filled → delivered → returned → inspected → refilled)
- Barcode/QR code per cylinder for tracking
- Order management with auto-assignment to nearest driver
- Delivery route optimization
- Smart stock alerts (low inventory, unreturned cylinders)
- Inspection scheduling and compliance tracking
- Payment processing (cash, Mada, STC Pay)
- SMS/WhatsApp notifications for order status

### React Dashboard (Web)
- Real-time stock dashboard (filled/empty/in-transit cylinders)
- Order management pipeline with auto-assignment
- Customer management with order history and address map
- Driver management with performance tracking
- Cylinder tracking (detailed lifecycle per cylinder)
- Route planning and delivery tracking map
- Payment reconciliation dashboard
- Inspection compliance calendar
- Reports (sales, deliveries, cylinder losses, customer churn)

### Flutter App (Mobile)
- Customer app: request cylinder, track delivery, pay online, rate service
- Driver app: view delivery manifest, navigate, scan cylinder barcode for pickup/delivery, proof of delivery with photo
- Distributor app: real-time dashboard, approve orders, manage stock
- Push notifications for order confirmation, driver assigned, out for delivery, delivered
- Barcode scanner for cylinder check-in/check-out

## 5. Data Model (MVP)

- **User:** id, name, email, phone, role, distributor_id
- **Distributor:** id, name, address, license_number, service_area, commission_rate, settings
- **CylinderType:** id, name, weight_kg, price, deposit_amount, is_active
- **Cylinder:** id, cylinder_number, barcode, type_id, status (empty/filled/delivered/returned/inspection), manufacture_date, last_inspection_date, current_location, distributor_id
- **Customer:** id, name, phone, address, lat, lng, cylinder_count, payment_method, notes
- **Order:** id, order_number, customer_id, distributor_id, driver_id, cylinder_type_id, quantity, total_amount, status, delivery_fee, notes, created_at
- **Delivery:** id, order_id, driver_id, cylinders_delivered, cylinders_picked_up, status, route_order, delivered_at, proof_photo
- **Payment:** id, order_id, amount, method, transaction_id, collected_by, status, paid_at
- **Inspection:** id, cylinder_id, inspector_id, inspection_date, result (pass/fail), next_inspection_date, notes, certificate

## 6. API Endpoints (MVP)

- `POST /api/auth/login` - Login (multi-role)
- `GET /api/dashboard` - Distributor dashboard stats
- `GET /api/cylinders` - List cylinders with filters
- `POST /api/cylinders` - Register new cylinder
- `GET /api/cylinders/{id}/history` - Cylinder lifecycle
- `PUT /api/cylinders/{id}/status` - Update cylinder status
- `GET /api/customers` - Customer list
- `POST /api/customers` - Add customer
- `GET /api/orders` - Orders list
- `POST /api/orders` - Create order (customer app)
- `GET /api/orders/{id}` - Order details
- `PUT /api/orders/{id}/status` - Update order status
- `POST /api/orders/{id}/assign-driver` - Assign driver
- `GET /api/deliveries` - Driver's delivery list
- `PUT /api/deliveries/{id}/status` - Update delivery (scan, proof)
- `POST /api/payments` - Record payment
- `GET /api/inspections` - Inspection schedule
- `POST /api/inspections` - Record inspection result
- `GET /api/reports/sales` - Daily/weekly/monthly sales
- `GET /api/reports/cylinders` - Cylinder inventory report

## 7. User Interface (Screen List)

### Dashboard Screens (React)
1. Login (Distributor/Admin)
2. Distributor Overview - stock, active orders, pending deliveries
3. Order Management - pipeline with assign driver flow
4. Cylinder Inventory - fill/empty counts, by type
5. Customer Map - customer locations with order frequency
6. Driver Management - drivers, performance, earnings
7. Delivery Tracking - real-time map of active deliveries
8. Payment Reconciliation - daily collections, pending
9. Inspection Management - upcoming, overdue, completed
10. Reports - exportable charts

### Mobile Screens (Flutter)
1. Customer: Splash → Login/Register → Home (order) → New Order → Track → Pay → History
2. Driver: Login → Today's Manifest → Navigate → Scan Cylinder → Photo Proof → Complete → Next
3. Distributor: Login → Dashboard → Approve Orders → View Tracking → Stock Alert → Reports

### Screen Flow
Customer orders → Distributor approves → Driver assigned → Driver picks up cylinders → Delivers to customer → Picks up empty → Returns to warehouse → Payment collected

## 8. Business Model
- **Pricing tiers:** Basic $49/month (up to 200 cylinders), Professional $99/month (up to 1,000 cylinders), Enterprise $199/month (unlimited)
- **Free trial:** 14-day free trial, limited to 50 cylinders
- **Target MRR per client:** $49-$199
- **Additional revenue:** SMS notifications $0.03/message, barcode stickers $1.50/cylinder, driver app $9/month per driver, payment gateway commission 1.5%

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2):** Laravel API - Auth, Cylinders, Customers, Orders CRUD
- **Phase 2 (Weeks 3-4):** React Dashboard - Stock management, order pipeline, customer management, reports
- **Phase 3 (Weeks 5-6):** Flutter Apps (Customer + Driver) - Ordering, barcode scanning, delivery flow, payments
- **Phase 4 (Weeks 7-8):** Route optimization, payment gateway, SMS notifications, inspection module, testing, deploy

## 10. Risk & Mitigation
- **Technical risks:** Barcode scanning in sunlight/dust conditions → Mitigation: high-contrast QR codes, durable sticker material
- **Safety compliance:** Cylinder inspection tracking liability → Mitigation: mandatory inspection reminders, expiry alerts, compliance reports
- **Cash dependency:** Many customers prefer cash → Mitigation: support cash on delivery + digital payment option, incentivize digital
- **Driver adoption:** Drivers may not use smartphone → Mitigation: simple UI with large buttons, voice navigation, offline mode for deliveries
- **Competition:** Gas companies building own apps → Mitigation: focus on independent distributors, multi-company support, affordable pricing
