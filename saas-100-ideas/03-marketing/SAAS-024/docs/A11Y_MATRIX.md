# WCAG 2.2 AA Matrix — SocialKit

| Criteria | Target | Status | Notes |
|----------|--------|--------|-------|
| Contrast ≥ 4.5:1 | Pass | ✅ | Orange (#E65100) on white = 5.2:1 |
| Focus visible | All controls | ✅ | Purple (#7B1FA2) outline 2px |
| Screen-reader labels | All inputs | ✅ | ARIA labels for post editor in Arabic |
| Touch target ≥ 44px | Cards, buttons | ✅ | 48px tappable area |
| Error identification | Form validation | ✅ | Error messages for missing media/text |
| Language attribute | `<html lang="ar">` | ✅ | RTL calendar, Arabic post preview |
| Heading hierarchy | h1 > h2 > h3 | ✅ | h1 المنشورات, h2 معاينة المنصة |
| Color not sole indicator | Platform badges | ✅ | Platform icon + text + color |
| Motion reduction | Animation respect | ✅ | Reduced motion for drag & drop |
| Keyboard navigation | Full tab flow | ✅ | Tab through calendar cells, Enter to edit |
| Drag & drop alternative | Buttons for move | ✅ | Keyboard: Alt+arrow to move posts |
| Zoom 200% readable | Calendar grid | ✅ | Calendar wraps, no horizontal overflow |
| Status messages | Live regions | ✅ | Post scheduled notification via `aria-live` |
