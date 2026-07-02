# PRD: OlivePress (SAAS-052)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary

- **One-liner:** نظام متكامل لإدارة معاصر الزيتون — يتتبع مواسم العصر، المخزون، والمبيعات — مصمم خصيصاً لقطاع إنتاج زيت الزيتون.
- **Problem:** معاصر الزيتون تعتمد على إدارة ورقية أو جداول إكسل لتتبع مواسم العصر، سجلات المزارعين، كميات الزيت المنتجة، والمبيعات. لا يوجد حل تقني متخصص يلبي احتياجات هذا القطاع الموسمي، مما يؤدي إلى أخطاء في الحسابات، ضياع السجلات، وصعوبة في احتساب حصص المزارعين.
- **Proposed solution:** Laravel API (إدارة المواسم والعملاء والمخزون) + React Dashboard (لوحة تحكم لصاحب المعصرة) + Flutter App (تطبيق للمزارعين لمتابعة موسم عصرهم).

## 2. Market & Opportunity

- **Target market:** آلاف معاصر الزيتون في العالم العربي — تونس، المغرب، الجزائر، سوريا، لبنان، الأردن، فلسطين، السعودية. يقدر عدد المعاصر بأكثر من 10,000 في المنطقة.
- **Customer segment:** B2B — معاصر زيتون (متوسطة وكبيرة)، منتجو زيت زيتون، تجار زيت الزيتون.
- **Competitor landscape:**
  1. حلا SYSTEMS — حل محاسبي عام لا يركز على المعاصر.
  2. ERPNext — نظام ERP مفتوح المصدر لكنه عام ومعقد.
  3. أكسيرا — حل محاسبة سحابي عربي عام.
  4. تطبيقات إكسل مخصصة — الحل الحالي لمعظم المعاصر.
  5. Vario — حلول صناعية دولية مكلفة جداً.
- **Differentiation:** حل متخصص 100% في معاصر الزيتون، فهم دقيق لدورة العمل الموسمية، دعم حصص المزارعين (النسبة المئوية)، تقارير إنتاجية الموسم، واجهة عربية مبسطة، سعر مناسب للسوق المحلي.

## 3. User Personas

### أساسي: صاحب المعصرة — الحاج منصور
- **الدور:** مالك معصرة زيتون موسمية
- **الأهداف:** تسجيل المزارعين، إدارة موسم العصر، تتبع كميات الزيت المنتجة، حساب حصص المزارعين، إدارة المبيعات للزبائن
- **نقاط الألم:** صعوبة تتبع المزارعين والكميات يدوياً، أخطاء في حساب حصص الزيت، نزاعات مع المزارعين، ضياع السجلات بعد نهاية الموسم

### أساسي: المزارع — أبو علي
- **الدور:** مزارع زيتون يأتي ثماره للعصر
- **الأهداف:** معرفة وزن ثماره، كمية الزيت المستخرجة، حصته المتبقية، موعد انتهاء العصر
- **نقاط الألم:** عدم معرفة وزنه بدقة، انتظار طويل، عدم ثقة بالحسابات اليدوية

### إداري: مشغل النظام — المدير
- **الدور:** مسؤول المنصة
- **الأهداف:** مراقبة موسم العصر، تقارير الإنتاج، إدارة المستخدمين
- **نقاط الألم:** الحاجة لرؤية شاملة لأداء الموسم

## 4. Features by Platform

### Laravel API (Backend)

- Season management (create, activate, close season)
- Farmer registration and profile management
- Olive delivery intake (weigh-in, quality check)
- Production batch tracking (pressing batches)
- Oil output calculation (liters/kg per batch)
- Farmer share calculation (percentage or fixed)
- Inventory management (oil stock by grade/type)
- Sales management (retail & wholesale)
- Expense tracking (labor, electricity, maintenance)
- Reporting engine (daily, weekly, seasonal)
- Push notification integration
- Farmer SMS/WhatsApp notifications

### React Dashboard (Web)

- Season dashboard: active season stats, daily production
- Farmer management: profiles, delivery history, balances
- Olive intake: batch registration form, scale integration
- Production view: batch-to-batch tracking
- Inventory: oil stock levels, movement log
- Sales: point-of-sale, invoices, customer ledger
- Expenses: category-wise expense entry
- Reports: season comparison, yield analysis, financial summary
- Settings: pricing, share ratios, user management

### Flutter App (Mobile)

- Farmer app: delivery tracking, oil balance, notifications
- Seasonal batch entry (for the mill operator on the floor)
- Real-time production updates
- Push notifications for farmers (ready for pickup)
- Arabic-first Material 3 UI
- Offline data entry for remote areas

## 5. Data Model (MVP)

- **User:** id, name, email, phone, role (admin/mill_owner/farmer), password, created_at
- **Season:** id, name, year, start_date, end_date, status (active/closed), notes
- **Farmer:** id, user_id, full_name, phone, id_number, address, city, land_area, olive_trees_count, status
- **OliveDelivery:** id, farmer_id, season_id, delivery_date, weight_kg, quality_grade, price_per_kg, total_value, notes
- **ProductionBatch:** id, season_id, delivery_id, batch_number, pressing_date, olive_weight_kg, oil_liters, oil_kg, waste_kg, duration_minutes, operator_name
- **OilInventory:** id, season_id, batch_id, grade, quantity_liters, quantity_kg, storage_location, production_date, expiry_date
- **FarmerShare:** id, farmer_id, season_id, total_olive_weight, oil_percentage, oil_due_liters, oil_received_liters, balance_liters
- **Sale:** id, inventory_id, customer_name, customer_phone, date, quantity, unit_price, total, payment_method, status
- **Expense:** id, season_id, category, amount, description, date, receipt_image, created_at
- **Report:** id, season_id, type, generated_at, data (json)
- **Notification:** id, farmer_id, title, body, type, is_read, created_at

## 6. API Endpoints (MVP)

- `POST /api/login` — Auth
- `GET /api/seasons` — List seasons
- `POST /api/seasons` — Create season
- `GET /api/seasons/{id}` — Season details
- `PUT /api/seasons/{id}` — Update season
- `GET /api/farmers` — List farmers
- `POST /api/farmers` — Create farmer
- `GET /api/farmers/{id}` — Farmer details
- `PUT /api/farmers/{id}` — Update farmer
- `GET /api/olive-deliveries` — List deliveries
- `POST /api/olive-deliveries` — Create delivery
- `GET /api/production-batches` — List batches
- `POST /api/production-batches` — Create batch
- `GET /api/production-batches/{id}` — Batch details
- `GET /api/oil-inventory` — List inventory
- `PUT /api/oil-inventory/{id}` — Update inventory
- `GET /api/farmer-shares` — List shares
- `GET /api/farmer-shares/{farmer_id}` — Farmer share detail
- `POST /api/sales` — Record sale
- `GET /api/sales` — List sales
- `GET /api/expenses` — List expenses
- `POST /api/expenses` — Create expense
- `GET /api/reports/season/{id}` — Season report
- `GET /api/reports/farmer/{id}` — Farmer report
- `GET /api/notifications` — List notifications

## 7. User Interface (Screen List)

### Dashboard Screens (React)
- Login
- Dashboard: active season stats (olive pressed, oil produced, active farmers)
- Farmers: CRUD table with search, filter by season
- Olive Deliveries: batch entry form, history table
- Production: batch tracking table, per-batch detail
- Inventory: stock table by grade, adjustments
- Sales: POS-style entry, invoices, customer ledger
- Farmer Shares: balance table, settlement actions
- Expenses: category-wise entry, monthly view
- Reports: season comparison charts, yield metrics
- Settings: pricing, grades, user accounts

### Mobile Screens (Flutter)
- Splash / Login
- Home: season status, daily production summary
- Deliveries: quick olive intake form
- My Balance (Farmer): olive weight, oil due, oil received
- Notifications: pick-up alerts, season reminders
- Profile: farmer info, season history

### Screen Flow
```
Login → Dashboard → Season Overview
  → Farmers → Farmer Detail → Delivery History
  → Olive Intake → Weigh-in → Production Batch
  → Inventory → Oil Grades
  → Sales → New Sale → Invoice
  → Expenses → Add Expense
  → Reports → Season Report
```

## 8. Business Model

- **Pricing tiers:**
  - فردي $15/شهر: معصرة واحدة، 50 مزارع، موسم واحد
  - باقة الأعمال $39/شهر: معصرة واحدة، مزارع غير محدودين، مواسم غير محدودة، تقارير
  - باقة الشركات $79/شهر: 3 معاصر، مستخدمين متعددين، دعم API، تكامل موازين
- **Free trial:** 14 يوم تجربة مجانية
- **Target MRR per client:** $15-$79
- **Seasonal pricing:** إمكانية الدفع فقط خلال موسم العصر (6 أشهر) $25/شهر موسمي

## 9. Implementation Plan

- **Phase 1 (Weeks 1-2):** Auth + Season, Farmer, OliveDelivery models + CRUD API
- **Phase 2 (Weeks 3-4):** ProductionBatch, OilInventory, FarmerShare, Sale + Expense APIs + notification engine
- **Phase 3 (Weeks 5-6):** React Dashboard — full management UI, POS sales, report views
- **Phase 4 (Weeks 7-8):** Flutter App — farmer portal, operator intake, notifications
- **Phase 5 (Weeks 9-10):** Scale integration testing, Arabic QA, deployment, training materials

## 10. Risk & Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| مقاومة التغيير من المعاصر التقليدية | High | تصميم بسيط جداً، دعم ميداني، نسخة ورقية + إدخال لاحق |
| تقلب مواسم الزيتون (عوامل طبيعية) | Medium | نموذج تسعير موسمي، عقود سنوية مرنة |
| ضعف الإنترنت في المناطق الريفية | High | دعم أوفلاين كامل في تطبيق Flutter، مزامنة لاحقة |
| منافسة من حلول الـ ERP العامة | Low | تخصص كامل في المعاصر، فهم عميق للصناعة |
| أمان البيانات المالية | Medium | تشفير AES-256، نسخ احتياطي يومي للبيانات |
