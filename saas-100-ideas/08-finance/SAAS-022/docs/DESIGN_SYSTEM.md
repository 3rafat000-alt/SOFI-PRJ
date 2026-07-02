# Design System — InvoiceFlow
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** InvoiceFlow — تدفق الفواتير
- **Logo concept:** رمز "ف" عربي داخل درع مالي مع خط بياني صاعد باللون الذهبي
- **Brand personality:** موثوق، احترافي، فاخر، بسيط، آمن

## Color Palette
- **Primary:** `#1B2A4A` — كحلي غامق (خلفيات رئيسية، أزرار، رأس الصفحة)
- **Secondary:** `#C5A55A` — ذهبي (أيقونات، تأكيدات، تمايز VIP)
- **Accent:** `#2E7D32` — أخضر مالي (حالة مدفوع، نجاح، مؤشرات إيجابية)
- **Neutral:** `#F5F5F5` (خلفيات) `#9E9E9E` (نص ثانوي) `#212121` (نص أساسي)
- **Semantic:** Success `#388E3C` · Warning `#F9A825` · Error `#D32F2F`

## Typography
- **Headings:** Inter — sizes: 28/24/20/18px (600 weight)
- **Body:** Inter — 14px (400 weight), table data 13px
- **Arabic:** Noto Sans Arabic — support for currency symbols, RTL
- **Monospace:** JetBrains Mono — invoice numbers, tax IDs

## Spacing
- Base unit: 8px
- Padding: 16/24/32/48px
- Border radius: 6px (buttons), 8px (cards), 4px (inputs)
- Table cell padding: 12px 16px

## Iconography
- Style: Outline
- Library: Lucide
- Key icons: FileText, DollarSign, Users, TrendingUp, Download, Send

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-primary, white text, 6px radius, 44px height | hover: lighter navy (mix white 10%), active: scale(0.97), disabled: opacity 0.5 |
| Button Gold | bg-secondary, dark text, 6px radius | hover: brighten 10%, active: press |
| Input Field | border gray-300 1px, 12px padding, 4px radius | focus: border gold + ring gold 10%, error: border error, disabled: bg gray-50 |
| DataTable | header bg primary, white text, striped rows | row hover: bg blue-50, sort indicator arrows |
| KPICard | bg white, shadow, 8px radius, 16px padding, icon circle | hover: shadow-lg, value animated on change |
| StatusBadge | 6px dot + label | Draft/ Sent(blue)/ Paid(green)/ Overdue(red)/ Cancelled(gray) |
| InvoiceForm | side-by-side layout, sticky preview | field-level validation, total auto-calc |
