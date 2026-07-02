# PROTOTYPE SPEC — SolarPro (SAAS-057)
> Owner: UI/UX Designer · Gate 2

## Screen: Production Dashboard (maps to Journey Stage: مراقبة الإنتاج)
- **Layout:** Total capacity + today's production + weather + system health + chart
- **Components:** CapacityBadge, ProductionGauge, WeatherWidget, HealthStatusCard, YieldChart
- **States:** Empty (no installations → "أضف نظاماً أولاً"), Loading (skeleton charts), Error (inverter offline → "بيانات غير متاحة"), Edge (night time → "لا يوجد إنتاج ليلي")
- **Key Interaction:** Tap chart → daily breakdown
- **Friction Resolved:** [#2] صعوبة حساب الأداء → لوحة أداء لحظية

## Screen: Work Order (maps to Journey Stage: الصيانة)
- **Layout:** Order list (kanban: open/in-progress/completed) + detail modal
- **Components:** KanbanColumn, WorkOrderCard, TechnicianSelect, ChecklistForm
- **States:** Empty (no work orders → "جميع الأنظمة سليمة"), Loading, Error, Edge (overdue order → red badge)
- **Key Interaction:** Drag to change status, tap for detail, assign technician
- **Friction Resolved:** [#1] عدم متابعة الصيانة → كانبان + تذكير

## Screen: Customer App — My System (maps to Journey Stage: مراقبة العميل)
- **Layout:** Production today + savings meter + system health + maintenance request
- **Components:** SavingsMeter, ProductionTodayCard, HealthDot, RequestMaintenanceButton
- **States:** Loading, Error (no data from inverter), Edge (system offline → "غير متصل")
- **Key Interaction:** View savings, tap "طلب صيانة" → create ticket

## Screen: Technician App — Job Detail (maps to Journey Stage: أمر الصيانة)
- **Layout:** Job info + customer details + checklist + photo upload + complete
- **Components:** JobInfoCard, CustomerLocationMap, TaskChecklist, PhotoUploader, CompleteButton
- **States:** Loading, Error (GPS failed), Edge (offline → save locally, sync later)

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| ProductionGauge | kw, kwh | live, idle, offline | Animated needle |
| SavingsMeter | riyals, % | positive, neutral | Currency display |
| KanbanColumn | open, progress, done | has-items, empty | Drag-and-drop |
| WorkOrderCard | urgent, normal | open, in-progress, completed | Priority color |
| HealthDot | green, yellow, red | online, warning, offline | Pulse animation |
