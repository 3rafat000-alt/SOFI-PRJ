# Design System — RideShare
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** Ride (رحلة) + Share (مشاركة) — مشاركة الرحلات
- **Logo concept:** سيارة + أيقونة مشاركة + طريق، بألوان حيوية
- **Brand personality:** حيوي، شبابي، موثوق، عصري، اجتماعي

## Color Palette
- **Primary:** `#1565C0` — Blue (trust/safety/reliability)
- **Secondary:** `#EF6C00` — Orange (energy/action/enthusiasm)
- **Accent:** `#00BFA5` — Teal (growth/eco-friendly)
- **Neutral:** `#FAFAFA` background, `#212121` text
- **Semantic:** Success `#2E7D32` · Warning `#F57F17` · Error `#D32F2F` · SOS `#C62828`

## Typography
- **Headings:** Noto Sans Arabic Bold — sizes: 28/24/20/18px
- **Body:** Noto Sans Arabic — 14px
- **Arabic:** Noto Sans Arabic
- **Numbers:** Tabular figures for prices/distances

## Spacing
- Base unit: 8px
- Padding: 16/24/32px
- Border radius: 12px (cards), 24px (buttons), 8px (inputs)

## Iconography
- Style: Filled (bold, clear for drivers on the road)
- Library: Lucide Icons (custom travel icons)

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-primary, white, 24px radius (pill) | hover: darken, active: darken, disabled |
| Button SOS | bg-error, white, pulse animation | triggered: shake + alert |
| Input Field | bg-white, border 1px, 12px radius | focus: border-primary, error: border-error |
| Card | white, shadow, 12px radius | hover: shadow-lg |
| Driver Avatar | circle, with green badge | verified: badge, not-verified: grey |
| Map Marker | custom pin | rider (blue), driver (green) |
