@extends('layouts.admin')

@section('title', 'إدارة الرسوم')
@section('breadcrumbs')
<span class="breadcrumb-item">الإعدادات</span>
<span class="breadcrumb-item">الرسوم</span>
@endsection

@push('styles')
<style>
/* ============================================================
   SAKK FEES — إدارة الرسوم
   Clean · Inline · Professional (v5)
   ============================================================ */

/* ── Header ── */
.fees-hdr {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-md);
  flex-wrap: wrap;
  padding: var(--space-lg);
  background: var(--surface);
  border-radius: var(--radius-main);
}
.fees-hdr-info {
  display: flex;
  align-items: center;
  gap: var(--space-md);
  min-width: 0;
}
.fees-hdr-icon {
  width: 44px; height: 44px;
  border-radius: var(--radius-sm);
  background: var(--sukk-primary-soft);
  color: var(--sukk-primary);
  display: grid; place-items: center;
  flex: none;
}
.fees-hdr-icon svg[data-slot="icon"] { width: 22px; height: 22px; }
.fees-hdr-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1.2;
}
.fees-hdr-sub {
  font-size: var(--font-size-xs);
  color: var(--text-muted);
  margin-top: 2px;
}
.fees-hdr-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.35rem 0.75rem;
  font-size: var(--font-size-xs);
  font-weight: 700;
  color: var(--sukk-primary);
  background: var(--sukk-primary-soft);
  border-radius: var(--radius-sm);
}

/* ── Summary row ── */
.fees-summary {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: var(--space-md);
}
.fees-summary-card {
  background: var(--surface);
  border-radius: var(--radius-main);
  padding: var(--space-md);
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}
.fees-summary-card .sum-label {
  font-size: var(--font-size-xs);
  font-weight: 600;
  color: var(--text-muted);
}
.fees-summary-card .sum-value {
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--text-primary);
  font-variant-numeric: tabular-nums;
}
.fees-summary-card .sum-sub {
  font-size: var(--font-size-xs);
  color: var(--text-muted);
}

/* ── Section ── */
.fees-section {
  background: var(--surface);
  border-radius: var(--radius-main);
  overflow: hidden;
}
.fees-section-header {
  display: flex;
  align-items: center;
  gap: var(--space-md);
  padding: var(--space-sm) var(--space-lg);
  background: var(--bg);
  border-bottom: 1px solid var(--border-light);
}
.fees-section-num {
  width: 26px; height: 26px;
  border-radius: var(--radius-sm);
  background: var(--sukk-primary-soft);
  color: var(--sukk-primary);
  display: grid; place-items: center;
  font-size: 0.7rem;
  font-weight: 800;
  flex: none;
}
.fees-section-icon {
  width: 32px; height: 32px;
  border-radius: var(--radius-sm);
  background: var(--sukk-primary-soft);
  color: var(--sukk-primary);
  display: grid; place-items: center;
  flex: none;
}
.fees-section-icon svg[data-slot="icon"] { width: 16px; height: 16px; }
.fees-section-title {
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--text-primary);
  flex: 1;
  letter-spacing: 0.02em;
}
.fees-section-count {
  font-size: 0.65rem;
  font-weight: 600;
  color: var(--text-muted);
  background: var(--surface);
  padding: 0.15rem 0.5rem;
  border-radius: var(--radius-sm);
}

/* ── Fee row (inline — no accordion) ── */
.fee-row {
  display: flex;
  align-items: center;
  gap: var(--space-md);
  padding: var(--space-sm) var(--space-lg);
  border-bottom: 1px solid var(--border-light);
  min-height: 52px;
  transition: background var(--transition-fast);
}
.fee-row:last-child { border-bottom: none; }
.fee-row:hover { background: var(--surface-hover); }

.fee-row-icon {
  width: 32px; height: 32px;
  border-radius: var(--radius-sm);
  background: var(--bg);
  color: var(--text-secondary);
  display: grid; place-items: center;
  flex: none;
}
.fee-row-icon svg[data-slot="icon"] { width: 16px; height: 16px; }

.fee-row-info {
  min-width: 0;
  width: 140px;
  flex: none;
}
.fee-row-name {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.fee-row-method {
  font-size: 0.6rem;
  color: var(--text-muted);
  margin-top: 1px;
}

.fee-row-status {
  flex: none;
  width: 60px;
  display: flex;
  justify-content: center;
}
.fee-row-status .status-dot {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.6rem;
  font-weight: 600;
}
.fee-row-status .status-dot .dot {
  width: 7px; height: 7px;
  border-radius: 50%;
}
.fee-row-status .status-dot .dot.on { background: var(--success); }
.fee-row-status .status-dot .dot.off { background: var(--border-strong); }
.fee-row-status .status-dot .label-on { color: var(--success); }
.fee-row-status .status-dot .label-off { color: var(--text-muted); }

.fee-row-rate {
  direction: ltr;
  text-align: right;
  flex: none;
  width: 90px;
}
.fee-row-rate .r-primary {
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--text-primary);
  font-variant-numeric: tabular-nums;
}
.fee-row-rate .r-secondary {
  font-size: 0.6rem;
  color: var(--text-muted);
  display: block;
  text-align: right;
}

/* ── Inline edit controls ── */
.fee-row-edit {
  flex: 1;
  min-width: 0;
  display: flex;
  align-items: center;
  gap: var(--space-sm);
}
.fee-row-edit .fet {
  display: flex;
  gap: 0;
  padding: 1px;
  background: var(--bg);
  border-radius: var(--radius-sm);
  flex: none;
}
.fee-row-edit .fet-btn {
  padding: 0.25rem 0.6rem;
  font-size: 0.6rem;
  font-weight: 600;
  color: var(--text-secondary);
  background: transparent;
  border: none;
  border-radius: 3px;
  cursor: pointer;
  font-family: inherit;
  transition: all var(--transition-fast);
  white-space: nowrap;
  line-height: 1.3;
}
.fee-row-edit .fet-btn:hover { color: var(--text-primary); }
.fee-row-edit .fet-btn--active {
  background: var(--surface);
  color: var(--sukk-primary);
  font-weight: 700;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.fee-row-edit .fei {
  position: relative;
  flex: 1;
  min-width: 80px;
  max-width: 140px;
}
.fee-row-edit .fei input {
  padding-inline-end: 1.6rem;
  height: 30px;
  font-size: 0.7rem;
  background: var(--input-bg);
  border: none;
  border-radius: var(--radius-sm);
  outline: none;
  width: 100%;
  font-family: inherit;
  color: var(--text-primary);
  padding-inline-start: 0.5rem;
  transition: box-shadow var(--transition-fast);
}
.fee-row-edit .fei input:focus {
  box-shadow: var(--shadow-focus);
  background: var(--surface);
}
.fee-row-edit .fei-suffix {
  position: absolute;
  inset-inline-end: 0.5rem;
  top: 50%;
  transform: translateY(-50%);
  font-size: 0.6rem;
  font-weight: 700;
  color: var(--text-muted);
  pointer-events: none;
}

.fee-row-edit .btn-save {
  flex: none;
  height: 30px;
  padding: 0 0.7rem;
  font-size: 0.65rem;
  font-weight: 700;
  border-radius: var(--radius-sm);
  border: none;
  background: var(--sukk-primary);
  color: #fff;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-family: inherit;
  transition: opacity var(--transition-fast);
}
.fee-row-edit .btn-save:hover { opacity: 0.9; }
.fee-row-edit .btn-save svg[data-slot="icon"] { width: 13px; height: 13px; }

.fee-row-toggle {
  flex: none;
  width: 34px;
  display: flex;
  justify-content: center;
}
.fee-row-toggle form { display: flex; }
.fee-row-toggle .tgl {
  width: 32px; height: 18px;
  border-radius: var(--radius-full);
  background: var(--border-strong);
  position: relative;
  transition: background var(--transition-fast);
  cursor: pointer;
  display: block;
  border: none;
  padding: 0;
}
.fee-row-toggle .tgl::after {
  content: '';
  position: absolute;
  top: 2px;
  inset-inline-start: 2px;
  width: 14px; height: 14px;
  border-radius: 50%;
  background: #fff;
  box-shadow: 0 1px 2px rgba(0,0,0,0.12);
  transition: transform var(--transition-fast);
}
.fee-row-toggle .tgl.is-on { background: var(--sukk-primary); }
.fee-row-toggle .tgl.is-on::after {
  transform: translateX(14px);
}
[dir="rtl"] .fee-row-toggle .tgl.is-on::after {
  transform: translateX(-14px);
}

/* ── Calculator ── */
.fees-calc {
  background: var(--surface);
  border-radius: var(--radius-main);
  padding: var(--space-lg);
}
.fees-calc-header {
  display: flex;
  align-items: center;
  gap: var(--space-md);
  margin-bottom: var(--space-md);
}
.fees-calc-header .calc-icon {
  width: 36px; height: 36px;
  border-radius: var(--radius-sm);
  background: var(--sukk-primary-soft);
  color: var(--sukk-primary);
  display: grid; place-items: center;
  flex: none;
}
.fees-calc-header .calc-icon svg[data-slot="icon"] { width: 18px; height: 18px; }
.fees-calc-header .calc-title {
  font-size: 0.9rem;
  font-weight: 700;
  color: var(--text-primary);
}
.fees-calc-header .calc-desc {
  font-size: var(--font-size-xs);
  color: var(--text-muted);
  margin-top: 1px;
}
.fees-calc-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-md);
}
@media (min-width: 768px) {
  .fees-calc-grid { grid-template-columns: 1fr 1fr 1.5fr; }
}
.calc-field { display: flex; flex-direction: column; gap: 0.25rem; }
.calc-field .cf-label {
  font-size: var(--font-size-xs);
  font-weight: 600;
  color: var(--text-secondary);
}
.calc-field select,
.calc-field input {
  height: 38px;
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
.calc-field select:focus,
.calc-field input:focus {
  box-shadow: var(--shadow-focus);
  background: var(--surface);
}
.calc-field .calc-result {
  height: 38px;
  padding: 0 var(--space-md);
  border-radius: var(--radius-sm);
  background: var(--input-bg);
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: var(--font-size-sm);
}
.calc-field .calc-result .cr-placeholder {
  color: var(--text-muted);
}
.calc-result-detail {
  display: flex;
  align-items: center;
  gap: var(--space-md);
}
.calc-result-detail .cr-divider {
  width: 1px;
  height: 1.25rem;
  background: var(--border);
}
.calc-result-detail .cr-label {
  color: var(--text-muted);
  font-size: var(--font-size-xs);
}
.calc-result-detail .cr-value {
  font-weight: 700;
  color: var(--text-primary);
  font-variant-numeric: tabular-nums;
}

/* ── Responsive ── */
@media (max-width: 1023px) {
  .fee-row { flex-wrap: wrap; padding: var(--space-md); gap: var(--space-sm); }
  .fee-row-info { width: 100px; }
  .fee-row-rate { width: 70px; }
  .fee-row-edit { flex: 1 1 100%; order: 10; }
}
@media (max-width: 767px) {
  .fees-summary { grid-template-columns: repeat(2, 1fr); }
  .fees-hdr { padding: var(--space-md); }
  .fees-calc { padding: var(--space-md); }
  .fees-section-header { padding: var(--space-sm) var(--space-md); }
  .fee-row { padding: var(--space-sm) var(--space-md); }
  .fee-row-edit .fei { max-width: 100px; }
}
</style>
@endpush

@section('content')
<div class="page-content">

  {{-- ===== Header ===== --}}
  @php
    $allFeesFlat = $fees->flatten();
    $activeCount  = $allFeesFlat->where('is_active', true)->count();
    $totalCount   = $allFeesFlat->count();
  @endphp
  <div class="fees-hdr">
    <div class="fees-hdr-info">
      <div class="fees-hdr-icon">
        <x-heroicon name="payments" />
      </div>
      <div>
        <h1 class="fees-hdr-title">إدارة الرسوم</h1>
        <p class="fees-hdr-sub">تعديل رسوم المنصة — كل العمليات في مكان واحد</p>
      </div>
    </div>
    <div class="fees-hdr-badge">
      <x-heroicon name="check_circle" style="width:14px;height:14px;" />
      {{ $activeCount }} / {{ $totalCount }} مفعّل
    </div>
  </div>

  {{-- ===== Summary ===== --}}
  <div class="fees-summary">
    <div class="fees-summary-card">
      <span class="sum-label">إجمالي الرسوم</span>
      <span class="sum-value">{{ $totalCount }}</span>
      <span class="sum-sub">رسم مسجّل</span>
    </div>
    <div class="fees-summary-card">
      <span class="sum-label">مفعّلة</span>
      <span class="sum-value">{{ $activeCount }}</span>
      <span class="sum-sub">نشطة حالياً</span>
    </div>
    <div class="fees-summary-card">
      <span class="sum-label">معطّلة</span>
      <span class="sum-value">{{ $totalCount - $activeCount }}</span>
      <span class="sum-sub">غير نشطة</span>
    </div>
    <div class="fees-summary-card">
      <span class="sum-label">الأقسام</span>
      <span class="sum-value">{{ $fees->count() }}</span>
      <span class="sum-sub">فئة تشغيلية</span>
    </div>
  </div>

  {{-- ===== Sections ===== --}}
  @php
    $sections = [
      1 => ['label' => 'المحفظة',   'types' => ['deposit', 'withdrawal'],              'icon' => 'account_balance_wallet'],
      2 => ['label' => 'البطاقات',  'types' => ['card_fund'],                          'icon' => 'credit_card'],
      3 => ['label' => 'التحويلات', 'types' => ['transfer', 'p2p'],                    'icon' => 'swap_horiz'],
      4 => ['label' => 'الذهب',     'types' => ['exchange'],                            'icon' => 'currency_exchange'],
      5 => ['label' => 'الجهات',    'types' => ['partner'],                             'icon' => 'groups'],
    ];
    $feeIcons = [
      'deposit'    => 'add',
      'withdrawal' => 'arrow_upward',
      'card_fund'  => 'credit_card',
      'transfer'   => 'send',
      'p2p'        => 'currency_exchange',
      'exchange'   => 'monetization_on',
      'partner'    => 'handshake',
    ];
  @endphp

  @foreach($sections as $num => $section)
    @php
      $secFees = collect();
      foreach ($section['types'] as $type) {
        if ($fees->has($type)) {
          $secFees = $secFees->merge($fees[$type]);
        }
      }
    @endphp
    @if($secFees->isNotEmpty())
      <div class="fees-section">
        {{-- Section header --}}
        <div class="fees-section-header">
          <div class="fees-section-num">{{ $num }}</div>
          <div class="fees-section-icon">
            <x-heroicon name="{{ $section['icon'] }}" />
          </div>
          <span class="fees-section-title">{{ $section['label'] }}</span>
          <span class="fees-section-count">{{ $secFees->count() }}</span>
        </div>

        {{-- Fee rows (inline, no accordion) --}}
        @foreach($secFees as $fee)
          @php $type = $fee->type; @endphp
          <div class="fee-row">
            {{-- Icon --}}
            <div class="fee-row-icon">
              <x-heroicon name="{{ $feeIcons[$type] ?? 'receipt' }}" />
            </div>

            {{-- Name + method --}}
            <div class="fee-row-info">
              <div class="fee-row-name">{{ $fee->name_ar }}</div>
              <div class="fee-row-method">{{ $fee->payment_method ?? '—' }}</div>
            </div>

            {{-- Status --}}
            <div class="fee-row-status">
              <span class="status-dot">
                <span class="dot {{ $fee->is_active ? 'on' : 'off' }}"></span>
                <span class="{{ $fee->is_active ? 'label-on' : 'label-off' }}">
                  {{ $fee->is_active ? 'مفعّل' : 'معطّل' }}
                </span>
              </span>
            </div>

            {{-- Rate display --}}
            <div class="fee-row-rate">
              @if($fee->percentage > 0)
                <span class="r-primary">{{ rtrim(rtrim(number_format($fee->percentage, 2), '0'), '.') }}%</span>
                @if($fee->fixed_amount > 0)
                  <span class="r-secondary">+ {{ number_format($fee->fixed_amount, 2) }}</span>
                @endif
              @else
                <span class="r-primary">{{ number_format($fee->fixed_amount, 2) }}</span>
                <span class="r-secondary">ثابت</span>
              @endif
            </div>

            {{-- Inline edit form --}}
            @php $feeType = $fee->percentage > 0 ? 'percentage' : 'fixed'; @endphp
            <div class="fee-row-edit" x-data="{ feeType: '{{ $feeType }}' }">
              <form action="{{ route('admin.fees.update', $fee->code) }}" method="POST" style="display:contents;">
                @csrf
                @method('PUT')

                <div class="fet">
                  <button type="button" class="fet-btn"
                    @click="feeType = 'fixed'"
                    :class="feeType === 'fixed' ? 'fet-btn--active' : ''">ثابت</button>
                  <button type="button" class="fet-btn"
                    @click="feeType = 'percentage'"
                    :class="feeType === 'percentage' ? 'fet-btn--active' : ''">نسبة</button>
                </div>
                <input type="hidden" name="fee_type" :value="feeType">

                <div class="fei" x-show="feeType === 'fixed'" x-cloak>
                  <input type="number" name="fixed_amount" value="{{ $fee->fixed_amount }}"
                    step="0.01" min="0" placeholder="0.00">
                  <span class="fei-suffix">{{ $fee->currency }}</span>
                </div>
                <div class="fei" x-show="feeType === 'percentage'" x-cloak>
                  <input type="number" name="percentage" value="{{ $fee->percentage }}"
                    step="0.01" min="0" max="100" placeholder="0.00">
                  <span class="fei-suffix">%</span>
                </div>

                <input type="hidden" name="is_active" value="{{ $fee->is_active ? '1' : '0' }}">
                <button type="submit" class="btn-save">
                  <x-heroicon name="check" />
                  حفظ
                </button>
              </form>
            </div>

            {{-- Toggle --}}
            <div class="fee-row-toggle">
              <form action="{{ route('admin.fees.toggle', $fee->code) }}" method="POST">
                @csrf
                @method('PATCH')
                <button type="submit" style="background:none;border:none;padding:0;cursor:pointer;display:block;">
                  <span class="tgl {{ $fee->is_active ? 'is-on' : '' }}"></span>
                </button>
              </form>
            </div>
          </div>
        @endforeach
      </div>
    @endif
  @endforeach

  @if($totalCount === 0)
    <div class="fees-section">
      <div class="fees-empty" style="text-align:center;padding:var(--space-xl);color:var(--text-muted);">
        <x-heroicon name="inbox" style="width:36px;height:36px;margin:0 auto;color:var(--border-strong);display:block;" />
        <p style="margin-top:var(--space-md);">لا توجد رسوم مضافة بعد</p>
      </div>
    </div>
  @endif

  {{-- ===== Fee Calculator ===== --}}
  <div class="fees-calc">
    <div class="fees-calc-header">
      <div class="calc-icon">
        <x-heroicon name="calculate" />
      </div>
      <div>
        <h2 class="calc-title">حاسبة الرسوم</h2>
        <p class="calc-desc">احسب الرسوم لأي مبلغ ونوع عملية</p>
      </div>
    </div>
    <div class="fees-calc-grid">
      <div class="calc-field">
        <label class="cf-label">نوع الرسم</label>
        <select id="calc-fee-code">
          <option value="">اختر...</option>
          @foreach($feeTypes as $type => $typeLabel)
            @php
              $secLabel = '';
              foreach ($sections as $s) {
                if (in_array($type, $s['types'])) { $secLabel = $s['label']; break; }
              }
            @endphp
            @if($fees->has($type))
              <optgroup label="{{ $secLabel ? "$secLabel — $typeLabel" : $typeLabel }}">
                @foreach($fees[$type] as $fee)
                  <option value="{{ $fee->code }}" data-currency="{{ $fee->currency }}">
                    {{ $fee->name_ar }}
                  </option>
                @endforeach
              </optgroup>
            @endif
          @endforeach
        </select>
      </div>
      <div class="calc-field">
        <label class="cf-label">المبلغ</label>
        <input type="number" id="calc-amount" step="0.01" min="0" placeholder="0.00">
      </div>
      <div class="calc-field">
        <label class="cf-label">النتيجة</label>
        <div id="calc-result" class="calc-result">
          <span class="cr-placeholder">اختر رسم وأدخل مبلغ</span>
        </div>
      </div>
    </div>
  </div>

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const feeSelect = document.getElementById('calc-fee-code');
  const amountInput = document.getElementById('calc-amount');
  const resultDiv = document.getElementById('calc-result');

  function escHtml(s) {
    return String(s || '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
  }

  function calculateFee() {
    const code = feeSelect.value;
    const amount = parseFloat(amountInput.value);
    if (!code || !amount || amount <= 0) {
      resultDiv.innerHTML = '<span class="cr-placeholder">اختر رسم وأدخل مبلغ</span>';
      return;
    }
    resultDiv.innerHTML = '<span style="color:var(--text-muted);">جاري الحساب...</span>';
    fetch('{{ route("admin.fees.preview") }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: JSON.stringify({ code, amount })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        const currency = feeSelect.selectedOptions[0]?.dataset?.currency || 'USD';
        const f = escHtml(formatNumber(data.fee));
        const n = escHtml(formatNumber(data.net_amount));
        const c = escHtml(currency);
        resultDiv.innerHTML = `<div class="calc-result-detail">
          <div><span class="cr-label">الرسوم:</span><span class="cr-value" style="color:var(--sukk-primary);">${f} ${c}</span></div>
          <div class="cr-divider"></div>
          <div><span class="cr-label">الصافي:</span><span class="cr-value">${n} ${c}</span></div>
        </div>`;
      } else {
        resultDiv.innerHTML = `<span style="color:var(--danger);">${escHtml(data.message || data.error || 'خطأ')}</span>`;
      }
    })
    .catch(() => {
      resultDiv.innerHTML = '<span style="color:var(--danger);">حدث خطأ</span>';
    });
  }
  function formatNumber(n) {
    return new Intl.NumberFormat('ar-SY', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
  }
  feeSelect?.addEventListener('change', calculateFee);
  amountInput?.addEventListener('input', calculateFee);
});
</script>
@endpush
@endsection
