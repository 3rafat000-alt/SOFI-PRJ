# Design System — AssetGuard
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** AssetGuard — حارس الأصول
- **Logo concept:** درع أزرق بداخله أيقونة مفتاح ربط وQR code
- **Brand personality:** حازم، موثوق، احترافي، متين، دقيق

## Color Palette
- **Primary:** `#1565C0` — أزرق صناعي (أزرار، رأس الصفحة، شريط علوي)
- **Secondary:** `#F57F17` — أصفر داكن (تحذيرات، صيانة، أيقونات)
- **Accent:** `#004D40` — أخضر داكن (نجاح، جرد مكتمل، حالة جيدة)
- **Neutral:** `#ECEFF1` (خلفيات) `#78909C` (نص ثانوي) `#37474F` (نص أساسي)
- **Condition:** Good `#2E7D32` · Fair `#F9A825` · Damaged `#E65100` · Lost `#E53935`
- **Ticket Priority:** Low `#43A047` · Medium `#F9A825` · High `#E65100` · Critical `#D32F2F`

## Typography
- **Headings:** Inter — sizes: 24/20/18/16px (600 weight)
- **Body:** Inter — 14px (400 weight)
- **Arabic:** Noto Sans Arabic — RTL asset labels, condition badges
- **Monospace:** JetBrains Mono — serial numbers, QR content, asset IDs

## Spacing
- Base unit: 8px
- Padding: 16/24/32px
- Border radius: 6px (buttons), 8px (cards), 4px (inputs)
- Asset table row: 56px height

## Iconography
- Style: Outline
- Library: Lucide
- Key icons: Package, Wrench, QrCode, MapPin, ClipboardList, AlertTriangle

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-primary, white text, 6px radius, 44px height | hover: darken 10%, active: scale(0.97), disabled: opacity 0.5 |
| AssetCard | image/icon left, info right, QR preview | hover: shadow, selected: border-primary 2px |
| StatusBadge | pill shape with icon | Good(green), Fair(yellow), Damaged(orange), Lost(red) |
| LocationTree | indented tree with expand/collapse | has-children: ▶ collapsed, ▼ expanded, leaf: ● |
| CameraScanner | full viewport, scan frame overlay | idle: white frame, scanning: blue pulse, success: green frame, error: red frame |
| MaintenanceCard | date left, asset info right, status badge | pending: blue, overdue: red, completed: green |
| MultiStepForm | progress indicator top, content middle | step circle: completed ✓, active ●, pending ○ |
