# Design System — MobileFix (SAAS-095)
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** MobileFix — صيانة الجوالات
- **Logo concept:** أيقونة جوال + مفتاح ربط باللون السماوي
- **Brand personality:** تقني، سريع، دقيق، عصري، شاب

## Color Palette
- **Primary:** `#06B6D4` (Cyan) — أزرار، روابط، شريط علوي
- **Secondary:** `#475569` (Slate) — تنقل، خلفيات داكنة، نصوص ثانوية
- **Accent:** `#10B981` (Green) — نجاح، متاح، ضمان
- **Neutral:** `#F1F5F9` — خلفيات فاتحة
- **Text:** `#0F172A` — نص أساسي
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#EF4444`

## Typography
- **Headings:** `Noto Sans Arabic` — 24/20/18/16px
- **Body:** `Noto Sans Arabic` — 14px
- **Monospace:** `JetBrains Mono` (تقني / أكواد)
- **English:** `Inter` — 14/16px

## Spacing
- Base: 4px/8px | Padding: 12/16/24px | Radius: 8px (أزرار) · 4px (حقول)

## Iconography
- Library: Lucide, Style: Filled (تقني), Sizes: 20/24/32px

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg #06B6D4, white, 8px rad | hover #0891B2 / active #0E7490 |
| Button Danger | bg #EF4444, white | hover #DC2626 |
| Input | border 1px #CBD5E1, 4px rad | focus #06B6D4 shadow |
| KanbanCard | bg white, border-left 4px (color=status) | hover shadow, dragging opacity 0.5 |
| StatusBadge | pending #F59E0B, diagnosing #06B6D4, repairing #8B5CF6, ready #10B981 | |
