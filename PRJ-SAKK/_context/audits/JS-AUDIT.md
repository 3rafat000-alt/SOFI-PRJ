# JS AUDIT — SAKK Admin Panel

**Auditor:** Lars Eriksson · JS/Vue.js Engineer (Tier 2)
**Date:** 2026-06-29
**Scope:** All inline `<script>` blocks + Alpine.js usage in `resources/views/admin/` and `resources/views/layouts/`

---

## Summary

| Metric | Value |
|---|---|
| Total findings | 15 |
| Critical | 1 |
| High | 2 |
| Medium | 5 |
| Low/Info | 7 |
| CSP compliance | PARTIAL — `'unsafe-inline'` required, `'unsafe-eval'` removed ✓ |
| Alpine.js version | `@alpinejs/csp@3.x.x` (CSP-safe build) ✓ |
| Orphaned JS | 2 files — dead code, zero runtime impact |
| jQuery usage | NONE ✓ |

---

## ⚠ Top 3 Issues

1. **CRITICAL:** `admin/fees/index.blade.php:315` — `innerHTML` with template literal `${data.message || data.error}`. If the server returns unescaped user-controlled text in error messages, this is a DOM XSS sink.
2. **HIGH:** Tailwind play-CDN loaded in production (`admin.blade.php:10`) — 3.5MB+ parser, blocks rendering, no SRI hash. Replace with pre-compiled CSS.
3. **HIGH:** `chat/show.blade.php:83` + `chat/index.blade.php:70` — unthrottled `setInterval` polling (3s/4s) runs indefinitely even when tab hidden. No `visibilitychange` pause. Battery/network drain on mobile.

---

## 1. Alpine.js Audit

### CSP Build — PASS
- Layout `admin.blade.php:12` loads `@alpinejs/csp@3.x.x` (CSP-compatible build). All components registered explicitly in `alpine:init` handler.
- SecurityHeaders middleware (`app/Http/Middleware/SecurityHeaders.php:17`) confirms `unsafe-eval` was removed post-migration.
- `Alpine.data()` registrations: `sidebarLayout`, `confirmModal`, `keyboardHelpModal`, `sidebarNav`, `dropdown`, `dashboardTabs`, `toastSystem`, `agentShow`, `merchantShow`, `txShow`, `notificationsApp`, `agentDocReject`, `feeTabs`, `feeCard`, `goldAutoSync`, `karatCard` — all in `admin.blade.php:1434-1518`.

### Page-Level Alpine Components
| File | Component | Pattern |
|---|---|---|
| `admin/dashboard.blade.php:107` | `dashboardTabs` | Tab state only, `setFlow()` cross-talk to Chart.js |
| `admin/settings/index.blade.php:129` | `settingsPage` | Section nav + form persistence via `@change="save(...)"` |
| `admin/users/index.blade.php` | `usersPage`, `statusModal`, `bulkModal`, `quickViewPanel`, `kycApproveModal`, `kycRejectModal` | Heavy AJAX + modal orchestration |
| `admin/users/show.blade.php:28` | `userShow` | Tab navigation + status modals |
| `admin/transactions/index.blade.php` | `transactionsPage`, `txQuickViewPanel` | Same pattern as users |
| `admin/integrations/overview.blade.php:160` | `integrationsApp` | Test connection + filter |
| `admin/integrations/show.blade.php:157` | `integrationSetup` | Toggle + test with Alpine refs |
| `admin/notifications/index.blade.php:79` | `notificationsApp` | Form preview + audience selector |
| `admin/gold/prices.blade.php` | `goldAutoSync` + `karatCard` | Auto-sync toggle + live price calculator |

### Pattern Consistency — PASS
- All modals use `show` boolean + `@click="show = false"` overlay dismiss + `@click.outside` or backdrop click.
- All event dispatching uses `window.dispatchEvent(new CustomEvent(...))` — consistent.
- All AJAX calls use `fetch()` with `X-Requested-With: XMLHttpRequest` + `Accept: application/json`.

### Found CSP-Violating Patterns
- `admin/settings/index.blade.php:537-551`: Inline `@click="togglePassword('currentPassword')"` references a global function — works because `'unsafe-inline'` allows inline event handlers.
- All inline `@click`, `@keydown` etc. in Alpine are compiled to `new Function()` in standard Alpine BUT `@alpinejs/csp` avoids this by using explicit registrations. However, the CSP header still has `'unsafe-inline'` which covers inline handlers — this is a tradeoff, not a bug.

---

## 2. Inline `<script>` Blocks — Inventory

31 `<script>` blocks found across admin views. Distribution:

| File | Line | Purpose | Risk |
|---|---|---|---|
| `layouts/admin.blade.php` | 1423, 1426 | Flash-toast dispatchers | LOW — server session data |
| `layouts/admin.blade.php` | 1433 | `alpine:init` + keyboard shortcuts + globals | LOW |
| `admin/dashboard.blade.php` | 306 | Chart.js init + `setFlow()` | LOW |
| `admin/users/index.blade.php` | 356 | `usersPage` + 5 sub-components | LOW |
| `admin/users/show.blade.php` | 1011 | `userShow` + action handlers | LOW |
| `admin/transactions/index.blade.php` | 214 | `transactionsPage` + `txQuickViewPanel` | LOW |
| `admin/transactions/show.blade.php` | 361 | Slide-over panel + action forms | LOW |
| `admin/fees/index.blade.php` | 272 | Fee calculator with innerHTML (see #3) | **HIGH** |
| `admin/settings/index.blade.php` | 636 | Settings persistence | LOW |
| `admin/chat/show.blade.php` | 49 | Live chat polling | MED (polling) |
| `admin/chat/index.blade.php` | 37 | Chat list polling | MED (polling) |
| `admin/system/health.blade.php` | 180 | Health check AJAX | LOW |
| `admin/system/backup.blade.php` | 397 | Restore confirm with innerHTML | MED (sink) |
| `admin/system/_shell.blade.php` | 120 | Submenu toggle | LOW |
| `admin/system/messages.blade.php` | 118 | Variable highlight preview | MED (innerHTML) |
| `admin/system/maintenance.blade.php` | 101 | Toggle maintenance mode | LOW |
| `admin/system/third-party.blade.php` | 363 | Webhook test + connection poll | LOW |
| `admin/agents/_location-map.blade.php` | 40, 127 | Google Maps init | LOW |
| `admin/agents/dashboard.blade.php` | 247 | KPI chart | LOW |
| `admin/merchants/dashboard.blade.php` | 286 | KPI chart | LOW |
| `admin/integrations/overview.blade.php` | 284 | Integration test connector | LOW |
| `admin/integrations/show.blade.php` | 281 | Integration setup + inline Alpine data | LOW |
| `admin/auth/login.blade.php` | 272 | Form validation + password toggle | LOW |
| `admin/audit/index.blade.php` | 434 | Audit log viewer | LOW |
| `admin/withdrawals/index.blade.php` | 321 | Withdrawal filtering | LOW |
| `admin/partials/flash.blade.php` | 85 | Fallback flash-toast | LOW |
| `admin/partials/sidebar.blade.php` | 415 | Submenu accordion | LOW |
| `admin/partials/navbar.blade.php` | 325 | **ORPHANED** — see §4 | INFO |
| `admin/partials/breadcrumbs.blade.php` | 136 | Breadcrumb chevron icons | LOW |
| `admin/merchants/documents.blade.php` | 295 | Document approval actions | LOW |
| `admin/merchants/documents-show.blade.php` | 184 | Document detail view | LOW |

---

## 3. Security Findings — DOM XSS Sinks

### Finding S-01 — innerHTML with dynamic data (HIGH)
- **File:** `admin/fees/index.blade.php:315`
- **Code:** `resultDiv.innerHTML = `<span style="color: #fca5a5;">${data.message || data.error}</span>`;`
- **Risk:** `data.message` returned from server could contain user-controlled text. If an attacker registers a fee code with an XSS payload in its name/label, and the server echoes it back in `data.message`, this is DOM XSS.
- **Fix:** Use `textContent` + create elements, or server-sanitize the error message.

### Finding S-02 — innerHTML with data-attribute (MEDIUM)
- **File:** `admin/system/backup.blade.php:410`
- **Code:** `msg.innerHTML = '... <strong dir="ltr">' + filename + '</strong> ...';`
- **Risk:** `filename` comes from `data-filename` attribute which is server-rendered. Low risk if server controls it, but `innerHTML` with string concatenation is fragile.
- **Fix:** Use `textContent` on a `<strong>` element.

### Finding S-03 — innerHTML on AJAX HTML responses (MEDIUM)
- **Files:** `admin/users/index.blade.php:543`, `admin/transactions/index.blade.php:340`
- **Code:** `region.innerHTML = html;` where `html = await r.text()` from server.
- **Risk:** Server returns rendered Blade HTML. Safe because server controls the HTML, but bypasses CSP `script-src` protections (inline `<script>` in fetched HTML executes). Should use `template` or DOM parser with scripting disabled, or ensure response never contains `<script>`.
- **Fix:** Strip `<script>` from AJAX HTML responses, or use `Accept: application/json` + client-side render.

### Finding S-04 — innerHTML with template literal (LOW)
- **File:** `admin/system/messages.blade.php:132`
- **Code:** `if (pBody) pBody.innerHTML = highlightVars(body.value);`
- **Risk:** `body.value` is from a textarea. `highlightVars()` likely adds `<span>` highlighting. If it doesn't escape, stored XSS.

### Finding S-05 — button innerHTML swaps (INFO)
- **Files:** Multiple (dashboard, audit, integrations, auth)
- **Pattern:** `btn.innerHTML = '<span class="spinner">...</span>'` — static strings, no injection vector.
- **Verdict:** Safe.

### Secure patterns observed
- `admin/chat/show.blade.php:60` — `esc()` sanitizer properly escapes `&<>"`
- `admin/chat/index.blade.php:56` — Same `esc()` sanitizer
- `components/admin/data-table-new.blade.php:393-397` — `escHtml()` using `createTextNode` → `.innerHTML`
- `admin.blade.php:1028` — Toast uses `x-text` (Alpine safe binding)
- `admin.js.orphaned:525` — `textContent` assignment (file unused but pattern correct)
- `flash.blade.php` — Uses `data-flash-message` + `sakkToast()` which uses `textContent`

---

## 4. Orphaned Files

### `public/sakk-admin/admin.js.orphaned` (~900 lines)
- **Status:** ORPHANED — not loaded by any layout.
- **Contents:** Comprehensive vanilla JS (sidebar, drawers, modals, dropdowns, tabs, table search/sort/filter, toast, confirm, password toggle, clipboard, CSV export, notification bell, keyboard shortcuts, chart canvas patches, light-only enforcement).
- **Assessment:** All functionality has been replicated in Alpine or per-page inline scripts. Some features (notification bell, drawers, vim-like `gd/gu` sequences) exist ONLY in this orphaned file and are effectively dead.
- **Recommendation:** Delete or extract chart canvas patches (`enforceLightOnly`, `paintGrid`, `paintTooltip` etc.) into a live file if dashboard canvas charts are used.
- **Code worth saving:** The `enforceLightOnly()` MutationObserver + canvas chart re-painting helpers (~250 lines of visual polish) are NOT replicated anywhere else. If the dashboard canvas charts still use the old inline draw functions, these canvas patches were providing the burgundy/gold styling.

### `resources/views/admin/partials/navbar.blade.php` (+ sidebar.blade.php)
- **Status:** ORPHANED — admin layout has inline topbar/sidebar HTML, not `@include`'d.
- **Code:** `navbar.blade.php:325-457` contains dropdown toggle + Ctrl+K + sidebar toggle JS.
- **Notable:** Line 368 uses the **old broken pattern** `e.key === 'k'` (Arabic keyboard bug). This is dead code so it causes no harm, but confirms the admin layout's fixed version is the only live one.

---

## 5. Toast / Notification System

| System | Location | Method | XSS Safe? |
|---|---|---|---|
| Alpine | `admin.blade.php:1018-1031` | `x-text` bindings | ✅ Yes |
| Alpine | `admin.blade.php:1469-1476` | `Alpine.data('toastSystem')` | ✅ Yes |
| HTML data-attrs | `flash.blade.php` | `data-flash-message` + `sakkToast()` | ✅ Yes |
| Inline script | `admin.blade.php:1423,1426` | `CustomEvent('toast')` dispatching | ✅ Yes |
| Orphaned | `admin.js.orphaned:493-548` | `textContent` assignment | ✅ Yes (unused) |

**Verdict:** Toast system is consistent and XSS-safe across all active paths.

---

## 6. Modal Patterns

### System-level modals (admin layout)
- `confirmModal` — confirmation dialog, dispatched via `@confirm-modal.window`
- `keyboardHelpModal` — keyboard shortcuts reference, dispatched via `@keyboard-help.window`
- Open: `window.dispatchEvent(new CustomEvent('confirm-modal', {...}))`
- Close: `@click="show = false"` on overlay + cancel buttons

### Page-level modals (users, transactions, settings)
- Pattern: `Alpine.data('xxxModal', () => ({ show: false, open(d) { ... }, close() { ... }, async submit() { ... } }))`
- Open: `window.dispatchEvent(new CustomEvent('open-xxx-modal', { detail: {...} }))`
- Close: `show = false` + overlay click
- All use `$nextTick(() => this.$refs.firstFocus?.focus())` for focus management — ✅ Accessible
- All use `$refs` for DOM references — consistent

### Slide-over panels
- `quickViewPanel`, `txQuickViewPanel` — reuse same open/close/reload/load pattern
- CSS-driven animation via `translateX` + transition

**Verdict:** Consistent, accessible modal pattern across all views. No inconsistency found.

---

## 7. Form Validation JS

### Client-side validation patterns
- **Users status/bulk/KYC modals** (`users/index.blade.php`): Explicit `errors` reactive object, `reason.length < 3` check, `errorMsg` fallback. Consistent.
- **Settings** (`settings/index.blade.php`): No client-side validation — relies on server `save()` endpoint.
- **Fees** (`fees/index.blade.php`): Input validation (`!amount || amount <= 0`) before fetch.
- **Notifications** (`notifications/index.blade.php`): HTML5 `required` on title/body fields.
- **Auth login** (`auth/login.blade.php`): Basic email format + empty field checks in inline script.
- **No validation library** (jQuery Validate, Parsley, etc.) — all custom inline.

**Verdict:** Basic but functional. No security validation issues.

---

## 8. Dropdown / Menu Interactions

### Topbar dropdowns (admin layout)
- Alpine: `Alpine.data('dropdown', () => ({ open: false, toggle() { this.open = !this.open; } }))`
- Open/close via `@click="toggle()"` + `@click.outside="open = false"`
- Notification dropdown: `@click.outside` + `@keydown.escape.window`
- Profile dropdown: same pattern

### Orphaned navbar.blade.php
- Vanilla JS: `querySelectorAll('[data-dropdown-toggle]')` + `addEventListener` + `closeAllDropdowns()`
- Old pattern, dead code.

**Verdict:** Alpine dropdown pattern is consistent and clean. Escape key handled.

---

## 9. Search Functionality JS

### Global search (topbar)
- `admin.blade.php:1526` — Ctrl+K handler: `document.querySelector('input[placeholder*="بحث"]')` — fuzzy match on RTL placeholder.
- Alpine layout inline HTML shows search bar with `sakk-global-search` id.

### Per-page live search
- `users/index.blade.php:441-492`: Debounced (280ms) `_initLiveSearch()` — reads form controls, builds query string, fetches HTML via AJAX, replaces `#usersResults` region.
- `transactions/index.blade.php:256-296`: Same pattern.
- `data-table-new` component: Client-side search (`getFiltered()`) with 200ms debounce.

**Verdict:** Robust, debounced, consistent across pages.

---

## 10. Function `eval` / `new Function` / `document.write`

| Sink | Occurrences | Verdict |
|---|---|---|
| `eval(` | 0 | ✅ None |
| `new Function` | 0 | ✅ None (Alpine CSP build avoids this) |
| `document.write` | 0 | ✅ None |
| `setTimeout(string)` | 0 | ✅ None |
| `innerHTML` | 15+ | ⚠️ See §3 findings S-01 through S-05 |
| `insertAdjacentHTML` | 1 | ✅ Safe — uses `esc()` sanitizer |

---

## 11. Event Handler Consistency

| Technique | Used in | Notes |
|---|---|---|
| **Alpine** `@click`, `@keydown`, `@change` | Primary interaction model | Consistent |
| **Alpine** `$dispatch` | Cross-component communication | Consistent — CustomEvent pattern |
| **Vanilla** `addEventListener` | AJAX-heavy pages (users, transactions, chat, health, backup, fees) | Appropriate — these are procedural fetch workflows |
| **jQuery** | Not found anywhere in admin views | ✅ None |

**Verdict:** Clear separation of concerns. Alpine for UI state, vanilla for AJAX. No jQuery baggage.

---

## 12. Keyboard Shortcuts — Verification

### Admin layout (`admin.blade.php:1521-1541`)
| Shortcut | Detection | Status |
|---|---|---|
| Ctrl+K (search) | `e.code === 'KeyK'` | ✅ Fixed |
| Ctrl+H (home) | `e.code === 'KeyH'` | ✅ Fixed |
| Ctrl+U (users) | `e.code === 'KeyU'` | ✅ Fixed |
| Ctrl+T (txns) | `e.code === 'KeyT'` | ✅ Fixed |
| Ctrl+S (settings) | `e.code === 'KeyS'` | ✅ Fixed |
| ? (help modal) | `e.key === '?'` + `(e.shiftKey && e.code === 'Slash')` | ✅ Fixed |
| Input guard | `INPUT/TEXTAREA/SELECT` check | ✅ Correct |

**Verdict:** Keyboard shortcuts confirmed fixed with `e.code`. Arabic layout compliant.

### Orphaned navbar.blade.php (dead code)
- Lines 362, 368: Still uses `e.key === 'Escape'` and `e.key === 'k'` — but this file is not loaded.

---

## 13. CSP Compliance Detail

**Current policy** (`SecurityHeaders.php:28-45`):
```
default-src 'self';
script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdn.jsdelivr.net https://maps.googleapis.com https://maps.gstatic.com;
style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com;
img-src 'self' data: blob: https:;
font-src 'self' data: https://cdn.jsdelivr.net https://fonts.gstatic.com;
connect-src 'self' https://maps.googleapis.com https://maps.gstatic.com https://cdn.jsdelivr.net;
worker-src 'self' blob:;
base-uri 'self';
form-action 'self';
frame-ancestors 'none'
```

### Blockers
- `'unsafe-inline'` required for: 31+ inline `<script>` blocks, inline `@click`/`@keydown` handlers, `<style>` blocks. Removing it would require migrating ALL inline scripts to separate `.js` files.
- Tailwind play-CDN requires `https://cdn.tailwindcss.com` — unnecessary in production.

### Improvements already made
- `'unsafe-eval'` **removed** ✅ — possible because of `@alpinejs/csp` migration.
- CDN allowlist is scoped to specific hosts ✅.
- `frame-ancestors 'none'` ✅ — anti-clickjacking.

---

## Recommendations

1. **CRITICAL:** Fix `admin/fees/index.blade.php:315` — use `textContent` instead of `innerHTML` for server error messages, or sanitize with `escHtml()`.
2. **HIGH:** Replace Tailwind play-CDN (`cdn.tailwindcss.com`) with pre-compiled CSS (`npm run build` or Tailwind CLI). Saves 3.5MB, removes CDN dependency, enables SRI.
3. **HIGH:** Add `visibilitychange` pause to chat polling intervals (`chat/show.blade.php:83`, `chat/index.blade.php:70`). Add `document.addEventListener('visibilitychange', () => { if (document.hidden) clearInterval(poll) })`.
4. **MEDIUM:** Strip `<script>` from AJAX HTML responses (`users/index.blade.php:543`, `transactions/index.blade.php:340`) using server-side middleware or sanitize before `innerHTML` assignment.
5. **MEDIUM:** Delete or re-wire `admin.js.orphaned` — currently dead code. Extract canvas chart patches if dashboard still needs them.
6. **LOW:** Delete orphaned `navbar.blade.php` and `sidebar.blade.php` (the partial files) — admin layout has all HTML and Alpine inline.
7. **LOW:** Add `SRI` integrity hashes to CDN-loaded scripts (Chart.js, Alpine.js, Cairo font) to prevent supply-chain injection.
8. **LOW:** The `?` shortcut double-detection (`e.key === '?'` + `e.shiftKey && e.code === 'Slash'`) is redundant — `e.key` returns `'?'` on all layouts when Shift is held. Could simplify to `e.key === '?'`.
9. **INFO:** Chat polling could use `fetch` with `AbortController` to cancel stale requests on overlap.
10. **INFO:** Consider migrating from Tailwind play-CDN to a proper build pipeline for production hardening.
