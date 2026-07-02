# PROTOTYPE SPEC — BreadChain (SAAS-056)
> Owner: UI/UX Designer · Gate 2

## Screen: Production Plan (maps to Journey Stage: تخطيط الإنتاج)
- **Layout:** Daily plan header + product list with planned vs actual + batch start button
- **Components:** DateSelector, ProductionRow (product, plan, actual, variance), BatchStartFAB
- **States:** Empty (no products → "أضف منتجاً أولاً"), Loading (skeleton), Error (fetch failed), Edge (past date → read-only view)
- **Key Interaction:** Set planned qty → tap start batch → production begins
- **Friction Resolved:** [#1] صعوبة تقدير الكميات → توقع ذكي + مقارنة يومية

## Screen: Batch Production (maps to Journey Stage: الإنتاج)
- **Layout:** Active batch card with timer + recipe ingredients + output entry
- **Components:** BatchTimer, IngredientChecklist, OutputQuantityInput, WasteToggle
- **States:** Empty (no active batch), Loading, Error (save failed), Edge (batch paused → "مؤقت")
- **Key Interaction:** Start timer → check ingredients → enter output → complete
- **Friction Resolved:** [#2] هدر المواد → تتبع دقيق للمواد والهدر

## Screen: Customer Orders (maps to Journey Stage: طلبات الزبائن)
- **Layout:** Order list with status tabs + order cards + fulfillment actions
- **Components:** StatusTab, OrderCard, FulfillButton, NotesField
- **States:** Empty (no orders → "لا توجد طلبات"), Loading, Error, Edge (overdue order → red highlight)
- **Key Interaction:** Tap order → view items → mark as ready/delivered

## Screen: Sales POS (maps to Journey Stage: البيع المباشر)
- **Layout:** Product grid + cart panel + payment method + receipt
- **Components:** ProductGridItem, CartPanel, PaymentMethodChip, ReceiptPreview
- **States:** Empty (no products selected), Loading, Error (payment failed), Edge (credit customer → add to ledger)

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| ProductionRow | planned, actual | normal, over-target, under-target | Color-coded variance |
| BatchTimer | count-up | running, paused, completed | MM:SS format |
| IngredientChecklist | per recipe | unchecked, checked, partial | Tap to toggle |
| OrderCard | wholesale, retail | pending, ready, delivered | Swipe to fulfill |
| ProductGridItem | with stock badge | available, low-stock, out | Visual stock indicator |
