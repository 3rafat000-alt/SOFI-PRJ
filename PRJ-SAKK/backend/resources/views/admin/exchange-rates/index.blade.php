@extends('layouts.admin')

@section('title', 'سعر صرف الدولار')
@section('breadcrumbs')
<span class="breadcrumb-item">الإعدادات</span>
<span class="breadcrumb-item">سعر الصرف</span>
@endsection

@push('styles')
<style>
/* ============================================================
   SAKK EXCHANGE RATES — سعر الصرف
   Clean · Sophisticated · Unified (v2)
   ============================================================ */

/* ── Header ── */
.xr-hdr {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-md);
  flex-wrap: wrap;
  padding: var(--space-lg);
  background: var(--surface);
  border-radius: var(--radius-main);
}
.xr-hdr-info {
  display: flex;
  align-items: center;
  gap: var(--space-md);
  min-width: 0;
}
.xr-hdr-icon {
  width: 44px; height: 44px;
  border-radius: var(--radius-sm);
  background: var(--sukk-primary-soft);
  color: var(--sukk-primary);
  display: grid; place-items: center;
  flex: none;
}
.xr-hdr-icon svg[data-slot="icon"] { width: 22px; height: 22px; }
.xr-hdr-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1.2;
}
.xr-hdr-sub {
  font-size: var(--font-size-xs);
  color: var(--text-muted);
  margin-top: 2px;
}
.xr-hdr-status {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.35rem 0.75rem;
  font-size: var(--font-size-xs);
  font-weight: 700;
  border-radius: var(--radius-sm);
}
.xr-hdr-status.is-active {
  background: var(--success-light);
  color: var(--success-dark);
}
.xr-hdr-status.is-inactive {
  background: var(--surface-hover);
  color: var(--text-muted);
}
.xr-hdr-status .dot {
  width: 6px; height: 6px;
  border-radius: 50%;
}
.xr-hdr-status.is-active .dot { background: var(--success); }
.xr-hdr-status.is-inactive .dot { background: var(--text-muted); }

/* ── Rate tiles ── */
.xr-tiles {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--space-md);
}
.xr-tile {
  background: var(--surface);
  border-radius: var(--radius-main);
  padding: var(--space-lg);
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}
.xr-tile .tile-label {
  font-size: var(--font-size-xs);
  font-weight: 600;
  color: var(--text-muted);
  display: flex;
  align-items: center;
  gap: 0.35rem;
}
.xr-tile .tile-label svg[data-slot="icon"] { width: 16px; height: 16px; }
.xr-tile .tile-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-primary);
  font-variant-numeric: tabular-nums;
  line-height: 1.2;
  direction: ltr;
  text-align: right;
}
.xr-tile .tile-sub {
  font-size: var(--font-size-xs);
  color: var(--text-muted);
}
.xr-tile.is-buy .tile-value { color: var(--success); }
.xr-tile.is-sell .tile-value { color: var(--danger); }

/* ── Converter ── */
.xr-convert {
  background: var(--surface);
  border-radius: var(--radius-main);
  padding: var(--space-lg);
}
.xr-convert-header {
  display: flex;
  align-items: center;
  gap: var(--space-md);
  margin-bottom: var(--space-lg);
}
.xr-convert-header .cv-icon {
  width: 40px; height: 40px;
  border-radius: var(--radius-sm);
  background: var(--sukk-primary-soft);
  color: var(--sukk-primary);
  display: grid; place-items: center;
  flex: none;
}
.xr-convert-header .cv-icon svg[data-slot="icon"] { width: 20px; height: 20px; }
.xr-convert-header .cv-title {
  font-size: 1rem;
  font-weight: 700;
  color: var(--text-primary);
}
.xr-convert-header .cv-desc {
  font-size: var(--font-size-xs);
  color: var(--text-muted);
  margin-top: 1px;
}

.xr-convert-grid {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: var(--space-md);
  align-items: end;
}
.xr-convert-grid .cv-field { display: flex; flex-direction: column; gap: 0.3rem; }
.xr-convert-grid .cv-field .cv-label {
  font-size: var(--font-size-xs);
  font-weight: 600;
  color: var(--text-secondary);
}
.xr-convert-grid .cv-field input {
  height: 42px;
  padding: 0 var(--space-md);
  font-size: var(--font-size-sm);
  font-family: inherit;
  color: var(--text-primary);
  background: var(--input-bg);
  border: none;
  border-radius: var(--radius-sm);
  outline: none;
  direction: rtl;
  transition: box-shadow var(--transition-fast);
}
.xr-convert-grid .cv-field input:focus {
  box-shadow: var(--shadow-focus);
  background: var(--surface);
}

.xr-convert-out {
  padding: 0.6rem var(--space-md);
  border-radius: var(--radius-sm);
  background: var(--bg);
  height: 42px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.xr-convert-out .co-label {
  font-size: var(--font-size-xs);
  font-weight: 600;
  color: var(--text-muted);
}
.xr-convert-out .co-value {
  font-size: var(--font-size-sm);
  font-weight: 700;
  color: var(--text-primary);
  font-variant-numeric: tabular-nums;
  direction: ltr;
}
.xr-convert-out.is-buy .co-value { color: var(--success); }
.xr-convert-out.is-sell .co-value { color: var(--danger); }

/* ── Form ── */
.xr-form {
  background: var(--surface);
  border-radius: var(--radius-main);
  padding: var(--space-lg);
}
.xr-form-header {
  display: flex;
  align-items: center;
  gap: var(--space-md);
  margin-bottom: var(--space-lg);
}
.xr-form-header .fm-icon {
  width: 40px; height: 40px;
  border-radius: var(--radius-sm);
  background: var(--sukk-primary-soft);
  color: var(--sukk-primary);
  display: grid; place-items: center;
  flex: none;
}
.xr-form-header .fm-icon svg[data-slot="icon"] { width: 20px; height: 20px; }
.xr-form-header .fm-title {
  font-size: 1rem;
  font-weight: 700;
  color: var(--text-primary);
}
.xr-form-header .fm-desc {
  font-size: var(--font-size-xs);
  color: var(--text-muted);
  margin-top: 1px;
}

.xr-form-preview {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: var(--space-md);
  padding: var(--space-md);
  background: var(--bg);
  border-radius: var(--radius-sm);
  margin-bottom: var(--space-lg);
}
.xr-form-preview .fp-item { display: flex; flex-direction: column; gap: 2px; }
.xr-form-preview .fp-item .fp-label {
  font-size: var(--font-size-xs);
  font-weight: 600;
  color: var(--text-muted);
}
.xr-form-preview .fp-item .fp-value {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--text-primary);
  font-variant-numeric: tabular-nums;
  direction: ltr;
  text-align: right;
}
.xr-form-preview .fp-item.is-buy .fp-value { color: var(--success); }
.xr-form-preview .fp-item.is-sell .fp-value { color: var(--danger); }
.xr-form-preview .fp-item.is-profit .fp-value { color: var(--accent-dark); }

.xr-form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--space-lg);
  margin-bottom: var(--space-lg);
}
.xr-form-grid .fg-field { display: flex; flex-direction: column; gap: 0.3rem; }
.xr-form-grid .fg-field .fg-label {
  font-size: var(--font-size-sm);
  font-weight: 600;
  color: var(--text-secondary);
}
.xr-form-grid .fg-field .fg-hint {
  font-size: var(--font-size-xs);
  color: var(--text-muted);
}
.xr-form-grid .fg-field input {
  height: 48px;
  padding: 0 var(--space-md);
  padding-inline-end: 5rem;
  font-size: 1.25rem;
  font-weight: 700;
  font-family: inherit;
  color: var(--text-primary);
  background: var(--input-bg);
  border: none;
  border-radius: var(--radius-sm);
  outline: none;
  transition: box-shadow var(--transition-fast);
}
.xr-form-grid .fg-field input:focus {
  box-shadow: var(--shadow-focus);
  background: var(--surface);
}

.xr-form-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-md);
  flex-wrap: wrap;
  padding-top: var(--space-lg);
  border-top: 1px solid var(--border-light);
}
.xr-form-footer .ff-info {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-size: var(--font-size-xs);
  color: var(--text-muted);
}

/* ── History ── */
.xr-history {
  background: var(--surface);
  border-radius: var(--radius-main);
  overflow: hidden;
}
.xr-history-trigger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: var(--space-md) var(--space-lg);
  border: none;
  background: transparent;
  cursor: pointer;
  font-family: inherit;
  transition: background var(--transition-fast);
}
.xr-history-trigger:hover { background: var(--surface-hover); }
.xr-history-trigger .ht-left {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}
.xr-history-trigger .ht-left svg[data-slot="icon"] {
  width: 18px; height: 18px;
  color: var(--text-secondary);
}
.xr-history-trigger .ht-left .ht-title {
  font-size: var(--font-size-sm);
  font-weight: 700;
  color: var(--text-primary);
}
.xr-history-trigger .ht-left .ht-count {
  font-size: var(--font-size-xs);
  font-weight: 700;
  padding: 0.15rem 0.5rem;
  border-radius: var(--radius-sm);
  background: var(--sukk-primary-soft);
  color: var(--sukk-primary);
}
.xr-history-trigger .ht-chev {
  color: var(--text-muted);
  transition: transform var(--transition-fast);
}
.xr-history-trigger .ht-chev.open { transform: rotate(180deg); }

.xr-history-body { padding: 0 var(--space-lg) var(--space-lg); }
.xr-history-table { width: 100%; border-collapse: collapse; }
.xr-history-table th {
  font-size: var(--font-size-xs);
  font-weight: 700;
  color: var(--text-muted);
  padding: 0.6rem 0.4rem;
  text-align: start;
  border-bottom: 1px solid var(--border-light);
}
.xr-history-table td {
  font-size: var(--font-size-sm);
  font-weight: 600;
  color: var(--text-primary);
  padding: 0.65rem 0.4rem;
  border-bottom: 1px solid var(--border-light);
  font-variant-numeric: tabular-nums;
  direction: ltr;
  text-align: left;
}
.xr-history-table tr:hover td { background: var(--surface-hover); }
.xr-history-table .h-source {
  font-size: var(--font-size-xs);
  font-weight: 700;
  padding: 0.15rem 0.45rem;
  border-radius: var(--radius-sm);
  background: var(--sukk-primary-soft);
  color: var(--sukk-primary);
  white-space: nowrap;
}
.xr-history-table .h-date {
  font-size: var(--font-size-xs);
  color: var(--text-muted);
  white-space: nowrap;
}
.xr-history-table .h-buy { color: var(--success); }
.xr-history-table .h-sell { color: var(--danger); }

/* ── Null state ── */
.xr-null {
  text-align: center;
  padding: var(--space-xl);
  background: var(--surface);
  border-radius: var(--radius-main);
  color: var(--text-muted);
}
.xr-null svg[data-slot="icon"] {
  width: 36px; height: 36px;
  margin: 0 auto var(--space-md);
  color: var(--border-strong);
  display: block;
}

/* ── Responsive ── */
@media (max-width: 767px) {
  .xr-tiles { grid-template-columns: 1fr; }
  .xr-convert-grid { grid-template-columns: 1fr; }
  .xr-form-preview { grid-template-columns: repeat(2, 1fr); }
  .xr-form-grid { grid-template-columns: 1fr; }
  .xr-hdr { padding: var(--space-md); }
  .xr-convert { padding: var(--space-md); }
  .xr-form { padding: var(--space-md); }
  .xr-tile { padding: var(--space-md); }
  .xr-tile .tile-value { font-size: 1.25rem; }
}
</style>
@endpush

@section('content')
<div x-data="fxRates" x-cloak>
<div class="page-content">

  {{-- ===== Header ===== --}}
  <div class="xr-hdr">
    <div class="xr-hdr-info">
      <div class="xr-hdr-icon">
        <x-heroicon name="currency_exchange" />
      </div>
      <div>
        <h1 class="xr-hdr-title">سعر صرف الدولار</h1>
        <p class="xr-hdr-sub">USD/SYP — إدارة سعر الصرف والسبريد</p>
      </div>
    </div>
    @if ($rate)
      <span class="xr-hdr-status is-active"><span class="dot"></span> مباشر</span>
    @else
      <span class="xr-hdr-status is-inactive"><span class="dot"></span> غير مفعّل</span>
    @endif
  </div>

  @if (!$rate)
  <div class="xr-null">
    <x-heroicon name="warning" />
    <p>لم يتم تعيين سعر الصرف بعد. استخدم النموذج أدناه لتعيين السعر.</p>
  </div>
  @endif

  {{-- ===== Rate Tiles ===== --}}
  <div class="xr-tiles">
    <div class="xr-tile is-buy">
      <span class="tile-label"><x-heroicon name="arrow_downward" /> سعر الشراء</span>
      <span class="tile-value" x-text="fmt(buyCalc)">{{ $rate ? number_format($rate->getBuyRate(), 2) : '—' }}</span>
      <span class="tile-sub">ل.س / $1 — للعميل</span>
    </div>
    <div class="xr-tile">
      <span class="tile-label"><x-heroicon name="currency_exchange" /> السعر الوسطي</span>
      <span class="tile-value" x-text="fmt(rate)">{{ $rate ? rtrim(rtrim(number_format((float)$rate->rate, 2), '0'), '.') : '—' }}</span>
      <span class="tile-sub">ل.س / $1 — سعر الأساس</span>
    </div>
    <div class="xr-tile is-sell">
      <span class="tile-label"><x-heroicon name="arrow_upward" /> سعر البيع</span>
      <span class="tile-value" x-text="fmt(sellCalc)">{{ $rate ? number_format($rate->getSellRate(), 2) : '—' }}</span>
      <span class="tile-sub">ل.س / $1 — للعميل</span>
    </div>
  </div>

  {{-- ===== Converter ===== --}}
  <div class="xr-convert">
    <div class="xr-convert-header">
      <div class="cv-icon"><x-heroicon name="calculate" /></div>
      <div>
        <h2 class="cv-title">محوّل فوري</h2>
        <p class="cv-desc">احسب قيمة أي مبلغ بالدولار</p>
      </div>
    </div>
    <div class="xr-convert-grid">
      <div class="cv-field">
        <span class="cv-label">المبلغ بالدولار</span>
        <input type="number" x-model.number="fxAmount" step="1" min="0" placeholder="100">
      </div>
      <div class="xr-convert-out is-buy">
        <span class="co-label">عند الشراء</span>
        <span class="co-value" x-text="fmt(fxBuyOut)">—</span>
      </div>
      <div class="xr-convert-out is-sell">
        <span class="co-label">عند البيع</span>
        <span class="co-value" x-text="fmt(fxSellOut)">—</span>
      </div>
    </div>
  </div>

  {{-- ===== Update Form ===== --}}
  <div class="xr-form">
    <div class="xr-form-header">
      <div class="fm-icon"><x-heroicon name="tune" /></div>
      <div>
        <h2 class="fm-title">تعديل سعر الصرف</h2>
        <p class="fm-desc">اضبط السعر الأساسي والسبريد</p>
      </div>
    </div>

    <form method="POST" action="{{ route('admin.exchange-rates.update') }}" @submit="saving = true">
      @csrf
      @method('PUT')

      {{-- Preview --}}
      <div class="xr-form-preview">
        <div class="fp-item is-buy">
          <span class="fp-label">سعر الشراء</span>
          <span class="fp-value" x-text="fmt(buyCalc)">{{ $rate ? number_format($rate->getBuyRate(), 2) : '—' }}</span>
        </div>
        <div class="fp-item">
          <span class="fp-label">السعر الوسطي</span>
          <span class="fp-value" x-text="fmt(rate)">{{ $rate ? rtrim(rtrim(number_format((float)$rate->rate, 2), '0'), '.') : '—' }}</span>
        </div>
        <div class="fp-item is-sell">
          <span class="fp-label">سعر البيع</span>
          <span class="fp-value" x-text="fmt(sellCalc)">{{ $rate ? number_format($rate->getSellRate(), 2) : '—' }}</span>
        </div>
        <div class="fp-item is-profit">
          <span class="fp-label">الربح / $100</span>
          <span class="fp-value" x-text="qvMoney('ل.س ', profitPer100, 0)">{{ $rate ? "\u{2066}ل.س " . number_format(($rate->getSellRate() - $rate->getBuyRate()) * 100, 0) . "\u{2069}" : '—' }}</span>
        </div>
      </div>

      {{-- Inputs --}}
      <div class="xr-form-grid">
        <div class="fg-field">
          <span class="fg-label">سعر الصرف الأساسي</span>
          <div style="position:relative;">
            <input type="number" name="rate" step="0.01" min="1"
              x-model.number="rate"
              value="{{ old('rate', $rate ? (float)$rate->rate : 13000) }}" required
              aria-label="سعر الصرف الأساسي">
            <span style="position:absolute;inset-inline-end:0.85rem;top:50%;transform:translateY(-50%);font-size:var(--font-size-xs);font-weight:700;color:var(--text-muted);pointer-events:none;">ل.س / $1</span>
          </div>
          <span class="fg-hint">كم ليرة سورية يساوي دولار واحد</span>
          @error('rate')<p style="color:var(--danger);font-size:var(--font-size-xs);margin-top:0.25rem;">{{ $message }}</p>@enderror
        </div>
        <div class="fg-field">
          <span class="fg-label">نسبة السبريد</span>
          <div style="position:relative;">
            <input type="number" name="spread" step="0.1" min="0" max="20"
              x-model.number="spread"
              value="{{ old('spread', $rate ? (float)$rate->spread : 2) }}" required
              aria-label="نسبة السبريد">
            <span style="position:absolute;inset-inline-end:0.85rem;top:50%;transform:translateY(-50%);font-size:var(--font-size-xs);font-weight:700;color:var(--text-muted);pointer-events:none;">%</span>
          </div>
          <span class="fg-hint">الفرق بين سعر الشراء والبيع (0–20%)</span>
          @error('spread')<p style="color:var(--danger);font-size:var(--font-size-xs);margin-top:0.25rem;">{{ $message }}</p>@enderror
        </div>
      </div>

      {{-- Footer --}}
      <div class="xr-form-footer">
        @if ($rate)
          <span class="ff-info">
            <x-heroicon name="schedule" />
            آخر تحديث: {{ $rate->updated_at->diffForHumans() }}
          </span>
        @else
          <span></span>
        @endif
        <button type="submit" class="btn btn-primary" :disabled="saving">
          <template x-if="!saving"><x-heroicon name="save" /></template>
          <template x-if="saving"><span class="spinner" style="border-top-color:#fff;border-color:rgba(255,255,255,0.3);width:16px;height:16px;"></span></template>
          <span x-text="saving ? 'جاري الحفظ…' : 'حفظ'">حفظ</span>
        </button>
      </div>
    </form>
  </div>

  {{-- ===== History ===== --}}
  @if ($rateHistory && $rateHistory->isNotEmpty())
  <div class="xr-history" x-data="{ historyOpen: false }">
    <button type="button" class="xr-history-trigger" @click="historyOpen = !historyOpen">
      <div class="ht-left">
        <x-heroicon name="history" />
        <span class="ht-title">سجل التغييرات</span>
        <span class="ht-count">{{ $rateHistory->count() }}</span>
      </div>
      <x-heroicon name="expand_more" class="ht-chev" x-bind:class="historyOpen && 'open'" />
    </button>
    <div class="xr-history-body" x-show="historyOpen" x-collapse.duration.300ms>
      <table class="xr-history-table">
        <thead>
          <tr>
            <th>التاريخ</th>
            <th>السعر الوسطي</th>
            <th>الشراء</th>
            <th>البيع</th>
            <th>المصدر</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($rateHistory as $h)
          <tr>
            <td class="h-date">{{ $h->recorded_at ? $h->recorded_at->format('Y-m-d H:i') : $h->created_at->format('Y-m-d H:i') }}</td>
            <td>{{ rtrim(rtrim(number_format((float)$h->rate, 2), '0'), '.') }}</td>
            <td class="h-buy">{{ number_format((float)$h->buy_rate, 2) }}</td>
            <td class="h-sell">{{ number_format((float)$h->sell_rate, 2) }}</td>
            <td><span class="h-source">{{ $h->source ?? 'يدوي' }}</span></td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif

</div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('fxRates', () => ({
    rate: {{ $rate ? (float)$rate->rate : 13000 }},
    spread: {{ $rate ? (float)$rate->spread : 2 }},
    fxAmount: 100,
    saving: false,

    get halfSpread() { return this.spread / 200; },
    get buyCalc() { return this.rate * (1 - this.halfSpread); },
    get sellCalc() { return this.rate * (1 + this.halfSpread); },
    get profitPer100() { return (this.sellCalc - this.buyCalc) * 100; },
    get fxBuyOut() { return this.fxAmount * this.buyCalc; },
    get fxSellOut() { return this.fxAmount * this.sellCalc; },

    fmt(n, d) {
      if (n == null || isNaN(n)) return '—';
      const opts = { minimumFractionDigits: d ?? 2, maximumFractionDigits: d ?? 2 };
      return Number(n).toLocaleString('ar-SY', opts);
    },

    // Wraps "symbol + number" in a real Unicode LTR isolate (LRI/PDI) so the
    // token stays visually left-to-right (symbol left, number right, never
    // reversed) inside the surrounding RTL page — mirrors App\Support\Money.
    qvMoney(sym, n, d) {
      const num = this.fmt(n, d);
      if (num === '—') return num;
      return '⁦' + sym + num + '⁩';
    },
  }));
});
</script>
@endpush
@endsection
