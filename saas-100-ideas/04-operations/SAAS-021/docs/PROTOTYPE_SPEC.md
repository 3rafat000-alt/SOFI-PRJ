# PROTOTYPE SPEC — ParkingIQ (SAAS-021)
> Owner: UI/UX Designer · Gate 2

## Screen: Home — خريطة المواقف (مقابل Stage: اكتشاف)
- **Layout:** خريطة تفاعلية (Google Maps) مع pins للمواقف القريبة. شريط سفلي بزر البحث والحجز. أيقونة فلتر.
- **Components:** Map, Pin Card, Search Bar, Bottom Nav
- **States:** Empty (لا يوجد مواقف قريبة), Loading (جلب المواقع), Error (الخدمة غير متاحة), Edge (خارج نطاق التغطية)
- **Key Interaction:** ينقر على pin ليرى اسم الموقف والسعر والشواغر
- **Friction Resolved:** #1 (عرض شواغر غير محدثة)

## Screen: Booking — تأكيد الحجز (مقابل Stage: حجز)
- **Layout:** بطاقة تفاصيل الموقف (الموقع، السعر، الوقت). أزرار +/- للوقت. زر تأكيد أزرق كبير.
- **Components:** Card, Time Picker, Price Display, Confirm Button
- **States:** Empty (بيانات مفقودة), Loading (حجز), Error (الموقف محجوز بالفعل), Edge (وقت الحجز أقل من 15 دقيقة)
- **Key Interaction:** يعدل الوقت وينقر تأكيد → سحب المبلغ
- **Friction Resolved:** #3 (وقت الحجز الافتراضي)

## Screen: Payment — الدفع (مقابل Stage: دفع)
- **Layout:** بطاقة المبلغ، خيارات الدفع (Mada, Apple Pay, STC Pay, Visa), حقل CVV. زر دفع.
- **Components:** Payment Card, Payment Method Selector, Input (CVV), Pay Button
- **States:** Empty, Loading (معالجة), Error (الدفع مرفوض مع سبب), Edge (بطاقة منتهية الصلاحية)
- **Key Interaction:** يختار طريقة الدفع ويدخل CVV → يدفع
- **Friction Resolved:** #2 (بوابة الدفع ترفض)

## Screen: QR Gate — دخول/خروج (مقابل Stage: دخول)
- **Layout:** كاميرا QR full-screen مع إطار مسح. رسالة تأكيد. زر إدخال يدوي.
- **Components:** Camera View, QR Frame, Confirmation Banner, Manual Input Link
- **States:** Active (الكاميرا شغالة), Scanning, Confirmed (QR صحيح), Error (QR غير معروف), Edge (إضاءة ضعيفة)
- **Key Interaction:** يوجّه الكاميرا نحو QR → دخول تلقائي
- **Friction Resolved:** #5 (الكاميرا لا تقرأ QR)

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Pin Card | Available/Reserved/Occupied | Default/Hover/Selected | ينبسط عند النقر مع معلومات |
| Search Bar | Default/Focused | Empty/Results/Suggestions | يبحث عن مولات ومواقف |
| Bottom Nav | 4 tabs (Home, Bookings, Wallet, Profile) | Active/Inactive/Badge | Badge على Bookings عند حجز نشط |
| Confirm Button | Primary/Loading/Disabled | Default/Hover/Pressed/Disabled | يُعطّل أثناء المعالجة |
| Camera View | QR/Barcode | Active/Scanning/Success/Error | يهتز عند نجاح المسح |
