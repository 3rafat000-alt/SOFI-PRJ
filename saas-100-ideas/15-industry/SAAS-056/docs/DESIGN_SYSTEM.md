# Design System — BreadChain
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** Bread (خبز) + Chain (سلسلة) — سلسلة الخبز
- **Logo concept:** رغيف خبز + أيقونة سلسلة توريد، بخط عربي دافئ
- **Brand personality:** دافئ، تقليدي، صناعي، موثوق، أصيل

## Color Palette
- **Primary:** `#546E7A` — Slate (industry/durability/reliability)
- **Secondary:** `#1565C0` — Blue (trust/technology/cleanliness)
- **Accent:** `#FF8F00` — Amber (warmth/bread golden)
- **Neutral:** `#F5F5F5` background, `#37474F` text
- **Semantic:** Success `#2E7D32` · Warning `#F57F17` · Error `#C62828`

## Typography
- **Headings:** Noto Kufi Arabic — sizes: 26/22/18/16px
- **Body:** Noto Sans Arabic — 14px
- **Arabic:** Noto Kufi Arabic (headings), Noto Sans Arabic (body)

## Spacing
- Base unit: 8px
- Padding: 16/24/32px
- Border radius: 6px (cards), 4px (inputs)

## Iconography
- Style: Filled (simple, for operators with limited tech exposure)
- Library: Lucide Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-primary, white text, 6px radius | hover: darken, active: darken, disabled |
| Button Secondary | bg-secondary, white text | hover: lighten |
| Input Field | border 1px #B0BEC5, 6px radius | focus: border-primary, error: border-error |
| Card | bg-white, border 1px #CFD8DC, 6px radius | hover: shadow |
| Badge | pill shape | success, warning, error |
| BatchTimer | bg-primary, white, large font | running: animated, paused: static |
