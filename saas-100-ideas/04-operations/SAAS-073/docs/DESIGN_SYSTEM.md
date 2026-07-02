# Design System — WaterMgt
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** WaterMgt — إدارة المياه
- **Logo concept:** Water droplet + network nodes icon with clean sans-serif
- **Brand personality:** Clean, Efficient, Reliable, Technical, Transparent

## Color Palette
- **Primary:** `#0E7490` — Blue (headers, nav, primary buttons)
- **Secondary:** `#06B6D4` — Cyan (secondary elements, data viz, links)
- **Accent:** `#0891B2` — Teal (hover states, active indicators)
- **Neutral:** `#F0F9FF` — Sky (backgrounds), `#334155` — slate (text), `#94A3B8` — grey (labels)
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#EF4444`

## Typography
- **Headings:** Cairo (Google Font) — sizes: 26/22/18/16px
- **Body:** Cairo — 13px (dashboard), 14px (mobile app)
- **Arabic:** Cairo — excellent Arabic with clean geometric forms
- **Data:** Tabular numbers for consumption, flow rates, billing

## Spacing
- Base unit: 4px
- Padding: 12/20/24/32px (tight for data-dense dashboards)
- Border radius: 6px (dashboard), 8px (mobile)
- Max content width: 1400px (wide dashboard layout)

## Iconography
- Style: Outline, 20px (dense dashboards)
- Library: Lucide Icons (water, pipe, meter, gauge)

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-blue-600 #0E7490, white text, 6px radius | hover: #0C5A72 / active: #094A5E / disabled |
| MapPin | Circle (water asset), Diamond (valve), Square (meter) | normal/alert/offline |
| DataRow | 13px, border-bottom #E2E8F0 | hover: bg #F0F9FF |
| AlertBanner | Full-width, icon + text | info/ warning/ critical |
| FilterChip | 6px radius, 32px height | selected: bg-blue text-white |
