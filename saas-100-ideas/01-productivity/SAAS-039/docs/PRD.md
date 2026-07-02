# PRD: FormBuilder (SAAS-039)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** منصة إنشاء نماذج ذكية تتيح تصميم نماذج ديناميكية بشروط منطقية وتخزين الردود وتحليلها
- **Problem:** المؤسسات والمدارس تحتاج نماذج تفاعلية بشروط ذكية (إذا اختار X أظهر Y) لكن الأدوات الحالية إما معقدة أو مكلفة أو لا تدعم المنطق الشرطي جيداً
- **Proposed solution:** Laravel API + React Dashboard + Flutter App تقدم منصة نماذج ذكية مع محرر بالسحب والإفلات، شروط منطقية، تخزين الردود، وتصدير

## 2. Market & Opportunity
- **Target market size:** سوق أدوات النماذج ~$3B. المدارس والجامعات والمؤسسات في المنطقة العربية تحتاج نماذج محلية
- **Customer segment:** B2B — مدارس (تسجيل طلاب، استبيانات)، مؤسسات (نماذج موظفين، استبيانات رضا)، شركات (توظيف)
- **Competitor landscape:**
  1. Google Forms (مجاني لكن لا يدعم الشروط المنطقية المعقدة)
  2. JotForm (أفضل نموذج شرطي لكن سعره مرتفع)
  3. Typeform (تجربة مستخدم ممتازة لكن محدود في الشروط)
  4. Wufoo (قديم، واجهته غير عصرية)
  5. Cognito Forms (قوي لكن معقد)
- **Differentiation:** شروط منطقية متقدمة (AND/OR/Nested)، دعم كامل للعربية، نماذج غير محدودة في الخطة المجانية للمدارس، تطبيق جوال لملء النماذج دون إنترنت

## 3. User Personas

### Primary: مسؤول توظيف (هند)
- **Role:** مديرة موارد بشرية في شركة تقنية
- **Goals:** نماذج تقديم الوظائف، فحص المتقدمين تلقائياً، تتبع حالة الطلب
- **Pain points:** تريد شروطاً منطقية لإخفاء/إظهار الأسئلة حسب مؤهلات المتقدم، صعوبة متابعة الطلبات في Excel

### Secondary: مدرس (فيصل)
- **Role:** مدرس علوم في مدرسة خاصة
- **Goals:** اختبارات إلكترونية، استبيانات للطلاب، جمع واجبات
- **Pain points:** Google Forms لا يدعم تصحيحاً تلقائياً، يريد شروطاً لإظهار أسئلة تعزيزية بناءً على إجابات الطالب

### Admin: مدير النظام
- **Dashboard operator:** يدير المستخدمين، يراقب عدد الردود، يضبط إعدادات المنصة

## 4. Features by Platform

### Laravel API (Backend)
- Form CRUD with drag-drop builder configuration
- Question types: text, email, number, date, file upload, multiple choice, checkbox, dropdown, rating, matrix
- Conditional logic engine (show/hide/skip questions based on answers)
- Multi-step / paginated forms
- Branching paths (different sections based on answers)
- Response storage with encryption for sensitive data
- File upload handling with size limits and type validation
- Webhook on form submission (integrate with Slack, Zapier-like)
- Email notifications — autoresponder to submitter + admin notification
- Export — CSV, Excel, PDF (individual responses)
- Form analytics — submission rates, drop-off points, time to complete
- CAPTCHA integration for spam prevention

### React Dashboard (Web)
- Form builder — drag-drop sidebar, canvas, live preview
- Question properties panel (type, required, validation, conditions)
- Conditional rules builder (visual: if this → do that)
- Design customization — themes, colors, fonts, header image
- Response dashboard — table view, individual view, chart summaries
- Form settings — confirmation message, redirect URL, limits
- Templates — 20+ form templates for different use cases
- Share — embed code, direct link, QR code

### Flutter App (Mobile)
- Form filler — submit forms on mobile with offline support
- File upload from camera/gallery
- Push notifications when form is assigned
- Quick access to recently used forms
- Draft auto-save — if user closes mid-form, resume later
- Form completion status (submitted, draft, overdue)
- Admin side: view responses, share form link

## 5. Data Model (MVP)
- **User:** id, name, email, role, workspace_id
- **Workspace:** id, name, plan, settings (JSON)
- **Form:** id, workspace_id, title, description, status (draft/published/closed), settings (JSON), design (JSON), custom_slug, created_at
- **FormElement:** id, form_id, type, label, required, order, options (JSON), validation (JSON), conditions (JSON)
- **FormPage:** id, form_id, title, order, elements (JSON — array of element IDs)
- **Submission:** id, form_id, respondent_id, answers (JSON), status (draft/completed), started_at, completed_at
- **Respondent:** id, email, name, ip_address, user_agent
- **File:** id, submission_id, form_element_id, filename, path, mime_type, size

## 6. API Endpoints (MVP)
- `GET /api/forms` — list forms
- `POST /api/forms` — create form
- `GET /api/forms/{id}` — form with elements (for builder)
- `PUT /api/forms/{id}` — update form config
- `GET /api/forms/{slug}/public` — public form (no auth)
- `POST /api/forms/{slug}/submit` — submit response (public)
- `GET /api/forms/{id}/submissions` — list submissions
- `GET /api/forms/{id}/submissions/{sid}` — single submission detail
- `GET /api/forms/{id}/analytics` — form analytics
- `POST /api/auth/login` — login
- `POST /api/auth/register` — register
- `GET /api/templates` — form templates

## 7. User Interface (Screen List)
- **Dashboard:**
  - Login/Register
  - Forms list (grid/table)
  - Form builder (sidebar elements + canvas + property panel)
  - Condition builder (modal: if question X = Y → show/hide Z)
  - Design editor (themes, colors, branding)
  - Submissions (table + detail view)
  - Analytics (submissions over time, drop-off rate)
  - Templates gallery
  - Settings (confirmation, limits, notifications)
- **Mobile:**
  - Login
  - Assigned forms list
  - Form fill view (one question at a time / scroll)
  - Draft auto-save indicator
  - Camera/gallery upload
  - Submission confirmation
  - Admin: form stats quick view

## 8. Business Model
- **Pricing tiers:**
  - Free: unlimited forms, 100 submissions/mo, basic fields
  - Pro ($19/mo): 1,000 submissions, conditional logic, file upload
  - Business ($49/mo): 10,000 submissions, analytics, branding removal
  - Enterprise ($99/mo): unlimited submissions, custom domain, API
- **Free trial:** 14-day Business trial
- **Target MRR per client:** $19-$99/month

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2):** API — Form CRUD, Elements, Submissions, Auth
- **Phase 2 (Weeks 3-4):** React Dashboard — Form builder, Conditions engine, Submissions view
- **Phase 3 (Weeks 5-6):** Flutter App — Form fill, Offline drafts, File upload, Notifications
- **Phase 4 (Weeks 7-8):** Analytics, Templates, Export, Spam protection, Deploy

## 10. Risk & Mitigation
- **Technical risk:** Complex conditional logic engine
  - *Mitigation:* Store conditions as JSON AST (Abstract Syntax Tree), evaluate on client and server
- **Security risk:** File uploads — malware, excessive size
  - *Mitigation:* Virus scanning, file type whitelist, size limits, signed upload URLs
- **Spam risk:** Bot submissions on public forms
  - *Mitigation:* CAPTCHA, honeypot field, rate limiting per IP
