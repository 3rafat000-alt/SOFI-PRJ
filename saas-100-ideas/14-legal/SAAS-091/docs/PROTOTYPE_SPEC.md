# PROTOTYPE SPEC — LawyerRef (SAAS-091)
> Owner: UI/UX Designer · Gate 2

## Screen: Search (Journey Stage: البحث)
- **Layout:** Header مع شعار + بحث، Results list مع فلتر جانبي
- **Components:** SearchBar, FilterChips, CategoryCard, ResultCard, Pagination
- **States:** Empty (اقتراحات تخصصات)، Loading (Skeleton cards)، Error (إعادة المحاولة)، Edge (نتائج كثيرة جداً)
- **Key Interaction:** كتابة + اختيار تخصص → نتائج فورية
- **Friction Resolved:** #2 — كثرة النتائج غير المفلترة

## Screen: Lawyer Profile (Journey Stage: الملف الشخصي)
- **Layout:** Hero (صورة + اسم)، Tabs (سيرة + تقييمات + تخصصات)، Call-to-action (احجز استشارة)
- **Components:** Avatar, RatingStars, TabBar, CertificationBadge, ReviewCard, PriceCard
- **States:** Empty (محامٍ جديد بدون تقييمات)، Loading (Skeleton)، Error (فشل تحميل الملف)، Edge (تقييمات كثيرة)
- **Key Interaction:** تصفح التقييمات ← ضغط "احجز استشارة"
- **Friction Resolved:** #1 — بناء الثقة

## Screen: Booking (Journey Stage: الحجز)
- **Layout:** تقويم + اختيار وقت + ملخص الحجز
- **Components:** CalendarPicker, TimeSlotGrid, PaymentCard, OrderSummary
- **States:** Empty (لا مواعيد متاحة)، Loading (معالجة الدفع)، Error (فشل الدفع)، Edge (فارق التوقيت)
- **Key Interaction:** اختيار وقت ← دفع ← تأكيد
- **Friction Resolved:** #3 — تعقيد الدفع

## Screen: Consultation Room (Journey Stage: الاستشارة)
- **Layout:** VideoCall main + Chat sidebar + أدوات القضية
- **Components:** VideoPlayer, ChatBubble, DocumentUploader, Whiteboard, Timer
- **States:** Loading (اتصال)، Error (انقطاع)، Edge (ضعف الشبكة)، Empty (بداية الاستشارة)
- **Key Interaction:** فيديو + مشاركة مستندات
- **Friction Resolved:** #4 — مشاكل اتصال

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| LawyerCard | compact, detailed | default, selected, offline | ضغط → ملف المحامي |
| RatingStars | 1-5 stars, with count | filled, empty, half | يعرض التقييم + العدد |
| SearchBar | with filter, without | default, focused, typing, results | بحث حي + اقتراحات |
| CalendarPicker | single, range, week | default, selected, disabled, full | اختيار تاريخ الحجز |
| PaymentCard | credit, stcpay, applepay | default, processing, success, failed | دفع آمن متعدد الطرق |
| ConsultationCard | upcoming, past, live | default, waiting, active, ended | متابعة المواعيد |
