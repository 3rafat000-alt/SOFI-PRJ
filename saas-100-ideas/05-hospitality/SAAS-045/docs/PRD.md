# PRD: KioskPro (SAAS-045)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** منصة شاشات خدمة ذاتية متكاملة — طلب طعام، معلومات زوار، إدارة قائمة انتظار، كل ذلك عبر شاشة لمس.
- **Problem:** المطاعم والمستشفيات والبنوك تحتاج أكشاك خدمة ذاتية لتقليل الزحام وتحسين تجربة الزوار لكن الحلول الحالية غالية وتحتاج أجهزة مخصصة.
- **Solution:** KioskPro — نظام كشك يعمل على أي جهاز لوحي/شاشة لمس (Web-based)، مع لوحة تحكم للطلبات والإحصائيات.

## 2. Market & Opportunity
- **Target market:** سوق أكشاك الخدمة الذاتية ~$30B (2025)، قطاع المطاعم ~$15B.
- **Customer segment:** B2B — مطاعم، مستشفيات، بنوك، فنادق، مراكز تسوق.
- **Competitors:**
  - Toast Kiosk: قوي لكن أمريكي فقط، غالي ($99/شهر).
  - Square Kiosk: جيد لكن يحتاج أجهزة Square.
  - Eats365: شامل لكن معقد وغالي.
  - Kiosk Software (تقليدي): يحتاج أجهزة مخصصة وتثبيت محلي.
- **Differentiation:** يعمل على أي متصفح/جهاز لوحي، تسعير أقل 60%، دعم عربي كامل، قائمة انتظار ذكية.

## 3. User Personas

### الشخصية الأساسية: محمد — صاحب مطعم
- **الدور:** يريد تقليل زحام الكاشير وزيادة سرعة الطلبات
- **الأهداف:** شاشة طلب ذاتي، تقليل وقت الانتظار، زيادة متوسط الفاتورة
- **المشكلات:** العملاء ينتظرون طويلاً، الأخطاء في الطلبات كثيرة، الأكشاك المتوفرة غالية

### الشخصية الثانوية: د. هدى — مديرة مستشفى
- **الدور:** تريد نظام معلومات للزوار وحجز مواعيد عبر أكشاك
- **الأهداف:** توجيه الزوار، تسجيل الدخول، طباعة بطاقات الزوار
- **المشكلات:** الزوار يتوهون، الإجراءات الورقية تبطئ العمل

### Admin: مشرف النظام
- يدير الأجهزة، المحتوى، الطلبات، تقارير الاستخدام.

## 4. Features by Platform

### Laravel API (Backend)
- Models: Kiosk, MenuCategory, MenuItem, Order, QueueTicket, VisitorLog
- Kiosk session management (device registration, pairing code)
- Menu management: categories, modifiers, images, availability
- Order pipeline: received → preparing → ready → completed
- Queue management: ticket generation, estimated wait time

### React Dashboard (Web)
- Menu editor: drag-drop categories, item CRUD, price management
- Order viewer: real-time order stream, status updates
- Kiosk management: device list, status (online/offline), assign location
- Queue display config: multi-screen support, call next
- Analytics: order volume, peak hours, popular items, avg wait time

### Flutter App (Mobile)
- Kiosk mode: runs in fullscreen on tablet, touch-optimized UI
- Menu browsing: categories → items → modifiers → cart → checkout
- Visitor mode: directory, appointment check-in, queue ticket
- Staff mode: view incoming orders, mark ready, call customer
- Arabic/English toggle

## 5. Data Model (MVP)
- **Kiosk**: id, device_id, location, location_type (restaurant/hospital/bank), status, last_ping
- **MenuCategory**: id, name_ar, name_en, sort_order, image_url, is_active
- **MenuItem**: id, category_id, name_ar, name_en, description, price, image_url, modifiers_json, is_available
- **Order**: id, kiosk_id, items_json, total, status (new/preparing/ready/completed/cancelled), customer_name, created_at
- **QueueTicket**: id, kiosk_id, ticket_number, department, customer_name, status (waiting/called/completed), created_at
- **VisitorLog**: id, kiosk_id, name, purpose, host_name, check_in, check_out, badge_printed

## 6. API Endpoints (MVP)
- `POST /api/kiosks/register` — device registration
- `GET /api/kiosks/{token}/menu` — get full menu for kiosk
- `POST /api/orders` — create order
- `GET /api/orders/{id}` — get order status
- `PATCH /api/orders/{id}/status` — update status (staff)
- `GET /api/kiosks/{id}/queue` — get queue
- `POST /api/queue` — issue ticket
- `PATCH /api/queue/{id}/call` — call next
- `CRUD /api/menu-categories`, `CRUD /api/menu-items` — admin
- `GET /api/stats/kiosk/{id}` — kiosk analytics
- `POST /api/auth/login`, `POST /api/auth/register`

## 7. User Interface (Screen List)
- **Dashboard** (React): Live orders, kiosk map, queue status, quick stats
- **Menu Editor** (React): Drag-drop categories, item form, modifiers builder
- **Kiosk Manager** (React): Device grid, status badges, location assignment
- **Queue Display** (React): Fullscreen queue board, call next button, sound alert
- **Analytics** (React): Orders trend, peak hours heatmap, popular items, conversion rate
- **Settings** (React): Branding, language, printer config, payment gateway
- **Mobile Kiosk** (Flutter): Fullscreen touch menu → cart → checkout → QR receipt
- **Mobile Staff** (Flutter): Order notifications → view → mark ready

## 8. Business Model
- **Starter**: $29/month — 1 kiosk, basic menu, 500 orders/month
- **Growth**: $79/month — 3 kiosks, queue management, analytics, custom branding
- **Enterprise**: $199/month — 10+ kiosks, visitor log, API access, priority support
- **Free trial**: 14 days Growth
- **Target MRR/client**: $29–$199

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2)**: Laravel API — Kiosk/Menu/Order models + device registration + queue logic
- **Phase 2 (Weeks 3-4)**: React Dashboard — menu editor, order viewer, kiosk management
- **Phase 3 (Weeks 5-6)**: Flutter Kiosk App — fullscreen mode, menu browsing, cart, checkout
- **Phase 4 (Weeks 7-8)**: Queue display screen, analytics, queue ticket printer integration, testing

## 10. Risk & Mitigation
- **Technical**: Touchscreen responsiveness → Mitigation: large buttons (48px+), gesture optimization, pointer events
- **Technical**: Offline resilience → Mitigation: PWA service worker for menu caching, queue offline mode
- **Market**: Hardware fragmentation → Mitigation: web-based (runs on any tablet browser), no native install needed for kiosk mode
