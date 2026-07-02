# PROTOTYPE SPEC — BuildTrack (SAAS-016)
> Owner: UI/UX Designer · Gate 2

## Screen: Projects Dashboard (Stage: لوحة المشاريع)
- **Layout:** Grid of project cards sorted by status (active/on-hold/completed) + progress overview bar
- **Components:** ProjectCard (name, location, budget bar, progress %, days remaining, status badge), CreateProjectFAB, FilterBar (status, date, value), MiniGantt (timeline view)
- **States:** Empty ("أنشئ مشروعك الأول") | Loading (skeleton cards) | Projects list | Error
- **Key Interaction:** Click project → project detail; progress bar click → open Gantt
- **Friction Resolved:** [#2] — Gantt view with actual vs planned comparison

## Screen: Project Detail / Site Diary (Stage: إدارة الموقع)
- **Layout:** Tabbed view: Overview | Daily Log | Progress | Materials | Workforce | Reports
- **Components:** TabNav, ProgressGauge (percentage ring by phase), DailyLogForm (text + photos + worker count + weather), PhotoGallery, PhaseBreakdown (list of phases with %) , BudgetBar (spent vs remaining)
- **States:** Loading | Active (daily entries) | No entries today | Error
- **Key Interaction:** Add daily log entry with photos; swipe between tabs; photo capture with timestamp + GPS
- **Friction Resolved:** [#3] — daily log with photo evidence, [#4] — revision history

## Screen: Material Inventory (Stage: إدارة المواد)
- **Layout:** Table/card view of materials (cement, steel, blocks, sand, etc.) with stock levels
- **Components:** MaterialCard (name, unit, qty in stock, min threshold, supplier), StockAlertBadge, PurchaseOrderForm, SupplierSelect, DeliveryNoteScan, ConsumptionLog (daily usage entry)
- **States:** Empty (no materials) | Loading | Normal | Low stock (red) | Out of stock
- **Key Interaction:** Click material → usage history; "order" → auto-create PO with preferred supplier; scan delivery note → auto-update stock
- **Friction Resolved:** [#1] — low stock alerts + auto-PO, [#4] — consumption tracking

## Screen: Workforce Management (Stage: إدارة العمال)
- **Layout:** Worker list (photo, name, role, daily wage, attendance today) + attendance grid (calendar view)
- **Components:** WorkerCard (photo, name, role, wage, status: present/absent/leave), AttendanceGrid (green/red dots per day), WageCalculator (auto-sum based on attendance), ShiftSchedule, TimeClockWidget (check-in/out button)
- **States:** Empty (no workers added) | Loading | Attendance in progress | End of month (wage calculation done)
- **Key Interaction:** Check-in/out button records timestamp; end of month → one-click wage calculation for payroll export
- **Friction Resolved:** [#3] — automated attendance + wages

## Screen: Progress Reports (Stage: تقرير التقدم)
- **Layout:** Top: project selector + date range + export; Main: chart grid (planned vs actual progress, budget burn, resource usage)
- **Components:** ProjectSelect, DateRangeFilter, ChartPanel (bar: planned vs actual, line: budget burn, pie: phase completion), SummaryStatRow, ExportButton (PDF/CSV), PhotoTimeline (before/after per phase)
- **States:** Empty (no data) | Loading | Filtered data | Error
- **Key Interaction:** Click chart segment → drill to phase detail; export → formatted PDF ready for client presentation
- **Friction Resolved:** [#2] — data-driven progress with photo evidence

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Button Primary | Default, Small | hover/active/disabled/loading | bg-#E65100, white, 8px radius, 14px |
| Button Secondary | Default, Outline | hover/active/disabled | border-#37474F, text #37474F |
| ProjectCard | Default, Compact | normal/hover/selected | progress bar, budget bar, days count |
| Input Field | Default, Search | focus/error/disabled | 12px padding, 6px radius |
| ProgressGauge | Circular, Bar | loading/data/empty | percentage display, color: green>amber>red |
| DailyLogForm | With photos, With weather | draft/submitted/approved | photo capture + GPS stamp |
| MaterialCard | Stock level color | normal/low/out | icon, name, stock qty, min threshold |
| PurchaseOrder | Form, Table | draft/approved/ordered/received | supplier, items, total, status |
| WorkerCard | Present, Absent, Leave | normal/hover | photo, name, role, attendance badge |
| AttendanceGrid | Calendar month grid | present green / absent red / leave yellow | tap cell to toggle |
| ChartPanel | Bar, Line, Pie | loading/data/empty/error | Chart.js responsive |
| PhotoTimeline | Horizontal scroll | loading/error/data | before/after pair per phase date |
| Modal | Default | open/close | backdrop, ESC close |
