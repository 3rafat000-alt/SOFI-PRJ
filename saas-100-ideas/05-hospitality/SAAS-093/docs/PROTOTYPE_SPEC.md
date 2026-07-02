# PROTOTYPE SPEC — HallBooking (SAAS-093)
> Owner: UI/UX Designer · Gate 2

## Screen: Search (Journey Stage: البحث)
- **Layout:** خريطة + قائمة نتائج، فلتر علوي (تاريخ + سعة + ميزانية)
- **Components:** MapView, HallCard, FilterDrawer, DatePicker
- **States:** Empty (لا نتائج)، Loading (Skeleton)، Error (فشل التحميل)، Edge (نتائج كثيرة)
- **Key Interaction:** اختيار تاريخ ← عرض القاعات المتاحة
- **Friction Resolved:** #1 — تعارض الحجوزات

## Screen: Hall Detail (Journey Stage: المعرض)
- **Layout:** Hero slider + معلومات + باقات + جدول توفر
- **Components:** ImageSlider, VideoPlayer, CapacityBadge, PackageCard, AvailabilityCalendar
- **States:** Loading (صور), Error (صورة لا تحمل), Empty (لا باقات مضافة)
- **Key Interaction:** تصفح الباقة ← ضغط "احجز معاينة"
- **Friction Resolved:** #2 — وضوح الباقات

## Screen: Contract (Journey Stage: العقد)
- **Layout:** نص العقد + حقول توقيع إلكتروني + ملخص الحجز
- **Components:** ContractViewer, SignaturePad, SummaryBox, CheckboxList
- **States:** Loading (تحميل العقد), Error (فشل التوقيع), Edge (تعديلات على العقد)
- **Key Interaction:** قراءة + توقيع + دفع
- **Friction Resolved:** #3 — تعقيد العقود

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| HallCard | list, grid, map | default, hover, selected, booked | بطاقة عرض القاعة |
| AvailabilityCalendar | month, week, day | available, partial, booked, maintenance | أيام متاحة / محجوزة |
| PackageCard | basic, premium, custom | default, selected, sold-out | بطاقة باقة مع مقارنة |
| SignaturePad | draw, type | empty, drawing, signed, error | توقيع إلكتروني مع أمان |
| ImageSlider | fullscreen, thumbnail | loading, error, swipeable | عرض صور القاعة |
| VendorCard | active, idle | default, contact, hired | بطاقة مورد / متعهد |
