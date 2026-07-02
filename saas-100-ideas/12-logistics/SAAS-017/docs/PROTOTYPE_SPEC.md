# PROTOTYPE SPEC — CargoNet (SAAS-017)
> Owner: UI/UX Designer · Gate 2

## Screen: Operations Dashboard (Stage: لوحة العمليات)
- **Layout:** Full-width map (main area) + left sidebar (active shipments list) + top stat bar (active, pending, delayed, delivered today)
- **Components:** LiveMap (GPS pins for drivers + shipment locations), ShipmentCard (shipment#, customer, driver, status, time elapsed), StatBar, AlertBanner (delays, breakdowns), QuickAssignButton
- **States:** Empty (no shipments) | Loading (map skeleton) | Active (vehicles moving) | Alert (breakdown/delay) | Error
- **Key Interaction:** Click driver pin → driver info card (contact, speed, route); click shipment card → shipment detail
- **Friction Resolved:** [#1] — nearest-driver algorithm highlighted on map

## Screen: New Shipment / Assign Driver (Stage: شحنة جديدة)
- **Layout:** Left: shipment form (customer, pickup, dropoff, weight, type); Right: driver availability list sorted by distance
- **Components:** CustomerAutoComplete, AddressInput (map picker), ShipmentTypeSelect, WeightInput, DriverListCard (name, vehicle, distance, availability now, rating), AssignButton, EstimatedPriceSummary
- **States:** Empty (form) | Loading drivers | Drivers found | No drivers (show ETA for next available) | Assigned
- **Key Interaction:** Enter pickup address → map shows nearby drivers; click driver → assign; customer notified automatically
- **Friction Resolved:** [#1] — intelligent assignment by proximity + availability

## Screen: Driver App (Driver-facing, Stage: نقل البضاعة)
- **Layout:** Simple card UI: big map with current location + route line + shipment cards below
- **Components:** NavigationCard (next stop, address, distance, time), StartTripButton, ArriveButton, ProofOfDelivery (photo + signature), StatusToggle (on way / arrived / delivered / problem), EmergencyContactButton
- **States:** Idle (no assignment) | En route to pickup | Arrived at pickup | En route to delivery | Delivered | Problem reported
- **Key Interaction:** Tap "Arrived" → GPS stamps location; tap "Delivered" → photo capture + signature prompt
- **Friction Resolved:** [#5] — simple 3-step flow per stop

## Screen: Fleet Maintenance (Stage: إدارة الأسطول)
- **Layout:** List of vehicles with health status + maintenance schedule calendar
- **Components:** VehicleCard (plate#, model, year, odometer, next service date, status), MaintenanceTimeline, ServiceRecordForm, CostTracker, AlertList (overdue services, upcoming)
- **States:** Empty (no vehicles) | Loading | All OK | Overdue alert (red) | Upcoming (yellow)
- **Key Interaction:** Click vehicle → service history; schedule service → auto-block driver calendar
- **Friction Resolved:** [#2] — automated maintenance reminders + calendar blocking

## Screen: Customer Tracking Portal (for clients)
- **Layout:** Minimal, mobile-friendly: map with shipment location + status timeline + estimated arrival
- **Components:** ShipmentMap (single pin moving), StatusTimeline (vertical, with timestamp per step), EstimatedArrivalCountdown, DriverContactButton (call/WhatsApp)
- **States:** Loading | In transit | Delivered | Problem (delayed)
- **Key Interaction:** Share tracking link via WhatsApp; call driver directly (masked number)
- **Friction Resolved:** [#3] — real-time tracking with driver contact

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Button Primary | Default, Small | hover/active/disabled/loading | bg-#1565C0, white, 8px radius, 14px |
| Button Secondary | Default, Outline | hover/active/disabled | border-#EF6C00, text #EF6C00 |
| LiveMap | Full, Compact | loading/error/data/interactive | Leaflet/Mapbox, GPS pins, clustering |
| ShipmentCard | Active, Pending, Delivered | normal/hover | status color dot, time elapsed, source-dest |
| DriverListCard | Available, Busy, Offline | normal/hover/selected | distance badge, rating stars, vehicle type |
| NavigationCard | Next Stop | en-route/arrived/completed | address, distance, big tap targets |
| VehicleCard | Health bar | normal/overdue/upcoming-due | odometer, next service, status color |
| ProofOfDelivery | Photo + Signature | capture/uploaded/verified | camera + canvas signature pad |
| StatusTimeline | Vertical | completed/active/pending | dot + line + timestamp |
| DriverContactBtn | Call, WhatsApp | normal | masked phone, one tap dial |
| MaintenanceForm | Service record | draft/completed | date, type, cost, odometer, notes |
| Modal | Default | open/close | backdrop, ESC close |
