# Design System — ExamPro
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** ExamPro — اختبارات احترافية
- **Logo concept:** Graduation cap + checklist + A+ grade icon
- **Brand personality:** Academic, Precise, Innovative, Trustworthy, Serious

## Color Palette
- **Primary:** `#1D4ED8` — Academic Blue (buttons, primary actions, main identity)
- **Secondary:** `#7C3AED` — Purple (accents, proctoring indicators, premium badges)
- **Accent:** `#3B82F6` — Light Blue (links, information, help)
- **Neutral:** `#F8FAFC` — Cool grey (backgrounds), `#1E293B` — dark slate (text), `#64748B` — grey (labels)
- **Semantic:** Success `#22C55E` · Warning `#EAB308` · Error `#EF4444` · Info `#3B82F6`

## Typography
- **Headings:** Noto Sans Arabic (Google Font) — bold clean — sizes: 28/24/20/18px
- **Body:** Noto Sans Arabic — 14px (dashboard), 16px (student exam text)
- **Arabic:** Noto Sans Arabic — full Arabic + RTL + scientific symbols
- **Monospace:** JetBrains Mono (code, exam codes, math formulas)

## Spacing
- Base unit: 4px
- Padding: 16/24/32px
- Border radius: 8px (buttons, cards), 4px (inputs, tight for forms)
- Max content width: 1200px

## Iconography
- Style: Outline, 24px
- Library: Lucide Icons (book, pen, clock, check, flag, chart)

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-blue-700 #1D4ED8, white text, 8px radius | hover: #1E40AF / active: #1E3A8A / disabled / loading |
| TimerWidget | Countdown, bg-slate-100 | normal: black / warning: #EAB308 / danger: #EF4444 blink |
| QuestionCard | White bg, 8px radius, border #E2E8F0 | flagged: yellow border / answered: green indicator / skipped: grey |
| ProgressBar | Segmented, 48px height | answered/skipped/flagged/review |
| Chart | bar, line, pie | interactive drill-down |
| NavigatorDot | 10px circle | answered: green / skipped: grey / flagged: yellow / current: blue |
