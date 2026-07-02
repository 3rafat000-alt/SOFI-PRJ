{{--
  Component: <x-admin.data-table-new>

  Advanced data table with built-in client-side search, sort, pagination, and CSV export.
  Zero external JS dependencies — all logic self-contained at bottom of component.
  RTL-first, Arabic labels, uses SAKK CSS variables + existing table styles.

  Props:
    $id         (string)     — unique DOM id. Auto-generated if omitted.
    $headers    (string[])   — column header labels in display order (RTL: index 0 = rightmost column).
    $rows       (Collection) — collection of arrays, each inner array = cell values by index.
                               Pass AFTER mapping to desired columns:
                                 `$users->map(fn($u) => [$u->name, $u->email, $u->status])`
    $searchable (bool)       — show search input in toolbar. Default: true.
    $sortable   (bool)       — enable column sort on header click. Default: true.
    $exportable (bool)       — show CSV export button in toolbar. Default: true.
    $pageSize   (int)        — rows per page. Default: 20.

  Usage:
    <x-admin.data-table-new
        id="users-table"
        :headers="['الاسم', 'البريد', 'الحالة', 'تاريخ التسجيل']"
        :rows="$users->map(fn($u) => [$u->name, $u->email, $u->status, $u->created_at->format('Y-m-d')])"
        :page-size="15"
    />

  Empty state shows automatically when no data or search yields no results.
--}}

@props([
    'id'        => 'dtn-' . \Illuminate\Support\Str::random(6),
    'headers'   => [],
    'rows'      => collect(),
    'searchable' => true,
    'sortable'  => true,
    'exportable' => true,
    'pageSize'  => 20,
])

@php
    /*
     * Normalise each row to a flat numeric array for JSON payload.
     * Handles arrays and objects — re-indexes with array_values().
     * Empty rows array → empty state shown on render.
     */
    $rowsArray = $rows->map(function ($row) {
        if (is_array($row)) {
            return array_values($row);
        }
        if (is_object($row)) {
            return array_values((array) $row);
        }
        return [];
    })->values()->toArray();
@endphp

{{-- =================================================================
     ROOT WRAPPER
     ================================================================= --}}
<div class="table-wrap" id="{{ $id }}" dir="rtl">

    {{-- =============================================================
         TOOLBAR — search + export
         ============================================================= --}}
    @if($searchable || $exportable)
    <div class="table-toolbar">

        @if($searchable)
        <div class="input-group" style="width:240px;flex-shrink:0">
            {{-- Magnifier SVG --}}
            <svg class="input-icon input-icon-s"
                 aria-hidden="true"
                 width="16" height="16"
                 viewBox="0 0 24 24"
                 fill="none"
                 stroke="currentColor"
                 stroke-width="2"
                 stroke-linecap="round"
                 stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/>
                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="search"
                   class="input input-icon-start"
                   data-dtn-search="{{ $id }}"
                   placeholder="بحث..."
                   autocomplete="off"
                   aria-label="بحث في الجدول"
                   style="height:36px;padding-top:0;padding-bottom:0;font-size:var(--text-sm)">
        </div>
        @endif

        @if($exportable)
        <div style="display:flex;align-items:center;gap:var(--sp-2);margin-inline-start:auto;flex-wrap:wrap">
            <button type="button"
                    class="sakk-btn sakk-btn--ghost sakk-btn--sm"
                    data-dtn-export="{{ $id }}"
                    aria-label="تصدير CSV">
                {{-- Download SVG --}}
                <svg aria-hidden="true"
                     width="14" height="14"
                     viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="2"
                     stroke-linecap="round"
                     stroke-linejoin="round"
                     style="vertical-align:middle;margin-inline-end:4px">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                <span>تصدير CSV</span>
            </button>
        </div>
        @endif

    </div>
    @endif

    {{-- =============================================================
         TABLE SCROLL CONTAINER
         ============================================================= --}}
    <div class="sakk-table-wrap" role="region" aria-label="جدول البيانات" tabindex="0">
        <table class="data-table" data-table-id="{{ $id }}">
            <thead>
                <tr>
                    @foreach($headers as $colIndex => $label)
                    <th data-col="{{ $colIndex }}"
                        @if($sortable)
                        class="sortable"
                        tabindex="0"
                        role="columnheader"
                        aria-sort="none"
                        @endif>
                        {{ $label }}
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                {{-- Populated entirely by JS --}}
            </tbody>
        </table>
    </div>

    {{-- =============================================================
         EMPTY STATE — shown when no rows or search yields zero
         ============================================================= --}}
    <div class="data-table-empty" style="display:none" role="status" aria-live="polite">
        <svg class="table-empty-icon"
             aria-hidden="true"
             width="48" height="48"
             viewBox="0 0 24 24"
             fill="none"
             stroke="currentColor"
             stroke-width="1.4"
             stroke-linecap="round"
             stroke-linejoin="round">
            <polyline points="21 8 21 21 3 21 3 8"/>
            <rect x="1" y="3" width="22" height="5"/>
            <line x1="10" y1="12" x2="14" y2="12"/>
        </svg>
        <p class="data-table-empty-text"
           style="font-size:var(--text-sm);font-weight:500;color:var(--text-muted);margin:0">
            لا توجد بيانات
        </p>
    </div>

    {{-- =============================================================
         PAGINATION CONTROLS
         ============================================================= --}}
    <div class="data-table-pagination" style="display:none">
        <div class="sakk-pagination-wrap">
            <p class="sakk-pagination-info"
               aria-live="polite"
               aria-atomic="true"
               data-dtn-info="{{ $id }}">
                عرض
                <strong data-dtn-from>0</strong>
                إلى
                <strong data-dtn-to>0</strong>
                من
                <strong data-dtn-total>0</strong>
                نتيجة
            </p>
            <nav class="sakk-pagination"
                 role="navigation"
                 aria-label="التنقل بين الصفحات"
                 data-dtn-nav="{{ $id }}">
                {{-- Previous (→ right-pointing in RTL) --}}
                <button type="button"
                        class="page-link page-link--arrow"
                        data-dtn-prev="{{ $id }}"
                        aria-label="الصفحة السابقة">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         width="16" height="16"
                         viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="2.2"
                         stroke-linecap="round"
                         stroke-linejoin="round"
                         aria-hidden="true">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </button>

                {{-- Page numbers injected by JS --}}
                <span class="page-link" data-dtn-pages="{{ $id }}"></span>

                {{-- Next (← left-pointing in RTL) --}}
                <button type="button"
                        class="page-link page-link--arrow"
                        data-dtn-next="{{ $id }}"
                        aria-label="الصفحة التالية">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         width="16" height="16"
                         viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="2.2"
                         stroke-linecap="round"
                         stroke-linejoin="round"
                         aria-hidden="true">
                        <polyline points="15 18 9 12 15 6"/>
                    </svg>
                </button>
            </nav>
        </div>
    </div>

</div>

{{-- =================================================================
     EMBEDDED JSON DATA PAYLOAD
     ================================================================= --}}
<script type="application/json" data-dtn-payload="{{ $id }}">{
    "headers": {{ Js::from($headers) }},
    "rows":    {{ Js::from($rowsArray) }},
    "pageSize": {{ $pageSize }}
}</script>

{{-- =================================================================
     SELF-CONTAINED JAVASCRIPT
     ================================================================= --}}
<script>
(function () {
    'use strict';

    /* ── Init ──────────────────────────────────────────────────── */
    var id = {{ Js::from($id) }};
    var container = document.getElementById(id);
    if (!container) return;

    var payloadEl = document.querySelector('script[data-dtn-payload="' + id + '"]');
    if (!payloadEl) return;
    var payload;
    try { payload = JSON.parse(payloadEl.textContent); } catch (_) { return; }

    var headers  = payload.headers  || [];
    var allRows  = payload.rows     || [];
    var pageSize = payload.pageSize || 20;

    /* ── DOM refs ──────────────────────────────────────────────── */
    var tbody        = container.querySelector('tbody');
    var emptyEl      = container.querySelector('.data-table-empty');
    var pagWrap      = container.querySelector('.data-table-pagination');
    var infoEl       = container.querySelector('[data-dtn-info]');
    var fromEl       = infoEl ? infoEl.querySelector('[data-dtn-from]') : null;
    var toEl         = infoEl ? infoEl.querySelector('[data-dtn-to]')   : null;
    var totalEl      = infoEl ? infoEl.querySelector('[data-dtn-total]'): null;
    var navEl        = container.querySelector('[data-dtn-nav]');
    var prevBtn      = container.querySelector('[data-dtn-prev]');
    var nextBtn      = container.querySelector('[data-dtn-next]');
    var pagesEl      = container.querySelector('[data-dtn-pages]');
    var searchInput  = container.querySelector('[data-dtn-search]');
    var exportBtn    = container.querySelector('[data-dtn-export]');
    var sortHeaders  = container.querySelectorAll('th[data-col].sortable');

    /* ── State ─────────────────────────────────────────────────── */
    var state = {
        query:   '',
        sortCol: -1,
        sortAsc: true,
        page:    1,
    };

    /* ── HTML escape helper (prevents XSS in innerHTML sink) ──── */
    function escHtml(s) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(s == null ? '' : String(s)));
        return d.innerHTML;
    }

    /* ── Data helpers ──────────────────────────────────────────── */
    function getFiltered() {
        var q = state.query.trim().toLowerCase();
        if (!q) return allRows.slice();
        return allRows.filter(function (row) {
            return row.some(function (cell) {
                return String(cell != null ? cell : '').toLowerCase().indexOf(q) !== -1;
            });
        });
    }

    function getSorted(rows) {
        if (state.sortCol < 0) return rows;
        var col = state.sortCol;
        var dir = state.sortAsc ? 1 : -1;
        return rows.slice().sort(function (a, b) {
            var va = String(a[col] != null ? a[col] : '');
            var vb = String(b[col] != null ? b[col] : '');
            var na = parseFloat(va.replace(/[^\d.\-]/g, ''));
            var nb = parseFloat(vb.replace(/[^\d.\-]/g, ''));
            if (!isNaN(na) && !isNaN(nb)) return (na - nb) * dir;
            return va.localeCompare(vb, 'ar') * dir;
        });
    }

    function updateSortUI() {
        sortHeaders.forEach(function (th) {
            var col = parseInt(th.dataset.col, 10);
            th.classList.remove('sort-asc', 'sort-desc');
            th.setAttribute('aria-sort', 'none');
            if (col === state.sortCol) {
                th.classList.add(state.sortAsc ? 'sort-asc' : 'sort-desc');
                th.setAttribute('aria-sort', state.sortAsc ? 'ascending' : 'descending');
            }
        });
    }

    /* ── Render ────────────────────────────────────────────────── */
    function render() {
        var filtered = getFiltered();
        var sorted   = getSorted(filtered);
        var total    = sorted.length;
        var totalPages = Math.max(1, Math.ceil(total / pageSize));

        if (state.page > totalPages) state.page = totalPages;
        if (state.page < 1)          state.page = 1;

        var start     = (state.page - 1) * pageSize;
        var pageRows  = sorted.slice(start, start + pageSize);
        var hasData   = pageRows.length > 0;

        /* ── Toggle empty / pagination ─────────────────────────── */
        emptyEl.style.display  = hasData ? 'none' : '';
        pagWrap.style.display  = hasData ? ''     : 'none';

        /* ── Rows ──────────────────────────────────────────────── */
        if (hasData) {
            tbody.innerHTML = pageRows.map(function (row) {
                return '<tr>' + row.map(function (cell) {
                    return '<td>' + escHtml(cell) + '</td>';
                }).join('') + '</tr>';
            }).join('');

            /* ── Pagination info ───────────────────────────────── */
            if (fromEl)  fromEl.textContent  = start + 1;
            if (toEl)    toEl.textContent    = Math.min(start + pageSize, total);
            if (totalEl) totalEl.textContent = total;

            renderPageButtons(totalPages);
            updateNavButtons(totalPages);
        } else {
            tbody.innerHTML = '';
            var msg = state.query.trim()
                ? 'لا توجد نتائج للبحث'
                : 'لا توجد بيانات';
            var textEl = emptyEl.querySelector('.data-table-empty-text');
            if (textEl) textEl.textContent = msg;
        }

        updateSortUI();
    }

    /* ── Page buttons ──────────────────────────────────────────── */
    function renderPageButtons(totalPages) {
        if (!pagesEl) return;

        if (totalPages <= 1) {
            pagesEl.innerHTML =
                '<span class="page-link active" aria-current="page" ' +
                'aria-label="الصفحة 1، الحالية">1</span>';
            return;
        }

        var win = 2;
        var cur = state.page;

        /* Build set of page numbers to show */
        var showSet = {};
        showSet[1]         = true;
        showSet[totalPages]= true;
        for (var i = Math.max(1, cur - win); i <= Math.min(totalPages, cur + win); i++) {
            showSet[i] = true;
        }
        var show = Object.keys(showSet).map(Number).sort(function (a, b) { return a - b; });

        var parts = [];
        var prev = 0;
        show.forEach(function (p) {
            if (prev > 0 && p - prev > 1) {
                parts.push(
                    '<span class="page-link page-link--ellipsis" aria-hidden="true">…</span>'
                );
            }
            if (p === cur) {
                parts.push(
                    '<span class="page-link active" aria-current="page" ' +
                    'aria-label="الصفحة ' + p + '، الحالية">' + p + '</span>'
                );
            } else {
                parts.push(
                    '<button type="button" class="page-link" data-dtn-page="' + p + '" ' +
                    'aria-label="انتقل إلى الصفحة ' + p + '">' + p + '</button>'
                );
            }
            prev = p;
        });

        pagesEl.innerHTML = parts.join('');

        /* Attach click on dynamically created page buttons */
        pagesEl.querySelectorAll('[data-dtn-page]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                state.page = parseInt(this.getAttribute('data-dtn-page'), 10);
                render();
            });
        });
    }

    function updateNavButtons(totalPages) {
        if (prevBtn) prevBtn.disabled = state.page <= 1;
        if (nextBtn) nextBtn.disabled = state.page >= totalPages;
    }

    /* ── Search ────────────────────────────────────────────────── */
    if (searchInput) {
        var debounceTimer;
        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            var self = this;
            debounceTimer = setTimeout(function () {
                state.query = self.value;
                state.page  = 1;
                render();
            }, 200);
        });
        /* Clear search on Escape */
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                this.value  = '';
                state.query = '';
                state.page  = 1;
                render();
            }
        });
    }

    /* ── Sort ──────────────────────────────────────────────────── */
    sortHeaders.forEach(function (th) {
        th.addEventListener('click', function () {
            var col = parseInt(this.dataset.col, 10);
            if (state.sortCol === col) {
                /* Toggle direction on same column */
                state.sortAsc = !state.sortAsc;
            } else {
                state.sortCol = col;
                state.sortAsc = true;
            }
            state.page = 1;
            render();
        });
        th.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });

    /* ── Export CSV ────────────────────────────────────────────── */
    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            var sep   = ',';
            var lines = [];

            /* Header row */
            lines.push(headers.map(function (h) {
                return '"' + String(h).replace(/"/g, '""') + '"';
            }).join(sep));

            /* All data rows (not just current page) */
            allRows.forEach(function (row) {
                lines.push(row.map(function (cell) {
                    var val = cell != null ? String(cell) : '';
                    return '"' + val.replace(/"/g, '""') + '"';
                }).join(sep));
            });

            var csv  = lines.join('\r\n');
            var bom  = '\uFEFF'; /* BOM for Excel Arabic support */
            var blob = new Blob([bom + csv], { type: 'text/csv;charset=utf-8;' });
            var url  = URL.createObjectURL(blob);
            var a    = document.createElement('a');
            a.href   = url;
            a.download = 'export-' + id + '.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        });
    }

    /* ── Pagination nav ────────────────────────────────────────── */
    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            if (state.page > 1) { state.page--; render(); }
        });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            state.page++;
            render();
        });
    }

    /* ── Init ──────────────────────────────────────────────────── */
    render();
})();
</script>
