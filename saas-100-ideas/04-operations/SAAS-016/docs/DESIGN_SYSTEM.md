# Design System — BuildTrack
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** "BuildTrack" — تتبع كل شيء في مشاريع البناء من الألف إلى الياء
- **Logo concept:** أيقونة رافعة بناء مع خط بياني صاعد، اسم BuildTrack بخط sans-serif متين
- **Brand personality:** متين · عملي · موثوق · مباشر · ريادي

## Color Palette (Operations: Amber/Dark Gray)
- **Primary:** `#E65100` — برتقالي داكن (headers, CTA buttons, active elements)
- **Secondary:** `#37474F` — رمادي غامق (nav, secondary text, icons)
- **Accent:** `#FF8F00` — برتقالي فاتح (hover, highlights, badges)
- **Neutral:** `#F5F5F5` — خلفية، `#E0E0E0` حدود، `#757575` نصوص ثانوية
- **Semantic:** Success `#2E7D32` · Warning `#F9A825` · Error `#C62828` · Info `#1565C0`

## Typography
- **Headings:** Roboto Condensed (English) / Noto Sans Arabic (Arabic) — sizes: 28/24/20/18/16px
- **Body:** Roboto / Noto Sans Arabic — 14px regular
- **Small/Meta:** 12px
- **Line height:** Headings 1.15, Body 1.6

## Spacing
- Base unit: 8px
- Container max-width: 1280px
- Padding: 16/24/32/48px
- Border radius: 6px (cards/buttons), 4px (inputs), 12px (modals)
- Gap: 16px / 24px

## Iconography
- Style: Filled outline, 1.5px stroke
- Library: Lucide Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-#E65100, white text, 6px radius, 14px/600 | hover #BF360C / active #A52714 / disabled 50% |
| Button Secondary | border 2px #37474F, text #37474F, 6px radius | hover bg-#37474F10 / active bg-#37474F20 |
| ProjectCard | bg-white, border-left 4px status, 6px radius | normal/hover shadow / selected border-#E65100 |
| ProgressGauge | ring chart, 80px diameter | green > 75% / amber 50-75% / red < 50% |
| Input Field | border 1px #E0E0E0, 12px padding, 4px radius | focus border-#E65100 / error border-#C62828 |
| MaterialCard | border-left stock color | normal / low alert red / empty |
| DailyLogCard | photo preview top, text below | draft / submitted with timestamp |
| WorkerCard | photo 48px, name, role, attend badge | present green / absent red / leave yellow |
| AttendanceCell | 44px square | present #2E7D32 / absent #C62828 / leave #F9A825 |
| PurchaseOrder | card with items table | draft / pending approval / ordered / received |
| PhotoTimeline | horizontal scroll each | before label / after label / side-by-side |
| Modal | bg-white, 12px radius, backdrop | open/close 200ms |
