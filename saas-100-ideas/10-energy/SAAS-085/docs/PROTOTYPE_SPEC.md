# PROTOTYPE SPEC — GasDistribute (SAAS-085)
> Owner: UI/UX Designer · Gate 2

## Screen: Distributor Dashboard (Journey Stage: Monitor Operations)
- **Layout:** Top stats (filled cylinders, empty, in-transit, active orders), order queue, driver status cards, stock alert bar
- **Components:** StatCard, OrderQueueItem, DriverCard, StockGauge, AlertBanner
- **States:** Empty (setup flow) | Loading (skeleton) | Error (retry) | Edge (stock low → red alert)
- **Key Interaction:** Tap order → assign driver from dropdown
- **Friction Resolved:** #2 — centralised order management

## Screen: Cylinder Tracking (Journey Stage: Track Cylinders)
- **Layout:** Search by cylinder number → full lifecycle timeline + current location on mini map
- **Components:** SearchInput, Timeline, MiniMap, StatusBadge, BarcodeDisplay
- **States:** Empty (search prompt) | Loading (scanning) | Error (cylinder not found) | Edge (multiple locations)
- **Key Interaction:** Scan barcode → see full history
- **Friction Resolved:** #1 — every cylinder tracked

## Screen: Driver Delivery App (Journey Stage: Deliver)
- **Layout:** Today's manifest as list, tap order → navigate → on arrival: scan cylinder + take photo + confirm
- **Components:** ManifestList, NavigationButton, BarcodeScanner, PhotoCapture, ConfirmButton
- **States:** Empty (no deliveries today) | Loading (syncing manifest) | Error (GPS off) | Edge (customer not home → reschedule)
- **Key Interaction:** Scan cylinder barcode out of truck → deliver → scan at door → photo
- **Friction Resolved:** #4 — proof of delivery with photo

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Button | Primary (orange), Secondary, Ghost | hover/active/disabled/loading | Ripple, 8px radius |
| StatCard | Value + label | default/hover | Color coded by metric type |
| DriverCard | Avatar + name + status + load | available/busy/offline | Tap to assign order |
| BarcodeScanner | Full screen camera overlay | scanning/found/error | Auto-focus, beep on scan |
| Timeline | Vertical timeline with icons | completed/current/pending | Scrollable |
| StockGauge | Horizontal bar fill | safe(blue)/low(orange)/critical(red) | Animated fill |
| ManifestList | Driver's delivery list | pending/completed/rescheduled | Swipe to complete |
| PhotoCapture | Camera + gallery | empty/captured/uploading | Compress <500KB |
