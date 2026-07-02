# PROTOTYPE SPEC — ButcherPro (SAAS-078)
> Owner: UI/UX Designer · Gate 2

## Screen: Customer Order (maps to Journey Stage: بيع)
- **Layout:** Product category tabs + cut grid + weight selector + cart
- **Components:** CategoryTab (Lamb/Beef/Chicken/Camel), CutGrid (with photo), WeightStepper (+0.5kg steps), CutInstructionsInput, CartSummary, OrderTypeToggle (delivery/pickup)
- **States:** Empty → Categories loading; Loading → Product skeleton; Error → "تعذر تحميل المنتجات" → retry; Edge → Requested weight > available stock → show max available
- **Key Interaction:** Select cut → adjust weight → add special instructions → add to cart
- **Friction Resolved:** #5 — Structured ordering replaces phone calls

## Screen: Scale Integration (maps to Journey Stage: وزن)
- **Layout:** Scale reading overlay + product selected + price calculation
- **Components:** ScaleConnectionIndicator, WeightDisplay (live from scale), ProductConfirm, PriceCalculation, PrintReceiptButton
- **States:** Empty → "اختر المنتج أولاً"; Loading → Connecting to scale; Error → "الميزان غير متصل" → manual weight entry; Edge → Weight unstable (customer holds bag) → "ضع اللحمة على الميزان"
- **Key Interaction:** Place meat on scale → weight auto-captures → confirm product → print label
- **Friction Resolved:** #3 — Digital scale integration eliminates manual entry errors

## Screen: Delivery Tracking (maps to Journey Stage: توصيل)
- **Layout:** Live map with driver path + customer info + ETA
- **Components:** MiniMapView, DriverInfoCard, CustomerInfoCard, ETAWidget, OrderItemsSummary, StatusUpdateButtons (Picked Up, Delivered)
- **States:** Empty → "لا توجد طلبات توصيل حالياً"; Loading → Loading route; Error → GPS signal lost → "سيتم التحديث عند استعادة الاتصال" + last known location; Edge → Customer not at address → "المستلم غير موجود" → call button + wait timer
- **Key Interaction:** Tap "تم التوصيل" → photo proof + signature
- **Friction Resolved:** #4 — Real-time delivery tracking with proof

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| WeightStepper | 0.5kg, 1kg steps | min/max/default | Haptic feedback on step |
| ScaleDisplay | connected, disconnected | idle/weighing/stable | Green=connected |
| CutCard | with photo, without | available/out-of-stock | Grey overlay if OOS |
| OrderCard | incoming, preparing, ready, delivered | with-priority | Color-coded status |
| DeliveryProof | photo, signature | pending/captured | Camera + signature pad |
| HalalBadge | certified, pending | verified | Green checkmark |
| TemperatureAlert | cold, frozen, ambient | normal/warning/critical | Cold chain compliance |
