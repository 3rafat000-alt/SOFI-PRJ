# PRD: ToolRental (SAAS-094)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary

**منصة لتأجير الأدوات والمعدات: حجوزات، توصيل، صيانة.** تهدف ToolRental إلى رقمنة قطاع تأجير الأدوات (معدات البناء، أدوات الحدائق، معدات التنظيف، أدوات المطبخ) من خلال منصة تربط المستأجرين بالمؤجرين وتدير دورة التأجير بأكملها.

- المشكلة: محلات تأجير الأدوات تعتمد على إدارة يدوية (سجلات ورقية، مكالمات) مما يسبب فقدان المخزون، صعوبة تتبع الأدوات المؤجرة، وتأخير في الصيانة. الأفراد والمقاولون يجدون صعوبة في إيجاد الأدوات المناسبة للاستئجار.
- الحل: Laravel API + React Dashboard + Flutter App.

## 2. Market & Opportunity

- السوق المستهدف: 5,000+ محل تأجير أدوات في الشرق الأوسط، 500,000+ مقاول وصاحب منزل
- الفئة: B2B + B2C (محلات تأجير، مقاولون، أفراد)
- المنافسون:
  - **RentSher** — تطبيق تأجير معدات (أمريكي، لا يدعم العربية).
  - **EquipmentShare** — تأجير معدات ثقيلة (مكلف، B2B فقط).
  - **Ferjan** — منصة تأجير سعودية ناشئة (تغطية محدودة).
  - **السوق المفتوح** — إعلانات تأجير عامة (ليست متخصصة).
- التمايز: منصة شاملة (إدارة مخزون + حجوزات + توصيل + صيانة + مدفوعات)، متخصصة للسوق العربي، تطبيق موبايل سهل للاستئجار الفوري.

## 3. User Personas

### شخص أساسي: صاحب محل تأجير أدوات
- الاسم: محمد
- الدور: يمتلك محل تأجير معدات بناء وصيانة في جدة
- الأهداف: رقمنة المخزون، أتمتة الحجوزات، تتبع الأدوات المؤجرة ومتابعة الصيانة
- نقاط الألم: فقدان الأدوات، عدم معرفة مكان الأداة، صعوبة تحصيل الإيجار، تأخير الصيانة

### شخص أساسي: مقاول يحتاج أدوات
- الاسم: سامي
- الدور: مقاول بناء يحتاج معدات بشكل مؤقت لكل مشروع
- الأهداف: إيجاد الأدوات المطلوبة بسرعة، مقارنة الأسعار، حجز وتوصيل في نفس اليوم
- نقاط الألم: شراء أدوات باهظة الثمن لمشروع واحد، صعوبة إيجاد أدوات متخصصة

### Admin: مدير المنصة
- مراقبة المعاملات، إدارة خطط الاشتراك، حل النزاعات.

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Tool, Category, RentalShop, Rental, Customer, Delivery, Maintenance, Payment, DamageReport
- RESTful CRUD for all resources
- Role-based auth (Admin, ShopOwner, Customer, DeliveryDriver)
- Inventory management — stock tracking, availability status, condition tracking
- Rental engine — date range availability check, pricing calculation (daily/weekly/monthly)
- Damage deposit — hold on card, refund on return
- Delivery tracking — driver assignment, status updates, estimated arrival
- Maintenance tracking — service history, next service date, cost tracking
- Fine/damage system — photo evidence, damage assessment, billing
- Notification engine: email, push, SMS (rental confirmation, return reminder, overdue)

### React Dashboard (Web)
- Shop dashboard: inventory overview, active rentals, revenue, overdue items
- Tool management: add/edit tools, photos, pricing, availability calendar
- Rental management: create rentals, track returns, calculate late fees
- Customer management: rental history, deposits, outstanding balances
- Delivery management: assign drivers, track deliveries, delivery zones
- Maintenance scheduler: service intervals, maintenance log, cost tracking
- Reports: monthly revenue, top rented tools, customer analytics, utilization rate

### Flutter App (Mobile)
- Customer app: browse tools by category, search, filter by price/availability
- Tool detail: photos, specifications, price (daily/weekly), availability
- Rental flow: select dates → add to cart → checkout → payment
- Real-time delivery tracking with driver location
- Rental management: active rentals, return instructions, extend rental
- Report damage: photo upload, description, submit claim
- Push notifications: rental confirmation, delivery ETA, return reminder, overdue alert

## 5. Data Model (MVP)

### Tool
- id, shop_id (FK), category_id (FK), name, description, brand, model, serial_number, purchase_price, rental_price_daily, rental_price_weekly, rental_price_monthly, deposit_amount, status (available/rented/maintenance/retired), condition, photos (JSON), created_at

### Category
- id, name (AR/EN), icon, parent_id (FK), created_at

### RentalShop
- id, owner_id (FK), name, address, city, phone, commission_rate, delivery_zones (JSON), status (active/inactive), created_at

### Rental
- id, tool_id (FK), customer_id (FK), shop_id (FK), start_date, end_date, duration_type (daily/weekly/monthly), rental_price, deposit_amount, total_amount, status (active/returned/overdue/cancelled), late_fee, returned_at, created_at

### Customer
- id, user_id (FK), phone, id_number, rental_count, total_spent, created_at

### Delivery
- id, rental_id (FK), driver_id (FK), type (pickup/delivery), address, status (pending/dispatched/in_transit/delivered), estimated_arrival, actual_arrival, created_at

### Maintenance
- id, tool_id (FK), description, type (scheduled/repair), cost, performed_by, performed_at, next_service_date, notes, created_at

### Payment
- id, rental_id (FK), amount, type (rental/deposit/fine/refund), method, status, transaction_id, created_at

### DamageReport
- id, rental_id (FK), description, photos (JSON), cost, status (pending/approved/rejected), created_at

## 6. API Endpoints (MVP)

```
POST   /api/auth/login
POST   /api/auth/register
GET    /api/auth/me

GET    /api/categories
GET    /api/tools
GET    /api/tools/{id}
GET    /api/tools/search?category=&city=&price_min=&price_max=
GET    /api/tools/{id}/availability?start=&end=

POST   /api/rentals
GET    /api/rentals
GET    /api/rentals/{id}
PUT    /api/rentals/{id}/extend
PUT    /api/rentals/{id}/return

POST   /api/rentals/{id}/damage
GET    /api/deliveries/active
POST   /api/deliveries

GET    /api/shops/{id}/dashboard
GET    /api/shops/{id}/tools
POST   /api/shops/tools

GET    /api/maintenance/schedule
POST   /api/maintenance

POST   /api/payments/checkout
POST   /api/payments/webhook

GET    /api/notifications
```

## 7. User Interface (Screen List)

### Dashboard Screens (React)
1. Login / Register (Shop Owner or Customer)
2. Shop Dashboard — active rentals, revenue, overdue alerts, inventory summary
3. Inventory Management — tool list, availability, status, photos
4. Rental Calendar — timeline view of current and upcoming rentals
5. Rental Detail — customer info, tool, dates, payment, delivery
6. Customer Directory — rental history, deposit tracking
7. Delivery Management — active deliveries, driver assignment, tracking
8. Maintenance Scheduler — upcoming service, maintenance log
9. Financial Reports — revenue, deposits, fines, monthly trends
10. Settings — shop profile, delivery zones, pricing rules

### Mobile Screens (Flutter)
1. Home — search bar, categories grid, nearby shops
2. Category Browse — tool list with filter and sort
3. Tool Detail — photos, specs, price, availability calendar
4. Rental Form — select dates, delivery address, insurance
5. Cart & Checkout — rental summary, payment
6. My Rentals — active, upcoming, returned
7. Delivery Tracking — live map with driver location
8. Damage Report — photo upload, description
9. Notifications — reminders, delivery updates
10. Profile — personal details, payment methods

### Screen Flow
```
Customer: Browse Category → Select Tool → Check Availability → Book → Pay → Track Delivery → Use → Return → Deposit Refund
Shop Owner: Dashboard → Manage Inventory → Approve Bookings → Assign Delivery → Track Returns → Process Maintenance
```

## 8. Business Model

- **للمؤجرين**: $39/شهر (حتى 50 أداة، حجوزات غير محدودة)
- **باقة المؤجرين الاحترافية**: $79/شهر (200 أداة، توصيل، تحليلات متقدمة)
- **باقة المؤسسات**: $149/شهر (غير محدود أدوات، API، تقارير مخصصة)
- **المستأجرون**: مجاني للتصفح والبحث، يدفعون رسوم التأجير فقط
- **عمولة المنصة**: 5% من قيمة كل تأجير
- فترة تجربة مجانية للمؤجرين: 14 يوماً
- MRR المستهدف لكل مؤجر: $39-$149

## 9. Implementation Plan

- Phase 1 (Weeks 1-2): Laravel API — Auth, Tool/Category/RentalShop CRUD, Sanctum roles
- Phase 2 (Weeks 3-4): Laravel API — Rental engine, availability check, delivery tracking, payment integration
- Phase 3 (Weeks 5-6): React Dashboard — Shop dashboard, inventory, rental calendar, delivery management
- Phase 4 (Weeks 7-8): Flutter App — Customer browse, rental flow, delivery tracking, damage reporting
- Phase 5 (Weeks 9-10): Maintenance tracking, fine system, Arabic localization, testing, deploy

## 10. Risk & Mitigation

- **مخاطرة تشغيلية**: تلف أو فقدان الأدوات المؤجرة — التخفيف: إيداع تأميني، نظام توثيق بالصور، غرامات تأخير.
- **مخاطرة لوجستية**: تأخير التوصيل والاستلام — التخفيف: نظام تتبع بالخريطة، جدولة ذكية للمناديب.
- **مخاطرة سوقية**: صعوبة إقناع محلات التأجير بالرقمنة — التخفيف: واجهة بسيطة، دعم فني، فترة تجريبية.
- **مخاطرة قانونية**: المسؤولية عن الحوادث والأضرار — التخفيف: عقود تأجير واضحة، تأمين اختياري على الأداة.
