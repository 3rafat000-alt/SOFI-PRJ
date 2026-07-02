# PRD: HRTide (SAAS-023)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** نظام إدارة الموارد البشرية للشركات الصغيرة والمتوسطة — موظفين، إجازات، رواتب، تقييم أداء.
- **Problem:** الشركات الصغيرة تفتقر لنظام HR مركزي. الإجازات تدار عبر Excel، الرواتب بحسابات يدوية، تقييم الموظفين غير منتظم.
- **Proposed solution:** Laravel API + React Dashboard للموارد البشرية + Flutter App للموظفين.

## 2. Market & Opportunity
- **Target market size:** سوق HR SaaS عالمياً ~$35B. الشركات الصغيرة (<50 موظف) تمثل 40%.
- **Customer segment:** B2B (شركات صغيرة ومتوسطة 5-200 موظف).
- **Competitor landscape:** BambooHR, Zoho People, Zenefits, Bayt.com.
- **Differentiation:** مصمم خصيصاً للسعودية — توافق مع التأمينات الاجتماعية ووزارة العمل، تسعير يبدأ من $2/موظف.

## 3. User Personas
- **Primary 1 — مدير HR (فاطمة):** مديرة موارد بشرية في شركة 30 موظفاً. تريد إدارة الإجازات، تتبع الحضور، إصدار خطابات.
- **Primary 2 — موظف (سالم):** موظف مبيعات يريد تقديم إجازة، عرض راتبه، تحديث بياناته.
- **Admin — مدير نظام:** يضبط صلاحيات، يراقب استخدام، يدير الاشتراكات.

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Employee, Department, Attendance, Leave, Payroll, PerformanceReview
- RESTful endpoints: CRUD employees/departments, leave workflow, payroll
- Auth & roles: JWT, roles (admin, hr_manager, employee)
- Notifications: إشعار بطلب إجازة، موافقة، تذكير دوام
- Reports: تقارير حضور، إجازات، رواتب

### React Dashboard (Web)
- هيكل تنظيمي تفاعلي (org chart)
- إدارة الإجازات: طلب، موافقة، رصيد
- شاشة حضور وغياب مع فلترة
- كشف رواتب مع حسابات الخصم والإضافة
- تقييم أداء (دورات تقييم، أهداف)

### Flutter App (Mobile)
- بصمة حضور عبر QR أو GPS
- تقديم طلب إجازة
- عرض الرصيد والإجازات المتبقية
- الإشعارات (موافقة إجازة، تذكير)
- عرض كشف الراتب الشهري

## 5. Data Model (MVP)
- **Employee:** id, user_id, department_id, position, salary, hire_date, status
- **Department:** id, name, manager_id, parent_id
- **Attendance:** id, employee_id, date, check_in, check_out, status
- **Leave:** id, employee_id, type (annual/sick/emergency), start, end, days, status, approved_by
- **Payroll:** id, employee_id, period, basic, allowances, deductions, net, status
- **PerformanceReview:** id, employee_id, reviewer_id, period, score, notes

## 6. API Endpoints (MVP)
- `POST /auth/login`, `POST /auth/register`
- `GET /employees`, `POST /employees`, `PUT /employees/{id}`
- `GET /departments`, `POST /departments`
- `GET /leaves`, `POST /leaves`, `PATCH /leaves/{id}/approve`
- `GET /attendance`, `POST /attendance/checkin`
- `GET /payroll`, `POST /payroll/run`
- `GET /reviews`, `POST /reviews`

## 7. User Interface (Screen List)
- **Dashboard:** مؤشرات (عدد موظفين، إجازات اليوم، غياب)
- **Employees:** جدول موظفين + بطاقة موظف
- **Org Chart:** هيكل تنظيمي تفاعلي
- **Leaves:** تقويم إجازات مع فلترة حسب القسم
- **Payroll:** شاشة تشغيل الراتب + معاينة
- **Mobile - Home:** حضور/انصراف، طلبات الإجازة
- **Mobile - Profile:** بياناتي، راتبي، إجازاتي

## 8. Business Model
- **Pricing tiers:**
  - Startup (1-10 employees): $29/شهر
  - Growth (11-50 employees): $79/شهر
  - Scale (51-200 employees): $199/شهر
- **Free trial:** 14 يوم
- **Target MRR per client:** $29-$199

## 9. Implementation Plan
- Phase 1 (Weeks 1-2): API + Auth + Employees/Departments CRUD
- Phase 2 (Weeks 3-4): React Dashboard + Leaves + Attendance
- Phase 3 (Weeks 5-6): Flutter App + Payroll + Reviews
- Phase 4 (Weeks 7-8): تكامل مع التأمينات، إشعارات، اختبارات

## 10. Risk & Mitigation
- **Technical risk:** حسابات الرواتب معقدة. → البدء بنموذج بسيط (راتب ثابت + بدلات ثابتة).
- **Market risk:** الشركات الصغيرة قد لا تدفع. → طبقة مجانية لـ 3 موظفين كحد أقصى.
- **Legal risk:** حماية بيانات الموظفين. → التزام بـ PDPL السعودية.
