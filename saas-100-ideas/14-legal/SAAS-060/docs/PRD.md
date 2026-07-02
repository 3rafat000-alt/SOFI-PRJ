# PRD: WorkPermit (SAAS-060)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary

- **One-liner:** منصة لإدارة المعاملات القانونية — تراخيص، أوراق رسمية، تذكير بالتجديد — للأفراد والشركات مع متابعة ذكية للمواعيد النهائية.
- **Problem:** الأفراد والشركات يعانون من تعقيد المعاملات القانونية والإدارية — مواعيد تجديد التراخيص المنسية، الأوراق المفقودة، إجراءات إصدار التراخيص المعقدة، وعدم وجود نظام مركزي يذكرهم بمواعيد التجديد والمواقف النهائية.
- **Proposed solution:** Laravel API (إدارة المعاملات والوثائق والتذكير) + React Dashboard (لوحة تحكم للمكاتب القانونية والشركات) + Flutter App (تطبيق للأفراد لمتابعة معاملاتهم).

## 2. Market & Opportunity

- **Target market:** الأفراد والشركات في العالم العربي الذين يحتاجون لإدارة تراخيصهم وتجديداتهم — تراخيص البلدية، السجلات التجارية، التراخيص المهنية، التأشيرات، الإقامات. يقدر السوق بملايين المعاملات سنوياً.
- **Customer segment:** B2C (أفراد) + B2B (شركات، مكاتب استشارات قانونية، محامون).
- **Competitor landscape:**
  1. أبشر (Absher) — منصة حكومية سعودية لكنها محدودة بالإجراءات الحكومية فقط.
  2. منصات حكومية محلية — كل حكومة منصة خاصة بها.
  3. إكسل وورق — الطريقة الأكثر شيوعاً.
  4. Google Calendar / تذكيرات الجوال — تذكير أساسي بدون إدارة معاملات.
  5. LegalZoom — منصة أمريكية متخصصة لكنها لا تدعم السوق العربي.
- **Differentiation:** منصة مركزية تدير كل أنواع التراخيص والمعاملات من جهة واحدة (حكومية، مهنية، تجارية). تذكير ذكي (قبل 30، 15، 7، 3 أيام). تخزين آمن للوثائق. مساعدة في إجراءات التجديد (روابط، نماذج). دعم كامل للغة العربية. تحليل الإنفاق على التراخيص.

## 3. User Personas

### أساسي: صاحب شركة صغيرة — سليمان
- **الدور:** صاحب شركة مقاولات صغيرة
- **الأهداف:** إدارة تراخيص الشركة (البلدية، التجارية، المهنية)، تجديدها في الوقت المناسب، تخزين الوثائق
- **نقاط الألم:** نسيان تجديد التراخيص، غرامات التأخير، صعوبة العثور على الوثائق المطلوبة عند الحاجة، عدم معرفة الإجراءات الجديدة

### أساسي: محامٍ أو مستشار — ريم
- **الدور:** محامية تدير معاملات لعملائها
- **الأهداف:** متابعة معاملات العملاء، إدارة المواعيد النهائية، رفع المستندات، التواصل مع الجهات
- **نقاط الألم:** تعدد العملاء وكثرة المواعيد، فقدان المستندات، إعادة العمل على نفس المعاملة

### ثانوي: فرد — هادي
- **الدور:** موظف يحتاج إدارة إقامته وتأشيراته
- **الأهداف:** متابعة صلاحية الإقامة، تجديد التأشيرة، تذكير بالوثائق المطلوبة
- **نقاط الألم:** نسيان تواريخ انتهاء الإقامة، غرامات التمديد، عدم معرفة الإجراءات

### إداري: مشغل النظام
- **الدور:** مسؤول المنصة
- **الأهداف:** إدارة المستخدمين، مراقبة المعاملات

## 4. Features by Platform

### Laravel API (Backend)

- User management (individual, company, legal office)
- License/permit types taxonomy (municipal, commercial, professional, visa, residency)
- Document management (upload, OCR, expiry tracking)
- Transaction/workflow management (per type)
- Smart reminder engine (30-15-7-3-0 days before expiry)
- Government fee tracking & payment
- Document templates & auto-fill
- Lawyer/office multi-client management
- Audit log (who did what, when)
- Notification engine (push, email, SMS)
- Reporting (expiry calendar, spending analysis)

### React Dashboard (Web)

- Dashboard: upcoming expiries, overdue alerts, active transactions
- Licenses: full catalog, status, documents attached
- Document vault: organized by type, searchable, expiry-aware
- Reminders: configurable notification schedules, history
- Clients (legal office): multi-client view, bulk actions
- Transactions: workflow tracker for each permit type
- Templates: document templates for common government forms
- Reports: expiry forecast, cost analysis, compliance score
- Settings: license types, reminder defaults, notification channels

### Flutter App (Mobile)

- Individual app: my licenses, documents vault, expiry countdowns
- Upload documents via camera
- Scan and OCR document details (future)
- Push notifications for upcoming renewals
- Step-by-step renewal guides per license type
- Share document with lawyer/office
- Arabic-first Material 3 UI
- Widget for home screen showing next expiry

## 5. Data Model (MVP)

- **User:** id, name, email, phone, role (individual/company/admin/lawyer), id_number, created_at
- **Company:** id, user_id, name, commercial_registration, tax_number, address, city, status
- **LicenseType:** id, name_ar, name_en, category (municipal/commercial/professional/visa/other), issuing_authority, renewal_period_days, icon
- **License:** id, user_id, company_id, license_type_id, license_number, issuing_authority, issue_date, expiry_date, status (active/expired/cancelled/renewing), fee, notes, created_at
- **Document:** id, license_id, user_id, name, type (id/contract/certificate/other), file_url, expiry_date, is_required, uploaded_at, notes
- **Task:** id, license_id, user_id, title, description, deadline, status (pending/in_progress/completed), completed_at, assigned_to
- **Reminder:** id, license_id, user_id, days_before, channel (push/email/sms), sent_at, status
- **Transaction:** id, license_id, user_id, type (new/renewal/amendment/cancellation), status (draft/submitted/in_progress/completed/rejected), notes, submitted_at, completed_date
- **TransactionStep:** id, transaction_id, step_name, status, deadline, notes
- **Note:** id, license_id, user_id, content, created_at
- **Notification:** id, user_id, title, body, type, is_read, created_at
- **Subscription:** id, user_id, plan, start_date, end_date, status

## 6. API Endpoints (MVP)

- `POST /api/register` — Register
- `POST /api/login` — Auth
- `GET /api/user` — Profile
- `PUT /api/user` — Update profile
- `GET /api/licenses` — My licenses
- `POST /api/licenses` — Add license
- `GET /api/licenses/{id}` — License detail
- `PUT /api/licenses/{id}` — Update license
- `DELETE /api/licenses/{id}` — Remove license
- `GET /api/licenses/types` — License types
- `GET /api/licenses/expiring` — Expiring soon
- `GET /api/licenses/overdue` — Overdue licenses
- `GET /api/documents` — Document list
- `POST /api/documents` — Upload document
- `DELETE /api/documents/{id}` — Delete document
- `GET /api/tasks` — Task list
- `POST /api/tasks` — Create task
- `PUT /api/tasks/{id}` — Update task
- `GET /api/reminders` — Reminder settings
- `PUT /api/reminders/{id}` — Update reminder
- `GET /api/transactions` — Transaction history
- `POST /api/transactions` — Start transaction
- `PUT /api/transactions/{id}` — Update transaction
- `POST /api/notes` — Add note
- `GET /api/notes?license_id=X` — License notes
- `GET /api/reports/expiry-calendar` — Expiry calendar
- `GET /api/reports/spending` — Fee spending analysis
- `GET /api/reports/compliance` — Compliance score
- `GET /api/notifications` — Notifications
- `POST /api/notifications/read-all` — Mark all read

## 7. User Interface (Screen List)

### Dashboard Screens (React)
- Login
- Dashboard: expiry countdown widget, alerts, active licenses count
- Licenses: table with status indicators, sort by expiry
- License Detail: tabs (info, documents, tasks, history)
- Add License: wizard (select type → enter details → upload docs)
- Documents: grid/list view, search, category filter
- Tasks: kanban or list view, assign, deadline tracking
- Reminders: preferences per license, per channel
- Transactions: timeline per transaction, step tracker
- Templates: editable document templates
- Reports: expiry forecast timeline, cost analysis chart
- Clients (lawyer view): client list, bulk expiry overview
- Settings: profile, notification preferences, billing

### Mobile Screens (Flutter)
- Splash / Onboarding
- Home: next 3 expiries countdown, compliance score ring
- My Licenses: categorized list (commercial, professional, personal)
- License Detail: full info, countdown timer, documents, actions
- Document Scanner: camera capture, auto-categorize
- Expiry Calendar: monthly grid with expiry badges
- Notifications: reminder list with acknowledge
- Quick Actions: renew, upload doc, view guide
- Profile: subscription, settings
- Lawyer Portal: client switcher, multi-license view

### Screen Flow
```
Home → Dashboard (expiry countdowns, alerts)
  → License List → License Detail → Renew Action → Upload Documents → Transaction Tracker
  → Document Vault → Upload → Categorize
  → Tasks → Complete → Update License
  → Calendar → Monthly View → Expiry Details
  → Reports → Compliance Score → Spending
```

## 8. Business Model

- **Pricing tiers:**
  - فردي مجاني: 3 تراخيص، تذكير أساسي 7 أيام
  - فردي مميز $7.99/شهر: 15 ترخيصاً، تذكير متعدد، تخزين وثائق
  - شركة صغيرة $19.99/شهر: 50 ترخيصاً، 3 مستخدمين، تقارير
  - شركة $49.99/شهر: تراخيص غير محدودة، مستخدمين غير محدودين، تقارير متقدمة
  - مكتب قانوني $79.99/شهر: إدارة عملاء متعددة، قوالب، أولوية دعم
- **Free trial:** 30 يوم تجربة مجانية للباقات المدفوعة
- **Target MRR per client:** $7.99-$79.99
- **Additional:** استشارات مدفوعة عبر المنصة (تكامل مستقبلي)

## 9. Implementation Plan

- **Phase 1 (Weeks 1-2):** Auth + LicenseType + License + Document APIs + user profiles
- **Phase 2 (Weeks 3-4):** Task + Reminder engine + Transaction + Note APIs + notification engine
- **Phase 3 (Weeks 5-6):** React Dashboard — license management, documents, reports, admin
- **Phase 4 (Weeks 7-8):** Flutter App — license view, document upload, reminders, calendar
- **Phase 5 (Weeks 9-10):** Smart reminder testing, OCR integration (future), deployment

## 10. Risk & Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| اختلاف أنواع التراخيص بين الدول | High | تصميم taxonomy مرن قابل للتوسع، إعدادات مخصصة لكل سوق |
| المنافسة من التطبيقات الحكومية الرسمية | Medium | تكامل مع المنصات الحكومية (حيثما أمكن)، قيمة مضافة في التذكير والتنظيم |
| حساسية البيانات الشخصية والوثائق | High | تشفير AES-256، تخزين آمن، توافق مع قوانين حماية البيانات المحلية |
| صعوبة تحديث معلومات التراخيص | Medium | تذكير دوري للمستخدم لتحديث البيانات، رابط مباشر للجهة المختصة |
| نموذج الإيرادات (المستخدمون يفضلون المجاني) | Medium | مستوى مجاني قيم، رسوم منخفضة جداً للمميز، التركيز على توفير المال (تجنب الغرامات) |
