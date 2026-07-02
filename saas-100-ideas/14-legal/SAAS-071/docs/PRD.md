# PRD: LegalConsult (SAAS-071)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** منصة استشارات قانونية أونلاين تربط الأفراد والشركات بالمحامين المعتمدين، وتدير المواعيد والوثائق والتوثيق إلكترونياً
- **Problem:** صعوبة الوصول لمحامين متخصصين بسرعة، ارتفاع تكاليف الاستشارات التقليدية، تعقيد إدارة الوثائق القانونية، غياب منصة موحدة للتوثيق الإلكتروني
- **Solution:** Laravel API + React Dashboard (for law firms) + Flutter App (for clients)

## 2. Market & Opportunity
- **Target market:** 50M+ AR in legal services across MENA; 500K+ law firms; 10M+ individuals needing legal consultation yearly
- **Customer segment:** B2B (law firms) + B2C (individuals, companies)
- **Competitors:** Sherpa (Morocco), Wathiq (KSA), Qanoon.ae (UAE), LegalAdviceME (regional), محامي نت
- **Differentiation:** Integrated document management + e-notarization + real-time appointment booking + AI-powered lawyer matching

## 3. User Personas

### المحامي — سامر (Primary)
- **Role:** محامٍ متخصص في القانون التجاري
- **Goals:** إدارة مواعيد العملاء، مشاركة الوثائق بسرعة، تحصيل الرسوم إلكترونياً
- **Pain points:** إلغاء مواعيد اللحظة الأخيرة، تأخر الدفع، إدارة الملفات الورقية

### العميل — ليلى (Secondary)
- **Role:** صاحبة شركة ناشئة تحتاج استشارات قانونية دورية
- **Goals:** حجز موعد استشارة بسرعة، مشاركة عقود للتدقيق، دفع آمن
- **Pain points:** أسعار المحامين غير واضحة مسبقاً، صعوبة مقارنة الخيارات

### Admin — Dashboard Operator
- **Role:** مدير المنصة يراقب المحامين المعتمدين، العمولات، جودة الخدمة

## 4. Features by Platform

### Laravel API (Backend)
- Lawyer profiles, ratings, availability management
- Appointment booking engine (calendar sync)
- Document upload, versioning, sharing (encrypted storage)
- E-signature / notarization workflow
- Payment gateway integration (Stripe/Tabby/stc pay)
- Push/email/SMS notifications
- Role-based auth: Admin, Lawyer, Client

### React Dashboard (Web)
- Lawyer onboarding & verification workflow
- Appointment calendar with schedule management
- Document repository with add/edit/delete
- Client management (list, history, notes)
- Financial reports (earnings, payouts, platform fees)
- Analytics dashboard (consultations, ratings, revenue)

### Flutter App (Mobile)
- Browse & search lawyers by specialty, rating, price
- Book & pay for consultations in-app
- Live chat with lawyers
- Document upload & share
- Push notifications for appointment reminders
- Rate & review lawyers post-consultation

## 5. Data Model (MVP)
- **User:** id, name, email, phone, role (admin/lawyer/client), specialty, verified_at, rating
- **LawyerProfile:** user_id, bar_number, specializations, years_experience, hourly_rate, bio, availability_slots (JSON)
- **Appointment:** id, lawyer_id, client_id, start_time, end_time, status, payment_id, notes
- **Document:** id, user_id, appointment_id, file_path, file_type, encrypted_hash, shared_with
- **Payment:** id, appointment_id, amount, fee_amount, status, payment_method, transaction_id
- **Review:** id, appointment_id, rating, comment, created_at

## 6. API Endpoints (MVP)
- `POST /api/register` / `POST /api/login` — Auth
- `GET /api/lawyers` — List lawyers (filter: specialty, rating, price)
- `GET /api/lawyers/{id}` — Lawyer profile + slots
- `POST /api/appointments` — Create booking
- `GET /api/appointments` — My appointments (client + lawyer)
- `PATCH /api/appointments/{id}/status` — Confirm/cancel/reschedule
- `POST /api/documents` — Upload document
- `GET /api/documents` — List my documents
- `POST /api/payments` — Initiate payment
- `POST /api/payments/{id}/confirm` — Confirm payment
- `POST /api/reviews` — Submit review
- `GET /api/admin/lawyers/pending` — Admin: pending verifications

## 7. User Interface (Screen List)
- **Dashboard screens:** Lawyer list, Appointment calendar, Document manager, Reports, Settings
- **Mobile screens:** Home (search lawyers), Lawyer profile, Book appointment, My appointments, Chat, Documents, Profile
- **Flow:** Home → Search → Lawyer Profile → Book → Pay → Confirm → Appointment Detail → Chat → Review

## 8. Business Model
- **Pricing:** Free for clients; Lawyers pay 15% commission per consultation; Premium law firm listing $49/month
- **Free trial:** 30-day free for law firms
- **Target MRR per firm:** $50–$300 (commission + subscription)
- **Additional:** Document storage (paid tier $9.99/month for 10GB+)

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2):** Laravel API — User auth, Lawyer profiles, Appointment CRUD, Document upload
- **Phase 2 (Weeks 3-4):** React Dashboard — Lawyer onboarding, Appointment calendar, Financial reports
- **Phase 3 (Weeks 5-6):** Flutter App — Search/browse, Booking flow, Chat, Push notifications
- **Phase 4 (Weeks 7-8):** Payment integration, E-signature feature, QA, Performance tuning, Deploy

## 10. Risk & Mitigation
- **Regulatory risk:** Legal practice varies by country; license verification needed → Partner with bar associations regionally
- **Trust risk:** Clients hesitant to share documents online → End-to-end encryption, SOC2 compliance roadmap
- **Quality risk:** Uneven lawyer quality → Rating system + manual verification + regular audits
- **Payment risk:** Chargebacks, refund disputes → Escrow-style payment release post-consultation
