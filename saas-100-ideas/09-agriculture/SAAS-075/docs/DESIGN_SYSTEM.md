# Design System — SouqFarmer
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** SouqFarmer — سوق المزارع
- **Logo concept:** Leaf + farmer hat + basket icon — earthy natural shapes
- **Brand personality:** Fresh, Natural, Honest, Community-driven, Earthy

## Color Palette
- **Primary:** `#15803D` — Green (CTA, fresh badges, farmer profile)
- **Secondary:** `#A16207` — Earthy Brown (headings, nav, secondary elements)
- **Accent:** `#65A30D` — Olive (hover states, organic badges)
- **Neutral:** `#FEFCE8` — Cream (backgrounds), `#422006` — deep brown (text), `#78716C` — warm grey (labels)
- **Semantic:** Success `#16A34A` · Warning `#CA8A04` · Error `#DC2626`

## Typography
- **Headings:** Noto Naskh Arabic (Google Font) — classic Arabic — sizes: 28/24/20/18px
- **Body:** Noto Naskh Arabic — 14px (consumer), 16px (farmer app)
- **Arabic:** Noto Naskh Arabic — elegant formal Arabic
- **Display:** Amiri (optional for logo)

## Spacing
- Base unit: 4px/8px
- Padding: 16/24/32px
- Border radius: 12px (cards), 8px (buttons), 20px (product cards)
- Max content width: 1100px

## Iconography
- Style: Filled, organic shapes, 24px
- Library: Lucide Icons (wheat, apple, cow, truck, leaf)

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-green-700 #15803D, white text, 8px radius | hover: #166534 / active: #14532D |
| Button Farmer | lg text 18px, bg-earth-600 #A16207 | hover: #854D0E |
| ProductCard | 20px radius, shadow-md, border #E7E5E4 | hover: shadow-xl |
| QualityBadge | Grade A: green, B: yellow, C: orange | letter + coloured bg |
| CategoryChip | Pill, 16px padding | selected: bg-green text-white |
| PriceTag | Bold, earth-700 #A16207 | sale: cross-out + new price |
