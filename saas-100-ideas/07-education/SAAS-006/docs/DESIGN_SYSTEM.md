# Design System — EduCloud LMS
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **اسم العلامة:** EduCloud — إديو كلاود
- **مفهوم الشعار:** كتاب مفتوح مع سحابة في الخلفية
- **شخصية العلامة:** تعليمية، جادة، مبتكرة، منفتحة، موثوقة
- **الجمهور:** مدربون، طلاب، مراكز تدريب

## Color Palette
- **Primary:** `#4F46E5` — نيلي (أزرار، رؤوس، روابط)
- **Secondary:** `#7C3AED` — بنفسجي (مخططات، مميزات)
- **Accent:** `#06B6D4` — سيان (تقدم، نشاط)
- **Neutral-100:** `#F8FAFC` — خلفية
- **Neutral-200:** `#E2E8F0` — حدود
- **Neutral-700:** `#334155` — نص أساسي
- **Neutral-900:** `#0F172A` — عناوين
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#EF4444`
- **Gradient:** `linear-gradient(135deg, #4F46E5, #7C3AED)`

## Typography
- **Headings (English):** Outfit — 28/24/20/18/16px, weight 700
- **Body (English):** Inter — 14px
- **Arabic:** Noto Sans Arabic
- **Line height:** Headings 1.2, Body 1.6

## Spacing
- Base: 4px
- Padding: 8/12/16/20/24/32/48px
- Max-width: 1280px (dashboard), 800px (lesson)
- Radius: 6px (badges), 10px (cards), 16px (modals)

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg=#4F46E5, white text, 10px radius | hover:#4338CA / active:#3730A3 / disabled:#A5B4FC |
| Module Item | bg=white, p=12px, border-left 3px | default/hover/dragging |
| Video Player | bg=black, rounded 12px | play/pause/buffering/error |
| Progress Bar | h=6px, bg=#E2E8F0, fill=#4F46E5 | animated transition |
| Quiz Card | bg=white, 10px radius, p=20px | answered:border #10B981, wrong:border #EF4444 |
| Certificate | bg=#FAFAFA, border gold, p=40px | generated/verified badge |
