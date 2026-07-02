# PRD: Eventify (SAAS-013)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary

**منصة شاملة لإدارة الفعاليات والمؤتمرات.** تقدم Eventify حلولاً رقمية لبيع التذاكر، تسجيل الحضور، إدارة الرعاة، وإرسال الدعوات الرقمية. تستهدف منظمي الفعاليات وقاعات المؤتمرات.

- المشكلة: منظمو الفعاليات يواجهون صعوبة في إدارة التذاكر والحضور والتواصل مع الحضور والرعاة عبر قنوات متفرقة.
- الحل: Laravel API + React Dashboard + Flutter App.

## 2. Market & Opportunity

- السوق المستهدف: 20,000+ منظم فعاليات، قاعات مؤتمرات، ومراكز مناسبات في الشرق الأوسط
- الفئة: B2B + B2C (منظمو فعاليات + حضور)
- المنافسون:
  - **Eventbrite** — منصة عالمية (رسوم عالية، دعم عربي محدود)
  - **Ticketmaster** — تذاكر كبيرة (غير مناسبة للفعاليات الصغيرة)
  - **Plany** — حل عربي مبسط يفتقر لميزات الرعاة والتقارير
- التمايز: دعم كامل للعربية وطرق الدفع المحلية (Mada, Vodafone Cash, فوري)، رسوم تنافسية

## 3. User Personas

### شخص أساسي: منظم الفعاليات
- الاسم: سارة
- الدور: منظمة مؤتمرات سنوية وفعاليات شركات
- الأهداف: بيع التذاكر إلكترونياً، إدارة قائمة الضيوف، التواصل مع الرعاة
- نقاط الألم: صعوبة تتبع الحضور، تكاليف عالية لمنصات التذاكر، عدم وجود تحليلات

### شخص أساسي: الحضور (الضيف)
- الاسم: محمد
- الدور: يحضر مؤتمرات تقنية وفعاليات مهنية
- الأهداف: شراء تذاكر بسهولة، استلام تذكرة رقمية، إدارة جدول مشاركاته
- نقاط الألم: إجراءات تسجيل طويلة، عدم وجود تذكرة رقمية، صعوبة إلغاء الحجز

### Admin: مدير المنصة
- إدارة المستخدمين، الإشراف على الفعاليات المبلغ عنها، إدارة العمولات.

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Event, TicketType, Attendee, Sponsor, Invitation, Payment, Discount
- RESTful CRUD for events, tickets, attendees
- Payment gateway integration (Stripe, PayPal, Mada, Fawry)
- QR ticket generation (signed URLs)
- Email notification engine (confirmation, reminder, receipt)
- Check-in validation endpoint with real-time sync
- Discount code engine (percentage, fixed, early-bird, referral)

### React Dashboard (Web)
- Dashboard: ticket sales chart, revenue KPI, attendee growth
- Event builder: multi-step wizard (details, venue, date, tickets, sponsors)
- Ticket management: types (VIP/regular/early-bird), pricing, capacity
- Attendee list: searchable with check-in status, filters
- Sponsor management: tiers (gold/silver/bronze), logos, contracts
- Invitation system: design + send digital invitations, track RSVP
- Check-in app (PWA): scan QR codes at venue door
- Reports: sales by ticket type, revenue, attendance rate, ROI

### Flutter App (Mobile)
- Event discovery: browse upcoming events, search, filter by category
- Ticket purchase: select type, payment, receive QR ticket
- Digital wallet: stored tickets, QR display at venue
- Push notifications: event reminders, schedule changes, special offers
- Schedule builder: create personal agenda from event sessions
- Social sharing: share event on WhatsApp, Twitter, LinkedIn

## 5. Data Model (MVP)

### Event
- id, title, description, category, venue, location (lat/lng), start_date, end_date, cover_image, status (draft/published/cancelled/completed), organizer_id (FK), created_at

### TicketType
- id, event_id (FK), name, price, quantity_total, quantity_sold, benefits (JSON), sale_start, sale_end, created_at

### Attendee
- id, event_id (FK), user_id (FK), ticket_type_id (FK), qr_code, check_in_status, check_in_time, purchase_date, payment_id, created_at

### Sponsor
- id, event_id (FK), name, logo, website, tier, amount, contract_file, created_at

### Invitation
- id, event_id (FK), email, name, code, rsvp_status, rsvp_date, sent_at, created_at

### Payment
- id, attendee_id (FK), amount, currency, gateway, transaction_id, status, created_at

### DiscountCode
- id, code, type (percentage/fixed), value, max_uses, current_uses, event_id (FK), expiry_date, created_at

### User
- id, name, email, password, role, phone, avatar, created_at

## 6. API Endpoints (MVP)

```
POST   /api/auth/login
POST   /api/auth/register
GET    /api/auth/me

GET    /api/events
POST   /api/events
GET    /api/events/{id}
PUT    /api/events/{id}
DELETE /api/events/{id}
GET    /api/events/{id}/tickets
GET    /api/events/{id}/attendees
GET    /api/events/my

GET    /api/ticket-types
POST   /api/ticket-types
PUT    /api/ticket-types/{id}
DELETE /api/ticket-types/{id}

POST   /api/attendees/register
POST   /api/attendees/{id}/check-in
GET    /api/attendees/{id}/ticket

GET    /api/sponsors
POST   /api/sponsors
DELETE /api/sponsors/{id}

GET    /api/invitations
POST   /api/invitations/send
PUT    /api/invitations/{id}/rsvp

POST   /api/payments/create-intent
POST   /api/payments/webhook

POST   /api/discounts/validate
GET    /api/discounts
POST   /api/discounts

GET    /api/reports/sales?event_id=&from=&to=
GET    /api/reports/attendance?event_id=
```

## 7. User Interface (Screen List)

### Dashboard Screens (React)
1. Login / Register
2. Dashboard - sales chart, ticket stats, upcoming events
3. Event List - own events with status badges
4. Event Builder - 5-step wizard (info, tickets, sponsors, design, publish)
5. Ticket Manager - pricing, capacity, sales tracking
6. Attendee List - searchable, filterable, check-in toggle
7. Check-in Scanner - camera-based QR scanner (PWA)
8. Sponsor Manager - add/edit sponsors, logo upload
9. Invitation Designer - template picker, send tracker
10. Reports - sales, attendance, revenue analytics

### Mobile Screens (Flutter)
1. Splash, Onboarding, Login/Register
2. Event Discovery - hero banner, category grid, search bar
3. Event Detail - description, ticket types, map, sponsor logos
4. Ticket Purchase - select type, apply promo, payment, QR
5. My Tickets - digital wallet with QR codes
6. QR Display - full-screen QR for scanner
7. Schedule Builder - session list, add to agenda
8. Profile - personal info, ticket history

### Screen Flow
Browse Events -> Select Event -> Choose Ticket -> Payment -> QR Ticket -> Check-in at venue

## 8. Business Model

- **الباقة المجانية**: فعالية واحدة، حتى 100 تذكرة (عمولة 5%)
- **الباقة الاحترافية**: $49/شهر (فعاليات غير محدودة، حتى 1000 تذكرة)
- **باقة المؤسسات**: $149/شهر (غير محدود، رعاة، تقارير متقدمة)
- فترة تجربة مجانية: 14 يوماً
- MRR المستهدف لكل عميل: $0-$149

## 9. Implementation Plan

- Phase 1 (Weeks 1-2): Laravel API - Auth, Event/Ticket CRUD, roles
- Phase 2 (Weeks 3-4): Attendee check-in, payment integration, QR generation
- Phase 3 (Weeks 5-6): React Dashboard - Event builder, ticket mgmt, check-in PWA
- Phase 4 (Weeks 7-8): Flutter App - Discovery, purchase, wallet, notifications
- Phase 5 (Weeks 9-10): Payment gateway certification, load testing, deployment

## 10. Risk & Mitigation

- **مخاطرة تقنية**: تكامل بوابات الدفع المتعددة والتعامل مع حالات الفشل
  - التخفيف: طبقة تجريد للمدفوعات، إعادة محاولة ذكية، وسجل تدقيق
- **مخاطرة سوقية**: منافسة Eventbrite القوية في السوق العالمي
  - التخفيف: التركيز على السوق المحلي العربي وطرق الدفع المحلية
- **مخاطرة تشغيلية**: إلغاء الفعاليات واسترداد المبالغ
  - التخفيف: سياسة استرداد مرنة، نظام محاسبة شفاف
