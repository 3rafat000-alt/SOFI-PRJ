# PROTOTYPE SPEC — CableTV (SAAS-086)
> Owner: UI/UX Designer · Gate 2

## Screen: Provider Dashboard (Journey Stage: Monitor Provider)
- **Layout:** KPI bar (active subs, MRR, open tickets, collection rate), subscriber chart, ticket queue, outage map widget
- **Components:** KpiCard, LineChart, TicketQueueItem, OutageMap, QuickActionBar
- **States:** Empty (setup wizard) | Loading (skeleton) | Error (retry) | Edge (0 new subscribers → marketing tip)
- **Key Interaction:** Tap KPI → drill-down filtered view
- **Friction Resolved:** #1 — immediate visibility on all metrics

## Screen: Ticket Management (Journey Stage: Resolve Issues)
- **Layout:** Ticket list with filters (status/priority/technician), detail panel on right
- **Components:** TicketTable, DetailPanel, PriorityBadge, Timer (SLA), AssignDropdown
- **States:** Empty ("No open tickets — all clear") | Loading (skeleton rows) | Error (failed) | Edge (SLA breached highlighted red)
- **Key Interaction:** Click assign → select available technician → auto-notify
- **Friction Resolved:** #2 — faster ticket resolution

## Screen: Technician Mobile App (Journey Stage: Field Work)
- **Layout:** Today's tasks list → task detail with address, contact, issue description → navigation button → update status form → equipment scan → photo proof
- **Components:** TaskCard, NavigationButton, StatusStepper, BarcodeScanner, PhotoCapture, SignaturePad
- **States:** Empty ("No tasks today") | Loading (syncing) | Error (no signal → offline mode) | Edge (customer not home → reschedule flow)
- **Key Interaction:** Swipe task to start → auto-navigation → scan equipment serial → complete with photo
- **Friction Resolved:** #3 — real-time technician status

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Button | Primary (blue), Secondary, Ghost | hover/active/disabled/loading | Ripple, 8px radius |
| KpiCard | Value + label + trend | default/hover | Trend up/down arrow |
| TicketTable | Row with priority color | default/hover/selected | Click expands detail |
| DetailPanel | Slide-in from right | open/closing/loading | 400px width |
| PriorityBadge | Critical/High/Med/Low | red/orange/yellow/green | Pulse for critical |
| SLA Timer | Countdown to deadline | safe(w)/warning(y)/breached(r) | Animated as time passes |
| TaskCard | Address + status | pending/in-progress/done | Swipe actions |
| StatusStepper | assigned→en-route→repair→done | step highlighted | Back+forward |
