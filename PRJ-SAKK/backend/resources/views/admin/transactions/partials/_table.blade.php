{{--
  admin.transactions.partials._table — AJAX results fragment
  Identity-card table: matches users table pattern
  Receives: $transactions (LengthAwarePaginator)
  Uses scoped CSS from index.blade.php (tx-* / tt-* classes)
--}}
<div class="tx-table-wrap">
<table class="tx-tbl">
  <thead>
    <tr>
      <th class="tx-tbl-ref @if(request('sort')==='reference') tx-tbl-sorted @endif"
          @click="sortBy('reference')" role="columnheader" aria-sort="{{ request('sort')==='reference' ? (request('dir')==='asc'?'ascending':'descending') : 'none' }}">
        <span>المرجع</span>
        <span class="tx-tbl-sort" x-text="sortIcon('reference')"></span>
      </th>
      <th class="tx-tbl-user">المستخدم</th>
      <th class="tx-tbl-type">النوع</th>
      <th class="tx-tbl-amount @if(request('sort')==='amount') tx-tbl-sorted @endif"
          @click="sortBy('amount')" role="columnheader" aria-sort="{{ request('sort')==='amount' ? (request('dir')==='asc'?'ascending':'descending') : 'none' }}">
        <span>المبلغ</span>
        <span class="tx-tbl-sort" x-text="sortIcon('amount')"></span>
      </th>
      <th class="tx-tbl-fee">الرسوم</th>
      <th class="tx-tbl-status @if(request('sort')==='status') tx-tbl-sorted @endif"
          @click="sortBy('status')" role="columnheader" aria-sort="{{ request('sort')==='status' ? (request('dir')==='asc'?'ascending':'descending') : 'none' }}">
        <span>الحالة</span>
        <span class="tx-tbl-sort" x-text="sortIcon('status')"></span>
      </th>
      <th class="tx-tbl-date @if(request('sort')==='created_at') tx-tbl-sorted @endif"
          @click="sortBy('created_at')" role="columnheader" aria-sort="{{ request('sort')==='created_at' ? (request('dir')==='asc'?'ascending':'descending') : 'none' }}">
        <span>التاريخ</span>
        <span class="tx-tbl-sort" x-text="sortIcon('created_at')"></span>
      </th>
      <th class="tx-tbl-actions">إجراءات</th>
    </tr>
  </thead>
  <tbody>
    @forelse($transactions as $tx)
    @php
      $typeVal   = $tx->type instanceof \App\Enums\TransactionType ? $tx->type : null;
      $statusVal = $tx->status instanceof \App\Enums\TransactionStatus ? $tx->status->value : $tx->status;
      $amount    = (float) $tx->amount;
      $isCredit  = $amount >= 0;
      $sym       = \App\Support\Money::symbol($tx->currency);
      $statusLabel = $tx->status instanceof \App\Enums\TransactionStatus ? $tx->status->labelAr() : $statusVal;
      $userInitial = $tx->user ? mb_strtoupper(mb_substr($tx->user->first_name ?? 'U', 0, 1)) : '؟';
    @endphp
    <tr class="tt-row" data-id="{{ $tx->id }}">
      {{-- Reference --}}
      <td>
        <a href="{{ route('admin.transactions.show', $tx->id) }}" class="tt-ref">{{ $tx->reference ?? 'N/A' }}</a>
      </td>

      {{-- User --}}
      <td>
        @if($tx->user)
        <div class="tt-user-wrap">
          <div class="tt-avatar">{{ $userInitial }}</div>
          <div class="tt-user-info">
            <a href="{{ route('admin.users.show', $tx->user->id) }}" class="tt-name">{{ $tx->user->first_name }} {{ $tx->user->last_name }}</a>
            <span class="tt-email">{{ $tx->user->email }}</span>
          </div>
        </div>
        @else
        <span style="font-size:0.65rem;color:var(--text-muted);">مستخدم محذوف</span>
        @endif
      </td>

      {{-- Type --}}
      <td>
        <span class="tt-type">{{ $typeVal ? $typeVal->labelAr() : str_replace('_', ' ', (string) $tx->type) }}</span>
      </td>

      {{-- Amount --}}
      <td class="tt-amount-cell">
        <div class="tt-amount">
          <span class="tt-amount-val" style="color:{{ $isCredit ? 'var(--success)' : 'var(--danger)' }};">{{ $isCredit ? '+' : '−' }}{!! \App\Support\Money::format(abs($amount), $tx->currency) !!}</span>
          @if($tx->currency)<div class="tt-amount-sub">{{ $tx->currency }}</div>@endif
        </div>
      </td>

      {{-- Fee --}}
      <td class="tt-fee-cell">
        <span class="tt-fee">{!! \App\Support\Money::format((float) ($tx->fee ?? 0), $tx->currency) !!}</span>
      </td>

      {{-- Status --}}
      <td>
        <span class="tt-status">
          <span class="tt-dot @switch($statusVal) @case('completed') tt-dot--completed @break @case('pending') @case('processing') tt-dot--processing @break @case('failed') @case('cancelled') tt-dot--failed @break @default tt-dot--reversed @endswitch"></span>
          {{ $statusLabel }}
        </span>
      </td>

      {{-- Date --}}
      <td>
        <span class="tt-date-cell">
          <span class="tt-date-time">{{ $tx->created_at->format('Y/m/d') }}</span>
          <span class="tt-date-sub">{{ $tx->created_at->format('H:i') }}</span>
        </span>
      </td>

      {{-- Actions --}}
      <td>
        <div class="tt-actions">
          <button type="button"
                  @click="openQuickView({{ $tx->id }}, '{{ route('admin.transactions.show', $tx->id) }}')"
                  class="tt-act tt-act--open" title="عرض سريع" aria-label="عرض سريع للمعاملة {{ $tx->reference }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
          <a href="{{ route('admin.transactions.show', $tx->id) }}"
             class="tt-act" title="التفاصيل" aria-label="تفاصيل المعاملة {{ $tx->reference }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
          </a>
        </div>
      </td>
    </tr>
    @empty
    <tr>
      <td colspan="8" class="tt-empty">
        <div class="tt-empty-inner">
          <div class="tt-empty-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
          </div>
          @if(request()->hasAny(['search','type','category','status','currency','date_from','date_to']))
          <div class="tt-empty-title">لا توجد نتائج</div>
          <div class="tt-empty-desc">جرّب تعديل معايير البحث أو إزالة الفلاتر</div>
          <a href="{{ route('admin.transactions') }}" class="tt-empty-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            تصفير الفلاتر
          </a>
          @else
          <div class="tt-empty-title">لا توجد معاملات بعد</div>
          <div class="tt-empty-desc">ستظهر المعاملات هنا تلقائياً عند حدوثها</div>
          @endif
        </div>
      </td>
    </tr>
    @endforelse
  </tbody>
</table>
</div>

{{-- Pagination --}}
@if($transactions->hasPages())
<div class="tx-pager">
  <span class="tx-pager-info">عرض {{ $transactions->firstItem() }}–{{ $transactions->lastItem() }} من {{ number_format($transactions->total()) }}</span>
  <div class="tx-pager-nav">
    @if($transactions->onFirstPage())
    <span>‹</span>
    @else
    <a href="{{ $transactions->previousPageUrl() }}" rel="prev" aria-label="السابق">‹</a>
    @endif
    @foreach($transactions->getUrlRange(max(1,$transactions->currentPage()-2), min($transactions->lastPage(),$transactions->currentPage()+2)) as $page => $url)
      @if($page == $transactions->currentPage())
      <span aria-current="page">{{ $page }}</span>
      @else
      <a href="{{ $url }}">{{ $page }}</a>
      @endif
    @endforeach
    @if($transactions->hasMorePages())
    <a href="{{ $transactions->nextPageUrl() }}" rel="next" aria-label="التالي">›</a>
    @else
    <span>›</span>
    @endif
  </div>
</div>
@endif
