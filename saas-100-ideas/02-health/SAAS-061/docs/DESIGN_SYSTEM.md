# Design System — LabMgt
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** LabMgt — مختبر (Laboratory) + Management
- **Logo concept:** DNA helix + beaker icon in teal, modern sans-serif text
- **Brand personality:** Clinical, Trustworthy, Precise, Modern, Clean

## Color Palette
- **Primary:** `#0D9488` — Teal 600, trust + health
- **Secondary:** `#059669` — Emerald 600, growth + vitality
- **Accent:** `#2563EB` — Blue 600, precision + clarity
- **Neutral:** `#1F2937` — Gray 800 (text), `#F9FAFB` (bg)
- **Semantic:** Success `#16A34A` · Warning `#D97706` · Error `#DC2626`

## Typography
- **Headings:** Inter — sizes: 24/20/18/16px
- **Body:** Inter — 14px
- **Arabic:** Noto Kufi Arabic — support for patient names, reports

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
| Button Primary | bg #0D9488, white text, 8px radius | hover #0F766E / active #115E59 / disabled opacity 50% / loading spinner |
| Input Field | border 1px #D1D5DB, 12px padding, 14px text | focus ring 2px #0D9488 / error border #DC2626 / disabled bg #F3F4F6 |
| Card | bg white, shadow-sm, 12px padding, 8px radius | hover shadow-md |
| Badge | pill shape, 8px padding horizontal, 12px text | success green / warning amber / error red / neutral gray |
| Table | header bg #F9FAFB, row hover #F3F4F6, sticky header | selected row #ECFDF5 |
| Kanban Column | bg #F3F4F6, 8px radius, min-height 200px | drag-over border dashed #0D9488 |
