# PROTOTYPE SPEC — CemeteryMgt (SAAS-081)
> Owner: UI/UX Designer · Gate 2

## Screen: Dashboard Overview (Journey Stage: Monitor & Manage)
- **Layout:** Top stats bar (total graves, today's visitors, pending maintenance), middle map widget, bottom recent activity feed
- **Components:** StatCard, MapWidget, ActivityFeed, QuickActionButton
- **States:** Empty (first-time setup wizard) | Loading (skeleton) | Error (retry banner) | Edge (0 visitors in off-season → show motivational tip)
- **Key Interaction:** Click map pin → opens grave detail modal
- **Friction Resolved:** #1 — clear overview of cemetery status at glance

## Screen: Grave Locator (Journey Stage: Find Grave)
- **Layout:** Full-screen map with search bar top, grave marker pins, bottom info card on selection
- **Components:** MapView, SearchInput, MarkerPin, InfoCard, QRScannerButton
- **States:** Empty (no search yet → show nearby landmarks) | Loading (spinner overlay) | Error ("GPS unavailable → show grid coordinates") | Edge (offline → cached map)
- **Key Interaction:** Search deceased name → map centers on grave → tap pin shows details
- **Friction Resolved:** #3 — GPS navigation to specific grave

## Screen: Burial Record Form (Journey Stage: Record Burial)
- **Layout:** Stepped form — Step 1: Deceased Info, Step 2: Plot Selection (map grid), Step 3: Guardian Info, Step 4: Confirm & Print
- **Components:** Stepper, TextInput, MapGrid, DatePicker, QRCodeDisplay, PrintButton
- **States:** Empty (fresh form) | Loading (saving) | Error (validation inline) | Edge (batch import from paper records)
- **Key Interaction:** Select plot from interactive grid → auto-fills plot ID
- **Friction Resolved:** #2 — digitize paper burial records

## Screen: Maintenance Board (Journey Stage: Schedule Maintenance)
- **Layout:** Kanban columns — Pending, In Progress, Completed, Overdue
- **Components:** KanbanCard, StatusBadge, PhotoAttachment, AssigneeAvatar, DueDateChip
- **States:** Empty ("No maintenance tasks — all clean!") | Loading (skeleton cards) | Error (failed to load) | Edge (overdue highlighted red pulse)
- **Key Interaction:** Drag card between columns → updates status
- **Friction Resolved:** #4 — track maintenance tasks

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Button | Primary, Secondary, Ghost, Danger | hover/active/disabled/loading | Ripple effect, 8px radius |
| Input | Text, Search, Date, Select | focus/error/disabled/filled | 12px padding, border 1px |
| Card | StatCard, InfoCard, KanbanCard | default/hover/selected | Shadow on hover, 12px radius |
| Map Widget | Fullscreen, Mini, Grid | loading/loaded/error/no-gps | Pin clustering at zoom-out |
| QR Code | Small (card), Large (print) | loading/scanned/expired | Auto-refresh every 60s |
| Modal | Confirm, Form, Detail | open/closing/loading | Backdrop blur, ESC to close |
| Toast | Success, Error, Warning, Info | enter/exit/stacked | Auto-dismiss 5s, top-right |
