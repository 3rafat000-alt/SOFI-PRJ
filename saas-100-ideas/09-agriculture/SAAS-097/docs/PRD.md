# PRD: FishFarm (SAAS-097)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary

**منصة متكاملة لإدارة مزارع الأسماك: تغذية، إنتاج، مبيعات.** تهدف FishFarm إلى رقمنة إدارة مزارع الأسماك بالكامل — من إدارة الأحواض والتغذية إلى تتبع الإنتاج والمبيعات وتحليل الأداء.

- المشكلة: مربو الأسماك يعتمدون على طرق تقليدية في إدارة المزارع (سجلات ورقية، تقديرات يدوية) مما يؤدي إلى ضعف الإنتاجية، ارتفاع تكاليف التغذية، صعوبة تتبع دورة الإنتاج، وفقدان البيانات التاريخية للتحسين.
- الحل: Laravel API + React Dashboard + Flutter App.

## 2. Market & Opportunity

- السوق المستهدف: 3,000+ مزرعة سمكية تجارية في الشرق الأوسط وشمال أفريقيا، 50,000+ مربٍ فردي
- الفئة: B2B (مزارع أسماك تجارية، موردو أعلاف، مشتري الجملة، وزارات الثروة السمكية)
- المنافسون:
  - **AKVA Group** — نظام إدارة مزارع سمكية عالمي (مكلف جداً، $10,000+ سنوياً، معقد).
  - **XpertSea** — حل ذكاء اصطناعي للاستزراع السمكي (أمريكي، يركز على الجمبري).
  - **Umitron** — نظام تغذية ذكي (ياباني، مكلف، لا يدعم العربية).
  - **منصة الثروة السمكية** — مبادرة حكومية سعودية (غير متاحة للقطاع الخاص).
- التمايز: حل ميسور التكلفة للمزارع المتوسطة والصغيرة في العالم العربي، دعم كامل للغة العربية، إدارة متكاملة (تغذية-إنتاج-مبيعات)، تحليلات ذكاء اصطناعي لتحسين الإنتاج.

## 3. User Personas

### شخص أساسي: مالك مزرعة أسماك
- الاسم: يوسف
- الدور: يملك مزرعة أسماك بلطي في كفر الشيخ، مصر، بطاقة إنتاجية 50 طن سنوياً
- الأهداف: تحسين معدلات النمو، تقليل تكاليف التغذية، توثيق دورة الإنتاج
- نقاط الألم: عدم معرفة معدل التحويل الغذائي بدقة، صعوبة تتبع نفوق الأسماك، التخمين في موعد الحصاد

### شخص أساسي: مشرف الإنتاج في المزرعة
- الاسم: حسن
- الدور: يشرف على 15 حوضا، يدير التغذية والعلاج والعمالة
- الأهداف: جدولة التغذية، مراقبة جودة المياه، تسجيل الإنتاج اليومي
- نقاط الألم: إدخال البيانات يدوياً، صعوبة مقارنة أداء الأحواض، تأخير اكتشاف المشاكل

### Admin: مدير المزرعة
- تقارير إنتاجية، تحليل تكاليف، إدارة المخزون السمكي.

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Farm, Pond, FishBatch, FeedInventory, FeedingRecord, WaterQualitySample, Harvest, Sale, Expense, MortalityRecord, Treatment
- RESTful CRUD for all resources
- Role-based auth (Admin, FarmManager, PondSupervisor, Buyer)
- Pond management — pond dimensions, water source, aeration type, species
- Fish batch tracking — species, age, stocking date, count, avg_weight, estimated_biomass
- Feeding management — feed type, quantity, scheduled feeding, FCR calculation (Feed Conversion Ratio)
- Water quality monitoring — temperature, pH, dissolved oxygen, ammonia, nitrite
- Harvest planning — estimated biomass, suggested harvest date, grading
- Sales management — buyers, pricing, weight categories, delivery
- Expense tracking — feed costs, medication, electricity, labor
- Mortality recording — count, cause, action taken
- Notification engine: SMS, push (water quality alerts, feeding reminders, harvest ready)

### React Dashboard (Web)
- Farm dashboard: pond overview, biomass chart, FCR trends, alerts
- Pond management: individual pond profiles with real-time metrics
- Feeding planner: schedule by pond, feed type, quantity, auto-FCR calculation
- Water quality dashboard: sensor readings (or manual entry), trends, alerts
- Harvest planner: estimated biomass, readiness score, suggested date
- Sales management: buyer directory, pricing, invoices, delivery tracking
- Financial reports: feed costs, operational costs, revenue per pond, profitability analysis
- Inventory management: feed stock levels, medication inventory, automated reorder points
- Reports: monthly production report, FCR analysis, mortality analysis

### Flutter App (Mobile)
- Pond monitoring: quick glance at all ponds, key metrics (temp, DO, pH)
- Feeding operation: scan feed bag → confirm feeding → record consumption
- Water quality entry: manual or Bluetooth sensor input
- Mortality recording: quick tap → species count → cause selection
- Photo documentation: pond condition, diseased fish, harvest records
- Push notifications: critical alerts (low DO, high ammonia, feeding missed)
- Harvest assistant: weigh fish, grade, record batch, generate sale slip
- Offline mode: record data without internet, sync when connected

## 5. Data Model (MVP)

### Farm
- id, name, location, coordinates, total_ponds, water_source (ground/sea/river), total_capacity_tons, license_number, created_at

### Pond
- id, farm_id (FK), name/number, dimensions (length, width, depth), area_m2, volume_m3, water_source, aeration_type, liner_type, status (active/draining/dry/maintenance), created_at

### FishBatch
- id, pond_id (FK), species, stocking_date, stocking_count, avg_weight_stocked_g, estimated_biomass_kg, current_avg_weight_g, estimated_count, status (growing/harvested/depopulated), created_at

### FeedInventory
- id, farm_id (FK), feed_type (starter/grower/finisher), brand, protein_percentage, bag_weight, current_bags, min_bags_alert, supplier, price_per_bag, created_at

### FeedingRecord
- id, pond_id (FK), fish_batch_id (FK), date, feed_type, quantity_kg, feeding_method (manual/auto), fed_by, created_at

### WaterQualitySample
- id, pond_id (FK), date, temperature, dissolved_oxygen, ph, ammonia_nh3, nitrite_no2, nitrate_no3, alkalinity, salinity, recorded_by, notes, created_at

### Harvest
- id, pond_id (FK), fish_batch_id (FK), harvest_date, total_weight_kg, total_count, avg_weight_g, grade_a_kg, grade_b_kg, grade_c_kg, created_at

### MortalityRecord
- id, pond_id (FK), fish_batch_id (FK), date, count, cause (disease/oxygen/predator/unknown), action_taken, notes, created_at

### Sale
- id, harvest_id (FK), buyer_id (FK), weight_kg, grade, price_per_kg, total_amount, payment_status, delivery_date, invoice_number, created_at

### Treatment
- id, pond_id (FK), fish_batch_id (FK), date, type (medication/vaccine/supplement), product_name, dosage, cost, notes, created_at

## 6. API Endpoints (MVP)

```
POST   /api/auth/login
GET    /api/auth/me

GET    /api/farms/{id}/dashboard

GET    /api/ponds
POST   /api/ponds
GET    /api/ponds/{id}
PUT    /api/ponds/{id}
GET    /api/ponds/{id}/batches
GET    /api/ponds/{id}/water-quality
GET    /api/ponds/{id}/feeding
GET    /api/ponds/{id}/mortality

POST   /api/fish-batches
GET    /api/fish-batches/{id}
PUT    /api/fish-batches/{id}

POST   /api/feeding-records
GET    /api/feeding-records?pond_id=&date_from=&date_to=
GET    /api/feeding-records/fcr?pond_id=&batch_id=

POST   /api/water-quality
GET    /api/water-quality?pond_id=&from=&to=
GET    /api/water-quality/alerts

POST   /api/harvests
GET    /api/harvests/{id}

POST   /api/mortality
GET    /api/mortality?pond_id=&date=

POST   /api/treatments
GET    /api/treatments

GET    /api/feed-inventory
POST   /api/feed-inventory/consume
POST    /api/feed-inventory/restock

GET    /api/sales
POST   /api/sales
GET    /api/sales/revenue?period=

GET    /api/expenses
POST   /api/expenses
GET    /api/expenses/by-category

GET    /api/reports/production?period=
GET    /api/reports/financial?period=
```

## 7. User Interface (Screen List)

### Dashboard Screens (React)
1. Login / Register
2. Farm Dashboard — biomass overview, pond status cards, FCR trends, revenue chart
3. Pond Detail — parameters, batch info, feeding history, water quality graphs
4. Feeding Planner — schedule, consumption reports, FCR analysis
5. Water Quality — table + line charts (temp, DO, pH), alert configuration
6. Harvest Planning — readiness scores, estimated biomass, scheduling
7. Inventory — feed stock, medications, auto-reorder setup
8. Sales — buyers, transactions, invoices, revenue tracking
9. Expenses — by category, pond-level costs, operational costs
10. Reports — production reports, financial reports, export (PDF/Excel)

### Mobile Screens (Flutter)
1. Login → Dashboard (pond tiles with color-coded health status)
2. Pond Quick View — key metrics (temp, DO, pH, biomass)
3. Feed Operation — scan bag → confirm → record
4. Water Quality Entry — form with preset ranges, quick save
5. Mortality Entry — tap pond → count → cause
6. Harvest Assistant — weigh → grade → create sale slip
7. Notifications — alerts, reminders
8. Offline Queue — pending records to sync

### Screen Flow
```
Manager: Dashboard → Pond Overview → Water Quality Check → Feeding → Record Data → Monitor Growth → Harvest → Sell
Mobile: Open App → Quick Pond Scan → Record Feeding → Record Water Quality → Notifications if Critical
```

## 8. Business Model

- **باقة مزرعة صغيرة**: $29/شهر (حتى 10 أحواض، 3 مستخدمين)
- **باقة مزرعة متوسطة**: $59/شهر (حتى 50 حوض، 10 مستخدمين، تقارير متقدمة)
- **باقة مؤسسة**: $129/شهر (غير محدود أحواض، API، استشارات إنتاجية)
- فترة تجربة مجانية: 14 يوماً
- MRR المستهدف لكل مزرعة: $29-$129

## 9. Implementation Plan

- Phase 1 (Weeks 1-2): Laravel API — Auth, Farm/Pond/FishBatch CRUD, Sanctum roles
- Phase 2 (Weeks 3-4): Laravel API — Feeding records, water quality, mortality, harvest, sales
- Phase 3 (Weeks 5-6): React Dashboard — Farm dashboard, pond monitoring, feeding planner, reports
- Phase 4 (Weeks 7-8): Flutter App — Mobile monitoring, feeding ops, water quality, harvest assistant
- Phase 5 (Weeks 9-10): FCR analytics, water quality alert engine, Arabic localization, testing

## 10. Risk & Mitigation

- **مخاطرة تقنية**: الاعتماد على الإدخال اليدوي للبيانات — التخفيف: دعم أجهزة الاستشعار Bluetooth، وضع عدم الاتصال بالإنترنت.
- **مخاطرة سوقية**: ضعف التبني الرقمي في قطاع الاستزراع السمكي التقليدي — التخفيف: واجهة بسيطة، تدريب ميداني، تطبيق موبايل سهل.
- **مخاطرة بيئية**: تغير جودة المياه بشكل مفاجئ — التخفيف: نظام تنبيهات فوري، توصيات تصحيحية تلقائية.
- **مخاطرة تشغيلية**: دقة بيانات التغذية والتحويل الغذائي — التخفيف: استخدام RFID/QR على أكياس العلف، معايرة دورية.
