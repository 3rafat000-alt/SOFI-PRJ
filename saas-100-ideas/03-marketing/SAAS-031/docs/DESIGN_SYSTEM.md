# Design System — SurveyCraft
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** SurveyCraft — حرفة صناعة الاستبيانات
- **Logo concept:** أيقونة استبيان (مربعات اختيار ملونة) + خط حديث sans-serif
- **Brand personality:** دقيق، احترافي، عصري، جدير بالثقة، مبتكر

## Color Palette
- **Primary:** `#E8590C` — برتقالي زاهي (أزرار، روابط، عناوين رئيسية)
- **Secondary:** `#7C3AED` — بنفسجي (تأكيدات، مميزات Pro، نقاط تميز)
- **Accent:** `#F59E0B` — كهرماني (شارات، تحذيرات، إشعارات)
- **Neutral:** `#F8F9FA` / `#212529` — خلفيات / نصوص أساسية
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#EF4444`

## Typography
- **Headings:** Inter — sizes: 32/24/20/18px (Arabic: 28/22/18/16px)
- **Body:** Inter — 14px/16px
- **Arabic:** Cairo — دعم كامل للخط العربي بـ 4 أوزان (400/500/600/700)

## Spacing
- Base unit: 8px
- Padding: 16/24/32/48px
- Border radius: 8px (cards, inputs), 12px (modals), 9999px (pills)

## Iconography
- Style: Outline 24px stroke 2
- Library: Lucide Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg `#E8590C`, white text, 8px radius | hover: `#D4540B` / active: `#C24D09` / disabled: opacity 50% / loading: spinner |
| Button Secondary | bg white, border `#E8590C`, orange text | hover: bg `#FFF5F0` / active: bg `#FFE8DB` |
| Input Field | border `#D1D5DB` 1px, 12px padding, 8px radius | focus: border `#E8590C` + ring 2px / error: border `#EF4444` / disabled: bg `#F3F4F6` |
| Card | bg white, border `#E5E7EB`, shadow-sm 8px radius | hover: shadow-md / loading: skeleton shimmer |
| Chart | SVG-based, responsive | loading: skeleton / empty: illustration + CTA |
| Modal | centered, backdrop blur, 24px padding | open: fade-in scale / close: fade-out |
| Tag | bg `#F3F4F6`, 4px radius, 6px padding | hover: bg `#E5E7EB` / selected: bg `#E8590C` white text |
