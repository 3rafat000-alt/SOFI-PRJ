# PRD: PharmaChain (SAAS-066)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner**: نظام إدارة سلسلة توريد الأدوية — تتبع المخزون، تواريخ الصلاحية، التوزيع على الصيدليات، وإدارة الطلبات.
- **Problem statement**: موزعو الأدوية يعانون من هدر الأدوية بسبب انتهاء الصلاحية، صعوبة تتبع الشحنات، ضعف التواصل مع الصيدليات، ومشاكل في إدارة المخزون بين المستودعات.
- **Proposed solution**: Laravel API + React Dashboard + Flutter App — إدارة المخزون، تتبع الشحنات، تنبيهات الصلاحية، طلبات الصيدليات الإلكترونية.

## 2. Market & Opportunity
- **Target market size**: سوق توريد الأدوية ~$1.5T عالمياً، الشرق الأوسط ~$40B (نمو 8% CAGR).
- **Customer segment**: B2B — موزعو أدوية (جملة)، صيدليات مستقلة، سلاسل صيدليات صغيرة.
- **Competitor landscape**:
  1. **Cerner/Epic**: أنظمة مستشفيات عملاقة، غير مناسبة للتوزيع.
  2. **McKesson**: أمريكي، $500M+ شركة، لا يخدم المنطقة.
  3. **Rx30**: إدارة صيدليات أمريكي، بدون سلسلة توريد.
  4. **صيدلتي**: حل محلي بسيط، بدون تتبع شحنات.
  5. **Tamer Group**: حل مخصص لشركة واحدة فقط.
- **Differentiation**: مصمم لسلسلة التوزيع بأكملها، تنبيهات صلاحية ذكية، تتبع شحنات بالباركود، منصة طلبات للصيدليات، تكامل مع هيئة الدواء السعودية SFDA.

## 3. User Personas

### Primary: م. فهد — مدير مستودع أدوية
- **الدور**: يدير مستودعاً ب 5000+ منتج دوائي، يوزع على 200 صيدلية.
- **الأهداف**: تقليل الأدوية منتهية الصلاحية، تحسين دوران المخزون، تسريع التوزيع.
- **نقاط الألم**: الأدوية تنتهي قبل بيعها، صعوبة تتبع الشحنات، الصيدليات تطلب عبر هاتف ويضيع الطلب.

### Secondary: صيدلي. مريم — صيدلانية (صيدلية مستقلة)
- **الدور**: تدير صيدلية، تطلب الأدوية من الموزعين.
- **الأهداف**: طلب سريع، متابعة حالة الطلب، معرفة البدائل المتاحة.
- **نقاط الألم**: الطلب عبر الهاتف ياخذ وقتاً، لا تعرف متى سيصل الطلب، الموزع يرسل أدوية بديلة بدون إشعار.

### Admin: نايف — مدير لوجستي
- **الدور**: ينظم الشحنات بين المستودعات والصيدليات.
- **الأهداف**: تحسين طرق التوزيع، تقليل تكاليف النقل، تتبع الأسطول.
- **نقاط الألم**: التوزيع غير فعال، المرتجعات فوضوية.

## 4. Features by Platform

### Laravel API (Backend)
- Core domain models: Wholesaler, Warehouse, Pharmacy, Product (Drug), Batch, InventoryMovement, Order, OrderItem, Shipment, ReturnRequest
- RESTful endpoints: CRUD for all models
- Auth: Sanctum multi-role (wholesaler_admin/warehouse/pharmacy_admin)
- Product catalog: drugs with SFDA registration, barcode (GS1), price tiers
- Batch tracking: each batch tracked by lot number + expiry date
- FEFO (First Expiry First Out) inventory logic
- Order management: pharmacy places order → warehouse picks → packs → ships
- Shipment tracking: status pipeline (picking → packed → shipped → delivered)
- Expiry alerts: 90/60/30 days before expiry — auto-suggest promotions
- Returns management: expiry damage, wrong item, expiry compensation
- Integration with SFDA drug database, barcode scanning

### React Dashboard (Web)
- Dashboard: active orders, pending shipments, low stock, expiring soon
- Product catalog: add products, set prices, manage batches
- Inventory view: stock levels per warehouse, batch details, expiry calendar
- Order inbox: incoming orders from pharmacies → process → assign to warehouse
- Shipment tracking: create shipment, assign driver, track status
- Pharmacy management: profiles, credit limits, order history
- Reports: inventory aging, fastest/slowest movers, order fulfillment rate
- Alerts: stock below threshold, batch expiring, order SLA breached

### Flutter App (Mobile)
- Warehouse app: scan barcode → pick items → pack → confirm shipment
- Pharmacy app: browse catalog → place order → track shipment → receive
- Push notifications: order confirmed, shipped, delivered, expiring stock
- Inventory scanner: scan product barcode for info + stock level
- Offline: product catalog cached, order queue for offline placement

## 5. Data Model (MVP)

| Entity | Fields | Relationships |
|---|---|---|
| Wholesaler | id, name, cr_no, license_no, address | hasMany Warehouse |
| Warehouse | id, wholesaler_id, name, location, capacity | belongsTo Wholesaler |
| Pharmacy | id, wholesaler_id, name, license_no, address, phone, credit_limit | belongsTo Wholesaler |
| Product | id, name_ar, name_en, ndc_code, barcode, category, manufacturer, package_size, unit | — |
| Batch | id, product_id, warehouse_id, lot_no, expiry_date, quantity, cost_price, selling_price | belongsTo Product/Warehouse |
| InventoryMovement | id, product_id, batch_id, warehouse_id, type (in/out/transfer), quantity, reference | belongsTo Product |
| Order | id, pharmacy_id, warehouse_id, status, total, ordered_at, delivered_at, notes | belongsTo Pharmacy/Warehouse |
| OrderItem | id, order_id, product_id, batch_id, quantity, price | belongsTo Order |
| Shipment | id, order_id, driver_name, vehicle, tracking_no, status, proof_of_delivery | belongsTo Order |
| ReturnRequest | id, order_id, item_id, reason, status, amount_refunded | belongsTo Order |

## 6. API Endpoints (MVP)

| Method | Endpoint | Description |
|---|---|---|
| POST | /api/auth/login | Login (multi-role) |
| GET | /api/products | Product catalog (searchable) |
| GET | /api/products/{id}/batches | Available batches with stock + expiry |
| POST | /api/orders | Pharmacy places order |
| GET | /api/orders | List orders (filterable: status, date) |
| PATCH | /api/orders/{id}/status | Update order status |
| POST | /api/orders/{id}/ship | Create shipment |
| GET | /api/inventory/expiring?days=30 | Products expiring within N days |
| POST | /api/inventory/scan | Scan barcode → product info + stock |
| GET | /api/dashboard/orders | Order fulfillment stats |

## 7. User Interface (Screen List)

### Dashboard screens (React)
- Login
- Dashboard: today's orders, pending pickups, expiry alerts
- Product management: add product, define batches, price list
- Order management: incoming orders → approve → assign warehouse
- Inventory: view by warehouse, batch detail, expiry timeline
- Pharmacy accounts: add pharmacy, set limit, order history
- Shipments: create, track, proof of delivery
- Reports: product movement, inventory aging, warehouse performance

### Mobile screens (Flutter)
- Warehouse: Login → Pick List → Scan Barcode → Confirm Pick → Ship
- Pharmacy: Login → Browse → Cart → Order → Track → Receive → Confirm
- Scanner: scan any product → info + stock levels across warehouses

### Screen flow (text)
```
Login → Dashboard (orders + expiry alerts)
           ├── Products → Add → Add Batches → Set Prices
           ├── Orders → Incoming → Process → Assign Warehouse
           │          → Picking → Packing → Ship
           │          → Delivered → Invoice
           ├── Inventory → By Warehouse → Batch Detail → Expiry Calendar
           │            → Scan Barcode → Stock Info
           ├── Pharmacies → Add → Detail → Order History → Credit
           └── Reports → Slow Movers / Expiry Risk / Fulfillment
```

## 8. Business Model
- **Starter**: $149/month — up to 500 products, 50 pharmacies
- **Pro**: $299/month — up to 5000 products, unlimited pharmacies, batch tracking, shipments
- **Enterprise**: Custom — multiple warehouses, API access, SFDA integration
- **Free trial**: 14-day free trial

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2)**: Laravel API — Product, Batch, Wholesaler/Warehouse/Pharmacy CRUD, FEFO logic
- **Phase 2 (Weeks 3-4)**: React Dashboard — Inventory management, Order processing, Product catalog
- **Phase 3 (Weeks 5-6)**: Flutter App — Warehouse scanner, Pharmacy ordering app
- **Phase 4 (Weeks 7-8)**: Shipment tracking, Returns, Reports, Expiry alerts, Testing, Deploy

## 10. Risk & Mitigation
- **Technical**: Drug data standards (SFDA, GS1) — strategy: flexible product model, import SFDA database.
- **Market**: Pharmacies loyal to current suppliers — strategy: free order portal for pharmacies (network effect).
- **Regulatory**: Drug tracing compliance — strategy: full batch audit trail, track-and-trace ready.
- **Competitive**: Large ERP vendors — strategy: focus on mid-market, affordable cloud alternative.
