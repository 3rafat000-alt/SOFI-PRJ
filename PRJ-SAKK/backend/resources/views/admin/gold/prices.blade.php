@extends('layouts.admin')

@section('title', 'أسعار الذهب')
@section('breadcrumbs')
<span class="breadcrumb-item">الذهب</span>
<span class="breadcrumb-item">أسعار الذهب</span>
@endsection

@push('styles')
    @include('admin.gold._styles')
@endpush

@section('content')
<div class="gold-page space-y-6">

    {{-- ===== Hero ===== --}}
    <div class="gold-hero">
        <div class="gold-hero-main">
            <div class="gold-hero-icon"><x-heroicon name="price_change" /></div>
            <div>
                <h1 class="gold-hero-title">أسعار الذهب</h1>
                <p class="gold-hero-sub">إدارة أسعار شراء وبيع الذهب لكل عيار — تُطبّق فوراً على تطبيق المحفظة</p>
            </div>
        </div>
        <div class="gold-hero-side">
            @if($stats['last_updated'])
            <span class="gold-stamp">
                <x-heroicon name="schedule" />
                آخر تحديث {{ \Carbon\Carbon::parse($stats['last_updated'])->diffForHumans() }}
            </span>
            @endif
            <a href="{{ route('admin.gold.transactions') }}" class="btn btn-secondary">
                <x-heroicon name="receipt" class="text-sm" />
                سجل المعاملات
            </a>
        </div>
    </div>

    {{-- ===== KPIs ===== --}}
    <div class="gold-kpis">
        <div class="gold-kpi">
            <div>
                <p class="gold-kpi-label">أسعار نشطة</p>
                <p class="gold-kpi-value">{{ $stats['active_prices'] }}<span class="gold-kpi-sub" style="display:inline; margin-inline-start:.3rem;">/ {{ $stats['total_prices'] }}</span></p>
                <p class="gold-kpi-sub">عيارات مفعّلة للتداول</p>
            </div>
            <div class="gold-kpi-icon" style="background: var(--gold-soft); color: var(--gold-deep);">
                <x-heroicon name="toll" />
            </div>
        </div>
        <div class="gold-kpi">
            <div>
                <p class="gold-kpi-label">متوسط الهامش</p>
                <p class="gold-kpi-value" dir="ltr">{{ number_format($stats['avg_spread'], 2) }}%</p>
                <p class="gold-kpi-sub">الفرق بين الشراء والبيع</p>
            </div>
            <div class="gold-kpi-icon" style="background: var(--info-light); color: var(--info);">
                <x-heroicon name="percent" />
            </div>
        </div>
        <div class="gold-kpi">
            <div>
                <p class="gold-kpi-label">الغرامات المتداولة</p>
                <p class="gold-kpi-value" dir="ltr">{{ number_format($stats['total_grams_traded'], 2) }}<span class="gold-kpi-sub" style="display:inline; margin-inline-start:.3rem;">g</span></p>
                <p class="gold-kpi-sub">إجمالي المعاملات المكتملة</p>
            </div>
            <div class="gold-kpi-icon" style="background: var(--success-light); color: var(--success);">
                <x-heroicon name="scale" />
            </div>
        </div>
        <div class="gold-kpi">
            <div>
                <p class="gold-kpi-label">حجم التداول</p>
                <p class="gold-kpi-value" dir="ltr">&lrm;${{ number_format($stats['total_volume_usd'], 2) }}</p>
                <p class="gold-kpi-sub">{{ number_format($stats['active_users']) }} مستخدم يملك رصيد ذهب</p>
            </div>
            <div class="gold-kpi-icon" style="background: var(--gold-soft); color: var(--gold-deep);">
                <x-heroicon name="payments" />
            </div>
        </div>
    </div>

    {{-- ===== Automatic global-price sync ===== --}}
    <div class="card" x-data="goldAutoSync" data-auto="{{ $stats['auto_enabled'] ? 'true' : 'false' }}">
        <div class="card-body" style="display:flex; flex-wrap:wrap; align-items:flex-start; gap:1.5rem; justify-content:space-between;">

            {{-- left: explainer + state --}}
            <div style="min-width:240px; flex:1;">
                <div style="display:flex; align-items:center; gap:.6rem; margin-bottom:.4rem;">
                    <x-heroicon name="public" style="color:var(--gold-deep);" />
                    <h2 style="font-size:1.05rem; font-weight:800; margin:0;">التحديث التلقائي حسب السعر العالمي</h2>
                    <span class="badge {{ $stats['auto_enabled'] ? 'badge-success' : 'badge-secondary' }}" x-text="auto ? 'مفعّل' : 'معطل'">
                        {{ $stats['auto_enabled'] ? 'مفعّل' : 'معطل' }}
                    </span>
                </div>
                <p class="text-sm text-gray-600" style="margin:0 0 .6rem;">
                    يجلب سعر الذهب العالمي (أونصة XAU/USD) كل ساعة ويحسب أسعار الشراء والبيع لكل عيار تلقائياً حسب النقاء والهامش.
                    المصدر: <span dir="ltr" style="font-weight:700;">{{ $stats['auto_provider'] }}</span>.
                </p>
                <div class="text-sm text-gray-700" style="display:flex; flex-wrap:wrap; gap:1rem;">
                    @if($stats['last_spot_24k'] > 0)
                    <span><x-heroicon name="grade" style="font-size:1rem; vertical-align:-2px;" />
                        آخر سعر غرام 24: <span dir="ltr" style="font-weight:700;">&lrm;${{ number_format($stats['last_spot_24k'], 2) }}</span></span>
                    @endif
                    @if($stats['last_auto_update'])
                    <span><x-heroicon name="sync" style="font-size:1rem; vertical-align:-2px;" />
                        آخر مزامنة: {{ \Carbon\Carbon::parse($stats['last_auto_update'])->diffForHumans() }}</span>
                    @endif
                </div>
            </div>

            {{-- middle: settings form (toggle + margin) --}}
            <form method="POST" action="{{ route('admin.gold.price.auto') }}"
                  style="display:flex; align-items:flex-end; gap:1rem; flex-wrap:wrap;">
                @csrf
                <label class="gold-switch" style="align-self:center;">
                    <input type="hidden" name="auto_update" value="0">
                    <input type="checkbox" name="auto_update" value="1" x-model="auto" {{ $stats['auto_enabled'] ? 'checked' : '' }}>
                    <span class="track"></span>
                    <span class="text-sm font-bold text-gray-700">تفعيل المزامنة</span>
                </label>
                <div>
                    <label class="text-xs font-bold text-gray-600 mb-1 block">هامش المنصّة (±%)</label>
                    <input type="number" step="0.01" min="0" max="10" name="margin" dir="ltr"
                           value="{{ number_format($stats['auto_margin'], 2) }}" class="input text-sm" style="width:110px;" required>
                </div>
                <button type="submit" class="btn btn-secondary btn-sm">
                    <x-heroicon name="save" class="text-sm" />
                    حفظ الإعدادات
                </button>
            </form>

            {{-- right: fetch-now --}}
            <form method="POST" action="{{ route('admin.gold.price.refresh') }}" style="align-self:center;"
                  onsubmit="this.querySelector('button').disabled=true;">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <x-heroicon name="cloud_download" class="text-sm" />
                    جلب الأسعار الآن
                </button>
            </form>
        </div>
    </div>

    {{-- ===== Price management ===== --}}
    <div class="gold-section-head">
        <x-heroicon name="tune" />
        <h2>إدارة الأسعار حسب العيار</h2>
        <span class="count">· {{ $prices->count() }} عيار</span>
    </div>

    <div class="karat-grid">
        @forelse($prices as $price)
        <div class="karat-card {{ $price->is_active ? '' : 'is-off' }}"
             x-data="karatCard" data-buy="{{ (float) $price->buy_price }}" data-sell="{{ (float) $price->sell_price }}">

            {{-- head --}}
            <div class="karat-head">
                <div class="karat-id">
                    <div class="karat-medallion">{{ $price->karat }}</div>
                    <div>
                        <p class="karat-name">{{ $price->karat_label }}</p>
                        <p class="karat-purity-label">نقاء {{ number_format($price->purity, 1) }}%</p>
                    </div>
                </div>
                <div style="display:flex; flex-direction:column; align-items:flex-end; gap:.3rem;">
                    <span class="badge {{ $price->is_active ? 'badge-success' : 'badge-secondary' }}">
                        {{ $price->is_active ? 'نشط' : 'معطل' }}
                    </span>
                    <span class="badge {{ $price->source === 'auto' ? 'badge-info' : 'badge-secondary' }}" title="مصدر السعر">
                        <x-heroicon name="public" style="font-size:.85rem; vertical-align:-2px;" x-show="$price->source === 'auto'" />
<x-heroicon name="edit" style="font-size:.85rem; vertical-align:-2px;" x-show="!($price->source === 'auto')" />
                        {{ $price->source === 'auto' ? 'تلقائي' : 'يدوي' }}
                    </span>
                </div>
            </div>

            {{-- live prices --}}
            <div class="karat-prices">
                <div class="karat-price buy">
                    <p class="karat-price-label"><x-heroicon name="south_west" /> شراء</p>
                    <p class="karat-price-value" dir="ltr">$<span x-text="buy.toFixed(2)">{{ number_format($price->buy_price, 2) }}</span></p>
                </div>
                <div class="karat-price sell">
                    <p class="karat-price-label"><x-heroicon name="north_east" /> بيع</p>
                    <p class="karat-price-value" dir="ltr">$<span x-text="sell.toFixed(2)">{{ number_format($price->sell_price, 2) }}</span></p>
                </div>
            </div>

            {{-- spread + purity --}}
            <div class="karat-meta">
                <span class="spread-chip">
                    <x-heroicon name="swap_vert" />
                    هامش <span dir="ltr" x-text="spread.toFixed(2) + '%'">{{ number_format($price->spread, 2) }}%</span>
                </span>
                <div class="purity-track"><span style="width: {{ number_format($price->purity, 1) }}%;"></span></div>
            </div>

            {{-- edit --}}
            <form method="POST" action="{{ route('admin.gold.price.update', $price) }}" class="karat-edit space-y-3">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-bold text-gray-600 mb-1 block">سعر الشراء ($)</label>
                        <input type="number" step="0.01" min="0.01" name="buy_price" x-model.number="buy"
                               value="{{ $price->buy_price }}" class="input text-sm" dir="ltr" required>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-600 mb-1 block">سعر البيع ($)</label>
                        <input type="number" step="0.01" min="0.01" name="sell_price" x-model.number="sell"
                               value="{{ $price->sell_price }}" class="input text-sm" dir="ltr" required>
                    </div>
                </div>
                <div class="flex items-center justify-between gap-3 pt-1">
                    <label class="gold-switch">
                        {{-- hidden 0 so an unchecked toggle actually deactivates (checkbox sends nothing otherwise) --}}
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" {{ $price->is_active ? 'checked' : '' }}>
                        <span class="track"></span>
                        <span class="text-sm font-bold text-gray-700">مفعّل</span>
                    </label>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <x-heroicon name="save" class="text-sm" />
                        حفظ
                    </button>
                </div>
            </form>
        </div>
        @empty
        <div class="card" style="grid-column: 1 / -1;">
            <div class="card-body">
                <div class="table-empty">
                    <x-heroicon name="price_change" class="table-empty-icon" />
                    <p>لا توجد أسعار ذهب معرّفة بعد</p>
                </div>
            </div>
        </div>
        @endforelse
    </div>
</div>
@endsection
