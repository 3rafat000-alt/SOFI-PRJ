# PRD: EduCloud LMS (SAAS-006)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner**: منصة تعليمية سحابية للمدربين ومراكز التدريب — رفع دورات، إدارة طلاب، اختبارات تفاعلية، شهادات إتمام، وتقارير تقدم.
- **Problem statement**: المدربون ومراكز التدريب العربية يفتقرون لمنصة تعليمية بسيطة بالعربية — معظم LMS إما معقد (Moodle) أو مكلف (Teachable) بدون دعم محلي.
- **Proposed solution**: Laravel API + React Dashboard + Flutter App — إنشاء دورات بسحب وإفلات، اختبارات آلية التصحيح، شهادات رقمية، متابعة تقدم الطلاب.

## 2. Market & Opportunity
- **Target market size**: سوق LMS العالمي ~$20B (2025)، الشرق الأوسط ~$900M نمو 22% CAGR.
- **Customer segment**: B2B/B2C — مدربون مستقلون، مراكز تدريب مهني، أكاديميات صغيرة.
- **Competitor landscape**:
  1. **Moodle**: مجاني لكن قديم، معقد، يحتاج استضافة، دعم عربي ضعيف.
  2. **Teachable**: سهل لكن سعري ($39/month)، إنجليزي فقط.
  3. **Thinkific**: مشابه لـ Teachable، بدون دعم عربي.
  4. **Udemy**: سوق وليس LMS، لا تحكم في المحتوى أو التسعير.
  5. **منصة سهل**: حكومي سعودي، ليس للمدربين الخاصين.
- **Differentiation**: عربي بالكامل، مستوى مجاني سخي (دورتين، 50 طالب)، شهادات رقمية مدعومة بـ blockchain، واجهة بسيطة بالذكاء الاصطناعي لإنشاء الاختبارات.

## 3. User Personas

### Primary: سامي — مدرب مهارات (Soft Skills)
- **الدور**: مدرب مستقل يقدم دورات في القيادة والتواصل عبر الإنترنت وحضوري.
- **الأهداف**: رفع دوراته، إدارة الطلاب، إصدار شهادات.
- **نقاط الألم**: يستخدم زوم + واتساب لإدارة الدورات، فوضى كاملة.

### Secondary: نورة — مديرة أكاديمية تدريب صغيرة
- **الدور**: تدير أكاديمية بـ 5 مدربين و200 طالب في جدة.
- **الأهداف**: متابعة كل المدربين والطلاب، تقارير أداء، تحصيل الرسوم.
- **نقاط الألم**: لا رؤية موحدة لأداء المدربين، صعوبة إصدار الشهادات.

### Admin: محمد — مطور محتوى تعليمي
- **الدور**: يصمم محتوى الدورات ويرفعه ويراجع الاختبارات.
- **الأهداف**: إضافة محتوى غني (فيديو، PDF، اختبارات)، هيكلة المنهج.
- **نقاط الألم**: رفع الفيديو بطيء، تنظيم المحتوى صعب.

## 4. Features by Platform

### Laravel API (Backend)
- Core domain models: Academy, Course, Module, Lesson, Quiz, Question, Enrollment, Student, Certificate, Grade
- RESTful endpoints: full CRUD
- Auth: Sanctum multi-role (admin/instructor/student)
- Course builder: drag-drop module/lesson ordering, video upload (S3)
- Quiz engine: multiple choice, true/false, essay, auto-grade
- Certificate generator: PDF with unique hash, QR verification
- Progress tracking: lesson completion, quiz scores, overall %
- Notifications: email enrollment confirmation, certificate issued, quiz graded

### React Dashboard (Web)
- Admin panel: academy settings, instructors, students, subscription
- Course builder: lesson editor (rich text, video embed, attachments)
- Quiz builder: add questions, set passing score, time limit
- Student management: enrollment list, progress per student, grades
- Reports: completion rate, average scores, revenue per course
- Certificate manager: templates, issued certificates, verify page

### Flutter App (Mobile)
- Student app: browse courses, enroll, watch lessons, take quizzes, download certificate
- Instructor app: view student progress, grade essays, send announcements
- Push notifications: new lesson, quiz reminder, certificate awarded
- Offline: downloaded lessons for offline viewing (video caching)

## 5. Data Model (MVP)

| Entity | Fields | Relationships |
|---|---|---|
| Academy | id, name, slug, logo, timezone | hasMany Course, Instructor |
| Course | id, academy_id, title, description, price, thumbnail, status | belongsTo Academy, hasMany Module |
| Module | id, course_id, title, sort_order | belongsTo Course, hasMany Lesson |
| Lesson | id, module_id, title, type (video/pdf/text), content_url, duration | belongsTo Module |
| Quiz | id, module_id, title, passing_score, time_limit, attempt_limit | belongsTo Module, hasMany Question |
| Question | id, quiz_id, type, question_text, options (JSON), correct_answer | belongsTo Quiz |
| Enrollment | id, course_id, student_id, progress, enrolled_at, completed_at | belongsTo Course/Student |
| Student | id, academy_id, name, email, enrolled_count | belongsTo Academy |
| Certificate | id, enrollment_id, hash, issued_at, verified_url | belongsTo Enrollment |

## 6. API Endpoints (MVP)

| Method | Endpoint | Description |
|---|---|---|
| GET | /api/courses | List published courses |
| GET | /api/courses/{id} | Course detail with modules |
| POST | /api/courses/{id}/enroll | Enroll student |
| GET | /api/students/{id}/progress | Student progress across courses |
| POST | /api/quizzes/{id}/submit | Submit quiz answers |
| GET | /api/certificates/{hash} | Verify certificate |
| GET | /api/academy/{id}/reports | Academy analytics |
| POST | /api/lessons/{id}/complete | Mark lesson as complete |

## 7. User Interface (Screen List)

### Dashboard screens (React)
- Login → Academy dashboard (students, revenue, active courses)
- Course list → Course editor (modules tree + lesson form)
- Quiz builder (question type selector + answer input)
- Students table → Student detail progress
- Reports page: charts for each metric
- Certificates: template editor + issued list
- Settings: academy profile, payment gateway, email config

### Mobile screens (Flutter)
- Student: Browse Courses → Course Detail → Enroll → Modules → Lesson Player → Quiz → Certificate
- Instructor: My Courses → Select Course → Student List → Grade essay

### Screen flow (text)
```
Dashboard → Courses → Course Detail (modules tree)
                ├── Module → Lesson Editor (video upload / text editor)
                ├── Quiz → Question Editor
                ├── Students → Progress per student
                └── Reports → Completion / Scores / Revenue

Student App → Browse → Enroll → Lessons (video player) → Quiz → Certificate
```

## 8. Business Model
- **Free**: Up to 2 courses, 50 students, basic quiz types
- **Pro**: $29/month — 10 courses, 500 students, all quiz types, certificates
- **Academy**: $79/month — unlimited courses, 2000 students, API, multiple instructors
- **Free trial**: 14-day Pro trial

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2)**: Laravel API — Academy, Course, Module, Lesson, Enrollment CRUD
- **Phase 2 (Weeks 3-4)**: React Dashboard — Course builder, Lesson editor, Student management
- **Phase 3 (Weeks 5-6)**: Flutter App — Student experience (lessons, quizzes), Certificate generation
- **Phase 4 (Weeks 7-8)**: Quiz engine, Auto-grading, Reports, Video streaming optimization

## 10. Risk & Mitigation
- **Technical**: Video streaming bandwidth — strategy: adaptive bitrate via M3U8, CDN caching.
- **Market**: Free alternatives like Moodle — strategy: better UX, Arabic support, hosted solution.
- **Educational**: Certificate fraud — strategy: unique hash QR code, public verification page.
