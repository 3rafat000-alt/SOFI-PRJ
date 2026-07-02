# PROTOTYPE SPEC — InventoryPro (SAAS-035)
> Owner: UI/UX Designer · Gate 2

## Screen: Inventory Dashboard (maps to Journey Stage: Monitor)
- **Layout:** Top KPI bar (total products, low stock count, stock value, pending POs) + alert feed + recent movements
- **Components:** Stat card, alert list, movement timeline, search bar
- **States:** Empty (first setup) | Loading (data fetch) | Error | Edge case (20k products → search + pagination)
- **Key Interaction:** Click low stock alert → filter product list by that alert
- **Friction Resolved:** #2 — رؤية المخزون والتنبيهات في مكان واحد

## Screen: Barcode Scanner / Stock Count (maps to Journey Stage: Scan → Stock In)
- **Layout:** Full-screen camera viewfinder with overlay — scan result card below
- **Components:** Camera viewfinder, product result card (name, SKU, current stock, last price), quantity input
- **States:** Camera idle | Scanning | Success (product found) | Error (not found → create product) | Edge case (bulk scan mode)
- **Key Interaction:** Point camera at barcode → vibrate on success → show product → enter qty → confirm
- **Friction Resolved:** #1 — مسح باركود سريع

## Screen: Purchase Order (maps to Journey Stage: Reorder → Supplier)
- **Layout:** Form with product search + line items table + supplier dropdown + totals
- **Components:** Product search (type to search, auto-suggest), line item row, supplier selector, date picker
- **States:** Empty (new PO) | Loading (saving) | Error (supplier not found) | Edge case (50+ line items)
- **Key Interaction:** Type product name → select from suggestions → auto-fill last price → adjust qty → add to PO
- **Friction Resolved:** #4 — إدارة الموردين والمشتريات

## Screen: Alerts Panel (maps to Journey Stage: Alert)
- **Layout:** Filtered list of all active alerts — low stock, out of stock, expiring soon
- **Components:** Alert card (product image, name, type badge, threshold, days left), filter tabs
- **States:** Empty (all good) | Loading | Error | Edge case (200+ alerts → grouped by type)
- **Key Interaction:** Click alert → navigate to product detail
- **Friction Resolved:** #3 — تنبيهات انتهاء الصلاحية ونفاد المخزون

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Stat Card | Products, Low Stock, Value, POs | default/hover | icon + number + trend arrow |
| Product Row | Grid/Table | default/hover/selected | image + SKU + name + stock qty + price |
| Barcode Viewfinder | Camera overlay | idle/scanning/success/error | scan line animation, flash on success |
| Alert Card | Low Stock, Out of Stock, Expiring | default/hover | color-coded: amber/red/orange |
| Line Item | PO item row | default/editing | product name, qty, unit price, total |
| Supplier Select | Dropdown with search | closed/loading/open/selected | searchable, rating, last order date |
| Stock Movement | In/Out/Transfer/Adjustment | default | icon + product + qty + timestamp + user |
| Product Quick View | Modal card | open/loading | image, stock across warehouses, price |
