# PRD: PharmacyRx (SAAS-079)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** صيدلية أونلاين متكاملة: إدارة الوصفات الطبية، توصيل الأدوية للمنازل، استشارات صيدلي مباشرة، إدارة مخزون الصيدلية
- **Problem:** المرضى يواجهون صعوبة في الوصول للأدوية الموصوفة، زحام في الصيدليات، نقص الأدوية، عدم وجود استشارة صيدلي موثوقة عن بعد
- **Solution:** Laravel API + React Dashboard (pharmacy admin) + Flutter App (patients + pharmacists)

## 2. Market & Opportunity
- **Target market:** $10B MENA pharmacy market; 80K+ pharmacies; Online pharmacy penetration <5% (vs 25% in US/UK)
- **Customer segment:** B2B (pharmacies, pharmacy chains) + B2C (patients, elderly, chronic condition patients)
- **Competitors:** Nashy (KSA), Tameeni (KSA), iPharmacy (KSA), Pharmapedia (Egypt), 100mg (UAE)
- **Differentiation:** Real-time pharmacy inventory federation (find medicine across nearby pharmacies), prescription OCR (scan + verify), pharmacist video consultation, medication adherence tracking, insurance integration

## 3. User Personas

### المريض — عبدالله (Primary)
- **Role:** مريض مزمن (سكري) يحتاج أدوية شهرية بوصفة طبية
- **Goals:** طلب الدواء بسهولة، توصيل للمنزل، تذكير بموعد إعادة الوصفة
- **Pain points:** زحمة الصيدليات، نسيان إعادة الوصفة، نقص الدواء في الصيدلية القريبة

### الصيدلي — نوره (Secondary)
- **Role:** صيدلانية في صيدلية مجتمعية
- **Goals:** إدارة الوصفات، تقديم استشارات دوائية، إدارة المخزون
- **Pain points:** وصفات يدوية غير واضحة، مراجعة وصفات مكررة، ضغط العمل

### الطبيب — الدكتور كريم (Tertiary)
- **Role:** طبيب يكتب وصفات إلكترونية لمرضاه
- **Goals:** إرسال الوصفة للصيدلية إلكترونياً، تتبع التزام المريض بالدواء
- **Pain points:** وصفات ورقية يخسرها المريض، لا يعرف إذا صرف المريض الدواء أم لا

### Admin — Dashboard Operator
- **Role:** مدير المنصة يراقب الصيدليات، الامتثال، التوصيل

## 4. Features by Platform

### Laravel API (Backend)
- Medicine catalog (branded + generic, classification, dosage forms)
- Prescription management (upload, OCR extraction, pharmacist verification)
- Pharmacy inventory federation (search across pharmacies)
- E-prescription (direct from doctor)
- Order management & delivery tracking
- Pharmacist consultation (video/chat)
- Medication adherence tracking & reminders
- Insurance eligibility & claim processing
- Regulatory compliance (SFDA/EMA rules, controlled substance tracking)

### React Dashboard (Web)
- Pharmacy profile & operating hours
- Medicine inventory management (stock, expiry, pricing)
- Prescription inbox (pending verification queue)
- Order management & dispatch
- Pharmacist consultation scheduler
- Patient management & history
- Insurance panel management
- Reports (sales, popular medicines, adherence rates)
- Compliance dashboard (controlled substance logs, audit trail)

### Flutter App (Mobile)
- **Patient App:** Upload prescription photo, Search medicines, Compare prices, Order & track delivery, Chat with pharmacist, Set medication reminders, Refill requests, Insurance card upload, Video consultation booking
- **Pharmacist App:** Prescription review queue, Verify & dispense, Manage inventory, Video consultation calls, Patient chat, Order fulfilment

## 5. Data Model (MVP)
- **Pharmacy:** id, name, address, license_no, delivery_radius, operating_hours, insurance_panels (JSON)
- **Medicine:** id, name_ar, name_en, category, classification (rx/otc/controlled), dosage_form, strength, manufacturer, requires_prescription
- **PharmacyMedicine:** id, pharmacy_id, medicine_id, stock_quantity, price, shelf_location, expiry_date
- **Prescription:** id, patient_id, doctor_name, clinic_name, issue_date, expiry_date, diagnosis, medicines (JSON with dosage), image_url, ocr_text, status (pending/verified/dispensed/rejected)
- **Order:** id, patient_id, pharmacy_id, items (JSON with medicine, quantity, price), prescription_id, total_amount, insurance_coverage, delivery_address, status, tracking_id
- **Consultation:** id, patient_id, pharmacist_id, type (chat/video), started_at, ended_at, notes, rating
- **AdherenceLog:** id, patient_id, medicine_id, scheduled_time, taken_time, missed
- **InsuranceClaim:** id, order_id, insurance_provider, policy_no, claim_amount, approved_amount, status

## 6. API Endpoints (MVP)
- `POST /api/register` / `POST /api/login` — Auth (patient/pharmacist/doctor)
- `POST /api/prescriptions/upload` — Upload & OCR prescription
- `GET /api/prescriptions/{id}` — Prescription details
- `GET /api/medicines/search?q=` — Search medicines (federated across pharmacies)
- `GET /api/pharmacies/{id}/medicines` — Pharmacy price + stock
- `POST /api/orders` — Place order (link prescription)
- `GET /api/orders` — My orders (patient) / Incoming (pharmacy)
- `PATCH /api/orders/{id}/status` — Update order (verified/dispensed/delivered)
- `POST /api/consultations` — Request consultation
- `GET /api/consultations` — Consultation history
- `POST /api/reminders` — Set medication reminder
- `GET /api/insurances` — List insurance panels
- `POST /api/insurance/verify` — Verify insurance coverage

## 7. User Interface (Screen List)
- **Dashboard screens:** Prescription queue, Orders overview, Inventory status, Consultation scheduler, Reports
- **Mobile (Patient):** Home (search medicines, upload Rx), Prescriptions, Orders, Consultations, Reminders, Profile
- **Mobile (Pharmacist):** Prescription queue, Order fulfilment, Inventory, Consultations, Chat
- **Flow (Patient):** Upload Rx → Search Medicine → Select Pharmacy → Place Order → Track Delivery → Receive → Remind me
- **Flow (Pharmacist):** Login → Review Prescriptions → Verify → Dispense → Dispatch → Consult → Done

## 8. Business Model
- **Pricing:** Pharmacies: Starter ($59/mo, 200 orders), Pro ($129/mo, 1000 orders), Enterprise ($299/mo, unlimited + insurance module)
- **Free trial:** 30-day free for pharmacies
- **Target MRR per pharmacy:** $59–$299
- **Additional:** Delivery fee ($2–$5 per order, shared with pharmacy), Consultation fee ($5–$15 per session, 70% to pharmacist), Insurance processing fee ($1/claim)

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2):** API — Medicine catalog, Pharmacy profiles, Order management, Prescription upload
- **Phase 2 (Weeks 3-4):** React Dashboard — Pharmacy admin panel, Inventory management, Order processing, Prescription queue
- **Phase 3 (Weeks 5-6):** Flutter Apps — Patient app (upload Rx, search, order, track), Pharmacist app (Rxs, fulfilment, chat)
- **Phase 4 (Weeks 7-8):** OCR engine for prescription parsing, Consultation video/chat, Insurance integration, Medication reminders, QA

## 10. Risk & Mitigation
- **Regulatory risk:** Controlled substances, prescription validity vary → Geo-specific compliance rules, expiry checks on prescriptions
- **Medical liability:** Incorrect dispensing → Double verification workflow, pharmacist liability insurance requirement
- **Supply chain:** Medicine out of stock → Federated inventory (show alternative pharmacies), generic substitution suggestions
- **Data privacy:** Health data sensitive → HIPAA/GDPR/PDPL compliance, encrypted health records, audit logs
- **Adoption:** Elderly patients not tech-savvy → SMS ordering, family member proxy accounts, voice ordering
