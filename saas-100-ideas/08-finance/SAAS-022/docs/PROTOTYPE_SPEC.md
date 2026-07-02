# PROTOTYPE SPEC — InvoiceFlow (SAAS-022)
> Owner: UI/UX Designer · Gate 2

## Screen: Dashboard — لوحة التحكم (مقابل Stage: لوحة)
- **Layout:** 3 بطاقات KPI (إجمالي الفواتير، المدفوع، المتأخر). جدول آخر 5 فواتير. رسم بياني دائري للحالات.
- **Components:** KPICards, DataTable, PieChart, Action Buttons
- **States:** Empty (لا فواتير بعد), Loading (جلب البيانات), Error (تعذر التحميل), Edge (حساب بعملة مختلفة)
- **Key Interaction:** نقر على بطاقة → يفتح الفلترة المقابلة
- **Friction Resolved:** #2 (أخطاء حساب VAT)

## Screen: InvoiceForm — إنشاء فاتورة (مقابل Stage: فاتورة)
- **Layout:** شريط جانبي للعميل والحقول الأساسية، جدول بنود مع إضافة صفوف. معاينة جانبية للسعر. حساب VAT تلقائي.
- **Components:** ClientSelect, ItemsTable, TaxBadge, TotalBar, PreviewPane
- **States:** Empty (فاتورة جديدة), Loading (جلب بيانات العميل), Error (VAT غير صحيح), Edge (فاتورة بآلاف البنود)
- **Key Interaction:** إضافة بند جديد ← حساب تلقائي للمجموع ← معاينة
- **Friction Resolved:** #1 (إضافة بنود يدوية)

## Screen: Send — إرسال الفاتورة (مقابل Stage: إرسال)
- **Layout:** تأكيد الإرسال مع معاينة PDF واختيار قنوات (إيميل، واتساب). حقل رسالة اختيارية.
- **Components:** PDFPreview, ChannelSelector, MessageInput, SendButton
- **States:** Empty, Loading (إرسال), Error (الإيميل خطأ), Edge (الفاتورة كبيرة جداً)
- **Key Interaction:** اختيار قناة ← إرسال ← تأكيد
- **Friction Resolved:** #4 (العميل لا يستلم الإيميل)

## Screen: Tracking — تتبع الدفعات (مقابل Stage: متابعة)
- **Layout:** جدول فواتير مع ألوان حسب الحالة (مدفوع أخضر، متأخر أحمر). شريط تقدم للـ AR aging.
- **Components:** StatusTable, AgingBar, FilterChips, RemindButton
- **States:** Empty (كل الفواتير مدفوعة), Loading, Error, Edge (500+ فاتورة)
- **Key Interaction:** نقر على فاتورة متأخرة ← إرسال تذكير
- **Friction Resolved:** #3 (تذكير الدفع)

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| KPICard | Revenue/Pending/Overdue | Default/Hover | تظهر مع أيقونة وفرق عن الشهر الماضي |
| DataTable | Invoices/Clients | Sortable/Filterable/Scrollable | فرز حسب العمود، فلترة حسب الحالة |
| ClientSelect | Search/Recent | Empty/Results/Selected | بحث مع اقتراحات من جهات الاتصال |
| TaxBadge | Inclusive/Exclusive | 5%/15%/0% | يتغير لونه حسب النسبة |
| StatusBadge | Draft/Sent/Paid/Overdue | كل حالة بلون مختلف | يتحرك للعمود مع التحديث |
