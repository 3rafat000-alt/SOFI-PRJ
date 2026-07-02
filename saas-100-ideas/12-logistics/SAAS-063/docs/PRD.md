# PRD: TruckNet (SAAS-063)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner**: نظام إدارة شركات الشحن البري — تتبع الشاحنات، إدارة البضائع، جدولة الرحلات، وتواصل مع السائقين.
- **Problem statement**: شركات النقل البري تعتمد على اتصال هاتفي ودفاتر ملاحظات لإدارة الرحلات، مما يسبب غياب الرؤية على موقع الشاحنات، ضعف التواصل مع السائقين، وهدر في استهلاك الوقود والوقت.
- **Proposed solution**: Laravel API + React Dashboard + Flutter App — لوحة تحكم للشركة وتطبيق للسائقين مع تتبع GPS مباشر، جدولة رحلات، وإدارة الحمولات.

## 2. Market & Opportunity
- **Target market size**: سوق إدارة أساطيل النقل البري ~$20B عالمياً، الشرق الأوسط ~$1.5B نمو 10% CAGR.
- **Customer segment**: B2B — شركات نقل بري (5-100 شاحنة)، شركات لوجستية صغيرة ومتوسطة.
- **Competitor landscape**:
  1. **Samsara**: أمريكي، متطور لكن غالي ($100+/شاحنة/شهر)، بدون دعم عربي.
  2. **Fleetio**: إدارة أساطيل عامة بدون تتبع لحظي قوي.
  3. **TruckX**: مخصص للشاحنات لكن بدون دعم عربي، تكامل محدود.
  4. **نقل**: حل محلي حكومي محدود الميزات.
  5. **GPS tracking devices**: أجهزة تتبع بدون نظام إدارة متكامل.
- **Differentiation**: عربي كامل، تتبع GPS لحظي مع خرائط محلية، دردشة سائق-إدارة، تكامل مع منصات الشحن المحلية، تسعير $19/شاحنة فقط.

## 3. User Personas

### Primary: خالد — مدير أسطول نقل
- **الدور**: يدير 30 شاحنة و 35 سائقاً في شركة نقل بري.
- **الأهداف**: تتبع جميع الشاحنات في الوقت الفعلي، تحسين جدولة الرحلات، تقليل تكاليف الوقود.
- **نقاط الألم**: لا يعرف موقع الشاحنات إلا بالاتصال، صعوبة توزيع الحمولات، السائقون يضيعون وقتاً في الطريق.

### Secondary: فيصل — سائق شاحنة
- **الدور**: يسلم البضائع بين المدن، يتبع المسارات المحددة.
- **الأهداف**: استلام تعليمات واضحة، إبلاغ الوصول، إثبات التسليم بسهولة.
- **نقاط الألم**: يضيع في الطرق الجديدة، صعوبة الإبلاغ عن التأخير، توثيق التسليم ورقياً.

### Admin: نورة — منسقة لوجستية
- **الدور**: تنسق الرحلات، تتصل بالعملاء، ترتب أولويات الشحن.
- **الأهداف**: تخصيص الرحلات للسائقين، متابعة حالة الشحنات، إبلاغ العملاء بموعد الوصول.
- **نقاط الألم**: جدولة الرحلات يدوياً، العملاء يسألون عن موقع الشحنة دائماً.

## 4. Features by Platform

### Laravel API (Backend)
- Core domain models: Company, Truck, Driver, Trip, Waypoint, Cargo, MaintenanceRecord, FuelLog, DriverDocument
- RESTful endpoints: CRUD for all models
- Auth: Sanctum multi-role (admin/dispatcher/driver)
- Real-time GPS tracking: WebSocket (Laravel Reverb) + periodic location pings
- Trip management: origin → waypoints → destination, ETAs
- Cargo tracking: weight, type, status (loaded/in-transit/delivered)
- Document management: driver license, truck registration, insurance, permits
- Fuel logging: liters, cost, station, odometer
- Maintenance scheduling: service intervals, alerts
- Geofencing: arrival/departure alerts per zone

### React Dashboard (Web)
- Live map view: all trucks on map, color-coded by status (moving/idle/stopped)
- Trip planner: assign driver + truck + cargo, set route waypoints
- Trip board: active trips, completed, delayed, cancelled
- Driver management: profiles, documents, performance score
- Truck management: specs, maintenance history, expiry tracking
- Cargo management: shipments, proof of delivery
- Reports: fuel consumption, trip duration, driver scorecards, fleet utilization
- Alerts: speed violation, geofence breach, maintenance due

### Flutter App (Mobile)
- Driver app: GPS auto-tracking, trip details (waypoints, cargo), navigation
- Start/end trip, mark waypoints arrived, capture proof of delivery (photo + signature)
- Push notifications: new trip assigned, route change, alert from dispatcher
- Dispatcher app: live fleet view, chat with drivers, quick trip reassign
- Offline: trip data cached, location pings queued and sent when online

## 5. Data Model (MVP)

| Entity | Fields | Relationships |
|---|---|---|
| Company | id, name, cr_no, address, phone | hasMany Truck, Driver, Trip |
| Truck | id, company_id, plate_no, brand, model, year, capacity_tons, insurance_expiry, registration_expiry | belongsTo Company |
| Driver | id, company_id, name, phone, license_no, license_expiry, status | belongsTo Company |
| Trip | id, company_id, truck_id, driver_id, origin, destination, status, scheduled_start, actual_start, actual_end, distance_km | belongsTo Company/Truck/Driver |
| Waypoint | id, trip_id, sequence, location, lat, lng, planned_arrival, actual_arrival, status | belongsTo Trip |
| Cargo | id, trip_id, description, weight, type, status, proof_url | belongsTo Trip |
| FuelLog | id, truck_id, driver_id, liters, cost_per_liter, total_cost, odometer, station, date | belongsTo Truck |
| MaintenanceRecord | id, truck_id, type, description, date, cost, next_due_km | belongsTo Truck |

## 6. API Endpoints (MVP)

| Method | Endpoint | Description |
|---|---|---|
| POST | /api/auth/login | Login (multi-role) |
| GET | /api/trucks | List fleet |
| POST | /api/trips | Create trip (assign driver + truck + cargo) |
| GET | /api/trips/active | Active trips with live positions |
| PATCH | /api/trips/{id}/status | Update trip status |
| POST | /api/locations | Driver reports location (lat, lng, speed) |
| GET | /api/locations/current | All driver current locations |
| POST | /api/trips/{id}/pod | Upload proof of delivery |
| GET | /api/dashboard/fleet | Fleet utilization stats |

## 7. User Interface (Screen List)

### Dashboard screens (React)
- Login
- Live map: truck positions, click for details
- Trip planner: create trip wizard
- Trip board: Kanban (planned → active → completed)
- Driver list → driver detail (info, documents, trips history)
- Truck list → truck detail (specs, maintenance schedule, documents)
- Reports: fuel consumption per truck, trip duration, utilization rate
- Alerts center: maintenance due, expired docs, violations

### Mobile screens (Flutter)
- Driver: Login → Home (current trip) → Trip detail → Navigation → Waypoints → POD
- Driver: History trips, earnings summary
- Dispatcher: Map view, driver list, chat, quick actions

### Screen flow (text)
```
Login → Fleet Dashboard (live map + stats)
           ├── Trips → Create Trip → Select Driver → Select Truck → Set Route
           │         → Active Trip → Track Live → View Waypoints
           │         → Completed → View POD
           ├── Fleet → Trucks → Detail → Maintenance History
           │         → Drivers → Detail → Documents → Trip History
           └── Reports → Fuel / Trips / Drivers / Utilization
```

## 8. Business Model
- **Starter**: $99/month — up to 5 trucks, basic tracking
- **Pro**: $199/month — up to 20 trucks, GPS tracking, dispatch, reports
- **Enterprise**: Custom — unlimited trucks, API access, dedicated support
- **Free trial**: 14-day free trial (up to 3 trucks)

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2)**: Laravel API — Company, Truck, Driver, Trip CRUD, GPS location ingestion
- **Phase 2 (Weeks 3-4)**: React Dashboard — Live map, Trip planner, Trip board
- **Phase 3 (Weeks 5-6)**: Flutter App — Driver navigation, GPS tracking, POD capture
- **Phase 4 (Weeks 7-8)**: Geofencing, Alerts, Reports, WebSocket, Testing, Deploy

## 10. Risk & Mitigation
- **Technical**: GPS battery drain — strategy: adaptive location interval (high freq when moving, low when idle).
- **Technical**: Map integration — strategy: use Mapbox (Arabic support, offline tiles).
- **Market**: Drivers without smartphones — strategy: SMS fallback for basic instructions, USSD codes.
- **Competitive**: Hardware GPS vendors bundle software — strategy: hardware-agnostic, integrate with any GPS device.
