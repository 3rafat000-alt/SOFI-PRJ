# PRD: ShopPulse (SAAS-005)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner**: لوحة تحليلات ذكية لأصحاب المتاجر الإلكترونية — تربط بـ WooCommerce و Shopify، تحلل المبيعات والمخزون وسلوك العملاء بتقارير عربية بصرية.
- **Problem statement**: أصحاب المتاجر الإلكترونية يغرقون في بيانات متفرقة بدون تحليلات مفهومة، يحتاجون رؤية موحدة لأداء متجرهم بالعربية.
- **Proposed solution**: Laravel API + React Dashboard + Flutter App — تكامل مع API المتاجر، لوحة معلومات شاملة، تنبيهات ذكية عن المخزون المنخفض وتراجع المبيعات.

## 2. Market & Opportunity
- **Target market size**: سوق تحليلات التجارة الإلكترونية ~$8B (2025)، الشرق الأوسط ~$500M نمو 20% CAGR.
- **Customer segment**: B2B — أصحاب متاجر WooCommerce/Shopify، تجار منتجات رقمية.
- **Competitor landscape**:
  1. **Google Analytics**: مجاني لكن معقد، غير موجه خصيصاً للمتاجر.
  2. **Triple Whale**: Shopify فقط، سعري ($79/month)، إنجليزي.
  3. **Lifetimely**: Shopify فقط، تحليلات ربحية لكن بدون دعم عربي.
  4. **Metorik**: WooCommerce، قوي لكن معقد للمبتدئين.
  5. **Polar Analytics**: شامل لكن سعره عالي ($99+).
- **Differentiation**: دعم كلا المنصتين (Woo + Shopify)، واجهة عربية بسيطة، تنبيهات واتساب، سعر مخفض للسوق العربي.

## 3. User Personas

### Primary: مريم — صاحبة متجر ملابس أونلاين (Shopify)
- **الدور**: تدير متجرها بنفسها، تبيع ملابس نسائية في الخليج.
- **الأهداف**: متابعة المبيعات اليومية، معرفة المنتجات الأكثر مبيعاً، تحسين الإعلانات.
- **نقاط الألم**: GA4 معقدة، لا تفهم المصطلحات الإنجليزية، تريد تقارير بسيطة.

### Secondary: فيصل — مالك متجر إلكترونيات (WooCommerce)
- **الدور**: يدير متجراً مع 500+ منتج، يتابع المخزون والمبيعات.
- **الأهداف**: تنبيهات عند انخفاض المخزون، تقارير أرباح صافية.
- **نقاط الألم**: إدارة المخزون يدوياً، صعوبة معرفة المنتجات الخاسرة.

### Admin: ليلى — مديرة تسويق إلكتروني
- **الدور**: تدير حسابات متاجر متعددة لعملاء.
- **الأهداف**: مقارنة أداء المتاجر، تصدير تقارير للعملاء.
- **نقاط الألم**: التبديل بين حسابات GA4 و Facebook Ads.

## 4. Features by Platform

### Laravel API (Backend)
- Core domain models: Store, Product, Order, Customer, InventorySnapshot, Report, Alert
- Integration connector: Shopify REST + GraphQL, WooCommerce REST API (OAuth)
- Data sync: scheduled jobs (cron) for order/product/inventory import
- Analytics engine: revenue, AOV, units sold, top products, customer cohort
- Inventory tracking: snapshot comparison, low-stock threshold alerts
- Alert engine: revenue drop, stockout, abnormal order cancellation rate
- Notifications: WhatsApp/email alerts for configurable triggers

### React Dashboard (Web)
- Overview: revenue card, orders, AOV, conversion rate (daily/weekly/monthly)
- Sales report: line chart, bar chart by product/category
- Product analytics: top sellers, low performers, profit margin
- Inventory dashboard: stock levels, low stock alerts, restock suggestions
- Customer insights: new vs returning, top customers, location map
- Integration settings: connect store, sync status, last sync time
- Alert configuration: set thresholds for notifications

### Flutter App (Mobile)
- Snapshot dashboard: today's revenue, orders count, top product
- Push notifications: daily digest, low stock alert, anomaly detected
- Quick glance: last 7 days chart (swipeable)
- Offline: last fetched data cached for offline viewing

## 5. Data Model (MVP)

| Entity | Fields | Relationships |
|---|---|---|
| Store | id, name, platform, api_key, api_secret, store_url, sync_status | hasMany Product, Order |
| Product | id, store_id, platform_id, title, price, cost, stock, category | belongsTo Store |
| Order | id, store_id, platform_id, customer_email, total, items_count, status, created_at | belongsTo Store |
| Customer | id, store_id, email, name, orders_count, total_spent, last_order_at | belongsTo Store |
| InventorySnapshot | id, store_id, product_id, stock, recorded_at | belongsTo Store/Product |
| Alert | id, store_id, type, metric, condition, enabled | belongsTo Store |
| Report | id, store_id, type, date_from, date_to, data (JSON) | belongsTo Store |

## 6. API Endpoints (MVP)

| Method | Endpoint | Description |
|---|---|---|
| POST | /api/stores/connect | Connect store (OAuth/API key) |
| GET | /api/stores/{id}/dashboard | Dashboard summary data |
| GET | /api/stores/{id}/sales | Sales report (query: period) |
| GET | /api/stores/{id}/products | Products analytics |
| GET | /api/stores/{id}/inventory | Inventory snapshot |
| GET | /api/stores/{id}/alerts | Configured alerts |
| POST | /api/stores/{id}/sync | Trigger manual sync |
| GET | /api/stores/{id}/export | Export report (CSV/PDF) |

## 7. User Interface (Screen List)

### Dashboard screens (React)
- Login → Store selector
- Dashboard: KPI cards (Revenue, Orders, AOV, Visitors) + 7-day sparkline
- Sales: line/bar charts with date range picker
- Products: sortable table (name, price, sold, revenue, margin)
- Inventory: stock levels with color indicators (green/yellow/red)
- Customers: top 10, new vs returning pie chart
- Alerts: rule builder (metric > threshold → notify via)
- Settings: sync schedule, store connection status

### Mobile screens (Flutter)
- Home: revenue + orders today, % change vs yesterday
- Detail: tap → full day breakdown
- Notifications: low stock, revenue drop, daily report
- Store switcher (if managing multiple)

### Screen flow (text)
```
Login → Store Select → Dashboard (KPI cards)
               ├── Sales → Chart (daily/weekly/monthly) → Export
               ├── Products → Sortable table → Product detail
               ├── Inventory → Stock list → Low stock alerts
               ├── Customers → Insights → Top customers
               └── Alerts → Rule builder → Save
```

## 8. Business Model
- **Starter**: $9/month — 1 store, daily sync, basic reports
- **Pro**: $19/month — 3 stores, hourly sync, inventory alerts, WhatsApp
- **Business**: $39/month — 10 stores, real-time sync, API access, priority support
- **Free trial**: 14-day Pro trial

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2)**: Laravel API — Store connector, Order/Product sync jobs, Analytics engine
- **Phase 2 (Weeks 3-4)**: React Dashboard — Dashboard KPI, Sales chart, Product table
- **Phase 3 (Weeks 5-6)**: Flutter App — Mobile dashboard, Push notifications, Store health
- **Phase 4 (Weeks 7-8)**: Alert engine, WhatsApp integration, Export, Testing

## 10. Risk & Mitigation
- **Technical**: API rate limits (Shopify/Woo) — strategy: queue-based syncing with backoff.
- **Market**: Store owners already use GA4 — strategy: lower price, Arabic-first, WhatsApp reports.
- **Data**: Sync latency — strategy: near-real-time via webhooks, hourly fallback.
