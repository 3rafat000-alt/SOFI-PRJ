# PRD: FitZone Pro (SAAS-007)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner**: نظام إدارة الصالات الرياضية والنوادي — اشتراكات الأعضاء، جدولة الحصص الجماعية، تتبع الحضور، وتطبيق جوال للأعضاء لتتبع اللياقة.
- **Problem statement**: النوادي الرياضية الصغيرة والمتوسطة تدير الاشتراكات والجدولة يدوياً أو بأنظمة قديمة بدون دعم عربي أو تطبيق جوال للأعضاء.
- **Proposed solution**: Laravel API + React Dashboard + Flutter App — لوحة تحكم للإدارة، تطبيق أعضاء لتتبع التمارين والحصص، بوابات دخول ذكية.

## 2. Market & Opportunity
- **Target market size**: سوق إدارة النوادي الرياضية العالمي ~$8B (2025)، الشرق الأوسط ~$400M نمو 17% CAGR.
- **Customer segment**: B2B — صالات رياضية، نوادي لياقة، استوديوهات يوغا/بيلاتس.
- **Competitor landscape**:
  1. **Mindbody**: شامل لكن سعره عالي ($150+)، دعم عربي محدود.
  2. **ClubExpress**: أمريكي، واجهة قديمة، بدون تطبيق جوال عربي.
  3. **Perfect Gym**: قوي لكن موجه للأندية الكبيرة (أسعار 300+).
  4. **PushPress**: جميل لكن Shopify فقط بدون دعم عربي.
  5. **Wodify**: مخصص لـ CrossFit، ليس للنوادي العامة.
- **Differentiation**: ثلاثي (إدارة + عضو + مدرب)، تسعير عربي مناسب ($29-$79)، QR دخول، برنامج ولاء، دعم المدفوعات المحلية.

## 3. User Personas

### Primary: فيصل — صاحب نادي رياضي (صالة متوسطة)
- **الدور**: يملك صالة رياضية في دبي مع 300 عضو و 5 مدربين.
- **الأهداف**: إدارة الاشتراكات، جدولة الحصص، متابعة الحضور.
- **نقاط الألم**: الأعضاء ينسون الحصص، تأخير في الدفعات، صعوبة معرفة شعبية الحصص.

### Secondary: هدى — مدربة لياقة (Fitness Coach)
- **الدور**: تقدّم حصص جماعية وفردية، تتابع تقدم عملائها.
- **الأهداف**: جدولة حصصها، رؤية قائمة الحضور، تتبع أداء الأعضاء.
- **نقاط الألم**: لا ترى تقييمات الأعضاء لحصصها، صعوبة متابعة العملاء خارج النادي.

### Member: أحمد — عضو في النادي
- **الدور**: يزور النادي 4 مرات أسبوعياً، يحضر حصص جماعية.
- **الأهداف**: حجز الحصص بسهولة، تتبع تقدمه، إشعارات.
- **نقاط الألم**: ازدحام النادي في أوقات الذروة، لا يعرف تقييم الحصص.

## 4. Features by Platform

### Laravel API (Backend)
- Core domain models: Gym, Member, MembershipPlan, Subscription, Class, Booking, Trainer, Attendance, WorkoutLog
- RESTful endpoints: full CRUD
- Auth: Sanctum multi-role (admin/trainer/member)
- Membership engine: plan creation, recurring billing, freeze/cancel
- Schedule engine: recurring class templates, trainer assignment, capacity
- Attendance: QR code scan check-in
- Notifications: class reminder (2hr before), membership expiry, payment receipt

### React Dashboard (Web)
- Admin panel: gym profile, staff management, subscription summary
- Membership plans: create/edit tiers (monthly/yearly/lifetime)
- Schedule calendar: weekly view, drag class to reschedule
- Member directory: search, profile, membership status, attendance history
- Financial dashboard: MRR, active members, churn rate, revenue per class
- Attendance reports: peak hours, daily traffic, trainer performance
- Check-in screen: QR scanner for entry

### Flutter App (Mobile)
- Member app: browse classes, book, cancel, digital membership card, workout log, progress photos
- Trainer app: my schedule, class attendance take, add workout notes for clients
- Push notifications: class reminder, workout streak, payment reminder
- Offline: class schedule cached, QR entry without internet

## 5. Data Model (MVP)

| Entity | Fields | Relationships |
|---|---|---|
| Gym | id, name, slug, address, capacity, timezone | hasMany Member, Trainer, Class |
| Member | id, gym_id, name, email, phone, emergency_contact, photo | belongsTo Gym, hasMany Booking |
| MembershipPlan | id, gym_id, name, price, duration_days, max_classes, features | belongsTo Gym |
| Subscription | id, member_id, plan_id, start_date, end_date, status, auto_renew | belongsTo Member/Plan |
| Trainer | id, gym_id, name, specialties, bio, photo | belongsTo Gym |
| Class | id, gym_id, trainer_id, name, start_time, end_time, capacity, recurring_days | belongsTo Gym/Trainer |
| Booking | id, class_id, member_id, status, booked_at, checked_in | belongsTo Class/Member |
| Attendance | id, member_id, visit_date, check_in_time, check_out_time | belongsTo Member |
| WorkoutLog | id, member_id, trainer_id, exercise, sets, reps, weight, notes | belongsTo Member/Trainer |

## 6. API Endpoints (MVP)

| Method | Endpoint | Description |
|---|---|---|
| GET | /api/classes | Class schedule (filterable: date/trainer) |
| POST | /api/classes/{id}/book | Book class |
| POST | /api/classes/{id}/cancel | Cancel booking |
| GET | /api/members/{id}/attendance | Attendance history |
| POST | /api/check-in | QR check-in |
| GET | /api/gym/{id}/dashboard | Gym analytics |
| GET | /api/members/{id}/workout-logs | Workout history |
| POST | /api/members/{id}/freeze | Freeze membership |

## 7. User Interface (Screen List)

### Dashboard screens (React)
- Login → Gym dashboard (active members, today's classes, revenue)
- Members: searchable table → Member detail (subscription, attendance, payments)
- Schedule: weekly calendar → Create/edit class modal
- Plans: pricing table → Create plan form
- Attendance: today's check-ins, hourly chart
- Reports: MRR chart, churn rate, class popularity, traffic heatmap
- Check-in: full-screen QR scanner with camera

### Mobile screens (Flutter)
- Member: Home (upcoming classes, streak) → Class schedule → Book → My membership → Workout log
- Trainer: My schedule → Attendance take → Member notes

### Screen flow (text)
```
Dashboard → Schedule (weekly) → Create Class
                ├── Members → Search → Member Detail → Subscription / Attendance
                ├── Plans → Pricing → Create Plan
                ├── Attendance → Today's Check-ins → Live traffic
                └── Reports → MRR / Churn / Popular classes

Member App → Home → Browse Classes → Book → QR Entry → Workout Log
                └── Membership → Renew / Freeze
```

## 8. Business Model
- **Starter**: $29/month — up to 200 members, 5 trainers, 1 gym
- **Pro**: $59/month — up to 500 members, unlimited trainers, member app, QR check-in
- **Enterprise**: $99/month — unlimited members, multiple branches, API access
- **Free trial**: 14-day Pro trial

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2)**: Laravel API — Gym, Member, MembershipPlan, Class, Booking CRUD
- **Phase 2 (Weeks 3-4)**: React Dashboard — Schedule calendar, Member mgmt, Attendance
- **Phase 3 (Weeks 5-6)**: Flutter App — Member booking, Trainer app, QR check-in
- **Phase 4 (Weeks 7-8)**: Workout logs, Reports, Payment gateway, Testing

## 10. Risk & Mitigation
- **Technical**: Real-time class booking conflicts — strategy: optimistic locking with booking window.
- **Market**: Gym owners prefer paper — strategy: free onboarding + QR check-in demo value.
- **Seasonal**: Membership spikes in Jan, drops in summer — strategy: annual plans with discount.
