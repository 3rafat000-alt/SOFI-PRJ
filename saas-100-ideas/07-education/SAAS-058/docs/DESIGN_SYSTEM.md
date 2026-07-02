# Design System — TutorMatch
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** Tutor (مدرس) + Match (تطابق) — تطابق المدرسين
- **Logo concept:** قبعة تخرج + علامة تطابق، بألوان أكاديمية
- **Brand personality:** أكاديمي، حديث، موثوق، شبابي، ملهم

## Color Palette
- **Primary:** `#7B1FA2` — Purple (wisdom/creativity/education)
- **Secondary:** `#283593` — Indigo (trust/depth/knowledge)
- **Accent:** `#00ACC1` — Cyan (energy/innovation/tech)
- **Neutral:** `#F5F5F5` background, `#212121` text
- **Semantic:** Success `#2E7D32` · Warning `#F57F17` · Error `#D32F2F`

## Typography
- **Headings:** Noto Sans Arabic Bold — sizes: 28/24/20/18px
- **Body:** Noto Sans Arabic — 14px
- **Arabic:** Noto Sans Arabic

## Spacing
- Base unit: 8px
- Padding: 16/24/32px
- Border radius: 12px (cards), 24px (buttons)

## Iconography
- Style: Outline (clean, academic)
- Library: Lucide Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-primary, white text, 24px radius | hover: darken, active, disabled |
| Button Book | bg-accent, white, pulse (to encourage booking) | hover, disabled |
| Input Field | border 1px #BDBDBD, 12px radius | focus: border-primary, error |
| Card | white, shadow, 12px radius, 16px padding | hover: shadow-lg |
| RatingStars | gold, interactive | 0-5 stars, half-star precision |
| Avatar | circle, with verification badge | verified (green), not-verified (grey) |
| AvailabilitySlot | selectable grid | free (white), booked (grey), selected (primary) |
