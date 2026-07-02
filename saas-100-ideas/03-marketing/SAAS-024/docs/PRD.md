# PRD: SocialKit (SAAS-024)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** منصة جدولة وإدارة حسابات التواصل الاجتماعي — نشر تلقائي، تحليل تفاعلات، إدارة تعليقات.
- **Problem:** المسوقون والشركات يديرون 3-5 منصات تواصل بشكل منفصل. نشر المحتوى متعب، تتبع التفاعلات صعب، الرد على التعليقات يستغرق وقتاً.
- **Proposed solution:** Laravel API للجدولة والتحليل، React Dashboard لإدارة المحتوى، Flutter App للمتابعة السريعة.

## 2. Market & Opportunity
- **Target market size:** سوق أدوات إدارة التواصل الاجتماعي ~$17B بحلول 2028.
- **Customer segment:** B2B (فرق تسويق، شركات) + B2C (مسوقون أفراد).
- **Competitor landscape:** Hootsuite, Buffer, Sprout Social, Later.
- **Differentiation:** دعم المنصات العربية بقوة (سناب شات، تويتر)، تحليل بالعربي، أسعار تبدأ من $15/شهر.

## 3. User Personas
- **Primary 1 — مسوق رقمي (هند):** تدير حسابات 3 عملاء على 4 منصات. تريد جدول نشر أسبوعي، تقارير أداء، أداة اقتراح محتوى.
- **Primary 2 — صاحب شركة (فهد):** يريد مشاهدة أداء حسابات شركته بلوحة واحدة بسيطة.
- **Admin — مدير المنصة:** يراقب حسابات الفرق، يدير الفواتير.

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Account, Post, Schedule, Analytics, Comment, MediaLibrary
- RESTful endpoints: CRUD posts/schedules, analytics aggregation
- Auth & roles: JWT, roles (admin, manager, team_member, client)
- Integrations: Twitter API, Instagram Graph, TikTok API, Snapchat, LinkedIn
- Queue: نشر مجدول عبر queues
- Notifications: إشعار عند نشر، تفاعل جديد، تعليق يحتاج رد

### React Dashboard (Web)
- لوحة تحكم مركزية: كل الحسابات في مكان واحد
- محرر منشورات مع معاينة (صورة، نص، رابط)
- تقويم المحتوى (content calendar) drag & drop
- تحليلات: وصول، تفاعل، متابعون، best posting time
- صندوق وارد موحد للتعليقات والرسائل
- مكتبة وسائط (صور، فيديوهات)

### Flutter App (Mobile)
- نشر سريع (نص + صورة)
- إشعارات التفاعلات
- عرض التحليلات الرئيسية
- إدارة تعليقات (رد، حذف)
- تقويم الأسبوع

## 5. Data Model (MVP)
- **SocialAccount:** id, platform, username, access_token, team_id
- **Post:** id, account_id, content, media_url, scheduled_at, published_at, status
- **Schedule:** id, team_id, week_start, slots (JSON of content per day)
- **Analytics:** id, account_id, date, followers, reach, engagement, likes, comments
- **Comment:** id, account_id, post_id, author, content, sentiment, replied_at
- **Media:** id, team_id, url, type, alt_text

## 6. API Endpoints (MVP)
- `POST /auth/login`, `POST /auth/oauth/{platform}`
- `GET /accounts`, `POST /accounts/connect`, `POST /accounts/{id}/disconnect`
- `GET /posts`, `POST /posts`, `PUT /posts/{id}`, `POST /posts/{id}/publish`
- `GET /schedule`, `POST /schedule`
- `GET /analytics?period=7d`, `GET /analytics/{account_id}`
- `GET /comments`, `POST /comments/{id}/reply`

## 7. User Interface (Screen List)
- **Dashboard:** كل الحسابات متصلة، آخر 10 منشورات، ملخص الأسبوع
- **Posts:** قائمة/تقويم + منشئ منشور + معاينة
- **Analytics:** رسوم بيانية، مقارنة فترات، توصيات
- **Inbox:** تعليقات ورسائل موحدة
- **Mobile - Home:** إشعارات، نشر سريع
- **Mobile - Calendar:** جدول الأسبوع

## 8. Business Model
- **Pricing tiers:**
  - Solo (5 accounts): $15/شهر
  - Team (15 accounts): $49/شهر
  - Agency (50 accounts): $149/شهر
- **Free trial:** 14 يوم
- **Target MRR per client:** $15-$149

## 9. Implementation Plan
- Phase 1 (Weeks 1-2): API + Auth + Social account connection
- Phase 2 (Weeks 3-4): React Dashboard + Post editor + Calendar
- Phase 3 (Weeks 5-6): Flutter App + Analytics
- Phase 4 (Weeks 7-8): Inbox + Comments + Polish

## 10. Risk & Mitigation
- **Technical risk:** APIs المنصات تتغير. → طبقة تجريد لكل platform.
- **Market risk:** منافسة قوية. → التركيز على المنصات العربية (سناب شات) كميزة تنافسية.
- **Rate limits:** منصات مثل Twitter تفرض حدوداً. → جدولة ذكية + queues.
