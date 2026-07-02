# PRD: BuildTrack (SAAS-016)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary

**منصة رقمية لإدارة مشاريع البناء والتشييد.** توفر BuildTrack أدوات لتتبع تقدم المشروع، إدارة العمال والمواد، مراقبة الميزانية، والتقارير اليومية. تستهدف المقاولين وشركات الإنشاءات الصغيرة والمتوسطة.

- المشكلة: إدارة مشاريع البناء تعتمد على زيارات ميدانية، مكالمات، ودفاتر ملاحظات مما يؤدي إلى تأخير في التقرير، تجاوز الميزانية، وضعف التنسيق.
- الحل: Laravel API + React Dashboard + Flutter App.

## 2. Market & Opportunity

- السوق المستهدف: 25,000+ شركة مقاولات ومكتب هندسي في الشرق الأوسط
- الفئة: B2B (مقاولون، شركات إنشاءات، مكاتب إشراف هندسي)
- المنافسون:
  - **Procore** — منصة عالمية رائدة (مكلفة جداً، $400+/شهر)
  - **PlanGrid** — متخصصة في المخططات (استحوذت عليها Autodesk)
  - **بناء** — حل محلي سعودي بميزات محدودة
- التمايز: تسعير مناسب للشركات الصغيرة، دعم عربي، واجهة مبسطة

## 3. User Personas

### شخص أساسي: المقاول (صاحب المشروع)
- الاسم: فهد
- الدور: مقاول يدير 3-5 مشاريع في وقت واحد
- الأهداف: تتبع تقدم كل مشروع، مراقبة المصروفات، إدارة المقاولين من الباطن
- نقاط الألم: صعوبة معرفة التقدم الفعلي في الموقع، تأخير التقارير، خروج عن الميزانية

### شخص أساسي: مهندس الموقع
- الاسم: يوسف
- الدور: يشرف يومياً على موقع بناء
- الأهداف: تسجيل الحضور، تتبع المواد، رفع تقارير يومية، الإبلاغ عن مشاكل
- نقاط الألم: كتابة تقارير يدوية، صعوبة التواصل مع المكتب، عدم توفر بيانات فورية

### Admin: مشرف المنصة
- إدارة حسابات الشركات، مراقبة الاستخدام، إدارة الخطط.

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Project, Phase, Task, Worker, Material, Expense, DailyReport, Document
- RESTful CRUD for all construction resources
- Role-based auth (Owner, SiteEngineer, Worker, Admin)
- File upload (blueprints, photos, contracts)
- Expense tracking and budget monitoring
- Daily report aggregation engine
- Notification: task due, budget alert, material low stock

### React Dashboard (Web)
- Dashboard: project portfolio overview, timeline Gantt chart, budget health
- Project management: create project, define phases, set milestones
- Task assignment: assign tasks to workers/teams, track completion
- Material tracking: purchase orders, delivery tracking, site inventory
- Worker management: attendance, payroll calculation, skill tracking
- Budget control: planned vs actual cost, variance alerts
- Daily reports: view/submit reports with photos, auto-generated summaries
- Document repository: blueprints, contracts, permits organized by project
- Reports: project progress %, cost analysis, labor productivity

### Flutter App (Mobile)
- Site diary: daily log with photos, voice notes, checklist
- Task checklist: view assigned tasks, mark complete, add comments
- Material request: request materials from site, approval workflow
- Worker attendance: QR scan check-in/out, timesheet
- Photo documentation: geo-tagged progress photos
- Offline mode: log data without internet, sync when connected
- Push notifications: task assignments, material deliveries, safety alerts

## 5. Data Model (MVP)

### Project
- id, name, description, client, location (lat/lng), start_date, expected_end_date, status (planning/in-progress/completed/on-hold), budget, created_at

### Phase
- id, project_id (FK), name, description, start_date, end_date, status, budget, progress_pct, created_at

### Task
- id, phase_id (FK), assigned_to (user_id FK), title, description, priority, status (todo/in-progress/done), due_date, completed_at, created_at

### Worker
- id, name, phone, id_number, role (mason/carpenter/electrician/plumber/laborer), daily_wage, skills (JSON), is_active, created_at

### Material
- id, project_id (FK), name, category, quantity, unit, unit_price, supplier, delivery_date, status (ordered/delivered/used), created_at

### Expense
- id, project_id (FK), category, description, amount, receipt_file, expense_date, created_at

### DailyReport
- id, project_id (FK), site_engineer_id (FK), report_date, weather, workers_count, work_done (text), issues (text), materials_received (text), photos (JSON), created_at

### Document
- id, project_id (FK), title, file_path, document_type (blueprint/contract/photo/report), tags, uploaded_by, created_at

### User
- id, name, email, password, role (owner/site-engineer/admin), company_id (FK), created_at

### Company
- id, name, license_number, address, subscription_tier, settings (JSON), created_at

## 6. API Endpoints (MVP)

```
POST   /api/auth/login
GET    /api/auth/me

GET    /api/projects
POST   /api/projects
GET    /api/projects/{id}
PUT    /api/projects/{id}
DELETE /api/projects/{id}
GET    /api/projects/{id}/phases
GET    /api/projects/{id}/tasks
GET    /api/projects/{id}/expenses
GET    /api/projects/{id}/reports
GET    /api/projects/{id}/budget

GET    /api/phases
POST   /api/phases
PUT    /api/phases/{id}

GET    /api/tasks
POST   /api/tasks
PUT    /api/tasks/{id}
PUT    /api/tasks/{id}/status

GET    /api/workers
POST   /api/workers
PUT    /api/workers/{id}
POST   /api/workers/{id}/attendance

GET    /api/materials
POST   /api/materials
PUT    /api/materials/{id}

GET    /api/expenses
POST   /api/expenses
GET    /api/expenses/{id}

GET    /api/daily-reports
POST   /api/daily-reports
GET    /api/daily-reports/{id}

GET    /api/documents
POST   /api/documents/upload
DELETE /api/documents/{id}

GET    /api/reports/progress?project_id=
GET    /api/reports/costs?project_id=&from=&to=
GET    /api/reports/labor?project_id=&from=&to=
```

## 7. User Interface (Screen List)

### Dashboard Screens (React)
1. Login / Register
2. Portfolio Dashboard - projects grid, budget health, timeline Gantt
3. Project Detail - phases, progress bar, budget gauge, recent reports
4. Phase Manager - timeline, tasks, completion percentage
5. Task Board - Kanban view (todo/in-progress/done)
6. Material Tracker - inventory table, low stock alerts
7. Worker Directory - list, attendance, wage calculator
8. Expense Log - add/view expenses by category
9. Daily Reports - calendar view, read/submit
10. Document Vault - folder tree, tagging, search
11. Reports - progress, cost, labor analytics
12. Settings - company profile, user management

### Mobile Screens (Flutter)
1. Login
2. Dashboard - active projects summary, today's tasks
3. Project Selector - choose project to work on
4. Site Diary - daily log form (weather, work done, issues, photos)
5. Task View - assigned tasks checklist
6. Material Request - request form with approval
7. Attendance Scanner - QR scan workers in/out
8. Photo Log - capture geo-tagged photos
9. Notifications - task alerts, material deliveries

### Screen Flow
Login -> Project Selector -> Daily Report -> Task Management -> Material Request

## 8. Business Model

- **الباقة الأساسية**: $49/شهر (مشروع واحد، 10 عمال)
- **الباقة الاحترافية**: $99/شهر (حتى 5 مشاريع، غير محدود العمال، تقارير)
- **باقة المؤسسات**: $199/شهر (غير محدود المشاريع، تقارير متقدمة، API)
- فترة تجربة مجانية: 14 يوماً
- MRR المستهدف لكل عميل: $49-$199

## 9. Implementation Plan

- Phase 1 (Weeks 1-2): Laravel API - Auth, Company/Project/Phase/Task CRUD, roles
- Phase 2 (Weeks 3-4): Worker management, materials, expenses, document upload
- Phase 3 (Weeks 5-6): React Dashboard - Portfolio, Gantt chart, task board, reports
- Phase 4 (Weeks 7-8): Flutter App - Site diary, attendance, photo log, offline mode
- Phase 5 (Weeks 9-10): Integration testing, Arabic localization, deployment

## 10. Risk & Mitigation

- **مخاطرة تقنية**: التعامل مع رفع الملفات الكبيرة (صور، مخططات)
  - التخفيف: رفع مجزأ (chunked upload)، ضغط الصور تلقائياً، CDN
- **مخاطرة سوقية**: مقاومة المقاولين للتحول الرقمي
  - التخفيف: واجهة بسيطة، تدريب عملي، دعم فني عبر واتساب
- **مخاطرة تشغيلية**: ضعف الإنترنت في مواقع البناء
  - التخفيف: وضع عدم اتصال كامل مع مزامنة تلقائية عند الاتصال
