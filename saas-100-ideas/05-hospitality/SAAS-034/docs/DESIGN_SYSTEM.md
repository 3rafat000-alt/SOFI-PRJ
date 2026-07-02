# Design System — BookingPro
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** BookingPro — حجوزات احترافية
- **Logo concept:** أيقونة تقويم مع علامة صح + نص sans-serif دافئ
- **Brand personality:** دافئ، موثوق، منظم، سهل، إنساني

## Color Palette
- **Primary:** `#D97706` — كهرماني دافئ (أزرار، رؤوس، عناوين)
- **Secondary:** `#F59E0B` — ذهبي (شارات Pro، تأكيدات، نجمة)
- **Accent:** `#059669` — أخضر زمردي (أوقات متاحة، نجاح، دفع مؤكد)
- **Neutral:** `#FFF7ED` / `#1C1917` — خلفيات دافئة / نصوص أساسية
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#EF4444`

## Typography
- **Headings:** Inter — sizes: 28/24/20/18px (Arabic: Cairo 26/22/18/16px)
- **Body:** Inter — 14px/16px
- **Arabic:** Cairo — دعم 4 أوزان (400/500/600/700)

## Spacing
- Base unit: 8px
- Padding: 16/24/32/48px
- Border radius: 12px (cards), 8px (inputs), 9999px (pills)

## Iconography
- Style: Outline 24px stroke 2
- Library: Lucide Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg `#D97706`, white text, 12px radius | hover: `#B45309` / active: `#92400E` / disabled: opacity 50% |
| Button Secondary | bg white, border `#D97706`, amber text | hover: bg `#FFFBEB` |
| Calendar Header | bg `#FFF7ED`, amber text, 8px radius | month/year navigation arrows |
| Time Slot | bg `#ECFDF5` available / bg `#F3F4F6` booked | hover available: bg `#D1FAE5` |
| Stepper | progress bar amber, step circles | active: amber / complete: green / pending: gray |
| Service Card | bg white, shadow-sm, 12px radius | hover: shadow-md, border amber-200 |
| Payment Form | Stripe-style, clean input | focus: border amber + ring |
| Stat Card | bg white, border `#FDE68A`, shadow-sm | hover: shadow-md |
