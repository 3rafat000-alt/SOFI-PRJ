# WCAG 2.2 AA Matrix — ParkingIQ

| Criteria | Target | Status | Notes |
|----------|--------|--------|-------|
| Contrast ≥ 4.5:1 | Pass | ✅ | Primary blue (#1565C0) on white = 5.8:1 |
| Focus visible | All interactive elements | ✅ | 2px solid outline, focus ring #F9A825 |
| Screen-reader labels | All inputs | ✅ | ARIA labels in Arabic + English |
| Touch target ≥ 44px | Buttons, pins, nav | ✅ | All tappable areas ≥ 48px |
| Error identification | Form validation | ✅ | Red border + Arabic error text below field |
| Language attribute | `<html lang="ar">` | ✅ | RTL layout, Arabic lang |
| Heading hierarchy | h1 > h2 > h3 | ✅ | Map screen: h1 مواقف قريبة, h2 اسم الموقف, etc. |
| Color not sole indicator | Status dots | ✅ | Text label + icon beside color |
| Motion reduction | Animation respect | ✅ | `prefers-reduced-motion` media query |
| Keyboard navigation | Full tab flow | ✅ | Tab through map pins, enter to select |
| Captions (media) | N/A | ✅ | No instructional video in MVP |
| Zoom 200% readable | All screens | ✅ | Responsive layout, no horizontal scroll |
| Status messages | Live regions | ✅ | Booking confirmation: `aria-live="polite"` |
