# PRD: MailCraft (SAAS-038)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** منصة حملات بريد إلكتروني متكاملة مع قوالب جاهزة، قوائم توزيع، اختبار A/B، وتحليلات أداء مفصلة
- **Problem:** الشركات والمتاجر الإلكترونية تحتاج أداة تسويق بريدي موثوقة بأسعار معقولة تدعم العربية وتوفر تقارير واضحة
- **Proposed solution:** Laravel API + React Dashboard + Flutter App تقدم نظاماً لإدارة الحملات البريدية مع محرر قوالب بالسحب والإفلات، إدارة القوائم، وتحليلات متقدمة

## 2. Market & Opportunity
- **Target market size:** سوق التسويق عبر البريد الإلكتروني ~$18B. الطلب كبير في العالم العربي مع نمو التجارة الإلكترونية
- **Customer segment:** B2B — شركات صغيرة ومتوسطة، متاجر إلكترونية، وكالات تسويق
- **Competitor landscape:**
  1. Mailchimp (المهيمن لكن غادر العديد من الأسواق العربية)
  2. SendGrid (قوي تقنياً لكن واجهته غير ودية للمسوقين)
  3. ActiveCampaign (شامل لكن باهظ الثمن)
  4. Moosend (جيد وسعره مناسب لكن عربي محدود)
  5. GetResponse (متكامل لكن مكلف للقوائم الصغيرة)
- **Differentiation:** دعم كامل للغة العربية والكتابة من اليمين لليسار في محرر القوالب، أسعار تبدأ من $9 للقوائم الصغيرة، تكامل مع متاجر Shopify/Magento المحلية، تحليلات واضحة وبسيطة

## 3. User Personas

### Primary: مسوق إلكتروني (ياسر)
- **Role:** مدير تسويق في متجر إلكتروني لبيع الأزياء
- **Goals:** إرسال نشرات دورية، حملات موسمية، استهداف العملاء حسب سلوكهم
- **Pain points:** أدوات البريد الإلكتروني الحالية باهظة للقوائم الكبيرة، صعوبة تصميم قوالب عربية جذابة، معدل فتح منخفض

### Secondary: مدير متجر صغير (نورة)
- **Role:** صاحبة متجر إلكتروني للحرف اليدوية
- **Goals:** إرسال عروض للمشتركين، متابعة الطلبات عبر البريد، معرفة من يفتح الإيميلات
- **Pain points:** ليس لديها خبرة تقنية، تحتاج قوالب جاهزة، تريد أداة بسيطة بنقرة زر

### Admin: مدير النظام
- **Dashboard operator:** يدير المستخدمين، يراقب سمعة الإرسال، يضبط حدود البريد

## 4. Features by Platform

### Laravel API (Backend)
- Campaign CRUD with status (draft/scheduled/sending/sent)
- Drag-drop email builder — output rendered HTML with RTL support
- Subscriber management — lists, segments, custom fields, tags
- Import/export subscribers (CSV, Mailchimp migration)
- Email sending engine — queue-based with rate limiting per domain
- Bounce handling (hard/soft, auto-remove invalid emails)
- Unsubscribe management (one-click, list-unsubscribe header)
- A/B testing — subject line, content, sender name, send time
- Analytics tracking — opens, clicks, bounces, complaints, unsubscribes
- Webhook for delivery events (via SES/SendGrid/Mailgun)
- Template library — 20+ Arabic/English responsive templates

### React Dashboard (Web)
- Campaign list — status, stats, schedule
- Email builder — drag-drop editor with RTL preview
- Template gallery — preview, duplicate, customize
- Subscriber dashboard — list management, segment builder
- Analytics dashboard — sends, opens, clicks, bounces over time
- A/B test setup and results comparison
- Automation editor (future: visual workflow builder)
- Sender reputation score
- Settings — SMTP/DKIM/SPF configuration guides

### Flutter App (Mobile)
- Campaign overview — recent sends, quick stats
- Monitor active campaign — real-time open/click count
- Quick broadcast — send to specific list
- New subscriber add (manual or contact import)
- Push notifications for campaign completion or issues
- Analytics snapshot — opens rate, click rate today

## 5. Data Model (MVP)
- **User:** id, name, email, role, workspace_id
- **Workspace:** id, name, sending_domain, daily_limit
- **List:** id, workspace_id, name, description, tags
- **Subscriber:** id, list_id, email, name, custom_fields (JSON), status (subscribed/unsubscribed/bounced), created_at
- **Template:** id, name, category, html_content, json_content (drag-builder config), thumbnail
- **Campaign:** id, workspace_id, name, subject, sender_name, sender_email, reply_to, html_content, status, scheduled_at, sent_at
- **CampaignRecipient:** id, campaign_id, list_id (campaign → list mapping)
- **ABTest:** id, campaign_id, type (subject/content), variants (JSON), winner_criteria, status
- **CampaignEvent:** id, campaign_id, subscriber_id, event_type (sent/opened/clicked/bounced/unsubscribed/complaint), metadata (JSON), created_at

## 6. API Endpoints (MVP)
- `GET /api/campaigns` — list campaigns
- `POST /api/campaigns` — create campaign
- `GET /api/campaigns/{id}` — campaign detail + stats
- `PUT /api/campaigns/{id}` — update campaign
- `POST /api/campaigns/{id}/send` — send/schedule campaign
- `GET /api/campaigns/{id}/events` — real-time event stream
- `GET /api/lists` — subscriber lists
- `POST /api/lists` — create list
- `POST /api/lists/{id}/subscribers` — add subscriber
- `POST /api/lists/{id}/subscribers/import` — bulk import
- `GET /api/templates` — template library
- `POST /api/templates` — save template
- `GET /api/analytics/overview` — workspace-level stats
- `POST /api/auth/login` — login
- `POST /api/auth/register` — register

## 7. User Interface (Screen List)
- **Dashboard:**
  - Login/Register
  - Campaign overview (list + stats cards)
  - Campaign editor (builder: drag-drop + settings)
  - Template gallery
  - Subscriber list management
  - Analytics dashboard (opens, clicks, bounces charts)
  - A/B test results
  - Settings (domain, DKIM, team)
- **Mobile:**
  - Login
  - Dashboard (recent campaigns, quick stats)
  - Campaign monitor (live event feed)
  - Quick send (pick list → pick template → send)
  - Subscriber count by list
  - Notifications

## 8. Business Model
- **Pricing tiers:**
  - Free: 500 emails/mo, 1 user
  - Starter ($19/mo): 5,000 emails, 3 users, templates
  - Pro ($49/mo): 25,000 emails, 10 users, A/B testing
  - Business ($99/mo): 100,000 emails, unlimited users, priority
- **Free trial:** 14-day Pro trial
- **Target MRR per client:** $19-$99/month

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2):** API — Subscribers, Lists, Campaign CRUD, Email sending engine
- **Phase 2 (Weeks 3-4):** React Dashboard — Campaign builder, Template editor, Subscriber manager
- **Phase 3 (Weeks 5-6):** Flutter App — Campaign monitoring, Quick send, Stats
- **Phase 4 (Weeks 7-8):** A/B testing, Send optimization, Reports, Deploy

## 10. Risk & Mitigation
- **Technical risk:** Email deliverability — landing in spam
  - *Mitigation:* Warm up sending domains, enforce DKIM/SPF, monitor reputation, use reputable SMTP (SendGrid/SES)
- **Security risk:** Unauthorized access to subscriber data
  - *Mitigation:* Data encryption, GDPR-compliant data handling, access logging
- **Market risk:** Mailchimp loyalty
  - *Mitigation:* Offer one-click migration from Mailchimp, local pricing, bilingual support
