# Design System — RealtyCRM
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** Realty (عقارات) + CRM (إدارة علاقات العملاء) — CRM عقاري
- **Logo concept:** مبنى + أيقونة اتصال، بألوان ترابية غنية
- **Brand personality:** محترف، ثري، موثوق، عصري، فاخر

## Color Palette
- **Primary:** `#5D4037` — Brown (earth/real estate/trust)
- **Secondary:** `#F9A825` — Gold (value/luxury/premium)
- **Accent:** `#1565C0` — Blue (tech/professionalism/CRM)
- **Neutral:** `#FAFAFA` background, `#212121` text
- **Semantic:** Success `#2E7D32` · Warning `#F57F17` · Error `#C62828`

## Typography
- **Headings:** Noto Sans Arabic — sizes: 28/24/20/18px
- **Body:** Noto Sans Arabic — 14px
- **Arabic:** Noto Sans Arabic
- **Numbers:** Tabular for property prices/areas

## Spacing
- Base unit: 8px
- Padding: 16/24/32/48px
- Border radius: 8px (cards), 4px (inputs)

## Iconography
- Style: Outline (professional, clean)
- Library: Lucide Icons + custom property icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-primary, white text, 8px radius | hover: darken, active, disabled |
| Button Secondary | bg-secondary, dark text | hover: lighten |
| Input Field | border 1px #BDBDBD, 8px radius | focus: border-primary, error |
| Card | white, border, 8px radius | hover: shadow |
| KanbanLead | draggable card | by stage color |
| PropertyBadge | pill | draft, published, rented, sold |
| SignaturePad | white, border | empty, signed, verified |
