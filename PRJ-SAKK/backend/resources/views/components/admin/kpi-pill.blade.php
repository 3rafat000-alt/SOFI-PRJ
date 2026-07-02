{{--
  Component: <x-admin.kpi-pill>
  Compact KPI pill for the top utility bar — shows a label + value in a small
  rounded chip. Optionally shows an inline trend arrow and icon.

  Props:
    $label   (string)       — required. Short Arabic label (e.g. "المستخدمون").
    $value   (string|int)   — required. Numeric or text value to display.
    $icon    (string|null)  — Material Icons Round name. Default: null (hidden).
    $trend   (string|null)  — 'up' | 'down' | 'flat' | null. Shows trend arrow.
    $color   (string)       — color variant: cyan | green | amber | red | blue | slate.
                              Default: 'cyan'.
    $href    (string|null)  — if set, wraps pill in an <a> link. Default: null.
    $title   (string|null)  — tooltip/title attribute. Default: null.

  Usage (in topbar or page header):
    <x-admin.kpi-pill label="المستخدمون" value="{{ $userCount }}" icon="people" color="cyan" trend="up"/>
    <x-admin.kpi-pill label="المعاملات" value="{{ $txCount }}"   icon="swap_horiz" color="green"/>
    <x-admin.kpi-pill label="الإيرادات"  value="ر.س {{ $revenue }}" icon="payments" color="amber" href="{{ route('admin.reports') }}"/>
--}}
@props([
    'label'  => '',
    'value'  => '—',
    'icon'   => null,
    'trend'  => null,
    'color'  => 'cyan',
    'href'   => null,
    'title'  => null,
])

@php
    /* Map color variant → CSS modifier class */
    $colorMap = [
        'cyan'  => 'kpi-pill--cyan',
        'green' => 'kpi-pill--green',
        'amber' => 'kpi-pill--amber',
        'red'   => 'kpi-pill--red',
        'blue'  => 'kpi-pill--blue',
        'slate' => 'kpi-pill--slate',
    ];
    $colorClass = $colorMap[$color] ?? 'kpi-pill--cyan';

    /* Trend icon name */
    $trendIcon = match($trend) {
        'up'   => 'arrow-trending-up',
        'down' => 'arrow-trending-down',
        'flat' => 'minus',
        default => null,
    };

    /* Trend modifier class */
    $trendClass = match($trend) {
        'up'   => 'kpi-pill__trend--up',
        'down' => 'kpi-pill__trend--down',
        'flat' => 'kpi-pill__trend--flat',
        default => '',
    };

    $tag   = $href ? 'a'   : 'div';
    $attrs = $href ? "href=\"{$href}\"" : '';
@endphp

<{{ $tag }}
    {{ $attrs ? new \Illuminate\Support\HtmlString($attrs) : '' }}
    {{ $attributes->merge(['class' => "kpi-pill {$colorClass}"]) }}
    @if($title) title="{{ $title }}" @endif
    @if($href) role="link" @endif
    dir="rtl"
>
    {{-- Optional leading icon --}}
    @if($icon)
    <span class="kpi-pill__icon" aria-hidden="true">
        <x-admin.icon :name="$icon" class="w-4 h-4" />
    </span>
    @endif

    {{-- Label --}}
    <span class="kpi-pill__label">{{ $label }}</span>

    {{-- Divider --}}
    <span class="kpi-pill__sep" aria-hidden="true"></span>

    {{-- Value --}}
    <span class="kpi-pill__value">{{ $value }}</span>

    {{-- Optional trend arrow --}}
    @if($trendIcon)
    <span class="kpi-pill__trend {{ $trendClass }}" aria-hidden="true">
        <x-admin.icon :name="$trendIcon" class="w-3.5 h-3.5" />
    </span>
    @endif
</{{ $tag }}>


