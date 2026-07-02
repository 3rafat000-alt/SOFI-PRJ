# Design System — DentistPro
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** DentistPro — طبيب أسنان محترف
- **Logo concept:** Tooth silhouette + cross icon in teal, clean rounded sans-serif
- **Brand personality:** Professional, Gentle, Modern, Caring, Precise

## Color Palette
- **Primary:** `#14B8A6` — Teal 500, health + trust
- **Secondary:** `#0D9488` — Teal 600, depth
- **Accent:** `#0284C7` — Sky 600, clarity + coolness
- **Neutral:** `#1E293B` — Slate 800 (text), `#F8FAFC` (bg)
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#EF4444`

## Typography
- **Headings:** Inter — sizes: 24/20/18/16px
- **Body:** Inter — 14px
- **Arabic:** Noto Kufi Arabic — patient records, treatment notes

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
| Button Primary | bg #14B8A6, white text, 8px radius | hover #0D9488 / active #0F766E / disabled |
| Input Field | border 1px #CBD5E1, 12px padding | focus ring #14B8A6 / error #EF4444 |
| Tooth SVG | interactive tooth, stroke #94A3B8 | hover fill #BAE6FD / selected #14B8A6 |
| Card | bg white, shadow, 8px radius | hover shadow-lg |
| Calendar Slot | bg white, border 1px | available #10B981 / booked #3B82F6 / past #CBD5E1 |
| X-ray Viewer | dark bg #0F172A, fit-contain | zoom 1x-5x, pan enabled |
