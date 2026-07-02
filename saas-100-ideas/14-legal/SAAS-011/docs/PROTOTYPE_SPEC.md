# PROTOTYPE SPEC — LawDesk (SAAS-011)
> Owner: UI/UX Designer · Gate 2

## Screen: Login (مstage: تسجيل الدخول)
- **Layout:** Centered card layout with law-themed illustration (scales icon)
- **Components:** TextInput (email), PasswordInput, PrimaryButton "دخول", LinkButton "نسيت كلمة المرور", Footer "ليس لديك حساب؟ سجل الآن"
- **States:** Empty | Validation error | Loading | Network error | 2FA challenge
- **Key Interaction:** Tab through RTL fields naturally; Enter submits
- **Friction Resolved:** [#1] — forgot password flow inline

## Screen: Dashboard (Stage: لوحة التحكم)
- **Layout:** Top navbar (logo + search + notifications + profile) + sidebar (navigation) + main content grid
- **Components:** StatCard (x4: active cases, pending hearings, overdue tasks, income), CaseList (recent 5), CalendarWidget (upcoming), TaskWidget (today's)
- **States:** Empty ("أضف قضيتك الأولى") | Loading (skeleton) | Full data | Error (API failure + retry)
- **Key Interaction:** Click any stat card to drill into filtered list; calendar shows case count per date
- **Friction Resolved:** [#5] — paginated lazy loading, skeleton screens

## Screen: New Case Form (Stage: إضافة قضية)
- **Layout:** Multi-step wizard (Step 1: case info, Step 2: client/opponent, Step 3: documents, Step 4: review)
- **Components:** StepperIndicator, TextInput, Select (case type/court), DatePicker, FileUpload (drag & drop), TextArea, ButtonGroup (back/next/submit)
- **States:** Empty (all fields) | Validation | File uploading | Draft auto-save | Submission success
- **Key Interaction:** Auto-fill client name from database, file preview before upload
- **Friction Resolved:** [#3] — client search from existing DB, no re-entry

## Screen: Case Detail (Stage: متابعة القضية)
- **Layout:** Left panel: case info + timeline; Right panel: documents + notes tabs
- **Components:** CaseHeader (status badge, case number), TimelineFeed, DocumentList (file size, upload date), NoteEditor (rich text), TimeTracker (start/stop), HearingSchedule, BillWidget
- **States:** Empty (no docs yet) | Loading | Error | Offline (last cached)
- **Key Interaction:** Swipe between tabs; drag-drop to upload from panel; click timeline entry to expand
- **Friction Resolved:** [#1] — batch document upload, [#4] — full-text search on docs

## Screen: Calendar (Stage: جدولة جلسة)
- **Layout:** Full-page calendar with month/week/day toggle; hearings listed below
- **Components:** MonthPicker, WeekView, DayView, HearingCard (time, court, case #, status), ConflictAlert
- **States:** Empty (no hearings) | Loading | Conflict detected | Day with 5+ hearings
- **Key Interaction:** Click empty slot → new hearing modal (suggest non-conflict times); drag hearing to reschedule
- **Friction Resolved:** [#2] — auto-conflict detection, [#5] — performance with lazy month loading

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Button Primary | Default, Small, Large | hover/active/disabled/loading | bg-#0A1F3F, white text, 8px radius, 14px font |
| Button Secondary | Default, Outline | hover/active/disabled | border-#C9A84C, text #C9A84C, transparent bg |
| TextInput | Default, WithIcon, Search | focus/error/disabled/success | 12px padding, 1px border #D1D5DB, 8px radius |
| Select | Default, Multi | focus/error/disabled | dropdown native or custom with search |
| DatePicker | Single, Range | focus/disabled | Persian/Gregorian toggle; min/max date validation |
| FileUpload | Single, Multiple (drag zone) | empty/hover/uploading/error/success | drag zone with preview; 25MB max per file |
| StatCard | Default, Clickable | normal/hover/active | 16px padding, shadow-sm, icon + number + label |
| CaseCard | Default, Expanded | normal/hover/active | status color left border, case number, client name, next hearing |
| Modal | Default, Fullscreen | open/closing | backdrop blur, ESC close, click outside close |
| Badge | Status (active/pending/closed/overdue) | — | pill shape, color coded: green/amber/red/gray |
| Timeline | Vertical, Horizontal | — | dot + line + content for each event |
| SearchBar | Global, WithinList | focus/typing/results/no-results | debounced 300ms, keyboard navigation |
| Toast | Success, Error, Warning, Info | show/hide | top center, auto-dismiss 5s, stackable |
