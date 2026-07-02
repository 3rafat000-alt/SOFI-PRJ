# PRD: BreadChain (SAAS-056)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary

- **One-liner:** نظام متكامل لإدارة المخابز والأفران — يتتبع الإنتاج اليومي، المخزون، طلبات الزبائن، والمبيعات — مصمم خصيصاً لقطاع المخابز.
- **Problem:** المخابز والأفران تدير عملياتها يدوياً — تسجيل الطلبات على ورق، تتبع الإنتاج بالعين، إدارة المخزون بدون دقة. هذا يؤدي إلى هدر في المواد، إنتاج زائد أو ناقص، وعدم القدرة على تحليل الأداء. لا يوجد حل تقني متخصص وبسعر مناسب للمخابز الصغيرة والمتوسطة.
- **Proposed solution:** Laravel API (إدارة الإنتاج والمخزون والطلبات) + React Dashboard (لوحة تحكم لصاحب المخبز) + Flutter App (تطبيق للزبائن والعاملين).

## 2. Market & Opportunity

- **Target market:** المخابز والأفران في العالم العربي — تقدر بأكثر من 100,000 مخبز وفرن. من المخابز البلدية الصغيرة إلى المخابز الآلية الكبيرة وسلاسل المخابز.
- **Customer segment:** B2B — أفران بلدية، مخابز آلية، سلاسل مخابز، محال حلويات ومخبوزات.
- **Competitor landscape:**
  1. كاشير (POS systems) — أنظمة نقاط بيع عامة غير متخصصة.
  2. برامج محاسبة (FOX, أونيكس) — محاسبة عامة بلا تخصص في المخابز.
  3. إكسل وورق — الحل الحالي لمعظم المخابز.
  4. Bakery Management Systems — أنظمة عالمية مكلفة (مثل FlexiBake).
  5. ERPNext — مفتوح المصدر لكن يتطلب تخصيصاً كبيراً.
- **Differentiation:** تخصص كامل في المخابز - يدعم الإنتاج بالجملة، وصفتين، خبز بلدي/آلي، حلويات. نموذج تسعير يناسب المخابز العربية. واجهة بسيطة بالعربية. دعم الطلبات المسبقة عبر الجوال. تقارير هدر الإنتاج.

## 3. User Personas

### أساسي: صاحب المخبز — الحاج إبراهيم
- **الدور:** مالك مخبز بلدي
- **الأهداف:** إدارة الإنتاج اليومي، تقليل الهدر، متابعة طلبات الزبائن، حساب الأرباح والخسائر
- **نقاط الألم:** صعوبة تقدير الكميات المطلوبة يومياً، هدر كبير في العجين والخبز، مشاكل في تحصيل الحسابات من الزبائن

### أساسي: مشرف الإنتاج — أبو أحمد
- **الدور:** مشرف على خط الإنتاج في المخبز
- **الأهداف:** تتبع الإنتاج، إدارة العمال، ضبط الجودة
- **نقاط الألم:** عدم معرفة كمية العجين المتبقية، صعوبة تسجيل الإنتاج يدوياً

### ثانوي: الزبون التاجر — سعيد
- **الدور:** صاحب محل بقالة يشتري خبزاً بالجملة
- **الأهداف:** طلب الخبز يومياً، متابعة الكمية المتوفرة
- **نقاط الألم:** صعوبة الوصول لصاحب المخبز، عدم معرفة إن كان الخبز متوفراً

### إداري: مشغل النظام
- **الدور:** مسؤول المنصة
- **الأهداف:** إدارة المستخدمين، ضبط الإعدادات

## 4. Features by Platform

### Laravel API (Backend)

- Production management (daily batches by product type)
- Recipe/BOM management (ingredients per product)
- Inventory management (flour, yeast, oil, additives)
- Supplier management (flour mills, ingredient suppliers)
- Order management (wholesale orders from shops)
- Sales management (direct sales in bakery shop)
- Customer accounts (credit customers, due tracking)
- Expense tracking (ingredients, utilities, labor)
- Waste tracking (production waste, returns)
- Employee management (shifts, attendance, productivity)
- Daily production planning (demand forecasting)
- Notification system
- Report engine (production, sales, profit)

### React Dashboard (Web)

- Dashboard: today's production, sales, inventory alerts
- Production: batch planning, recipe selection, production log
- Inventory: ingredient stock, flour silo levels, purchase orders
- Orders: customer order inbox, order fulfillment tracking
- Sales: point of sale, customer ledger, daily sales summary
- Customers: credit customers, payment tracking, order history
- Recipes: recipe library, ingredient ratios, cost calculation
- Expenses: ingredient purchases, utilities, monthly comparison
- Reports: daily production report, profit margin, waste analysis
- Settings: bakery profile, product catalog, pricing

### Flutter App (Mobile)

- Customer app: daily ordering, order history, account balance
- Production operator: record production batches, log waste
- Push notifications for order ready, stock alerts
- Offline order taking (for delivery drivers)
- Simple Arabic-first interface
- Barcode scanning for products (future)

## 5. Data Model (MVP)

- **User:** id, name, email, phone, role (admin/production/sales/customer), bakery_id
- **Bakery:** id, name, type (baladi/automatic/pastry), address, city, phone, license, status
- **Product:** id, bakery_id, name_ar, name_en, category (bread/pastry/cake), unit, price, production_lead_time, status
- **Recipe:** id, product_id, name, yield_quantity, yield_unit, instructions, cost_per_unit
- **RecipeIngredient:** id, recipe_id, ingredient_id, quantity, unit
- **Ingredient:** id, name_ar, name_en, category (flour/yeast/oil/other), unit, stock_qty, min_stock, supplier_id
- **Supplier:** id, name, phone, address, material_type, payment_terms
- **ProductionBatch:** id, product_id, recipe_id, planned_qty, actual_qty, date, shift, status, started_at, completed_at, notes
- **Customer:** id, name, phone, address, type (retail/wholesale), credit_limit, balance, status
- **Order:** id, customer_id, delivery_date, status, total, paid_amount, notes, created_at
- **OrderItem:** id, order_id, product_id, quantity, unit_price, total
- **Sale:** id, payment_method, customer_id, items (json), total, date, cashier_id
- **Waste:** id, production_batch_id, product_id, quantity, reason, recorded_by, date
- **Employee:** id, user_id, role, shift, salary_type, phone, status
- **Expense:** id, category, amount, description, date
- **Payment:** id, customer_id, amount, date, method, reference
- **Report:** id, type, date, data (json)

## 6. API Endpoints (MVP)

- `POST /api/login` — Auth
- `GET /api/products` — Product list
- `POST /api/products` — Create product
- `GET /api/recipes` — Recipe list
- `POST /api/recipes` — Create recipe
- `GET /api/ingredients` — Ingredient stock
- `POST /api/ingredients/purchase` — Purchase ingredient
- `PUT /api/ingredients/{id}/stock` — Update stock
- `GET /api/production-batches` — List batches
- `POST /api/production-batches` — Start production batch
- `PUT /api/production-batches/{id}/complete` — Complete batch
- `POST /api/production-batches/{id}/waste` — Record waste
- `GET /api/customers` — Customer list
- `POST /api/customers` — Create customer
- `GET /api/customers/{id}/orders` — Customer orders
- `POST /api/orders` — Place order (customer)
- `GET /api/orders` — List orders
- `PUT /api/orders/{id}/status` — Update order status
- `POST /api/sales` — Record sale
- `GET /api/sales` — Sales list
- `GET /api/expenses` — Expense list
- `POST /api/expenses` — Add expense
- `POST /api/payments` — Record payment
- `GET /api/payments` — Payment list
- `GET /api/reports/daily` — Daily production report
- `GET /api/reports/profit` — Profit analysis
- `GET /api/reports/waste` — Waste analysis
- `GET /api/notifications` — Notifications

## 7. User Interface (Screen List)

### Dashboard Screens (React)
- Login
- Dashboard: production vs plan, sales today, stock alerts
- Products: product catalog, pricing, recipe assignment
- Recipes: ingredient list, cost calculation, yield per batch
- Ingredients: stock table, purchase orders, low stock alerts
- Production: daily plan, batch tracking, completion form
- Customers: customer list, credit limits, balance tracking
- Orders: today's orders table, fulfillment tracker
- Sales: POS-style checkout, invoice printing
- Expenses: category view, monthly comparison chart
- Reports: configurable production/sales/waste reports
- Settings: bakery info, shift scheduling, pricing

### Mobile Screens (Flutter)
- Customer App: order products, view balance, order history
- Operator App: production batch entry, waste logging
- Sales App: quick sale recording, customer lookup
- Notifications: order ready, low ingredient

### Screen Flow
```
Login → Dashboard → Production Plan → Batch Production → Waste Recording
  → Inventory → Ingredients → Purchase Order
  → Orders → New Order → Fulfill → Deliver
  → Sales → POS → Payment → Receipt
  → Reports → Daily Summary → Monthly Analysis
```

## 8. Business Model

- **Pricing tiers:**
  - فردي $19/شهر: مخبز واحد، 3 موظفين، 50 منتج
  - باقة الأعمال $49/شهر: مخبز واحد، 10 موظفين، منتجات غير محدودة، تقارير
  - سلسلة $99/شهر: 3 مخابز، موظفين غير محدودين، إدارة مركزية
- **Free trial:** 14 يوم تجربة مجانية
- **Target MRR per client:** $19-$99

## 9. Implementation Plan

- **Phase 1 (Weeks 1-2):** Auth + Product, Recipe, Ingredient models + CRUD APIs
- **Phase 2 (Weeks 3-4):** ProductionBatch + Waste + Order + Sale APIs + Customer accounts
- **Phase 3 (Weeks 5-6):** React Dashboard — bakery management UI, POS, reports
- **Phase 4 (Weeks 7-8):** Flutter App — customer ordering, operator production log
- **Phase 5 (Weeks 9-10):** Testing, ingredient cost calculation tuning, deployment

## 10. Risk & Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| صعوبة تقدير الطلب يومياً | Medium | خوارزمية توقع بسيطة بناءً على تاريخ الطلبات |
| هدر المواد الأولية | High | تتبع يومي للإنتاج مقابل المبيعات، إنذارات الهدر |
| انقطاع الكهرباء (في بعض المناطق) | Medium | دعم أوفلاين، حفظ تلقائي، مزامنة عند الاتصال |
| منافسة من أنظمة نقاط البيع العامة | Low | تخصص كامل في المخابز، فهم عميق لسير العمل |
| التحصيل من الزبائن آجلاً | Medium | نظام تنبيه آلي، حظر تلقائي عند تجاوز الحد الائتماني |
