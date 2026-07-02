# PROTOTYPE SPEC — PowerBackup (SAAS-099)
> Owner: UI/UX Designer · Gate 2

## Screen: Generator Dashboard (Journey Stage: المراقبة)
- **Layout:** قائمة مولدات + بطاقة كل مولد مع قراءات حية
- **Components:** GeneratorCard, StatusIndicator, GaugeWidget (fuel/temp/battery), AlertSummary
- **States:** Empty (لا مولدات), Loading (اتصال IoT), Error (جهاز غير متصل), Edge (مولدات كثيرة)
- **Key Interaction:** ضغط على مولد ← تفاصيل
- **Friction Resolved:** #1 — أعطال مفاجئة

## Screen: Generator Detail (Journey Stage: التفاصيل + الصيانة)
- **Layout:** قراءات حية رسومية + سجل صيانة + إجراءات (طلب صيانة/تعبئة وقود)
- **Components:** LiveGauges, MaintenanceTimeline, FuelLevelChart, ActionPanel
- **States:** Loading (قراءات), Error (جهاز معطل), Empty (بدون سجل)
- **Key Interaction:** مراقبة القراءات ← طلب صيانة إذا لزم
- **Friction Resolved:** #2 — الوقود

## Screen: Maintenance Schedule (Journey Stage: جدولة الصيانة)
- **Layout:** تقويم + قائمة مهام صيانة + تفاصيل كل مهمة
- **Components:** CalendarView, MaintenanceTaskCard, TechnicianSelector, PartInventory
- **States:** Empty (بدون مهام), Loading, Error, Edge (مهام متكررة)
- **Key Interaction:** اختيار تاريخ ← تعيين فني ← تأكيد
- **Friction Resolved:** #3 — تفويت الصيانة

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| GeneratorCard | active, idle, alert, offline | default, hover, selected | بطاقة مولد مع مؤشرات |
| GaugeWidget | fuel %, temp °C, battery V, runtime h | normal, warning, critical | مقياس دائري |
| AlertBanner | info, warning, critical, resolved | default, dismissable | تنبيهات المولد |
| MaintenanceTimeline | upcoming, past, overdue | per item: scheduled, in-progress, done | جدول زمني للصيانة |
| FuelOrderButton | auto, manual | idle, ordering, ordered | طلب وقود تلقائي |
| TechnicianCard | available, busy, offline | default, selected | بطاقة فني مع تقييم |
