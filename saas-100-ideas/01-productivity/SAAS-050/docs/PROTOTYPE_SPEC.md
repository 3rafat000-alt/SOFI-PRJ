# PROTOTYPE SPEC — DevSync (SAAS-050)
> Owner: UI/UX Designer · Gate 2

## Screen: Sprint Board (Kanban) (maps to Journey Stage: Sprint Active)
- **Layout:** Horizontal columns (To Do / In Progress / In Review / Done), ticket cards with priority + assignee + points
- **Components:** KanbanColumn, TicketCard, PriorityIcon, AvatarStack, PointsBadge, SprintHeader
- **States:**
  - Empty: "Sprint is empty — move tickets from backlog"
  - Loading: Skeleton columns
  - Error: "Failed to load sprint" + retry
  - Edge: 50+ tickets per column — collapsed + scroll
- **Key Interaction:** Drag ticket → drop in next column → status auto-updates
- **Friction Resolved:** [#4] حالة التذكرة تتحدث تلقائياً عند السحب

## Screen: Ticket Detail (maps to Journey Stage: Develop)
- **Layout:** Title + status + priority (top), description (middle), tabs (Comments / PR Links / Activity)
- **Components:** TicketHeader, Tabs, CommentList, PRLinkCard, ActivityTimeline, AssigneeSelect
- **States:**
  - Loading: Skeleton
  - Error: "Ticket not found"
  - Edge: 100+ comments — paginated
- **Key Interaction:** Link PR → type PR URL → auto-fetch PR title + status
- **Friction Resolved:** [#2] ربط PR تلقائي بإدخال الرابط

## Screen: Code Review (maps to Journey Stage: Review)
- **Layout:** Split view: file tree (left), diff view (center), comment per line (right sidebar)
- **Components:** FileTree, DiffView, CommentThread, ApproveButton, RequestChangesButton
- **States:**
  - Loading: Diff loading skeleton
  - Empty: "No files changed"
  - Error: "Failed to load diff"
  - Edge: 50+ files changed — collapsible + search
- **Key Interaction:** Click line → add comment → submit review → approve/reject
- **Friction Resolved:** [#3] Diff view مع تعليقات لكل سطر

## Screen: Analytics (maps to Journey Stage: Retro)
- **Layout:** Sprint selector, burndown chart, velocity bar chart, cycle time scatter, bug rate
- **Components:** BurndownChart, VelocityChart, CycleTimeScatter, MetricCard, SprintSelect
- **States:**
  - Empty: "Complete a sprint to see analytics"
  - Loading: Chart skeletons
  - Error: "Analytics unavailable"
  - Edge: 52 weeks of data — zoomable
- **Key Interaction:** Select sprint → burndown updates; hover for point values
- **Friction Resolved:** [#5] تحليلات سرعة الفريق

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| KanbanColumn | To Do/In Progress/In Review/Done | default, drag-over (highlight) | Count badge on header |
| TicketCard | task/bug/story/epic | default, dragging, blocked | Left border colour by type |
| PriorityIcon | urgent/high/medium/low | default | Colour + icon (red/orange/blue/grey) |
| DiffView | unified/split | loading, loaded, empty | Line numbers, +/- highlights |
| CommentThread | inline/sidebar | default, resolved (collapsed) | Reply, resolve, edit, delete |
| BurndownChart | svg line | ideal vs actual | Red if behind, green if ahead |
| AvatarStack | 1-5 overflow | added, removed | Tooltip with names |
| PointsBadge | story points | default | Pill shape, bg #EFF6FF |
