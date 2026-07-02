# Design System — ColdStorage (SAAS-087)
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** ColdStorage — تبريد (Arabic for refrigeration)
- **Logo concept:** Snowflake + thermometer icon, ice blue wordmark
- **Brand personality:** Cold, Precise, Trustworthy, Modern, Efficient

## Color Palette
- **Primary:** `#0D9488` — Teal-600 (headers, buttons, brand)
- **Secondary:** `#14B8A6` — Teal-500 (secondary, hover, links)
- **Accent:** `#06B6D4` — Cyan-500 (highlights, temperature indicators)
- **Background:** `#F0FDFA` — Teal-50 (cool page background)
- **Surface:** `#FFFFFF` — White (cards, modals)
- **Neutral:** `#64748B` — Slate-500 (muted text, borders)
- **Semantic:** Success `#22C55E` · Warning `#EAB308` · Error `#EF4444` · Info `#06B6D4`

## Typography
- **Headings:** Inter — sizes: 24/20/18/16px, semibold
- **Body:** Inter — 14px regular, line-height 1.6
- **Arabic:** Noto Sans Arabic — full support
- **Monospace:** JetBrains Mono — 13px (temperature readings, batch codes)

## Spacing
- Base unit: 4px
- Padding: 16/24/32/48px
- Gap: 8/12/16/24px
- Border radius: 8px (cards), 6px (inputs), 4px (gauges)
- Shadow: sm, md, lg

## Iconography
- Style: Outline 2px stroke
- Library: Lucide Icons
- Size: 20/24/32px

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-teal-600, white text, 8px radius | hover:bg-teal-700 / active:scale-98 / disabled:opacity-50 |
| Button Secondary | border-teal-300, text-teal-700 | hover:bg-teal-50 |
| ColdRoomCard | Gauge with border | normal:teal-border / warning:orange-border / critical:red-border pulse |
| TempGauge | Arc gauge | blue fill(0°C) / white(-20°C) / red(breach) |
| ExpiryHeatmap | Calendar grid | green(>30d) / yellow(7-30d) / red(<7d) |
| AlertBanner | Top bar | info:blue / warning:orange / critical:red with slide-in |
| ProductRow | Horizontal row | default/hover / expiring:pulse |
| LocationPicker | Grid of zones | available:green / partial:yellow / full:red |
