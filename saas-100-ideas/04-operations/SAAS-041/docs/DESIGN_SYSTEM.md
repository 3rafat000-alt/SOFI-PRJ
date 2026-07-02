# Design System — AlertHub
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name:** AlertHub — مركز التنبيهات
- **Logo concept:** حرف A على شكل جرس إنذار مع موجات (signal waves)
- **Brand personality:** موثوق، سريع، مهني، واضح، جاد

## Color Palette
- **Primary:** `#1A56DB` — أزرق تقني للرؤوس والأزرار الرئيسية
- **Secondary:** `#0E9F6E` — أخضر للحالات الناجحة والمؤكدة
- **Accent:** `#F05252` — أحمر للتنبيهات الحرجة والتحذيرات
- **Neutral:** `#F3F4F6` خلفية، `#374151` نص أساسي، `#6B7280` نص ثانوي
- **Semantic:** Success `#0E9F6E` · Warning `#F59E0B` · Error `#F05252` · Info `#3F83F8`

## Typography
- **Headings:** Inter — sizes: 24/20/18/16px
- **Body:** Inter — 14px / 1.5
- **Arabic:** Noto Sans Arabic — support for all Arabic text
- **Monospace:** JetBrains Mono — for code/variable snippets

## Spacing
- Base unit: 4px
- Padding: 16/24/32px
- Border radius: 8px (cards), 6px (buttons), 4px (inputs)
- Grid: 12-column, gutter 24px

## Iconography
- Style: Outline (2px stroke)
- Library: Lucide Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-#1A56DB, white text, 8px radius, 14px font | hover: #1648B5 / active: #123A94 / disabled: opacity 0.5 / loading: spinner |
| Button Critical | bg-#F05252, white text | hover: #E02424 / active: #C81E1E |
| Input Field | border 1px #D1D5DB, 12px padding, 8px radius | focus: border #1A56DB + ring / error: border #F05252 / disabled: bg #F9FAFB |
| AlertBadge | pill shape, 6px padding, 12px font | pulse animation for critical, static otherwise |
| StatCard | white bg, shadow-sm, 16px padding, rounded-lg | hover: shadow-md |
| DataTable | header bg #F9FAFB, row stripes | hover row: bg #F3F4F6, selected: bg #EFF6FF |
