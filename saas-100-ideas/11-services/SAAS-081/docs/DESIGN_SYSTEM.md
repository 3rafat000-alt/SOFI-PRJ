# Design System — CemeteryMgt (SAAS-081)
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** CemeteryMgt — مقابر (Arabic for cemeteries)
- **Logo concept:** Stylized gate arch + tree silhouette, sans-serif Arabic/English wordmark
- **Brand personality:** Respectful, Dignified, Calm, Organized, Trustworthy

## Color Palette
- **Primary:** `#475569` — Slate-600 (headers, primary buttons, navigation)
- **Secondary:** `#64748B` — Slate-500 (secondary elements, hover states)
- **Accent:** `#3B82F6` — Blue-500 (links, active states, highlights)
- **Background:** `#F8FAFC` — Slate-50 (page background)
- **Surface:** `#FFFFFF` — White (cards, modals, inputs)
- **Neutral:** `#94A3B8` — Slate-400 (muted text, borders, placeholders)
- **Semantic:** Success `#22C55E` · Warning `#EAB308` · Error `#EF4444` · Info `#3B82F6`

## Typography
- **Headings:** Inter — sizes: 24/20/18/16px, semibold
- **Body:** Inter — 14px regular, line-height 1.6
- **Arabic:** Noto Sans Arabic — support all weights, same size scale
- **Monospace:** JetBrains Mono — 13px for data/codes

## Spacing
- Base unit: 4px
- Padding: 16/24/32/48px
- Gap: 8/12/16/24px
- Border radius: 8px (cards), 6px (inputs), 999px (badges)
- Shadow: sm (cards), md (modals), lg (dropdowns)

## Iconography
- Style: Outline 2px stroke
- Library: Lucide Icons
- Size: 20px (inline), 24px (buttons), 32px (illustrations)

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-slate-600, white text, 8px radius | hover:bg-slate-700 / active:scale-98 / disabled:opacity-50 / loading:spinner |
| Button Secondary | border-slate-300, text-slate-700 | hover:bg-slate-50 / active:bg-slate-100 |
| Input Field | border 1px #CBD5E1, 12px padding, 6px radius | focus:ring-2 ring-blue-500 / error:border-red-500 / disabled:bg-slate-100 |
| Card | bg-white, shadow-sm, 12px radius | hover:shadow-md, transition 200ms |
| Badge | 14px, 999px radius, 4px padding-x | slate/blue/green/yellow/red variants |
| Map Pin | 32px circle with shadow | default/selected (ring) / hover (scale-110) |
| Modal | 480px max-w, backdrop blur-sm | open:animate-fadeIn, close:animate-fadeOut |
