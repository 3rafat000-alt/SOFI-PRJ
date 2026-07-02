# PROTOTYPE SPEC — LeadFunnel (SAAS-032)
> Owner: UI/UX Designer · Gate 2

## Screen: Pipeline Kanban (maps to Journey Stage: Assign → Negotiate)
- **Layout:** Horizontal swimlanes per stage (New → Contacted → Qualified → Proposal → Negotiation → Won/Lost), cards draggable
- **Components:** Kanban column, lead card (avatar, name, value, score badge), quick action buttons
- **States:** Empty (no leads) | Loading (WebSocket sync) | Error (sync failure) | Edge case (200+ leads → virtual scroll + column grouping)
- **Key Interaction:** Drag lead card → drop in new stage column → auto-update stage + log activity
- **Friction Resolved:** #2 — رؤية مرحلة الصفقة + توزيع العملاء

## Screen: Lead Detail (maps to Journey Stage: Contact → Negotiate)
- **Layout:** 2-column — left (lead info + scoring), right (activity feed timeline)
- **Components:** Info card, activity timeline, quick actions (call, WhatsApp, email, SMS), task list, notes
- **States:** Loading (lead data) | Error (fetch failed) | Edge case (100+ activities → paginated timeline)
- **Key Interaction:** Click WhatsApp button → opens WhatsApp with pre-filled template message
- **Friction Resolved:** #3 — تذكيرات ومتابعة

## Screen: Analytics Dashboard (maps to Journey Stage: Goal)
- **Layout:** Stats row (total leads, conversion rate, pipeline value, win rate) + charts below
- **Components:** Conversion funnel chart, team performance bar chart, revenue forecast line chart
- **States:** Empty (no data) | Loading | Error | Edge case (filtered by team/date range)
- **Key Interaction:** Click funnel stage → drill-down to lead list filtered by that stage
- **Friction Resolved:** #4 — تقارير آلية آنية

## Screen: Import Leads (maps to Journey Stage: Enter)
- **Layout:** Single page with file drop zone, column mapping table, preview grid
- **Components:** Drag-drop file upload, CSV preview table, column matcher dropdown
- **States:** Empty (no file) | Loading (parsing) | Error (format error) | Edge case (50k leads → chunked import)
- **Key Interaction:** Drop CSV → auto-detect columns → preview → confirm → import
- **Friction Resolved:** #5 — استيراد من Excel

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Kanban Column | New, Contacted, Qualified, Proposal, etc. | empty/with-cards/hover | scrollable, drop target highlight |
| Lead Card | Default, High-priority, Won, Lost | default/hover/dragging | shadow-sm, draggable, click for detail |
| Activity Timeline | Call, Email, Meeting, Note, WhatsApp | default/hover | icon-left, time-right, clickable |
| Score Badge | Hot (red), Warm (orange), Cold (gray) | static | 16px pill with score number |
| Quick Action Bar | Call, WhatsApp, Email, SMS, Task | enabled/disabled | icon buttons with tooltip |
| Drop Zone | File upload | default/drag-over/error/uploading | dashed border, format hint text |
