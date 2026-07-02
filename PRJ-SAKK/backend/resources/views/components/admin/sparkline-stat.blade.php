{{--
  Component: <x-admin.sparkline-stat>
  Anonymous Blade component — stat card with inline SVG sparkline chart.
  Follows SAKK wine/gold design system with full RTL support.

  Props:
    $label          (string)       — required. KPI label in Arabic.
    $value          (string|int)   — required. Primary KPI value to display.
    $icon           (string|null)  — optional. SVG path string for small decorative icon.
    $color          (string)       — color variant:
                                     wine | gold | green | amber | red | blue | slate
                                     Default: 'wine'.
    $delta          (string|null)  — optional. Change text, e.g. "+١٢% اليوم".
    $deltaType      (string)       — Trend direction: up | down | flat. Default: 'up'.
    $sparklineData  (string)       — comma-separated numeric values for the chart,
                                     e.g. "10,20,15,30,25,40,35". Min 2 values needed.
    $href           (string|null)  — optional. Makes the whole card a link.

  Slots: (none — all content is prop-driven)

  CSS dependencies:
    .stat-card  .stat-label  .stat-value  .stat-delta (from admin.css)

  Icon font: none — all icons are inline SVGs (zero external dependency).

  Usage:
    <x-admin.sparkline-stat
        label="المعاملات اليوم"
        value="١٬٢٣٤"
        icon="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"
        color="wine"
        delta="+١٢%"
        delta-type="up"
        sparkline-data="10,20,15,30,25,40,35,50"
    />

    {{-- Minimal usage without icon or delta --}}
    <x-admin.sparkline-stat
        label="المستخدمون"
        value="٨٬٤٣١"
        color="green"
        sparkline-data="100,200,150,300,250,400"
    />

    {{-- As a link --}}
    <x-admin.sparkline-stat
        label="الإيرادات"
        :value="number_format($revenue)"
        color="gold"
        sparkline-data="5,12,8,20,15,25"
        href="{{ route('admin.reports') }}"
    />
--}}
@props([
    'label'         => 'KPI',
    'value'         => '—',
    'icon'          => null,
    'color'         => 'wine',
    'delta'         => null,
    'deltaType'     => 'up',
    'sparklineData' => '',
    'href'          => null,
])

@php
    /* ── Color map: sparkline stroke ──────────────────────────────────── */
    $strokeColorMap = [
        'wine'  => 'var(--sukk-primary)',
        'gold'  => 'var(--accent)',
        'green' => '#1F9D55',
        'amber' => '#D97706',
        'red'   => '#C0392B',
        'blue'  => '#1A5276',
        'slate' => 'var(--text-secondary)',
    ];
    $stroke = $strokeColorMap[$color] ?? 'var(--sukk-primary)';

    /* ── Parse sparkline data ─────────────────────────────────────────── */
    $raw   = array_map('floatval', explode(',', (string) $sparklineData));
    $data  = array_values(array_filter($raw, fn($v) => is_numeric($v) && !is_nan($v)));
    $count = count($data);

    /* ── SVG viewBox math ─────────────────────────────────────────────── */
    $points  = '';
    $areaPts = '';
    $svgW    = 100;
    $svgH    = 30;
    $pad     = 3;
    $drawH   = $svgH - 2 * $pad;

    if ($count >= 2) {
        $min   = min($data);
        $max   = max($data);
        $range = ($max - $min) ?: 1;

        foreach ($data as $i => $val) {
            $x      = ($i / ($count - 1)) * $svgW;
            $y      = $pad + (($max - $val) / $range) * $drawH;
            $fmt    = number_format($x, 1) . ',' . number_format($y, 1);
            $points .= ($i > 0 ? ' ' : '') . $fmt;
            $areaPts .= ($i > 0 ? ' ' : '') . $fmt;
        }
        /* Close polygon for under-line fill */
        $areaPts .= ' ' . $svgW . ',' . $svgH . ' 0,' . $svgH;
    }

    /* ── Trend icon paths (inline SVG, no font dependency) ────────────── */
    $trendPath = match($deltaType) {
        'down' => 'M20 12l-1.41-1.41L13 16.17V4h-2v12.17l-5.58-5.59L4 12l8 8 8-8z',
        'flat' => 'M4 11h16v2H4z',
        default => 'M4 12l1.41 1.41L11 7.83V20h2V7.83l5.58 5.59L20 12l-8-8-8 8z',
    };

    /* ── Wrapper tag ──────────────────────────────────────────────────── */
    $tag      = $href ? 'a' : 'div';
    $linkAttr = $href ? 'href="' . e($href) . '"' : '';
@endphp

<{{ $tag }}
    {{ $tag === 'a' ? $linkAttr : '' }}
    {{ $attributes->merge(['class' => 'stat-card sparkline-stat' . ($href ? ' sparkline-stat--link' : '')]) }}
    role="{{ $href ? 'link' : 'region' }}"
    aria-label="{{ $label }}: {{ $value }}{{ $delta ? ' — ' . $delta : '' }}"
    dir="rtl"
>
    <div style="display:flex;align-items:center;gap:16px">

        {{-- Text column (label, value, delta) --}}
        <div style="min-width:0;flex:1">
            <p class="stat-label">
                @if($icon)
                <svg class="sparkline-stat__icon" viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true">
                    <path d="{{ $icon }}"/>
                </svg>
                @endif
                {{ $label }}
            </p>

            <p class="stat-value">{{ $value }}</p>

            @if($delta)
            <div class="stat-delta {{ $deltaType }}" role="status" aria-label="{{ $delta }}">
                <svg class="sparkline-stat__trend" viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true">
                    <path d="{{ $trendPath }}"/>
                </svg>
                <span>{{ $delta }}</span>
            </div>
            @endif
        </div>

        {{-- Sparkline chart --}}
        @if($count >= 2)
        <div class="sparkline-stat__chart" aria-hidden="true">
            <svg viewBox="0 0 {{ $svgW }} {{ $svgH }}" width="80" height="28" preserveAspectRatio="none" style="display:block;">
                @if($areaPts)
                <polygon
                    points="{{ $areaPts }}"
                    fill="{{ $stroke }}"
                    fill-opacity="0.08"
                />
                @endif
                <polyline
                    points="{{ $points }}"
                    fill="none"
                    stroke="{{ $stroke }}"
                    stroke-width="1.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>
        </div>
        @endif

    </div>
</{{ $tag }}>

