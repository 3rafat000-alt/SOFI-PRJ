# PROTOTYPE SPEC — FishFarm (SAAS-097)
> Owner: UI/UX Designer · Gate 2

## Screen: Dashboard (Journey Stage: نظرة عامة)
- **Layout:** شبكة أحواض (PondGrid) + قراءات آنية + تنبيهات
- **Components:** PondCard, SensorReadingWidget, AlertBanner, FCRDisplay
- **States:** Empty (بدون أحواض), Loading (Skeleton), Error (انقطاع مستشعر), Edge (أحواض كثيرة)
- **Key Interaction:** ضغط على حوض ← تفاصيل
- **Friction Resolved:** #1 — المراقبة

## Screen: Pond Detail (Journey Stage: المراقبة + التغذية)
- **Layout:** رسوم بيانية حية (حرارة/أكسجين/pH) + جدول تغذية + سجل وزن
- **Components:** LiveChart, FeedingTable, WeightLog, AlertHistory, ActionButton
- **States:** Loading (بيانات المستشعر), Error (جهاز معطل), Empty (بدون بيانات)
- **Key Interaction:** مراقبة القراءات ← ضبط التغذية
- **Friction Resolved:** #2 — هدر العلف

## Screen: Harvest Planning (Journey Stage: الحصاد)
- **Layout:** قائمة أحواض جاهزة + وزن متوقع + جدولة
- **Components:** ReadyPondList, EstimatedWeightCard, HarvestCalendar, BuyerSelector
- **States:** Empty (لا أحواض جاهزة), Loading, Error
- **Key Interaction:** اختيار حوض ← تحديد تاريخ الحصاد ← تعيين مشترٍ
- **Friction Resolved:** #4 — تأخير الحصاد

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| PondCard | active, harvesting, empty, alert | default, hover, alert | بطاقة حوض مع مؤشرات حية |
| SensorReading | temperature, oxygen, pH, salinity | normal, warning, critical | قراءة + لون حسب الحالة |
| LiveChart | line, area, gauge | live, historical, loading | رسم بياني لحظي |
| FCRDisplay | daily, cumulative | good, average, poor | نسبة تحويل العلف |
| GrowthCurve | weight, length | projected, actual | منحنى النمو المتوقع |
| HarvestCard | ready, scheduled, completed | default, processing, done | بطاقة حصاد |
