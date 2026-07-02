# PROTOTYPE SPEC — ColdStorage (SAAS-087)
> Owner: UI/UX Designer · Gate 2

## Screen: Temperature Dashboard (Journey Stage: Monitor Cold Rooms)
- **Layout:** Grid of cold room cards, each showing name, target vs current temp, humidity, status indicator. Real-time gauge per room.
- **Components:** ColdRoomCard, TempGauge, StatusDot, AlertThreshold, HistorySparkline
- **States:** Empty (add first cold room) | Loading (skeleton gauges) | Error (sensor disconnected) | Edge (temp breach → card border red + pulse)
- **Key Interaction:** Tap card → detailed chart view
- **Friction Resolved:** #1 — real-time temperature visibility

## Screen: Expiry Dashboard (Journey Stage: Monitor Expiry)
- **Layout:** Calendar heatmap view by expiry date, product list sorted by soonest expiry, color coded (green >30d, yellow 7-30d, red <7d)
- **Components:** ExpiryHeatmap, ProductRow, ColorDot, DaysRemaining, AlertBanner
- **States:** Empty (no products) | Loading (skeleton) | Error (failed load) | Edge (red items highlighted)
- **Key Interaction:** Tap product → see batch details and location
- **Friction Resolved:** #2 — never miss expiry dates

## Screen: Stock Receipt Flow (Journey Stage: Receive Stock)
- **Layout:** Barcode scanner first → shows product info → enter quantity → select cold room → confirm location → print label
- **Components:** BarcodeScanner, ProductInfoCard, QuantityInput, RoomSelector, LocationPicker, LabelPrintButton
- **States:** Empty (scan prompt) | Loading (lookup) | Error (product not found) | Edge (room full → suggest alternative)
- **Key Interaction:** Scan pallet → auto-fills product → tap location on room map
- **Friction Resolved:** #3 — fast stock receipt

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Button | Primary (teal), Secondary, Ghost | hover/active/disabled/loading | Ripple, 8px radius |
| ColdRoomCard | Gauge + status | normal/warning/critical | Card border color changes |
| TempGauge | Arc gauge 0-50°C | safe:blue / warning:orange / critical:red | Needle sweep animation |
| ExpiryHeatmap | Calendar grid | green/yellow/red cells | Tap cell shows products |
| ProductRow | Name + batch + expiry + location | default/hover/expiring-soon | Swipe for actions |
| BarcodeScanner | Camera overlay | scanning/found/error | Auto-focus, beep |
| RoomSelector | Grid of room cards | available/full/maintenance | Tap to select |
| LocationPicker | Mini grid map of room | selectable zones | Zoom in/out |
