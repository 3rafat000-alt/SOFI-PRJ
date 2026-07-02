# Design System — DocuSign Pro
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** DocuSign Pro — توقيع المستندات المحترف
- **Logo concept:** قلم توقيع أزرق بخط منحني مع أيقونة مستند أخضر
- **Brand personality:** آمن، احترافي، سريع، رسمي، جدير بالثقة

## Color Palette
- **Primary:** `#1565C0` — أزرق ثقة (أزرار، رأس الصفحة، شعار)
- **Secondary:** `#00897B` — أخضر مزرق (توقيع مكتمل، نجاح، تأكيد)
- **Accent:** `#FF8F00` — كهرماني (تحذيرات، أرشفة، تمايز)
- **Neutral:** `#F5F5F5` (خلفيات) `#757575` (نص ثانوي) `#212121` (نص أساسي)
- **Envelope Status:** Draft `#757575` · Sent `#1565C0` · Viewed `#00897B` · Signed `#43A047` · Declined `#E53935` · Expired `#F9A825`
- **Field Types:** Signature `#1565C0` · Initial `#00897B` · Date `#FF8F00` · Text `#7B1FA2`

## Typography
- **Headings:** Inter — sizes: 24/20/18/16px (600 weight)
- **Body:** Inter — 14px (400 weight)
- **Arabic:** Noto Sans Arabic — RTL signing UI, Arabic contract support
- **Document text:** as-is in original font (embedded PDF)

## Spacing
- Base unit: 8px
- Padding: 16/24/32px
- Border radius: 6px (buttons), 8px (cards), 0px (document canvas)
- Document canvas: full width, max 800px

## Iconography
- Style: Outline
- Library: Lucide
- Key icons: FileSignature, FileText, Send, CheckCircle, Clock, Download, Shield

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-primary, white text, 6px radius, 44px | hover: darken 10%, active: scale(0.97), loading: skeleton bar |
| DropZone | dashed border 2px, 200px min height, icon center | empty: dashed gray, drag-over: dashed primary + bg-blue-50, uploaded: thumbnail + name |
| DocumentCanvas | white bg, shadow inner, scrollable | zoom buttons top-right, field overlay on hover |
| FieldToolbar | horizontal icon bar, drag source | icon + label, dragged: clone follows cursor |
| SignaturePad | white canvas, 300x150px, drawing tools | empty: "وقع هنا" watermark, drawing: ink stroke, confirmed: lock icon |
| SigningTimeline | horizontal steps with connector lines | pending: gray circle, signed: green circle with ✓, declined: red circle with ✕, current: blue pulsing |
| EnvelopeCard | status left, info middle, actions right | hover: shadow, status color bar top |
