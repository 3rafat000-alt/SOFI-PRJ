# Design System — MailCraft
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** MailCraft — حرفة البريد
- **Logo concept:** أيقونة ظرف / envelope + ريشة / feather + نص sans-serif كلاسيكي
- **Brand personality:** احترافي، أنيق، موثوق، إبداعي، واضح

## Color Palette
- **Primary:** `#DC2626` — أحمر (أزرار، رؤوس، شعارات مهمة)
- **Secondary:** `#2563EB` — أزرق (روابط، معلومات، ترويسات)
- **Accent:** `#F59E0B` — كهرماني (تحذيرات، أوسمة، تمييزات)
- **Neutral:** `#FEF2F2` / `#1F2937` — خلفيات / نصوص أساسية
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#EF4444`

## Typography
- **Headings:** Inter — sizes: 28/24/20/18px (Arabic: Cairo 26/22/18/16px)
- **Body:** Inter — 14px/16px
- **Arabic:** Cairo — دعم 5 أوزان (300/400/500/600/700)

## Spacing
- Base unit: 8px
- Padding: 16/24/32/48px
- Border radius: 8px (cards, inputs), 4px (template thumbnails)

## Iconography
- Style: Outline 24px stroke 2
- Library: Lucide Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg `#DC2626`, white text, 8px radius | hover: `#B91C1C` / active: `#991B1B` / disabled: opacity 50% |
| Button Secondary | bg white, border `#DC2626`, red text | hover: bg `#FEF2F2` |
| Drag Component | bg white, border `#E5E7EB`, 8px radius | drag: shadow-lg, placeholder: dashed |
| Email Canvas | bg `#F9FAFB`, 600px max-width | editing: border blue / preview: shadow-lg |
| Template Card | bg white, shadow-sm, 8px radius | hover: shadow-md, transform scale(1.02) |
| Live Counter | large text + icon | counting: pulse / paused: static |
| Subscriber Row | bg white, border-bottom | hover: bg `#F9FAFB` / selected: bg `#DBEAFE` |
| Event Item | icon + email + event + time | new: bg `#FEF2F2` |
