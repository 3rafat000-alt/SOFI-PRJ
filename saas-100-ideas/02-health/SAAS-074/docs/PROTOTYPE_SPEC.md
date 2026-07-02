# PROTOTYPE SPEC — NurseryPro (SAAS-074)
> Owner: UI/UX Designer · Gate 2

## Screen: Child Registration (maps to Journey Stage: تسجيل)
- **Layout:** Multi-step form (Profile → Guardian → Medical → Documents → Photo)
- **Components:** StepIndicator, ProfileForm, GuardianForm, MedicalNotesInput, DocumentUpload, ChildPhotoCapture, ConsentCheckbox
- **States:** Empty → "سجّل طفلك الآن في الحضانة"; Loading → Saving spinner; Error → "فشل الحفظ، حاول مرة أخرى" → retry; Edge → Age outside nursery range → "عمر الطفل خارج النطاق المسموح"
- **Key Interaction:** Take child photo with camera; Upload vaccination card
- **Friction Resolved:** #1 — Digital form replaces paper (cuts time 80%)

## Screen: Daily Log Entry (maps to Journey Stage: نشاطات)
- **Layout:** Tabbed form (Meals, Naps, Activities, Mood, Incidents)
- **Components:** MealSelector (breakfast/lunch/snack with photo), NapTimer, ActivityPicker, MoodEmojiSelector, IncidentReportForm, PhotoGallery
- **States:** Empty → "لم يتم تسجيل أي نشاط اليوم"; Loading → Saving; Error → Sync failed → queued offline; Edge → Multiple children for one staff → batch entry mode
- **Key Interaction:** Tap emoji for mood; Start/stop nap timer; Take activity photo
- **Friction Resolved:** #2 — Quick tap-entry instead of typing

## Screen: Parent Feed (maps to Journey Stage: تقارير)
- **Layout:** Infinite scroll timeline of child's day
- **Components:** TimelineCard (time, activity type, photo, description), DaySummaryHero, MoodTrendChart, PhotoGrid, PayFeesButton
- **States:** Empty → "سيتم نشر الأنشطة قريباً"; Loading → Skeleton timeline; Error → "تعذر التحميل" → pull to refresh; Edge → No updates today → "اليوم كان هادئاً!" cheerful message
- **Key Interaction:** Tap photo → full-screen gallery; Like/react to updates
- **Friction Resolved:** #3 — Real-time feed replaces end-of-day paper report

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| StepIndicator | 5-step | active/complete/pending | Animated progress |
| EmotionSelector | emoji, scale | unselected/selected | 5 emoji face scale |
| NapTimer | auto, manual | running/stopped/paused | Start/stop with elapsed |
| TimelineCard | meal, nap, activity, incident | with-photo/without | Expandable |
| FeeCard | paid, pending, overdue | normal/overdue | Red border if overdue |
| PhotoGrid | 1-col, 2-col, 3-col | loading/loaded/empty | Masonry layout |
| GuardianBadge | primary, secondary, emergency | default | Color-coded role |
