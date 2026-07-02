@php
  $merchants = $merchants ?? collect();
  $sortField = $sortField ?? 'created_at';
  $sortDir = $sortDir ?? 'desc';
@endphp

<div class="mr-table-wrap">
  <table class="mr-tbl">
    <thead>
      <tr>
        <th>#</th>
        <th class="@if(request('sort')==='store_name') mr-tbl-sorted @endif"
            @click="sortBy('store_name')" role="columnheader">
          <span>المتجر</span>
          <span class="mr-tbl-sort" x-text="sortIcon('store_name')"></span>
        </th>
        <th>النوع</th>
        <th>KYC</th>
        <th>الحساب</th>
        <th class="@if(request('sort')==='balance') mr-tbl-sorted @endif text-end"
            @click="sortBy('balance')" role="columnheader">
          <span>الرصيد</span>
          <span class="mr-tbl-sort" x-text="sortIcon('balance')"></span>
        </th>
        <th class="@if(request('sort')==='is_active') mr-tbl-sorted @endif"
            @click="sortBy('is_active')" role="columnheader">
          <span>الحالة</span>
          <span class="mr-tbl-sort" x-text="sortIcon('is_active')"></span>
        </th>
        <th style="width:80px;">إجراءات</th>
      </tr>
    </thead>
    <tbody>
      @forelse($merchants as $merchant)
      @php
        $initials = mb_strtoupper(mb_substr($merchant->store_name,0,1));
        $status = $merchant->is_active ? 'active' : 'inactive';
        $statusLabel = $status === 'active' ? 'نشط' : 'معطّل';
        $typeLabel = match($merchant->type) { 'physical'=>'فعلي', 'ecommerce'=>'إلكتروني', 'both'=>'فعلي+إلكتروني', default=>'—' };
        $kycLabel = match($merchant->kyc_status) { 'verified'=>'موثّق', 'pending'=>'معلّق', 'rejected'=>'مرفوض', default=>'—' };
        $kycBadge = match($merchant->kyc_status) { 'verified'=>'success', 'pending'=>'warn', 'rejected'=>'danger', default=>'muted' };
      @endphp
      <tr class="mr-row">
        <td style="font-size:0.7rem;color:var(--text-muted);font-weight:600;">{{ $merchant->merchant_code }}</td>
        <td>
          <div class="mr-user-wrap">
            <div class="mr-avatar">{{ $initials }}</div>
            <div>
              <a href="{{ route('admin.merchants.show', $merchant->id) }}" class="mr-name">{{ $merchant->store_name }}</a>
              <span class="mr-sub">{{ $merchant->owner_name ?? '' }} · {{ $merchant->phone }}</span>
            </div>
          </div>
        </td>
        <td><span class="mr-type">{{ $typeLabel }}</span></td>
        <td><span class="mr-badge mr-badge--{{ $kycBadge }}">{{ $kycLabel }}</span></td>
        <td style="font-size:0.68rem;font-weight:600;color:var(--text-secondary);white-space:nowrap;max-width:140px;overflow:hidden;text-overflow:ellipsis;" title="{{ $merchant->bankAccount?->bank_name }} · {{ $merchant->bankAccount?->account_number_last4 }}">
          @if($merchant->bankAccount)
            {{ $merchant->bankAccount->bank_name }} · ****{{ $merchant->bankAccount->account_number_last4 }}
          @else
            <span style="color:var(--text-muted);">—</span>
          @endif
        </td>
        <td class="text-end" style="font-size:0.78rem;font-weight:700;color:var(--text-primary);direction:ltr;">&lrm;${{ number_format($merchant->balance,2) }}</td>
        <td>
          <span class="mr-status">
            <span class="mr-dot mr-dot--{{ $status }}"></span>
            {{ $statusLabel }}
          </span>
        </td>
        <td>
          <div class="mr-actions">
            <a href="{{ route('admin.merchants.show', $merchant->id) }}" class="mr-act" title="عرض">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </a>
            <a href="{{ route('admin.merchants.edit', $merchant->id) }}" class="mr-act" title="تعديل">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </a>
          </div>
        </td>
      </tr>
      @empty
      <tr><td colspan="8" class="mr-empty">
        <div class="mr-empty-inner">
          <div class="mr-empty-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
          </div>
          <div class="mr-empty-title">لا يوجد تجار</div>
          <div class="mr-empty-desc">لم يتم إضافة أي تاجر بعد</div>
        </div>
      </td></tr>
      @endforelse
    </tbody>
  </table>
</div>

@if($merchants->hasPages())
<div class="mr-pager">
  <span class="mr-pager-info">عرض {{ $merchants->firstItem() }}–{{ $merchants->lastItem() }} من {{ number_format($merchants->total()) }}</span>
  <div class="mr-pager-nav">
    @if($merchants->onFirstPage()) <span>‹</span>
    @else <a href="{{ $merchants->previousPageUrl() }}" rel="prev">‹</a> @endif
    @foreach($merchants->getUrlRange(max(1,$merchants->currentPage()-2), min($merchants->lastPage(),$merchants->currentPage()+2)) as $page => $url)
      @if($page == $merchants->currentPage()) <span aria-current="page">{{ $page }}</span>
      @else <a href="{{ $url }}">{{ $page }}</a> @endif
    @endforeach
    @if($merchants->hasMorePages()) <a href="{{ $merchants->nextPageUrl() }}" rel="next">›</a>
    @else <span>›</span> @endif
  </div>
</div>
@endif
