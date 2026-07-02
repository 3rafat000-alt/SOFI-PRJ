# PRD: SouqFarmer (SAAS-075)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** منصة ربط المزارعين مباشرة بالمستهلكين: طلبات المنتجات الزراعية، توصيل، ضمان الجودة، تقييم
- **Problem:** المزارعون لا يحصلون على سعر عادل لمنتجاتهم بسبب الوسطاء، المستهلكون يدفعون أسعاراً مرتفعة، هدر غذائي كبير في سلسلة التوزيع
- **Solution:** Laravel API + React Dashboard (warehouse/ logistics) + Flutter App (farmers + consumers)

## 2. Market & Opportunity
- **Target market:** $50B MENA agricultural produce market; 30%+ waste in current supply chain; 12M+ farmers in the region
- **Customer segment:** B2B (farmers, cooperatives) + B2C (consumers, restaurants, grocery stores)
- **Competitors:** Khamsat (Tunisia), Mozare3 (KSA), Twiga Foods (Kenya/F&B), FarmLink (Egypt), Souq.com agricultural section
- **Differentiation:** Farmer-first pricing (80%+ revenue to farmer), quality guarantee with photo verification, same-day delivery, AI demand forecasting

## 3. User Personas

### المزارع — عبود (Primary)
- **Role:** مزارع خضار وفواكه في منطقة ريفية
- **Goals:** بيع محصوله بسعر عادل، تقليل الوسطاء، توقع الطلب المستقبلي
- **Pain points:** الوسطاء يأخذون 40–60% من السعر، تأخير الدفع، عدم معرفة أسعار السوق الحقيقية

### المستهلك — نورة (Secondary)
- **Role:** ربة منزل تريد خضار طازجة بأسعار معقولة
- **Goals:** شراء خضار طازجة من المزرعة مباشرة، توصيل للمنزل، جودة مضمونة
- **Pain points:** أسعار السوبر ماركت مرتفعة، جودة الخضار متوسطة، لا تعرف مصدر المنتج

### مدير المستودع — سالم (Tertiary)
- **Role:** يدير مركز توزيع وتجميع المنتجات الزراعية
- **Goals:** إدارة المخزون، تنسيق التوصيل، فحص الجودة
- **Pain points:** المخزون يتلف بسرعة، صعوبة تتبع الشحنات

### Admin — Dashboard Operator
- **Role:** مدير المنصة يراقب العمليات، الرسوم، جودة الخدمة

## 4. Features by Platform

### Laravel API (Backend)
- Farmer profiles, certifications, harvest schedules
- Product listings (vegetables, fruits, dairy, eggs, honey)
- Order management (cart, checkout, splits)
- Logistics tracking (pickup → warehouse → delivery)
- Quality verification workflow (photo + inspector)
- Payment processing (farmer payouts, COD, card)
- Rating & review system
- Demand forecasting (seasonal + historical)

### React Dashboard (Web)
- Product catalog management
- Order dashboard (incoming, processing, delivered)
- Warehouse inventory management
- Farmer management (onboarding, verification, payouts)
- Logistics route optimization
- Quality control queue
- Analytics (sales by product, farmer performance, demand trends)
- Financial reports (revenue, commissions, farmer settlements)

### Flutter App (Mobile)
- **Farmer App:** List products, Set prices, View orders, Track payments, Announce harvest, Receive demand alerts
- **Consumer App:** Browse products by category, Search by farm/location, Place orders, Track delivery, Rate products, Subscribe to seasonal boxes, Chat with farmer

## 5. Data Model (MVP)
- **Farmer:** id, user_id, farm_name, address, land_area, crops, certifications, rating, total_sales
- **Product:** id, farmer_id, name, category, unit (kg/box/piece), price, quantity_available, harvest_date, photos, quality_grade
- **Order:** id, consumer_id, items (JSON), status, total_amount, delivery_fee, delivery_address, delivery_date, notes
- **OrderItem:** id, order_id, product_id, quantity, unit_price, farmer_payout
- **Payment:** id, order_id, amount, farmer_payout, commission, status, method, paid_at
- **Delivery:** id, order_id, driver_id, warehouse_id, pickup_time, delivered_at, status
- **Review:** id, order_id, product_id, rating, photos, comment

## 6. API Endpoints (MVP)
- `POST /api/farmers/register` — Farmer onboarding
- `GET /api/products` — List products (filter: category, farm, location, price)
- `POST /api/products` — Create listing (farmer)
- `POST /api/orders` — Place order
- `GET /api/orders` — My orders (consumer) / Orders received (farmer)
- `PATCH /api/orders/{id}/status` — Update order status
- `POST /api/payments` — Process payment
- `GET /api/farmers/{id}/products` — Farmer's catalog
- `POST /api/reviews` — Submit review
- `GET /api/farmers/{id}/payouts` — Farmer payout history
- `POST /api/deliveries/assign` — Assign delivery
- `GET /api/analytics/demand` — Demand forecast data

## 7. User Interface (Screen List)
- **Dashboard screens:** Order management, Inventory, Farmer list, Quality control, Logistics, Reports
- **Mobile (Consumer):** Home (categories, featured farms), Search, Product detail, Cart, Checkout, Orders, Profile
- **Mobile (Farmer):** Dashboard (sales today), Products, Orders, Payouts, Announce harvest, Messages
- **Flow (Consumer):** Browse → Product → Add to Cart → Checkout → Pay → Track Order → Rate
- **Flow (Farmer):** Login → List Product → Receive Order → Confirm → Handover → Get Paid

## 8. Business Model
- **Pricing:** 10% commission per sale (paid by consumer side); Farmers pay 3% for payout processing
- **Free trial:** Free for first 3 months for farmers
- **Target MRR:** $2K–$10K per regional hub
- **Additional:** Featured listings $19/month, Promoted badges $9/month, Premium analytics $29/month

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2):** API — Farmer/Farmer profiles, Product CRUD, Order management, Basic payments
- **Phase 2 (Weeks 3-4):** React Dashboard — Admin panel, Order dashboard, Farmer management, Inventory tracking
- **Phase 3 (Weeks 5-6):** Flutter Apps — Consumer app (browse, order, pay), Farmer app (list, manage, receive orders)
- **Phase 4 (Weeks 7-8):** Logistics tracking, Quality verification workflow, Demand forecasting, Reviews system, QA

## 10. Risk & Mitigation
- **Quality risk:** Variable produce quality leads to complaints → Photo verification, quality grading, money-back guarantee
- **Logistics risk:** Perishable goods spoilage → Cold chain partners, same-day delivery, real-time temperature tracking
- **Adoption risk:** Farmers not tech-savvy → Simple SMS interface, agent-assisted onboarding, farmer training
- **Supply risk:** Seasonal gaps in produce → Multi-farmer sourcing, greenhouse partner network
- **Payment risk:** Farmers need cash quickly → Instant payout via mobile wallets, daily settlement option
