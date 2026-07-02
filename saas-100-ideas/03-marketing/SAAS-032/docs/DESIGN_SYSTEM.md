# Design System — LeadFunnel
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** LeadFunnel — قمع العملاء المحتملين
- **Logo concept:** أيقونة قمع/ funnel بألوان متدرجة + نص sans-serif modern
- **Brand personality:** جريء، ديناميكي، موثوق، محفز، احترافي

## Color Palette
- **Primary:** `#7C3AED` — بنفسجي (أزرار، رؤوس، روابط، ترويسة)
- **Secondary:** `#F59E0B` — كهرماني (شعارات المبيعات، تحذيرات، جوائز)
- **Accent:** `#EC4899` — وردي (تمييزات Pro، شارات VIP، إشعارات)
- **Neutral:** `#F8FAFC` / `#1E293B` — خلفيات / نصوص أساسية
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#EF4444`

## Typography
- **Headings:** Inter — sizes: 28/24/20/18px (Arabic: Cairo 26/22/18/16px)
- **Body:** Inter — 14px/16px
- **Arabic:** Cairo — دعم كامل مع 4 أوزان (400/500/600/700)

## Spacing
- Base unit: 8px
- Padding: 16/24/32/48px
- Border radius: 8px (cards), 12px (kanban columns), 4px (badges)

## Iconography
- Style: Outline 24px stroke 2
- Library: Lucide Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg `#7C3AED`, white text, 8px radius, 14px font | hover: `#6D28D9` / active: `#5B21B6` / disabled: opacity 50% |
| Button Success (Won) | bg `#10B981`, white text | hover: `#059669` / active: `#047857` |
| Button Danger (Lost) | bg `#EF4444`, white text | hover: `#DC2626` / active: `#B91C1C` |
| Kanban Column | bg `#F1F5F9`, dotted separator, 12px radius | drop-hover: bg `#EDE9FE` + border purple |
| Lead Card | bg white, shadow-sm, 8px radius, border `#E2E8F0` | hover: shadow-md, border purple-200 |
| Activity Item | icon + text + timestamp | hover: bg `#F8FAFC` |
| Score Badge | pill, 20px height | hot: red bg / warm: orange bg / cold: gray bg |
| Drop Zone | dashed border 2px `#CBD5E1`, 12px radius | drag-over: border purple-400, bg purple-50 |
