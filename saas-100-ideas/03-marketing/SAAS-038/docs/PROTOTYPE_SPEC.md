# PROTOTYPE SPEC — MailCraft (SAAS-038)
> Owner: UI/UX Designer · Gate 2

## Screen: Email Builder (maps to Journey Stage: Design → Preview)
- **Layout:** 3-column — left (components panel), center (email canvas), right (settings/HTML)
- **Components:** Component palette (text, image, button, divider, header, footer), canvas, properties panel
- **States:** Empty (new campaign) | Loading (auto-save) | Error (save failed) | Edge case (RTL content preview)
- **Key Interaction:** Drag component → drop in canvas → select → configure in right panel → live update
- **Friction Resolved:** #1 — قوالب RTL عربية

## Screen: Campaign Monitor (maps to Journey Stage: Monitor → Analyze)
- **Layout:** Real-time stats dashboard — KPI row + event stream + chart grid
- **Components:** Live counter (sent/opened/clicked/bounced), event feed (timestamp, email, event), trend chart
- **States:** Sending (live updates) | Sent (final stats) | Error (send failure) | Edge case (500k recipients → aggregated stats)
- **Key Interaction:** Watch live count update every 2 seconds → click event → see details
- **Friction Resolved:** #3 — مراقبة آنية

## Screen: Template Gallery (maps to Journey Stage: Choose Template)
- **Layout:** Grid of template cards with category filter tabs
- **Components:** Template card (thumbnail, name, category, popularity), category tabs, preview modal
- **States:** Empty (no templates yet) | Loading | Error | Edge case (50+ templates → search + filter)
- **Key Interaction:** Hover card → preview button → full preview modal → "Use this template"
- **Friction Resolved:** #5 — قوالب عربية جاهزة

## Screen: Subscriber List Management (maps to Journey Stage: Select List)
- **Layout:** Table with search + filter + bulk actions + import button
- **Components:** Subscriber table (email, name, status, last opened, added date), filter chips, import dialog
- **States:** Empty (no subscribers) | Loading | Error | Edge case (100k subscribers → paginated)
- **Key Interaction:** Click "Import" → upload CSV → column mapping → preview → confirm
- **Friction Resolved:** #4 — استيراد سهل

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Drag Component | Text, Image, Button, Divider, Header, Footer | default/dragging/placed | drag from palette, drop in canvas |
| Email Canvas | Mobile/Desktop preview toggle | editing/preview | responsive, RTL toggle |
| Template Card | Thumbnail + name + category | default/hover/selected | hover: overlay preview button |
| Live Counter | Sent, Opened, Clicked, Bounced | counting/paused/complete | animated number ticker |
| Event Feed | Time, email, event type | streaming/paused | auto-scroll, filter by event |
| Subscriber Row | Email, name, status, date | default/selected | checkbox + click for detail |
| Import Dialog | File upload + mapping | closed/uploading/mapping/importing | drag-drop CSV |
| AB Test Card | Variant A vs B | setup/running/results | progress bar, winner indicator |
