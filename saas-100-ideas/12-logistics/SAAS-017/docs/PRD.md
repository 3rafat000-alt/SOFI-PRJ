# PRD: CargoNet (SAAS-017)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary

**منصة متكاملة لإدارة شركات الشحن والتوصيل.** تقدم CargoNet حلولاً رقمية لتتبع الشحنات، إدارة السائقين، تحسين المسارات، وإعداد تقارير الأداء. تستهدف شركات الشحن ومكاتب التوصيل الصغيرة والمتوسطة.

- المشكلة: شركات الشحن تعتمد على المكالمات والواتساب لتتبع الشحنات، مما يؤدي إلى ضعف الشفافية، تأخير التوصيل، وصعوبة تقييم أداء السائقين.
- الحل: Laravel API + React Dashboard + Flutter App.

## 2. Market & Opportunity

- السوق المستهدف: 10,000+ شركة شحن ومكتب توصيل في الشرق الأوسط
- الفئة: B2B (شركات شحن، مكاتب توصيل، خدمات لوجستية)
- المنافسون:
  - **Shipa** — منصة شحن سحابية (تخدم التجارة الإلكترونية، وليس شركات الشحن)
  - **Logisti** — حل لوجستي محلي محدود الميزات
  - **Wheelys** — تطبيق توصيل طلبات (طعام، مشاوير)
- التمايز: إدارة كاملة لأسطول السائقين، مسارات ذكية، تتبع حي للشحنات

## 3. User Personas

### شخص أساسي: مدير شركة الشحن
- الاسم: سامي
- الدور: يدير شركة شحن صغيرة مع 10 سائقين
- الأهداف: تتبع الشحنات، توزيع المهام على السائقين، تحسين المسارات
- نقاط الألم: صعوبة متابعة السائقين، شكاوى العملاء عن التأخير، عدم وجود تقارير أداء

### شخص أساسي: سائق التوصيل
- الاسم: حسن
- الدور: سائق يقوم بتوصيل 20-30 شحنة يومياً
- الأهداف: عرض الطلبات الموكلة إليه، التنقل بأفضل طريق، إثبات التوصيل
- نقاط الألم: صعوبة العثور على العنوان، إهدار الوقت في الطريق، توثيق التسليم

### شخص أساسي: العميل (متلقي الشحنة)
- الاسم: علي
- الدور: ينتظر استلام طرد أو مستند
- الأهداف: تتبع الشحنة في الوقت الفعلي، معرفة وقت التوصيل المتوقع
- نقاط الألم: عدم معرفة موعد التوصيل، صعوبة التواصل مع السائق

### Admin: مشرف المنصة
- إدارة شركات الشحن المسجلة، الإشراف على الجودة، إدارة العمولات.

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Company, Shipment, Driver, Vehicle, Route, TrackingEvent, ProofOfDelivery
- RESTful CRUD for shipments, drivers, vehicles
- Real-time tracking via WebSocket (socket.io or Laravel WebSockets)
- Route optimization engine (waypoint sequencing, traffic-aware)
- Proof of delivery (photo, signature, barcode scan)
- Barcode/label generation for each shipment
- SMS notifications (tracking link, delivery ETA, delivered confirmation)
- Driver earnings calculation (per-delivery, distance-based)

### React Dashboard (Web)
- Dashboard: active shipments map, delivery success rate, driver performance
- Shipment management: create, assign, track, proof of delivery view
- Driver management: profiles, performance score, earnings, route history
- Vehicle management: registration, maintenance schedule, fuel tracking
- Route planner: drag-and-drop waypoints, optimize sequence, view ETA
- Live tracking map: real-time driver locations with heat map
- Proof of delivery gallery: photos and signatures per shipment
- Reports: delivery success %, average delivery time, driver ranking

### Flutter App (Mobile) - Driver App
- Login with face/OTP verification
- Today's deliveries: sorted list with route optimization
- Turn-by-turn navigation (Google Maps/Waze integration)
- Status update: picked up -> in transit -> delivered -> failed
- Proof of delivery: capture photo, collect signature, scan barcode
- Call/text customer with masked number (privacy)
- Daily earnings counter, tips, bonus tracking
- Offline mode: download today's list, sync status when online

### Flutter App (Mobile) - Customer (optional white-label)
- Track shipment: enter tracking number, view real-time location
- Push notifications: out for delivery, ETA update, delivered
- Delivery instructions: add notes for driver
- Rate delivery experience

## 5. Data Model (MVP)

### Company
- id, name, phone, email, license, address, subscription_tier, settings (JSON), created_at

### Shipment
- id, company_id (FK), tracking_number, sender_name, sender_phone, recipient_name, recipient_phone, pickup_address, delivery_address, status (pending/picked-up/in-transit/delivered/failed), package_type, weight, notes, scheduled_date, delivered_at, created_at

### Driver
- id, company_id (FK), name, phone, email, password, id_number, license_number, vehicle_id (FK), is_active, rating, total_deliveries, earnings, location (lat/lng), created_at

### Vehicle
- id, company_id (FK), plate_number, model, year, type (car/van/truck), fuel_type, status (active/maintenance), created_at

### Route
- id, driver_id (FK), date, shipment_ids (JSON), start_location, end_location, distance_km, duration_minutes, status (planned/in-progress/completed), created_at

### TrackingEvent
- id, shipment_id (FK), event_type (picked-up/in-transit/out-for-delivery/delivered/failed), location (lat/lng), timestamp, description, created_at

### ProofOfDelivery
- id, shipment_id (FK), photo_url, signature_url, recipient_name, received_at, notes, created_at

### User
- id, name, email, password, role (company-admin/dispatcher/driver/admin), company_id (FK), created_at

## 6. API Endpoints (MVP)

```
POST   /api/auth/login
POST   /api/auth/verify-otp
GET    /api/auth/me

GET    /api/shipments
POST   /api/shipments
GET    /api/shipments/{id}
PUT    /api/shipments/{id}/status
PUT    /api/shipments/{id}/assign
GET    /api/shipments/tracking/{tracking_number}

GET    /api/drivers
POST   /api/drivers
GET    /api/drivers/{id}
PUT    /api/drivers/{id}/location
GET    /api/drivers/{id}/deliveries/today
GET    /api/drivers/{id}/earnings

GET    /api/vehicles
POST   /api/vehicles
PUT    /api/vehicles/{id}

GET    /api/routes
POST   /api/routes
POST   /api/routes/{id}/optimize
PUT    /api/routes/{id}/start
PUT    /api/routes/{id}/complete

POST   /api/tracking-events
GET    /api/tracking-events?shipment_id=

POST   /api/proof-of-delivery
GET    /api/proof-of-delivery/{id}

GET    /api/reports/delivery-success?company_id=&from=&to=
GET    /api/reports/driver-performance?company_id=&from=&to=
```

## 7. User Interface (Screen List)

### Dashboard Screens (React)
1. Login
2. Dashboard - live map, delivery stats, pending shipments, driver status
3. Shipment List - filterable, searchable with status badges
4. New Shipment - form with auto-generated tracking number
5. Shipment Detail - full tracking timeline, proof of delivery
6. Driver Management - list with live status, performance scores
7. Driver Detail - route history, earnings, rating, feedback
8. Vehicle Manager - fleet overview, maintenance alerts
9. Route Planner - map with draggable waypoints
10. Live Tracking - real-time fleet view with driver clustering
11. Proof of Delivery Gallery - photo/signature per shipment
12. Reports - delivery success chart, driver ranking, on-time rate

### Mobile Screens (Flutter) - Driver App
1. Login (OTP verification)
2. Today's Dashboard - delivery count, earnings today, route button
3. Delivery List - sorted by route order
4. Delivery Detail - customer info, address, navigation button
5. Status Update - pick up, in transit, delivered (photo + signature)
6. Navigation - turn-by-turn with Google Maps
7. Earnings - day/week/month breakdown
8. Profile - personal info, vehicle, stats

### Screen Flow
Dispatcher Creates Shipment -> Driver Assigned -> Driver Follows Route -> Updates Status -> POD Collected

## 8. Business Model

- **الباقة الأساسية**: $39/شهر (حتى 200 شحنة/شهر، 3 سائقين)
- **الباقة الاحترافية**: $79/شهر (حتى 1000 شحنة/شهر، 10 سائقين، مسارات ذكية)
- **باقة المؤسسات**: $159/شهر (غير محدود، API، تقارير متقدمة، دعم ممتاز)
- فترة تجربة مجانية: 14 يوماً
- MRR المستهدف لكل عميل: $39-$159

## 9. Implementation Plan

- Phase 1 (Weeks 1-2): Laravel API - Auth, Company/Shipment/Driver CRUD, tracking number generation
- Phase 2 (Weeks 3-4): Real-time tracking (WebSocket), route optimization algorithm, proof of delivery
- Phase 3 (Weeks 5-6): React Dashboard - All screens, live map, route planner
- Phase 4 (Weeks 7-8): Flutter Driver App - Deliveries, navigation, status updates, offline mode
- Phase 5 (Weeks 9-10): Flutter Customer App (optional), load testing, deployment

## 10. Risk & Mitigation

- **مخاطرة تقنية**: تحديثات الموقع الحي تستهلك بطارية السائق
  - التخفيف: تحسين تردد التحديث، استخدام significant location changes
- **مخاطرة سوقية**: منافسة التطبيقات الكبيرة (مرسول، نون، جاهز)
  - التخفيف: التركيز على شحن الطرود والمستندات (B2B) وليس توصيل الطعام
- **مخاطرة تشغيلية**: دقة تحسين المسار تعتمد على جودة بيانات الخرائط
  - التخفيف: تكامل مع Google Maps و Here Maps لتغطية أفضل
