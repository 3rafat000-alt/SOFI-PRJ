# PROTOTYPE SPEC — DentistPro (SAAS-062)
> Owner: UI/UX Designer · Gate 2

## Screen: Appointment Calendar (maps to Journey Stage: حجز موعد)
- **Layout:** Full-page calendar with day/week/month toggle, left sidebar shows open slots colour-coded
- **Components:** CalendarGrid, SlotChip, PatientSearchDropdown, AppointmentCard, TimeIndicator
- **States:** Empty (no appointments) | Loading (skeleton grid) | Error (sync failure) | Edge (double-booking prevention modal)
- **Key Interaction:** Drag appointment card to reschedule
- **Friction Resolved:** #1 (no show rate reduced via reminders)

## Screen: Patient File (maps to Journey Stage: تسجيل الدخول)
- **Layout:** Left sidebar tabs (Profile / Medical History / Teeth / X-rays / Treatments / Invoices), main content panel
- **Components:** TabBar, PatientInfoCard, HistoryTimeline, ToothChartMini, XrayThumbnailGrid
- **States:** Empty (new patient) | Loading (fetching) | Error (patient not found) | Edge (deleted patient restore option)
- **Key Interaction:** Click tooth in mini-chart → full tooth chart modal
- **Friction Resolved:** #3 (file search and organization)

## Screen: Interactive Tooth Chart (maps to Journey Stage: خريطة الأسنان)
- **Layout:** 2D top/down view of 32 teeth, colour-coded by condition (healthy/decayed/filled/missing/crown)
- **Components:** ToothCanvas (SVG), ToothTooltip, SurfaceSelector, ConditionPicker, Legend
- **States:** Empty (all teeth healthy) | Loading (chart rendering) | Error (data corruption) | Edge (patient with no teeth/partial dentures)
- **Key Interaction:** Click tooth → select surface → pick condition → add treatment notes
- **Friction Resolved:** #2 (visual tooth tracking)

## Screen: Treatment Plan (maps to Journey Stage: خطة علاج)
- **Layout:** Table of proposed procedures with fee column, expandable rows for details
- **Components:** ProcedureTable, AddProcedureModal, FeeInput, ToothRefBadge, StatusTimeline
- **States:** Empty (no plan created) | Loading | Error | Edge (multiple phases with dependencies)
- **Key Interaction:** Add procedure → auto-calculate total → present to patient
- **Friction Resolved:** #4 (no price visibility before treatment)

## Screen: X-ray Viewer (maps to Journey Stage: فحص الأسنان)
- **Layout:** Full-screen viewer with zoom/pan, left/right navigation between images, compare mode toggle
- **Components:** ImageCanvas, ZoomControls, CompareSlider, AnnotationTool, DateLabel
- **States:** Empty (no X-rays) | Loading (image downloading) | Error (corrupted image) | Edge (DICOM unsupported fallback to JPEG)
- **Key Interaction:** Click compare → side-by-side (before/after)
- **Friction Resolved:** #2 (X-ray storage and comparison)

## Screen: Billing & Insurance (maps to Journey Stage: الفاتورة + متابعة التأمين)
- **Layout:** Top half invoice preview, bottom half insurance claim form
- **Components:** InvoicePreview, InsuranceClaimForm, ClaimStatusBadge, PaymentSplitter, ReceiptButton
- **States:** Empty (no invoice) | Loading (generating) | Error (insurance API down) | Edge (partial insurance coverage)
- **Key Interaction:** Click submit claim → auto-populate from invoice + treatment codes
- **Friction Resolved:** #5 (paper invoices lost), #6 (insurance complexity)

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Button | Primary #14B8A6, Secondary, Ghost, Icon | normal/hover/active/disabled/loading | 48px min height |
| CalendarGrid | Day/Week/Month | loading/empty/error/populated | Drag-drop reschedule |
| ToothCanvas | 2D SVG 32 teeth | interactive/default/selected/treated | Click → surface selector |
| XrayViewer | Zoom/Pan/Compare | loading/ready/error/empty | Touch pinch-zoom, keyboard arrows |
| AppointmentCard | Confirmed/Pending/Completed/NoShow | default/dragging/overdue | Colour-coded left border |
| TabBar | Underline/Pill | active/inactive | Animated indicator |
| ImageCanvas | Single/Compare mode | loading/ready/error | Compare slider overlay |
| ClaimBadge | Submitted/Approved/Denied/Pending | — | Icon + colour |
