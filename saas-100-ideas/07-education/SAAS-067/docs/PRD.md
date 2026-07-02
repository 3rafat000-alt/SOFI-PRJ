# PRD: SchoolEase (SAAS-067)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner**: نظام إدارة المدارس الخاصة — طلاب، جدول حصص، درجات، رسوم دراسية، تواصل مع أولياء الأمور.
- **Problem statement**: المدارس الخاصة تعتمد على أنظمة قديمة أو ورقية لإدارة شؤون الطلاب والموظفين والرسوم، مما يسبب فوضى في السجلات، صعوبة متابعة التحصيل الدراسي، وضعف التواصل مع أولياء الأمور.
- **Proposed solution**: Laravel API + React Dashboard + Flutter App — نظام متكامل لإدارة الطلاب، الجداول الدراسية، الدرجات، الرسوم، والتواصل مع أولياء الأمور.

## 2. Market & Opportunity
- **Target market size**: سوق إدارة المدارس ~$8B عالمياً، الشرق الأوسط ~$600M (نمو 12% CAGR — زيادة المدارس الخاصة في الخليج).
- **Customer segment**: B2B — مدارس خاصة (رياض أطفال، ابتدائي، متوسط، ثانوي)، معاهد تعليمية.
- **Competitor landscape**:
  1. **PowerSchool**: معيار عالمي لكن غالي ($2000+/سنوياً)، بدون دعم عربي كاف.
  2. **Schoology**: منصة تعلم فقط، بدون إدارة مدرسية.
  3. **Classera**: منصة تعليمية عربية لكن تركيز على التعلم الإلكتروني وليس الإدارة.
  4. **مدرسة (منصة وزارية)**: حكومية، غير متاحة للمدارس الخاصة.
  5. **Edumate**: أسترالي، بدون دعم عربي أو منهج محلي.
- **Differentiation**: عربي كامل، دعم المناهج المحلية (سعودية، مصرية، إماراتية)، تطبيق ولي أمر متكامل، دفعات إلكترونية، جدول حصص ذكي.

## 3. User Personas

### Primary: أ. عبير — مديرة مدرسة خاصة
- **الدور**: تدير مدرسة ب 400 طالب و 50 معلماً.
- **الأهداف**: متابعة أداء الطلاب، إدارة الموظفين، تحسين التواصل مع أولياء الأمور.
- **نقاط الألم**: صعوبة استخراج التقارير، أولياء الأمور يشتكون من قلة التواصل، الجدول المدرسي معقد.

### Secondary: أ. حاتم — معلم صف
- **الدور**: يدرس 25 طالباً، يسجل الدرجات، يتابع الحضور.
- **الأهداف**: تسجيل درجات سريع، متابعة الحضور والغياب، إرسال ملاحظات لأولياء الأمور.
- **نقاط الألم**: إدخال الدرجات مضيعة للوقت، لا يعرف الطلاب المتعثرين بسهولة.

### Tertiary: ولي أمر — سعيد (أب طالب)
- **الدور**: يتابع مستوى ابنه الدراسي ورسوم المدرسة.
- **الأهداف**: مشاهدة الدرجات والحضور، دفع الرسوم إلكترونياً، التواصل مع المدرسة.
- **نقاط الألم**: المدرسة لا ترسل تقارير منتظمة، دفع الرسوم يتطلب زيارة المدرسة.

## 4. Features by Platform

### Laravel API (Backend)
- Core domain models: School, AcademicYear, Grade, Class, Student, Teacher, Subject, Timetable, AttendanceRecord, GradeEntry, Exam, FeeStructure, FeePayment, ParentCommunication, ReportCard
- RESTful endpoints: CRUD for all models
- Auth: Sanctum multi-role (admin/teacher/parent/accountant)
- Academic year/semester management
- Student lifecycle: enrollment → active → graduated → transferred
- Gradebook: assignment/exam scores, weighted calculations, GPA
- Attendance: daily entry, SMS/email alerts for absence
- Timetable generator: teacher-class-subject-period assignment, conflict checker
- Fee management: fee structure per grade, installment plans, late fee calc, receipts
- Report cards: PDF generation, auto-calc grades, comments
- Parent portal API: grades, attendance, schedule, invoices
- Communication: in-app messaging, email/SMS push to parents

### React Dashboard (Web)
- Dashboard: student count, attendance rate, fees collected, alerts
- Student management: add, search, profile (personal, guardian, medical, documents)
- Academic records: grades per subject, cumulative, GPA chart
- Attendance dashboard: daily report, monthly trends, top absentees
- Timetable: grid view (grade → day → period), teacher view
- Staff management: teachers, admins, payroll data
- Fee management: invoice creation, payment tracking, overdue reports
- Exam management: create exam schedule, enter results
- Report card builder: template editor, batch generate PDFs
- Parent messaging: send notifications, announcements, report cards

### Flutter App (Mobile)
- Parent app: student grades, attendance, timetable, fee payment, messaging
- Teacher app: take attendance, enter grades, view timetable, send messages
- Push notifications: child absent, fee due, exam schedule, new grades
- Offline: attendance cached for offline schools
- Payment integration: Mada, Apple Pay, STC Pay for fees

## 5. Data Model (MVP)

| Entity | Fields | Relationships |
|---|---|---|
| School | id, name, address, phone, logo, academic_years | hasMany Grade, Teacher, Student |
| AcademicYear | id, school_id, name (1446-1447), start_date, end_date, is_current | belongsTo School |
| Grade (Year Level) | id, school_id, name (Grade 1), head_teacher | belongsTo School |
| Class | id, grade_id, name (1A), room_no | belongsTo Grade |
| Student | id, school_id, class_id, name, dob, guardian_name, guardian_phone, national_id, enrollment_date | belongsTo School/Class |
| Teacher | id, school_id, name, email, phone, specialization | belongsTo School |
| Subject | id, school_id, name_ar, name_en, credit_hours | belongsTo School |
| TimetableEntry | id, class_id, subject_id, teacher_id, day, period, room | belongsTo Class/Subject/Teacher |
| AttendanceRecord | id, student_id, class_id, date, status (present/absent/late/excused), notes | belongsTo Student |
| GradeEntry | id, student_id, subject_id, exam_type, score, max_score, term, academic_year_id | belongsTo Student/Subject |
| FeeStructure | id, grade_id, amount, term, due_date, late_fee | belongsTo Grade |
| FeePayment | id, student_id, fee_structure_id, amount_paid, paid_at, method, receipt_no | belongsTo Student |
| ReportCard | id, student_id, term, academic_year_id, pdf_url, generated_at | belongsTo Student |

## 6. API Endpoints (MVP)

| Method | Endpoint | Description |
|---|---|---|
| POST | /api/auth/login | Login (multi-role) |
| GET | /api/students | List students (filterable: grade, class, status) |
| POST | /api/students | Enroll new student |
| GET | /api/students/{id}/grades | Student grades by subject/term |
| POST | /api/attendance | Bulk attendance entry |
| GET | /api/attendance/report/daily | Today's attendance by class |
| GET | /api/timetable?class_id= | Timetable for class/teacher |
| GET | /api/fees/{student_id} | Student fee account |
| POST | /api/fees/payment | Record fee payment |
| GET | /api/report-cards/{student_id} | Generate report card |
| POST | /api/communication/send | Send message to parents |

## 7. User Interface (Screen List)

### Dashboard screens (React)
- Login (role-based redirect)
- Dashboard: attendance %, fees collected, alerts, notices
- Student management: list → detail → grades, attendance, fees
- Gradebook: by class/subject, enter scores, finalize
- Attendance: register, daily report, monthly trends
- Timetable: visual grid editor
- Staff: teachers list → detail → assignments
- Fees: structure setup, invoice list, payment journal
- Exams: schedule, results entry, report cards
- Communication: announcements, parent messages
- Reports: academic performance, fee collection, attendance

### Mobile screens (Flutter)
- Parent: Login → Dashboard (child summary) → Grades → Attendance → Fees → Messages
- Teacher: Login → My Classes → Take Attendance → Enter Grades → Timetable → Messages
- Push: absence alert, fee reminder, exam notice, grade posted

### Screen flow (text)
```
Login (Admin) → Dashboard (attendance + fees + alerts)
            ├── Students → Add → Profile → Grades → Attendance History
            ├── Academics → Gradebook → Select Class → Enter Scores
            │             → Exam Schedule → Results Entry
            ├── Timetable → Visual Grid → Add Entry → Conflict Check
            ├── Finance → Fee Structure → Invoices → Payments → Overdue
            └── Reports → Report Cards → Batch Generate → Send to Parents
```

## 8. Business Model
- **Starter**: $99/month — up to 200 students, 10 teachers
- **Pro**: $199/month — up to 1000 students, unlimited teachers, parents app, fee management
- **Enterprise**: Custom — unlimited students, report cards, multi-campus, dedicated support
- **Free trial**: 14-day free trial

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2)**: Laravel API — School, Student, Grade, Class, Teacher, Subject CRUD
- **Phase 2 (Weeks 3-4)**: React Dashboard — Gradebook, Attendance, Timetable editor, Student management
- **Phase 3 (Weeks 5-6)**: Flutter App — Parent app (grades, attendance, fees), Teacher app
- **Phase 4 (Weeks 7-8)**: Fee management, Report cards, Communication, Payment integration, Testing, Deploy

## 10. Risk & Mitigation
- **Technical**: Timetable conflict resolution — strategy: constraint-based algorithm with manual override.
- **Market**: Schools slow to change systems — strategy: free data migration from existing systems, end-of-year transition cycle.
- **Regulatory**: Student data privacy — strategy: PDPL compliance (Saudi), encrypted storage, role-based access.
- **Competitive**: PowerSchool dominance — strategy: 5x cheaper, WhatsApp communication, local curriculum support.
