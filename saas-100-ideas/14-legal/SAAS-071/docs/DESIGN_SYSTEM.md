# Design System — LegalConsult
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** LegalConsult — استشارات قانونية
- **Logo concept:** Scale of justice (ميزان) icon + sans-serif wordmark
- **Brand personality:** Trustworthy, Professional, Authoritative, Modern, Dependable

## Color Palette
- **Primary:** `#1E3A5F` — Navy (headers, buttons, primary actions)
- **Secondary:** `#C8912E` — Gold (accents, highlights, premium badges)
- **Accent:** `#E5B94B` — Light Gold (hover states, secondary elements)
- **Neutral:** `#F8F9FA` — Light grey (backgrounds), `#6B7280` — grey (body text), `#1F2937` — dark (headings)
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#EF4444`

## Typography
- **Headings:** Noto Sans Arabic (Google Font) — sizes: 28/24/20/18px
- **Body:** Noto Sans Arabic — 14px regular
- **Arabic:** Noto Sans Arabic — full Arabic + RTL support
- **Monospace:** JetBrains Mono — for legal document numbers, codes

## Spacing
- Base unit: 4px
- Padding: 16/24/32/48px
- Border radius: 8px (buttons, cards), 4px (inputs), 12px (modals)
- Max content width: 1200px

## Iconography
- Style: Outline, 24px default
- Library: Lucide Icons (accessible, consistent stroke)

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-primary #1E3A5F, white text, 8px radius, 14px font | hover: #152D4A / active: #0F1F33 / disabled: opacity 50% / loading: spinner |
| Button Secondary | border 2px #1E3A5F, navy text transparent bg | hover: bg #1E3A5F 5% / active: 10% |
| Input Field | border 1px #D0D5DD, 12px padding, 14px text | focus: border #1E3A5F + ring 3px / error: border #EF4444 / disabled: bg #F3F4F6 |
| Card | bg white, border 1px #E5E7EB, 12px radius, shadow-sm | hover: shadow-md |
| Tag | Pill shape, 20px height, 8px padding, 12px font | selected: bg #1E3A5F text white / unselected: bg #F3F4F6 |
| Toast | Success/Warning/Error with icon, 12px radius | show: slide from top 3s / dismiss: slide out |
