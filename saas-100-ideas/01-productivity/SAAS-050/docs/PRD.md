# PRD: DevSync (SAAS-050)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** منصة إدارة مشاريع برمجية متكاملة — Sprints، Bugs، Repository، Code Review، كل شيء في مكان واحد.
- **Problem:** فرق البرمجة تستخدم أدوات متفرقة (Jira للتذاكر، GitHub لل code، Slack للتواصل) مما يسبب تشتت وفقدان السياق.
- **Solution:** DevSync — منصة موحدة تجمع إدارة المهام (Sprints)، تتبع الأخطاء، مراجعة الكود، وربط المستودع في لوحة واحدة.

## 2. Market & Opportunity
- **Target market:** سوق أدوات إدارة المشاريع البرمجية ~$12B (2025). أدوات Agile ~$6B.
- **Customer segment:** B2B — فرق برمجة (3-50 شخص)، شركات تقنية ناشئة، استوديوهات تطوير.
- **Competitors:**
  - Jira (Atlassian): المعيار الصناعي لكن معقد وبطيء وغالي ($10/مستخدم).
  - Linear: سريع وجميل لكن $16/مستخدم، بدون code review مدمج.
  - GitHub Projects: مجاني مع GitHub لكن محدود في إدارة Sprints.
  - ClickUp: شامل لكن عام جداً، ليس مخصصاً للبرمجة.
- **Differentiation:** أول منصة تدمج Sprints + Bugs + Code Review + Git في واجهة واحدة موحدة، بسعر أقل 50% من Jira.

## 3. User Personas

### الشخصية الأساسية: يوسف — قائد فريق برمجة (Tech Lead)
- **الدور:** يدير فريق 8 مطورين ويشرف على جودة الكود
- **الأهداف:** تتبع سرعة السبرنت، ضمان جودة ال code reviews، إدارة المهام
- **المشكلات:** Jira بطيء ومعقد، GitHub PRs منفصلة عن المهام، التشتت بين 3 أدوات

### الشخصية الثانوية: لينا — مطورة برمجيات
- **الدور:** تكتب كود، ترفع PRs، تحل bugs
- **الأهداف:** التركيز على البرمجة بدون تغيير سياق بين الأدوات
- **المشكلات:** تضيع بين Jira + GitHub + Slack، الـ context switching يستنزف الوقت

### Admin: مدير المشروع
- يدير المشاريع، الأعضاء، sprints، صلاحيات، تقارير الأداء.

## 4. Features by Platform

### Laravel API (Backend)
- Models: Project, Sprint, Ticket, Bug, Task, PRLink, Comment, CodeReview, Repository, Release
- Sprint management: active/backlog/completed, velocity tracking
- Ticket states: Open → In Progress → In Review → Done → Closed
- Bug tracking: severity, environment, steps to reproduce, attachment
- PR integration: link GitHub/GitLab PR to ticket
- Code review workflow: request review → approve/request changes → merge
- Notification system: in-app, email, Slack webhook, Telegram bot

### React Dashboard (Web)
- Sprint board (Kanban): drag tickets between columns (To Do/In Progress/In Review/Done)
- Backlog: prioritized list, quick add, bulk move to sprint
- Ticket detail: sidebar with PR links, comments, activity timeline, linked bugs
- Code review panel: diff view (embedded), comment per line, approve/reject
- Repository browser: branches, commit history (read-only via API)
- Analytics: sprint burndown, velocity trend, cycle time, bug rate, contributor stats
- Team view: workload per developer, PRs awaiting review, open bugs assigned

### Flutter App (Mobile)
- Sprint board: compact Kanban, quick status update
- Ticket detail: title, status, assignee, priority, description
- Comment on tickets: quick reply, @mentions
- Push notifications: assigned ticket, PR review requested, @mention
- Quick bug report: title, severity, description + photo/attachment
- PR review notifications + approve/request changes quick action

## 5. Data Model (MVP)
- **Project**: id, name, key (e.g. DEV), description, repository_url, owner_id
- **Sprint**: id, project_id, name (Sprint 1), goal, start_date, end_date, status (active/completed/backlog)
- **Ticket**: id, project_id, sprint_id, type (task/bug/story/epic), title, description, status, priority, assignee_id, reporter_id, story_points, linked_pr_url
- **Bug**: id, ticket_id, severity (critical/major/minor/trivial), environment, steps_to_reproduce, found_in_version, fixed_in_version
- **PRLink**: id, ticket_id, pr_url, pr_provider (github/gitlab), status (open/merged/closed)
- **CodeReview**: id, pr_url, reviewer_id, status (pending/approved/changes-requested), comments_count, reviewed_at
- **Comment**: id, ticket_id, user_id, body, attachment_url, created_at
- **Release**: id, project_id, version, tag_name, tickets_json, released_at

## 6. API Endpoints (MVP)
- `CRUD /api/projects` — project management
- `CRUD /api/sprints` — sprint CRUD + activate/complete
- `CRUD /api/tickets` — with filters (sprint, status, assignee, type)
- `PATCH /api/tickets/{id}/status` — quick status change (for drag)
- `POST /api/tickets/{id}/assign` — assign/reassign
- `CRUD /api/bugs` — bug details linked to ticket
- `POST /api/tickets/{id}/link-pr` — link PR
- `CRUD /api/code-reviews` — review request/response
- `POST /api/code-reviews/{id}/approve` — approve
- `POST /api/code-reviews/{id}/request-changes` — request changes
- `CRUD /api/comments` — ticket comments
- `GET /api/analytics/sprint/{id}` — burndown data
- `GET /api/analytics/velocity` — velocity chart data
- `POST /api/auth/login`, `POST /api/auth/register`

## 7. User Interface (Screen List)
- **Dashboard** (React): Active sprint board (Kanban) → drag tickets, quick filters
- **Backlog** (React): Prioritized list → multi-select → move to sprint
- **Ticket Detail** (React): Full view with tabs (description, comments, PR links, activity log)
- **Sprint Planning** (React): Drag tickets from backlog, set story points, view capacity
- **Code Review** (React): Inline diff, comment per line, approve/reject buttons, checks display
- **Analytics** (React): Burndown chart, velocity bar chart, cycle time scatter, bug rate line
- **Repository** (React): Branches list, commits, CI status badges (read-only from provider)
- **Settings** (React): Project setup, GitHub/GitLab integration, webhooks, team roles
- **Mobile** (Flutter): My tickets → sprint board compact → quick status update → comment
- **Mobile Notifications**: PR review request, @mention, bug assigned → tap to detail

## 8. Business Model
- **Free**: 1 project, 5 users, basic Kanban, no code review
- **Team**: $8/month — 5 projects, 15 users, code review, GitHub integration, sprint analytics
- **Business**: $19/month — 20 projects, 50 users, all features, priority support, API
- **Enterprise**: Custom — unlimited, on-prem option, SSO, SLA
- **Free trial**: 14 days Business
- **Target MRR/client**: $8–$19 (Team/Business avg $12)

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2)**: Laravel API — Project/Sprint/Ticket/Bug models + CRUD + status transitions
- **Phase 2 (Weeks 3-4)**: React Dashboard — Kanban board, backlog, ticket detail, drag-drop
- **Phase 3 (Weeks 5-6)**: Flutter App — sprint board, ticket view, comment, push notifications
- **Phase 4 (Weeks 7-8)**: Code review system, GitHub/GitLab API integration, analytics, burndown chart, testing

## 10. Risk & Mitigation
- **Technical**: GitHub API rate limits → Mitigation: webhook-driven sync + caching, batch updates
- **Technical**: Drag-drop Kanban performance with 1000+ tickets → Mitigation: virtualized list, pagination, optimistic updates
- **Market**: Jira lock-in (teams already use it) → Mitigation: Jira import tool, migration guide, lower price, faster UX
- **Competitive**: Linear growing fast → Mitigation: code review + local PR integration (Linear lacks), Arabic interface
