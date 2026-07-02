# Design System — WorkPermit
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** Work (عمل) + Permit (تصريح) — تصريح العمل
- **Logo concept:** ختم رسمي + وثيقة + علامة صح، بألوان رسمية
- **Brand personality:** رسمي، موثوق، آمن، منظّم، قانوني

## Color Palette
- **Primary:** `#1A237E` — Navy (trust/authority/professionalism)
- **Secondary:** `#F9A825` — Gold (value/premium/formal)
- **Accent:** `#00838F` — Teal (clarity/peace-of-mind)
- **Neutral:** `#F5F5F5` background, `#212121` text
- **Semantic:** Success `#2E7D32` · Warning `#F57F17` · Error `#C62828`

## Typography
- **Headings:** Noto Naskh Arabic — sizes: 26/22/18/16px
- **Body:** Noto Sans Arabic — 14px
- **Arabic:** Noto Naskh Arabic (headings, formal feel), Noto Sans Arabic (body)

## Spacing
- Base unit: 8px
- Padding: 16/24/32px
- Border radius: 4px (formal, sharp)

## Iconography
- Style: Outline (professional, legal)
- Library: Lucide Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-primary, white text, 4px radius | hover: darken, active, disabled |
| Button Danger | bg-error, white (for delete) | hover: darken |
| Input Field | border 1px #BDBDBD, 4px radius | focus: border-primary, error |
| Card | white, border 1px #E0E0E0, 4px radius | hover: shadow |
| CountdownCard | color-coded | safe (green), warning (yellow), critical (red), expired (grey) |
| DocumentBadge | with expiry | valid, expiring-soon, expired |
| ComplianceRing | circular gauge | 0-100% score |
