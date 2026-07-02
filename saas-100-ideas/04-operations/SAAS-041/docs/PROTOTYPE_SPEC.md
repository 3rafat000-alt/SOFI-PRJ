# PROTOTYPE SPEC — AlertHub (SAAS-041)
> Owner: UI/UX Designer · Gate 2

## Screen: Alert Dashboard (maps to Journey Stage: Receive)
- **Layout:** Top analytics cards (total/critical/warning/info), main alert table with severity badge, channel icons, timestamp, ack button
- **Components:** DataTable, Badge, StatCard, SearchInput, FilterChips, Pagination
- **States:**
  - Empty: "No alerts yet. Connect your first webhook."
  - Loading: Skeleton rows × 5
  - Error: "Failed to load alerts" + retry button
  - Edge: 500+ alerts — virtualized rows, search + date filter
- **Key Interaction:** Click row → expand detail panel; click ack → confirm modal → update status
- **Friction Resolved:** [#1] تصفية متقدمة لتقليل الضوضاء

## Screen: Alert Detail (maps to Journey Stage: Investigate)
- **Layout:** Left panel (alert info, timeline, logs), right panel (actions: ack/escalate/close)
- **Components:** Timeline, ActionPanel, LogTable, SeverityBadge, ChannelList
- **States:**
  - Empty: N/A (loaded from parent)
  - Loading: Spinner + skeleton
  - Error: "Timeline unavailable"
  - Edge: Very long timeline — collapsible sections
- **Key Interaction:** Escalate → user picker modal → reassign
- **Friction Resolved:** [#2] إضافة سياق (source link, related logs)

## Screen: Template Studio (maps to Journey Stage: Create)
- **Layout:** Left sidebar (template list), main area (editor with rich text + variable injection + preview)
- **Components:** RichTextEditor, VariablePicker, PreviewPane, TemplateCard
- **States:**
  - Empty: "Create your first template" + CTA
  - Loading: Skeleton
  - Error: "Save failed" + retry
  - Edge: Template with 50+ variables — searchable variable list
- **Key Interaction:** Insert variable → {{variable_name}} → preview renders with sample data
- **Friction Resolved:** [#4] قوالب جاهزة للتكرار

## Screen: Channel Config (maps to Journey Stage: Setup)
- **Layout:** Channel type selection → wizard per type → test button
- **Components:** ChannelCard, WizardStepper, ConfigForm, TestButton, StatusIndicator
- **States:**
  - Empty: "No channels configured"
  - Loading: Testing connection spinner
  - Error: "Configuration failed — check API key"
  - Edge: Multiple providers per channel — fallback ordering
- **Key Interaction:** Add channel → select type → fill config → test → save
- **Friction Resolved:** [#3] توحيد القنوات في شاشة واحدة

## Screen: Analytics (maps to Journey Stage: Report)
- **Layout:** Date range selector, metrics grid (delivery rate, MTTA, MTTR, volume), trend charts
- **Components:** LineChart, BarChart, MetricCard, DateRangePicker, ExportButton
- **States:**
  - Empty: "No data for selected period"
  - Loading: Chart skeletons
  - Error: "Analytics unavailable"
  - Edge: Zero values for new accounts — "Start sending alerts to see analytics"
- **Key Interaction:** Filter by date/channel/severity → chart updates
- **Friction Resolved:** [#5] تقارير أداء جاهزة

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| AlertBadge | critical/warning/info | default, pulse animation for critical | Color-coded (red/amber/blue) |
| StatCard | default, clickable | hover, loading skeleton | Shows count + trend arrow |
| ActionButton | ack/escalate/close/snooze | default, hover, active, disabled, loading | Icons per action type |
| RichTextEditor | full/compact | focus, error, loading | Toolbar: bold/italic/list/variable insert |
| Timeline | vertical/horizontal | loading, empty, with data | Expandable log entries |
| FilterChips | severity/date/channel/status | active, inactive, multi-select | Toggle to filter table |
| DataTable | default, expandable | loading, empty, error, populated | Sortable columns, row expand |
