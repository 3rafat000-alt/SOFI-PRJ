# PROTOTYPE SPEC — ShiftMaster (SAAS-027)
> Owner: UI/UX Designer · Gate 2

## Screen: Schedule — تقويم المناوبات (مقابل Stage: إنشاء)
- **Layout:** تقويم أسبوعي/شهري مع صفوف للموظفين وأعمدة للأيام. خلايا شفتات قابلة للسحب والإفلات. ألوان حسب نوع الشفت.
- **Components:** ScheduleGrid, ShiftBlock, EmployeeRow, WeekTabs, AutoScheduleButton
- **States:** Empty (أسبوع بدون شفتات), Loading (جلب الجدول), Error (تعارض), Edge (20+ موظف)
- **Key Interaction:** سحب ShiftBlock من موظف لآخر أو من يوم لآخر
- **Friction Resolved:** #1 (سحب وإفلات بطيء)

## Screen: Swap — طلبات التبديل (مقابل Stage: تبديل)
- **Layout:** قائمة طلبات تبديل معلقة. بطاقة طلب (طالب، شفت، مع من؟). أزرار موافقة/رفض. فلترة (معلقة/مقبولة/مرفوضة).
- **Components:** SwapRequestCard, ApproveButton, RejectButton, FilterChips, StatusBadge
- **States:** Empty (لا طلبات), Loading, Error, Edge (طلبات متكررة لنفس الشفت)
- **Key Interaction:** نقر على بطاقة ← تفاصيل ← موافقة ← تحديث الجدول تلقائياً
- **Friction Resolved:** #4 (المدير يتأخر بالموافقة - push notification)

## Screen: ClockIn — تسجيل الدخول (مقابل Stage: حضور)
- **Layout:** GPS map مع موقع المطعم. زر كبير "تسجيل دخول". QR scanner بديل. تأكيد مع صورة شخصية.
- **Components:** GPSMap, ClockButton, QRScanner, SelfieCapture, ConfirmationBanner
- **States:** OutsideRange (الموظف خارج الموقع), Active (يمكن التسجيل), Loading, Error (GPS معطل), Edge (تسجيل متأخر)
- **Key Interaction:** ضغط زر ← GPS يتحقق ← تأكيد ← تسجيل
- **Friction Resolved:** #5 (GPS يقرأ موقع خاطئ)

## Screen: MySchedule — جدول الموظف (مقابل Stage: عرض)
- **Layout:** عرض أسبوعي مبسط لجدول الموظف. أيام العمل ملونة. أزرار لطلب تبديل أو إجازة. إشعارات التغيير.
- **Components:** WeekView, ShiftCard, SwapAction, LeaveRequestButton, ChangeNotification
- **States:** Empty (لا مناوبات), Loading, Error, Edge (جدول متغير)
- **Key Interaction:** نقر على شفت ← بطاقة تفاصيل ← طلب تبديل
- **Friction Resolved:** #2 (الجدول يتغير بعد النشر)

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| ScheduleGrid | Weekly/Monthly | Loading/Ready/Conflict | ألوان الخلايا حسب نوع الشفت (صباح أزرق، مساء برتقالي) |
| ShiftBlock | Morning/Evening/Night | Default/Dragging/Assigned/Unassigned | ظل عند السحب ومكان متقطع عند الإفلات |
| SwapRequestCard | Pending/Approved/Rejected | Default/Hover/Expanded | يتمدد لإظهار سبب التبديل |
| ClockButton | In/Out | Idle/Active/Loading/Success/Error | يتغير لونه ونصه حسب الحالة |
| WeekView | Single employee | Today/Off/Shift | خط عمودي أحمر على اليوم الحالي |
