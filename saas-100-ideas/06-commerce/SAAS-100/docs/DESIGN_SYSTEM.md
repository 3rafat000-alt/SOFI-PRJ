# Design System — AutoMarket (SAAS-100)
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** AutoMarket — سوق السيارات المستعملة
- **Logo concept:** أيقونة سيارة + درع أمان بالأحمر والأزرق
- **Brand personality:** موثوق، ديناميكي، جريء، عصري، أمن

## Color Palette
- **Primary:** `#DC2626` (Red) — أزرار رئيسية، شعار، عروض
- **Secondary:** `#2563EB` (Blue) — ثقة، روابط، معلومات، ضمان
- **Accent:** `#9CA3AF` (Silver) — عناصر ثانوية، خلفيات، أيقونات
- **Neutral:** `#F9FAFB` — خلفيات فاتحة
- **Text:** `#111827` — نص أساسي
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#DC2626`

## Typography
- **Headings:** `Noto Sans Arabic` — 28/24/20/18/16px
- **Body:** `Noto Sans Arabic` — 14px
- **Monospace:** `JetBrains Mono` (للأرقام والسعر)
- **English:** `Inter` — 14px

## Spacing
- Base: 4px/8px | Padding: 16/24/32/48px | Radius: 8px (أزرار) · 12px (بطاقات سيارات)

## Iconography
- Library: Lucide, Style: Filled (تجاري), Sizes: 20/24/32/40px

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg #DC2626, white, 8px rad | hover #B91C1C / active #991B1B |
| Button Secondary | bg #2563EB, white | hover #1D4ED8 |
| CarCard | bg white, border 1px #E5E7EB, 12px rad | hover border #DC2626 / selected shadow |
| PriceTag | bg #DC2626, white | default, sale, negotiable |
| Badge (Verified) | bg #2563EB, white | verified, pending |
| EscrowStatus | pending #F59E0B, funded #2563EB, released #10B981, refunded #9CA3AF | |
| StarRating | 1-5 stars | filled #F59E0B, empty #D1D5DB |
