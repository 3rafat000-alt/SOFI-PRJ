# Design System — FarmTech
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** "FarmTech" — تكنولوجيا الزراعة لجعل المزارع أكثر ذكاءً وإنتاجية
- **Logo concept:** ورقة خضراء مع خطوط تكنولوجية (circuit leaf)، اسم FarmTech بخط sans-serif عريض
- **Brand personality:** طبيعي · موثوق · مبتكر · بسيط · صديق للبيئة

## Color Palette (Agriculture: Green/Earth)
- **Primary:** `#2D5016` — أخضر غابات عميق (headers, nav, primary buttons)
- **Secondary:** `#8B7355` — بني ترابي دافئ (secondary elements, icons)
- **Accent:** `#4A7C28` — أخضر فاتح للأرضيات والـ hover
- **Neutral:** `#F5F2ED` — بيج فاتح للخلفيات، `#D4C9B5` للحدود، `#5C4F3E` للنصوص الثانوية
- **Semantic:** Success `#28A745` · Warning `#FFC107` · Error `#DC3545` · Info `#17A2B8`

## Typography
- **Headings:** Rubik (English) / Noto Sans Arabic (Arabic) — sizes: 28/24/20/18/16px
- **Body:** Rubik / Noto Sans Arabic — 14px regular
- **Small/Meta:** 12px
- **Line height:** Headings 1.15, Body 1.6

## Spacing
- Base unit: 8px
- Container max-width: 1280px
- Padding: 16/24/32/48px
- Border radius: 8px (cards/buttons), 4px (inputs), 12px (modals)
- Gap: 16px / 24px

## Iconography
- Style: Outline, 1.5px stroke
- Library: Lucide Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-#2D5016, white text, 8px radius, 14px/500 | hover #3A6B1C / active #234012 / disabled opacity 50% |
| Button Secondary | border 2px #8B7355, text #8B7355 | hover bg-#8B735510 / active bg-#8B735520 |
| Input Field | border 1px #D4C9B5, 12px padding, 8px radius | focus border-#2D5016 ring-2px / error border-#DC3545 |
| Card | bg-white, border 1px #D4C9B5, 8px radius | hover shadow / selected border-#4A7C28 |
| Select | custom dropdown, searchable | focus border-#2D5016 |
| Badge (quality) | pill, 12px/600 | A bg-#2D501610 text-#2D5016 / B text-#8B7355 / C text-#DC3545 |
| ParcelCard | mini map + status + details | normal/hover/selected border color active/inactive |
| Modal | bg-white, 12px radius, backdrop 50% | open/close 200ms |
| WeatherWidget | 7-day compact | loading (skeleton) / error / data with temperature |
