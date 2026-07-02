# PRD: GasStation (SAAS-055)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary

- **One-liner:** نظام متكامل لإدارة محطات الوقود — يتتبع المخزون، المبيعات، وسلسلة التوريد مع تقارير دقيقة للمحطة الواحدة وسلاسل المحطات.
- **Problem:** محطات الوقود تعاني من إدارة المخزون يدوياً (بنزين، ديزل، غاز)، صعوبة تتبع المبيعات اليومية والفاقد، عدم وجود رؤية واضحة لحالة الخزانات، ومشاكل في تسعير المشتقات البترولية المتغير. المحطات المتعددة (سلاسل) تفتقر إلى منصة موحدة للإدارة.
- **Proposed solution:** Laravel API (إدارة المخزون والمبيعات وسلسلة التوريد) + React Dashboard (لوحة تحكم شاملة) + Flutter App (تطبيق ميداني للمحطات).

## 2. Market & Opportunity

- **Target market:** محطات الوقود في العالم العربي — تقدر بأكثر من 50,000 محطة في المنطقة. سلاسل محطات الوقود تتوسع وتحتاج حلول إدارة مركزية.
- **Customer segment:** B2B — محطات وقود فردية، سلاسل محطات، موزعون، شركات بترول.
- **Competitor landscape:**
  1. أوراكل (Oracle) — حلول كبيرة ومعقدة ومكلفة.
  2. SAP — ERP ضخم بمكونات لإدارة المحطات لكنه مكلف جداً.
  3. حلول محلية (PTMS) — حلول محدودة بإمكانيات بسيطة.
  4. إكسل وسجلات ورقية — الحل الأكثر انتشاراً.
  5. Fuel Depot — حل أمريكي غير موجه للسوق العربي.
- **Differentiation:** حل مخصص للسوق العربي، دعم المحطات المتعددة، تكامل مع موزعات الوقود (محطات الدفع)، تقارير حكومية جاهزة، تسعير ميسور، دعم عربي كامل، واجهة جوال للعاملين في المحطة.

## 3. User Personas

### أساسي: مدير المحطة — خالد
- **الدور:** مدير محطة وقود
- **الأهداف:** مراقبة مخزون الوقود، إدارة المبيعات، تتبع الفاقد، إدارة الموظفين، إعداد التقارير اليومية
- **نقاط الألم:** عدم معرفة مستوى الخزانات لحظة بلحظة، صعوبة اكتشاف الفاقد والسرقات، مشاكل في تسعير البنزين عند التغيير

### أساسي: مدير السلسلة — فهد
- **الدور:** مدير مجموعة محطات وقود
- **الأهداف:** رؤية موحدة لجميع المحطات، مقارنة الأداء، إدارة التوريد المركزي، تحليل الربحية
- **نقاط الألم:** عدم وجود تقارير موحدة، صعوبة المقارنة بين المحطات، إدارة التوريد لكل محطة على حدة

### ثانوي: مشغل المحطة — عمار
- **الدور:** موظف في المحطة يتعامل مع الموزعات والزبائن
- **الأهداف:** تسجيل المبيعات، إدارة الدفع، الإبلاغ عن الأعطال
- **نقاط الألم:** بطء النظام الحالي، صعوبة التعامل مع حالات الدفع المختلفة (نقدي/بطاقة/محفظة)

### إداري: مشرف النظام
- **الدور:** مسؤول المنصة
- **الأهداف:** إدارة المستخدمين، الصلاحيات، الإعدادات

## 4. Features by Platform

### Laravel API (Backend)

- Station management (single & multi-station chains)
- Tank management (capacity, fuel type, current level, alerts)
- Inventory tracking (fuel in, sales, losses, transfers)
- Supply chain management (purchase orders, deliveries, invoices from distributors)
- Sales tracking (by pump, by cashier, by shift, by payment method)
- Pricing management (multiple price lists, bulk updates)
- Employee management (shifts, attendance, sales per cashier)
- Expense tracking (electricity, water, maintenance, salaries)
- Fuel loss/shrinkage detection (expected vs actual)
- Government report generation (monthly quotas, tax reports)
- Notification system (low tank, price change, equipment failure)
- Tank level API integration (simulated at MVP)

### React Dashboard (Web)

- Dashboard: daily sales, tank levels, alerts, shift summary
- Tanks: tank list, levels visualization, fill history, alerts
- Inventory: stock movement, purchase orders, delivery tracking
- Sales: real-time sales board, shift comparison, payment method breakdown
- Pricing: price list management, bulk update, historical pricing
- Employees: staff management, shift scheduling, sales per employee
- Reports: daily/weekly/monthly P&L, tank reconciliation, VAT reports
- Multi-station: chain overview, station comparison, consolidated reports
- Settings: station profile, tank config, pricing rules

### Flutter App (Mobile)

- Station operator: daily log, tank reading entry, sales entry, shift closure
- Manager: real-time dashboard, alerts, approval of purchase orders
- Push notifications for low inventory, price changes, equipment issues
- Offline entry for tank readings (sync when online)
- Quick price update broadcast
- QR code scanning for delivery verification

## 5. Data Model (MVP)

- **User:** id, name, email, phone, role, station_id, created_at
- **Chain:** id, name, logo, address, phone, created_at
- **Station:** id, chain_id, name, code, address, city, phone, lat, lng, status
- **Tank:** id, station_id, name, fuel_type (gasoline_91/95/diesel/gas), capacity_liters, current_level_liters, min_threshold, status
- **FuelType:** id, name_ar, name_en, code, unit (liter/kg)
- **Supplier:** id, name, commercial_registration, phone, email, address, status
- **PurchaseOrder:** id, station_id, supplier_id, order_date, expected_date, fuel_type, quantity, price, status, notes
- **Delivery:** id, purchase_order_id, delivered_date, quantity_liters, temperature_adjustment, driver_name, vehicle_plate, status
- **PriceList:** id, station_id, fuel_type_id, price_per_liter, effective_date, status
- **Shift:** id, station_id, cashier_id, start_time, end_time, opening_balance, closing_balance, sales_total, status
- **Sale:** id, station_id, pump_id, cashier_id, shift_id, fuel_type_id, quantity_liters, price_per_liter, total, payment_method (cash/card/wallet/credit), created_at
- **Pump:** id, station_id, number, fuel_type_id, status (active/maintenance)
- **Expense:** id, station_id, category, amount, description, date, receipt
- **TankReading:** id, tank_id, reading_date, level_liters, water_level, temperature, operator_notes
- **InventoryMovement:** id, station_id, fuel_type_id, type (in/sale/transfer/loss), quantity, reference, date, notes
- **Report:** id, station_id, type, date, data (json)
- **Notification:** id, user_id, title, body, type, is_read, created_at

## 6. API Endpoints (MVP)

- `POST /api/login` — Auth
- `GET /api/stations` — List stations
- `POST /api/stations` — Create station
- `GET /api/stations/{id}` — Station detail
- `PUT /api/stations/{id}` — Update station
- `GET /api/tanks` — List tanks (filter by station)
- `POST /api/tanks` — Create tank
- `PUT /api/tanks/{id}/level` — Update tank level
- `GET /api/tanks/{id}/history` — Tank level history
- `GET /api/fuel-types` — List fuel types
- `GET /api/suppliers` — List suppliers
- `POST /api/purchase-orders` — Create PO
- `GET /api/purchase-orders` — List POs
- `PUT /api/purchase-orders/{id}/status` — Update PO status
- `POST /api/deliveries` — Record delivery
- `GET /api/price-lists` — List price lists
- `POST /api/price-lists` — Create price list
- `PUT /api/price-lists/{id}/activate` — Activate price
- `GET /api/shifts` — List shifts
- `POST /api/shifts` — Open shift
- `PUT /api/shifts/{id}/close` — Close shift
- `POST /api/sales` — Record sale
- `GET /api/sales` — List sales (filter by date/station/shift)
- `POST /api/sales/bulk` — Bulk sale entry
- `GET /api/expenses` — List expenses
- `POST /api/expenses` — Create expense
- `GET /api/tank-readings` — List readings
- `POST /api/tank-readings` — Record reading
- `GET /api/reports/daily` — Daily report
- `GET /api/reports/monthly` — Monthly report
- `GET /api/reports/tank-reconciliation` — Tank reconciliation
- `GET /api/notifications` — Notifications

## 7. User Interface (Screen List)

### Dashboard Screens (React)
- Login
- Station Dashboard: tank levels gauge, today's sales, shift status, low tank alerts
- Tanks: visual tank map, level indicators, fill log, threshold alerts
- Sales: real-time sales board, cashier breakdown, payment method pie chart
- Shifts: shift log, closure checklist, discrepancy alerts
- Inventory: stock in/out log, PO management, delivery tracking
- Pricing: price list table, effective dates, bulk update modal
- Employees: shift calendar, performance metrics
- Suppliers: PO history, delivery reliability scoring
- Reports: daily sales report, tank reconciliation, VAT report
- Multi-Station: chain dashboard, station comparison, consolidated P&L

### Mobile Screens (Flutter)
- Login
- Operator Home: today's tasks, tank status summary
- Tank Reading: quick entry form (level, water, temp)
- Sales Entry: quick sale recording (fuel type, liters, amount, payment)
- Shift: open/close shift, opening/closing balance
- Alerts: low tank level, price change reminders
- Manager Home: chain overview, alerts, approve POs
- Notifications: real-time alerts

### Screen Flow
```
Login → Dashboard → Tank Levels → Tank Detail → Reading History
  → Sales Board → Sale Details → Payment Breakdown
  → Inventory → POs → Delivery Record
  → Shifts → Open/Close Shift → Shift Report
  → Reports → Daily Report → Monthly → Annual
```

## 8. Business Model

- **Pricing tiers:**
  - محطة واحدة $49/شهر: محطة واحدة، 10 موظفين، 6 خزانات
  - 3 محطات $99/شهر: 3 محطات، 30 موظفاً، تقارير مقارنة
  - سلسلة محطات $199/شهر: محطات غير محدودة، تقارير موحدة، تكامل API
- **Free trial:** 14 يوم تجربة مجانية
- **Target MRR per client:** $49-$199
- **Setup fee:** $199 رسوم تهيئة وإعداد للمحطات (تركيب أجهزة قياس اختياري)

## 9. Implementation Plan

- **Phase 1 (Weeks 1-2):** Auth + Station, Tank, FuelType, Supplier models + CRUD APIs
- **Phase 2 (Weeks 3-4):** PurchaseOrder, Delivery, Shift, Sale APIs + TankReading + InventoryMovement
- **Phase 3 (Weeks 5-6):** React Dashboard — full management UI + report views
- **Phase 4 (Weeks 7-8):** Flutter App — operator app, tank reading, sales entry, shift management
- **Phase 5 (Weeks 9-10):** Integration testing, hardware calibration (tank sensors), deployment

## 10. Risk & Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| دقة قراءة الخزانات | High | دعم إدخال يدوي كبديل، معايرة دورية، مقارنة مع deliveries |
| تقلب أسعار الوقود الحكومية | Medium | نظام تحديث أسعار فوري، تاريخ أسعار للرجوع |
| فاقد وتسريبات غير مكتشفة | High | إنذارات الفاقد، تقارير تسوية يومية، تتبع الفروقات |
| منافسة من أنظمة الشركات البترولية الحكومية | Medium | تركيز على المحطات المستقلة والسلاسل الصغيرة |
| أمان بيانات المبيعات المالية | Medium | تشفير، سجل تدقيق، صلاحيات محكمة |
