# PRD: AlertHub (SAAS-041)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** منصة مركزية لإدارة وإرسال التنبيهات عبر قنوات متعددة — Push، SMS، Email — مع تحكم كامل في الجدولة والقوالب والتوزيع.
- **Problem:** فرق DevOps والتشغيل تعاني من توزع أنظمة التنبيه بين أدوات مختلفة (PagerDuty، Slack، Email منفصل)، مما يؤدي إلى فوضى إشعارات، تكرار، وعدم قدرة على تتبع الحالة.
- **Solution:** AlertHub يوحّد جميع التنبيهات في نظام واحد مع لوحة تحكم، قوالب ديناميكية، توجيه ذكي، وتقارير موثوقية.

## 2. Market & Opportunity
- **Target market:** ~$4.5B سوق إدارة التنبيهات والاتصالات في حالات الطوارئ (2025)، ينمو بـ CAGR 12%.
- **Customer segment:** B2B — شركات تقنية، فرق DevOps، مراكز عمليات، مزودي خدمات.
- **Competitors:**
  - PagerDuty: قوي في IT alerting لكن غالي ومعقد.
  - Twilio SendGrid: Email + SMS لكن بدون إدارة تنبيهات.
  - Opsgenie (Atlassian): منافس مباشر لكن مكلف.
  - Slack: إشعارات مدمجة لكن محدودة في القنوات.
- **Differentiation:** تكامل سهل مع أنظمة عربية، دعم SMS عبر مزودين محليين، تسعير بسيط للشركات الناشئة.

## 3. User Personas

### الشخصية الأساسية: م. أحمد — مهندس DevOps
- **الدور:** مسؤول عن مراقبة الأنظمة وإدارة التنبيهات
- **الأهداف:** توحيد الإشعارات، تقليل الضوضاء، ضمان وصول التنبيهات الحرجة
- **المشكلات:** كثرة الأدوات، تنبيهات مكررة، صعوبة تتبع حالة كل تنبيه

### الشخصية الثانوية: سارة — مديرة فريق تقني
- **الدور:** تشرف على فريق DevOps وتحتاج تقارير أداء
- **الأهداف:** رؤية واضحة لزمن الاستجابة، تحليل فجوات التنبيهات
- **المشكلات:** لا توجد لوحة تحكم موحدة، صعوبة قياس أداء التنبيهات

### Admin: مشرف النظام
- يدير المستخدمين، الصلاحيات، إعدادات القنوات، مزودي الخدمة.

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Alert, Channel (Push/SMS/Email), Template, Schedule, Log
- CRUD endpoints for all entities
- Auth via Sanctum (API tokens) + Laravel permission roles
- Queue-based dispatch (Laravel Horizon for high throughput)
- Webhook receiver for external systems (Prometheus, Grafana, Zabbix)

### React Dashboard (Web)
- Alert management grid with filtering, search, status badges
- Template editor (rich text, variables injection)
- Delivery analytics: success rates, response times, channel breakdown
- User/team management with RBAC
- Channel configuration (Twilio for SMS, SES for Email, FCM/APNs for Push)

### Flutter App (Mobile)
- Real-time push notifications via Firebase
- Alert acknowledgement (ack/nack) with timestamp
- View alert history and delivery status
- Quick mute/snooze controls

## 5. Data Model (MVP)
- **User**: id, name, email, phone, role, team_id
- **Team**: id, name, slack_webhook, default_channel
- **Alert**: id, title, body, severity (critical/warning/info), channel, status (pending/sent/failed/acknowledged), scheduled_at
- **Template**: id, name, subject, body, variables_json, channel
- **Channel**: id, type (push/sms/email), config_json, provider, is_active
- **AlertLog**: id, alert_id, channel_id, status, sent_at, error_message
- **Schedule**: id, name, cron_expression, template_id, filters_json

## 6. API Endpoints (MVP)
- `GET /api/alerts` — list with filters (status, severity, date)
- `POST /api/alerts` — create alert
- `POST /api/alerts/batch` — batch send
- `GET /api/alerts/{id}` — detail + logs
- `PATCH /api/alerts/{id}/acknowledge` — ack
- `CRUD /api/templates` — full CRUD
- `CRUD /api/channels` — full CRUD
- `GET /api/stats/delivery` — delivery stats
- `POST /api/auth/login`, `POST /api/auth/register`
- `GET /api/me`, `PATCH /api/me`

## 7. User Interface (Screen List)
- **Dashboard** (React): Alert grid → filters → bulk actions → analytics cards
- **Template Studio** (React): Rich editor, drag-drop variables, preview
- **Channel Config** (React): Wizard per channel type, test send button
- **Analytics** (React): Charts (delivery rate, response time trend, channel breakdown)
- **Settings** (React): Team management, API keys, webhooks
- **Mobile** (Flutter): Alert feed → tap → detail → ack → share
- **Mobile Settings**: Notification preferences, quiet hours

## 8. Business Model
- **Free**: 500 alerts/month, 2 channels, email only
- **Pro**: $49/month — 10K alerts, 3 channels, templates + analytics
- **Enterprise**: $199/month — unlimited alerts, custom channels, SLA, priority support
- **Free trial**: 14 days full-featured
- **Target MRR/client**: $49–$199

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2)**: Laravel API — Alert, Channel, Template models + CRUD + Sanctum auth + queue setup
- **Phase 2 (Weeks 3-4)**: React Dashboard — alert grid, template editor, channel config, basic analytics
- **Phase 3 (Weeks 5-6)**: Flutter App — push notifications via FCM, alert feed, ack flow
- **Phase 4 (Weeks 7-8)**: Webhook receiver integration, delivery analytics, testing, deployment to Fly.io

## 10. Risk & Mitigation
- **Technical**: Email deliverability (SPF/DKIM setup) → Mitigation: built-in email warmup guide, SES integration
- **Technical**: SMS reliability with Arabic providers → Mitigation: provider fallback chain (Twilio → local provider)
- **Market**: PagerDuty/Opsgenie lock-in → Mitigation: import tool from competitor exports, lower price point
