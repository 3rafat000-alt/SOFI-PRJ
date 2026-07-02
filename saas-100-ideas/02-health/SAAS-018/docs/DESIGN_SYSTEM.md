# Design System — PharmaStock
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** "PharmaStock" — تحكم كامل في مخزون الصيدلية بأمان ودقة
- **Logo concept:** أيقونة صليب أخضر (رمز الصيدليات) مع شريط باركود، اسم PharmaStock بخط sans-serif نظيف
- **Brand personality:** آمن · دقيق · مهني · موثوق · عناية

## Color Palette (Health: Teal/Green)
- **Primary:** `#00695C` — أخضر غابي (headers, buttons, primary elements)
- **Secondary:** `#004D40` — أخضر غامق (nav, secondary elements)
- **Accent:** `#26A69A` — أخضر فاتح (hover states, highlights)
- **Neutral:** `#F5F5F5` — خلفية، `#E0E0E0` حدود، `#616161` نصوص ثانوية
- **Semantic:** Success `#2E7D32` · Warning `#F9A825` · Error `#C62828` · Info `#0277BD`

## Typography
- **Headings:** Source Sans Pro (English) / Noto Sans Arabic (Arabic) — sizes: 28/24/20/18/16px
- **Body:** Source Sans Pro / Noto Sans Arabic — 14px regular
- **Small/Meta:** 12px
- **Line height:** Headings 1.15, Body 1.6

## Spacing
- Base unit: 8px
- Container max-width: 1280px
- Padding: 16/24/32/48px
- Border radius: 8px (cards/buttons), 4px (inputs), 12px (modals)
- Gap: 16px / 24px

## Iconography
- Style: Filled outline, 1.5px stroke
- Library: Lucide Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-#00695C, white, 8px radius, 14px/600 | hover #004D40 / active #00352C / disabled 50% |
| Button Secondary | border 2px #004D40, text #004D40 | hover bg-#004D4010 / active bg-#004D4020 |
| Input Field | border 1px #E0E0E0, 12px padding, 6px radius | focus border-#00695C / error border-#C62828 |
| InventoryTable | striped rows, sticky header, sortable | hover row highlight, sort indicator |
| DrugCard | barcode + name + generic + stock + expiry | normal / low stock alert / expiring red |
| ExpiryBadge | pill 12px/600 | >6mo #2E7D32 / 1-3mo #F9A825 / <1mo #C62828 |
| InteractionAlert | banner with drug names | info blue / warning amber / danger red |
| PatientCard | national ID + name + DOB + allergies | collapsed / expanded with history |
| PrescriptionForm | multi-field with auto-complete | empty / filled / interaction-flagged / complete |
| POStatusBadge | pill | draft / sent / confirmed / partial / received |
| BarcodeScanner | viewfinder overlay | scanning / success green / error red |
| AuditLog | chronological table | normal / filtered by date |
| Modal | bg-white, 12px radius, backdrop | open/close 200ms |
