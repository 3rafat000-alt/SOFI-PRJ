# PRD: SolarPro (SAAS-057)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary

- **One-liner:** منصة متكاملة لإدارة أنظمة الطاقة الشمسية — تتبع الإنتاج، جدولة الصيانة، إدارة العملاء والفواتير — لشركات الطاقة الشمسية والمنازل.
- **Problem:** شركات الطاقة الشمسية تدير مشاريعها عبر الإكسل والورق — تتبع صعب لصيانة الألواح، عدم توفر بيانات إنتاج دقيقة للعملاء، صعوبة في إدارة فواتير الصيانة وعقود التشغيل. العملاء بدورهم لا يرون مردود استثمارهم الشمسي بوضوح.
- **Proposed solution:** Laravel API (إدارة الأنظمة والعملاء والصيانة والإنتاج) + React Dashboard (لوحة تحكم للشركات) + Flutter App (تطبيق للعملاء والفنيين).

## 2. Market & Opportunity

- **Target market:** شركات الطاقة الشمسية، منازل ومنشآت تجارية مثبت عليها ألواح شمسية. سوق الطاقة الشمسية في العالم العربي ينمو بأكثر من 20% سنوياً، خاصة في السعودية، الإمارات، مصر، الأردن، والمغرب.
- **Customer segment:** B2B (شركات طاقة شمسية) + B2C (أصحاب المنازل والمنشآت).
- **Competitor landscape:**
  1. SolarEdge monitoring — منصة لمراقبة الإنتاج لكنها مرتبطة بأجهزة محددة.
  2. Enphase Enlighten — مشابهة، مخصصة لعواكس إنفيز.
  3. محطات الرصد المحلية — بوابات إنترنت خاصة بالعواكس.
  4. إكسل — الطريقة الأكثر شيوعاً لإدارة المشاريع.
  5. EV360 — حل صيانة عام غير متخصص في الطاقة الشمسية.
- **Differentiation:** منصة شاملة تتجاوز مجرد مراقبة الإنتاج — إدارة دورة حياة كاملة (تركيب → صيانة → فواتير → عقود). دعم للعواكس المتعددة (التكامل عبر API عام). تقارير توفير المال والعائد على الاستثمار (ROI). دعم عربي، تسعير ميسور.

## 3. User Personas

### أساسي: مدير شركة طاقة شمسية — مازن
- **الدور:** مدير شركة تركيب وصيانة أنظمة شمسية
- **الأهداف:** إدارة مشاريع التركيب، جدولة الصيانة، متابعة العملاء، حساب أرباح المشاريع
- **نقاط الألم:** عدم متابعة صيانة الأنظمة بعد التركيب، صعوبة حساب أداء الأنظمة للعملاء، ضياع عقود الصيانة

### أساسي: فني الصيانة — يوسف
- **الدور:** فني ميداني يقوم بتركيب وصيانة الألواح
- **الأهداف:** معرفة مهامه اليومية، تسجيل الصيانة، رفع تقارير الأعطال
- **نقاط الألم:** عدم معرفة جدول الصيانة، صعوبة تسجيل الأعطال ميدانياً

### ثانوي: صاحب المنزل — عبدالرحمن
- **الدور:** زبون لديه نظام شمسي على سطح منزله
- **الأهداف:** متابعة إنتاج الكهرباء، معرفة التوفير، جدولة الصيانة
- **نقاط الألم:** عدم معرفة أداء النظام، القلق من الأعطال، عدم وضوح الفواتير

### إداري: مشغل النظام
- **الدور:** مسؤول المنصة
- **الأهداف:** إدارة الشركات والفنيين، مراقبة الأداء

## 4. Features by Platform

### Laravel API (Backend)

- Company/Solar installer management
- Customer management (residential & commercial)
- System/installation records (panels, inverter, battery specs)
- Production data ingestion (API integration with inverters)
- Maintenance scheduling & work orders
- Invoice & contract management
- Performance monitoring (daily/monthly/yearly yield)
- Financial tracking (ROI, payback period, savings)
- Alert system (underperformance, fault detection)
- Technician assignment & scheduling
- Spare parts inventory
- Report generation

### React Dashboard (Web)

- Dashboard: total installed capacity, active systems, health status, production charts
- Customers: customer list with system details, contract info
- Installations: system registry, component specs, warranty tracking
- Production: real-time & historical production, comparison with expected
- Maintenance: work order management, schedule, technician assignment
- Invoices: billing, payment tracking, contract renewals
- Inventory: panel, inverter, battery stock
- Alerts: fault alerts, underperforming systems, pending maintenance
- Reports: production reports, ROI calculators, environmental impact

### Flutter App (Mobile)

- Customer app: real-time production, savings dashboard, maintenance requests
- Technician app: work orders, checklists, photo upload, GPS check-in
- Push notifications: system alerts, maintenance reminders, invoice due
- Production charts (daily, weekly, monthly, yearly)
- Offline work order completion (sync when online)
- Arabic-first Material 3 UI

## 5. Data Model (MVP)

- **User:** id, name, email, phone, role, company_id, created_at
- **Company:** id, name, commercial_registration, tax_number, phone, address, logo, status
- **Customer:** id, user_id, company_id, name, phone, address, city, property_type, contract_id, created_at
- **Contract:** id, customer_id, company_id, type (installation/maintenance/ppp), start_date, end_date, amount, status
- **Installation:** id, customer_id, company_id, installation_date, total_capacity_kw, panel_count, inverter_count, battery_count, status
- **Panel:** id, installation_id, brand, model, wattage, quantity, serial_numbers
- **Inverter:** id, installation_id, brand, model, capacity_kw, serial_number, api_source
- **Battery:** id, installation_id, brand, model, capacity_kwh, quantity
- **ProductionRecord:** id, installation_id, recorded_at, energy_kwh, power_kw, status, source (api/manual)
- **MaintenanceWorkOrder:** id, installation_id, technician_id, title, description, status (open/in_progress/completed), priority, scheduled_date, completed_date, notes
- **WorkOrderTask:** id, work_order_id, task, completed, notes
- **Invoice:** id, customer_id, contract_id, amount, type, due_date, status, paid_at
- **Alert:** id, installation_id, type, severity, message, status, created_at, resolved_at
- **SparePart:** id, company_id, name, quantity, min_stock, unit_price
- **TechnicianLog:** id, work_order_id, action, timestamp, location (lat/lng)
- **Notification:** id, user_id, title, body, type, is_read

## 6. API Endpoints (MVP)

- `POST /api/login` — Auth
- `GET /api/customers` — Customer list
- `POST /api/customers` — Create customer
- `GET /api/customers/{id}` — Customer detail with systems
- `GET /api/installations` — List installations
- `POST /api/installations` — Register installation
- `GET /api/installations/{id}` — Installation detail
- `PUT /api/installations/{id}` — Update installation
- `GET /api/installations/{id}/production` — Production data
- `POST /api/production` — Record production reading
- `GET /api/production/range` — Production by date range
- `GET /api/maintenance-orders` — List work orders
- `POST /api/maintenance-orders` — Create work order
- `PUT /api/maintenance-orders/{id}` — Update status
- `GET /api/technicians` — List technicians
- `GET /api/contracts` — List contracts
- `POST /api/contracts` — Create contract
- `GET /api/invoices` — Invoice list
- `POST /api/invoices` — Generate invoice
- `PUT /api/invoices/{id}/pay` — Record payment
- `GET /api/alerts` — System alerts
- `PUT /api/alerts/{id}/resolve` — Resolve alert
- `GET /api/inventory` — Spare parts
- `POST /api/inventory/adjust` — Adjust stock
- `GET /api/reports/production` — Production report
- `GET /api/reports/roi` — ROI analysis
- `GET /api/reports/environmental` — CO2 savings
- `GET /api/notifications` — Notifications

## 7. User Interface (Screen List)

### Dashboard Screens (React)
- Login
- Dashboard: total MW installed, active systems, energy generated today, CO2 saved
- Customers: CRM view with system status, contract details
- Installations: system registry with component list, warranty tracker
- Production: daily curve, monthly comparison, annual yield
- Maintenance: work order kanban, technician calendar
- Alerts: fault log, underperformance detection
- Invoices: billing dashboard, payment tracking
- Contracts: active contracts, renewals calendar
- Inventory: spare parts stock, purchase orders
- Reports: exportable production, financial, environmental reports

### Mobile Screens (Flutter)
- Customer App: production dashboard, savings meter, maintenance request
- Technician App: work order list, job detail, GPS navigation, completion form
- Alerts: push notification for faults
- Profile: customer info, system details

### Screen Flow
```
Company Dashboard →
  Installations → Installation Detail → Production Charts → Alerts History
  → Maintenance → Create Work Order → Assign Technician → Complete → Invoice
  → Customers → Customer Detail → Contracts → Invoices → Payments
  → Reports → Production Report → ROI Report → Environmental Report

Customer App →
  Home → Production Today → Savings → Comparison
  → Maintenance → Request Service → Track Technician
  → Invoices → Pay → History
```

## 8. Business Model

- **Pricing tiers:**
  - Starter $29/شهر: حتى 50 نظاماً، فني واحد، تقارير أساسية
  - Professional $79/شهر: حتى 200 نظام، 5 فنيين، تكامل عواكس، تقارير متقدمة
  - Enterprise $199/شهر: أنظمة غير محدودة، فنيين غير محدودين، API كامل، تقارير مخصصة
- **Free trial:** 14 يوم تجربة مجانية
- **Target MRR per client:** $29-$199
- **Customer app:** مجاني لأصحاب المنازل (مدعوم من الشركات)

## 9. Implementation Plan

- **Phase 1 (Weeks 1-2):** Auth + Customer, Installation, Panel/Inverter/Battery models + CRUD APIs
- **Phase 2 (Weeks 3-4):** ProductionRecord + Maintenance + Invoice + Alert APIs + inverter API integration stub
- **Phase 3 (Weeks 5-6):** React Dashboard — company management UI, production charts, maintenance board
- **Phase 4 (Weeks 7-8):** Flutter Customer App + Technician App
- **Phase 5 (Weeks 9-10):** Inverter API integration (SolarEdge/Huawei), testing, deployment

## 10. Risk & Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| تكامل API العواكس معقد ومتعدد | High | البدء بدعم عاكسين (SolarEdge + Huawei)، API gateway موحد |
| دقة بيانات الإنتاج من الأجهزة | Medium | التحقق من صحة البيانات، معايرة readings، إدخال يدوي بديل |
| ضعف معرفة العملاء بالطاقة الشمسية | Medium | لوحة معلومات بسيطة جداً مع شرح عائد الاستثمار بالصور |
| طول دورة مبياع أنظمة الطاقة الشمسية | Medium | عقود صيانة سنوية كاشتراك متجدد (MRR ثابت) |
| تغير سياسات الدعم الحكومي للطاقة الشمسية | Low | تصميم مرن للتكيف مع تغييرات التعرفة والقيود |
