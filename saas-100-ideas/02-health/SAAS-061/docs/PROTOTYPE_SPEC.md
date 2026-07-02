# PROTOTYPE SPEC — LabMgt (SAAS-061)
> Owner: UI/UX Designer · Gate 2

## Screen: Login Page (maps to Journey Stage: —)
- **Layout:** Centered card, logo top, email/password fields, role selector dropdown
- **Components:** TextInput, Button, Dropdown (role), Checkbox (remember me)
- **States:** Empty | Loading (spinner on submit) | Error (invalid credentials banner) | Edge (session expired redirect)
- **Key Interaction:** Role-based redirect after login
- **Friction Resolved:** — (pre-auth)

## Screen: Lab Dashboard (maps to Journey Stage: Daily Ops)
- **Layout:** Top stat cards (samples today, pending, completed, revenue today), center Kanban-style sample board, right sidebar notifications
- **Components:** StatCard, SampleBoard, NotificationList, DatePicker, QuickActionButton
- **States:** Empty (no samples today — show "start your day" CTA) | Loading (skeleton cards) | Error (API failure toast) | Edge (zero revenue)
- **Key Interaction:** Click Kanban card → opens sample detail modal
- **Friction Resolved:** #5 (no immediate financial visibility)

## Screen: Sample Registration (maps to Journey Stage: تسجيل المريض + سحب العينة)
- **Layout:** Left panel patient search/create, right panel barcode preview + sample details
- **Components:** SearchInput (patient), PatientForm, BarcodePreview, SampleTypeSelect, TestPanelSelector
- **States:** Empty (no patient selected) | Loading (searching patient) | Error (duplicate patient warning) | Edge (walk-in patient without appointment)
- **Key Interaction:** Scan patient ID → auto-fill form → select tests → print barcode
- **Friction Resolved:** #2 (barcode matching)

## Screen: Result Entry (maps to Journey Stage: إدخال النتائج)
- **Layout:** Table format, rows = tests, columns = sample info + value input + unit + reference range + flag
- **Components:** DataTable, NumericInput, FlagBadge (normal/abnormal), BatchSelect, ImportButton
- **States:** Empty (queue selected but no samples) | Loading (fetching sample queue) | Error (value out of range warning) | Edge (batch import with malformed data)
- **Key Interaction:** Tab → type value → auto-flag if abnormal → Enter → next
- **Friction Resolved:** #1 (manual entry slow, auto-flag reduces errors)

## Screen: Report Generation (maps to Journey Stage: توليد التقرير)
- **Layout:** Preview panel showing generated PDF, left toolbar with share options
- **Components:** PDFPreview, ShareButtonGroup (WhatsApp/Email/SMS), PatientInfoCard, ApprovalCheckbox
- **States:** Empty (no results to report) | Loading (PDF generating spinner) | Error (PDF generation failed) | Edge (patient has no phone number)
- **Key Interaction:** Click WhatsApp → opens pre-filled message with PDF link
- **Friction Resolved:** #3 (patients calling for results)

## Screen: Inventory Management (maps to Journey Stage: —)
- **Layout:** Table of inventory items with stock level bar, colour-coded (green/yellow/red)
- **Components:** DataTable, StockBar, AlertBadge, ThresholdInput, ReorderButton
- **States:** Empty (no items registered) | Loading (fetching) | Error (sync failure) | Edge (expired items)
- **Key Interaction:** Click item → batch list → expiry timeline
- **Friction Resolved:** #4 (stock runs out without alert)

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Button | Primary, Secondary, Ghost, Danger, Icon | normal/hover/active/disabled/loading | Min 44px tap target |
| TextInput | Single-line, Multi-line, Numeric, Search | default/focus/error/disabled/success | Label float on focus, RTL support |
| BarcodePreview | QR, Code128 | generated/scanning/error | Auto-generate on form submit |
| StatCard | Number, Currency, Percentage | normal/loading/empty | Animated counter on mount |
| DataTable | Sortable, Filterable, Selectable | loading/empty/error/populated | Virtual scroll for 500+ rows |
| Modal | Default, Fullscreen | open/closed/closing | Esc to close, backdrop click |
| KanbanBoard | 3-5 columns, DragDrop | loading/empty/error | Drag to change sample status |
| FlagBadge | Normal, Abnormal High, Abnormal Low | — | Colour + icon (green/red/amber) |
| PDFPreview | Document, Thumbnail | loading/ready/error | Embed viewer or download fallback |
