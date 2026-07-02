# PRD: ParkingIQ (SAAS-021)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** نظام حجز وإدارة مواقف السيارات الذكي — احجز موقفك، ادفع رقمياً، ادخل بدون توقف.
- **Problem:** مواقف السيارات في المولات والمناطق العامة تعاني ازدحاماً وعدم توفر معلومات فورية عن الأماكن الشاغرة. السائقون يضيعون وقتاً للبحث (10-20 دقيقة)، والمراكز التجارية تخسر زبائن.
- **Proposed solution:** Laravel API يدير المواقف والحجوزات والمدفوعات، React Dashboard لمشغلي المواقف، Flutter App للسائقين.

## 2. Market & Opportunity
- **Target market size:** سوق إدارة مواقف السيارات الذكي عالمياً ~$7B بحلول 2027. الشرق الأوسط ~$400M.
- **Customer segment:** B2B (مشغلو مواقف، مولات) + B2C (سائقون).
- **Competitor landscape:** ParkMobile, EasyPark, HonkMobile, مواقف دبي الذكية.
- **Differentiation:** تكامل مع أنظمة الدفع المحلية (Mada, STC Pay)، دعم اللغة العربية بالكامل، تكلفة أقل 40% من المنافسين.

## 3. User Personas
- **Primary 1 — سائق (خالد):** رجل أعمال يزور مولات يومياً. يريد حجز موقف مسبقاً، دفع سريع، معرفة الشواغر فورياً.
- **Primary 2 — مشغل مواقف (سارة):** مديرة مول تريد مراقبة الإشغال، تسعير ديناميكي، تقارير دخل.
- **Admin — مدير نظام:** يضيف مواقف جديدة، يدير المستخدمين، يراقب المعاملات.

## 4. Features by Platform

### Laravel API (Backend)
- Core models: User, ParkingLot, ParkingSpot, Booking, Payment, Vehicle
- RESTful endpoints: CRUD lots/spots, booking lifecycle, payment processing
- Auth & roles: JWT, roles (admin, operator, driver)
- Notifications: SMS لتأكيد الحجز، push قبل انتهاء الوقت، إشعارات فواتير
- Integrations: بوابة دفع Mada/STC Pay، خرائط Google

### React Dashboard (Web)
- لوحة تحكم تفاعلية: خريطة حية للمواقف، إشغال آني
- إدارة الأسعار: تسعير ديناميكي حسب وقت الذروة
- تقارير: إيرادات يومية، معدل إشغال، ساعات الذروة
- إدارة المستخدمين والمشغلين

### Flutter App (Mobile)
- خريطة تفاعلية لعرض المواقف الشاغرة
- حجز موقف ودفع آمن
- تذكير بانتهاء الوقت (push notification)
- تاريخ الحجوزات والفواتير
- دعم QR code للدخول والخروج

## 5. Data Model (MVP)
- **User:** id, name, phone, email, role, vehicle_plate, payment_methods
- **ParkingLot:** id, name, location (lat/lng), total_spots, hourly_rate, operator_id
- **ParkingSpot:** id, lot_id, spot_number, floor, status (available/occupied/reserved)
- **Booking:** id, user_id, spot_id, start_time, end_time, total_amount, status
- **Payment:** id, booking_id, amount, method, transaction_id, status
- **Vehicle:** id, user_id, plate_number, model, color

## 6. API Endpoints (MVP)
- `POST /auth/register`, `POST /auth/login`, `POST /auth/refresh`
- `GET /lots`, `GET /lots/{id}/spots` (مع حالة الشواغر)
- `POST /bookings`, `GET /bookings`, `PATCH /bookings/{id}/cancel`
- `POST /payments/process`, `GET /payments/history`
- `GET /reports/occupancy`, `GET /reports/revenue`

## 7. User Interface (Screen List)
- **Dashboard:** خريطة ساخنة للمواقف، مؤشرات أداء رئيسية (KPI)
- **Mobile - Home:** خريطة بقائمة مواقف قريبة
- **Mobile - Booking:** اختيار موقف، وقت، دفع
- **Mobile - Profile:** سياراتي، حجوزاتي، فواتيري
- **Mobile - Live:** شاشة دخول/خروج QR

## 8. Business Model
- **Pricing tiers:**
  - Basic (1-2 lots): $99/شهر
  - Pro (3-10 lots): $249/شهر
  - Enterprise (unlimited): $499/شهر
- **Free trial:** 14 يوم
- **Target MRR per client:** $99-$499

## 9. Implementation Plan
- Phase 1 (Weeks 1-2): Laravel API + Auth + CRUD lots/spots/bookings
- Phase 2 (Weeks 3-4): React Dashboard مع خريطة حية وتقارير
- Phase 3 (Weeks 5-6): Flutter App مع خريطة وحجز ودفع
- Phase 4 (Weeks 7-8): بوابة دفع، QR، اختبارات، نشر

## 10. Risk & Mitigation
- **Technical risk:** تكامل بوابة الدفع قد يتأخر. → استخدام واجهة مجردة تدعم multiple gateways.
- **Market risk:** المولات قد تفضل حلولاً تقليدية. → عرض تجربة مجانية + ROI calculator.
- **Hardware integration:** عدّاد الدخول/الخروج يحتاج IoT. → البدء بنموذج manual check-in عبر QR.
