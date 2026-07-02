# PRD: HallBooking (SAAS-093)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary

**منصة متكاملة لإدارة حجوزات قاعات المناسبات والأفراح.** تهدف HallBooking إلى رقمنة إدارة قاعات الاحتفالات بالكامل — من الحجز والدفع إلى التجهيزات والعقود وإدارة الموردين.

- المشكلة: مالكو قاعات المناسبات يعتمدون على الحجوزات اليدوية (مكالمات هاتفية، دفتر مواعيد) مما يسبب حجوزات مزدوجة، نسيان المواعيد، وصعوبة في إدارة التجهيزات والموردين.
- الحل: Laravel API + React Dashboard + Flutter App.

## 2. Market & Opportunity

- السوق المستهدف: 10,000+ قاعة مناسبات في الشرق الأوسط، 50,000+ مورد ومزود خدمات
- الفئة: B2B (مالكو قاعات، موردو خدمات، منسقو حفلات)
- المنافسون:
  - **Eventtus** — منصة أحداث عامة (ليست متخصصة للقاعات).
  - **مكتبة.كوم** — حجوزات قاعات (تغطية محدودة لوظائف السعودية).
  - **Peerspace** — حجوزات مساحات عالمية (لا تدعم المناسبات العربية).
  - **إعجاز** — تنظيم حفلات (ليس نظام إدارة قاعات).
- التمايز: نظام إدارة شامل (حجوزات + عقود + موردين + مدفوعات)، دعم العروض والتجهيزات، تطبيق موبايل للعملاء لتصفح القاعات والحجز.

## 3. User Personas

### شخص أساسي: مالك قاعة مناسبات
- الاسم: فهد
- الدور: يمتلك قاعتي أفراح في الرياض
- الأهداف: رقمنة الحجوزات، منع الحجوزات المزدوجة، إدارة التجهيزات والموردين
- نقاط الألم: ضياع الحجوزات في المكالمات، صعوبة تنسيق مواعيد التجهيز، عدم وضوح التقويم

### شخص أساسي: عميل يبحث عن قاعة
- الاسم: منال
- الدور: عروس تبحث عن قاعة أفراح مناسبة
- الأهداف: تصفح القاعات، مقارنة الأسعار والتجهيزات، حجز بسهولة ودفع آمن
- نقاط الألم: قنوات التواصل محدودة، صعوبة معرفة التوفر، عدم القدرة على مقارنة الخيارات

### Admin: مشرف القاعة (موظف الاستقبال)
- إدارة الحجوزات اليومية، تأكيد المواعيد، التنسيق مع الموردين.

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Hall, Booking, Customer, Service, Vendor, Contract, Payment, Availability, Equipment
- RESTful CRUD for all resources
- Role-based auth (Admin, HallOwner, Staff, Customer)
- Calendar engine — availability management, conflict detection, recurring slots
- Contract generation — dynamic contract templates with terms and pricing
- Vendor management — catering, decorations, photography, music booking
- Equipment inventory — tables, chairs, sound system, lighting tracking
- Payment processing — deposit, installment, full payment, refund
- Notification engine: email, SMS, push (booking confirmation, reminders, payment due)

### React Dashboard (Web)
- Hall owner dashboard: calendar view with bookings, revenue analytics, occupancy rate
- Booking management: create/edit/cancel bookings, conflict checking
- Customer management: contact history, booking history, preferences
- Service packages: create tiered packages (basic/silver/gold) with pricing
- Vendor directory: manage approved vendors, service agreements
- Financial reports: monthly revenue, deposit tracking, outstanding payments
- Contract templates: customize terms and conditions, digital signature

### Flutter App (Mobile)
- Customer app: browse halls, view photos, check availability, book, pay
- Hall gallery — photo gallery with virtual tour option
- Real-time availability calendar
- Booking request with customization form (guest count, services needed)
- Secure payment (deposit/full)
- Booking management — view upcoming bookings, reschedule/cancel
- Push notifications — booking confirmation, reminders, special offers
- Vendor showcase — explore available vendors per hall

## 5. Data Model (MVP)

### Hall
- id, owner_id (FK), name, description, capacity_min, capacity_max, price_per_hour, price_full_day, address, city, amenities (JSON), photos (JSON), status (active/inactive), created_at

### Booking
- id, hall_id (FK), customer_id (FK), event_type (wedding/party/corporate), guest_count, booking_date, start_time, end_time, total_amount, deposit_amount, status (pending/confirmed/completed/cancelled), contract_signed, notes, created_at

### Customer
- id, user_id (FK), phone, preferred_contact_method, total_bookings, total_spent, created_at

### Service
- id, hall_id (FK), name, description, price, category (catering/decoration/photography/music), created_at

### Vendor
- id, name, service_type, phone, email, contract_terms, rating, status (active/inactive), created_at

### BookingService
- id, booking_id (FK), service_id (FK), vendor_id (FK), price, status (pending/confirmed/completed)

### Contract
- id, booking_id (FK), contract_text, terms (JSON), signed_by_customer_at, signed_by_owner_at, created_at

### Payment
- id, booking_id (FK), amount, type (deposit/final/refund), method (card/transfer/cash), status, transaction_id, created_at

## 6. API Endpoints (MVP)

```
POST   /api/auth/login
POST   /api/auth/register
GET    /api/auth/me

GET    /api/halls
GET    /api/halls/{id}
POST   /api/halls
PUT    /api/halls/{id}
GET    /api/halls/{id}/availability?date=
GET    /api/halls/{id}/services

POST   /api/bookings
GET    /api/bookings
GET    /api/bookings/{id}
PUT    /api/bookings/{id}/status
GET    /api/bookings/calendar?start=&end=

POST   /api/bookings/{id}/contract
PUT    /api/bookings/{id}/contract/sign
POST   /api/bookings/{id}/payment
GET    /api/bookings/{id}/payments

GET    /api/services
POST   /api/services
PUT    /api/services/{id}

GET    /api/vendors
POST   /api/vendors
PUT    /api/vendors/{id}

GET    /api/dashboard/owner    (revenue, occupancy, upcoming)
GET    /api/dashboard/admin    (platform metrics)
```

## 7. User Interface (Screen List)

### Dashboard Screens (React)
1. Login / Register (hall owner or customer)
2. Owner Dashboard — calendar heatmap, booking stats, revenue chart
3. Hall Management — add/edit halls, gallery, pricing, amenities
4. Booking Calendar — monthly/weekly/daily view with color-coded bookings
5. Booking Detail — customer info, services, payment status, contract
6. Customer List — searchable directory with booking history
7. Service Packages — CRUD for packages and pricing
8. Vendor Management — add/edit vendors, contracts
9. Financial Reports — revenue, deposits, monthly comparison
10. Settings — company profile, contract templates, payment gateway

### Mobile Screens (Flutter)
1. Home — featured halls, search by city/date/capacity
2. Hall List — filter by capacity, price, amenities
3. Hall Detail — gallery, pricing, availability calendar
4. Booking Form — date/time, guest count, services selection
5. Payment — deposit or full payment
6. My Bookings — upcoming and past bookings
7. Booking Detail — status, services, contract, payment
8. Notifications — reminders, confirmations
9. Profile — personal info, preferences

### Screen Flow
```
Customer: Browse → Filter → Select Hall → Check Availability → Select Services → Book → Pay → Confirm → Receive Reminder
Owner: Login → Dashboard → Manage Calendar → Approve Bookings → Coordinate Vendors → Follow Up
```

## 8. Business Model

- **باقة القاعة الأساسية**: $29/شهر (قاعة واحدة، 50 حجز/شهر)
- **باقة القاعة الاحترافية**: $59/شهر (3 قاعات، غير محدود حجوزات، عقود رقمية)
- **باقة المؤسسات**: $99/شهر (غير محدود قاعات، تقارير متقدمة، API)
- **رسوم الحجز من العملاء**: 5% من قيمة كل حجز
- فترة تجربة مجانية: 14 يوماً
- MRR المستهدف لكل قاعة: $29-$99

## 9. Implementation Plan

- Phase 1 (Weeks 1-2): Laravel API — Auth, Hall/Customer/Booking CRUD, Sanctum roles
- Phase 2 (Weeks 3-4): Laravel API — Calendar engine, service/vendor management, payment integration
- Phase 3 (Weeks 5-6): React Dashboard — Hall owner dashboard, booking calendar, financial reports
- Phase 4 (Weeks 7-8): Flutter App — Customer hall browsing, booking flow, payment, notifications
- Phase 5 (Weeks 9-10): Contract generation, digital signatures, Arabic localization, testing

## 10. Risk & Mitigation

- **مخاطرة تقنية**: إدارة التقويم المتزامن وتعارض الحجوزات — التخفيف: استخدام قاعدة بيانات مع قفل متفائل (optimistic locking) وكشف التعارض الفوري.
- **مخاطرة سوقية**: تفضيل أصحاب القاعات للطرق التقليدية — التخفيف: واجهة بسيطة، دعم فني عبر الهاتف، فترة تجربة مجانية.
- **مخاطرة تشغيلية**: التعامل مع الموردين الخارجيين — التخفيف: نظام تقييم للموردين، عقود موحدة، ضمانات.
- **مخاطرة قانونية**: عقود الحجوزات والإلغاء — التخفيف: قوالب عقود قانونية معتمدة، سياسة إلغاء واضحة.
