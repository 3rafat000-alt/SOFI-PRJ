# PRD: RealtyCRM (SAAS-059)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary

- **One-liner:** نظام متكامل لإدارة مكاتب العقارات — إعلانات، عملاء، عقود، جولات افتراضية — منصة CRM عقارية شاملة بالعربية.
- **Problem:** مكاتب العقارات تدير عملياتها بشكل تقليدي — إعلانات على إكسل، متابعة العملاء يدوياً، عقود ورقية، إعادة إدخال البيانات في منصات متعددة. السماسرة يضيعون وقتاً طويلاً في إدخال بيانات العقارات والاتصالات المتكررة.
- **Proposed solution:** Laravel API (إدارة العقارات والعملاء والعقود) + React Dashboard (لوحة تحكم متقدمة للمكاتب) + Flutter App (تطبيق للسماسرة والعملاء مع جولات افتراضية).

## 2. Market & Opportunity

- **Target market:** مكاتب عقارية، سماسرة عقاريون، شركات تطوير عقاري في العالم العربي. سوق العقارات من أكبر الأسواق في المنطقة، مع آلاف المكاتب العقارية في كل مدينة رئيسية.
- **Customer segment:** B2B — مكاتب عقارية (صغيرة ومتوسطة)، سماسرة مستقلون، شركات تطوير عقاري.
- **Competitor landscape:**
  1. عقار (Aqar) — منصة إعلانات عقارية، ليست CRM.
  2. بيوت (Bayut) / PropertyFinder — إعلانات فقط.
  3. كود العقارية (Code) — حل عربي محدود الإمكانيات.
  4. Salesforce / HubSpot — CRM عام غير متخصص بالعقارات.
  5. Lime Technologies — حل أوروبي مكلف.
- **Differentiation:** CRM متخصص بالعقارات بالعربية الفصحى وإنجليزية الأعمال. إدارة دورة حياة كاملة (استلام عقار → إعلان → جولة افتراضية → عقد → إيجار/بيع). جولات افتراضية 360° مدمجة. تكامل مع منصات الإعلانات (عقار، بيوت). مركز عقود ذكي مع قوالب جاهزة.

## 3. User Personas

### أساسي: مدير المكتب العقاري — عبدالعزيز
- **الدور:** مالك ومدير مكتب عقاري
- **الأهداف:** إدارة فريق السماسرة، متابعة الصفقات، تحليل أداء المكتب، تسريع دورة البيع
- **نقاط الألم:** صعوبة متابعة أداء السماسرة، عدم وجود رؤية واضحة للصفقات قيد التنفيذ، فقدان العملاء

### أساسي: السمسار العقاري — نوره
- **الدور:** وسيطة عقارية
- **الأهداف:** تسويق العقارات، متابعة العملاء، إتمام الصفقات، بناء علاقات
- **نقاط الألم:** إدخال بيانات العقارات المتكرر في منصات مختلفة، متابعة العملاء يدوياً، إعداد العقود

### ثانوي: الباحث عن عقار — فيصل
- **الدور:** عميل يبحث عن شقة للإيجار
- **الأهداف:** مشاهدة عقارات مناسبة، حجز جولات، تقديم عروض
- **نقاط الألم:** عدم توفر معلومات كافية، تكرار الزيارات لعقارات غير مناسبة

### إداري: مشغل النظام
- **الدور:** مسؤول المنصة
- **الأهداف:** إدارة الحسابات، مراقبة الاستخدام

## 4. Features by Platform

### Laravel API (Backend)

- Property management (rent, sale, commercial, residential)
- Multi-platform listing distribution (Aqar, Bayut, Dubizzle)
- Client/tenant/buyer management
- Owner/landlord management
- Lease & sales contract management
- Commission & fee tracking
- Calendar & appointment scheduling
- Virtual tour integration (360° photos/video)
- Document management (contracts, IDs, title deeds)
- Notification & reminder engine (rent due, contract expiry)
- Lead management & pipeline tracking
- Reporting & analytics

### React Dashboard (Web)

- Dashboard: pipeline value, active listings, upcoming renewals, agent performance
- Property management: full CRUD, photo upload, virtual tour embedding
- Client CRM: contact management, interaction history, lead scoring
- Contracts: lease/sales contract templates, digital signing, expiry alerts
- Appointments: calendar view, property viewing scheduling
- Listings: multi-platform publishing, listing status sync
- Agents: performance dashboard, commission tracking, targets
- Owners: owner portal view, statement generation
- Reports: listings, deals, commissions, market analysis
- Settings: office profile, commission structure, contract templates

### Flutter App (Mobile)

- Agent app: property management on the go, client calls, appointment notifications
- Client app: browse properties, virtual tours, request viewings, submit offers
- QR code property info (scan to view details)
- Real-time chat (client ↔ agent)
- Push notifications for new listings, offer updates
- Arabic-first Material 3 UI

## 5. Data Model (MVP)

- **User:** id, name, email, phone, role, office_id, commission_rate, created_at
- **Office:** id, name, commercial_registration, address, city, phone, logo, license_number, status
- **Property:** id, office_id, agent_id, owner_id, type (apartment/villa/office/land/ commercial), purpose (rent/sale), title_ar, title_en, description, price, currency, area_sqm, bedrooms, bathrooms, floor, furnishing, parking, latitude, longitude, address, city, district, status (draft/published/rented/sold/expired), featured, created_at
- **PropertyImage:** id, property_id, url, is_primary, sort_order
- **PropertyVirtualTour:** id, property_id, type (360_photo/video), url
- **Owner:** id, name, phone, email, id_number, address, bank_account, notes
- **Client:** id, name, phone, email, type (buyer/tenant/seller), id_number, nationality, budget_min, budget_max, property_type_preference, notes, status
- **Appointment:** id, property_id, client_id, agent_id, type (viewing/meeting), status (scheduled/completed/cancelled), start_time, end_time, notes
- **Lead:** id, source, client_id, agent_id, status (new/contacted/interested/negotiation/closed/lost), notes, assigned_to
- **Contract:** id, type (lease/sale), property_id, client_id, owner_id, agent_id, start_date, end_date, rent_amount/sale_amount, commission_amount, payment_frequency, terms, status (draft/signed/active/expired/terminated), signed_date, expiry_date
- **ContractDocument:** id, contract_id, name, file_url, uploaded_at
- **Transaction:** id, contract_id, type (rent_payment/commission/deposit), amount, date, method, receipt, notes
- **Commission:** id, contract_id, agent_id, amount, percentage, status (pending/paid), paid_date
- **ListingPlatform:** id, name, api_endpoint, api_key (encrypted), status
- **ListingSync:** id, property_id, platform_id, external_id, status, last_sync
- **Notification:** id, user_id, title, body, type, is_read, created_at

## 6. API Endpoints (MVP)

- `POST /api/login` — Auth
- `GET /api/properties` — List properties (with filters)
- `POST /api/properties` — Create property
- `GET /api/properties/{id}` — Property detail
- `PUT /api/properties/{id}` — Update property
- `DELETE /api/properties/{id}` — Delete/archive property
- `POST /api/properties/{id}/images` — Upload images
- `POST /api/properties/{id}/virtual-tour` — Add virtual tour
- `PUT /api/properties/{id}/status` — Update status
- `GET /api/clients` — Client list
- `POST /api/clients` — Create client
- `GET /api/clients/{id}` — Client detail
- `PUT /api/clients/{id}` — Update client
- `GET /api/clients/{id}/leads` — Client leads
- `GET /api/owners` — Owner list
- `POST /api/owners` — Create owner
- `GET /api/appointments` — Appointments (filter by agent/date)
- `POST /api/appointments` — Create appointment
- `PUT /api/appointments/{id}/status` — Update appointment
- `GET /api/leads` — Lead pipeline
- `POST /api/leads` — Create lead
- `PUT /api/leads/{id}/status` — Update lead status
- `GET /api/contracts` — Contract list
- `POST /api/contracts` — Create contract
- `PUT /api/contracts/{id}` — Update contract
- `PUT /api/contracts/{id}/sign` — Sign contract
- `GET /api/transactions` — Transactions
- `POST /api/transactions` — Record transaction
- `GET /api/commissions` — Commission list
- `PUT /api/commissions/{id}/pay` — Pay commission
- `GET /api/reports/listings` — Listing report
- `GET /api/reports/sales` — Sales report
- `GET /api/reports/agents` — Agent performance
- `POST /api/listings/publish` — Publish to platforms
- `GET /api/notifications` — Notifications

## 7. User Interface (Screen List)

### Dashboard Screens (React)
- Login
- Dashboard: pipeline kanban, active listings, upcoming appointments, revenue chart
- Properties: property table with search/filter, map view
- Property Detail: tabs (info, media, tours, client interest, contract)
- Property Form: wizard (basic info → media → pricing → owner → publish)
- Clients: client table, detailed profile with history
- Leads: kanban board (new → contacted → interested → negotiation → closed)
- Appointments: calendar view, list view
- Contracts: contract table, detail with documents, signing workflow
- Transactions: financial log, receipt upload
- Commissions: agent earnings, payout management
- Reports: exportable PDF/Excel reports
- Data Sync: listing platform connections, sync status

### Mobile Screens (Flutter)

**Agent App:**
- Home: today's appointments, hot leads, property requests
- Properties: quick add with camera, edit, publish toggle
- Clients: search, call, message, appointment schedule
- Appointments: calendar, confirm/cancel
- Contracts: create from template, send to sign
- Commissions: my earnings, payout history

**Client App:**
- Browse: property search with filters (type, price, location)
- Property Detail: gallery, virtual tour, contact agent
- Favorites: saved properties
- Appointments: request viewing, manage bookings
- Offers: submit offer, track status
- Notifications: new listings, offer updates

### Screen Flow
```
Agent:
  Dashboard → Properties → Add Property → Publish → Client Inquiry → Appointment → Showing → Offer → Contract → Commission

Client:
  Browse → Search → Filter → Property Detail → Virtual Tour → Request Viewing → Appointment → Offer → Contract
```

## 8. Business Model

- **Pricing tiers:**
  - سمسار فردي $19/شهر: 50 عقاراً، إدارة عملاء أساسية
  - مكتب صغير $49/شهر: 3 مستخدمين، 200 عقار، تقارير، عقود
  - مكتب محترف $99/شهر: 10 مستخدمين، عقارات غير محدودة، تكامل إعلانات، جولات افتراضية
  - شركة $199/شهر: مستخدمين غير محدودين، API، تقارير متقدمة، أولوية الدعم
- **Free trial:** 14 يوم تجربة مجانية
- **Target MRR per client:** $19-$199
- **Additional:** رسوم نشر الإعلانات على المنصات الخارجية (تمرير التكلفة)

## 9. Implementation Plan

- **Phase 1 (Weeks 1-2):** Auth + Property + Owner + Client CRUD models + APIs
- **Phase 2 (Weeks 3-4):** Appointment + Lead pipeline + Contract + Transaction APIs
- **Phase 3 (Weeks 5-6):** React Dashboard — property management, CRM, contracts, reports
- **Phase 4 (Weeks 7-8):** Flutter Agent App — property, clients, appointments
- **Phase 5 (Weeks 9-10):** Virtual tour integration + Listing platform API integration + Flutter Client App
- **Phase 6 (Weeks 11-12):** Testing, data migration tools, deployment

## 10. Risk & Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| منافسة من منصات الإعلانات الكبرى | High | التحول من مجرد إعلانات إلى CRM كامل، قيمة مضافة حقيقية |
| تعقيد تكامل منصات الإعلانات المتعددة | Medium | واجهة نشر موحدة، تكامل مع أكبر 3 منصات أولاً |
| مقاومة السماسرة للنظام الجديد | Medium | تصميم سهل جداً، دعم فني، فترة انتقالية مع دعم |
| صحة بيانات العقارات (تكرار، قديم) | Medium | نظام أرشفة تلقائي، تنبيهات للبيانات غير النشطة |
| تعقيد العقود (لأنظمة قانونية مختلفة) | Medium | قوالب عقود قابلة للتخصيص، إخلاء مسؤولية قانوني |
