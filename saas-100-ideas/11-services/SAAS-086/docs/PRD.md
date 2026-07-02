# PRD: CableTV (SAAS-086)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** منصة متكاملة لإدارة اشتراكات التلفاز والإنترنت تمكن مزودي الخدمة من إدارة العملاء، الفواتير، والصيانة الفنية بكفاءة.
- **Problem statement:** يعاني مزودو خدمات الكيبل والإنترنت من صعوبة إدارة آلاف الاشتراكات، تأخير في معالجة شكاوى العملاء، ضعف تتبع فنيي الصيانة، ونسبة مرتفعة من الفواتير غير المحصلة.
- **Proposed solution:** Laravel API + React Dashboard + Flutter App

## 2. Market & Opportunity
- **Target market:** مزودو خدمات الإنترنت والكيبل المحليون، شركات الاتصالات الصغيرة، مزودو خدمات التلفاز المدفوع. سوق الاشتراكات في العالم العربي يتجاوز 50 مليون مشترك.
- **Customer segment:** B2B (service providers) + B2C (end subscribers via provider portal)
- **Competitor landscape:**
  1. **ISPWare** - نظام إدارة مزودي إنترنت لكنه معقد ومكلف
  2. **BillingPlatform** - حل فوترة عام، غير متخصص بالكيبل
  3. **Splynx** - نظام ISP جيد لكن تركيزه على الشبكات
  4. **أنظمة محلية** - بدائية تغطي الفوترة فقط
- **Differentiation:** حل متكامل خفيف وسهل للمزودين المحليين يغطي إدارة العملاء، الفوترة، الصيانة، تتبع الفنيين مع تطبيق جوال للمشتركين.

## 3. User Personas

### الشخصية الأساسية: مدير مزود إنترنت - سامي
- **الدور:** يدير شركة إنترنت محلية تخدم 3,000 مشترك
- **الأهداف:** إدارة الاشتراكات، تقليل الفواتير المتأخرة، تحسين سرعة الاستجابة للشكاوى
- **نقاط الألم:** صعوبة تتبع المشتركين النشطين، نسبة انقطاع عالية، الفنيون لا يبلغون عن إتمام المهام

### الشخصية الثانوية: فني صيانة - كريم
- **الدور:** فني تركيب وصيانة إنترنت وكيبل
- **الأهداف:** معرفة مهام اليوم، تحديث حالة التذكرة، تسجيل القطع المستخدمة
- **نقاط الألم:** يتلقى المهام عبر الهاتف، ينسى التفاصيل، لا يسجل القطع المستبدلة

### الشخصية الثالثة: مشترك - ريم
- **الدور:** عائلة لديها اشتراك إنترنت وتلفاز
- **الأهداف:** دفع الفاتورة، تقديم شكوى، معرفة موعد الفني
- **نقاط الألم:** خطوط الاتصال مشغولة، لا تعرف حالة الشكوى، الفاتورة تضيع

## 4. Features by Platform

### Laravel API (Backend)
- Core models: ServiceProvider, SubscriptionPlan, Subscriber, Invoice, Payment, Ticket, Technician, Inventory
- RESTful endpoints
- Auth & roles: ProviderAdmin, BillingStaff, Technician, Subscriber
- Subscription lifecycle (active/suspended/terminated/pending)
- Automated invoice generation with configurable billing cycles
- Payment gateway integration (Mada, Apple Pay, bank transfer)
- Ticket management with SLA tracking
- Technician assignment and real-time location tracking
- Inventory management (routers, cables, receivers, dishes)
- Notification engine (SMS, email, push for invoices, outages, appointments)
- CPE (Customer Premise Equipment) tracking

### React Dashboard (Web)
- Subscriber management with advanced search
- Billing dashboard (paid/unpaid/overdue invoices)
- Invoice lifecycle management (generate, send, remind, collect)
- Ticket management system with SLA monitoring
- Technician scheduling and route optimization
- Real-time outage map
- Inventory tracking (equipment by subscriber)
- Financial reports (MRR, churn, collection rate)
- Admin settings (plans, pricing, billing rules)

### Flutter App (Mobile)
- Subscriber app: view plan, pay invoices, submit tickets, track technician, usage stats
- Technician app: task list, navigation, ticket update, equipment scan, proof of work
- Push notifications: invoice due, payment confirmed, ticket update, technician en route
- Offline capability: download tasks before leaving office
- Digital invoice viewing and sharing

## 5. Data Model (MVP)

- **User:** id, name, email, phone, role, provider_id
- **ServiceProvider:** id, name, address, service_area, subscription_count, settings
- **SubscriptionPlan:** id, name, type (internet/tv/bundle), speed_mbps, channels_count, price, billing_cycle
- **Subscriber:** id, name, phone, email, address, lat, lng, plan_id, status, activation_date, cpe_device_id
- **Invoice:** id, subscriber_id, invoice_number, period_start, period_end, amount, tax, total, status, due_date, paid_at
- **Payment:** id, invoice_id, amount, method, transaction_id, collected_by, paid_at
- **Ticket:** id, subscriber_id, ticket_number, type, priority, status, description, assigned_technician_id, created_at, resolved_at, sla_deadline
- **Technician:** id, user_id, provider_id, specialization, service_area, is_available, current_location
- **Inventory (CPE):** id, type (router/receiver/dish/cable), serial_number, subscriber_id, status, purchase_date, warranty_expiry
- **Outage:** id, area, start_time, end_time, cause, affected_subscribers, status

## 6. API Endpoints (MVP)

- `POST /api/auth/login` - Multi-role login
- `GET /api/subscribers` - List with filters (status, plan, area)
- `POST /api/subscribers` - Add subscriber
- `PUT /api/subscribers/{id}` - Update subscriber/plan change
- `GET /api/invoices` - Invoice list (filtered by status)
- `POST /api/invoices/generate` - Generate batch invoices
- `GET /api/invoices/{id}` - Invoice detail
- `POST /api/payments` - Record payment
- `GET /api/tickets` - Ticket list (role-based)
- `POST /api/tickets` - Create ticket (subscriber)
- `PUT /api/tickets/{id}` - Update ticket status
- `POST /api/tickets/{id}/assign` - Assign technician
- `GET /api/technicians` - Technician availability
- `PUT /api/technicians/{id}/location` - Update location
- `GET /api/inventory` - CPE inventory
- `POST /api/inventory` - Register CPE device
- `GET /api/outages` - Outage list/map
- `POST /api/outages` - Report outage
- `GET /api/reports/mrr` - MRR report
- `GET /api/reports/churn` - Churn analysis

## 7. User Interface (Screen List)

### Dashboard Screens (React)
1. Login - role-based redirect
2. Provider Dashboard - active subs, MRR, open tickets, collection rate
3. Subscriber Management - table with filters, add/edit, plan change
4. Billing - invoice list, batch generate, payment reconciliation
5. Ticket System - queue view, detail, assign technician
6. Technician Map - live tracking, task assignment drag-drop
7. Outage Management - report, affected subs, broadcast message
8. Inventory - CPE tracking by subscriber
9. Plans & Pricing - manage subscription plans
10. Reports - MRR trends, churn rate, collection efficiency, technician performance

### Mobile Screens (Flutter)
1. Subscriber: Home → My Plan → Pay Bill → Tickets → Track Technician → Profile
2. Technician: Home → Today's Tasks → Navigation → Ticket Details → Update Status → Scan Equipment → Complete

### Screen Flow
Subscriber reports issue → Ticket created → Assigned to technician → Technician dispatched → Resolved → Invoice generated end of cycle → Payment collected

## 8. Business Model
- **Pricing tiers:** Basic $99/month (up to 500 subscribers), Professional $199/month (up to 2,000 subscribers), Enterprise $399/month (unlimited)
- **Free trial:** 14-day free trial, limited to 100 subscribers
- **Target MRR per client:** $99-$399
- **Additional revenue:** SMS notifications $0.03/message, technician app $10/month per technician, payment gateway commission 1.5%, equipment tracking add-on $29/month

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2):** Laravel API - Auth, Subscribers, Plans, Invoices CRUD + billing engine
- **Phase 2 (Weeks 3-4):** React Dashboard - Subscriber management, billing system, ticket management
- **Phase 3 (Weeks 5-6):** Flutter Apps - Subscriber portal (pay, tickets) + Technician app (tasks, navigation)
- **Phase 4 (Weeks 7-8):** Payment gateway, SMS notifications, inventory tracking, reporting, testing, deploy

## 10. Risk & Mitigation
- **Technical risks:** Billing cycle complexity (prorated charges, discounts, taxes) → Mitigation: configurable billing engine, test with multiple scenarios
- **Integration:** Payment gateway fragmentation across countries → Mitigation: support multiple gateways, pluggable payment architecture
- **Adoption:** Subscriber app download friction → Mitigation: WhatsApp bot for basic operations, QR code on invoice for quick download
- **Churn:** Providers may churn if system doesn't reduce operational load → Mitigation: focus on automation features (auto-invoice, batch SMS)
- **Competition:** ERP players adding ISP modules → Mitigation: deep ISP domain features (CPE tracking, outage map, technician dispatch)
