# Design System — OlivePress
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** Olive (زيتون) + Press (عصر) — معصرة الزيتون
- **Logo concept:** غصن زيتون أخضر + قطرة زيت ذهبية، بخط عربي تراثي
- **Brand personality:** طبيعي، تقليدي، موثوق، ترابي، أصيل

## Color Palette
- **Primary:** `#388E3C` — Green (nature/olive/growth)
- **Secondary:** `#795548` — Brown (earth/tradition/soil)
- **Accent:** `#F9A825` — Gold (oil quality/value)
- **Neutral:** `#F1F8E9` background, `#3E2723` text
- **Semantic:** Success `#2E7D32` · Warning `#F57F17` · Error `#C62828`

## Typography
- **Headings:** Noto Kufi Arabic — sizes: 28/24/20/18px
- **Body:** Noto Sans Arabic — 14px (16px mobile)
- **Arabic:** Noto Kufi Arabic (headings), Noto Sans Arabic (body)
- **Direction:** RTL default

## Spacing
- Base unit: 8px
- Padding: 16/24/32px
- Border radius: 8px (cards), 4px (inputs)

## Iconography
- Style: Filled (for low-literacy users, more intuitive)
- Library: Lucide Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-primary, white text, 8px radius | hover: darken, active: darken, disabled: opacity 0.5 |
| Button Secondary | border-secondary, text-secondary | hover: bg-secondary-50 |
| Input Field | border 1px #A5D6A7, 12px padding | focus: border-primary, error: border-error |
| Card | bg-white, border 1px #E8F5E9, 8px radius | hover: shadow |
| Badge | pill, small | success, warning, error |
| ProgressRing | circular stroke | 0-100% with label |
