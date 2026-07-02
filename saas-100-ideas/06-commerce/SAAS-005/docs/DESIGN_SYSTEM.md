# Design System — ShopPulse
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **اسم العلامة:** ShopPulse — شوب بولس
- **مفهوم الشعار:** رسم بياني نبضي على شكل متجر
- **شخصية العلامة:** حيوية، تحليلية، جريئة، عصرية، مفيدة
- **الجمهور:** أصحاب متاجر إلكترونية، مسوقون

## Color Palette
- **Primary:** `#F97316` — برتقالي (أزرار، مؤشرات، عناوين)
- **Secondary:** `#8B5CF6` — بنفسجي (مخططات، ثانوي)
- **Accent:** `#10B981` — أخضر (أرباح، إيجابي)
- **Neutral-100:** `#FFFAF5` — خلفية دافئة
- **Neutral-200:** `#FED7AA` — حدود
- **Neutral-700:** `#431407` — نص أساسي
- **Neutral-900:** `#1C1917` — نص عناوين
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#EF4444`
- **Gradient:** `linear-gradient(135deg, #F97316, #8B5CF6)` — chart areas

## Typography
- **Headings (English):** Plus Jakarta Sans — 28/24/20/18/16px
- **Body (English):** Inter — 13px (data), 14px (UI)
- **Arabic:** Noto Sans Arabic
- **Monospace:** JetBrains Mono — 12px (numerical data)
- **Line height:** Headings 1.2, Body 1.5

## Spacing
- Base unit: 4px
- Padding: 8/12/16/20/24/32px
- Container: 1440px (dashboard), 1140px (public)
- Border radius: 6px (badges), 10px (cards), 16px (modals)

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg=#F97316, text=white, 10px radius | hover:#EA580C / active:#C2410C |
| KPI Card | bg=white, 10px radius, p=20px, shadow | hover:shadow-lg, trend-up:text#10B981 |
| Data Table | header bg=#F9FAFB, striped rows | hover:bg#F3F4F6, selected:border #F97316 |
| Chart | Recharts responsive | interaction:tooltip + crosshair |
| Stock Gauge | bg=#E5E7EB, fill=gradient | ok:#10B981, low:#F59E0B, critical:#EF4444 |
| Platform Card | bg=white, 12px radius, border 1px | selected:border #F97316 ring-2 |
