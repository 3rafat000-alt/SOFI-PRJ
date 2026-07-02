# PRD: SurveyCraft (SAAS-031)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** منصة متكاملة لإنشاء الاستبيانات وتحليلها توفر قوالب جاهزة وتحليلات بصرية تفاعلية للباحثين والمسوقين
- **Problem:** أدوات الاستبيانات الحالية إما معقدة أو باهظة الثمن أو تفتقر لتحليلات عربية متقدمة. الباحثون والمسوقون يضيعون وقتاً طويلاً في إعداد الاستبيانات وتحليل النتائج يدوياً
- **Proposed solution:** Laravel API + React Dashboard + Flutter App تقدم محرر استبيانات بالسحب والإفلات، قوالب جاهزة، تحليلات بصرية آنية، وتصدير البيانات

## 2. Market & Opportunity
- **Target market size:** سوق أدوات الاستبيانات العالمي ~$5B. الشرق الأوسط وشمال أفريقيا يمثل فرصة غير مخدومة بشكل كافٍ
- **Customer segment:** B2B بشكل أساسي (جامعات، شركات أبحاث، أقسام تسويق) + B2C للباحثين المستقلين
- **Competitor landscape:**
  1. SurveyMonkey (مهيمن عالمياً لكن باهظ للأسواق الناشئة)
  2. Google Forms (مجاني لكن محدود التحليلات)
  3. Typeform (تجربة مستخدم ممتازة لكن سعره مرتفع)
  4. JotForm (قوي لكن واجهته معقدة)
  5. QuestionPro (متخصص في أبحاث السوق لكن دعم عربي محدود)
- **Differentiation:** دعم كامل للغة العربية، أسعار مناسبة للشركات الناشئة والمؤسسات التعليمية في المنطقة، تحليلات متقدمة مع تصور بياني مخصص، قوالب عربية جاهزة متوافقة مع الثقافة المحلية

## 3. User Personas

### Primary: باحث أكاديمي (أحمد)
- **Role:** طالب دكتوراه / باحث في مركز أبحاث
- **Goals:** تصميم استبيانات دقيقة، جمع عينات كبيرة، تحليل النتائج إحصائياً
- **Pain points:** صعوبة إيجاد أداة تدعم العربية والإنجليزية معاً، تكاليف الاشتراك مرتفعة، عدم توفر تحليلات إحصائية متقدمة

### Secondary: مسوق رقمي (سارة)
- **Role:** مديرة تسويق في شركة ناشئة
- **Goals:** استطلاع رضا العملاء، أبحاث سوق سريعة، تقارير جاهزة للعرض على الإدارة
- **Pain points:** تحتاج نتائج سريعة، لا وقت لتعلم أدوات معقدة، تريد قوالب جاهزة للتعديل

### Admin: مدير المنصة
- **Dashboard operator:** يدير المستخدمين، يراقب الاستخدام، يضبط خطط الأسعار والإعدادات

## 4. Features by Platform

### Laravel API (Backend)
- Survey CRUD with drag-drop builder logic
- Question types: multiple choice, Likert scale, text, rating, matrix, file upload
- Response collection with conditional logic / skip patterns
- Reporting engine: aggregations, cross-tabulation, statistical summaries
- Export: CSV, Excel, PDF, SPSS-compatible format
- User auth: email/password, Google SSO, role-based permissions
- Webhook notifications on survey completion
- Email invitation engine with tracking

### React Dashboard (Web)
- Visual survey builder with live preview
- Template library (30+ Arabic/English templates)
- Real-time response dashboard with charts (pie, bar, line, heatmap)
- Respondent management and filtering
- Team collaboration spaces
- Billing and subscription management

### Flutter App (Mobile)
- Take surveys offline, sync when online
- Push notifications for new surveys
- Quick summary stats on mobile dashboard
- QR code survey access
- Share surveys via WhatsApp, SMS, email

## 5. Data Model (MVP)
- **User:** id, name, email, role, plan_id, created_at
- **Survey:** id, user_id, title (ar/en), description, status, template_id, theme_id, settings, created_at
- **Question:** id, survey_id, type, title (ar/en), options (JSON), required, order, conditions (JSON)
- **Response:** id, survey_id, respondent_id, answers (JSON), started_at, completed_at, device_info
- **Respondent:** id, email, name, custom_fields (JSON)
- **Template:** id, name, category, questions_config (JSON), thumbnail
- **Report:** id, survey_id, type, config (JSON), generated_at

## 6. API Endpoints (MVP)
- `GET /api/surveys` — list user surveys
- `POST /api/surveys` — create survey
- `GET /api/surveys/{id}` — get survey with questions
- `PUT /api/surveys/{id}` — update survey
- `DELETE /api/surveys/{id}` — delete survey
- `GET /api/surveys/{id}/responses` — list responses
- `POST /api/surveys/{id}/responses` — submit response (public)
- `GET /api/surveys/{id}/report` — aggregated report
- `POST /api/auth/login` — login
- `POST /api/auth/register` — register
- `GET /api/templates` — list templates
- `GET /api/users/me` — current user profile

## 7. User Interface (Screen List)
- **Dashboard:**
  - Login/Register
  - Survey list (grid/list view)
  - Survey editor (drag-drop builder, live preview)
  - Response dashboard (charts + filters)
  - Template gallery
  - Settings (profile, team, billing)
- **Mobile:**
  - Login/Register
  - Survey list
  - Survey taker (one question at a time, progress bar)
  - My responses history
  - Offline queue indicator

## 8. Business Model
- **Pricing tiers:**
  - Free: 3 surveys, 100 responses/month
  - Basic ($19/mo): 15 surveys, 5,000 responses, export
  - Pro ($49/mo): unlimited surveys, 50,000 responses, advanced analytics
  - Enterprise ($99/mo): custom branding, team, API access
- **Free trial:** 14-day free trial on paid plans
- **Target MRR per client:** $19-$99/month

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2):** Laravel API — User auth, Survey CRUD, Question types, Response storage
- **Phase 2 (Weeks 3-4):** React Dashboard — Survey builder, Response dashboard, Template library
- **Phase 3 (Weeks 5-6):** Flutter App — Survey taker, offline sync, Push notifications
- **Phase 4 (Weeks 7-8):** Reporting engine, Export, Testing, Deployment to production

## 10. Risk & Mitigation
- **Technical risk:** Complex conditional logic in survey builder
  - *Mitigation:* Use JSON-based conditions engine, iterate based on beta feedback
- **Market risk:** Incumbents (SurveyMonkey, Typeform) already strong
  - *Mitigation:* Arabic-first, local pricing, academic discounts, offline mobile capability
- **Scale risk:** Handling large response volumes
  - *Mitigation:* Queue-based response writing, database indexing from day one
