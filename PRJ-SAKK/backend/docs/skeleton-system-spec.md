# Whole-Page Skeleton System — Architecture Spec

> 🛑 **CEO RULING 2026-07-01 — §1-4 whole-page rollout NOT BUILT (rejected).** SAKK admin is
> server-rendered Blade: content is in first-byte HTML, so a full-page skeleton over
> paint/fonts-ready injects an artificial delay veil over already-present content = fake perceived
> perf. Skeletons apply ONLY to genuinely-async regions (empty→filled by client fetch). Audit
> (Minh) found the ONLY such regions = transactions + users quick-view slide-overs, and both
> ALREADY have correct skeletons (`.skeleton` blocks, `loading` Alpine gate, hide on success+error).
> Nothing to build. Keep this spec as reference for the async pattern + archetype inventory only;
> do NOT implement §2's whole-page swap-contract on synchronous pages.

**Author:** Théo Laurent (Frontend Tech Lead) · **Gate:** 4 (Build) · **PRJ-SAKK**
**Status:** architecture only — no implementation. Builder: Minh (blade-architect) + Lars (Alpine/JS) for the swap script.
**Frozen constraint:** do not touch `public/css/admin/base.css:1649` canonical `.skeleton`/`.sakk-skeleton` shimmer primitive. Build strictly on top of it.

## 0. Problem with current state

Grep confirms skeleton usage today is piecemeal: `x-admin.skeleton` / `.sakk-skeleton` appear scattered inside individual widgets (`users/partials/_slide_over.blade.php`, `transactions/partials/_modals.blade.php`, `_kpi_card_grid.blade.php`, per-page tables) with no shared page-level shell and no synchronized reveal. Each widget pops in independently → staggered, unprofessional "popcorn" loading. There is no `x-cloak`-driven full-page skeleton anywhere in `resources/views/admin/**`. This spec replaces that with one page-level contract, reused by every archetype.

Stack fact that shapes the contract: SAKK admin is server-rendered Blade + Alpine (no client-side data fetching/hydration framework on first paint — `@yield('content')` in `layouts/admin.blade.php:349` renders real content immediately from the server). There is no async data-loading gap to skeleton over in the traditional SPA sense. The "load" we're skeletoning is **paint completion** (fonts, images, Alpine directive initialization, layout stabilization) — not a data fetch. This changes the ready-signal design in §2.

## 1. Archetypes — view inventory clustered by layout shape

6 archetypes cover all ~92 admin blade views (30 top-level pages + partials). Mapping grounded in `resources/views/admin/**` grep.

| # | Archetype | Shape | Representative real views |
|---|-----------|-------|---------------------------|
| A | **List/Table page** | filter bar + KPI strip (optional) + data table + pagination | `users/index.blade.php`, `agents/index.blade.php`, `merchants/index.blade.php`, `companies/index.blade.php`, `transactions/index.blade.php`, `withdrawals/index.blade.php`, `audit/index.blade.php`, `notifications/index.blade.php`, `chat/index.blade.php`, `support/index.blade.php`, `gold/transactions.blade.php` |
| B | **Dashboard / stats+widgets page** | KPI cards grid + 2-3 column widget grid (chart/list/aside cards), no single table | `dashboard/index.blade.php` (+ partials: `kpi_cards`, `balances-card`, `recent_ledger(s)`, `pending-kyc`, `system_alerts`, `quick_actions`, `aside_widgets`, `welcome_card`), `agents/dashboard.blade.php`, `merchants/dashboard.blade.php`, `gold/index.blade.php`, `gold/prices.blade.php` |
| C | **Form/settings page** | single or tabbed card with label/input groups, save footer | `settings/index.blade.php`, `fees/index.blade.php`, `exchange-rates/index.blade.php`, `profile/index.blade.php`, `profile/two-factor/index.blade.php`, `integrations/overview.blade.php`, `system/app-update.blade.php`, `system/backup.blade.php`, `system/channels.blade.php`, `system/maintenance.blade.php`, `system/messages.blade.php`, `system/support.blade.php` (all via shared `system/_shell.blade.php`), `agents/create.blade.php`, `agents/edit.blade.php`, `merchants/create.blade.php`, `merchants/edit.blade.php`, `companies/create.blade.php`, `companies/edit.blade.php` |
| D | **Detail/record page** | header/identity block + stat row + tabbed sections (timeline, documents, ledger) | `users/show.blade.php`, `agents/show.blade.php`, `merchants/show.blade.php`, `companies/show.blade.php`, `transactions/show.blade.php`, `withdrawals/show.blade.php`, `audit/show.blade.php`, `support/show.blade.php` |
| E | **Split/master-detail (slide-over or 2-pane)** | list/table pane + slide-over or side panel with detail, or document viewer split | `chat/show.blade.php`, `transactions/partials/_slide_over.blade.php` (embedded in Archetype A pages), `users/partials/_slide_over.blade.php` (ditto), `agents/documents.blade.php` + `_document_viewer.blade.php`, `merchants/documents.blade.php`, `companies/documents.blade.php`, `agents/documents-show.blade.php`, `merchants/documents-show.blade.php`, `companies/documents-show.blade.php` |
| F | **Auth/standalone page (no admin chrome)** | centered card, no sidebar/topbar — **excluded from this system**: too small/fast for a full-page skeleton to add value (already perf-fixed per STATE.md critical-CSS work) | `auth/login.blade.php`, `auth/two-factor.blade.php` |

Note on E: slide-overs/split panes are usually a secondary reveal *inside* an Archetype A host page, not a standalone route — they get their own **panel-level** skeleton sub-variant (§3, `panel` slot) triggered independently when the user opens them (data genuinely async there — via existing AJAX/Alpine fetch), not part of the initial single-swap. Document-viewer full pages (`documents.blade.php` index views) behave as Archetype A (table) with an E sub-panel.

## 2. Contract — single-swap mechanism

Core idea: server renders **both** the full skeleton shell and the full real content in the same response, stacked in the DOM. Alpine shows exactly one of them, flips once, atomically, no stagger.

```
<body x-data="pageReady()" x-init="init()">
  <div class="sakk-page-skeleton" x-show="!ready" x-cloak>{{-- full skeleton, mirrors real layout box-for-box --}}</div>
  <div class="sakk-page-content"  x-show="ready"  x-cloak>{{ real @yield('content') }}</div>
</body>
```

1. **Ready trigger** — `Alpine.data('pageReady', () => ({ ready:false, init(){ requestAnimationFrame(()=>requestAnimationFrame(()=>{ this.ready = document.fonts?.ready ? false : true; document.fonts.ready.then(()=> this.ready = true); if(!document.fonts) this.ready = true; }) ) } }))`. Practically: flip `ready = true` on `document.fonts.ready` (IBM Plex Sans Arabic is the CLS/FOUT risk) **and** on a `window.load` fallback with a max 400ms cap via `setTimeout`, whichever fires first is irrelevant — use `Promise.race([fonts.ready, timeout(400)])`. This is the only JS Minh/Lars need; no per-widget readiness polling.
2. **One swap, not staggered** — a single boolean (`ready`) gates both `x-show` blocks via Alpine's reactive re-render, which is one microtask — CSS `x-show` toggles `display` on both nodes in the same paint frame. No sequencing of individual skeleton blocks; do not resolve KPI-card skeleton before table skeleton, etc.
3. **Zero CLS** — the skeleton shell and the real content shell MUST share identical outer box dimensions (grid template, card min-heights, table row-height × count, KPI card fixed height). This is achieved by the skeleton components reusing the *same* Blade partial's outer wrapper classes (`.card`, `.stat-card`, `.table`) and only swapping *inner* content for `.sakk-skeleton-*` blocks — never a different container. Concretely: skeleton and content are siblings inside the same `.card`/grid wrapper markup pattern, so swapping visibility never changes layout box size. Row/card *counts* in the skeleton must match realistic counts (§3 `rows`/`count` props) sized to viewport-typical results, accepting that below/above that count is a rare, acceptable minor reflow (unavoidable without knowing real data length pre-render — but since content is server-rendered, we DO know the real count at render time, so the skeleton can be told `rows = count(actual collection)` — true zero CLS, not just typical-case).
4. **No-JS degradation** — `x-cloak` + `[x-cloak]{display:none!important}` (already global in admin.blade.php, verify Grace has it in base.css) hides both blocks until Alpine boots; if JS is fully disabled, Alpine never boots, `x-cloak` never lifts, user sees nothing rendered — **unacceptable regression**. Fix: skeleton lives inside `x-show`, but the *real content* block gets `<noscript>` override: `<noscript><style>.sakk-page-content{display:block!important}.sakk-page-skeleton{display:none!important}</style></noscript>` right after it, so no-JS users get real content immediately, JS users get the skeleton-then-swap. This is the only new pattern beyond existing conventions.
5. **RTL correctness** — skeleton blocks are plain `div`s with `width`/`height` styles only (per existing `skeleton.blade.php` props) — no directional CSS (`margin-left` etc.) inside the primitive, so RTL is inherited for free from the page's `dir="rtl"` on `<html>`. Table-row skeletons must lay out columns in the same logical (not physical) order as the real `<table>` — i.e., generate skeleton cells via the same Blade `@foreach` over the real column config, not a hardcoded LTR array.
6. **Reduced motion** — already guarded at the primitive level (`base.css:1649` block references reduced-motion guard on the canonical `.skeleton`/`.sakk-skeleton`) — nothing new needed here; page-level wrapper adds no new animation, only the existing shimmer per skeleton block.
7. **Perf budget** — this is a display-toggle over content that's already server-rendered and inline in the DOM (not a second fetch), so it costs ~0 network/JS beyond one small Alpine component (~15 lines) — consistent with "few token do trick" / token-frugal JS mandate.

## 3. Component API (spec only — Minh builds)

### 3.1 Page-level wrapper (new)
`<x-admin.skeleton-page :type="'table'|'dashboard'|'form'|'detail'" :rows="8" :cards="4">`
- **Props:**
  - `type` (string, required) — one of the 5 archetypes: `table`, `dashboard`, `form`, `detail`, `split`.
  - `rows` (int, default 8) — for `table`/`split`: number of skeleton table rows. Caller passes the **real** collection count where known (`:rows="$items->count()"`) for true zero-CLS per §2.3; falls back to 8 for empty/first-load states.
  - `cards` (int, default per-type) — for `dashboard`: number of KPI/widget skeleton cards; for `detail`: number of stat-strip cards.
  - `filters` (bool, default true for `table`) — whether to render a filter-bar skeleton row above the table skeleton.
  - `tabs` (int, default 0) — for `detail`/`form`: number of tab-nav skeleton pills, 0 = no tab bar.
- **Slots:** none — this component is fully prop-driven per archetype so every page gets a consistent shape without bespoke markup. If a page has a genuinely unique layout wrinkle within an archetype, extend via a documented default value rather than a slot (keeps the pattern from drifting page-by-page — the whole point of "unified pattern across every admin page").
- **Internals (for Minh, not the caller):** renders the archetype-specific skeleton composition by delegating to the existing atomic `<x-admin.skeleton type="..." />` for lines/inputs/badges, `.stat-card`-shaped wrapper divs for KPI skeletons, and `.table`-shaped wrapper for row skeletons — always reusing the real component's outer CSS class so §2.3 zero-CLS holds structurally, not just by accident.

### 3.2 Panel-level wrapper (new, for Archetype E sub-panels)
`<x-admin.skeleton-panel :type="'slideover'|'viewer'" />` — same idea, scoped to the slide-over/document-viewer panel body only; triggered by that panel's own existing open/loading Alpine state (not part of the page-level single-swap, since panels open post-interaction and are already async today).

### 3.3 Layout opt-in mechanism
`layouts/admin.blade.php` gains one new optional section, not a breaking change to the 174-view Blade base:
```blade
@hasSection('page-skeleton')
  <div class="sakk-page-skeleton" x-show="!ready" x-cloak>@yield('page-skeleton')</div>
@endif
<div class="sakk-page-content" @if(View::hasSection('page-skeleton')) x-show="ready" x-cloak @endif>
  @yield('content')
</div>
```
Pages that don't opt in (`@section('page-skeleton')` absent) render exactly as today — zero regression risk, incremental rollout page-by-page. Opt-in page usage:
```blade
@section('page-skeleton')
  <x-admin.skeleton-page type="table" :rows="$users->count()" />
@endsection
@section('content')
  {{-- existing real markup, unchanged --}}
@endsection
```
The `pageReady()` Alpine component is registered once, globally, in `admin.blade.php`'s existing Alpine bootstrap block (same place `sidebarLayout`/`toastSystem`/`confirmModal` already live per grep) — no per-page JS.

## 4. Build sequence for Minh (+ Lars for the Alpine ready-component)

1. **Lars** — add `pageReady()` Alpine component to `layouts/admin.blade.php`'s existing global Alpine registration block (~10 lines, `document.fonts.ready` + 400ms race). Add the `@hasSection('page-skeleton')` wrapper + `<noscript>` no-JS fallback around `@yield('content')` (§2.4, §3.3). No change to any other page.
2. **Minh** — build `<x-admin.skeleton-page>` (5 `type` branches) and `<x-admin.skeleton-panel>` (2 `type` branches) in `resources/views/components/admin/`, composed strictly from the existing `<x-admin.skeleton>` atom + real component wrapper classes (`.card`, `.stat-card`, `.table`, `.filter-bar` if it exists — verify class names against one real view per archetype before building, e.g. `users/index.blade.php` for table, `dashboard/index.blade.php` for dashboard, `settings/index.blade.php` for form, `users/show.blade.php` for detail).
3. **Minh** — roll out `@section('page-skeleton')` opt-in to all ~30 top-level admin pages, one archetype at a time (table pages first — largest count, highest perceived-load value), passing real collection counts where available for exact-match row counts.
4. **Minh** — wire Archetype E panels (`_slide_over.blade.php`, `_document_viewer.blade.php`) to `<x-admin.skeleton-panel>` using their existing open-state Alpine variable as the ready flag (already async today, no new mechanism needed beyond swapping their current ad-hoc skeleton markup for the new component).
5. **Théo (me)** — design-fidelity + gate-bar audit pass per page once Minh ships a batch.

### Gate-bar (must all hold before Gate 4 sign-off on this feature)
- CLS = 0 measured (Lighthouse or manual: skeleton and content boxes are pixel-identical per archetype).
- Exactly one swap observed per page load — no staggered/sequential reveal of individual widgets (verify via DevTools Performance: single re-render frame flips all `x-show` nodes together).
- All 5 non-auth archetypes have a shipped `type` variant; all ~30 top-level pages opted in (Archetype F auth pages explicitly excluded, documented).
- Works with JS disabled (noscript fallback shows real content, no blank page).
- RTL verified on at least one table page + one form page (logical column order, no LTR leakage).
- `prefers-reduced-motion` still suppresses shimmer (inherited from frozen primitive — regression-check only, not new work).
- Zero changes to `base.css:1649` canonical skeleton primitive or its keyframes.
