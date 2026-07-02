# PRD: InvoiceFlow (SAAS-022)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** منصة إصدار الفواتير الإلكترونية للشركات الصغيرة — قوالب احترافية، تتبع دفعات، تقارير ضريبية.
- **Problem:** أصحاب الأعمال الحرة والشركات الصغيرة يعانون من إعداد الفواتير يدوياً، أخطاء حسابية، تأخر في التحصيل، عدم توافق مع متطلبات هيئة الزكاة والضريبة.
- **Proposed solution:** Laravel API لإدارة الفواتير والعملاء والمدفوعات، React Dashboard لمحاسبة الشركات، Flutter App للعملاء والتوقيع المتنقل.

## 2. Market & Opportunity
- **Target market size:** سوق الفوترة الإلكترونية عالمياً ~$12B. في السعودية، متطلب ZATCA يدفع التبني.
- **Customer segment:** B2B (شركات صغيرة، محاسبون، فريلانسر).
- **Competitor landscape:** Zoho Invoice, FreshBooks, Wafeq, QuickBooks.
- **Differentiation:** توافق مع ZATCA، تسعير أقل 50%، دعم كامل للغة العربية والفواتير بالريال، تكامل مع الدفع المحلي.

## 3. User Personas
- **Primary 1 — صاحب عمل حر (نورة):** مصممة جرافيك تريد إنشاء فواتير احترافية لعملائها بسرعة.
- **Primary 2 — محاسب شركة (أحمد):** يدير فواتير 20 عميلاً، يريد تتبع المدفوعات والمتأخرات وتقارير ضريبية.
- **Admin — مدير منصة:** يراقب الاشتراكات، يدعم العملاء، يضبط الإعدادات.

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Invoice, InvoiceItem, Client, Payment, TaxReport, Company
- RESTful endpoints: CRUD invoices/clients/items, payment tracking
- Auth & roles: JWT, roles (admin, accountant, company_owner)
- Notifications: إيميل بتذكير الدفع، SMS للعملاء، إشعار باستلام الدفع
- Tax engine: حساب VAT تلقائي، تقارير ZATCA، فواتير ضريبية

### React Dashboard (Web)
- قوالب فواتير قابلة للتخصيص
- لوحة تحكم: إجمالي الفواتير، المدفوع، المتأخر
- إدارة العملاء مع سجل الفواتير
- تقارير ضريبية (VAT, ZATCA)
- إعدادات الشركة والشعار والبنود

### Flutter App (Mobile)
- إنشاء وإرسال فواتير سريع
- عرض حالة الدفع
- إشعارات الدفع المستلمة
- معاينة PDF للفاتورة
- إدارة العملاء الأساسية

## 5. Data Model (MVP)
- **Company:** id, name, logo, tax_number, cr_number, address, phone
- **Client:** id, company_id, name, email, phone, address, tax_number
- **Invoice:** id, company_id, client_id, number, date, due_date, subtotal, tax, total, status (draft/sent/paid/overdue/cancelled)
- **InvoiceItem:** id, invoice_id, description, quantity, unit_price, total
- **Payment:** id, invoice_id, amount, method, date, reference
- **TaxReport:** id, company_id, period, total_sales, total_vat, status

## 6. API Endpoints (MVP)
- `POST /auth/register`, `POST /auth/login`
- `GET /clients`, `POST /clients`, `PUT /clients/{id}`
- `GET /invoices`, `POST /invoices`, `GET /invoices/{id}`, `PATCH /invoices/{id}/status`
- `GET /invoices/{id}/pdf` (generate PDF)
- `POST /payments`, `GET /payments`
- `GET /reports/tax?period=2026-Q1`

## 7. User Interface (Screen List)
- **Dashboard:** بطاقات ملخص (فواتير هذا الشهر، مدفوع، متأخر)
- **Invoices:** جدول فواتير مع فلترة وحالة
- **Invoice Form:** محرر فواتير مع إضافة بنود وحساب تلقائي
- **Clients:** قائمة جهات اتصال
- **Reports:** تقارير ضريبية شهرية وربعية وسنوية
- **Mobile - Home:** آخر الفواتير مع إجراءات سريعة

## 8. Business Model
- **Pricing tiers:**
  - Starter (30 invoice/month): $19/شهر
  - Business (100 invoice/month): $49/شهر
  - Unlimited: $99/شهر
- **Free trial:** 14 يوم
- **Target MRR per client:** $19-$99

## 9. Implementation Plan
- Phase 1 (Weeks 1-2): Laravel API + Companies/Clients/Invoices CRUD
- Phase 2 (Weeks 3-4): React Dashboard + تقارير ضريبية + PDF generation
- Phase 3 (Weeks 5-6): Flutter App + إشعارات
- Phase 4 (Weeks 7-8): بوابة دفع، ZATCA compliance، اختبارات

## 10. Risk & Mitigation
- **Technical risk:** متطلبات ZATCA تتغير. → بناء tax engine قابل للتعديل.
- **Market risk:** منافسة QuickBooks. → التركيز على السوق السعودي والتسعير التنافسي.
- **Compliance risk:** عدم تطابق مع الفوترة الضريبية. → استشارة محامٍ ضريبي في المرحلة الأولى.
