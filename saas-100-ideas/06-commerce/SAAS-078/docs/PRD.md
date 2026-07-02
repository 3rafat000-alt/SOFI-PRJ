# PRD: ButcherPro (SAAS-078)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** نظام إدارة متكامل لمحلات القصاب واللحوم: طلبات العملاء، إدارة مخزون اللحوم، توصيل الطلبات، حسابات الموردين
- **Problem:** محلات الجزارة تعمل بنظام تقليدي، صعوبة تتبع أوزان اللحوم وأنواعها، طلبات الهاتف المربكة، إهدار اللحوم بسبب عدم إدارة المخزون
- **Solution:** Laravel API + React Dashboard (shop owner) + Flutter App (customers + delivery)

## 2. Market & Opportunity
- **Target market:** 500K+ butcher shops in MENA; $30B regional meat market; 60%+ still operate with paper receipts
- **Customer segment:** B2B (butcher shops, meat distributors, halal meat suppliers)
- **Competitors:** POS systems (Square, SumUp), general inventory software (Zoho, Odoo), WhatsApp ordering
- **Differentiation:** Niche butcher-specific features (cuts management, weight-based pricing, halal certification tracking, batch/lot tracking for meat traceability), Arabic-first

## 3. User Personas

### الجزار — منصور (Primary)
- **Role:** صاحب محل جزارة في حي سكني
- **Goals:** إدارة الطلبات، تتبع المبيعات اليومية، تقليل هدر اللحوم، معرفة الموردين
- **Pain points:** الزبائن يطلبون بالتلفون وينسى الطلبات، لا يعرف كمية اللحم المتبقية بالضبط، صعوبة تسعير القطع المختلفة

### الزبونة — هدى (Secondary)
- **Role:** ربة منزل تطلب لحماً أسبوعياً لعائلتها
- **Goals:** طلب لحمة بمواصفات محددة، توصيل للمنزل، دفع أونلاين
- **Pain points:** تضطر تتصل كل مرة، الأوزان غير دقيقة، توصيل متأخر

### المورد — عبدالعزيز (Tertiary)
- **Role:** مورد لحوم بالجملة
- **Goals:** إدارة طلبات المحلات، معرفة الأسعار التنافسية، تتبع الدفعات
- **Pain points:** تحصيل الديون صعب، تواصل غير منظم

### Admin — Dashboard Operator
- **Role:** مدير المنصة يراقب المحلات المشتركة، الرسوم، الدعم

## 4. Features by Platform

### Laravel API (Backend)
- Product catalog (meat cuts: lamb, beef, chicken, camel with weight-based pricing)
- Inventory tracking (whole carcass → cuts, batch numbers, expiry tracking)
- Order management (phone, app, walk-in)
- Weight integration (digital scale API for accurate billing)
- Delivery management (routing, driver assignment, status tracking)
- Supplier management & accounts
- Halal certification tracking & batch traceability
- Payment processing (COD, card, wallet)

### React Dashboard (Web)
- Menu/Product management (cuts, prices by weight, special offers)
- Order dashboard (incoming, preparing, ready, delivered)
- Meat inventory (whole animal breakdown, cut yield tracking)
- Supplier management & purchase history
- Delivery zone configuration
- Customer management & order history
- Financial reports (daily sales, cost of goods, profit margins)
- Staff management (butchers, delivery drivers)

### Flutter App (Mobile)
- **Customer App:** Browse meat cuts, Place orders (weight + cut preference), Schedule delivery, Track order in real-time, Pay online, Reorder favorites, Rate quality
- **Shop App:** Receive orders (sound alert), Process walk-in POS, Weigh items with scale integration, Manage delivery queue, View daily sales

## 5. Data Model (MVP)
- **Shop:** id, name, address, license, halal_certification, delivery_zones (JSON)
- **Product:** id, shop_id, name (e.g. "لحم غنم مفروم"), category (lamb/beef/chicken/camel), cut_type, price_per_kg, stock_kg, min_order_kg
- **AnimalBatch:** id, supplier_id, animal_type, whole_weight_kg, cost_per_kg, received_date, halal_cert_ref, expiry_date
- **Order:** id, shop_id, customer_id, items (JSON with product, weight, price), subtotal, delivery_fee, total, status, payment_method, delivery_address, scheduled_time, notes
- **OrderItem:** id, order_id, product_id, weight_kg, unit_price, total_price, cut_instructions
- **Supplier:** id, shop_id, name, contact, payment_terms, balance
- **Customer:** id, shop_id, name, phone, address, favorite_orders (JSON), total_orders
- **Delivery:** id, order_id, driver_id, status, picked_up_at, delivered_at, distance_km

## 6. API Endpoints (MVP)
- `GET /api/products` — List available meat cuts (filter: category)
- `POST /api/orders` — Place order (with items & delivery)
- `GET /api/orders` — My orders (customer) / Incoming orders (shop)
- `PATCH /api/orders/{id}/status` — Update order (preparing/ready/delivered)
- `POST /api/orders/{id}/weight` — Update actual weight + price
- `GET /api/inventory` — Current stock levels (by cut)
- `POST /api/inventory/batch` — Record incoming meat batch
- `POST /api/customers` — Register customer
- `GET /api/suppliers` — Supplier list
- `POST /api/suppliers/payment` — Record supplier payment
- `GET /api/reports/daily` — Daily sales + COGS report

## 7. User Interface (Screen List)
- **Dashboard screens:** Today's orders, Inventory summary, Supplier list, Delivery board, Sales report
- **Mobile (Customer):** Home (featured cuts), Category (lamb/beef/chicken), Product detail (select weight), Cart, Checkout, Track order, Favorites, History
- **Mobile (Shop):** Orders queue, Take order (POS), Inventory, Scale integration, Delivery dispatch, Daily summary
- **Flow (Customer):** Browse → Select cut+weight → Cart → Schedule delivery → Pay → Track → Receive → Rate
- **Flow (Shop):** Login → New Order Alert → Prepare → Weigh → Confirm Weight → Dispatch Delivery → Done

## 8. Business Model
- **Pricing:** Per shop: Starter ($29/mo, 100 orders/mo, 1 staff), Pro ($59/mo, 500 orders, 3 staff), Premium ($119/mo, unlimited, delivery module)
- **Free trial:** 14-day free trial
- **Target MRR per shop:** $29–$119
- **Additional:** Delivery tracking $19/mo, Customer app white label $49/mo, Digital scale API integration $9/mo

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2):** API — Product catalog (meat cuts), Order management, Inventory tracking, Basic supplier
- **Phase 2 (Weeks 3-4):** React Dashboard — Order dashboard, Inventory management, Supplier accounts, Daily reports
- **Phase 3 (Weeks 5-6):** Flutter Apps — Customer app (browse, order, track), Shop app (orders, POS, delivery)
- **Phase 4 (Weeks 7-8):** Scale hardware integration, Delivery tracking, Payment gateway, Halal certification module, QA

## 10. Risk & Mitigation
- **Perishability risk:** Meat spoils fast if inventory mismanaged → Expiry alerts, FIFO reporting, daily stock recommendation
- **Weight accuracy risk:** Disputes over weight → Scale API ensures accurate billing, customer can see weight on receipt
- **Seasonal demand:** Ramadan + Eid spikes → Demand prediction, pre-order scheduling, capacity alerts
- **Hygiene compliance:** Health inspections → Built-in HACCP checklist, temperature log for cold storage
- **Cash dependency:** Many butcher customers pay cash → Support COD, card, wallets; cash reconciliation tool
