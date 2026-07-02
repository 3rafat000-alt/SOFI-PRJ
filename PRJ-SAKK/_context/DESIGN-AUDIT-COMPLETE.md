# 🎨 DESIGN-AUDIT-COMPLETE — SAKK Full Design Overhaul

> خطة التدقيق الشامل للتصميم — كل الواجهات، كل الصفحات، كل المكونات

## Scope
- **Admin panel:** all Blade views (~80+ files)
- **Company portal:** company dashboard, payroll, documents
- **Auth pages:** login, register, 2FA, forgot password
- **Legal pages:** privacy, terms
- **Mobile:** Flutter screens (verify consistency with web design tokens)
- **All CSS/JS:** admin.js, navbar/sidebar/health CSS, inline styles, Tailwind config
- **Responsive:** all breakpoints, mobile-first or not
- **Design tokens:** colors, typography, spacing, shadows, border-radius
- **Components:** cards, buttons, inputs, modals, tables, navigation
- **Content:** Arabic/English copy consistency, labels, errors, placeholders
- **UX flow:** user journeys, states (loading/empty/error/edge), transitions

## Wave 1 — CSS/JS/Blade/Design Audit (4 parallel agents)
1. **Grace (CSS-Tailwind-A11y):** CSS variables, Tailwind config, responsive, WCAG AA, design tokens
2. **Nguyen (Blade-Architect):** Blade layouts, sidebar structure, footer, component extraction, @stack/@section usage
3. **Lars (JS-Vue-Engineer):** admin.js / Alpine.js usage, interactivity patterns, event handlers, keyboard shortcuts
4. **Dan (UI-UX-Designer):** Visual design audit — spacing, alignment, color harmony, component consistency, RTL correctness

## Wave 2 — Content + UX Audit (2 parallel agents)
5. **Margaret (Content-Strategist):** UX copy audit — all pages, states, labels, errors, consistency
6. **Rosa (Manual-Exploratory-Tester):** UX flow audit — navigation, states, transitions, error handling, edge cases

## Wave 3 — Fix Implementation (parallel per finding)
7. CSS fixes → Grace
8. Blade/structure fixes → Nguyen
9. JS fixes → Lars
10. Design fixes → Dan

## Wave 4 — Content + UX fixes
11. Copy fixes → Margaret
12. UX fixes → Rosa

## Wave 5 — Verification
13. Full regression: all pages visual check
14. Test suite: backend 705 + mobile 131
15. Final report

## Files to audit

### CSS
- `public/sakk-admin/css/navbar.css` (extracted)
- `public/sakk-admin/css/sidebar.css` (extracted)
- `public/sakk-admin/css/health.css` (extracted)
- `resources/css/app.css` (Tailwind)
- Inline `<style>` in all admin Blade files
- `tailwind.config.js`

### Blade Views
- `resources/views/layouts/admin.blade.php` (master layout)
- `resources/views/admin/partials/navbar.blade.php`
- `resources/views/admin/partials/sidebar.blade.php`
- All `resources/views/admin/*/` (30+ directories)

### JS
- All inline `<script>` in Blade files
- Alpine.js x-data/x-init patterns
- Keyboard shortcuts (admin.blade.php ~880)

### Legal
- `resources/views/legal/privacy.blade.php`
- `resources/views/legal/terms.blade.php`

### Design Reference
- `docs/DESIGN.md` (design system)
- `docs/carda-wallet_Copy.json` (UX copy)
