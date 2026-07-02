# PRD: LawyerRef (SAAS-091)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary

**شبكة ذكية لربط طالبي الاستشارات القانونية بالمحامين المتخصصين.** تهدف LawyerRef إلى إنشاء منصة وسيطة تربط الأفراد والشركات بمحامين متخصصين بناءً على نوع القضية، الموقع، التقييمات، والتكلفة. توفر المنصة محرك بحث متقدم، نظام حجوزات استشارات، وتوثيق المراجعات.

- المشكلة: صعوبة إيجاد محامٍ متخصص في مجال معين (جنائي، تجاري، أحوال شخصية) بسرعة وثقة، والاعتماد على التوصيات الشخصية والتجارب السابقة دون نظام تقييم موثوق.
- الحل: Laravel API + React Dashboard + Flutter App.

## 2. Market & Opportunity

- السوق المستهدف: 20,000+ محامٍ في العالم العربي، 5M+ طالب استشارات سنوياً
- الفئة: B2B + B2C (محامون، أفراد، شركات)
- المنافسون:
  - **LegalMatch** — منصة إحالة أمريكية (لا تدعم العربية، لا تغطي المنطقة).
  - **Avvo** — دليل محامين عالمي (توقف في 2022).
  - **JustAnswer** — استشارات قانونية عامة (ليست متخصصة).
  - **محامي نت** — موقع محلي محدود التغطية.
- التمايز: تركيز على السوق العربي، تصنيف دقيق بالتخصصات القانونية، تقييمات موثقة، دفع آمن للاستشارات، تكامل مع أنظمة المحاكم المحلية.

## 3. User Personas

### شخص أساسي: طالب استشارة قانونية
- الاسم: سارة
- الدور: صاحبة شركة ناشئة تحتاج استشارة في العقود التجارية
- الأهداف: إيجاد محامٍ متخصص في العقود التجارية بسرعة، قراءة تقييمات حقيقية، حجز استشارة بسعر معقول
- نقاط الألم: لا تعرف أي محامٍ متخصص، تخاف من الاحتيال، ليس لديها وقت للبحث الطويل

### شخص أساسي: محامٍ متخصص
- الاسم: خالد
- الدور: محامٍ متخصص في القضايا التجارية والتحكيم الدولي
- الأهداف: زيادة عدد العملاء، بناء سمعة رقمية، تحويل وقت الفراغ إلى استشارات مدفوعة
- نقاط الألم: صعوبة الوصول لعملاء جدد، تكاليف إعلانات مرتفعة، لا توجد منصة عربية متخصصة

### Admin: مشرف المنصة
- إدارة المحامين المسجلين، التحقق من التراخيص، مراقبة التقييمات، إدارة خطط الاشتراك.

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Lawyer, Client, Consultation, Review, Specialty, CaseType, Payment
- RESTful CRUD for all resources
- Role-based auth (Admin, Lawyer, Client)
- Lawyer verification workflow — license upload + manual approval
- Search engine: filter by specialty, location, rating, price range, availability
- Consultation booking — calendar integration, video call links, payment processing
- Review & rating system — verified reviews only from booked consultations
- Notification engine: email, push (new lead, booking confirmation, reminder)

### React Dashboard (Web)
- Admin panel: lawyer applications, user management, dispute resolution, platform analytics
- Lawyer dashboard: profile management, availability calendar, consultation requests, earnings analytics, client reviews
- Client dashboard: search lawyers, view profiles, book consultations, manage appointments, payment history
- Reports: platform KPIs (total consultations, revenue, active lawyers, client satisfaction)

### Flutter App (Mobile)
- Search lawyers by specialty, location, or name
- Lawyer profile: bio, specializations, ratings, consultation pricing, availability
- Book consultation: select date/time, describe case, make payment
- In-app chat with lawyer for pre-consultation questions
- Video/audio consultation via WebRTC integration
- Review after consultation completion
- Push notifications for booking confirmations, reminders, messages

## 5. Data Model (MVP)

### Lawyer
- id, user_id (FK), license_number, license_photo, specialties (JSON), years_of_experience, bio, consultation_price, availability (JSON), rating_avg, rating_count, verification_status (pending/approved/rejected), created_at

### Client
- id, user_id (FK), preferred_categories (JSON), consultation_count, created_at

### Consultation
- id, lawyer_id (FK), client_id (FK), case_type, description, status (pending/confirmed/completed/cancelled), scheduled_at, duration_minutes, price, payment_status, meeting_link, notes, created_at

### Review
- id, consultation_id (FK), lawyer_id (FK), client_id (FK), rating (1-5), comment, created_at

### Specialty
- id, name (AR/EN), category, description, created_at

### User (Laravel Sanctum)
- id, name, email, phone, password, role (admin/lawyer/client), avatar, locale, created_at

### Payment
- id, consultation_id (FK), amount, fee, net_amount, status (pending/completed/refunded), payment_method, transaction_id, created_at

## 6. API Endpoints (MVP)

```
POST   /api/auth/register
POST   /api/auth/login
POST   /api/auth/logout
GET    /api/auth/me

GET    /api/lawyers
GET    /api/lawyers/{id}
GET    /api/lawyers/{id}/reviews
GET    /api/lawyers/search?specialty=&location=&rating=&price_min=&price_max=

POST   /api/lawyers/register          (lawyer onboarding)
PUT    /api/lawyers/profile           (update availability, bio, price)
GET    /api/lawyers/dashboard          (my consultations, earnings)

POST   /api/consultations
GET    /api/consultations             (client: my bookings; lawyer: my requests)
GET    /api/consultations/{id}
PUT    /api/consultations/{id}/status (confirm/cancel/complete)

POST   /api/consultations/{id}/review
GET    /api/reviews/lawyer/{id}

GET    /api/specialties
POST   /api/payments/checkout
POST   /api/payments/webhook         (payment gateway callback)

GET    /api/notifications
PUT    /api/notifications/{id}/read
```

## 7. User Interface (Screen List)

### Dashboard Screens (React)
1. Login / Register (role selection)
2. Home — search bar, featured lawyers, specialties grid
3. Search Results — filterable lawyer cards with ratings and price
4. Lawyer Profile — bio, specialties, reviews, availability calendar
5. Booking Flow — case description form → date/time picker → payment → confirmation
6. Client Dashboard — upcoming consultations, history, reviews
7. Lawyer Dashboard — consultation requests, earnings, profile editor
8. Admin Panel — user management, verification queue, platform metrics

### Mobile Screens (Flutter)
1. Splash → Onboarding (role selection)
2. Home — search bar, specialties, nearby lawyers
3. Lawyer List — filter by specialty, rating, price
4. Lawyer Detail — profile, reviews, book button
5. Booking — case details, date select, payment
6. My Consultations — upcoming and past
7. Chat — in-app messaging
8. Profile — personal info, settings

### Screen Flow
```
Client: Login → Search → Select Lawyer → View Profile → Book → Pay → Confirm → Attend → Review
Lawyer: Login → Dashboard → Manage Requests → Accept → Conduct Consultation → Receive Payment
Admin:  Login → Dashboard → Verify Lawyers → Manage Platform → View Reports
```

## 8. Business Model

- **باقة المحامي الأساسية**: $19/شهر (5 استشارات/شهر، نسبة 15%)
- **باقة المحامي الاحترافية**: $49/شهر (غير محدود الاستشارات، نسبة 10%)
- **باقة المحامي المميزة**: $99/شهر (أولوية في البحث، تحليلات متقدمة، نسبة 8%)
- استخدام الأفراد: مجاني (البحث والحجز) مع رسوم خدمة 10% على قيمة الاستشارة
- فترة تجربة مجانية للمحامين: 30 يوماً
- MRR المستهدف لكل محام: $19-$99

## 9. Implementation Plan

- Phase 1 (Weeks 1-2): Laravel API — Auth, User/Lawyer/Client CRUD, Specialty model, Sanctum roles
- Phase 2 (Weeks 3-4): Laravel API — Consultation booking, search engine, payment integration, review system
- Phase 3 (Weeks 5-6): React Dashboard — Admin panel, Lawyer/Client dashboards, profile management
- Phase 4 (Weeks 7-8): Flutter App — Search, booking, chat, video consultation, notifications
- Phase 5 (Weeks 9-10): Lawyer verification workflow, Arabic localization, testing, deployment

## 10. Risk & Mitigation

- **مخاطرة تنظيمية**: اختلاف تراخيص المحاماة بين الدول — التخفيف: نظام تحقق يدوي مع فريق قانوني لكل دولة.
- **مخاطرة ثقة**: صعوبة بناء ثقة بين طالبي الاستشارات والمحامين الجدد — التخفيف: نظام تقييمات موثقة، ضمان استرداد.
- **مخاطرة تقنية**: تكامل الدفع عبر الحدود — التخفيف: استخدام بوابات دفع متعددة تدعم المنطقة (PayTabs، Moyasar، Stripe).
- **مخاطرة سوقية**: المحامون قد يرفضون تخفيض أسعارهم — التخفيف: تسعير مرن، التركيز على المحامين الشباب الطموحين.
