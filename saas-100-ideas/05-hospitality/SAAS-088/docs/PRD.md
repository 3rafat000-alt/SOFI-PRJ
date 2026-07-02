# PRD: BakeryMgt (SAAS-088)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** منصة متخصصة لإدارة محلات الحلويات والمخابز تمكن أصحابها من إدارة الوصفات، تنظيم الإنتاج، واستقبال طلبات الزبائن رقمياً.
- **Problem statement:** تعتمد محلات الحلويات على سجلات ورقية للوصفات والمخزون مما يسبب تفاوت الجودة، صعوبة تحديد الكميات المطلوبة يومياً، عدم القدرة على توقع الطلب، وهدر في المواد الخام.
- **Proposed solution:** Laravel API + React Dashboard + Flutter App

## 2. Market & Opportunity
- **Target market:** محلات الحلويات الشرقية والغربية، المخابز الفاخرة، الكافيهات التي تقدم حلويات، مصانع الحلويات الصغيرة. آلاف المحلات في كل مدينة عربية.
- **Customer segment:** B2B (food & beverage)
- **Competitor landscape:**
  1. **Toast** - نظام مطاعم أمريكي، لا يدعم الحلويات المتخصصة
  2. **Apicbase** - إدارة وصفات للمطاعم، سعره مرتفع، أجنبي
  3. **Bakertrak** - متخصص في المخابز لكنه بريطاني بدون دعم عربي
  4. **Excel ودفاتر** - النظام السائد حالياً
- **Differentiation:** حل عربي متخصص في الحلويات والمخابز يدير دورة الإنتاج الكاملة (وصفة ← مقادير ← إنتاج ← تغليف ← بيع) مع دعم الوصفات الخاصة والمقادير الدقيقة وتكامل مع منصات التوصيل.

## 3. User Personas

### الشخصية الأساسية: صاحب محل حلويات - أمين
- **الدور:** يمتلك محل حلويات شرقية مع 10 موظفين
- **الأهداف:** توحيد جودة المنتجات، تقليل الهدر، إدارة الطلبيات الكبيرة (أفراح، مناسبات)
- **نقاط الألم:** وصفات شفهية تختلف من عامل لآخر، خسائر بسبب مقادير غير دقيقة، صعوبة حساب تكلفة القطعة

### الشخصية الثانوية: شيف حلويات - ميرنا
- **الدور:** شيف متخصصة في إنتاج الحلويات
- **الأهداف:** الوصول السريع للوصفات، حساب المقادير حسب الكمية المطلوبة، تسجيل ملاحظات الإنتاج
- **نقاط الألم:** إضاعة وقت في البحث عن الوصفات، المقادير تحتاج حسابات يومية، تعديلات الوصفات تضيع

### الشخصية الثالثة: زبون - هند
- **الدور:** تطلب حلويات للمناسبات العائلية
- **الأهداف:** تصفح المنتجات، طلب كمية محددة، حجز تاريخ التوصيل
- **نقاط الألم:** لا تعرف الأسعار مسبقاً، تحتاج الاتصال للتأكيد، الدفع نقداً فقط

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Product, Recipe, RecipeIngredient, Ingredient, ProductionBatch, Customer, Order, OrderItem, Expense
- RESTful endpoints
- Auth & roles: Owner, Chef, Cashier, Customer
- Recipe management with scalable ingredient calculations
- Costing engine (ingredient cost + labor + overhead per unit)
- Production planning and batch tracking
- Order management (walk-in, pre-order, event catering)
- Inventory tracking with low-stock alerts
- Supplier management and purchase orders
- Daily sales and production reports
- Seasonal menu and pricing management
- Integration with delivery apps (HungerStation, Jahez, etc.)

### React Dashboard (Web)
- Dashboard: daily sales, production volumes, top products
- Recipe management: create/edit, scale quantities, cost calculation
- Ingredient inventory with stock alerts
- Production planning: batch scheduling by day
- Order pipeline: pending, in production, ready, delivered
- Event catering management (large orders with deposit)
- Customer database with order history
- Financial reports: daily P&L, cost per product, profit margins
- Supplier management and purchase orders

### Flutter App (Mobile)
- Menu catalog with photos, descriptions, prices
- Online ordering: select products, quantity, delivery time
- Order tracking with push notifications
- Customer loyalty program (points, rewards)
- Pre-order for events and special occasions
- Recipe viewer for chefs (ingredients, steps, photos)
- Production task list (what to produce today)
- Inventory quick-check and stock request
- Daily sales summary for owner

## 5. Data Model (MVP)

- **User:** id, name, email, phone, role, shop_id
- **Shop:** id, name, address, phone, opening_hours, delivery_radius, settings
- **Product:** id, name, category (cake/pastry/cookie/bread), description, unit_price, cost_per_unit, image, is_active, recipe_id
- **Recipe:** id, name, product_id, yield_quantity, yield_unit, preparation_time, cooking_time, instructions, version
- **Ingredient:** id, name, category, unit, unit_price, stock_quantity, reorder_level, supplier_id
- **RecipeIngredient:** id, recipe_id, ingredient_id, quantity, unit, waste_percentage, notes
- **ProductionBatch:** id, product_id, recipe_id, quantity_produced, date, production_cost, chef_id, start_time, end_time, notes
- **Customer:** id, name, phone, email, address, total_orders, loyalty_points, created_at
- **Order:** id, order_number, customer_id, items, total_amount, deposit, status, payment_status, delivery_date, delivery_address, notes
- **OrderItem:** id, order_id, product_id, quantity, unit_price, subtotal
- **Supplier:** id, name, contact_person, phone, email, delivery_days, payment_terms
- **Expense:** id, shop_id, category, amount, description, date, receipt_photo

## 6. API Endpoints (MVP)

- `POST /api/auth/login` - Login
- `GET /api/dashboard` - Daily stats
- `GET /api/products` - Product catalog
- `POST /api/products` - Add product
- `PUT /api/products/{id}` - Update product/price
- `GET /api/recipes` - Recipe list
- `POST /api/recipes` - Create recipe
- `GET /api/recipes/{id}` - Full recipe with ingredients
- `POST /api/recipes/{id}/scale` - Scale recipe for quantity
- `GET /api/ingredients` - Ingredient inventory
- `POST /api/ingredients/reorder` - Request stock
- `POST /api/production-batches` - Record production batch
- `GET /api/orders` - Orders list
- `POST /api/orders` - Place order
- `PUT /api/orders/{id}/status` - Update order status
- `GET /api/customers` - Customer list
- `GET /api/reports/sales` - Sales report (day/week/month)
- `GET /api/reports/costing` - Costing report per product
- `GET /api/reports/waste` - Waste tracking

## 7. User Interface (Screen List)

### Dashboard Screens (React)
1. Login
2. Owner Dashboard - daily sales, orders pending, low stock alerts, today's production
3. Product Management - catalog with images, pricing, cost
4. Recipe Editor - ingredient list with quantities, scaling calculator
5. Inventory - stock levels, purchase orders, supplier management
6. Production Planner - what to make, batch sizes, schedule
7. Orders - pipeline: new, confirmed, in production, ready, delivered
8. Catering - large event orders with deposit tracking
9. Customer Management - list, order history, loyalty points
10. Reports - sales, costs, margins, waste

### Mobile Screens (Flutter)
1. Customer App: Browse Menu → Product Detail → Add to Cart → Checkout → Track Order → Rate
2. Chef App: Today's Production → View Recipe → Scale → Mark Complete
3. Cashier App: Quick POS → Select Items → Take Payment → Print Receipt
4. Owner App: Dashboard → Approve Orders → View Reports → Manage Staff

### Screen Flow
Customer orders (online/walk-in) → Chef receives production task → Recipe consulted → Ingredients picked → Batch produced → Quality check → Displays in shop → Customer picks up/receives delivery

## 8. Business Model
- **Pricing tiers:** Basic $39/month (single shop, 50 products), Professional $79/month (single shop, unlimited products), Premium $149/month (multi-shop, catering module)
- **Free trial:** 14-day free trial, limited to 20 products
- **Target MRR per client:** $39-$149
- **Additional revenue:** SMS marketing $0.02/message, loyalty program add-on $19/month, delivery commission 3%, multi-shop add-on $49/month per additional shop

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2):** Laravel API - Auth, Products, Recipes, Ingredients CRUD + costing engine
- **Phase 2 (Weeks 3-4):** React Dashboard - Recipe management, inventory, production planning, order management
- **Phase 3 (Weeks 5-6):** Flutter App - Customer ordering, chef recipe viewer, cashier POS
- **Phase 4 (Weeks 7-8):** Delivery integration, catering module, loyalty program, reporting, testing, deploy

## 10. Risk & Mitigation
- **Technical risks:** Recipe scaling math (fractions, conversions) → Mitigation: unit conversion library, imperial to metric, batch calculation validation
- **Inventory accuracy:** Real-time stock depletion requires discipline → Mitigation: barcode scanning for ingredient usage, waste recording mandatory
- **Adoption:** Bakers prefer paper recipes → Mitigation: print-friendly recipe cards, tablet mode in kitchen, video tutorials
- **Competition:** Generic restaurant POS systems adding bakery features → Mitigation: deep bakery features (recipe costing, batch production, moisture/fat ratios)
- **Seasonality:** Ramadan, Eid, Valentine's spikes → Mitigation: production forecasting, pre-order windows, scalable infrastructure
