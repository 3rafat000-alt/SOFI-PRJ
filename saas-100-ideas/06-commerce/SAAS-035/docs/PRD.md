# PRD: InventoryPro (SAAS-035)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** نظام متكامل لإدارة المخازن والجرد يتتبع المنتجات وينبه عند نفاد المخزون ويدير الموردين بذكاء
- **Problem:** متاجر الجملة والمستودعات تعتمد على Excel وجرد يدوي مما يؤدي إلى أخطاء المخزون، نفاد غير متوقع، وصعوبة إدارة الموردين
- **Proposed solution:** Laravel API + React Dashboard + Flutter App تقدم لوحة تحكم للمخزون مع تتبع آني، تنبيهات، وإدارة متكاملة للموردين

## 2. Market & Opportunity
- **Target market size:** سوق إدارة المخزون ~$5B. التجارة الإلكترونية والطلب على التوزيع السريع يضاعف الحاجة
- **Customer segment:** B2B — متاجر جملة، تجار تجزئة، مستودعات توزيع، مصنعين صغار
- **Competitor landscape:**
  1. TradeGecko (قوي لكن اشتراكه مرتفع)
  2. Zoho Inventory (متكامل مع Zoho لكن صعب التعلم)
  3. Odoo Inventory (مفتوح المصدر لكن يتطلب خبرة تقنية)
  4. Cin7 (حل مؤسسي كبير ومكلف)
  5. inFlow Inventory (بسيط لكن بدون تطبيق جوال عربي)
- **Differentiation:** واجهة عربية كاملة، أسعار تبدأ من $19، تنبيهات ذكية عبر واتساب، تكامل مع متاجر Shopify ومتاجر المحلي، تطبيق جوال لمسح الباركود والجرد

## 3. User Personas

### Primary: مدير مستودع (عمر)
- **Role:** مدير مخازن في شركة توزيع مواد غذائية
- **Goals:** معرفة المخزون الفعلي آنياً، إعادة الطلب في الوقت المناسب، تقليل الهدر
- **Pain points:** الجرد اليدوي يستغرق 3 أيام، نفاد المخزون يحدث فجأة، صعوبة تتبع تواريخ انتهاء الصلاحية

### Secondary: مشتري (هدى)
- **Role:** مسؤولة مشتريات في متجر سوبر ماركت
- **Goals:** طلب كميات مناسبة من الموردين، تتبع الطلبيات، مقارنة أسعار الموردين
- **Pain points:** لا توجد رؤية واضحة لمستويات المخزون، صعوبة معرفة أفضل مورد لكل منتج

### Admin: مدير النظام
- **Dashboard operator:** يدير المستخدمين والصلاحيات، يضبط إعدادات التنبيهات

## 4. Features by Platform

### Laravel API (Backend)
- Product catalog CRUD with categories, variants, barcodes, images
- Stock tracking — inbound, outbound, adjustments, transfers across warehouses
- Low-stock and out-of-stock alerts engine
- Supplier management — purchase orders, supplier pricing, lead times
- Purchase order workflow (create → send → receive)
- Inventory valuation (FIFO, weighted average)
- Batch/lot tracking with expiry date management
- Barcode/QR code generation for labels
- Integration webhooks for e-commerce platforms
- Reporting engine (stock movement, turnover, valuation)

### React Dashboard (Web)
- Inventory dashboard — stock levels, alerts, recent movements
- Product management — list, add/edit, bulk import via CSV/Excel
- Stock movements log (date, type, quantity, reference)
- Purchase orders — create, track deliveries
- Supplier management — contact info, pricing, performance
- Warehouse management — multiple locations, transfers
- Reports — stock aging, turnover, valuation, low-stock prediction
- Alerts panel — all active alerts in one view

### Flutter App (Mobile)
- Barcode scanner for quick stock lookup
- Stock count — scan product, enter quantity (offline-capable)
- Receive purchase orders — scan items to confirm delivery
- Low stock notifications with push alerts
- Quick dashboard — stock value, low stock count, pending orders
- Move stock between warehouses (scan → transfer)

## 5. Data Model (MVP)
- **Warehouse:** id, name, location, address, capacity
- **Product:** id, name, sku, barcode, category_id, unit, price, cost, min_stock, max_stock, image
- **ProductVariant:** id, product_id, name, sku, barcode, price, stock (JSON)
- **Stock:** id, product_id, warehouse_id, quantity, batch_number, expiry_date
- **StockMovement:** id, product_id, warehouse_id, user_id, type (in/out/transfer/adjustment), quantity, reference, notes
- **Supplier:** id, name, contact_person, email, phone, address, payment_terms
- **PurchaseOrder:** id, supplier_id, warehouse_id, status, expected_date, notes
- **PurchaseOrderItem:** id, purchase_order_id, product_id, quantity, unit_price, received_quantity
- **Alert:** id, product_id, alert_type (low_stock/out_of_stock/expiring), threshold, triggered_at

## 6. API Endpoints (MVP)
- `GET /api/products` — list products (search, filter)
- `POST /api/products` — create product
- `GET /api/products/{id}` — product detail + stock across warehouses
- `PUT /api/products/{id}` — update product
- `POST /api/products/{id}/stock` — add stock movement
- `GET /api/warehouses` — list warehouses
- `POST /api/warehouses` — create warehouse
- `GET /api/stock/movements` — movement log (filter by product/warehouse/date)
- `GET /api/suppliers` — list suppliers
- `POST /api/suppliers` — create supplier
- `GET /api/purchase-orders` — list POs
- `POST /api/purchase-orders` — create PO
- `PATCH /api/purchase-orders/{id}/receive` — mark items received
- `GET /api/alerts` — active alerts
- `POST /api/auth/login` — login
- `POST /api/auth/register` — register

## 7. User Interface (Screen List)
- **Dashboard:**
  - Login/Register
  - Inventory overview (stock levels, low stock alerts, value)
  - Product list (grid/table with search + filters)
  - Product detail (info, stock, movements)
  - Stock movement log
  - Purchase order management
  - Supplier management
  - Warehouse management
  - Reports
  - Settings
- **Mobile:**
  - Login
  - Dashboard (summary cards)
  - Barcode scanner (find product instantly)
  - Stock count mode (scan → enter qty → save)
  - Product quick view
  - Receive PO (scan items)
  - Low stock alerts

## 8. Business Model
- **Pricing tiers:**
  - Starter ($19/mo): 500 products, 1 warehouse
  - Pro ($49/mo): 5,000 products, 3 warehouses, purchase orders
  - Enterprise ($99/mo): unlimited products, unlimited warehouses, API
- **Free trial:** 14-day free trial
- **Target MRR per client:** $19-$99/month

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2):** API — Product/Stock CRUD, Warehouse, Auth
- **Phase 2 (Weeks 3-4):** React Dashboard — Product management, Stock dashboard, POs
- **Phase 3 (Weeks 5-6):** Flutter App — Barcode scanner, Stock count, Receive PO
- **Phase 4 (Weeks 7-8):** Alerts engine, Reports, Supplier module, Deploy

## 10. Risk & Mitigation
- **Technical risk:** Barcode scanning on mobile (variance in camera quality)
  - *Mitigation:* Use ML Kit barcode scanning, support manual SKU entry as fallback
- **Market risk:** Incumbents well-established
  - *Mitigation:* Start with smallest businesses, offline mobile scanning for Arabic-speaking markets
- **Accuracy risk:** Stock discrepancies between system and physical
  - *Mitigation:* Built-in stock count workflow with discrepancy reports, audit trail
