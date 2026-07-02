# Design System — SalonPro
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **اسم العلامة:** SalonPro — صالون برو
- **مفهوم الشعار:** أيقونة مشط مع نجمة ذهبية ترمز للجودة
- **شخصية العلامة:** أنيقة، أنثوية، فاخرة، عصرية، دافئة
- **الجمهور:** صالونات تجميل، كوافيرات، زبونات

## Color Palette
- **Primary:** `#BE185D` — وردي غامق (أزرار، شعار، عناوين)
- **Secondary:** `#F59E0B` — ذهبي (نجوم، نقاط ولاء)
- **Accent:** `#8B5CF6` — بنفسجي (مميزات، عروض)
- **Neutral-100:** `#FFF1F2` — خلفية وردية فاتحة
- **Neutral-200:** `#FECDD3` — حدود
- **Neutral-700:** `#4C0519` — نص أساسي
- **Neutral-900:** `#2D0A1B` — عناوين
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#BE123C`
- **Gradient:** `linear-gradient(135deg, #BE185D, #8B5CF6)`

## Typography
- **Headings (English):** Playfair Display — 28/24/20/18px, weight 600
- **Body (English):** Inter — 14px
- **Arabic:** Noto Sans Arabic — elegant weight 300/400/700
- **Line height:** Headings 1.15, Body 1.6

## Spacing
- Base: 4px
- Padding: 8/12/16/20/24/32px
- Radius: 8px (cards), 16px (modals), 9999px (avatars)
- Container: 1200px

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg=#BE185D, white text, 10px radius | hover:#9D174D / active:#831843 / disabled:#FBCFE8 |
| Service Card | bg=white, 12px radius, shadow | hover:shadow-lg, selected:border #BE185D |
| Stylist Avatar | 64×64px, ring-2 | available:ring#10B981, busy:ring#F59E0B |
| Time Slot | bg=white, border 1px, 8px radius | available / booked / selected |
| Employee Card | bg=white, 10px radius, p=16px | hover:shadow |
| Loyalty Card | gradient bg, 16px radius | bronze:#CD7F32, silver:#C0C0C0, gold:#FFD700 |
| Reward Card | bg=white, 12px radius, border | available:border#BE185D, redeemed:opacity-50 |
