# Design System — WikiBase
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** WikiBase — قاعدة المعرفة
- **Logo concept:** أيقونة كتاب مفتوح / open book + أيقونة قاعدة / database + نص sans-serif نظيف
- **Brand personality:** منظم، موثوق، واضح، حكيم، جماعي

## Color Palette
- **Primary:** `#2563EB` — أزرق (أزرار، رؤوس، روابط)
- **Secondary:** `#7C3AED` — بنفسجي (تمييزات Pro، شارات، إشارات @)
- **Accent:** `#059669` — أخضر (حفظ، نشر، تأكيدات)
- **Neutral:** `#EFF6FF` / `#172554` — خلفيات / نصوص أساسية
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#EF4444`

## Typography
- **Headings:** Inter — sizes: 28/24/20/18px (Arabic: Cairo 26/22/18/16px)
- **Body:** Inter — 14px/16px
- **Arabic:** Cairo — دعم 5 أوزان (300/400/500/600/700)

## Spacing
- Base unit: 8px
- Padding: 16/24/32/48px
- Border radius: 8px (modals), 6px (inputs), 4px (tree items)

## Iconography
- Style: Outline 24px stroke 2
- Library: Lucide Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg `#2563EB`, white text, 8px radius | hover: `#1D4ED8` / active: `#1E40AF` / disabled: opacity 50% |
| Tree Node | indent 20px per level, 32px height | hover: bg `#DBEAFE` / selected: bg `#BFDBFE`, text blue-700 |
| Slash Command Item | icon + name + shortcut | hover: bg `#EFF6FF` / selected: bg `#DBEAFE` |
| Search Input | bg `#F1F5F9`, 16px padding, 16px font | focus: bg white, border blue, ring |
| Result Card | bg white, shadow-sm, 8px radius | hover: shadow-md, border blue-200 |
| Version Row | bg white, border-bottom | hover: bg `#F8FAFC` / current: bg `#EFF6FF` |
| Diff Addition | bg `#D1FAE5`, text `#065F46` | static | + line content |
| Diff Deletion | bg `#FEE2E2`, text `#991B1B` | static | - line content |
| Breadcrumb | text `#64748B` > text `#0F172A` | link hover: text blue-600 | clickable segments |
