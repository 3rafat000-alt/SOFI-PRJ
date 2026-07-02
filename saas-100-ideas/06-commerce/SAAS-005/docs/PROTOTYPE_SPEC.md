# PROTOTYPE SPEC — ShopPulse (SAAS-005)
> Owner: UI/UX Designer · Gate 2

## Screen 1: Store Connection (maps to: Connect)
- **Layout:** Platform selector (Shopify / WooCommerce), API credentials form with field-level help tooltips, Test connection button, Success/Error status indicator
- **Components:** Platform card (selectable), Input field with tooltip, Test button, Status badge (connected/disconnected)
- **States:**
  - Empty: No store connected yet
  - Loading: Testing connection spinner
  - Error: Connection failed → specific error + troubleshooting link
  - Edge: Reconnect existing store, switch platform
- **Key Interaction:** Select platform → enter credentials → tap "اختبار الاتصال" → success → sync starts
- **Friction Resolved:** #1 — guided connection wizard

## Screen 2: Dashboard KPI (maps to: Dashboard)
- **Layout:** Top: date range selector + store switcher, KPI cards row: Revenue, Orders, AOV, Conversion Rate (each with vs previous period %), Charts: revenue line chart (7d/30d), top products bar chart
- **Components:** Date picker, Dropdown (store switch), KPI metric card, Line chart (Recharts), Bar chart, Sparkline mini charts
- **States:**
  - Empty: "Connect a store to see your dashboard"
  - Loading: KPI skeleton + chart shimmer
  - Error: Data fetch error → retry + last cached data
  - Edge: Multi-store mode → aggregate or individual view
- **Key Interaction:** Tap KPI card → drill-down detail page
- **Friction Resolved:** #3 — customizable KPI layout

## Screen 3: Product Analytics (maps to: Products)
- **Layout:** Search/filter bar, Sortable table: Product name, SKU, Price, Units sold, Revenue, Profit margin, Stock, Status indicator, Product detail slide panel on row click
- **Components:** Data table (sortable, searchable, paginated), Product row, Profit margin badge (green/yellow/red), Status toggle (active/inactive)
- **States:**
  - Empty: "No products synced yet"
  - Loading: Table skeleton
  - Error: Sync delay warning
  - Edge: 500+ products → virtual scrolling + server-side pagination
- **Key Interaction:** Click column header → sort asc/desc → click row → slide panel detail
- **Friction Resolved:** #2 — profit margin visibility

## Screen 4: Inventory Monitor (maps to: Inventory → Alert)
- **Layout:** Inventory table: product name, current stock, minimum threshold, status (ok/low/critical), Reorder button, Alert configuration panel
- **Components:** Stock level bar (visual gauge), Status chip, Reorder button, Threshold input, Alert toggle
- **States:**
  - Empty: "No inventory data. Sync your store."
  - Loading: Table skeleton
  - Error: Stale data warning
  - Edge: Low stock → row highlighted red, Critical → pulsing alert
- **Key Interaction:** Tap "إعادة طلب" → reorder modal with supplier info
- **Friction Resolved:** #5 — low stock alerts via WhatsApp

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| KPI Card | Revenue, Orders, AOV, Conversion | default/hover/drill-down | icon, value, % change arrow |
| Line Chart | 7d, 30d, 90d | loading/empty/data | Recharts responsive |
| Data Table | Sortable, Filterable | loading/empty/data/error | server-side pagination |
| Stock Gauge | Bar, Dot | ok/low/critical | green→yellow→red gradient |
| Platform Card | Shopify, WooCommerce | default/selected/connected | logo + name + checkmark |
| Alert Config | Toggle row | enabled/disabled | WhatsApp/Email/In-app toggle |
| Profit Badge | Margin % | positive/negative/zero | green/red/gray pill |
