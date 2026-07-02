# Design System — LawDesk
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** "LawDesk" — مكتب القانون، رمز الثقة والتنظيم القانوني
- **Logo concept:** رمح العدالة (icon) مع اسم LawDesk بخط sans-serif عصري، النص العربي "لاوديسك" بخط مناسب للغة العربية
- **Brand personality:** مهني · موثوق · جاد · منظم · عصري

## Color Palette (Legal: Navy/Gold)
- **Primary:** `#0A1F3F` — Navy عميق للثقة والاحترافية (headers, nav, buttons)
- **Secondary:** `#C9A84C` — Gold للأيقونات والتأكيدات والعناصر البارزة
- **Accent:** `#1B3A6B` — Navy فاتح للـ hover والـ active
- **Neutral:** `#F8F9FA` — خلفية الصفحة، `#E9ECEF` حدود، `#6C757D` نصوص ثانوية
- **Semantic:** Success `#28A745` · Warning `#FFC107` · Error `#DC3545` · Info `#17A2B8`

## Typography
- **Headings:** Inter (English) / Noto Sans Arabic (Arabic) — sizes: 28/24/20/18/16px
- **Body:** Inter / Noto Sans Arabic — 14px regular
- **Small/Meta:** 12px
- **Line height:** Headings 1.2, Body 1.6

## Spacing
- Base unit: 8px
- Container max-width: 1280px
- Padding: 16/24/32/48px
- Border radius: 8px (cards/buttons), 4px (inputs), 16px (modals)
- Gap grid: 16px / 24px

## Iconography
- Style: Outline, 1.5px stroke, 24x24 default
- Library: Lucide Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-#0A1F3F, white text, 8px radius, 14px/500 | hover #1B3A6B / active #071632 / disabled opacity 50% / loading spinner |
| Button Secondary | border 2px #C9A84C, text #C9A84C, transparent bg | hover bg-#C9A84C10 / active bg-#C9A84C20 / disabled opacity 50% |
| Input Field | border 1px #D1D5DB, 12px padding, 8px radius, 14px | focus border-#0A1F3F ring-2px / error border-#DC3545 / disabled bg-#E9ECEF |
| Card | bg-white, border 1px #E9ECEF, 8px radius, 16px padding, shadow-sm | hover shadow-md / selected border-#C9A84C |
| Select | similar to Input, custom dropdown | focus border-#0A1F3F / disabled |
| Badge (status) | pill, 12px/600, 6px padding x 12px | active bg-#28A74510 text-#28A745 / pending bg-#FFC10710 text-#856404 / closed bg-#E9ECEF text-#6C757D / overdue bg-#DC354510 text-#DC3545 |
| Modal | bg-white, 16px radius, backdrop rgba(0,0,0,0.5), max-w 600px | open/close transition 200ms |
| Table | striped rows, sticky header | hover row highlight / sort indicator on header |
| Tab | horizontal, active indicator bottom | active text-#0A1F3F border-bottom 3px / hover text-#1B3A6B |
| Toast | 8px radius, 14px, icon + message | success bg-#28A745 / error bg-#DC3545 / warning bg-#FFC107 / slide-in top |
| Search | icon + input, 14px, 12px padding | focus ring / results dropdown / no-results message |
