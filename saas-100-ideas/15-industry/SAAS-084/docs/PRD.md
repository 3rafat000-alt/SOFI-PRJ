# PRD: PrintHub (SAAS-084)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** منصة شاملة لإدارة المطابع تمكن أصحاب المطابع من استقبال طلبات الطباعة، إدارة التصميم، تنظيم التوصيل، وأتمتة الفوترة.
- **Problem statement:** تعتمد المطابع على استقبال الطلبات عبر الهاتف والواتساب مما يسبب أخطاء في التفاصيل، صعوبة تتبع حالة كل طلب، تأخير في التوصيل، ومشاكل في التحصيل والفوترة.
- **Proposed solution:** Laravel API + React Dashboard + Flutter App

## 2. Market & Opportunity
- **Target market:** المطابع التجارية (متوسطة وكبيرة)، دور النشر، مكاتب التصميم، شركات الدعاية والإعلان. سوق الطباعة في العالم العربي يتجاوز 15 مليار دولار.
- **Customer segment:** B2B
- **Competitor landscape:**
  1. **Printful** - منصة عالمية تطبع وتشحن لكن لا تدير المطابع المحلية
  2. **PressProof** - نظام إدارة مطابع أجنبي، مكلف، بدون دعم عربي
  3. **Printify** - تركز على الطباعة عند الطلب وليس إدارة المطبعة
  4. أنظمة محلية بدائية أو Excel
- **Differentiation:** حل متكامل يغطي دورة حياة الطلب بالكامل (استلام ← تصميم ← طباعة ← مراجعة ← توصيل ← فوترة) مع دعم اللغة العربية بالكامل وتكامل مع شركات التوصيل المحلية.

## 3. User Personas

### الشخصية الأساسية: صاحب مطبعة - سمير
- **الدور:** يمتلك مطبعة متوسطة مع 15 موظفاً
- **الأهداف:** تنظيم استقبال الطلبات، تقليل الأخطاء، تسريع دورة الإنتاج، تحسين التحصيل
- **نقاط الألم:** الطلبات تضيع بين الواتساب والهاتف، العملاء يغيرون المواصفات بعد البدء، تأخير الدفعات

### الشخصية الثانوية: مصمم جرافيك - ندى
- **الدور:** مصممة في المطبعة تستقبل طلبات التصميم من العملاء
- **الأهداف:** استلام ملفات التصميم بسهولة، إرسال المعاينات للعملاء، تتبع تعديلات العملاء
- **نقاط الألم:** إرسال الملفات عبر الإيميل يضيع، صعوبة تتبع التعديلات، العملاء لا يردون على المعاينات

### الشخصية الثالثة: عميل مطبعة - محمد
- **الدور:** صاحب شركة يحتاج خدمات طباعة دورية
- **الأهداف:** تقديم طلبات بسهولة، متابعة حالة الطلب، استلام الفواتير إلكترونياً
- **نقاط الألم:** لا يعرف حالة طلبه، يضطر للاتصال باستمرار، الفواتير الورقية تضيع

## 4. Features by Platform

### Laravel API (Backend)
- Core models: PrintShop, Order, OrderItem, DesignFile, Proof, ProductionTask, Delivery, Invoice, Customer
- RESTful endpoints
- Auth & roles: ShopOwner, Designer, Printer, DeliveryDriver, Customer
- Order workflow engine (pending → design → proof → approved → printing → finishing → delivery → completed)
- File upload and preview system (PDF, AI, PSD, CDR)
- Proof approval workflow with version tracking
- Production scheduling and load balancing across machines
- Delivery integration with local courier APIs
- Invoicing with partial payment tracking
- Notification system (email, SMS, push for order status changes)

### React Dashboard (Web)
- Order pipeline board (kanban by status)
- Customer management with order history
- Design file management with version control
- Proof review interface with annotation tools
- Production planning calendar
- Machine utilization dashboard
- Delivery tracking and route optimization
- Invoicing and payment tracking
- Reports (orders by type, revenue trends, machine utilization, customer segments)

### Flutter App (Mobile)
- Customer portal: submit new order, upload files, select specs
- Design proof viewer with approve/reject/comment
- Order status tracking with push notifications
- Delivery tracking with live driver location
- Payment processing (Mada, Apple Pay, STC Pay)
- Driver app: delivery list, navigation, proof of delivery with photo
- Printer app: production task list, mark status updates

## 5. Data Model (MVP)

- **User:** id, name, email, phone, role, shop_id
- **PrintShop:** id, name, address, machines, opening_hours, delivery_radius, settings
- **Customer:** id, name, company, phone, email, address, credit_limit, created_at
- **Order:** id, order_number, customer_id, shop_id, total_amount, status, payment_status, delivery_method, notes, created_at, due_date
- **OrderItem:** id, order_id, product_type (business_card/flyer/banner/book/packaging), quantity, paper_type, size, finishing_options, unit_price, notes
- **DesignFile:** id, order_item_id, file_name, file_path, file_type, version, uploaded_by, uploaded_at
- **Proof:** id, order_item_id, file_path, status (pending/approved/rejected), comment, reviewed_by, reviewed_at
- **ProductionTask:** id, order_item_id, machine_id, assigned_to, start_time, end_time, status, notes
- **Machine:** id, shop_id, name, type, hourly_rate, status, maintenance_schedule
- **Delivery:** id, order_id, driver_id, courier_name, tracking_number, status, estimated_arrival, proof_photo
- **Invoice:** id, order_id, amount, tax, total, due_date, paid_at, payment_method

## 6. API Endpoints (MVP)

- `POST /api/auth/login` - Login
- `POST /api/auth/register` - Register customer
- `GET /api/orders` - List orders (filtered by role)
- `POST /api/orders` - Create order
- `GET /api/orders/{id}` - Order detail with items
- `PUT /api/orders/{id}/status` - Update order status
- `POST /api/orders/{id}/items` - Add item to order
- `POST /api/orders/{id}/design-files` - Upload design file
- `GET /api/orders/{id}/proofs` - Get proofs
- `POST /api/proofs/{id}/approve` - Approve proof
- `POST /api/proofs/{id}/reject` - Reject proof with comment
- `GET /api/production-tasks` - Production queue
- `PUT /api/production-tasks/{id}` - Update task status
- `GET /api/deliveries` - Delivery list
- `POST /api/deliveries/{id}/status` - Update delivery status
- `GET /api/invoices` - Invoice list
- `POST /api/invoices/{id}/pay` - Record payment
- `GET /api/reports/dashboard` - Dashboard KPIs
- `GET /api/machines` - Machine list and status

## 7. User Interface (Screen List)

### Dashboard Screens (React)
1. Login / Register
2. Order Pipeline - kanban board by status
3. Order Detail - items, files, proofs, production timeline
4. Design Review - proof viewer with annotations
5. Production Planner - calendar + machine assignment
6. Machine Dashboard - utilization, status, maintenance
7. Customer Management - list, orders, credit
8. Delivery Tracking - orders by delivery status + map
9. Invoicing - unpaid, paid, overdue
10. Reports - revenue, orders, performance charts

### Mobile Screens (Flutter)
1. Customer Home - new order, track orders, my invoices
2. New Order Form - product type, specs, file upload
3. Order Tracker - status timeline with push updates
4. Proof Viewer - zoom, approve/reject, comment
5. Cart & Checkout - review items, pay
6. Driver Dashboard - pending deliveries, route, navigation
7. Driver Proof of Delivery - photo + signature capture
8. Printer Dashboard - today's tasks, mark complete
9. Notifications - order updates, proof pending

### Screen Flow
Customer submits Order → Designer reviews files, creates Proof → Customer approves → Production Print → Quality Check → Delivery → Invoice sent → Payment received

## 8. Business Model
- **Pricing tiers:** Starter $79/month (500 orders/year), Professional $179/month (2,000 orders), Enterprise $379/month (unlimited)
- **Free trial:** 14-day free trial, limited to 50 orders
- **Target MRR per client:** $79-$379
- **Additional revenue:** SMS notifications $0.03/message, design proof storage $9/month per 10GB, API access $49/month, delivery fee commission 2%

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2):** Laravel API - Auth, Orders, Customers, Design Files, Proofs CRUD
- **Phase 2 (Weeks 3-4):** React Dashboard - Order pipeline, proof review, production planner, customer management
- **Phase 3 (Weeks 5-6):** Flutter App - Customer portal, order submission, proof viewer, driver app
- **Phase 4 (Weeks 7-8):** Delivery integration, invoicing, reporting, notifications, testing, deploy

## 10. Risk & Mitigation
- **Technical risks:** File upload size and format handling → Mitigation: chunked upload, convert to PDF preview, cloud storage integration
- **Proof workflow:** Customer not responding to proofs → Mitigation: auto-reminders via SMS/email, auto-approve after 48h
- **Delivery dependency:** Integration with local couriers varies → Mitigation: flexible driver module, support multiple courier APIs
- **Adoption:** Print shop staff not tech-savvy → Mitigation: simple kanban UI, mobile-first for floor staff, training materials
- **Competition:** Large printing chains building internal solutions → Mitigation: target SMB print shops, affordable pricing, fast deployment
