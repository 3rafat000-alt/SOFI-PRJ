# WCAG 2.2 AA Matrix — AssetGuard

| Criteria | Target | Status | Notes |
|----------|--------|--------|-------|
| Contrast ≥ 4.5:1 | Pass | ✅ | Blue (#1565C0) on white = 5.8:1 |
| Focus visible | All controls | ✅ | Yellow (#F57F17) outline 2px |
| Screen-reader labels | All inputs | ✅ | ARIA labels in Arabic for asset form |
| Touch target ≥ 44px | Buttons, QR scan | ✅ | 48px scan button |
| Error identification | Form validation | ✅ | Red border on missing serial number |
| Language attribute | `<html lang="ar">` | ✅ | RTL asset table, Arabic status labels |
| Heading hierarchy | h1 > h2 > h3 | ✅ | h1 الأصول, h2 تفاصيل الأصل |
| Color not sole indicator | Status badges | ✅ | Icon + text (جيد/تالف/مفقود) + color |
| Motion reduction | Animation respect | ✅ | Reduced motion for scan animation |
| Keyboard navigation | Full tab flow | ✅ | Tab through asset list, Enter to view |
| Status messages | Live regions | ✅ | QR scan result via `aria-live="polite"` |
| Zoom 200% readable | All screens | ✅ | Asset table scrollable |
| Camera access | Permission required | ✅ | Clear prompt + fallback manual entry |
