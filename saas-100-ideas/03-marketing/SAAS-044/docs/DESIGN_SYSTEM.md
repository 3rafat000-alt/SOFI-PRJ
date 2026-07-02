# Design System — ChatFlow
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name:** ChatFlow — تدفق المحادثات
- **Logo concept:** فقاعة حوار مع 3 نقاط (typing) وخط منحني يمثل التدفق
- **Brand personality:** ودود، سريع، ذكي، عصري، سهل

## Color Palette
- **Primary:** `#2563EB` — أزرق زاهي للأزرار والروابط
- **Secondary:** `#7C3AED` — بنفسجي للبوت (لتمييز ردود البوت)
- **Accent:** `#F59E0B` — ذهبي للشارات والإنجازات
- **Neutral:** `#F8FAFC` خلفية، `#0F172A` نص رئيسي، `#64748B` نص ثانوي
- **Semantic:** Success `#10B981` · Warning `#F59E0B` · Error `#EF4444` · Online `#22C55E`

## Typography
- **Headings:** Plus Jakarta Sans — sizes: 24/20/18/16px
- **Body:** Inter — 14px / 1.6
- **Arabic:** Noto Sans Arabic — for Arabic chat messages
- **Mono:** JetBrains Mono — for code snippets in chat

## Spacing
- Base unit: 4px
- Padding: 12/16/24px
- Border radius: 16px (chat bubbles), 8px (cards), 6px (inputs)
- Max widget width: 380px (desktop), 100% (mobile)

## Iconography
- Style: Outline (2px stroke)
- Library: Lucide Icons

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| ChatBubble | bg-white, shadow-lg, 16px radius, floating | hover: shadow-xl / open: panel |
| Bot Message | bg-#F5F3FF, text #4C1D95, 12px radius | - |
| Agent Message | bg-#2563EB, white text, 12px radius | - |
| Customer Message | bg-#F1F5F9, text #0F172A, 12px radius | - |
| QuickReply | bg-white, border 1px #E2E8F0, 8px radius, 14px | hover: border #2563EB / selected: bg #EFF6FF |
| Input | border 1px #CBD5E1, 12px padding, 8px radius | focus: ring #2563EB / disabled: bg #F1F5F9 |
| TransferButton | outline #2563EB | hover: bg #EFF6FF |
