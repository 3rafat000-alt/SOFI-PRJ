# Design System — HoneyFarm (SAAS-082)
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** HoneyFarm — منحل (Arabic for apiary)
- **Logo concept:** Hexagonal honeycomb + bee silhouette, golden amber wordmark
- **Brand personality:** Natural, Warm, Pure, Trustworthy, Traditional

## Color Palette
- **Primary:** `#B45309` — Amber-700 (headers, buttons, brand elements)
- **Secondary:** `#D97706` — Amber-600 (secondary buttons, hover)
- **Accent:** `#16A34A` — Green-600 (success, health indicators, growth)
- **Background:** `#FFFBEB` — Amber-50 (warm page background)
- **Surface:** `#FFFFFF` — White (cards, modals)
- **Neutral:** `#78716C` — Stone-500 (muted text, borders)
- **Semantic:** Success `#16A34A` · Warning `#EAB308` · Error `#DC2626` · Info `#2563EB`

## Typography
- **Headings:** Playfair Display — sizes: 24/20/18/16px, semibold
- **Body:** Inter — 14px regular, line-height 1.6
- **Arabic:** Noto Sans Arabic — all weights
- **Monospace:** JetBrains Mono — 13px

## Spacing
- Base unit: 4px
- Padding: 16/24/32/48px
- Gap: 8/12/16/24px
- Border radius: 8px (cards), 6px (inputs), 999px (badges)
- Shadow: sm (cards), md (modals)

## Iconography
- Style: Outline 2px stroke
- Library: Lucide Icons
- Size: 20/24/32px

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-amber-700, white text, 8px radius | hover:bg-amber-800 / active:scale-98 / disabled:opacity-50 |
| Button Secondary | border-amber-300, text-amber-700 | hover:bg-amber-50 |
| Input Field | border #D6D3D1, 12px padding, 6px radius | focus:ring-2 ring-amber-500 |
| Card | bg-white, shadow-sm, 12px radius | hover:shadow-md |
| Badge | 14px, 999px | amber/green/red variants |
| Health Indicator | Circle dot: green/amber/red | pulse animation for critical |
| Sync Indicator | Online/Syncing/Offline | animated dots when syncing |
