# Design System — MicroFund
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** Micro (صغير) + Fund (صندوق) — صندوق التمويل الصغير
- **Logo concept:** عملة ذهبية تنمو + سهم صاعد، بخط عربي أنيق
- **Brand personality:** موثوق، مهني، آمن، إسلامي، محترم

## Color Palette
- **Primary:** `#F9A825` — Gold (wealth/value/prosperity)
- **Secondary:** `#1A237E` — Navy (trust/security/professional)
- **Accent:** `#00897B` — Teal (growth/stability)
- **Neutral:** `#F5F5F5` background, `#212121` text
- **Semantic:** Success `#2E7D32` · Warning `#F57F17` · Error `#C62828`

## Typography
- **Headings:** Noto Naskh Arabic — sizes: 28/24/20/18px
- **Body:** Noto Sans Arabic — 14px
- **Arabic:** Noto Naskh Arabic (headings for formal feel), Noto Sans Arabic (body)
- **Numbers:** Tabular figures for financial data

## Spacing
- Base unit: 4px
- Padding: 16/24/32px
- Border radius: 4px (sharp, professional)

## Iconography
- Style: Outline (professional, clean)
- Library: Lucide Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-primary, dark text, 4px radius | hover: darken, active: darken, disabled |
| Button Secondary | bg-secondary, white text | hover: lighten |
| Input Field | border 1px #BDBDBD, 4px radius | focus: border-secondary + ring, error: border-error |
| Card | white, border 1px #E0E0E0, 4px radius | hover: border-secondary |
| Table | striped rows, sticky header | sortable columns, row hover |
| Financial Amount | bold, tabular-nums | positive (green), negative (red) |
