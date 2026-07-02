# Design System — FurniturePro (SAAS-090)
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** FurniturePro — أثاث (Arabic for furniture)
- **Logo concept:** Stylised armchair + wooden floor, warm brown wordmark
- **Brand personality:** Warm, Elegant, Premium, Trustworthy, Homely

## Color Palette
- **Primary:** `#78350F` — Amber-900 (headers, buttons, brand)
- **Secondary:** `#92400E` — Amber-800 (secondary, hover)
- **Accent:** `#D97706` — Amber-600 (highlights, AR button, sale tags)
- **Background:** `#FFFBEB` — Amber-50 (warm cream page background)
- **Surface:** `#FFFFFF` — White (cards, modals)
- **Neutral:** `#78716C` — Stone-500 (muted text, borders)
- **Semantic:** Success `#16A34A` · Warning `#EAB308` · Error `#DC2626` · Info `#D97706`

## Typography
- **Headings:** Playfair Display — sizes: 28/24/20/18px, semibold
- **Body:** Inter — 14px regular, line-height 1.6
- **Arabic:** Noto Sans Arabic — full support
- **Monospace:** JetBrains Mono — 13px (SKUs, order numbers)

## Spacing
- Base unit: 4px
- Padding: 16/24/32/48/64px
- Gap: 8/12/16/24/32px
- Border radius: 12px (cards), 8px (inputs), 999px (badges)
- Shadow: sm (soft), md, lg (elevated), xl (modals)

## Iconography
- Style: Outline 2px stroke
- Library: Lucide Icons
- Size: 20/24/32/48px (AR button prominent)

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-amber-900, white text, 12px radius | hover:bg-amber-800 / active:scale-98 / disabled:opacity-50 |
| Button AR | bg-amber-600, white, 48px, glow | hover:bg-amber-700 / pulse animation |
| ProductCard | bg-white, 12px radius, shadow-sm | hover:shadow-lg, scale-102 |
| CategoryCard | Full image background, overlay text | default/hover:overlay darken |
| VariantPicker | Color/size swatches | selected:ring-2 ring-amber-600 |
| ImageGallery | Full-width carousel | zoom:pinch / fullscreen |
| CalendarView | Month grid with events | default/hover/selected/occupied |
| StatusTimeline | Vertical with icons | completed:green / current:amber / pending:slate |
| ProofPhoto | Thumbnail 80px | empty:dashed / captured:border / verified:green-check |
