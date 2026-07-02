# PROTOTYPE SPEC — AutoMarket (SAAS-100)
> Owner: UI/UX Designer · Gate 2

## Screen: Car Listing Search (Journey Stage: البحث)
- **Layout:** SearchBar + فلتر متقدم (الماركة/الموديل/السنة/المسافة/السعر) + نتائج Grid/List
- **Components:** SearchBar, FilterPanel, CarCard, ComparisonCheckbox, SortDropdown
- **States:** Empty (🚫 لا نتائج), Loading (Skeleton), Error (فشل), Edge (نتائج كثيرة) 
- **Key Interaction:** تصفية ← مقارنة ← اختيار ← معاينة
- **Friction Resolved:** #5 — إعلانات غير دقيقة

## Screen: Car Detail (Journey Stage: المعاينة)
- **Layout:** معرض صور + فيديو + تقرير فحص + مواصفات + سعر
- **Components:** ImageGallery360, VideoPlayer, InspectionReportBadge, SpecTable, PriceHistory, SellerCard
- **States:** Loading (صور), Error (فشل تحميل), Empty (بدون تقرير فحص)
- **Key Interaction:** تصفح الصور ← قراءة التقرير ← طلب معاينة/فحص
- **Friction Resolved:** #3 — الثقة في الحالة

## Screen: Contract & Payment (Journey Stage: الدفع + التوثيق)
- **Layout:** ملخص الصفقة + عقد إلكتروني + خيارات الدفع
- **Components:** DealSummary, ContractViewer, EscrowPaymentCard, SignaturePad, IDVerification
- **States:** Loading (معالجة الدفع), Error (فشل الدفع), Edge (تمويل خارجي)
- **Key Interaction:** قراءة العقد ← توقيع ← دفع ← استلام إيصال
- **Friction Resolved:** #2 — نقل الملكية

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| CarCard | grid, list, compact | default, compared, saved, sold | بطاقة سيارة + حالة |
| FilterPanel | basic, advanced, collapsed | default, expanded, applied | فلترة متعددة المعايير |
| InspectionReportBadge | pass, conditional, fail | default, clickable | شارة تقرير الفحص |
| ImageGallery360 | photos, video, vr | loading, fullscreen, swipeable | معاينة تفاعلية |
| PriceHistoryChart | line, bar | with-data, empty | تاريخ السعر |
| EscrowPaymentCard | credit, bank, wallet | pending, funded, released, refunded | دفع آمن بالضمان |
| ContractPreview | summary, full | not-signed, signed, verified | عقد إلكتروني + توثيق |
