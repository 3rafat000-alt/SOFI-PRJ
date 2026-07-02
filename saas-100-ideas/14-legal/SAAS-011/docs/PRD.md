# PRD: LawDesk (SAAS-011)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary

**منصة متكاملة لإدارة مكاتب المحاماة.** تهدف LawDesk إلى رقمنة إدارة القضايا، الموكلين، الجلسات، والمستندات القانونية في مكان واحد. توفر المنصة لوحة تحكم للمحامين لمتابعة أدوار القضايا، إدارة المواعيد، وأرشفة المستندات القانونية.

- المشكلة: مكاتب المحاماة تعاني من التشتت بين أنظمة متعددة (جداول إكسل، مستندات ورقية، تقويمات يدوية) مما يؤدي لضياع المواعيد وضعف المتابعة.
- الحل: Laravel API + React Dashboard + Flutter App.

## 2. Market & Opportunity

- السوق المستهدف: 50,000+ مكتب محاماة في الشرق الأوسط وشمال أفريقيا
- الفئة: B2B (مكاتب محاماة، محامون مستقلون)
- المنافسون:
  - **Clio** — منصة قانونية سحابية (أجنبية، لا تدعم العربية).
  - **MyCase** — إدارة القضايا (أجنبية، اشتراك مرتفع).
  - **محامي السعودية** — حل محلي محدود الميزات.
- التمايز: دعم كامل للعربية، توافق مع الأنظمة القضائية المحلية (مصر، السعودية، الإمارات)، أسعار تنافسية.

## 3. User Personas

### شخص أساسي: المحامي المستقل
- الاسم: أحمد
- الدور: محامٍ يتابع 20-30 قضية في وقت واحد
- الأهداف: تنظيم مواعيد الجلسات، توثيق المرافعات، إدارة اتصالات الموكلين
- نقاط الألم: صعوبة تتبع مواعيد الجلسات، تكرار إدخال البيانات، فقدان المستندات

### شخص أساسي: مدير مكتب محاماة
- الاسم: ليلى
- الدور: تدير مكتباً بخمسة محامين وموظفين إداريين
- الأهداف: توزيع القضايا، متابعة أداء الفريق، إعداد الفواتير
- نقاط الألم: عدم وضوح سير العمل، صعوبة إعداد التقارير المالية

### Admin: مشرف النظام
- مدير حساب المكتب، إدارة الصلاحيات وخطط الاشتراك.

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Case, Client, Session, Document, Invoice, Task
- RESTful CRUD for all resources
- Role-based auth (Admin, Lawyer, Paralegal, Accountant)
- Notification engine: email (case updates), push (session reminders), SMS (client alerts)
- Document upload with OCR metadata extraction
- Full-text search across cases and documents

### React Dashboard (Web)
- Dashboard: case pipeline (filing → hearing → verdict → closed), upcoming sessions calendar
- Case management: create/edit/view cases with full timeline
- Client management: contact details, case history, communication log
- Document repository: upload, tag, search contracts and pleadings
- Invoice generation: billable hours tracking, payment reminders
- Reports: revenue by practice area, case closure rate, client acquisition

### Flutter App (Mobile)
- Real-time session reminders and push notifications
- Mobile case lookup — search and view case details
- Court session calendar sync with device calendar
- Document scanner with OCR — snap and save court documents
- Client communication: in-app messaging, call log
- Offline access to cached case list

## 5. Data Model (MVP)

### Case
- id, case_number, title, type (civil/criminal/commercial), status (filing/hearing/verdict/closed), court, judge, filing_date, next_session_date, created_at, updated_at

### Client
- id, name, phone, email, id_number, address, notes, created_at

### Session
- id, case_id (FK), session_date, session_type, court_room, judge_notes, outcome, next_session_date, created_at

### Document
- id, case_id (FK), title, document_type, file_path, ocr_text, tags, uploaded_by, created_at

### Invoice
- id, client_id (FK), case_id (FK), amount, status (draft/sent/paid/overdue), due_date, line_items (JSON), created_at

### Task
- id, case_id (FK), assigned_to, title, description, due_date, priority, status, created_at

### User (Laravel Sanctum)
- id, name, email, password, role (admin/lawyer/paralegal/accountant), firm_id (FK), created_at

### Firm
- id, name, license_number, address, subscription_tier, settings (JSON), created_at

## 6. API Endpoints (MVP)

```
POST   /api/auth/login
POST   /api/auth/logout
GET    /api/auth/me

GET    /api/cases
POST   /api/cases
GET    /api/cases/{id}
PUT    /api/cases/{id}
DELETE /api/cases/{id}
GET    /api/cases/{id}/sessions
GET    /api/cases/{id}/documents

GET    /api/clients
POST   /api/clients
GET    /api/clients/{id}
PUT    /api/clients/{id}

GET    /api/sessions
POST   /api/sessions
PUT    /api/sessions/{id}
GET    /api/sessions/upcoming

GET    /api/documents
POST   /api/documents/upload
GET    /api/documents/{id}
DELETE /api/documents/{id}

GET    /api/invoices
POST   /api/invoices
PUT    /api/invoices/{id}/pay

GET    /api/tasks
POST   /api/tasks
PUT    /api/tasks/{id}/status

GET    /api/firms/settings
PUT    /api/firms/settings
GET    /api/firms/users
POST   /api/firms/users/invite
```

## 7. User Interface (Screen List)

### Dashboard Screens (React)
1. Login / Register
2. Dashboard — case pipeline widget, calendar, KPI cards
3. Case List — filterable table with search
4. Case Detail — timeline, sessions, documents tabs
5. Client List — searchable directory
6. Client Detail — profile, case history, invoices
7. Calendar — monthly/weekly/daily session view
8. Documents — repository with tagging and search
9. Invoices — billing dashboard with payment tracking
10. Settings — firm profile, user management, subscription

### Mobile Screens (Flutter)
1. Splash → Login
2. Dashboard — upcoming sessions, recent cases
3. Case List — searchable, filterable
4. Case Detail — status, sessions, documents
5. Session Reminder — push notification → detail view
6. Document Scanner — camera → upload
7. Calendar — session schedule
8. Profile — personal settings

### Screen Flow
Login → Dashboard → Select Case → View Detail → Manage Sessions/Documents

## 8. Business Model

- **الباقة الأساسية**: $49/شهر (محامٍ واحد، 50 قضية)
- **الباقة الاحترافية**: $99/شهر (حتى 5 محامين، غير محدود القضايا)
- **باقة المؤسسات**: $199/شهر (غير محدود المستخدمين، تقارير متقدمة)
- فترة تجربة مجانية: 14 يوماً
- MRR المستهدف لكل عميل: $49-$199

## 9. Implementation Plan

- Phase 1 (Weeks 1-2): Laravel API — Auth, User/Firm/Case/Client CRUD, Sanctum roles
- Phase 2 (Weeks 3-4): Laravel API — Sessions, Documents (upload+OCR), Invoices, Tasks
- Phase 3 (Weeks 5-6): React Dashboard — All screens above, Chart.js visualizations
- Phase 4 (Weeks 7-8): Flutter App — Customer features, push notifications, offline cache
- Phase 5 (Weeks 9-10): Integration testing, Arabic localization, deployment

## 10. Risk & Mitigation

- **مخاطرة تقنية**: التعقيد القانوني لاختلاف الأنظمة القضائية بين الدول
  - التخفيف: البدء بدولة واحدة (مصر)، ثم التوسع
- **مخاطرة سوقية**: صعوبة تبني المحامين للأنظمة الرقمية
  - التخفيف: واجهة بسيطة، دعم فني مكثف، فترة تجريبية مجانية
- **مخاطرة أمنية**: حساسية البيانات القانونية والسرية
  - التخفيف: تشفير AES-256، سجلات تدقيق، توافق مع قوانين حماية البيانات
