# PROTOTYPE SPEC — LoyaltyBox (SAAS-026)
> Owner: UI/UX Designer · Gate 2

## Screen: SetupWizard — إعداد برنامج الولاء (مقابل Stage: إعداد)
- **Layout:** معالج 3 خطوات: (1) اسم المتجر والشعار (2) قواعد النقاط (كم ريال = نقطة) (3) المكافآت. شريط تقدم.
- **Components:** StepIndicator, LogoUpload, RuleForm, RewardList, PreviewCard
- **States:** Empty (برنامج جديد), Loading (حفظ), Error (قواعد غير صحيحة), Edge (مكافآت بدون نقاط)
- **Key Interaction:** إضافة مكافأة بتحديد اسم ونقاط ← حفظ
- **Friction Resolved:** #2 (خيارات كثيرة - تقليص لـ 3 خطوات)

## Screen: Customer Wallet — محفظة العميل (مقابل Stage: انضمام)
- **Layout:** بطاقات الولاء (كل متجر بطاقة). إجمالي النقاط. شريط تقدم لأقرب مكافأة. زر مسح QR.
- **Components:** LoyaltyCard, PointsCounter, ProgressBar, QRButton, MerchantList
- **States:** Empty (لا اشتراكات), Loading (جلب), Error (المحفظة لا تحمل), Edge (50+ بطاقة)
- **Key Interaction:** نقر على بطاقة ← تفاصيل المتجر والمعاملات
- **Friction Resolved:** #3 (الزبون يحتاج تحميل تطبيق → Web App)

## Screen: POS Scan — نقطة البيع (مقابل Stage: شراء)
- **Layout:** كاميرا QR مع إطار. بعد المسح: اسم العميل، رصيده، إضافة معاملة (إيداع نقاط/استرداد).
- **Components:** CameraScanner, CustomerInfo, PointsInput, TransactionType, ConfirmButton
- **States:** Active, Scanning, CustomerFound, Error (عميل غير معروف), Edge (نقاط سالبة)
- **Key Interaction:** مسح QR ← يظهر العميل ← إدخال المبلغ ← إضافة نقاط
- **Friction Resolved:** #1 (البائع ينسى إدخال النقاط → إشعار تذكير)

## Screen: Reports — التقارير (مقابل Stage: تقارير)
- **Layout:** إجمالي الأعضاء، النقاط المصروفة، تكلفة المكافآت، ROI. جدول بأكثر العملاء ولاءً. رسم بياني لاتجاه النقاط.
- **Components:** MetricCards, TopCustomers, PointsChart, ROICalculator, ExportButton
- **States:** Empty (لا بيانات كافية), Loading (حساب), Error (نطاق غير صحيح), Edge (سنة بيانات)
- **Key Interaction:** تغيير الفترة ← تحديث كل المؤشرات
- **Friction Resolved:** #4 (الأرقام لا تظهر ROI)

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| LoyaltyCard | Active/Expired | Default/Hover | يعرض اسم المتجر والنقاط مع ألوان المتجر |
| PointsCounter | Animated/Static | Increment/Decrement | animate العدد عند إضافة نقاط |
| ProgressBar | 0-100% | Percent/Complete | يهتز عند اكتمال المكافأة |
| CameraScanner | QR/Barcode | Idle/Scanning/Success/Error | فلاش أخضر عند النجاح |
| StepIndicator | 3 steps | Completed/Active/Pending | يعود للتعديل |
