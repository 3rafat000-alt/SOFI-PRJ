# Design System — TutorSpace
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** "TutorSpace" — مساحة التعلم الرقمية التي تربط المعلمين بالطلاب
- **Logo concept:** أيقونة قبعة تخرج مع خطوط اتصال (شبكة تعليمية)، اسم TutorSpace بخط sans-serif ودود
- **Brand personality:** تعليمي · ودود · ملهم · مبتكر · محفز

## Color Palette (Education: Purple/Blue)
- **Primary:** `#5E35B1` — بنفسجي عميق (headers, buttons, nav)
- **Secondary:** `#1E88E5` — أزرق تعليمي (links, secondary CTAs, highlights)
- **Accent:** `#7E57C2` — بنفسجي فاتح (hover states, active elements)
- **Neutral:** `#FAFAFA` — خلفية، `#E0E0E0` حدود، `#616161` نصوص ثانوية
- **Semantic:** Success `#43A047` · Warning `#FDD835` · Error `#E53935` · Info `#1E88E5`

## Typography
- **Headings:** Quicksand (English) / Noto Sans Arabic (Arabic) — sizes: 28/24/20/18/16px
- **Body:** Quicksand / Noto Sans Arabic — 14px regular
- **Small/Meta:** 12px
- **Line height:** Headings 1.15, Body 1.6

## Spacing
- Base unit: 8px
- Container max-width: 1280px
- Padding: 16/24/32/48px
- Border radius: 12px (cards/buttons), 6px (inputs), 16px (modals)
- Gap: 16px / 24px

## Iconography
- Style: Filled outline, 1.5px stroke
- Library: Lucide Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-#5E35B1, white, 12px radius, 14px/600 | hover #4527A0 / active #38248A / disabled 50% |
| Button Secondary | border 2px #1E88E5, text #1E88E5 | hover bg-#1E88E510 / active bg-#1E88E520 |
| WeeklyCalendar | grid 7×12 (days × slots) | 30min slots, conflict red overlay, booked purple |
| SessionCard | left border status, time | normal/hover / rescheduling |
| VideoTile | large 70% / small 30% (PiP) | connecting / video-on / audio-only / muted border icon |
| Whiteboard | canvas 100% | draw mode / eraser / shape / text tool |
| ProgressChart | radar, 6 skills | data lines per assessment date |
| StudentCard | avatar + name + subject + next | normal / expanded with history |
| InvoiceLineItem | editable row with trash | normal / edited indicator |
| PaymentBadge | pill 12px/600 | paid #43A047 / pending #FDD835 text #7A5E00 / overdue #E53935 |
| AssessmentForm | quiz builder | draft / published / closed |
| ReportCard | subject, score, notes | available / generating spinner |
| Modal | bg-white, 16px radius, backdrop 50% | open/close 250ms |
