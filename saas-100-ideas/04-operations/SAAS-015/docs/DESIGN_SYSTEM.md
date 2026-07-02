# Design System — LaundryHub
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** "LaundryHub" — مركز الغسيل الذكي، محطة واحدة لكل احتياجات التنظيف
- **Logo concept:** أيقونة قطعة ملابس نظيفة مع دوائر دوران (ترمز للغسيل)، اسم LaundryHub بخط sans-serif
- **Brand personality:** نظيف · موثوق · سريع · عصري · منظم

## Color Palette (Operations: Blue/Gray)
- **Primary:** `#1565C0` — أزرق ثقة (headers, buttons, nav)
- **Secondary:** `#FF8F00` — برتقالي طاقة (accent, badges, highlights)
- **Accent:** `#42A5F5` — أزرق فاتح (hover states, links)
- **Neutral:** `#F5F5F5` — رمادي فاتح للخلفيات، `#E0E0E0` للحدود، `#616161` نصوص ثانوية
- **Semantic:** Success `#2E7D32` · Warning `#F9A825` · Error `#C62828` · Info `#1565C0`

## Typography
- **Headings:** Nunito (English) / Noto Sans Arabic (Arabic) — sizes: 28/24/20/18/16px
- **Body:** Nunito / Noto Sans Arabic — 14px regular
- **Small/Meta:** 12px
- **Line height:** Headings 1.15, Body 1.6

## Spacing
- Base unit: 8px
- Container max-width: 1280px
- Padding: 16/24/32/48px
- Border radius: 8px (cards/buttons), 4px (inputs), 12px (modals)
- Gap: 16px / 24px

## Iconography
- Style: Outline, 1.5px stroke
- Library: Lucide Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-#1565C0, white, 8px radius, 14px/600 | hover #0D47A1 / active #0A347D / disabled 50% |
| Button Secondary | border 2px #FF8F00, text #FF8F00 | hover bg-#FF8F0010 / active bg-#FF8F0020 |
| OrderCard | bg-white, border-left 4px status, 8px radius | normal/hover shadow-md / dragging opacity 70% |
| Input Field | border 1px #E0E0E0, 12px padding, 6px radius | focus border-#1565C0 / error border-#C62828 |
| StageQueue | 4 col grid with column header | empty/with-orders / drag-over highlight |
| DriverCard | bg-white, 8px radius, status indicator | available green dot / busy orange / offline gray |
| ItemRow | border-bottom, 3 columns | editable with delete / readonly |
| StatusBadge | pill, 12px/600 | pending gray / washing blue / drying teal / folding purple / ready green / delivered gray |
| KanbanColumn | min-width 280px, header count | scrollable horizontally |
| MiniMap | container, pin markers | loading / error / data with route |
| Modal | bg-white, 12px radius, backdrop | open/close 200ms |
