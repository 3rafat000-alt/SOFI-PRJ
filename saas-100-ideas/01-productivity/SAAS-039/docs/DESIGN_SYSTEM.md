# Design System — FormBuilder
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** FormBuilder — بناء النماذج
- **Logo concept:** أيقونة نموذج / form checkbox + أيقونة بناء / builder + نص sans-serif حديث
- **Brand personality:** ذكي، مرن، عصري، بسيط، مبتكر

## Color Palette
- **Primary:** `#0D9488` — تيل (أزرار، رؤوس، روابط)
- **Secondary:** `#6366F1` — نيلي (شروط منطقية، ميزات متقدمة)
- **Accent:** `#F59E0B` — كهرماني (تحذيرات، تلميحات)
- **Neutral:** `#F0FDFA` / `#134E4A` — خلفيات / نصوص أساسية
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#EF4444`

## Typography
- **Headings:** Inter — sizes: 28/24/20/18px (Arabic: Cairo 26/22/18/16px)
- **Body:** Inter — 14px/16px
- **Arabic:** Cairo — دعم 4 أوزان (400/500/600/700)

## Spacing
- Base unit: 8px
- Padding: 16/24/32/48px
- Border radius: 8px (cards, inputs), 4px (element palette)

## Iconography
- Style: Outline 24px stroke 2
- Library: Lucide Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg `#0D9488`, white text, 8px radius | hover: `#0F766E` / active: `#115E59` / disabled: opacity 50% |
| Element Palette Item | bg white, border, 8px radius, icon + label | hover: bg `#F0FDFA`, border teal / dragging: opacity 50% |
| Canvas Element | border `#E2E8F0`, 8px radius, 12px padding | hover: bg `#F8FAFC` / selected: border teal-500 / focus: ring |
| Rule Row | bg white, border `#E2E8F0`, 8px radius | conflict: bg `#FEF2F2` border red |
| Condition Connector | OR (bg `#F0FDFA`) / AND (bg `#EEF2FF`) | static | badge-style between rule rows |
| Stats Card | bg white, shadow-sm, 8px radius, border-left 4px | teal: total / indigo: today |
| Response Row | bg white, border-bottom | hover: bg `#F8FAFC` |
| Progress Bar | bg `#E2E8F0`, fill `#0D9488` | 0-100% | animated fill transition |
