# PRD: SouqSync (SAAS-051)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary

- **One-liner:** منصة تربط التجار المحليين بأسواق الجملة لتسهيل إدارة الطلبات والمخزون والتوصيل — B2B SaaS تربط محال التجزئة الصغيرة بتجار الجملة عبر واجهة رقمية موحدة.
- **Problem:** التجار المحليون والمحال الصغيرة يعانون من صعوبة التواصل مع موردي الجملة، تتبع الطلبات يدوياً، إدارة المخزون عبر جداول إكسل، وعدم وجود رؤية واضحة لحالة التوصيل. الموردون بدورهم يفتقرون إلى منصة رقمية لعرض المخزون واستقبال الطلبات.
- **Proposed solution:** Laravel API (إدارة المخزون والطلبات والمستخدمين) + React Dashboard (لوحة تحكم للتجار والموردين) + Flutter App (تطبيق جوال للتتبع والتواصل).

## 2. Market & Opportunity

- **Target market:** سوق التجارة بالتجزئة والجملة في العالم العربي، يقدر بمئات الآلاف من المحال التجارية الصغيرة التي تعتمد على التوريد من أسواق الجملة.
- **Customer segment:** B2B — تجار جملة (suppliers)، محال تجارية (retailers)، موزعون.
- **Competitor landscape:**
  1. سلة (Salla) — منصة متاجر إلكترونية لكن لا تركز على ربط التجزئة بالجملة.
  2. زد (Zid) — منصة متاجر إلكترونية، تركيز على B2C.
  3. متجر (Mutejer) — حلول متاجر إلكترونية سعودية.
  4. TradeGecko — منصة عالمية لإدارة المخزون لكن بدون دعم عربي كافٍ.
  5. OpenCart / WooCommerce — حلول مفتوحة المصدر تتطلب تكاملاً يدوياً.
- **Differentiation:** تركيز حصري على ربط التجزئة بالجملة (B2B wholesale-retail bridge)، دعم كامل للغة العربية، واجهة جوال بسيطة، تكامل مع خدمات التوصيل المحلية، نموذج تسعير يناسب السوق العربي.

## 3. User Personas

### أساسي: تاجر الجملة — أبو عبدالله
- **الدور:** مالك مؤسسة توزيع مواد غذائية
- **الأهداف:** عرض المخزون على التجار، استقبال طلبات الجملة إلكترونياً، إدارة حسابات العملاء، تتبع المدفوعات
- **نقاط الألم:** الاتصالات الهاتفية المتكررة، أخطاء في تسجيل الطلبات، صعوبة متابعة الحسابات المدينة، عدم معرفة حالة المخزون الفعلية

### أساسي: صاحب المحل التجاري — سارة
- **الدور:** مالكة محل بقالة
- **الأهداف:** طلب البضائع من الموردين بسهولة، تتبع حالة الطلب، معرفة توفر المخزون، إدارة المدفوعات
- **نقاط الألم:** قضاء وقت طويل على الهاتف للطلب، عدم معرفة مدى توفر المنتج، تأخير التوصيل غير متوقع، صعوبة مقارنة أسعار الموردين

### إداري: مشغل النظام — المدير
- **الدور:** مسؤول المنصة
- **الأهداف:** مراقبة المعاملات، إدارة المستخدمين، إعداد تقارير الأداء
- **نقاط الألم:** الحاجة إلى رؤية شاملة للعمليات، ضبط صلاحيات المستخدمين

## 4. Features by Platform

### Laravel API (Backend)

- User & role management (Admin, Supplier, Retailer)
- Product catalog management per supplier
- Inventory & stock levels with low-stock alerts
- Order management (create, approve, ship, deliver)
- Payment tracking (cash, bank transfer, card)
- Delivery integration (optional API with local couriers)
- Notification engine (push, email, SMS via provider)
- Report generation (sales, inventory, orders)
- RESTful endpoints with Laravel Sanctum auth

### React Dashboard (Web)

- Supplier panel: product management, order inbox, inventory dashboard, payment tracking
- Retailer panel: order creation, supplier browsing, order history, payment ledger
- Admin panel: user management, platform analytics, commission settings, dispute resolution
- Dashboard widgets: daily orders, top suppliers, pending payments
- Arabic-first UI with RTL support

### Flutter App (Mobile)

- Retailer app: browse suppliers, place orders, track deliveries, receive notifications
- Supplier app: manage inventory, confirm orders, update delivery status
- Real-time order status updates via WebSocket
- Offline order draft creation (sync when online)
- Push notifications for order status changes
- Barcode scanning for inventory (future phase)
- Arabic-first UI with Material 3

## 5. Data Model (MVP)

- **User:** id, name, email, phone, role (admin/supplier/retailer), password, avatar, status, created_at
- **SupplierProfile:** id, user_id, business_name, commercial_registration, tax_number, address, city, phone, logo, status
- **RetailerProfile:** id, user_id, store_name, address, city, phone, license_number, status
- **Category:** id, name_ar, name_en, icon, parent_id, sort_order
- **Product:** id, supplier_id, category_id, name_ar, name_en, sku, barcode, unit, price, stock_qty, min_order_qty, images, status, is_active
- **Order:** id, order_number, retailer_id, supplier_id, status (pending/confirmed/shipped/delivered/cancelled), subtotal, delivery_fee, total, notes, created_at
- **OrderItem:** id, order_id, product_id, quantity, unit_price, total
- **Payment:** id, order_id, amount, method (cash/bank_transfer/card), status, paid_at, notes
- **Delivery:** id, order_id, courier_name, tracking_number, status, estimated_date, delivered_at
- **Notification:** id, user_id, title, body, type, is_read, created_at
- **Setting:** id, key, value, group

## 6. API Endpoints (MVP)

- `POST /api/register` — User registration
- `POST /api/login` — Authentication
- `POST /api/logout` — Logout
- `GET /api/user` — Current user profile
- `PUT /api/user` — Update profile
- `GET /api/suppliers` — List suppliers
- `GET /api/suppliers/{id}` — Supplier details
- `GET /api/products` — List products (filter by supplier, category)
- `POST /api/products` — Create product (supplier)
- `PUT /api/products/{id}` — Update product (supplier)
- `DELETE /api/products/{id}` — Delete product (supplier)
- `GET /api/categories` — List categories
- `GET /api/orders` — List orders (filter by role)
- `POST /api/orders` — Create order (retailer)
- `GET /api/orders/{id}` — Order details
- `PUT /api/orders/{id}/status` — Update order status
- `DELETE /api/orders/{id}` — Cancel order
- `GET /api/payments` — List payments
- `POST /api/payments` — Record payment
- `GET /api/deliveries` — List deliveries
- `PUT /api/deliveries/{id}` — Update delivery
- `GET /api/notifications` — List notifications
- `PUT /api/notifications/{id}/read` — Mark as read
- `POST /api/notifications/read-all` — Mark all as read
- `GET /api/reports/orders` — Orders report
- `GET /api/reports/sales` — Sales report
- `GET /api/reports/inventory` — Inventory report

## 7. User Interface (Screen List)

### Dashboard Screens (React)
- Login / Register
- Supplier Dashboard: stats, recent orders, low stock alerts
- Supplier Products: CRUD table with search/filter
- Supplier Orders: incoming orders table, status management
- Supplier Inventory: stock levels, movement log
- Retailer Dashboard: recent orders, favorite suppliers
- Retailer Browse: supplier catalog with product grid
- Retailer Cart: order summary, checkout
- Retailer Orders: order history with status tracking
- Admin Dashboard: platform KPIs, user management
- Admin Users: user table, role management, activate/deactivate
- Admin Settings: commission rates, payment methods
- Notifications: notification list with read/unread
- Reports: charts and exportable tables

### Mobile Screens (Flutter)
- Splash & Onboarding (3 slides: Arabic intro)
- Login / Register with phone/email
- Home: supplier recommendations, recent orders, quick actions
- Browse Suppliers: searchable list with categories
- Supplier Store: products grid, filter, add to cart
- Cart: quantity adjust, notes, place order
- Orders: status timeline, reorder action
- Order Detail: items, status, delivery tracking
- Payments: payment history, outstanding balance
- Profile: edit store info, settings
- Notifications: native push notification list

### Screen Flow
```
Splash → Login → Home (Dashboard)
  → Browse Suppliers → Select Supplier → View Products → Add to Cart → Cart → Checkout → Order Confirmed
  → Orders → Order Detail → Track Delivery
  → Profile → Settings
  → Notifications
```

## 8. Business Model

- **Pricing tiers:**
  - مجاني (Free): تاجر واحد، 50 منتج، 10 طلبات/شهر
  - باقة البداية $19/شهر: تاجر، 500 منتج، طلبات غير محدودة
  - باقة النمو $49/شهر: 3 مستخدمين، 2000 منتج، تقارير متقدمة
  - باقة المؤسسة $99/شهر: مستخدمين غير محدودين، دعم API مخصص
- **Free trial:** 14 يوم تجربة مجانية على الباقات المدفوعة
- **Target MRR per client:** $19-$99
- **Additional revenue:** نسبة عمولة 1-2% على قيمة الطلبات (اختياري)، الإعلانات المميزة للموردين

## 9. Implementation Plan

- **Phase 1 (Weeks 1-2):** Laravel API scaffold + User auth + User roles + Category & Product CRUD + Supplier & Retailer profiles
- **Phase 2 (Weeks 3-4):** Order lifecycle + Payment tracking + Delivery integration stub + Notification engine + API documentation
- **Phase 3 (Weeks 5-6):** React Dashboard — authentication, supplier/retailer/admin panels, dashboard widgets, order management UI, report views
- **Phase 4 (Weeks 7-8):** Flutter App — auth flow, browse & order flow, order tracking, notification handling, offline draft capability, push notifications
- **Phase 5 (Weeks 9-10):** Integration testing, bug fixes, performance tuning, Arabic QA, deployment (Laravel Forge / Vapor), app store submission

## 10. Risk & Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| ضعف تبني التجار للتقنية | High | تصميم واجهة فائقة البساطة، دعم فني عبر واتساب، تدريب مجاني |
| منافسة من منصات المتاجر الإلكترونية | Medium | تركيز على B2B wholesale-retail وليس B2C، بناء مجتمع تجار |
| تعقيد تكامل التوصيل | Medium | البدء بتكامل بسيط مع شركة توصيل واحدة، واجهة API عامة للتوسع |
| مشاكل الدفع الإلكتروني | Medium | دعم الدفع عند الاستلام كخيار أساسي، التكامل مع منصات دفع عربية |
| جودة البيانات من الموردين | Medium | آلية مراجعة وتقييم، moderation على المنتجات المضافة |
