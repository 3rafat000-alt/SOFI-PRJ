{{--
  Component: <x-admin.button>

  Renders a fully styled SAKK button or anchor, using .sakk-btn classes (defined in
  /sakk-admin/admin.css). RTL-ready. No external CDN. All icon SVG is inline.

  Props:
    variant  (string)      — primary | gold | ghost | danger. Default: primary.
    size     (string)      — sm | md (default) | lg.
    type     (string)      — HTML button type: button | submit | reset. Default: button.
    href     (string|null) — When set renders an <a> tag instead of <button>.
    disabled (bool)        — Disables interaction and dims appearance.
    loading  (bool)        — Shows a spinner and disables the element.
    icon     (string|null) — Inline SVG path data for a leading icon (optional).
    iconEnd  (string|null) — Inline SVG path data for a trailing icon (optional).
    iconLabel(string|null) — aria-label for the icon span (used when icon-only button).
    block    (bool)        — true = full-width (display:flex on root).
    rounded  (string)      — pill | default. pill = fully rounded. Default: default.

  Named slots:
    $slot — button label text (Arabic). May be empty when icon-only.

  Usage:
    Primary:
      <x-admin.button variant="primary" type="submit">حفظ</x-admin.button>

    Gold create button (topbar pattern):
      <x-admin.button variant="gold" href="{{ route('admin.users.create') }}">
          + إنشاء مستخدم
      </x-admin.button>

    Ghost/secondary:
      <x-admin.button variant="ghost" href="{{ url()->previous() }}">عودة</x-admin.button>

    Danger with confirm (use data-confirm handled by admin.js):
      <x-admin.button variant="danger" type="submit" data-confirm="هل أنت متأكد؟">
          حذف
      </x-admin.button>

    Loading state (e.g. form submission):
      <x-admin.button variant="primary" :loading="true" type="submit">جاري الحفظ…</x-admin.button>

    Icon-only (accessible):
      <x-admin.button variant="ghost" size="sm" icon="M19 11H7.83l..." icon-label="بحث"/>
--}}
@props([
    'variant'   => 'primary',
    'size'      => 'md',
    'type'      => 'button',
    'href'      => null,
    'disabled'  => false,
    'loading'   => false,
    'icon'      => null,
    'iconEnd'   => null,
    'iconLabel' => null,
    'block'     => false,
    'rounded'   => 'default',
])

@php
/*
 * Map variant → modifier class.
 * Allowed variants per shared vocab: primary | gold | ghost | danger.
 * Fall through to primary for unknown values to avoid broken styling.
 */
$variantMap = [
    'primary'   => 'sakk-btn--primary',
    'gold'      => 'sakk-btn--gold',
    'ghost'     => 'sakk-btn--ghost',
    'danger'    => 'sakk-btn--danger',
];
$variantClass = $variantMap[$variant] ?? 'sakk-btn--primary';

/*
 * Size modifier classes.
 */
$sizeClass = match($size) {
    'sm'    => 'sakk-btn--sm',
    'lg'    => 'sakk-btn--lg',
    default => '',               // md: governed by .sakk-btn base in admin.css
};

/*
 * Block (full-width) + pill modifiers.
 */
$blockClass  = $block   ? 'sakk-btn--block'  : '';
$roundClass  = $rounded === 'pill' ? 'sakk-btn--pill' : '';

/*
 * Loading class.
 */
$loadingClass = $loading ? 'sakk-btn--loading' : '';

/*
 * Final class string — compact, no double spaces.
 */
$btnClass = trim(implode(' ', array_filter([
    'sakk-btn',
    $variantClass,
    $sizeClass,
    $blockClass,
    $roundClass,
    $loadingClass,
])));

/*
 * Effective disabled/aria state.
 * A loading button is always considered disabled.
 */
$isDisabled = $disabled || $loading;

/*
 * Icon SVG wrapper inline style — consistent size scales with font-size.
 * Rendered via <svg> so zero external dependency.
 * Callers pass the raw SVG <path> d="…" string in $icon / $iconEnd.
 * If the caller passes a full <svg>…</svg> string it is output verbatim.
 */
$iconSize = match($size) {
    'sm'    => '14',
    'lg'    => '20',
    default => '16',
};
@endphp

{{--
  ============================================================
  Inline once-only micro-CSS for modifiers not covered by
  the global admin.css (block, pill, size variants, loading
  spinner). Keeps the component self-contained; no CDN.
  ============================================================
--}}
@once
<style>
/* ── Button base (admin.css defines .sakk-btn; these extend it) ── */

/* Size: sm */
.sakk-btn--sm {
    font-size: .75rem;
    padding: 5px 14px;
    gap: 5px;
    min-height: 30px;
    font-family: 'IBM Plex Sans Arabic', system-ui, sans-serif;
}

/* Size: lg */
.sakk-btn--lg {
    font-size: .9375rem;
    padding: 11px 28px;
    gap: 9px;
    min-height: 46px;
    font-family: 'IBM Plex Sans Arabic', system-ui, sans-serif;
}

/* Block (full width) */
.sakk-btn--block {
    display: flex;
    width: 100%;
    justify-content: center;
}

/* Pill shape */
.sakk-btn--pill {
    border-radius: 999px;
}

/* Loading state — spinner via pseudo-element, no CDN */
.sakk-btn--loading {
    position: relative;
    pointer-events: none;
    opacity: .8;
}
.sakk-btn--loading > * {
    visibility: hidden;
}
.sakk-btn--loading::after {
    content: '';
    position: absolute;
    inset: 0;
    margin: auto;
    width: 1em;
    height: 1em;
    border: 2px solid currentColor;
    border-top-color: transparent;
    border-radius: 50%;
    animation: sakk-btn-spin .65s linear infinite;
}
@keyframes sakk-btn-spin {
    to { transform: rotate(360deg); }
}
@media (prefers-reduced-motion: reduce) {
    .sakk-btn--loading::after { animation-duration: .001ms; }
}

/* Icon wrapper alignment inside button */
.sakk-btn__icon {
    display: inline-flex;
    align-items: center;
    flex-shrink: 0;
    line-height: 0;
}

/* Disabled state for <a> tags (real buttons use HTML disabled attr) */
a.sakk-btn[aria-disabled="true"] {
    pointer-events: none;
    opacity: .5;
    cursor: not-allowed;
}

/* Ensure all buttons use SAKK wine focus ring */
.sakk-btn:focus-visible {
    outline: 2px solid var(--primary);
    outline-offset: 2px;
    box-shadow: 0 0 0 4px var(--primary);
}

/* Primary: wine velvet — one per viewport as per playbook §1.2 law 2 */
.sakk-btn--primary {
    background: var(--primary);
    color: #fff;
    border: none;
    box-shadow: var(--sh-wine, 0 10px 30px rgba(110,27,45,.18));
    font-family: 'IBM Plex Sans Arabic', system-ui, sans-serif;
    font-weight: 600;
}
.sakk-btn--primary:hover:not(:disabled) {
    background: var(--primary-dark);
    box-shadow: var(--sh-wine, 0 10px 30px rgba(110,27,45,.18)), 0 2px 8px rgba(110,27,45,.12);
}

/* Gold — premium accent CTA */
.sakk-btn--gold {
    background: var(--primary);
    color: #fff;
    border: none;
    box-shadow: var(--sh-gold, 0 8px 24px rgba(110,27,45,.22));
    font-family: 'IBM Plex Sans Arabic', system-ui, sans-serif;
    font-weight: 600;
}
.sakk-btn--gold:hover:not(:disabled) {
    filter: brightness(1.08);
}
</style>
@endonce

{{-- ============================================================
     Render: <a> when href provided, else <button>
     ============================================================ --}}
@if($href)
<a
    href="{{ $isDisabled ? '#' : $href }}"
    dir="rtl"
    {{ $attributes->merge(['class' => $btnClass]) }}
    @if($isDisabled) aria-disabled="true" tabindex="-1" @endif
    @if($iconLabel && $slot->isEmpty()) aria-label="{{ $iconLabel }}" @endif
    role="button"
>
    @if($icon)
        <span class="sakk-btn__icon" aria-hidden="true">
            {{-- If caller passed a full <svg> tag, output raw; otherwise wrap in standard svg --}}
            @if(str_starts_with(trim($icon), '<'))
                {!! $icon !!}
            @else
                <svg xmlns="http://www.w3.org/2000/svg" width="{{ $iconSize }}" height="{{ $iconSize }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="{{ $icon }}"/></svg>
            @endif
        </span>
    @endif

    @if(!$slot->isEmpty())
        <span>{{ $slot }}</span>
    @endif

    @if($iconEnd)
        <span class="sakk-btn__icon" aria-hidden="true">
            @if(str_starts_with(trim($iconEnd), '<'))
                {!! $iconEnd !!}
            @else
                <svg xmlns="http://www.w3.org/2000/svg" width="{{ $iconSize }}" height="{{ $iconSize }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="{{ $iconEnd }}"/></svg>
            @endif
        </span>
    @endif
</a>
@else
<button
    type="{{ $type }}"
    dir="rtl"
    {{ $attributes->merge(['class' => $btnClass]) }}
    @if($isDisabled) disabled aria-disabled="true" @endif
    @if($iconLabel && $slot->isEmpty()) aria-label="{{ $iconLabel }}" @endif
>
    @if($icon)
        <span class="sakk-btn__icon" aria-hidden="true">
            @if(str_starts_with(trim($icon), '<'))
                {!! $icon !!}
            @else
                <svg xmlns="http://www.w3.org/2000/svg" width="{{ $iconSize }}" height="{{ $iconSize }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="{{ $icon }}"/></svg>
            @endif
        </span>
    @endif

    @if(!$slot->isEmpty())
        <span>{{ $slot }}</span>
    @endif

    @if($iconEnd)
        <span class="sakk-btn__icon" aria-hidden="true">
            @if(str_starts_with(trim($iconEnd), '<'))
                {!! $iconEnd !!}
            @else
                <svg xmlns="http://www.w3.org/2000/svg" width="{{ $iconSize }}" height="{{ $iconSize }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="{{ $iconEnd }}"/></svg>
            @endif
        </span>
    @endif
</button>
@endif
