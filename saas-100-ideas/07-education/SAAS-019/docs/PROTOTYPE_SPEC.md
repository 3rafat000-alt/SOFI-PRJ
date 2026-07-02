# PROTOTYPE SPEC — TutorSpace (SAAS-019)
> Owner: UI/UX Designer · Gate 2

## Screen: Teacher Dashboard (Stage: لوحة المعلم)
- **Layout:** Weekly calendar (main view, left) + student list sidebar + upcoming session cards (right)
- **Components:** WeeklyCalendar (time slots × days), SessionCard (student name, subject, time, status), StudentListMini, QuickActionButtons (add session, add student, invoice), NotificationList (payment reminders, cancellations)
- **States:** Empty ("أضف طلابك الأولين") | Loading (skeleton) | Full schedule | Overlap detected (red highlight) | Error
- **Key Interaction:** Click time slot → create session; click session → session detail; drag to reschedule
- **Friction Resolved:** [#1] — calendar with auto-conflict detection

## Screen: Virtual Classroom (Stage: عقد الدرس)
- **Layout:** Main: video feed (large, teacher + student tiles); Side: collaborative whiteboard + chat
- **Components:** VideoTile (teacher/student, mute/unmute, camera toggle), WhiteboardToolbar (pen, text, shapes, eraser, color picker), ChatPanel, FileShareButton, ScreenShareButton, RecordingIndicator, TimerWidget (lesson duration)
- **States:** Connecting | Connected (video on) | Audio only | Muted | Recording | Disconnected (with rejoin)
- **Key Interaction:** Start lesson → timer begins; share whiteboard → both can draw; end lesson → prompt for rating
- **Friction Resolved:** [#3] — adaptive bitrate, audio-only fallback

## Screen: Student Profile / Progress (Stage: متابعة التقدم)
- **Layout:** Student header (name, grade, subject, start date) + tabs: Sessions, Progress, Payments, Files
- **Components:** StudentInfoCard, SessionHistoryTable (date, topic, duration, rating, homework), ProgressChart (skill areas radar chart), AssessmentList (quiz/midterm scores), HomeworkUploadArea, PaymentHistory
- **States:** Loading | Active (with history) | New student (empty history) | Error
- **Key Interaction:** Click session → view notes/recording; radar chart updates per assessment
- **Friction Resolved:** [#4] — skill radar chart for visual progress tracking

## Screen: Invoicing & Payments (Stage: إعداد الفاتورة)
- **Layout:** Top: student selector + month picker; Main: invoice items table (session date, duration, rate, amount) + summary
- **Components:** StudentSelect, MonthPicker, InvoiceLineItem, AddManualItem (materials, travel), DiscountInput, TaxToggle (VAT), TotalSummary, PaymentStatusBadge (paid/pending/overdue), SendInvoiceButton, PaymentGatewayButton (Mada/STC Pay/Tabby)
- **States:** Empty | Draft (editable) | Sent | Paid | Overdue | Partial payment
- **Key Interaction:** Auto-generate invoice from attended sessions; add manual items; send → student receives email/WhatsApp with pay link
- **Friction Resolved:** [#2] — auto-generation + payment gateway integration

## Screen: Parent Portal (Stage: تقرير ولي الأمر)
- **Layout:** Minimal dashboard: child's upcoming sessions + progress summary + recent report cards
- **Components:** ChildSelector (if multiple children), UpcomingSessionsList, ProgressBadge (green/amber/red per subject), ReportCardList (downloadable PDF), PaymentMethods (saved cards/STC Pay), InvoiceHistory, MessageTeacherButton
- **States:** Loading | Active | No recent data | Payment overdue
- **Key Interaction:** Click report → download PDF; click pay → quick payment flow
- **Friction Resolved:** [#5] — regular progress reports auto-generated and shared

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Button Primary | Default, Small, Icon | hover/active/disabled/loading | bg-#5E35B1, white, 8px radius, 14px |
| Button Secondary | Default, Outline | hover/active/disabled | border-#1E88E5, text #1E88E5 |
| WeeklyCalendar | Time slots × Days | normal/hover/selected/conflict | 30min slots, conflict red highlight |
| SessionCard | Upcoming, Past, Cancelled | normal/hover | time, student, subject, status color |
| VideoTile | Large, Small (PiP) | connecting/video/audio-only/muted/disconnected | adaptive quality indicator |
| Whiteboard | Canvas with toolbar | draw/erase/shapes/text | multi-touch, color picker |
| ProgressChart | Radar, Line | loading/data/empty | skill areas (problem solving, algebra, etc.) |
| StudentCard | Compact, Expanded | normal/hover | name, grade, subject, next session |
| InvoiceLineItem | Editable, Read-only | default/edited | date, duration, rate, amount |
| PaymentBadge | pill | paid green / pending amber / overdue red |
| ReportCard | Card, PDF download | available/generating | subject, score, teacher notes |
| AssessmentForm | Quiz, Assignment | draft/submitted/graded | points, feedback, skill tags |
| Modal | Default | open/close | backdrop, ESC close |
