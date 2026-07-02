# PRD: AssetGuard (SAAS-028)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** نظام تتبع الأصول والممتلكات — جرد معدات، صيانة دورية، تقارير دورة حياة الأصول.
- **Problem:** المدارس والمصانع والمؤسسات تفقد تتبع معداتها. الصيانة الدورية تهمل، الجرد السنوي مرهق.
- **Proposed solution:** Laravel API + React Dashboard لإدارة الأصول + Flutter App للجرد الميداني.

## 2. Market & Opportunity
- **Target market size:** سوق Asset Management ~$25B. قطاع التعليم والصناعة الأكثر طلباً.
- **Customer segment:** B2B (مؤسسات، مدارس، مصانع، مستشفيات).
- **Competitor landscape:** Asset Panda, EZOfficeInventory, UpKeep, ServiceNow.
- **Differentiation:** واجهة عربية كاملة، تطبيق جوال لمسح QR/باركود، أسعار مناسبة للقطاع التعليمي.

## 3. User Personas
- **Primary 1 — مدير صيانة (خالد):** يدير صيانة 500+ أصل. يريد تتبع أعطال، جدولة صيانة، تقارير.
- **Primary 2 — مسؤول جرد (سعد):** يجرد الأصول سنوياً. يريد مسح QR سريع، تسجيل الحالة، رفع صور.
- **Admin — مدير المنشأة:** يراقب قيمة الأصول، تكاليف الصيانة، الاستهلاك.

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Asset, Category, Location, Maintenance, Ticket, Depreciation
- RESTful endpoints: CRUD assets/categories/maintenance
- QR code generation for each asset
- Auth & roles: JWT, roles (admin, manager, technician, auditor)
- Notifications: إشعار صيانة دورية، تذكير جرد، حالة تذكرة
- Reports: قيمة الأصول، تكاليف الصيانة، الإهلاك

### React Dashboard (Web)
- خريطة الأصول (بموقعها)
- إدارة الأصول: إضافة، تعديل، نقل، تصفية
- جدول الصيانة الدورية
- تذاكر الأعطال
- تقارير: دورة حياة الأصل، تكلفة الصيانة، الإهلاك

### Flutter App (Mobile)
- مسح QR للتأكد من الأصل
- جرد ميداني: تأكيد الوجود، تحديث الحالة
- تسجيل عطل أو طلب صيانة
- رفع صور للأصل
- البحث في الأصول

## 5. Data Model (MVP)
- **Asset:** id, category_id, location_id, name, serial_number, qr_code, purchase_date, purchase_cost, status, condition
- **Category:** id, name, depreciation_rate, expected_lifespan
- **Location:** id, name, building, floor, room
- **Maintenance:** id, asset_id, type (preventive/corrective), description, scheduled_date, completed_date, cost, vendor
- **Ticket:** id, asset_id, reported_by, description, priority, status, assigned_to
- **Depreciation:** id, asset_id, year, book_value, accumulated_depreciation

## 6. API Endpoints (MVP)
- `POST /auth/login`, `POST /auth/register`
- `GET /assets`, `POST /assets`, `PUT /assets/{id}`, `DELETE /assets/{id}`
- `GET /assets/{id}/qr` (regenerate QR)
- `GET /categories`, `POST /categories`
- `GET /locations`, `POST /locations`
- `GET /maintenances`, `POST /maintenances`, `PATCH /maintenances/{id}/complete`
- `GET /tickets`, `POST /tickets`, `PATCH /tickets/{id}/status`
- `GET /reports/asset-summary`, `GET /reports/maintenance-cost`
- `POST /audit/start`, `POST /audit/scan` (مسح QR للجرد)

## 7. User Interface (Screen List)
- **Dashboard:** ملخص (إجمالي الأصول، قيد الصيانة، Tickets مفتوحة)
- **Assets:** جدول أصول مع بحث وفلترة + بطاقة أصل
- **Location Tree:** هيكل المواقع
- **Maintenance Calendar:** تقويم الصيانة الدورية
- **Tickets:** قائمة تذاكر مع أولوية وحالة
- **Mobile - Scan:** كاميرا QR
- **Mobile - Audit:** جرد ميداني

## 8. Business Model
- **Pricing tiers:**
  - Basic (up to 100 assets): $49/شهر
  - Pro (up to 500 assets): $149/شهر
  - Enterprise (unlimited): $399/شهر
- **Free trial:** 14 يوم
- **Target MRR per client:** $49-$399

## 9. Implementation Plan
- Phase 1 (Weeks 1-2): API + Auth + Assets CRUD + QR generation
- Phase 2 (Weeks 3-4): React Dashboard + Locations + Categories
- Phase 3 (Weeks 5-6): Flutter App + QR scanner + Audit
- Phase 4 (Weeks 7-8): Maintenance + Tickets + Reports

## 10. Risk & Mitigation
- **Technical risk:** QR يحتاج طباعة ولصق على الأصول. → تصميم QR printable label.
- **Market risk:** المؤسسات قد تستخدم Excel. → إظهار قيمة بأن audit سريع 10×.
- **Adoption risk:** الفرق الميدانية قد لا تستخدم التطبيق. → تصميم واجهة بسيطة (مسح ← تأكيد).
