{{--
  Component: <x-admin.pagination>

  Wraps Laravel's LengthAwarePaginator with full SAKK "Cloud Deck" styling.
  Uses .sakk-pagination / .page-link classes defined in /sakk-admin/admin.css.
  Zero external dependencies — chevrons rendered as inline SVG.
  Fully RTL: prev = right chevron (→), next = left chevron (←).

  Props:
    $paginator   (LengthAwarePaginator) — required.
    $showInfo    (bool)   — show "عرض X–Y من Z نتيجة" summary. Default: true.
    $windowSize  (int)    — page numbers shown on each side of current. Default: 2.
                           e.g. windowSize=2 → [1 … 3 4 [5] 6 7 … 20]
    $queryParam  (string) — URL query param name for the page number. Default: 'page'.

  Usage:
    Basic:
      <x-admin.pagination :paginator="$users"/>

    No info line:
      <x-admin.pagination :paginator="$users" :show-info="false"/>

    Wider window (show 3 neighbours each side):
      <x-admin.pagination :paginator="$users" :window-size="3"/>

    Custom query param (multiple paginators on same page):
      <x-admin.pagination :paginator="$orders" query-param="orders_page"/>
--}}
@props([
    'paginator'  => null,
    'showInfo'   => true,
    'windowSize' => 2,
    'queryParam' => 'page',
])

{{-- CSS moved to base.css (Component: Pagination) --}}

@php
/*
 * Guard: nothing to render if no paginator or paginator has only one page.
 */
$hasPaginator = $paginator && method_exists($paginator, 'hasPages');
$shouldRender = $hasPaginator && $paginator->hasPages();

/*
 * Build the page-number window to display, with ellipsis markers.
 * Returns an array of entries:
 *   ['type' => 'page',     'number' => int, 'url' => string, 'active' => bool]
 *   ['type' => 'ellipsis']
 */
$pageItems = [];
if ($shouldRender) {
    $current  = $paginator->currentPage();
    $last     = $paginator->lastPage();
    $win      = max(1, (int) $windowSize);

    // Always show first, last, and the window around current.
    $show = array_unique(array_filter(
        array_merge(
            [1, $last],
            range(max(1, $current - $win), min($last, $current + $win))
        ),
        fn($p) => $p >= 1 && $p <= $last
    ));
    sort($show);

    $prev = null;
    foreach ($show as $p) {
        if ($prev !== null && $p - $prev > 1) {
            $pageItems[] = ['type' => 'ellipsis'];
        }
        $pageItems[] = [
            'type'   => 'page',
            'number' => $p,
            'url'    => $paginator->url($p),
            'active' => ($p === $current),
        ];
        $prev = $p;
    }
}

/*
 * Inline SVG paths (24×24 viewBox, no icon font):
 *   RTL: "previous page" visually points RIGHT  → chevron_right path
 *        "next page" visually points LEFT       → chevron_left path
 */
$svgRight = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>';
$svgLeft  = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>';
@endphp

@if($shouldRender)
<div class="sakk-pagination-wrap" dir="rtl">

    {{-- ── Info summary ────────────────────────────────────────── --}}
    @if($showInfo)
    <p class="sakk-pagination-info" aria-live="polite" aria-atomic="true">
        عرض
        <strong>{{ number_format($paginator->firstItem()) }}</strong>
        إلى
        <strong>{{ number_format($paginator->lastItem()) }}</strong>
        من
        <strong>{{ number_format($paginator->total()) }}</strong>
        نتيجة
    </p>
    @endif

    {{-- ── Navigation ─────────────────────────────────────────── --}}
    <nav
        class="sakk-pagination"
        role="navigation"
        aria-label="التنقل بين الصفحات"
    >
        {{-- Previous (→ right-pointing in RTL) --}}
        @if($paginator->onFirstPage())
            <span
                class="page-link page-link--arrow disabled"
                aria-disabled="true"
                aria-label="الصفحة السابقة"
            >{!! $svgRight !!}</span>
        @else
            <a
                href="{{ $paginator->previousPageUrl() }}"
                class="page-link page-link--arrow"
                rel="prev"
                aria-label="الصفحة السابقة"
            >{!! $svgRight !!}</a>
        @endif

        {{-- Page numbers with smart ellipsis --}}
        @foreach($pageItems as $item)
            @if($item['type'] === 'ellipsis')
                <span class="page-link page-link--ellipsis" aria-hidden="true">…</span>
            @else
                @if($item['active'])
                    <span
                        class="page-link active"
                        aria-current="page"
                        aria-label="الصفحة {{ $item['number'] }}، الحالية"
                    >{{ $item['number'] }}</span>
                @else
                    <a
                        href="{{ $item['url'] }}"
                        class="page-link"
                        aria-label="انتقل إلى الصفحة {{ $item['number'] }}"
                    >{{ $item['number'] }}</a>
                @endif
            @endif
        @endforeach

        {{-- Next (← left-pointing in RTL) --}}
        @if($paginator->hasMorePages())
            <a
                href="{{ $paginator->nextPageUrl() }}"
                class="page-link page-link--arrow"
                rel="next"
                aria-label="الصفحة التالية"
            >{!! $svgLeft !!}</a>
        @else
            <span
                class="page-link page-link--arrow disabled"
                aria-disabled="true"
                aria-label="الصفحة التالية"
            >{!! $svgLeft !!}</span>
        @endif
    </nav>

</div>
@endif
