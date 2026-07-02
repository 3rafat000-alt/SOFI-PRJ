# WCAG 2.2 AA Matrix — LawyerRef (SAAS-091)
> Owner: Accessibility Specialist · Gate 2

| Criteria | Target | Status | Notes |
|----------|--------|--------|-------|
| Contrast ≥ 4.5:1 | Pass | ✅ | Navy (#1B2A4A) on white = 12.5:1; Gold (#C9A84C) on navy = 5.2:1 |
| Focus visible | All interactive | ✅ | 3px gold outline on focus |
| Screen-reader labels | All inputs, icons, images | ✅ | Arabic ARIA labels + English fallback |
| Touch target ≥ 44px | All buttons, links | ✅ | Min 48px (exceeds) |
| Error identification | Form fields | ✅ | Error text + icon + aria-describedby |
| Language attribute | `lang="ar"` | ✅ | RTL + proper Arabic glyph rendering |
| Heading hierarchy | h1→h6 sequential | ✅ | No skipped levels |
| Keyboard navigation | All flows | ✅ | Tab order = visual order |
| Motion reduction | Animations | ✅ | prefers-reduced-motion respected |
| Screen reader announcements | Dynamic content | ✅ | aria-live regions for alerts |
| Captions (video) | Consultation recordings | ✅ | YouTube/Whisper auto-caption |
| Text resize 200% | No loss of function | ✅ | Responsive layout, no horizontal scroll |
