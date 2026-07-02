@extends('layouts.admin')

@section('title', 'الوكلاء')
@section('breadcrumbs')
<span class="breadcrumb-item">الوكلاء</span>
<span class="breadcrumb-item">جميع الوكلاء</span>
@endsection

@php
  $now     = \Carbon\Carbon::now();
  $dayAr   = ['الأحد','الإثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
  $monthAr = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
  $dateStr = $dayAr[$now->dayOfWeek] . '، ' . $now->format('j') . ' ' . $monthAr[$now->month - 1] . ' ' . $now->format('Y');
@endphp

@section('content')
<style>
  .ag-scope *,
  .ag-scope *::before,
  .ag-scope *::after { box-sizing:border-box; }
  .ag-scope { --ag-radius:var(--radius-lg); }

  /* ── Section identity card (burgundy) ── */
  .ag-section-card {
    background:linear-gradient(135deg, var(--primary-dark), var(--primary));
    border-radius:var(--radius-xl); padding:18px 22px;
    display:flex; align-items:center; justify-content:space-between; gap:16px;
    flex-wrap:wrap; margin-bottom:16px;
    position:relative; overflow:hidden;
  }
  .ag-section-card::after {
    content:''; position:absolute; inset:0;
    background:radial-gradient(ellipse 70% 60% at 0% 100%, rgba(255,255,255,0.06) 0%, transparent 70%);
    pointer-events:none;
  }
  .ag-section-start { display:flex; align-items:center; gap:14px; position:relative; z-index:1; }
  .ag-section-icon {
    width:44px; height:44px; border-radius:var(--radius-lg); flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    background:rgba(255,255,255,0.15); color:#fff; backdrop-filter:blur(4px);
  }
  .ag-section-icon svg { width:20px; height:20px; }
  .ag-section-title {
    font-size:1.05rem; font-weight:800; color:#fff; margin:0;
    text-shadow:0 1px 2px rgba(0,0,0,0.12);
  }
  .ag-section-desc { font-size:0.72rem; color:rgba(255,255,255,0.7); margin:2px 0 0; }
  .ag-section-date {
    display:flex; align-items:center; gap:6px;
    font-size:0.7rem; font-weight:600; color:rgba(255,255,255,0.85);
    background:rgba(255,255,255,0.12); padding:5px 14px; border-radius:var(--radius-full);
    white-space:nowrap; position:relative; z-index:1; backdrop-filter:blur(4px);
  }
  .ag-section-date svg { width:12px; height:12px; flex-shrink:0; }

  /* ── Filter chips ── */
  .ag-chips { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:14px; }
  .ag-chip {
    display:inline-flex; align-items:center; gap:4px;
    padding:3px 10px; border-radius:var(--radius-2xl); font-size:0.68rem; font-weight:600;
    background:var(--primary-soft); color:var(--primary); cursor:default;
  }
  .ag-chip button {
    display:inline-flex; align-items:center; justify-content:center;
    width:14px; height:14px; border:none; border-radius:var(--radius-full);
    background:transparent; color:var(--primary); cursor:pointer; padding:0;
  }
  .ag-chip button:hover { background:var(--primary); color:#fff; }
  .ag-chip-clear {
    font-size:0.68rem; font-weight:600; color:var(--text-muted);
    background:none; border:none; cursor:pointer; font-family:inherit;
    padding:3px 6px; border-radius:var(--radius-sm);
  }
  .ag-chip-clear:hover { color:var(--danger); }

  /* ── Table toolbar ── */
  .ag-toolbar {
    display:flex; flex-wrap:wrap; align-items:center; gap:8px;
    background:#fff; border-radius:var(--ag-radius) var(--ag-radius) 0 0;
    padding:10px 14px; border-bottom:1px solid var(--border-light);
  }
  .ag-tb-search {
    flex:1; display:flex; align-items:center; gap:6px;
    background:var(--input-bg); border-radius:var(--radius-sm);
    padding:6px 10px; min-width:140px; max-width:260px;
  }
  .ag-tb-search:focus-within { background:var(--surface); }
  .ag-tb-search svg { width:15px; height:15px; color:var(--text-muted); flex-shrink:0; }
  .ag-tb-search input {
    border:none; background:transparent; outline:none;
    font-size:0.78rem; font-family:inherit; color:var(--text-primary); width:100%;
  }
  .ag-tb-search input::placeholder { color:var(--text-muted); }
  .ag-tb-date select {
    appearance:none; padding:5px 28px 5px 10px;
    border:1.5px solid var(--border-light); border-radius:var(--radius-sm);
    font-size:0.72rem; font-weight:600; font-family:inherit;
    color:var(--text-secondary); background:#fff; cursor:pointer;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%23999' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat:no-repeat; background-position:left 8px center; background-size:10px;
  }
  .ag-tb-date select:hover, .ag-tb-date select:focus { border-color:var(--primary); outline:none; }
  .ag-tb-btn {
    display:inline-flex; align-items:center; justify-content:center;
    width:30px; height:30px; border:1.5px solid var(--border-light); border-radius:var(--radius-sm);
    background:#fff; color:var(--text-muted); cursor:pointer; transition:all .12s; flex-shrink:0;
  }
  .ag-tb-btn:hover { border-color:var(--primary); color:var(--primary); }
  .ag-tb-btn--active { background:var(--primary-soft); color:var(--primary); border-color:var(--primary); }
  .ag-tb-btn svg { width:14px; height:14px; }
  .ag-tb-export {
    display:inline-flex; align-items:center; gap:4px;
    padding:4px 10px; border:1.5px solid var(--border-light); border-radius:var(--radius-sm);
    font-size:0.68rem; font-weight:700; font-family:inherit;
    color:var(--text-muted); background:#fff; cursor:pointer; text-decoration:none;
    transition:all .12s; flex-shrink:0;
  }
  .ag-tb-export:hover { border-color:var(--primary); color:var(--primary); }
  .ag-tb-export svg { width:13px; height:13px; }
  /* ── Table ── */
  .ag-table-wrap {
    background:#fff; border-radius:0 0 var(--ag-radius) var(--ag-radius); overflow:hidden;
  }
  .ag-tbl { width:100%; border-collapse:collapse; }
  .ag-tbl th {
    text-align:start; padding:10px 12px;
    font-size:0.68rem; font-weight:700; color:var(--text-muted);
    white-space:nowrap; user-select:none; cursor:pointer;
    border-bottom:1px solid var(--border-light); transition:color .1s;
  }
  .ag-tbl th:hover { color:var(--text-primary); }
  .ag-tbl-sort { font-size:0.55rem; margin-inline-start:3px; }
  .ag-tbl-sorted { color:var(--primary) !important; }
  .ag-row { transition:background .1s; }
  .ag-row:hover { background:var(--input-bg); }
  .ag-row td { padding:6px 12px; vertical-align:middle; border-bottom:1px solid var(--border-light); }

  /* Agent cell */
  .ag-user-wrap { display:flex; align-items:center; gap:10px; }
  .ag-avatar {
    width:36px; height:36px; border-radius:var(--radius-full); flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    font-size:0.72rem; font-weight:800; color:#fff;
    background:linear-gradient(135deg,var(--primary),var(--primary-dark));
  }
  .ag-name {
    display:block; font-size:0.8rem; font-weight:700; color:var(--text-primary);
    text-decoration:none; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
  }
  .ag-name:hover { color:var(--primary); }
  .ag-sub {
    display:block; font-size:0.62rem; color:var(--text-muted); margin-top:1px;
    direction:ltr; text-align:start;
  }

  /* Status cell */
  .ag-status {
    display:inline-flex; align-items:center; gap:5px;
    font-size:0.72rem; font-weight:600; color:var(--text-secondary);
  }
  .ag-dot { width:7px; height:7px; border-radius:var(--radius-full); flex-shrink:0; }
  .ag-dot--active   { background:var(--success); }
  .ag-dot--inactive { background:var(--danger); }
  .ag-dot--featured { background:var(--accent); }

  /* Badge cell (KYC, services) */
  .ag-badge {
    display:inline-flex; align-items:center; gap:3px;
    padding:2px 7px; border-radius:var(--radius-sm);
    font-size:0.62rem; font-weight:600; line-height:1.4;
  }
  .ag-badge--success { background:var(--success-soft, rgba(22,163,74,0.1)); color:var(--success); }
  .ag-badge--warn    { background:rgba(181,138,60,0.1); color:var(--accent); }
  .ag-badge--muted   { background:var(--input-bg); color:var(--text-muted); }
  .ag-badge--danger  { background:var(--danger-light); color:var(--danger); }

  /* Service tags */
  .ag-service { font-size:0.62rem; color:var(--text-muted); }
  .ag-service svg { width:10px; height:10px; vertical-align:-1px; }

  /* Rating */
  .ag-rating { font-size:0.72rem; font-weight:700; color:var(--accent); direction:ltr; }

  /* Actions */
  .ag-actions { display:flex; align-items:center; justify-content:center; gap:2px; opacity:0.3; transition:opacity .15s; }
  .ag-row:hover .ag-actions { opacity:1; }
  .ag-act {
    display:inline-flex; align-items:center; justify-content:center;
    width:26px; height:26px; border:none; border-radius:var(--radius-sm);
    background:transparent; color:var(--text-muted); cursor:pointer;
    text-decoration:none; transition:background .1s,color .1s;
  }
  .ag-act:hover { background:var(--input-bg); color:var(--primary); }
  .ag-act svg { width:12px; height:12px; }

  /* Empty state */
  .ag-empty { text-align:center; padding:0; }
  .ag-empty-inner {
    display:flex; flex-direction:column; align-items:center;
    justify-content:center; padding:48px 24px;
  }
  .ag-empty-icon {
    width:44px; height:44px; border-radius:var(--radius-full);
    display:flex; align-items:center; justify-content:center;
    background:var(--input-bg); color:var(--text-muted); margin-bottom:10px;
  }
  .ag-empty-icon svg { width:20px; height:20px; }
  .ag-empty-title { font-size:0.82rem; font-weight:700; color:var(--text-primary); margin-bottom:3px; }
  .ag-empty-desc { font-size:0.7rem; color:var(--text-muted); max-width:260px; }

  /* Pagination */
  .ag-pager {
    display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between;
    gap:8px; padding:10px 14px;
    background:#fff; border-radius:0 0 var(--ag-radius) var(--ag-radius);
    border-top:1px solid var(--border-light);
  }
  .ag-pager-info { font-size:0.68rem; color:var(--text-muted); font-weight:500; }
  .ag-pager-nav { display:flex; gap:4px; }
  .ag-pager-nav a, .ag-pager-nav span {
    display:inline-flex; align-items:center; justify-content:center;
    min-width:30px; height:30px; padding:0 6px;
    border-radius:var(--radius-sm); font-size:0.72rem; font-weight:600;
    color:var(--text-secondary); text-decoration:none;
  }
  .ag-pager-nav a:hover { background:var(--input-bg); color:var(--primary); }
  .ag-pager-nav span[aria-current=page] { background:var(--primary); color:#fff; }

  /* ── Drawer (slide-over) ── */
  .ag-overlay {
    position:fixed; inset:0; z-index:900; background:rgba(0,0,0,0.25);
    backdrop-filter:blur(2px);
  }
  .ag-drawer {
    position:fixed; top:0; bottom:0; left:0; z-index:901;
    width:320px; max-width:85vw;
    background:#fff; display:flex; flex-direction:column;
    box-shadow:-4px 0 24px rgba(0,0,0,0.08);
    transition:transform .25s cubic-bezier(.22,1,.36,1);
  }
  .ag-drawer[aria-hidden=true] { transform:translateX(-105%); }
  .ag-drawer-head {
    display:flex; align-items:center; justify-content:space-between;
    padding:16px 18px 12px; border-bottom:1px solid var(--border-light);
  }
  .ag-drawer-head h3 { font-size:0.9rem; font-weight:800; color:var(--text-primary); }
  .ag-drawer-close {
    width:30px; height:30px; border:none; border-radius:var(--radius-sm);
    background:transparent; color:var(--text-muted); cursor:pointer;
    display:flex; align-items:center; justify-content:center;
  }
  .ag-drawer-close:hover { background:var(--input-bg); color:var(--text-primary); }
  .ag-drawer-close svg { width:16px; height:16px; }
  .ag-drawer-body { flex:1; overflow-y:auto; padding:16px 18px; }
  .ag-drawer-group { margin-bottom:18px; }
  .ag-drawer-label {
    display:block; font-size:0.68rem; font-weight:700; color:var(--text-secondary);
    margin-bottom:5px; text-transform:uppercase; letter-spacing:0.03em;
  }
  .ag-drawer-select, .ag-drawer-input {
    width:100%; padding:8px 10px; border:1.5px solid var(--border-light);
    border-radius:var(--radius-md); font-size:0.78rem; font-family:inherit;
    background:#fff; color:var(--text-primary);
  }
  .ag-drawer-select:focus, .ag-drawer-input:focus { outline:none; border-color:var(--primary); }
  .ag-drawer-foot {
    display:flex; gap:8px; padding:12px 18px 16px;
    border-top:1px solid var(--border-light);
  }
  .ag-drawer-foot button {
    flex:1; padding:8px; border-radius:var(--radius-md);
    font-size:0.75rem; font-weight:700; font-family:inherit; cursor:pointer;
  }
  .ag-drawer-apply { border:none; background:var(--primary); color:#fff; }
  .ag-drawer-apply:hover { opacity:0.9; }
  .ag-drawer-reset {
    border:1.5px solid var(--border-light); background:transparent; color:var(--text-secondary);
  }
  .ag-drawer-reset:hover { border-color:var(--danger); color:var(--danger); }

  @media (max-width:768px) {
    .ag-toolbar { flex-wrap:wrap; }
    .ag-tb-search { max-width:100%; }
    .ag-drawer { width:85vw; }
    .ag-tbl-owner, .ag-owner-cell { display:none; }
  }
</style>

<div class="ag-scope" x-data="agentsPage">
  {{-- ═══ Section identity card ═══ --}}
  <div class="ag-section-card">
    <div class="ag-section-start">
      <div class="ag-section-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
      </div>
      <div>
        <h3 class="ag-section-title">إدارة الوكلاء</h3>
        <p class="ag-section-desc">عرض وإدارة وكلاء السحب والإيداع النقدي</p>
      </div>
    </div>
    <div class="ag-section-date">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      <span>{{ $dateStr }}</span>
    </div>
  </div>

  {{-- ═══ KPI cards (shared partial) ═══ --}}
  @include('admin.partials._kpi_card_grid', ['ns' => 'agents-kpi'])

  {{-- ═══ Filter chips ═══ --}}
  <div class="ag-chips" x-show="hasActiveFilters" x-cloak>
    <template x-for="chip in activeChips" :key="chip.key">
      <span class="ag-chip">
        <span x-text="chip.label"></span>
        <button @click="removeFilter(chip.key)" aria-label="إزالة">&times;</button>
      </span>
    </template>
    <button class="ag-chip-clear" @click="clearAllFilters()">مسح الكل</button>
  </div>

  {{-- ═══ Table toolbar ═══ --}}
  <div class="ag-toolbar">
    <div class="ag-tb-search">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
      <input type="text" x-model.debounce.300ms="query" @input.debounce.300ms="onSearch"
             placeholder="اسم الوكيل، الكود، المالك، الهاتف…" autocomplete="off">
    </div>
    <div class="ag-tb-date">
      <select x-model="datePreset" @change="onDatePreset">
        <option value="">كل الأوقات</option>
        <option value="today">اليوم</option>
        <option value="7d">آخر 7 أيام</option>
        <option value="30d">آخر 30 يوم</option>
        <option value="90d">آخر 90 يوم</option>
      </select>
    </div>
          <a href="{{ route('admin.agents.export', request()->query()) }}" id="ag-export-link" class="ag-tb-export" title="CSV">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            <span>CSV</span>
          </a>
    <button type="button" class="ag-tb-btn" :class="drawerOpen && 'ag-tb-btn--active'"
            @click="drawerOpen = !drawerOpen" aria-label="فلاتر">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="20" y2="12"/><line x1="12" y1="18" x2="20" y2="18"/></svg>
    </button>

  </div>

  {{-- ═══ Table ═══ --}}
  <div id="ag-results" x-html="tableHtml" aria-live="polite" aria-busy="false">
    @include('admin.agents.partials._table')
  </div>

  {{-- ═══ Drawer ═══ --}}
  <template x-teleport="body">
    <div>
      <div class="ag-overlay" x-show="drawerOpen" x-cloak
           x-transition:enter="transition ease-out duration-200"
           x-transition:enter-start="opacity-0"
           x-transition:enter-end="opacity-100"
           x-transition:leave="transition ease-in duration-150"
           x-transition:leave-start="opacity-100"
           x-transition:leave-end="opacity-0"
           @click="drawerOpen = false"></div>
      <div class="ag-drawer" role="dialog" aria-modal="true" aria-label="فلاتر البحث"
           x-show="drawerOpen" x-cloak
           x-transition:enter="transition ease-out duration-250"
           x-transition:enter-start="-translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-200"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="-translate-x-full">
        <div class="ag-drawer-head">
          <h3>فلاتر البحث</h3>
          <button type="button" class="ag-drawer-close" @click="drawerOpen = false" aria-label="إغلاق">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div class="ag-drawer-body">
          <div class="ag-drawer-group">
            <label class="ag-drawer-label">الحالة</label>
            <select class="ag-drawer-select" x-model="filters.status" @change="onFilterChange">
              <option value="">الكل</option>
              <option value="active">نشط</option>
              <option value="inactive">معطل</option>
              <option value="featured">مميز</option>
            </select>
          </div>
          <div class="ag-drawer-group">
            <label class="ag-drawer-label">الخدمة</label>
            <select class="ag-drawer-select" x-model="filters.service" @change="onFilterChange">
              <option value="">الكل</option>
              <option value="cash_in">إيداع نقدي</option>
              <option value="cash_out">سحب نقدي</option>
            </select>
          </div>
          <div class="ag-drawer-group">
            <label class="ag-drawer-label">المدينة</label>
            <select class="ag-drawer-select" x-model="filters.city" @change="onFilterChange">
              <option value="">كل المدن</option>
              @foreach($cities ?? [] as $city)
              <option value="{{ $city }}">{{ $city }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="ag-drawer-foot">
          <button type="button" class="ag-drawer-reset" @click="clearAllFilters()">إعادة ضبط</button>
          <button type="button" class="ag-drawer-apply" @click="drawerOpen = false">تطبيق</button>
        </div>
      </div>
    </div>
  </template>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('agentsPage', () => ({
    query: '{{ request('search') }}',
    filters: {
      status:  '{{ request('status') }}',
      service: '{{ request('service') }}',
      city:    '{{ request('city') }}',
    },
    sortCol: '{{ request('sort', 'created_at') }}',
    sortDir: '{{ request('dir', 'desc') }}',
    dateFrom: '{{ request('date_from') }}',
    dateTo: '{{ request('date_to') }}',
    kpis: null,
    drawerOpen: false,
    datePreset: '',
    tableHtml: '',

    get hasActiveFilters() {
      return Object.values(this.filters).some(v => v !== '') || this.query !== '';
    },
    get activeChips() {
      const chips = [];
      if (this.query) chips.push({ key:'search', label:'بحث: "'+this.query+'"' });
      const map = { status:'الحالة: ', service:'الخدمة: ', city:'المدينة: ' };
      const valMap = {
        status:{ active:'نشط', inactive:'معطل', featured:'مميز' },
        service:{ cash_in:'إيداع نقدي', cash_out:'سحب نقدي' },
      };
      for (const [k, v] of Object.entries(this.filters)) {
        if (v === '') continue;
        chips.push({ key:k, label: (map[k]||k+': ') + ((valMap[k]&&valMap[k][v])||v) });
      }
      return chips;
    },
    get kpiCards() {
      if (!this.kpis) return [];
      const k = this.kpis;
      return [
        { iconSvg:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>', bg:'rgba(107,15,36,0.08)', color:'var(--sukk-primary)', label:'إجمالي الوكلاء', value:Number(k.total).toLocaleString()+' <span class="dash4-kpi-sub">وكيل</span>', changeText:'', changeDir:'up', sparkSvg:this._spark([47,63,38,72,55,80,68],'#6E1B2D') },
        { iconSvg:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><polyline points="20 6 9 17 4 12"/></svg>', bg:'rgba(31,157,85,0.1)', color:'var(--success)', label:'نشط', value:Number(k.active).toLocaleString()+' <span class="dash4-kpi-sub">وكيل</span>', changeText:k.active+'/'+k.total, changeDir:'up', sparkSvg:this._spark([49,63,41,58,34,49,54],'#16A34A') },
        { iconSvg:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>', bg:'rgba(181,138,60,0.1)', color:'var(--accent)', label:'KYC معلّق', value:Number(k.pending_kyc).toLocaleString()+' <span class="dash4-kpi-sub">طلب</span>', changeText:'قيد المراجعة', changeDir:'up', sparkSvg:this._spark([22,35,18,42,30,48,26],'#B58A3C') },
        { iconSvg:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>', bg:'rgba(181,138,60,0.08)', color:'var(--accent)', label:'مميز', value:Number(k.featured).toLocaleString()+' <span class="dash4-kpi-sub">وكيل</span>', changeText:'', changeDir:'up', sparkSvg:this._spark([35,52,44,68,40,58,70],'#B58A3C') },
      ];
    },
    _spark(h, c) {
      return h.map(v => '<div class="dash4-spark-bar" style="background:'+c+';height:'+v+'%;"></div>').join('');
    },

    init() {
      this.loadKpis();
      this.$nextTick(() => {
        const el = document.getElementById('ag-results');
        if (el) {
          el.addEventListener('click', (e) => {
            const link = e.target.closest('.ag-page-link');
            if (link) {
              e.preventDefault();
              const url = new URL(link.href);
              this._fetchResults(url.searchParams.get('page') || '1');
            }
          });
        }
      });
      window.addEventListener('users-changed', () => {
        this.loadKpis();
        this._fetchResults();
      });
      this._fetchResults();
    },

    onSearch() { this._fetchResults(); },
    onFilterChange() { this._fetchResults(); },

    onDatePreset() {
      const now = new Date();
      const fmt = d => d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');
      if (!this.datePreset) { this.dateFrom = ''; this.dateTo = ''; this._fetchResults(); return; }
      const d = new Date(now);
      switch(this.datePreset) {
        case 'today': break;
        case '7d': d.setDate(d.getDate()-7); break;
        case '30d': d.setDate(d.getDate()-30); break;
        case '90d': d.setDate(d.getDate()-90); break;
      }
      this.dateFrom = fmt(d);
      this.dateTo = fmt(now);
      this._fetchResults();
    },

    removeFilter(key) {
      if (key === 'search') this.query = '';
      else this.filters[key] = '';
      this._fetchResults();
    },
    clearAllFilters() {
      this.query = '';
      for (const k in this.filters) this.filters[k] = '';
      this.datePreset = '';
      this.dateFrom = '';
      this.dateTo = '';
      this._fetchResults();
    },

    sortBy(col) {
      if (this.sortCol === col) this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
      else { this.sortCol = col; this.sortDir = 'asc'; }
      this._fetchResults();
    },
    sortIcon(col) {
      if (this.sortCol !== col) return '↕';
      return this.sortDir === 'asc' ? '↑' : '↓';
    },
    isSorted(col) { return this.sortCol === col; },

    async loadKpis() {
      try {
        const r = await fetch('{{ route('admin.agents.kpis') }}', {
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        });
        if (!r.ok) return;
        this.kpis = await r.json();
      } catch (e) { /* silent */ }
    },

    async _fetchResults(page) {
      try {
        const params = new URLSearchParams();
        if (this.query) params.set('search', this.query);
        if (this.filters.status) params.set('status', this.filters.status);
        if (this.filters.service) params.set('service', this.filters.service);
        if (this.filters.city) params.set('city', this.filters.city);
        params.set('sort', this.sortCol);
        params.set('dir', this.sortDir);
        if (page) params.set('page', page);
        if (this.dateFrom) params.set('date_from', this.dateFrom);
        if (this.dateTo) params.set('date_to', this.dateTo);
        params.set('fragment', '1');
        const r = await fetch('{{ route('admin.agents.index') }}?' + params.toString(), {
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!r.ok) return;
        this.tableHtml = await r.text();
        this.$nextTick(() => { const el = document.getElementById('ag-results'); if (el) Alpine.initTree(el); });
        history.replaceState({}, '', window.location.pathname + '?' + params.toString());
        const agExportLink = document.getElementById('ag-export-link');
        if (agExportLink) {
            const exportUrl = new URL('{{ route('admin.agents.export') }}', location.origin);
            agExportLink.href = exportUrl.pathname + (qs => qs ? '?' + qs : '')(params.toString());
        }
      } catch(e) {}
    },
  }));
});
</script>
@endpush
