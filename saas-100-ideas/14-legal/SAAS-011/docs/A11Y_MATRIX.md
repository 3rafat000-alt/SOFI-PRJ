# WCAG 2.2 AA Matrix — LawDesk
| Criteria | Target | Status | Notes |
|----------|--------|--------|-------|
| 1.1.1 Non-text Content | All icons have `aria-label` | ✅ | Law scales icon labelled "شعار محاماة" |
| 1.3.1 Info & Relationships | Semantic HTML headings hierarchy h1-h6 | ✅ | Case detail uses h1→h2→h3 correctly |
| 1.4.1 Use of Color | Status not conveyed by color alone | ✅ | Text label + icon alongside color badge |
| 1.4.3 Contrast (Minimum) | ≥ 4.5:1 normal text | ✅ | Navy #0A1F3F on white = 14.5:1 |
| 1.4.4 Resize Text | 200% zoom no loss | ✅ | Responsive layout, no horizontal scroll at 200% |
| 1.4.10 Reflow | Single column at 320px | ✅ | Mobile-first grid collapses to stacked |
| 1.4.11 Non-text Contrast | UI components ≥ 3:1 | ✅ | Button borders, input outlines all ≥ 3:1 |
| 1.4.12 Text Spacing | No loss with 0.16x letter-spacing | ✅ | Tested with paragraph overrides |
| 2.1.1 Keyboard | All interactive elements reachable by Tab | ✅ | Forms, buttons, calendar all Tab-navigable |
| 2.1.2 No Keyboard Trap | No trapped focus | ✅ | Modal focus trap with Esc to close |
| 2.2.1 Timing Adjustable | Session timeout warning at 60s | ✅ | Warning banner appears 60s before expiry, extendable |
| 2.4.3 Focus Order | Logical RTL focus sequence | ✅ | Right-to-left Tab order on all pages |
| 2.4.7 Focus Visible | 3px outline on focus | ✅ | Navy ring #0A1F3F on all focusable elements |
| 2.4.11 Focus Not Obscured | Focused element fully visible | ✅ | Sticky headers accounted for |
| 2.5.8 Target Size (Minimum) | Touch targets ≥ 24x24px | ✅ | All buttons/links minimum 44x44px mobile |
| 3.2.1 On Focus | No context change on focus | ✅ | Dropdowns require explicit selection |
| 3.3.1 Error Identification | Error text below each field | ✅ | "هذا الحقل مطلوب" in red below input |
| 3.3.2 Labels or Instructions | All inputs have `<label>` | ✅ | Visible labels above each RTL-aligned field |
| 3.3.7 Accessible Authentication | No cognitive function test | ✅ | 2FA via authenticator app, no CAPTCHA |
| 4.1.2 Name, Role, Value | ARIA roles for custom components | ✅ | Custom select, datepicker, modal all have roles |
