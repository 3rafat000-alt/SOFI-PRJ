# PROTOTYPE SPEC — EduFinance (SAAS-092)
> Owner: UI/UX Designer · Gate 2

## Screen: Dashboard (Journey Stage: البحث عن تمويل)
- **Layout:** Hero section مع حاسبة تمويل، 3 بطاقات (قروض/منح/جامعات)
- **Components:** CalculatorBar, StatCard, UniversityCard, QuickActionButton
- **States:** Empty (بدون نشاط)، Loading (Skeleton)، Error (فشل التحميل)، Edge (نتائج كثيرة)
- **Key Interaction:** إدخال مبلغ ← عرض خيارات فورية
- **Friction Resolved:** #1 — تعقيد التقديم

## Screen: Loan Comparison (Journey Stage: المقارنة)
- **Layout:** جدول مقارنة + فلترة (ربوي/إسلامي + مدة + بنك)
- **Components:** ComparisonTable, FilterBar, ProgressBar, Tag (حلال/ممتاز/جديد)
- **States:** Empty (لا توجد عروض)، Loading (Spinner)، Error (فشل الفلترة)، Edge (عروض كثيرة)
- **Key Interaction:** تحديد 2-4 عروض للمقارنة الجانبية
- **Friction Resolved:** #2 — الشفافية في الشروط

## Screen: Application (Journey Stage: تقديم الطلب)
- **Layout:** نموذج متعدد الخطوات (Stepper) مع شريط تقدم
- **Components:** FormStepper, FileUploader, SignaturePad, DocumentPreview
- **States:** Empty (استمارة جديدة)، Loading (رفع مستند)، Error (فشل الإرسال)، Edge (استئناف طلب سابق)
- **Key Interaction:** رفع مستندات + توقيع إلكتروني
- **Friction Resolved:** #3 — بطء المعالجة

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| CalculatorBar | slider + input | default, dragging, result | حساب القسط الشهري الفوري |
| ComparisonTable | 2-col, 3-col, 4-col | default, hover, selected | مقارنة جانبية مع تمييز الأفضل |
| FileUploader | single, multiple | default, dragging, uploading, success, error | رفع + معاينة + ضغط تلقائي |
| FormStepper | vertical, horizontal | active, completed, pending, error | حفظ تلقائي في كل خطوة |
| LoanCard | recommended, standard | default, hover, selected | بطاقة عرض تمويل مع تفاصيل |
| ProgressTracker | percentage, steps | in-progress, approved, rejected, completed | تتبع حالة الطلب |
