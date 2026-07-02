@php
    $cards = $cards ?? [];
    $cols = count($cards);
@endphp
@once @endonce

<div class="cmp-kpi-g" style="--cmp-kpi-cols: {{ max($cols, 1) }};" role="region" aria-label="مؤشرات الأداء">
    @foreach($cards as $card)
    <div class="cmp-kpi-c">
        <div class="cmp-kpi-i"
             style="background: {{ $card['iconBg'] ?? 'var(--surface-hover)' }}; color: {{ $card['accent'] ?? 'var(--text-muted)' }};">
            <x-heroicon :name="$card['icon']" aria-hidden="true" />
        </div>
        <div class="cmp-kpi-b">
            <span class="cmp-kpi-v" dir="ltr">{{ $card['value'] }}</span>
            <span class="cmp-kpi-l">{{ $card['label'] }}</span>
        </div>
    </div>
    @endforeach
</div>
