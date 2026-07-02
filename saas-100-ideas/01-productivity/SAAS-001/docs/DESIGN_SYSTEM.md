# Design System — TaskSync Pro
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **اسم العلامة:** TaskSync Pro — تاسك سينك برو
- **مفهوم الشعار:** حرف T يتكون من خطوط متصلة تمثل تزامن المهام مع نقطة في الأعلى ترمز لاكتمال المهمة
- **شخصية العلامة:** احترافية، نشطة، موثوقة، بسيطة، عصرية
- **الجمهور:** فرق عمل، شركات ناشئة، مستقلون

## Color Palette
- **Primary:** `#2563EB` — الأزرق الأساسي (أزرار، روابط، رؤوس)
- **Secondary:** `#7C3AED` — بنفسجي ثانوي (أيقونات، علامات مميزة)
- **Accent:** `#F59E0B` — كهرماني (تنبيهات، شارات الأولوية العالية)
- **Neutral-100:** `#F9FAFB` — خلفية الصفحات
- **Neutral-200:** `#E5E7EB` — حدود، فواصل
- **Neutral-700:** `#374151` — نص أساسي
- **Neutral-900:** `#111827` — نص عناوين
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#EF4444` · Info `#3B82F6`
- **Gradient:** `linear-gradient(135deg, #2563EB, #7C3AED)` — بطاقات العناوين

## Typography
- **Headings (English):** Inter — sizes: 32/24/20/18/16px, weight 700/600
- **Body (English):** Inter — 14px, weight 400/500
- **Arabic:** Noto Sans Arabic — matching weights, RTL-tailored letter-spacing
- **Monospace:** JetBrains Mono — 13px (code blocks, task IDs)
- **Line height:** Headings 1.2, Body 1.6
- **Font loading:** Google Fonts + system font fallback

## Spacing
- Base unit: 4px (quarter-step)
- Padding scale: 4/8/12/16/20/24/32/48/64px
- Margin scale: symmetric
- Container max-width: 1280px (dashboard), 1140px (public)
- Gap grid: 16/24/32px between sections
- Border radius scale: 4px (badges), 8px (cards), 12px (modals), 9999px (pills)

## Shadows
- Card: `0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.06)`
- Elevated: `0 10px 15px -3px rgba(0,0,0,0.1)`
- Modal: `0 20px 40px rgba(0,0,0,0.15)`

## Iconography
- Style: Outline, 24×24px default
- Library: Lucide Icons (550+ open-source)
- Color: Inherits text color, or semantic colors for status

## Motion
- Duration: 200ms (micro-interactions), 300ms (transitions), 500ms (page enter)
- Easing: ease-out (enter), ease-in-out (hover)
- Timing function: cubic-bezier(0.4, 0, 0.2, 1)

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg=#2563EB, text=white, 8px radius, py=10px px=20px | hover:#1D4ED8 / active:#1E40AF / disabled:#93C5FD / loading: spinner |
| Button Secondary | bg=white, border=#D1D5DB, text=#374151, 8px radius | hover:bg#F9FAFB / active:bg#F3F4F6 / disabled:opacity-50 |
| Input Field | border=1px #D1D5DB, 12px padding, 8px radius, text=14px | focus:ring-3 #2563EB / error:border#EF4444 / disabled:bg#F9FAFB |
| Task Card | bg=white, shadow-sm, 8px radius, p=16px | hover:shadow-md, drag:opacity-50, overdue:border-right 3px #EF4444 |
| Kanban Column | bg=#F3F4F6, 8px radius, min-height=400px | dragover:bg#E5E7EB dashed border |
| Timer Display | circular, 48×48px, stroke=#2563EB | running:pulse, paused:static, completed:stroke=#10B981 |
| Modal | bg=white, 12px radius, shadow-xl, max-w=600px | backdrop:bg rgba(0,0,0,0.5), close:× button |
| KPI Card | bg=white, shadow-sm, 8px radius, p=20px | hover:shadow-md, trend-up:text#10B981, trend-down:text#EF4444 |
| Badge | 4px radius, px=8px py=2px, font=12px | priority-high:bg#FEE2E2 text#EF4444, priority-med:bg#FEF3C7 text#F59E0B, priority-low:bg#ECFDF5 text#10B981 |
