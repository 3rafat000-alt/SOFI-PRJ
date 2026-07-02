{{--
  Component: <x-admin.data-table>

  Props:
    $id          (string)  — unique id; wires data-search-table + data-table-id. Default: 'dt'.
    $searchable  (bool)    — show search input in toolbar. Default: true.
    $searchCols  (string)  — comma-separated 0-based column indexes to search. Default: '' (all).
    $searchPlaceholder (string) — placeholder for search input. Default: 'بحث...'.
    $sort        (bool)    — enable client-side column sort on th[data-col]. Default: false.
    $caption     (string)  — accessible <caption> for screen readers. Default: ''.
    $striped     (bool)    — add alternating row shading. Default: false.
    $stickyHead  (bool)    — make <thead> sticky on scroll. Default: false.
    $minWidth    (string)  — min-width of the overflow wrapper e.g. '640px'. Default: ''.
    $tabs        (array)   — segmented tab definitions. Each entry: ['id'=>'', 'label'=>'', 'count'=>null].
                             If non-empty a tabs bar is rendered above the toolbar. Default: [].
    $activeTab   (string)  — id of the initially active tab. Default: first tab.

  Named slots:
    $toolbar     — extra content injected at the end of the toolbar row (filters, export buttons, etc.)
    $head        — one or more <th> elements rendered inside <thead><tr>
    $empty       — shown when tbody has no rows; default is a generic table-empty state
    default      — <tr> rows for <tbody>

  Usage — basic:
    <x-admin.data-table id="users-table" search-cols="0,1,2">
        <x-slot:head>
            <th>الاسم</th>
            <th>البريد</th>
            <th>الحالة</th>
        </x-slot:head>
        @foreach($users as $u)
            <tr>
                <td>{{ $u->name }}</td>
                <td>{{ $u->email }}</td>
                <td><x-admin.badge type="active">نشط</x-admin.badge></td>
            </tr>
        @endforeach
    </x-admin.data-table>

  Usage — with tabs + toolbar:
    <x-admin.data-table id="tx-table" :tabs="[
        ['id'=>'all',     'label'=>'الكل',    'count'=>$total],
        ['id'=>'pending', 'label'=>'معلقة',   'count'=>$pending],
        ['id'=>'done',    'label'=>'مكتملة',  'count'=>$done],
    ]" active-tab="all">
        <x-slot:toolbar>
            <x-admin.button variant="ghost" size="sm" icon="file_download">تصدير CSV</x-admin.button>
        </x-slot:toolbar>
        <x-slot:head>
            <th data-col="0">رقم المعاملة</th>
            <th data-col="1">المبلغ</th>
            <th data-col="2">الحالة</th>
        </x-slot:head>
        @foreach($transactions as $tx) ... @endforeach
    </x-admin.data-table>
--}}

@props([
    'id'                => 'dt',
    'searchable'        => true,
    'searchCols'        => '',
    'searchPlaceholder' => 'بحث...',
    'sort'              => false,
    'caption'           => '',
    'striped'           => false,
    'stickyHead'        => false,
    'minWidth'          => '',
    'tabs'              => [],
    'activeTab'         => '',
])

@php
    $resolvedActiveTab = $activeTab ?: (isset($tabs[0]['id']) ? $tabs[0]['id'] : '');
    $hasTabs           = !empty($tabs);
    $hasToolbar        = $searchable || isset($toolbar);

    $tableClasses = 'data-table';
    if ($striped)    $tableClasses .= ' data-table--striped';
    if ($stickyHead) $tableClasses .= ' data-table--sticky-head';
    if ($sort)       $tableClasses .= ' data-table--sortable';
@endphp

{{-- =====================================================================
     ROOT WRAPPER
     ===================================================================== --}}
<div class="table-wrap"
     @if($hasTabs) data-tabs @endif
     id="table-wrap-{{ $id }}">

    {{-- =================================================================
         SEGMENTED TABS BAR (optional)
         ================================================================= --}}
    @if($hasTabs)
    <div class="tabs"
         role="tablist"
         aria-label="{{ $caption ?: 'أقسام الجدول' }}"
         style="padding: 0 var(--sp-5, 1.25rem); border-bottom: 1.5px solid rgba(0,0,0,0.08); background: var(--surface)">
        @foreach($tabs as $tab)
        <button
            type="button"
            class="tab-btn{{ $tab['id'] === $resolvedActiveTab ? ' active' : '' }}"
            role="tab"
            data-tab="{{ $tab['id'] }}"
            aria-selected="{{ $tab['id'] === $resolvedActiveTab ? 'true' : 'false' }}"
            aria-controls="tab-panel-{{ $tab['id'] }}"
            id="tab-btn-{{ $tab['id'] }}">
            {{ $tab['label'] }}
            @if(isset($tab['count']) && $tab['count'] !== null)
                <span class="tab-badge" aria-label="{{ $tab['count'] }} عنصر">{{ $tab['count'] }}</span>
            @endif
        </button>
        @endforeach
    </div>
    @endif

    {{-- =================================================================
         TOOLBAR (search + injected slot)
         ================================================================= --}}
    @if($hasToolbar)
    <div class="table-toolbar">

        {{-- Search input (RTL: icon on the right [inline-start in LTR = inline-end in RTL]) --}}
        @if($searchable)
        <div class="input-group" style="width: 240px; flex-shrink: 0">
            {{-- Inline SVG magnifier — no CDN, no Material Icons font dependency --}}
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
            <input
                type="search"
                class="input input-icon-start"
                data-search-table="{{ $id }}"
                @if($searchCols) data-search-cols="{{ $searchCols }}" @endif
                placeholder="{{ $searchPlaceholder }}"
                autocomplete="off"
                aria-label="{{ $searchPlaceholder }}"
                style="height: 36px; padding-top: 0; padding-bottom: 0; font-size: var(--text-sm)">
        </div>
        @endif

        {{-- Injected toolbar content (buttons, filters, etc.) --}}
        @if(isset($toolbar))
        <div style="display: flex; align-items: center; gap: var(--sp-2); margin-inline-start: auto; flex-wrap: wrap">
            {{ $toolbar }}
        </div>
        @endif

    </div>
    @endif

    {{-- =================================================================
         TABLE SCROLL CONTAINER
         ================================================================= --}}
    <div style="overflow-x: auto{{ $minWidth ? '; min-width: ' . $minWidth : '' }}">
        <table
            class="{{ $tableClasses }}"
            id="{{ $id }}"
            data-table-id="{{ $id }}"
            @if($sort) data-sortable @endif
            @if($caption) aria-label="{{ $caption }}" @endif
            role="grid">

            {{-- Accessible caption --}}
            @if($caption)
            <caption class="sr-only">{{ $caption }}</caption>
            @endif

            {{-- Head --}}
            @if(isset($head))
            <thead>
                <tr>{{ $head }}</tr>
            </thead>
            @endif

            {{-- Body --}}
            <tbody>
                @if($slot->isNotEmpty())
                    {{ $slot }}
                @else
                    {{-- Empty state row — shown by JS search + by default when no rows --}}
                    <tr data-empty-row>
                        <td colspan="99" style="padding: 0; border-bottom: none">
                            @if(isset($empty))
                                {{ $empty }}
                            @else
                                <div class="table-empty" role="status" aria-live="polite">
                                    {{-- Inline SVG inbox icon --}}
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
                                    <p style="font-size: var(--text-sm); font-weight: 500; color: var(--text-muted); margin: 0">
                                        لا توجد بيانات
                                    </p>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endif
            </tbody>

        </table>
    </div>

</div>

{{-- =================================================================
     CLIENT-SIDE COLUMN SORT (inlined, zero-dep, activates only when sort=true)
     ================================================================= --}}
@if($sort)
<script>
(function () {
    'use strict';
    var tid = {{ Js::from($id) }};
    var table = document.getElementById(tid);
    if (!table || !table.dataset.sortable) return;

    var headers = table.querySelectorAll('thead th[data-col]');
    headers.forEach(function (th) {
        th.classList.add('sortable');
        th.setAttribute('tabindex', '0');
        th.setAttribute('role', 'columnheader');
        th.setAttribute('aria-sort', 'none');

        function doSort() {
            var col = parseInt(th.dataset.col, 10);
            var asc = th.dataset.dir !== 'asc';
            th.dataset.dir = asc ? 'asc' : 'desc';

            // Reset siblings
            headers.forEach(function (h) {
                h.classList.remove('sort-asc', 'sort-desc');
                h.setAttribute('aria-sort', 'none');
                delete h.dataset.dir;
            });
            th.classList.add(asc ? 'sort-asc' : 'sort-desc');
            th.dataset.dir = asc ? 'asc' : 'desc';
            th.setAttribute('aria-sort', asc ? 'ascending' : 'descending');

            var tbody = table.querySelector('tbody');
            var rows  = Array.from(tbody.querySelectorAll('tr:not([data-empty-row])'));

            rows.sort(function (a, b) {
                var cellA = (a.querySelectorAll('td')[col] || { textContent: '' }).textContent.trim();
                var cellB = (b.querySelectorAll('td')[col] || { textContent: '' }).textContent.trim();
                var numA  = parseFloat(cellA.replace(/[^\d.\-]/g, ''));
                var numB  = parseFloat(cellB.replace(/[^\d.\-]/g, ''));
                var result = (!isNaN(numA) && !isNaN(numB))
                    ? numA - numB
                    : cellA.localeCompare(cellB, 'ar');
                return asc ? result : -result;
            });

            rows.forEach(function (r) { tbody.appendChild(r); });
        }

        th.addEventListener('click', doSort);
        th.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); doSort(); }
        });
    });
})();
</script>
@endif
