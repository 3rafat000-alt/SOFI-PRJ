# PRD: BookingPro (SAAS-034)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** نظام حجوزات عام ومرن يتيح لأي مزود خدمة حجز مواعيد مع تقويم متكامل ودفع إلكتروني وإشعارات ذكية
- **Problem:** المدربون والمستشارون وأصحاب الخدمات الفردية لا يملكون أداة بسيطة لحجز المواعيد وإدارة التقويم وقبول الدفع إلكترونياً
- **Proposed solution:** Laravel API + React Dashboard + Flutter App تقدم منصة حجز متعددة الاستخدامات مع تقويم آني وبوابة دفع

## 2. Market & Opportunity
- **Target market size:** سوق تطبيقات المواعيد والحجوزات ~$2.5B. آلاف المدربين والمستشارين في العالم العربي يحتاجون أداة كهذه
- **Customer segment:** B2B — مدربون ومستشارون أفراد، عيادات صغيرة، صالونات تجميل، مكاتب استشارات
- **Competitor landscape:**
  1. Calendly (شائع لكن لا يدعم الدفع في النسخة الأساسية)
  2. Acuity Scheduling (قوي لكن سعره مرتفع للمبتدئين)
  3. SimplyBook.me (مرن لكن واجهته مزدحمة)
  4. Setmore (سهل لكن دعم عربي محدود)
  5. Bookly (متخصص ووردبريس لكن ليس كمنصة مستقلة)
- **Differentiation:** يدعم الدفع النقدي والإلكتروني معاً، تركيز على السوق العربي مع أسعار منافسة، تقويم متكامل مع Google/Apple Calendar، تطبيق جوال لكل من مقدم الخدمة والعميل

## 3. User Personas

### Primary: مدرب محترف (منى)
- **Role:** مدربة تطوير ذاتي تقدم جلسات فردية وجماعية
- **Goals:** حجز مواعيد بسهولة، استقبال الدفع مسبقاً، إدارة جدولها دون تعقيد
- **Pain points:** تنسيق المواعيد عبر واتساب مرهق، العملاء يتأخرون في الدفع، إلغاء المواعيد في اللحظة الأخيرة

### Secondary: عميل (محمد)
- **Role:** عميل يبحث عن حجز جلسة استشارية
- **Goals:** حجز موعد سريع، اختيار الوقت المناسب، دفع آمن
- **Pain points:** عدم معرفة المواعيد المتاحة فورياً، حاجة إلى إعادة إدخال المعلومات في كل مرة

### Admin: مدير المنصة
- **Dashboard operator:** يدير مقدمي الخدمات، يراقب الحجوزات، يضبط خطط الأسعار والعمولات

## 4. Features by Platform

### Laravel API (Backend)
- Service provider onboarding with verification
- Service definition (name, duration, price, capacity, location — physical/online)
- Booking engine with time-slot management
- Availability calendar with custom schedules, breaks, blackout dates
- Payment integration (Stripe, PayPal, Tap, local gateways)
- Cancellation and rescheduling with policy enforcement
- Reminder notifications (SMS, email, push)
- Review and rating system
- Payout engine — automated payout to providers minus commission

### React Dashboard (Web)
- Provider dashboard — manage services, availability, bookings
- Calendar view (day/week/month) with booking details
- Booking management (confirm, reschedule, cancel)
- Payout history and earnings reports
- Client management (list, history, notes)
- Marketing tools (discount codes, loyalty programs)
- Analytics (revenue, booking trends, cancellation rate)

### Flutter App (Mobile)
- **Client side:** Browse providers, book appointments, pay, manage bookings
- **Provider side:** Calendar, manage bookings, start/end sessions, payouts
- Real-time notifications for new bookings, cancellations, reminders
- In-app chat for provider-client communication
- Barcode/QR code booking confirmation

## 5. Data Model (MVP)
- **User:** id, name, email, phone, role (client/provider/admin), avatar
- **Provider:** id, user_id, business_name, description, category, location, settings (JSON)
- **Service:** id, provider_id, name, description, duration_minutes, price, online_available, capacity
- **Booking:** id, service_id, client_id, provider_id, start_time, end_time, status (pending/confirmed/cancelled/completed), payment_status, total_price
- **AvailabilitySlot:** id, provider_id, day_of_week, start_time, end_time, is_available
- **BlockedDate:** id, provider_id, date, reason
- **Payment:** id, booking_id, amount, method, transaction_id, status
- **Review:** id, booking_id, rating, comment, created_at
- **Payout:** id, provider_id, amount, period_start, period_end, status, paid_at

## 6. API Endpoints (MVP)
- `GET /api/providers` — list providers (category, location filter)
- `GET /api/providers/{id}` — provider profile + services
- `GET /api/providers/{id}/availability` — available slots (date range)
- `POST /api/bookings` — create booking
- `GET /api/bookings/mine` — my bookings (client or provider)
- `PATCH /api/bookings/{id}/status` — confirm/cancel/complete
- `POST /api/bookings/{id}/payment` — process payment
- `POST /api/auth/login` — login
- `POST /api/auth/register` — register (client + provider)
- `GET /api/providers/{id}/reviews` — list reviews
- `POST /api/reviews` — submit review
- `GET /api/providers/{id}/earnings` — earnings dashboard

## 7. User Interface (Screen List)
- **Dashboard (Provider):**
  - Login/Register
  - Dashboard (upcoming bookings, revenue, stats)
  - Calendar view (day/week/month)
  - Services management (add/edit service)
  - Availability settings
  - Bookings list
  - Payouts & earnings
  - Client list
- **Mobile (Client):**
  - Browse/search providers
  - Provider profile + services
  - Booking flow (select time → confirm → pay)
  - My bookings (upcoming + past)
  - Notifications
- **Mobile (Provider):**
  - Calendar + bookings
  - Accept/reject bookings
  - Start/end session
  - Earnings snapshot

## 8. Business Model
- **Pricing tiers:**
  - Free: 1 service, 10 bookings/mo (provider)
  - Pro ($19/mo): unlimited services, 100 bookings, calendar sync
  - Business ($49/mo): multiple staff, custom brand, priority support
- **Platform commission:** Take rate per booking (5% on Pro, 3% on Business)
- **Free trial:** 14-day free Pro trial
- **Target MRR per client:** $19-$49/month + commission

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2):** API — Auth, Provider/Service/Booking CRUD, Availability engine
- **Phase 2 (Weeks 3-4):** React Dashboard — Provider dashboard, Calendar, Bookings
- **Phase 3 (Weeks 5-6):** Flutter App — Client booking flow, Provider mobile view, Notifications
- **Phase 4 (Weeks 7-8):** Payment integration, Reviews, Payouts, Testing

## 10. Risk & Mitigation
- **Technical risk:** Calendar sync + timezone handling
  - *Mitigation:* Store all times as UTC, convert on display. Use Spatie/Google Calendar package
- **Market risk:** Calendly dominates scheduling niche
  - *Mitigation:* Focus on providers needing integrated payment and local gateways
- **Financial risk:** Payment gateway fees + chargebacks
  - *Mitigation:* Use Stripe Radar for fraud detection, hold payouts until session completed
