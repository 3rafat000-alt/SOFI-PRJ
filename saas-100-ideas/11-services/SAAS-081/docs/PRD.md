# PRD: CemeteryMgt (SAAS-081)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** منصة متكاملة لإدارة المقابر تمكن البلديات والجمعيات من أرشفة سجلات الدفن إلكترونياً، وإدارة الزوار، وتنظيم أعمال الصيانة، وحجز المقابر بشكل رقمي بالكامل.
- **Problem statement:** تعتمد إدارة المقابر حالياً على سجلات ورقية وعمليات يدوية تؤدي إلى فقدان البيانات، صعوبة إيجاد سجلات الدفن القديمة، ازدحام الزوار في المناسبات، وعدم وجود نظام موحد للصيانة والحجوزات.
- **Proposed solution:** Laravel API + React Dashboard + Flutter App

## 2. Market & Opportunity
- **Target market:** البلديات والمجالس المحلية التي تدير المقابر العامة، الجمعيات الخيرية المشرفة على المقابر، وشركات الصيانة المتعاقدة. السوق المستهدف يشمل آلاف البلديات في العالم العربي.
- **Customer segment:** B2G (Business to Government) / B2B
- **Competitor landscape:**
  1. **Cemetery.io** - منصة أمريكية تركز على النعي والتذكار لا على الإدارة
  2. **BillionGraves** - تطبيق لتوثيق القبور فقط
  3. **Chronicle** - نظام بريطاني للمقابر لكن سعره مرتفع
  4. أنظمة محلية بدائية تعتمد على Excel
- **Differentiation:** حل عربي شامل يدير العمليات الخلفية بالكامل (حجوزات، صيانة، زوار، سجلات) مع تطبيق جوال للزوار للاستعلام والملاحة داخل المقبرة.

## 3. User Personas

### الشخصية الأساسية: مدير المقبرة - أحمد
- **الدور:** مشرف على إدارة المقبرة البلدية
- **الأهداف:** تحويل السجلات الورقية إلى رقمية، تسريع عملية إيجاد سجلات الدفن، تنظيم مواعيد الزوار
- **نقاط الألم:** السجلات الورقية تتعرض للتلف، صعوبة البحث في آلاف السجلات، شكاوى الزوار من عدم التنظيم

### الشخصية الثانوية: زائر - فاطمة
- **الدور:** فرد من عائلة المتوفي تزور المقبرة للزيارة
- **الأهداف:** معرفة موقع القبر بدقة، حجز موعد زيارة، استخراج شهادات دفن
- **نقاط الألم:** عدم معرفة موقع القبر، أوقات الانتظار الطويلة، صعوبة استخراج المستندات

### الشخصية الإدارية: مشرف النظام - خالد
- **الدور:** مدير تكنولوجيا المعلومات في البلدية
- **الأهداف:** مراقبة أداء النظام، إدارة صلاحيات المستخدمين، تصدير التقارير والإحصائيات
- **نقاط الألم:** الحاجة لتقارير دقيقة عن إشغال المقابر، تتبع أعمال الصيانة

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Burial, Grave, Plot, Visitor, Maintenance, Reservation, Payment
- RESTful endpoints for all CRUD operations
- Auth & roles: SuperAdmin, MunicipalityAdmin, MaintenanceStaff, Visitor
- Notifications: SMS for visit reminders, email for maintenance alerts
- Search engine for burial records with fuzzy matching
- QR code generation per grave
- Geo-fencing for cemetery zones
- Audit log for all record changes

### React Dashboard (Web)
- Interactive cemetery map with plot occupancy visualization
- Burial record management with advanced search
- Maintenance scheduling dashboard
- Reservation calendar for plot visits
- Reporting module (occupancy rates, maintenance costs, visitor statistics)
- User and role management
- Document generation (burial certificates, permits)
- Payment tracking for plot reservations

### Flutter App (Mobile)
- Grave locator with GPS navigation inside cemetery
- Visit scheduling and check-in
- Burial record lookup by deceased name or QR scan
- Maintenance request submission with photo upload
- Push notifications for visit reminders and cemetery events
- Offline access to cemetery maps
- Donation/payment for plot reservations via Mada/Apple Pay

## 5. Data Model (MVP)

- **User:** id, name, email, password, role, phone, avatar, created_at
- **Cemetery:** id, name, address, lat, lng, total_plots, capacity, opening_hours, municipality_id
- **Plot:** id, cemetery_id, section, row, number, type, status (available/occupied/reserved), price, qr_code
- **Grave:** id, plot_id, deceased_name, deceased_dob, deceased_dod, burial_date, certificate_number, notes
- **Burial:** id, grave_id, deceased_name, guardian_name, guardian_phone, burial_date, ceremony_type, created_by
- **Visitor:** id, name, phone, email, relation_type, grave_id, visit_date, check_in_time, check_out_time
- **Maintenance:** id, plot_id, type, description, status, assigned_to, scheduled_date, completed_date, cost
- **Reservation:** id, plot_id, visitor_id, reservation_date, status, payment_status, amount
- **Payment:** id, reservation_id, amount, method, transaction_id, status, paid_at

## 6. API Endpoints (MVP)

- `POST /api/auth/login` - Login
- `POST /api/auth/register` - Register
- `GET /api/cemeteries` - List cemeteries
- `GET /api/cemeteries/{id}` - Cemetery details
- `POST /api/cemeteries` - Create cemetery (admin)
- `PUT /api/cemeteries/{id}` - Update cemetery
- `GET /api/plots` - List plots with filters
- `POST /api/plots` - Add plot
- `GET /api/plots/{id}` - Plot details with history
- `PUT /api/plots/{id}` - Update plot status
- `GET /api/graves` - Search graves
- `POST /api/graves` - Add burial record
- `GET /api/graves/{id}` - Grave details
- `GET /api/visitors` - Visitor log
- `POST /api/visitors` - Record visit
- `GET /api/maintenance` - Maintenance list
- `POST /api/maintenance` - Create maintenance ticket
- `PUT /api/maintenance/{id}` - Update maintenance status
- `GET /api/reservations` - List reservations
- `POST /api/reservations` - Create reservation
- `POST /api/payments` - Process payment
- `GET /api/reports/occupancy` - Occupancy report
- `GET /api/reports/maintenance` - Maintenance report

## 7. User Interface (Screen List)

### Dashboard Screens (React)
1. Login / Forgot Password
2. Cemetery Overview - map + stats dashboard
3. Plot Management - grid/map view with filters
4. Burial Records - searchable table with filters
5. Visitor Log - real-time visitor tracking
6. Maintenance Board - kanban-style task management
7. Reservations Calendar - monthly/weekly view
8. Reports - charts for occupancy, costs, visits
9. Users & Roles - admin panel
10. Settings - cemetery configuration

### Mobile Screens (Flutter)
1. Splash / Login
2. Home - map overview + quick actions
3. Grave Locator - map with GPS navigation
4. Visitor Check-in - QR scanner
5. Burial Record Search - by name/date/QR
6. Reservation Request - date picker + plot selector
7. Maintenance Report - photo + description form
8. Notifications - list of alerts
9. Profile - user settings

### Screen Flow
Login → Dashboard → [Cemetery Map | Records | Maintenance | Reports] → Detail views

## 8. Business Model
- **Pricing tiers:** Basic $99/month (1 cemetery, 500 records), Professional $199/month (3 cemeteries, unlimited records), Enterprise $499/month (unlimited)
- **Free trial:** 14-day free trial with full features, limited to 50 records
- **Target MRR per client:** $99-$499
- **Additional revenue:** Implementation fee $500, QR code tags $2/unit, SMS credits $0.03/message

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2):** Laravel API - Auth, Cemeteries, Plots, Graves CRUD + database schema + search
- **Phase 2 (Weeks 3-4):** React Dashboard - Map view, burial records management, maintenance board
- **Phase 3 (Weeks 5-6):** Flutter App - Grave locator, visitor check-in, reservation flow
- **Phase 4 (Weeks 7-8):** QR code integration, payment gateway, reporting, testing, deployment

## 10. Risk & Mitigation
- **Technical risks:** GPS accuracy in dense cemetery layouts → Mitigation: integrate offline maps + manual grid coordinates
- **Data migration:** Converting decades of paper records → Mitigation: batch import tool + OCR for old ledgers
- **Cultural sensitivity:** Privacy concerns around burial data → Mitigation: granular access controls, data encryption, GDPR compliance
- **Adoption:** Municipality staff resistant to digital transition → Mitigation: training program + simple UI designed for non-tech users
- **Market risk:** Low budget allocation for cemetery digitization → Mitigation: target high-population municipalities first, ROI calculator in pitch
