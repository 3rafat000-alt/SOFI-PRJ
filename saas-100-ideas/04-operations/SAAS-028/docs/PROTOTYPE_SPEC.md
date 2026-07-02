# PROTOTYPE SPEC — AssetGuard (SAAS-028)
> Owner: UI/UX Designer · Gate 2

## Screen: Dashboard — لوحة الأصول (مقابل Stage: لوحة)
- **Layout:** 4 بطاقات (إجمالي الأصول، قيد الصيانة، Tickets مفتوحة، قيمة الأصول). خريطة بمواقع الأصول. آخر التذاكر.
- **Components:** MetricCards, AssetMap, TicketFeed, CategoryDonut, QuickFilters
- **States:** Empty (لا أصول بعد), Loading (جلب), Error (تعذر التحميل), Edge (10000+ أصل)
- **Key Interaction:** نقر على بطاقة فئة ← فلترة قائمة الأصول
- **Friction Resolved:** #4 (التقارير لا تصدر PDF)

## Screen: AddAsset — إضافة أصل (مقابل Stage: إضافة)
- **Layout:** معالج خطوات: (1) معلومات عامة (اسم، تصنيف) (2) موقع (مبنى، طابق، غرفة) (3) شراء (تاريخ، سعر، مدة ضمان). معاينة QR.
- **Components:** MultiStepForm, CategorySelect, LocationTree, PurchaseFields, QRPreview
- **States:** Empty (جديد), Loading (حفظ), Error (رقم تسلسلي مكرر), Edge (أصل بدون موقع)
- **Key Interaction:** إكمال الحقول ← معاينة QR ← حفظ
- **Friction Resolved:** #1 (20+ حقل → معالج خطوات)

## Screen: Audit — الجرد الميداني (مقابل Stage: جرد)
- **Layout:** كاميرا QR full-screen. بعد المسح: تأكيد الوجود، تحديث الحالة (جيد/تالف/مفقود)، رفع صورة. تقدم الجرد.
- **Components:** CameraView, AssetInfoCard, StatusSelector, PhotoUpload, ProgressCounter
- **States:** Scanning, AssetFound, AssetNotFound (غير مسجل), Error (QR تالف), Edge (مكرر)
- **Key Interaction:** مسح QR ← تأكيد الحالة ← التالي
- **Friction Resolved:** #2 (QR لا يقرأ من مسافة)

## Screen: Maintenance — جدول الصيانة (مقابل Stage: صيانة)
- **Layout:** تقويم صيانة مع ألوان حسب النوع (وقائية/تصحيحية). قائمة صيانة مجدولة. تسجيل صيانة منتهية.
- **Components:** MaintenanceCalendar, TaskCard, CompleteForm, VendorField, CostInput
- **States:** Empty (لا صيانة مجدولة), Loading, Error (تعارض تواريخ), Edge (صيانة متأخرة)
- **Key Interaction:** نقر على مهمة ← تسجيل الإنجاز مع التكلفة
- **Friction Resolved:** #3 (صعوبة تحديد تكرار الصيانة)

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| AssetMap | Pin/Heatmap | Loading/Pins/Cluster | تجميع pins عند zoom out |
| MultiStepForm | 3/4/5 steps | Active/Completed/Error | يتحقق من كل خطوة قبل التالي |
| LocationTree | Building/Floor/Room | Collapsed/Expanded/Selected | هيكل شجري مع أرقام الأصول |
| CameraView | QR/Barcode | Idle/Scanning/Success/Error | اهتزاز + فلاش أخضر عند المسح |
| MaintenanceCalendar | Monthly/Weekly | Event/Overdue/Completed | أحمر للمتأخر، أخضر للمنجز |
