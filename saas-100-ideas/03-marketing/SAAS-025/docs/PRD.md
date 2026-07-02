# PRD: ReviewRadar (SAAS-025)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** نظام مراقبة تقييمات العملاء — راقب تقييمات Google وفيسبوك وتويتر، حلل المشاعر، رد تلقائياً.
- **Problem:** الشركات لا تعرف متى ينشر العميل تقييماً سلبياً. المراقبة اليدوية مستحيلة عبر 3+ منصات. التأخر في الرد يضر السمعة.
- **Proposed solution:** Laravel API لسحب التقييمات وتحليلها، React Dashboard لمراقبة السمعة، Flutter App للتنبيهات الفورية.

## 2. Market & Opportunity
- **Target market size:** سوق إدارة السمعة الرقمية ~$5B. قطاع المطاعم والفنادق الأكثر احتياجاً.
- **Customer segment:** B2B (مطاعم، فنادق، عيادات، متاجر).
- **Competitor landscape:** ReviewTrackers, Birdeye, Podium, Yext.
- **Differentiation:** دعم Google Business Profile + فيسبوك + تويتر + تطبيق محلي، ردود ذكية بالعربية، تسعير يبدأ من $49.

## 3. User Personas
- **Primary 1 — مدير تسويق (ليلى):** مسؤولة عن سمعة 5 فروع. تريد تنبيه فوري عند تقييم سلبي، تقارير أسبوعية.
- **Primary 2 — صاحب مطعم (ماجد):** يريد مشاهدة كل تقييمات مطعمه في مكان واحد والرد عليها بسرعة.
- **Admin — مدير الوكالة:** يدير حسابات عملاء متعددين، تقارير مقارنة.

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Review, PlatformAccount, SentimentAnalysis, AutoReply, Report
- RESTful endpoints: CRUD reviews, sentiment, reply management
- Integrations: Google My Business API, Facebook Graph, Twitter API
- NLP: تحليل المشاعر بالعربية (مع crowd أو نموذج بسيط)
- Queue: سحب مجدول كل ساعة

### React Dashboard (Web)
- لوحة قيادة: إجمالي التقييمات، المتوسط، التوزيع (نجوم)
- تنبيهات حية: تقييم جديد، تقييم سلبي
- صندوق الردود: ردود تلقائية مقترحة، ردود مجدولة
- تحليلات: اتجاه التقييمات، مقارنة بالفترة السابقة
- إعدادات: ربط الحسابات، قوالب الرد التلقائي

### Flutter App (Mobile)
- إشعارات فورية عند تقييم جديد
- عرض التقييم والرد عليه مباشرة
- ملخص السمعة (التقييم المتوسط، عدد التقييمات)
- تقارير أسبوعية push

## 5. Data Model (MVP)
- **PlatformAccount:** id, platform, page_id, access_token, name, rating
- **Review:** id, account_id, platform, author, rating, content, reply, replied_at, created_at
- **SentimentResult:** id, review_id, sentiment (positive/negative/neutral), score, keywords
- **AutoReplyTemplate:** id, name, trigger_rating_min, trigger_rating_max, content
- **Report:** id, team_id, period, total_reviews, avg_rating, sentiment_distribution, generated_at

## 6. API Endpoints (MVP)
- `POST /auth/login`, `POST /auth/oauth/{platform}`
- `GET /accounts`, `POST /accounts/connect`
- `GET /reviews`, `GET /reviews?rating=1&sentiment=negative`
- `POST /reviews/{id}/reply`
- `GET /reports/summary?period=7d`, `GET /reports/trends`
- `GET /settings/templates`, `POST /settings/templates`
- `POST /sync` (force fetch reviews)

## 7. User Interface (Screen List)
- **Dashboard:** بطاقات (إجمالي، متوسط، حرج)، آخر التقييمات
- **Inbox:** كل التقييمات غير المجاب عليها
- **Analytics:** توزيع النجوم، مشاعر، كلمات مفتاحية
- **Settings:** ربط الحسابات، قوالب رد
- **Mobile - Home:** آخر التقييمات، إشعارات
- **Mobile - Reply:** رد مباشر من الجهاز

## 8. Business Model
- **Pricing tiers:**
  - Basic (1 location): $29/شهر
  - Business (5 locations): $99/شهر
  - Enterprise (unlimited): $249/شهر
- **Free trial:** 14 يوم
- **Target MRR per client:** $29-$249

## 9. Implementation Plan
- Phase 1 (Weeks 1-2): API + Platform connections + Review fetching
- Phase 2 (Weeks 3-4): React Dashboard + Inbox + Analytics
- Phase 3 (Weeks 5-6): Sentiment analysis + Auto-reply templates
- Phase 4 (Weeks 7-8): Flutter App + Notifications + Reports

## 10. Risk & Mitigation
- **Technical risk:** APIs منصة Google تتغير. → مراقبة تغييرات API + اختبارات دورية.
- **Market risk:** العملاء قد يفضلون حلولاً مجانية. → إظهار ROI بتقارير السمعة.
- **NLP risk:** تحليل المشاعر بالعربية صعب. → البدء بنموذج keywords بسيط، ثم تطويره.
