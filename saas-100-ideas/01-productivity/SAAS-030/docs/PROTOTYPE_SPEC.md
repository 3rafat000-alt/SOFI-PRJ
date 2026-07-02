# PROTOTYPE SPEC — TimeSheet Pro (SAAS-030)
> Owner: UI/UX Designer · Gate 2

## Screen: Timer — المؤقت الرئيسي (مقابل Stage: تشغيل)
- **Layout:** دائرة زمنية كبيرة مع الوقت (HH:MM:SS) في المنتصف. زر تشغيل/إيقاف أسفلها. اختيار المشروع والمهمة في الأعلى. ساعة التوقف.
- **Components:** TimerCircle, StartStopButton, ProjectSelect, TaskSelect, RunningIndicator
- **States:** Idle (متوقف), Running (يعمل), Paused, Error (تعطل المؤقت), Edge (24+ ساعة متصلة)
- **Key Interaction:** ضغط زر التشغيل ← يختار مشروع ومهمة ← يبدأ العد
- **Friction Resolved:** #1 (ينسى اختيار المهمة → auto-select)

## Screen: ManualEntry — إدخال يدوي (مقابل Stage: إدخال)
- **Layout:** حقل اختيار مشروع ومهمة. منتقي وقت (من/إلى). حقل وصف. زر حفظ. مؤشر "هذا الإدخال للوقت الماضي".
- **Components:** ProjectTaskPicker, TimeRangePicker, DurationInput, DescriptionField, SaveButton
- **States:** Empty (إدخال جديد), Loading (حفظ), Error (وقت متداخل مع إدخال موجود), Edge (تاريخ في المستقبل)
- **Key Interaction:** اختيار الوقت ← اختيار مشروع ← وصف ← حفظ
- **Friction Resolved:** #3 (إضافة وقت فات)

## Screen: Reports — التقارير (مقابل Stage: تقرير)
- **Layout:** شريط تحديد الفترة (يوم/أسبوع/شهر/مخصص). رسم بياني شريطي للساعات لكل مشروع. جدول تفصيلي. مقارنة المخطط vs الفعلي.
- **Components:** PeriodTabs, BarChart, DetailTable, PlanVsActual, ExportDropdown
- **States:** Empty (لا إدخالات), Loading (حساب), Error (لا مشاريع), Edge (نطاق سنة)
- **Key Interaction:** نقر على شريط ← تفاصيل المشروع في الجدول
- **Friction Resolved:** #4 (التقرير يظهر ساعات غير متوقعة)

## Screen: Projects — إدارة المشاريع (مقابل Stage: لوحة)
- **Layout:** قائمة مشاريع مع كلIENT. بطاقة مشروع (اسم، ساعات مخطط/فعلي، تقدم %، لون). إضافة مشروع جديد.
- **Components:** ProjectCard, ClientBadge, ProgressBar, BudgetIndicator, AddProjectButton
- **States:** Empty (لا مشاريع), Loading, Error, Edge (مشروع بدون Client)
- **Key Interaction:** نقر على بطاقة ← تفاصيل المشروع مع كل المهام والإدخالات
- **Friction Resolved:** #2 (تقدير ساعات العمل بالتخمين → بيانات فعلية)

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| TimerCircle | Large/Small | Idle(رمادي)/Running(أخضر)/Paused(أصفر) | ينبض أثناء التشغيل |
| StartStopButton | Start/Stop | Enabled/Disabled/Loading | أحمر في Stop، أخضر في Start |
| TimeRangePicker | From/To | Open/Closed/Invalid | يمنع اختيار وقت في المستقبل للإدخال اليدوي |
| PlanVsActual | Bar/Line | Under Budget/On Budget/Over Budget | أحمر إذا تجاوز 100% من المخطط |
| ExportDropdown | PDF/CSV/Excel | Default/Loading | يعرض تنسيقات ويفتح معاينة قبل التصدير |
