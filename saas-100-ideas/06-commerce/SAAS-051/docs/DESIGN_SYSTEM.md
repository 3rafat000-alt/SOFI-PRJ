# Design System — SouqSync
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** سوق (Souq = Market) + Sync (مزامنة) — سوق متزامن
- **Logo concept:** أيقونة عربة تسوق + خطوط متصلة (رمز المزامنة)، بخط عربي حديث
- **Brand personality:** موثوق، حديث، تجاري، دافئ، بسيط

## Color Palette
- **Primary:** `#2E7D32` — Green (commerce/trust/growth)
- **Secondary:** `#F57C00` — Orange (energy/warmth/action)
- **Accent:** `#FFB300` — Amber (highlights/CTA)
- **Neutral:** `#F5F5F5` background, `#424242` text
- **Semantic:** Success `#43A047` · Warning `#FFA000` · Error `#D32F2F`

## Typography
- **Headings:** Noto Sans Arabic — sizes: 28/24/20/18px
- **Body:** Noto Sans Arabic — 14px (16px for mobile)
- **Arabic:** Noto Sans Arabic (variable weight 400-700)
- **Direction:** RTL default, LTR for numbers/English

## Spacing
- Base unit: 8px
- Padding: 16/24/32/48px
- Border radius: 8px (cards), 20px (buttons), 4px (inputs)

## Iconography
- Style: Outline (consistent stroke 2px)
- Library: Lucide Icons (RTL-aware)

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-primary, white text, 8px radius | hover: darken 10%, active: darken 20%, disabled: opacity 0.5, loading: spinner |
| Button Secondary | border-primary, text-primary, bg-white | hover: bg-primary-50, disabled: opacity 0.5 |
| Input Field | border 1px #E0E0E0, 12px padding, 8px radius | focus: border-primary + ring 2px, error: border-error, disabled: bg-gray-100 |
| Card | bg-white, shadow-sm, 8px radius | hover: shadow-md, selected: border-primary |
| Badge | pill shape, 12px padding | default, success, warning, error |
