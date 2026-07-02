# PRD: AutoMarket (SAAS-100)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary

**سوق السيارات المستعملة الرقمي: إعلانات، معاينة، توثيق عقود.** تهدف AutoMarket إلى إنشاء منصة متكاملة لبيع وشراء السيارات المستعملة تتجاوز حدود الإعلانات التقليدية — تشمل معاينة افتراضية، توثيق العقود إلكترونياً، وخدمات الضمان والتمويل.

- المشكلة: بيع وشراء السيارات المستعملة يعاني من الاحتيال، إعلانات مضللة، صعوبة التحقق من حالة السيارة، إجراءات ورقية معقدة لنقل الملكية، وغياب آليات الثقة بين البائع والمشتري.
- الحل: Laravel API + React Dashboard + Flutter App.

## 2. Market & Opportunity

- السوق المستهدف: 10M+ سيارة مستعملة تُباع سنوياً في الشرق الأوسط، 50,000+ تاجر سيارات ومعرض
- الفئة: B2B + B2C (تجار سيارات، أفراد بائعون، مشترون، مزودو تمويل)
- المنافسون:
  - **حراج** — أكبر منصة إعلانات سيارات في السعودية (إعلانات فقط، لا معاينة أو توثيق).
  - **Dubizzle / OpenSooq** — إعلانات عامة (ليست متخصصة بالسيارات).
  - **CarSwitch** — سوق سيارات مستعملة في الإمارات (ناشئ، تغطية محدودة).
  - **سيارتي** — منصة سعودية (ميزات محدودة، لا توثيق عقود).
- التمايز: منصة متكاملة (إعلانات + معاينة + توثيق عقود + تمويل + ضمان)، فحص فني معتمد، توثيق إلكتروني لنقل الملكية، آليات دفع آمنة (escrow).

## 3. User Personas

### شخص أساسي: تاجر سيارات
- الاسم: عبدالعزيز
- الدور: يمتلك معرض سيارات مستعملة في الرياض مع 30 سيارة
- الأهداف: عرض السيارات بتفاصيل دقيقة، جذب مشترين جادين، إتمام البيع بسرعة وبأمان
- نقاط الألم: إعلانات ورقية، مشترين غير جادين، تأخير في نقل الملكية، تعثر في التحصيل

### شخص أساسي: مشتري فرد
- الاسم: فيصل
- الدور: موظف حكومي يبحث عن سيارة عائلية مستعملة بمواصفات معينة
- الأهداف: البحث عن سيارة بحالة جيدة وسعر عادل، التأكد من خلوها من الحوادث، شراء آمن
- نقاط الألم: عدم الثقة في البائعين، صعوبة التحقق من حالة السيارة، إجراءات الشراء المعقدة

### Admin: مشرف المنصة
- التحقق من البائعين، إدارة الفحص الفني، مراقبة العقود، فض النزاعات.

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Vehicle, Listing, Dealer, Buyer, Inspection, TestDrive, Contract, Payment, Review, FinancingOffer
- RESTful CRUD for all resources
- Role-based auth (Admin, Dealer, PrivateSeller, Buyer, Inspector)
- Vehicle catalog — make, model, year, mileage, VIN, condition, features, photos, price
- Listing management — featured listings, expiry, status, view/lead tracking
- VIN check integration — accident history, ownership, liens (via third-party API)
- Inspection booking — certified inspector visit, checklist, report generation
- Test drive scheduling — dealer availability, buyer booking, confirmation
- Contract generation — dynamic sale contract, terms, digital signature
- Payment escrow — secure payment hold, release on delivery/transfer
- Financing offers — bank integration, loan pre-approval, monthly payment calculator
- Review & rating — dealer/buyer reviews after completed transaction
- Notification engine: SMS, email, push (inquiry, offer, test drive, payment)

### React Dashboard (Web)
- Dealer dashboard: listings management, lead tracking, sales analytics
- Listing manager: create/edit listings, photo upload, feature selection, pricing
- Vehicle detail page builder: 360° photos, specs, inspection report, CARFAX
- Lead management: inquiries, test drive requests, offer negotiation
- Contract builder: generate sale contract, e-signature, payment tracking
- Inspection management: schedule inspectors, view reports, certification badges
- Buyer dashboard: saved searches, favorite listings, test drive bookings, purchase history
- Admin panel: user verification, dispute resolution, platform analytics, featured listings

### Flutter App (Mobile)
- Search & browse: filter by make, model, year, price, location, mileage
- Vehicle detail: photos gallery, specs, inspection report, seller info
- Compare: side-by-side comparison of up to 3 vehicles
- Save search: notify when matching vehicles listed
- Contact seller: in-app chat, call, schedule test drive
- Test drive booking: pick date/time, confirm with dealer
- Financing calculator: monthly payment estimate based on price and down payment
- My garage: saved cars, active listings, purchase history
- Push notifications: new matching listings, price drop, offer received, test drive reminder

## 5. Data Model (MVP)

### Vehicle
- id, vin, make, model, year, trim, mileage, fuel_type, transmission, engine_cc, color, body_type, doors, seats, features (JSON—sunroof, nav, cameras, sensors), condition (excellent/good/fair/poor), accident_history, owners_count, service_history, created_at

### Listing
- id, vehicle_id (FK), seller_id (FK), seller_type (dealer/private), title, description, price, negotiable, images (JSON), videos (JSON), status (active/pending/sold/expired/removed), view_count, lead_count, featured_until, created_at

### Dealer
- id, user_id (FK), business_name, license_number, commercial_register, address, city, phone, showroom_photos (JSON), rating, listings_count, verified, created_at

### Buyer
- id, user_id (FK), preferred_makes (JSON), budget_min, budget_max, saved_searches (JSON), created_at

### Inspection
- id, listing_id (FK), inspector_id (FK), status (scheduled/in_progress/completed), report (JSON—body, engine, transmission, suspension, interior, electronics, summary), rating, certificate_url, created_at

### TestDrive
- id, listing_id (FK), buyer_id (FK), scheduled_at, status (pending/confirmed/completed/cancelled), notes, created_at

### Contract
- id, listing_id (FK), buyer_id (FK), seller_id (FK), price_agreed, terms (JSON), signed_by_seller_at, signed_by_buyer_at, payment_status (pending/escrow/released/completed), escrow_amount, created_at

### FinancingOffer
- id, listing_id (FK), buyer_id (FK), bank_id (FK), loan_amount, down_payment, monthly_payment, interest_rate, tenure_months, status (pending/approved/rejected), created_at

### Review
- id, contract_id (FK), reviewer_id (FK), reviewed_id (FK), type (seller/buyer), rating, comment, created_at

### Payment
- id, contract_id (FK), amount, type (escrow_deposit/escrow_release/final), method, status, transaction_id, created_at

## 6. API Endpoints (MVP)

```
POST   /api/auth/login
POST   /api/auth/register
GET    /api/auth/me

GET    /api/listings
GET    /api/listings/search?make=&model=&year_min=&year_max=&price_min=&price_max=&city=&mileage_max=
GET    /api/listings/{id}
POST   /api/listings
PUT    /api/listings/{id}
DELETE /api/listings/{id}
POST   /api/listings/{id}/feature

GET    /api/vehicles/decode-vin?vin=

POST   /api/inspections
GET    /api/inspections/{id}
PUT    /api/inspections/{id}/report

POST   /api/test-drives
GET    /api/test-drives
PUT    /api/test-drives/{id}/status

POST   /api/contracts
GET    /api/contracts/{id}
PUT    /api/contracts/{id}/sign-buyer
PUT    /api/contracts/{id}/sign-seller

POST   /api/payments/escrow
POST   /api/payments/escrow/release
POST   /api/payments/webhook

POST   /api/financing/calculate
POST   /api/financing/apply
GET    /api/financing/offers

POST   /api/reviews
GET    /api/dealers/{id}/reviews

GET    /api/dealers/{id}/dashboard
GET    /api/buyer/dashboard
GET    /api/admin/dashboard
```

## 7. User Interface (Screen List)

### Dashboard Screens (React)
1. Login / Register (buyer/dealer)
2. Dealer Dashboard — active listings, leads, test drives, sales chart
3. Listing Manager — create/edit, photo upload, pricing, feature tags
4. Lead Management — inquiries list, test drive requests, offers
5. Vehicle Detail Page — gallery, specs, inspection, CARFAX, seller info
6. Contract Builder — price, terms, e-signatures, payment
7. Inspection Management — schedule, reports, certification
8. Buyer Dashboard — saved searches, favorites, bids, purchase history
9. Admin Panel — user verification, listing approval, disputes, platform stats

### Mobile Screens (Flutter)
1. Home — search bar, featured listings, browse by make
2. Search Results — filter panel, list/grid view, sort
3. Vehicle Detail — gallery swipe, specs, inspection badge, price, contact buttons
4. Compare — side-by-side comparison (up to 3 cars)
5. Contact Seller — in-app chat, call, schedule test drive
6. Test Drive Booking — pick date/time → confirm
7. Financing Calculator — price input → down payment → monthly estimate
8. My Garage — favorites, saved searches, my listings, purchase history
9. Notifications — new matches, price drops, offers, reminders

### Screen Flow
```
Buyer: Search → Filter → View Vehicle → Compare → Inspect → Test Drive → Negotiate → Contract → Escrow → Transfer → Review
Seller: List Vehicle → Manage Leads → Schedule Test Drives → Negotiate → Sign Contract → Receive Payment → Transfer Ownership
```

## 8. Business Model

- **إعلانات الأفراد**: مجاني (إعلان واحد) / $9.99 للإعلانات المميزة
- **باقة التاجر الأساسية**: $49/شهر (10 سيارات، تحليلات أساسية)
- **باقة التاجر الاحترافية**: $99/شهر (50 سيارة، إعلانات مميزة، تقارير)
- **باقة التاجر الغير محدودة**: $199/شهر (غير محدود سيارات، API، أولوية الدعم)
- **خدمة الفحص الفني**: $29-59 حسب نوع الفحص
- **خدمة الضمان**: 1-3% من سعر السيارة
- **رسوم البيع**: 1% رسوم منصة على كل صفقة ناجحة
- فترة تجربة مجانية للتجار: 30 يوماً
- MRR المستهدف لكل تاجر: $49-$199

## 9. Implementation Plan

- Phase 1 (Weeks 1-2): Laravel API — Auth, Vehicle/Listing/Dealer CRUD, Sanctum roles
- Phase 2 (Weeks 3-4): Laravel API — VIN lookup, inspection workflow, test drive booking, escrow payments
- Phase 3 (Weeks 5-6): React Dashboard — Dealer dashboard, listing manager, lead management, contracts
- Phase 4 (Weeks 7-8): Flutter App — Search, vehicle detail, compare, financing calculator, notifications
- Phase 5 (Weeks 9-10): Contract e-signatures, financing integration, Arabic localization, testing, deploy

## 10. Risk & Mitigation

- **مخاطرة ثقة**: الاحتيال في الإعلانات وعدم تطابق السيارة مع الوصف — التخفيف: فحص فني معتمد، نظام تقييمات، ضمان المنصة.
- **مخاطرة قانونية**: توثيق عقود البيع ونقل الملكية — التخفيف: عقود قانونية معتمدة لكل دولة، تكامل مع أنظمة المرور المحلية.
- **مخاطرة مالية**: التعامل في المدفوعات الكبيرة — التخفيف: نظام escrow آمن، دفع محمي حتى اكتمال نقل الملكية.
- **مخاطرة سوقية**: منافسة حراج المجاني — التخفيف: قيمة مضافة (فحص، توثيق، ضمان، تمويل) تبرر الرسوم، تجربة مستخدم متفوقة.
