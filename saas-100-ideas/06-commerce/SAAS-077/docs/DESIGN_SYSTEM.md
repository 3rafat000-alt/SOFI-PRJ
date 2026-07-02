# Design System — TobaccoShop
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** TobaccoShop — إدارة محلات الدخان
- **Logo concept:** Shield + leaf icon (neutral professional identity)
- **Brand personality:** Professional, Discrete, Efficient, Reliable, Compliant

## Color Palette
- **Primary:** `#78350F` — Warm Brown (headers, nav, primary UI)
- **Secondary:** `#65A30D` — Olive (accents, success indicators, fresh stock)
- **Accent:** `#DC2626` — Red (age verification, warnings, critical alerts)
- **Neutral:** `#FFFBEB` — Cream (backgrounds), `#292524` — dark brown (text), `#A8A29E` — warm grey (labels)
- **Semantic:** Success `#16A34A` · Warning `#CA8A04` · Error `#DC2626`

## Typography
- **Headings:** Cairo (Google Font) — geometric Arabic — sizes: 26/22/18/16px
- **Body:** Cairo — 14px (dashboard), 16px (POS, for quick reading)
- **Arabic:** Cairo — clear readable Arabic
- **Numbers:** Tabular (for prices, quantities, tax)

## Spacing
- Base unit: 4px
- Padding: 12/16/24px (tight for POS efficiency)
- Border radius: 8px (cards), 4px (inputs), 6px (buttons)
- Max content width: 1200px

## Iconography
- Style: Outline, 24px (POS), 20px (dense screens)
- Library: Lucide Icons (package, barcode, truck, shield, receipt)

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-brown-800 #78350F, white text, 6px radius | hover: #451A03 / active / disabled |
| Button POS | Large 56px height, olive #65A30D bg | hover: #4D7C0F |
| BarcodeScanner | Full-width camera, guide overlay | idle/scanning/found/not-found |
| AgeVerifyScreen | Red overlay "تحقق من العمر" | pending/verified/rejected |
| ProductRow | 14px, list layout, border-b | low-stock: yellow bg / expiring: orange bg |
| InvoiceLine | Name + qty + price + tax | subtotal/total |
