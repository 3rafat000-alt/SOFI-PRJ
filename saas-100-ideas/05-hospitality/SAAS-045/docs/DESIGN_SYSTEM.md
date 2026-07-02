# Design System — KioskPro
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name:** KioskPro — أكشاك احترافية
- **Logo concept:** أيقونة كشك (شاشة لمس) مع علامة صح
- **Brand personality:** ودود، دافئ، سريع، بسيط، مرحّب

## Color Palette
- **Primary:** `#EA580C` — برتقالي دافئ للأزرار والعناوين
- **Secondary:** `#D97706` — كهرماني للعناصر الثانوية
- **Accent:** `#16A34A` — أخضر للحالات الناجحة والمؤكدة
- **Neutral:** `#FFF7ED` خلفية دافئة، `#431407` نص داكن، `#9A3412` نص ثانوي
- **Semantic:** Success `#16A34A` · Warning `#F59E0B` · Error `#DC2626`

## Typography
- **Headings:** Hanken Grotesk — sizes: 28/24/20/18px (kiosk needs large)
- **Body:** Inter — 18px / 1.5 (kiosk minimum for readability)
- **Arabic:** Noto Sans Arabic — for Arabic kiosk interface
- **Display:** Clash Display — for order numbers and status (very large)

## Spacing
- Base unit: 8px (kiosk needs larger spacing)
- Padding: 16/24/32/48px
- Border radius: 16px (cards), 12px (buttons)
- Kiosk padding: 24px minimum for touch comfort

## Iconography
- Style: Filled (better visibility on screens)
- Library: Phosphor Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| KioskButton | bg-#EA580C, white text, 16px radius, 20px font, 56px height | hover: #C2410C / active: #9A3412 / disabled: opacity 0.4 |
| CategoryCard | 80px height, image cover, overlay text | hover: scale(1.02) / active: scale(0.98) |
| ItemCard | border 2px #FED7AA, 16px padding, 12px radius | sold-out: opacity 0.5 + badge / selected: border #EA580C |
| ModifierChip | border 1px #D1D5DB, 12px radius, 20px font | selected: bg #EA580C + white text / sold-out: strikethrough |
| QuantityStepper | - [number] +, large touch zones | min/max state, 48px buttons |
| OrderNumber | font 48px, bold, center | pulse animation when ready |
| StatusTimeline | colored dots (grey/amber/green) | active dot pulses |
