@extends('layouts.admin')

@section('title', 'الذهب')

@section('breadcrumbs')
<span class="breadcrumb-item">الذهب</span>
@endsection

@push('styles')
    @include('admin.gold._styles')
    <style>
        /* ── Add-karat panel ── */
        .add-karat-panel {
            background: var(--surface);
            border: 1px dashed var(--border-strong);
            border-radius: var(--radius-xl);
            padding: 1.1rem 1.25rem;
            margin-top: 0.9rem;
        }
        .add-karat-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 0.85rem; }
        .add-karat-field { display: flex; flex-direction: column; gap: 0.3rem; }
        .add-karat-field label { font-size: 0.72rem; font-weight: 700; color: var(--text-muted); }
        .add-karat-field input {
            width: 120px;
            height: 38px;
            padding: 0 0.7rem;
            font-size: var(--font-size-sm);
            font-weight: 700;
            font-family: inherit;
            color: var(--text-primary);
            background: var(--input-bg, var(--surface-hover));
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            outline: none;
            transition: box-shadow var(--transition-fast), border-color var(--transition-fast);
        }
        .add-karat-field input:focus-visible {
            box-shadow: 0 0 0 3px var(--primary-ring);
            border-color: var(--primary);
        }
        .add-karat-toggle-btn {
            display: inline-flex; align-items: center; gap: 0.4rem;
        }

        /* ── Transactions link footer ── */
        .gold-tx-footer {
            display: flex;
            justify-content: center;
            padding: 0.9rem;
            border-top: 1px solid var(--border);
        }

        /* ── Keyboard-focus ring on native toggle button ── */
        .gold-switch-btn {
            background: none; border: none; padding: 0; margin: 0; cursor: pointer;
            border-radius: var(--radius-full);
        }
        .gold-switch-btn:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px var(--primary-ring);
        }
    </style>
@endpush

@section('content')
<div class="gold-page space-y-6" x-data="{ addOpen: false }">

  {{-- ===== Hero ===== --}}
  <div class="gold-hero">
    <div class="gold-hero-main">
      <div class="gold-hero-icon">
        <x-heroicon name="monetization_on" />
      </div>
      <div>
        <h1 class="gold-hero-title">الذهب</h1>
        <p class="gold-hero-sub">أسعار شراء وبيع الذهب لكل عيار — آخر المعاملات</p>
      </div>
    </div>
    <div class="gold-hero-side">
      <span class="gold-stamp">
        <x-heroicon name="check_circle" />
        {{ $stats['active_prices'] }} / {{ $stats['total_prices'] }} عيار نشط
      </span>
      <button type="button" class="btn btn-primary add-karat-toggle-btn" @click="addOpen = !addOpen" :aria-expanded="addOpen.toString()" aria-controls="add-karat-panel">
        <x-heroicon name="add" style="width:14px;height:14px;" />
        إضافة عيار
      </button>
    </div>
  </div>

  {{-- ===== Add-karat panel (collapsed by default) ===== --}}
  <div id="add-karat-panel" class="add-karat-panel" x-show="addOpen" x-cloak x-transition>
    <form method="POST" action="{{ route('admin.gold.price.store') }}" class="add-karat-form">
      @csrf
      <div class="add-karat-field">
        <label for="new-karat">العيار</label>
        <input type="number" id="new-karat" name="karat" min="1" max="24" step="1" placeholder="مثال: 14" required>
      </div>
      <div class="add-karat-field">
        <label for="new-buy">سعر الشراء</label>
        <input type="number" id="new-buy" name="buy_price" min="0.01" step="0.01" placeholder="0.00" required>
      </div>
      <div class="add-karat-field">
        <label for="new-sell">سعر البيع</label>
        <input type="number" id="new-sell" name="sell_price" min="0.01" step="0.01" placeholder="0.00" required>
      </div>
      <button type="submit" class="btn btn-primary">
        <x-heroicon name="check" style="width:14px;height:14px;" />
        حفظ العيار
      </button>
      <button type="button" class="btn btn-ghost" @click="addOpen = false">
        <x-heroicon name="close" style="width:14px;height:14px;" />
        إلغاء
      </button>
    </form>
    @error('karat')
      <p style="color: var(--danger); font-size: var(--font-size-xs); font-weight: 600; margin-top: 0.6rem;">{{ $message }}</p>
    @enderror
    @error('buy_price')
      <p style="color: var(--danger); font-size: var(--font-size-xs); font-weight: 600; margin-top: 0.6rem;">{{ $message }}</p>
    @enderror
  </div>

  {{-- ===== KPI ===== --}}
  <div class="gold-kpis">
    <div class="gold-kpi">
      <div>
        <p class="gold-kpi-label">أسعار نشطة</p>
        <p class="gold-kpi-value">{{ $stats['active_prices'] }} <small style="font-size:0.65rem;font-weight:600;color:var(--text-muted);">/ {{ $stats['total_prices'] }}</small></p>
        <p class="gold-kpi-sub">عيارات مفعّلة</p>
      </div>
      <div class="gold-kpi-icon" style="background: var(--primary-light, rgba(0,0,0,0.04)); color: var(--primary);">
        <x-heroicon name="check_circle" />
      </div>
    </div>
    <div class="gold-kpi">
      <div>
        <p class="gold-kpi-label">متوسط الفارق</p>
        <p class="gold-kpi-value" dir="ltr">{{ number_format($stats['avg_spread'], 2) }}%</p>
        <p class="gold-kpi-sub">الفرق بين الشراء والبيع</p>
      </div>
      <div class="gold-kpi-icon" style="background: var(--surface-hover); color: var(--text-secondary);">
        <x-heroicon name="swap_vert" />
      </div>
    </div>
    <div class="gold-kpi">
      <div>
        <p class="gold-kpi-label">المعاملات</p>
        <p class="gold-kpi-value">{{ $txStats['total'] }}</p>
        <p class="gold-kpi-sub">{{ $txStats['buy'] }} شراء · {{ $txStats['sell'] }} بيع</p>
      </div>
      <div class="gold-kpi-icon" style="background: var(--info-light); color: var(--info);">
        <x-heroicon name="receipt" />
      </div>
    </div>
    <div class="gold-kpi">
      <div>
        <p class="gold-kpi-label">حجم التداول</p>
        <p class="gold-kpi-value" dir="ltr">&lrm;${{ number_format($txStats['volume'], 0) }}</p>
        <p class="gold-kpi-sub" dir="ltr">&lrm;رسوم: ${{ number_format($txStats['fees'], 0) }}</p>
      </div>
      <div class="gold-kpi-icon" style="background: var(--success-light); color: var(--success);">
        <x-heroicon name="payments" />
      </div>
    </div>
  </div>

  {{-- ===== Auto-sync card ===== --}}
  <div class="gold-hero" style="padding: 1.1rem 1.25rem;">
    <div class="gold-hero-main">
      <div class="gold-hero-icon" style="width:40px;height:40px; background: var(--gold-soft); color: var(--gold-deep); box-shadow: inset 0 0 0 1px var(--gold-line);">
        <x-heroicon name="sync" />
      </div>
      <div>
        <div class="gold-kpi-label" style="font-size: var(--font-size-sm); color: var(--text-primary); font-weight: 700;">التحديث التلقائي من السوق العالمي</div>
        <p class="gold-hero-sub" style="margin-top: 1px;">
          @if($stats['last_auto_update'])
            @php $lastAuto = \Carbon\Carbon::parse($stats['last_auto_update']); @endphp
            آخر تحديث: {{ $lastAuto->diffForHumans() }}
            @if($stats['last_spot_24k'] > 0)
              · السعر الفوري 24k: <span dir="ltr" style="font-weight: 700; color: var(--gold-deep);">&lrm;${{ number_format($stats['last_spot_24k'], 2) }}</span>
            @endif
          @else
            لم يتم التحديث بعد
          @endif
        </p>
      </div>
    </div>

    <div class="gold-hero-side">
      <form method="POST" action="{{ route('admin.gold.price.auto') }}" style="display:flex;align-items:center;gap:0.9rem;flex-wrap:wrap;"
        onsubmit="var b=this.querySelector('button[type=submit]'); b.disabled=true; b.style.opacity='0.6'; b.style.cursor='wait'; b.innerHTML='<span style=\'display:inline-block;width:14px;height:14px;border:2px solid currentColor;border-inline-end-color:transparent;border-radius:50%;animation:spin .6s linear infinite;\'></span> جارٍ الحفظ...';">
        @csrf
        <label class="gold-switch">
          <input type="hidden" name="auto_update" value="0">
          <input type="checkbox" name="auto_update" value="1" role="switch" aria-checked="{{ $stats['auto_enabled'] ? 'true' : 'false' }}" aria-label="تفعيل التحديث التلقائي"
            {{ $stats['auto_enabled'] ? 'checked' : '' }}
            onchange="this.closest('form').querySelector('[name=auto_update][value=0]').disabled = this.checked; this.setAttribute('aria-checked', this.checked);">
          <span class="track"></span>
          <span style="font-size:var(--font-size-xs);font-weight:600;color:var(--text-secondary);">تلقائي</span>
        </label>
        <div style="display:flex;align-items:center;gap:0.4rem;">
          <label for="auto-margin" style="font-size:var(--font-size-xs);font-weight:600;color:var(--text-muted);">هامش المنصة:</label>
          <input type="number" id="auto-margin" name="margin" value="{{ number_format($stats['auto_margin'], 2) }}"
            step="0.1" min="0" max="10" class="add-karat-field input" style="width:80px;height:36px;text-align:center;">
          <span style="font-size:var(--font-size-xs);font-weight:700;color:var(--text-muted);">%</span>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">
          <x-heroicon name="check" style="width:14px;height:14px;" />
          حفظ
        </button>
      </form>
      <form method="POST" action="{{ route('admin.gold.price.refresh') }}"
        onsubmit="var b=this.querySelector('button[type=submit]'); b.disabled=true; b.style.opacity='0.6'; b.style.cursor='wait'; b.innerHTML='<span style=\'display:inline-block;width:14px;height:14px;border:2px solid currentColor;border-inline-end-color:transparent;border-radius:50%;animation:spin .6s linear infinite;\'></span> جارٍ التحديث...';">
        @csrf
        <button type="submit" class="btn btn-secondary btn-sm">
          <x-heroicon name="refresh" style="width:14px;height:14px;" />
          تحديث الآن
        </button>
      </form>
    </div>
  </div>

  {{-- ===== Prices Grid ===== --}}
  <div class="gold-section-head">
    <x-heroicon name="price_change" />
    <h2>أسعار العيارات</h2>
  </div>

  <div class="karat-grid">
    @forelse($prices as $price)
      <div class="karat-card {{ !$price->is_active ? 'is-off' : '' }}">
        {{-- Header --}}
        <div class="karat-head">
          <div class="karat-id">
            <div class="karat-medallion">{{ $price->karat }}k</div>
            <div>
              <div class="karat-name">{{ $price->karat_label }}</div>
              <div class="karat-purity-label">{{ number_format($price->purity, 1) }}% نقاء</div>
            </div>
          </div>
          <form action="{{ route('admin.gold.price.toggle', $price) }}" method="POST">
            @csrf
            <button type="submit" class="gold-switch-btn"
              role="switch" aria-checked="{{ $price->is_active ? 'true' : 'false' }}"
              aria-label="{{ $price->is_active ? 'تعطيل' : 'تفعيل' }} {{ $price->karat_label }}"
              onclick="return {{ $price->is_active ? "confirm('تعطيل هذا العيار سيخفيه عن التطبيق. متابعة؟')" : 'true' }}">
              <span class="gold-switch">
                <span class="track" style="{{ $price->is_active ? 'background: var(--success);' : '' }}"></span>
              </span>
            </button>
          </form>
        </div>

        {{-- Prices --}}
        <div class="karat-prices">
          <div class="karat-price buy">
            <div class="karat-price-label">
              <x-heroicon name="south_west" />
              شراء
            </div>
            <div class="karat-price-value" dir="ltr">&lrm;${{ number_format($price->buy_price, 2) }}</div>
          </div>
          <div class="karat-price sell">
            <div class="karat-price-label">
              <x-heroicon name="north_east" />
              بيع
            </div>
            <div class="karat-price-value" dir="ltr">&lrm;${{ number_format($price->sell_price, 2) }}</div>
          </div>
        </div>

        {{-- Meta --}}
        <div class="karat-meta">
          <span class="spread-chip">
            <x-heroicon name="swap_vert" />
            الفارق: {{ number_format($price->spread, 2) }}%
          </span>
          <span class="source-pill {{ $price->source === 'auto' ? 'auto' : 'manual' }}">{{ $price->source === 'auto' ? 'تلقائي' : 'يدوي' }}</span>
        </div>

        {{-- Inline edit --}}
        <div class="karat-edit">
          <form action="{{ route('admin.gold.price.update', $price) }}" method="POST" style="display:flex;gap:0.5rem;align-items:flex-end;flex-wrap:wrap;">
            @csrf
            @method('PUT')
            <div class="add-karat-field" style="gap:0.2rem;">
              <label for="buy-{{ $price->id }}" style="font-size:0.65rem;">شراء</label>
              <input type="number" id="buy-{{ $price->id }}" name="buy_price" value="{{ $price->buy_price }}" step="0.01" min="0" style="width:90px;height:34px;">
            </div>
            <div class="add-karat-field" style="gap:0.2rem;">
              <label for="sell-{{ $price->id }}" style="font-size:0.65rem;">بيع</label>
              <input type="number" id="sell-{{ $price->id }}" name="sell_price" value="{{ $price->sell_price }}" step="0.01" min="0" style="width:90px;height:34px;">
            </div>
            <input type="hidden" name="is_active" value="{{ $price->is_active ? '1' : '0' }}">
            <button type="submit" class="btn btn-primary btn-sm" style="height:34px;" aria-label="حفظ سعر {{ $price->karat_label }}">
              <x-heroicon name="check" style="width:14px;height:14px;" />
            </button>
          </form>
        </div>
      </div>
    @empty
      <div style="grid-column:1/-1;">
        <div class="table-empty">
          <x-heroicon name="inbox" class="table-empty-icon" />
          <p>لا توجد أسعار ذهب — شغّل «تحديث الآن» أو أضف عياراً</p>
          <button type="button" class="btn btn-primary" style="margin-top: 0.75rem;" @click="addOpen = true">
            <x-heroicon name="add" style="width:14px;height:14px;" />
            إضافة عيار
          </button>
        </div>
      </div>
    @endforelse
  </div>

  {{-- ===== Recent Transactions ===== --}}
  <div class="card">
    <div class="card-body p-0">
      <div class="gold-section-head" style="padding: 1.1rem 1.25rem 0;">
        <x-heroicon name="receipt" />
        <h2>آخر المعاملات</h2>
        <span class="count">{{ $txStats['total'] }} معاملة</span>
      </div>

      @if($recentTransactions->isNotEmpty())
        <div class="table-container" style="border: none; margin-top: 0.75rem;">
          <table class="table">
            <thead>
              <tr>
                <th>المرجع</th>
                <th>النوع</th>
                <th>المستخدم</th>
                <th>العيار</th>
                <th>الجرامات</th>
                <th>السعر/غرام</th>
                <th>الإجمالي</th>
                <th>الرسوم</th>
                <th>الحالة</th>
                <th>التاريخ</th>
              </tr>
            </thead>
            <tbody>
              @foreach($recentTransactions as $tx)
                <tr>
                  <td><span class="font-mono font-bold text-gray-900 text-xs" dir="ltr">{{ $tx->reference }}</span></td>
                  <td>
                    <span class="tx-type {{ $tx->type === 'buy' ? 'buy' : 'sell' }}">
                      @if($tx->type === 'buy')
                        <x-heroicon name="south_west" /> شراء
                      @else
                        <x-heroicon name="north_east" /> بيع
                      @endif
                    </span>
                  </td>
                  <td>
                    <div class="flex items-center gap-2">
                      <div class="tx-avatar">{{ mb_strtoupper(mb_substr($tx->user?->first_name ?? '?', 0, 1)) }}</div>
                      <span class="text-sm font-bold text-gray-900">{{ trim(($tx->user?->first_name ?? '#'.$tx->user_id) . ' ' . ($tx->user?->last_name ?? '')) }}</span>
                    </div>
                  </td>
                  <td><span class="karat-tag">{{ $tx->karat }}</span></td>
                  <td dir="ltr" style="font-weight:600;">{{ number_format($tx->grams, 4) }}</td>
                  <td dir="ltr">&lrm;${{ number_format($tx->price_per_gram_usd, 2) }}</td>
                  <td dir="ltr" style="font-weight:700;">&lrm;${{ number_format($tx->total_usd, 2) }}</td>
                  <td dir="ltr">&lrm;${{ number_format($tx->fee_usd, 2) }}</td>
                  <td>
                    @php
                      $statusAr = ['completed' => 'مكتمل', 'pending' => 'معلق', 'failed' => 'فاشل', 'cancelled' => 'ملغي'];
                      $statusBadge = ['completed' => 'badge-success', 'pending' => 'badge-warning', 'failed' => 'badge-danger', 'cancelled' => 'badge-danger'];
                    @endphp
                    <span class="badge {{ $statusBadge[$tx->status] ?? 'badge-warning' }}">
                      {{ $statusAr[$tx->status] ?? $tx->status }}
                    </span>
                  </td>
                  <td>
                    <span class="text-xs text-gray-700 block" dir="ltr">{{ $tx->created_at?->format('Y/m/d H:i') }}</span>
                    <span class="text-[11px] text-gray-400">{{ $tx->created_at?->diffForHumans() }}</span>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <div class="table-empty">
          <x-heroicon name="inbox" class="table-empty-icon" />
          <p>لا توجد معاملات ذهب بعد</p>
        </div>
      @endif

      <div class="gold-tx-footer">
        <a href="{{ route('admin.gold.transactions') }}" class="btn btn-secondary">
          <x-heroicon name="receipt_long" style="width:14px;height:14px;" />
          عرض كل المعاملات
        </a>
      </div>
    </div>
  </div>

</div>
@endsection
