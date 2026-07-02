# Design System — RepairPro
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** RepairPro — تصليح محترف
- **Logo concept:** Wrench + gear icon in slate, orange accent for energy
- **Brand personality:** Reliable, Practical, Industrial, Trustworthy, Efficient

## Color Palette
- **Primary:** `#475569` — Slate 600, reliability + professionalism
- **Secondary:** `#EA580C` — Orange 600, energy + action
- **Accent:** `#0F172A` — Slate 900, strength + seriousness
- **Neutral:** `#1E293B` — Slate 800 (text), `#F8FAFC` (bg)
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#EF4444`

## Typography
- **Headings:** Inter — sizes: 24/20/18/16px
- **Body:** Inter — 14px
- **Arabic:** Noto Kufi Arabic — device info, customer communication, invoices

## Spacing
- Base unit: 4px/8px
- Padding: 16/24/32px
- Border radius: 6px (slightly sharper for industrial feel)

## Iconography
- Style: Filled (for kanban), Outline (for UI)
- Library: Lucide

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg #475569, white text, 6px radius | hover #374151 / active #1F2937 / disabled |
| Button Secondary | bg #EA580C, white text | hover #D97706 / active #C2410C |
| Device Card | bg white, shadow, 6px radius, left border status | received #94A3B8 / diagnosing #F59E0B / repairing #3B82F6 / ready #10B981 |
| Kanban Column | bg #F1F5F9, 6px radius, min-h 300px | drag-over border dashed #EA580C |
| Barcode Label | white bg, black code | printable 40mm × 25mm |
| Stock Level | green #10B981 > 20 / amber #F59E0B 5-20 / red #EF4444 1-5 / empty #94A3B8 | — |
| Photo Attachment | thumbnail grid, 80px each | upload progress / failed retry |
| Status Timeline | vertical dots connected | completed green / current orange / upcoming gray |
