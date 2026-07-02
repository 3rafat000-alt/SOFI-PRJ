# Design System — CourseCraft
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name:** CourseCraft — صياغة الدورات
- **Logo concept:** كتاب مفتوح مع قلم وأيقونة شهادة
- **Brand personality:** أكاديمي، أنيق، موثوق، عصري، ملهم

## Color Palette
- **Primary:** `#6D28D9` — بنفسجي أكاديمي للأزرار والعناوين
- **Secondary:** `#2563EB` — أزرق دراسي للروابط والعناصر الثانوية
- **Accent:** `#F59E0B` — ذهبي للشهادات والإنجازات
- **Neutral:** `#F8FAFC` خلفية، `#0F172A` نص رئيسي، `#475569` نص ثانوي
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#EF4444` · Info `#3B82F6`

## Typography
- **Headings:** Playfair Display — sizes: 28/24/20/18px (أكاديمي أنيق)
- **Body:** Inter — 14px / 1.6
- **Arabic:** Noto Naskh Arabic — أنيق للعناوين العربية
- **Mono:** JetBrains Mono — لمقتطفات الكود في دورات البرمجة

## Spacing
- Base unit: 4px
- Padding: 16/24/32/48px
- Border radius: 12px (cards), 8px (buttons), 6px (inputs)
- Content max-width: 720px (readability)

## Iconography
- Style: Outline (1.5px stroke)
- Library: Lucide Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-#6D28D9, white text, 8px radius | hover: #5B21B6 / active: #4C1D95 / disabled: opacity 0.5 |
| ModuleCard | border 1px #E2E8F0, 12px radius, 16px padding | dragging: shadow-lg + #DDD6FE border |
| LessonCard | icon+title+status | locked: opacity 0.5 / available: default / completed: green check |
| VideoPlayer | custom controls, dark theme | play/pause, buffering, error state |
| ProgressBar | bg #E2E8F0, fill #6D28D9 at 100% | animated transition, milestone dots |
| QuizOption | 4 choices, radio style | default, selected (#DDD6FE), correct (#D1FAE5), wrong (#FEE2E2) |
| CertificateBadge | gold border, seal icon | generated: full colour / pending: grey |
| InstructorCard | avatar + name + headline | hover: shadow-md |
