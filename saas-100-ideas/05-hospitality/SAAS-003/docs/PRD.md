# PRD: MenuByte (SAAS-003)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner**: منصة رقمية للمطاعم والمقاهي — قوائم طعام عبر QR، استقبال طلبات مباشر، تتبع المطبخ، وإدارة الطلبات مع شاشة عرض للمطبخ (KDS).
- **Problem statement**: المطاعم الصغيرة تعاني من تكاليف طباعة القوائم، أخطاء الـويتري يدوياً، وانتظار العملاء بدون تحديث لحالة الطلب.
- **Proposed solution**: Laravel API + React Dashboard + Flutter App (Admin + KDS) — QR menu يتيح للعميل طلب الطعام من جواله، توجيه الطلب فوراً للمطبخ مع إشعارات التجهيز.

## 2. Market & Opportunity
- **Target market size**: سوق تكنولوجيا المطاعم العالمي ~$25B (2025)، الشرق الأوسط ~$1.5B نمو 18% سنوياً.
- **Customer segment**: B2B — مطاعم ومقاهي صغيرة ومتوسطة، قطاع الضيافة.
- **Competitor landscape**:
  1. **Zomato / HungerStation**: طلب توصيل فقط، لا تدير تشغيل المطعم الداخلي.
  2. **Toast**: شامل لكن أمريكي فقط، لا دعم عربي أو QR menu.
  3. **Lightspeed**: POS قوي لكن سعره عالي، معقد للمطاعم الصغيرة.
  4. **Eat App**: حجوزات طاولات فقط.
  5. **MenuHub**: قوائم رقمية فقط بدون طلب مباشر.
- **Differentiation**: QR menu كامل مع طلب ودفع، KDS مدمجة، إشعارات واتساب للعميل، دعم عربي كامل وأسعار مناسبة.

## 3. User Personas

### Primary: عمر — صاحب مطعم برجر (30 طاولة)
- **الدور**: يدير مطعماً مع 10 موظفين، يريد تقليل وقت الانتظار.
- **الأهداف**: قائمة رقمية، طلب مباشر من الطاولة، إشعارات للعملاء.
- **نقاط الألم**: أخطاء الطلبات اليدوية، تأخر الخدمة.

### Secondary: سامر — شيف (Head Chef)
- **الدور**: مسؤول عن المطبخ وتجهيز الطلبات.
- **الأهداف**: رؤية الطلبات الواردة فوراً، إشعار عند تجهيز الطلب.
- **نقاط الألم**: صعوبة قراءة خط اليد، فقدان أوراق الطلبات.

### Admin: ندى — مديرة المطعم
- **الدور**: تشغيل يومي، محاسبة، إدارة قائمة الطعام.
- **الأهداف**: تقارير مبيعات يومية، إدارة الأسعار، متابعة المخزون.
- **نقاط الألم**: لا رؤية فورية للمبيعات، صعوبة تحديث القائمة.

## 4. Features by Platform

### Laravel API (Backend)
- Core domain models: Restaurant, Branch, MenuItem, Category, Menu, Order, OrderItem, KdsDisplay, Payment
- RESTful endpoints: full CRUD
- Auth: Sanctum multi-role (owner/chef/cashier)
- Order pipeline: submitted → preparing → ready → served → paid
- QR code generation: static per table, dynamic per menu version
- Gateway integration: Stripe/Moyasar/MyFatoorah
- Notifications: WhatsApp order updates, push to KDS

### React Dashboard (Web)
- Admin panel: restaurant profile, branch management
- Menu builder: drag-item categories, photo upload, pricing, modifiers
- Order monitor: live order feed, filter by status/table
- KDS view: queue of orders, mark as preparing/ready
- Reports: sales by item, peak hours, revenue, order volume
- Staff management: roles, PIN codes for staff login

### Flutter App (Mobile)
- Customer-facing: scan QR → view menu → add items → place order → pay → track status
- KDS (chef app): incoming order notification, order queue, mark complete
- Admin app: daily sales snapshot, quick menu edit
- Offline: menu cached locally, orders queue when offline
- Push notifications: new order to kitchen, payment confirmed

## 5. Data Model (MVP)

| Entity | Fields | Relationships |
|---|---|---|
| Restaurant | id, name, slug, logo, currency, timezone | hasMany Branch, Menu |
| Branch | id, restaurant_id, name, address, phone, tables_count | belongsTo Restaurant |
| Category | id, restaurant_id, name, sort_order, icon | belongsTo Restaurant |
| MenuItem | id, category_id, name, description, price, image, available | belongsTo Category |
| Modifier | id, menu_item_id, name, options (JSON) | belongsTo MenuItem |
| Order | id, branch_id, table_number, status, total, source | belongsTo Branch |
| OrderItem | id, order_id, menu_item_id, quantity, modifiers, price | belongsTo Order |
| Payment | id, order_id, method, amount, gateway_ref | belongsTo Order |

## 6. API Endpoints (MVP)

| Method | Endpoint | Description |
|---|---|---|
| GET | /api/{restaurant_slug}/menu | Public menu JSON |
| POST | /api/orders | Place order (from customer app) |
| GET | /api/orders/{id} | Order status |
| GET | /api/branches/{id}/kds | KDS queue |
| PATCH | /api/orders/{id}/status | Update order status |
| GET | /api/restaurants/{id}/reports | Sales report |
| POST | /api/qr/{table_id}/generate | Generate table QR |

## 7. User Interface (Screen List)

### Dashboard screens (React)
- Login → Branch selector
- Order monitor: real-time cards for each order
- Menu editor: category tree + menu item form
- KDS full-screen mode
- Reports: date picker + charts
- Settings: restaurant info, staff PINs, payment gateways

### Mobile screens (Flutter)
- Customer: QR scanner → Menu grid → Cart → Checkout → Order status
- Chef (KDS): incoming orders (sound alert) → Prepare → Ready
- Admin: Sales card, quick item toggle (available/unavailable)

### Screen flow (text)
```
Customer: Scan Table QR → View Menu (grid/categorized) → Add Items → Cart → Place Order → Pay → Track: Preparing → Ready
KDS: New Order Alert → Order Queue (sorted by time) → Press "Preparing" → Press "Ready"
Dashboard Login → Monitor (live feed) → Menu Editor → Reports
```

## 8. Business Model
- **Lite**: $19/month — 1 branch, 50 menu items, 1 KDS screen
- **Pro**: $39/month — 3 branches, unlimited items, 3 KDS, reports
- **Premium**: $79/month — unlimited branches, API access, WhatsApp integration
- **Free trial**: 14-day Pro trial

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2)**: Laravel API — Restaurant, Menu, Category, MenuItem, Order CRUD
- **Phase 2 (Weeks 3-4)**: React Dashboard — Menu builder, Order monitor, Report dashboard
- **Phase 3 (Weeks 5-6)**: Flutter App — Customer menu/ordering, Chef KDS, Print integration
- **Phase 4 (Weeks 7-8)**: Payment gateway, QR gen, Testing, Deploy

## 10. Risk & Mitigation
- **Technical**: Real-time order sync — strategy: server-sent events + polling fallback.
- **Market**: Restaurant staff tech aversion — strategy: simple UI, large touch targets, KDS with sound.
- **Operational**: Internet downtime — strategy: offline order queuing syncs on reconnect.
