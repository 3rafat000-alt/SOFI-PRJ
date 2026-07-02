{{--
  Component: <x-admin.timeline>

  RTL activity timeline with vertical line on the right side.
  Supports prop-driven items or default slot content.

  Props:
    $items     (Collection|array|null) — timeline entries.
    $emptyText (string) — empty-state message. Default: "لا توجد أحداث حالياً"

  Item structure (when using $items prop, each as object or array):
    title       (string)        — required.
    description (string|null)   — optional.
    time        (Carbon|string|null) — optional. Shown as "منذ …" via Carbon.
    icon        (string|null)   — optional. Material Icons Round name beside title.
    color       (string)        — dot color: wine|gold|green|amber|blue|red. Default: wine.

  Usage — prop-driven:
    <x-admin.timeline :items="$activities"/>

  Usage — slot-driven (full control):
    <x-admin.timeline>
        @foreach($activities as $a)
            <div class="tl-item">
                <div class="tl-dot" style="--dot-clr:var(--green)"></div>
                <div class="tl-content">
                    <div class="tl-header">
                        <span class="tl-title">{{ $a->title }}</span>
                    </div>
                    <p class="tl-desc">{{ $a->description }}</p>
                    <span class="tl-time">{{ $a->created_at->diffForHumans() }}</span>
                </div>
            </div>
        @endforeach
    </x-admin.timeline>

  Colors: wine (#6E1B2D), gold (#C8A46B), green (#2D7D46), amber (#C87A2D), blue (#2D5C7D), red (#B33A3A)
--}}
@props([
    'items'     => null,
    'emptyText' => 'لا توجد أحداث حالياً',
])

@php
$colorMap = [
    'wine'  => '#6E1B2D',
    'gold'  => '#C8A46B',
    'green' => '#2D7D46',
    'amber' => '#C87A2D',
    'blue'  => '#2D5C7D',
    'red'   => '#B33A3A',
];

$hasItems = !empty($items);
$hasSlot  = !$hasItems && $slot->isNotEmpty();
$isEmpty  = !$hasItems && !$hasSlot;
@endphp

{{-- CSS moved to base.css (Component: Timeline) --}}

<div
    dir="rtl"
    {{ $attributes->merge(['class' => 'tl-root']) }}
    role="list"
    aria-label="الجدول الزمني"
>

    {{-- ── Prop-driven items ──────────────────────────── --}}
    @if($hasItems)
        @foreach($items as $item)
            @php
                $title       = is_array($item) ? ($item['title'] ?? '') : ($item->title ?? '');
                $description = is_array($item) ? ($item['description'] ?? null) : ($item->description ?? null);
                $rawTime     = is_array($item) ? ($item['time'] ?? null) : ($item->time ?? null);
                $icon        = is_array($item) ? ($item['icon'] ?? null) : ($item->icon ?? null);
                $color       = is_array($item) ? ($item['color'] ?? 'wine') : ($item->color ?? 'wine');
                $dotColor    = $colorMap[$color] ?? $colorMap['wine'];

                $timeHtml = null;
                if ($rawTime) {
                    if ($rawTime instanceof \Carbon\Carbon) {
                        $timeHtml = $rawTime->diffForHumans();
                    } elseif (is_string($rawTime) && strtotime($rawTime)) {
                        $timeHtml = \Carbon\Carbon::parse($rawTime)->diffForHumans();
                    } else {
                        $timeHtml = (string) $rawTime;
                    }
                }
            @endphp

            <div class="tl-item" role="listitem">
                <div class="tl-dot" style="--dot-clr:{{ $dotColor }};"></div>
                <div class="tl-content">
                    <div class="tl-header">
                        @if($icon)
                            {{-- TODO: map $icon to Heroicon; use <x-admin.icon name="..." class="w-4 h-4" /> --}}
                            <x-heroicon name="{{ $icon }}" class="tl-icon" aria-hidden="true" />
                        @endif
                        <span class="tl-title">{{ $title }}</span>
                    </div>
                    @if($description)
                        <p class="tl-desc">{{ $description }}</p>
                    @endif
                    @if($timeHtml)
                        <span class="tl-time">{{ $timeHtml }}</span>
                    @endif
                </div>
            </div>
        @endforeach

    {{-- ── Slot-driven items ──────────────────────────── --}}
    @elseif($hasSlot)
        {{ $slot }}

    {{-- ── Empty state ────────────────────────────────── --}}
    @else
        <div class="tl-empty" role="status">
            <div class="tl-empty__icon" aria-hidden="true">
                <x-admin.icon name="queue-list" class="w-8 h-8" />
            </div>
            <p class="tl-empty__text">{{ $emptyText }}</p>
        </div>
    @endif

</div>
