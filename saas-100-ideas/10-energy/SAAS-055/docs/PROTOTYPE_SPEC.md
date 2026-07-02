# PROTOTYPE SPEC — GasStation (SAAS-055)
> Owner: UI/UX Designer · Gate 2

## Screen: Tank Level Dashboard (maps to Journey Stage: مراقبة المخزون)
- **Layout:** Tank visual indicators (gauge style) + level % + alerts + fill history
- **Components:** TankGauge, LevelBar, AlertBadge, FillHistoryChart
- **States:** Empty (no tanks configured → "أضف خزاناً أولاً"), Loading (gauges skeleton), Error (sensor offline → display last known), Edge (tank critical → red pulsing)
- **Key Interaction:** Tap tank → detail with readings history
- **Friction Resolved:** [#1] عدم معرفة مستويات الخزانات → عرض بصري لحظي

## Screen: Sales Board (maps to Journey Stage: تسجيل المبيعات)
- **Layout:** Real-time sales feed + payment method breakdown + shift totals
- **Components:** SalesFeedItem, PaymentPieChart, ShiftTotalCard, FuelTypeSelector
- **States:** Empty (no sales today → "لا توجد مبيعات"), Loading, Error (sync failed), Edge (shift closed → read-only view)
- **Key Interaction:** Auto-update sales feed, manual entry if pump disconnected
- **Friction Resolved:** [#2] صعوبة تتبع المبيعات → لوحة حية

## Screen: Shift Open/Close (maps to Journey Stage: فتح/إقفال الوردية)
- **Layout:** Opening balance → sales summary → closing balance → discrepancy check
- **Components:** BalanceInput, SalesSummaryCard, DiscrepancyAlert, CloseButton
- **States:** Empty (no shift open → "ابدأ وردية جديدة"), Loading (calculating), Error (discrepancy → force explanation), Edge (manager override for large discrepancies)
- **Key Interaction:** Enter opening → work → enter closing → system reconciles

## Screen: Purchase Order (maps to Journey Stage: طلب التوريد)
- **Layout:** Supplier selector + fuel type + quantity + price + submit
- **Components:** SupplierDropdown, FuelTypeSelector, QuantityInput, PriceDisplay, SubmitButton
- **States:** Empty (no suppliers → "أضف مورداً أولاً"), Loading, Error, Edge (pending delivery → status tracker)

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| TankGauge | horizontal, vertical | normal, warning, critical | Animated fill with color zones |
| SalesFeedItem | cash, card, wallet | new, confirmed | Auto-appear animation |
| ShiftTotalCard | open, closed | normal, discrepancy | Red if discrepancy |
| FuelTypeSelector | 91, 95, diesel, gas | selected, disabled | Radio group |
| DiscrepancyAlert | small, large | warning, critical | Requires manager PIN |
