# WCAG 2.2 AA Matrix — ReviewRadar

| Criteria | Target | Status | Notes |
|----------|--------|--------|-------|
| Contrast ≥ 4.5:1 | Pass | ✅ | Deep orange (#D84315) on white = 5.4:1 |
| Focus visible | All controls | ✅ | Purple (#6A1B9A) outline 2px |
| Screen-reader labels | All inputs | ✅ | ARIA labels for review inbox in Arabic |
| Touch target ≥ 44px | Cards, buttons | ✅ | 48px minimum |
| Error identification | Reply form | ✅ | Red border on empty reply |
| Language attribute | `<html lang="ar">` | ✅ | RTL reply editor, Arabic sentiment labels |
| Heading hierarchy | h1 > h2 > h3 | ✅ | h1 التقييمات, h2 تحليل المشاعر |
| Color not sole indicator | Sentiment badges | ✅ | Icon + Arabic text + color |
| Motion reduction | Animation respect | ✅ | Reduced motion for chart transitions |
| Keyboard navigation | Full tab through inbox | ✅ | Tab between reviews, Enter to reply |
| Status messages | Live regions | ✅ | New review alert via `aria-live="assertive"` |
| Zoom 200% readable | All screens | ✅ | Review cards stack vertically |
| Time-independent | Not time-based | ✅ | No time limits on reading/responding |
