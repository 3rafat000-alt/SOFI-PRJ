# PROTOTYPE SPEC — SouqFarmer (SAAS-075)
> Owner: UI/UX Designer · Gate 2

## Screen: Product Listing (Farmer) (maps to Journey Stage: نشر محصول)
- **Layout:** Camera-first form: take photo → add details → set price → publish
- **Components:** ProductPhotoCapture, CategoryPicker, UnitSelector (kg/box/piece), PriceInput, QuantityInput, HarvestDatePicker, QualityGradeSelector, PublishButton
- **States:** Empty → "انشر محصولك الآن" + how-to guide; Loading → Uploading images progress; Error → "فشل النشر" → retry; Edge → Image too dark → "الصورة غير واضحة، حاول تحت إضاءة جيدة"
- **Key Interaction:** Take product photo → auto-crop → add description
- **Friction Resolved:** #6 — Photo tips + auto-enhance

## Screen: Consumer Browse (maps to Journey Stage: شراء)
- **Layout:** Horizontal category strip + vertical product grid
- **Components:** CategoryChip, ProductCard (photo, name, farm, price, unit), SearchBar, FilterSheet (price range, farm location, quality grade), CartFAB
- **States:** Empty → "لا توجد منتجات حالياً" → check back later; Loading → Skeleton grid; Error → "تعذر التحميل" → retry; Edge → Filter returns 0 results → "حاول تصفية مختلفة"
- **Key Interaction:** Tap card → product detail → add to cart
- **Friction Resolved:** #3 — Clear product info with farm origin

## Screen: Order Tracking (maps to Journey Stage: توصيل)
- **Layout:** Vertical timeline with status updates + map
- **Components:** OrderStatusStepper (Confirmed → Picked → Quality Check → Out for Delivery → Delivered), MiniMapView, DriverContact, EstimatedTime, RateButton
- **States:** Empty → N/A; Loading → "جاري تحديث الحالة"; Error → "فشل تحميل التتبع" → refresh; Edge → Delayed delivery → "تأخير 15 دقيقة" apology + compensation offer
- **Key Interaction:** Tap driver contact → call/direct message
- **Friction Resolved:** #5 — Real-time tracking with driver contact

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| CategoryChip | produce, dairy, honey, eggs | unselected/selected | Pill shape with icon |
| ProductCard | grid, list | default/pressed | Shadow, 12px radius, farm badge |
| OrderStepper | 5-step | complete/active/pending | Animated line |
| StarRating | display, input | 0-5 stars | Tap to rate (input) |
| CartFAB | with-count | default/updated | Bounce animation on add |
| FarmerBadge | certified, top-rated | default | Green checkmark |
| QualityGrade | A, B, C | with-explanation | Grade A = green, B = yellow, C = orange |
