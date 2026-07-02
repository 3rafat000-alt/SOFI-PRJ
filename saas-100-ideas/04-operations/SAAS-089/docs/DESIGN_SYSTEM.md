# Design System — SpareParts (SAAS-089)
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** SpareParts — قطع غيار (Arabic for spare parts)
- **Logo concept:** Stylised gear + car silhouette, industrial orange wordmark
- **Brand personality:** Industrial, Tough, Reliable, Efficient, Professional

## Color Palette
- **Primary:** `#EA580C` — Orange-600 (headers, buttons, brand)
- **Secondary:** `#475569` — Slate-600 (secondary, nav, footer)
- **Accent:** `#F97316` — Orange-500 (highlights, sales badges)
- **Background:** `#F8FAFC` — Slate-50 (page background)
- **Surface:** `#FFFFFF` — White (cards, modals)
- **Neutral:** `#64748B` — Slate-500 (muted text, borders)
- **Semantic:** Success `#22C55E` · Warning `#EAB308` · Error `#EF4444` · Info `#3B82F6`

## Typography
- **Headings:** Plus Jakarta Sans — sizes: 24/20/18/16px, bold
- **Body:** Inter — 14px regular, line-height 1.6
- **Arabic:** Noto Sans Arabic — full support
- **Monospace:** JetBrains Mono — 13px (part numbers, OEM codes)

## Spacing
- Base unit: 4px
- Padding: 16/24/32/48px
- Gap: 8/12/16/24px
- Border radius: 6px (industrial), 8px (cards), 4px (inputs)
- Shadow: sm, md, lg

## Iconography
- Style: Outline 2px stroke
- Library: Lucide Icons
- Size: 20/24/32px (search bar 24px)

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-orange-600, white text, 6px radius | hover:bg-orange-700 / active:scale-98 / disabled:opacity-50 |
| Button Secondary | border-slate-300, text-slate-700 | hover:bg-slate-50 |
| SearchToggle | Tab buttons: "رقم القطعة" / "بالمركبة" | active:bg-orange-600 / inactive:bg-white |
| VehicleSelector | 4-step stepper | step:orange / completed:green / pending:slate |
| PartCard | Border-left stock color | in-stock:green / low:orange / out:red |
| BarcodeScanner | Full overlay | scanning:blue / found:green / error:red |
| CartItemRow | Horizontal with delete | default/low-stock:orange side |
| PriceComparison | Table with highlighted best | cheapest row:green check |
| StatusTracker | Horizontal pipeline | ordered:slate / shipped:blue / received:green |
