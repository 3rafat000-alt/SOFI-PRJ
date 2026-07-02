# PROTOTYPE SPEC — SchoolEase (SAAS-067)
> Owner: UI/UX Designer · Gate 2

## Screen: Admin Dashboard (maps to Journey Stage: —)
- **Layout:** Top row (total students, attendance %, fees collected, alerts), below attendance trend chart + recent activities feed
- **Components:** StatCard, LineChart (attendance trend), AlertList, QuickActionButtons (add student, send notice)
- **States:** Empty (new school setup) | Loading (skeleton) | Error (sync failure) | Edge (zero alerts — green check)
- **Key Interaction:** Click stat card → detailed report modal
- **Friction Resolved:** #5 (instant data visibility)

## Screen: Gradebook (maps to Journey Stage: إدخال الدرجات)
- **Layout:** Grid table — rows = students, columns = assignments/exams, final grade calculated column
- **Components:** GradeTable, ScoreInput, WeightConfig, GPA_Calculator, GradeDistributionChart
- **States:** Empty (no assignments) | Loading | Error (calculation mismatch) | Edge (excused student — exempted from grade)
- **Key Interaction:** Tab → type score → auto-calc weighted total → color-code (green ≥80, amber 60-79, red <60)
- **Friction Resolved:** #1 (speed up grading)

## Screen: Attendance Register (maps to Journey Stage: الحضور اليومي)
- **Layout:** Full-screen grid — rows = students, columns = dates (month view), quick tap Present/Absent/Late/Excused
- **Components:** AttendanceGrid, StatusChip, DateNavigator, AbsenceAlert, BulkActionBar
- **States:** Empty (no students in class) | Loading | Error | Edge (student transferred mid-month)
- **Key Interaction:** Tap student cell → cycle through status (P→A→L→E→P)
- **Friction Resolved:** #2 (quick attendance entry)

## Screen: Parent App — Student Progress (maps to Journey Stage: التواصل مع ولي الأمر)
- **Layout:** Top card (student name, class, GPA), below segmented tabs (Grades / Attendance / Schedule / Fees / Messages)
- **Components:** StudentSummaryCard, GradeList, AttendanceCalendarMini, TimetableCard, FeeBalanceCard, MessageThread
- **States:** Empty (no data yet) | Loading | Error | Edge (multiple children — swipe to switch)
- **Key Interaction:** Pinch to zoom on attendance calendar for monthly overview
- **Friction Resolved:** #2 (parent communication gap)

## Screen: Fee Management (maps to Journey Stage: دفع الرسوم)
- **Layout:** Left panel fee structure per grade, right panel payment journal with search
- **Components:** FeeStructureTable, InvoiceCard, PaymentForm, ReceiptPreview, OverdueList
- **States:** Empty (no fee structure defined) | Loading | Error (payment gateway down) | Edge (partial payment allowed)
- **Key Interaction:** Click "Send Invoice" → WhatsApp/SMS to parent with payment link
- **Friction Resolved:** #3 (fee collection improved)

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Button | Primary #7C3AED, Secondary #4F46E5, Ghost | hover/active/disabled/loading | 44px |
| GradeTable | Editable/Read-only | loading/empty/error | Tab-to-next cell |
| AttendanceGrid | Daily/Monthly view | loading/ready/editing | Cycle status on tap |
| StatCard | Number/Percentage/Currency | normal/loading/error | Click to drill-down |
| StudentSummary | Card with avatar, grade, class | default/loading | Swipe between children |
| FeeStructure | Table per grade level | editing/saved/published | Version history |
| AttendanceCalendar | Heatmap (colour by status) | month/week view | Green/Amber/Red per day |
| ReportCard | PDF preview, template | generating/ready/error | Download + share button |
| AlertBanner | Academic/Attendance/Fee | info/warning/critical | Dismissible with action |
