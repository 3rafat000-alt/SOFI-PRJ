# Design System — HallBooking (SAAS-093)
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** HallBooking — قاعات المناسبات
- **Logo concept:** أيقونة قاعة مضاءة بالثريا + وردة ذهبية
- **Brand personality:** فاخر، أنيق، دافئ، احتفالي، عصري

## Color Palette
- **Primary:** `#B76E79` (Rose Gold) — أزرار، روابط، أيقونات
- **Secondary:** `#FFF8F0` (Cream) — خلفيات، بطاقات، مساحات واسعة
- **Accent:** `#D4A574` (Warm Gold) — عناصر مميزة، تأكيدات
- **Neutral:** `#F5F0EB` — خلفيات ثانوية
- **Text:** `#2C1810` — نص أساسي داكن
- **Semantic:** Success `#5B8C5A` · Warning `#D4A017` · Error `#A0524A`

## Typography
- **Headings:** `Noto Naskh Arabic` — 28/24/20/18px (زخرفي للمناسبات)
- **Body:** `Noto Sans Arabic` — 14/16px
- **English:** `Playfair Display` (headings) · `Inter` (body)
- **Arabic:** `Noto Naskh Arabic` — مناسب للنصوص الفاخرة

## Spacing
- Base: 4px/8px | Padding: 16/24/32/48px | Radius: 12px (ناعم) · 8px (أزرار)

## Iconography
- Library: Lucide, Style: Outline (لطيف), Sizes: 20/24/32px

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg #B76E79, white, 12px rad | hover #C88A93 / active #A05A65 / disabled 0.5 |
| Button Secondary | transparent, border #B76E79 | hover bg #B76E79 10% |
| Input | border 1px #D4C5B8, 12px pad | focus #B76E79 shadow / error #A0524A |
| Card | bg #FFF8F0, shadow 2px 4px 12px rgba(183,110,121,0.12) | hover shadow 4px 8px 20px |
| Badge | capacity, status, price | default, premium, sold-out |
