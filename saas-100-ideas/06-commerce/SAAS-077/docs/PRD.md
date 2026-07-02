# PRD: TobaccoShop (SAAS-077)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** نظام إدارة متخصص لمحلات الدخان والنرجيلة: إدارة المخزون، الموردين، طلبات العملاء، التقارير المالية
- **Problem:** محلات الدخان تعاني من إدارة مخزون كبيرة ومتنوعة، توثيق موردين معقد، طلبات متكررة، ومتطلبات رقابية صارمة
- **Solution:** Laravel API + React Dashboard + Flutter App (shop owners + staff + delivery)

## 2. Market & Opportunity
- **Target market:** 1M+ tobacco shops in MENA; Large informal sector; Increasing digital payments and age verification requirements
- **Customer segment:** B2B (tobacco shops, shisha lounges, distributors, wholesalers)
- **Competitors:** ECRS (general POS), Loyverse, ToastTab, ShopBox, most use Excel/paper
- **Differentiation:** Niche specialization (tobacco-specific inventory categories), age verification integration, supplier management with brand tracking, regulatory compliance tools

## 3. User Personas

### صاحب المحل — جابر (Primary)
- **Role:** مالك محل دخان ونرجيلة في مدينة متوسط
- **Goals:** إدارة المخزون بأنواعه المتعددة، متابعة الموردين، حساب الأرباح
- **Pain points:** أنواع كثيرة من الدخان يصعب تتبعها، سرقة المخزون، عدم دقة التقارير اليدوية

### الموزع — فايز (Secondary)
- **Role:** موزع جملة لمنتجات التبغ والنرجيلة
- **Goals:** معرفة طلبات المحلات، إدارة قائمة الأسعار، تتبع التوصيل
- **Pain points:** اتصال هاتفي مستمر مع المحلات، أخطاء في الطلبات، صعوبة تحصيل المدفوعات

### Admin — Dashboard Operator
- **Role:** مدير المنصة يراقب اشتراكات المحلات، الامتثال، الدعم

## 4. Features by Platform

### Laravel API (Backend)
- Product catalog (cigarettes, molasses, shisha, coal, accessories) with brand/variant tracking
- Inventory management (stock-in, stock-out, expiry, damaged)
- Supplier management (contacts, pricing, order history)
- Purchase orders to suppliers
- Sales & billing (with VAT/SDT calculation)
- Customer age verification (ID scan + facial age estimation)
- Regulatory compliance (tax reports, purchase limits)

### React Dashboard (Web)
- Product catalog with variant management
- Stock dashboard (low stock alerts, expiry warnings)
- Supplier management & purchase order creation
- Sales dashboard (daily/weekly/monthly reports)
- Customer database
- Compliance reports (tax, age verification logs)
- Multi-shop management (for chains)
- Staff management (roles, shifts, permissions)

### Flutter App (Mobile)
- **Shop App:** Scan barcodes for stock in/out, Create sales invoices, View stock levels, Receive low stock alerts, Process payments (cash/card/wallet), Customer age verification, View daily sales report
- **Supplier App:** Product catalog, Receive orders, Manage price lists, Track deliveries

## 5. Data Model (MVP)
- **Shop:** id, name, address, license_no, owner_id, subscription_tier
- **Product:** id, shop_id, category (cigarette/molasses/coal/accessory), brand, variant, barcode, unit_price, cost_price, stock_quantity, reorder_level, expiry_date
- **Supplier:** id, shop_id, name, contact, payment_terms, products_supplied (JSON)
- **PurchaseOrder:** id, shop_id, supplier_id, items (JSON), total_amount, status, ordered_at, received_at
- **Sale:** id, shop_id, items (JSON), total_amount, vat_amount, discount, payment_method, customer_age_verified, created_at
- **Customer:** id, shop_id, name, phone, id_verified, total_purchases
- **Staff:** id, shop_id, name, role, pin_code, permissions (JSON), schedule

## 6. API Endpoints (MVP)
- `POST /api/products` — Add product
- `GET /api/products?low_stock=true` — Low stock alerts
- `POST /api/purchase-orders` — Create PO
- `GET /api/purchase-orders` — List POs
- `PATCH /api/purchase-orders/{id}/receive` — Receive inventory
- `POST /api/sales` — Create sale (including items, VAT, payment)
- `GET /api/sales?date=YYYY-MM-DD` — Daily sales report
- `GET /api/reports/profit` — Profit & loss
- `POST /api/customers/verify-age` — Age verification record
- `GET /api/compliance/tax-report` — Tax report for period
- `POST /api/staff` — Manage staff accounts
- `POST /api/suppliers` — Add supplier

## 7. User Interface (Screen List)
- **Dashboard screens:** Sales overview, Stock status, Low stock widget, Supplier list, Compliance center
- **Mobile (Shop):** Home (POS quick sale), Inventory, Stock alerts, Sales history, Reports, Settings
- **Flow (Shop):** Login → POS Screen → Scan Item or Select Product → Checkout (with age check) → Receipt → Dashboard
- **Flow (Supplier):** Login → View Orders → Manage Price List → Confirm Delivery → Payment

## 8. Business Model
- **Pricing:** Per shop: Basic ($39/mo, 1 user, 500 products), Pro ($79/mo, 3 users, unlimited), Premium ($149/mo, unlimited users, multi-shop)
- **Free trial:** 14-day free trial
- **Target MRR per shop:** $39–$149
- **Add-ons:** Age verification SDK ($19/mo), Multi-shop support ($49/mo extra), API access for custom integration ($29/mo)

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2):** API — Product catalog, Inventory management, Basic sales, Supplier CRUD
- **Phase 2 (Weeks 3-4):** React Dashboard — Product management, Stock dashboard, Sales reports, Supplier PO
- **Phase 3 (Weeks 5-6):** Flutter App — POS interface, Barcode scanning, Stock management, Sales history
- **Phase 4 (Weeks 7-8):** Age verification integration, Tax/compliance reports, Multi-shop support, QA, Deploy

## 10. Risk & Mitigation
- **Regulatory risk:** Tobacco sales heavily regulated → Built-in age verification, purchase limits, tax calculation compliant with local laws
- **Market risk:** Social stigma around tobacco → Market as "retail management software" — same product, neutral branding
- **Inventory accuracy risk:** Barcode scanning reduces errors but theft remains → Regular audit features, discrepancy reports
- **Payment integration risk:** Cash-heavy business → Support multiple payment methods including cash, build cash management features
