# Design System — FishFarm (SAAS-097)
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** FishFarm — إدارة مزارع الأسماك
- **Logo concept:** أيقونة سمكة + موجة ماء بالأزرق المحيطي
- **Brand personality:** طبيعي، موثوق، حديث، محترف، صديق للبيئة

## Color Palette
- **Primary:** `#0077B6` (Ocean Blue) — أزرار، رأس، روابط
- **Secondary:** `#009688` (Teal) — تأكيد، تفاعل، عناصر
- **Accent:** `#4CAF50` (Green) — نجاح، نمو، جودة مياه جيدة
- **Neutral:** `#F0F8FF` (Alice Blue) — خلفيات
- **Text:** `#023E8A` — نص أساسي
- **Semantic:** Success `#2E7D32` · Warning `#FF9800` · Error `#D32F2F`

## Typography
- **Headings:** `Noto Sans Arabic` — 24/20/18/16px
- **Body:** `Noto Sans Arabic` — 14px
- **Monospace:** `JetBrains Mono` (للأرقام والقراءات)
- **English:** `Inter` — 14px

## Spacing
- Base: 4px/8px | Padding: 16/24/32px | Radius: 8px

## Iconography
- Library: Lucide, Style: Outline (طبيعي), Sizes: 20/24/32px

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg #0077B6, white, 8px rad | hover #005F8A / active #004970 |
| Button Alert | bg #FF9800, white | hover #F57C00 |
| SensorBadge | bg, icon, value | normal #4CAF50 / warning #FF9800 / critical #D32F2F |
| Card | bg white, border 1px #B3E5FC, 8px rad | hover border #0077B6 |
| ChartContainer | responsive, min-300px | loading skeleton / error retry / empty placeholder |
| PondIndicator | circle, color-coded | active, harvesting, empty, alert |
