# PRD: LoyaltyBox (SAAS-026)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** برنامج ولاء العملاء — نقاط مكافآت، بطاقات عضوية رقمية، عروض مخصصة لزيادة الولاء والتكرار.
- **Problem:** المتاجر والمطاعم تفقد زبائنها لعدم وجود برنامج ولاء جذاب. الحلول الحالية إما معقدة أو مكلفة.
- **Proposed solution:** Laravel API لإدارة برامج الولاء، React Dashboard للتجار، Flutter App للعملاء.

## 2. Market & Opportunity
- **Target market size:** سوق برامج الولاء ~$17B. الشرق الأوسط ينمو 15% سنوياً.
- **Customer segment:** B2B (متاجر، مطاعم، مقاهي) + B2C (عملاء).
- **Competitor landscape:** LoyaltyLion, Smile.io, Yotpo, نقطتي (سعودي).
- **Differentiation:** مجاني للعملاء، تسعير شهري منخفض للتجار $29، دعم QR للختم الرقمي.

## 3. User Personas
- **Primary 1 — صاحب مقهى (سامي):** يريد برنامج ولاء بسيط: كل 10 قهوات → قهوة مجانية. بدون تطبيق معقد.
- **Primary 2 — زبون (هدى):** تحب تجميع النقاط في كل محل. تريد بطاقة ولاء رقمية في محفظة جوالها.
- **Admin — مدير البرنامج:** يراقب تكلفة المكافآت، يحلل سلوك العملاء.

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Merchant, LoyaltyProgram, Customer, Membership, Transaction, Reward
- RESTful endpoints: CRUD programs/memberships, earn/burn points
- Auth & roles: JWT, roles (admin, merchant, customer)
- Points engine: rules engine for earn/burn rates, expiry
- Notifications: Push عندما يقترب العميل من المكافأة، عرض جديد

### React Dashboard (Web)
- إعداد برنامج الولاء: النقاط لكل ريال، المكافآت
- شاشة إدارة العملاء: نقاط كل عميل، تاريخ المعاملات
- تقارير: العملاء الأكثر ولاءً، تكلفة المكافآت، ROI
- حملات العروض: إنشاء عروض مخصصة (وقت محدود، buy X get Y)

### Flutter App (Mobile — Customer)
- بطاقة ولاء رقمية (QR code للمحل)
- عرض الرصيد النقطي
- تاريخ المعاملات
- الإشعارات (عرض جديد، اقتراب المكافأة)
- البحث عن متاجر قريبة

### Flutter App (Merchant POS)
- مسح QR للعميل
- إضافة معاملة (إيداع أو استرداد نقاط)
- عرض رصيد العميل

## 5. Data Model (MVP)
- **Merchant:** id, name, category, logo, address, phone
- **LoyaltyProgram:** id, merchant_id, earn_rate (points per SAR), burn_rate (SAR per point), expiry_days, welcome_bonus
- **Customer:** id, name, phone, total_points, tier
- **Membership:** id, customer_id, merchant_id, points_balance, lifetime_points, joined_at
- **Transaction:** id, membership_id, type (earn/burn), points, amount, description, created_at
- **Reward:** id, merchant_id, name, points_cost, description, max_redemptions
- **Campaign:** id, merchant_id, title, type, start, end, conditions

## 6. API Endpoints (MVP)
- `POST /auth/customer/register`, `POST /auth/merchant/register`
- `POST /merchants`, `GET /merchants`, `GET /merchants/{id}`
- `POST /memberships/join`, `GET /memberships/{id}`
- `POST /transactions/earn`, `POST /transactions/burn`
- `GET /rewards`, `POST /rewards/{id}/redeem`
- `GET /campaigns`, `POST /campaigns`
- `GET /reports/loyalty`, `GET /reports/customers`

## 7. User Interface (Screen List)
- **Dashboard (Merchant):** إجمالي الأعضاء، النقاط المصروفة، المكافآت
- **Program Setup:** معالج إنشاء برنامج ولاء (3 خطوات)
- **Customer Lookup:** بحث و عرض رصيد العميل
- **Mobile (Customer) - Wallet:** بطاقات الولاء، الرصيد
- **Mobile (Customer) - History:** المعاملات
- **Mobile (Merchant POS) - Scan:** مسح QR + إضافة معاملة

## 8. Business Model
- **Pricing tiers:**
  - Starter (1 location): $29/شهر
  - Growth (1-5 locations): $79/شهر
  - Enterprise (unlimited): $199/شهر
- **Free trial:** 30 يوم
- **Target MRR per client:** $29-$199

## 9. Implementation Plan
- Phase 1 (Weeks 1-2): API + Auth + Programs/Points engine
- Phase 2 (Weeks 3-4): React Dashboard + Membership management
- Phase 3 (Weeks 5-6): Flutter Customer App + Wallet
- Phase 4 (Weeks 7-8): POS app + Campaigns + Reports

## 10. Risk & Mitigation
- **Technical risk:** points engine مع حسابات دقيقة. → استخدام MySQL transactions + audit log.
- **Market risk:** عملاء يخافون من التعقيد. → واجهة بسيطة جداً (مقهى يحتاج 3 نقرات).
- **Fraud risk:** تزوير النقاط. → QR مع timestamp + rate limiting.
