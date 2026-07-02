# PRD: ColdStorage (SAAS-087)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** نظام متقدم لإدارة مخازن التبريد والتجميد يتيح تتبع درجات الحرارة آنياً، إدارة المخزون، ومراقبة تواريخ الصلاحية.
- **Problem statement:** يعاني مشغلو مخازن التبريد من تلف المنتجات بسبب انقطاع التبريد دون إنذار، صعوبة تتبع تواريخ الصلاحية عبر آلاف الأصناف، عدم امتثال لمعايير السلامة الغذائية، وخسائر مالية كبيرة.
- **Proposed solution:** Laravel API + React Dashboard + Flutter App

## 2. Market & Opportunity
- **Target market:** مستودعات التبريد والتجميد، شركات الأغذية والمشروبات، محلات السوبرماركت، شركات اللحوم والأسماك، شركات الأدوية (التبريد الدوائي). سوق التبريد في العالم العربي يتجاوز 20 مليار دولار.
- **Customer segment:** B2B (logistics & food industry)
- **Competitor landscape:**
  1. **Sensitech** - تتبع درجة الحرارة للحاويات فقط، مكلف
  2. **Berlinger** - أنظمة مراقبة درجة حرارة سويسرية، سعر مرتفع
  3. **Monnit** - أجهزة استشعار عامة بدون نظام إدارة مخزون
  4. **Excel + أجهزة إنذار** - الطريقة التقليدية السائدة
- **Differentiation:** حل متكامل يجمع مراقبة درجة الحرارة الآنية (IoT) مع إدارة المخزون الكاملة وتتبع الصلاحية مع تطبيق جوال للفرق الميدانية.

## 3. User Personas

### الشخصية الأساسية: مدير مستودع تبريد - عبدالله
- **الدور:** يدير مستودع تبريد كبير تبلغ سعته 5,000 طن
- **الأهداف:** ضمان استمرارية التبريد، تقليل الفاقد، تحسين استغلال المساحة، الامتثال للمواصفات
- **نقاط الألم:** أعطال التبريد تكتشف متأخرة، الفاقد كبير، صعوبة معرفة المحتوى الدقيق للمستودع

### الشخصية الثانوية: مفتش جودة - نورة
- **الدور:** مسؤولة عن فحص جودة المنتجات المجمدة والمبردة
- **الأهداف:** مراجعة سجلات الحرارة، فحص العينات، إصدار شهادات المطابقة
- **نقاط الألم:** سجلات الحرارة يدوية ومبعثرة، صعوبة تتبع تاريخ المنتجات

### الشخصية الثالثة: مشتري شركة أغذية - فهد
- **الدور:** مسؤول مشتريات في شركة لحوم
- **الأهداف:** معرفة المخزون المتاح في المستودع، حجز مساحة تخزين، تتبع شحناته
- **نقاط الألم:** لا يعرف المساحة المتاحة، تأخير في استلام البضاعة، المستندات غير منظمة

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Warehouse, ColdRoom, TemperatureSensor, TemperatureLog, Product, StockBatch, StockMovement, Order, QualityCheck, Client
- RESTful endpoints
- Auth & roles: WarehouseAdmin, QualityInspector, Operator, Client
- Real-time temperature monitoring with configurable thresholds
- Automated alerts (SMS, email, push) when temperature deviates
- Inventory management with FIFO/FEFO tracking
- Barcode/QR code per pallet/batch
- Stock aging report with expiry alerts
- Cold storage space optimization and booking
- Integration with IoT temperature sensors (Modbus, MQTT)
- Temperature compliance reports for auditing
- Client portal for inventory visibility

### React Dashboard (Web)
- Real-time temperature dashboard with warehouse map
- Cold room status (temperature, humidity, compressor status)
- Inventory management with batch tracking
- Stock in/out with barcode scanning
- Expiry dashboard with color-coded alerts (green/yellow/red)
- Order management (storage booking, retrieval requests)
- Temperature history charts with trend analysis
- Quality check management and certificate generation
- Client portal with inventory visibility
- Reports (temperature compliance, stock aging, space utilization)

### Flutter App (Mobile)
- Real-time temperature monitoring with push alerts
- Barcode scanner for stock receipt and dispatch
- Inventory lookup (product, quantity, location, expiry)
- Stock movement recording (inbound, outbound, transfer between rooms)
- Photo capture for damage/quality documentation
- Temperature log review and acknowledgment
- Notifications: temperature breach, expiry warning, task assigned
- Offline mode for warehouse areas with poor connectivity

## 5. Data Model (MVP)

- **User:** id, name, email, phone, role, warehouse_id
- **Warehouse:** id, name, address, total_capacity_tons, temperature_range, client_count
- **ColdRoom:** id, warehouse_id, name, target_temp_c, target_humidity, capacity_tons, current_temp, current_humidity, status
- **TemperatureSensor:** id, cold_room_id, sensor_id, type (temp/humidity), location, battery_level, last_reading, status
- **TemperatureLog:** id, sensor_id, cold_room_id, temperature, humidity, recorded_at
- **Product:** id, name, category, requires_freezing, min_temp, max_temp, shelf_life_days
- **StockBatch:** id, product_id, cold_room_id, batch_number, quantity_kg, received_date, expiry_date, supplier, location_in_room, barcode
- **StockMovement:** id, stock_batch_id, type (in/out/transfer), quantity_kg, reference_document, operator_id, moved_at
- **Client:** id, name, company, phone, email, contract_start, storage_rate_per_kg
- **Order:** id, client_id, type (storage_in/retrieval), stock_batch_id, quantity, status, requested_date, completed_date
- **QualityCheck:** id, stock_batch_id, inspector_id, check_date, temperature_at_check, condition, result (pass/fail), certificate_number

## 6. API Endpoints (MVP)

- `POST /api/auth/login` - Login
- `GET /api/warehouse/dashboard` - Real-time dashboard
- `GET /api/cold-rooms` - Cold room status
- `GET /api/cold-rooms/{id}/temperature` - Temperature history
- `POST /api/cold-rooms/{id}/alerts` - Configure alert thresholds
- `POST /api/sensors/reading` - IoT sensor data ingestion
- `GET /api/products` - Product catalog
- `POST /api/products` - Add product type
- `GET /api/stock-batches` - Inventory with filter by expiry
- `POST /api/stock-batches` - Record inbound stock
- `PUT /api/stock-batches/{id}/location` - Update storage location
- `POST /api/stock-movements` - Record movement
- `GET /api/stock-batches/expiring` - Soon-to-expire list
- `GET /api/clients` - Client list
- `POST /api/orders` - Create storage/release order
- `GET /api/quality-checks` - Quality check list
- `POST /api/quality-checks` - Record QC result
- `GET /api/reports/temperature-compliance` - Compliance report
- `GET /api/reports/stock-aging` - Aging report
- `GET /api/reports/space-utilization` - Space usage report

## 7. User Interface (Screen List)

### Dashboard Screens (React)
1. Login
2. Live Dashboard - warehouse overview with temperature gauges
3. Cold Room Monitor - detail per room with chart
4. Temperature Alerts - active/acknowledged/resolved
5. Inventory View - product catalog, stock by room, expiry heatmap
6. Stock In/Out - barcode scanning flow
7. Client Management - accounts, contracts, usage
8. Order Management - storage requests pipeline
9. Quality Check - inspection form with certificate generation
10. Reports - compliance, aging, space utilization, client billing

### Mobile Screens (Flutter)
1. Home - quick status (all rooms temp), alert count
2. Temperature View - per room graph, acknowledge alerts
3. Barcode Scanner - scan pallet → show product, expiry, location
4. Stock Receipt - scan → confirm quantity → assign location
5. Stock Dispatch - scan → confirm → remove from inventory
6. Quality Check - select batch → inspect → photo → result
7. Notifications - critical alerts push
8. Profile / Settings

### Screen Flow
Sensor → Cloud → Alert if breach → Dashboard shows + Notification sent → Operator acknowledges → Quality check if needed → Report generated

## 8. Business Model
- **Pricing tiers:** Basic $149/month (up to 5 cold rooms, manual temp logging), Professional $349/month (up to 20 cold rooms, IoT sensors), Enterprise $749/month (unlimited, API access)
- **Free trial:** 14-day free trial, limited to 2 cold rooms
- **Target MRR per client:** $149-$749
- **Additional revenue:** IoT sensor kit (gateway + 5 sensors) $399 one-time, installation fee $299, SMS alerts $0.03/message, compliance report certification $49/month, client portal white-label $99/month

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2):** Laravel API - Auth, Warehouses, ColdRooms, Products, StockBatch CRUD
- **Phase 2 (Weeks 3-4):** React Dashboard - Temperature monitoring, inventory management, expiry tracking
- **Phase 3 (Weeks 5-6):** Flutter App - Barcode scanning, stock movement, temp alerts, quality checks
- **Phase 4 (Weeks 7-8):** IoT sensor integration (MQTT), alert engine, reporting, client portal, testing, deploy

## 10. Risk & Mitigation
- **Technical risks:** IoT sensor connectivity in metal cold rooms → Mitigation: support multiple protocols (WiFi, LoRa, cellular gateway)
- **Data accuracy:** Temperature readings at single point may not represent whole room → Mitigation: multiple sensors per room, map of sensor locations
- **Power outage:** No internet during outage → Mitigation: local data buffering on gateway, battery backup for sensors
- **Adoption:** Cold storage operators skeptical of cloud systems → Mitigation: on-premise option for critical operations, proven reliability track record
- **Compliance:** Different countries have different cold chain regulations → Mitigation: configurable compliance templates, local regulation library
