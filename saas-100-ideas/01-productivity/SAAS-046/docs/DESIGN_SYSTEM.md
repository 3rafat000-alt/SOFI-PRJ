# Design System — StampMe
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name:** StampMe — بصمتي
- **Logo concept:** ختم دائري مع علامة صح في المنتصف
- **Brand personality:** دقيق، موثوق، سريع، احترافي، بسيط

## Color Palette
- **Primary:** `#0D9488` — تيل (Teal) للأزرار والعناوين
- **Secondary:** `#0891B2` — أزرق سماوي للعناصر الثانوية
- **Accent:** `#F59E0B` — ذهبي للتحذيرات والتأخير
- **Neutral:** `#F0FDFA` خلفية، `#134E4A` نص رئيسي، `#5C6B6A` نص ثانوي
- **Semantic:** Present `#10B981` · Late `#F59E0B` · Absent `#EF4444` · Overtime `#3B82F6`

## Typography
- **Headings:** Inter — sizes: 24/20/18/16px
- **Body:** Inter — 14px / 1.5
- **Arabic:** Noto Sans Arabic — clear Arabic for workers
- **Display:** Inter Bold — for check-in status (large)

## Spacing
- Base unit: 4px
- Padding: 16/24/32px
- Border radius: 12px (cards), 50% (check button)
- Mobile-first: 16px gutters

## Iconography
- Style: Filled (clear on small screens)
- Library: Lucide Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| CheckButton | 80px circle, icon inside | default: pulse / loading: spin / success: check / error: X |
| Primary Button | bg-#0D9488, white text, 8px radius | hover: #0F766E / active: #115E59 / disabled: opacity 0.5 |
| CameraView | full-width camera, oval guide | success: green border / fail: red border / low-light: flash icon |
| Calendar | month grid, 14px cells | today: circle / present: green dot / late: amber / absent: red |
| DataTable | striped rows, 13px font | hover: bg #F0FDFA / sorted: arrow indicator |
| StatCard | white bg, shadow-sm, 12px radius | value animated on mount |
| StatusBadge | present/late/absent/leave | pill shape, icon + text | 
