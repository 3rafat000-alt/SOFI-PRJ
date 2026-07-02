# Design System — PowerBackup (SAAS-099)
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** PowerBackup — الطاقة الاحتياطية
- **Logo concept:** أيقونة مولد كهرباء + وميض أصفر (طاقة)
- **Brand personality:** قوي، موثوق، طارئ، جاهز، احترافي

## Color Palette
- **Primary:** `#F59E0B` (Yellow/Amber) — أزرار رئيسية، تحذيرات، طاقة
- **Secondary:** `#1E293B` (Slate) — خلفيات داكنة، رأس، تنقل
- **Accent:** `#DC2626` (Red) — أعطال، إنذارات طارئة
- **Neutral:** `#F8FAFC` — خلفيات فاتحة
- **Text:** `#0F172A` — نص أساسي
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#DC2626`

## Typography
- **Headings:** `Noto Sans Arabic` — 28/24/20/18/16px
- **Body:** `Noto Sans Arabic` — 14px
- **Monospace:** `JetBrains Mono` (للقراءات الفنية)
- **English:** `Inter` — 14px

## Spacing
- Base: 4px/8px | Padding: 16/24/32/48px | Radius: 8px (أزرار) · 4px (gauges)

## Iconography
- Library: Lucide, Style: Filled (طاقة), Sizes: 20/24/32px

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg #F59E0B, #1E293B text, 8px rad | hover #D97706 / active #B45309 |
| Button Danger | bg #DC2626, white | hover #B91C1C |
| GaugeArc | track #E2E8F0, arc color by status | normal #10B981 / warning #F59E0B / critical #DC2626 |
| Card Dark | bg #1E293B, white text, 8px rad | hover bg #334155 |
| GeneratorStatusDot | online #10B981, idle #F59E0B, offline #94A3B8, alert #DC2626 | pulse animation on alert |
| FuelBar | percentage fill | low <25% #DC2626 / medium <50% #F59E0B / high #10B981 |
