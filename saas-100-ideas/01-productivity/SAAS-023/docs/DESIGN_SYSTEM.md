# Design System — HRTide
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** HRTide — مد الموارد البشرية
- **Logo concept:** موجة زرقاء صاعدة مع حرف H عربي في المنتصف
- **Brand personality:** مهني، إنساني، منظم، ودود، حديث

## Color Palette
- **Primary:** `#1976D2` — أزرق مهني (أزرار رئيسية، أشرطة، روابط)
- **Secondary:** `#0097A7` — أزرق مخضر (أيقونات، أقسام، شريط جانبي)
- **Accent:** `#43A047` — أخضر (حضور، نشاط، مؤشرات إيجابية)
- **Neutral:** `#FAFAFA` (خلفيات) `#757575` (نص ثانوي) `#212121` (نص أساسي)
- **Semantic:** Present `#43A047` · Absent `#E53935` · Leave `#FDD835` · Pending `#FB8C00`

## Typography
- **Headings:** Inter — sizes: 24/20/18/16px (600 weight)
- **Body:** Inter — 14px (400 weight)
- **Arabic:** Noto Sans Arabic — RTL support, Hijri date compatibility
- **Monospace:** JetBrains Mono — employee IDs, payroll figures

## Spacing
- Base unit: 8px
- Padding: 16/24/32px
- Border radius: 8px (cards), 6px (buttons), 4px (inputs)
- Gap: 12px in forms, 16px between cards

## Iconography
- Style: Outline
- Library: Lucide
- Key icons: Users, Calendar, Clock, DollarSign, TrendingUp, FileText

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-primary, white text, 6px radius, 44px | hover: darken 10%, active: scale(0.97), loading: spinner |
| WizardStepper | 3 circles connected by line | completed: green circle, active: blue circle, pending: gray circle |
| EmployeeCard | 80px avatar left, info right | hover: shadow, selected: border-primary 2px |
| Table | header bg-secondary, white text | row hover: bg-blue-50, sortable headers |
| Badge | 4px radius, 8px padding | Present(green), Absent(red), Leave(yellow), Remote(blue) |
| OrgChart | tree layout with connected nodes | expand/collapse children on click |
| Calendar | monthly grid, event dots | day hover: bg-blue-50, selected: bg-primary text white |
