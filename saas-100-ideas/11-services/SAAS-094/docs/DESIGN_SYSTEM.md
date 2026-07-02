# Design System — ToolRental (SAAS-094)
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** ToolRental — تأجير الأدوات
- **Logo concept:** أيقونة مفتاح ربط + أيقونة إيجار باللون البرتقالي
- **Brand personality:** عملي، متين، موثوق، سريع، مباشر

## Color Palette
- **Primary:** `#475569` (Slate) — أزرار، رأس، تنقل
- **Secondary:** `#F97316` (Orange) — تفاعل، تأكيد، عناوين بارزة
- **Accent:** `#FCD34D` (Yellow) — تنبيهات، تحذيرات
- **Neutral:** `#F8FAFC` — خلفيات
- **Text:** `#1E293B` — نص أساسي
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#EF4444`

## Typography
- **Headings:** `Noto Sans Arabic` — 24/20/18/16px
- **Body:** `Noto Sans Arabic` — 14px
- **English:** `Inter` — 14/16px
- **Arabic:** `Noto Sans Arabic`

## Spacing
- Base: 4px/8px | Padding: 12/16/24/32px | Radius: 8px (أزرار) · 6px (حقول)

## Iconography
- Library: Lucide, Style: Filled (أدوات), Sizes: 20/24/32px

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg #475569, white, 8px rad | hover #334155 / active #1E293B |
| Button CTA | bg #F97316, white | hover #EA580C / active #C2410C |
| Input | border 1px #CBD5E1, 6px rad | focus #F97316 shadow |
| Card | bg white, border 1px #E2E8F0, 8px rad | hover border #F97316 |
| Badge | available (#10B981), rented (#F59E0B), maintenance (#EF4444) | |
| Slider | track #E2E8F0, thumb #F97316 | hover thumb scale 1.2 |
