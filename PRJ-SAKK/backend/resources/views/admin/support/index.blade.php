@extends('layouts.admin')

@section('title', 'الدعم الفني')

@section('breadcrumbs')
<span class="breadcrumb-item">الدعم الفني</span>
@endsection

@php
    $statusLabels = [
        'open' => ['مفتوحة', '#2563eb', '#eff6ff'],
        'in_progress' => ['قيد المعالجة', '#b45309', '#fffbeb'],
        'waiting_customer' => ['بانتظار العميل', '#7c3aed', '#f5f3ff'],
        'resolved' => ['تم الحل', '#15803d', '#f0fdf4'],
        'closed' => ['مغلقة', '#64748b', '#f1f5f9'],
    ];
    $priorityLabels = [
        'urgent' => ['عاجلة', '#dc2626'],
        'high' => ['مرتفعة', '#ea580c'],
        'medium' => ['متوسطة', '#ca8a04'],
        'low' => ['منخفضة', '#64748b'],
    ];
    $categoryLabels = [
        'general' => 'عام', 'transaction' => 'معاملة', 'card' => 'بطاقة',
        'kyc' => 'تحقق', 'technical' => 'تقني', 'billing' => 'فواتير',
    ];
@endphp

@push('styles')
<style>
/* ── Support index — SAKK clean ── */
.supp-hdr {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-md);
  flex-wrap: wrap;
  padding: var(--space-lg);
  background: var(--surface);
  border-radius: var(--radius-main);
}
.supp-hdr-info {
  display: flex;
  align-items: center;
  gap: var(--space-md);
}
.supp-hdr-icon {
  width: 44px; height: 44px;
  border-radius: var(--radius-sm);
  background: var(--sukk-primary-soft);
  color: var(--sukk-primary);
  display: grid; place-items: center;
  flex: none;
}
.supp-hdr-icon svg[data-slot="icon"] { width: 22px; height: 22px; }
.supp-hdr-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1.2;
}
.supp-hdr-sub {
  font-size: var(--font-size-xs);
  color: var(--text-muted);
  margin-top: 2px;
}

/* ── KPI strip ── */
.supp-kpis {
  display: flex;
  gap: var(--space-md);
  flex-wrap: wrap;
}
.supp-kpi {
  flex: 1;
  min-width: 100px;
  background: var(--surface);
  border-radius: var(--radius-main);
  padding: var(--space-md);
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}
.supp-kpi-val {
  font-size: 1.3rem;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
  line-height: 1.1;
}
.supp-kpi-label {
  font-size: var(--font-size-xs);
  color: var(--text-muted);
  font-weight: 600;
}

/* ── Filter bar ── */
.supp-filter {
  background: var(--surface);
  border-radius: var(--radius-main);
  padding: var(--space-md) var(--space-lg);
  display: flex;
  flex-wrap: wrap;
  align-items: end;
  gap: var(--space-md);
}
.supp-filter-field {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}
.supp-filter-field label {
  font-size: var(--font-size-xs);
  font-weight: 600;
  color: var(--text-secondary);
}
.supp-filter-field select,
.supp-filter-field input {
  height: 36px;
  padding: 0 var(--space-md);
  font-size: var(--font-size-sm);
  background: var(--input-bg);
  border: none;
  border-radius: var(--radius-sm);
  outline: none;
  font-family: inherit;
  color: var(--text-primary);
  min-width: 140px;
  transition: box-shadow var(--transition-fast);
}
.supp-filter-field select:focus,
.supp-filter-field input:focus {
  box-shadow: var(--shadow-focus);
  background: var(--surface);
}
.supp-filter-field.search-field {
  flex: 1;
  min-width: 200px;
}
.supp-filter-actions {
  display: flex;
  gap: var(--space-sm);
  flex: none;
}

/* ── Table ── */
.supp-table-wrap {
  background: var(--surface);
  border-radius: var(--radius-main);
  overflow: hidden;
}
.supp-table {
  width: 100%;
  border-collapse: collapse;
}
.supp-table th {
  text-align: start;
  padding: 0.7rem 1rem;
  font-size: var(--font-size-xs);
  font-weight: 700;
  color: var(--text-secondary);
  background: var(--bg);
  border-bottom: 1px solid var(--border-light);
  white-space: nowrap;
}
.supp-table td {
  padding: 0.75rem 1rem;
  font-size: var(--font-size-sm);
  color: var(--text-primary);
  border-bottom: 1px solid var(--border-light);
}
.supp-table tr:last-child td { border-bottom: none; }
.supp-table tr { cursor: pointer; transition: background var(--transition-fast); }
.supp-table tr:hover { background: var(--surface-hover); }
.supp-table .ticket-num {
  font-family: monospace;
  font-weight: 700;
  direction: ltr;
  color: var(--text-primary);
}
.supp-table .ticket-subject {
  max-width: 260px;
}
.supp-table .ticket-subject .subj-text {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: var(--text-primary);
  font-weight: 600;
}
.supp-table .ticket-subject .subj-meta {
  font-size: var(--font-size-xs);
  color: var(--text-muted);
  margin-top: 1px;
}
.supp-table .ticket-user {
  color: var(--text-primary);
}
.supp-table .ticket-cat {
  color: var(--text-muted);
  font-size: var(--font-size-sm);
}
.supp-table .ticket-time {
  font-size: var(--font-size-xs);
  color: var(--text-muted);
  white-space: nowrap;
}

/* ── Priority badge ── */
.pr-badge {
  font-weight: 700;
  font-size: 0.75rem;
}
.st-badge {
  display: inline-flex;
  padding: 0.2rem 0.65rem;
  border-radius: var(--radius-full);
  font-size: 0.7rem;
  font-weight: 700;
  white-space: nowrap;
}

/* ── Pagination ── */
.supp-paginate {
  padding: 0.75rem 1rem;
  border-top: 1px solid var(--border-light);
}
</style>
@endpush

@section('content')
<div class="page-content">

  {{-- Header --}}
  <div class="supp-hdr">
    <div class="supp-hdr-info">
      <div class="supp-hdr-icon">
        <x-heroicon name="support_agent" />
      </div>
      <div>
        <h1 class="supp-hdr-title">الدعم الفني</h1>
        <p class="supp-hdr-sub">تذاكر الدعم الواردة — عرض، رد، وإدارة الحالة</p>
      </div>
    </div>
  </div>

  {{-- KPI strip --}}
  <div class="supp-kpis">
    <div class="supp-kpi">
      <span class="supp-kpi-val" style="color:#2563eb;">{{ $kpis['open'] }}</span>
      <span class="supp-kpi-label">مفتوحة</span>
    </div>
    <div class="supp-kpi">
      <span class="supp-kpi-val" style="color:#b45309;">{{ $kpis['in_progress'] }}</span>
      <span class="supp-kpi-label">قيد المعالجة</span>
    </div>
    <div class="supp-kpi">
      <span class="supp-kpi-val" style="color:#7c3aed;">{{ $kpis['waiting_customer'] }}</span>
      <span class="supp-kpi-label">بانتظار العميل</span>
    </div>
    <div class="supp-kpi">
      <span class="supp-kpi-val" style="color:var(--danger);">{{ $kpis['urgent'] }}</span>
      <span class="supp-kpi-label">عاجلة نشطة</span>
    </div>
    <div class="supp-kpi">
      <span class="supp-kpi-val" style="color:var(--text-primary);">{{ $kpis['total'] }}</span>
      <span class="supp-kpi-label">الإجمالي</span>
    </div>
  </div>

  {{-- Filters --}}
  <form method="GET" class="supp-filter">
    <div class="supp-filter-field search-field">
      <label>بحث</label>
      <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="رقم التذكرة، الموضوع، أو العميل">
    </div>
    <div class="supp-filter-field">
      <label>الحالة</label>
      <select name="status">
        <option value="">الكل</option>
        @foreach($statusLabels as $k => $v)
          <option value="{{ $k }}" @selected($filters['status'] === $k)>{{ $v[0] }}</option>
        @endforeach
      </select>
    </div>
    <div class="supp-filter-field">
      <label>الأولوية</label>
      <select name="priority">
        <option value="">الكل</option>
        @foreach($priorityLabels as $k => $v)
          <option value="{{ $k }}" @selected($filters['priority'] === $k)>{{ $v[0] }}</option>
        @endforeach
      </select>
    </div>
    <div class="supp-filter-field">
      <label>التصنيف</label>
      <select name="category">
        <option value="">الكل</option>
        @foreach($categoryLabels as $k => $v)
          <option value="{{ $k }}" @selected($filters['category'] === $k)>{{ $v }}</option>
        @endforeach
      </select>
    </div>
    <div class="supp-filter-actions">
      <button type="submit" class="btn btn-primary btn-sm">تصفية</button>
      <a href="{{ route('admin.support.index') }}" class="btn btn-ghost btn-sm">إعادة</a>
    </div>
  </form>

  {{-- Table --}}
  <div class="supp-table-wrap">
    <table class="supp-table">
      <thead>
        <tr>
          <th>رقم التذكرة</th>
          <th>الموضوع</th>
          <th>العميل</th>
          <th>التصنيف</th>
          <th>الأولوية</th>
          <th>الحالة</th>
          <th>آخر تحديث</th>
        </tr>
      </thead>
      <tbody>
        @forelse($tickets as $t)
          @php
            $st = $statusLabels[$t->status] ?? [$t->status, '#64748b', '#f1f5f9'];
            $pr = $priorityLabels[$t->priority] ?? [$t->priority, '#64748b'];
          @endphp
          <tr onclick="window.location='{{ route('admin.support.show', $t) }}'">
            <td><span class="ticket-num">{{ $t->ticket_number }}</span></td>
            <td>
              <div class="ticket-subject">
                <div class="subj-text">{{ $t->subject }}</div>
                <div class="subj-meta">{{ $t->messages_count }} رسالة</div>
              </div>
            </td>
            <td class="ticket-user">{{ $t->user?->first_name }} {{ $t->user?->last_name }}</td>
            <td><span class="ticket-cat">{{ $categoryLabels[$t->category] ?? $t->category }}</span></td>
            <td><span class="pr-badge" style="color:{{ $pr[1] }};">{{ $pr[0] }}</span></td>
            <td><span class="st-badge" style="background:{{ $st[2] }};color:{{ $st[1] }};">{{ $st[0] }}</span></td>
            <td><span class="ticket-time">{{ $t->updated_at?->diffForHumans() }}</span></td>
          </tr>
        @empty
          <tr><td colspan="7" style="padding:2rem;text-align:center;color:var(--text-muted);">لا توجد تذاكر مطابقة</td></tr>
        @endforelse
      </tbody>
    </table>
    @if($tickets->hasPages())
      <div class="supp-paginate">
        {{ $tickets->links() }}
      </div>
    @endif
  </div>

</div>
@endsection
