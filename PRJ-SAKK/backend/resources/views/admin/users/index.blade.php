@extends('layouts.admin')

@section('title', 'المستخدمون')

@section('breadcrumbs')
<span class="breadcrumb-item">المستخدمون</span>
@endsection

@php
  $now     = \Carbon\Carbon::now();
  $dayAr   = ['الأحد','الإثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
  $monthAr = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
  $dateStr = $dayAr[$now->dayOfWeek] . '، ' . $now->format('j') . ' ' . $monthAr[$now->month - 1] . ' ' . $now->format('Y');
@endphp

@section('content')
{{-- ═══ RADICAL REDESIGN — Profile-Stack Users Page ═══ --}}
<style>
  /* ── Reset layer ── */
  .up-scope *,
  .up-scope *::before,
  .up-scope *::after { box-sizing:border-box; }

  /* ── Root ── */
  .up-scope { --up-radius:var(--radius-lg); --up-gap:10px; }

  /* ── Table toolbar (inside table card) ── */
  .up-table-toolbar {
    display:flex; flex-wrap:wrap; align-items:center; gap:8px;
    background:#fff; border-radius:var(--up-radius) var(--up-radius) 0 0;
    padding:10px 14px; border-bottom:1px solid var(--border-light);
  }
  .up-th-search {
    flex:1; display:flex; align-items:center; gap:6px;
    background:var(--input-bg); border-radius:var(--radius-sm);
    padding:6px 10px; min-width:140px; max-width:280px;
    transition:background .15s;
  }
  .up-th-search:focus-within { background:var(--surface); }
  .up-th-search svg { width:15px; height:15px; color:var(--text-muted); flex-shrink:0; }
  .up-th-search input {
    border:none; background:transparent; outline:none;
    font-size:0.78rem; font-family:inherit; color:var(--text-primary);
    width:100%;
  }
  .up-th-search input::placeholder { color:var(--text-muted); }

  .up-th-date {
    position:relative;
  }
  .up-th-date select {
    appearance:none; padding:5px 28px 5px 10px;
    border:1.5px solid var(--border-light); border-radius:var(--radius-sm);
    font-size:0.72rem; font-weight:600; font-family:inherit;
    color:var(--text-secondary); background:#fff;
    cursor:pointer; transition:border-color .15s;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%23999' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat:no-repeat; background-position:left 8px center; background-size:10px;
  }
  .up-th-date select:hover { border-color:var(--primary); }
  .up-th-date select:focus { outline:none; border-color:var(--primary); }
  .up-th-date--active select { background-color:var(--primary-soft); color:var(--primary); border-color:var(--primary); }

  .up-th-btn {
    display:inline-flex; align-items:center; justify-content:center;
    width:30px; height:30px; border:1.5px solid var(--border-light); border-radius:var(--radius-sm);
    background:#fff; color:var(--text-muted); cursor:pointer;
    transition:all .12s; flex-shrink:0;
  }
  .up-th-btn:hover { border-color:var(--primary); color:var(--primary); }
  .up-th-btn--active { background:var(--primary-soft); color:var(--primary); border-color:var(--primary); }
  .up-th-btn svg { width:14px; height:14px; }

  .up-th-export {
    display:inline-flex; align-items:center; gap:4px;
    padding:4px 10px; border:1.5px solid var(--border-light); border-radius:var(--radius-sm);
    font-size:0.68rem; font-weight:700; font-family:inherit;
    color:var(--text-muted); background:#fff; cursor:pointer; text-decoration:none;
    transition:all .12s; flex-shrink:0;
  }
  .up-th-export:hover { border-color:var(--primary); color:var(--primary); }
  .up-th-export svg { width:13px; height:13px; }

  /* ── Custom date popup ── */
  .up-date-custom {
    display:flex; align-items:center; gap:6px;
    margin-top:8px; padding:8px 12px;
    background:var(--input-bg); border-radius:var(--radius-sm);
  }
  .up-date-custom input[type=date] {
    flex:1; padding:5px 8px; border:1.5px solid var(--border-light);
    border-radius:var(--radius-sm); font-size:0.72rem; font-family:inherit;
    background:#fff; color:var(--text-primary); direction:ltr;
    transition:border-color .15s;
  }
  .up-date-custom input[type=date]:focus { outline:none; border-color:var(--primary); }
  .up-date-custom span { font-size:0.7rem; color:var(--text-muted); flex-shrink:0; }

  /* ── Section identity card (burgundy accent) ── */
  .up-section-card {
    background:linear-gradient(135deg, var(--primary-dark), var(--primary));
    border-radius:var(--radius-xl); padding:18px 22px;
    display:flex; align-items:center; justify-content:space-between; gap:16px;
    flex-wrap:wrap; margin-bottom:16px;
    position:relative; overflow:hidden;
  }
  .up-section-card::after {
    content:''; position:absolute; inset:0;
    background:radial-gradient(ellipse 70% 60% at 0% 100%, rgba(255,255,255,0.06) 0%, transparent 70%);
    pointer-events:none;
  }
  .up-section-start {
    display:flex; align-items:center; gap:14px; position:relative; z-index:1;
  }
  .up-section-icon {
    width:44px; height:44px; border-radius:var(--radius-lg); flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    background:rgba(255,255,255,0.15); color:#fff; backdrop-filter:blur(4px);
  }
  .up-section-icon svg { width:20px; height:20px; }
  .up-section-title {
    font-size:1.05rem; font-weight:800; color:#fff; margin:0;
    letter-spacing:-0.02em; text-shadow:0 1px 2px rgba(0,0,0,0.12);
  }
  .up-section-desc {
    font-size:0.72rem; color:rgba(255,255,255,0.7); margin:2px 0 0;
  }
  .up-section-date {
    display:flex; align-items:center; gap:6px;
    font-size:0.7rem; font-weight:600; color:rgba(255,255,255,0.85);
    background:rgba(255,255,255,0.12); padding:5px 14px; border-radius:var(--radius-full);
    white-space:nowrap; position:relative; z-index:1; backdrop-filter:blur(4px);
  }
  .up-section-date svg { width:12px; height:12px; flex-shrink:0; }

  /* ── Active filter chips ── */
  .up-chips {
    display:flex; flex-wrap:wrap; gap:6px; margin-bottom:14px;
  }
  .up-chip {
    display:inline-flex; align-items:center; gap:4px;
    padding:3px 10px; border-radius:var(--radius-2xl); font-size:0.68rem; font-weight:600;
    background:var(--primary-soft); color:var(--primary); cursor:default;
  }
  .up-chip button {
    display:inline-flex; align-items:center; justify-content:center;
    width:14px; height:14px; border:none; border-radius:var(--radius-full);
    background:transparent; color:var(--primary); cursor:pointer; padding:0;
    transition:background .1s;
  }
  .up-chip button:hover { background:var(--primary); color:#fff; }
  .up-chip button svg { width:10px; height:10px; }
  .up-chip-clear {
    font-size:0.68rem; font-weight:600; color:var(--text-muted);
    background:none; border:none; cursor:pointer; font-family:inherit;
    padding:3px 6px; border-radius:var(--radius-sm); transition:color .1s;
  }
  .up-chip-clear:hover { color:var(--danger); }

  /* ── Identity-card Table ── */
  .up-table-wrap {
    background:#fff; border-radius:0 0 var(--up-radius) var(--up-radius); overflow:hidden;
  }
  /* Bulk mode checkbox column hidden by default, shown via class */
  .up-tbl-cb input[type=checkbox], .ut-cb input[type=checkbox] { display:block; }
  .up-tbl { width:100%; border-collapse:collapse; }
  .up-tbl th {
    text-align:start; padding:10px 12px;
    font-size:0.68rem; font-weight:700; color:var(--text-muted);
    white-space:nowrap; user-select:none; cursor:pointer;
    border-bottom:1px solid var(--border-light);
    transition:color .1s;
  }
  .up-tbl th:hover { color:var(--text-primary); }
  .up-tbl-sortable:focus-visible {
    outline:2px solid var(--primary); outline-offset:2px;
  }
  .up-tbl-sort { font-size:0.55rem; margin-inline-start:3px; }
  .up-tbl-sorted { color:var(--primary) !important; }
  .up-tbl-cb { width:36px; }
  .up-tbl-cb input[type=checkbox], .ut-cb input[type=checkbox] {
    width:15px; height:15px; accent-color:var(--primary); cursor:pointer;
    display:block; margin:0;
  }
  .up-tbl-user { min-width:160px; }
  .up-tbl-status { width:80px; }
  .up-tbl-kyc { width:90px; }
  .up-tbl-balance { width:110px; text-align:end; }
  .up-tbl-activity { width:120px; }
  .up-tbl-actions { width:72px; text-align:center; }

  /* ── Table rows ── */
  .ut-row { transition:background .1s; }
  .ut-row:hover { background:var(--input-bg); }
  .ut-row td {
    padding:6px 12px; vertical-align:middle;
    border-bottom:1px solid var(--border-light);
  }
  .ut-cb { width:36px; }
  .ut-cb input[type=checkbox] { display:block; }

  /* User cell */
  .ut-user-wrap {
    display:flex; align-items:center; gap:10px;
  }
  .ut-avatar {
    width:36px; height:36px; border-radius:var(--radius-full); flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    font-size:0.72rem; font-weight:800; color:#fff;
    background:linear-gradient(135deg,var(--primary),var(--primary-dark));
  }
  .ut-user-info { min-width:0; }
  .ut-name {
    display:block; font-size:0.8rem; font-weight:700; color:var(--text-primary);
    text-decoration:none; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    transition:color .1s;
  }
  .ut-name:hover { color:var(--primary); }
  .ut-email {
    display:block; font-size:0.64rem; color:var(--text-muted);
    direction:ltr; text-align:start; white-space:nowrap;
    overflow:hidden; text-overflow:ellipsis;
  }

  /* Status cell */
  .ut-status {
    display:inline-flex; align-items:center; gap:5px;
    font-size:0.72rem; font-weight:600; color:var(--text-secondary);
  }
  .ut-dot {
    width:7px; height:7px; border-radius:var(--radius-full); flex-shrink:0;
  }
  .ut-dot--active    { background:var(--success); }
  .ut-dot--suspended { background:var(--danger); }
  .ut-dot--banned    { background:var(--danger-dark); }
  .ut-dot--pending   { background:var(--accent); }

  /* KYC cell */
  .ut-kyc-wrap { display:flex; align-items:center; gap:6px; }
  .ut-kyc-dots { display:flex; gap:3px; align-items:center; }
  .ut-kyc-dots .ut-dot { width:5px; height:5px; }
  .ut-kyc-label {
    font-size:0.62rem; font-weight:600; white-space:nowrap;
  }
  .ut-kyc--verified { color:var(--success); }
  .ut-kyc--submitted { color:var(--accent); }
  .ut-kyc--rejected  { color:var(--danger); }
  .ut-kyc--pending   { color:var(--text-muted); }

  /* Balance cell */
  .ut-balance-cell { text-align:end; }
  .ut-balance { direction:ltr; }
  .ut-balance-usd {
    font-size:0.78rem; font-weight:700; color:var(--text-primary);
  }
  .ut-balance-syp {
    font-size:0.6rem; font-weight:600; color:var(--text-muted); margin-top:1px;
  }
  .ut-balance-empty { font-size:0.65rem; color:var(--border-strong); }

  /* Activity cell */
  .ut-activity-cell { font-size:0.65rem; }
  .ut-activity-time { color:var(--text-muted); font-weight:500; }
  .ut-activity-none { color:var(--border-strong); }

  /* Actions cell */
  .ut-actions {
    display:flex; align-items:center; justify-content:center; gap:2px;
    opacity:0.3; transition:opacity .15s;
  }
  .ut-row:hover .ut-actions { opacity:1; }
  .ut-act {
    display:inline-flex; align-items:center; justify-content:center;
    width:26px; height:26px; border:none; border-radius:var(--radius-sm);
    background:transparent; color:var(--text-muted); cursor:pointer;
    text-decoration:none; transition:background .1s,color .1s;
  }
  .ut-act:hover { background:var(--input-bg); color:var(--primary); }
  .ut-act svg { width:12px; height:12px; }
  .ut-act--warn:hover { color:var(--danger); background:var(--danger-light); }
  .ut-act--ok:hover { color:var(--success); background:rgba(22,163,74,0.08); }

  /* ── Empty state (table) ── */
  .ut-empty { text-align:center; padding:0; }
  .ut-empty-inner {
    display:flex; flex-direction:column; align-items:center;
    justify-content:center; padding:48px 24px;
  }
  .ut-empty-icon {
    width:44px; height:44px; border-radius:var(--radius-full);
    display:flex; align-items:center; justify-content:center;
    background:var(--input-bg); color:var(--text-muted); margin-bottom:10px;
  }
  .ut-empty-icon svg { width:20px; height:20px; }
  .ut-empty-title {
    font-size:0.82rem; font-weight:700; color:var(--text-primary); margin-bottom:3px;
  }
  .ut-empty-desc { font-size:0.7rem; color:var(--text-muted); max-width:260px; }
  .ut-empty-btn {
    margin-top:10px; display:inline-flex; align-items:center; gap:4px;
    padding:5px 12px; border:none; border-radius:var(--up-radius);
    font-size:0.72rem; font-weight:600; font-family:inherit;
    color:var(--primary); background:transparent; cursor:pointer;
    transition:background .1s;
  }
  .ut-empty-btn:hover { background:var(--primary-soft); }
  .ut-empty-btn svg { width:13px; height:13px; }

  /* ── Empty state ── */
  .up-empty {
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    text-align:center; padding:48px 24px;
  }
  .up-empty-icon {
    width:48px; height:48px; border-radius:var(--radius-full);
    display:flex; align-items:center; justify-content:center;
    background:var(--input-bg); color:var(--text-muted); margin-bottom:12px;
  }
  .up-empty-icon svg { width:22px; height:22px; }
  .up-empty-title {
    font-size:0.85rem; font-weight:700; color:var(--text-primary); margin-bottom:4px;
  }
  .up-empty-desc {
    font-size:0.72rem; color:var(--text-muted); max-width:280px;
  }
  .up-empty-btn {
    margin-top:12px; display:inline-flex; align-items:center; gap:5px;
    padding:6px 14px; border:none; border-radius:var(--up-radius);
    font-size:0.75rem; font-weight:600; font-family:inherit;
    color:var(--primary); background:transparent; cursor:pointer;
    transition:background .1s;
  }
  .up-empty-btn:hover { background:var(--primary-soft); }

  /* ── Pagination ── */
  .up-pager {
    display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between;
    gap:8px; padding:10px 14px;
    background:#fff; border-radius:0 0 var(--up-radius) var(--up-radius);
    border-top:1px solid var(--border-light);
  }
  .up-pager-info {
    font-size:0.68rem; color:var(--text-muted); font-weight:500;
  }
  .up-pager-nav { display:flex; gap:4px; }
  .up-pager-nav a, .up-pager-nav span {
    display:inline-flex; align-items:center; justify-content:center;
    min-width:30px; height:30px; padding:0 6px;
    border-radius:var(--radius-sm); font-size:0.72rem; font-weight:600;
    color:var(--text-secondary); text-decoration:none;
    transition:background .1s,color .1s;
  }
  .up-pager-nav a:hover { background:var(--input-bg); color:var(--primary); }
  .up-pager-nav span[aria-current=page] {
    background:var(--primary); color:#fff;
  }

  /* ── (old date presets removed — using dropdown now) ── */

  /* ── Slide-over filter panel (Left side — opposite of RTL content) ── */
  .up-overlay {
    position:fixed; inset:0; z-index:900; background:rgba(0,0,0,0.25);
    backdrop-filter:blur(2px); transition:opacity .2s;
  }
  .up-drawer {
    position:fixed; top:0; bottom:0; left:0; z-index:901;
    width:320px; max-width:85vw;
    background:#fff; display:flex; flex-direction:column;
    box-shadow:-4px 0 24px rgba(0,0,0,0.08);
    transition:transform .25s cubic-bezier(.22,1,.36,1);
  }
  .up-drawer[aria-hidden=true] { transform:translateX(-105%); }
  .up-drawer-head {
    display:flex; align-items:center; justify-content:space-between;
    padding:16px 18px 12px; border-bottom:1px solid var(--border-light);
  }
  .up-drawer-head h3 { font-size:0.9rem; font-weight:800; color:var(--text-primary); }
  .up-drawer-close {
    width:30px; height:30px; border:none; border-radius:var(--radius-sm);
    background:transparent; color:var(--text-muted); cursor:pointer;
    display:flex; align-items:center; justify-content:center;
    transition:background .1s;
  }
  .up-drawer-close:hover { background:var(--input-bg); color:var(--text-primary); }
  .up-drawer-close svg { width:16px; height:16px; }

  .up-drawer-body { flex:1; overflow-y:auto; padding:16px 18px; overscroll-behavior:contain; }
  .up-drawer-group { margin-bottom:18px; }
  .up-drawer-label {
    display:block; font-size:0.68rem; font-weight:700; color:var(--text-secondary);
    margin-bottom:5px; text-transform:uppercase; letter-spacing:0.03em;
  }
  .up-drawer-select, .up-drawer-input {
    width:100%; padding:8px 10px; border:1.5px solid var(--border-light);
    border-radius:var(--radius-md); font-size:0.78rem; font-family:inherit;
    background:#fff; color:var(--text-primary); transition:border-color .1s;
  }
  .up-drawer-select:focus, .up-drawer-input:focus {
    outline:none; border-color:var(--primary);
  }

  .up-drawer-foot {
    display:flex; gap:8px; padding:12px 18px 16px;
    border-top:1px solid var(--border-light);
  }
  .up-drawer-foot button {
    flex:1; padding:8px; border-radius:var(--radius-md);
    font-size:0.75rem; font-weight:700; font-family:inherit; cursor:pointer;
    transition:background .12s,opacity .12s;
  }
  .up-drawer-apply {
    border:none; background:var(--primary); color:#fff;
  }
  .up-drawer-apply:hover { opacity:0.9; }
  .up-drawer-reset {
    border:1.5px solid var(--border-light); background:transparent; color:var(--text-secondary);
  }
  .up-drawer-reset:hover { border-color:var(--danger); color:var(--danger); }

  /* ── Bulk bar (inside table wrap) ── */
  .up-bulk-bar {
    display:flex; align-items:center; justify-content:space-between;
    padding:10px 18px; background:#fff;
    border-top:1px solid var(--border-light);
  }
  .up-bulk-info {
    display:flex; align-items:center; gap:8px;
    font-size:0.78rem; color:var(--text-secondary);
  }
  .up-bulk-info svg { width:16px; height:16px; color:var(--success); }
  .up-bulk-act {
    display:inline-flex; align-items:center; gap:5px;
    padding:6px 14px; border:none; border-radius:var(--radius-md);
    font-size:0.72rem; font-weight:700; font-family:inherit; cursor:pointer;
    transition:background .12s,opacity .12s;
  }
  .up-bulk-act--activate { background:rgba(22,163,74,0.12); color:var(--success); }
  .up-bulk-act--activate:hover { background:rgba(22,163,74,0.2); }
  .up-bulk-act--suspend { background:rgba(220,38,38,0.1); color:var(--danger); }
  .up-bulk-act--suspend:hover { background:rgba(220,38,38,0.18); }
  .up-bulk-close {
    width:30px; height:30px; border:none; border-radius:var(--radius-sm);
    background:transparent; color:var(--text-muted); cursor:pointer;
    display:flex; align-items:center; justify-content:center;
    transition:background .1s;
  }
  .up-bulk-close:hover { background:var(--input-bg); color:var(--danger); }
  .up-bulk-close svg { width:15px; height:15px; }

  /* ── Responsive ── */
  @media (max-width:768px) {
    .up-table-toolbar { flex-wrap:wrap; }
    .up-th-search { max-width:100%; order:0; }
    .up-th-date { order:2; }
    .up-th-date select { min-width:100%; }
    .up-th-btn { order:3; }
    .up-th-export { order:4; }
    .up-drawer { width:85vw; }

    /* Table: hide less essential cols */
    .up-tbl-kyc, .ut-kyc-cell,
    .up-tbl-activity, .ut-activity-cell,
    .up-tbl-balance, .ut-balance-cell { display:none; }
    .ut-actions { opacity:1; }
    .ut-avatar { width:30px; height:30px; font-size:0.65rem; }
    .up-tbl-user { min-width:120px; }
    .up-tbl th, .ut-row td { padding:6px 8px; }
  }
</style>

{{-- ── Hidden filter form (JS sync) ── --}}
<form method="GET" action="{{ route('admin.users') }}" id="up-filter-form" hidden aria-hidden="true">
  <input type="text"     name="search"     value="{{ request('search') }}">
  <input type="text"     name="status"     value="{{ request('status') }}">
  <input type="text"     name="kyc_level"  value="{{ request('kyc_level') }}">
  <input type="text"     name="kyc_status" value="{{ request('kyc_status') }}">
  <input type="text"     name="two_fa"     value="{{ request('two_fa') }}">
  <input type="text"     name="aml_flagged" value="{{ request('aml_flagged') }}">
  <input type="text"     name="date_from"  value="{{ request('date_from') }}">
  <input type="text"     name="date_to"    value="{{ request('date_to') }}">
  @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
  @if(request('dir'))<input type="hidden" name="dir" value="{{ request('dir') }}">@endif
</form>

<div class="up-scope" x-data="usersPage">
  {{-- ═══ Section identity card ═══ --}}
  <div class="up-section-card">
    <div class="up-section-start">
      <div class="up-section-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
      </div>
      <div>
        <h3 class="up-section-title">إدارة المستخدمين</h3>
        <p class="up-section-desc">عرض وإدارة حسابات المنصة — فلترة، بحث، تعديل حالة، وعمليات حظر وتفعيل</p>
      </div>
    </div>
    <div class="up-section-date">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      <span>{{ $dateStr }}</span>
    </div>
  </div>

  {{-- ═══ KPI cards (shared partial) ═══ --}}
  @include('admin.partials._kpi_card_grid', ['ns' => 'users-kpi'])

  {{-- ═══ Active filter chips ═══ --}}
  <div class="up-chips" x-show="hasActiveFilters" x-cloak>
    <template x-for="chip in activeChips" :key="chip.key">
      <span class="up-chip">
        <span x-text="chip.label"></span>
        <button @click="removeFilter(chip.key)" aria-label="إزالة">&times;</button>
      </span>
    </template>
    <button class="up-chip-clear" @click="clearAllFilters()">مسح الكل</button>
  </div>

  {{-- ═══ Table toolbar (search · date · filter · export) ═══ --}}
  <div class="up-table-toolbar">
    <div class="up-th-search">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
      <input type="text" x-model.debounce.250ms="query" @input.debounce.250ms="onSearch"
             placeholder="بحث بالاسم أو البريد…" autocomplete="off">
    </div>
    <div class="up-th-date" :class="datePreset==='custom' && 'up-th-date--active'">
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
    <button type="button" class="up-th-btn" :class="drawerOpen && 'up-th-btn--active'"
            @click="drawerOpen = !drawerOpen" aria-label="فلاتر">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="20" y2="12"/><line x1="12" y1="18" x2="20" y2="18"/></svg>
    </button>
    <a href="{{ route('admin.users.export', request()->query()) }}" id="up-export-link" class="up-th-export" title="CSV">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      <span>CSV</span>
    </a>
  </div>
  {{-- Custom date range (visible when اختيار مخصص) --}}
  <div class="up-date-custom" x-show="datePreset==='custom'" x-cloak>
    <input type="date" x-model="filters.date_from" @change="onDateCustom" placeholder="من">
    <span>–</span>
    <input type="date" x-model="filters.date_to" @change="onDateCustom" placeholder="إلى">
  </div>

  {{-- ═══ User list (AJAX region) ═══ --}}
  <div id="up-results" aria-live="polite" aria-busy="false">
    @include('admin.users.partials._table')
  </div>



  {{-- ═══ Filter drawer (slide-over) ═══ --}}
  <template x-teleport="body">
    <div>
      <div class="up-overlay" x-show="drawerOpen" x-cloak
           x-transition:enter="transition ease-out duration-200"
           x-transition:enter-start="opacity-0"
           x-transition:enter-end="opacity-100"
           x-transition:leave="transition ease-in duration-150"
           x-transition:leave-start="opacity-100"
           x-transition:leave-end="opacity-0"
           @click="drawerOpen = false"></div>
      <div class="up-drawer" role="dialog" aria-modal="true" aria-label="فلاتر البحث"
           x-show="drawerOpen" x-cloak
           x-transition:enter="transition ease-out duration-250"
           x-transition:enter-start="-translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-200"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="-translate-x-full">
        <div class="up-drawer-head">
          <h3>فلاتر البحث</h3>
          <button type="button" class="up-drawer-close" @click="drawerOpen = false" aria-label="إغلاق">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div class="up-drawer-body">
          <div class="up-drawer-group">
            <label class="up-drawer-label">الحالة</label>
            <select class="up-drawer-select" x-model="filters.status" @change="onFilterChange">
              <option value="">الكل</option>
              <option value="active">نشط</option>
              <option value="suspended">موقوف</option>
              <option value="banned">محظور</option>
              <option value="pending">قيد الانتظار</option>
            </select>
          </div>
          <div class="up-drawer-group">
            <label class="up-drawer-label">مستوى KYC</label>
            <select class="up-drawer-select" x-model="filters.kyc_level" @change="onFilterChange">
              <option value="">الكل</option>
              <option value="0">0</option>
              <option value="1">1</option>
              <option value="2">2</option>
              <option value="3">3</option>
            </select>
          </div>
          <div class="up-drawer-group">
            <label class="up-drawer-label">حالة KYC</label>
            <select class="up-drawer-select" x-model="filters.kyc_status" @change="onFilterChange">
              <option value="">الكل</option>
              <option value="pending">قيد الانتظار</option>
              <option value="submitted">مقدّم</option>
              <option value="verified">موثّق</option>
              <option value="rejected">مرفوض</option>
            </select>
          </div>
          <div class="up-drawer-group">
            <label class="up-drawer-label">المصادقة الثنائية</label>
            <select class="up-drawer-select" x-model="filters.two_fa" @change="onFilterChange">
              <option value="">الكل</option>
              <option value="1">مفعّل</option>
              <option value="0">معطّل</option>
            </select>
          </div>
          <div class="up-drawer-group">
            <label class="up-drawer-label">مُبلّغ عنه (AML)</label>
            <select class="up-drawer-select" x-model="filters.aml_flagged" @change="onFilterChange">
              <option value="">الكل</option>
              <option value="1">مُبلَّغ عنه</option>
            </select>
          </div>
        </div>
        <div class="up-drawer-foot">
          <button type="button" class="up-drawer-reset" @click="clearAllFilters()">إعادة ضبط</button>
          <button type="button" class="up-drawer-apply" @click="drawerOpen = false">تطبيق</button>
        </div>
      </div>
    </div>
  </template>
</div>

{{-- Modals --}}
@include('admin.users.partials._modals')
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('usersPage', () => ({
    // ── State ──
    query: '{{ request('search') }}',
    filters: {
      status:     '{{ request('status') }}',
      kyc_level:  '{{ request('kyc_level') }}',
      kyc_status: '{{ request('kyc_status') }}',
      two_fa:     '{{ request('two_fa') }}',
      aml_flagged:'{{ request('aml_flagged') }}',
      date_from:  '{{ request('date_from') }}',
      date_to:    '{{ request('date_to') }}',
    },
    sortCol: '{{ request('sort', 'created_at') }}',
    sortDir: '{{ request('dir', 'desc') }}',

    kpis: null,
    totalCount: {{ $users->total() }},
    drawerOpen: false,
    selectedUsers: [],
    datePreset: '',
    _debounce: null,

    // ── Computed ──
    get hasActiveFilters() {
      return Object.values(this.filters).some(v => v !== '') || this.query !== '';
    },
    get activeChips() {
      const chips = [];
      if (this.query) chips.push({ key:'search', label:'بحث: "'+this.query+'"' });
      const map = {
        status:'الحالة: ', kyc_level:'KYC: ', kyc_status:'حالة KYC: ',
        two_fa:'2FA: ', aml_flagged:'AML: ',
        date_from:'من: ', date_to:'إلى: ',
      };
      const valMap = {
        status:{active:'نشط',suspended:'موقوف',banned:'محظور',pending:'قيد الانتظار'},
        kyc_status:{pending:'قيد الانتظار',submitted:'مقدّم',verified:'موثّق',rejected:'مرفوض'},
        two_fa:{'1':'مفعّل','0':'معطّل'},
        aml_flagged:{'1':'مُبلَّغ عنه'},
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
        { iconSvg:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>', bg:'rgba(107,15,36,0.08)', color:'var(--sukk-primary)', label:'إجمالي المستخدمين', value:Number(k.total).toLocaleString()+' <span class="dash4-kpi-sub">مستخدم</span>', changeText:'+'+Math.round(k.total*0.03)+'% أسبوعياً', changeDir:'up', sparkSvg:this._spark([47,63,38,72,55,80,68],'#6E1B2D') },
        { iconSvg:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><polyline points="20 6 9 17 4 12"/></svg>', bg:'rgba(31,157,85,0.1)', color:'var(--success)', label:'المستخدمون النشطون', value:Number(k.active).toLocaleString()+' <span class="dash4-kpi-sub">+0 اليوم</span>', changeText:'نشط 100%', changeDir:'up', sparkSvg:this._spark([49,63,41,58,34,49,54],'#16A34A') },
        { iconSvg:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>', bg:'rgba(181,138,60,0.1)', color:'var(--accent)', label:'KYC قيد المراجعة', value:Number(k.pending_kyc).toLocaleString()+' <span class="dash4-kpi-sub">طلب</span>', changeText:'+2 هذا الأسبوع', changeDir:'up', sparkSvg:this._spark([22,35,18,42,30,48,26],'#B58A3C') },
        { iconSvg:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>', bg:'rgba(220,38,38,0.08)', color:'var(--danger)', label:'موقوفون', value:Number(k.suspended).toLocaleString()+' <span class="dash4-kpi-sub">حساب</span>', changeText:'—3% هذا الشهر', changeDir:'down', sparkSvg:this._spark([60,72,45,68,55,70,50],'#DC2626') },
      ];
    },
    _spark(h, c) {
      return h.map(v => '<div class="dash4-spark-bar" style="background:'+c+';height:'+v+'%;"></div>').join('');
    },

    // ── Init ──
    init() {
      this.loadKpis();
      window.addEventListener('users-changed', () => {
        this.loadKpis();
        this._fetchResults();
      });
      // Filter drawer: lock/unlock #mainContent scroll while open (it is
      // the app-shell's scroll container now — body itself never scrolls).
      this.$watch('drawerOpen', (open) => {
        var el = document.getElementById('mainContent') || document.body;
        el.style.overflow = open ? 'hidden' : '';
      });
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
      const form = document.getElementById('up-filter-form');
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

    // ── KPI loading ──
    async loadKpis() {
      try {
        const r = await fetch('{{ route('admin.users.kpis') }}', {
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        });
        if (!r.ok) return;
        this.kpis = await r.json();
        this.totalCount = this.kpis.total;
      } catch (e) { /* silent */ }
    },

    // ── Selection ──
    toggleAll(checked) {
      const cbs = document.querySelectorAll('#up-results input[type=checkbox][value]');
      this.selectedUsers = checked ? Array.from(cbs).map(cb => cb.value) : [];
    },
    toggleUser(uuid) {
      const idx = this.selectedUsers.indexOf(uuid);
      if (idx >= 0) this.selectedUsers.splice(idx, 1);
      else this.selectedUsers.push(uuid);
    },
    isSelected(uuid) { return this.selectedUsers.includes(uuid); },

    // ── Modals ──
    openStatusModal(userId, userName, userInitials, currentStatus) {
      window.dispatchEvent(new CustomEvent('open-status-modal', {
        detail: { userId, userName, userInitials, currentStatus, updateUrl: `/admin/users/${userId}/update-status` }
      }));
    },
    openBulkModal(action) {
      const ids   = [...this.selectedUsers];
      const names = ids.slice(0, 5).map(uuid => {
        const cb = document.querySelector(`#up-results input[value="${uuid}"]`);
        return cb ? cb.getAttribute('aria-label').replace('تحديد ', '') : uuid;
      });
      window.dispatchEvent(new CustomEvent('open-bulk-modal', {
        detail: { action, userIds: ids, previewNames: names, extraCount: Math.max(0, ids.length - 5), userCount: ids.length, bulkUrl: '{{ route('admin.users.bulk') }}' }
      }));
    },

    // ── AJAX ──
    _syncHidden(key, value) {
      const form = document.getElementById('up-filter-form');
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
      const form = document.getElementById('up-filter-form');
      if (!form) return '';
      const data = new FormData(form);
      const params = new URLSearchParams();
      for (const [k, v] of data.entries()) if (v !== '') params.append(k, v);
      return params.toString();
    },
    _fetchResults() {
      const qs = this._buildQs();
      this._fetchUrl('{{ route('admin.users') }}' + (qs ? '?' + qs : ''));
    },
    async _fetchUrl(url) {
      const region = document.getElementById('up-results');
      if (!region) return;
      region.setAttribute('aria-busy', 'true');

      const exportLink = document.getElementById('up-export-link');
      if (exportLink) {
        const exportUrl = new URL('{{ route('admin.users.export') }}', location.origin);
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

  // ── Status Modal ──
  Alpine.data('statusModal', () => ({
    show:false,loading:false,userId:null,userName:'',userInitials:'',currentStatus:'',newStatus:'active',reason:'',errors:{},errorMsg:'',updateUrl:'',
    get currentStatusLabel() { return {active:'نشط',suspended:'موقوف'}[this.currentStatus]||this.currentStatus; },
    get currentStatusClass() { return {active:'badge-success',suspended:'badge-danger'}[this.currentStatus]||'badge-secondary'; },
    open(d){Object.assign(this,{userId:d.userId,userName:d.userName,userInitials:d.userInitials,currentStatus:d.currentStatus,newStatus:d.currentStatus==='active'?'suspended':'active',updateUrl:d.updateUrl,reason:'',errors:{},errorMsg:'',show:true});this.$nextTick(()=>this.$refs.firstFocus?.focus());},
    close(){this.show=false;},
    async submit(){
      this.errors={};this.errorMsg='';
      if(!this.reason||this.reason.length<3){this.errors.reason='أدخل سبباً لا يقل عن 3 أحرف.';return;}
      this.loading=true;
      try{
        const r=await fetch(this.updateUrl,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},body:JSON.stringify({status:this.newStatus,reason:this.reason})});
        if(!r.ok){window.location.reload();return;}
        const d=await r.json();this.close();
        window.dispatchEvent(new CustomEvent('toast',{detail:{type:'success',message:d.message||'تم تحديث الحالة'}}));
        window.dispatchEvent(new CustomEvent('users-changed'));
      }catch(e){this.errorMsg='حدث خطأ — يرجى المحاولة مجدداً.';}finally{this.loading=false;}
    }
  }));

  // ── Bulk Modal ──
  Alpine.data('bulkModal', () => ({
    show:false,loading:false,action:'activate',userIds:[],previewNames:[],extraCount:0,userCount:0,reason:'',errors:{},errorMsg:'',bulkUrl:'',
    open(d){Object.assign(this,{action:d.action,userIds:d.userIds,previewNames:d.previewNames,extraCount:d.extraCount,userCount:d.userCount,bulkUrl:d.bulkUrl,reason:'',errors:{},errorMsg:'',show:true});this.$nextTick(()=>this.$refs.firstFocus?.focus());},
    close(){this.show=false;},
    async submit(){
      this.errors={};this.errorMsg='';
      if(!this.reason||this.reason.length<3){this.errors.reason='أدخل سبباً لا يقل عن 3 أحرف.';return;}
      this.loading=true;
      try{
        const r=await fetch(this.bulkUrl,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},body:JSON.stringify({action:this.action,user_ids:this.userIds,reason:this.reason})});
        if(!r.ok){window.location.reload();return;}
        const d=await r.json();this.close();
        const label=this.action==='activate'?'تفعيل':'إيقاف';
        window.dispatchEvent(new CustomEvent('toast',{detail:{type:'success',message:`تم ${label} ${d.processed} مستخدم`}}));
        window.dispatchEvent(new CustomEvent('users-changed'));
      }catch(e){this.errorMsg='حدث خطأ.';}finally{this.loading=false;}
    }
  }));

  // ── KYC Modals ──
  Alpine.data('kycApproveModal', () => ({
    show:false,loading:false,userId:null,docId:null,docType:'',docNumber:'',approveUrl:'',errorMsg:'',
    open(d){Object.assign(this,{userId:d.userId,docId:d.docId,docType:d.docType,docNumber:d.docNumber,approveUrl:d.approveUrl,errorMsg:'',show:true});this.$nextTick(()=>this.$refs.firstFocus?.focus());},
    close(){this.show=false;},
    async submit(){this.loading=true;this.errorMsg='';try{const r=await fetch(this.approveUrl,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},body:'{}'});if(!r.ok){window.location.reload();return;}const d=await r.json();this.close();window.dispatchEvent(new CustomEvent('toast',{detail:{type:'success',message:d.message||'تم القبول'}}));window.dispatchEvent(new CustomEvent('users-changed'));}catch(e){this.errorMsg='حدث خطأ.';}finally{this.loading=false;}}
  }));
  Alpine.data('kycRejectModal', () => ({
    show:false,loading:false,userId:null,docId:null,docType:'',rejectUrl:'',reason:'',errors:{},errorMsg:'',
    open(d){Object.assign(this,{userId:d.userId,docId:d.docId,docType:d.docType,rejectUrl:d.rejectUrl,reason:'',errors:{},errorMsg:'',show:true});this.$nextTick(()=>this.$refs.firstFocus?.focus());},
    close(){this.show=false;},
    async submit(){this.errors={};this.errorMsg='';if(!this.reason||this.reason.length<3){this.errors.reason='أدخل سبباً.';return;}this.loading=true;try{const r=await fetch(this.rejectUrl,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},body:JSON.stringify({reason:this.reason})});if(!r.ok){window.location.reload();return;}const d=await r.json();this.close();window.dispatchEvent(new CustomEvent('toast',{detail:{type:'success',message:d.message||'تم الرفض'}}));window.dispatchEvent(new CustomEvent('users-changed'));}catch(e){this.errorMsg='حدث خطأ.';}finally{this.loading=false;}}
  }));
});
</script>
@endpush
