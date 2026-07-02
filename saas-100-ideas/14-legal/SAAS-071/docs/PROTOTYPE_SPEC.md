# PROTOTYPE SPEC — LegalConsult (SAAS-071)
> Owner: UI/UX Designer · Gate 2

## Screen: Lawyer List (maps to Journey Stage: بحث)
- **Layout:** Vertical scrollable list with horizontal filter chips
- **Components:** SearchBar, FilterChips (specialty, city, price, rating), LawyerCard (name, specialty, rating, price, photo)
- **States:** Empty → "لا يوجد محامون حسب بحثك" + illustration; Loading → Skeleton cards (3); Error → Retry banner with refresh; Edge → No results for tight filter combination → suggest removing filters
- **Key Interaction:** Tap card → Lawyer Profile; Pull-to-refresh
- **Friction Resolved:** #1 — Search with filters now precise

## Screen: Lawyer Profile (maps to Journey Stage: تصفح)
- **Layout:** Scrollable profile with header, bio, specialties, ratings, calendar
- **Components:** ProfileHeader (photo, name, bar number, rating), SpecialtyTags, RatingCarousel, AvailabilityCalendar, BookButton
- **States:** Empty → N/A (profile always loaded); Loading → Skeleton; Error → "تعذر تحميل الملف" → retry; Edge → Lawyer not available this week → show next availability
- **Key Interaction:** Select date/time slot → Book flow
- **Friction Resolved:** #2 — Clear pricing and availability upfront

## Screen: Video Consultation (maps to Journey Stage: استشارة)
- **Layout:** Full-screen video with bottom control bar
- **Components:** VideoView, ControlBar (mute, camera, hangup, chat), DocumentShareButton, Timer, ConnectionStatus
- **States:** Empty → Pre-call lobby (waiting for lawyer); Loading → Connecting spinner; Error → "انقطع الاتصال" → rejoin button; Edge → Poor connection → audio-only fallback
- **Key Interaction:** Share document during call; End call → Review prompt
- **Friction Resolved:** #3 — Document sharing during consultation

## Screen: Document Manager (maps to Journey Stage: وثائق)
- **Layout:** Tab bar (My Documents | Shared with Me | Upload)
- **Components:** DocumentCard (name, date, size, shared badge), UploadFAB, FilePreview, ShareDialog
- **States:** Empty → "لا توجد وثائق" + upload CTA; Loading → Progress per file; Error → Upload failed → retry individual; Edge → File too large (>25MB) → show compression option
- **Key Interaction:** Swipe to delete; Tap to preview; Share button → select lawyer
- **Friction Resolved:** #4 — Large file upload with retry and compression

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Button Primary | Full-width, icon-left, icon-only | hover/active/disabled/loading | bg-navy text-white, 8px radius |
| Button Secondary | Same variants | hover/active/disabled | border-navy text-navy |
| Input Field | text, textarea, phone, password | default/focus/error/disabled | border 1px #D0D5DD, 12px padding |
| LawyerCard | compact, detailed | default/pressed | Shadow-sm, 12px radius |
| FilterChip | single, multi-select | unselected/selected/disabled | Pill shape 20px |
| BottomSheet | OptionPicker, CalendarPicker | open/closing | Drag to dismiss, 40% default |
| Toast | success, error, warning | show/hide | Slide from top, 3s auto-dismiss |
| Avatar | 32/40/56/80px | online/offline/busy | Circle with status dot |
| Modal | Alert, Confirm, ImagePreview | open/closing | Overlay 80% opacity |
