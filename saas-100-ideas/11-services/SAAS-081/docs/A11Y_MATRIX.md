# WCAG 2.2 AA Matrix — CemeteryMgt (SAAS-081)
| Criteria | Target | Status | Notes |
|----------|--------|--------|-------|
| Contrast ≥ 4.5:1 | Pass | ✅ | Slate #475569 on white #FFFFFF = 7.2:1 |
| Focus visible | Pass | ✅ | 3px outline-offset:2px on all interactive |
| Screen-reader labels | All inputs | ✅ | Arabic + English aria-labels |
| Touch target ≥ 44×44px | All tappable | ✅ | Buttons 48px min, links 44px |
| Error identification | Inline + summary | ✅ | Red border + icon + text below field |
| Link purpose in context | Descriptive | ✅ | "افتح خريطة المقبرة" not "اضغط هنا" |
| Headings hierarchy | h1→h6 logical | ✅ | Skip nav: h1 per page |
| Language attribute | ar + en | ✅ | lang="ar" dir="rtl" / lang="en" dir="ltr" |
| Keyboard navigation | Full tab order | ✅ | Tab through map pins, Enter to select |
| Focus trap in modals | Loop focus | ✅ | Tab cycles within modal, ESC to close |
| Motion reduction | Reduced animation | ✅ | prefers-reduced-motion: no animation |
| Zoom 200% | Readable | ✅ | Fluid layout, no horizontal scroll |
| Status messages | role="status" | ✅ | Toasts use aria-live="polite" |
| Target spacing | ≥24px cursor gap | ✅ | Adjacent clickable elements spaced |
