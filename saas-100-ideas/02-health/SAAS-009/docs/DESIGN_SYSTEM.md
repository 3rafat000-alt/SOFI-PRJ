# Design System — PetCare Vet
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **اسم العلامة:** PetCare — پت كير
- **مفهوم الشعار:** بصمة قدم حيوان مع صليب أخضر طبي
- **شخصية العلامة:** لطيفة، مهنية، دافئة، موثوقة، حنونة
- **الجمهور:** أطباء بيطريون، ملاك حيوانات

## Color Palette
- **Primary:** `#0D9488` — فيروزي (أزرار، رؤوس، شعار)
- **Secondary:** `#2563EB` — أزرق (روابط، معلومات)
- **Accent:** `#F59E0B` — كهرماني (تنبيهات)
- **Neutral-100:** `#F0FDFA` — خلفية فيروزية فاتحة
- **Neutral-200:** `#CCFBF1` — حدود
- **Neutral-700:** `#134E4A` — نص أساسي
- **Neutral-900:** `#042F2E` — عناوين
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#EF4444`
- **Gradient:** `linear-gradient(135deg, #0D9488, #2563EB)`

## Typography
- **Headings (English):** Nunito — rounded, 28/24/20/18px
- **Body (English):** Inter — 14px
- **Arabic:** Noto Sans Arabic
- **Medical terms:** Inter — 13px (bold for diagnoses)
- **Line height:** Headings 1.2, Body 1.6

## Spacing
- Base: 4px
- Padding: 8/12/16/20/24/32px
- Radius: 8px (cards), 16px (modals), 9999px (pills)
- Container: 1280px

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg=#0D9488, white text, 10px radius | hover:#0F766E / active:#115E59 / disabled:#99F6E4 |
| Pet Card | bg=white, 12px radius, shadow | hover:shadow-lg, selected:border #0D9488 |
| Visit Card | bg=white, 8px radius, left border 4px | collapsed/expanded |
| Vaccine Badge | 6px radius pill | completed:bg#D1FAE5 text#065F46, overdue:bg#FEE2E2 text#991B1B |
| Stock Gauge | bar h-6px | ok:#10B981, low:#F59E0B, critical:#EF4444, expired:#6B7280 |
| Weight Chart | line svg, 2px stroke | trend: #0D9488 |
| Timeline | vertical line + dots | each dot = visit, clickable |
