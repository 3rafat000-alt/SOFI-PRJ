# PRD: PharmaStock (SAAS-018)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary

**منصة متكاملة لإدارة الصيدليات والمخزون الدوائي.** تقدم PharmaStock حلولاً رقمية لتتبع المخزون، مراقبة تواريخ انتهاء الصلاحية، إدارة الوصفات الطبية، وتسجيل المبيعات. تستهدف الصيدليات المستقلة وسلاسل الصيدليات الصغيرة.

- المشكلة: الصيدليات تعاني من ضعف إدارة المخزون، انتهاء صلاحية الأدوية دون اكتشافها، صعوبة تتبع الوصفات الطبية، وعدم وجود نظام موحد للمبيعات.
- الحل: Laravel API + React Dashboard + Flutter App.

## 2. Market & Opportunity

- السوق المستهدف: 35,000+ صيدلية في الشرق الأوسط وشمال أفريقيا
- الفئة: B2B (صيدليات مستقلة، سلاسل صيدليات صغيرة، شركات أدوية)
- المنافسون:
  - **Pharmapedia** — منصة معلومات دوائية (وليست نظام إدارة)
  - **RXNT** — نظام صيدليات أمريكي (بدون دعم عربي، مكلف)
  - **صيدلتي** — حل محلي محدود الميزات
- التمايز: تنبيهات تاريخ الانتهاء، تكامل مع نظام الوصفات الإلكترونية، تسعير مناسب

## 3. User Personas

### شخص أساسي: الصيدلي (صاحب الصيدلية)
- الاسم: أحمد
- الدور: يدير صيدلية مستقلة مع صيدلي مساعد واحد
- الأهداف: تتبع المخزون، تقليل الأدوية منتهية الصلاحية، إدارة المبيعات
- نقاط الألم: صعوبة معرفة المخزون المتبقي، خسائر بسبب انتهاء الصلاحية، فواتير يدوية

### شخص أساسي: مساعد الصيدلي
- الاسم: مريم
- الدور: صيدلانية مساعدة تتعامل مع الزبائن والوصفات
- الأهداف: صرف الوصفات بسرعة، تسجيل المبيعات، التأكد من توفر الدواء
- نقاط الألم: بطء البحث عن الأدوية، صعوبة قراءة الوصفات اليدوية، تكرار إدخال البيانات

### Admin: مشرف المنصة
- إدارة الصيدليات المسجلة، مراقبة المخزون الإجمالي، إدارة الاشتراكات.

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Pharmacy, Medicine, Stock, Prescription, Sale, Supplier, ExpiryAlert
- RESTful CRUD for medicines, stock, sales, prescriptions
- Expiry date tracking with automated alert engine
- Barcode scanning for medicine identification
- Prescription management (scan, log, track controlled substances)
- Sales reporting and daily closing
- Supplier management and purchase orders
- Low stock alerts and reorder suggestions
- Invoice generation (tax-compliant with VAT)

### React Dashboard (Web)
- Dashboard: sales chart, expiring soon list, low stock alerts, revenue KPIs
- Inventory management: medicine catalog, stock levels, batch tracking
- Expiry tracker: dashboard widget + detailed report of expiring medicines
- Sales register: daily sales log, payment methods, refunds
- Prescription log: scanned prescriptions, dispensed medicines, patient history
- Supplier management: contact info, purchase orders, payment tracking
- Purchase orders: create PO, receive stock, update quantities
- Reports: sales by category, profit margin, expiry loss analysis
- Settings: pharmacy profile, tax settings, receipt template

### Flutter App (Mobile)
- Barcode scanner: scan medicine barcode to look up stock
- Stock check: real-time availability, search by name/category
- Expiry alert: push notification for near-expiry medicines
- Quick sale: scan items, calculate total, print receipt (Bluetooth)
- Prescription capture: photo scan, OCR for medicine names
- Order supplier: reorder from known suppliers with one tap
- Daily sales summary: view today's takings
- Offline mode: cached medicine catalog, queue sales for sync

## 5. Data Model (MVP)

### Pharmacy
- id, name, address, phone, license_number, tax_number, settings (JSON), created_at

### Medicine
- id, pharmacy_id (FK), name_ar, name_en, category, generic_name, manufacturer, barcode, unit, price, requires_prescription, created_at

### Stock
- id, medicine_id (FK), batch_number, quantity, unit_price, purchase_price, expiry_date, received_date, created_at

### Prescription
- id, pharmacy_id (FK), patient_name, patient_phone, doctor_name, diagnosis, medicines (JSON: medicine_id, quantity, dosage), status (pending/dispensed/cancelled), scanned_image, notes, created_at

### Sale
- id, pharmacy_id (FK), items (JSON: medicine_id, quantity, price, subtotal), total, tax, grand_total, payment_method (cash/card/wallet), prescription_id (FK, nullable), created_at

### Supplier
- id, pharmacy_id (FK), name, contact_person, phone, email, address, payment_terms, created_at

### PurchaseOrder
- id, pharmacy_id (FK), supplier_id (FK), items (JSON: medicine_id, quantity, price), status (ordered/received/cancelled), total, ordered_at, received_at, created_at

### ExpiryAlert
- id, stock_id (FK), alert_type (30-days/7-days/expired), notified_at, acknowledged_at, created_at

### User
- id, name, email, password, role (owner/pharmacist/assistant/admin), pharmacy_id (FK), created_at

## 6. API Endpoints (MVP)

```
POST   /api/auth/login
GET    /api/auth/me

GET    /api/medicines
POST   /api/medicines
GET    /api/medicines/{id}
PUT    /api/medicines/{id}
DELETE /api/medicines/{id}
POST   /api/medicines/barcode-lookup

GET    /api/stock
POST   /api/stock/batch-add
GET    /api/stock/expiring-soon?days=30
GET    /api/stock/low-stock

GET    /api/prescriptions
POST   /api/prescriptions
GET    /api/prescriptions/{id}
PUT    /api/prescriptions/{id}/dispense

GET    /api/sales
POST   /api/sales
GET    /api/sales/today
GET    /api/sales/{id}

GET    /api/suppliers
POST   /api/suppliers
PUT    /api/suppliers/{id}

GET    /api/purchase-orders
POST   /api/purchase-orders
PUT    /api/purchase-orders/{id}/receive

GET    /api/expiry-alerts
PUT    /api/expiry-alerts/{id}/acknowledge

GET    /api/reports/sales?pharmacy_id=&from=&to=
GET    /api/reports/expiry-loss?pharmacy_id=&from=&to=
GET    /api/reports/stock-value?pharmacy_id=
```

## 7. User Interface (Screen List)

### Dashboard Screens (React)
1. Login
2. Dashboard - sales today, expiring alerts, low stock widgets, revenue chart
3. Medicine Catalog - searchable, filterable with barcode display
4. Add Medicine - form with barcode scanner (PWA)
5. Stock Overview - batch list, quantity adjustments
6. Expiry Tracker - table with 30/7/0 day alerts, batch details
7. Prescription Manager - photo view, dispense workflow
8. Sales Register - chronological list with payment methods
9. Supplier Directory - contacts, order history
10. Purchase Orders - create/receive/status tracking
11. Reports - sales analysis, expiry loss, top-selling medicines
12. Settings - pharmacy profile, tax, receipt, user management

### Mobile Screens (Flutter)
1. Splash -> Login
2. Dashboard - quick actions (scan, sale, stock check)
3. Barcode Scanner - full-screen camera, auto-detect
4. Medicine Detail - stock level, price, expiry dates
5. Quick Sale - scan items -> cart -> total -> payment
6. Prescription Scan - camera -> OCR -> dispense
7. Stock Check - search results with quantity and expiry
8. Expiry Alerts - list with acknowledge action
9. Supplier Quick Order - select supplier, add items, send
10. Daily Summary - today's sales, transactions count

### Screen Flow
Scan Barcode -> View Medicine -> Add to Sale -> Payment -> Receipt

## 8. Business Model

- **الباقة الأساسية**: $29/شهر (صيدلية واحدة، حتى 500 صنف)
- **الباقة الاحترافية**: $59/شهر (صيدلية واحدة، غير محدود الأصناف، تقارير)
- **باقة المؤسسات**: $119/شهر (حتى 5 صيدليات، تقارير موحدة، API)
- فترة تجربة مجانية: 14 يوماً
- MRR المستهدف لكل عميل: $29-$119

## 9. Implementation Plan

- Phase 1 (Weeks 1-2): Laravel API - Auth, Pharmacy/Medicine/Stock CRUD, roles
- Phase 2 (Weeks 3-4): Prescription management, sales processing, expiry alerts
- Phase 3 (Weeks 5-6): React Dashboard - Catalog, stock, expiry tracker, sales register
- Phase 4 (Weeks 7-8): Flutter App - Barcode scanner, quick sale, prescription capture
- Phase 5 (Weeks 9-10): Bluetooth receipt printing, OCR integration, deployment

## 10. Risk & Mitigation

- **مخاطرة تقنية**: دقة التعرف على الباركود والأدوية (خاصة الأدوية المحلية)
  - التخفيف: قاعدة بيانات أدوية محلية (Egyptian/MOH drug database)، إدخال يدوي كخيار احتياطي
- **مخاطرة تنظيمية**: تتبع الوصفات الطبية للمواد الخاضعة للرقابة
  - التخفيف: سجل تدقيق كامل، تقارير مخصصة للجهات الرقابية
- **مخاطرة سوقية**: صعوبة إقناع الصيادلة باستخدام نظام جديد
  - التخفيف: فترة تجريبية 30 يوماً، دعم تأهيل مجاني عبر واتساب
