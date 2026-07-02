# PROTOTYPE SPEC — FreelanceHub (SAAS-064)
> Owner: UI/UX Designer · Gate 2

## Screen: Dashboard (maps to Journey Stage: —)
- **Layout:** Top row earnings card (this month vs last), active projects count, pending invoices, timer quick-start
- **Components:** StatCard (earnings), ProjectCard (list), InvoiceAlertList, TimerQuickStart, ActivityChart
- **States:** Empty (new user onboarding) | Loading (skeleton) | Error (API error toast) | Edge (zero earnings month)
- **Key Interaction:** Click "Start Timer" on any project card from dashboard
- **Friction Resolved:** #2 (timer readily accessible)

## Screen: Project Detail (maps to Journey Stage: إنجاز المهام)
- **Layout:** Top breadcrumb, left sidebar tasks, center Kanban board, right panel time entries + expenses
- **Components:** KanbanBoard (drag-drop), TaskCard, TimeEntryList, ExpenseList, TimerButton, ClientInfoCard
- **States:** Empty (no tasks) | Loading | Error | Edge (project overdue — red banner)
- **Key Interaction:** Drag task between columns, click to expand details
- **Friction Resolved:** #4 (task tracking centralised)

## Screen: Timer Tracker (maps to Journey Stage: تشغيل التايمر)
- **Layout:** Full-width timer display (HH:MM:SS), project selector, task dropdown, notes field, manual entry toggle
- **Components:** TimerDisplay, ProjectSelect, TaskSelect, NotesInput, ManualEntryForm, WeeklyTimesheet
- **States:** Empty (no projects) | Running (timer active) | Stopped (show elapsed) | Error (timer sync conflict)
- **Key Interaction:** Click Start → timer runs even if app closes (background sync)
- **Friction Resolved:** #2 (timer sync across devices)

## Screen: Invoice Generator (maps to Journey Stage: الفاتورة)
- **Layout:** Preview panel (what you see is what you get), right panel invoice items (from time entries or manual), bottom tax and total
- **Components:** InvoicePreview, TimeEntrySelector, LineItemEditor, TaxInput, DiscountInput, PaymentTermSelect
- **States:** Empty (no time entries or items) | Loading (generating preview) | Error (template render failed) | Edge (zero-amount invoice warning)
- **Key Interaction:** Click "Add from Time Entries" → auto-populate from project timer logs
- **Friction Resolved:** #1 (professional invoice with payment reminders)

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Button | Primary #3B82F6, Secondary, Ghost, Danger | hover/active/disabled/loading | |
| TimerDisplay | Running/Stopped/Paused | default/loading/no-project | Persistent across navigation |
| KanbanBoard | 4 default columns (To Do/In Progress/Review/Done) | loading/empty/error | Drag-drop, auto-save position |
| InvoicePreview | PDF/HTML preview | loading/generated/error | Download PDF, send link |
| StatCard | Earnings/Projects/Invoices/Hours | normal/loading/empty | Animated counter |
| TaskCard | Priority (high/medium/low) | default/dragging/overdue | Colour-coded left border |
| TimeEntry | Billable/Non-billable | default/editing/flagged | Inline edit duration |
| PaymentBadge | Paid/Pending/Overdue/Partial | — | Colour + days overdue |
