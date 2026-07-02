# PROTOTYPE SPEC — TaskSync Pro (SAAS-001)
> Owner: UI/UX Designer · Gate 2

## Screen 1: Landing Page & Signup (maps to Journey Stage: Discover → Signup)
- **Layout:** Hero section with product mockup + feature highlights + CTA button, below: 3-step setup explanation, pricing cards
- **Components:** Navigation bar, Hero banner, Feature cards (×4), Pricing table (×3 tiers), Footer
- **States:**
  - Empty: N/A (public page)
  - Loading: Skeleton loaders for hero image
  - Error: Network error banner
  - Edge: RTL switch prominent, mobile-responsive breakpoints
- **Key Interaction:** Click "ابدأ مجاناً" → scroll to signup form or modal
- **Friction Resolved:** #2 — clear value proposition in Arabic instantly

## Screen 2: Workspace Setup (maps to: Create Workspace)
- **Layout:** Wizard-style 3-step setup — Step1: Workspace name & industry, Step2: Invite members (email/WhatsApp), Step3: Choose template (Scrum/Kanban/Basic)
- **Components:** Progress stepper, Input field, Member list with avatar chips, Template cards (×3)
- **States:**
  - Empty: No team members yet
  - Loading: Saving workspace skeleton
  - Error: Workspace name taken validation
  - Edge: Skipping invite step allowed
- **Key Interaction:** Typing workspace name → real-time slug generation
- **Friction Resolved:** #2 — guided wizard reduces setup complexity

## Screen 3: Invite Members (maps to: Invite)
- **Layout:** Email input field + WhatsApp share button + member list (pending/accepted)
- **Components:** Input field with validation, Button group (email/WhatsApp/link), Member status chips, Copy link button
- **States:**
  - Empty: No invites sent yet
  - Loading: Sending invite spinner
  - Error: Invalid email format, duplicate member
  - Edge: Maximum members reached (upgrade prompt for free plan)
- **Key Interaction:** Type email → Enter → pending list updates
- **Friction Resolved:** #3 — WhatsApp fallback reduces spam issues

## Screen 4: Kanban Board (maps to: Add Tasks → Assign)
- **Layout:** 3-column Kanban (To Do / In Progress / Done), floating "+" button, project header with timer
- **Components:** Kanban column, Task card (title, assignee avatar, priority badge, due date indicator), Timer toggle button
- **States:**
  - Empty: Illustration + "Create your first task" CTA
  - Loading: Card skeleton placeholders
  - Error: Sync error badge with retry
  - Edge: Board overflow (scroll), long task titles (truncated with tooltip)
- **Key Interaction:** Drag card between columns → status update with animation
- **Friction Resolved:** #4 — simple task creation with minimal fields

## Screen 5: Time Tracker (maps to: Track Time)
- **Layout:** Task timer modal (play/pause button, elapsed time display, task selector, manual entry toggle)
- **Components:** Circular timer display, Play/Pause/FAB, Task dropdown, Manual time entry (hours:minutes), Note input
- **States:**
  - Empty: No tasks available for time entry
  - Loading: Timer sync indicator
  - Error: Network failure mid-tracking → queue locally
  - Edge: Timer running in background (persistent notification in mobile)
- **Key Interaction:** Tap play → timer starts → tap pause → entries added to time log
- **Friction Resolved:** #1 — manual entry fallback + notification reminder

## Screen 6: Reports Dashboard (maps to: Report → Goal)
- **Layout:** Date range selector + KPI cards row (total hours, tasks completed, projects active) + bar chart (hours per project) + pie chart (member workload)
- **Components:** Date picker, KPI metric card, Bar chart (Recharts), Pie chart, Export button (PDF/CSV), Data table drill-down
- **States:**
  - Empty: "Complete tasks to see reports" illustration
  - Loading: Chart skeleton with shimmer
  - Error: Data fetch error → retry CTA
  - Edge: No time data yet → show task completion instead
- **Key Interaction:** Click chart segment → drill-down modal with detail list
- **Friction Resolved:** #5 — visual Arabic reports with export

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Button Primary | Default, Large, Small, Icon+label | hover/active/disabled/loading | bg-primary (#2563EB) white text, 8px radius, transition 200ms |
| Input Field | Text, Email, Search, Textarea | focus/error/disabled/success | border 1px #D1D5DB, 12px padding, focus ring blue 3px |
| Task Card | Default, Dragging, Overdue | default/hover/selected/overdue | shadow 1px, 8px radius, drag ghost opacity 0.5 |
| Timer | Running, Paused, Stopped | active/inactive/completed | circular progress, pulse animation when running |
| Modal | Small (task detail), Large (report) | open/closed/closing | backdrop blur 4px, slide-up animation 300ms |
| Dropdown | Single, Multi, Searchable | closed/open/selected/disabled | 8px radius, shadow-xl, z-index 50 |
| KPI Card | Default, Positive trend, Negative trend | default/hover | white bg, icon, metric value, trend arrow |
