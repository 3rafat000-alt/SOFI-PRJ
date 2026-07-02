# Design System — HotelEase
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** "HotelEase" — سهولة إدارة الفنادق بلمسة عربية دافئة
- **Logo concept:** أيقونة مبنى فندق مع هلال ذهبي، اسم HotelEase بخط أنيق
- **Brand personality:** فاخر · دافئ · مضياف · موثوق · بسيط

## Color Palette (Hospitality: Gold/Warm Brown)
- **Primary:** `#B8860B` — ذهبي داكن (headers, buttons, key elements)
- **Secondary:** `#8B4513` — بني دافئ (secondary text, icons, backgrounds)
- **Accent:** `#D4A843` — ذهبي فاتح (hover states, highlights, stars)
- **Neutral:** `#FFF8E7` — كريمي فاتح للخلفيات، `#E8DCC4` للحدود، `#5C4033` نصوص ثانوية
- **Semantic:** Success `#2E7D32` · Warning `#F9A825` · Error `#C62828` · Info `#1565C0`

## Typography
- **Headings:** Playfair Display (English) / Noto Sans Arabic (Arabic) — sizes: 28/24/20/18/16px
- **Body:** Playfair Display / Noto Sans Arabic — 14px regular
- **Small/Meta:** 12px
- **Line height:** Headings 1.15, Body 1.6

## Spacing
- Base unit: 8px
- Container max-width: 1280px
- Padding: 16/24/32/48px
- Border radius: 12px (cards/buttons), 6px (inputs), 16px (modals)
- Gap: 16px / 24px

## Iconography
- Style: Outline, 1.5px stroke
- Library: Lucide Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-#B8860B, white text, 12px radius, 14px/600 | hover #9A7209 / active #7A5B07 / disabled opacity 50% |
| Button Secondary | border 2px #8B4513, text #8B4513 | hover bg-#8B451310 / active bg-#8B451320 |
| RoomCard | bg-white, 12px radius, shadow, status border | hover shadow-lg / selected border-#B8860B |
| RoomGridCell | 64x64px, rounded 8px | available green / occupied red / cleaning yellow / maintenance gray |
| Input Field | border 1px #E8DCC4, 14px padding, 8px radius | focus border-#B8860B / error border-#C62828 |
| GuestCard | avatar + info + status | normal / expanded folio view |
| InvoicePreview | white bg, 12px radius, itemized | editable rows with add/remove |
| IDScanner | camera viewfinder overlay | scanning / success / error |
| Badge | pill shape | room status / booking source |
| Modal | bg-white, 16px radius, backdrop | open/close 250ms |
