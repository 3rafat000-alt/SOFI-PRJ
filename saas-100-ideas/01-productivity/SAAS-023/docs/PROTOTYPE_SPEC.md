# PROTOTYPE SPEC — HRTide (SAAS-023)
> Owner: UI/UX Designer · Gate 2

## Screen: Dashboard — لوحة HR (مقابل Stage: لوحة)
- **Layout:** 4 بطاقات (عدد الموظفين، إجازات اليوم، غياب، تحت التجربة). تقويم صغير. جدول آخر الطلبات.
- **Components:** KPICard, MiniCalendar, RequestTable, QuickActions
- **States:** Empty (لا موظفين بعد), Loading (جلب البيانات), Error (تعذر التحميل), Edge (أكثر من 200 موظف)
- **Key Interaction:** نقر على بطاقة ← يفتح الشاشة المختصة
- **Friction Resolved:** #1 (أرقام كثيرة بدون سياق)

## Screen: Employees — إدارة الموظفين (مقابل Stage: موظفين)
- **Layout:** معالج إضافة موظف بـ 3 خطوات (معلومات أساسية ← وظيفية ← مصرفية). جدول موظفين مع بحث وفلترة. بطاقة موظف.
- **Components:** WizardStepper, EmployeeTable, EmployeeCard, SearchField, FilterDropdown
- **States:** Empty (البحث لا نتائج), Loading (جلب الموظفين), Error (تعذر الحفظ), Edge (رقم الهاتف مكرر)
- **Key Interaction:** إضافة موظف ← معالج 3 خطوات ← حفظ
- **Friction Resolved:** #1 (حقول كثيرة)

## Screen: Leaves — إدارة الإجازات (مقابل Stage: إجازات)
- **Layout:** تقويم إجازات (ألوان حسب النوع). طلبات معلقة مع موافقة/رفض. رصيد كل موظف.
- **Components:** LeaveCalendar, RequestCard, ApproveButton, RejectButton, BalanceBadge
- **States:** Empty (لا إجازات), Loading, Error, Edge (إجازة تتجاوز الرصيد)
- **Key Interaction:** نقر على طلب ← بطاقة ← موافقة/رفض مع سبب
- **Friction Resolved:** #3 (كثير طلبات متزامنة - Batch approval)

## Screen: Payroll — الرواتب (مقابل Stage: رواتب)
- **Layout:** تحديد الشهر، زر تشغيل الراتب، معاينة كشوفات، أزرار اعتماد وتصدير. جدول الموظفين مع الراتب والبدلات والخصوم.
- **Components:** PeriodSelector, RunPayrollButton, PayrollTable, ApproveButton, ExportButton
- **States:** Empty (لا رواتب بعد), Loading (حساب الرواتب), Error (حساب خاطئ), Edge (راتب متغير)
- **Key Interaction:** اختيار فترة ← تشغيل ← مراجعة ← اعتماد
- **Friction Resolved:** #2 (حسابات الرواتب معقدة)

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| WizardStepper | 3/4/5 steps | Active/Completed/Pending | يعود للتعديل بنقر على الخطوة |
| EmployeeCard | View/Edit | Default/Selected | يعرض صورة، اسم، قسم، منصب |
| LeaveCalendar | Monthly/Weekly | Day/Event/Overlap | أيام الإجازة ملونة حسب النوع |
| BalanceBadge | Annual/Sick/Emergency | Available/Used/Exhausted | أحمر عند نفاد الرصيد |
| PayrollTable | Editable/ReadOnly | Cell/Row/Valid/Error | خلايا قابلة للتعديل قبل الاعتماد |
