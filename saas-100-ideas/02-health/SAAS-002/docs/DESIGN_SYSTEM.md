# Design System — ClinicFlow
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **اسم العلامة:** ClinicFlow — كلينيك فلو
- **مفهوم الشعار:** أيقونة صليب أخضر يتدفق منه خطوط تمثل انسيابية العمل في العيادة
- **شخصية العلامة:** مهنية، موثوقة، هادئة، دقيقة، نظيفة
- **الجمهور:** أطباء، عيادات خاصة، ممرضون

## Color Palette
- **Primary:** `#0284C7` — الأزرق الطبي (أزرار، روابط، رؤوس)
- **Secondary:** `#059669` — الأخضر الثانوي (حالات ناجحة، نشط)
- **Accent:** `#F59E0B` — كهرماني (تنبيهات، تحذيرات)
- **Neutral-100:** `#F0F9FF` — خلفية (أزرق فاتح جداً)
- **Neutral-200:** `#E2E8F0` — حدود، فواصل
- **Neutral-700:** `#334155` — نص أساسي
- **Neutral-900:** `#0F172A` — نص عناوين
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#EF4444` · Info `#0EA5E9`
- **Gradient:** `linear-gradient(135deg, #0284C7, #059669)` — بطاقات رأسية

## Typography
- **Headings (English):** Inter — sizes: 28/24/20/18/16px, weight 700/600
- **Body (English):** Inter — 14px, weight 400/500
- **Arabic:** Noto Sans Arabic — full RTL support
- **Monospace:** JetBrains Mono — 13px (prescription codes)
- **Line height:** Headings 1.2, Body 1.6

## Spacing
- Base unit: 4px
- Padding scale: 4/8/12/16/20/24/32/48/64px
- Container max-width: 1200px
- Border radius: 4px (badges), 8px (cards), 12px (modals), 9999px (pills)

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg=#0284C7, text=white, 8px radius, py=10px px=24px | hover:#0369A1 / active:#075985 / disabled:#BAE6FD |
| Search Input | icon prefix, border 1px #E2E8F0, 12px padding, 8px radius | focus:ring-3 #0284C7 |
| Patient Card | bg=white, shadow-sm, 8px radius, p=16px, avatar left | hover:shadow-md, selected:border #0284C7 |
| Appointment Card | left border 4px, p=12px | upcoming:#0284C7, completed:#10B981, cancelled:#CBD5E1 |
| Vitals Input | inline row, label left, value right | normal:#10B981 / warning:#F59E0B / critical:#EF4444 |
| Timeline Entry | icon + line connector, p=12px | default/hover/expanded |
