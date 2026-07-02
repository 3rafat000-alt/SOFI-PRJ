# Design System — ReviewRadar
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** ReviewRadar — رادار التقييمات
- **Logo concept:** أيقونة رادار مع نجمة خضراء في المنتصف وأمواج بنفسجية
- **Brand personality:** يقظ، سريع، موثوق، حاسم، ذكي

## Color Palette
- **Primary:** `#D84315` — برتقالي محمر (أزرار، تنبيهات، شريط علوي)
- **Secondary:** `#6A1B9A` — بنفسجي (أيقونات، تمايز، تحليلات)
- **Accent:** `#00838F` — أزرق داكن (روابط، خلفيات ثانوية)
- **Neutral:** `#FBE9E7` (خلفيات) `#78909C` (نص ثانوي) `#37474F` (نص أساسي)
- **Semantic:** 5 stars `#2E7D32` · 4 stars `#43A047` · 3 stars `#F9A825` · 2 stars `#EF6C00` · 1 star `#D32F2F`
- **Sentiment:** Positive `#43A047` · Negative `#E53935` · Neutral `#757575`

## Typography
- **Headings:** Inter — sizes: 24/20/18/16px (600 weight)
- **Body:** Inter — 14px (400 weight)
- **Arabic:** Noto Sans Arabic — sentiment labels, auto-reply templates
- **Rating numbers:** bold 14px Inter

## Spacing
- Base unit: 8px
- Padding: 16/24/32px
- Border radius: 8px (cards), 6px (buttons), 20px (sentiment badges)
- Inbox gap: 12px between review cards

## Iconography
- Style: Outline
- Library: Lucide
- Key icons: Star, MessageSquare, Bell, TrendingUp, ThumbsUp, AlertTriangle

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Alert | bg-error, white text, 8px radius, 48px | hover: darken, pulse when new negative review |
| RatingGauge | large number + 5 stars row | animated on change, color shifts with average |
| ReviewCard | avatar left, content middle, time right | unreplied: bg-white, replied: bg-green-50 |
| SentimentBadge | pill shape with icon | positive: green bg, negative: red bg, neutral: gray bg |
| SuggestionChip | text in pill, X to dismiss | hover: bg-primary-light, selected: bg-primary text white |
| TrendChart | line/bar, time range selector | hover: tooltip with exact numbers, click: drill down |
