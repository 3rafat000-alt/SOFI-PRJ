# PROTOTYPE SPEC — PharmaChain (SAAS-066)
> Owner: UI/UX Designer · Gate 2

## Screen: Dashboard (maps to Journey Stage: —)
- **Layout:** Top alert bar (expiring soon count, low stock count), stat cards (orders today, pending shipments, revenue), below quick-view table of recent orders
- **Components:** AlertBanner, StatCard, OrderTable, ExpiryCountdown, StockLevelBar
- **States:** Empty (first-time setup) | Loading (skeleton) | Error (data sync error) | Edge (zero expiring items — green success)
- **Key Interaction:** Click expiring count → opens expiring products list
- **Friction Resolved:** #1 (expiry visibility)

## Screen: Pharmacy Order Portal (maps to Journey Stage: تقديم الطلب)
- **Layout:** Top search bar with barcode scanner, below product catalog grid, bottom cart summary with submit button
- **Components:** SearchInput, BarcodeScannerButton, ProductCard, CartSummary, QuantityStepper, AlternateSuggestions
- **States:** Empty (no products found) | Loading (searching) | Error (scan failed) | Edge (product out of stock — show alternatives)
- **Key Interaction:** Scan barcode → auto-add to cart with last price
- **Friction Resolved:** #2 (phone ordering eliminated)

## Screen: Order Processing (maps to Journey Stage: مراجعة الطلب + التجهيز)
- **Layout:** Left panel order list (filterable by status), right panel order detail with pick list
- **Components:** OrderList, OrderDetailCard, PickListItem, BatchSelector, StatusDropdown, WarehouseSelect
- **States:** Empty (no orders) | Loading | Error | Edge (partial fulfilment — split shipment)
- **Key Interaction:** Scan bin location → confirm pick → auto-decrement stock
- **Friction Resolved:** #3 (warehouse stock visibility)

## Screen: Inventory & Expiry (maps to Journey Stage: —)
- **Layout:** Table with product name, batch/lot, expiry date (colour-coded: green >90d, yellow 30-90d, red <30d), stock qty
- **Components:** DataTable, ExpiryBar, ColourDot, FilterChips (all/expiring/expired), BatchDetailModal
- **States:** Empty (no products) | Loading | Error | Edge (over 5000 products — virtual scroll)
- **Key Interaction:** Sort by expiry date → see nearest expiring first → promote or discount
- **Friction Resolved:** #1 (FEFO enforcement)

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Button | Primary #0891B2, Secondary #059669 | hover/active/disabled/loading | |
| AlertBanner | Expiry/LowStock/OrderSLA | info/warning/critical/closed | Dismissible |
| ProductCard | InStock/LowStock/OutOfStock | default/selected/disabled | Barcode on hover |
| ExpiryBar | Horizontal bar, green→yellow→red | — | Percentage of remaining days |
| PickListItem | Pending/Picked/Verified | default/scanning/complete | Tap to scan location barcode |
| OrderTable | All/Pending/Processing/Shipped/Delivered | loading/empty/error | Row click → order detail |
| CartSummary | Collapsed/Expanded | empty/items/max-cart | Expand for item list |
| BatchDetail | Modal with batch info | open/closed | Lot, expiry, stock, cost price |
| StockLevelBar | Mini progress bar | green/yellow/red/empty | Threshold configurable |
