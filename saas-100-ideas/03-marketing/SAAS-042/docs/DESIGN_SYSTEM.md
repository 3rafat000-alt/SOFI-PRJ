# Design System — PollPro
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name:** PollPro — تصويت محترف
- **Logo concept:** أيقونة استفتاء (checkbox) مع موجات live تغذيها
- **Brand personality:** حيوي، بسيط، ممتع، موثوق، عصري

## Color Palette
- **Primary:** `#7C3AED` — بنفسجي حيوي للأزرار والعناوين
- **Secondary:** `#EC4899` — وردي للعناصر التفاعلية والإشعارات
- **Accent:** `#F59E0B` — ذهبي/برتقالي للشارات والإنجازات
- **Neutral:** `#F9FAFB` خلفية، `#1F2937` نص رئيسي، `#6B7280` نص ثانوي
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#EF4444`

## Typography
- **Headings:** Plus Jakarta Sans — sizes: 28/24/20/18px
- **Body:** Inter — 14px / 1.6
- **Arabic:** Noto Naskh Arabic — elegant Arabic for poll questions
- **Display:** Clash Display — for projector mode (large text)

## Spacing
- Base unit: 4px
- Padding: 16/24/32/48px
- Border radius: 12px (cards), 8px (buttons), 6px (inputs)
- Grid: 12-column, gutter 16px

## Iconography
- Style: Filled (playful)
- Library: Phosphor Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-#7C3AED, white text, 8px radius, 16px font | hover: #6D28D9 / active: #5B21B6 / disabled: opacity 0.5 |
| OptionCard | border 2px #E5E7EB, 12px padding, 12px radius | selected: border #7C3AED + bg #F5F3FF / hover: border #D1D5DB |
| LiveChart | smooth transitions, rounded bars | animating: ease-out 300ms / empty: grey placeholder |
| QRDisplay | white bg, 8px padding, rounded-xl | active: border animation / expired: grey overlay |
| CodeInput | 6 boxes, 48px each, center text | focus: ring #7C3AED / error: ring #EF4444 + shake |
| QuestionCard | white bg, 16px padding, shadow-sm | active: shadow-md |
