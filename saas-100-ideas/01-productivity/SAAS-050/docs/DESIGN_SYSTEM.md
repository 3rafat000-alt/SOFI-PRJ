# Design System — DevSync
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name:** DevSync — تزامن المطورين
- **Logo concept:** رمز "</>" (code brackets) مع سهم دائري (sync)
- **Brand personality:** تقني، سريع، نظيف، مهني، حديث

## Color Palette
- **Primary:** `#0D9488` — تيل (Teal) للأزرار والعناوين
- **Secondary:** `#0891B2` — أزرق سماوي للروابط
- **Accent:** `#F59E0B` — كهرماني للتحذيرات
- **Neutral:** `#F0FDFA` خلفية، `#134E4A` نص رئيسي، `#5C6B6A` نص ثانوي
- **Semantic:** Bug `#EF4444` · Task `#3B82F6` · Story `#10B981` · Epic `#8B5CF6`

## Typography
- **Headings:** Inter — sizes: 24/20/18/16px
- **Body:** Inter — 14px / 1.5
- **Arabic:** Noto Sans Arabic — for Arabic labels
- **Monospace:** JetBrains Mono — for code diff, PR titles, commit messages

## Spacing
- Base unit: 4px
- Padding: 12/16/20/24px
- Border radius: 8px (cards), 6px (buttons), 4px (code blocks)
- Grid: 12-column, gutter 16px

## Iconography
- Style: Outline (1.5px stroke)
- Library: Lucide Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-#0D9488, white text, 6px radius, 14px font | hover: #0F766E / active: #115E59 / disabled: opacity 0.5 |
| KanbanColumn | bg #F0FDFA, min-width 240px, 8px radius | drag-over: bg #D1FAE5 + border #0D9488 dashed |
| TicketCard | white bg, left 4px border by type | dragging: shadow-lg + rotate 2deg / blocked: bg #FEF2F2 |
| DiffView | bg #F8FAFC, monospace 13px | added line: bg #D1FAE5 / removed: bg #FEE2E2 |
| CommentLine | inline in diff | default, resolved (grey), has-reply (indent) |
| BurndownChart | svg, ideal= dashed line, actual= solid | behind: red fill, ahead: green |
| PriorityBadge | urgent/high/medium/low | pill shape, icon+text |
| SprintHeader | name+dates+progress | active: green dot / completed: check / future: clock |
