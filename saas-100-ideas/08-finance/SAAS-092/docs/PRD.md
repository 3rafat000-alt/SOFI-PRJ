# PRD: EduFinance (SAAS-092)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary

**منصة تمويل التعليم الجامعي تربط الطلاب بالمؤسسات المالية والجامعات.** تهدف EduFinance إلى تسهيل حصول الطلاب على التمويل الجامعي من خلال نظام متكامل يربط بين الطلاب الراغبين في الالتحاق بالجامعات، والمؤسسات المالية المقدمة للقروض والمنح، والجامعات نفسها.

- المشكلة: الطلاب وأسرهم يواجهون صعوبة في فهم خيارات التمويل المتاحة، مقارنة القروض والمنح، وإجراءات التقديم المعقدة. الجامعات تفقد طلاباً مؤهلين بسبب العوائق المالية.
- الحل: Laravel API + React Dashboard + Flutter App.

## 2. Market & Opportunity

- السوق المستهدف: 3M+ طالب جامعي في الشرق الأوسط يحتاجون تمويلاً، 500+ مؤسسة تعليم عالي، 200+ بنك ومؤسسة مالية
- الفئة: B2B + B2C (طلاب، جامعات، بنوك ومؤسسات تمويل)
- المنافسون:
  - **قرضك** — منصة تمويل شخصي سعودية (ليست مخصصة للتعليم).
  - **StudyPay** — تمويل تعليمي في الإمارات (تغطية محدودة).
  - **Prodigy Finance** — تمويل دولي (لا يدعم العربية، شروط صعبة).
  - **Future Finance** — تمويل تعليمي في أوروبا فقط.
- التمايز: تركيز على السوق العربي بالكامل، ربط مباشر مع الجامعات، خوارزمية ذكاء اصطناعي لمطابقة الطالب مع أفضل خيار تمويل، متابعة ما بعد التخرج.

## 3. User Personas

### شخص أساسي: طالب جامعي
- الاسم: عمر
- الدور: طالب ثانوية عامة يريد الالتحاق بجامعة خاصة
- الأهداف: معرفة خيارات التمويل المتاحة، التقديم على قرض دراسي، مقارنة العروض
- نقاط الألم: لا يفهم الفرق بين أنواع القروض، لا يعرف من أين يبدأ، إجراءات ورقية طويلة

### شخص أساسي: مسؤول القروض التعليمية في بنك
- الاسم: نورة
- الدور: مسؤولة عن برنامج القروض التعليمية في بنك سعودي
- الأهداف: الوصول لشريحة أكبر من الطلاب المؤهلين، تقليل مخاطر التخلف عن السداد
- نقاط الألم: صعوبة تقييم الجدارة الائتمانية للطلاب بدون تاريخ ائتماني، تكاليف تسويق عالية

### Admin: مشرف المنصة
- إدارة الجامعات والمؤسسات المالية المسجلة، مراقبة الطلبات، إعداد التقارير.

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Student, University, Program, LoanProduct, Grant, Application, RepaymentPlan, CoSigner
- RESTful CRUD for all resources
- Role-based auth (Admin, Student, University, FinancialInstitution)
- Loan comparison engine — APR, tenure, monthly payment, total cost comparison
- Application workflow — document upload, credit check integration, approval flow
- Repayment tracking — schedule, reminders, late payment alerts
- Notification engine: email, SMS, push (application status, payment due, approval)

### React Dashboard (Web)
- Admin panel: manage universities, financial institutions, monitor applications, platform analytics
- Student dashboard: browse programs, compare loans, apply, track application status, repayment schedule
- University dashboard: manage listed programs, view student applicants, track enrollment from platform
- Bank dashboard: manage loan products, review applications, approve/reject, track portfolio
- Reports: total applications, disbursement volume, default rates, student demographics

### Flutter App (Mobile)
- Student onboarding — profile creation, academic info, financial needs assessment
- Program browser — search universities and programs by field, location, cost
- Loan comparison — side-by-side comparison of offers
- Application tracker — real-time status updates
- Repayment manager — view schedule, make payments, payment history
- Document upload — snap and upload required documents
- Push notifications — application updates, payment reminders, new offers

## 5. Data Model (MVP)

### Student
- id, user_id (FK), national_id, date_of_birth, education_level, gpa, university_id (FK), program_id (FK), financial_need_amount, credit_score, employment_status, created_at

### University
- id, name, country, city, accreditation, ranking, tuition_range_min, tuition_range_max, created_at

### Program
- id, university_id (FK), name, field, degree_level, duration_years, total_tuition, language, created_at

### LoanProduct
- id, institution_id (FK), name, type (government/private/Islamic), min_amount, max_amount, interest_rate, profit_rate (Islamic), tenure_months, grace_period, eligibility_criteria (JSON), created_at

### Grant
- id, university_id (FK) / institution_id (FK), name, amount, eligibility_criteria (JSON), application_deadline, created_at

### Application
- id, student_id (FK), product_id (FK), type (loan/grant), amount_requested, status (draft/submitted/under_review/approved/rejected/disbursed), documents (JSON), co_signer_id (FK), created_at

### RepaymentPlan
- id, application_id (FK), monthly_amount, total_paid, remaining_balance, start_date, end_date, status (active/completed/defaulted), created_at

### CoSigner
- id, name, national_id, employment_status, monthly_income, relationship, created_at

## 6. API Endpoints (MVP)

```
POST   /api/auth/register
POST   /api/auth/login
GET    /api/auth/me

GET    /api/universities
GET    /api/universities/{id}/programs
GET    /api/programs
GET    /api/programs/{id}

GET    /api/loan-products
GET    /api/loan-products/compare?amount=&tenure=
GET    /api/grants
GET    /api/grants/{id}

POST   /api/applications
GET    /api/applications
GET    /api/applications/{id}
PUT    /api/applications/{id}/submit
PUT    /api/applications/{id}/review          (bank role)
PUT    /api/applications/{id}/approve         (bank role)

GET    /api/applications/{id}/repayment
POST   /api/payments/make
GET    /api/payments/history

POST   /api/documents/upload
GET    /api/documents/{id}

GET    /api/notifications
GET    /api/student/dashboard
GET    /api/institution/dashboard
GET    /api/university/dashboard
```

## 7. User Interface (Screen List)

### Dashboard Screens (React)
1. Login / Register (role selection: Student/University/Institution)
2. Home — featured programs, financing options, eligibility calculator
3. University Browser — filter by country, field, tuition range
4. Program Detail — tuition fees, duration, available financing
5. Loan Comparison — side-by-side table with APR, monthly payment, total cost
6. Application Form — personal info, financial info, document upload
7. Application Tracker — status timeline, next steps
8. Student Dashboard — my applications, repayment status, offers
9. Institution Dashboard — loan products, applications, portfolio
10. Admin Panel — platform metrics, verification queue

### Mobile Screens (Flutter)
1. Splash → Onboarding → Login
2. Home — recommended programs, quick eligibility check
3. Program Search — by field, university name, country
4. Financing Options — loans and grants list
5. Compare — side-by-side financing comparison
6. Apply — form with document upload
7. My Applications — status cards with timeline
8. Repayment — schedule view, payment button
9. Profile — personal info, documents

### Screen Flow
```
Student: Register → Eligibility Quiz → Browse Programs → Compare Financing → Apply → Upload Docs → Track Status → Receive Funds → Repay
University: Login → Manage Programs → View Applicants → Confirm Enrollment
Bank: Login → Manage Products → Review Applications → Approve → Disburse → Monitor Repayment
```

## 8. Business Model

- **للطلاب**: مجاني — البحث والمقارنة والتقديم بدون رسوم
- **للجامعات**: $299/شهر (قائمة برامج غير محدودة، تحليلات المتقدمين)
- **للبنوك**: $499/شهر (إدارة منتجات تمويل، مراجعة الطلبات، تحليلات المحفظة)
- **رسوم الإقراض**: 0.5% من قيمة كل قرض تمت الموافقة عليه عبر المنصة
- فترة تجربة مجانية للجامعات والبنوك: 30 يوماً
- MRR المستهدف لكل عميل مؤسسي: $299-$499

## 9. Implementation Plan

- Phase 1 (Weeks 1-2): Laravel API — Auth, Student/University/Program CRUD, Sanctum roles
- Phase 2 (Weeks 3-4): Laravel API — Loan product management, application workflow, document upload
- Phase 3 (Weeks 5-6): React Dashboard — Student/University/Institution dashboards, loan comparison engine
- Phase 4 (Weeks 7-8): Flutter App — Program browser, application flow, repayment tracker, notifications
- Phase 5 (Weeks 9-10): Payment integration, credit check API, Arabic localization, testing, deploy

## 10. Risk & Mitigation

- **مخاطرة تنظيمية**: اختلاف الأنظمة المصرفية وقوانين التمويل بين الدول — التخفيف: التعاقد مع مستشار قانوني لكل دولة مستهدفة، البدء بالمملكة العربية السعودية.
- **مخاطرة ائتمانية**: صعوبة تقييم الجدارة الائتمانية للطلاب — التخفيف: تكامل مع شركات التقارير الائتمانية، خيار الكفيل (cosigner).
- **مخاطرة تقنية**: أمن البيانات المالية والشخصية الحساسة — التخفيف: تشفير AES-256، SSL، توافق مع PCI DSS وGDPR.
- **مخاطرة سوقية**: عزوف البنوك عن تمويل الطلاب بدون ضمانات — التخفيف: نموذج تمويل إسلامي، شراكات مع صناديق التنمية الحكومية.
