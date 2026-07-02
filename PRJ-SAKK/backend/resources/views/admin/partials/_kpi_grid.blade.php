@php
    $cards = $cards ?? [];
    $cols = count($cards);
@endphp
@once @endonce

<div class="kpi-grid" style="--kpi-cols: {{ max($cols, 1) }};" role="region" aria-label="مؤشرات الأداء">
    @foreach($cards as $card)
    <div class="kpi-card">
        <div class="kpi-card-icon"
             style="background: {{ $card['iconBg'] ?? 'var(--surface-hover)' }}; color: {{ $card['accent'] ?? 'var(--text-muted)' }};">
            <x-heroicon :name="$card['icon']" aria-hidden="true" />
        </div>
        <div class="kpi-card-body">
            <span class="kpi-card-value" dir="ltr">{{ $card['value'] }}</span>
            <span class="kpi-card-label">{{ $card['label'] }}</span>
        </div>
    </div>
    @endforeach
</div>
