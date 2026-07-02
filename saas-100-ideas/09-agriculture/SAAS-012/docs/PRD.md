# PRD: FarmTech (SAAS-012)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary

**منصة ذكية لإدارة المزارع.** تقدم FarmTech حلولاً رقمية لجدولة الري، تتبع المحاصيل، إدارة المخزون الزراعي، وإعداد تقارير الإنتاج. تستهدف المزارع المتوسطة والكبيرة التي تبحث عن رفع الإنتاجية وخفض التكاليف.

- المشكلة: المزارعون يديرون عملياتهم يدوياً (دفاتر ورقية، مكالمات هاتفية)، مما يؤدي لعدم دقة بيانات الإنتاج، هدر المياه، وضعف تتبع المخزون.
- الحل: Laravel API + React Dashboard + Flutter App.

## 2. Market & Opportunity

- السوق المستهدف: 100,000+ مزرعة في الشرق الأوسط وشمال أفريقيا
- الفئة: B2B (مزارعون، شركات زراعية، تعاونيات زراعية)
- المنافسون:
  - **FarmLogs** — منصة أمريكية لإدارة المزارع (بدون دعم عربي)
  - **Cropio** — نظام مراقبة المحاصيل (أوكراني، تركيز على أوروبا)
  - **نايتروجين** — حل سعودي ناشئ محدود الميزات
- التمايز: دعم اللغة العربية، تكامل مع أجهزة استشعار الري، تسعير مناسب للأسواق الناشئة

## 3. User Personas

### شخص أساسي: المزارع (صاحب المزرعة)
- الاسم: خالد
- الدور: يدير مزرعة خضروات بمساحة 50 فداناً
- الأهداف: مراقبة المحاصيل، جدولة الري بكفاءة، تتبع المخزون والخسائر
- نقاط الألم: صعوبة تتبع مواسم الزراعة، عدم وجود بيانات إنتاج دقيقة، هدر المياه

### شخص أساسي: المهندس الزراعي
- الاسم: نور
- الدور: مهندس زراعي يشرف على 3 مزارع
- الأهداف: تحليل التربة، التوصية بالمحاصيل، متابعة الآفات والأمراض
- نقاط الألم: عدم وجود سجل رقمي للمحاصيل، صعوبة التواصل مع العمال

### Admin: مشرف المنصة
- إدارة حسابات المزارع، الاشتراكات، إعدادات التكامل مع أجهزة الاستشعار.

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Farm, Field, Crop, IrrigationSchedule, Inventory, HarvestReport, SensorReading
- RESTful CRUD for all agricultural resources
- Role-based auth (FarmOwner, Agronomist, Worker, Admin)
- IoT sensor data ingestion endpoint (temperature, humidity, soil moisture)
- Notification: irrigation alerts, pest warnings, weather updates
- Report generation engine with export (PDF/CSV)

### React Dashboard (Web)
- Dashboard: farm overview map, crop health KPI, irrigation status, harvest forecast
- Field management: define fields, assign crops, track growth stages
- Irrigation manager: schedule viewer, auto/manual control, water usage analytics
- Inventory: seeds, fertilizers, pesticides — stock tracking with expiry dates
- Harvest logs: record quantity, quality grade, market price
- Reports: yield per crop, water usage efficiency, cost analysis
- Sensor dashboard: real-time readings from field sensors (charts + alerts)

### Flutter App (Mobile)
- Field scanner: scan crop areas, log observations with photos
- Real-time alerts: irrigation due, pest detected, weather warning
- Task assignment: assign workers to tasks (weeding, spraying, harvesting)
- Offline mode: log field observations without internet, sync later
- Inventory scanner: scan barcodes on supplies, update stock
- Market prices: view live commodity prices per region

## 5. Data Model (MVP)

### Farm
- id, name, location (lat/lng), total_area, soil_type, timezone, owner_id (FK), settings (JSON), created_at

### Field
- id, farm_id (FK), name, area_hectares, crop_type, planting_date, growth_stage, status (active/fallow), soil_moisture, created_at

### Crop
- id, field_id (FK), crop_type, variety, planting_date, expected_harvest_date, actual_harvest_date, yield_kg, quality_grade, notes

### IrrigationSchedule
- id, field_id (FK), schedule_type (drip/sprinkler/flood), frequency, duration_minutes, water_source, next_run, status (active/paused), created_at

### Inventory
- id, farm_id (FK), item_type (seed/fertilizer/pesticide), name, quantity, unit, expiry_date, supplier, reorder_level, created_at

### SensorReading
- id, field_id (FK), sensor_type, value, unit, reading_time, created_at

### HarvestReport
- id, field_id (FK), crop_type, harvest_date, quantity_kg, quality_grade, unit_price, total_value, notes, created_at

### User
- id, name, email, password, role, farm_id (FK), phone, created_at

## 6. API Endpoints (MVP)

```
POST   /api/auth/login
POST   /api/auth/logout
GET    /api/auth/me

GET    /api/farms
POST   /api/farms
GET    /api/farms/{id}
PUT    /api/farms/{id}

GET    /api/fields
POST   /api/fields
GET    /api/fields/{id}
PUT    /api/fields/{id}

GET    /api/crops
POST   /api/crops
GET    /api/crops/{id}
PUT    /api/crops/{id}

GET    /api/irrigation-schedules
POST   /api/irrigation-schedules
PUT    /api/irrigation-schedules/{id}
POST   /api/irrigation-schedules/{id}/trigger

GET    /api/inventory
POST   /api/inventory
PUT    /api/inventory/{id}
GET    /api/inventory/low-stock

POST   /api/sensor-readings/batch
GET    /api/sensor-readings?field_id=&type=&from=&to=

GET    /api/harvest-reports
POST   /api/harvest-reports
GET    /api/harvest-reports/{id}

GET    /api/reports/yield?farm_id=&from=&to=
GET    /api/reports/water-usage?farm_id=&from=&to=
```

## 7. User Interface (Screen List)

### Dashboard Screens (React)
1. Login / Register
2. Farm Dashboard — interactive map, KPI cards (crops, irrigation, inventory)
3. Field List — grid view of fields with crop and status badges
4. Field Detail — growth timeline, sensor chart, irrigation schedule
5. Irrigation Manager — schedule list, water usage chart
6. Inventory — stock table with low-stock alerts
7. Harvest Reports — list with export
8. Sensor Dashboard — real-time gauges, historical line charts
9. Reports — yield analysis, water efficiency, cost breakdown
10. Settings — farm profile, user management, sensor integration

### Mobile Screens (Flutter)
1. Splash → Login
2. Dashboard — alerts, today's tasks, field status summary
3. Field Scanner — camera → AI crop detection (future)
4. Task List — assigned jobs with status toggle
5. Inventory Scanner — barcode → stock lookup
6. Alert Inbox — irrigation reminders, pest alerts
7. Field Visit Log — photo + notes → save offline → sync

### Screen Flow
Login → Dashboard → Select Field → View Crops/Irrigation/Sensors → Log Harvest

## 8. Business Model

- **الباقة الأساسية**: $39/شهر (مزرعة واحدة، حتى 10 حقول)
- **الباقة الاحترافية**: $89/شهر (حتى 5 مزارع، تقارير متقدمة، تكامل حساسات)
- **باقة المؤسسات**: $179/شهر (غير محدود، دعم فني مخصص)
- فترة تجربة مجانية: 14 يوماً
- MRR المستهدف لكل عميل: $39-$179

## 9. Implementation Plan

- Phase 1 (Weeks 1-2): Laravel API — Auth, Farm/Field/Crop CRUD, roles
- Phase 2 (Weeks 3-4): Irrigation, Inventory, Sensor ingestion endpoint
- Phase 3 (Weeks 5-6): React Dashboard — Map, charts, all management screens
- Phase 4 (Weeks 7-8): Flutter App — Alerts, field scanner, offline mode
- Phase 5 (Weeks 9-10): IoT integration testing, weather API, deployment

## 10. Risk & Mitigation

- **مخاطرة تقنية**: تكامل أجهزة الاستشعار من مختلف الشركات المصنعة
  - التخفيف: بناء طبقة تجريد (adapter pattern) تدعم MQTT/HTTP/Modbus
- **مخاطرة سوقية**: ضعف الاتصال بالإنترنت في المناطق الزراعية النائية
  - التخفيف: وضع عدم الاتصال بالإنترنت مع المزامنة التلقائية لاحقاً
- **مخاطرة تشغيلية**: موسمية الزراعة تؤثر على الاستخدام المنتظم
  - التخفيف: تسعير سنوي بخصم 20% لتشجيع الالتزام
