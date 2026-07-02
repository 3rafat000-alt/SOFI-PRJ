# PRD: TextilePro (SAAS-083)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** نظام متكامل لإدارة مصانع النسيج يغطي سلسلة القيمة من استلام الخامات إلى إدارة طلبيات التصدير.
- **Problem statement:** تعاني مصانع النسيج من ضعف تتبع المخزون الخام، صعوبة إدارة مراحل الإنتاج المتعددة (الغزل، النسيج، الصباغة، التجهيز)، عدم دقة تقدير التكاليف، وتأخير في تنفيذ طلبيات التصدير.
- **Proposed solution:** Laravel API + React Dashboard + Flutter App

## 2. Market & Opportunity
- **Target market:** مصانع النسيج المتوسطة (20-200 عامل)، تجار الأقمشة بالجملة، مصانع الملابس الجاهزة. السوق العربي من أكبر أسواق النسيج عالمياً بقيمة تتجاوز 50 مليار دولار.
- **Customer segment:** B2B / Manufacturing
- **Competitor landscape:**
  1. **ERPNext** - مفتوح المصدر لكن يحتاج تخصيص كبير لقطاع النسيج
  2. **SAP Textile** - حل قوي لكن مكلف جداً للمصانع المتوسطة
  3. **Infor** - حل متخصص معقد ويتطلب استشارات باهظة
  4. **أنظمة محلية** - محدودة تغطي جانب المحاسبة فقط
- **Differentiation:** حل متخصص في النسيج بسعر مناسب للمصنع المتوسط، يدير دورة الإنتاج الكاملة (خام ← منتج نهائي ← تصدير) مع دعم التكامل مع ماكينات الإنتاج.

## 3. User Personas

### الشخصية الأساسية: مدير مصنع نسيج - حسن
- **الدور:** يدير مصنع نسيج متوسط يضم 80 عاملاً
- **الأهداف:** تتبع الإنتاج بدقة، خفض الهدر، تحسين الجدولة، زيادة الصادرات
- **نقاط الألم:** عدم وضوح مراحل الإنتاج، صعوبة تحديد أسباب التأخير، هدر الخامات دون رقابة

### الشخصية الثانوية: تاجر أقمشة - ليلى
- **الدور:** تستورد وتوزع الأقمشة بالجملة للأسواق والمصانع
- **الأهداف:** متابعة الطلبيات، معرفة حالة الإنتاج، طلب عينات
- **نقاط الألم:** تأخير التوصيل، عدم الشفافية في حالة الطلب، صعوبة التواصل مع المصنع

### الشخصية الإدارية: مشرف الإنتاج - خالد
- **الدور:** يشرف على مراحل الإنتاج اليومية
- **الأهداف:** جدولة الماكينات، تتبع العمالة، مراقبة الجودة
- **نقاط الألم:** كثرة الأعطال، صعوبة تخصيص المهام، نقص التقارير الفورية

## 4. Features by Platform

### Laravel API (Backend)
- Core models: RawMaterial, Supplier, ProductionOrder, ProductionStage, Machine, QualityCheck, FinishedProduct, ExportOrder
- RESTful endpoints
- Auth & roles: FactoryOwner, ProductionManager, QualityInspector, SalesTeam, Customer
- Inventory tracking with batch/lot numbers
- Production order routing through stages (spinning, weaving, dyeing, finishing)
- Quality check workflow with pass/fail thresholds
- Export documentation (certificate of origin, invoice, packing list)
- Costing engine (raw material + labor + overhead per meter)
- Machine maintenance scheduling

### React Dashboard (Web)
- Production dashboard with real-time KPIs (efficiency, downtime, output)
- Inventory management (raw materials, WIP, finished goods)
- Production order creation and routing
- Stage-by-stage production tracking with Gantt chart
- Quality control dashboard with defect analysis
- Machine monitoring and maintenance calendar
- Supplier and purchase order management
- Sales order management (domestic + export)
- Financial reports (cost per meter, profit margin, P&L)

### Flutter App (Mobile)
- Production floor view: machine status, operator assignments
- Quality inspection form with photo capture
- Barcode scanning for material receipt and dispatch
- Real-time production alerts (machine stoppage, quality failure)
- Shift reporting (production output per operator)
- Inventory lookup with stock levels
- Notification center for order updates

## 5. Data Model (MVP)

- **User:** id, name, email, phone, role, factory_id
- **Factory:** id, name, address, machines_count, capacity_meters_day
- **RawMaterial:** id, name, type (cotton/polyester/blend), supplier_id, stock_kg, unit_cost, reorder_level
- **Supplier:** id, name, contact_person, phone, email, address, payment_terms
- **ProductionOrder:** id, order_number, customer_id, fabric_type, quantity_meters, start_date, due_date, status, priority
- **ProductionStage:** id, production_order_id, stage (spinning/weaving/dyeing/finishing), machine_id, operator_id, start_time, end_time, output_kg, waste_kg, status
- **Machine:** id, factory_id, name, type, status (running/idle/maintenance), hourly_capacity, operator_id
- **QualityCheck:** id, production_stage_id, inspector_id, check_date, parameter, value, pass_fail, notes
- **FinishedProduct:** id, production_order_id, fabric_type, grade, quantity_meters, batch_number, warehouse_location
- **ExportOrder:** id, customer_id, items, total_value_usd, incoterm, port_of_loading, eta, documents_status
- **Customer:** id, name, company, phone, email, address, country, type (local/export)

## 6. API Endpoints (MVP)

- `POST /api/auth/login` - Login
- `GET /api/factory/dashboard` - Factory KPIs
- `GET /api/raw-materials` - Material inventory
- `POST /api/raw-materials` - Add material receipt
- `PUT /api/raw-materials/{id}` - Update stock
- `GET /api/suppliers` - Supplier list
- `POST /api/suppliers` - Add supplier
- `GET /api/production-orders` - List orders
- `POST /api/production-orders` - Create order
- `GET /api/production-orders/{id}` - Order detail with stages
- `POST /api/production-stages` - Record stage completion
- `GET /api/machines` - Machine status overview
- `POST /api/machines/{id}/status` - Update machine status
- `POST /api/quality-checks` - Record quality check
- `GET /api/quality-checks?order_id=X` - Quality results
- `GET /api/finished-products` - Finished goods inventory
- `POST /api/export-orders` - Create export order
- `GET /api/export-orders/{id}` - Export documents
- `GET /api/reports/production` - Production report
- `GET /api/reports/costing` - Cost per meter report

## 7. User Interface (Screen List)

### Dashboard Screens (React)
1. Login / Role selection
2. Factory Overview - KPIs (output today, efficiency, downtime)
3. Production Orders - list with Gantt chart view
4. Stage Tracking - visual pipeline with bottlenecks
5. Inventory - raw materials, WIP, finished goods with alerts
6. Machine Monitor - live status cards per machine
7. Quality Dashboard - pass/fail rates, defect Pareto chart
8. Supplier Management - purchase orders, lead times
9. Sales Orders - domestic + export pipeline
10. Reports - production, costing, export documentation

### Mobile Screens (Flutter)
1. Home - shift overview + quick actions
2. Stage Scan - barcode scan to record stage completion
3. Machine Status - tap to report running/idle/breakdown
4. Quality Form - parameter input + photo
5. Material Receipt - scan + confirm quantities
6. Alerts - machine stoppage, low stock, quality flags
7. Shift Report - my production output today

### Screen Flow
Login → Factory Dashboard → Production Order → Stages → Quality → Finish → Ship

## 8. Business Model
- **Pricing tiers:** Basic $149/month (up to 5 machines), Professional $349/month (up to 20 machines), Enterprise $749/month (unlimited)
- **Free trial:** 14-day free trial, limited to 3 machines
- **Target MRR per client:** $149-$749
- **Additional revenue:** Implementation fee $1,000, training $500/day, IoT machine monitoring add-on $49/month per machine, export documentation automation $99/month

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2):** Laravel API - Factory, Materials, Production Orders, Machines CRUD + auth
- **Phase 2 (Weeks 3-4):** React Dashboard - Production tracking, machine monitoring, inventory management
- **Phase 3 (Weeks 5-6):** Flutter App - Floor operations, barcode scanning, quality checks
- **Phase 4 (Weeks 7-8):** Export docs, costing engine, reporting, testing, deployment

## 10. Risk & Mitigation
- **Technical risks:** Machine integration complexity → Mitigation: start with manual status updates, API-first for future IoT
- **Data accuracy:** Real-time production data depends on operator input → Mitigation: barcode scanning for each stage, supervisor verification
- **Market risk:** Factory managers prefer paper-based tracking → Mitigation: print-friendly reports, gradual digitization path
- **Costing complexity:** Accurate cost per meter requires precise data → Mitigation: configurable overhead allocation formulas
- **Competition:** ERP players expanding into textile → Mitigation: deep textile domain expertise, faster implementation, better support
