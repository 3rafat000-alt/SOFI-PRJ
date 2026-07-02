# PRD: HotelEase (SAAS-014)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary

**منصة متكاملة لإدارة الفنادق الصغيرة والشقق المفروشة.** توفر HotelEase حلولاً رقمية للحجوزات، التسكين والخرج، الفوترة، وتقييم النزلاء. تستهدف الفنادق الصغيرة والشقق الفندقية التي تبحث عن نظام إدارة (PMS) بسعر مناسب.

- المشكلة: الفنادق الصغيرة تدير الحجوزات يدوياً أو عبر أنظمة معقدة ومكلفة، مما يؤدي إلى حجوزات مزدوجة، أخطاء في الفوترة، وضعف تجربة النزيل.
- الحل: Laravel API + React Dashboard + Flutter App.

## 2. Market & Opportunity

- السوق المستهدف: 30,000+ فندق صغير وشقة مفروشة في الشرق الأوسط وشمال أفريقيا
- الفئة: B2B (فنادق صغيرة، شقق فندقية، موتيلات)
- المنافسون:
  - **Cloudbeds** — PMS عالمي (مكلف للفنادق الصغيرة، $50+/شهر)
  - **Mews** — نظام متقدم (ميزات زائدة عن الحاجة للفنادق الصغيرة)
  - **Oyo** — حل حجز فقط وليس نظام إدارة متكامل
- التمايز: تسعير مخفض للفنادق الصغيرة، دعم كامل للعربية، واجهة مبسطة

## 3. User Personas

### شخص أساسي: مدير الفندق
- الاسم: كريم
- الدور: يدير فندقاً صغيراً من 20 غرفة مع 5 موظفين
- الأهداف: إدارة الحجوزات اليومية، متابعة تسكين/خرج النزلاء، مراقبة الإشغال
- نقاط الألم: اعتماد على إكسل وورق، صعوبة تتبع حجوزات المنصات (Booking, Airbnb)

### شخص أساسي: موظف الاستقبال
- الاسم: سلمى
- الدور: تسكين النزلاء ومعالجة الدفع
- الأهداف: تسريع إجراءات تسكين النزلاء، طباعة الفواتير بدقة
- نقاط الألم: بطء النظام، تكرار إدخال المعلومات، صعوبة التعامل مع حالات الدفع

### Admin: مشرف المنصة
- إدارة الفنادق المسجلة، مراقبة العمولات، إدارة حسابات المسؤولين.

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Hotel, Room, RoomType, Booking, Guest, Invoice, Payment, Review
- RESTful CRUD for all PMS resources
- Channel manager integration (Booking.com, Airbnb, Agoda) via API
- Rate management: dynamic pricing, seasonal rates, discounts
- Email/SMS notification engine (confirmation, reminders, post-stay)
- Payment processing (card, cash, bank transfer)

### React Dashboard (Web)
- Dashboard: occupancy rate chart, revenue KPI, booking calendar
- Booking calendar: drag-and-drop reservations, color-coded by status
- Room management: types, amenities, photos, pricing
- Guest management: profile, stay history, preferences, notes
- Front desk: check-in/out workflow, document scanner (ID/passport)
- Invoice generator: auto-calculate nights, taxes, extras
- Channel manager: sync rates and availability across OTA platforms
- Reports: occupancy, RevPAR, ADR, revenue by month

### Flutter App (Mobile)
- Housekeeping view: room status list (clean/dirty/out-of-order)
- Mobile check-in: scan guest ID, capture signature, assign room
- Push notifications: upcoming arrivals, VIP alerts, maintenance
- Guest communication: in-app messaging, service requests
- Room status updates: staff marks clean/inspected via phone
- Offline mode: view today's arrivals and room status without internet

## 5. Data Model (MVP)

### Hotel
- id, name, address, city, country, phone, email, license_number, star_rating, total_rooms, settings (JSON), created_at

### RoomType
- id, hotel_id (FK), name, base_price, max_guests, amenities (JSON), total_rooms, created_at

### Room
- id, hotel_id (FK), room_type_id (FK), room_number, floor, status (available/occupied/maintenance), created_at

### Booking
- id, hotel_id (FK), room_id (FK), guest_id (FK), check_in, check_out, status (pending/confirmed/checked-in/checked-out/cancelled), source (direct/booking/airbnb/agoda), total_amount, paid_amount, notes, created_at

### Guest
- id, name, phone, email, id_number, passport_number, nationality, is_vip, preferences (JSON), created_at

### Invoice
- id, booking_id (FK), issue_date, line_items (JSON), subtotal, tax, total, status (draft/paid/void), payment_method, created_at

### Payment
- id, invoice_id (FK), amount, method, transaction_id, paid_at, created_at

### Review
- id, booking_id (FK), guest_id (FK), rating, comment, created_at

### User
- id, name, email, password, role (admin/receptionist/housekeeping/manager), hotel_id (FK), created_at

## 6. API Endpoints (MVP)

```
POST   /api/auth/login
GET    /api/auth/me

GET    /api/hotels/settings
PUT    /api/hotels/settings

GET    /api/room-types
POST   /api/room-types
PUT    /api/room-types/{id}
DELETE /api/room-types/{id}

GET    /api/rooms
POST   /api/rooms
PUT    /api/rooms/{id}/status

GET    /api/bookings
POST   /api/bookings
GET    /api/bookings/{id}
PUT    /api/bookings/{id}/check-in
PUT    /api/bookings/{id}/check-out
PUT    /api/bookings/{id}/cancel
GET    /api/bookings/calendar?from=&to=

GET    /api/guests
POST   /api/guests
GET    /api/guests/{id}
PUT    /api/guests/{id}

GET    /api/invoices
POST   /api/invoices
GET    /api/invoices/{id}
PUT    /api/invoices/{id}/pay

POST   /api/reviews
GET    /api/reviews?hotel_id=

GET    /api/reports/occupancy?from=&to=
GET    /api/reports/revenue?from=&to=
```

## 7. User Interface (Screen List)

### Dashboard Screens (React)
1. Login
2. Dashboard - occupancy gauge, revenue chart, today's arrivals/departures
3. Booking Calendar - monthly/weekly with drag-and-drop
4. New Booking - guest search, room picker, date selector, rate calculator
5. Check-in Wizard - guest info, ID scan, room assignment, payment
6. Check-out Wizard - invoice review, payment, room condition
7. Room Status Board - visual grid of all rooms with status colors
8. Guest Directory - searchable with stay history
9. Invoice Manager - list with paid/unpaid filters, print
10. Channel Manager - OTA sync status, rate comparison
11. Reports - RevPAR, ADR, occupancy % by month
12. Settings - hotel profile, room types, user roles

### Mobile Screens (Flutter)
1. Login
2. Dashboard - today's summary (arrivals, departures, occupancy)
3. Room Status - floor-by-floor room grid, tap to update
4. Booking Alerts - upcoming arrivals push notification
5. Guest Lookup - search, profile view
6. Room Checklist - mark clean/inspect/maintenance
7. Profile - staff info, shift schedule

### Screen Flow
Dashboard -> Booking Calendar -> New Booking -> Guest Info -> Confirm -> Check-in

## 8. Business Model

- **الباقة الأساسية**: $29/شهر (حتى 20 غرفة، نظام أساسي)
- **الباقة الاحترافية**: $69/شهر (حتى 50 غرفة، Channel Manager، تقارير)
- **باقة المؤسسات**: $129/شهر (غير محدود، API، دعم ممتاز)
- فترة تجربة مجانية: 14 يوماً
- MRR المستهدف لكل عميل: $29-$129

## 9. Implementation Plan

- Phase 1 (Weeks 1-2): Laravel API - Auth, Hotel/Room/Booking CRUD
- Phase 2 (Weeks 3-4): Guest management, invoices, payment processing
- Phase 3 (Weeks 5-6): React Dashboard - All PMS screens, booking calendar, reports
- Phase 4 (Weeks 7-8): Flutter App - Housekeeping, mobile check-in, notifications
- Phase 5 (Weeks 9-10): OTA channel API integrations, load testing, deployment

## 10. Risk & Mitigation

- **مخاطرة تقنية**: تكامل API مع منصات OTA المختلفة (Booking, Airbnb) يتطلب صيانة مستمرة
  - التخفيف: طبقة تجريد OTAs، اختبارات تكامل آلية أسبوعية
- **مخاطرة سوقية**: صعوبة إقناع الفنادق الصغيرة بالتخلي عن الأنظمة اليدوية
  - التخفيف: فترة تجريبية مجانية 30 يوماً، دعم تأهيل مجاني
- **مخاطرة تشغيلية**: اعتماد الفندق على المنصة في عملياته اليومية (حجوزات، تسكين)
  - التخفيف: وجودة عالية (SLA 99.9%)، نسخ احتياطي يومي، وضع عدم اتصال محدود
