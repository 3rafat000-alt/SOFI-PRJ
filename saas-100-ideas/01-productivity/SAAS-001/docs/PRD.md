# PRD: TaskSync Pro (SAAS-001)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner**: نظام إدارة مهام ومشاريع سحابي بسيط للفرق الصغيرة مع تتبع وقت مدمج ولوحات Kanban مرئية — يركز على تجربة الفرق العربية.
- **Problem statement**: الفرق الصغيرة والشركات الناشئة تعاني من تعقيد أدوات إدارة المشاريع الحالية (Jira، Asana) أو تفتقر لدعم اللغة العربية والتكاملات المحلية.
- **Proposed solution**: Laravel API + React Dashboard + Flutter App — إصدار خفيف ب 3 مستخدمين مجاناً، تتبع وقت مدمج، تقارير أداء، ولوحة Kanban بالسحب والإفلات.

## 2. Market & Opportunity
- **Target market size**: سوق أدوات إدارة المشاريع العالمي ~$7B (2025)، النمو 12% CAGR. الشرق الأوسط ~$400M قطاع يحتاج توطين.
- **Customer segment**: B2B — فرق 3-20 شخص، شركات ناشئة، أقسام تقنية صغيرة.
- **Competitor landscape**:
  1. **Asana**: قوي لكن معقد، دعم عربي ضعيف، سعره مرتفع للفرق الصغيرة.
  2. **Trello**: بسيط لكن محدود بدون تتبع وقت مهام.
  3. **ClickUp**: ميزات كثيرة جداً مع منحنى تعلم حاد.
  4. **Monday.com**: واجهة جميلة لكن سعر باهظ.
  5. **Notion**: مرن لكن ليس أداة إدارة مشاريع خالصة.
- **Differentiation**: عربي بالكامل، تتبع وقت مدمج، سعر مخفض للفرق الصغيرة، تكامل مع تطبيقات محلية (WhatsApp، حساب موحد).

## 3. User Personas

### Primary: سارة — مديرة فريق تسويق
- **الدور**: مديرة فريق ناشئ في شركة تقنية، فريقها 7 أشخاص.
- **الأهداف**: توزيع المهام، متابعة التقدم، معرفة وقت كل مشروع.
- **نقاط الألم**: تفتقد لتقارير أداء بسيطة بالعربي، الأدوات الحالية معقدة.

### Secondary: أحمد — مطور مستقل (Freelancer)
- **الدور**: مطور ويب يدير 3-4 مشاريع متزامنة مع عملاء.
- **الأهداف**: تتبع وقت كل مشروع، إرسال تقارير زمنية للعملاء.
- **نقاط الألم**: لا يريد دفع $30/شهر لأداة واحدة.

### Admin: يوسف — مدير تقنية
- **الدور**: يدير حسابات الفريق، يراجع الإنتاجية.
- **الأهداف**: إضافة/إزالة مستخدمين، تصدير تقارير، رؤية كل المشاريع.
- **نقاط الألم**: ضوابط صلاحيات غير واضحة، صعوبة دمج أدوات متعددة.

## 4. Features by Platform

### Laravel API (Backend)
- Core domain models: User, Team, Project, Task, TimeEntry, Comment, Label, Attachment
- RESTful endpoints: CRUD for all core models
- Auth: Sanctum token-based, email/Google OAuth, role-based (owner/admin/member)
- Notifications: push (Flutter), email (Laravel Mail), SMS (Twilio) — due dates, mentions
- Reporting engine: export time reports (PDF/CSV)
- Integrations: Slack webhook, WhatsApp Cloud API

### React Dashboard (Web)
- Admin panel: team management, subscription/billing
- Kanban board: drag & drop columns (To Do / In Progress / Done)
- Project timeline: Gantt-chart-style view
- Time tracking: start/stop timer per task, manual entry
- Reports dashboard: pie charts per project, member workload, burndown
- Settings: workspace config, labels, integrations

### Flutter App (Mobile)
- Customer-facing: view tasks, update status, comment
- Time tracker: play/pause from notification bar, offline sync
- Push notifications: task assigned, @mention, deadline approaching
- Quick add: voice-to-task (Arabic), photo attachment
- Offline: queue changes when offline, sync on reconnect

## 5. Data Model (MVP)

| Entity | Fields | Relationships |
|---|---|---|
| User | id, name, email, password, avatar, locale, timezone | belongsToMany Team (pivot: role) |
| Team | id, name, slug, owner_id, max_members, plan | hasMany Project, belongsToMany User |
| Project | id, team_id, name, description, color, status, start_date, end_date | belongsTo Team, hasMany Task |
| Task | id, project_id, assignee_id, title, description, priority, status, due_date, estimated_minutes | belongsTo Project/User, hasMany TimeEntry |
| TimeEntry | id, task_id, user_id, started_at, ended_at, duration_minutes, note | belongsTo Task, belongsTo User |
| Comment | id, task_id, user_id, body, attachment | belongsTo Task, belongsTo User |
| Label | id, team_id, name, color | belongsTo Team, morphToMany Task |

## 6. API Endpoints (MVP)

| Method | Endpoint | Description |
|---|---|---|
| POST | /api/auth/register | Register user |
| POST | /api/auth/login | Login |
| GET | /api/me | Current user profile |
| GET | /api/teams | List teams |
| POST | /api/teams | Create team |
| GET | /api/teams/{id}/projects | Projects in team |
| CRUD | /api/projects/{id}/tasks | Task CRUD |
| GET | /api/tasks/{id}/time-entries | Time entries per task |
| POST | /api/tasks/{id}/time-entries/start | Start timer |
| PATCH | /api/tasks/{id}/time-entries/stop | Stop timer |
| GET | /api/reports/time | Time report (query: team_id, from, to) |

## 7. User Interface (Screen List)

### Dashboard screens (React)
- Login / Register / Forgot Password
- Workspace selector
- Dashboard: project cards, member workload, upcoming deadlines
- Kanban board per project
- Task detail modal (side panel)
- Timeline / Gantt view
- Time report page (filters, export)
- Team settings page
- User profile settings

### Mobile screens (Flutter)
- Login / Register
- Home: My Tasks, Today's tasks
- Task list (filterable by project/priority/status)
- Task detail (comment, timer, attachment)
- Timer widget (bottom bar, persistent)
- Profile & settings

### Screen flow (text)
```
Login → Workspace Selector → Dashboard
                                ├── Project List → Select Project → Kanban Board
                                │                                    └── Task Detail (side modal)
                                ├── Reports → Time Report (filter/export)
                                └── Settings → Team / Profile / Billing
```

## 8. Business Model
- **Free**: Up to 3 members, 5 projects, unlimited time tracking
- **Pro**: $12/seat/month — 25 projects, labels, integrations, reports export
- **Business**: $19/seat/month — unlimited projects, Gantt, priority support, API access
- **Free trial**: 14-day Pro trial (no credit card)

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2)**: Laravel API — Auth, User, Team, Project, Task CRUD, TimeEntry
- **Phase 2 (Weeks 3-4)**: React Dashboard — Login, Workspace, Kanban board, Task detail modal
- **Phase 3 (Weeks 5-6)**: Flutter App — Auth, Task list, Timer, Push notifications
- **Phase 4 (Weeks 7-8)**: Reports engine, Integrations (WhatsApp/Slack), Testing, Deploy

## 10. Risk & Mitigation
- **Technical**: Offline sync complexity — strategy: queue + conflict resolution via `updated_at` timestamps.
- **Market**: Freemium cannibalization — strategy: limit free tier to 3 users to drive upgrades.
- **Mitigation**: MVP launch targeting Arabic market first as beachhead.
