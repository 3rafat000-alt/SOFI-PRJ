# PRD: SupportDesk (SAAS-033)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** نظام تذاكر دعم فني ذكي يستقبل الشكاوى ويوزعها على الفريق ويتتبع الحلول مع تقييم رضا العملاء
- **Problem:** الشركات التقنية ومزودو الخدمات يعانون من فوضى التذاكر، تأخير في الاستجابة، عدم وضوح في توزيع المهام، وصعوبة قياس أداء الدعم
- **Proposed solution:** Laravel API + React Dashboard + Flutter App تقدم نظام تذاكر متكامل مع توزيع تلقائي، متابعة آنية، وتقارير أداء

## 2. Market & Opportunity
- **Target market size:** سوق أنظمة تذاكر الدعم الفني ~$10B. الطلب متزايد مع التحول الرقمي للشركات في المنطقة
- **Customer segment:** B2B — شركات تقنية، مزودو خدمات إنترنت، متاجر إلكترونية، شركات SaaS
- **Competitor landscape:**
  1. Zendesk (المهيمن لكن سعره مرتفع للشركات الصغيرة)
  2. Freshdesk (منافس قوي لكن لا يركز على السوق العربي)
  3. Jira Service Management (قوي لكن معقد للمبتدئين)
  4. Help Scout (بسيط وجيد لكن يفتقر لميزات متقدمة)
  5. Intercom (شامل لكن باهظ الثمن)
- **Differentiation:** توطين كامل للسوق العربي، تسعير مناسب، تكامل مع واتساب كقناة أساسية للدعم، تحليلات أداء متقدمة للفريق

## 3. User Personas

### Primary: مدير الدعم الفني (نور)
- **Role:** مدير فريق دعم في شركة تقنية
- **Goals:** توزيع التذاكر بذكاء، مراقبة أداء الفريق، تحسين رضا العملاء
- **Pain points:** التوزيع اليدوي يستغرق وقتاً، لا توجد رؤية واضحة لحجم العمل، صعوبة إعداد تقارير الأداء

### Secondary: وكيل دعم (سامر)
- **Role:** فني دعم في شركة خدمات
- **Goals:** استلام تذاكر واضحة، حل سريع، تحديث العميل
- **Pain points:** تذاكر متكررة بدون حل جذري، قوالب ردود غير متوفرة، صعوبة البحث في التذاكر السابقة

### Admin: مدير النظام
- **Dashboard operator:** يدير الفرق، يضبط آليات التوزيع، يراقب المقاييس

## 4. Features by Platform

### Laravel API (Backend)
- Ticket CRUD with priority, status, category
- Auto-assignment engine (round-robin, skill-based, load-based)
- Multi-channel intake: email → ticket, WhatsApp → ticket, web form → ticket
- SLAs — configurable response/resolution time targets
- Canned responses (macros) library
- Satisfaction surveys (CSAT, NPS)
- Knowledge base articles linked to tickets
- Webhooks for third-party integrations

### React Dashboard (Web)
- Ticket queue with filters (status, priority, assignee, tags)
- Ticket detail page (conversation, activity log, attachments)
- Real-time agent status board (online/away/busy)
- Performance dashboards (response time, resolution time, CSAT)
- Knowledge base editor
- SLA configuration
- Reports & export

### Flutter App (Mobile)
- Push notifications for new/scheduled tickets
- Quick reply to active tickets
- View ticket queue (my tickets, unassigned)
- Update ticket status
- Chat-style conversation view
- Voice note attachments (for agents on the move)

## 5. Data Model (MVP)
- **User:** id, name, email, role, agent_status, team_id, skills (JSON)
- **Team:** id, name, description, schedule (JSON)
- **Ticket:** id, title, description, status (open/in_progress/resolved/closed), priority, category, requester_email, assigned_to, sla_deadline, created_at
- **TicketMessage:** id, ticket_id, user_id, body, attachments (JSON), is_internal_note, created_at
- **SLA:** id, name, priority, response_time_minutes, resolution_time_minutes
- **CannedResponse:** id, title, body, category, team_id
- **SatisfactionRating:** id, ticket_id, score, comment, created_at
- **KnowledgeArticle:** id, title, body, tags, category, views_count

## 6. API Endpoints (MVP)
- `GET /api/tickets` — list tickets (filters, pagination)
- `POST /api/tickets` — create ticket
- `GET /api/tickets/{id}` — ticket detail with messages
- `PUT /api/tickets/{id}` — update ticket
- `POST /api/tickets/{id}/messages` — add message
- `PATCH /api/tickets/{id}/assign` — assign ticket
- `PATCH /api/tickets/{id}/status` — change status
- `GET /api/tickets/stats` — dashboard statistics
- `GET /api/canned-responses` — list canned responses
- `GET /api/knowledge-articles` — list knowledge base
- `POST /api/auth/login` — login
- `GET /api/teams/{id}/agents` — agent status board

## 7. User Interface (Screen List)
- **Dashboard:**
  - Login/Register
  - Ticket queue (list with filters)
  - Ticket detail (chat-style thread, activity sidebar)
  - Dashboard (CSAT, response time, volume charts)
  - Agent management (status, skills)
  - Knowledge base editor
  - SLA configuration
  - Reports
- **Mobile:**
  - Login
  - My tickets list
  - Ticket detail with chat
  - Quick reply
  - Push notifications
  - Status toggle (available/away)

## 8. Business Model
- **Pricing tiers:**
  - Starter ($29/mo): 500 tickets/mo, 3 agents
  - Growth ($59/mo): 2,000 tickets/mo, 10 agents, SLA
  - Enterprise ($99/mo): unlimited tickets, unlimited agents, API
- **Free trial:** 14-day free trial
- **Target MRR per client:** $29-$99/month

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2):** API — Ticket CRUD, Auth, Teams, Auto-assignment
- **Phase 2 (Weeks 3-4):** React Dashboard — Ticket queue, Agent board, Dashboard
- **Phase 3 (Weeks 5-6):** Flutter App — Ticket list, Chat view, Push notifications
- **Phase 4 (Weeks 7-8):** SLAs, CSAT, Knowledge base, Canned responses, Deploy

## 10. Risk & Mitigation
- **Technical risk:** Real-time chat with WebSockets
  - *Mitigation:* Use Laravel Reverb for lightweight WebSocket server
- **Market risk:** Strong existing players
  - *Mitigation:* Focus on WhatsApp-first support, Arabic market, affordable pricing
- **Integration risk:** Email parsing for ticket creation
  - *Mitigation:* Use Mailgun/Postmark inbound parsing webhooks
