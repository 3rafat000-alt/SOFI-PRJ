# Design System — BakeryMgt (SAAS-088)
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** BakeryMgt — حلويات (Arabic for sweets/pastry)
- **Logo concept:** Stylised pastry + chef hat, warm brown wordmark
- **Brand personality:** Warm, Sweet, Homely, Authentic, Passionate

## Color Palette
- **Primary:** `#78350F` — Amber-900 (headers, buttons, brand)
- **Secondary:** `#92400E` — Amber-800 (secondary, hover)
- **Accent:** `#D97706` — Amber-600 (highlights, specials, ratings)
- **Background:** `#FFFBEB` — Amber-50 (warm cream page background)
- **Surface:** `#FFFFFF` — White (cards, modals)
- **Neutral:** `#78716C` — Stone-500 (muted text, borders)
- **Semantic:** Success `#16A34A` · Warning `#EAB308` · Error `#DC2626` · Info `#D97706`

## Typography
- **Headings:** Playfair Display — sizes: 24/20/18/16px, semibold
- **Body:** Inter — 14px regular, line-height 1.6
- **Arabic:** Noto Sans Arabic — full support
- **Monospace:** JetBrains Mono — 13px (recipe codes, batch numbers)

## Spacing
- Base unit: 4px
- Padding: 16/24/32/48px
- Gap: 8/12/16/24px
- Border radius: 12px (cards), 8px (inputs), 999px (badges)
- Shadow: sm (soft), md, lg

## Iconography
- Style: Outline 2px stroke
- Library: Lucide Icons
- Size: 20/24/32px

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-amber-900, white text, 12px radius | hover:bg-amber-800 / active:scale-98 / disabled:opacity-50 |
| Button Secondary | border-amber-700, text-amber-900 | hover:bg-amber-50 |
| RecipeCard | bg-white, 12px radius, shadow-sm | hover:shadow-md, soft lift |
| ScaleSlider | Custom range input | idle:amber / dragging:amber-600 |
| IngredientRow | Horizontal row with unit | default/low-stock:orange-bg |
| ProductionItem | Card with checkbox | pending:cream / in-progress:amber-light / done:green |
| ProductGrid | 2-column grid | default/hover:scale-105 |
| CategoryNav | Vertical accordion | expanded/collapsed |
