# Design System — FitZone Pro
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **اسم العلامة:** FitZone Pro — فيت زون برو
- **مفهوم الشعار:** أيقونة عضلات (ذراع) على شكل نبضة قلب خضراء
- **شخصية العلامة:** نشيطة، صحية، محفزة، عصرية، قوية
- **الجمهور:** نوادي رياضية، مدربون، أعضاء

## Color Palette
- **Primary:** `#059669` — أخضر (أزرار، شعار، حالات نشطة)
- **Secondary:** `#0284C7` — أزرق (روابط، معلومات)
- **Accent:** `#F97316` — برتقالي (تحديات، إنجازات)
- **Neutral-100:** `#F0FDF4` — خلفية خضراء فاتحة
- **Neutral-200:** `#DCFCE7` — حدود
- **Neutral-700:** `#166534` — نص أساسي
- **Neutral-900:** `#022C22` — عناوين
- **Semantic:** Success `#22C55E` · Warning `#F59E0B` · Error `#EF4444`
- **Gradient:** `linear-gradient(135deg, #059669, #0284C7)`

## Typography
- **Headings (English):** Bebas Neue — 32/28/24/20px (uppercase, bold)
- **Body (English):** Inter — 14px
- **Arabic:** Noto Sans Arabic — bold headings
- **Workout numbers:** JetBrains Mono — 16px bold
- **Line height:** Headings 1.0, Body 1.5

## Spacing
- Base: 4px
- Padding: 8/12/16/20/24/32/48px
- Radius: 4px (badges), 12px (cards), 9999px (pills)

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg=#059669, white text, 12px radius, bold | hover:#047857 / active:#065F46 / disabled:#A7F3D0 |
| Pricing Card | bg=white, 12px radius, shadow | popular:border #059669 ring-2 |
| Schedule Cell | bg=white, 8px radius, p=8px | available / full / booked / waitlist |
| QR Scanner | fullscreen camera | scanning#22C55E / error#EF4444 |
| Set Row | bg=#F9FAFB, rounded 8px | done:bg#DCFCE7 text#059669 |
| Progress Ring | circular svg | fill percentage, stroke #059669 |
| Capacity Bar | h=6px, bg=#E2E8F0 | fill green→yellow→red gradient |
