# PROTOTYPE SPEC — CourierMgt (SAAS-098)
> Owner: UI/UX Designer · Gate 2

## Screen: Live Tracking Map (Journey Stage: التوزيع + التوصيل)
- **Layout:** خريطة كاملة + قائمة مندوبين جانبية + حالة كل طرد
- **Components:** MapView, DriverMarker, ParcelCard, RouteLine, StatusPanel
- **States:** Loading (تحميل خريطة), Error (GPS معطل), Empty (لا مناديب نشطين), Edge (مناديب كثيرة)
- **Key Interaction:** ضغط على مندوب ← مسار توصيله ← تفاصيل الطرود
- **Friction Resolved:** #1 — ضعف التتبع

## Screen: Parcel Registration (Journey Stage: التسجيل)
- **Layout:** نموذج سريع + طباعة لاصقة + جدول الطرود المسجلة
- **Components:** FormFields, BarcodeScanner, LabelPrinter, ParcelTable
- **States:** Empty (بدون طرود), Loading (بحث عن عميل), Error (باركود مكرر)
- **Key Interaction:** Scan باركود ← تعبئة تلقائية ← حفظ ← طباعة
- **Friction Resolved:** #3 — تسجيل غير دقيق

## Screen: Driver App - Navigation (Journey Stage: التوصيل)
- **Layout:** خريطة + قائمة الطرود + إثبات التسليم
- **Components:** RouteNavigation, ParcelQueue, ProofOfDeliveryForm, CODSummary
- **States:** Loading (توجيه), Error (انقطاع), Edge (طرود كثيرة)
- **Key Interaction:** اتباع المسار ← توصيل ← تصوير ← توقيع
- **Friction Resolved:** #2 — إثبات التسليم

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| DriverMarker | available, busy, offline | default, selected, animating | علامة مندوب على الخريطة |
| ParcelCard | pending, in-transit, delivered, failed | default, expanded | بطاقة طرد مع الحالة |
| RouteLine | optimized, actual | planned, in-progress, completed | خط المسار على الخريطة |
| ProofOfDeliveryForm | photo + signature + GPS | empty, capturing, done, error | إثبات تسليم متكامل |
| CODSummary | collected, pending, settled | default, expanded | ملخص المدفوعات النقدية |
| StatusTimeline | stages with time | active, completed, pending | جدول زمني لحالة الطرد |
