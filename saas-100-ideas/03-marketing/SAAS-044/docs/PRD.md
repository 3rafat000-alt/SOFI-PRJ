# PRD: ChatFlow (SAAS-044)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** منصة دردشة عملاء مباشرة مع بوت ذكي — توجيه ذكي للموظفين، سجل محادثات كامل، تحليلات رضا العملاء.
- **Problem:** المتاجر الإلكترونية وشركات الخدمات تحتاج دعم عملاء فوري لكن توظيف فريق كامل مكلف. Chatbots الموجودة إما باهظة أو سيئة.
- **Solution:** ChatFlow — chat widget سهل الدمج مع بوت ذكي يجيب على 80% من الأسئلة، ويتحول للموظف عند الحاجة.

## 2. Market & Opportunity
- **Target market:** سوق الدردشة الحية و chatbots ~$4.2B (2025), نمو 24% CAGR.
- **Customer segment:** B2B — متاجر إلكترونية متوسطة، شركات خدمات، فنادق، عيادات.
- **Competitors:**
  - Tidio: chatbot + live chat جيد لكن غالي ($29/شهر).
  - Intercom: ممتاز جداً لكن باهظ ($74/شهر).
  - Tawk.to: مجاني لكن chatbot بسيط جداً.
  - Zendesk Chat: قوي لكن معقد ويتطلب حزمة كاملة.
- **Differentiation:** بوت عربي ذكي، تسعير متوسط، دمج سريع (كود embed)، تحليلات رضا متقدمة.

## 3. User Personas

### الشخصية الأساسية: نور — صاحبة متجر إلكتروني
- **الدور:** تدير متجر أزياء أونلاين وتحتاج دعم عملاء
- **الأهداف:** تقليل ضغط الاستفسارات المتكررة، تحسين تجربة العميل
- **المشكلات:** تقضي 4 ساعات يومياً بأسئلة متكررة (الأسعار، الشحن، المقاسات)

### الشخصية الثانوية: ياسر — مدير خدمة عملاء
- **الدور:** يشرف على فريق دعم في شركة اتصالات
- **الأهداف:** توجيه المحادثات للفريق المناسب، تقارير رضا، تحسين سرعة الاستجابة
- **المشكلات:** لا توجد رؤية موحدة، صعوبة تحليل أداء الفريق

### Admin: مشرف النظام
- يدير إعدادات البوت، قواعد الرد، صلاحيات الوكلاء، تقارير المنصة.

## 4. Features by Platform

### Laravel API (Backend)
- Models: Conversation, Message, Agent, BotRule, WidgetSetting, SatisfactionRating
- WebSocket (Reverb) for real-time messaging
- NLP bot engine (rule-based + LLM fallback via API)
- Agent assignment (round-robin, skill-based, least-busy)
- Canned responses / quick replies CRUD

### React Dashboard (Web)
- Conversations panel with status (waiting/active/closed)
- Agent workspace: type reply, transfer, close, rate
- Bot training interface: Q&A pairs, keywords, fallback response
- Analytics: response time, CSAT score, conversation volume trend
- Widget customizer: color, position, greeting message, offline form

### Flutter App (Mobile)
- Agent app: real-time notifications for new chats
- Quick replies, typing indicator
- Transfer conversation, close and tag
- Push notifications when offline
- Customer app (optional embedded): chat with support

## 5. Data Model (MVP)
- **User**: id, name, email, role (admin/agent/customer)
- **Conversation**: id, session_token, customer_name, customer_email, status (waiting/active/closed), assigned_agent_id, source (web/mobile), created_at
- **Message**: id, conversation_id, sender_type (bot/agent/customer), body, attachment_url, read_at
- **BotRule**: id, keywords_json, response, intent (greeting/order/shipping/return), priority
- **Agent**: id, user_id, max_concurrent_chats, skills_json, is_online
- **WidgetSetting**: id, organization_id, color, position, greeting, offline_message
- **SatisfactionRating**: id, conversation_id, score (1-5), comment

## 6. API Endpoints (MVP)
- `POST /api/conversations` — create (via widget)
- `GET /api/conversations` — list (agents see assigned)
- `GET /api/conversations/{id}/messages` — get messages
- `POST /api/messages` — send message
- `PATCH /api/conversations/{id}/assign` — assign agent
- `PATCH /api/conversations/{id}/status` — close/reopen
- `CRUD /api/bot-rules` — manage bot rules
- `POST /api/bot/ask` — bot response endpoint (NLP)
- `GET /api/widget-settings` — public widget config
- `POST /api/ratings` — submit satisfaction rating
- `POST /api/auth/login`, `POST /api/auth/register`

## 7. User Interface (Screen List)
- **Dashboard** (React): Conversation list with status badges → click → agent workspace
- **Agent Workspace** (React): Chat panel + canned responses + transfer button + close button
- **Bot Training** (React): Q&A pairs CRUD, test bot, import/export CSV
- **Analytics** (React): CSAT trend, volume chart, response time distribution, agent leaderboard
- **Widget Customizer** (React): Live preview, color picker, position toggle, greeting editor
- **Settings** (React): Team management, hours of operation, API keys
- **Mobile Agent** (Flutter): Notifications → open chat → reply / transfer / close

## 8. Business Model
- **Free**: 1 agent, 100 conversations/month, basic bot (5 rules)
- **Starter**: $19/month — 3 agents, 2K conversations, unlimited bot rules, basic analytics
- **Pro**: $49/month — 10 agents, 10K conversations, LLM bot, CSAT surveys, custom branding
- **Enterprise**: $149/month — unlimited, SLA, dedicated bot training, SSO, on-prem
- **Free trial**: 14 days Starter
- **Target MRR/client**: $19–$49

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2)**: Laravel API — Conversation/Message/BotRule models + Reverb WebSocket + auth
- **Phase 2 (Weeks 3-4)**: React Dashboard — agent workspace, conversation panel, widget customizer
- **Phase 3 (Weeks 5-6)**: Flutter Agent App — push notifications, chat UI, quick replies
- **Phase 4 (Weeks 7-8)**: Bot training UI, analytics dashboard, embeddable JS widget, testing

## 10. Risk & Mitigation
- **Technical**: NLP bot quality for Arabic → Mitigation: start with rule-based, add LLM fallback (OpenAI/Gemini API)
- **Technical**: WebSocket scaling → Mitigation: Reverb with Redis, sticky sessions, horizontal pods
- **Market**: Tawk.to (free) dominance → Mitigation: better bot, analytics, and Arabic support they lack
