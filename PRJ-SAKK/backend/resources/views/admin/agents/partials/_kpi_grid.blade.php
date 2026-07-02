@php
    $cards = $cards ?? [];
    $cols = count($cards);
@endphp


<div class="agt-kpi-g" style="--agt-kpi-cols: {{ max($cols, 1) }};" role="region" aria-label="مؤشرات الأداء">
    @foreach($cards as $card)
    <div class="agt-kpi-c">
        <div class="agt-kpi-i"
             style="background: {{ $card['iconBg'] ?? 'var(--surface-hover)' }}; color: {{ $card['accent'] ?? 'var(--text-muted)' }};">
            <x-heroicon :name="$card['icon']" aria-hidden="true" />
        </div>
        <div class="agt-kpi-b">
            <span class="agt-kpi-v" dir="ltr">{{ $card['value'] }}</span>
            <span class="agt-kpi-l">{{ $card['label'] }}</span>
        </div>
    </div>
    @endforeach
</div>
