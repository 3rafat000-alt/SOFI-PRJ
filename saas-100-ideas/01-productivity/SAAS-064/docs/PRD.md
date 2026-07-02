# PRD: FreelanceHub (SAAS-064)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner**: منصة إدارة أعمال الفريلانسر — مشاريع، فواتير، عملاء، مدفوعات، وتتبع الوقت.
- **Problem statement**: الفريلانسر (المستقلون) يعانون من فوضى إدارة المشاريع، تأخر الدفعات، صعوبة تتبع الوقت، وغياب نظام موحد للفواتير والمحاسبة البسيطة.
- **Proposed solution**: Laravel API + React Dashboard + Flutter App — إدارة شاملة للمشاريع، تتبع الوقت، فواتير احترافية، بوابات دفع مدمجة.

## 2. Market & Opportunity
- **Target market size**: سوق منصات العمل الحر ~$15B عالمياً، الشرق الأوسط ~$1B ونمو سريع (اقتصاد الوظائف المؤقتة).
- **Customer segment**: B2C — مستقلون عرب (مطورين، مصممين، كتاب، مسوقين)، B2B — شركات صغيرة توظف مستقلين.
- **Competitor landscape**:
  1. **FreshBooks**: محاسبة رائعة لكن غير عربي، باهظ ($19+).
  2. **Toggl Track**: تتبع وقت ممتاز لكن بدون فواتير أو إدارة عملاء.
  3. **Bonsai**: مخصص للفريلانسر لكن إنجليزي فقط، دعم عربي محدود.
  4. **Harvest**: تتبع وقت وفواتير لكن $12/شهر، بدون عربي.
  5. **مستقل / خمسات**: منصات عربية لكن للبحث عن عمل وليس إدارة الأعمال.
- **Differentiation**: عربي بالكامل، بوابات دفع محلية (STC Pay، تمارا، تابي)، قوالب فواتير عربية، تتبع وقت ذكي، تكامل مع منصات العمل الحر.

## 3. User Personas

### Primary: عمر — مطور ويب مستقل (Full-stack)
- **الدور**: يعمل مع 5-10 عملاء شهرياً، مشاريع قصيرة ومتوسطة.
- **الأهداف**: إدارة المشاريع، إرسال فواتير احترافية، تتبع الوقت بدقة.
- **نقاط الألم**: الفواتير تأخذ وقتاً طويلاً، العملاء يتأخرون في الدفع، لا يعرف كم ساعة قضاها فعلاً.

### Secondary: لنا — مصممة جرافيك مستقلة
- **الدور**: تصمم شعارات وهويات بصرية، تعمل مع عملاء عبر وسائل التواصل.
- **الأهداف**: عرض ملف أعمال، إرسال عقود، استلام دفعات بسهولة.
- **نقاط الألم**: لا يوجد نظام لعرض التصاميم للمراجعة، صعوبة إدارة المراجعات.

### Admin: عبدالله — فريلانسر محترف (يدير فريق)
- **الدور**: يدير فريق صغير من 3-5 مستقلين، يسند مهاماً فرعية.
- **الأهداف**: توزيع المهام، متابعة سير العمل، إصدار فواتير موحدة للعملاء.
- **نقاط الألم**: لا توجد رؤية لتقدم الفريق، صعوبة حساب أرباح كل مشروع.

## 4. Features by Platform

### Laravel API (Backend)
- Core domain models: User, Client, Project, Task, TimeEntry, Invoice, Payment, Contract, Proposal, Expense
- RESTful endpoints: CRUD for all models
- Auth: Sanctum + social login (Google, Apple)
- Project management: status pipeline (draft → active → review → completed → archived)
- Time tracking: start/stop timer, manual entry, weekly timesheet
- Invoice generation: auto-calc from time entries or flat fee, tax handling (TVA/VAT)
- Payment integration: Stripe, PayPal, STC Pay, Tamara, Tabby
- Contract templates: NDA, service agreement, milestone-based
- Proposal builder: estimate → client approval → convert to project
- Expense tracking: per project, categories, receipt photo upload
- Reporting: earnings, hours, tax summary, profitability per client

### React Dashboard (Web)
- Dashboard: active projects, pending invoices, this month earnings
- Project list → project detail (tasks, time, expenses, files)
- Task board: Kanban (to-do → in progress → review → done)
- Time tracker: timer control, weekly view, edit entries
- Invoice manager: create, send, track (paid/pending/overdue)
- Client manager: contact info, project history, payment terms
- Proposal builder: template editor, send for e-signature
- Reports: earnings chart, hours breakdown, tax report
- Settings: profile, payment methods, tax info, invoice templates

### Flutter App (Mobile)
- Timer: start/stop project timer, quick task entry
- Tasks: view today's tasks, quick status update
- Invoices: view pending/paid, send payment reminders
- Notifications: invoice paid, client message, deadline approaching
- Expenses: snap receipt photo, categorize, attach to project
- Offline: time entries cached and synced when online

## 5. Data Model (MVP)

| Entity | Fields | Relationships |
|---|---|---|
| User (Freelancer) | id, name, email, phone, avatar, tax_id, payment_info | hasMany Client, Project, Invoice |
| Client | id, user_id, name, company, email, phone, address, payment_terms | belongsTo User |
| Project | id, user_id, client_id, title, description, rate_type (hourly/fixed), rate_amount, budget, status, start_date, deadline | belongsTo User/Client |
| Task | id, project_id, title, description, status, estimated_hours, priority | belongsTo Project |
| TimeEntry | id, task_id, user_id, start_time, end_time, duration_minutes, notes, billable | belongsTo Task |
| Invoice | id, user_id, client_id, project_id, number, items, subtotal, tax, total, status, due_date, paid_at | belongsTo User/Client |
| Payment | id, invoice_id, amount, method, gateway, transaction_id, paid_at | belongsTo Invoice |
| Expense | id, project_id, amount, category, receipt_url, date | belongsTo Project |

## 6. API Endpoints (MVP)

| Method | Endpoint | Description |
|---|---|---|
| POST | /api/auth/register | Register freelancer |
| POST | /api/auth/login | Login |
| GET | /api/projects | List projects |
| POST | /api/projects | Create project |
| GET | /api/tasks | List tasks (filterable: project, status) |
| POST | /api/time-entries/start | Start timer |
| POST | /api/time-entries/stop | Stop timer |
| GET | /api/invoices | List invoices |
| POST | /api/invoices | Generate invoice |
| POST | /api/invoices/{id}/send | Send to client |
| GET | /api/dashboard/earnings | Earnings stats |

## 7. User Interface (Screen List)

### Dashboard screens (React)
- Login/Register
- Dashboard: earnings, active projects, pending invoices
- Project list → project detail (tasks, time, files, team)
- Task Kanban board (drag & drop)
- Time tracker: timer + weekly view
- Invoice manager: create → send → track → paid
- Client list → client detail (projects, invoices)
- Proposals: create → send → approved
- Reports: earnings, hours, taxes
- Settings: profile, payment, templates

### Mobile screens (Flutter)
- Login
- Home: today's tasks, timer start/stop
- Projects list → project detail → tasks → add time
- Invoices list → invoice detail → send reminder
- Expenses: capture receipt → categorize
- Notifications

### Screen flow (text)
```
Login → Dashboard (earnings + active projects)
           ├── Projects → Create → Add Tasks → Start Timer
           │            → Detail → Tasks Board (Kanban) → Time Entries
           │            → Expenses → Add Receipt
           ├── Invoices → Create (from time entries / flat fee)
           │            → Send to Client → Track → Mark Paid
           ├── Clients → Add → Detail → Project History
           ├── Proposals → Create → Send → Client Approval
           └── Reports → Earnings / Hours / Profitability
```

## 8. Business Model
- **Free**: $0 — up to 3 projects, 10 invoices/month, basic time tracking
- **Pro**: $9/month — unlimited projects, proposals, expenses, payment integrations
- **Business**: $19/month — team up to 5, role management, white-label invoices
- **Free trial**: Freemium model (free tier always available)

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2)**: Laravel API — Auth, Project, Client, Task CRUD, Time tracking
- **Phase 2 (Weeks 3-4)**: React Dashboard — Project board, Timer, Invoice generator
- **Phase 3 (Weeks 5-6)**: Flutter App — Timer, Tasks, Invoices, Expenses
- **Phase 4 (Weeks 7-8)**: Payment integrations, Proposals, Reports, Testing, Deploy

## 10. Risk & Mitigation
- **Technical**: Timer accuracy across devices — strategy: server-side validation, idle detection.
- **Market**: Freelancers price-sensitive — strategy: strong free tier, $9 Pro affordable.
- **Competitive**: Many similar tools — strategy: focus on Arabic + local payment gateways as moat.
- **Monetization**: Freemium conversion — strategy: limit projects not time (time is habit, upsell after 3rd project).
