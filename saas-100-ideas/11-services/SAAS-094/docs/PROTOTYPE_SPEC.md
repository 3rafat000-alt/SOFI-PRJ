# PROTOTYPE SPEC — ToolRental (SAAS-094)
> Owner: UI/UX Designer · Gate 2

## Screen: Search/Browse (Journey Stage: البحث)
- **Layout:** Categories grid + SearchBar + فلتر (فئة/سعر/متاح اليوم)
- **Components:** CategoryCard, ToolCard, SearchBar, FilterChips, SortDropdown
- **States:** Empty (فئة بدون أدوات)، Loading (Skeleton)، Error (فشل)، Edge (أدوات كثيرة)
- **Key Interaction:** بحث ← تصفية ← اختيار ← حجز
- **Friction Resolved:** #5 — التصنيف

## Screen: Tool Detail + Availability (Journey Stage: التوفر)
- **Layout:** صورة الأداة + مواصفات + تقويم توفر + سعر المدة
- **Components:** ToolImageGallery, SpecTable, AvailabilityCalendar, PriceCalculator, RentalDurationSlider
- **States:** Loading, Error (صورة), Empty (لا توفر)
- **Key Interaction:** اختيار مدة ← حساب السعر ← حجز
- **Friction Resolved:** #1 — دقة التوفر

## Screen: Return (Journey Stage: الإعادة)
- **Layout:** نموذج فحص الأداة + صور المقارنة + إيصال الإعادة
- **Components:** InspectionForm, PhotoUploader, ConditionSelector, RefundSummary
- **States:** Empty (لم يتم), Loading (رفع صور), Error (فشل), Edge (تلف/تأخير)
- **Key Interaction:** تصوير الأداة ← تسليم ← استلام الإيصال
- **Friction Resolved:** #2 — قلق التلف

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| ToolCard | list, grid, compact | default, rented, unavailable, maintenance | بطاقة عرض الأداة + حالة |
| AvailabilityCalendar | month, week | available, partial, booked, maintenance | أيام متاحة / محجوزة |
| PriceCalculator | hourly, daily, weekly | default, calculating, result | حساب سعر الإيجار حسب المدة |
| RentalDurationSlider | slider + presets (1d/3d/1w) | default, dragging, set | اختيار مدة الإيجار |
| InspectionForm | with photos, without | pending, inspecting, passed, failed | فحص الأداة عند الإعادة |
| ConditionSelector | excellent, good, fair, damaged | default, selected | تقييم حالة الأداة |
