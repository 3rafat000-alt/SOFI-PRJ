# PRD: PetCare Vet (SAAS-009)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner**: نظام إدارة العيادات البيطرية — سجلات الحيوانات، مواعيد، تطعيمات، وصفات علاجية، وتذكير الملاك بمواعيد التطعيم.
- **Problem statement**: العيادات البيطرية تفتقر لأنظمة إدارة متخصصة — معظمها يستخدم سجلات ورقية أو أنظمة بشرية غير مناسبة لإدارة ملفات الحيوانات.
- **Proposed solution**: Laravel API + React Dashboard + Flutter App — سجل طبي شامل لكل حيوان، جدولة مواعيد، تذكير تلقائي للملاك، متابعة التطعيمات والعلاج.

## 2. Market & Opportunity
- **Target market size**: سوق إدارة العيادات البيطرية ~$2B (2025)، الشرق الأوسط ~$150M نمو 18% CAGR مع ارتفاع ملكية الحيوانات الأليفة.
- **Customer segment**: B2B — عيادات بيطرية خاصة، مستشفيات بيطرية صغيرة.
- **Competitor landscape**:
  1. **Vetstoria**: حجوزات فقط، لا يدير السجلات الطبية.
  2. **Veterinary Practice Manager (VPM)**: نظام قديم، سعري، إنجليزي فقط.
  3. **ezyVet**: مكلف ($200+)، معقد، موجه للعيادات الكبيرة.
  4. **Vetport**: نيوزيلندي، لا دعم عربي أو محلي.
  5. **VetCheck**: أسترالي، بدون تطبيق جوال.
- **Differentiation**: عربي بالكامل، سعر منخفض، تذكير ملاك عبر واتساب، سجل تطعيمات تفاعلي، مناسب للعيادات الصغيرة والمتوسطة.

## 3. User Personas

### Primary: د. فارس — طبيب بيطري (عيادة صغيرة)
- **الدور**: يدير عيادة بيطرية في جدة، يستقبل 15-20 حيواناً يومياً.
- **الأهداف**: تسجيل سريع للفحص، متابعة التطعيمات، إصدار وصفات.
- **نقاط الألم**: ينسى مواعيد التطعيم، صعوبة إيجاد ملفات قديمة.

### Secondary: سارة — مالكة كلب (Labrador)
- **الدور**: مالكة كلب عمره سنتان، تحتاج متابعة تطعيمات وفحوصات.
- **الأهداف**: تذكير بمواعيد التطعيم، سهولة حجز الكشف، إمكانية الوصول لسجل صحي للكلب.
- **نقاط الألم**: تنسى تطعيمات كلبها، لا تعرف متى يحين موعد الزيارة.

### Admin: ماجد — مدير مستشفى بيطري
- **الدور**: يدير عيادة ب 3 أطباء بيطريين وموظفين.
- **الأهداف**: تقارير إيرادات، إدارة المخزون الدوائي، متابعة أداء الأطباء.
- **نقاط الألم**: شراء أدوات بيطرية بدون نظام، لا تتبع فعالية العلاج.

## 4. Features by Platform

### Laravel API (Backend)
- Core domain models: Clinic, Veterinarian, Pet, PetOwner, Appointment, MedicalRecord, Vaccination, Prescription, LabResult, InventoryItem
- RESTful endpoints: full CRUD
- Auth: Sanctum multi-role (admin/vet/receptionist/owner)
- Pet profile: species, breed, birth_date, weight chart, allergies, medical history timeline
- Vaccination schedule: core vaccines by species, auto-schedule reminders
- Appointment: booking, check-in, consultation, follow-up tracking
- Notifications: WhatsApp vaccination reminder, appointment reminder, birthday

### React Dashboard (Web)
- Admin panel: clinic settings, staff management, service pricing
- Appointment calendar: day view, vet assignment, status tracking
- Pet directory: search by owner name/pet name/species, detailed profile
- Medical record timeline: each visit as card (diagnosis, Rx, lab)
- Vaccination dashboard: overdue, upcoming, completed
- Lab & imaging: order tests, upload results
- Inventory: medications, vaccines, supplies stock control
- Reports: revenue by service, patient volume, vaccine compliance rate

### Flutter App (Mobile)
- Vet app: today's list, add SOAP note, prescribe, view pet history
- Pet Owner app: my pets, upcoming vaccines, book appointment, chat with vet
- Push notifications: vaccine due, appointment reminder, lab results ready
- Offline: patient list cached for mobile vets in the field

## 5. Data Model (MVP)

| Entity | Fields | Relationships |
|---|---|---|
| Clinic | id, name, address, phone, license | hasMany Vet, Pet, Appointment |
| Pet | id, clinic_id, owner_id, name, species, breed, dob, weight, allergies, microchip | belongsTo Clinic/Owner |
| PetOwner | id, clinic_id, name, phone, email, address | belongsTo Clinic, hasMany Pet |
| Veterinarian | id, clinic_id, name, specialization, license_no, phone | belongsTo Clinic |
| Appointment | id, clinic_id, pet_id, vet_id, date, type, status, reason, diagnosis | belongsTo Clinic/Pet/Vet |
| MedicalRecord | id, pet_id, vet_id, visit_date, diagnosis, treatment, notes | belongsTo Pet |
| Vaccination | id, pet_id, vaccine_name, batch_no, date_administered, next_due | belongsTo Pet |
| Prescription | id, pet_id, vet_id, medication, dosage, duration, instructions | belongsTo Pet |
| LabResult | id, pet_id, vet_id, test_type, result, file_url, notes | belongsTo Pet |
| InventoryItem | id, clinic_id, name, category, stock, expiry_date, supplier | belongsTo Clinic |

## 6. API Endpoints (MVP)

| Method | Endpoint | Description |
|---|---|---|
| GET | /api/pets | List pets (search: name/owner/species) |
| POST | /api/pets | Register new pet |
| GET | /api/pets/{id}/records | Medical record timeline |
| POST | /api/appointments | Book appointment |
| GET | /api/appointments | List appointments (filter: date/vet/status) |
| POST | /api/vaccinations | Record vaccination |
| GET | /api/vaccinations/overdue | Overdue vaccinations list |
| POST | /api/prescriptions | Issue prescription |
| GET | /api/clinic/{id}/reports | Clinic analytics |

## 7. User Interface (Screen List)

### Dashboard screens (React)
- Login → Dashboard (today's appointments, vaccination alerts, revenue)
- Appointments: day/week calendar, color by status
- Pets: searchable grid → Pet profile (info, weight chart, timeline)
- Medical record: timeline view per visit, add new entry
- Vaccination: master list by pet, overdue alert banner
- Lab: order test form, results viewer (PDF/image)
- Inventory: stock list with expiry dates, low stock alerts
- Reports: patient volume chart, revenue breakdown, vaccine rate

### Mobile screens (Flutter)
- Vet: Login → Today → Pet list → Record notes → Prescribe
- Owner: My Pets → Pet Profile → Book Appointment → Vaccination Card → Notifications

### Screen flow (text)
```
Dashboard → Calendar → Appointment Detail → Check-in → Vet Consult
                ├── Pets → Search → Pet Profile
                │            ├── Medical Timeline → Add Record
                │            ├── Vaccinations → Add → Schedule next
                │            └── Prescriptions → New Rx
                ├── Inventory → Stock List → Add Order
                └── Reports → Revenue / Volume / Vaccines
```

## 8. Business Model
- **Starter**: $19/month — up to 200 pets, 1 vet, basic records
- **Pro**: $39/month — up to 1000 pets, 3 vets, vaccinations, reminders, owner app
- **Premium**: $69/month — unlimited pets, 10 vets, inventory, lab integration
- **Free trial**: 14-day Pro trial

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2)**: Laravel API — Clinic, Pet, Owner, Vet, Appointment CRUD
- **Phase 2 (Weeks 3-4)**: React Dashboard — Calendar, Pet profile, Medical record timeline
- **Phase 3 (Weeks 5-6)**: Flutter App — Vet records app, Owner app, Vaccination tracking
- **Phase 4 (Weeks 7-8)**: Lab results, Inventory, WhatsApp reminders, Testing

## 10. Risk & Mitigation
- **Technical**: Standardized pet medical terminology — strategy: create controlled vocab for species-specific conditions.
- **Market**: Small vet clinics price sensitive — strategy: low entry price ($19) with upgrade path.
- **Regulatory**: Veterinary record-keeping laws — strategy: align with ministry of agriculture standards.
