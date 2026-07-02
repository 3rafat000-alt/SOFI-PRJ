@extends('layouts.admin')

@section('title', 'المعاملات')

@section('breadcrumbs')
<span class="breadcrumb-item">المعاملات</span>
@endsection

@php
  $now     = \Carbon\Carbon::now();
  $dayAr   = ['الأحد','الإثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
  $monthAr = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
  $dateStr = $dayAr[$now->dayOfWeek] . '، ' . $now->format('j') . ' ' . $monthAr[$now->month - 1] . ' ' . $now->format('Y');
@endphp

@section('content')
{{-- ═══ RADICAL REDESIGN — Transactions Page (matches users pattern) ═══ --}}
<style>
  /* ── Reset layer ── */
  .tx-scope *,
  .tx-scope *::before,
  .tx-scope *::after { box-sizing:border-box; }

  /* ── Root ── */
  .tx-scope { --tx-radius:var(--radius-lg); --tx-gap:10px; }

  /* ── Table toolbar (inside table card) ── */
  .tx-table-toolbar {
    display:flex; flex-wrap:wrap; align-items:center; gap:8px;
    background:#fff; border-radius:var(--tx-radius) var(--tx-radius) 0 0;
    padding:10px 14px; border-bottom:1px solid var(--border-light);
  }
  .tx-th-search {
    flex:1; display:flex; align-items:center; gap:6px;
    background:var(--input-bg); border-radius:var(--radius-sm);
    padding:6px 10px; min-width:140px; max-width:280px;
    transition:background .15s;
  }
  .tx-th-search:focus-within { background:var(--surface); }
  .tx-th-search svg { width:15px; height:15px; color:var(--text-muted); flex-shrink:0; }
  .tx-th-search input {
    border:none; background:transparent; outline:none;
    font-size:0.78rem; font-family:inherit; color:var(--text-primary);
    width:100%;
  }
  .tx-th-search input::placeholder { color:var(--text-muted); }

  .tx-th-date { position:relative; }
  .tx-th-date select {
    appearance:none; padding:5px 28px 5px 10px;
    border:1.5px solid var(--border-light); border-radius:var(--radius-sm);
    font-size:0.72rem; font-weight:600; font-family:inherit;
    color:var(--text-secondary); background:#fff;
    cursor:pointer; transition:border-color .15s;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%23999' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat:no-repeat; background-position:left 8px center; background-size:10px;
  }
  .tx-th-date select:hover { border-color:var(--primary); }
  .tx-th-date select:focus { outline:none; border-color:var(--primary); }
  .tx-th-date--active select { background-color:var(--primary-soft); color:var(--primary); border-color:var(--primary); }

  .tx-th-btn {
    display:inline-flex; align-items:center; justify-content:center;
    width:30px; height:30px; border:1.5px solid var(--border-light); border-radius:var(--radius-sm);
    background:#fff; color:var(--text-muted); cursor:pointer;
    transition:all .12s; flex-shrink:0;
  }
  .tx-th-btn:hover { border-color:var(--primary); color:var(--primary); }
  .tx-th-btn--active { background:var(--primary-soft); color:var(--primary); border-color:var(--primary); }
  .tx-th-btn svg { width:14px; height:14px; }

  .tx-th-export {
    display:inline-flex; align-items:center; gap:4px;
    padding:4px 10px; border:1.5px solid var(--border-light); border-radius:var(--radius-sm);
    font-size:0.68rem; font-weight:700; font-family:inherit;
    color:var(--text-muted); background:#fff; cursor:pointer; text-decoration:none;
    transition:all .12s; flex-shrink:0;
  }
  .tx-th-export:hover { border-color:var(--primary); color:var(--primary); }
  .tx-th-export svg { width:13px; height:13px; }

  /* ── Custom date popup ── */
  .tx-date-custom {
    display:flex; align-items:center; gap:6px;
    margin-top:8px; padding:8px 12px;
    background:var(--input-bg); border-radius:var(--radius-sm);
  }
  .tx-date-custom input[type=date] {
    flex:1; padding:5px 8px; border:1.5px solid var(--border-light);
    border-radius:var(--radius-sm); font-size:0.72rem; font-family:inherit;
    background:#fff; color:var(--text-primary); direction:ltr;
    transition:border-color .15s;
  }
  .tx-date-custom input[type=date]:focus { outline:none; border-color:var(--primary); }
  .tx-date-custom span { font-size:0.7rem; color:var(--text-muted); flex-shrink:0; }

  /* ── Section identity card (burgundy accent) ── */
  .tx-section-card {
    background:linear-gradient(135deg, var(--primary-dark), var(--primary));
    border-radius:var(--radius-xl); padding:18px 22px;
    display:flex; align-items:center; justify-content:space-between; gap:16px;
    flex-wrap:wrap; margin-bottom:16px;
    position:relative; overflow:hidden;
  }
  .tx-section-card::after {
    content:''; position:absolute; inset:0;
    background:radial-gradient(ellipse 70% 60% at 0% 100%, rgba(255,255,255,0.06) 0%, transparent 70%);
    pointer-events:none;
  }
  .tx-section-start {
    display:flex; align-items:center; gap:14px; position:relative; z-index:1;
  }
  .tx-section-icon {
    width:44px; height:44px; border-radius:var(--radius-lg); flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    background:rgba(255,255,255,0.15); color:#fff; backdrop-filter:blur(4px);
  }
  .tx-section-icon svg { width:20px; height:20px; }
  .tx-section-title {
    font-size:1.05rem; font-weight:800; color:#fff; margin:0;
    letter-spacing:-0.02em; text-shadow:0 1px 2px rgba(0,0,0,0.12);
  }
  .tx-section-desc {
    font-size:0.72rem; color:rgba(255,255,255,0.7); margin:2px 0 0;
  }
  .tx-section-date {
    display:flex; align-items:center; gap:6px;
    font-size:0.7rem; font-weight:600; color:rgba(255,255,255,0.85);
    background:rgba(255,255,255,0.12); padding:5px 14px; border-radius:var(--radius-full);
    white-space:nowrap; position:relative; z-index:1; backdrop-filter:blur(4px);
  }
  .tx-section-date svg { width:12px; height:12px; flex-shrink:0; }

  /* ── Active filter chips ── */
  .tx-chips {
    display:flex; flex-wrap:wrap; gap:6px; margin-bottom:14px;
  }
  .tx-chip {
    display:inline-flex; align-items:center; gap:4px;
    padding:3px 10px; border-radius:var(--radius-2xl); font-size:0.68rem; font-weight:600;
    background:var(--primary-soft); color:var(--primary); cursor:default;
  }
  .tx-chip button {
    display:inline-flex; align-items:center; justify-content:center;
    width:14px; height:14px; border:none; border-radius:var(--radius-full);
    background:transparent; color:var(--primary); cursor:pointer; padding:0;
    transition:background .1s;
  }
  .tx-chip button:hover { background:var(--primary); color:#fff; }
  .tx-chip button svg { width:10px; height:10px; }
  .tx-chip-clear {
    font-size:0.68rem; font-weight:600; color:var(--text-muted);
    background:none; border:none; cursor:pointer; font-family:inherit;
    padding:3px 6px; border-radius:var(--radius-sm); transition:color .1s;
  }
  .tx-chip-clear:hover { color:var(--danger); }

  /* ── Table ── */
  .tx-table-wrap {
    background:#fff; border-radius:0 0 var(--tx-radius) var(--tx-radius); overflow:hidden;
  }
  .tx-tbl { width:100%; border-collapse:collapse; }
  .tx-tbl th {
    text-align:start; padding:10px 12px;
    font-size:0.68rem; font-weight:700; color:var(--text-muted);
    white-space:nowrap; user-select:none; cursor:pointer;
    border-bottom:1px solid var(--border-light);
    transition:color .1s;
  }
  .tx-tbl th:hover { color:var(--text-primary); }
  .tx-tbl-sort { font-size:0.55rem; margin-inline-start:3px; }
  .tx-tbl-sorted { color:var(--primary) !important; }
  .tx-tbl-ref { min-width:120px; }
  .tx-tbl-user { min-width:160px; }
  .tx-tbl-type { width:90px; }
  .tx-tbl-amount { width:120px; text-align:end; }
  .tx-tbl-fee { width:90px; text-align:end; }
  .tx-tbl-status { width:80px; }
  .tx-tbl-date { width:130px; }
  .tx-tbl-actions { width:72px; text-align:center; }

  /* ── Table rows ── */
  .tt-row { transition:background .1s; }
  .tt-row:hover { background:var(--input-bg); }
  .tt-row td {
    padding:6px 12px; vertical-align:middle;
    border-bottom:1px solid var(--border-light);
  }

  /* Reference cell */
  .tt-ref {
    font-size:0.72rem; font-weight:700; color:var(--text-primary);
    text-decoration:none; direction:ltr; display:inline-block; font-family:monospace;
  }
  .tt-ref:hover { color:var(--primary); text-decoration:underline; }

  /* User cell */
  .tt-user-wrap {
    display:flex; align-items:center; gap:10px;
  }
  .tt-avatar {
    width:36px; height:36px; border-radius:var(--radius-full); flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    font-size:0.72rem; font-weight:800; color:#fff;
    background:linear-gradient(135deg,var(--primary),var(--primary-dark));
  }
  .tt-user-info { min-width:0; }
  .tt-name {
    display:block; font-size:0.8rem; font-weight:700; color:var(--text-primary);
    text-decoration:none; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    transition:color .1s;
  }
  .tt-name:hover { color:var(--primary); }
  .tt-email {
    display:block; font-size:0.64rem; color:var(--text-muted);
    direction:ltr; text-align:start; white-space:nowrap;
    overflow:hidden; text-overflow:ellipsis;
  }

  /* Type cell */
  .tt-type {
    display:inline-block; padding:.15rem .5rem; border-radius:999px;
    background:var(--surface-hover); font-size:0.65rem; font-weight:600; color:var(--text-secondary);
    white-space:nowrap;
  }

  /* Amount cell */
  .tt-amount-cell { text-align:end; }
  .tt-amount { direction:ltr; }
  .tt-amount-val {
    font-size:0.78rem; font-weight:700; font-variant-numeric:tabular-nums;
  }
  .tt-amount-sub {
    font-size:0.6rem; font-weight:500; margin-top:1px;
  }

  /* Fee cell */
  .tt-fee-cell { text-align:end; }
  .tt-fee {
    font-size:0.68rem; color:var(--text-muted); font-variant-numeric:tabular-nums; direction:ltr;
    display:inline-block;
  }

  /* Status cell */
  .tt-status {
    display:inline-flex; align-items:center; gap:5px;
    font-size:0.72rem; font-weight:600; color:var(--text-secondary);
  }
  .tt-dot {
    width:7px; height:7px; border-radius:var(--radius-full); flex-shrink:0;
  }
  .tt-dot--completed  { background:var(--success); }
  .tt-dot--pending,
  .tt-dot--processing  { background:var(--accent); }
  .tt-dot--failed,
  .tt-dot--cancelled  { background:var(--danger); }
  .tt-dot--reversed,
  .tt-dot--refunded   { background:var(--text-muted); }

  /* Date cell */
  .tt-date-cell {
    font-size:0.65rem; font-weight:500; direction:ltr; white-space:nowrap;
  }
  .tt-date-time { color:var(--text-primary); }
  .tt-date-sub { color:var(--text-muted); }

  /* Actions cell */
  .tt-actions {
    display:flex; align-items:center; justify-content:center; gap:2px;
    opacity:0.3; transition:opacity .15s;
  }
  .tt-row:hover .tt-actions { opacity:1; }
  .tt-act {
    display:inline-flex; align-items:center; justify-content:center;
    width:26px; height:26px; border:none; border-radius:var(--radius-sm);
    background:transparent; color:var(--text-muted); cursor:pointer;
    text-decoration:none; transition:background .1s,color .1s;
  }
  .tt-act:hover { background:var(--input-bg); color:var(--primary); }
  .tt-act svg { width:12px; height:12px; }
  .tt-act--open:hover { color:var(--primary); background:var(--primary-soft); }
  .tt-act--warn:hover { color:var(--danger); background:var(--danger-light); }

  /* ── Empty state (table) ── */
  .tt-empty { text-align:center; padding:0; }
  .tt-empty-inner {
    display:flex; flex-direction:column; align-items:center;
    justify-content:center; padding:48px 24px;
  }
  .tt-empty-icon {
    width:44px; height:44px; border-radius:var(--radius-full);
    display:flex; align-items:center; justify-content:center;
    background:var(--input-bg); color:var(--text-muted); margin-bottom:10px;
  }
  .tt-empty-icon svg { width:20px; height:20px; }
  .tt-empty-title {
    font-size:0.82rem; font-weight:700; color:var(--text-primary); margin-bottom:3px;
  }
  .tt-empty-desc { font-size:0.7rem; color:var(--text-muted); max-width:260px; }
  .tt-empty-btn {
    margin-top:10px; display:inline-flex; align-items:center; gap:4px;
    padding:5px 12px; border:none; border-radius:var(--tx-radius);
    font-size:0.72rem; font-weight:600; font-family:inherit;
    color:var(--primary); background:transparent; cursor:pointer;
    transition:background .1s;
  }
  .tt-empty-btn:hover { background:var(--primary-soft); }
  .tt-empty-btn svg { width:13px; height:13px; }

  /* ── Pre-table empty state ── */
  .tx-empty {
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    text-align:center; padding:48px 24px;
  }
  .tx-empty-icon {
    width:48px; height:48px; border-radius:var(--radius-full);
    display:flex; align-items:center; justify-content:center;
    background:var(--input-bg); color:var(--text-muted); margin-bottom:12px;
  }
  .tx-empty-icon svg { width:22px; height:22px; }
  .tx-empty-title {
    font-size:0.85rem; font-weight:700; color:var(--text-primary); margin-bottom:4px;
  }
  .tx-empty-desc { font-size:0.72rem; color:var(--text-muted); max-width:280px; }

  /* ── Pagination ── */
  .tx-pager {
    display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between;
    gap:8px; padding:10px 14px;
    background:#fff; border-radius:0 0 var(--tx-radius) var(--tx-radius);
    border-top:1px solid var(--border-light);
  }
  .tx-pager-info {
    font-size:0.68rem; color:var(--text-muted); font-weight:500;
  }
  .tx-pager-nav { display:flex; gap:4px; }
  .tx-pager-nav a, .tx-pager-nav span {
    display:inline-flex; align-items:center; justify-content:center;
    min-width:30px; height:30px; padding:0 6px;
    border-radius:var(--radius-sm); font-size:0.72rem; font-weight:600;
    color:var(--text-secondary); text-decoration:none;
    transition:background .1s,color .1s;
  }
  .tx-pager-nav a:hover { background:var(--input-bg); color:var(--primary); }
  .tx-pager-nav span[aria-current=page] {
    background:var(--primary); color:#fff;
  }

  /* ── Slide-over filter panel (Left side — opposite of RTL content) ── */
  .tx-overlay {
    position:fixed; inset:0; z-index:900; background:rgba(0,0,0,0.25);
    backdrop-filter:blur(2px); transition:opacity .2s;
  }
  .tx-drawer {
    position:fixed; top:0; bottom:0; left:0; z-index:901;
    width:320px; max-width:85vw;
    background:#fff; display:flex; flex-direction:column;
    box-shadow:-4px 0 24px rgba(0,0,0,0.08);
    transition:transform .25s cubic-bezier(.22,1,.36,1);
  }
  .tx-drawer[aria-hidden=true] { transform:translateX(-105%); }
  .tx-drawer-head {
    display:flex; align-items:center; justify-content:space-between;
    padding:16px 18px 12px; border-bottom:1px solid var(--border-light);
  }
  .tx-drawer-head h3 { font-size:0.9rem; font-weight:800; color:var(--text-primary); }
  .tx-drawer-close {
    width:30px; height:30px; border:none; border-radius:var(--radius-sm);
    background:transparent; color:var(--text-muted); cursor:pointer;
    display:flex; align-items:center; justify-content:center;
    transition:background .1s;
  }
  .tx-drawer-close:hover { background:var(--input-bg); color:var(--text-primary); }
  .tx-drawer-close svg { width:16px; height:16px; }

  .tx-drawer-body { flex:1; overflow-y:auto; padding:16px 18px; }
  .tx-drawer-group { margin-bottom:18px; }
  .tx-drawer-label {
    display:block; font-size:0.68rem; font-weight:700; color:var(--text-secondary);
    margin-bottom:5px; text-transform:uppercase; letter-spacing:0.03em;
  }
  .tx-drawer-select, .tx-drawer-input {
    width:100%; padding:8px 10px; border:1.5px solid var(--border-light);
    border-radius:var(--radius-md); font-size:0.78rem; font-family:inherit;
    background:#fff; color:var(--text-primary); transition:border-color .1s;
  }
  .tx-drawer-select:focus, .tx-drawer-input:focus {
    outline:none; border-color:var(--primary);
  }

  .tx-drawer-foot {
    display:flex; gap:8px; padding:12px 18px 16px;
    border-top:1px solid var(--border-light);
  }
  .tx-drawer-foot button {
    flex:1; padding:8px; border-radius:var(--radius-md);
    font-size:0.75rem; font-weight:700; font-family:inherit; cursor:pointer;
    transition:background .12s,opacity .12s;
  }
  .tx-drawer-apply {
    border:none; background:var(--primary); color:#fff;
  }
  .tx-drawer-apply:hover { opacity:0.9; }
  .tx-drawer-reset {
    border:1.5px solid var(--border-light); background:transparent; color:var(--text-secondary);
  }
  .tx-drawer-reset:hover { border-color:var(--danger); color:var(--danger); }

  /* ── Quick-view slide-over ── */
  .tx-qv-overlay {
    position:fixed; inset:0; z-index:900; background:rgba(0,0,0,0.25);
    backdrop-filter:blur(2px);
  }
  .tx-qv-panel {
    position:fixed; top:0; bottom:0; right:0; z-index:901;
    width:380px; max-width:90vw;
    background:#fff; display:flex; flex-direction:column;
    box-shadow:4px 0 24px rgba(0,0,0,0.08);
  }
  .tx-qv-head {
    display:flex; align-items:center; justify-content:space-between; gap:10px;
    padding:16px 18px 12px; border-bottom:1px solid var(--border-light);
  }
  .tx-qv-head h3 { font-size:0.9rem; font-weight:800; color:var(--text-primary); }
  .tx-qv-close {
    width:30px; height:30px; border:none; border-radius:var(--radius-sm);
    background:var(--input-bg); color:var(--text-muted); cursor:pointer;
    display:flex; align-items:center; justify-content:center;
    transition:background .1s;
  }
  .tx-qv-close:hover { background:var(--danger-light); color:var(--danger); }
  .tx-qv-close svg { width:16px; height:16px; }

  .tx-qv-body { flex:1; overflow-y:auto; padding:16px 18px; }
  .tx-qv-field { margin-bottom:14px; }
  .tx-qv-label { font-size:0.65rem; font-weight:700; color:var(--text-muted); margin-bottom:3px; text-transform:uppercase; }
  .tx-qv-value { font-size:0.85rem; font-weight:600; color:var(--text-primary); }
  .tx-qv-divider { height:1px; background:var(--border-light); margin:12px 0; }

  .tx-qv-foot {
    padding:12px 18px 16px;
    border-top:1px solid var(--border-light);
  }

  /* ── KPI Skeleton ── */
  .tx-kpi-skeleton {
    display:grid; grid-template-columns:repeat(auto-fill, minmax(170px, 1fr)); gap:.75rem;
    margin-bottom:16px;
  }
  .tx-kpi-skel {
    padding:1rem 1.1rem; border-radius:var(--radius-main);
    background:var(--surface); box-shadow:var(--shadow-card);
  }

  /* ── Responsive ── */
  @media (max-width:768px) {
    .tx-table-toolbar { flex-wrap:wrap; }
    .tx-th-search { max-width:100%; order:0; }
    .tx-th-date { order:2; }
    .tx-th-date select { min-width:100%; }
    .tx-th-btn { order:3; }
    .tx-th-export { order:4; }
    .tx-drawer { width:85vw; }

    /* Table: hide less essential cols */
    .tx-tbl-fee, .tt-fee-cell,
    .tx-tbl-type, .tt-type-cell { display:none; }
    .tt-actions { opacity:1; }
    .tt-avatar { width:30px; height:30px; font-size:0.65rem; }
    .tx-tbl-user { min-width:120px; }
    .tx-tbl th, .tt-row td { padding:6px 8px; }
  }
</style>

{{-- ── Hidden filter form (JS sync) ── --}}
<form method="GET" action="{{ route('admin.transactions') }}" id="tx-filter-form" hidden aria-hidden="true">
  <input type="text"     name="search"     value="{{ request('search') }}">
  <input type="text"     name="type"       value="{{ request('type') }}">
  <input type="text"     name="category"   value="{{ request('category') }}">
  <input type="text"     name="status"     value="{{ request('status') }}">
  <input type="text"     name="currency"   value="{{ request('currency') }}">
  <input type="text"     name="date_from"  value="{{ request('date_from') }}">
  <input type="text"     name="date_to"    value="{{ request('date_to') }}">
  @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
  @if(request('dir'))<input type="hidden" name="dir" value="{{ request('dir') }}">@endif
</form>

<div class="tx-scope" x-data="transactionsPage">
  {{-- ═══ Section identity card ═══ --}}
  <div class="tx-section-card">
    <div class="tx-section-start">
      <div class="tx-section-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v3m0 16v3M4 7l8-4 8 4M4 17l8 4 8-4M4 7v10l8 4M20 7v10l-8 4"/><path d="M8 9v6m8-6v6"/><path d="M12 7v2m0 6v2"/></svg>
      </div>
      <div>
        <h3 class="tx-section-title">سجل المعاملات</h3>
        <p class="tx-section-desc">دفتر حركة الأموال — سجل ثابت قابل للتدقيق</p>
      </div>
    </div>
    <div class="tx-section-date">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      <span>{{ $dateStr }}</span>
    </div>
  </div>

  {{-- ═══ KPI cards ═══ --}}
  {{-- Skeleton loading --}}
  <div class="tx-kpi-skeleton" x-show="kpiLoading" x-cloak>
    <template x-for="i in 6" :key="i">
      <div class="tx-kpi-skel">
        <div class="skeleton" style="height:36px;width:36px;border-radius:var(--radius-md);margin-bottom:.6rem"></div>
        <div class="skeleton" style="height:22px;width:70%;border-radius:var(--radius-sm);margin-bottom:.25rem"></div>
        <div class="skeleton" style="height:12px;width:50%;border-radius:var(--radius-sm)"></div>
      </div>
    </template>
  </div>

  {{-- KPI error --}}
  <div x-show="!kpiLoading && kpiError" x-cloak>
    <div class="dash4-kpi-card" style="padding:1.5rem;text-align:center;">
      <p class="text-sm" style="color:var(--text-secondary)">تعذّر تحميل المؤشرات</p>
      <button type="button" @click="loadKpis()" class="btn btn-ghost btn-sm mt-2" style="color:var(--primary)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;display:inline;margin-left:4px;"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/></svg>
        إعادة المحاولة
      </button>
    </div>
  </div>

  {{-- KPI grid --}}
  <template x-if="!kpiLoading && !kpiError && kpis">
    @include('admin.partials._kpi_card_grid', ['ns' => 'tx-kpi'])
  </template>

  {{-- ═══ Active filter chips ═══ --}}
  <div class="tx-chips" x-show="hasActiveFilters" x-cloak>
    <template x-for="chip in activeChips" :key="chip.key">
      <span class="tx-chip">
        <span x-text="chip.label"></span>
        <button @click="removeFilter(chip.key)" aria-label="إزالة">&times;</button>
      </span>
    </template>
    <button class="tx-chip-clear" @click="clearAllFilters()">مسح الكل</button>
  </div>

  {{-- ═══ Table toolbar (search · date · filter · export) ═══ --}}
  <div class="tx-table-toolbar">
    <div class="tx-th-search">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
      <input type="text" x-model.debounce.250ms="query" @input.debounce.250ms="onSearch"
             placeholder="بحث بالمرجع أو اسم المستخدم…" autocomplete="off">
    </div>
    <div class="tx-th-date" :class="datePreset==='custom' && 'tx-th-date--active'">
      <select x-model="datePreset" @change="onDatePreset">
        <option value="">كل الأوقات</option>
        <option value="today">اليوم</option>
        <option value="yesterday">أمس</option>
        <option value="7d">آخر 7 أيام</option>
        <option value="14d">آخر 14 يوم</option>
        <option value="30d">آخر 30 يوم</option>
        <option value="90d">آخر 90 يوم</option>
        <option value="this_month">هذا الشهر</option>
        <option value="last_month">الشهر الماضي</option>
        <option value="custom">مخصّص</option>
      </select>
    </div>
    <button type="button" class="tx-th-btn" :class="drawerOpen && 'tx-th-btn--active'"
            @click="drawerOpen = !drawerOpen" aria-label="فلاتر">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="20" y2="12"/><line x1="12" y1="18" x2="20" y2="18"/></svg>
    </button>
    <a href="{{ route('admin.transactions.export', request()->query()) }}" id="tx-export-link" class="tx-th-export" title="CSV">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      <span>CSV</span>
    </a>
  </div>
  {{-- Custom date range (visible when اختيار مخصص) --}}
  <div class="tx-date-custom" x-show="datePreset==='custom'" x-cloak>
    <input type="date" x-model="filters.date_from" @change="onDateCustom" placeholder="من">
    <span>–</span>
    <input type="date" x-model="filters.date_to" @change="onDateCustom" placeholder="إلى">
  </div>

  {{-- ═══ Results table (AJAX region) ═══ --}}
  <div id="tx-results" aria-live="polite" aria-busy="false">
    @include('admin.transactions.partials._table')
  </div>

  {{-- ═══ Filter drawer (slide-over) ═══ --}}
  <template x-teleport="body">
    <div>
      <div class="tx-overlay" x-show="drawerOpen" x-cloak
           x-transition:enter="transition ease-out duration-200"
           x-transition:enter-start="opacity-0"
           x-transition:enter-end="opacity-100"
           x-transition:leave="transition ease-in duration-150"
           x-transition:leave-start="opacity-100"
           x-transition:leave-end="opacity-0"
           @click="drawerOpen = false"></div>
      <div class="tx-drawer" role="dialog" aria-modal="true" aria-label="فلاتر البحث"
           x-show="drawerOpen" x-cloak
           x-transition:enter="transition ease-out duration-250"
           x-transition:enter-start="-translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-200"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="-translate-x-full">
        <div class="tx-drawer-head">
          <h3>فلاتر البحث</h3>
          <button type="button" class="tx-drawer-close" @click="drawerOpen = false" aria-label="إغلاق">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div class="tx-drawer-body">
          <div class="tx-drawer-group">
            <label class="tx-drawer-label">النوع</label>
            <select class="tx-drawer-select" x-model="filters.type" @change="onFilterChange">
              <option value="">كل الأنواع</option>
              @foreach(\App\Enums\TransactionType::cases() as $t)
              <option value="{{ $t->value }}">{{ $t->labelAr() }}</option>
              @endforeach
            </select>
          </div>
          <div class="tx-drawer-group">
            <label class="tx-drawer-label">التصنيف</label>
            <select class="tx-drawer-select" x-model="filters.category" @change="onFilterChange">
              <option value="">الكل</option>
              @foreach(\App\Enums\TransactionCategory::cases() as $c)
              <option value="{{ $c->value }}">{{ $c->labelAr() }}</option>
              @endforeach
            </select>
          </div>
          <div class="tx-drawer-group">
            <label class="tx-drawer-label">الحالة</label>
            <select class="tx-drawer-select" x-model="filters.status" @change="onFilterChange">
              <option value="">كل الحالات</option>
              @foreach(\App\Enums\TransactionStatus::cases() as $s)
              <option value="{{ $s->value }}">{{ $s->labelAr() }}</option>
              @endforeach
            </select>
          </div>
          <div class="tx-drawer-group">
            <label class="tx-drawer-label">العملة</label>
            <select class="tx-drawer-select" x-model="filters.currency" @change="onFilterChange">
              <option value="">الكل</option>
              <option value="USD">USD</option>
              <option value="SYP">SYP</option>
            </select>
          </div>
        </div>
        <div class="tx-drawer-foot">
          <button type="button" class="tx-drawer-reset" @click="clearAllFilters()">إعادة ضبط</button>
          <button type="button" class="tx-drawer-apply" @click="drawerOpen = false">تطبيق</button>
        </div>
      </div>
    </div>
  </template>

  {{-- ═══ Quick-view slide-over (right side) ═══ --}}
  <template x-teleport="body">
    <div>
      <div class="tx-qv-overlay" x-show="qvShow" x-cloak
           x-transition:enter="transition ease-out duration-200"
           x-transition:enter-start="opacity-0"
           x-transition:enter-end="opacity-100"
           x-transition:leave="transition ease-in duration-150"
           x-transition:leave-start="opacity-100"
           x-transition:leave-end="opacity-0"
           @click="qvShow = false"></div>
      <div class="tx-qv-panel" role="dialog" aria-modal="true" aria-label="عرض سريع للمعاملة"
           x-show="qvShow" x-cloak
           x-transition:enter="transition ease-out duration-250"
           x-transition:enter-start="translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-200"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="translate-x-full">
        <div class="tx-qv-head">
          <h3>عرض سريع</h3>
          <button type="button" class="tx-qv-close" @click="qvShow = false" aria-label="إغلاق">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>

        {{-- Loading --}}
        <div class="tx-qv-body" x-show="qvLoading" x-cloak>
          <div class="space-y-4">
            <div class="skeleton" style="height:24px;width:60%;border-radius:var(--radius-sm)"></div>
            <div class="skeleton" style="height:14px;width:40%;border-radius:var(--radius-sm)"></div>
            <div class="tx-qv-divider"></div>
            <template x-for="i in 4" :key="i">
              <div class="skeleton" style="height:18px;width:100%;border-radius:var(--radius-sm);margin-bottom:8px"></div>
            </template>
          </div>
        </div>

        {{-- Error --}}
        <div class="tx-qv-body" x-show="!qvLoading && qvError" x-cloak>
          <div class="text-center py-10">
            <p class="text-sm" style="color:var(--text-secondary)">تعذّر التحميل</p>
            <button type="button" @click="qvReload()" class="btn btn-ghost btn-sm mt-2" style="color:var(--primary)">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;display:inline;margin-left:4px;"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/></svg>
              إعادة المحاولة
            </button>
          </div>
        </div>

        {{-- Content --}}
        <div class="tx-qv-body" x-show="!qvLoading && !qvError && qvTx" x-cloak>
          <div class="tx-qv-field">
            <div class="tx-qv-label">المرجع</div>
            <div class="tx-qv-value" dir="ltr" x-text="qvTx.reference"></div>
          </div>
          <div class="tx-qv-field">
            <div class="tx-qv-label">الحالة</div>
            <div>
              <span class="badge" :class="qvStatusClass(qvTx.status)" x-text="qvStatusLabel(qvTx.status)"></span>
            </div>
          </div>

          <div class="tx-qv-divider"></div>

          <div class="tx-qv-field">
            <div class="tx-qv-label">المبلغ</div>
            <div class="tx-qv-value" style="font-size:1.2rem;" :style="qvTx.amount >= 0 ? 'color:var(--success)' : 'color:var(--danger)'" dir="ltr"
                 x-text="(qvTx.amount >= 0 ? '+' : '−') + qvMoney(qvTx.amount, qvTx.currency, true)"></div>
          </div>
          <div class="tx-qv-field">
            <div class="tx-qv-label">الرسوم</div>
            <div class="tx-qv-value" dir="ltr" x-text="qvMoney(qvTx.fee || 0, qvTx.currency)"></div>
          </div>
          <div class="tx-qv-field">
            <div class="tx-qv-label">صافي المبلغ</div>
            <div class="tx-qv-value" dir="ltr" x-text="qvMoney(qvTx.net_amount || 0, qvTx.currency)"></div>
          </div>

          <div class="tx-qv-divider"></div>

          <div class="tx-qv-field">
            <div class="tx-qv-label">النوع</div>
            <div class="tx-qv-value" x-text="qvTx.type_label || qvTx.type"></div>
          </div>
          <div class="tx-qv-field">
            <div class="tx-qv-label">التصنيف</div>
            <div class="tx-qv-value" x-text="qvTx.category || '—'"></div>
          </div>
          <div class="tx-qv-field" x-show="qvUser">
            <div class="tx-qv-label">المستخدم</div>
            <div class="flex items-center gap-2 mt-1">
              <span class="fw-bold" x-text="qvUser.full_name"></span>
              <span class="text-xs" style="color:var(--text-muted)" dir="ltr" x-text="qvUser.email"></span>
            </div>
          </div>
          <div class="tx-qv-field">
            <div class="tx-qv-label">التاريخ</div>
            <div class="tx-qv-value" dir="ltr" x-text="qvTx.created_at"></div>
          </div>
        </div>

        <div class="tx-qv-foot" x-show="!qvLoading && !qvError && qvTx">
          <a :href="qvViewUrl" class="btn btn-primary btn-sm w-full">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;display:inline;margin-left:6px;"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            عرض التفاصيل الكاملة
          </a>
        </div>
      </div>
    </div>
  </template>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('transactionsPage', () => ({
    // ── State ──
    query: '{{ request('search') }}',
    filters: {
      type:      '{{ request('type') }}',
      category:  '{{ request('category') }}',
      status:    '{{ request('status') }}',
      currency:  '{{ request('currency') }}',
      date_from: '{{ request('date_from') }}',
      date_to:   '{{ request('date_to') }}',
    },
    sortCol: '{{ request('sort', 'created_at') }}',
    sortDir: '{{ request('dir', 'desc') }}',

    kpis: null,
    kpiLoading: true,
    kpiError: false,
    totalCount: {{ $transactions->total() }},
    drawerOpen: false,
    datePreset: '',
    _debounce: null,

    // ── Quick view state ──
    qvShow: false, qvLoading: false, qvError: false,
    qvTx: null, qvUser: null, qvViewUrl: '#', _qvQuickUrl: '',

    // ── Computed ──
    get hasActiveFilters() {
      return Object.values(this.filters).some(v => v !== '') || this.query !== '';
    },
    get activeChips() {
      const chips = [];
      if (this.query) chips.push({ key:'search', label:'بحث: "'+this.query+'"' });
      const map = {
        type:'النوع: ', category:'التصنيف: ', status:'الحالة: ',
        currency:'العملة: ', date_from:'من: ', date_to:'إلى: ',
      };
      const valMap = {
        status:{completed:'مكتمل',pending:'معلق',processing:'قيد المعالجة',failed:'فاشل',cancelled:'ملغي',reversed:'معكوس',refunded:'مسترد'},
        currency:{USD:'USD',SYP:'SYP'},
      };
      for (const [k, v] of Object.entries(this.filters)) {
        if (v === '') continue;
        const label = (valMap[k] && valMap[k][v]) ? valMap[k][v] : v;
        chips.push({ key:k, label: (map[k]||k+': ') + label });
      }
      return chips;
    },
    get kpiCards() {
      if (!this.kpis) return [];
      const k = this.kpis;
      return [
        { iconSvg:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><path d="M12 1v3m0 16v3M4 7l8-4 8 4M4 17l8 4 8-4M4 7v10l8 4M20 7v10l-8 4"/><path d="M8 9v6m8-6v6"/></svg>', bg:'rgba(107,15,36,0.08)', color:'var(--sukk-primary)', label:'حجم اليوم', value:'$'+Number(k.today_volume||0).toLocaleString('en',{minimumFractionDigits:2,maximumFractionDigits:2})+' <span class="dash4-kpi-sub">USD</span>', changeText:''+Number(k.today_count||0).toLocaleString()+' معاملة', changeDir:'up', sparkSvg:'<div class="dash4-spark-bar" style="background:#6E1B2D;height:60%"></div><div class="dash4-spark-bar" style="background:#6E1B2D;height:75%"></div><div class="dash4-spark-bar" style="background:#6E1B2D;height:42%"></div><div class="dash4-spark-bar" style="background:#6E1B2D;height:68%"></div><div class="dash4-spark-bar" style="background:#6E1B2D;height:55%"></div>' },
        { iconSvg:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><polyline points="20 6 9 17 4 12"/></svg>', bg:'rgba(31,157,85,0.1)', color:'var(--success)', label:'إجمالي الإيداعات', value:'$'+Number(k.total_deposits||0).toLocaleString('en',{minimumFractionDigits:2,maximumFractionDigits:2})+' <span class="dash4-kpi-sub">USD</span>', changeText:''+Number(k.deposit_count||0).toLocaleString()+' معاملة', changeDir:'up', sparkSvg:'<div class="dash4-spark-bar" style="background:#16A34A;height:40%"></div><div class="dash4-spark-bar" style="background:#16A34A;height:65%"></div><div class="dash4-spark-bar" style="background:#16A34A;height:50%"></div><div class="dash4-spark-bar" style="background:#16A34A;height:72%"></div><div class="dash4-spark-bar" style="background:#16A34A;height:58%"></div>' },
        { iconSvg:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><polyline points="6 9 12 15 18 9"/></svg>', bg:'rgba(220,38,38,0.08)', color:'var(--danger)', label:'إجمالي السحوبات', value:'$'+Number(k.total_withdrawals||0).toLocaleString('en',{minimumFractionDigits:2,maximumFractionDigits:2})+' <span class="dash4-kpi-sub">USD</span>', changeText:''+Number(k.withdrawal_count||0).toLocaleString()+' معاملة', changeDir:'down', sparkSvg:'<div class="dash4-spark-bar" style="background:#DC2626;height:55%"></div><div class="dash4-spark-bar" style="background:#DC2626;height:38%"></div><div class="dash4-spark-bar" style="background:#DC2626;height:62%"></div><div class="dash4-spark-bar" style="background:#DC2626;height:44%"></div><div class="dash4-spark-bar" style="background:#DC2626;height:70%"></div>' },
        { iconSvg:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>', bg:'rgba(181,138,60,0.1)', color:'var(--accent)', label:'الرسوم', value:'$'+Number(k.total_fees||0).toLocaleString('en',{minimumFractionDigits:2,maximumFractionDigits:2})+' <span class="dash4-kpi-sub">USD</span>', changeText:''+Number(k.fee_count||0).toLocaleString()+' عملية', changeDir:'up', sparkSvg:'<div class="dash4-spark-bar" style="background:#B58A3C;height:35%"></div><div class="dash4-spark-bar" style="background:#B58A3C;height:52%"></div><div class="dash4-spark-bar" style="background:#B58A3C;height:40%"></div><div class="dash4-spark-bar" style="background:#B58A3C;height:58%"></div><div class="dash4-spark-bar" style="background:#B58A3C;height:45%"></div>' },
        { iconSvg:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>', bg:'rgba(245,158,11,0.1)', color:'#D97706', label:'معلّقة', value:Number(k.pending_count||0).toLocaleString()+' <span class="dash4-kpi-sub">معاملة</span>', changeText:'', changeDir:'up', sparkSvg:'<div class="dash4-spark-bar" style="background:#D97706;height:45%"></div><div class="dash4-spark-bar" style="background:#D97706;height:60%"></div><div class="dash4-spark-bar" style="background:#D97706;height:35%"></div><div class="dash4-spark-bar" style="background:#D97706;height:50%"></div><div class="dash4-spark-bar" style="background:#D97706;height:42%"></div>' },
        { iconSvg:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>', bg:'rgba(220,38,38,0.08)', color:'var(--danger)', label:'فاشلة', value:Number(k.failed_count||0).toLocaleString()+' <span class="dash4-kpi-sub">معاملة</span>', changeText:'', changeDir:'down', sparkSvg:'<div class="dash4-spark-bar" style="background:#DC2626;height:50%"></div><div class="dash4-spark-bar" style="background:#DC2626;height:30%"></div><div class="dash4-spark-bar" style="background:#DC2626;height:55%"></div><div class="dash4-spark-bar" style="background:#DC2626;height:40%"></div><div class="dash4-spark-bar" style="background:#DC2626;height:65%"></div>' },
      ];
    },
    _spark(h, c) {
      return h.map(v => '<div class="dash4-spark-bar" style="background:'+c+';height:'+v+'%;"></div>').join('');
    },

    // ── Init ──
    init() { this.loadKpis(); },

    // ── KPI ──
    async loadKpis() {
      this.kpiLoading = true; this.kpiError = false;
      try {
        const r = await fetch('{{ route('admin.transactions.kpis') }}', {
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        });
        if (!r.ok) throw new Error('kpi-fail');
        this.kpis = await r.json();
        this.totalCount = this.kpis.total_count || this.kpis.total || this.totalCount;
      } catch (e) { this.kpiError = true; }
      finally { this.kpiLoading = false; }
    },

    // ── Search ──
    onSearch() {
      this._syncHidden('search', this.query);
      this._fetchResults();
    },

    // ── Filters ──
    onFilterChange() {
      this._syncAllFilters();
      this._fetchResults();
    },
    // ── Date preset (dropdown) ──
    onDatePreset() {
      const now = new Date();
      const fmt = d => d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');
      if (this.datePreset === '' || this.datePreset === 'custom') {
        this.filters.date_from=''; this.filters.date_to='';
        this._syncHidden('date_from',''); this._syncHidden('date_to','');
        this._fetchResults(); return;
      }
      const d = new Date(now);
      switch(this.datePreset) {
        case 'today': break;
        case 'yesterday': d.setDate(d.getDate()-1); break;
        case '7d':  d.setDate(d.getDate()-7); break;
        case '14d': d.setDate(d.getDate()-14); break;
        case '30d': d.setDate(d.getDate()-30); break;
        case '90d': d.setDate(d.getDate()-90); break;
        case 'this_month': d.setDate(1); break;
        case 'last_month': d.setMonth(d.getMonth()-1,1); break;
      }
      this.filters.date_from = fmt(d);
      if (this.datePreset === 'last_month') {
        this.filters.date_to = fmt(new Date(now.getFullYear(), now.getMonth(), 0));
      } else {
        this.filters.date_to = fmt(now);
      }
      this._syncHidden('date_from', this.filters.date_from);
      this._syncHidden('date_to', this.filters.date_to);
      this._fetchResults();
    },
    onDateCustom() {
      this._syncHidden('date_from', this.filters.date_from);
      this._syncHidden('date_to', this.filters.date_to);
      this._fetchResults();
    },
    removeFilter(key) {
      if (key === 'search') { this.query = ''; this._syncHidden('search', ''); }
      else { this.filters[key] = ''; this._syncHidden(key, ''); }
      this._fetchResults();
    },
    clearAllFilters() {
      this.query = '';
      for (const k in this.filters) this.filters[k] = '';
      const form = document.getElementById('tx-filter-form');
      if (form) form.querySelectorAll('input,select').forEach(el => el.value = '');
      this._fetchResults();
    },

    // ── Sort ──
    sortBy(col) {
      if (this.sortCol === col) this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
      else { this.sortCol = col; this.sortDir = 'asc'; }
      this._syncHidden('sort', this.sortCol);
      this._syncHidden('dir', this.sortDir);
      this._fetchResults();
    },
    sortIcon(col) {
      if (this.sortCol !== col) return '↕';
      return this.sortDir === 'asc' ? '↑' : '↓';
    },
    isSorted(col) { return this.sortCol === col; },

    // ── Quick view ──
    openQuickView(txId, viewUrl) {
      this.qvViewUrl = viewUrl;
      this._qvQuickUrl = '/admin/transactions/' + txId + '/quick-view';
      this.qvTx = null; this.qvUser = null; this.qvError = false;
      this.qvShow = true; this.qvLoad();
    },
    qvReload() { this.qvError = false; this.qvLoad(); },
    async qvLoad() {
      this.qvLoading = true; this.qvError = false;
      try {
        const r = await fetch(this._qvQuickUrl, {
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        });
        if (!r.ok) throw new Error('qv-fail');
        const d = await r.json();
        this.qvTx = d.transaction; this.qvUser = d.user;
      } catch (e) { this.qvError = true; }
      finally { this.qvLoading = false; }
    },
    qvSymbol(c) { return c === 'USD' ? '$' : (c === 'SYP' ? 'ل.س ' : c + ' '); },
    // Wraps "symbol + number" in a real Unicode LTR isolate (LRI/PDI) so the
    // token stays visually left-to-right (symbol left, number right, never
    // reversed) inside the surrounding RTL page — mirrors App\Support\Money.
    qvMoney(v, c, useAbs = false) {
      const n = useAbs ? Math.abs(Number(v)) : Number(v);
      const decimals = c === 'USD' ? 2 : 0;
      const formatted = n.toLocaleString('en', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
      return '⁦' + this.qvSymbol(c) + formatted + '⁩';
    },
    qvStatusClass(s) { return { completed:'badge-success', pending:'badge-warning', processing:'badge-warning', failed:'badge-danger', cancelled:'badge-danger', reversed:'badge-secondary', refunded:'badge-secondary' }[s] || 'badge-secondary'; },
    qvStatusLabel(s) { return { completed:'مكتمل', pending:'معلق', processing:'قيد المعالجة', failed:'فاشل', cancelled:'ملغي', reversed:'معكوس', refunded:'مسترد' }[s] || s; },

    // ── AJAX ──
    _syncHidden(key, value) {
      const form = document.getElementById('tx-filter-form');
      if (!form) return;
      let el = form.querySelector(`[name="${key}"]`);
      if (!el) {
        el = document.createElement('input');
        el.type = 'hidden';
        el.name = key;
        form.appendChild(el);
      }
      el.value = value;
    },
    _syncAllFilters() {
      this._syncHidden('search', this.query);
      for (const [k, v] of Object.entries(this.filters)) {
        this._syncHidden(k, v);
      }
      this._syncHidden('sort', this.sortCol);
      this._syncHidden('dir', this.sortDir);
    },
    _buildQs() {
      const form = document.getElementById('tx-filter-form');
      if (!form) return '';
      const data = new FormData(form);
      const params = new URLSearchParams();
      for (const [k, v] of data.entries()) if (v !== '') params.append(k, v);
      return params.toString();
    },
    _fetchResults() {
      const qs = this._buildQs();
      this._fetchUrl('{{ route('admin.transactions') }}' + (qs ? '?' + qs : ''));
    },
    async _fetchUrl(url) {
      const region = document.getElementById('tx-results');
      if (!region) return;
      region.setAttribute('aria-busy', 'true');

      const exportLink = document.getElementById('tx-export-link');
      if (exportLink) {
        const exportUrl = new URL('{{ route('admin.transactions.export') }}', location.origin);
        exportLink.href = exportUrl.pathname + (qs => qs ? '?' + qs : '')(this._buildQs());
      }

      try {
        const r = await fetch(url, {
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
        });
        if (!r.ok) { window.location.reload(); return; }
        region.innerHTML = await r.text();
        history.replaceState(null, '', url);
      } catch (e) { window.location.reload(); }
      finally { region.setAttribute('aria-busy', 'false'); }
    },
  }));
});
</script>
@endpush
