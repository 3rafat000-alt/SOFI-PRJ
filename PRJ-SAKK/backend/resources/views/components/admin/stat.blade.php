{{--
  Component: <x-admin.stat>
  Anonymous Blade component — KPI / stat card for the SAKK admin panel.

  Props:
    $label      (string)         — required. KPI label in Arabic.
    $value      (string|int)     — required. Primary KPI value to display.
    $icon       (string)         — Material Icons Round ligature name. Default: 'insights'.
    $color      (string)         — Icon bubble color variant:
                                   cyan | green | amber | red | blue | slate | wine | gold
                                   Default: 'cyan'.
    $delta      (string|null)    — optional. Change text, e.g. "+١٢ اليوم".
    $deltaType  (string)         — Trend direction: up | down | flat. Default: 'up'.
    $suffix     (string|null)    — optional. Unit appended after value, e.g. "ر.س".
    $href       (string|null)    — optional. Makes the whole card a link.
    $loading    (bool)           — If true, shows a skeleton shimmer overlay. Default: false.

  Slots: (none — all content is prop-driven)

  CSS dependencies (public/sakk-admin/admin.css):
    .stat-card  .stat-icon  .stat-icon-{color}  .stat-value
    .stat-label  .stat-delta.{up|down|flat}

  Icon font (loaded by layouts/admin.blade.php):
    Material Icons Round — offline self-hosted via /sakk-admin/fonts/ referenced
    in admin.css; no external CDN calls from this component.

  Usage examples removed — documented by @props below.
--}}
@props([
    'label'     => 'KPI',
    'value'     => '—',
    'icon'      => 'insights',
    'color'     => 'cyan',
    'delta'     => null,
    'deltaType' => 'up',
    'suffix'    => null,
    'href'      => null,
    'loading'   => false,
])

@php
    /*
     * wine / gold icon variants are SAKK identity colors not defined in
     * admin.css stat-icon-* rules, so we resolve them to inline styles here.
     * All other variants are handled by the .stat-icon-{color} CSS class.
     */
    $inlineColorMap = [
        'wine' => 'background:rgba(110,27,45,0.10);color:#6E1B2D;',
        'gold' => 'background:var(--accent-soft);color:var(--accent-dark);',
    ];
    $iconInlineStyle = $inlineColorMap[$color] ?? null;
    $iconClass       = 'stat-icon' . ($iconInlineStyle ? '' : ' stat-icon-' . $color);

    /*
     * Trend icon: choose a lightweight inline SVG to avoid depending on the
     * Material Icons font for the delta indicator (the label text is enough
     * for screen readers; aria-hidden keeps SVGs decorative).
     */
    $trendIcon = match($deltaType) {
        'down'  => 'arrow-trending-down',
        'flat'  => 'minus',
        default => 'arrow-trending-up',
    };

    /*
     * Wrapper tag: use <a> when an href is provided so the whole card
     * becomes a focusable, keyboard-navigable link — accessible without JS.
     */
    $tag     = $href ? 'a' : 'div';
    $linkAttr = $href ? 'href="' . e($href) . '"' : '';
@endphp

<{{ $tag }}
    {{ $tag === 'a' ? $linkAttr : '' }}
    {{ $attributes->merge(['class' => 'stat-card' . ($href ? ' stat-card--link' : '')]) }}
    role="{{ $href ? 'link' : 'region' }}"
    aria-label="{{ $label }}: {{ $value }}{{ $suffix ? ' ' . $suffix : '' }}"
    dir="rtl"
>
    {{-- Loading shimmer overlay --}}
    @if($loading)
        <div class="stat-card__skeleton" aria-hidden="true" role="status" aria-label="جاري التحميل">
            <div class="skeleton-line" style="height:14px;width:55%;margin-bottom:8px;border-radius:6px;background:var(--surface-hover,#F2ECE5);animation:pulse 1.4s ease-in-out infinite;"></div>
            <div class="skeleton-line" style="height:30px;width:70%;margin-bottom:12px;border-radius:6px;background:var(--surface-hover,#F2ECE5);animation:pulse 1.4s ease-in-out infinite 0.15s;"></div>
            <div class="skeleton-line" style="height:20px;width:40%;border-radius:20px;background:var(--surface-hover,#F2ECE5);animation:pulse 1.4s ease-in-out infinite 0.3s;"></div>
        </div>
    @else
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px">

            {{-- Text column (label + value + delta) --}}
            <div style="min-width:0;flex:1">
                <p class="stat-label">{{ $label }}</p>

                <p class="stat-value">
                    {{ $value }}
                    @if($suffix)
                        <span style="font-size:1.1rem;font-weight:600;margin-inline-start:4px;color:var(--text-secondary);font-variant-numeric:tabular-nums;" aria-hidden="true">{{ $suffix }}</span>
                    @endif
                </p>

                @if($delta)
                    <div class="stat-delta {{ $deltaType }}" role="status" aria-label="{{ $delta }}">
                        <x-admin.icon :name="$trendIcon" class="w-3.5 h-3.5" aria-hidden="true" />
                        <span>{{ $delta }}</span>
                    </div>
                @endif
            </div>

            {{-- Icon bubble --}}
            <div
                class="{{ $iconClass }}"
                @if($iconInlineStyle) style="{{ $iconInlineStyle }}" @endif
                aria-hidden="true"
            >
                <x-admin.icon :name="$icon" class="w-5 h-5" />
            </div>

        </div>
    @endif
</{{ $tag }}>


