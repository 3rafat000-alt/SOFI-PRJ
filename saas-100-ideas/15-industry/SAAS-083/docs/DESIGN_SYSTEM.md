# Design System — TextilePro (SAAS-083)
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** TextilePro — نسيج (Arabic for textile)
- **Logo concept:** Stylized loom shuttle + fabric wave, bold indigo wordmark
- **Brand personality:** Industrial, Precise, Reliable, Professional, Strong

## Color Palette
- **Primary:** `#4338CA` — Indigo-700 (headers, primary buttons, navigation)
- **Secondary:** `#6366F1` — Indigo-500 (secondary elements, hover)
- **Accent:** `#F43F5E` — Rose-500 (alerts, warnings, highlights)
- **Background:** `#F8FAFC` — Slate-50 (page background)
- **Surface:** `#FFFFFF` — White (cards, modals)
- **Neutral:** `#64748B` — Slate-500 (muted text, borders)
- **Semantic:** Success `#22C55E` · Warning `#EAB308` · Error `#EF4444` · Info `#6366F1`

## Typography
- **Headings:** Plus Jakarta Sans — sizes: 24/20/18/16px, bold
- **Body:** Inter — 14px regular, line-height 1.6
- **Arabic:** Noto Sans Arabic — full support
- **Monospace:** JetBrains Mono — 13px (machine codes, batch numbers)

## Spacing
- Base unit: 4px
- Padding: 16/24/32/48px
- Gap: 8/12/16/24px
- Border radius: 6px (industrial), 8px (cards), 4px (inputs)
- Shadow: sm, md, lg

## Iconography
- Style: Outline 2px stroke
- Library: Lucide Icons
- Size: 20/24/32px

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-indigo-700, white text, 6px radius | hover:bg-indigo-800 / active:scale-98 / disabled:opacity-50 |
| Button Secondary | border-indigo-300, text-indigo-700 | hover:bg-indigo-50 |
| MachineCard | Border-left colored by status | running:green / idle:yellow / breakdown:red |
| GanttBar | Colored by stage | hover:brightness-110 / selected:ring |
| PipelineStage | 4-step connector | completed:green / active:indigo / pending:slate |
| Input | border 1px #CBD5E1, 6px radius | focus:ring-indigo-500 / error:red |
