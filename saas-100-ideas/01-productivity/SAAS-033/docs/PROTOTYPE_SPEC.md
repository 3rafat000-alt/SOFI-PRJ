# PROTOTYPE SPEC — SupportDesk (SAAS-033)
> Owner: UI/UX Designer · Gate 2

## Screen: Ticket Queue (maps to Journey Stage: Intake → Classify)
- **Layout:** Table/list view with filter bar top — status, priority, assignee, category, search
- **Components:** Filter chips, ticket row (ID, title, priority badge, status, assignee avatar, SLA timer), pagination
- **States:** Empty (no tickets) | Loading (skeleton rows) | Error (fetch failed) | Edge case (1000+ tickets → server-side pagination + search)
- **Key Interaction:** Click ticket row → opens ticket detail in split view
- **Friction Resolved:** #1 — رؤية جميع التذاكر في مكان واحد مع فلترة

## Screen: Ticket Detail (maps to Journey Stage: Agent Work → Resolve)
- **Layout:** 3-column — left (conversation thread), center (ticket info + activity), right (knowledge base suggestions)
- **Components:** Chat bubble (agent/client), canned response picker, file attachment, status dropdown, SLA countdown
- **States:** Loading (messages) | Error (send failed) | Edge case (100+ msg → paginated / virtual scroll)
- **Key Interaction:** Type @ → auto-suggest canned responses → click to insert
- **Friction Resolved:** #2 — ردود جاهزة + قاعدة معرفة

## Screen: Agent Status Board (maps to Journey Stage: Auto-assign)
- **Layout:** Grid of agent cards with status indicator, current load, active tickets
- **Components:** Agent card (avatar, name, status dot, active tickets count, online time), filter by team
- **States:** Loading | Error | Edge case (50+ agents → scroll + search)
- **Key Interaction:** Click agent → view their active tickets
- **Friction Resolved:** #3 — متابعة أداء الفريق

## Screen: Analytics Dashboard (maps to Journey Stage: Close → Goal)
- **Layout:** KPI row (tickets today, avg response time, CSAT, resolution rate) + trend charts
- **Components:** Line chart (tickets over time), bar chart (by category), CSAT gauge, SLA compliance pie
- **States:** Empty (no data) | Loading | Error | Edge case (date range filter)
- **Key Interaction:** Drag date range → charts update
- **Friction Resolved:** #5 — تقارير آلية

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Ticket Row | Default, Urgent, Overdue, Resolved | default/hover/selected | avatar + title + priority + timer |
| Filter Chip | Status, Priority, Assignee, Category | default/selected | click to toggle, multiple select |
| Chat Bubble | Agent (blue), Client (gray), Internal (yellow) | default/hover | timestamp, read receipt |
| Canned Response List | By category | collapsed/expanded/selected | searchable, preview on hover |
| Status Badge | Open, In Progress, Resolved, Closed | static | color-coded: blue/amber/green/gray |
| Agent Card | Online, Away, Busy, Offline | default/hover | status dot green/yellow/red/gray |
| SLA Timer | Normal, Warning (50%), Critical (25%), Overdue | updating | countdown, red flash when critical |
