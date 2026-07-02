# PRD: URLShort Pro (SAAS-037)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** منصة إدارة روابط مختصرة ذكية تولد روابط قصيرة ورموز QR تلقائياً مع تحليلات نقرات متقدمة للمسوقين والمؤثرين
- **Problem:** المسوقون والمؤثرون يستخدمون أدوات تقصير روابط عامة (Bitly, TinyURL) لكنها تفتقر لميزات العلامة التجارية المخصصة وتحليلات النقرات المتقدمة بالعربية
- **Proposed solution:** Laravel API + React Dashboard + Flutter App تقدم روابط قصيرة بأسماء نطاق مخصصة، QR codes، وتحليلات نقرات آنية

## 2. Market & Opportunity
- **Target market size:** سوق تقصير الروابط ~$1B. المسوقون والمؤثرون في العالم العربي يحتاجون أدوات محلية
- **Customer segment:** B2B + B2C — مسوقون رقميون، مؤثرون على وسائل التواصل، شركات إعلانات، مديري حملات
- **Competitor landscape:**
  1. Bitly (المهيمن لكن مكلف للميزات المتقدمة)
  2. TinyURL (مجاني لكن محدود جداً)
  3. Rebrandly (قوي بالعلامات التجارية لكن أغلى)
  4. Short.io (جيد لكن بواجهة معقدة)
  5. YOURLS (مفتوح المصدر لكن يتطلب استضافة)
- **Differentiation:** دعم كامل للعربية، QR Code تلقائي لكل رابط، تكامل مع منصات التواصل الاجتماعي العربية، أسعار تنافسية تبدأ من مجاني، تحليلات نقرات مفصلة مع تصفية حسب الموقع والجهاز والمتصفح

## 3. User Personas

### Primary: أخصائي تسويق رقمي (رنا)
- **Role:** مديرة حملات إعلانية في وكالة تسويق
- **Goals:** تتبع أداء الروابط في الحملات، إنشاء روابط ذات علامة تجارية، تقارير أداء للعملاء
- **Pain points:** تحتاج روابط تعكس اسم العلامة التجارية، صعوبة تتبع النقرات عبر حملات متعددة، تقارير الأداء تستغرق وقتاً

### Secondary: مؤثر على وسائل التواصل (عبدالله)
- **Role:** مؤثر على إنستغرام ويوتيوب مع 500K متابع
- **Goals:** مشاركة روابط مختصرة لمتابعيه، معرفة عدد النقرات من كل منصة
- **Pain points:** الروابط الطويلة تشوه المحتوى البصري، يريد معرفة أي منصة تجلب أكثر نقرات

### Admin: مدير المنصة
- **Dashboard operator:** يدير المستخدمين، يراقب إجمالي النقرات، يضبط حدود الاستخدام

## 4. Features by Platform

### Laravel API (Backend)
- URL shortening engine (custom alias, random slug, domain binding)
- Link management CRUD with tags, campaigns, groups
- QR code generation (static/dynamic, custom colors/logo)
- Click tracking — IP, user agent, referrer, geolocation, device, browser
- Campaign linking — group links by campaign, compare performance
- Domain management — custom domains, SSL certificates
- Rate limiting per user tier
- Link expiry — set start/end dates, auto-deactivate
- Webhook notifications per click or event
- Bulk link creation via CSV

### React Dashboard (Web)
- Links dashboard — total clicks, top links, recent activity
- Link creator — paste URL → custom slug → domain → create
- QR code download (PNG, SVG, PDF)
- Analytics dashboard — click trends, geography, devices, referrers
- Campaign view — aggregate stats across campaign links
- Domains settings — add, verify, configure custom domain
- Teams — invite members, shared links, permission levels
- Export reports (CSV, PDF)

### Flutter App (Mobile)
- Quick link creation (share sheet → app → short link)
- Dashboard — today's clicks, total links, recent activity
- QR code scanner → expand or view analytics
- Push notifications for click milestones
- Share short link + QR code directly to social media
- Manage links (edit, deactivate, delete)
- Widget — quick shorten from home screen

## 5. Data Model (MVP)
- **User:** id, name, email, role, plan_id
- **Domain:** id, user_id, domain, verified, ssl_enabled, created_at
- **Link:** id, user_id, domain_id, title, original_url, short_url_slug, is_active, click_limit, starts_at, ends_at, created_at
- **Click:** id, link_id, clicked_at, ip_address, country, city, device_type, browser, os, referrer, user_agent
- **Campaign:** id, user_id, name, description, utm_params (JSON)
- **LinkCampaign:** id, link_id, campaign_id
- **QRCode:** id, link_id, style (JSON — colors, logo, shape), created_at

## 6. API Endpoints (MVP)
- `POST /api/shorten` — create short link
- `GET /api/links` — list user links
- `GET /api/links/{id}` — link detail + stats
- `PUT /api/links/{id}` — update link
- `DELETE /api/links/{id}` — deactivate/delete link
- `GET /api/links/{id}/clicks` — click analytics (time series)
- `GET /api/links/{id}/qrcode` — get QR code
- `POST /api/domains` — add custom domain
- `GET /api/domains` — list domains
- `GET /api/campaigns` — list campaigns
- `POST /api/campaigns` — create campaign
- `GET /api/dashboard/stats` — overall stats
- `POST /api/auth/login` — login
- `POST /api/auth/register` — register
- Redirect: `GET /{short_slug}` — redirect to original URL (no auth)

## 7. User Interface (Screen List)
- **Dashboard:**
  - Login/Register
  - Overview (total clicks, links, top performing)
  - Link creator
  - Links table (search, filter, sort)
  - Link detail + analytics charts
  - QR code designer
  - Campaign manager
  - Domains settings
  - Team management
  - Billing/subscription
- **Mobile:**
  - Login
  - Dashboard summary
  - Quick create (paste URL → create)
  - Links list
  - Link stats view
  - Share short link + QR
  - QR scanner
  - Widget (home screen quick action)

## 8. Business Model
- **Pricing tiers:**
  - Free: 50 links, 1,000 clicks/mo, basic analytics
  - Pro ($19/mo): 500 links, 50,000 clicks, custom domain
  - Business ($49/mo): 5,000 links, 500,000 clicks, team, API
- **Free trial:** 14-day Pro trial
- **Target MRR per client:** $19-$49/month

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2):** API — Link shortening engine, redirect, Click tracking, Auth
- **Phase 2 (Weeks 3-4):** React Dashboard — Link creator, Analytics dashboard, Domains
- **Phase 3 (Weeks 5-6):** Flutter App — Quick create, QR code, Share, Stats
- **Phase 4 (Weeks 7-8):** Campaigns, Teams, Bulk import, Export, Deploy

## 10. Risk & Mitigation
- **Technical risk:** High-traffic redirects (millions of clicks)
  - *Mitigation:* Cache redirects with Redis, use 301 redirects served from nginx/cache layer
- **Security risk:** Malicious links / phishing through domain
  - *Mitigation:* Automated link scanning + manual review queue + abuse reporting
- **Scale risk:** Click tracking data volume
  - *Mitigation:* Click logs written to Redis queue, batch-insert to DB, archive old data
