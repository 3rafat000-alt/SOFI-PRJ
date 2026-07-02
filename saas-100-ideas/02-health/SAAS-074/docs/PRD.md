# PRD: NurseryPro (SAAS-074)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** نظام إدارة متكامل لدور الحضانة ورياض الأطفال: تسجيل الأطفال، تتبع الحضور والغياب، إدارة الرسوم، تواصل مع أولياء الأمور
- **Problem:** إدارة الحضانات تتم يدوياً بأوراق العمل، صعوبة متابعة الأطفال، تأخير تحصيل الرسوم، ضعف التواصل مع الأهالي
- **Solution:** Laravel API + React Dashboard (nursery admin) + Flutter App (parents)

## 2. Market & Opportunity
- **Target market:** 50K+ nurseries in MENA; Early childhood education market $3B regionally; Digital transformation in education accelerating
- **Customer segment:** B2B (nurseries, kindergartens, early childhood centers)
- **Competitors:** Kinderlime (US), HiMama (Canada), BrightWheel (US), روضتي (KSA), EduStep (Egypt)
- **Differentiation:** Arabic-first design, local payment gateways, offline attendance capture, parent engagement features, affordable MENA pricing

## 3. User Personas

### مديرة الحضانة — سارة (Primary)
- **Role:** مديرة حضانة خاصة تضم 60 طفلاً
- **Goals:** تسجيل أطفال جدد بسهولة، متابعة الحضور، تحصيل الرسوم في وقتها، إرسال تقارير يومية للأهالي
- **Pain points:** أوراق تسجيل كثيرة، أخطاء في الحضور، تأخر دفع الرسوم، وقت طويل للتواصل مع كل ولي أمر

### ولي الأمر — محمد (Secondary)
- **Role:** أب يعمل، لديه طفلان في الحضانة
- **Goals:** متابعة نشاط أطفاله يومياً، دفع الرسوم أونلاين، استلام تقارير التطور
- **Pain points:** لا يعرف ماذا يفعل طفله في الحضانة، يحتاج لدفع نقداً، قلق على سلامة الطفل

### Admin — Dashboard Operator
- **Role:** مدير المنصة يراقب اشتراكات الحضانات، الدعم الفني، جودة الخدمة

## 4. Features by Platform

### Laravel API (Backend)
- Child registration & enrollment management
- Attendance tracking (check-in/check-out with QR/BLE)
- Fee management (tuition plans, discounts, late fees)
- Payment processing (weekly/monthly/term)
- Communication system (announcements, direct messaging)
- Daily activity log (meals, naps, activities, incidents)
- Teacher/Staff management & ratio compliance
- Parent portal auth

### React Dashboard (Web)
- Nursery profile & branding
- Child enrollment & batch management
- Attendance dashboard (real-time + reports)
- Fee tracking & invoicing
- Staff schedule & ratio compliance
- Communication center
- Daily activity reports
- Analytics (enrollment trends, revenue, attendance rates)

### Flutter App (Mobile) — Parent App
- View child daily activities (photos, meals, naps)
- Check-in/out notifications
- Pay fees online (Mada, Apple Pay, STC Pay)
- Receive announcements & messages
- Update child profile & emergency contacts
- Attendance history
- Share media from nursery (daily moments)

## 5. Data Model (MVP)
- **Nursery:** id, name, address, license_no, capacity, age_groups, settings (JSON)
- **Child:** id, nursery_id, name, dob, age_group, guardian_name, guardian_phone, guardian_email, emergency_contact, medical_notes, enrollment_date, status
- **Attendance:** id, child_id, nursery_id, date, check_in_time, check_out_time, status (present/absent/excused)
- **Fee:** id, child_id, nursery_id, amount, period_start, period_end, due_date, status, paid_at, payment_method
- **DailyLog:** id, child_id, date, meals (JSON), naps (JSON), activities (JSON), mood, incidents
- **Message:** id, nursery_id, sender_id, recipient_id, subject, body, type (announcement/direct), sent_at, read_at
- **Staff:** id, nursery_id, name, role, phone, email, qualifications, schedule (JSON)

## 6. API Endpoints (MVP)
- `POST /api/nurseries/register` — Nursery onboarding
- `POST /api/children` — Register child
- `GET /api/children/{id}` — Child profile
- `POST /api/attendance` — Check-in/out
- `GET /api/attendance?date=&nursery_id=` — Daily attendance list
- `GET /api/children/{id}/attendance` — Child attendance history
- `POST /api/fees` — Create fee record
- `GET /api/fees?nursery_id=` — Fee list per nursery
- `POST /api/payments` — Process fee payment
- `POST /api/daily-logs` — Create daily log entry
- `GET /api/children/{id}/daily-logs` — Child's daily logs
- `POST /api/messages` — Send message
- `GET /api/parent/feed` — Parent's activity feed

## 7. User Interface (Screen List)
- **Dashboard screens:** Overall attendance, Fee collection status, Enrollment stats, Staff roster, Daily reports
- **Mobile (Parent):** Home (today's summary), Child feed, Activities, Pay fees, Messages, Profile
- **Flow (Parent):** Login → Home (child activity summary) → View photos → Read daily log → Pay fees → Message teacher
- **Flow (Nursery):** Login → Dashboard → Attendance check-in → Manage children → Generate fees → Send announcement

## 8. Business Model
- **Pricing:** Per child per month: Starter ($1/child/mo, up to 30 children $29/mo), Standard ($0.80/child/mo), Enterprise ($0.50/child/mo)
- **Free trial:** 14-day free trial
- **Target MRR per nursery:** $30–$150 (depending on size)
- **Add-ons:** Camera integration ($49/mo), SMS credits ($10/500 msg), Custom branding ($19/mo)

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2):** API — Nursery/Child CRUD, Attendance tracking, Fee management
- **Phase 2 (Weeks 3-4):** React Dashboard — Nursery admin panel, Attendance dashboard, Fee management, Staff scheduling
- **Phase 3 (Weeks 5-6):** Flutter App — Parent app (daily feed, attendance, payments, messages)
- **Phase 4 (Weeks 7-8):** QR check-in/out, Photo sharing, Push notifications, Payment gateway integration, QA

## 10. Risk & Mitigation
- **Privacy risk:** Children photos shared via app → GDPR/KSA PDPL compliance, parent consent, encrypted storage
- **Adoption risk:** Nursery staff used to paper → Simple onboarding, import wizards, phone support
- **Payment risk:** Late payments → Automated reminders, late fee calculation, partial payment support
- **Retention:** Families leave after preschool → Offer kindergarten transition module to retain nurseries
