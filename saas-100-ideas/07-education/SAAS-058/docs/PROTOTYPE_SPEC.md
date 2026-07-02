# PROTOTYPE SPEC — TutorMatch (SAAS-058)
> Owner: UI/UX Designer · Gate 2

## Screen: Search Tutors (maps to Journey Stage: البحث عن مدرس)
- **Layout:** Subject selector + university filter + price range + rating filter + results grid
- **Components:** SubjectDropdown, UniversityChip, PriceRangeSlider, RatingFilter, TutorCard
- **States:** Empty (no tutors match → "حاول تغيير الفلترة"), Loading (skeleton cards ×4), Error (search failed → retry), Edge (tutor offline → "غير متاح حالياً")
- **Key Interaction:** Select subject → filter → results appear → tap card for profile
- **Friction Resolved:** [#1] صعوبة إيجاد مدرسين → بحث متقدم بفلترة دقيقة

## Screen: Tutor Profile (maps to Journey Stage: عرض ملف المدرس)
- **Layout:** Tutor photo + bio + qualifications + ratings + availability calendar + book button
- **Components:** TutorPhotoBadge, BioSection, QualificationList, RatingStars, AvailabilityGrid, BookButton
- **States:** Loading (profile), Error (profile not found), Edge (tutor fully booked → "جميع الأوقات محجوزة")
- **Key Interaction:** View availability → select slot → tap book
- **Friction Resolved:** [#2] عدم معرفة الجودة → تقييمات وملف موثق

## Screen: Book Session (maps to Journey Stage: حجز الحصة)
- **Layout:** Session summary + time slot + online/in-person toggle + payment + confirm
- **Components:** SessionSummaryCard, TimeSlotPicker, ModeToggle, EscrowPaymentInfo, ConfirmButton
- **States:** Loading (booking), Error (payment failed → retry), Edge (escrow hold → "سيتم الإفراج بعد الحصة")
- **Key Interaction:** Select time → choose mode → pay → confirm
- **Friction Resolved:** [#3] إلغاء الحصص → دفع آمن (escrow)

## Screen: Student Progress (maps to Journey Stage: متابعة التقدم)
- **Layout:** GPA tracker + subject progress bars + goals list + recent sessions
- **Components:** GaugeRing, ProgressBar, GoalCard, SessionHistoryItem
- **States:** Empty (no data yet → "ابدأ حصصك الأولى"), Loading, Error

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| TutorCard | grid, list | available, busy, offline | Badge if verified |
| RatingStars | interactive, read-only | 0-5 stars | Half-star precision |
| AvailabilityGrid | week view | free, booked, selected | Tap to select slot |
| EscrowPaymentInfo | default | holding, released, refunded | Status indicator |
| GaugeRing | GPA scale | 0.0-4.0 | Color-coded by grade |
