# PRD: RideShare (SAAS-054)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary

- **One-liner:** تطبيق لمشاركة الرحلات والنقل التشاركي بين المدن — يربط المسافرين بسائقي المركبات الخاصة للرحلات البعيدة.
- **Problem:** التنقل بين المدن في العالم العربي مكلف وغير منظم. حافلات النقل العام محدودة، وسيارات الأجرة باهظة، ولا توجد منصة موثوقة لمشاركة الرحلات بين المدن. يضطر المسافرون للبحث عن رحلات عبر فيسبوك ومجموعات واتساب بدون ضمانات أو تنظيم.
- **Proposed solution:** Laravel API (إدارة الرحلات والحجوزات والمدفوعات) + React Dashboard (لوحة تحكم للمشرفين) + Flutter App (تطبيق للركاب والسائقين).

## 2. Market & Opportunity

- **Target market:** المسافرون بين المدن في العالم العربي — طلاب، موظفون، عائلات. سائقو سيارات خاصة يبحثون عن مشاركة تكاليف الرحلات. السوق في تزايد مع ارتفاع تكاليف الوقود والمواصلات.
- **Customer segment:** B2C — مسافرون، سائقون (P2P marketplace).
- **Competitor landscape:**
  1. كريم (Careem) — خدمة سيارات داخل المدن بشكل أساسي.
  2. أوبر (Uber) — تركيز على التنقل داخل المدن.
  3. باص (BaaS) — حجوزات حافلات بين المدن.
  4. مجموعات فيسبوك وواتساب — الطريقة الحالية غير المنظمة.
  5. Blablacar — رائد عالمياً لكن حضوره العربي محدود.
- **Differentiation:** تركيز على السوق العربي، دعم عربي كامل، مدفوعات محلية (STC Pay، فوري، كاش)، التحقق من هوية السائقين، نظام تقييم، دعم للنساء (رحلات نسائية فقط)، تكامل مع الخرائط المحلية.

## 3. User Personas

### أساسي: المسافر — أحمد
- **الدور:** طالب جامعي يسافر بين مدينته وجامعته أسبوعياً
- **الأهداف:** حجز رحلة مشاركة بتكلفة منخفضة، معرفة وقت الوصول بدقة، التواصل مع السائق
- **نقاط الألم:** ارتفاع تكاليف المواصلات، عدم وجود رحلات منتظمة، القلق من السلامة، عدم الثقة بالسائقين غير المعروفين

### أساسي: السائق — فيصل
- **الدور:** موظف يسافر بين مدينتين بشكل أسبوعي ويريد مشاركة التكاليف
- **الأهداف:** نشر رحلته اليومية، مشاركة تكاليف الوقود، مقابلة ركاب موثوقين
- **نقاط الألم:** صعوبة إيجاد ركاب، إلغاء اللحظة الأخيرة، التعامل النقدي غير المنظم

### إداري: مشغل النظام — مشرف
- **الدور:** مدير المنصة
- **الأهداف:** مراقبة الرحلات، حل النزاعات، إدارة المدفوعات
- **نقاط الألم:** ضمان الثقة والسلامة، التعامل مع الشكاوى

## 4. Features by Platform

### Laravel API (Backend)

- User management (rider, driver, admin)
- Driver verification (ID, license, vehicle registration)
- Ride creation & management
- Search & book rides
- Booking management (confirm, cancel, no-show)
- Payment processing (card, wallet, cash)
- Rating & review system
- Real-time location sharing (WebSocket)
- SMS & push notifications
- Admin moderation & dispute resolution
- Ride matching algorithm (route proximity)

### React Dashboard (Web)

- Admin dashboard: active rides, user stats, revenue
- User management: verify drivers, suspend accounts
- Ride monitoring: live rides map, booking list
- Payment management: transaction log, settlement reports
- Dispute resolution: complaint tickets, chat logs
- Settings: commission rate, pricing rules, cancellation policy
- Reports: rides per day, revenue, user growth

### Flutter App (Mobile)

- Rider app: search rides, book seat, view driver info, real-time tracking, rate trip
- Driver app: create ride, manage bookings, start trip, navigation, earnings dashboard
- Real-time chat between rider and driver
- In-app wallet for cashless payments
- SOS/emergency button
- Arabic-first Material 3 UI
- Offline ride search (cached results)

## 5. Data Model (MVP)

- **User:** id, name, email, phone, password, role, avatar, verified, status, created_at
- **DriverProfile:** id, user_id, license_number, license_expiry, vehicle_model, vehicle_color, vehicle_plate, vehicle_year, id_verified, vehicle_verified, status
- **Vehicle:** id, driver_id, make, model, year, color, plate_number, seats, ac, luggage_space, photos
- **Ride:** id, driver_id, vehicle_id, from_city, to_city, departure_time, arrival_time, available_seats, price_per_seat, status (scheduled/in_progress/completed/cancelled), notes, recurring_pattern
- **RideStop:** id, ride_id, location_name, lat, lng, stop_order
- **Booking:** id, ride_id, rider_id, seats, status (pending/confirmed/cancelled/completed), total_price, payment_method, booking_date, pickup_location
- **Payment:** id, booking_id, from_user_id, to_user_id, amount, fee, method, status, transaction_date
- **Wallet:** id, user_id, balance, created_at
- **WalletTransaction:** id, wallet_id, type (credit/debit), amount, reference, description, created_at
- **Review:** id, booking_id, from_user_id, to_user_id, rating, comment, created_at
- **RideChat:** id, ride_id, from_user_id, message, created_at
- **Complaint:** id, booking_id, from_user_id, description, status, resolved_by, resolved_at
- **Notification:** id, user_id, title, body, type, is_read, created_at

## 6. API Endpoints (MVP)

- `POST /api/register` — User registration
- `POST /api/login` — Auth
- `GET /api/user` — Current user
- `PUT /api/user` — Update profile
- `POST /api/driver/verify` — Submit verification
- `GET /api/driver/status` — Driver verification status
- `GET /api/rides` — Search rides (from, to, date, seats)
- `POST /api/rides` — Create ride (driver)
- `GET /api/rides/{id}` — Ride details
- `PUT /api/rides/{id}` — Update ride (driver)
- `DELETE /api/rides/{id}` — Cancel ride (driver)
- `GET /api/rides/{id}/bookings` — Ride bookings (driver)
- `POST /api/bookings` — Book a ride (rider)
- `GET /api/bookings/{id}` — Booking details
- `PUT /api/bookings/{id}/status` — Confirm/cancel booking
- `GET /api/payments` — Payment history
- `POST /api/payments/deposit` — Wallet deposit
- `POST /api/payments/release` — Release payment to driver
- `GET /api/wallet` — Wallet balance
- `POST /api/reviews` — Submit review
- `GET /api/reviews/{user_id}` — User reviews
- `POST /api/complaints` — Submit complaint
- `GET /api/notifications` — Notifications
- `PUT /api/notifications/{id}/read` — Mark read
- `GET /api/trips/upcoming` — Upcoming trips
- `GET /api/trips/history` — Trip history

## 7. User Interface (Screen List)

### Dashboard Screens (React)
- Login
- Admin Dashboard: KPIs, active rides map, revenue chart
- Users: user list, driver verification queue, suspend/ban
- Rides: all rides table, filter by status/city
- Bookings: booking list, dispute cases
- Payments: transaction log, settlement, commission report
- Complaints: ticket list, chat viewer, resolution
- Reports: daily/weekly/monthly stats, export
- Settings: commission, pricing, cancellation policy

### Mobile Screens (Flutter)
- Splash / Onboarding
- Rider Home: search bar (from → to → date), recent searches
- Ride Results: list of rides with driver info, departure time, price
- Ride Detail: driver profile, vehicle info, reviews, book button
- Book Ride: select seats, pickup point, payment method
- My Bookings: upcoming, active, past bookings
- Active Trip: real-time map, driver contact, SOS button
- Driver Home: create ride form, my rides, earnings
- Create Ride: route, date/time, price, vehicle, stops
- Driver Ride Detail: booking requests, passenger list, start trip
- Earnings: trip earnings, wallet balance, withdraw
- Profile: edit info, verify documents, settings
- Chat: ride-specific chat with passengers/driver
- Notifications: list of all notifications

### Screen Flow
```
Rider:
  Search → Results → Ride Detail → Book → Confirm → My Bookings → Active Trip (map) → Complete → Review

Driver:
  Create Ride → Publish → Receive Requests → Accept → Start Trip → Complete → Payment Released
```

## 8. Business Model

- **Revenue:** عمولة 15% على كل رحلة مكتملة (من السائق)
- **Pricing:**
  - مجاني للركاب
  - السائقون يدفعون عمولة 15%، أو اشتراك شهري $9.99 بدون عمولة (Pro)
- **Free trial:** 3 رحلات مجانية للسائقين الجدد بدون عمولة
- **Target revenue per driver:** $5-$15/شهر عمولات
- **Additional:** إعلانات مميزة، رحلات عمل (Business account)

## 9. Implementation Plan

- **Phase 1 (Weeks 1-2):** Auth + User roles + Driver verification + Vehicle + Ride CRUD APIs
- **Phase 2 (Weeks 3-4):** Booking flow + Payment (wallet, release) + Reviews + Notifications + WebSocket for chat
- **Phase 3 (Weeks 5-6):** React Dashboard — admin panel, verification queue, reports, settings
- **Phase 4 (Weeks 7-8):** Flutter Rider App — search, book, track, pay, review
- **Phase 5 (Weeks 9-10):** Flutter Driver App — create ride, manage bookings, earnings, navigation integration
- **Phase 6 (Weeks 11-12):** Testing, safety features (SOS, tracking), deployment, launch

## 10. Risk & Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| السلامة وحوادث السير | High | تحقق صارم من السائقين، تأمين على الرحلات، تتبع GPS، زر SOS |
| الثقة بين الركاب والسائقين | High | نظام تقييم متبادل، هويات موثقة، دعم فني 24/7 |
| الإلغاء في اللحظة الأخيرة | Medium | غرامة على الإلغاء المتأخر، نظام حجز مؤكد بالدفع المسبق |
| المنافسة من بلابلاكار وبيكسل | Medium | تركيز على المدن الثانوية والجامعات، دفع محلي، دعم عربي |
| تحديات تنظيمية وقانونية | High | استشارة قانونية، هيكل تشغيلي متوافق مع القوانين المحلية |
