# PROTOTYPE SPEC — ExamPro (SAAS-076)
> Owner: UI/UX Designer · Gate 2

## Screen: Exam Builder (maps to Journey Stage: إنشاء)
- **Layout:** Left sidebar (question bank) + right editor (question details)
- **Components:** QuestionBankPanel, QuestionEditor (rich text, image embed), AnswerOptionsEditor (MCQ/TF/Matching), PointsInput, DifficultySlider, TagInput
- **States:** Empty → قالب اختبار جديد; Loading → Autosave indicator; Error → "فشل الحفظ" → autosave queued; Edge → Duplicate question detected → merge option
- **Key Interaction:** Drag question from bank to exam; Autosave every 30s
- **Friction Resolved:** #3 — Rich editor with images and formatting

## Screen: Exam Taker (Student) (maps to Journey Stage: أداء)
- **Layout:** Question card with progress bar + question navigator
- **Components:** QuestionCard, ProgressBar, QuestionNavigatorGrid, TimerWidget, FlagButton, CalculatorButton (if math), SubmitButton
- **States:** Empty → "الاختبار جاهز" → start button; Loading → Downloading exam for offline; Error → Connection lost → "سيتم حفظ الإجابات محلياً" + offline indicator; Edge → Time ran out → auto-submit with remaining answers
- **Key Interaction:** Swipe left/right between questions; Tap flag to mark for review
- **Friction Resolved:** #2 — Offline mode + auto-save per question

## Screen: Analytics Dashboard (Instructor) (maps to Journey Stage: تحليل)
- **Layout:** Summary cards row + interactive charts + question breakdown table
- **Components:** SummaryMetricCard (avg score, pass rate, highest, lowest), ScoreDistributionChart, QuestionDifficultyChart, StudentScoreTable, DrillDownFilter (by section, by topic)
- **States:** Empty → "لا توجد بيانات تحليلية بعد" (no exams graded); Loading → Chart skeleton; Error → "فشل تحميل التحليلات" → retry; Edge → One section has 0% pass rate → highlight in red with recommendation
- **Key Interaction:** Tap bar in chart → filter table to that cohort
- **Friction Resolved:** #6 — Granular analytics without overwhelming

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| QuestionCard | MCQ, TF, Essay, Matching | default/flagged/answered/skipped | Swipe navigation |
| TimerWidget | countdown, elapsed | normal/warning/danger | Red blink when <5min |
| ProgressBar | linear, stepped | 0-100% | Animated segments |
| NavigatorGrid | 10, 20, 30 questions | answered/skipped/flagged/review | Color-coded dots |
| Chart | bar, line, pie | loading/loaded/empty/animated | Interactive tap |
| QuestionEditor | rich-text, code, math | editing/saving/error | Image + LaTeX support |
| ProctoringAlert | face, audio, screen | warning/flag/kicked | 3 violations → auto-submit |
