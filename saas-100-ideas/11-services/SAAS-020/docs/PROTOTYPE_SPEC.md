# PROTOTYPE SPEC — CleanPro (SAAS-020)
> Owner: UI/UX Designer · Gate 2

## Screen: Operations Dashboard (Stage: لوحة العمليات)
- **Layout:** Map view (main, team GPS pins) + left sidebar (today's jobs list) + top stat bar (pending, in progress, completed, delayed)
- **Components:** LiveMap (team markers with status colors), JobCard (customer, service type, address, time, team name, status), StatBar, AlertPanel (delays, material shortages), QuickAssignFAB
- **States:** Empty (no jobs today) | Loading (skeleton map) | Active (jobs in progress) | Alerts | Error
- **Key Interaction:** Click team pin → team detail card (members, current job, ETA); click job card → job detail
- **Friction Resolved:** [#1] — real-time GPS tracking on map

## Screen: Job Detail / Team Assignment (Stage: تعيين فريق)
- **Layout:** Left: job details (customer, address, service type, time, notes); Right: available teams list sorted by distance
- **Components:** JobInfoCard, CustomerInfoCard, TeamListCard (team name, member count, vehicle, distance, current status), AssignButton, RescheduleButton, MapRoutePreview
- **States:** Loading teams | Teams available | No teams (show next ETA) | Assigned
- **Key Interaction:** Click team → preview route to job location; assign → notification sent to team leader
- **Friction Resolved:** [#1] — assign by proximity

## Screen: Cleaning Checklist (Team App, Stage: أداء الخدمة)
- **Layout:** Step-by-step checklist grouped by area (kitchen, bathroom, living room, bedroom) with photo evidence per step
- **Components:** AreaAccordion, TaskItem (checkbox with timer), PhotoCaptureButton (before/after per task), MaterialUsageInput (cleaning products used), ProblemReportButton, OverallProgressBar
- **States:** Not started | In progress (partial) | Completed | Problem reported | Photo missing
- **Key Interaction:** Check task → photo required (enforces before/after); complete all areas → submit for customer review
- **Friction Resolved:** [#2] — standardized checklist ensures consistent quality + photo proof

## Screen: Customer Portal / Booking (Customer-facing)
- **Layout:** Service selection card + date/time picker + address form + price summary + book button
- **Components:** ServiceCard (deep clean, regular, office, move-in/out), DatePicker, TimeSlotGrid, AddressAutoComplete (Google Maps), AddOnSelect (balcony, oven, fridge), PriceCalculator, BookButton, PaymentMethodSelect
- **States:** Browsing services | Selecting date/time | Entering address | Confirming booking | Booking success | Error
- **Key Interaction:** Select service → price shown; pick date → available slots highlighted; book → confirmation with ETA
- **Friction Resolved:** [#3] — self-service booking with instant confirmation

## Screen: Invoicing & Payments (Stage: الدفع والفاتورة)
- **Layout:** Invoice preview (service details, add-ons, materials, labour, VAT, total) + payment history
- **Components:** InvoicePreview, PaymentMethodSelect (cash, card, Mada, STC Pay, Apple Pay), SplitPaymentOption, SendInvoiceButton (WhatsApp/email), PaymentHistoryTable, RecurringInvoiceToggle (for contracts)
- **States:** Draft | Sent | Paid | Overdue | Partial
- **Key Interaction:** Send invoice via WhatsApp → customer pays link → status updates automatically
- **Friction Resolved:** [#4] — instant invoice sharing with pay link

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Button Primary | Default, Small | hover/active/disabled/loading | bg-#00897B, white, 8px radius, 14px |
| Button Secondary | Default, Outline | hover/active/disabled | border-#26A69A, text #26A69A |
| LiveMap | Full, Mini | loading/error/data/interactive | GPS pins with status color, clustering |
| JobCard | Active, Pending, Completed | normal/hover | status dot, time, address, team |
| TeamCard | Available, Busy, Offline | normal/hover/selected | member count, distance, vehicle icon |
| ChecklistItem | Task with checkbox + photo | pending/in-progress/complete/photo-required | camera opens automatically when unchecked |
| AreaAccordion | Collapsed, Expanded | default/completed/error | progress per area, photo count |
| ServiceCard | Icon + title + price + duration | normal/hover/selected | radio selection, price highlight |
| PhotoCapture | Before/After pair | capture/uploaded/verified | side-by-side preview |
| InvoicePreview | itemized with VAT | draft/sent/paid/overdue | share via WhatsApp, email, SMS |
| PaymentButton | Mada, STC Pay, Apple Pay | processing/success/error/extended | redirect to payment gateway |
| CustomerForm | Name + phone + address | empty/filled/validated | autocomplete from previous visits |
| Modal | Default | open/close | backdrop, ESC close |
