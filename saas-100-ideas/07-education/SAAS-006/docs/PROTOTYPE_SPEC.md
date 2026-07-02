# PROTOTYPE SPEC — EduCloud LMS (SAAS-006)
> Owner: UI/UX Designer · Gate 2

## Screen 1: Course Builder (maps to: Create Course → Add Content)
- **Layout:** Left: module tree (sortable accordion), Right: lesson editor (title, type selector, content area, attachments)
- **Components:** Accordion (module list), Drag handle, Type selector tab (video/PDF/text/quiz), Rich text editor, Upload zone, Lesson ordering buttons
- **States:**
  - Empty: "Add your first module. Modules group your lessons."
  - Loading: Autosave indicator
  - Error: Save conflict → timestamp warning
  - Edge: Reorder by drag, deep nesting (module → lesson → resource)
- **Key Interaction:** Drag module → reorder → click "+" → add lesson → type content
- **Friction Resolved:** #3 — intuitive tree structure

## Screen 2: Quiz Builder (maps to: Add Quiz)
- **Layout:** Question list (sortable), Question editor: type selector (MCQ/TF/essay), question text, options, correct answer, points
- **Components:** Question card, Type selector, Option row (+ add option), Correct answer toggle, Point input, Preview button
- **States:**
  - Empty: "No questions yet. Add your first question."
  - Loading: Saving question
  - Error: Validation (no correct answer selected)
  - Edge: Randomize option order, time limit per question
- **Key Interaction:** Select type → type question → add options → mark correct → save
- **Friction Resolved:** #2 — auto-graded MCQs

## Screen 3: Lesson Player (Student) (maps to: Learn)
- **Layout:** Top: video player (if video), Below: lesson title, content scroll, progress bar, "Complete" button, Next/Previous navigation
- **Components:** Video player (HLS.js), Content renderer (markdown/PDF), Progress indicator, Complete button, Module sidebar
- **States:**
  - Loading: Video buffering spinner
  - Error: Video fail → download fallback
  - Edge: Offline-downloaded lessons, speed control (0.5x-2x)
- **Key Interaction:** Watch video → scroll to read → tap "إكمال الدرس" → progress updates
- **Friction Resolved:** #4 — trackable progress per lesson

## Screen 4: Certificate Generator (maps to: Certificate)
- **Layout:** Certificate preview (real-time), Student name, course name, date, unique QR code, "Download PDF" button
- **Components:** Certificate canvas (HTML2PDF), QR code (unique hash), Issue date auto, Share buttons
- **States:**
  - Empty: "Complete the course to earn your certificate"
  - Loading: Generating PDF
  - Error: QR generation failure → retry
  - Edge: Custom template per academy
- **Key Interaction:** Tap "إصدار الشهادة" → preview → download
- **Friction Resolved:** #5 — instant digital certificate with verification

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Module Accordion | Collapsed, Expanded | default/dragging/editing | animated expand, drag to reorder |
| Lesson Card | Video, PDF, Text, Quiz icon | default/complete/locked | icon + title + duration |
| Video Player | HLS, MP4 | loading/playing/paused/error | buffering spinner, speed control |
| Question Card | MCQ, TF, Essay | default/answered/correct/wrong | auto-highlight correct answer |
| Quiz Result | Pass, Fail | score-circle/graded/retake | passing score configurable |
| Progress Bar | Course, Module | percentage/step | animated fill, milestone markers |
| Certificate | Preview, PDF | generated/verified/pending | QR code scan to verify |
