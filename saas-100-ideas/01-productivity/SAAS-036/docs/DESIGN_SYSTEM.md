# Design System — NoteSpace
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** NoteSpace — مساحة الملاحظات
- **Logo concept:** أيقونة مفكرة / notebook + قلم + نص sans-serif ناعم
- **Brand personality:** بسيط، نظيف، إبداعي، مريح، ودود

## Color Palette
- **Primary:** `#0D9488` — تيل (أزرار، رؤوس، روابط، أكرنت)
- **Secondary:** `#2563EB` — أزرق (معلومات، روابط، ترويسات)
- **Accent:** `#8B5CF6` — بنفسجي فاتح (تمييزات Pro، إشارات @، علامات)
- **Neutral:** `#F0FDFA` / `#134E4A` — خلفيات / نصوص أساسية
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#EF4444`

## Typography
- **Headings:** Inter — sizes: 28/24/20/18px (Arabic: Cairo 26/22/18/16px)
- **Body:** Inter — 14px/16px
- **Arabic:** Cairo — دعم 4 أوزان مع RTL

## Spacing
- Base unit: 8px
- Padding: 16/24/32/48px
- Border radius: 8px (cards), 6px (inputs), 4px (tree items)

## Iconography
- Style: Outline 24px stroke 2
- Library: Lucide Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg `#0D9488`, white text, 8px radius | hover: `#0F766E` / active: `#115E59` / disabled: opacity 50% |
| Folder Tree Item | indent 16px per level, icon + name | hover: bg `#CCFBF1` / selected: bg `#99F6E4`, text teal-700 |
| Note Card | bg white, shadow-sm, 8px radius, border `#E2E8F0` | hover: shadow-md, border teal-200 |
| Toolbar Button | icon 20px, 8px padding | hover: bg `#F0FDFA` / active: bg `#CCFBF1` |
| Cursor Indicator | 4px circle | color per user, smooth CSS transition |
| Search Input | bg `#F1F5F9`, 12px padding, icon left | focus: bg white, border teal, ring |
| Conflict Dialog | modal, max-w-lg | open: backdrop blur, diff view left/right |
| Tag Pill | bg `#F0FDFA`, text `#0F766E`, 4px radius | hover: bg `#CCFBF1` |
