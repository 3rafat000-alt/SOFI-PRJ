# PROTOTYPE SPEC — SouqSync (SAAS-051)
> Owner: UI/UX Designer · Gate 2

## Screen: Supplier Browse (maps to Journey Stage: تصفح الموردين)
- **Layout:** Top search bar + category chips + supplier cards grid (2 cols)
- **Components:** SearchInput, CategoryChip, SupplierCard, LoadingSkeleton
- **States:** Empty (no suppliers found → illustration + "لا يوجد موردون"), Loading (skeleton cards ×6), Error (retry button + message), Edge (0 results after filter → "حاول تغيير البحث")
- **Key Interaction:** Tap supplier card → navigate to product grid
- **Friction Resolved:** [#1] كثرة الخيارات → تصنيف وبحث ذكي

## Screen: Products Grid & Cart (maps to Journey Stage: اختيار المنتجات)
- **Layout:** Product cards with image, name, price, stock badge, add-to-cart button
- **Components:** ProductCard, StockBadge, CartFAB, QuantitySelector
- **States:** Empty (no products → "لا توجد منتجات"), Loading (shimmer), Error (network fail), Edge (product out of stock → disabled button + "نفدت الكمية")
- **Key Interaction:** Tap + to add → FAB shows count → tap FAB → cart slide-up
- **Friction Resolved:** [#2] أخطاء في الكميات → واجهة واضحة مع تأكيد

## Screen: Order Checkout (maps to Journey Stage: تأكيد الطلب)
- **Layout:** Order summary list + delivery address + payment method + confirm button
- **Components:** OrderItemList, AddressCard, PaymentSelector, ConfirmButton
- **States:** Empty (empty cart → "السلة فارغة"), Loading (submitting), Error (submit failed → retry), Edge (address missing → prompt to add)
- **Key Interaction:** Review → confirm → loading → success screen with order number
- **Friction Resolved:** [#3] أخطاء في الطلب → مراجعة شاملة قبل الإرسال

## Screen: Order Tracking (maps to Journey Stage: انتظار التأكيد)
- **Layout:** Order status timeline + item list + action buttons
- **Components:** StatusTimeline, OrderItem, ActionButton (cancel/contact)
- **States:** Loading, Empty (order not found), Error, Edge (cancelled → show reason + refund status)
- **Key Interaction:** Pull to refresh status, tap timeline node for details

## Screen: Supplier Dashboard (maps to Journey Stage: إدارة المخزون)
- **Layout:** Stats row + recent orders table + low stock alerts + quick actions
- **Components:** StatsCard, OrdersTable, AlertBanner, QuickActionButton
- **States:** Empty (no orders yet → "لا توجد طلبات جديدة"), Loading (spinner), Error (data fetch failed)

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| SupplierCard | default, featured | normal, loading, error | Tap → product grid |
| ProductCard | grid, list | normal, out-of-stock, loading | Tap → detail, + → cart |
| StatusTimeline | order, delivery | pending, active, completed, cancelled | Animated steps |
| CartFAB | empty, has-items | default, disabled | Tap → checkout slide-up |
| SearchInput | default, with-filter | normal, focused, has-results | Debounced search |
| AlertBanner | info, warning, error | visible, dismissed | Swipe to dismiss |
