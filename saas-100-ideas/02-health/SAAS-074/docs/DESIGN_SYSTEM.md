# Design System — NurseryPro
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** NurseryPro — حضانتي المحترفة
- **Logo concept:** Heart + child face outline + play icon — soft rounded shapes
- **Brand personality:** Warm, Caring, Playful, Trustworthy, Nurturing

## Color Palette
- **Primary:** `#0D9488` — Soft Teal (headers, buttons, main identity)
- **Secondary:** `#DB2777` — Pink (accents, highlights, parent app badges)
- **Accent:** `#F472B6` — Light Pink (hover, secondary elements)
- **Neutral:** `#F0FDFA` — Soft mint (backgrounds), `#374151` — dark grey (text), `#9CA3AF` — grey (labels)
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#EF4444` · Info `#3B82F6`

## Typography
- **Headings:** Vazirmatn (Google Font) — rounded Arabic — sizes: 26/22/18/16px
- **Body:** Vazirmatn — 14px (parent app), 13px (dashboard)
- **Arabic:** Vazirmatn — warm rounded Arabic script
- **Display:** Playfair Display (English logo only)

## Spacing
- Base unit: 4px/8px
- Padding: 16/24/32px (spacious, child-friendly)
- Border radius: 16px (cards, child-friendly), 8px (buttons), 24px (modals)
- Max content width: 1000px

## Iconography
- Style: Filled, rounded, 28px default (larger for children's theme)
- Library: Lucide Icons (heart, baby, smile, apple, bed)

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-teal-600 #0D9488, white text, 8px radius, rounded | hover: #0F766E / active: #115E59 / disabled |
| Button Icon | 48px circle, pink #DB2777 bg | hover: #BE185D |
| MoodSelector | 5 emoji faces, 40px each | selected: scale 1.2 + bg pink |
| TimelineCard | 16px radius, border-l-4 coloured | meal=green / nap=blue / activity=orange |
| PhotoGrid | 2-col, 3-col masonry | tap: full-screen gallery |
