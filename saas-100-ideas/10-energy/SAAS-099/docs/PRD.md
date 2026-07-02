# PRD: PowerBackup (SAAS-099)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary

**منصة متكاملة لإدارة مولدات الكهرباء: صيانة، وقود، توزيع.** تهدف PowerBackup إلى رقمنة إدارة مولدات الطاقة الاحتياطية — من جدولة الصيانة وإدارة الوقود إلى توزيع الأحمال ومراقبة الأداء عن بُعد.

- المشكلة: مالكو مولدات الكهرباء (منازل، شركات، أبراج اتصالات) يعانون من انقطاع الصيانة الدورية، نفاد الوقود في أوقات حرجة، وصعوبة تتبع أداء المولد وحالته التشغيلية.
- الحل: Laravel API + React Dashboard + Flutter App.

## 2. Market & Opportunity

- السوق المستهدف: 1M+ مولد كهرباء احتياطي في الشرق الأوسط (منازل، شركات، مستشفيات، أبراج اتصالات)، 1,000+ شركة صيانة مولدات
- الفئة: B2B + B2C (شركات صيانة، أبراج اتصالات، مستشفيات، فنادق، مراكز تجارية، أفراد)
- المنافسون:
  - **Generac MobileLink** — مراقبة مولدات (علامة تجارية واحدة، Generac فقط).
  - **Cummins PowerCommand** — نظام مراقبة (مكلف، Cummins فقط).
  - **مولدات السعودية** — خدمات صيانة تقليدية (لا يوجد تطبيق).
  - **Fleetio** — إدارة صيانة أسطول (عام، ليس للمولدات تحديداً).
- التمايز: منصة متعددة العلامات التجارية (تعمل مع أي موديل)، تركيز على السوق العربي، مراقبة IoT اختيارية، إدارة وقود متكاملة.

## 3. User Personas

### شخص أساسي: مسئول صيانة في شركة
- الاسم: سلطان
- الدور: مسئول الصيانة في فندق كبير في دبي يمتلك 3 مولدات احتياطية
- الأهداف: جدولة الصيانة الدورية، مراقبة استهلاك الوقود، التأكد من جاهزية المولد للطوارئ
- نقاط الألم: صعوبة تتبع مواعيد الصيانة، نفاد الوقود في عطلات نهاية الأسبوع، عدم معرفة حالة المولد عن بُعد

### شخص أساسي: صاحب منزل بمولد احتياطي
- الاسم: وليد
- الدور: صاحب منزل في الرياض يمتلك مولد احتياطي بسبب انقطاع الكهرباء
- الأهداف: تذكير بمواعيد الصيانة، معرفة متى يحتاج المولد وقوداً، طلب فني عند العطل
- نقاط الألم: ينسى مواعيد تغيير الزيت، لا يعرف كمية الوقود المتبقية، صعوبة إيجاد فني صيانة موثوق

### Admin: مدير شركة الصيانة
- إدارة الفنيين، تتبع عقود الصيانة، تحليل أداء المولدات لديها.

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Generator, Customer, MaintenanceSchedule, FuelTransaction, ServiceRequest, Technician, GeneratorReadings, Alert, Contract, SparePart
- RESTful CRUD for all resources
- Role-based auth (Admin, Technician, Customer, FuelSupplier)
- Generator registry — brand, model, capacity (kVA), fuel type, serial number, installation date, location (lat/lng)
- Maintenance scheduling — periodic (oil change, filter, belt), engine hours based, calendar integration
- Fuel management — tank capacity, current level, consumption rate, refill history, supplier management
- Remote monitoring (IoT optional) — runtime hours, voltage, frequency, temperature, oil pressure, auto alerts
- Service request workflow — customer request → technician dispatch → diagnosis → repair → close
- Alert engine — low fuel, maintenance overdue, fault detected, high temperature, low oil pressure
- Contract management — service contracts with SLA, pricing, renewal dates
- Notification engine: SMS, push (maintenance due, fuel low, fault detected)

### React Dashboard (Web)
- Overview dashboard: generator status (online/offline/error), active alerts, upcoming maintenance
- Generator detail: specifications, location, status, readings history, maintenance log
- Maintenance planner: calendar view, recurring task templates, engine hour tracking
- Fuel management: tank levels, consumption trends, refill history, cost analysis
- Service requests: ticket queue, dispatch management, repair history
- Technician management: workload, skills, service area, performance
- Alert configuration: threshold settings, notification rules, escalation
- Reports: uptime percentage, fuel consumption trends, maintenance costs, SLA compliance

### Flutter App (Mobile)
- Dashboard: quick status of all generators, active alerts count
- Generator monitoring: real-time readings (if IoT enabled), status indicators
- Fuel level gauge: visual gauge, consumption rate, days remaining estimate
- Maintenance reminders: upcoming service, past due items, auto-log after service
- Service request: describe issue, attach photos, request technician visit
- Technician app: view assigned requests, update status, log parts used
- Push notifications: critical alerts (fuel low, fault detected, maintenance due)
- QR code scanning: quick generator lookup and status check

## 5. Data Model (MVP)

### Generator
- id, customer_id (FK), brand, model, serial_number, capacity_kva, fuel_type (diesel/gas/dual), tank_capacity_l, current_fuel_level_l, installation_date, engine_hours, last_service_date, location (JSON—lat/lng/address), status (online/offline/error), created_at

### Customer
- id, user_id (FK), type (individual/company), company_name, phone, address, generators_count, created_at

### MaintenanceSchedule
- id, generator_id (FK), service_type (oil_change/filter/battery/coolant/belt/inspection), frequency_type (time/engine_hours), frequency_value (days or hours), last_performed_at, next_due_at, assigned_technician_id (FK), created_at

### FuelTransaction
- id, generator_id (FK), supplier_id (FK), amount_l, cost_per_l, total_cost, previous_level, new_level, type (refill/consumption), recorded_by, created_at

### ServiceRequest
- id, generator_id (FK), customer_id (FK), technician_id (FK), issue_description, priority (low/medium/high/critical), status (open/dispatched/in_progress/resolved/closed), fault_code, resolution_notes, parts_used (JSON), cost, customer_signature, created_at

### Technician
- id, user_id (FK), specialization (diesel/electric/both), service_area, certifications (JSON), rating, status (available/busy/offline), created_at

### GeneratorReading
- id, generator_id (FK), engine_hours, voltage, frequency, oil_pressure, coolant_temp, load_percentage, fuel_level, recorded_at, source (iot/manual), created_at

### Alert
- id, generator_id (FK), type (low_fuel/maintenance_due/fault/overheating/low_oil/overload), severity (warning/critical), message, status (active/acknowledged/resolved), acknowledged_by, resolved_at, created_at

### Contract
- id, customer_id (FK), generator_ids (JSON), type (preventive_maintenance/full_service/on_demand), start_date, end_date, monthly_rate, included_visits, status (active/expired/cancelled), created_at

## 6. API Endpoints (MVP)

```
POST   /api/auth/login
GET    /api/auth/me

GET    /api/generators
POST   /api/generators
GET    /api/generators/{id}
PUT    /api/generators/{id}
GET    /api/generators/{id}/readings
GET    /api/generators/{id}/maintenance
GET    /api/generators/{id}/fuel-history
GET    /api/generators/{id}/alerts

POST   /api/readings
POST   /api/readings/batch          (IoT bulk)

GET    /api/maintenance/schedule
POST   /api/maintenance/schedule
PUT    /api/maintenance/schedule/{id}/complete
GET    /api/maintenance/upcoming

POST   /api/fuel/refill
GET    /api/fuel/transactions
GET    /api/fuel/consumption?generator_id=&from=&to=

POST   /api/service-requests
GET    /api/service-requests
GET    /api/service-requests/{id}
PUT    /api/service-requests/{id}/dispatch
PUT    /api/service-requests/{id}/status

GET    /api/technicians
POST   /api/technicians
GET    /api/technicians/{id}/schedule

GET    /api/alerts
PUT    /api/alerts/{id}/acknowledge
PUT    /api/alerts/{id}/resolve

GET    /api/contracts
POST   /api/contracts
GET    /api/contracts/{id}

GET    /api/dashboard/overview
GET    /api/dashboard/customer
GET    /api/dashboard/technician
```

## 7. User Interface (Screen List)

### Dashboard Screens (React)
1. Login / Register
2. Admin Dashboard — generator status map, active alerts bar, maintenance KPI cards
3. Generator List — searchable table with status indicators (green/yellow/red)
4. Generator Detail — specs, live readings (if IoT), maintenance history, fuel gauge
5. Maintenance Planner — calendar view, auto-schedule, pending tasks
6. Fuel Management — tank levels, consumption graph, refill log, cost analysis
7. Service Requests — ticket queue with priority coloring, dispatch button
8. Technician Management — profiles, workload, performance metrics
9. Alert Management — active alerts, history, threshold settings
10. Contracts — customer contracts, SLA tracking, renewal reminders
11. Reports — uptime report, fuel consumption, maintenance spend

### Mobile Screens (Flutter)
1. Dashboard — generator cards with status dot, total alerts count
2. Generator Quick View — status, fuel gauge, engine hours, last reading
3. Fuel Level — visual gauge with days remaining estimate
4. Maintenance — upcoming tasks, past service, complete service log
5. Service Request — describe problem, attach photos, submit
6. Technician App — assigned jobs, navigate to site, update status, log parts
7. QR Scanner — point at generator QR code → full status page
8. Notifications — critical alerts, maintenance reminders

### Screen Flow
```
Customer: Dashboard → View Generator Status → Check Fuel → Review Maintenance → Request Service → Track Technician
Technician: Receive Assignment → View Job Detail → Navigate → Diagnose → Repair → Log Service → Close
Admin: Dashboard → Monitor All Generators → Manage Alerts → Schedule Maintenance → Assign Technicians → Reports
```

## 8. Business Model

- **باقة المالك الفردي**: $9/شهر (مولد واحد، صيانة مجدولة أساسية)
- **باقة الشركات**: $29/شهر (حتى 5 مولدات، مراقبة IoT، تقارير متقدمة)
- **باقة المؤسسات**: $79/شهر (غير محدود مولدات، مراقبة لحظية، عقود صيانة)
- **خدمة IoT**: $5/شهر لكل مولد (جهاز استشعار إضافي)
- فترة تجربة مجانية: 14 يوماً
- MRR المستهدف لكل عميل: $9-$79

## 9. Implementation Plan

- Phase 1 (Weeks 1-2): Laravel API — Auth, Generator/Customer/Maintenance CRUD, Sanctum roles
- Phase 2 (Weeks 3-4): Laravel API — Fuel management, service requests, alert engine, IoT integration
- Phase 3 (Weeks 5-6): React Dashboard — Generator monitoring, maintenance planner, fuel management
- Phase 4 (Weeks 7-8): Flutter App — Generator monitoring, fuel gauge, service requests, technician app
- Phase 5 (Weeks 9-10): IoT sensor integration (ESP32/Raspberry Pi), QR labeling, Arabic localization

## 10. Risk & Mitigation

- **مخاطرة تقنية**: تكامل IoT مع أنواع مختلفة من المولدات — التخفيف: استخدام بروتوكول Modbus القياسي، دعم الإدخال اليدوي كبديل.
- **مخاطرة سوقية**: عدم وعي أصحاب المولدات بأهمية الصيانة الدورية — التخفيف: توعية عبر البريد الإلكتروني، تقارير توفر المال على المدى الطويل.
- **مخاطرة تشغيلية**: دقة استشعار مستوى الوقود — التخفيف: استخدام حساسات معايرة، السماح بالإدخال اليدوي للمعايرة.
- **مخاطرة أمنية**: أمان بيانات مواقع المولدات الحساسة (لأبراج الاتصالات والمستشفيات) — التخفيف: تشفير الموقع، صلاحيات وصول مقيدة.
