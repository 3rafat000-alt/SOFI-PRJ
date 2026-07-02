# Design System — GasStation
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** Gas (وقود) + Station (محطة) — محطة وقود
- **Logo concept:** مضخة وقود + مؤشر وقود، بألوان الطاقة
- **Brand personality:** صناعي، موثوق، عملي، متين، مباشر

## Color Palette
- **Primary:** `#FDD835` — Yellow (energy/fuel/warning)
- **Secondary:** `#E65100` — Orange (heat/power/action)
- **Accent:** `#1565C0` — Blue (tech/precision/trust)
- **Neutral:** `#F5F5F5` background, `#212121` text
- **Semantic:** Success `#2E7D32` · Warning `#FF6F00` · Error `#C62828`

## Typography
- **Headings:** Noto Sans Arabic — sizes: 26/22/18/16px
- **Body:** Noto Sans Arabic — 13px (14px mobile)
- **Arabic:** Noto Sans Arabic
- **Numbers:** Monospace for tank readings (tabular)

## Spacing
- Base unit: 8px
- Padding: 12/16/24/32px
- Border radius: 4px (functional, industrial)

## Iconography
- Style: Filled (clear for operators, quick recognition)
- Library: Lucide Icons + custom fuel/tank icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-primary, dark text, 4px radius | hover: darken, active: darken, disabled |
| Button Danger | bg-error, white text | hover: darken |
| TankGauge | gradient fill, color zones | safe (green), warning (yellow), critical (red) |
| Input Field | border 1px #9E9E9E, 4px radius | focus: border-secondary, error: border-error |
| Card | bg-white, border 1px #E0E0E0, 4px radius | hover: shadow |
| Toggle | switch | on/off with label |
