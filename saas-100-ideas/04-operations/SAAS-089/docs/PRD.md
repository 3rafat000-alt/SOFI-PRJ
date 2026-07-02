# PRD: SpareParts (SAAS-089)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** منصة متكاملة لإدارة محلات قطع غيار السيارات تغطي المخزون، الموردين، الطلبات، وفواتير العملاء.
- **Problem statement:** يعاني تجار قطع غيار السيارات من فوضى المخزون (آلاف القطع المختلفة حسب الموديل والسنة)، صعوبة تحديد القطعة المناسبة للعميل، تكرار شراء قطع موجودة، وضعف إدارة الموردين وأوامر الشراء.
- **Proposed solution:** Laravel API + React Dashboard + Flutter App

## 2. Market & Opportunity
- **Target market:** محلات قطع غيار السيارات، ورش السيارات، موزعو قطع الغيار بالجملة. سوق قطع الغيار في العالم العربي يتجاوز 30 مليار دولار.
- **Customer segment:** B2B (auto parts retailers & wholesalers)
- **Competitor landscape:**
  1. **AutoZone** - سلسلة عالمية لكن بدون نظام SaaS للمحلات المستقلة
  2. **PartsTech** - منصة أمريكية للبحث عن القطع، لا تدير المخزون
  3. **ShopWorx** - نظام إدارة ورش، يغطي قطع الغيار بشكل ثانوي
  4. **Excel/Wave** - أنظمة محاسبة عامة بدون تخصص بقطع الغيار
- **Differentiation:** حل متخصص في قطع غيار السيارات مع قاعدة بيانات لأنواع القطع حسب الموديل والسنة، نظام بحث ذكي، وربط مع موردين متعددين.

## 3. User Personas

### الشخصية الأساسية: صاحب محل قطع غيار - منصور
- **الدور:** يمتلك محلاً لقطع غيار السيارات اليابانية والأمريكية
- **الأهداف:** تنظيم المخزون حسب الموديل وسنة الصنع، تسريع البحث عن القطعة، تحسين إدارة المشتريات
- **نقاط الألم:** آلاف القطع بدون تنظيم، يبيع قطعة ولا يعرف هل لديه بديل، يشتري قطع لا يحتاجها

### الشخصية الثانوية: فني ورشة - بدر
- **الدور:** ميكانيكي يحتاج قطع غيار لسيارات العملاء
- **الأهداف:** البحث عن القطعة المناسبة بسرعة، معرفة التوفر والسعر، طلب التوصيل للورشة
- **نقاط الألم:** يقضي وقتاً طويلاً في البحث، القطع تأتي غير مناسبة أحياناً، أسعار غير واضحة

### الشخصية الثالثة: ممثل مبيعات - سعيد
- **الدور:** يعمل كاشير ويساعد الزبائن في المحل
- **الأهداف:** إيجاد القطعة بسرعة، إصدار فاتورة، إدارة طلبات الواتساب
- **نقاط الألم:** بطء البحث في الكتالوج الورقي، سهولة الخطأ في رقم القطعة، صعوبة متابعة طلبات الواتساب

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Part, PartCompatibility, Category, Supplier, PurchaseOrder, PurchaseOrderItem, Customer, SaleOrder, SaleOrderItem, InventoryMovement
- RESTful endpoints
- Auth & roles: ShopOwner, SalesStaff, WholesaleCustomer, Supplier
- Part catalog with vehicle compatibility (make, model, year, engine)
- Barcode/QR code system for parts
- Inventory management with multi-warehouse support
- Smart search by part number, vehicle, or description
- Supplier management with price comparison
- Purchase order generation (auto-suggest reorder)
- Customer management with vehicle profiles
- Sales pipeline (retail + wholesale + workshop)
- Integration with vehicle parts databases (TecDoc, etc.)

### React Dashboard (Web)
- Inventory dashboard: stock value, low stock alerts, top sellers
- Parts catalog management with vehicle compatibility matrix
- Bulk inventory import/export
- Purchase orders: create, approve, receive
- Supplier price comparison
- Sales dashboard: daily sales, customer segments
- Customer management with vehicle profiles
- Workshop management: recurring orders, credit tracking
- Barcode label printing
- Reports: inventory turnover, profit margins, sales by brand

### Flutter App (Mobile)
- Part finder: search by vehicle (make/model/year) or part number
- Barcode scanner for instant part lookup
- Stock check with real-time availability
- Order placement (retail/wholesale)
- Customer app: browse parts, request quote, track orders
- Delivery tracking
- Push notifications for stock availability
- Offline catalog browsing

## 5. Data Model (MVP)

- **User:** id, name, email, phone, role, shop_id
- **Shop:** id, name, address, specialization (brands), vat_number, settings
- **Part:** id, part_number, name, description, category_id, brand, unit_price, cost_price, stock_quantity, reorder_level, location_in_shop, barcode, image
- **PartCompatibility:** id, part_id, make, model, year_start, year_end, engine, notes
- **Category:** id, name, parent_id (brakes, engine, suspension, electrical, body)
- **Supplier:** id, name, contact_person, phone, email, lead_time_days, payment_terms
- **PurchaseOrder:** id, supplier_id, order_number, status, total_amount, expected_date, notes, created_at
- **PurchaseOrderItem:** id, purchase_order_id, part_id, quantity, unit_cost, received_quantity
- **Customer:** id, name, phone, email, address, type (retail/wholesale/workshop), credit_limit, vehicle_info
- **SaleOrder:** id, customer_id, items, total_amount, discount, tax, status, payment_status, created_at
- **SaleOrderItem:** id, sale_order_id, part_id, quantity, unit_price
- **InventoryMovement:** id, part_id, type (in/out/adjustment), quantity, reference_type, reference_id, notes, moved_by, moved_at

## 6. API Endpoints (MVP)

- `POST /api/auth/login` - Login
- `GET /api/dashboard` - Shop dashboard stats
- `GET /api/parts` - Part list with filters (category, vehicle)
- `POST /api/parts` - Add part
- `PUT /api/parts/{id}/stock` - Update stock
- `GET /api/parts/search?q=...` - Smart search
- `GET /api/parts/{id}/compatibility` - Vehicle compatibility
- `GET /api/categories` - Category tree
- `GET /api/suppliers` - Supplier list
- `POST /api/purchase-orders` - Create PO
- `GET /api/purchase-orders` - PO list
- `PUT /api/purchase-orders/{id}/receive` - Receive stock
- `GET /api/customers` - Customer list
- `POST /api/sale-orders` - Create sale
- `GET /api/sale-orders` - Sale history
- `GET /api/inventory/movements` - Movement log
- `POST /api/inventory/adjust` - Stock adjustment
- `GET /api/reports/sales` - Sales report
- `GET /api/reports/inventory` - Inventory report
- `GET /api/reports/suppliers` - Supplier performance

## 7. User Interface (Screen List)

### Dashboard Screens (React)
1. Login
2. Shop Dashboard - stock value, today's sales, low stock alerts
3. Parts Catalog - table with filters, add/edit, bulk import
4. Vehicle Compatibility - matrix editor per part
5. Inventory - stock levels, movements, adjustments
6. Purchase Orders - PO pipeline, receive stock
7. Suppliers - list, pricing, lead times, performance
8. Sales - POS-like interface, quotes, invoices
9. Customers - list, purchase history, vehicle profiles
10. Reports - sales, inventory turnover, profit margin by part

### Mobile Screens (Flutter)
1. Home - quick search bar, recent sales, low stock
2. Part Search - by vehicle (select make/model/year) → compatible parts
3. Barcode Scanner - scan → see part details, stock, price
4. Customer App: Browse → Add to Cart → Order → Track Delivery
5. Sales: scan parts → build invoice → take payment
6. Stock Check: scan → show location in shop, quantity
7. Notifications: low stock, PO received, customer inquiries

### Screen Flow
Customer requests part → Search by vehicle or part number → Check stock → If available: sell → Update stock → If not: create PO → Supplier delivers → Receive stock → Sell

## 8. Business Model
- **Pricing tiers:** Basic $59/month (up to 1,000 parts), Professional $129/month (up to 10,000 parts), Enterprise $249/month (unlimited)
- **Free trial:** 14-day free trial, limited to 200 parts
- **Target MRR per client:** $59-$249
- **Additional revenue:** TecDoc integration add-on $49/month, barcode label printer kit $199, SMS notifications $0.03/message, wholesale customer portal $29/month

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2):** Laravel API - Auth, Parts, Categories, Compatibility CRUD + search engine
- **Phase 2 (Weeks 3-4):** React Dashboard - Parts catalog, inventory, purchase orders, sales interface
- **Phase 3 (Weeks 5-6):** Flutter App - Part finder, barcode scanner, customer ordering, stock check
- **Phase 4 (Weeks 7-8):** Supplier portal, vehicle database integration, reporting, testing, deploy

## 10. Risk & Mitigation
- **Technical risks:** Vehicle compatibility data complexity → Mitigation: start with manual compatibility assignment, integrate TecDoc API later
- **Part numbering:** Multiple numbering systems (OEM, aftermarket, cross-reference) → Mitigation: flexible part number field, cross-reference table
- **Inventory accuracy:** Physical stock vs system always a challenge → Mitigation: barcode scanning mandatory for in/out, cycle counting module
- **Adoption:** Shop owners loyal to paper + Excel → Mitigation: start with inventory only, add sales gradually, demonstrate ROI
- **Competition:** Large auto parts distributors building own portals → Mitigation: target independent shops, multi-supplier support, affordable pricing
