# Design System — CleanPro
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** "CleanPro" — خدمات تنظيف احترافية بمعايير جودة عالية
- **Logo concept:** أيقونة مكنسة مع دائرة نظافة (ترمز للعناية والنظافة)، اسم CleanPro بخط sans-serif نظيف
- **Brand personality:** نظيف · احترافي · موثوق · سريع · مهتم بالتفاصيل

## Color Palette (Services: Teal/White)
- **Primary:** `#00897B` — أخضر زبرجدي (headers, buttons, primary elements)
- **Secondary:** `#26A69A` — زبرجدي فاتح (secondary elements, highlights)
- **Accent:** `#4DB6AC` — زبرجدي باهت (hover states, backgrounds)
- **Neutral:** `#FFFFFF` — أبيض للخلفيات, `#E0F2F1` — خلفيات فاتحة, `#E0E0E0` حدود, `#616161` نصوص ثانوية
- **Semantic:** Success `#43A047` · Warning `#FDD835` · Error `#E53935` · Info `#039BE5`

## Typography
- **Headings:** Montserrat (English) / Noto Sans Arabic (Arabic) — sizes: 28/24/20/18/16px
- **Body:** Montserrat / Noto Sans Arabic — 14px regular
- **Small/Meta:** 12px
- **Line height:** Headings 1.15, Body 1.6

## Spacing
- Base unit: 8px
- Container max-width: 1280px
- Padding: 16/24/32/48px
- Border radius: 8px (cards/buttons), 4px (inputs), 12px (modals)
- Gap: 16px / 24px

## Iconography
- Style: Filled outline, 1.5px stroke
- Library: Lucide Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-#00897B, white, 8px radius, 14px/600 | hover #00695C / active #004D40 / disabled 50% |
| Button Secondary | border 2px #26A69A, text #26A69A | hover bg-#26A69A10 / active bg-#26A69A20 |
| LiveMap | full height, zoom controls | loading skeleton / interactive / error |
| JobCard | left border status, time, address | normal/hover/selected |
| TeamCard | avatar group, vehicle icon, distance | available green / busy red / offline gray |
| ChecklistItem | task with before/after photo required | pending / done / photo-missing alert |
| AreaAccordion | collapsible sections | collapsed / expanded / complete with ✓ |
| ServiceCard | icon + title + price + duration | normal / hover / selected with check |
| PhotoCapture | before / after pair | captured / uploaded / side-by-side |
| ProgressBar | overall checklist | percentage + step count |
| InvoicePreview | itemized white card | draft / sent / paid / overdue badge |
| CustomerForm | autocomplete inputs | empty / filled / validated |
| Modal | bg-white, 12px radius, backdrop | open/close 200ms |
