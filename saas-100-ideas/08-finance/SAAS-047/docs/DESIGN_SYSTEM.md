# Design System — BudgetWave
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name:** BudgetWave — موجة الميزانية
- **Logo concept:** موجة صاعدة بلونين ذهبي وأخضر (نمو + ازدهار)
- **Brand personality:** ذكي، دافئ، محفز، سهل، إيجابي

## Color Palette
- **Primary:** `#B45309` — ذهبي داكن (Gold) للعناوين والأزرار الرئيسية
- **Secondary:** `#047857` — زمردي (Emerald) للعناصر الإيجابية والتوفير
- **Accent:** `#D97706` — كهرماني للتحذيرات والإشعارات
- **Neutral:** `#FFFCF5` خلفية دافئة، `#1C1917` نص رئيسي، `#57534E` نص ثانوي
- **Semantic:** Income `#047857` · Expense `#DC2626` · Savings `#2563EB` · Warning `#D97706`

## Typography
- **Headings:** Hanken Grotesk — sizes: 24/20/18/16px
- **Body:** Inter — 14px / 1.6
- **Arabic:** Noto Sans Arabic — clear for financial terms
- **Numbers:** Tabular figures for aligned amounts

## Spacing
- Base unit: 4px
- Padding: 16/20/24/32px
- Border radius: 12px (cards), 8px (buttons), 6px (inputs)
- Grid: 12-column, gutter 16px

## Iconography
- Style: Outline (1.5px stroke)
- Library: Lucide Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-#B45309, white text, 8px radius | hover: #92400E / active: #78350F / disabled: opacity 0.5 |
| BalanceCard | gradient bg (gold→emerald), white text | pulse on update |
| DoughnutChart | svg circle segments | interactive on hover |
| CategoryGrid | 4-col grid, 12px padding | selected: border #B45309 + bg #FFF7ED |
| BudgetSlider | track bg #E7E5E4, thumb #B45309 | focus ring, dragging state |
| ProgressRing | svg circle, stroke-dasharray | green when >70%, amber 30-70%, red <30% |
| AmountInput | font 24px, bold, right-aligned | focus: border #B45309 |
| FAB | 56px circle, shadow-lg, bg #B45309 | hover: scale(1.05) / active: scale(0.95) |
