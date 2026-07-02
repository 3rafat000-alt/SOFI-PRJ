# PRD: HoneyFarm (SAAS-082)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** منصة متخصصة لإدارة المناحل تمكن النحالين من تتبع خلايا النحل، مراقبة الإنتاج، وإدارة المبيعات بكفاءة عالية.
- **Problem statement:** يعاني النحالون من صعوبة تتبع حالة مئات الخلايا يدوياً، عدم وجود سجلات دقيقة للإنتاج لكل خلية، صعوبة إدارة مواسم التزهير والترحال، وضعف قنوات البيع المباشر للمستهلكين.
- **Proposed solution:** Laravel API + React Dashboard + Flutter App

## 2. Market & Opportunity
- **Target market:** النحالون المحترفون (من 50 خلية فأكثر)، منتجو العسل التجاريون، الجمعيات التعاونية النحلية. السوق العربي يقدر بأكثر من 30 مليون خلية نحل.
- **Customer segment:** B2B / B2C (mixed)
- **Competitor landscape:**
  1. **Beebot** - تطبيق أجنبي لتتبع الخلايا لكن بدون إدارة مبيعات
  2. **ApiManager** - برنامج ألماني متخصص لكن سعره مرتفع وبدون عربي
  3. **HiveTracks** - أمريكي، مجتمعي، تركيز على مشاركة البيانات
  4. **BeePlus** - حل محلي بدائي، واجهة ضعيفة
- **Differentiation:** حل عربي متكامل يغطي كامل سلسلة القيمة من تتبع الخلية إلى البيع، مع دعم الترحال وإدارة مواسم التزهير، وتطبيق جوال يعمل بدون إنترنت في المناطق النائية.

## 3. User Personas

### الشخصية الأساسية: نحال محترف - أبو محمد
- **الدور:** يمتلك 200 خلية نحل في 3 مناحل متنقلة
- **الأهداف:** تتبع إنتاج كل خلية، معرفة وقت استخراج العسل، إدارة مواسم التزهير والترحال
- **نقاط الألم:** صعوبة تذكر حالة كل خلية، خسائر بسبب تفويت وقت القطف أو علاج الأمراض

### الشخصية الثانوية: منتج عسل تجاري - سارة
- **الدور:** تدير منحل تجاري وتعبئ وتوزع العسل للأسواق
- **الأهداف:** تتبع المخزون، إدارة طلبات الجملة، تصدير شهادات الجودة
- **نقاط الألم:** عدم دقة سجلات الإنتاج، صعوبة تتبع تواريخ الإنتاج والصلاحية

### الشخصية الإدارية: مشرف جمعية نحالين - فيصل
- **الدور:** يدير تعاونية تضم 50 نحالاً
- **الأهداف:** متابعة إنتاج الأعضاء، إدارة المخزون الموحد، تسويق المنتجات
- **نقاط الألم:** عدم وجود نظام موحد، صعوبة تجميع الإحصائيات

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Apiary, Hive, Inspection, Harvest, Product, Order, Customer, Treatment
- RESTful endpoints
- Auth & roles: Beekeeper, Producer, AssociationAdmin, SuperAdmin
- Notifications: weather alerts, treatment reminders, harvest season alerts
- Honey production analytics
- QR/barcode generation per hive
- Season and flowering calendar management
- GPS tracking for mobile apiaries

### React Dashboard (Web)
- Hive overview with health status dashboard
- Apiary map view with color-coded hives
- Harvest records and production reports
- Inventory management (honey, wax, propolis, pollen)
- Order management and invoicing
- Customer management (wholesale/retail)
- Flowering calendar and migration planner
- Treatment logs and disease tracking
- Financial reports (revenue, expenses, profit per hive)

### Flutter App (Mobile)
- Hive inspection form with photo upload
- QR scanner for hive identification
- Weather forecast integration
- Treatment tracker with dosage calculator
- Harvest recording with weight and quality notes
- Offline mode for remote apiaries
- Push notifications for critical alerts
- Direct sales channel (product listings, orders)

## 5. Data Model (MVP)

- **User:** id, name, email, phone, role, apiary_count, created_at
- **Apiary:** id, name, lat, lng, location_description, is_mobile, beekeeper_id, created_at
- **Hive:** id, apiary_id, hive_number, type, queen_age, frames, health_status, last_inspected_at, qr_code
- **Inspection:** id, hive_id, inspector_id, date, temper, brood_pattern, disease_signs, food_stores, actions_taken, photos
- **Treatment:** id, hive_id, treatment_type, product_name, dosage, date_applied, effectiveness, notes
- **Harvest:** id, hive_id, harvest_date, honey_kg, wax_kg, honey_type, quality_grade, notes
- **Product:** id, name, type (honey/wax/pollen/propolis), unit_price, stock, harvest_id, batch_number, expiry_date
- **Order:** id, customer_id, items, total_amount, status, payment_status, delivery_date, notes
- **Customer:** id, name, phone, email, address, type (wholesale/retail), created_at
- **FloweringCalendar:** id, plant_name, region, start_date, end_date, suitability_score

## 6. API Endpoints (MVP)

- `POST /api/auth/login` - Login
- `POST /api/auth/register` - Register
- `GET /api/apiaries` - List apiaries
- `POST /api/apiaries` - Create apiary
- `GET /api/apiaries/{id}` - Apiary details with hives
- `GET /api/hives` - List hives with filters
- `POST /api/hives` - Add hive
- `GET /api/hives/{id}` - Hive full history
- `POST /api/inspections` - Record inspection
- `GET /api/inspections?hive_id=X` - Inspection history
- `POST /api/treatments` - Record treatment
- `GET /api/treatments` - Treatment logs
- `POST /api/harvests` - Record harvest
- `GET /api/harvests` - Harvest list with production totals
- `GET /api/products` - Product inventory
- `POST /api/products` - Create product
- `GET /api/orders` - Order management
- `POST /api/orders` - Create order
- `GET /api/customers` - Customer list
- `GET /api/analytics/production` - Production analytics per period
- `POST /api/flowering-calendar` - Manage seasons
- `GET /api/reports/profitability` - Profit per hive report

## 7. User Interface (Screen List)

### Dashboard Screens (React)
1. Login / Register
2. Dashboard Overview - stats (total hives, healthy %, production this month)
3. Apiary Map - interactive map with hive markers
4. Hive Details - inspection history, treatments, harvests
5. Inspection Log - list with filters (date, apiary, health status)
6. Harvest Records - production by period, quality breakdown
7. Product Management - inventory table + batch tracking
8. Orders - pipeline with status tracking
9. Customers - CRM view
10. Flowering Calendar - seasonal planner
11. Reports - charts and exportable tables

### Mobile Screens (Flutter)
1. Home - quick stats + recent inspections
2. Hive Scanner - QR/barcode scanner
3. Inspection Form - health check checklist with photos
4. Treatment Log - dosage calculator + schedule
5. Harvest Entry - weight input + quality assessment
6. Product Store - list products for sale
7. My Orders - customer order tracking
8. Weather - forecast + apiary-specific alerts
9. Notifications - treatments due, harvest reminders

### Screen Flow
Login → Dashboard → Apiary → [Hive details | Inspection | Harvest] → Data entry → Sync

## 8. Business Model
- **Pricing tiers:** Starter $29/month (up to 50 hives), Professional $59/month (up to 200 hives), Commercial $99/month (unlimited)
- **Free trial:** 14-day free trial, limited to 20 hives
- **Target MRR per client:** $29-$99
- **Additional revenue:** SMS alerts $0.05/message, premium analytics pack $19/month, hardware bundles (hive scales, sensors) commission 10%

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2):** Laravel API - Auth, Apiaries, Hives, Inspections CRUD + basic analytics
- **Phase 2 (Weeks 3-4):** React Dashboard - Map view, hive management, inspection logs, reports
- **Phase 3 (Weeks 5-6):** Flutter App - Scanner, offline inspections, harvest entry, store
- **Phase 4 (Weeks 7-8):** Payment integration, weather API, notification system, testing, deploy

## 10. Risk & Mitigation
- **Technical risks:** Offline usage in remote areas → Mitigation: local SQLite sync with background sync when online
- **Seasonal nature:** Low usage in winter → Mitigation: annual subscription model, add winter planning features
- **Adoption:** Beekeepers prefer traditional methods → Mitigation: simple UI, training videos, WhatsApp support group
- **Competition:** Free apps exist for basic tracking → Mitigation: focus on complete value chain (production to sale)
- **IoT integration:** Future sensor support → Mitigation: design API with IoT device schema extensibility
