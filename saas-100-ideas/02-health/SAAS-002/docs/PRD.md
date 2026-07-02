# PRD: ClinicFlow (SAAS-002)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner**: نظام إدارة العيادات الطبية السحابي — حجوزات مواعيد، سجلات مرضى إلكترونية (EMR)، تذكير آلي عبر واتساب/رسائل، وتقارير مالية للعيادات الخاصة.
- **Problem statement**: العيادات الخاصة في المنطقة العربية تعتمد على دفاتر ورقية أو أنظمة قديمة بدون دعم عربي، مما يسبب فويت مواعيد، فقدان سجلات، وصعوبة في متابعة الإيرادات.
- **Proposed solution**: Laravel API + React Dashboard + Flutter App — EMR مبسط، جدولة ذكية، إشعارات مرضى، فواتير إلكترونية.

## 2. Market & Opportunity
- **Target market size**: سوق إدارة العيادات العالمي ~$12B (2025)، الشرق الأوسط ~$800M نمو 15% سنوياً.
- **Customer segment**: B2B — عيادات خاصة (1-10 أطباء)، مجمعات طبية صغيرة.
- **Competitor landscape**:
  1. **Vezeeta**: حجوزات فقط بدون EMR، تركيز على المستخدم النهائي.
  2. **Pharmate**: نظام صيدليات وليس عيادات.
  3. **ClinicSource**: أمريكي، بدون دعم عربي أو تكامل محلي.
  4. **Practice Fusion**: حل قديم، واجهة غير عربية، سحابة محدودة.
  5. **صحة**: حكومي، غير موجه للقطاع الخاص.
- **Differentiation**: عربي بالكامل، تذكير ذكي عبر واتساب، تكامل مع أنظمة التأمين المحلية، تصميم بسيط يناسب العيادات الصغيرة.

## 3. User Personas

### Primary: د. ليلى — طبيبة أسنان (عيادة خاصة)
- **الدور**: تملك عيادة ب 3 غرف وطبيبين مساعدين.
- **الأهداف**: إدارة مواعيد المرضى، سجل علاج سريع، إصدار وصفات إلكترونية.
- **نقاط الألم**: المرضى يتخلفون عن المواعيد، تبحث عن بديل ورق.

### Secondary: نور — مساعدة طبية (Receptionist)
- **الدور**: استقبال المرضى، حجز المواعيد، متابعة الملفات.
- **الأهداف**: حجز سريع، عرض جدول اليوم، تأكيد المواعيد تلقائياً.
- **نقاط الألم**: ضغط المكالمات الهاتفية، صعوبة تنظيم الملفات.

### Admin: خالد — مدير العيادة
- **الدور**: يدير الحسابات، المشتريات، التقارير.
- **الأهداف**: تقارير إيرادات يومية، متابعة مخزون المستلزمات.
- **نقاط الألم**: لا توجد رؤية موحدة للأداء المالي.

## 4. Features by Platform

### Laravel API (Backend)
- Core domain models: Patient, Doctor, Appointment, MedicalRecord, Prescription, Invoice, InventoryItem
- RESTful endpoints: CRUD for all models
- Auth: Sanctum multi-role (admin/doctor/receptionist)
- Notifications: WhatsApp (cloud API), SMS (Twilio), email — appointment reminders
- Medical record templates: configurable per specialty
- Billing: invoice generation, payment tracking (cash/card/insurance)

### React Dashboard (Web)
- Admin panel: clinic settings, user management, subscription
- Appointment calendar: weekly view, drag-to-reschedule, color-coded by status
- Patient search: name, phone, file number
- Financial dashboard: daily revenue, pending payments, insurance claims
- Inventory: stock tracking, low-stock alerts
- Reports: patient visits, doctor workload, revenue trends

### Flutter App (Mobile)
- Doctor app: today's schedule, patient history, quick SOAP note entry, e-prescription
- Patient app: book appointment, receive reminders, view history (optional white-label)
- Push notifications: appointment confirmed, reminder (1hr before), follow-up due
- Offline: cached schedule for clinics with poor connectivity

## 5. Data Model (MVP)

| Entity | Fields | Relationships |
|---|---|---|
| Clinic | id, name, address, phone, logo, timezone | hasMany Doctor, Patient, Appointment |
| User (Staff) | id, clinic_id, name, email, role, specialization | belongsTo Clinic |
| Patient | id, clinic_id, name, phone, dob, gender, blood_type, file_number | belongsTo Clinic, hasMany Appointment |
| Appointment | id, clinic_id, patient_id, doctor_id, start_time, end_time, status, type, notes | belongsTo Clinic/Patient/Doctor |
| MedicalRecord | id, patient_id, doctor_id, visit_date, diagnosis, prescription, notes | belongsTo Patient, belongsTo Doctor |
| Prescription | id, patient_id, doctor_id, medication, dosage, duration | belongsTo Patient |
| Invoice | id, appointment_id, amount, paid, method, insurance_claim | belongsTo Appointment |
| InventoryItem | id, clinic_id, name, quantity, threshold, unit_price | belongsTo Clinic |

## 6. API Endpoints (MVP)

| Method | Endpoint | Description |
|---|---|---|
| POST | /api/auth/login | Login (role-based) |
| GET | /api/appointments | List appointments (filterable: date, doctor, status) |
| POST | /api/appointments | Create appointment |
| PATCH | /api/appointments/{id}/status | Update status (confirmed/cancelled/completed) |
| GET | /api/patients/{id}/records | Medical history for patient |
| POST | /api/patients/{id}/records | Add medical record entry |
| GET | /api/dashboard/revenue | Revenue data (query: from, to) |
| GET | /api/patients/search?q= | Search patients |

## 7. User Interface (Screen List)

### Dashboard screens (React)
- Login (role-aware redirect)
- Dashboard: today's appointments, revenue card, pending tasks
- Calendar view (weekly/monthly)
- Patient search & detail page
- Appointment creation wizard
- Medical record viewer (timeline)
- Financial reports page
- Inventory management
- Settings: clinic info, staff, working hours

### Mobile screens (Flutter)
- Doctor Login → Today's Schedule
- Patient list → Patient detail (history, add record)
- Appointment action: confirm, start, complete, cancel
- Quick SOAP note form
- Prescription form (medication autocomplete)
- Patient App: My Appointments, Book Appointment, Notifications

### Screen flow (text)
```
Login → Dashboard (revenue + today's appointments)
           ├── Calendar (weekly view) → Click slot → New Appointment
           ├── Patients → Search → Patient Detail → Add Record / Prescription
           ├── Reports → Revenue / Visits / Insurance
           └── Settings → Clinic / Staff / Hours / Inventory
```

## 8. Business Model
- **Starter**: $29/month — up to 2 doctors, 500 patients, basic reports
- **Pro**: $59/month — up to 5 doctors, unlimited patients, WhatsApp reminders, inventory
- **Enterprise**: Custom — unlimited doctors, dedicated support, white-label
- **Free trial**: 14-day free trial (all features)

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2)**: Laravel API — Auth, Clinic, Patient, Appointment CRUD, MedicalRecord
- **Phase 2 (Weeks 3-4)**: React Dashboard — Calendar, Patient search, Appointment flow
- **Phase 3 (Weeks 5-6)**: Flutter Mobile — Doctor app (schedule, records), Patient app
- **Phase 4 (Weeks 7-8)**: WhatsApp integration, Billing, Reports, Testing, Deploy

## 10. Risk & Mitigation
- **Technical**: Medical data privacy — strategy: encrypt PHI at rest, HIPAA-inspired access controls.
- **Market**: Resistance to cloud in healthcare — strategy: offline-first mode, local data residency option.
- **Regulatory**: Health data compliance — strategy: align with NCA (Saudi) and DHA (UAE) standards.
