# PROTOTYPE SPEC — NetworkHub CRM (SAAS-049)
> Owner: UI/UX Designer · Gate 2

## Screen: Contacts List (maps to Journey Stage: Add Contact)
- **Layout:** Search bar (top), filter chips, contact cards with avatar, name, company, last interaction, quick actions
- **Components:** SearchInput, ContactCard, FilterChip, ImportButton, AddFab
- **States:**
  - Empty: "No contacts yet. Import or add your first contact."
  - Loading: Skeleton cards × 5
  - Error: "Failed to load contacts" + retry
  - Edge: 5000+ contacts — virtualized list + database-side search
- **Key Interaction:** Tap contact → detail page; swipe → call/message
- **Friction Resolved:** [#4] استيراد CSV + دمج مكررات

## Screen: Contact Detail (maps to Journey Stage: View Timeline)
- **Layout:** Profile header, tab bar (Info / Activity / Deals / Notes), activity timeline
- **Components:** ProfileHeader, TabBar, TimelineItem, QuickActionButton, DealCard
- **States:**
  - Loading: Skeleton profile
  - Error: "Contact not found"
  - Empty (Activity): "No interactions yet — log your first call"
- **Key Interaction:** Scroll timeline; tap interaction to expand; tap "Log" FAB
- **Friction Resolved:** [#1] تسجيل التفاعل السريع

## Screen: Deal Pipeline (maps to Journey Stage: Move Pipeline)
- **Layout:** Horizontal Kanban columns (stages) with deal cards, drag-drop enabled
- **Components:** PipelineColumn, DealCard, DragHandle, StageHeader, AddDealButton
- **States:**
  - Empty: "No deals yet. Create your first deal."
  - Loading: Skeleton columns
  - Error: "Pipeline unavailable"
  - Edge: 100+ deals per column — collapsed view
- **Key Interaction:** Drag deal card → drop on next stage → update probability
- **Friction Resolved:** [#3] السحب يعمل باللمس في الجوال

## Screen: Tasks (maps to Journey Stage: Follow Up)
- **Layout:** Filter bar (today/upcoming/overdue), task cards with checkboxes, priority, due date, related contact
- **Components:** TaskCard, Checkbox, PriorityBadge, FilterToggle, AddTaskButton
- **States:**
  - Empty: "No tasks. Create a follow-up task."
  - Loading: Skeleton
  - Error: "Tasks unavailable"
  - Overdue: Red highlight on cards past due
- **Key Interaction:** Check box → mark complete (strike-through animation)
- **Friction Resolved:** [#2] تذكيرات ذكية محددة

## Screen: Reports (maps to Journey Stage: Report)
- **Layout:** Date range filter, KPI cards, funnel chart, trend line chart, export button
- **Components:** DateRangePicker, KPICard, FunnelChart, LineChart, ExportButton
- **States:**
  - Empty: "No data — start using the CRM"
  - Loading: Chart skeletons
  - Error: "Report failed to load"
- **Key Interaction:** Change date range → charts update → export
- **Friction Resolved:** [#5] تقارير PDF جاهزة

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| ContactCard | grid/list | default, selected, swipe | Swipe actions (call/message/email) |
| PipelineColumn | stage with count | default, drag-over (highlight) | Scrollable horizontally |
| DealCard | compact/expanded | default, dragging, won, lost | Won = green left border, Lost = red |
| TimelineItem | call/email/meeting/whatsapp/note | default, expandable | Icon + text + timestamp |
| TaskCard | today/upcoming/overdue | default, checked, overdue | Checkbox animation |
| QuickActionBtn | call/email/whatsapp | default, loading (call) | Phone icon, tap to call via VoIP |
| FunnelChart | stages+counts | interactive on hover | Svg path, curved funnel |
| SearchInput | global/contact search | default, focus, results | Debounced 300ms, dropdown results |
