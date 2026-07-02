# Design System — PharmaChain
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** PharmaChain — سلسلة الدواء
- **Logo concept:** Pill shape + chain links in cyan, clean medical cross accent
- **Brand personality:** Trustworthy, Medical, Precise, Efficient, Modern

## Color Palette
- **Primary:** `#0891B2` — Cyan 600, medical + cleanliness
- **Secondary:** `#059669` — Emerald 600, health + growth
- **Accent:** `#0D9488` — Teal 600, depth + stability
- **Neutral:** `#1E293B` — Slate 800 (text), `#F0FDFA` (bg)
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#EF4444`

## Typography
- **Headings:** Inter — sizes: 24/20/18/16px
- **Body:** Inter — 14px
- **Arabic:** Noto Kufi Arabic — product names, pharmacy communication

## Spacing
- Base unit: 4px/8px
- Padding: 16/24/32px
- Border radius: 8px

## Iconography
- Style: Outline
- Library: Lucide

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg #0891B2, white text, 8px radius | hover #0E7490 / active #155E75 / disabled |
| Button Success | bg #059669, white text | hover #047857 / active #065F46 |
| Expiry Bar | bg gradient green→amber→red | — 90d+ green / 30-90d amber / <30d red |
| Product Card | bg white, shadow, 8px radius | out-of-stock opacity 60% |
| DataTable | header bg #F0FDFA, row hover #CCFBF1 | selected row #99F6E4 |
| AlertBanner | CTA / Warning / Critical | dismissible with X |
| Input Field | border 1px #CBD5E1 | focus ring #0891B2 / error |
| Badge | Expiry/Warning/Active | colour-coded pill |
