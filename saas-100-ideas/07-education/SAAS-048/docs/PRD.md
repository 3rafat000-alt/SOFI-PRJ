# PRD: CourseCraft (SAAS-048)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** منصة إنشاء وبيع الدورات الرقمية — أدوات تفاعلية، مجتمع طلاب، بوابة دفع مدمجة.
- **Problem:** المدربون والخبراء يريدون إنشاء وبيع دورات تدريبية أونلاين لكن المنصات الحالية (Udemy, Teachable) تأخذ عمولة كبيرة أو معقدة.
- **Solution:** CourseCraft — منصة عربية متكاملة لإنشاء الدورات مع أدوات تفاعلية (اختبارات، مناقشات، شهادات) وبيع مباشر بدون عمولات باهظة.

## 2. Market & Opportunity
- **Target market:** سوق التعليم الإلكتروني ~$50B (2025)، نمو 20% CAGR. السوق العربي ~$7B.
- **Customer segment:** B2B/B2C — مدربون، خبراء، مراكز تدريب، شركات تدريب داخلي.
- **Competitors:**
  - Udemy: جمهور كبير لكن يأخذ 37-63% عمولة.
  - Teachable: منصة قوية لكن $39/شهر + عمولة 5%.
  - Thinkific: جيد مجاني لكن ترقية غالية ($49/شهر).
  - LearnDash (WordPress): مرن لكن يتطلب خادم وصيانة.
- **Differentiation:** دعم عربي كامل، عمولة 2% فقط (أقل بالسوق)، أدوات تفاعلية مدمجة، مجتمع طلابي، شهادات إتمام.

## 3. User Personas

### الشخصية الأساسية: د. عمر — مدرب وخبير في التسويق الرقمي
- **الدور:** يريد إنشاء كورس احترافي وبيعه لجمهور عربي
- **الأهداف:** تصميم محتوى تفاعلي، تحصيل أرباح عادلة، بناء مجتمع طلاب
- **المشكلات:** Udemy تأخذ 63%، Teachable معقدة ولا تدعم العربية جيداً

### الشخصية الثانوية: نورة — طالبة جامعية
- **الدور:** تشتري دورات لتطوير مهاراتها في البرمجة والتصميم
- **الأهداف:** محتوى عربي عالي الجودة، شهادة معتمدة، تفاعل مع المدرب
- **المشكلات:** قلة محتوى عربي ممتاز، صعوبة الحصول على شهادات معترف بها

### Admin: مشرف المنصة
- يدير المدربين، المراجعات، المدفوعات، تقارير المنصة، مراقبة الجودة.

## 4. Features by Platform

### Laravel API (Backend)
- Models: Course, Module, Lesson, Quiz, Question, Student, Enrollment, Review, Certificate, Payment
- Video hosting integration (Cloudflare Stream / Bunny.net)
- DRM: signed URLs, domain restriction
- Payment pipeline: Stripe/Moyasar → activate enrollment
- Certificate generation (PDF with verification QR)

### React Dashboard (Web)
- Course builder: drag-drop module organization, lesson editor (video/text/quiz)
- Quiz creator: multiple choice, true/false, essay, auto-grade
- Student management: enrollment list, progress tracking, messaging
- Analytics: revenue, enrollment trend, completion rate, student feedback
- Payout management: instructor earnings, withdrawal requests
- Community management: Q&A, discussion forums per course

### Flutter App (Mobile)
- Course player: video streaming with download for offline
- Progress tracking: resume where left off, module completion check
- Quiz taking: timer, instant feedback, score display
- Certificate download: view and share
- Discussion: ask questions, reply, instructor responses
- Push notifications: new content, quiz reminder, certificate earned
- Dark mode for extended viewing

## 5. Data Model (MVP)
- **User**: id, name, email, role (student/instructor/admin), bio, avatar
- **Instructor**: id, user_id, headline, payment_info, total_earnings, course_count
- **Course**: id, instructor_id, title, description, price, thumbnail, category, status (draft/published/archived), total_students, avg_rating
- **Module**: id, course_id, title, sort_order, estimated_minutes
- **Lesson**: id, module_id, title, type (video/text/quiz), content_url, duration_seconds, is_free
- **Quiz**: id, lesson_id, title, passing_score, time_limit_minutes
- **Question**: id, quiz_id, text, type, options_json, correct_answer, points
- **Enrollment**: id, student_id, course_id, progress_percent, enrolled_at, completed_at, certificate_url
- **Review**: id, course_id, student_id, rating, comment
- **Certificate**: id, enrollment_id, certificate_number, issued_at, verification_url

## 6. API Endpoints (MVP)
- `CRUD /api/courses` — full course management
- `CRUD /api/modules` — module CRUD (sorted)
- `CRUD /api/lessons` — lesson CRUD, file upload
- `CRUD /api/quizzes`, `CRUD /api/questions` — quiz/questions
- `POST /api/enroll` — enroll (payment gateway callback)
- `GET /api/my-courses` — student enrolled courses
- `POST /api/lessons/{id}/progress` — update progress
- `POST /api/quiz/{id}/submit` — submit quiz, get score
- `POST /api/reviews` — submit review
- `GET /api/certificates/{id}/verify` — verify certificate
- `POST /api/auth/login`, `POST /api/auth/register`

## 7. User Interface (Screen List)
- **Dashboard** (React): Earnings chart, enrollment trend, course list with stats
- **Course Builder** (React): Drag-drop modules → add lesson → video upload → quiz creator
- **Student Grid** (React): Enrolled students, progress bar per student, messaging
- **Quiz Builder** (React): Question list, timer config, auto-grade rules, preview
- **Analytics** (React): Revenue line chart, completion funnel, rating distribution
- **Community** (React): Discussion threads per course, Q&A, announcements
- **Settings** (React): Profile, payment info, branding, course categories
- **Mobile Student** (Flutter): My Courses → tap → lesson player → quiz → certificate
- **Mobile Instructor** (Flutter): Stats at a glance, recent enrollments, quick reply to Q&A

## 8. Business Model
- **Free**: 1 course, 10 students, 500MB video storage, 2% platform fee
- **Creator**: $19/month — 5 courses, 200 students, 10GB video, 0% fee, custom domain
- **Pro**: $49/month — 20 courses, 1000 students, 50GB video, 0% fee, API access
- **Enterprise**: Custom — unlimited, white-label, SSO, dedicated hosting
- **Platform fee**: Free tier 2% (vs Udemy's 37-63%), paid tiers 0%
- **Target MRR/client**: $19–$49

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2)**: Laravel API — Course/Module/Lesson/Quiz models + video upload + CRUD
- **Phase 2 (Weeks 3-4)**: React Dashboard — course builder, quiz creator, enrollment management
- **Phase 3 (Weeks 5-6)**: Flutter App — video player, offline download, quiz taker, discussions
- **Phase 4 (Weeks 7-8)**: Payment integration (Moyasar/Stripe), certificate generation, community features, testing

## 10. Risk & Mitigation
- **Technical**: Video streaming costs → Mitigation: Bunny.net (CDN with pay-as-you-go), compress with HLS
- **Market**: Free competitors (YouTube) → Mitigation: structured courses, quizzes, certificates, community that YouTube lacks
- **Technical**: Payment failures in MENA → Mitigation: multiple gateways (Stripe + Moyasar + PayPal), manual bank transfer fallback
- **Content**: Piracy → Mitigation: signed URLs, watermark, IP blocking, DMCA takedown
