# PRD: CoachingPro (SAAS-068)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner**: منصة تدريب مهني متكاملة — إنشاء دورات، متابعة المتدربين، تقييم، وشهادات معتمدة للمدربين المحترفين.
- **Problem statement**: المدربون المحترفون في المنطقة العربية يفتقرون إلى منصة موحدة لإدارة دوراتهم (تسجيل، متابعة، تقييم، شهادات) بدون اعتماد على أدوات متفرقة (زوم، إكسل، واتساب).
- **Proposed solution**: Laravel API + React Dashboard + Flutter App — منصة شاملة لإنشاء الدورات، إدارة المتدربين، تقييم الأداء، إصدار الشهادات المعتمدة.

## 2. Market & Opportunity
- **Target market size**: سوق التدريب المهني ~$350B عالمياً، الشرق الأوسط ~$10B (رؤية السعودية 2030 تدفع التدريب والتطوير).
- **Customer segment**: B2B — مدربون محترفون، شركات تدريب، معاهد تدريب صغيرة. B2C — متدربون أفراد.
- **Competitor landscape**:
  1. **Teachable/Udemy**: منصات عالمية، تأخذ عمولة 30-50%، لا تدعم العربية.
  2. **Thinkific**: منصة جيدة لكن إنجليزية، $49/شهر، بدون خدمات محلية.
  3. **درر**: منصة عربية للدورات لكن بدون أدوات للمدرب المستقل.
  4. **Edraak**: منصة عربية أكاديمية، ليست للمدربين الأفراد.
  5. **Zoom + Excel**: الحل البديل اليدوي بدون إدارة موحدة.
- **Differentiation**: عربي بالكامل، شهادات معتمدة (مؤسسة التدريب التقني)، بوابات دفع محلية، غرفة اجتماعات مدمجة (Jitsi)، تقييم 360 درجة للمتدربين.

## 3. User Personas

### Primary: د. أحمد — مدرب محترف (تطوير قيادة)
- **الدور**: يقدم دورات في القيادة والإدارة، 15-20 دورة سنوياً، 25 متدرب لكل دورة.
- **الأهداف**: تسجيل المتدربين، متابعة تقدمهم، إصدار شهادات، تسويق الدورات.
- **نقاط الألم**: إدارة المتدربين يدوياً، صعوبة إصدار الشهادات، لا توجد منصة موحدة.

### Secondary: سارة — متدربة
- **الدور**: تبحث عن دورات تطوير مهني، تسجل وتحضر.
- **الأهداف**: إيجاد دورات مناسبة، متابعة تقدمها، الحصول على شهادة معتمدة.
- **نقاط الألم**: صعوبة البحث عن دورات عربية موثوقة، لا تعرف مستوى تقدمها.

### Admin: ريم — مديرة أكاديمية تدريب
- **الدور**: تدير أكاديمية ب 10 مدربين و 500+ متدرب سنوياً.
- **الأهداف**: إدارة الفريق، توزيع المهام، تقارير الأداء، متابعة الإيرادات.
- **نقاط الألم**: توزيع المدربين على الدورات صعب، التقارير المالية غير دقيقة.

## 4. Features by Platform

### Laravel API (Backend)
- Core domain models: Coach, Trainee, Course, Module, Lesson, Enrollment, Progress, Assessment, Certificate, LiveSession, Review, Payout
- RESTful endpoints: CRUD for all models
- Auth: Sanctum + social login
- Course builder: modules → lessons (video, PDF, quiz, assignment)
- Enrollment flow: browse → register → pay → access
- Progress tracking: lesson completion, quiz scores, time spent
- Assessment engine: quizzes (MCQ, true/false, essay), assignments, grading
- Certificate generator: template, completion criteria, verification QR code
- Live session scheduling: Zoom/Jitsi integration, calendar sync
- Review & rating: course rating, trainer rating
- Payout system: revenue share or fixed, monthly payouts
- Accreditation integration:对接 جهات الاعتماد المحلية (مؤسسة التدريب التقني)

### React Dashboard (Web)
- Coach dashboard: course performance, student stats, revenue
- Course builder: drag-drop module/lesson creator
- Student management: list, progress, messaging
- Quiz/question bank: create, categorize, reuse
- Certificate designer: template editor, criteria setting
- Live sessions: schedule, attendees, recording upload
- Reports: enrollment trends, completion rates, revenue analytics
- Payout dashboard: earnings, withdrawal history
- Settings: profile, payment info, accreditation docs

### Flutter App (Mobile)
- Trainee app: browse courses → enroll → watch lessons → take quizzes → certificate
- Coach app: manage courses, review assignments, live session host
- Push notifications: new lesson, assignment due, live session reminder, certificate earned
- Offline: downloaded lessons for offline viewing (Pro feature)
- In-app video player with playback position sync

## 5. Data Model (MVP)

| Entity | Fields | Relationships |
|---|---|---|
| Coach | id, name, email, bio, expertise, accreditation, payout_info | hasMany Course |
| Trainee | id, name, email, phone, bio, interests | hasMany Enrollment |
| Course | id, coach_id, title_ar, title_en, description, category, price, duration_hours, level, status | belongsTo Coach |
| Module | id, course_id, title, sequence, description | belongsTo Course |
| Lesson | id, module_id, title, type (video/pdf/quiz/assignment), content_url, duration_minutes, sequence | belongsTo Module |
| Enrollment | id, trainee_id, course_id, enrolled_at, completed_at, progress_pct, status | belongsTo Trainee/Course |
| QuizAttempt | id, lesson_id, trainee_id, score, max_score, passed, attempted_at | belongsTo Lesson/Trainee |
| Certificate | id, trainee_id, course_id, certificate_no, issued_at, verification_url, status | belongsTo Trainee/Course |
| LiveSession | id, course_id, title, start_time, duration, meeting_url, recording_url | belongsTo Course |
| Payout | id, coach_id, amount, period, status, paid_at | belongsTo Coach |

## 6. API Endpoints (MVP)

| Method | Endpoint | Description |
|---|---|---|
| POST | /api/auth/register | Register (coach/trainee) |
| POST | /api/auth/login | Login |
| GET | /api/courses | Browse courses (filterable: category, level, price) |
| POST | /api/courses | Create course (coach) |
| POST | /api/courses/{id}/modules | Add module |
| POST | /api/courses/{id}/enroll | Trainee enrolls |
| GET | /api/enrollments/{id}/progress | Trainee progress |
| POST | /api/lessons/{id}/complete | Mark lesson complete |
| POST | /api/lessons/{id}/quiz/submit | Submit quiz |
| GET | /api/certificates/{id}/verify | Verify certificate |
| GET | /api/coach/dashboard | Coach dashboard stats |

## 7. User Interface (Screen List)

### Dashboard screens (React)
- Login with role selection
- Coach Dashboard: course stats, earnings, student activity
- Course Builder: list → edit → modules → lessons
- Question bank: create quizzes, categorize
- Student list → student detail (progress, quiz scores)
- Certificates: template editor, issue, verify
- Live sessions: schedule, manage, recordings
- Payout: earnings, history, withdrawal
- Reports: enrollments, completion, ratings

### Mobile screens (Flutter)
- Trainee: Browse → Course Detail → Enroll → Lessons → Quiz → Certificate
- Coach: Dashboard → Courses → Messages → Sessions
- Video player: playback, speed control, download (Pro)

### Screen flow (text)
```
Login → Coach Dashboard (courses + earnings)
           ├── Courses → Create → Add Modules → Add Lessons → Publish
           │           → Student Progress → View Per Student → Message
           ├── Quizzes → Question Bank → Create Quiz → Add to Lesson
           ├── Certificates → Design Template → Set Criteria → Issue
           ├── Live Sessions → Schedule → Jitsi/Zoom → Recordings
           └── Reports → Enrollments / Completion / Revenue / Ratings
```

## 8. Business Model
- **Coach Free**: $0 — up to 1 course, 50 students, basic certificate
- **Coach Pro**: $19/month — unlimited courses, quizzes, certificates, live sessions
- **Coach Business**: $39/month — team coaching, accreditation support, priority support
- **Platform fee**: 10% transaction fee on Free tier, 5% on Pro, 0% on Business
- **Free trial**: 14-day Pro trial for coaches

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2)**: Laravel API — Auth (coach/trainee), Course, Module, Lesson CRUD
- **Phase 2 (Weeks 3-4)**: React Dashboard — Course builder, Quiz engine, Certificate designer
- **Phase 3 (Weeks 5-6)**: Flutter App — Trainee experience (browse, learn, quiz), Coach mobile
- **Phase 4 (Weeks 7-8)**: Live sessions, Payouts, Accreditation, Reports, Testing, Deploy

## 10. Risk & Mitigation
- **Technical**: Video streaming & storage — strategy: use Vimeo/Cloudflare Stream, DRM for paid courses.
- **Market**: Building trust in coaches — strategy: verification badges, trainee reviews, sample lessons.
- **Competitive**: Free YouTube content — strategy: structured curriculum, certificate value, direct coach interaction.
- **Regulatory**: Accreditation requirements — strategy: modular accreditation API, partner with local certification bodies.
