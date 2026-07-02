# PRD: LabMgt (SAAS-061)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner**: نظام إدارة المختبرات الطبية السحابي — تحاليل، نتائج، تذكير آلي، وتقارير لمختبرات التحاليل الطبية.
- **Problem statement**: المختبرات الطبية تعتمد على أنظمة قديمة أو ورقية لإدارة التحاليل، مما يسبب أخطاء في تسجيل النتائج، تأخير في تسليم التقارير للمرضى، وصعوبة في تتبع العينات وإدارة المخزون.
- **Proposed solution**: Laravel API + React Dashboard + Flutter App — إدارة شاملة للتحاليل (مدخلات/مخرجات)، تسجيل نتائج، تذكير المرضى بالنتائج، وإدارة مخزون المستلزمات.

## 2. Market & Opportunity
- **Target market size**: سوق إدارة المختبرات الطبية العالمي ~$5B (2025)، الشرق الأوسط ~$350M نمو 12% سنوياً.
- **Customer segment**: B2B — مختبرات طبية خاصة (5-50 موظف)، معامل المستشفيات الصغيرة، مراكز التحاليل.
- **Competitor landscape**:
  1. **LIS (Lab Information System) تقليدي**: أنظمة سطح مكتب مثل Orchard — باهظة ($10K+)، بدون دعم عربي.
  2. **OpenELIS**: مفتوح المصدر لكن بدون واجهة عربية أو دعم جوال.
  3. **مختبر بلس**: حل محلي بسيط لكن بدون تطبيق مريض أو إشعارات ذكية.
  4. **CGM LABDAQ**: أمريكي معقد، تكلفة عالية، صيانة سنوية مرتفعة.
- **Differentiation**: سحابي بالكامل، عربي، تطبيق مريض لمشاهدة النتائج، تذكير ذكي عبر واتساب، باركود للعينات، تقارير تحليلية.

## 3. User Personas

### Primary: د. ماجد — مدير مختبر طبي
- **الدور**: يدير مختبر تحاليل ب 10 موظفين، يستقبل 50-100 عينة يومياً.
- **الأهداف**: تسريع إدخال النتائج، تقليل الأخطاء، تحسين تجربة المرضى في استلام النتائج.
- **نقاط الألم**: النتائج تتأخر، النظام الحالي معقد، المرضى يزعجون هاتفياً للاستفسار.

### Secondary: سارة — فنية مختبر
- **الدور**: تستقبل العينات، تسجل البيانات، تدخل النتائج بعد التحليل.
- **الأهداف**: إدخال سريع، مسح باركود، طباعة تقارير مباشرة.
- **نقاط الألم**: إدخال يدوي ممل، أخطاء في مطابقة العينات مع المرضى.

### Admin: أحمد — مسؤول النظام
- **الدور**: يدير حسابات المستخدمين، الفواتير، المخزون.
- **الأهداف**: تقارير أداء المختبر، متابعة المخزون، إدارة الأسعار.
- **نقاط الألم**: لا توجد رؤية فورية للإيرادات، المخزون ينفد بدون إنذار.

## 4. Features by Platform

### Laravel API (Backend)
- Core domain models: Lab, Patient, Test, Sample, TestResult, Report, InventoryItem, Reagent, Invoice
- RESTful endpoints: CRUD for all models
- Auth: Sanctum multi-role (admin/technician/receptionist/patient)
- Barcode generation for samples (QR + Code128)
- Result entry with reference ranges (auto-flag abnormal)
- Notification: WhatsApp/SMS/email — result ready, appointment reminder
- Integration with laboratory analyzers (HL7/LIS interface)
- Inventory tracking: reagents, consumables, low-stock alerts

### React Dashboard (Web)
- Dashboard: today's samples count, pending results, revenue card
- Sample tracking board: received → processing → verified → reported
- Test catalog management: add tests, set reference ranges, define panels
- Result entry form: batch entry, manual + analyzer import
- Patient search & result history
- Financial reports: daily revenue, pending payments, insurance billing
- Inventory management: reagents, thresholds, expiry tracking
- User management: technicians, roles, permissions
- Settings: lab info, test menu, price list, printer config

### Flutter App (Mobile)
- Technician app: scan sample barcode, enter results, view pending

- Patient app: book appointment, view test results (PDF), receive notifications

- Push notifications: result ready, appointment confirmation, health tips
- Offline: cached test catalog, results queue for offline entry sync later
- QR code scanner for sample tracking

## 5. Data Model (MVP)

| Entity | Fields | Relationships |
|---|---|---|
| Lab | id, name, address, phone, license_no, logo | hasMany Patient, Test, Sample |
| Patient | id, lab_id, name, phone, dob, gender, national_id | belongsTo Lab, hasMany Sample |
| Test | id, lab_id, name, category, price, turnaround_hours, reference_ranges | belongsTo Lab |
| Sample | id, lab_id, patient_id, barcode, type, collected_at, status | belongsTo Lab/Patient |
| TestResult | id, sample_id, test_id, value, unit, reference_range, flag (normal/abnormal/high/low), verified_by, verified_at | belongsTo Sample/Test |
| Report | id, sample_id, generated_at, pdf_url, shared_with_patient | belongsTo Sample |
| Invoice | id, patient_id, total, paid, method, insurance_claim | belongsTo Patient |
| InventoryItem | id, lab_id, name, sku, quantity, threshold, expiry_date, unit_price | belongsTo Lab |

## 6. API Endpoints (MVP)

| Method | Endpoint | Description |
|---|---|---|
| POST | /api/auth/login | Login (multi-role) |
| GET | /api/samples | List samples (filterable: date, status, patient) |
| POST | /api/samples | Register new sample with barcode |
| GET | /api/samples/{id} | Sample detail with results |
| POST | /api/samples/{id}/results | Enter results for sample tests |
| GET | /api/tests | Test catalog |
| GET | /api/patients/search?q= | Search patients |
| GET | /api/patients/{id}/reports | Patient report history |
| GET | /api/dashboard/revenue | Revenue data |

## 7. User Interface (Screen List)

### Dashboard screens (React)
- Login page
- Dashboard: today's stats (samples in, pending, completed)
- Sample tracking board (Kanban style)
- Patient search → Patient detail → Sample history → Report
- Test catalog management
- Result entry (batch mode)
- Inventory management
- Financial reports
- Settings: lab profile, users, prices, printers

### Mobile screens (Flutter)
- Login
- Technician Home: Scan barcode → Sample detail → Enter results
- Pending samples list
- Patient App: My Results, Book Appointment, Notifications
- Result viewer (PDF inline)

### Screen flow (text)
```
Login → Dashboard (stats + pending samples)
           ├── Samples Board → Register Sample (scan patient) → Assign Tests
           ├── Result Entry → Select Sample → Enter Values → Flag Abnormal → Verify
           ├── Patients → Search → Patient Detail → Results History
           ├── Inventory → Reagents → Low Stock Alerts → Orders
           └── Reports → Revenue / Test Volume / Turnaround Time
```

## 8. Business Model
- **Starter**: $49/month — up to 500 samples/month, 2 technicians
- **Pro**: $99/month — up to 2000 samples/month, 10 technicians, analyzer integration, WhatsApp
- **Enterprise**: Custom — unlimited samples, dedicated support, white-label patient app
- **Free trial**: 14-day free trial

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2)**: Laravel API — Lab, Patient, Test, Sample CRUD, barcode generation
- **Phase 2 (Weeks 3-4)**: React Dashboard — Sample board, result entry, test catalog
- **Phase 3 (Weeks 5-6)**: Flutter App — Technician scanner, Patient result viewer, notifications
- **Phase 4 (Weeks 7-8)**: Analyzer integration, Reports, WhatsApp notifications, Testing, Deploy

## 10. Risk & Mitigation
- **Technical**: HL7/LIS integration complexity — strategy: build adapter layer with common formats, support manual entry fallback.
- **Medical**: Data accuracy critical — strategy: dual verification for abnormal results, audit log for all changes.
- **Market**: Labs resist cloud for data sensitivity — strategy: offline mode, local DB option, Saudi PDPL compliance.
- **Competitive**: Existing LIS vendors — strategy: price disruption (cloud vs on-prem 10x cheaper), mobile-first.
