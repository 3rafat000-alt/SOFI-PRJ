# Design System — SupportDesk
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** SupportDesk — مكتب الدعم
- **Logo concept:** أيقونة رأس سماعة / headset + شاشة دعم + نص sans-serif
- **Brand personality:** ودود، موثوق، سريع، محترف، داعم

## Color Palette
- **Primary:** `#0EA5E9` — أزرق فاتح (أزرار، رؤوس، روابط)
- **Secondary:** `#14B8A6` — تيل (تأكيدات، شارات CSAT، حالات إيجابية)
- **Accent:** `#F59E0B` — كهرماني (تحذيرات، SLA وشيك، تنبيهات)
- **Neutral:** `#F8FAFC` / `#0F172A` — خلفيات / نصوص أساسية
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#EF4444`

## Typography
- **Headings:** Inter — sizes: 28/24/20/18px (Arabic: Cairo 26/22/18/16px)
- **Body:** Inter — 14px/16px
- **Arabic:** Cairo — دعم 4 أوزان (400/500/600/700)

## Spacing
- Base unit: 8px
- Padding: 16/24/32/48px
- Border radius: 8px (cards), 9999px (badges), 12px (modals)

## Iconography
- Style: Outline 24px stroke 2
- Library: Lucide Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg `#0EA5E9`, white text, 8px radius | hover: `#0284C7` / active: `#0369A1` / disabled: opacity 50% |
| Button Secondary | bg white, border `#0EA5E9`, blue text | hover: bg `#F0F9FF` |
| Ticket Row | bg white, border-bottom `#E2E8F0` | hover: bg `#F8FAFC` / selected: bg `#F0F9FF` |
| Chat Bubble | agent: `#0EA5E9` bg white text / client: `#F1F5F9` / internal: `#FEF3C7` | hover: slight opacity |
| Filter Chip | bg `#F1F5F9`, text `#475569` | selected: bg `#0EA5E9` white text |
| SLA Timer | blue text / orange text at 50% / red text at 25% / bold red at overdue | pulse animation at critical |
| Agent Card | bg white, shadow-sm, 8px radius | hover: shadow-md, border blue-200 |
