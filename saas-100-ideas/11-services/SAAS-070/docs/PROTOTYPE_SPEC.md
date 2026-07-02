# PROTOTYPE SPEC — RepairPro (SAAS-070)
> Owner: UI/UX Designer · Gate 2

## Screen: Repair Kanban Board (maps to Journey Stage: —)
- **Layout:** Horizontal columns: Received → Diagnosing → Parts Ordered → Repairing → QC → Ready → Delivered
- **Components:** KanbanColumn, DeviceCard, StatusCountBadge, PriorityFlag, TimerBadge
- **States:** Empty (no devices) | Loading | Error | Edge (device in same status > 3 days — red highlight)
- **Key Interaction:** Drag card between columns, auto-update status + notify customer
- **Friction Resolved:** #1, #2 (visual device tracking)

## Screen: Device Intake (maps to Journey Stage: استلام الجهاز)
- **Layout:** Form with customer search/create, device details (brand, model, serial, issue), barcode preview
- **Components:** CustomerSearch, DeviceForm, BarcodeLabelPreview, IssueDescription, PhotoCapture
- **States:** Empty | Loading (searching customer) | Error (duplicate serial) | Edge (customer has previous device in repair)
- **Key Interaction:** Scan serial number → auto-fill brand/model if known
- **Friction Resolved:** #2 (barcode tracking eliminates lost devices)

## Screen: Technician App — Task View (maps to Journey Stage: التشخيص + الإصلاح)
- **Layout:** Top strip — today's tasks count, below card list of assigned devices, tap to open detail
- **Components:** TaskCard, StatusToggle, DiagnosisForm, PartSelector, PhotoBeforeAfter, CustomerInfo
- **States:** Empty (no tasks) | Loading | Error | Edge (device on-site — GPS location)
- **Key Interaction:** Scan device barcode → opens repair form with history
- **Friction Resolved:** #4 (tasks distributed evenly)

## Screen: Inventory Management (maps to Journey Stage: —)
- **Layout:** Table with part name, SKU, stock, min threshold, compatible brands, last order date
- **Components:** PartsTable, StockLevelBar, ReorderButton, SupplierSelect, MovementLog
- **States:** Empty (no parts) | Loading | Error | Edge (stock = 0 — red + "order now" CTA)
- **Key Interaction:** Click part → movement history + compatible devices
- **Friction Resolved:** #3 (stock alerts prevent downtime)

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Button | Primary #475569, Secondary #EA580C | hover/active/disabled/loading | |
| DeviceCard | In-Progress/Waiting/Ready | default/dragging/overdue | Left border status colour |
| KanbanColumn | 7 status columns | drag-over highlight | Auto-scroll on drag edge |
| BarcodePreview | Code128, 40mm label | generated/printed | Label printer compatible |
| TaskCard | Priority (urgent/normal) | assigned/in-progress/complete | Photo attachment count |
| StockLevelBar | Green/Amber/Red/Empty | — | Threshold configurable |
| PartSelector | Search + category filter | loading/empty/no-results | Compatible device filter |
| PhotoCapture | Before/After | captured/uploading/error | Max 5 photos per repair |
| DiagnosisForm | Issue + notes + parts used | empty/editing/complete | Required fields validation |
