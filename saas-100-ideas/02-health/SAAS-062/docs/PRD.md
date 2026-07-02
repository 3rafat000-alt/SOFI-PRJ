# PRD: DentistPro (SAAS-062)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner**: نظام إدارة عيادات الأسنان السحابي — حجوزات، ملفات مرضى، صور أشعة، تذكير مواعيد، وفواتير.
- **Problem statement**: عيادات الأسنان تستخدم أنظمة قديمة (غالباً سطح مكتب) لا تدومج التذكير الذكي أو السجلات الرقمية الكاملة، مما يسبب تغيب المرضى وفوضى في المواعيد.
- **Proposed solution**: Laravel API + React Dashboard + Flutter App — جدولة حجوزات، سجل علاجي رقمي (صور أشعة، تقارير)، تذكير ذكي، وفواتير إلكترونية.

## 2. Market & Opportunity
- **Target market size**: سوق إدارة عيادات الأسنان ~$4B عالمياً، الشرق الأوسط ~$250M نمو 14% CAGR.
- **Customer segment**: B2B — عيادات أسنان خاصة (1-5 كراسي)، مجمعات أسنان، مراكز تجميل الأسنان.
- **Competitor landscape**:
  1. **Dentrix**: معيار الصناعة لكن سطح مكتب، $300/شهر، بدون دعم عربي.
  2. **Open Dental**: مفتوح المصدر لكن بدون واجهة عربية أو تطبيق جوال.
  3. **Eaglesoft**: أمريكي، باهظ الثمن، تكامل محدود مع واتساب.
  4. **Tabib (Vezeeta)**: حجوزات فقط، لا يوجد سجل علاجي.
  5. **ClinicSource**: عام بدون تخصص أسنان.
- **Differentiation**: عربي كامل، تكامل مع صور البانوراما والأشعة, تذكير عبر واتساب قبل 24 ساعة، خريطة أسنان رقمية، فواتير تأمين سهلة.

## 3. User Personas

### Primary: د. محمد — طبيب أسنان (عيادة خاصة)
- **الدور**: يدير عيادة بكرسيين وطبيب مساعد.
- **الأهداف**: جدولة المرضى بسهولة، تسجيل العلاج بسرعة، تقليل حالات التغيب.
- **نقاط الألم**: المرضى يتخلفون عن المواعيد، حفظ صور الأشعة فوضوي، النظام الحالي بطيء.

### Secondary: ليان — مساعدة طبية (Reception/Coordinator)
- **الدور**: تحجز المواعيد، تستقبل المرضى، تطبع التقارير.
- **الأهداف**: حجز سريع، تأكيد تلقائي، تنظيم ملفات المرضى.
- **نقاط الألم**: مكالمات هاتفية مستمرة، صعوبة إيجاد ملفات المرضى.

### Admin: سامر — مدير العيادة
- **الدور**: يدير الحسابات والفواتير والتأمين.
- **الأهداف**: تقارير إيرادات دقيقة، متابعة مطالبات التأمين.
- **نقاط الألم**: الفواتير الورقية تضيع، التأمين معقد.

## 4. Features by Platform

### Laravel API (Backend)
- Core domain models: Clinic, Dentist, Patient, Appointment, ToothChart, Treatment, Prescription, Invoice, XrayImage, InsuranceClaim
- RESTful endpoints: CRUD for all models
- Auth: Sanctum multi-role (admin/dentist/receptionist)
- Tooth chart data model: 32 teeth, surfaces, conditions, treatments per tooth
- X-ray image upload: integrated viewer, DICOM basic support
- Appointment scheduling: time slots, duration presets, chair assignment
- Notifications: WhatsApp reminders (24h + 1h before), SMS, email
- Insurance: claim submission, approval tracking, co-pay calculation

### React Dashboard (Web)
- Dashboard: today's appointments, new patients, revenue today
- Appointment calendar: day/week/month view, drag-to-reschedule
- Digital tooth chart: interactive 2D chart, click tooth → add treatment/condition
- Patient file: personal info, medical history, treatment plan, X-rays timeline
- X-ray viewer: zoom, compare, annotate
- Treatment plan builder: sequence procedures with fees
- Billing: invoice, payment, insurance claim
- Inventory: dental supplies, stock alerts
- Reports: procedures per doctor, revenue trends, insurance aging

### Flutter App (Mobile)
- Dentist app: today's schedule, patient list, tooth chart, treatment notes
- Patient app: book appointment, receive reminders, view treatment plan
- Push notifications: reminder 24h before, treatment complete, follow-up due
- Photo capture: intraoral photos linked to patient record
- Offline: cached schedule, treatment notes sync when online

## 5. Data Model (MVP)

| Entity | Fields | Relationships |
|---|---|---|
| Clinic | id, name, address, phone, license_no, logo | hasMany Dentist, Patient, Chair |
| Dentist | id, clinic_id, name, specialization, license_no, working_hours | belongsTo Clinic |
| Patient | id, clinic_id, name, phone, dob, medical_history, allergies | belongsTo Clinic, hasMany Appointment |
| Appointment | id, clinic_id, patient_id, dentist_id, chair_id, start, end, status, type, notes | belongsTo Clinic/Patient/Dentist |
| ToothRecord | id, patient_id, tooth_number, surface, condition, treatment, notes | belongsTo Patient |
| Treatment | id, appointment_id, tooth_number, procedure, fee, notes | belongsTo Appointment |
| XrayImage | id, patient_id, file_url, type (panoramic/periapical), taken_at | belongsTo Patient |
| Invoice | id, appointment_id, total, insurance_portion, patient_portion, paid, status | belongsTo Appointment |
| InsuranceClaim | id, invoice_id, provider, claim_no, status, approved_amount | belongsTo Invoice |

## 6. API Endpoints (MVP)

| Method | Endpoint | Description |
|---|---|---|
| POST | /api/auth/login | Login multi-role |
| GET | /api/appointments | List appointments (filterable) |
| POST | /api/appointments | Create appointment |
| PATCH | /api/appointments/{id}/status | Update status |
| GET | /api/patients/{id}/teeth | Get tooth chart data |
| PUT | /api/patients/{id}/teeth | Update tooth record |
| GET | /api/patients/{id}/xrays | Patient X-ray list |
| POST | /api/patients/{id}/xrays | Upload X-ray |
| GET | /api/dashboard/revenue | Revenue stats |

## 7. User Interface (Screen List)

### Dashboard screens (React)
- Login
- Dashboard: today overview, alerts
- Calendar view (day/week/month)
- Patient search → detail page (profile, teeth, X-rays, history)
- Interactive tooth chart (click tooth → add treatment)
- X-ray gallery viewer
- Treatment plan builder
- Billing & Invoices
- Insurance claims
- Inventory management
- Settings

### Mobile screens (Flutter)
- Dentist: Login → Today's Schedule → Patient list → Tooth chart
- Patient appointment entry → treatment note → save
- Patient App: My Appointments → Book → Reminders → Treatment Plan

### Screen flow (text)
```
Login → Dashboard (appointments + revenue)
           ├── Calendar → Select Slot → New Appointment → Patient Search
           ├── Patient Detail → Medical History → Tooth Chart
           │                                   → X-rays → Upload/View
           │                                   → Treatment Plan → Add Procedure
           ├── Billing → Invoice → Payment → Insurance Claim
           └── Reports → Revenue / Procedures / Insurance Aging
```

## 8. Business Model
- **Starter**: $39/month — up to 2 chairs, 300 patients
- **Pro**: $79/month — up to 5 chairs, unlimited patients, WhatsApp, X-ray storage
- **Enterprise**: Custom — unlimited chairs, white-label patient app, priority support
- **Free trial**: 14-day free trial

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2)**: Laravel API — Clinic, Dentist, Patient, Appointment CRUD, tooth chart model
- **Phase 2 (Weeks 3-4)**: React Dashboard — Calendar, Patient detail, Interactive tooth chart
- **Phase 3 (Weeks 5-6)**: Flutter App — Dentist schedule + tooth chart, Patient app
- **Phase 4 (Weeks 7-8)**: X-ray viewer, Insurance, WhatsApp integration, Testing, Deploy

## 10. Risk & Mitigation
- **Technical**: DICOM/X-ray viewing complexity — strategy: use standard image formats initially, integrate with 3rd-party DICOM viewer later.
- **Market**: Dentists slow to adopt cloud — strategy: offline-first, local backups, free migration from legacy systems.
- **Competitive**: Dentrix loyalty — strategy: data import tool from Dentrix/OpenDental, lower price 5x.
