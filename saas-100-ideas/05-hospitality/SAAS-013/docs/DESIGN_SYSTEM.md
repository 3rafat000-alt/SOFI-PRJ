# Design System — Eventify
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** "Eventify" — تحويل الأفكار إلى فعاليات لا تُنسى
- **Logo concept:** نجمة مع دائرتين متداخلتين (ترمز للالتقاء والفعالية)
- **Brand personality:** ديناميكي · دافئ · عصري · احتفالي · منظم

## Color Palette (Hospitality: Warm Red/Orange)
- **Primary:** `#C0392B` — أحمر دافئ (headers, primary buttons, key CTAs)
- **Secondary:** `#E67E22` — برتقالي دافئ (secondary elements, highlights, badges)
- **Accent:** `#F39C12` — ذهبي برتقالي (discount codes, premium badges, stars)
- **Neutral:** `#FDF2E9` — كريمي للخلفيات، `#F5E6CC` للحدود، `#7F5A3E` نصوص ثانوية
- **Semantic:** Success `#27AE60` · Warning `#F1C40F` · Error `#C0392B` · Info `#2980B9`

## Typography
- **Headings:** Poppins (English) / Noto Sans Arabic (Arabic) — sizes: 28/24/20/18/16px
- **Body:** Poppins / Noto Sans Arabic — 14px regular
- **Small/Meta:** 12px
- **Line height:** Headings 1.15, Body 1.6

## Spacing
- Base unit: 8px
- Container max-width: 1280px
- Padding: 16/24/32/48px
- Border radius: 12px (cards), 8px (buttons), 20px (modals)
- Gap: 16px / 24px

## Iconography
- Style: Filled outline, 1.5px stroke, 24x24
- Library: Lucide Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-#C0392B, white text, 8px radius, 14px/600 | hover #A93226 / active #922B21 / disabled opacity 50% |
| Button Secondary | border 2px #E67E22, text #E67E22 | hover bg-#E67E2210 / active bg-#E67E2220 |
| EventCard | bg-white, 12px radius, shadow-md, image top | hover shadow-lg / selected border-#C0392B |
| Input Field | border 1px #F5E6CC, 12px padding, 8px radius | focus border-#C0392B ring-2px / error border-#C0392B |
| StepIndicator | numbered circles + connector line | completed bg-#27AE60 white text / active bg-#C0392B white text / pending bg-#F5E6CC |
| SpeakerCard | avatar + name + topic + drag handle | normal / drag state with opacity |
| QRScanner | full camera view + corner brackets | scanning / success green flash / error red flash |
| TicketRow | name + price + progress bar + actions | active / paused / sold out |
| PriceBadge | pill, currency formatted | standard / early-bird / VIP / free |
| Modal | bg-white, 20px radius, backdrop 50% | open/close 250ms ease |
