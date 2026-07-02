# WCAG 2.2 AA Matrix — LegalConsult
| Criteria | Target | Status | Notes |
|----------|--------|--------|-------|
| Contrast ≥ 4.5:1 (text) | Pass | ✅ | Navy #1E3A5F on white passes |
| Contrast ≥ 3:1 (large text) | Pass | ✅ | Gold #C8912E on navy passes |
| Focus visible | All interactive | ✅ | 2px navy ring + 4px offset |
| Screen-reader labels | All inputs | ✅ | Arabic ARIA labels |
| Heading hierarchy | h1→h6 sequential | ✅ | Skip one level = error |
| Touch target ≥ 44px | All buttons/links | ✅ | 48px minimum |
| Error identification | Inline + summary | ✅ | Red text + icon + top banner |
| Error suggestions | All inputs | ✅ | "أدخل رقماً صحيحاً" guidance |
| Landmarks | header, nav, main, footer | ✅ | ARIA landmarks in Arabic |
| Keyboard navigation | All screens | ✅ | Tab order = visual order |
| Skip to content | Global | ✅ | "تخطى إلى المحتوى الرئيسي" |
| Resize 200% | Responsive | ✅ | No horizontal scroll |
| Motion reduced | Optional | ⚠️ | Plan: respect prefers-reduced-motion |
| Focus order | Meaningful | ✅ | Logical RTL tab sequence |
| Link purpose | Context-aware | ✅ | aria-label for icon-only links |
| Language attribute | html lang="ar" | ✅ | Also support English |
| Session timeout | 30min warning | ✅ | 2min extension offered |
| CAPTCHA alternative | Audio + logic | ⚠️ | Plan: hCaptcha accessible |
