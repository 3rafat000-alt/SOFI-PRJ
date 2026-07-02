{{--
  admin.agents.partials._table — AJAX results fragment
  Receives: $agents (LengthAwarePaginator), $sortField, $sortDir
--}}
<div class="ag-table-wrap">
<table class="ag-tbl">
  <thead>
    <tr>
      <th class="ag-tbl-user @if(request('sort')==='name') ag-tbl-sorted @endif"
          @click="sortBy('name')" role="columnheader">
        <span>الوكيل</span>
        <span class="ag-tbl-sort" x-text="sortIcon('name')"></span>
      </th>
      <th class="ag-tbl-owner">المالك</th>
      <th>KYC</th>
      <th>الحساب</th>
      <th>الخدمات</th>
      <th class="ag-tbl-owner">التقييم</th>
      <th class="ag-tbl-status @if(request('sort')==='is_active') ag-tbl-sorted @endif"
          @click="sortBy('is_active')" role="columnheader">
        <span>الحالة</span>
        <span class="ag-tbl-sort" x-text="sortIcon('is_active')"></span>
      </th>
      <th class="text-center" style="width:80px;">إجراءات</th>
    </tr>
  </thead>
  <tbody>
    @forelse($agents as $agent)
    @php
      $initials = mb_strtoupper(mb_substr($agent->name,0,1));
      $status = $agent->is_active ? 'active' : 'inactive';
      if ($agent->is_featured) $status = 'featured';
      $statusLabel = match($status) { 'active'=>'نشط', 'inactive'=>'معطل', 'featured'=>'مميز', default=>'—' };
      $kycLabel = match($agent->kyc_status) { 'verified'=>'موثّق', 'pending'=>'معلّق', 'rejected'=>'مرفوض', default=>'—' };
      $kycBadge = match($agent->kyc_status) { 'verified'=>'success', 'pending'=>'warn', 'rejected'=>'danger', default=>'muted' };
      $services = is_array($agent->services) ? $agent->services : (json_decode($agent->services, true) ?? []);
    @endphp
    <tr class="ag-row">
      <td class="ag-user">
        <div class="ag-user-wrap">
          <div class="ag-avatar">{{ $initials }}</div>
          <div>
            <a href="{{ route('admin.agents.show', $agent->id) }}" class="ag-name">{{ $agent->name }}</a>
            <span class="ag-sub">{{ $agent->agent_code }} · {{ $agent->city }}</span>
          </div>
        </div>
      </td>
      <td class="ag-owner-cell"><span style="font-size:0.72rem;color:var(--text-secondary);">{{ $agent->owner_name ?? '—' }}</span></td>
      <td><span class="ag-badge ag-badge--{{ $kycBadge }}">{{ $kycLabel }}</span></td>
      <td style="font-size:0.68rem;font-weight:600;color:var(--text-secondary);white-space:nowrap;max-width:140px;overflow:hidden;text-overflow:ellipsis;" title="{{ $agent->bankAccount?->bank_name }} · {{ $agent->bankAccount?->account_number_last4 }}">
        @if($agent->bankAccount)
          {{ $agent->bankAccount->bank_name }} · ****{{ $agent->bankAccount->account_number_last4 }}
        @else
          <span style="color:var(--text-muted);">—</span>
        @endif
      </td>
      <td>
        <div style="display:flex;gap:3px;flex-wrap:wrap;">
          @if(in_array('cash_in', $services))<span class="ag-badge ag-badge--muted">إيداع</span>@endif
          @if(in_array('cash_out', $services))<span class="ag-badge ag-badge--muted">سحب</span>@endif
          @if(empty($services))<span class="ag-service">—</span>@endif
        </div>
      </td>
      <td class="ag-owner-cell"><span class="ag-rating">★ {{ number_format($agent->rating,1) }}</span></td>
      <td>
        <span class="ag-status">
          <span class="ag-dot ag-dot--{{ $status }}"></span>
          {{ $statusLabel }}
        </span>
      </td>
      <td>
        <div class="ag-actions">
          <a href="{{ route('admin.agents.show', $agent->id) }}" class="ag-act" title="عرض">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </a>
          <a href="{{ route('admin.agents.edit', $agent->id) }}" class="ag-act" title="تعديل">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          </a>
        </div>
      </td>
    </tr>
    @empty
    <tr><td colspan="8" class="ag-empty">
      <div class="ag-empty-inner">
        <div class="ag-empty-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
        </div>
        <div class="ag-empty-title">لا يوجد وكلاء</div>
        <div class="ag-empty-desc">لم يتم إضافة أي وكيل بعد</div>
      </div>
    </td></tr>
    @endforelse
  </tbody>
</table>
</div>

@if($agents->hasPages())
<div class="ag-pager">
  <span class="ag-pager-info">عرض {{ $agents->firstItem() }}–{{ $agents->lastItem() }} من {{ number_format($agents->total()) }}</span>
  <div class="ag-pager-nav">
    @if($agents->onFirstPage())
    <span>‹</span>
    @else
    <a href="{{ $agents->previousPageUrl() }}" class="ag-page-link" rel="prev">‹</a>
    @endif
    @foreach($agents->getUrlRange(max(1,$agents->currentPage()-2), min($agents->lastPage(),$agents->currentPage()+2)) as $page => $url)
      @if($page == $agents->currentPage())
      <span aria-current="page">{{ $page }}</span>
      @else
      <a href="{{ $url }}" class="ag-page-link">{{ $page }}</a>
      @endif
    @endforeach
    @if($agents->hasMorePages())
    <a href="{{ $agents->nextPageUrl() }}" class="ag-page-link" rel="next">›</a>
    @else
    <span>›</span>
    @endif
  </div>
</div>
@endif
