# PRD: WaterMgt (SAAS-073)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** نظام متكامل لإدارة شبكات المياه: تتبع استهلاك المشتركين، إصدار الفواتير، جدولة الصيانة، تحليل تسربات الشبكة
- **Problem:** فواتير مياه غير دقيقة، صعوبة ت追踪 استهلاك المشتركين، تأخير في اكتشاف التسربات، إدارة صيانة غير فعالة في البلديات
- **Solution:** Laravel API + React Dashboard (water authorities) + Flutter App (field workers, consumers)

## 2. Market & Opportunity
- **Target market:** $4B MENA water utility management software; 2000+ municipal water authorities; Smart water meter adoption growing 25% CAGR
- **Customer segment:** B2G (municipalities, water authorities, utility companies)
- **Competitors:** Bentley WaterGEMS, Schneider EcoStruxure, IBM Intelligent Water, منصة تَمّ, SAP Water
- **Differentiation:** Affordable SaaS for mid-tier municipalities, Arabic-first interface, integrated leak detection AI, field mobile app for technicians

## 3. User Personas

### مدير شبكة المياه — فهد (Primary)
- **Role:** مدير إدارة المياه في بلدية متوسطة
- **Goals:** تحسين دقة الفواتير، تقليل الفاقد، أتمتة قراءة العدادات
- **Pain points:** أنظمة قديمة مكلفة، صعوبة الحصول على تقارير دقيقة، شكاوى المواطنين

### فني الصيانة — يوسف (Secondary)
- **Role:** فني مياه ميداني يقوم بالصيانة وقراءة العدادات
- **Goals:** معرفة مهام اليوم، تسجيل القراءات ميدانياً، الإبلاغ عن الأعطال بسرعة
- **Pain points:** أوراق عمل ورقية، عدم توفر خرائط الشبكة، تواصل صعب مع المركز

### المشترك — أحمد (Tertiary)
- **Role:** مواطن يريد متابعة استهلاكه وفواتير المياه
- **Goals:** دفع الفواتير أونلاين، الإبلاغ عن تسرب، معرفة تاريخ الاستهلاك

### Admin — Dashboard Operator
- **Role:** مشغل النظام في البلدية يدير المستخدمين والإعدادات

## 4. Features by Platform

### Laravel API (Backend)
- Consumer management (accounts, meters, zones)
- Meter reading management (manual, AMI integration)
- Billing engine (tariff tiers, due dates, penalties)
- Work order management for maintenance/repairs
- Leak detection analytics (flow anomalies, pressure data)
- Payment gateway integration
- Roles: Admin, Billing Operator, Field Tech, Consumer

### React Dashboard (Web)
- Network map visualization (GIS integration)
- Billing dashboard (invoices, collections, arrears)
- Consumer management (accounts, history, flags)
- Work order dispatch board
- Reports & analytics (NRW, consumption trends, collection rate)
- Tariff configuration & rate tables
- Meter inventory management

### Flutter App (Mobile)
- **Field Worker App:** Tasks list, Meter reading capture (manual + camera OCR), Work order details, Map with network assets, Issue reporting, Offline mode
- **Consumer App:** View consumption, Pay bills (card/wallet), Submit meter reading, Report leaks, Contact support, Bill history

## 5. Data Model (MVP)
- **Consumer:** id, account_no, name, address, zone_id, phone, email
- **Meter:** id, consumer_id, meter_no, type (analog/smart), installation_date, last_reading_date, status
- **MeterReading:** id, meter_id, reading_value, reading_date, source (manual/ami), technician_id
- **Invoice:** id, consumer_id, period_start, period_end, consumption, amount_due, due_date, status, paid_at
- **Payment:** id, invoice_id, amount, method, transaction_id, paid_at
- **WorkOrder:** id, type (repair/install/read), assigned_to, status, priority, scheduled_date, notes
- **Zone:** id, name, boundaries (JSON), supervisor_id
- **LeakAlert:** id, zone_id, location, severity, status, detected_at, resolved_at

## 6. API Endpoints (MVP)
- `POST /api/consumers` — Create consumer account
- `GET /api/consumers/{id}/bills` — Consumer billing history
- `POST /api/meters/readings` — Submit meter reading
- `GET /api/readings/{meter_id}` — Reading history for meter
- `GET /api/consumers/{id}/balance` — Current balance + due
- `POST /api/payments` — Process payment
- `GET /api/work-orders` — List work orders (filter: status, technician)
- `PATCH /api/work-orders/{id}` — Update work order status
- `POST /api/leak-alerts` — Report leak
- `GET /api/reports/consumption` — Consumption analytics
- `GET /api/reports/nrw` — Non-revenue water report
- `POST /api/billing/generate` — Generate invoices for period

## 7. User Interface (Screen List)
- **Dashboard screens:** Network overview map, Billing summary, Work order board, Consumer search, Reports
- **Mobile (Worker):** Dashboard (today's tasks), Meter reading, Work order detail, Map, Issue report
- **Mobile (Consumer):** Dashboard (consumption chart), Pay bill, Submit reading, Report leak, History
- **Flow (Worker):** Login → Today's Tasks → Navigate → Read Meter → Submit → Next Task
- **Flow (Consumer):** Login → View Consumption → Pay Bill → Submit Reading

## 8. Business Model
- **Pricing:** Tiered by zone count: Small (<10K consumers $299/mo), Medium (10K–50K $799/mo), Large (50K+ $1,999/mo)
- **Free trial:** 30-day pilot with one zone
- **Target MRR per municipality:** $299–$1,999
- **Add-ons:** AMI integration ($199/mo), GIS module ($99/mo), SMS notifications ($0.02/msg)

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2):** API — Consumer CRUD, Meter management, Reading submission, Basic billing engine
- **Phase 2 (Weeks 3-4):** React Dashboard — Consumer management, Billing dashboard, Work order board, Zone management
- **Phase 3 (Weeks 5-6):** Flutter Apps — Field worker app (meter reading + work orders), Consumer app (bills + payments)
- **Phase 4 (Weeks 7-8):** Leak detection analytics, GIS map integration, Payment gateways, QA, Training materials

## 10. Risk & Mitigation
- **Data migration risk:** Legacy data from Excel/old systems → Build import wizards with validation
- **Adoption risk:** Field workers resistant to mobile apps → Simple UI, offline-first, training program
- **Integration risk:** AMI meters use proprietary protocols → Vendor-specific adapters, MQTT bridge
- **Accuracy risk:** Billing errors cause complaints → Double-entry validation, audit trails, consumer portal for disputes
