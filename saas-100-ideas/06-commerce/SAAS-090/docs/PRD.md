# PRD: FurniturePro (SAAS-090)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** منصة شاملة لمعارض الأثاث تتيح إنشاء معرض افتراضي ثلاثي الأبعاد، إدارة الطلبات، وتنسيق التوصيل والتركيب.
- **Problem statement:** تعاني معارض الأثاث من محدودية ساعات العمل والموقع الفعلي، صعوبة عرض كافة المنتجات في المساحة المتاحة، عدم قدرة العملاء على تخيل القطع في منازلهم، وتأخير في التوصيل والتركيب.
- **Proposed solution:** Laravel API + React Dashboard + Flutter App

## 2. Market & Opportunity
- **Target market:** معارض الأثاث (متوسطة وكبيرة)، موزعو الأثاث، مصممو الديكور الداخلي. سوق الأثاث في العالم العربي يتجاوز 40 مليار دولار.
- **Customer segment:** B2B (furniture retailers & distributors) + B2C (end customers)
- **Competitor landscape:**
  1. **IKEA Place** - تطبيق AR لكنه خاص بايكيا فقط
  2. **Houzz** - منصة تصميم داخلي، لا تدير مبيعات الأثاث
  3. **Wayfair** - متجر إلكتروني، ليس نظام إدارة للمعارض
  4. **أنظمة محلية** - ERP عامة بدون ميزات المعرض الافتراضي
- **Differentiation:** حل متكامل يجمع إدارة المعرض مع معرض افتراضي (AR للجوال) وإدارة سلسلة التوصيل والتركيب، مصمم خصيصاً لسوق الأثاث العربي.

## 3. User Personas

### الشخصية الأساسية: صاحب معرض أثاث - عادل
- **الدور:** يمتلك معرض أثاث كبير بمساحة 1000 متر مربع
- **الأهداف:** عرض أكبر قدر من المنتجات، زيادة المبيعات عبر الإنترنت، تحسين تجربة العميل، إدارة التوصيل والتركيب
- **نقاط الألم:** المساحة محدودة لعرض كل القطع، العملاء يزورون ولا يشترون، التوصيل متأخر والتركيب غير منظم

### الشخصية الثانوية: عميل أثاث - سارة
- **الدور:** عائلة تبحث عن أثاث لمنزل جديد
- **الأهداف:** تصفح الأثاث من المنزل، معرفة كيف سيبدو في منزلها، مقارنة الأسعار، توصيل وتركيب سريع
- **نقاط الألم:** لا يمكنها زيارة كل المعارض، لا تتخيل القطعة في منزلها، تنتظر أسابيع للتوصيل

### الشخصية الثالثة: منسق توصيل - رامي
- **الدور:** مسؤول التوصيل والتركيب في المعرض
- **الأهداف:** جدولة التوصيلات، تتبع فنيي التركيب، إدارة المخزون في المستودع
- **نقاط الألم:** صعوبة التنسيق بين المبيعات والتوصيل، العملاء يغيرون مواعيدهم، قطع تصل تالفة

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Showroom, Product, ProductCategory, ProductVariant, Customer, Order, OrderItem, Delivery, Installation, Inventory, ARModel
- RESTful endpoints
- Auth & roles: ShowroomOwner, SalesRep, DeliveryManager, Installer, Customer
- Product catalog with variants (color, size, material, finish)
- 3D model and AR asset management
- Order lifecycle (quote → confirmed → payment → production → delivery → installation → completed)
- Inventory management (showroom + warehouse)
- Delivery scheduling with route optimization
- Installation crew management and task assignment
- Customer communication (WhatsApp, email, SMS)
- Warranty and after-sales tracking
- Commission calculation for sales team

### React Dashboard (Web)
- Product catalog with bulk import/export
- Product variants management (color, size, fabric, finish options)
- Interactive showroom layout editor (virtual floor plan)
- Order pipeline management
- Inventory dashboard (showroom stock vs warehouse)
- Delivery scheduling calendar
- Installation crew assignment and tracking
- Customer relationship management
- AR model upload and tagging
- Reports: sales by category, best sellers, delivery performance, installer productivity

### Flutter App (Mobile)
- Customer app: browse catalog, AR viewer (camera overlay), room planner, order tracking, delivery tracking, after-sales requests
- Sales rep app: customer management, quick quote generation, product catalog offline, commission tracking
- Delivery app: delivery manifest, navigation, proof of delivery with photo, damage reporting
- Installer app: task list, customer communication, completion confirmation with photo
- AR Room Planner: point camera at room → select furniture → see how it fits
- Push notifications: order confirmation, delivery scheduling, installation reminders

## 5. Data Model (MVP)

- **User:** id, name, email, phone, role, showroom_id
- **Showroom:** id, name, address, lat, lng, area_sqm, phone, opening_hours, settings
- **Product:** id, name, category (sofa/bed/table/chair/cabinet/mattress), description, base_price, brand, material, dimensions, weight, assembly_required, ar_model_url, image
- **ProductVariant:** id, product_id, color, size, fabric_type, finish, price_modifier, stock_quantity, sku, image
- **ProductCategory:** id, name, parent_id, sort_order
- **Customer:** id, name, phone, email, address, lat, lng, customer_type, created_at
- **Order:** id, order_number, customer_id, showroom_id, sales_rep_id, items, subtotal, discount, tax, delivery_fee, total, status, payment_status, notes, created_at
- **OrderItem:** id, order_id, product_variant_id, quantity, unit_price, subtotal
- **Delivery:** id, order_id, driver_id, scheduled_date, status, tracking_link, delivered_at, proof_photos
- **Installation:** id, order_id, installer_id, scheduled_date, assembly_items, status, completed_at, notes
- **Inventory:** id, product_variant_id, location (showroom/warehouse), quantity, min_stock
- **ARModel:** id, product_id, model_url, model_format, scale, created_at

## 6. API Endpoints (MVP)

- `POST /api/auth/login` - Login
- `GET /api/products` - Product catalog (filtered by category, price)
- `GET /api/products/{id}` - Product detail with variants
- `GET /api/products/{id}/ar-model` - Download AR model
- `GET /api/categories` - Category tree
- `GET /api/customers` - Customer list
- `POST /api/customers` - Register customer
- `GET /api/orders` - Order list (role-based)
- `POST /api/orders` - Create order
- `GET /api/orders/{id}` - Order detail with timeline
- `PUT /api/orders/{id}/status` - Update order status
- `POST /api/orders/{id}/quote` - Generate PDF quote
- `GET /api/deliveries` - Delivery schedule
- `POST /api/deliveries` - Schedule delivery
- `PUT /api/deliveries/{id}/status` - Update delivery (proof)
- `GET /api/installations` - Installation tasks
- `PUT /api/installations/{id}/status` - Mark installation complete
- `GET /api/inventory` - Stock levels
- `POST /api/payments` - Record payment
- `GET /api/reports/sales` - Sales report
- `GET /api/reports/inventory` - Inventory report

## 7. User Interface (Screen List)

### Dashboard Screens (React)
1. Login
2. Showroom Dashboard - today's sales, pending deliveries, new leads
3. Product Catalog - grid/table with variants, AR model upload
4. Virtual Showroom - floor plan editor, product placement
5. Order Management - pipeline: quotes → confirmed → production → delivery → installed
6. Delivery Calendar - schedule view with driver assignment
7. Installation Board - crew tasks by day
8. Inventory - showroom + warehouse stock
9. Customer Management - profiles, purchase history, preferences
10. Reports - sales trends, top products, delivery performance, installer KPIs

### Mobile Screens (Flutter)
1. Customer: Splash → Browse Catalog → AR View Room → Select Variant → Cart → Checkout → Track → Delivery → Installation → Review
2. Sales Rep: Home → Customer Search → Quick Quote → Product Catalog (offline) → Order History → Commission
3. Delivery Driver: Today's Schedule → Navigation → Proof of Delivery (photo) → Damage Report → Complete
4. Installer: Tasks Today → Customer Details → Assembly Confirm → Photo Proof → Complete

### Screen Flow
Customer browses online → Uses AR to preview → Requests quote / Orders → Sales confirms → Production/Procurement → Delivery scheduled → Delivered → Installation appointment → Installation completed → After-sales follow-up

## 8. Business Model
- **Pricing tiers:** Basic $99/month (500 products, 50 orders/month), Professional $199/month (2,000 products, unlimited orders), Enterprise $399/month (multi-showroom, AR module)
- **Free trial:** 14-day free trial, limited to 100 products
- **Target MRR per client:** $99-$399
- **Additional revenue:** AR model creation service $99/model, premium product placement $19/month, delivery tracking white-label $49/month, installer app $15/month per installer

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2):** Laravel API - Auth, Products, Variants, Categories, Customers CRUD
- **Phase 2 (Weeks 3-4):** React Dashboard - Product catalog, order management, inventory, delivery scheduling
- **Phase 3 (Weeks 5-6):** Flutter Apps - Customer (catalog + AR viewer), Sales Rep, Delivery/Installer apps
- **Phase 4 (Weeks 7-8):** AR model pipeline, payment integration, reporting, after-sales module, testing, deploy

## 10. Risk & Mitigation
- **Technical risks:** AR model performance on mid-range phones → Mitigation: compressed GLB format, progressive loading, fallback to 360° images
- **Product dimensions:** Customer returns due to size mismatch → Mitigation: AR scale calibration guide, room measurement tool, clear dimension labeling
- **Delivery complexity:** Furniture delivery requires white-glove service → Mitigation: dedicated delivery/installer apps, damage reporting protocol, customer communication
- **Adoption:** Furniture showroom owners reluctant to digitize → Mitigation: showroom digitization service, before/after sales impact case studies
- **Inventory accuracy:** Showroom pieces vs warehouse stock confusion → Mitigation: clear location tracking, display-model flag, real-time sync
