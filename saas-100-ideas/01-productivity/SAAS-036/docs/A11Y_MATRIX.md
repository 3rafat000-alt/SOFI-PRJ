# WCAG 2.2 AA Matrix — NoteSpace
| Criteria | Target | Status | Notes |
|----------|--------|--------|-------|
| Contrast ≥ 4.5:1 | Pass | ✅ | Teal `#0D9488` on white = 5.4:1 |
| Focus visible | Editor + nav | ✅ | 2px teal outline in editor |
| Screen-reader labels | Editor buttons | ✅ | aria-label for formatting buttons |
| Keyboard nav | Full editing | ✅ | Tab through toolbar, arrow nav in tree |
| Touch target ≥ 44px | Mobile editor | ✅ | Toolbar buttons 44×44px |
| ARIA landmarks | All pages | ✅ | header, main, nav, complementary |
| Live region | Save status | ✅ | aria-live="polite" for "Saving..." |
| RTL support | Arabic content | ✅ | dir="auto" per note |
| Focus management | Modal dialogs | ✅ | Trap focus in modal, return on close |
| Error identification | Save failure | ✅ | Toast with retry action |
