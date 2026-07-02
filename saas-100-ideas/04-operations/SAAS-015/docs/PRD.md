# PRD: LaundryHub (SAAS-015)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary

**منصة رقمية لإدارة مغاسل الملابس وخدمات الغسيل.** تقدم LaundryHub حلاً متكاملاً لاستقبال الطلبات، تتبع حالة الغسيل، إدارة التوصيل، والفوترة الإلكترونية. تستهدف مغاسل الملابس وخدمات غسيل السيارات.

- المشكلة: مغاسل الملابس تعتمد على الدفاتر اليدوية والمكالمات الهاتفية لتتبع الطلبات، مما يسبب أخطاء في تسليم الغسيل، تأخير، وضعف تجربة العملاء.
- الحل: Laravel API + React Dashboard + Flutter App.

## 2. Market & Opportunity

- السوق المستهدف: 15,000+ مغسلة وخدمة غسيل في الشرق الأوسط
- الفئة: B2B (مغاسل ملابس، مغاسل سيارات، خدمات غسيل فندقية)
- المنافسون:
  - **LaundryCare** — نظام أجنبي لم يترجم للعربية
  - **CleanCloud** — منصة مغاسل سحابية (مكلفة نسبياً)
  - **محلّي** — حل محلي محدود الميزات
- التمايز: دعم عربي، واجهة مبسطة للمغاسل الصغيرة، تطبيق عميل لتتبع الطلبات

## 3. User Personas

### شخص أساسي: صاحب المغسلة
- الاسم: عمر
- الدور: يدير مغسلة ملابس متوسطة مع 3 عمال
- الأهداف: تنظيم الطلبات، تتبع حالة كل طلب، تقليل أخطاء التسليم
- نقاط الألم: ضياع الطلبات، صعوبة تتبع العملاء المتكررين، عدم وجود تقارير

### شخص أساسي: العميل (مستخدم الخدمة)
- الاسم: هدى
- الدور: تبحث عن خدمة غسيل موثوقة وسريعة
- الأهداف: طلب الخدمة بسهولة، تتبع حالة الغسيل، دفع إلكتروني
- نقاط الألم: عدم معرفة موعد التوصيل، صعوبة التواصل مع المغسلة

### Admin: مشرف المنصة
- إدارة حسابات المغاسل، مراقبة الجودة، إدارة العمولات.

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Laundry, Order, OrderItem, Customer, Driver, Payment, Rating
- RESTful CRUD for orders, customers, inventory
- Order lifecycle tracking (received -> washing -> drying -> folding -> ready -> delivered)
- SMS/Email notifications for order status changes
- Barcode/label generation for order tracking
- Driver assignment and route optimization
- Payment integration (cash, card, wallet)

### React Dashboard (Web)
- Dashboard: orders today, revenue, pending/delivered ratio, service KPIs
- Order management: create order, assign items, track status, mark complete
- Customer management: profile, order history, preferences, notes
- Inventory tracking: detergents, supplies with low-stock alerts
- Delivery management: assign drivers, view routes, track delivery status
- Pricing: service menu, price per item, bulk discounts
- Reports: daily revenue, popular services, customer retention

### Flutter App (Mobile)
- Customer app: place order, select services, schedule pickup, track status
- Real-time order tracking: push notifications at each stage
- Driver app: view assigned deliveries, navigate to customer, mark delivered
- Barcode scanner: scan order label to update status
- Payment: wallet, cash on delivery, card
- Rating and review after delivery

## 5. Data Model (MVP)

### Laundry
- id, name, address, phone, license, opening_hours, delivery_radius, services (JSON), created_at

### Order
- id, laundry_id (FK), customer_id (FK), driver_id (FK), order_number, status (received/washing/drying/folding/ready/delivered/cancelled), pickup_address, delivery_address, pickup_time, delivery_time, total_amount, notes, created_at

### OrderItem
- id, order_id (FK), service_name, quantity, unit_price, subtotal, special_instructions, created_at

### Customer
- id, name, phone, email, address, total_orders, total_spent, is_vip, notes, created_at

### Driver
- id, laundry_id (FK), name, phone, vehicle_type, license_number, is_active, location (lat/lng), created_at

### Service
- id, laundry_id (FK), name, category (wash/dry/iron/dry-clean), base_price, unit (piece/kg), estimated_minutes, created_at

### Payment
- id, order_id (FK), amount, method, transaction_id, status, paid_at, created_at

### Rating
- id, order_id (FK), customer_id (FK), rating, comment, created_at

### User
- id, name, email, password, role (owner/staff/driver/admin), laundry_id (FK), created_at

## 6. API Endpoints (MVP)

```
POST   /api/auth/login
POST   /api/auth/register
GET    /api/auth/me

GET    /api/laundries/settings
PUT    /api/laundries/settings

GET    /api/services
POST   /api/services
PUT    /api/services/{id}
DELETE /api/services/{id}

GET    /api/orders
POST   /api/orders
GET    /api/orders/{id}
PUT    /api/orders/{id}/status
PUT    /api/orders/{id}/assign-driver
GET    /api/orders/today

GET    /api/customers
POST   /api/customers
GET    /api/customers/{id}
GET    /api/customers/{id}/orders

GET    /api/drivers
POST   /api/drivers
PUT    /api/drivers/{id}/location

POST   /api/payments
GET    /api/payments/{id}

GET    /api/deliveries/today
PUT    /api/deliveries/{id}/status

POST   /api/ratings

GET    /api/reports/daily?laundry_id=&date=
GET    /api/reports/revenue?from=&to=
```

## 7. User Interface (Screen List)

### Dashboard Screens (React)
1. Login / Register
2. Dashboard - order volume chart, revenue today, status distribution
3. Order List - filterable by status, search by order/customer
4. Order Detail - full timeline, customer info, item list
5. New Order - customer lookup, item selection, pricing
6. Customer List - searchable with order count
7. Customer Detail - profile, history, notes
8. Driver Management - list, status, active deliveries
9. Service Menu - pricing, categories, edit
10. Reports - daily revenue, popular services, customer stats
11. Settings - laundry profile, users, notifications

### Mobile Screens (Flutter) - Customer App
1. Splash -> Login/Register
2. Home - place new order, track current, browse services
3. New Order - select services, schedule pickup, address
4. Order Tracking - status timeline with live updates
5. Order History - past orders with reorder option
6. Wallet - balance, transactions
7. Profile - personal info, addresses
8. Notifications - order updates, promotions

### Mobile Screens (Flutter) - Driver App
1. Login
2. Today's Deliveries - list with route optimization
3. Delivery Detail - customer info, items, navigation
4. Status Update - swipe to mark delivered
5. Barcode Scanner - scan label to confirm
6. Earnings - daily/weekly summary

### Screen Flow
Customer Orders -> Laundry Receives -> Status Updates -> Driver Assigned -> Delivered -> Rated

## 8. Business Model

- **الباقة الأساسية**: $29/شهر (حتى 500 طلب/شهر، مستخدم واحد)
- **الباقة الاحترافية**: $59/شهر (غير محدود الطلبات، 3 مستخدمين، تطبيق عميل)
- **باقة المؤسسات**: $119/شهر (غير محدود، تطبيق عميل و driver app، دعم ممتاز)
- فترة تجربة مجانية: 14 يوماً
- MRR المستهدف لكل عميل: $29-$119

## 9. Implementation Plan

- Phase 1 (Weeks 1-2): Laravel API - Auth, Laundry/Service/Order CRUD
- Phase 2 (Weeks 3-4): Customer/Driver management, order lifecycle, payment
- Phase 3 (Weeks 5-6): React Dashboard - All screens, reports, settings
- Phase 4 (Weeks 7-8): Flutter Customer App - ordering, tracking, wallet
- Phase 5 (Weeks 9-10): Flutter Driver App - deliveries, scanner, navigation

## 10. Risk & Mitigation

- **مخاطرة تقنية**: تتبع المواقع الجغرافية للسائقين في الوقت الفعلي
  - التخفيف: WebSocket للتحديثات المباشرة، تخزين مؤقت للمواقع
- **مخاطرة سوقية**: تنافس تطبيقات التوصيل الكبيرة (مرسول، نون)
  - التخفيف: التركيز على B2B والمغاسل المستقلة، وليس التوصيل فقط
- **مخاطرة تشغيلية**: إدارة حالة الطلبات يدوياً من قبل العمال
  - التخفيف: واجهة بسيطة بأزرار كبيرة، تدريب مجاني، دعم فني
