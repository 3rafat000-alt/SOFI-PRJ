# Design System — URLShort Pro
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** URLShort Pro — تقصير الروابط الاحترافي
- **Logo concept:** أيقونة رابط / link + أيقونة قصر / scissors + نص sans-serif جريء
- **Brand personality:** سريع، ذكي، احترافي، حديث، جريء

## Color Palette
- **Primary:** `#7C3AED` — بنفسجي (أزرار، رؤوس، روابط)
- **Secondary:** `#EC4899` — وردي (QR code، شارات، تمييزات)
- **Accent:** `#06B6D4` — سماوي (روابط مختصرة، معلومات، تلميحات)
- **Neutral:** `#F5F3FF` / `#1E1B4B` — خلفيات / نصوص أساسية
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#EF4444`

## Typography
- **Headings:** Inter — sizes: 28/24/20/18px (Arabic: Cairo 26/22/18/16px)
- **Body:** Inter — 14px/16px
- **Arabic:** Cairo — دعم 4 أوزان (400/500/600/700)

## Spacing
- Base unit: 8px
- Padding: 16/24/32/48px
- Border radius: 8px (cards, inputs), 12px (QR card), 9999px (badges)

## Iconography
- Style: Outline 24px stroke 2
- Library: Lucide Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg `#7C3AED`, white text, 8px radius | hover: `#6D28D9` / active: `#5B21B6` / disabled: opacity 50% |
| Button Copy | bg white, border `#7C3AED`, purple text | hover: bg `#F5F3FF` / copied: bg `#10B981` white text |
| URL Input | bg white, border `#D1D5DB`, 12px padding, 8px radius | focus: border purple + ring / invalid: border red |
| Slug Input | inline-flex with domain prefix | checking: spinner / available: green check / taken: red X + suggestions |
| Result Card | bg `#F5F3FF`, border purple-200, 12px radius | copy hover: bg purple-50 |
| QR Preview | 200px square, centered | loading: skeleton / ready: SVG / error: retry button |
| Chart Card | bg white, shadow-sm, 12px radius, overflow hidden | loading: skeleton chart |
| Domain Row | bg white, border-bottom | pending: amber dot / verified: green dot / failed: red dot |
