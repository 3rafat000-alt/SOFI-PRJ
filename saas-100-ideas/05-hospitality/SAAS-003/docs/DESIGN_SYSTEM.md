# Design System — MenuByte
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **اسم العلامة:** MenuByte — مينيو بايت
- **مفهوم الشعار:** أيقونة همبرغر مع رمز QR مدمج في الشريحة العلوية
- **شخصية العلامة:** دافئة، شهية، عصرية، نشطة، ودودة
- **الجمهور:** مطاعم، مقاهي، شيفات

## Color Palette
- **Primary:** `#DC2626` — أحمر (أزرار، رؤوس، شريط المطبخ)
- **Secondary:** `#F97316` — برتقالي ثانوي (إضافات، مميزات)
- **Accent:** `#EAB308` — أصفر (تقييمات، نجوم)
- **Neutral-100:** `#FFF7ED` — خلفية دافئة
- **Neutral-200:** `#FED7AA` — حدود وفواصل
- **Neutral-700:** `#431407` — نص أساسي (بني غامق)
- **Neutral-900:** `#1C1917` — نص عناوين
- **Semantic:** Success `#22C55E` · Warning `#F59E0B` · Error `#EF4444` · Info `#3B82F6`
- **Gradient:** `linear-gradient(135deg, #DC2626, #F97316)` — KDS header

## Typography
- **Headings (English):** Fredoka One — rounded friendly, 28/24/20/18px
- **Body (English):** Nunito — 15px, weight 400/600/700
- **Arabic:** Noto Sans Arabic — bold weights for headings
- **KDS Display:** Inter Bold — 24px items, 48px order number
- **Line height:** Headings 1.1, Body 1.5

## Spacing
- Base unit: 4px
- Padding: 8/12/16/20/24/32px
- KDS spacing: generous (48px between cards)
- Border radius: 12px (cards), 20px (pills), 9999px (modifiers)

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Primary Button | bg=#DC2626, text=white, 12px radius, py=14px px=24px | hover:#B91C1C / active:#991B1B / disabled:#FCA5A5 |
| Menu Item Card | bg=white, 12px radius, shadow-sm, image 3:2 | hover:shadow-md, out-of-stock:opacity-50 |
| Modifier Chip | bg=#FEF2F2, border=#FECACA, text=#DC2626 | selected:bg=#DC2626 text=white, disabled:opacity-40 |
| KDS Card | bg=#1C1917, text=white, 16px radius, large padding | new:border-left 6px #22C55E, urgent:pulse red |
| Order Stepper | 4 circles connected by line | done:bg=#22C55E, current:pulse #DC2626, pending:bg=#D1D5DB |
| Cart FAB | bg=#DC2626, text=white, 56×56px, badge count | has-items:show, empty:dot-hidden |
