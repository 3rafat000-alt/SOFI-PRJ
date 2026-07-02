# Design System — GarageMaster
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **اسم العلامة:** GarageMaster — جاراج ماستر
- **مفهوم الشعار:** أيقونة مفتاح ربط مع ترس دائري
- **شخصية العلامة:** قوية، عملية، موثوقة، مهنية، صلبة
- **الجمهور:** ورش سيارات، فنيين، مراكز صيانة

## Color Palette
- **Primary:** `#EA580C` — برتقالي غامق (أزرار، شعار، رؤوس)
- **Secondary:** `#1E293B` — كحلي غامق (أشرطة، خلفيات داكنة)
- **Accent:** `#F59E0B` — كهرماني (تحذيرات)
- **Neutral-100:** `#FFF7ED` — خلفية دافئة
- **Neutral-200:** `#FED7AA` — حدود
- **Neutral-700:** `#431407` — نص أساسي
- **Neutral-900:** `#1C1917` — عناوين
- **Semantic:** Success `#16A34A` · Warning `#F59E0B` · Error `#DC2626`
- **Gradient:** `linear-gradient(135deg, #EA580C, #1E293B)`

## Typography
- **Headings (English):** Plus Jakarta Sans — 28/24/20/18/16px
- **Body (English):** Inter — 14px
- **Arabic:** Noto Sans Arabic
- **Kanban text:** Inter SemiBold — 16px (card title)
- **Monospace:** JetBrains Mono — 13px (plate numbers)
- **Line height:** Headings 1.2, Body 1.5

## Spacing
- Base: 4px
- Padding: 8/12/16/20/24/32/48px
- Radius: 4px (badges), 8px (cards), 12px (modals)
- Container: 1440px (dashboard), 1140px (public)
- Kanban card min-width: 280px

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg=#EA580C, white text, 8px radius, bold | hover:#C2410C / active:#9A3412 / disabled:#FDBA74 |
| Kanban Column | bg=#F1F5F9, 8px radius, p=12px | droppable:bg#E2E8F0 |
| Job Card | bg=white, 8px radius, shadow-sm | pending:#F59E0B / in-progress:#3B82F6 / qc:#8B5CF6 / done:#10B981 / delivered:#6B7280 |
| Vehicle Search | icon prefix, 12px padding, 8px radius | result dropdown, loading spinner |
| Service Line | bg=#F9FAFB, 8px radius, p=12px | editable inline |
| Parts Line | bg=white, border bottom | in-stock / low / out |
| Invoice Preview | bg=white, 8px radius | paid:border #16A34A / draft:border #D1D5DB |
| Status Timeline | vertical dots + lines | animated, current dot pulsing |
