# JS FIX LOG — SAKK Admin Panel

**Engineer:** Lars Eriksson · JS/Vue.js Engineer (Tier 2)
**Date:** 2026-06-29
**Scope:** JS audit findings remediation

---

## CRITICAL — DOM XSS in fees/index.blade.php (S-01)

**File:** `backend/resources/views/admin/fees/index.blade.php:315`
**Fix:** Replaced unsafe `resultDiv.innerHTML = '<span ...>${data.message || data.error}</span>'` with `resultDiv.textContent = data.message || data.error || '...'; resultDiv.style.color = '#fca5a5';`
**Added:** `escHtml()` sanitizer for all dynamic values in success path (fee, net_amount, currency).
**Verification:** `grep` confirms no unsanitized `innerHTML` with user-controlled data remains.

## HIGH — Tailwind CDN (S-02)

**Status:** Tailwind CDN (`cdn.tailwindcss.com`) was already removed from admin.blade.php prior to this session. Confirmed by grep — no reference found in any admin view.
**Added:** Comprehensive CSS utility class block (~300 lines) replacing all Tailwind utility classes used in admin.blade.php HTML: flexbox, spacing, sizing, typography, display, position, overflow, z-index, cursor, opacity, animation, responsive breakpoints. Classes defined as `!important` to match Tailwind's specificity behavior.
**Verification:** `php -l` on admin.blade.php passes. `php artisan view:cache` compiles clean.

## HIGH — Chat polling visibilitychange (S-03)

**Files:**
- `backend/resources/views/admin/chat/show.blade.php:83` — `setInterval(poll, 3000)` now pauses on tab hide, resumes on show.
- `backend/resources/views/admin/chat/index.blade.php:70` — `setInterval(refresh, 4000)` same pattern.
**Pattern:** `document.addEventListener('visibilitychange', () => { if (document.hidden) clearInterval(pollInt); else if (!pollInt) pollInt = setInterval(fn, ms); })`

## MEDIUM — Orphaned admin.js.orphaned (S-04)

**File:** `public/sakk-admin/admin.js.orphaned` (1629 lines)
**Assessment:** ALL functionality replicated in Alpine.js or per-page inline scripts. Canvas chart code (`enforceLightOnly`, `paintGrid`, etc.) targets element IDs `sakk-tx-chart`/`sakk-users-chart` which don't exist in any loaded views. Dashboard uses Chart.js instead. No code worth extracting.
**Recommendation:** Delete or archive the orphaned file.

## MEDIUM — SRI hashes added (S-05)

**Files:** `backend/resources/views/layouts/admin.blade.php`
- Chart.js: pinned to `4.4.7`, integrity `sha384-vsrfeLOOY6KuIYKDlmVH5UiBmgIdB1oEf7p01YgWHuqmOHfZr374+odEv96n9tNC`
- Alpine.js CSP: pinned to `3.14.8`, integrity `sha384-ToFwPnlRgZIoDDSatKvWLkecUR4Py5dma633TT4TVBQfs4nHKXXexI3v72xi5LnC`
- Both use `crossorigin="anonymous"` for SRI compliance.

## MEDIUM — Additional DOM XSS fixes

### backup.blade.php (S-02)
**File:** `backend/resources/views/admin/system/backup.blade.php:410`
**Fix:** `msg.innerHTML` concatenation with `filename` replaced with `createTextNode()` + `msg.querySelector('strong').appendChild(safeName)`.

### messages.blade.php (S-04)
**File:** `backend/resources/views/admin/system/messages.blade.php:132`
**Fix:** Wrapped `highlightVars()` in `setHighlighted()` helper for clarity. `highlightVars()` already HTML-escapes input before adding `<span>` highlighting.

## CSP Update

**File:** `app/Http/Middleware/SecurityHeaders.php`
**Removed from CSP:**
- `https://cdn.tailwindcss.com` from `script-src` (CDN removed)
- `https://cdn.jsdelivr.net` from `style-src` (no styles loaded from jsdelivr)
- `https://cdn.jsdelivr.net` from `connect-src` (no XHR to jsdelivr)
- `https://cdn.jsdelivr.net` from `font-src` (fonts self-hosted)
**Kept:** `'unsafe-inline'` (31 inline scripts remain), `https://cdn.jsdelivr.net` in `script-src` (Alpine + Chart.js).
**Tests:** `SecurityHeadersTest` — 4/4 passed.

## Verification

| Check | Result |
|---|---|
| `php -l` on all changed files | PASS |
| `php artisan view:cache` | PASS |
| `grep -rn "innerHTML\|outerHTML\|insertAdjacentHTML" resources/views/admin/` | All remaining uses are static strings or server-controlled HTML (safe) |
| `grep -rn "eval\|new Function\|document.write" resources/views/` | 0 matches |
| `SecurityHeadersTest` | 4/4 PASS |
| CSP `script-src` updated | `cdn.tailwindcss.com` removed ✅ |

## Summary

| Priority | Issue | Status |
|---|---|---|
| Critical | DOM XSS fees/index.blade.php (innerHTML) | ✅ FIXED |
| High | Tailwind CDN | ✅ Already removed; utility CSS added |
| High | Chat polling visibilitychange | ✅ FIXED |
| Medium | Orphaned admin.js.orphaned | ⚠️ Reviewed — dead code, no extraction needed |
| Medium | SRI hashes for CDN scripts | ✅ FIXED |
| Medium | CSP cleanup (remove Tailwind CDN) | ✅ FIXED |
| Low | Additional innerHTML patterns (backup, messages) | ✅ FIXED |
