# Design System — FileVault
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name:** FileVault — خزينة الملفات
- **Logo concept:** درع مع مجلد في المنتصف، أيقونتي قفل وشيفرة
- **Brand personality:** آمن، موثوق، مهني، أنيق، واضح

## Color Palette
- **Primary:** `#0D9488` — تيل (Teal) للأزرار والروابط
- **Secondary:** `#0891B2` — أزرق سماوي للعناصر الثانوية
- **Accent:** `#F59E0B` — ذهبي للشارات والتأكيدات المهمة
- **Neutral:** `#F0FDFA` خلفية فاتحة، `#134E4A` نص رئيسي، `#5C6B6A` نص ثانوي
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#EF4444` · Info `#3B82F6`

## Typography
- **Headings:** Inter — sizes: 24/20/18/16px
- **Body:** Inter — 14px / 1.5
- **Arabic:** Noto Sans Arabic — support for all Arabic labels
- **Monospace:** JetBrains Mono — for file hashes and checksums

## Spacing
- Base unit: 4px
- Padding: 16/24/32px
- Border radius: 8px (cards), 6px (buttons), 4px (inputs)
- Grid: 12-column, gutter 24px

## Iconography
- Style: Outline (1.5px stroke)
- Library: Lucide Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-#0D9488, white text, 6px radius | hover: #0F766E / active: #115E59 / disabled: opacity 0.5 |
| FileCard | border 1px #E2E8F0, rounded-lg, 12px padding | hover: shadow-md / selected: border #0D9488 / uploading: opacity 0.7 |
| Input Field | border 1px #CBD5E1, 12px padding, 6px radius | focus: border #0D9488 + ring / error: border #EF4444 |
| ShareLink | bg #F0FDFA, border dashed #0D9488, monospace | copied: bg #D1FAE5, checkmark icon |
| DragDropZone | border 2px dashed #CBD5E1, 32px padding | drag-over: border #0D9488 + bg #F0FDFA |
| AccessLogTable | striped rows, 13px font | hover row: bg #F0FDFA |
