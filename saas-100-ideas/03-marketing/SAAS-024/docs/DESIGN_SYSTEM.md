# Design System — SocialKit
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** SocialKit — عدة التواصل الاجتماعي
- **Logo concept:** أيقونة صاروخ من الألوان البرتقالي والبنفسجي مع دائرة اتصال
- **Brand personality:** حيوي، إبداعي، ديناميكي، عصري، جريء

## Color Palette
- **Primary:** `#E65100` — برتقالي نابض بالحياة (أزرار رئيسية، شعار، رأس الصفحة)
- **Secondary:** `#7B1FA2` — بنفسجي (أقسام، أيقونات، تمايز)
- **Accent:** `#FF6F00` — برتقالي داكن (تأكيدات، روابط، إشعارات)
- **Neutral:** `#F5F5F5` (خلفيات) `#757575` (نص ثانوي) `#212121` (نص أساسي)
- **Semantic:** Instagram pink `#E4405F` · Twitter blue `#1DA1F2` · LinkedIn `#0A66C2` · Snapchat yellow `#FFFC00`
- **Status:** Published `#43A047` · Draft `#757575` · Failed `#E53935` · Scheduled `#1E88E5`

## Typography
- **Headings:** Inter — sizes: 24/20/18/16px (700 weight)
- **Body:** Inter — 14px (400 weight)
- **Arabic:** Noto Sans Arabic — RTL post editor, Arabic analytics labels
- **Character limit counter:** monospace at 14px

## Spacing
- Base unit: 8px
- Padding: 16/24/32px
- Border radius: 12px (cards), 8px (buttons), 6px (inputs)
- Card gap: 16px in grid

## Iconography
- Style: Filled (platform icons), Outline (UI)
- Library: Lucide (UI) + Simple Icons (platforms)
- Key icons: Instagram, Twitter, Snapchat, Linkedin, Calendar, BarChart3

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-primary, white text, 8px radius, 48px | hover: darken 10%, active: scale(0.97), loading: skeleton |
| PlatformBadge | icon + platform name colored | connected: solid, disconnected: gray outline |
| PostCard | image top, text bottom, shadow-sm | draft: gray border, scheduled: blue dot, published: green border, failed: red border |
| Calendar | weekly grid, drop zones between days | hover: bg-orange-50, drop: highlight green |
| MediaUpload | dashed border dropzone | empty: dashed gray, drag-over: dashed primary, uploaded: thumbnail |
| QuickReply | chip with text, X to delete | default: bg-gray-100, selected: bg-primary text white |
