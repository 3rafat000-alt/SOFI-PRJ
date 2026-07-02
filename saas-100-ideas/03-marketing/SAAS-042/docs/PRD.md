# PRD: PollPro (SAAS-042)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** منصة تصويت واستفتاءات حية — إنشاء تصويتات فورية، مشاركة عبر QR ورابط، نتائج في الوقت الفعلي.
- **Problem:** المؤتمرات والمدارس تحتاج أدوات تصويت سريعة وسهلة بدون تعقيد. الحلول الحالية (Mentimeter, Slido) باهظة أو محدودة.
- **Solution:** PollPro — تطبيق تصويت خفيف مع نتائج حية، QR للدخول السريع، ودعم استفتاءات متعددة الأنواع.

## 2. Market & Opportunity
- **Target market:** سوق التفاعل في المؤتمرات والفعاليات ~$2B. التعليم ~$1.5B.
- **Customer segment:** B2B — منظمي مؤتمرات، مدارس، جامعات، شركات تدريب.
- **Competitors:**
  - Mentimeter: غني بالميزات لكن غالي ($25/شهر).
  - Slido (Cisco): تكامل مع Webex لكن معقد.
  - Google Forms: مجاني لكن بدون نتائج حية.
  - Poll Everywhere: SMS مدعوم لكن باهظ.
- **Differentiation:** أسعار أقل بكثير، QR دخول فوري، دعم كامل للغة العربية، تصويت بدون حساب.

## 3. User Personas

### الشخصية الأساسية: ليلى — منسقة مؤتمرات
- **الدور:** تنظم فعاليات وتحتاج أدوات تفاعل سريعة للحضور
- **الأهداف:** إنشاء تصويتات خلال دقائق، عرض النتائج على الشاشة، إشراك الجمهور
- **المشكلات:** الأدوات الحالية معقدة، بطيئة، لا تدعم العربية

### الشخصية الثانوية: خالد — معلم مدرسة
- **الدور:** يريد اختبار فهم الطلاب أثناء الحصة
- **الأهداف:** تصويت سريع للفصل، نتائج فورية، تقارير أداء
- **المشكلات:** لا يوجد حل مجاني مناسب، الطلاب بلا أجهزة كمبيوتر (يحتاجون جوال)

### Admin: مشرف المنصة
- يدير حسابات المؤسسات، فئات التصويت، تقارير الاستخدام.

## 4. Features by Platform

### Laravel API (Backend)
- Models: Poll, Question, Option, Vote, Session, Participant
- Real-time vote counting via Laravel Reverb (WebSocket)
- QR code generation per poll/session
- CSV/Excel export of results
- Rate limiting per IP/device

### React Dashboard (Web)
- Poll creation wizard (single/multiple choice, rating, open text)
- Live results dashboard with animated charts
- QR code display for projector mode
- Session management (active/completed polls)
- Participant analytics (count, response rate, demographics)

### Flutter App (Mobile)
- Scan QR or enter code to join poll
- Vote and see live results (WebSocket)
- Dark mode for projector compatibility
- Past polls history
- Offline submission queue (submits when online)

## 5. Data Model (MVP)
- **User**: id, name, email, role, organization_id
- **Organization**: id, name, plan, max_polls_per_session
- **Poll**: id, title, type (multiple/single/rating/text), status (draft/active/closed), session_id, created_by
- **Question**: id, poll_id, text, options_json (array), correct_answer
- **Option**: id, question_id, label, image_url
- **Vote**: id, question_id, option_id, participant_token, created_at
- **Session**: id, title, code (6-char), qr_code_url, status, started_at, ended_at
- **Participant**: id, session_id, device_id, name (optional), votes_count

## 6. API Endpoints (MVP)
- `POST /api/polls` — create poll
- `GET /api/polls/{id}` — get poll with questions + live results
- `PATCH /api/polls/{id}/status` — activate/close
- `POST /api/votes` — cast vote (rate-limited)
- `GET /api/sessions/{code}` — join session by code/QR
- `GET /api/sessions/{id}/results` — aggregated results
- `GET /api/sessions/{id}/participants` — participant list
- `POST /api/auth/login`, `POST /api/auth/register`

## 7. User Interface (Screen List)
- **Dashboard** (React): My polls list → create → live view → analytics
- **Poll Builder** (React): Step-by-step wizard with live preview
- **Projector View** (React): Fullscreen mode with QR + live bar chart
- **Analytics** (React): Participation rate, avg response time, per-question breakdown
- **Settings** (React): Organization profile, API keys, branding
- **Mobile Join** (Flutter): Home → enter code / scan QR → join poll
- **Mobile Vote** (Flutter): See question → select → submit → see live results
- **Mobile Results**: Real-time chart with animation

## 8. Business Model
- **Free**: 3 active polls, 100 votes/poll, basic charts
- **Starter**: $15/month — 10 active polls, 500 votes, QR branding, CSV export
- **Pro**: $39/month — unlimited polls, 5K votes, custom domain, team accounts
- **Enterprise**: $99/month — unlimited everything, SSO, on-prem option
- **Free trial**: 7 days Pro
- **Target MRR/client**: $15–$99

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2)**: Laravel API — Poll, Vote models + CRUD + WebSocket setup (Reverb)
- **Phase 2 (Weeks 3-4)**: React Dashboard — poll wizard, live results, projector view, QR gen
- **Phase 3 (Weeks 5-6)**: Flutter App — join screen, vote flow, WebSocket results
- **Phase 4 (Weeks 7-8)**: Admin panel, analytics, CSV export, testing, deployment

## 10. Risk & Mitigation
- **Technical**: WebSocket scaling for large events → Mitigation: Laravel Reverb with Redis, horizontal scaling
- **Market**: Free tools (Google Forms) → Mitigation: live results + QR speed differentiator
- **Technical**: Vote fraud / multiple votes → Mitigation: device fingerprint + rate limiting + optional auth
