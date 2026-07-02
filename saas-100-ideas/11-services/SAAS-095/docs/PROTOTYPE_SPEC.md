# PROTOTYPE SPEC — MobileFix (SAAS-095)
> Owner: UI/UX Designer · Gate 2

## Screen: Intake (Journey Stage: التسجيل)
- **Layout:** نموذج سريع (عميل + جهاز + عطل) + طباعة باركود
- **Components:** CustomerForm, DeviceSelector, BarcodePrinter, SymptomTags
- **States:** Empty (جهاز جديد)، Loading (بحث عن عميل), Error (باركود مكرر)، Edge (أجهزة متعددة لنفس العميل)
- **Key Interaction:** Scan باركود ← تعبئة تلقائية ← إضافة جهاز
- **Friction Resolved:** #1 — فوضى الاستقبال

## Screen: Repair Dashboard (Journey Stage: الإسناد + التصليح)
- **Layout:** Kanban (مستلم/قيد التشخيص/قيد الإصلاح/منتهي) + لوحة الفني
- **Components:** KanbanBoard, DeviceCard, StatusBadge, TimerWidget, TechnicianAvatar
- **States:** Empty (لا أجهزة), Loading, Error, Edge (أجهزة كثيرة)
- **Key Interaction:** سحب وإفلات الأجهزة بين المراحل
- **Friction Resolved:** #3 — تتبع التصليحات

## Screen: Inventory (Journey Stage: قطع الغيار)
- **Layout:** جدول قطع الغيار + فلتر + تنبيهات نفاد
- **Components:** InventoryTable, StockAlertCard, SupplierCard, ReorderButton
- **States:** Empty (مخزون صفر), Loading, Error, Edge (مخزون كبير)
- **Key Interaction:** فحص المخزون ← طلب توريد
- **Friction Resolved:** #2 — نقص قطع الغيار

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| DeviceCard | phone, tablet, other | pending, diagnosing, repairing, ready, delivered | بطاقة جهاز مع الحالة |
| KanbanBoard | 3-col, 4-col, 5-col | default, dragging, drop | سحب وإفلات الأجهزة |
| SymptomTags | common, custom | default, selected, suggested | وصف العطل بضغطة |
| TechnicianAvatar | online, busy, offline | idle, working, break | حالة الفني + عدد الأجهزة |
| StockAlertCard | low, critical, overstock | default, dismissed | تنبيه نقص قطع الغيار |
| BarcodeLabel | device, customer | generated, printed | باركود لاصق للجهاز |
