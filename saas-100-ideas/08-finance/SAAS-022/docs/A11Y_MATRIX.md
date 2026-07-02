# WCAG 2.2 AA Matrix — InvoiceFlow

| Criteria | Target | Status | Notes |
|----------|--------|--------|-------|
| Contrast ≥ 4.5:1 | Pass | ✅ | Navy (#1B2A4A) on white = 8.1:1 |
| Focus visible | All inputs | ✅ | Focus ring #C5A55A (gold) |
| Screen-reader labels | All fields | ✅ | ARIA labels for invoice form in Arabic |
| Touch target ≥ 44px | Buttons, cells | ✅ | 48px minimum |
| Error identification | Invoice validation | ✅ | Field-level error + summary alert |
| Language attribute | `<html lang="ar">` | ✅ | RTL forms, Arabic currency format |
| Heading hierarchy | h1 > h2 > h3 | ✅ | Dashboard h1 الفواتير, section h2 ملخص |
| Color not sole indicator | Status badges | ✅ | Badge text + icon + color |
| Motion reduction | Animation respect | ✅ | Reduced motion for chart animations |
| Keyboard navigation | Full tab through form | ✅ | Tab through invoice items, add new row with Tab |
| Captions (media) | N/A | ✅ | No video |
| Zoom 200% readable | All screens | ✅ | Table horizontal scroll at 200% |
| Status messages | Toast notifications | ✅ | `role="status"` for invoice sent/paid |
