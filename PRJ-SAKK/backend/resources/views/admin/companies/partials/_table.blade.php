{{--
  admin.companies.partials._table — AJAX results fragment
  Receives: $companies (LengthAwarePaginator), $sortField, $sortDir
--}}
<div class="cp-table-wrap">
<table class="cp-tbl">
  <thead>
    <tr>
      <th>الشركة</th>
      <th>KYC</th>
      <th>الحساب</th>
      <th>الموظفون</th>
      <th>الرواتب</th>
      <th class="@if(request('sort')==='is_active') cp-tbl-sorted @endif" @click="sortBy('is_active')">
        <span>الحالة</span>
        <span class="cp-tbl-sort" x-text="sortIcon('is_active')"></span>
      </th>
      <th style="width:80px;">إجراءات</th>
    </tr>
  </thead>
  <tbody>
    @forelse($companies as $company)
    @php
      $initials = mb_strtoupper(mb_substr($company->name,0,1));
      $status = $company->is_active ? 'active' : 'inactive';
      $statusLabel = $status === 'active' ? 'نشطة' : 'معطّلة';
      $kycLabel = match($company->kyc_status) { 'verified'=>'موثّقة', 'pending'=>'معلّقة', default=>'—' };
      $kycBadge = match($company->kyc_status) { 'verified'=>'success', 'pending'=>'warn', default=>'muted' };
    @endphp
    <tr class="cp-row">
      <td>
        <div class="cp-user-wrap">
          <div class="cp-avatar">{{ $initials }}</div>
          <div>
            <a href="{{ route('admin.companies.show', $company->id) }}" class="cp-name">{{ $company->name }}</a>
            <span class="cp-sub">{{ $company->phone ?? '' }}</span>
          </div>
        </div>
      </td>
      <td><span class="cp-badge cp-badge--{{ $kycBadge }}">{{ $kycLabel }}</span></td>
      <td style="font-size:0.68rem;font-weight:600;color:var(--text-secondary);white-space:nowrap;max-width:140px;overflow:hidden;text-overflow:ellipsis;" title="{{ $company->bankAccount?->bank_name }} · {{ $company->bankAccount?->account_number_last4 }}">
        @if($company->bankAccount)
          {{ $company->bankAccount->bank_name }} · ****{{ $company->bankAccount->account_number_last4 }}
        @else
          <span style="color:var(--text-muted);">—</span>
        @endif
      </td>
      <td style="font-size:0.72rem;font-weight:600;color:var(--text-secondary);">{{ $company->employees_count ?? 0 }}</td>
      <td>
        @if($company->payroll_enabled)
        <span class="cp-badge cp-badge--success">مفعّلة</span>
        @else
        <span class="cp-badge cp-badge--muted">معطّلة</span>
        @endif
      </td>
      <td>
        <span class="cp-status"><span class="cp-dot cp-dot--{{ $status }}"></span>{{ $statusLabel }}</span>
      </td>
      <td>
        <div class="cp-actions">
          <a href="{{ route('admin.companies.show', $company->id) }}" class="cp-act" title="عرض">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </a>
          <a href="{{ route('admin.companies.edit', $company->id) }}" class="cp-act" title="تعديل">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          </a>
        </div>
      </td>
    </tr>
    @empty
    <tr><td colspan="7" class="cp-empty">
      <div class="cp-empty-inner">
        <div class="cp-empty-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg></div>
        <div class="cp-empty-title">لا يوجد شركات</div>
        <div class="cp-empty-desc">لم يتم إضافة أي شركة بعد</div>
      </div>
    </td></tr>
    @endforelse
  </tbody>
</table>
</div>

@if($companies->hasPages())
<div class="cp-pager">
  <span class="cp-pager-info">عرض {{ $companies->firstItem() }}–{{ $companies->lastItem() }} من {{ number_format($companies->total()) }}</span>
  <div class="cp-pager-nav">
    @if($companies->onFirstPage()) <span>‹</span>
    @else <a href="{{ $companies->previousPageUrl() }}" rel="prev">‹</a> @endif
    @foreach($companies->getUrlRange(max(1,$companies->currentPage()-2), min($companies->lastPage(),$companies->currentPage()+2)) as $page => $url)
      @if($page == $companies->currentPage()) <span aria-current="page">{{ $page }}</span>
      @else <a href="{{ $url }}">{{ $page }}</a> @endif
    @endforeach
    @if($companies->hasMorePages()) <a href="{{ $companies->nextPageUrl() }}" rel="next">›</a>
    @else <span>›</span> @endif
  </div>
</div>
@endif
