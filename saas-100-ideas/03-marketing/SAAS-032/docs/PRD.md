# PRD: LeadFunnel (SAAS-032)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** نظام متكامل لإدارة العملاء المحتملين ومراحل المبيعات مع تنبيهات ذكية وتحليلات أداء لفرق المبيعات
- **Problem:** فرق المبيعات تعاني من ضياع العملاء المحتملين بين القنوات المختلفة، صعوبة تتبع مراحل البيع، وعدم وجود تنبيهات استباقية للمتابعة
- **Proposed solution:** Laravel API + React Dashboard + Flutter App تقدم إدارة كاملة لدورة حياة العميل المحتمل (Lead) من أول اتصال حتى إغلاق الصفقة

## 2. Market & Opportunity
- **Target market size:** سوق CRM العالمي ~$80B. قطاع العقارات والتجارة الإلكترونية في الشرق الأوسط يشهد نمواً سريعاً
- **Customer segment:** B2B — فرق مبيعات صغيرة ومتوسطة، شركات عقارية، وكلاء مبيعات أفراد
- **Competitor landscape:**
  1. Salesforce (مهيمن لكن معقد ومكلف للفرق الصغيرة)
  2. HubSpot (جيد لكن النسخة المجانية محدودة جداً)
  3. Pipedrive (ممتاز لإدارة الصفقات لكن لا يركز على المنطقة العربية)
  4. Zoho CRM (منافس جيد لكن واجهته قديمة نسبياً)
  5. Freshsales (سهل لكن يفتقر لميزات عقارية متخصصة)
- **Differentiation:** تركيز على السوق العربي، تكامل مع واتساب لتسجيل العملاء، أسعار مخفضة للشركات الناشئة، تنبيهات ذكية مدعومة بالذكاء الاصطناعي

## 3. User Personas

### Primary: مدير مبيعات (خالد)
- **Role:** مدير فريق مبيعات في شركة تطوير عقاري
- **Goals:** توزيع العملاء على فريق المبيعات، متابعة مراحل كل صفقة، تحقيق أهداف المبيعات الشهرية
- **Pain points:** صعوبة معرفة من يعمل على أي عميل، تأخير متابعة العملاء، تقارير المبيعات تستغرق وقتاً طويلاً

### Secondary: مندوب مبيعات (ليلى)
- **Role:** مندوبة مبيعات في شركة تجارية
- **Goals:** تسجيل عملاء جدد بسرعة، متابعة مهام اليوم، رفع تقارير الأداء
- **Pain points:** تنسيق العملاء على Excel غير دقيق، تنسى مواعيد المتابعة، لا توجد تنبيهات للمهام

### Admin: مدير النظام
- **Dashboard operator:** يدير المستخدمين، يراقب أداء الفريق، يضبط خطط الأسعار

## 4. Features by Platform

### Laravel API (Backend)
- Lead pipeline management (stages: new → contacted → qualified → proposal → negotiation → won/lost)
- Lead scoring engine (assign score based on actions, source, engagement)
- Activity tracking (calls, emails, meetings logged per lead)
- Task automation (auto-assign leads, scheduled follow-up reminders)
- Smart notifications (WhatsApp, email, SMS, push)
- Reports & analytics engine (conversion rates, velocity, team performance)

### React Dashboard (Web)
- Kanban board view of pipeline stages
- Lead profile pages with full activity history
- Performance dashboards (win rate, conversion, revenue forecast)
- Import leads from CSV/Excel
- Custom pipeline stages per team
- Team management and role assignment
- WhatsApp integration — log messages as lead activities

### Flutter App (Mobile)
- Lead list with quick filter and search
- Add lead via form or phone contact import
- Receive notifications for follow-ups
- Call/WhatsApp/SMS lead directly from app
- Daily task summary
- Pipeline view for mobile

## 5. Data Model (MVP)
- **User:** id, name, email, role, team_id, phone, avatar
- **Team:** id, name, description, created_at
- **Lead:** id, name, email, phone, source, score, status, stage, assigned_to, created_at
- **LeadActivity:** id, lead_id, user_id, type (call/email/meeting/note), description, outcome, created_at
- **Pipeline:** id, name, stages (JSON), default_for_team_id
- **Deal:** id, lead_id, value, expected_close_date, stage, probability, notes
- **Task:** id, lead_id, user_id, title, due_date, completed, type

## 6. API Endpoints (MVP)
- `GET /api/leads` — list leads (with filters, search)
- `POST /api/leads` — create lead
- `GET /api/leads/{id}` — get lead profile + activities
- `PUT /api/leads/{id}` — update lead
- `PATCH /api/leads/{id}/stage` — move lead stage
- `DELETE /api/leads/{id}` — soft delete lead
- `GET /api/leads/{id}/activities` — activities
- `POST /api/leads/{id}/activities` — log activity
- `POST /api/auth/login` — login
- `POST /api/auth/register` — register
- `GET /api/dashboard/stats` — pipeline overview stats
- `GET /api/teams` — team management

## 7. User Interface (Screen List)
- **Dashboard:**
  - Login/Register
  - Pipeline Kanban (drag-drop between stages)
  - Lead detail page (info + activity feed)
  - Analytics dashboard (conversion funnel, team stats)
  - Import leads (CSV/Excel)
  - Settings (team, pipeline config, integrations)
- **Mobile:**
  - Login
  - Lead list with search + filters
  - Lead detail (info + add activity)
  - Pipeline summary
  - Daily tasks view
  - Quick add lead

## 8. Business Model
- **Pricing tiers:**
  - Starter ($29/mo): 500 leads, 3 users
  - Pro ($59/mo): 2,000 leads, 10 users, WhatsApp integration
  - Enterprise ($99/mo): unlimited leads, unlimited users, API access
- **Free trial:** 14-day free trial
- **Target MRR per client:** $29-$99/month

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2):** Laravel API — Lead CRUD, Pipeline stages, Activity logging, Auth
- **Phase 2 (Weeks 3-4):** React Dashboard — Kanban board, Lead detail, Import, Dashboard
- **Phase 3 (Weeks 5-6):** Flutter App — Lead list, Add lead, Tasks, Notifications
- **Phase 4 (Weeks 7-8):** Reports, WhatsApp integration, Testing, Deploy

## 10. Risk & Mitigation
- **Technical risk:** Real-time Kanban sync across users
  - *Mitigation:* Use broadcasting + Laravel Reverb for WebSocket sync
- **Market risk:** CRM market is crowded
  - *Mitigation:* Start with real estate niche, expand after PMF
- **Integration risk:** WhatsApp API restrictions
  - *Mitigation:* Use WhatsApp Business API partner (Twilio/MessageBird)
