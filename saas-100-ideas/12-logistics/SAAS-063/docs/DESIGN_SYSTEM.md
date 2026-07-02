# Design System — TruckNet
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** TruckNet — شبكة الشاحنات
- **Logo concept:** Truck silhouette + network nodes in blue, orange accent
- **Brand personality:** Reliable, Fast, Industrial, Modern, Connected

## Color Palette
- **Primary:** `#2563EB` — Blue 600, trust + reliability
- **Secondary:** `#EA580C` — Orange 600, energy + urgency
- **Accent:** `#0EA5E9` — Sky 500, movement + clarity
- **Neutral:** `#1E293B` — Slate 800 (text), `#F8FAFC` (bg)
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#EF4444`

## Typography
- **Headings:** Inter — sizes: 24/20/18/16px
- **Body:** Inter — 14px
- **Arabic:** Noto Kufi Arabic — driver notifications, trip details

## Spacing
- Base unit: 4px/8px
- Padding: 16/24/32px
- Border radius: 8px

## Iconography
- Style: Filled (for map markers), Outline (for UI)
- Library: Lucide

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg #2563EB, white text | hover #1D4ED8 / active #1E40AF / disabled |
| Button Secondary | bg #EA580C, white text | hover #D97706 / active #C2410C |
| Map Marker | Moving #2563EB / Idle #F59E0B / Stopped #EF4444 / Offline #94A3B8 | selected ring 3px white |
| Trip Card | bg white, shadow-sm, 8px radius | drag shadow-lg / overdue red border |
| Input Field | border 1px #CBD5E1 | focus ring #2563EB |
| Driver Avatar | circle, initials | online/offline indicator dot |
| POD Image | rounded 8px, max-h 300px | loading skeleton / error retry |
