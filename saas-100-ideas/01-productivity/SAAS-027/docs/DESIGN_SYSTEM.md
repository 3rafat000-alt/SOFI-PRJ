# Design System — ShiftMaster
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** ShiftMaster — سيد المناوبات
- **Logo concept:** دائرة ساعة مع عقارب وقسمين ملونين (ليل/نهار)
- **Brand personality:** فعال، مرن، منظم، ودود، عملي

## Color Palette
- **Primary:** `#0288D1` — أزرق فاتح (أزرار، رأس الصفحة، روابط)
- **Secondary:** `#00796B` — أزرق مخضر (شريط جانبي، أيقونات، أقسام)
- **Accent:** `#FF8F00` — كهرماني (تنبيهات، تمايز، أزرار ثانوية)
- **Neutral:** `#F5F5F5` (خلفيات) `#757575` (نص ثانوي) `#212121` (نص أساسي)
- **Shift Types:** Morning `#0288D1` · Evening `#FF8F00` · Night `#37474F`
- **Semantic:** On time `#43A047` · Late `#F9A825` · Absent `#E53935` · Swap pending `#1E88E5`

## Typography
- **Headings:** Inter — sizes: 22/18/16/14px (600 weight)
- **Body:** Inter — 13px (400 weight, schedule grid)
- **Arabic:** Noto Sans Arabic — RTL schedule, shift type labels
- **Schedule time:** monospace 12px

## Spacing
- Base unit: 4px (grid cell), 8px (layout)
- Padding: 12/16/24/32px
- Border radius: 4px (grid cells), 8px (cards), 6px (buttons)
- Grid cell height: 48px

## Iconography
- Style: Outline
- Library: Lucide
- Key icons: Calendar, Clock, Users, ArrowLeftRight, MapPin, Bell

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| ShiftBlock | 48px height, colored by shift type, 4px radius | assigned: solid color, unassigned: dashed border, dragging: shadow-lg+rotated |
| ScheduleGrid | sticky header, alternating rows | cell hover: highlight, drop target: dashed border green |
| ClockButton | large circle, GPS indicator | in-range: green text "تسجيل دخول", out-of-range: red "خارج النطاق" |
| SwapCard | employee avatars left-right with arrow | pending: amber border, approved: green border, rejected: red border |
| WeekTabs | 7 day tabs | today: primary bg, selected: underline, past: gray |
| EmployeeRow | 48px row, name + role + current shift | hover: bg-blue-50, dragging: opacity 50% |
