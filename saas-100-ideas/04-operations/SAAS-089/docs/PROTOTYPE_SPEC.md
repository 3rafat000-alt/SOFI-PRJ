# PROTOTYPE SPEC — SpareParts (SAAS-089)
> Owner: UI/UX Designer · Gate 2

## Screen: Smart Part Search (Journey Stage: Find Part)
- **Layout:** Search bar prominent top. Two modes: "By Part Number" (text input) or "By Vehicle" (step selector: Make → Model → Year → Engine → Category)
- **Components:** SearchToggle, PartNumberInput, VehicleSelector (4-step), ResultsList, PartCard, CompatibilityBadge
- **States:** Empty (search prompt) | Loading (searching) | Error (no results → "try different search") | Edge (multiple OEM numbers for same part)
- **Key Interaction:** Select vehicle → instantly show compatible categories → tap category → list parts
- **Friction Resolved:** #1 — fast accurate part lookup

## Screen: Sales POS (Journey Stage: Sell Part)
- **Location:** Cashier counter
- **Layout:** Left: barcode scanner / search. Center: cart items. Right: customer info + totals. Bottom: payment buttons
- **Components:** BarcodeScanner, CartItemRow, CustomerSelect, TotalSummary, PaymentMethod (Cash/Card/STC Pay/Apple Pay), ReceiptPrintButton
- **States:** Empty (scan first item) | Loading (processing payment) | Error (payment failed) | Edge (discount approval needed)
- **Key Interaction:** Scan barcode → adds to cart → shows stock remaining
- **Friction Resolved:** #3 — real-time stock deduction

## Screen: Purchase Order Management (Journey Stage: Order from Supplier)
- **Layout:** List of suppliers with price comparison per part → select supplier → create PO → track delivery
- **Components:** SupplierCard, PriceComparisonTable, PurchaseOrderForm, StatusTracker, DeliveryDateBadge
- **States:** Empty (no POs) | Loading (skeleton) | Error (supplier API down) | Edge (partial delivery)
- **Key Interaction:** Search part → see all suppliers with prices → select cheapest → create PO
- **Friction Resolved:** #2 — avoid duplicate stock

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Button | Primary (orange), Secondary, Ghost | hover/active/disabled/loading | Ripple, 8px radius |
| VehicleSelector | 4-step horizontal stepper | step1/2/3/4 completed | Each step filters next |
| PartCard | Image + number + name + stock + price | in-stock/low-stock/out-of-stock | Color coded stock dot |
| BarcodeScanner | Camera overlay | scanning/found/error | Beep + vibration |
| CartItemRow | Part + qty + price + remove | default/low-stock warning | Swipe to remove |
| SupplierCard | Name + lead time + price | default/selected/cheapest | Highlight cheapest |
| PriceComparisonTable | Part row per supplier | sorted by price | Color coded bars |
| StatusTracker | PO status pipeline | ordered→shipped→received | Step indicator |
