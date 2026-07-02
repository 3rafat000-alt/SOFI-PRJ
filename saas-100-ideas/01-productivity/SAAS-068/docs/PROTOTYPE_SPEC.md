# PROTOTYPE SPEC — CoachingPro (SAAS-068)
> Owner: UI/UX Designer · Gate 2

## Screen: Course Builder (maps to Journey Stage: إنشاء الدورة)
- **Layout:** Left sidebar module list (drag to reorder), right panel lesson editor (add video/PDF/quiz)
- **Components:** ModuleList (drag-drop), LessonCard, VideoUploader, PDFUploader, QuizCreator, ContentPreview
- **States:** Empty (new course) | Loading (saving) | Error (upload failed) | Edge (template import from existing course)
- **Key Interaction:** Drag module to reorder, click lesson to edit inline
- **Friction Resolved:** #1 (all content in one builder)

## Screen: Course Catalog (maps to Journey Stage: اكتشاف الدورة)
- **Layout:** Grid of course cards with thumbnail, title, rating, price, filter sidebar (category/level/price range)
- **Components:** CourseCard, FilterSidebar, SearchInput, SortDropdown, Pagination
- **States:** Empty (no courses yet) | Loading (skeleton grid) | Error (search failed) | Edge (no results — suggest popular)
- **Key Interaction:** Click course → detail page with syllabus and enrollment CTA
- **Friction Resolved:** #2 (discovery and comparison)

## Screen: Lesson Player (maps to Journey Stage: التعلم)
- **Layout:** Full-width video player, below lesson description + resources, right sidebar playlist
- **Components:** VideoPlayer (Vimeo/Stream), PlaylistSidebar, ResourceDownload, MarkCompleteButton, NotesField
- **States:** Empty (no content) | Loading (buffering) | Error (video unavailable) | Edge (offline — show cached)
- **Key Interaction:** Video auto-pauses on tab switch, syncs progress
- **Friction Resolved:** #3 (centralized learning experience)

## Screen: Quiz Engine (maps to Journey Stage: الاختبار)
- **Layout:** Question card with timer, progress bar top, single question visible, next/previous navigation
- **Components:** QuestionCard, TimerBar, ProgressDots, OptionList, SubmitButton, ResultFeedback
- **States:** Empty (no questions) | Loading (generating) | Error (submission failed) | Edge (timeout — auto-submit)
- **Key Interaction:** Select option → instant correct/incorrect feedback
- **Friction Resolved:** #4 (automated assessment)

## Screen: Certificate Viewer (maps to Journey Stage: الشهادة)
- **Layout:** Elegant certificate preview with QR verification code, download and share buttons
- **Components:** CertificatePreview (SVG), QRCodeBadge, DownloadButton, ShareButton, VerifyInput
- **States:** Empty (not yet issued) | Loading (generating) | Error (generation failed) | Edge (expired certificate)
- **Key Interaction:** Click "Verify" → enter code → shows original certificate data
- **Friction Resolved:** #2 (instant certificate generation)

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Button | Primary #2563EB, Secondary #14B8A6, Ghost | hover/active/disabled/loading | |
| CourseCard | Horizontal/Vertical | default/hover/enrolled | Enrolled shows progress bar |
| VideoPlayer | Vimeo/Stream/HTML5 | loading/playing/paused/error/offline | Playback position sync |
| QuizOption | Radio/Checkbox | default/selected/correct/incorrect | Instant feedback |
| ProgressBar | Linear/Circular | percentage display | Animated transition |
| CertificatePreview | SVG template | generating/ready/error | QR code for verification |
| ModuleList | Expandable/Collapsed | default/dragging/editing | Drag to reorder items |
| LessonCard | Video/PDF/Quiz/Assignment | completed/in-progress/locked | Icons for type |
| TimerBar | Countdown | running/paused/expired | Warning flash at 30s |
