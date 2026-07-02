{{--
  Component: <x-admin.page-header>

  The top-of-page hero strip rendered inside .main-content, immediately below
  the topbar. Matches the "Cloud Deck" design: light cream area, title + icon
  tile, optional breadcrumb trail, optional subtitle, and a named action slot
  for the gold "+ إنشاء …" button or any other CTA. RTL. No CDN. Inline SVG.

  Props:
    $title     (string)       — required. Main page heading in Arabic.
    $subtitle  (string|null)  — optional. One-line muted description.
    $crumbs    (array|null)   — optional breadcrumb trail.
                                Each item: a string label, or ['label', 'href'].
                                Last crumb is always the current page (no link).
                                Example: [['الرئيسية', route('admin.dashboard')], 'المستخدمون']
    $icon      (string|null)  — inline SVG <path> d="…" string, OR a full <svg>…</svg>
                                string, OR null. Default: null (no icon tile shown).
    $iconColor (string)       — CSS colour / var() for the icon tile bg tint.
                                Default: 'var(--primary-bg)' (wine tint).
    $count     (string|int|null) — optional record count badge beside the title.
    $noSep     (bool)         — true = hide the bottom separator line. Default: false.

  Named slots:
    $action — action area (inline-start / right side in RTL). Usually
              an <x-admin.button variant="gold"> with a "+" prefix.

  Usage — minimal:
    <x-admin.page-header title="المستخدمون"/>

  Usage — full:
    <x-admin.page-header
        title="المستخدمون"
        subtitle="إدارة حسابات النظام ومستويات التحقق"
        :crumbs="[['الرئيسية', route('admin.dashboard')], 'المستخدمون']"
        icon="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3z..."
        :count="$users->total()">
        <x-slot:action>
            <x-admin.button variant="gold" href="{{ route('admin.users.create') }}">
                + إنشاء مستخدم
            </x-admin.button>
        </x-slot:action>
    </x-admin.page-header>

  Usage — full SVG icon (paste the whole tag):
    <x-admin.page-header title="البطاقات"
        icon='<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v4z"/></svg>'>
--}}
@props([
    'title'     => '',
    'subtitle'  => null,
    'crumbs'    => null,
    'icon'      => null,
    'iconColor' => 'var(--primary-bg, rgba(110,27,45,.10))',
    'count'     => null,
    'noSep'     => false,
])

@php
/*
 * Determine whether the caller provided a full <svg> tag or just a <path> d string.
 * We normalise both to a ready-to-render HTML string.
 */
$iconHtml = null;
if ($icon) {
    $trimmed = trim($icon);
    if (str_starts_with($trimmed, '<')) {
        // Full SVG or other element passed verbatim.
        $iconHtml = $trimmed;
    } else {
        // Bare <path> d string — wrap in a standard 20 × 20 viewBox SVG.
        $iconHtml = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="' . e($trimmed) . '"/></svg>';
    }
}

/*
 * Breadcrumb normalisation.
 * Each item may be a plain string (label only) or a [label, href] array.
 * The last item is always treated as the current page (no link even if href given).
 */
$normalCrumbs = [];
if (!empty($crumbs) && is_array($crumbs)) {
    foreach ($crumbs as $i => $crumb) {
        $isLast = ($i === array_key_last($crumbs));
        if (is_array($crumb)) {
            $normalCrumbs[] = [
                'label'   => $crumb[0] ?? '',
                'href'    => (!$isLast && isset($crumb[1])) ? $crumb[1] : null,
                'current' => $isLast,
            ];
        } else {
            $normalCrumbs[] = [
                'label'   => (string) $crumb,
                'href'    => null,
                'current' => $isLast,
            ];
        }
    }
}

$hasCrumbs  = !empty($normalCrumbs);
$hasAction  = isset($action) && $action->isNotEmpty();
$hasCount   = $count !== null && $count !== '';
@endphp

{{-- ===================================================================
     Markup
     =================================================================== --}}
<header
    dir="rtl"
    role="banner"
    {{ $attributes->merge(['class' => 'ph-root' . ($noSep ? '' : ' ph-sep')]) }}
>

    {{-- ── Breadcrumb trail ──────────────────────────────────────────── --}}
    @if($hasCrumbs)
    <nav class="ph-crumbs" aria-label="مسار التنقل">
        @foreach($normalCrumbs as $crumb)
            @if(!$loop->first)
            <span class="ph-crumbs__sep" aria-hidden="true">
                {{-- Inline chevron SVG, flipped via scaleX for LTR breadcrumb reading order (right → left visually in RTL) --}}
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.5"
                     stroke-linecap="round" stroke-linejoin="round"
                     style="transform:scaleX(-1)" aria-hidden="true">
                    <polyline points="9 18 15 12 9 6"/>
                </svg>
            </span>
            @endif

            @if($crumb['current'])
                <span class="ph-crumbs__current" aria-current="page">{{ $crumb['label'] }}</span>
            @elseif($crumb['href'])
                <a href="{{ $crumb['href'] }}">{{ $crumb['label'] }}</a>
            @else
                <span>{{ $crumb['label'] }}</span>
            @endif
        @endforeach
    </nav>
    @endif

    {{-- ── Main row: icon + text + action ───────────────────────────── --}}
    <div class="ph-row">

        {{-- Icon tile (optional) --}}
        @if($iconHtml)
        <div
            class="ph-icon-tile"
            style="background: {{ $iconColor }};"
            aria-hidden="true"
        >
            {!! $iconHtml !!}
        </div>
        @endif

        {{-- Text block: title + subtitle --}}
        <div class="ph-body">
            <div class="ph-title-row">
                <h1 class="ph-title">{{ $title }}</h1>

                @if($hasCount)
                <span class="ph-count" aria-label="{{ $count }} عنصر">
                    {{ number_format((int) $count) }}
                </span>
                @endif
            </div>

            @if($subtitle)
            <p class="ph-subtitle">{{ $subtitle }}</p>
            @endif
        </div>

        {{-- Action slot (gold "+ إنشاء …" button or any CTA) --}}
        @if($hasAction)
        <div class="ph-action">
            {{ $action }}
        </div>
        @endif

    </div>
    {{-- /ph-row --}}

</header>
