# PROTOTYPE SPEC — CourseCraft (SAAS-048)
> Owner: UI/UX Designer · Gate 2

## Screen: Course Builder (maps to Journey Stage: Create)
- **Layout:** Left sidebar (module list, drag-drop), main area (lesson editor), right panel (settings)
- **Components:** ModuleList, LessonCard, DragHandle, EditorPane, PublishButton
- **States:**
  - Empty: "Add your first module to start building"
  - Loading: Skeleton structure
  - Error: "Auto-save failed" + manual save button
  - Edge: 20+ modules — collapsible sections, search
- **Key Interaction:** Drag module → reorder; click lesson → edit in main pane
- **Friction Resolved:** [#3] تعديل الدروس بعد النشر

## Screen: Lesson Editor (maps to Journey Stage: Upload)
- **Layout:** Video upload with progress, text editor (rich), quiz creator tab
- **Components:** VideoUploader, RichTextEditor, QuizCreatorTab, ProgressBar, SaveButton
- **States:**
  - Uploading: Progress bar with speed + ETA
  - Processing: "Processing video..." (HLS conversion)
  - Error: "Upload failed — retry" (resumable)
  - Empty: "Drag video or click to upload"
- **Key Interaction:** Upload video → auto-process → preview available
- **Friction Resolved:** [#1] رفع فيديو مع تقدم + قابل للاستئناف

## Screen: Course Player (maps to Journey Stage: Learn)
- **Layout:** Video player (top 60%), lesson content (bottom 40%), sidebar (module/lesson list)
- **Components:** VideoPlayer, LessonContent, SidebarNav, ProgressIndicator, QuizInline
- **States:**
  - Loading: Video buffering spinner
  - Playing: Video with controls
  - Error: "Video unavailable" + retry
  - Completed: Green checkmark, next lesson CTA
- **Key Interaction:** Play video → auto-mark progress → scroll to content
- **Friction Resolved:** [#2] HLS + adaptive bitrate

## Screen: Quiz (maps to Journey Stage: Quiz)
- **Layout:** Question + options, timer (if set), submit button, progress dots
- **Components:** QuestionCard, OptionList, Timer, SubmitButton, ProgressDots
- **States:**
  - Not started: "Ready to start quiz?" with info
  - In progress: Timer counting, questions
  - Submitted: Score + correct/wrong indicators
  - Failed: "Score too low" + retry button
- **Key Interaction:** Select option → next question → submit → instant result
- **Friction Resolved:** [#4] Feedback فوري بعد الاختبار

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| ModuleCard | collapsed/expanded | default, dragging, editing | Drop zone indicator |
| LessonCard | video/text/quiz | locked, available, completed | Green check when done |
| VideoUploader | drag-drop / click | uploading, processing, error, done | Resumable, chunked upload |
| VideoPlayer | HLS with quality selector | play, pause, buffering, error | Quality switch (auto/720p/480p) |
| QuizQuestion | multiple-choice/tf/essay | unanswered, selected, correct, wrong | Instant feedback |
| ProgressBar | lesson/module/course | default, milestone | Percentage + step markers |
| CertificateView | PDF preview with QR | generated, downloadable | QR links to verification |
