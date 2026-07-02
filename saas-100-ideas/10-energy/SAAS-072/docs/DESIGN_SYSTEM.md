# Design System — RooftopSolar
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** RooftopSolar — الطاقة الشمسية للأسطح
- **Logo concept:** Sun + house roof icon with solar panels
- **Brand personality:** Innovative, Energetic, Trustworthy, Green-conscious, Modern

## Color Palette
- **Primary:** `#EAB308` — Yellow (CTA buttons, highlights, energy badges)
- **Secondary:** `#F97316` — Orange (secondary actions, warnings, savings indicators)
- **Neutral:** `#334155` — Slate (text, headings, UI elements)
- **Background:** `#FFFBEB` — Warm cream (page bg), `#F8FAFC` — light slate (card bg)
- **Semantic:** Success `#22C55E` · Warning `#EAB308` · Error `#EF4444`

## Typography
- **Headings:** IBM Plex Sans Arabic — sizes: 28/24/20/18px
- **Body:** IBM Plex Sans Arabic — 14px regular
- **Numbers:** Tabular figures (monospace numbers for savings, kWh)
- **Arabic:** IBM Plex Sans Arabic (excellent Arabic + RTL)

## Spacing
- Base unit: 4px/8px
- Padding: 16/24/32/48px
- Border radius: 12px (cards), 8px (buttons), 6px (inputs)
- Max content width: 1100px

## Iconography
- Style: Filled, 24px default
- Library: Lucide Icons (sun, panel, bolt, leaf)

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-yellow-500 #EAB308, black text, 12px radius | hover: #D97706 / active: #B45309 / disabled: opacity 50% |
| Button Secondary | bg-orange-500 #F97316, white text | hover: #EA580C / active: #C2410C |
| Gauge | Circular arc, bg #F3F4F6, fill yellow-500 | warning: orange / critical: red |
| Input | border #CBD5E1, 12px radius | focus: ring yellow-400 |
| Card | bg white, 12px radius, border #E2E8F0 | hover: shadow-lg |
