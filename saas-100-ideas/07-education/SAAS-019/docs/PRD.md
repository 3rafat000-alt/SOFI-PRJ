# PRD: TutorSpace (SAAS-019)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary

**منصة رقمية تربط المعلمين الخصوصيين بالطلاب.** تقدم TutorSpace حلاً متكاملاً لجلسات التعليم الخصوصي: جدولة الدروس، فصول افتراضية مدمجة، دفع إلكتروني، وتقييم لكل من المعلم والطالب. تستهدف المعلمين الخصوصيين والطلاب في المنطقة العربية.

- المشكلة: سوق الدروس الخصوصية يعتمد على التوصيات الشفهية والواتساب للجدولة والدفع، مما يسبب عدم شفافية، صعوبة في إيجاد معلمين موثوقين، ومشاكل في الدفع.
- الحل: Laravel API + React Dashboard + Flutter App.

## 2. Market & Opportunity

- السوق المستهدف: 5 ملايين+ طالب و 500,000+ معلم خصوصي في الشرق الأوسط وشمال أفريقيا
- الفئة: B2C (طلاب + معلمون) + B2B (مراكز تعليمية)
- المنافسون:
  - **Preply** — منصة عالمية (عمولة 100% على أول درس، دعم عربي محدود)
  - **iTalki** — منصة لغات فقط
  - **VIPKid** — تعليم إنجليزي للأطفال فقط
  - **نخبة** — منصة عربية ناشئة محدودة الميزات
- التمايز: تركيز على المنهج الدراسي العربي (مصري، سعودي، إماراتي)، أدوات تفاعلية عربية، دفع محلي

## 3. User Personas

### شخص أساسي: المعلم الخصوصي
- الاسم: نورة
- الدور: مدرسة رياضيات تقدم دروساً خصوصية لطلاب الثانوية
- الأهداف: جذب طلاب جدد، جدولة الدروس بسهولة، الحصول على دفع فوري
- نقاط الألم: صعوبة إيجاد طلاب، إلغاء الجلسات في اللحظة الأخيرة، تأخر الدفع

### شخص أساسي: الطالب (أو ولي الأمر)
- الاسم: خالد (ولي أمر)
- الدور: يبحث عن معلم خصوصي لابنه في مادة الفيزياء
- الأهداف: إيجاد معلم مؤهل، مرونة في المواعيد، متابعة التقدم
- نقاط الألم: عدم وجود تقييمات موثوقة للمعلمين، صعوبة الجدولة، الدفع نقداً

### Admin: مشرف المنصة
- التحقق من هوية المعلمين، إدارة النزاعات، مراقبة الجودة، إدارة العمولات.

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Tutor, Student, Subject, Session, Review, Payment, Availability, VirtualClassroom
- RESTful CRUD for profiles, sessions, payments
- Tutor verification (ID upload, qualification check)
- Session scheduling engine (timezone-aware, recurring sessions)
- Virtual classroom integration (Jitsi Meet embedded, no extra cost)
- Payment processing: wallet, card, local gateways
- Review and rating system (double-blind after session)
- Recommendation engine: match tutors to students by subject/level/price
- Notification: session reminders, new tutor match, payment confirmations

### React Dashboard (Web)
- Dashboard: upcoming sessions, earnings chart, student list (tutor view)
- Tutor profile builder: subjects, qualifications, rates, availability calendar
- Student management: view students, session history, notes per student
- Session scheduler: calendar picker, recurring rules, conflict detection
- Virtual classroom: Jitsi iframe, chat, screen sharing, whiteboard
- Payment dashboard: earnings, withdrawal, transaction history
- Reviews: view student feedback, respond
- Reports: earnings by month, session completion rate, popular subjects

### Flutter App (Mobile)
- Browse tutors: search by subject, level, price, rating, location
- Tutor profile: qualifications, reviews, availability, book button
- Booking flow: select subject -> pick timeslot -> confirm -> pay
- In-app virtual classroom: Jitsi Meet integration
- Session reminders: push notification 15min before
- In-app messaging: chat with tutor/student
- Wallet: balance, top-up, withdraw, transaction history
- Rate session: after session ends, rate and review
- Learning materials: upload/share PDF notes within session

## 5. Data Model (MVP)

### Tutor
- id, name, phone, email, password, bio, qualifications (JSON), id_verified, subjects (JSON: subject_id, level, price/hour), rating, total_sessions, total_earnings, is_available, created_at

### Student
- id, name, phone, email, password, grade, parent_phone, subjects_needed (JSON), created_at

### Subject
- id, name_ar, name_en, category (math/science/language/etc.), level (primary/middle/high/university), created_at

### Session
- id, tutor_id (FK), student_id (FK), subject_id (FK), start_time, end_time, status (scheduled/in-progress/completed/cancelled), price, payment_status (pending/paid/refunded), notes, recorded_url, created_at

### Availability
- id, tutor_id (FK), day_of_week, start_time, end_time, is_recurring, specific_date, created_at

### Review
- id, session_id (FK), rating, comment, reviewer_role (tutor/student), created_at

### Payment
- id, session_id (FK), amount, commission_amount, tutor_amount, gateway, transaction_id, status, type (session/wallet-deposit/withdrawal), created_at

### VirtualClassroom
- id, session_id (FK), room_url, started_at, ended_at, duration_minutes, created_at

### Message
- id, sender_id, receiver_id, session_id (FK, nullable), content, file_url, read_at, created_at

### User
- id, name, email, password, role (tutor/student/admin), wallet_balance, created_at

## 6. API Endpoints (MVP)

```
POST   /api/auth/login
POST   /api/auth/register
POST   /api/auth/verify-tutor
GET    /api/auth/me
PUT    /api/auth/profile

GET    /api/tutors
GET    /api/tutors/{id}
GET    /api/tutors/search?subject=&level=&price_min=&price_max=

GET    /api/students
GET    /api/students/{id}
POST   /api/students

GET    /api/subjects
POST   /api/subjects

GET    /api/sessions
POST   /api/sessions
GET    /api/sessions/{id}
PUT    /api/sessions/{id}/status
GET    /api/sessions/my (as tutor/student)
GET    /api/sessions/upcoming

GET    /api/availability
POST   /api/availability
DELETE /api/availability/{id}
GET    /api/availability/{tutor_id} (public)

POST   /api/virtual-classroom/create
POST   /api/virtual-classroom/{id}/end

POST   /api/reviews
GET    /api/reviews?tutor_id=

POST   /api/payments/deposit
POST   /api/payments/withdraw
GET    /api/payments/transactions
POST   /api/payments/session/{session_id}/pay

GET    /api/messages?conversation_id=
POST   /api/messages/send

GET    /api/reports/earnings?from=&to=
GET    /api/reports/sessions?from=&to=
```

## 7. User Interface (Screen List)

### Dashboard Screens (React)
1. Login / Register
2. Tutor Dashboard - upcoming sessions, earnings chart, new requests
3. Student Dashboard - upcoming lessons, favorite tutors
4. Profile Builder (tutor) - subjects, rates, availability, qualifications
5. Tutor Search (student) - filters, results grid, compare
6. Tutor Public Profile - bio, reviews, availability calendar
7. Booking Wizard - select subject, pick time, confirm, pay
8. Session Manager - list view, status filters
9. Virtual Classroom - Jitsi Meet embedded, chat, whiteboard
10. Payment Dashboard - wallet, transactions, withdrawal
11. Reviews - received/given with response option
12. Reports - session history, earnings analytics

### Mobile Screens (Flutter)
1. Splash -> Onboarding -> Login/Register
2. Home - recommended tutors, upcoming sessions (role-based)
3. Tutor Discovery - search bar, category chips, results list
4. Tutor Detail - profile, reviews, availability slots
5. Booking - date picker, time slot selector, payment
6. Session View - countdown, join classroom button
7. Virtual Classroom (in-app) - Jitsi Meet with chat overlay
8. Messages - conversation list, chat with tutor/student
9. Wallet - balance, top-up, transactions
10. Profile - settings, subjects, availability (tutor) / grade (student)

### Screen Flow (Student)
Search Tutors -> View Profile -> Check Availability -> Book -> Pay -> Join Session

### Screen Flow (Tutor)
Set Availability -> Receive Booking -> Accept -> Teach Session -> Receive Payment -> Get Rated

## 8. Business Model

- **عمولة المنصة**: 15% من قيمة كل جلسة
- **باقة المعلم المحترف**: $9.99/شهر (عمولة مخفضة 10%، ظهور مميز في البحث)
- **باقة المركز التعليمي**: $49/شهر (حتى 10 معلمين، لوحة تحكم موحدة)
- فترة تجربة مجانية: 30 يوماً (للباقة المدفوعة)
- MRR المستهدف: $9.99-$49 من الاشتراكات + 10-15% عمولة جلسات

## 9. Implementation Plan

- Phase 1 (Weeks 1-2): Laravel API - Auth, Tutor/Student/Subject CRUD, roles
- Phase 2 (Weeks 3-4): Session scheduling engine, availability, payment processing
- Phase 3 (Weeks 5-6): React Dashboard - Profile builder, booking, virtual classroom
- Phase 4 (Weeks 7-8): Flutter App - Discovery, booking, wallet, notifications
- Phase 5 (Weeks 9-10): Virtual classroom (Jitsi) testing, moderation tools, deployment

## 10. Risk & Mitigation

- **مخاطرة تقنية**: جودة الفصول الافتراضية (الصوت والصورة)
  - التخفيف: Jitsi Meet مع خيار تبديل السيرفرات، تقليل جودة الفيديو تلقائياً
- **مخاطرة سوقية**: بناء الثقة بين الطلاب والمعلمين على منصة جديدة
  - التخفيف: التحقق من هوية المعلمين، ضمان استرداد المبلغ، تقييمات مزدوجة
- **مخاطرة تشغيلية**: إلغاء الجلسات وعدم التزام الطرفين
  - التخفيف: سياسة إلغاء واضحة (إلغاء قبل 4 ساعات مجاناً)، خصم من رصيد المخالف
