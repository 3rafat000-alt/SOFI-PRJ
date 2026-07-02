# Design System — InventoryPro
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** InventoryPro — مخزون احترافي
- **Logo concept:** أيقونة صندوق / box + باركود + نص sans-serif متين
- **Brand personality:** متين، موثوق، عملي، واضح، جاد

## Color Palette
- **Primary:** `#059669` — أخضر زمردي (أزرار، رؤوس، تأكيدات)
- **Secondary:** `#0284C7` — أزرق (روابط، معلومات، إرشادات)
- **Accent:** `#D97706` — كهرماني (تحذيرات، تنبيهات مخزون منخفض)
- **Neutral:** `#F0FDF4` / `#052E16` — خلفيات / نصوص أساسية
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#EF4444`

## Typography
- **Headings:** Inter — sizes: 28/24/20/18px (Arabic: Cairo 26/22/18/16px)
- **Body:** Inter — 14px/16px
- **Arabic:** Cairo — دعم 4 أوزان (400/500/600/700)

## Spacing
- Base unit: 8px
- Padding: 16/24/32/48px
- Border radius: 8px (cards, inputs), 4px (badges)

## Iconography
- Style: Outline 24px stroke 2
- Library: Lucide Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg `#059669`, white text, 8px radius | hover: `#047857` / active: `#065F46` / disabled: opacity 50% |
| Button Secondary | bg white, border `#059669`, green text | hover: bg `#ECFDF5` |
| Stat Card | bg white, border-l-4 green, shadow-sm | hover: shadow-md |
| Barcode Viewfinder | full-screen camera, overlay border | scanning: animated line / success: green flash / error: red flash |
| Alert Card | bg white, border-l-4 | low stock: amber / out: red / expiring: orange |
| Product Row | grid padding 12px, border-bottom | hover: bg `#F0FDF4` / selected: bg `#D1FAE5` |
| Line Item | flex row, bg white, border | editing: bg blue-50 |
| Supplier Select | dropdown with avatar + name + rating | open: shadow-lg, searchable |
| Stock Badge | Normal, Low, Out, Expiring | static | green/amber/red/orange bg |
