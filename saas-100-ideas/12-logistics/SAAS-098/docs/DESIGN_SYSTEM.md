# Design System — CourierMgt (SAAS-098)
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** CourierMgt — إدارة شركات التوصيل
- **Logo concept:** أيقونة طرد + مسار (نقطة إلى نقطة) + سهم
- **Brand personality:** سريع، دقيق، موثوق، ديناميكي، عصري

## Color Palette
- **Primary:** `#2563EB` (Blue) — أزرار، رأس، روابط رئيسية
- **Secondary:** `#EA580C` (Orange) — تفاعل، تأكيدات، مسار محسن
- **Accent:** `#10B981` (Green) — تم التوصيل، نجاح
- **Neutral:** `#F8FAFC` — خلفيات
- **Text:** `#1E293B` — نص أساسي
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#EF4444`

## Typography
- **Headings:** `Noto Sans Arabic` — 24/20/18/16px
- **Body:** `Noto Sans Arabic` — 14px
- **Monospace:** `JetBrains Mono` (للأرقام التتبعية)
- **English:** `Inter` — 14px

## Spacing
- Base: 4px/8px | Padding: 12/16/24/32px | Radius: 8px

## Iconography
- Library: Lucide, Style: Filled (لوجستي), Sizes: 20/24/32px

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg #2563EB, white, 8px rad | hover #1D4ED8 / active #1E40AF |
| Button Secondary | bg #EA580C, white | hover #C2410C |
| Input | border 1px #CBD5E1, 8px rad | focus #2563EB shadow |
| MapMarker | driver, pickup, dropoff | default, selected, animating |
| StatusBadge | pending #F59E0B, in-transit #2563EB, delivered #10B981, failed #EF4444 |
| TimelineItem | dot + line + text | active, completed, pending |
