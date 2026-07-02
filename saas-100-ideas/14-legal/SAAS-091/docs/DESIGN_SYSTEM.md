# Design System — LawyerRef (SAAS-091)
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** LawyerRef — Lawyer Referral Network (شبكة إحالة المحامين)
- **Logo concept:** أيقونة ميزان العدالة + شبكة ربط باللون الذهبي
- **Brand personality:** موثوق، مهني، فخم، جاد، عصري

## Color Palette
- **Primary:** `#1B2A4A` (Navy) — خلفيات رئيسية، أزرار، رأس الصفحة
- **Secondary:** `#C9A84C` (Gold) — عناصر تفاعلية، أيقونات، تأكيدات
- **Accent:** `#7B1E1E` (Maroon) — تنبيهات، عناصر مهمة، خصومات
- **Neutral:** `#F5F5F0` (Cream) — خلفيات ثانوية، بطاقات
- **Dark:** `#1A1A2E` — نص أساسي، تذييل
- **Semantic:** Success `#2E7D32` · Warning `#F9A825` · Error `#C62828`

## Typography
- **Headings:** `Noto Sans Arabic` — sizes: 28/24/20/18/16px
- **Body:** `Noto Sans Arabic` — 14px / 16px للمحتوى الطويل
- **English:** `Inter` — same sizes
- **Arabic:** `Noto Sans Arabic` — دعم كامل للتشكيل

## Spacing
- Base unit: 4px/8px
- Padding: 16/24/32/48px
- Border radius: 8px (أزرار) · 12px (بطاقات) · 4px (حقول إدخال)
- Gap: 8/12/16/24px

## Iconography
- Style: Outline
- Library: Lucide Icons
- Size: 20px (قوائم) · 24px (أزرار) · 32px (شاشة فارغة)

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg #1B2A4A, white text, 8px radius | hover #2A3F6A / active #0F1A30 / disabled opacity 0.5 / loading spinner |
| Button Secondary | bg transparent, border #C9A84C, navy text | hover bg #C9A84C 10% / active bg 20% |
| Input Field | border 1px #D0D0D0, 12px padding, 4px radius | focus border #C9A84C + shadow / error border #C62828 / disabled bg #F0F0F0 |
| Card | bg white, shadow 0 2px 8px rgba(0,0,0,0.08), 12px radius | hover shadow 0 4px 16px / selected border #C9A84C |
| RatingStars | gold #C9A84C filled, #D0D0D0 empty | interactive hover scale 1.1 / readonly static |
| Tag | bg #F5F5F0, navy text, 16px py, 4px radius | interactive hover bg #E0E0D8 / selected #C9A84C |
