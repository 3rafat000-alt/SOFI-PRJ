# Design System — SolarPro
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** Solar (شمسي) + Pro (احترافي) — محترف الطاقة الشمسية
- **Logo concept:** شمس + لوح شمسي + سهم طاقة، بخط عصري
- **Brand personality:** نظيف، حديث، أخضر، مبتكر، محترف

## Color Palette
- **Primary:** `#FDD835` — Yellow (sun/energy/light)
- **Secondary:** `#E65100` — Orange (heat/power/warmth)
- **Accent:** `#2E7D32` — Green (eco/environment/savings)
- **Neutral:** `#FAFAFA` background, `#212121` text
- **Semantic:** Success `#2E7D32` · Warning `#F57F17` · Error `#C62828`

## Typography
- **Headings:** Noto Sans Arabic — sizes: 28/24/20/18px
- **Body:** Noto Sans Arabic — 14px
- **Arabic:** Noto Sans Arabic
- **Numbers:** Tabular for energy/production data

## Spacing
- Base unit: 8px
- Padding: 16/24/32px
- Border radius: 8px (cards), 20px (buttons)

## Iconography
- Style: Outline (clean, modern)
- Library: Lucide Icons + custom solar icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-primary, dark text, 20px radius | hover: darken, active, disabled |
| Button Secondary | bg-secondary, white text | hover: lighten |
| Input Field | border 1px, 8px radius | focus: border-primary, error: border-error |
| Card | white, shadow-sm, 8px radius | hover: shadow-md |
| ProductionGauge | gradient green-yellow | live: animate, idle: dim |
| HealthDot | 3 colors | green (online), yellow (warning), red (offline) |
