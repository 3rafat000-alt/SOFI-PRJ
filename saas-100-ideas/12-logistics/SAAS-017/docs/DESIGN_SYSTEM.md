# Design System — CargoNet
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** "CargoNet" — شبكة الشحن الذكية التي تربط الشاحنات بالشحنات
- **Logo concept:** أيقونة شاحنة في مسار دائري (ترمز لشبكة لوجستية)، اسم CargoNet بخط sans-serif
- **Brand personality:** سريع · موثوق · تقني · ديناميكي · فعال

## Color Palette (Logistics: Blue/Orange)
- **Primary:** `#1565C0` — أزرق ثقة (headers, nav, primary buttons)
- **Secondary:** `#EF6C00` — برتقالي طاقة (highlights, badges, CTAs)
- **Accent:** `#42A5F5` — أزرق فاتح (hover states, links, secondary elements)
- **Neutral:** `#F5F5F5` — خلفية، `#E0E0E0` حدود، `#616161` نصوص ثانوية
- **Semantic:** Success `#2E7D32` · Warning `#F9A825` · Error `#C62828` · Info `#1565C0`

## Typography
- **Headings:** Inter (English) / Noto Sans Arabic (Arabic) — sizes: 28/24/20/18/16px
- **Body:** Inter / Noto Sans Arabic — 14px regular
- **Small/Meta:** 12px
- **Line height:** Headings 1.15, Body 1.6

## Spacing
- Base unit: 8px
- Container max-width: 1280px
- Padding: 16/24/32/48px
- Border radius: 8px (cards/buttons), 4px (inputs), 12px (modals)
- Gap: 16px / 24px

## Iconography
- Style: Filled outline, 1.5px stroke
- Library: Lucide Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-#1565C0, white, 8px radius, 14px/600 | hover #0D47A1 / active #0A347D / disabled 50% |
| Button Secondary | border 2px #EF6C00, text #EF6C00 | hover bg-#EF6C0010 / active bg-#EF6C0020 |
| LiveMap | full height, zoom controls | loading skeleton / interactive / error |
| ShipmentCard | left border status, time elapsed | normal/hover / dragging |
| DriverCard | list item, distance badge | available green / busy orange / offline gray |
| VehicleCard | health bar horizontal | healthy green / due-soon yellow / overdue red |
| ProofCard | photo + signature preview | captured / uploaded / verified badge |
| NavigationCard | big tap area, dist + addr | en-route blue / arrived green / completed gray |
| DriverContactBtn | phone, whatsapp | normal call / WA icon |
| StatusTimeline | vertical dot + line | completed green / active blue / pending gray |
| Input Field | border 1px #E0E0E0, 12px pad, 6px rad | focus border-#1565C0 / error border-#C62828 |
| Modal | bg-white, 12px radius, backdrop | open/close 200ms |
