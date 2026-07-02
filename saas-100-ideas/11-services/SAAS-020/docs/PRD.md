# PRD: CleanPro (SAAS-020)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary

**منصة رقمية لإدارة شركات التنظيف والخدمات المنزلية.** تقدم CleanPro حلولاً لجدولة فرق التنظيف، تتبع مواقع العمل، إدارة المهام، والفوترة الإلكترونية. تستهدف شركات التنظيف ومقدمي الخدمات المنزلية.

- المشكلة: شركات التنظيف تدير عملياتها عبر المكالمات والواتساب، مما يؤدي صعوبة في جدولة الفرق، ضعف متابعة الجودة، وتأخير في الفوترة.
- الحل: Laravel API + React Dashboard + Flutter App.

## 2. Market & Opportunity

- السوق المستهدف: 12,000+ شركة تنظيف وخدمات منزلية في الشرق الأوسط
- الفئة: B2B (شركات تنظيف، خدمات منزلية، شركات إدارة مرافق)
- المنافسون:
  - **Housecall Pro** — منصة أمريكية للخدمات المنزلية (بدون دعم عربي)
  - **Serviz** — تطبيق خدمات منزلي (موجه للمستهلك، وليس B2B)
  - **نظفها** — منصة عربية مبسطة
- التمايز: تركيز على B2B (إدارة الشركة وليس مجرد حجز)، تتبع الفرق في الموقع، تقارير الجودة

## 3. User Personas

### شخص أساسي: مدير شركة التنظيف
- الاسم: فيصل
- الدور: يدير شركة تنظيف مع 15 عاملاً و 3 فرق
- الأهداف: جدولة الفرق على المشاريع، تتبع جودة العمل، إعداد الفواتير
- نقاط الألم: عدم معرفة موقع العمال، صعوبة توزيع المهام، شكاوى العملاء عن الجودة

### شخص أساسي: مشرف الفريق
- الاسم: ياسر
- الدور: يقود فريق تنظيف من 4 عمال في المواقع
- الأهداف: تسليم المهام في الوقت المحدد، توثيق العمل المنجز، الإبلاغ عن مشاكل
- نقاط الألم: عدم وجود قائمة مهام واضحة، صعوبة توثيق الصور قبل/بعد

### شخص أساسي: العميل (صاحب المنشأة)
- الاسم: هند
- الدور: تطلب خدمات تنظيف لمكتبها شهرياً
- الأهداف: حجز مواعيد ثابتة، متابعة جودة الخدمة، دفع إلكتروني
- نقاط الألم: عدم معرفة موعد وصول الفريق، اختلاف الجودة بين مرة وأخرى

### Admin: مشرف المنصة
- إدارة حسابات شركات التنظيف، مراقبة الجودة، إدارة الاشتراكات.

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Company, Team, Worker, Customer, Job, Task, Service, Invoice, QualityCheck
- RESTful CRUD for jobs, customers, teams, services
- Team scheduling engine (skill-based assignment, location-aware)
- Real-time GPS tracking for field teams
- Job lifecycle: assigned -> en-route -> in-progress -> completed -> inspected
- Before/after photo documentation
- Customer approval workflow (photo evidence, sign-off)
- Recurring job scheduling (weekly/monthly contracts)
- Invoice generation with payment tracking

### React Dashboard (Web)
- Dashboard: active jobs map, team status, revenue today, upcoming schedule
- Job management: create job, assign team, set schedule, track status
- Team management: build teams, assign skills, view schedule
- Worker management: profiles, skills, attendance, performance score
- Customer management: profiles, contracts, service history, notes
- Scheduling calendar: drag-and-drop jobs on Gantt timeline
- Quality control: review before/after photos, customer ratings
- Invoice management: auto-generate, send, track payment
- Reports: revenue by service, team performance, customer retention
- Contract management: recurring service agreements with auto-invoicing

### Flutter App (Mobile) - Team App
- View today's jobs: sorted by time and location
- Job detail: address, customer info, task checklist, instructions
- GPS navigation: one-tap navigation to job site
- Status updates: en-route -> start -> complete with photo evidence
- Before/after photo capture: geo-tagged and timestamped
- Task checklist: check off each cleaning task as completed
- Customer sign-off: collect digital signature on completion
- Time tracking: auto clock-in/clock-out per job
- Offline mode: download jobs, sync when connected

### Flutter App (Mobile) - Customer App (optional)
- Book cleaning: one-time or recurring schedule
- Real-time tracker: see team on map, ETA notification
- Job history: past cleanings with photos
- Rate and review: after each service
- In-app payment: card or wallet

## 5. Data Model (MVP)

### Company
- id, name, phone, email, license, address, service_area, subscription_tier, settings (JSON), created_at

### Team
- id, company_id (FK), name, supervisor_id (FK), member_ids (JSON), vehicle_id, service_area, is_active, created_at

### Worker
- id, company_id (FK), team_id (FK), name, phone, id_number, skills (JSON), rating, is_active, location (lat/lng), created_at

### Customer
- id, company_id (FK), name, phone, email, address (lat/lng), is_contract, contract_frequency, notes, created_at

### Job
- id, company_id (FK), team_id (FK), customer_id (FK), service_id (FK), scheduled_date, start_time, end_time, status (scheduled/en-route/in-progress/completed/cancelled), estimated_hours, actual_hours, total_amount, customer_notes, photos_before (JSON), photos_after (JSON), created_at

### Task
- id, job_id (FK), description, is_completed, completed_at, completed_by, created_at

### Service
- id, company_id (FK), name, category (office/home/deep/construction), base_price, price_per_hour, estimated_hours, created_at

### Invoice
- id, company_id (FK), customer_id (FK), job_ids (JSON), issue_date, due_date, line_items (JSON), subtotal, tax, total, status (draft/sent/paid/overdue), payment_method, created_at

### QualityCheck
- id, job_id (FK), inspector_id, rating, checklist (JSON: item, passed), notes, created_at

### User
- id, name, email, password, role (company-admin/supervisor/worker/admin), company_id (FK), created_at

## 6. API Endpoints (MVP)

```
POST   /api/auth/login
GET    /api/auth/me

GET    /api/jobs
POST   /api/jobs
GET    /api/jobs/{id}
PUT    /api/jobs/{id}/status
PUT    /api/jobs/{id}/assign-team
GET    /api/jobs/today
GET    /api/jobs/calendar

GET    /api/teams
POST   /api/teams
PUT    /api/teams/{id}

GET    /api/workers
POST   /api/workers
PUT    /api/workers/{id}/location

GET    /api/customers
POST   /api/customers
GET    /api/customers/{id}
PUT    /api/customers/{id}
GET    /api/customers/{id}/jobs

GET    /api/services
POST   /api/services
PUT    /api/services/{id}

GET    /api/tasks
POST   /api/tasks
PUT    /api/tasks/{id}/complete

POST   /api/jobs/{id}/photos
GET    /api/jobs/{id}/photos

POST   /api/quality-checks
GET    /api/quality-checks?job_id=

GET    /api/invoices
POST   /api/invoices
PUT    /api/invoices/{id}/send
PUT    /api/invoices/{id}/pay

GET    /api/reports/revenue?company_id=&from=&to=
GET    /api/reports/team-performance?company_id=&from=&to=
GET    /api/reports/customer-retention?company_id=
```

## 7. User Interface (Screen List)

### Dashboard Screens (React)
1. Login
2. Dashboard - map of active jobs, team status grid, revenue widgets, upcoming schedule
3. Job Manager - list view, calendar view, Gantt view
4. New Job - customer select, service selection, team assign, scheduling
5. Job Detail - timeline, team info, photos, customer notes, task list
6. Team Manager - team composition, schedule, performance metrics
7. Worker Directory - profiles, skills, attendance, rating
8. Customer List - searchable with contract status
9. Customer Detail - profile, job history, contracts, open invoices
10. Services Catalog - pricing, categories, estimated duration
11. Quality Dashboard - ratings, checklist pass rates, photos review
12. Invoices - send, track payment, overdue alerts
13. Reports - revenue analysis, team performance, customer retention

### Mobile Screens (Flutter) - Team App
1. Login
2. Today's Jobs - ordered by time, distance indicator
3. Job Detail - address, customer, task checklist, photo buttons
4. Navigation - one-tap Google Maps/Waze
5. Photo Capture - before/after with geo-tag
6. Task Checklist - check off items
7. Customer Sign-off - digital signature pad
8. Job Complete - summary, next job button
9. Earnings - today's jobs and estimated pay
10. Profile - shift start/end, personal stats

### Screen Flow
Manager Creates Job -> Team Assigned -> Team Receives on App -> Navigate -> Complete Tasks -> Photos -> Customer Sign-off

## 8. Business Model

- **الباقة الأساسية**: $29/شهر (فريق واحد، حتى 50 Job/شهر)
- **الباقة الاحترافية**: $69/شهر (حتى 3 فرق، غير محدود الـJobs، تقارير)
- **باقة المؤسسات**: $149/شهر (غير محدود الفرق، تطبيق عميل، API)
- فترة تجربة مجانية: 14 يوماً
- MRR المستهدف لكل عميل: $29-$149

## 9. Implementation Plan

- Phase 1 (Weeks 1-2): Laravel API - Auth, Company/Service/Worker/Team CRUD
- Phase 2 (Weeks 3-4): Job management, scheduling engine, GPS tracking
- Phase 3 (Weeks 5-6): React Dashboard - Map, Gantt calendar, quality dashboard
- Phase 4 (Weeks 7-8): Flutter Team App - Jobs, photos, tasks, navigation
- Phase 5 (Weeks 9-10): Customer app, invoice automation, deployment

## 10. Risk & Mitigation

- **مخاطرة تقنية**: تتبع المواقع الحي للفرق يستهلك بطارية وبيانات
  - التخفيف: تحديث الموقع كل 5 دقائق فقط أثناء ساعات العمل، وضع توفير الطاقة
- **مخاطرة سوقية**: ارتفاع معدل دوران العمال في قطاع التنظيف
  - التخفيف: واجهة بسيطة لا تتطلب تدريباً طويلاً، دعم فوري عند انضمام عامل جديد
- **مخاطرة تشغيلية**: اختلاف جودة الخدمة بين الفرق
  - التخفيف: قائمة مهام موحدة، توثيق بالصور قبل/بعد، تقييم العميل إلزامي
