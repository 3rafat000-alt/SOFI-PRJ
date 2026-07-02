# PRD: CourierMgt (SAAS-098)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary

**منصة متكاملة لإدارة شركات الطرود البريدية: تتبع، توصيل، دفع.** تهدف CourierMgt إلى رقمنة إدارة شركات التوصيل والبريد السريع بالكامل — من استلام الطرود وتوزيعها على المندوبين إلى تتبع التوصيل المباشر وإدارة المدفوعات.

- المشكلة: شركات التوصيل الصغيرة والمتوسطة تعاني من إدارة الشحنات يدوياً، صعوبة تتبع المندوبين، شكاوى العملاء من التأخير، وضعف تحصيل المدفوعات.
- الحل: Laravel API + React Dashboard + Flutter App.

## 2. Market & Opportunity

- السوق المستهدف: 2,000+ شركة توصيل صغيرة ومتوسطة في الشرق الأوسط، 100,000+ مندوب توصيل
- الفئة: B2B (شركات توصيل، متاجر إلكترونية، مندوبون مستقلون)
- المنافسون:
  - **Winged** — نظام إدارة توصيل سعودي (ناشئ، ميزات محدودة).
  - **Shipa** — منصة شحن (تركز على الشحن الدولي بين الشركات).
  - **Onfleet** — نظام إدارة توصيل عالمي (مكلف، $69-149/شهر، إنجليزي).
  - **Bringg** — حل توصيل مؤسسي (مكلف جداً، $500+/شهر).
- التمايز: حل متكامل بسعر مناسب للشركات الناشئة والصغيرة، دعم عربي كامل، تتبع مباشر للعملاء، إدارة مناطق التوصيل والجدولة الذكية.

## 3. User Personas

### شخص أساسي: مدير شركة توصيل
- الاسم: خليل
- الدور: يدير شركة توصيل صغيرة في عمّان مع 10 مندوبين
- الأهداف: توزيع الشحنات على المندوبين بكفاءة، تتبع التوصيل لحظة بلحظة، تحصيل المدفوعات
- نقاط الألم: صعوبة توزيع الشحنات يدوياً، عدم معرفة موقع المندوبين، تأخير التوصيل

### شخص أساسي: مندوب توصيل
- الاسم: رامي
- الدور: مندوب توصيل يستخدم دراجة نارية
- الأهداف: معرفة مسار التوصيل الأمثل، تأكيد التسليم بسرعة، تسجيل المدفوعات النقدية
- نقاط الألم: ضياع العنوان، الاتصال المستمر بالعميل، حمل النقود

### شخص أساسي: صاحب متجر إلكتروني (عميل شركة التوصيل)
- الاسم: لينا
- الدور: تدير متجر ملابس أونلاين، تشحن 30-50 طرداً يومياً
- الأهداف: شحن سريع، تتبع للعملاء، تقارير شهرية
- نقاط الألم: عدم وجود تكامل مع متجرها، عدم قدرة عملائها على التتبع

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Company, Driver, Package, Route, DeliveryAttempt, Customer, Payment, Zone, Merchant, PackageScan
- RESTful CRUD for all resources
- Role-based auth (Admin, CompanyManager, Driver, Merchant, Customer)
- Package intake — barcode/QR generation, weight, dimensions, pickup address, delivery address
- Route optimization — zone-based assignment, distance calculation, suggested order
- Real-time driver tracking — GPS coordinates, status updates, geofencing
- Delivery workflow — out for delivery → arrived → delivered / failed → photo proof → signature
- COD (Cash on Delivery) management — amounts collected, reconciliation, driver settlement
- Merchant integration — API/webhook for order import, tracking widget
- Customer tracking portal — real-time package location, estimated delivery time
- Proof of delivery — photo, digital signature, timestamp
- Notification engine: SMS (customer updates), push (driver assignments), email (daily reports)

### React Dashboard (Web)
- Operations dashboard: active deliveries, driver status map, today's metrics
- Package management: intake form, batch creation, label printing, sorting
- Route management: zone creation, driver assignment, route optimization
- Driver management: profiles, performance metrics, earnings, shift management
- Live tracking map: all drivers on map with status colors, package info popup
- Merchant portal: import orders, tracking links, delivery reports, billing
- COD reconciliation: daily collection vs delivery, driver settlement, bank deposits
- Customer management: delivery history, addresses, preferences
- Reports: delivery success rate, on-time rate, driver productivity, revenue per zone

### Flutter App (Mobile)
- Driver app: login → view assigned packages → optimized route → navigate
- Package scanning: scan barcode → confirm pickup → mark delivered
- Navigation: Google Maps/Waze integration, address search
- Proof of delivery: photo capture, customer signature, notes
- COD collection: record cash/ card payment, issue digital receipt
- Customer communication: call or text customer with one tap
- Status updates: auto-status based on geofencing (arrived/delivered)
- Daily summary: packages delivered, COD collected, earnings
- Offline mode: download route, work without internet, sync when online

## 5. Data Model (MVP)

### Company
- id, name, license_number, address, phone, zones (JSON), pricing_model, created_at

### Driver
- id, user_id (FK), company_id (FK), vehicle_type (motorcycle/car/van/truck), license_plate, vehicle_color, status (available/busy/offline), current_location (JSON), total_deliveries, rating, created_at

### Package
- id, company_id (FK), merchant_id (FK), driver_id (FK), tracking_number, weight, dimensions (JSON), pickup_address, delivery_address, delivery_lat, delivery_lng, recipient_name, recipient_phone, cod_amount, status (pending/picked_up/in_transit/out_for_delivery/delivered/failed/returned), notes, created_at

### Route
- id, driver_id (FK), date, packages (JSON — ordered list), status (pending/active/completed), started_at, completed_at, created_at

### DeliveryAttempt
- id, package_id (FK), driver_id (FK), attempted_at, status (successful/failed), failure_reason, photo_url, signature_url, notes, created_at

### Merchant
- id, user_id (FK), company_id (FK), store_name, integration_type (manual/api/webhook), monthly_shipments, pricing_tier, created_at

### Payment (COD)
- id, driver_id (FK), company_id (FK), amount, type (collected/settled/returned), status (pending/cleared/disputed), settled_at, created_at

### Zone
- id, company_id (FK), name, boundaries (JSON — polygon), driver_count, created_at

### PackageScan
- id, package_id (FK), driver_id (FK), scan_type (pickup/arrived_at_hub/out_for_delivery/delivered), location (JSON), scanned_at, created_at

## 6. API Endpoints (MVP)

```
POST   /api/auth/login
GET    /api/auth/me

POST   /api/packages
POST   /api/packages/batch
GET    /api/packages
GET    /api/packages/{id}
GET    /api/packages/{id}/track
PUT    /api/packages/{id}/assign

GET    /api/drivers
POST   /api/drivers
GET    /api/drivers/{id}
PUT    /api/drivers/{id}/location
GET    /api/drivers/{id}/route

POST   /api/routes/generate
GET    /api/routes
GET    /api/routes/{id}

POST   /api/delivery-attempts
PUT    /api/delivery-attempts/{id}/proof

GET    /api/merchants/dashboard
POST   /api/merchants/import-orders

GET    /api/tracking/public/{tracking_number}

GET    /api/cod/daily
POST   /api/cod/settle
GET    /api/cod/reconciliation

GET    /api/zones
POST   /api/zones

GET    /api/dashboard/company
GET    /api/dashboard/driver
GET    /api/dashboard/merchant

POST   /api/webhooks/{company_id}/order
```

## 7. User Interface (Screen List)

### Dashboard Screens (React)
1. Login / Register
2. Company Dashboard — live map, deliveries today, on-time rate, COD summary
3. Package Management — intake form, batch upload (CSV), label printing
4. Route Planning — zone map, drag-drop driver assignment, auto-optimize
5. Live Tracking — map view of all drivers with package info popups
6. Driver Management — profiles, performance, earnings, shift calendar
7. Merchant Portal — order import, tracking links, monthly billing
8. COD Management — daily collection, driver settlement reports, bank deposits
9. Customer Tracking Portal — public tracking page (real-time)
10. Reports — delivery KPIs, driver rankings, zone performance

### Mobile Screens (Flutter)
1. Driver Login → Today's Route (list)
2. Route Overview — package list in optimized order
3. Package Detail — address, contact, special instructions, collect COD flag
4. Navigation — Google Maps directions to next stop
5. Delivery Screen — photo capture, signature pad, COD input, status
6. Failed Delivery — reason selection (customer not available/wrong address/rejected)
7. Daily Summary — delivered count, failed, COD collected, earnings
8. Chat — quick message to dispatcher
9. Profile — shift status, vehicle info

### Screen Flow
```
Merchant: Create Order → Import to System → Print Label → Hand to Courier
Company: Receive Package → Sort → Assign Driver → Optimize Route → Monitor
Driver: Accept Route → Navigate → Deliver → Photo + Signature → Complete → Next Stop
Customer: Receive SMS → Track Online → Receive Package → Confirm
```

## 8. Business Model

- **باقة البداية**: $49/شهر (مندوب واحد، 500 شحنة/شهر)
- **باقة النمو**: $99/شهر (حتى 5 مناديب، 2,000 شحنة/شهر، تقارير)
- **باقة الاحترافية**: $199/شهر (غير محدود مناديب وشحنات، API، خرائط حية)
- **رسوم إضافية**: $0.10 لكل شحنة للتتبع المباشر وتحليلات متقدمة
- فترة تجربة مجانية: 14 يوماً
- MRR المستهدف لكل شركة: $49-$199

## 9. Implementation Plan

- Phase 1 (Weeks 1-2): Laravel API — Auth, Package/Driver/Company CRUD, Sanctum roles
- Phase 2 (Weeks 3-4): Laravel API — Route optimization, real-time tracking, COD, merchant integration
- Phase 3 (Weeks 5-6): React Dashboard — Operations dashboard, live map, route planning, merchant portal
- Phase 4 (Weeks 7-8): Flutter App — Driver app with navigation, delivery flow, proofs, offline mode
- Phase 5 (Weeks 9-10): Tracking portal, merchant webhooks, COD reconciliation, Arabic localization

## 10. Risk & Mitigation

- **مخاطرة تقنية**: التحديث الفوري لموقع المندوبين — التخفيف: استخدام WebSockets لتحديث الموقع، وضع عدم الاتصال مع المزامنة.
- **مخاطرة تشغيلية**: اعتماد المندوبين على هواتفهم الخاصة — التخفيف: التطبيق خفيف، يعمل على أندرويد منخفض المواصفات، خاصية توفير البطارية.
- **مخاطرة مالية**: صعوبة تسوية المدفوعات النقدية — التخفيف: نظام تسوية يومي، تتبع COD بالفواتير، تقارير مطابقة.
- **مخاطرة سوقية**: منافسة من الخدمات المجانية (واتساب) — التخفيف: قيمة مضافة بالتوجيه الذكي والتتبع المباشر والتقارير.
