# PRD: SalonPro (SAAS-008)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner**: نظام إدارة صالونات التجميل والحلاقة — حجوزات مواعيد، إدارة الموظفين والعمولات، برنامج ولاء، وتقارير مالية مبسّطة.
- **Problem statement**: صالونات التجميل والحلاقة تدير الحجوزات والعمولات يدوياً أو بتطبيقات غير متخصصة، مما يسبب فوضى في المواعيد وفقدان الإيرادات.
- **Proposed solution**: Laravel API + React Dashboard + Flutter App — تقويم حجوزات ذكي، تتبع عمولات، برنامج ولاء، تطبيق للعميل لحجز المواعيد.

## 2. Market & Opportunity
- **Target market size**: سوق إدارة الصالونات العالمي ~$6B (2025)، الشرق الأوسط ~$350M نمو 14% CAGR.
- **Customer segment**: B2B — صالونات تجميل نسائية، صالونات حلاقة رجالية، منتجعات سبا.
- **Competitor landscape**:
  1. **Booksy**: تطبيق حجوزات فقط، لا يدير عمولات أو ولاء، إنجليزي.
  2. **Salon Target**: برنامج أمريكي، واجهة قديمة، سعري.
  3. **StyleSeat**: تطبيق حجوزات أمريكي، غير متاح في المنطقة.
  4. **Vagaro**: شامل لكن تركيزه على أمريكا، دعم عربي محدود.
  5. **Glofox**: لياقة بدنية وليس صالونات.
- **Differentiation**: عربي بالكامل، إدارة عمولات ومرتبات، برنامج ولاء، تطبيق عميل، إشعارات واتساب، مناسب للسوق الخليجي (تسعيرات منخفضة).

## 3. User Personas

### Primary: منال — صاحبة صالون تجميل (8 موظفات)
- **الدور**: تملك صالوناً نسائياً في الرياض، توظف 6 كوافيرات و2 أخصائيات بشرة.
- **الأهداف**: إدارة المواعيد، حساب العمولات، متابعة المخزون.
- **نقاط الألم**: الكوافيرات يخطئون في تسجيل وقت العملاء، حساب العمولات أسبوعياً مرهق.

### Secondary: ليان — كوافيرة (Stylist)
- **الدور**: تقدم خدمات قص وتصفيف ومكياج، لها زبائنها الخاصين.
- **الأهداف**: رؤية جدول مواعيدها، إشعارات عند حجز عميلتها المفضلة.
- **نقاط الألم**: تضارب المواعيد، لا ترى إجمالي عمولاتها بوضوح.

### Customer: هند — زبونة دائمة
- **الدور**: تزور الصالون أسبوعياً لعناية الأظافر والشعر.
- **الأهداف**: حجز موعد بسرعة، نقاط ولاء، عروض خاصة.
- **نقاط الألم**: تنتظر على الهاتف للحجز، تنسى مواعيدها.

## 4. Features by Platform

### Laravel API (Backend)
- Core domain models: Salon, Employee, Service, Appointment, Commission, LoyaltyPoint, Customer, Product, Inventory
- RESTful endpoints: full CRUD
- Auth: Sanctum multi-role (owner/stylist/customer)
- Appointment engine: time-slot generation, double-booking prevention, waitlist
- Commission engine: percentage per service, tiered rates, tips tracking
- Loyalty engine: points per visit, reward redemption, birthday bonus
- Inventory: product stock, service consumption tracking
- Notifications: WhatsApp appointment reminders, promotion alerts, loyalty updates

### React Dashboard (Web)
- Admin panel: salon profile, employee management, service catalog
- Appointment calendar: day/week view, color-coded by stylist, drag-to-reschedule
- Employee dashboard: commission report, hours worked, tips, ratings
- Customer directory: profile, visit history, loyalty points, preferred services
- Financial dashboard: daily revenue by service/employee, expenses, profit
- Loyalty program: point rules, rewards catalog, redemption history
- Inventory: stock levels, low-stock alerts, supplier orders

### Flutter App (Mobile)
- Customer app: browse services, view stylist profiles, book appointment, loyalty card, pay online
- Stylist app: today's schedule, clock in/out, list services done, view tips
- Push notifications: booking confirmed, 24hr reminder, stylist changed, promotion
- Offline: cached services and schedule

## 5. Data Model (MVP)

| Entity | Fields | Relationships |
|---|---|---|
| Salon | id, name, address, phone, working_hours, timezone | hasMany Employee, Service, Customer |
| Employee | id, salon_id, name, role, commission_rate, phone, photo | belongsTo Salon |
| Service | id, salon_id, name, duration_minutes, price, category | belongsTo Salon |
| Appointment | id, salon_id, employee_id, customer_id, service_id, start_time, end_time, status, total | belongsTo Salon/Employee/Customer |
| Commission | id, appointment_id, employee_id, service_price, commission_amount, tip | belongsTo Appointment/Employee |
| Customer | id, salon_id, name, phone, email, loyalty_points, total_visits, last_visit | belongsTo Salon |
| LoyaltyPoint | id, customer_id, points, type (earn/redeem), reference | belongsTo Customer |
| Product | id, salon_id, name, stock, price, supplier | belongsTo Salon |

## 6. API Endpoints (MVP)

| Method | Endpoint | Description |
|---|---|---|
| GET | /api/services | List services with employees |
| POST | /api/appointments | Book appointment |
| GET | /api/appointments | List appointments (filterable: date/employee/status) |
| PATCH | /api/appointments/{id}/status | Confirm/complete/cancel |
| GET | /api/employees/{id}/schedule | Employee schedule for date |
| GET | /api/customers/{id}/loyalty | Customer loyalty history |
| GET | /api/salon/{id}/revenue | Revenue report (query: period) |
| POST | /api/employees/{id}/commission | Calculate commissions |

## 7. User Interface (Screen List)

### Dashboard screens (React)
- Login → Dashboard (today's appointments, revenue today, top employees)
- Calendar: weekly/daily view, time slots, each appointment card shows customer + service
- Services: list with prices, duration, category, toggle active/inactive
- Employees: profiles, commission rates, schedule, ratings
- Customers: search → profile → history → loyalty
- Loyalty: points rules editor, rewards gallery
- Reports: revenue by service, employee performance, peak hours

### Mobile screens (Flutter)
- Customer: Sign up → Browse services → Select stylist → Pick time → Confirmation → Loyalty card
- Stylist: Login → Today's schedule → Clock in → Mark service done → See tips

### Screen flow (text)
```
Dashboard → Calendar (weekly)
                ├── Appointment → Assign stylist → Confirm
                ├── Services → Add/Edit service
                ├── Employees → Commission report
                ├── Customers → Profile → History
                └── Reports → Revenue / Performance

Customer App → Home → Services → Stylist → Time → Book → Track → Loyalty
```

## 8. Business Model
- **Starter**: $19/month — 1 salon, up to 5 employees, basic calendar
- **Pro**: $39/month — 1 salon, unlimited employees, loyalty, reports, customer app
- **Premium**: $69/month — 3 salons, inventory, API, WhatsApp integration
- **Free trial**: 14-day Pro trial

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2)**: Laravel API — Salon, Service, Employee, Appointment CRUD
- **Phase 2 (Weeks 3-4)**: React Dashboard — Calendar, Employee mgmt, Commission engine
- **Phase 3 (Weeks 5-6)**: Flutter App — Customer booking, Stylist schedule, Loyalty
- **Phase 4 (Weeks 7-8)**: Reports, Inventory, WhatsApp notifications, Testing

## 10. Risk & Mitigation
- **Technical**: Time-slot complexity — strategy: fixed interval slots (30min) with buffer between.
- **Market**: Stylists resistant to commission tracking — strategy: show real-time earnings as motivator.
- **Operational**: Last-minute cancellations — strategy: automated waitlist, cancellation fee option.
