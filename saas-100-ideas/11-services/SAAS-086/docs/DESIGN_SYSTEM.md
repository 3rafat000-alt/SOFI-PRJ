# Design System — CableTV (SAAS-086)
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** CableTV — كيبل (Arabic for cable)
- **Logo concept:** Stylised cable wave + TV screen, blue wordmark
- **Brand personality:** Connected, Fast, Reliable, Modern, Clear

## Color Palette
- **Primary:** `#2563EB` — Blue-600 (headers, buttons, brand)
- **Secondary:** `#475569` — Slate-600 (secondary elements, footer)
- **Accent:** `#06B6D4` — Cyan-500 (highlights, special offers)
- **Background:** `#F8FAFC` — Slate-50 (page background)
- **Surface:** `#FFFFFF` — White (cards, modals)
- **Neutral:** `#94A3B8` — Slate-400 (muted text, borders)
- **Semantic:** Success `#22C55E` · Warning `#EAB308` · Error `#EF4444` · Info `#06B6D4`

## Typography
- **Headings:** Inter — sizes: 24/20/18/16px, semibold
- **Body:** Inter — 14px regular, line-height 1.6
- **Arabic:** Noto Sans Arabic — full support
- **Monospace:** JetBrains Mono — 13px (serial numbers, IPs)

## Spacing
- Base unit: 4px
- Padding: 16/24/32/48px
- Gap: 8/12/16/24px
- Border radius: 8px (cards), 6px (inputs), 999px (badges)
- Shadow: sm, md, lg

## Iconography
- Style: Outline 2px stroke
- Library: Lucide Icons
- Size: 20/24/32px

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-blue-600, white text, 8px radius | hover:bg-blue-700 / active:scale-98 / disabled:opacity-50 |
| Button Secondary | border-blue-300, text-blue-700 | hover:bg-blue-50 |
| KpiCard | bg-white, shadow-sm | default/hover:shadow-md |
| TicketTable | border-collaPSed rows | hover:bg-slate-50 / selected:bg-blue-50 |
| PriorityBadge | 14px rounded pill | critical:red / high:orange / med:yellow / low:green |
| SLA Timer | Countdown text | safe:slate / warning:orange / breached:red pulse |
| OutageMap | Mini map with affected area | normal/outage-highlighted |
| TaskCard | White card with left border | pending:blue-border / done:green-border |
