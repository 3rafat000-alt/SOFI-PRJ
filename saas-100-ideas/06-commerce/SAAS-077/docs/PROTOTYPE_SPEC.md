# PROTOTYPE SPEC — TobaccoShop (SAAS-077)
> Owner: UI/UX Designer · Gate 2

## Screen: POS Quick Sale (maps to Journey Stage: بيع)
- **Layout:** Bottom sheet = product grid, top = cart summary
- **Components:** BarcodeScanner, ProductQuickSearch, ProductGrid (category tabs), CartDrawer (items, total, VAT), AgeVerificationPrompt, PaymentMethodSelector
- **States:** Empty → "امسح الباركود أو ابحث عن المنتج"; Loading → Scanning; Error → "منتج غير موجود" → manual entry; Edge → Customer under age → "لا يمكن إتمام البيع" block
- **Key Interaction:** Scan barcode → product auto-adds → adjust quantity → checkout
- **Friction Resolved:** #3 — Fast barcode-based POS

## Screen: Inventory Dashboard (maps to Journey Stage: تسجيل مخزون)
- **Layout:** Search bar + filter tabs + product table list
- **Components:** SearchBar, FilterTabs (all/active/expired/low-stock), ProductRow (name, brand, stock, expiring, actions), AlertBadge, StockAdjustDialog
- **States:** Empty → "لم يتم إضافة منتجات بعد"; Loading → Skeleton rows; Error → "تعذر تحميل المخزون" → retry; Edge → Multiple products expiring soon → "10 منتجات قريبة من الانتهاء"
- **Key Interaction:** Swipe row → adjust stock; Tap → edit product details
- **Friction Resolved:** #1 — Real-time stock tracking with low-stock alerts

## Screen: Compliance Report (maps to Journey Stage: تقارير)
- **Layout:** Tabbed report (Tax Summary, Age Verification Log, Purchase Limits)
- **Components:** ReportPeriodSelector, TaxSummaryCard (VAT + excise), AgeVerificationTable, ExportButton (PDF/CSV)
- **States:** Empty → Select date range to generate report; Loading → Generating PDF; Error → "تعذر إنشاء التقرير" → retry; Edge → Missing tax data for period → highlight with warning
- **Key Interaction:** Tap export → share to tax consultant
- **Friction Resolved:** #2 — Automated tax reports with VAT + excise calculation

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| BarcodeScanner | camera, manual-entry | idle/scanning/found/not-found | Flashlight toggle |
| ProductGrid | 3-col, list | default/out-of-stock | Grey overlay if OOS |
| CartDrawer | expanded, collapsed | empty/with-items | Slide from bottom |
| AgeVerification | ID-scan, manual | pending/verified/rejected | Red screen when rejected |
| AlertBadge | low-stock, expiring | normal/critical | Red = critical |
| ReportCard | tax, compliance, sales | loading/loaded/error | Expandable content |
| PaymentButton | cash, card, wallet, multiple | enabled/processing/disabled | Split payment support |
