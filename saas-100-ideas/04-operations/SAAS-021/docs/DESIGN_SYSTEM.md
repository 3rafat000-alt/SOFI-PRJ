# Design System — ParkingIQ
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** ParkingIQ — ذكاء مواقف السيارات
- **Logo concept:** حرف P أزرق داخل شكل مربع بزوايا دائرية يمثل موقف سيارة أيقونة سيارة صغيرة بجانبه
- **Brand personality:** ذكي، موثوق، سريع، احترافي، عصري

## Color Palette
- **Primary:** `#1565C0` — أزرق صناعي (خلفيات، أزرار رئيسية، روابط)
- **Secondary:** `#F9A825` — أصفر كهرماني (تنبيهات، إشعارات، أزرار ثانوية)
- **Accent:** `#00897B` — أزرق مخضر (نجاح، أيقونات، تمايز)
- **Neutral:** `#ECEFF1` (خلفيات) `#90A4AE` (نص ثانوي) `#37474F` (نص أساسي)
- **Semantic:** Success `#43A047` · Warning `#FDD835` · Error `#E53935`

## Typography
- **Headings:** Inter — sizes: 24/20/18/16px (700 weight)
- **Body:** Inter — 14px (400 weight)
- **Arabic:** Noto Sans Arabic — matching weights, RTL support
- **Monospace:** JetBrains Mono — for codes, times, parking spot IDs

## Spacing
- Base unit: 8px
- Padding: 16/24/32px
- Border radius: 8px (cards, buttons), 4px (inputs)
- Gap grid: 16px between cards

## Iconography
- Style: Outline
- Library: Lucide
- Key icons: MapPin, Car, Clock, CreditCard, QrCode, Bell

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-primary, white text, 8px radius, 48px height, 16px padding | hover: darken 10%, active: scale(0.97), disabled: opacity 0.5, loading: spinner |
| Button Secondary | border primary 1px, primary text, 8px radius | hover: bg primary 10%, active: scale(0.97) |
| Input Field | border neutral 1px, 12px padding, 8px radius | focus: border primary + ring primary 10%, error: border error + error text, disabled: bg gray-100 |
| Card | bg white, shadow sm, 8px radius, 16px padding | hover: shadow md, selected: border primary 2px |
| Map Pin | 32px circle with icon | available: green, reserved: yellow, occupied: red |
| Bottom Nav | height 64px, icon + label | active: primary color, inactive: gray-400 |
| Status Badge | 6px dot + label | available/occupied/reserved/maintenance |
