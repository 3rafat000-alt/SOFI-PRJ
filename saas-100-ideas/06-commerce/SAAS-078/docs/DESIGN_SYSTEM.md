# Design System — ButcherPro
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** ButcherPro — قصاب برو
- **Logo concept:** Cleaver knife + meat cut diagram icon
- **Brand personality:** Fresh, Traditional, Trustworthy, Quality-first, Local

## Color Palette
- **Primary:** `#991B1B` — Rich Red (CTA, fresh badges, brand identity)
- **Secondary:** `#78350F` — Brown (headings, nav, earthy tones)
- **Accent:** `#D97706` — Amber (discounts, special offers, highlights)
- **Neutral:** `#FEF2F2` — Light pink (backgrounds), `#1F2937` — dark grey (text), `#9CA3AF` — grey (labels)
- **Semantic:** Success `#22C55E` · Warning `#F59E0B` · Error `#DC2626`

## Typography
- **Headings:** Noto Naskh Arabic (classic traditional) — sizes: 28/24/20/18px
- **Body:** Noto Naskh Arabic — 14px (products), 18px (customer app for legibility)
- **Arabic:** Noto Naskh Arabic — clear traditional Arabic
- **Numbers:** Bold large (weight display 24px+)

## Spacing
- Base unit: 4px/8px
- Padding: 16/20/24px
- Border radius: 12px (cards), 8px (buttons), 4px (inputs)
- Max content width: 1000px

## Iconography
- Style: Filled, 24px
- Library: Lucide Icons (beef, scale, truck, knife, package)

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-red-800 #991B1B, white text, 8px radius | hover: #7F1D1D / active: #581C1C |
| WeightDisplay | 24px bold, monospace numbers | stable: green / unstable: amber |
| MeatCutCard | 12px radius, product photo dominant | available/ out-of-stock (grey) |
| ScaleIndicator | Connected: green dot + "متصل" | disconnected: red + reconnect |
| OrderAlert | Incoming: slide-down banner + sound | new/ acknowledged |
| HalalBadge | Green checkmark + "حلال" text | verified / pending verification |
