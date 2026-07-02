# Design System — BankMicro
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** BankMicro — بنك المصغر
- **Logo concept:** Piggy bank + coin stack + shielding hand icon
- **Brand personality:** Trustworthy, Inclusive, Modern, Stable, Empowering

## Color Palette
- **Primary:** `#C8912E` — Gold (brand identity, CTA, premium indicators)
- **Secondary:** `#1E3A5F` — Navy (headers, nav, primary text)
- **Accent:** `#E5B94B` — Light Gold (hover states, highlights)
- **Neutral:** `#F8FAFC` — Light grey (backgrounds), `#334155` — slate (text), `#94A3B8` — grey (labels)
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#EF4444`

## Typography
- **Headings:** Noto Sans Arabic (Google Font) — professional — sizes: 28/24/20/18px
- **Body:** Noto Sans Arabic — 14px (app), 13px (dashboard)
- **Arabic:** Noto Sans Arabic — full Arabic + financial terms
- **Numbers:** Tabular / monospace (balances, account numbers, amounts)

## Spacing
- Base unit: 4px
- Padding: 16/24/32px
- Border radius: 12px (cards), 8px (buttons), 6px (inputs)
- Max content width: 1100px

## Iconography
- Style: Outline, 24px
- Library: Lucide Icons (wallet, bank, qr-code, users, arrow-left-right)

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-gold-600 #C8912E, white text, 8px radius | hover: #B37A22 / active: #9C661A / disabled |
| Button Secondary | bg-navy-800 #1E3A5F, white text | hover: #152D4A / active |
| BalanceCard | White bg, shadow, 12px radius, 28px balance amount | hidden: "••••" / visible: amount |
| TransactionRow | Icon + description + amount + date | incoming: green arrow / outgoing: red / pending: amber |
| QrScanner | Full-screen camera | idle/scanning/found/paid |
| JameyaCard | Progress bar + cycle indicator | active/completed |
| KYCStep | Document upload with guide | pending/verified/rejected |
