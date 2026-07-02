{{--
  Component: <x-admin.folder-card>

  A visual "folder" card used to surface document groups, file collections,
  or categorised content sets. Renders a rounded-2xl white card with a
  colour-coded folder icon, a title, optional subtitle, item count badge,
  an optional action button, and an expandable slot for child content.

  Design: Cloud Deck × SAKK — wine/gold identity, full RTL, no CDN.
  CSS classes: .sakk-folder-card + sub-elements (scoped @once block below).

  Props:
    $title      (string)        — required. Folder name in Arabic.
    $subtitle   (string|null)   — optional. Short muted description.
    $count      (int|string|null) — item count shown in a badge. Default: null (hidden).
    $countLabel (string)        — label appended to count badge. Default: 'ملف'.
    $href       (string|null)   — if set, the card is clickable (wraps in <a>). Default: null.
    $color      (string)        — folder icon color variant:
                                    wine (default) | gold | green | amber | red | blue | slate.
    $icon       (string)        — SVG path d="…" for the folder icon.
                                    Defaults to a standard folder SVG path.
    $size       (string)        — sm | md (default) | lg — adjusts card proportions.
    $selected   (bool)          — true = highlighted gold ring (active/selected state).
    $disabled   (bool)          — true = dims the card and disables pointer events.
    $loading    (bool)          — true = shimmer skeleton instead of content.

  Named slots:
    $actions — end area of header row (buttons, dropdowns). Stops event bubble on
               clickable cards via pointer capture on the inner wrapper.
    $slot    — child content rendered below the header (file rows, quick-cards, etc.).
               When empty and $count is null, an empty-state is shown.

  Usage:

    Minimal:
      <x-admin.folder-card title="وثائق العملاء" color="wine" count="24"/>

    Clickable folder link:
      <x-admin.folder-card
          title="الفواتير"
          subtitle="مستندات المدفوعات المؤرشفة"
          :count="$invoiceCount"
          count-label="فاتورة"
          color="gold"
          href="{{ route('admin.docs.invoices') }}"
      />

    With actions slot:
      <x-admin.folder-card title="التقارير الشهرية" color="blue">
          <x-slot:actions>
              <x-admin.button variant="ghost" size="sm">تحميل</x-admin.button>
          </x-slot:actions>
          ...file rows...
      </x-admin.folder-card>

    Selected state (e.g. active in a grid):
      <x-admin.folder-card title="العقود" :selected="true" color="wine"/>

    Loading skeleton:
      <x-admin.folder-card title="..." :loading="true"/>
--}}
@props([
    'title'      => '',
    'subtitle'   => null,
    'count'      => null,
    'countLabel' => 'ملف',
    'href'       => null,
    'color'      => 'wine',
    'icon'       => null,
    'size'       => 'md',
    'selected'   => false,
    'disabled'   => false,
    'loading'    => false,
])

@php
/*
 * Color palette — maps variant names to CSS custom-property colours
 * sourced from the SAKK design token set (admin.css :root).
 */
$colorMap = [
    'wine'  => ['bg' => 'var(--primary-bg)',      'icon' => 'var(--sukk-primary)', 'border' => 'var(--primary-bg-md)'],
    'gold'  => ['bg' => 'rgba(110,27,45,0.10)',  'icon' => 'var(--accent)',        'border' => 'rgba(110,27,45,0.25)'],
    'green' => ['bg' => 'var(--success-light)',    'icon' => 'var(--success)',     'border' => '#bbf7d0'],
    'amber' => ['bg' => 'var(--warning-light)',    'icon' => 'var(--warning)',     'border' => '#fde68a'],
    'red'   => ['bg' => 'var(--danger-light)',     'icon' => 'var(--danger)',      'border' => '#fecaca'],
    'blue'  => ['bg' => 'var(--info-light)',       'icon' => 'var(--info)',        'border' => '#bfdbfe'],
    'slate' => ['bg' => 'var(--surface-active)',   'icon' => 'var(--text-secondary)', 'border' => 'var(--border-strong)'],
];
$palette = $colorMap[$color] ?? $colorMap['wine'];

/*
 * Default folder icon — standard open-folder SVG path (24 × 24 viewBox).
 * Callers may supply their own $icon path string to override.
 */
$folderPath = $icon ?? 'M20 6h-8l-2-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2z';

/*
 * Size → dimensional tokens.
 */
$sizeMap = [
    'sm' => ['iconBox' => '36px', 'iconSvg' => '18', 'padding' => '14px 16px', 'gap' => '10px', 'radius' => 'var(--r-lg,16px)'],
    'md' => ['iconBox' => '44px', 'iconSvg' => '22', 'padding' => '18px 20px', 'gap' => '12px', 'radius' => 'var(--r-xl,20px)'],
    'lg' => ['iconBox' => '52px', 'iconSvg' => '26', 'padding' => '22px 24px', 'gap' => '14px', 'radius' => 'var(--r-xl,20px)'],
];
$dim = $sizeMap[$size] ?? $sizeMap['md'];

/*
 * Root element: <a> when href is provided, else <article>.
 * aria-* roles adjusted accordingly.
 */
$tag       = $href && !$disabled ? 'a' : 'article';
$tagAttrs  = ($href && !$disabled) ? "href=\"{$href}\"" : '';

/*
 * CSS class composition on root element.
 */
$rootClass  = 'sakk-folder-card';
$rootClass .= $selected  ? ' sakk-folder-card--selected'  : '';
$rootClass .= $disabled  ? ' sakk-folder-card--disabled'  : '';
$rootClass .= ($href && !$disabled) ? ' sakk-folder-card--link' : '';

/*
 * Determine whether the body slot has visible content.
 */
$hasBody = !$loading && $slot->isNotEmpty();
@endphp

{{-- CSS moved to base.css (Component: Folder Card) --}}

{{-- ============================================================
     Root element: <a> (link) or <article> (static)
     ============================================================ --}}
<{{ $tag }}
    {{ $tagAttrs ? new \Illuminate\Support\HtmlString($tagAttrs) : '' }}
    {{ $attributes->merge(['class' => $rootClass]) }}
    style="border-radius:{{ $dim['radius'] }};"
    @if($disabled) aria-disabled="true" @endif
    @if($href && !$disabled) role="link" @endif
    dir="rtl"
>
    {{-- ── HEADER ────────────────────────────────────────────── --}}
    <div
        class="sakk-folder-card__header"
        style="padding:{{ $dim['padding'] }};gap:{{ $dim['gap'] }};"
    >
        {{-- Folder icon --}}
        <div
            class="sakk-folder-card__icon-wrap"
            aria-hidden="true"
            style="
                width:{{ $dim['iconBox'] }};
                height:{{ $dim['iconBox'] }};
                background:{{ $palette['bg'] }};
                border:1px solid {{ $palette['border'] }};
                color:{{ $palette['icon'] }};
                margin-inline-end:{{ $dim['gap'] }};
            "
        >
            @if($loading)
                {{-- Skeleton circle in place of icon --}}
                <div
                    class="sakk-folder-card__skeleton-line"
                    style="width:{{ $dim['iconSvg'] }}px;height:{{ $dim['iconSvg'] }}px;border-radius:50%;"
                ></div>
            @else
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="{{ $dim['iconSvg'] }}"
                    height="{{ $dim['iconSvg'] }}"
                    viewBox="0 0 24 24"
                    fill="currentColor"
                    aria-hidden="true"
                >
                    <path d="{{ $folderPath }}"/>
                </svg>
            @endif
        </div>

        {{-- Title + subtitle --}}
        <div class="sakk-folder-card__text">
            @if($loading)
                <div class="sakk-folder-card__skeleton">
                    <div class="sakk-folder-card__skeleton-line" style="width:55%;height:13px;"></div>
                    @if($subtitle)
                        <div class="sakk-folder-card__skeleton-line" style="width:75%;height:11px;"></div>
                    @endif
                </div>
            @else
                <div class="sakk-folder-card__title">{{ $title }}</div>
                @if($subtitle)
                    <div class="sakk-folder-card__subtitle">{{ $subtitle }}</div>
                @endif
            @endif
        </div>

        {{-- Count badge --}}
        @if(!$loading && $count !== null)
            <div
                class="sakk-folder-card__count"
                aria-label="{{ $count }} {{ $countLabel }}"
                style="
                    background:{{ $palette['bg'] }};
                    border:1px solid {{ $palette['border'] }};
                    color:{{ $palette['icon'] }};
                "
            >
                <span aria-hidden="true">{{ $count }}</span>
                <span style="opacity:0.7;">{{ $countLabel }}</span>
            </div>
        @endif

        {{-- Actions slot — stop propagation on link cards via data attr handled by admin.js --}}
        @if(isset($actions))
            <div
                class="sakk-folder-card__actions"
                @if($href) data-stop-propagation="true" @endif
            >
                {{ $actions }}
            </div>
        @endif
    </div>

    {{-- ── BODY (slot content or skeleton or empty state) ────── --}}
    @if($loading)
        {{-- Loading skeleton body --}}
        <div class="sakk-folder-card__divider" aria-hidden="true"></div>
        <div
            class="sakk-folder-card__body"
            style="padding:{{ $dim['padding'] }};"
            aria-busy="true"
            aria-label="جاري التحميل"
        >
            <div class="sakk-folder-card__skeleton" role="status">
                <div class="sakk-folder-card__skeleton-line" style="width:90%;height:12px;"></div>
                <div class="sakk-folder-card__skeleton-line" style="width:70%;height:12px;"></div>
                <div class="sakk-folder-card__skeleton-line" style="width:80%;height:12px;"></div>
            </div>
        </div>
    @elseif($hasBody)
        {{-- Slot content --}}
        <div class="sakk-folder-card__divider" aria-hidden="true"></div>
        <div class="sakk-folder-card__body" style="padding:{{ $dim['padding'] }};">
            {{ $slot }}
        </div>
    @elseif($count === null)
        {{-- No count and no body: show inline empty state --}}
        <div class="sakk-folder-card__divider" aria-hidden="true"></div>
        <div
            class="sakk-folder-card__body sakk-folder-card__empty"
            style="padding:{{ $dim['padding'] }};"
            role="status"
            aria-label="المجلد فارغ"
        >
            <span class="sakk-folder-card__empty-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20 6h-8l-2-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm0 12H4V8h16v10z"/>
                </svg>
            </span>
            <span class="sakk-folder-card__empty-label">المجلد فارغ</span>
        </div>
    @endif

</{{ $tag }}>
