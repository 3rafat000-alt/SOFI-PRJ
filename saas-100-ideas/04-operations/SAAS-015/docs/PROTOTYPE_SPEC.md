# PROTOTYPE SPEC — LaundryHub (SAAS-015)
> Owner: UI/UX Designer · Gate 2

## Screen: Orders Dashboard (Stage: لوحة الطلبات)
- **Layout:** Top: status filter tabs (all/pending/in-progress/ready/delivered) + search; Main: order cards in kanban-style columns by status
- **Components:** StatusFilterTabs, OrderCard (order#, customer name, item count, status badge, time elapsed), SearchBar, KanbanColumn, StatRow (today's orders, pending, overdue), CreateOrderFAB
- **States:** Empty ("لا توجد طلبات") | Loading (skeleton cards) | Orders list | Error
- **Key Interaction:** Click order card → order detail; drag card between status columns to update; FAB → new order
- **Friction Resolved:** [#2] — real-time status visible to customer via shared link

## Screen: New Order / Item Entry (Stage: طلب جديد)
- **Layout:** Left: customer info form; Right: item entry area (add pieces one by one with auto-pricing)
- **Components:** CustomerSearch (phone autocomplete), CustomerForm, ItemRow (type dropdown, quantity, service: wash/dry/iron, unit price, subtotal), AddItemButton, PriceSummary, ScannerButton (scan barcode if repeat customer), NotesField
- **States:** Empty (no items) | Customer found (pre-filled) | New customer (manual) | Adding items | Submit success
- **Key Interaction:** Enter phone → customer data auto-fills; add item → auto-calculate price based on type+service; scan old barcode → duplicate previous order items
- **Friction Resolved:** [#4] — quick-add items with preset pricing, [#5] — price list pre-configured

## Screen: Production Tracking (Stage: مرحلة الغسيل)
- **Layout:** Queue-style view of orders by production stage (sorted: wash → dry → iron → fold → pack)
- **Components:** StageQueue (4 columns: washing, drying, ironing, packing), OrderStageCard (order#, items count, time in stage), WorkerAssignDropdown, TimerWidget (time per stage), DefectReportButton
- **States:** Empty (no orders) | Loading | Active | Defect flagged (red item)
- **Key Interaction:** Click order → advance to next stage; assign worker to stage batch; report defect → pause + notify customer
- **Friction Resolved:** [#1] — barcode scanning at each stage ensures no mixup

## Screen: Delivery Management (Stage: التوصيل)
- **Layout:** Map view of delivery zones + driver list sidebar + order queue per driver
- **Components:** MiniMap (with route polylines), DriverCard (name, vehicle, active orders, status), RouteOptimizerButton, OrderDeliveryCard (address, phone, time window), ProofOfDelivery (photo capture + signature)
- **States:** Empty (no deliveries) | Loading | Routes optimized | Driver assigned | Delivered | Failed delivery
- **Key Interaction:** Assign orders to driver → route auto-optimized; driver marks delivered → photo proof; failed → reschedule
- **Friction Resolved:** [#3] — route optimization clusters orders by area

## Screen: Customer Portal (for clients to track orders)
- **Layout:** Minimal card with order status timeline + items list + estimated delivery
- **Components:** StatusTimeline (vertical, stages with dates), ItemChecklist (each piece shown as checked when processed), DeliveryCountdown, ContactButton (WhatsApp link)
- **States:** Loading | Active (order in progress) | Ready for delivery | Delivered
- **Key Interaction:** Click WhatsApp → contact laundry directly; share status link
- **Friction Resolved:** [#2] — full transparency on order progress

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Button Primary | Default, Small | hover/active/disabled/loading | bg-#1565C0, white, 8px radius, 14px |
| Button Secondary | Default, Outline | hover/active/disabled | border-#FF8F00, text #FF8F00 |
| OrderCard | Kanban, List, Compact | normal/hover/dragging | status color left border, time elapsed badge |
| Input Field | Default, Search | focus/error/disabled | 12px padding, 8px radius |
| ItemRow | Editable, Read-only | default/editing | type + quantity + service + price + delete |
| StageQueue | Kanban column | normal/hover | 4 columns with order count header |
| DriverCard | Available, Busy, Offline | normal/selected | name, vehicle, active orders count, status dot |
| MiniMap | Route, Cluster | loading/error/data | order pins + driver route line |
| StatusTimeline | Vertical | completed/active/pending | dot + line + timestamp + label |
| ProofOfDelivery | Photo, Signature | capture/uploaded/verified | camera or file upload |
| DefectReport | Modal | report/reviewed/resolved | photo, description, action taken |
