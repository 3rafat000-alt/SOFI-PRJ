# Design System — NGOmgt (SAAS-096)
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** NGOmgt — إدارة الجمعيات الخيرية
- **Logo concept:** أيقونة يدين متصاعدتين + قلب باللون الأخضر
- **Brand personality:** دافئ، إنساني، شفاف، موثوق، متفائل

## Color Palette
- **Primary:** `#0D9488` (Teal) — عناصر تفاعلية، أزرار، روابط
- **Secondary:** `#059669` (Green) — نجاح، تأكيد، أيقونات إيجابية
- **Accent:** `#F59E0B` (Amber) — تنبيهات، تحذيرات
- **Neutral:** `#F0FDF4` (Green-tinted white) — خلفيات
- **Text:** `#064E3B` — نص أساسي أخضر داكن
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#DC2626`

## Typography
- **Headings:** `Noto Sans Arabic` — 28/24/20/18/16px
- **Body:** `Noto Sans Arabic` — 14px
- **English:** `Inter` — 14px
- **Arabic:** `Noto Sans Arabic` — دعم كامل

## Spacing
- Base: 4px/8px | Padding: 16/24/32/48px | Radius: 10px (ناعم) · 8px (أزرار)

## Iconography
- Library: Lucide, Style: Outline (ناعم), Sizes: 20/24/32px

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg #0D9488, white, 8px rad | hover #0F766E / active #115E59 |
| Button Success | bg #059669, white | hover #047857 |
| Input | border 1px #A7F3D0, 8px rad | focus #0D9488 shadow |
| Card | bg white, border 1px #D1FAE5, 10px rad | hover border #0D9488 |
| ProgressBar | track #D1FAE5, fill #0D9488 | animated / complete / paused |
| BeneficiaryAvatar | online, offline | default, hover + name tooltip |
