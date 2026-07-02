# Design System — GasDistribute (SAAS-085)
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** GasDistribute — توزيع غاز (Arabic for gas distribution)
- **Logo concept:** Flame icon + cylinder silhouette, orange/blue wordmark
- **Brand personality:** Energetic, Safe, Reliable, Fast, Professional

## Color Palette
- **Primary:** `#EA580C` — Orange-600 (headers, buttons, brand)
- **Secondary:** `#2563EB` — Blue-600 (secondary, links, info)
- **Accent:** `#F97316` — Orange-500 (highlights, alerts)
- **Background:** `#FFF7ED` — Orange-50 (warm page background)
- **Surface:** `#FFFFFF` — White (cards, modals)
- **Neutral:** `#78716C` — Stone-500 (muted text, borders)
- **Semantic:** Success `#16A34A` · Warning `#EAB308` · Error `#DC2626` · Info `#2563EB`

## Typography
- **Headings:** Plus Jakarta Sans — sizes: 24/20/18/16px, bold
- **Body:** Inter — 14px regular, line-height 1.6
- **Arabic:** Noto Sans Arabic — full support
- **Driver UI:** 18px minimum (large touch targets for drivers)
- **Monospace:** JetBrains Mono — 13px (cylinder IDs)

## Spacing
- Base unit: 4px
- Padding: 16/24/32/48px
- Gap: 8/12/16/24px
- Border radius: 8px (cards), 6px (inputs), 999px (badges)
- Shadow: sm, md, lg

## Iconography
- Style: Filled (driver UI for visibility), Outline (dashboard)
- Library: Lucide Icons
- Size: 24px (driver), 20px (dashboard)

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-orange-600, white text, 8px radius | hover:bg-orange-700 / active:scale-98 / disabled:opacity-50 |
| Button Driver | bg-blue-600, white, 48px min height, 16px font | hover:bg-blue-700 / active:scale-98 |
| CylinderBadge | Color by status (filled/empty/in-transit) | green/slate/blue variants |
| BarcodeScanner | Full screen camera | scanning:overlay / found:green-border / error:red-border |
| DriverCard | Large avatar, status dot | available:green / busy:orange / offline:slate |
| StockGauge | Horizontal bar with fill | safe:blue / low:orange / critical:red with pulse |
| PhotoProof | Captured image thumbnail | empty:dashed / captured:border / uploading:spinner |
