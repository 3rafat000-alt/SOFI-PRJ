# Design System — NetworkHub CRM
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name:** NetworkHub — مركز الشبكات
- **Logo concept:** أيقونة شبكة (دوائر متصلة) مع نجمة في المركز
- **Brand personality:** مهني، موثوق، ديناميكي، بسيط، متصل

## Color Palette
- **Primary:** `#2563EB` — أزرق زاهي للأزرار والروابط
- **Secondary:** `#7C3AED` — بنفسجي للصفقات والتمايز
- **Accent:** `#10B981` — أخضر للفوز بالصفقات والنشاط
- **Neutral:** `#F8FAFC` خلفية، `#0F172A` نص رئيسي، `#475569` نص ثانوي
- **Semantic:** Won `#10B981` · Lost `#EF4444` · Pending `#F59E0B` · Lead `#3B82F6`

## Typography
- **Headings:** Inter — sizes: 24/20/18/16px
- **Body:** Inter — 14px / 1.5
- **Arabic:** Noto Sans Arabic — for Arabic CRM labels
- **Table:** Tabular figures for deal values

## Spacing
- Base unit: 4px
- Padding: 16/20/24/32px
- Border radius: 8px (cards), 6px (buttons), 4px (table cells)
- Grid: 12-column, gutter 16px

## Iconography
- Style: Outline (1.5px stroke)
- Library: Lucide Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-#2563EB, white text, 6px radius | hover: #1D4ED8 / active: #1E40AF / disabled: opacity 0.5 |
| ContactCard | border 1px #E2E8F0, 8px radius, 12px padding | hover: shadow-sm / selected: border #2563EB |
| PipelineColumn | bg #F8FAFC, min-width 280px | drag-over: bg #EFF6FF + border dashed #2563EB |
| DealCard | white bg, left border colour | won: #10B981 / lost: #EF4444 / pending: #F59E0B |
| TimelineItem | icon + text + time in a row | expandable: chevron rotates |
| TaskCard | white bg, 8px radius, checkbox | overdue: red left border / checked: opacity 0.6 |
| KPICard | white bg, shadow-sm, value large | trend up: green arrow / trend down: red arrow |
| TableHeader | bg #F1F5F9, sticky, sortable | sorted: arrow indicator / hover: bg #E2E8F0 |
