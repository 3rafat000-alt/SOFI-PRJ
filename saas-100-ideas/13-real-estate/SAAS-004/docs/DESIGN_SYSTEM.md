# Design System — RentTrack
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **اسم العلامة:** RentTrack — رينت تراك
- **مفهوم الشعار:** أيقونة مبنى بمفتاح ذهبي في الأمام
- **شخصية العلامة:** فاخرة، موثوقة، مهنية، جادة، مستقرة
- **الجمهور:** ملاك عقارات، وسطاء، مستأجرون

## Color Palette
- **Primary:** `#B8860B` — ذهبي (أزرار، شعار، عناوين)
- **Secondary:** `#1E3A5F` — كحلي ثانوي (أشرطة، خلفيات داكنة)
- **Accent:** `#D4AF37` — ذهبي فاتح (تأكيدات، إنجازات)
- **Neutral-100:** `#FAFAF5` — خلفية كريمية
- **Neutral-200:** `#E8E0D0` — حدود وفواصل
- **Neutral-700:** `#3D3228` — نص أساسي
- **Neutral-900:** `#1A1512` — نص عناوين
- **Semantic:** Success `#2E7D32` · Warning `#F9A825` · Error `#C62828`
- **Gradient:** `linear-gradient(135deg, #B8860B, #1E3A5F)`

## Typography
- **Headings (English):** Playfair Display — serif, 32/28/24/20/18px
- **Body (English):** Inter — 14px, weight 400/500
- **Arabic:** Noto Sans Arabic — serif headings, sans body
- **Monospace:** JetBrains Mono — 13px (contract refs)
- **Line height:** Headings 1.15, Body 1.6

## Spacing
- Base unit: 4px
- Padding: 8/12/16/20/24/32/48px
- Container max-width: 1280px
- Border radius: 4px (badges), 8px (cards), 16px (modals)

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg=#B8860B, text=white, 8px radius | hover:#A0760A / active:#8B6508 / disabled:#E8D5A8 |
| Property Card | bg=white, 12px radius, shadow | hover:shadow-lg, selected:border #B8860B |
| Contract Preview | bg=#FAFAF5, border=#E8E0D0, 8px radius | signed:border #2E7D32 |
| Payment Badge | 4px radius, px=8px py=2px | paid:#E8F5E9 bg #2E7D32 text, overdue:#FFEBEE bg #C62828 text |
| Maintenance Card | bg=white, 8px radius, shadow | urgent:border-left 4px #C62828 |
