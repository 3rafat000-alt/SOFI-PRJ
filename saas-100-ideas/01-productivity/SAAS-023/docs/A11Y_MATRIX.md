# WCAG 2.2 AA Matrix — HRTide

| Criteria | Target | Status | Notes |
|----------|--------|--------|-------|
| Contrast ≥ 4.5:1 | Pass | ✅ | Blue (#1976D2) on white = 5.6:1 |
| Focus visible | All controls | ✅ | 2px teal (#0097A7) outline |
| Screen-reader labels | All inputs | ✅ | ARIA labels in Arabic for employee form |
| Touch target ≥ 44px | Buttons, table rows | ✅ | 48px row height |
| Error identification | Form validation | ✅ | Inline errors + red border |
| Language attribute | `<html lang="ar">` | ✅ | RTL tables, Hijri date support |
| Heading hierarchy | h1 > h2 > h3 | ✅ | h1 الموظفين, h2 تفاصيل الموظف |
| Color not sole indicator | Status badges | ✅ | Text + icon + color for leave type |
| Motion reduction | Animation respect | ✅ | Respects prefers-reduced-motion |
| Keyboard navigation | Full tab flow | ✅ | Tab through wizard steps |
| Captions (media) | N/A | ✅ | No instructional video |
| Zoom 200% readable | All screens | ✅ | Responsive org chart, scrollable table |
| Status messages | Live regions | ✅ | Leave approval: `aria-live="assertive"` |
