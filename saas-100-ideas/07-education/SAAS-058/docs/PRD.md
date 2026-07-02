# PRD: TutorMatch (SAAS-058)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary

- **One-liner:** منصة تربط طلاب الجامعات بمدرسين خصوصيين موثوقين — مع نظام تقييم متكامل، جدولة، ومتابعة الأداء الأكاديمي.
- **Problem:** طلاب الجامعات يعانون من صعوبة إيجاد مدرسين خصوصيين موثوقين، عدم وجود شفافية في الأسعار، صعوبة متابعة التقدم الأكاديمي، وإلغاء الحصص في اللحظة الأخيرة. المدرسون بدورهم يفتقرون إلى منصة لعرض خدماتهم وبناء سمعة رقمية.
- **Proposed solution:** Laravel API (إدارة المستخدمين والحصص والمدفوعات) + React Dashboard (لوحة تحكم للإدارة) + Flutter App (تطبيق للطلاب والمدرسين).

## 2. Market & Opportunity

- **Target market:** طلاب الجامعات في العالم العربي. عدد طلاب الجامعات يتجاوز 15 مليون طالب في المنطقة العربية. نسبة كبيرة منهم تحتاج دروس خصوصية خاصة في المواد العلمية (رياضيات، فيزياء، كيمياء، برمجة).
- **Customer segment:** B2C/B2B — طلاب جامعات، مدرسون خصوصيون، جامعات (لتقديم خدمات دعم أكاديمي).
- **Competitor landscape:**
  1. بريڤات (Privat) — منصة عربية للدروس الخصوصية لكنها عامة (غير مقتصرة على الجامعات).
  2. أكاديمية خان (Khan Academy) — محتوى مجاني لكن بدون مدرسين مباشرين.
  3. أونساين (OnSign) — منصة عربية للخدمات التعليمية.
  4. فيسبوك ومجموعات واتساب — السوق غير المنظم الأكبر.
  5. Wyzant / Chegg — منصات عالمية بدون دعم عربي كافٍ.
- **Differentiation:** تركيز حصري على طلاب الجامعات (وليس المدارس)، نظام تقييم مزدوج (تقييم المدرس + تقييم الطالب)، دفع آمن مع تعليق المبلغ حتى انتهاء الحصة، متابعة الأداء الأكاديمي، تطابق ذكي مع المدرس بناءً على التخصص والمادة، دعم عربي كامل.

## 3. User Personas

### أساسي: الطالب — لين
- **الدور:** طالبة جامعية في السنة الثانية تخصص هندسة
- **الأهداف:** إيجاد مدرس خصوصي في الرياضيات، حجز حصص بمرونة، تحسين درجاتها
- **نقاط الألم:** صعوبة إيجاد مدرسين متخصصين في مواد الجامعة، عدم معرفة جودة المدرس قبل التجربة، أسعار غير واضحة

### أساسي: المدرس الخصوصي — أحمد
- **الدور:** مدرس خصوصي متخصص في الفيزياء والهندسة
- **الأهداف:** عرض خدماته، بناء سمعته، الحصول على طلاب جدد، إدارة جدوله
- **نقاط الألم:** صعوبة إيجاد طلاب، إلغاء الحصص المتكرر، تأخر الدفع

### ثانوي: الجامعة (شريك مؤسسي)
- **الدور:** إدارة أكاديمية
- **الأهداف:** توفير دعم أكاديمي إضافي للطلاب، متابعة نتائجهم
- **نقاط الألم:** ضعف أداء الطلاب، عدم وجود برنامج دعم رسمي

### إداري: مشغل النظام
- **الدور:** مدير المنصة
- **الأهداف:** مراقبة الجودة، حل النزاعات، إدارة المدفوعات

## 4. Features by Platform

### Laravel API (Backend)

- User registration (student, tutor, admin)
- Tutor verification (credentials, ID, interview)
- Subject & specialization taxonomy
- Tutor search & matching (subject, location, rating, price)
- Session booking & scheduling system
- Session management (confirm, complete, cancel, no-show)
- Payment processing (escrow: hold until session completed)
- Rating & review system (mutual)
- Academic progress tracking (grades, goals, reports)
- Real-time chat between student & tutor
- Video session integration (future)
- Push & email notifications
- Reporting & analytics

### React Dashboard (Web)

- Admin dashboard: users growth, sessions volume, revenue
- Tutor management: verification queue, performance metrics
- Student management: profiles, usage stats, reports
- Sessions: live sessions, completed history, dispute cases
- Payments: transaction log, tutor payouts, refunds
- Subjects & specializations: taxonomy management
- Reviews & disputes: moderation panel, issue resolution
- Reports: platform KPIs, user analytics, financial reports
- Settings: commission rates, pricing rules, content moderation

### Flutter App (Mobile)

- Student app: search tutors, view profiles, book sessions, attend, pay, rate
- Tutor app: manage availability, accept/reject sessions, teaching dashboard, earnings
- In-app chat with file sharing
- Session reminders & notifications
- Academic progress dashboard (student)
- Earnings and withdrawal management (tutor)
- Arabic-first Material 3 UI

## 5. Data Model (MVP)

- **User:** id, name, email, phone, role, avatar, verified, bio, created_at
- **TutorProfile:** id, user_id, education, university, major, graduation_year, teaching_experience_years, hourly_rate, currency, bio_video_url, id_verified, credentials_verified, approval_status
- **TutorSpecialization:** id, tutor_id, subject_id, level (undergrad/postgrad)
- **AvailabilitySlot:** id, tutor_id, day_of_week, start_time, end_time, is_recurring
- **Subject:** id, name_ar, name_en, category, parent_id
- **StudentProfile:** id, user_id, university, major, year, gpa
- **Session:** id, student_id, tutor_id, subject_id, status (pending/confirmed/in_progress/completed/cancelled), start_time, end_time, duration_minutes, amount, platform (online/in_person), location, notes
- **Booking:** id, session_id, student_id, tutor_id, status, created_at, confirmed_at
- **Payment:** id, session_id, amount, tutor_earnings, platform_fee, status (held/released/refunded), payment_method, transaction_date, release_date
- **Review:** id, session_id, from_user_id, to_user_id, rating, comment, created_at
- **Complaint:** id, session_id, from_user_id, reason, description, status, resolved_by
- **AcademicGoal:** id, student_id, subject, target_grade, target_date, status
- **ProgressReport:** id, student_id, tutor_id, session_id, rating, notes, created_at
- **Message:** id, session_id, sender_id, message, file_url, created_at
- **Notification:** id, user_id, title, body, type, is_read

## 6. API Endpoints (MVP)

- `POST /api/register` — Register (student/tutor)
- `POST /api/login` — Auth
- `GET /api/user` — Profile
- `PUT /api/user` — Update profile
- `POST /api/tutor/verify` — Submit verification
- `GET /api/tutor/status` — Verification status
- `GET /api/subjects` — Subject list
- `GET /api/tutors` — Search tutors (subject, price, rating)
- `GET /api/tutors/{id}` — Tutor profile
- `GET /api/tutors/{id}/availability` — Tutor availability
- `POST /api/availability` — Set availability (tutor)
- `GET /api/sessions` — List sessions
- `POST /api/sessions` — Request session (student)
- `PUT /api/sessions/{id}/confirm` — Confirm session (tutor)
- `PUT /api/sessions/{id}/complete` — Complete session
- `PUT /api/sessions/{id}/cancel` — Cancel session
- `POST /api/sessions/{id}/start` — Start session
- `GET /api/payments` — Payment history
- `GET /api/payments/earnings` — Tutor earnings
- `POST /api/payments/withdraw` — Withdraw earnings (tutor)
- `POST /api/reviews` — Submit review
- `POST /api/complaints` — Submit complaint
- `GET /api/students/{id}/goals` — Student goals
- `POST /api/students/goals` — Set goal
- `GET /api/students/{id}/progress` — Progress reports
- `POST /api/messages` — Send message
- `GET /api/messages?session_id=X` — Session chat
- `GET /api/notifications` — Notifications
- `GET /api/admin/stats` — Admin dashboard stats

## 7. User Interface (Screen List)

### Dashboard Screens (React)
- Login
- Admin Dashboard: users, sessions, revenue, growth charts
- Users: student & tutor management, verification queue
- Tutors: profiles, ratings, subject expertise, approval
- Sessions: calendar view, session detail, dispute flags
- Payments: transaction log, payout queue, commission summary
- Subjects: taxonomy management, category tree
- Reviews: flagged reviews, moderation queue
- Reports: financial, user growth, platform KPIs

### Mobile Screens (Flutter)

**Student App:**
- Splash / Onboarding
- Home: recommended tutors, recent sessions, quick booking
- Search: filter by subject, price range, rating, university
- Tutor Profile: bio, qualifications, reviews, availability, book
- Book Session: select date/time, choose online/in-person, confirm
- My Sessions: upcoming, in progress, completed, cancelled
- Session Detail: countdown, chat, materials, rating
- Academic Dashboard: goals, progress chart, GPA tracking
- Notifications: session reminders, messages
- Profile: edit info, payment methods, settings

**Tutor App:**
- Home: today's sessions, new booking requests, earnings summary
- Availability: weekly schedule manager, time blocks
- Sessions: requests, upcoming, completed
- Earnings: total earned, pending, withdrawn, withdrawal history
- Profile: edit bio, set rates, verification status
- Reviews: student reviews, response
- Notifications: booking requests, session reminders

### Screen Flow
```
Student:
  Home → Search Tutors → Filter → Tutor Profile → Book → Confirm → Session (chat/meet) → Complete → Rate

Tutor:
  Home → Requests → Accept/Decline → Pre-session → Start Session → Complete → Receive Rating → Paid
```

## 8. Business Model

- **Revenue:** عمولة 20% على كل حصة (من المدرس)
- **Pricing to students:** السعر يحدده المدرس، المنصة تضيف 20%
- **Student subscription:**
  - مجاني: بحث وحجز أساسي و 5 حصص/شهر
  - مميز $9.99/شهر: حصص غير محدودة، أولوية الدعم
- **Tutor subscription:**
  - مجاني: عمولة 25%
  - محترف $14.99/شهر: عمولة 15%، ظهور مميز
- **Free trial:** الحصة الأولى مجانية للطلاب الجدد
- **Target take rate:** $5-$15 متوسط العمولة لكل حصة

## 9. Implementation Plan

- **Phase 1 (Weeks 1-2):** Auth + Subject + User profiles + Tutor verification + Availability APIs
- **Phase 2 (Weeks 3-4):** Session lifecycle + Payment escrow + Reviews + Chat + Notifications
- **Phase 3 (Weeks 5-6):** React Dashboard — admin panel, tutor management, reports
- **Phase 4 (Weeks 7-8):** Flutter Student App — search, book, attend, review
- **Phase 5 (Weeks 9-10):** Flutter Tutor App — availability, sessions, earnings
- **Phase 6 (Weeks 11-12):** Testing, moderation tools, deployment, launch marketing

## 10. Risk & Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| ضمان جودة المدرسين | High | عملية تحقق صارمة (هوية، مؤهل، مقابلة)، تقييم بعد كل حصة |
| حصص وهمية أو احتيال | High | نظام ضمان دفع (escrow)، مراقبة أنماط الاحتيال |
| إلغاء الحصص | Medium | غرامة على الإلغاء المتأخر، نظام حجز مؤكد بالدفع |
| المنافسة من المنصات العالمية | Medium | تركيز على المواد العربية والجامعات العربية، تسعير محلي |
| الثقة بين الطلاب والمدرسين | High | ملفات تعريف موثقة، تقييمات مفصلة، دعم فني ونزاعات |
