# Design System — PrintHub (SAAS-084)
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** PrintHub — مطبعة (Arabic for print shop)
- **Logo concept:** Stylized printer roller + paper sheet, bold coral wordmark
- **Brand personality:** Creative, Fast, Precise, Modern, Reliable

## Color Palette
- **Primary:** `#E11D48` — Rose-600 (headers, primary buttons, accent)
- **Secondary:** `#4338CA` — Indigo-700 (secondary, links, info)
- **Accent:** `#F97316` — Orange-500 (highlights, special offers)
- **Background:** `#F8FAFC` — Slate-50 (page background)
- **Surface:** `#FFFFFF` — White (cards, modals)
- **Neutral:** `#64748B` — Slate-500 (muted text, borders)
- **Semantic:** Success `#22C55E` · Warning `#EAB308` · Error `#EF4444` · Info `#4338CA`

## Typography
- **Headings:** Plus Jakarta Sans — sizes: 24/20/18/16px, bold
- **Body:** Inter — 14px regular, line-height 1.6
- **Arabic:** Noto Sans Arabic — full support
- **Monospace:** JetBrains Mono — 13px (order numbers, file names)

## Spacing
- Base unit: 4px
- Padding: 16/24/32/48px
- Gap: 8/12/16/24px
- Border radius: 8px (cards), 6px (inputs), 4px (buttons)
- Shadow: sm, md, lg

## Iconography
- Style: Outline 2px stroke
- Library: Lucide Icons
- Size: 20/24/32px

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-rose-600, white text, 8px radius | hover:bg-rose-700 / active:scale-98 / disabled:opacity-50 |
| Button Secondary | border-rose-300, text-rose-700 | hover:bg-rose-50 |
| KanbanColumn | bg-slate-50, dashed drop-zone | drag-over:border-indigo-400 bg-indigo-50 |
| OrderCard | bg-white, border-l-4 colored | pending:yellow / design:blue / production:indigo / delivery:orange / done:green |
| PdfViewer | bg-slate-100, shadow | zoom:scale / fullscreen |
| Stepper | Horizontal circles + labels | completed:green / active:indigo / pending:slate |
| FileUploader | Dashed border 2px | drag:border-indigo-500 / uploading:progress / error:border-red |
