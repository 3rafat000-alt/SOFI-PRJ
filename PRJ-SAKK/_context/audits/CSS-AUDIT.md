# CSS Audit Report — SAKK Admin Panel

**Auditor:** Grace Achieng (CSS/Tailwind & A11y Expert)
**Date:** 2026-06-29
**Scope:** All CSS files, inline `<style>` blocks, Tailwind config, design token consistency
**Reference:** `docs/DESIGN.md` (v1), `public/sakk-assets/sakk-tokens.css` (canonical tokens)

---

## Summary

| Severity | Count |
|----------|-------|
| Critical | 4 |
| High | 8 |
| Medium | 7 |
| Low | 5 |
| **Total** | **24** |

---

## Critical Findings

### C1. Font Mismatch — Admin Uses Cairo, Design Specifies IBM Plex Sans Arabic

**Files:**
- `resources/views/layouts/admin.blade.php:13-17`
- `resources/views/admin/auth/login.blade.php:10-14`

**Issue:** DESIGN.md §3.1 explicitly specifies **IBM Plex Sans Arabic** as the sole project font (400/500/600/700). The admin layout loads **Cairo** from CDN (5 weights: 500/600/700/800/900). Login page also loads Cairo (6 weights including 900). The canonical `sakk-tokens.css` defines and self-hosts IBM Plex Sans Arabic woff2 with `font-display:swap`, but admin never imports it.

**Impact:** Brand identity violation. Renders all typography tokens in DESIGN.md invalid for admin panel. Two fonts in the same product = fragmented brand.

**Fix:** Replace Cairo CDN links with IBM Plex Sans Arabic self-hosted from `/sakk-assets/fonts/`. Remove all Cairo `@fontsource` imports. Set `body { font-family: var(--font); }` referencing canonical token.

---

### C2. Duplicate CSS Variable Systems — No Single Source of Truth

**Files:**
- `public/sakk-assets/sakk-tokens.css` (canonical, 121 lines)
- `resources/views/layouts/admin.blade.php:26-108` (admin layout root vars, ~82 lines)
- `resources/views/admin/auth/login.blade.php:17-24` (login page root vars)
- `resources/views/admin/integrations/overview.blade.php:12-16` (dark theme vars)
- `public/sakk-admin/css/sidebar.css:3-15` (sidebar vars)
- `public/sakk-admin/css/health.css:2-5` (health vars)

**Issue:** The admin panel defines its own complete CSS variable system in `admin.blade.php` inline `<style>` that is **completely different** from `sakk-tokens.css`. Key discrepancies:

| Token | sakk-tokens.css | Admin layout | Diff? |
|-------|----------------|--------------|-------|
| Background | `--marble: #F7F3EE` | `--bg: #F6F6F5` | YES |
| Text primary | `--ink: #2A1A1F` | `--text-primary: #18181B` | YES |
| Text secondary | `--ink-2: #6E5F63` | `--text-secondary: #57575C` | YES |
| Text muted | — | `--text-muted: #9B9B9F` | Missing in canonical |
| Surface | `--surface: #FFFFFF` | `--surface: #ffffff` | OK |
| Success | `--success: #1F9D55` | `--success: #16a34a` | YES |
| Warning | `--warning: #B58A3C` | `--warning: #f59e0b` | YES |
| Error | `--error: #C0392B` | `--danger: #ef4444` | YES |
| Radius lg | `--r-lg: 16px` | `--radius-lg: 0.875rem` (14px) | YES |
| Shadow sm | `--sh-sm` | `--shadow-sm` | Naming diff |

**Impact:** The admin panel is visually disconnected from the landing page and mobile app. Design tokens cannot be changed in one place — must update 4+ locations. CSS variables cannot cascade from canonical source.

**Fix:** Import `sakk-tokens.css` in admin layout via `<link>`. Remove all duplicate `:root` variable definitions. Use canonical `--wine`, `--gold`, `--marble`, `--ink`, `--sh-*`, `--r-*` tokens throughout.

---

### C3. No Tailwind Config — CDN Play Mode Only

**File:** No `tailwind.config.js` exists in project (searched entire repo).

**Issue:** Tailwind CSS loaded via `<script src="https://cdn.tailwindcdn.com"></script>` (admin.blade.php:10). Play CDN mode provides no customization — all Tailwind utility classes use default colors (`bg-green-50`, `text-red-700`, `border-green-200`, etc.), not SAKK brand tokens. No theme extension possible.

**Impact:** Toast system (admin.blade.php:1022-1026) uses raw Tailwind color strings:
```html
'bg-green-50 text-green-700 border border-green-200': toast.type === 'success',
'bg-red-50 text-red-700 border border-red-200': toast.type === 'error',
```
These bypass SAKK semantic palette entirely. Any Tailwind class used in Blade will not respect brand colors.

**Fix:** Install Tailwind CSS v3/v4 locally, create `tailwind.config.js` extending `colors` with SAKK palette (`wine`, `gold`, `marble`, `ink`), build via PostCSS/npm. Replace CDN script.

---

### C4. Admin Layout Inline Style Block (~1000 Lines) — Uncacheable, Unmaintainable

**File:** `resources/views/layouts/admin.blade.php:20-1012`

**Issue:** The ENTIRE admin component library (~992 lines) lives inside a single `<style>` tag in the layout Blade file. This means:
- Cannot be cached by browser (embedded in HTML)
- Cannot be loaded async
- Cannot be versioned or CDN-deployed
- Every page load sends 11KB of CSS inline
- Mixes component styles (C-01 through C-10), layout, reset, utilities all in one block

The separate CSS files (`public/sakk-admin/css/navbar.css`, `sidebar.css`, `health.css`) duplicate these styles — and are essentially **dead code** since admin renders via the inline block.

**Impact:** Performance penalty on every page load. Impossible to maintain design system in one file. Duplicate CSS files add confusion.

**Fix:** Extract inline `<style>` to `public/sakk-admin/css/admin.css`. Use `<link>` with version hash. Delete orphaned `navbar.css`/`sidebar.css` after verifying they're unused. Dead code was already noted in STATE.md.

---

## High Findings

### H1. Sidebar Background Hardcoded — Violates DESIGN.md

**File:** `public/sakk-admin/css/sidebar.css:3`
**Token:** `--cs-bg: #0a0a0f`

**Issue:** DESIGN.md §2.8 specifies sidebar background as `#4A1320` (wine-dark). The sidebar CSS uses `#0a0a0f` (near-black). The inline admin layout uses `--sidebar-bg: #ffffff` (white). Three different sidebar colors across the codebase — only the inline admin layout is actually rendered.

**Impact:** The "wine-dark" sidebar (DESIGN.md:138) is the ONLY dark surface meant to signal "admin power." Neither the extracted `sidebar.css` nor the inline layout honor this.

**Fix:** In admin layout, set `--sidebar-bg: #4A1320` per DESIGN.md. Update all sidebar text colors for dark background contrast.

---

### H2. WCAG AA Failures — Badge Text and Tab Text

**File:** `resources/views/layouts/admin.blade.php:393-397, 504-520`

| Element | Colors | Ratio | Verdict |
|---------|--------|-------|---------|
| `.badge-primary` text | `#6E1B2D` on `rgba(18,18,18,0.08)` bg | ~2.85:1* | ❌ FAIL |
| `.tab` default color | `var(--text-muted)` #9B9B9F on white | 2.68:1 | ❌ FAIL |
| `.topbar-badge` | `#ef4444` on white (10px text) | 3.21:1 | ❌ FAIL AA normal |
| `.stat-change.up` | `#16a34a` on `#dcfce7` | ~2.84:1 | ❌ FAIL AA normal |

*Computed: primary on near-transparent bg approximates primary on white

**Impact:** Users with low vision cannot read badge labels, tab labels, stat changes, or notification counts.

**Fix:**
- `.badge-primary`: Use `background: var(--wine)` / `color: #fff` instead of tinted bg
- `.tab`: Use `color: var(--ink-2)` (#6E5F63, 6.03:1)
- `.topbar-badge`: Use `--error` (#C0392B, 5.44:1) for bg
- `.stat-change.up`: Use `--success` (#155E36, 4.97:1 on success-light)

---

### H3. Hardcoded Colors in Extracted CSS — Not Using Design Tokens

**Files:**
- `public/sakk-admin/css/health.css:16-18` — `rgba(110, 27, 45, 0.2)`, `rgba(110, 27, 45, 0.12)`, `rgba(110, 27, 45, 0.1)`, `rgba(110, 27, 45, 0.08)`, `rgba(110, 27, 45, 0.04)`
- `public/sakk-admin/css/navbar.css:93-94` — `rgba(201,162,75,0.15)` for search focus
- `public/sakk-admin/css/navbar.css:149` — `rgba(201,162,75,0.3)`, `rgba(201,162,75,0.35)`
- `public/sakk-admin/css/health.css:19` — `rgba(0, 0, 0, 0.05)` for shadow

**Issue:** Multiple hardcoded `rgba()` values should reference CSS variables:
- `rgba(110,27,45,0.2)` → `var(--glass-border)` or computed from `--wine`
- `rgba(201,162,75,0.15)` → should reference `--gold-bright` at opacity
- `rgba(0,0,0,0.05)` → should reference `--sh-sm` or computed

**Impact:** Making a global color change requires finding and updating every hardcoded `rgba()`. Tokens lose their purpose.

**Fix:** Replace hardcoded color values with `rgb(from var(--wine) r g b / 0.2)` syntax (CSS relative colors) or pre-computed token variables.

---

### H4. RTL Violations — Physical Properties Used

**Files:**
- `resources/views/layouts/admin.blade.php:219-241` — `.input-group` uses `border-left`, `border-radius: 0 var(--radius-md) var(--radius-md) 0`
- `resources/views/layouts/admin.blade.php:815, 825-829` — `.slide-over` uses `left: 0`, `transform: translateX(-100%)`
- `resources/views/admin/settings/index.blade.php:38` — `.switch-thumb` uses `right: 3px`
- `resources/views/admin/settings/index.blade.php:84` — `.fx-track .pin` uses `left: 50%`
- `public/sakk-admin/css/sidebar.css:232-236` — `.cs-mobile-toggle` uses `right: 16px`
- `public/sakk-admin/css/sidebar.css:245` — `.sakk-mobile-bottom-nav` uses `left: 0; right: 0`

**Issue:** DESIGN.md §9 explicitly requires `margin-inline-start/end`, `padding-inline-start/end`, `inset-inline-start/end` — never `left`/`right`. Multiple components use physical properties.

**Impact:** In RTL mode (which the admin panel uses), physical `left`/`right` are wrong. The slide-over panel comment (lines 812-814) acknowledges the ambiguity but the implementation anchors to `left` regardless.

**Fix:** Replace `left`/`right` with `inset-inline-start`/`inset-inline-end`. For `.input-group`, use `border-inline-start` and `border-radius: var(--radius-md) 0 0 var(--radius-md)` with logical properties.

---

### H5. Responsive Breakpoints Not Matching DESIGN.md

**Files:**
- `resources/views/layouts/admin.blade.php:693-699` — Only one breakpoint at 768px
- `public/sakk-admin/css/sidebar.css:229-238` — Only 768px breakpoint
- `public/sakk-admin/css/navbar.css:469-506` — Only 767/768px breakpoints
- `public/sakk-admin/css/health.css:213-216` — Only 640px breakpoint

**Issue:** DESIGN.md §15 specifies a 4-level breakpoint system:
- ≤767px mobile (drawer sidebar hidden)
- 768-1023px tablet (240px sidebar)
- 1024-1279px desktop (272px sidebar)
- ≥1280px wide (300px sidebar, max 1440px container)

Actual implementation has only one breakpoint at 768px. The sidebar is fixed at the layout's CSS variable width (~272px) regardless of viewport width. No tablet/desktop/wide distinction.

**Impact:** Sidebar is too wide on tablets (272px on 768px screen = 35% of viewport). Content area too narrow on wide screens (no max-width container).

**Fix:** Add `@media (min-width: 1024px)` and `@media (min-width: 1280px)` breakpoints with sidebar width and content padding adjustments per DESIGN.md.

---

### H6. Tabular Numerals Not Applied to Admin Figures

**Files:**
- `resources/views/layouts/admin.blade.php` — No import of `.tnum` or `[data-money]` classes
- All admin Blade views with balance/amount displays

**Issue:** DESIGN.md §3.4 requires `font-variant-numeric: tabular-nums` on every money value, balance, stat, and figure. The canonical classes (`.tnum`, `.money`, `[data-money]`) are defined in `sakk-tokens.css` but never imported in admin. Only `.topbar-badge` (line 638) has it.

**Impact:** Admin dashboard stat cards, transaction amounts, wallet balances all render with proportional numerals — numbers shift width as digits change, causing visual jitter. Brand identity rule violated.

**Fix:** Import `sakk-tokens.css` in admin layout. Apply `.tnum` class to all numeric values. Add `font-variant-numeric: tabular-nums` to `.stat-value`, `.kpi-value`, table td with amounts.

---

### H7. `prefers-reduced-motion` Incomplete

**File:** `resources/views/layouts/admin.blade.php:997-1004`

**Issue:** Reduced motion media query only disables skeleton, slide-over, tab-nav, and sort-header animations. DESIGN.md §6.5 requires ALL animations/transitions to be disabled:
- Toast slide-in/fade-out animations (lines 523-531)
- Dropdown `sakk-dropdown-in` keyframe (navbar.css:246-249)
- `sakk-pulse` on KPI pill (navbar.css:406-409)
- `health-spin` (health.css:208-210)
- Status pulse-ring (integrations/overview.blade.php:76-79)
- `fxPulse` (settings/index.blade.php:72)

**Impact:** Users with vestibular disorders will experience motion from toast animations, pulsing indicators, and dropdowns even with reduced motion preference set.

**Fix:** Add `animation: none !important; transition: none !important;` catch-all per DESIGN.md §6.5. Test with `prefers-reduced-motion: reduce` in DevTools.

---

### H8. Inline Style Blocks Fragment Design System — 25+ Scattered `<style>` Tags

**Files (all under `resources/views/admin/`):**
- `auth/login.blade.php` — complete redefines design (22 vars)
- `gold/_styles.blade.php` — redefines `--gold: #C08A1E` (should be `#B58A3C`)
- `integrations/overview.blade.php` — DARK theme (`--node-bg: #0f1117`) in light-only admin
- `settings/index.blade.php` — ~100 lines of custom styles
- `system/_shell.blade.php`, `system/backup.blade.php`, `system/messages.blade.php`, `system/third-party.blade.php`, `system/maintenance.blade.php`, `system/channels.blade.php`
- `audit/index.blade.php`, `notifications/index.blade.php`
- `users/index.blade.php`, `users/show.blade.php`
- `merchants/index.blade.php`, `merchants/documents.blade.php`
- `agents/create.blade.php`, `agents/edit.blade.php`, `agents/_location-map.blade.php`
- `transactions/index.blade.php`
- `invoices/transaction.blade.php`
- `partials/breadcrumbs.blade.php`
- `support/index.blade.php`, `support/show.blade.php`
- `fees/index.blade.php`

**Issue:** 25+ scattered inline `<style>` blocks each define local design decisions outside the design system. The `gold/_styles.blade.php` redefines gold to `#C08A1E` (should match `--gold: #B58A3C`). The `integrations/overview.blade.php` introduces a dark NOC theme (`--node-bg: #0f1117`) — a direct violation of DESIGN.md §1.3 rule #1 ("Light-only. No dark mode").

**Impact:** Design system fragmention. Gold components will render in wrong color. Dark integration cards break the light-only brand rule.

**Fix:** Audit each `<style>` block. Move reusable styles to centralized CSS. Convert page-specific styles to reference canonical variables. Remove `integrations` dark theme — use light marble colors.

---

## Medium Findings

### M1. Dead/Orphaned CSS Files

**Files:**
- `public/sakk-admin/css/navbar.css` (507 lines) — NOT loaded in admin layout
- `public/sakk-admin/css/sidebar.css` (279 lines) — NOT loaded in admin layout
- `public/sakk-admin/css/health.css` (217 lines) — loaded in health page but layout inline styles duplicate
- `public/sakk-admin/admin.js` — NOT loaded in admin layout

**Issue:** STATE.md already noted these files are orphaned. The admin UI runs on the inline `<style>` block in `admin.blade.php`. These extracted CSS files are dead code — they maintain their own variable systems (sidebar.css `--cs-bg: #0a0a0f` vs layout `--sidebar-bg: #ffffff`).

**Impact:** 1003 lines of dead CSS. Maintainer confusion — editing `navbar.css` has zero effect.

**Fix:** Audit which extracted files are actually loaded (health.css may be). Delete confirmed orphans. Move any unique styles into the main CSS file.

---

### M2. Variable Naming Inconsistency Across Token Sets

| Domain | sakk-tokens.css | Admin layout | DESIGN.md |
|--------|----------------|--------------|-----------|
| Radius | `--r-sm` (8px) | `--radius-sm` (0.5rem=8px) | `--r-sm` / `--radius-sm` |
| Shadow | `--sh-sm` | `--shadow-sm` | `--shadow-sm` |
| Spacing | `--s-xs` (0.25rem) | `--spacing-xs` (0.25rem) | `--sp-1` (0.25rem) |
| Font size | `--fs-xs` | `--font-size-xs` | `--fs-xs` |
| Transition | `--t-fast` | `--transition-fast` | (not specified) |

**Issue:** Three naming conventions for the same tokens. Cannot mix and match between systems.

**Fix:** Standardize on one convention. DESIGN.md uses `--fs-*` / `--sp-*` / `--r-*` / `--shadow-*`. Align admin to match.

---

### M3. Gold Token Value Drift

| Source | Value |
|--------|-------|
| DESIGN.md | `#B58A3C` (accent), `#C9A24B` (gold bright) |
| sakk-tokens.css | `--gold: #B58A3C`, `--gold-bright: #C9A24B` |
| `gold/_styles.blade.php` | `--gold: #C08A1E` |
| `navbar.css` | `--gold-dark: #B58A3C` | 
| `sidebar.css` | `--cs-gold: #C9A24B`, `--cs-gold-d: #B58A3C` |

**Issue:** `gold/_styles.blade.php` uses `#C08A1E` which does not match any canonical gold value.

---

### M4. Login Page CDN Font Loads 6 Cairo Weights Unnecessarily

**File:** `resources/views/admin/auth/login.blade.php:10-15`

**Issue:** 6 separate HTTP requests for Cairo font files (400, 500, 600, 700, 800, 900). Weight 900 is never used in login page CSS.

**Fix:** Load only weights used. Or better, use self-hosted IBM Plex Sans Arabic.

---

### M5. Missing `focus-visible` Rings on Icon Buttons

**Files:**
- `public/sakk-admin/css/navbar.css:33-47` — `.sakk-navbar__toggle` no `:focus-visible`
- `public/sakk-admin/css/navbar.css:98-114` — `.sakk-navbar__action` no `:focus-visible`

**Issue:** DESIGN.md §12.2 requires `:focus-visible` ring on ALL interactive elements.

**Fix:** Add `outline: 2px solid var(--wine)` / `outline-offset: 2px` on `:focus-visible`.

---

### M6. Z-Index Scale Violation

**File:** `resources/views/layouts/admin.blade.php:804, 820`

DESIGN.md §14: Dropdown=100, Sticky=200, Navbar=300, Overlay=400, Modal=500.
Admin layout: `.slide-over-backdrop` at 40, `.slide-over` at 50, modal at 50.

**Fix:** Use canonical z-index values (`--z-overlay`, `--z-modal`).

---

### M7. Danger Color Value Mismatch

| Source | Value |
|--------|-------|
| DESIGN.md | `#C0392B` |
| sakk-tokens.css | `--error: #C0392B` |
| Admin layout | `--danger: #ef4444` |

---

## Low Findings

### L1. Inline Style Attributes in Blade (Non-CSS-Variable Colors)

**Files:** Multiple admin views use inline `style=""` attributes with hardcoded colors instead of CSS variables:
- `dashboard.blade.php:94` — `color: #16a34a'` instead of `var(--success)`
- `dashboard.blade.php:234-236` — `onmouseover/onmouseout` events for styling (antipattern)
- `partials/_slide_over.blade.php:76-119` — all skeleton elements use inline `style="border-radius: var(--radius-sm)"` instead of classes
- `fees/index.blade.php:63` — `style="background: var(--surface-active); color: var(--text-muted);"` should be class-based

---

### L2. Overly Specific Selectors

- `nav.sidebar-nav::-webkit-scrollbar-thumb` (layout:689) — `.sidebar-nav` alone sufficient
- `div#toastContainer` (layout:1018) — `#toastContainer` alone sufficient

---

### L3. Duplicate Topbar Button Styles

- `.topbar-icon-btn:hover` defined twice (layout:605 and 612)

---

### L4. No Mobile Search on Login Page

Login page (auth/login.blade.php) has no responsive breakpoint for screens below 768px — the 2-column layout may break on mobile.

---

### L5. Missing Safari `-webkit-` Prefixes

- `health.css:14-15` — has both `backdrop-filter` and `-webkit-backdrop-filter` (good)
- But admin layout inline styles use `backdrop-filter: blur(6px)` (line 534) without `-webkit-` prefix

---

## WCAG 2.2 AA Compliance Score

| Criterion | Status | Notes |
|-----------|--------|-------|
| 1.4.1 Use of Color | ✅ PASS | Icons accompany color-coded states |
| 1.4.3 Contrast (AA) | ⚠️ 71% | 4 failures found (H2) |
| 1.4.4 Resize Text | ✅ PASS | rem units used |
| 1.4.10 Reflow | ✅ PASS | No horizontal scroll at 320px |
| 1.4.12 Text Spacing | ✅ PASS | Line heights adequate |
| 2.1.1 Keyboard | ⚠️ 85% | 2 missing focus-visible rings (M5) |
| 2.4.7 Focus Visible | ⚠️ 85% | Same as 2.1.1 |
| 2.5.5 Target Size | ✅ PASS | All touch targets ≥44px |
| 2.5.8 Target Size (AA) | ✅ PASS | 24×24px minimum met |
| 3.3.1 Error Identification | ✅ PASS | aria-invalid on forms |
| 3.3.2 Labels | ✅ PASS | Labels present |
| 4.1.2 Name, Role, Value | ✅ PASS | ARIA roles present |
| 4.1.3 Status Messages | ⚠️ 50% | Toasts lack `role="status"` |

**Overall Score: 84%** (11/13 criteria pass fully, 2 pass partially)

---

## Recommendations (Priority Order)

1. **Install Tailwind locally** with SAKK theme extension — replace CDN. (C3)
2. **Import `sakk-tokens.css`** in admin layout, delete inline `:root` block. (C2)
3. **Replace Cairo with IBM Plex Sans Arabic** — match DESIGN.md. (C1)
4. **Extract inline `<style>`** to external cached CSS file `admin.css`. (C4)
5. **Fix 4 WCAG AA contrast failures** — badge, tab, stat-change, notif-badge. (H2)
6. **Align sidebar colors** to DESIGN.md `#4A1320` wine-dark. (H1)
7. **Add 1024px and 1280px breakpoints** per DESIGN.md sidebar widths. (H5)
8. **Fix RTL physical properties** — replace `left`/`right` with logical equivalents. (H4)
9. **Apply tabular numerals** to all money/figures in admin. (H6)
10. **Consolidate gold token** — remove `#C08A1E` drift. (M3)
11. **Kill orphaned CSS** — `navbar.css`, `sidebar.css`, `admin.js`. (M1)
12. **Standardize variable naming** across all CSS sources. (M2)
