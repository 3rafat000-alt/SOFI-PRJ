# PRD: NetworkHub CRM (SAAS-049)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** نظام إدارة علاقات عملاء بسيط للشركات الصغيرة — جهات اتصال، تتبع تفاعلات، مهام متابعة، تقارير مبيعات.
- **Problem:** الشركات الصغيرة والفريلانسر يحتاجون CRM لكن الحلول الحالية (Salesforce, HubSpot) غالية ومعقدة. Excel لا يكفي لتتبع التفاعلات والمهام.
- **Solution:** NetworkHub CRM — إدارة جهات اتصال سهلة، تسجيل تلقائي للتفاعلات، مهام متابعة ذكية، وتحليلات مبيعات بأسعار معقولة.

## 2. Market & Opportunity
- **Target market:** سوق CRM ~$80B (2025). قطاع الشركات الصغيرة ~$15B.
- **Customer segment:** B2B — شركات صغيرة (1-50 موظف)، فريلانسر، مستشارين، محامين.
- **Competitors:**
  - HubSpot CRM: مجاني أساسي لكن محدود، الترقية غالية ($50/شهر).
  - Salesforce: قوي جداً لكن معقد وغالي ($150/شهر/مستخدم).
  - Zoho CRM: جيد مجاني لكن واجهة معقدة.
  - Pipedrive: ممتاز للمبيعات لكن غالي ($15/شهر/مستخدم).
- **Differentiation:** واجهة عربية بسيطة، تسعير ثابت (ليس لكل مستخدم)، تكامل مع واتساب، تتبع تلقائي للتفاعلات.

## 3. User Personas

### الشخصية الأساسية: كريم — مستشار تسويق (فريلانسر)
- **الدور:** يدير علاقات مع 50-100 عميل محتمل وحالي
- **الأهداف:** تتبع المحادثات، متابعة العروض، معرفة العملاء المتوقعين
- **المشكلات:** ينسى متابعة العملاء، لا يوجد سجل موحد للتواصل، الخلط بين العروض

### الشخصية الثانوية: رنا — مديرة مبيعات في شركة صغيرة
- **الدور:** تدير فريق مبيعات 5 أشخاص وتحتاج رؤية واضحة للصفقات
- **الأهداف:** تتبع مراحل البيع، تقارير أداء الفريق، التنبؤ بالمبيعات
- **المشكلات:** لا رؤية موحدة، صعوبة تقييم أداء المندوبين، ضياع الصفقات

### Admin: مشرف النظام
- يدير المستخدمين، الصلاحيات، إعدادات التكامل، خطط الأسعار.

## 4. Features by Platform

### Laravel API (Backend)
- Models: Contact, Company, Deal, Interaction, Task, Pipeline, Note, Tag
- Pipeline management: stages with drag-drop ordering
- Interaction logging: email, call, meeting, WhatsApp (via API)
- Task automation: follow-up reminders, birthday greetings, inactivity alerts
- Dashboard analytics: conversion rate, deal value, activity volume

### React Dashboard (Web)
- Contact manager: list/kanban, quick add, merge duplicates, import CSV
- Deal pipeline: drag deals between stages, add note, schedule follow-up
- Activity feed: timeline per contact/deal with all interactions
- Task list: today's tasks, overdue, upcoming, assign to team
- Reports: sales forecast, conversion funnel, team leaderboard
- Email integration: send/receive via IMAP/SMTP, auto-log

### Flutter App (Mobile)
- Contact list with search, call/email/WhatsApp tap
- Add interaction quickly: meeting/call/note + timestamp
- Deal pipeline view: quick status update
- Task notifications: follow-up reminders, overdue alerts
- Voice notes: record and auto-transcribe to note
- QR scan: add contact from business card

## 5. Data Model (MVP)
- **Contact**: id, name, email, phone, company_id, position, source, tags_json, assigned_to, life_stage (lead/customer/churned)
- **Company**: id, name, industry, size, website, phone, address
- **Deal**: id, name, value, pipeline_id, stage, contact_id, company_id, probability, expected_close_date, owner_id
- **Pipeline**: id, name, stages_json (ordered list), organization_id
- **Interaction**: id, contact_id, deal_id, type (email/call/meeting/whatsapp/note), subject, body, timestamp, created_by
- **Task**: id, title, description, due_date, priority, status (pending/completed), contact_id, deal_id, assigned_to
- **Note**: id, contact_id/deal_id, body, created_by, created_at

## 6. API Endpoints (MVP)
- `CRUD /api/contacts` — full CRUD + search + import CSV
- `CRUD /api/companies` — company management
- `CRUD /api/deals` — deal CRUD with pipeline stage
- `PATCH /api/deals/{id}/stage` — move deal stage
- `CRUD /api/pipelines` — pipeline + stages CRUD
- `CRUD /api/interactions` — interactions log
- `CRUD /api/tasks` — tasks with filters (due, status, assignee)
- `GET /api/reports/sales-forecast` — forecast data
- `GET /api/reports/team-performance` — team metrics
- `GET /api/me/activities` — user activity feed
- `POST /api/auth/login`, `POST /api/auth/register`

## 7. User Interface (Screen List)
- **Dashboard** (React): KPI cards (deals won, pipeline value, tasks overdue), activity feed, deal chart
- **Contacts** (React): Table with filters → click → contact detail (timeline, deals, tasks)
- **Deal Pipeline** (React): Kanban board → drag columns → click for detail → edit stage
- **Task Manager** (React): List/calendar view, filter by priority/assignee/status
- **Reports** (React): Funnel chart, monthly trend, team leaderboard, export PDF
- **Email Integration** (React): Connected inbox, compose, templates, auto-log
- **Settings** (React): Pipeline config, tags, team, email/WhatsApp integration
- **Mobile** (Flutter): Contacts → tap → call/email/WhatsApp → add interaction
- **Mobile Deals**: Pipeline quick view → swipe stage → add note
- **Mobile Tasks**: Today list → complete → snooze

## 8. Business Model
- **Free**: 50 contacts, 1 pipeline, basic tasks, 1 user
- **Starter**: $15/month — 500 contacts, 3 pipelines, email integration, 3 users
- **Pro**: $39/month — 2K contacts, unlimited pipelines, WhatsApp integration, reports, 10 users
- **Enterprise**: $99/month — unlimited, custom fields, API, SSO, priority support
- **Free trial**: 14 days Pro
- **Target MRR/client**: $15–$39

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2)**: Laravel API — Contact/Company/Deal/Pipeline models + CRUD + search
- **Phase 2 (Weeks 3-4)**: React Dashboard — contact manager, deal kanban, task list, reports
- **Phase 3 (Weeks 5-6)**: Flutter App — contact list, deal view, task notifications, quick add interaction
- **Phase 4 (Weeks 7-8)**: Email + WhatsApp integration, CSV import, export, testing, deployment

## 10. Risk & Mitigation
- **Technical**: Email integration complexity (IMAP sync) → Mitigation: start with outgoing only via SMTP, add IMAP in phase 2
- **Market**: HubSpot free tier dominance → Mitigation: simpler UX, Arabic-first, fixed pricing (not per user), WhatsApp integration
- **Technical**: Data import quality → Mitigation: CSV preview before import, duplicate detection with merge, mandatory field mapping
