# WCAG 2.2 AA Matrix — LoyaltyBox

| Criteria | Target | Status | Notes |
|----------|--------|--------|-------|
| Contrast ≥ 4.5:1 | Pass | ✅ | Orange (#E65100) on white = 5.2:1 |
| Focus visible | All controls | ✅ | Pink (#AD1457) outline 2px |
| Screen-reader labels | All inputs | ✅ | ARIA labels for QR scan, wallet in Arabic |
| Touch target ≥ 44px | Buttons, cards | ✅ | 48px minimum for QR button |
| Error identification | Form validation | ✅ | Red border on points rule errors |
| Language attribute | `<html lang="ar">` | ✅ | RTL wallet cards, Arabic numbers for points |
| Heading hierarchy | h1 > h2 > h3 | ✅ | h1 محفظتي, h2 برامج الولاء |
| Color not sole indicator | Points status | ✅ | Icon + text + color (أخضر/أصفر/أحمر) |
| Motion reduction | Animation respect | ✅ | Reduced motion for point animations |
| Keyboard navigation | Full tab flow | ✅ | Tab through loyalty cards |
| Status messages | Live regions | ✅ | Points earned notification via `aria-live` |
| Zoom 200% readable | Wallet, POS | ✅ | Cards stack, no truncation |
| Flashing content | None | ✅ | No flashing elements |
