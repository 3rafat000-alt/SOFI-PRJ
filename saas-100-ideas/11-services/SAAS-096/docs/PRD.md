# PRD: NGOmgt (SAAS-096)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary

**منصة متكاملة لإدارة الجمعيات الخيرية والمنظمات غير الحكومية.** تهدف NGOmgt إلى رقمنة إدارة العمل الخيري بالكامل — من إدارة المتبرعين والحملات إلى تنفيذ المشاريع وإعداد التقارير المالية والإدارية.

- المشكلة: الجمعيات الخيرية تواجه صعوبات في تتبع التبرعات، إدارة المشاريع الميدانية، إعداد تقارير الشفافية للمانحين، والتواصل مع المتبرعين بشكل منظم. معظمها يعتمد على إكسل وسجلات ورقية.
- الحل: Laravel API + React Dashboard + Flutter App.

## 2. Market & Opportunity

- السوق المستهدف: 10,000+ جمعية خيرية ومنظمة غير حكومية في الشرق الأوسط وشمال أفريقيا
- الفئة: B2B (جمعيات خيرية، منظمات غير حكومية، مؤسسات وقفية)
- المنافسون:
  - **Bloomberg Philanthropies Tools** — مجاني لكن معقد، غير موجه للمنطقة العربية.
  - **DonorPerfect** — نظام إدارة متبرعين (أجنبي، $1,000+ سنوياً).
  - **إحسان** — منصة تبرعات سعودية حكومية (ليست نظام إدارة شامل).
  - **Blackbaud** — حلول مؤسسات غير ربحية (مكلف جداً، $5,000+ سنوياً).
- التمايز: نظام متكامل مصمم خصيصاً للمنظمات العربية (توافق مع قوانين العمل الخيري المحلية)، متعدد اللغات (عربي + إنجليزي)، أسعار مخفضة للقطاع غير الربحي.

## 3. User Personas

### شخص أساسي: مدير جمعية خيرية
- الاسم: عبدالله
- الدور: يدير جمعية خيرية متوسطة في الرياض تضم 15 موظفاً و 50 متطوعاً
- الأهداف: تنظيم التبرعات، توثيق المشاريع، إعداد تقارير شفافة للمانحين
- نقاط الألم: صعوبة تتبع التبرعات النقدية والعينية، ضعف التقارير، إهدار وقت في الإجراءات اليدوية

### شخص أساسي: مسئول العلاقات مع المتبرعين
- الاسم: هدى
- الدور: تتولى التواصل مع المتبرعين وتنظيم حملات التبرع
- الأهداف: إدارة قاعدة المتبرعين، إطلاق حملات مستهدفة، إرسال تقارير أثر التبرعات
- نقاط الألم: عدم وجود نظام لإدارة علاقات المتبرعين، صعوبة قياس رضا المانحين

### Admin: مشرف المنصة
- إدارة صلاحيات المستخدمين، مراقبة الامتثال، إعداد التقارير الموحدة.

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Organization, Donor, Campaign, Project, Volunteer, Donation, Expense, Report, Beneficiary
- RESTful CRUD for all resources
- Role-based auth (Admin, OrgManager, Donor, Volunteer)
- Donation management — one-time, recurring, in-kind, zakat calculation
- Campaign management — goal tracking, progress bar, donor wall, expiry dates
- Project management — budget, milestones, activities, beneficiary tracking
- Expense tracking — category, receipt upload, approval workflow
- Report generation — financial reports, impact reports, donor statements
- Zakat calculation engine — nisab threshold, asset types, automatic calculation
- Notification engine: SMS, email (donation receipts, campaign updates), push

### React Dashboard (Web)
- Organization dashboard: donation overview, campaign progress, expense summary
- Donor management: donor profiles, donation history, communication log, segments
- Campaign manager: create/edit campaigns, fundraising goals, tracking widget
- Project management: milestones, budget tracking, beneficiary stories, photo uploads
- Volunteer management: registration, scheduling, hours tracking, certificates
- Financial management: income/expense tracking, budget vs actual, audit trail
- Report builder: financial reports, annual reports, donor impact reports (export PDF/Excel)
- Settings: organization profile, bank accounts, tax receipts, notification templates

### Flutter App (Mobile)
- Donor app: browse campaigns, donate, view impact reports, recurring donation setup
- Fundraising campaigns: view progress, share on social media, invite friends
- Project updates: photo/video updates from the field, beneficiary stories
- Donation receipt: instant tax receipt download
- Push notifications: campaign milestones, project updates, receipt delivery
- Volunteer app: view volunteer opportunities, sign up, check-in, log hours
- Beneficiary stories: real-time updates from projects

## 5. Data Model (MVP)

### Organization
- id, name, license_number, registration_country, type (charity/ngo/waqf), mission_statement, bank_accounts (JSON), tax_id, status (active/inactive), created_at

### Donor
- id, user_id (FK), type (individual/corporate), total_donated, last_donation_date, donation_count, preferred_causes (JSON), communication_preferences (JSON), created_at

### Campaign
- id, organization_id (FK), name, description, goal_amount, raised_amount, start_date, end_date, status (draft/active/completed/cancelled), cover_image, created_at

### Project
- id, organization_id (FK), campaign_id (FK), name, description, budget, spent_amount, location, status (planning/active/completed), start_date, end_date, created_at

### Donation
- id, donor_id (FK), campaign_id (FK), organization_id (FK), type (one-time/recurring/in-kind/zakat), amount, payment_method, receipt_number, status (pending/completed/refunded), notes, recurring_frequency (monthly/quarterly/yearly), created_at

### Volunteer
- id, user_id (FK), organization_id (FK), skills (JSON), availability (JSON), total_hours, status (active/inactive), created_at

### VolunteerShift
- id, volunteer_id (FK), project_id (FK), start_time, end_time, hours_logged, task_description, status (signed_up/checked_in/completed), created_at

### Expense
- id, project_id (FK), organization_id (FK), category, amount, description, receipt_path, approved_by, status (pending/approved/rejected), created_at

### Beneficiary
- id, project_id (FK), name, id_number, contact, assistance_type, amount_received, story, created_at

### Report
- id, organization_id (FK), type (financial/annual/impact/donor), period_start, period_end, data (JSON), generated_at, created_at

## 6. API Endpoints (MVP)

```
POST   /api/auth/login
GET    /api/auth/me

GET    /api/organizations/{id}/dashboard

GET    /api/donors
POST   /api/donors
GET    /api/donors/{id}
GET    /api/donors/{id}/donations
PUT    /api/donors/{id}/communication

GET    /api/campaigns
POST   /api/campaigns
GET    /api/campaigns/{id}
PUT    /api/campaigns/{id}
GET    /api/campaigns/{id}/donors

GET    /api/projects
POST   /api/projects
GET    /api/projects/{id}
PUT    /api/projects/{id}
GET    /api/projects/{id}/expenses
GET    /api/projects/{id}/beneficiaries

POST   /api/donations
GET    /api/donations
GET    /api/donations/{id}
GET    /api/donations/recurring

GET    /api/volunteers
POST    /api/volunteers
GET    /api/volunteers/{id}/shifts
POST   /api/volunteer-shifts
PUT    /api/volunteer-shifts/{id}/check-in
PUT    /api/volunteer-shifts/{id}/complete

GET    /api/expenses
POST   /api/expenses
PUT    /api/expenses/{id}/approve

POST   /api/reports/generate
GET    /api/reports
GET    /api/reports/{id}/download
```

## 7. User Interface (Screen List)

### Dashboard Screens (React)
1. Login / Register
2. Organization Dashboard — donation heatmap, campaign progress bars, expense pie chart
3. Donor Management — directory, segments, communication history
4. Campaign Management — create/edit, progress tracking, donor wall
5. Project Management — milestones, budget, photo gallery, beneficiary stories
6. Financial Management — income vs expense, budget tracking, audit log
7. Volunteer Management — directory, scheduling, hours dashboard
8. Report Builder — template selection, date range, export options
9. Settings — org profile, bank accounts, notification templates

### Mobile Screens (Flutter)
1. Donor: Campaign Feed — browse active campaigns
2. Donor: Campaign Detail — story, progress, donate button
3. Donor: Donation Flow — amount, payment method, recurring options
4. Donor: My Donations — history, receipts
5. Donor: Impact — project updates, beneficiary stories
6. Volunteer: Opportunities — available shifts, sign up
7. Volunteer: My Shifts — upcoming, history, check-in
8. Volunteer: Hours Log — total hours, certificates
9. Notifications — campaign updates, receipts, shift reminders

### Screen Flow
```
Donor: Browse Campaigns → Select → View Detail → Donate → Receipt → Follow Updates
Volunteer: Browse Opportunities → Sign Up → Check In → Complete Shift → Log Hours → Certificate
Admin: Dashboard → Manage Campaigns → Track Projects → Generate Reports → Export
```

## 8. Business Model

- **باقة البداية**: $19/شهر (جمعية واحدة، 500 متبرع، 3 مشاريع)
- **باقة النمو**: $49/شهر (جمعية واحدة، غير محدود متبرعين، 20 مشروع)
- **باقة المؤسسات**: $99/شهر (غير محدود جمعيات، تقارير متقدمة، API)
- **خصم للجمعيات الصغيرة**: 50% للمؤسسات المسجلة حديثاً (أول 6 أشهر)
- فترة تجربة مجانية: 30 يوماً
- MRR المستهدف لكل عميل: $19-$99

## 9. Implementation Plan

- Phase 1 (Weeks 1-2): Laravel API — Auth, Organization/Donor/Campaign CRUD, Sanctum roles
- Phase 2 (Weeks 3-4): Laravel API — Project/Expense/Volunteer management, donation processing
- Phase 3 (Weeks 5-6): React Dashboard — Org dashboard, donor management, campaign tools, reports
- Phase 4 (Weeks 7-8): Flutter App — Donor campaign browsing, donation flow, volunteer app
- Phase 5 (Weeks 9-10): Report builder, zakat calculation, Arabic localization, testing, deploy

## 10. Risk & Mitigation

- **مخاطرة تنظيمية**: اختلاف قوانين العمل الخيري والرقابة بين الدول — التخفيف: إعداد تقارير متوافقة مع معايير كل دولة، نظام تدقيق مدمج.
- **مخاطرة ثقة**: حساسية بيانات المتبرعين والمستفيدين — التخفيف: تشفير البيانات، سياسة خصوصية صارمة، سجلات تدقيق.
- **مخاطرة سوقية**: الميزانيات المحدودة للجمعيات الخيرية — التخفيف: أسعار مخفضة، خصومات للمؤسسات الصغيرة، نموذج freemium.
- **مخاطرة تقنية**: تكامل بوابات الدفع للتبرعات — التخفيف: دعم بوابات متعددة تشمل الدفع المحلي (STC Pay، فوري، كليك).
