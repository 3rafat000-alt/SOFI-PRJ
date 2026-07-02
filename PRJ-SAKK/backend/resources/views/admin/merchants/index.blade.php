@extends('layouts.admin')

@section('title', 'التجار')
@section('breadcrumbs')
<span class="breadcrumb-item">التجار</span>
<span class="breadcrumb-item">جميع التجار</span>
@endsection

@php
  $now     = \Carbon\Carbon::now();
  $dayAr   = ['الأحد','الإثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
  $monthAr = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
  $dateStr = $dayAr[$now->dayOfWeek] . '، ' . $now->format('j') . ' ' . $monthAr[$now->month - 1] . ' ' . $now->format('Y');
@endphp

@section('content')
<style>
  .mr-scope *,
  .mr-scope *::before,
  .mr-scope *::after { box-sizing:border-box; }
  .mr-scope { --mr-radius:var(--radius-lg); }

  .mr-section-card {
    background:linear-gradient(135deg, var(--primary-dark), var(--primary));
    border-radius:var(--radius-xl); padding:18px 22px;
    display:flex; align-items:center; justify-content:space-between; gap:16px;
    flex-wrap:wrap; margin-bottom:16px;
    position:relative; overflow:hidden;
  }
  .mr-section-card::after {
    content:''; position:absolute; inset:0;
    background:radial-gradient(ellipse 70% 60% at 0% 100%, rgba(255,255,255,0.06) 0%, transparent 70%);
    pointer-events:none;
  }
  .mr-section-start { display:flex; align-items:center; gap:14px; position:relative; z-index:1; }
  .mr-section-icon {
    width:44px; height:44px; border-radius:var(--radius-lg); flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    background:rgba(255,255,255,0.15); color:#fff; backdrop-filter:blur(4px);
  }
  .mr-section-icon svg { width:20px; height:20px; }
  .mr-section-title {
    font-size:1.05rem; font-weight:800; color:#fff; margin:0;
    text-shadow:0 1px 2px rgba(0,0,0,0.12);
  }
  .mr-section-desc { font-size:0.72rem; color:rgba(255,255,255,0.7); margin:2px 0 0; }
  .mr-section-date {
    display:flex; align-items:center; gap:6px;
    font-size:0.7rem; font-weight:600; color:rgba(255,255,255,0.85);
    background:rgba(255,255,255,0.12); padding:5px 14px; border-radius:var(--radius-full);
    white-space:nowrap; position:relative; z-index:1; backdrop-filter:blur(4px);
  }
  .mr-section-date svg { width:12px; height:12px; flex-shrink:0; }

  .mr-chips { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:14px; }
  .mr-chip {
    display:inline-flex; align-items:center; gap:4px;
    padding:3px 10px; border-radius:var(--radius-2xl); font-size:0.68rem; font-weight:600;
    background:var(--primary-soft); color:var(--primary); cursor:default;
  }
  .mr-chip button {
    display:inline-flex; align-items:center; justify-content:center;
    width:14px; height:14px; border:none; border-radius:var(--radius-full);
    background:transparent; color:var(--primary); cursor:pointer; padding:0;
  }
  .mr-chip button:hover { background:var(--primary); color:#fff; }
  .mr-chip-clear {
    font-size:0.68rem; font-weight:600; color:var(--text-muted);
    background:none; border:none; cursor:pointer; font-family:inherit;
    padding:3px 6px; border-radius:var(--radius-sm);
  }
  .mr-chip-clear:hover { color:var(--danger); }

  .mr-toolbar {
    display:flex; flex-wrap:wrap; align-items:center; gap:8px;
    background:#fff; border-radius:var(--mr-radius) var(--mr-radius) 0 0;
    padding:10px 14px; border-bottom:1px solid var(--border-light);
  }
  .mr-tb-search {
    flex:1; display:flex; align-items:center; gap:6px;
    background:var(--input-bg); border-radius:var(--radius-sm);
    padding:6px 10px; min-width:140px; max-width:260px;
  }
  .mr-tb-search:focus-within { background:var(--surface); }
  .mr-tb-search svg { width:15px; height:15px; color:var(--text-muted); flex-shrink:0; }
  .mr-tb-search input {
    border:none; background:transparent; outline:none;
    font-size:0.78rem; font-family:inherit; color:var(--text-primary); width:100%;
  }
  .mr-tb-search input::placeholder { color:var(--text-muted); }
  .mr-tb-date select {
    appearance:none; padding:5px 28px 5px 10px;
    border:1.5px solid var(--border-light); border-radius:var(--radius-sm);
    font-size:0.72rem; font-weight:600; font-family:inherit;
    color:var(--text-secondary); background:#fff; cursor:pointer;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%23999' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat:no-repeat; background-position:left 8px center; background-size:10px;
  }
  .mr-tb-date select:hover, .mr-tb-date select:focus { border-color:var(--primary); outline:none; }
  .mr-tb-btn {
    display:inline-flex; align-items:center; justify-content:center;
    width:30px; height:30px; border:1.5px solid var(--border-light); border-radius:var(--radius-sm);
    background:#fff; color:var(--text-muted); cursor:pointer; transition:all .12s; flex-shrink:0;
  }
  .mr-tb-btn:hover { border-color:var(--primary); color:var(--primary); }
  .mr-tb-btn--active { background:var(--primary-soft); color:var(--primary); border-color:var(--primary); }
  .mr-tb-btn svg { width:14px; height:14px; }
  .mr-tb-export {
    display:inline-flex; align-items:center; gap:4px;
    padding:4px 10px; border:1.5px solid var(--border-light); border-radius:var(--radius-sm);
    font-size:0.68rem; font-weight:700; font-family:inherit;
    color:var(--text-muted); background:#fff; cursor:pointer; text-decoration:none;
    transition:all .12s; flex-shrink:0;
  }
  .mr-tb-export:hover { border-color:var(--primary); color:var(--primary); }
  .mr-tb-export svg { width:13px; height:13px; }
  .mr-table-wrap {
    background:#fff; border-radius:0 0 var(--mr-radius) var(--mr-radius); overflow:hidden;
  }
  .mr-tbl { width:100%; border-collapse:collapse; }
  .mr-tbl th {
    text-align:start; padding:10px 12px;
    font-size:0.68rem; font-weight:700; color:var(--text-muted);
    white-space:nowrap; user-select:none; cursor:pointer;
    border-bottom:1px solid var(--border-light); transition:color .1s;
  }
  .mr-tbl th:hover { color:var(--text-primary); }
  .mr-tbl-sort { font-size:0.55rem; margin-inline-start:3px; }
  .mr-tbl-sorted { color:var(--primary) !important; }
  .mr-row { transition:background .1s; }
  .mr-row:hover { background:var(--input-bg); }
  .mr-row td { padding:6px 12px; vertical-align:middle; border-bottom:1px solid var(--border-light); }

  .mr-user-wrap { display:flex; align-items:center; gap:10px; }
  .mr-avatar {
    width:36px; height:36px; border-radius:var(--radius-full); flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    font-size:0.72rem; font-weight:800; color:#fff;
    background:linear-gradient(135deg,var(--primary),var(--primary-dark));
  }
  .mr-name {
    display:block; font-size:0.8rem; font-weight:700; color:var(--text-primary);
    text-decoration:none; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
  }
  .mr-name:hover { color:var(--primary); }
  .mr-sub {
    display:block; font-size:0.62rem; color:var(--text-muted); margin-top:1px;
  }
  .mr-status {
    display:inline-flex; align-items:center; gap:5px;
    font-size:0.72rem; font-weight:600; color:var(--text-secondary);
  }
  .mr-dot { width:7px; height:7px; border-radius:var(--radius-full); flex-shrink:0; }
  .mr-dot--active   { background:var(--success); }
  .mr-dot--inactive { background:var(--danger); }
  .mr-dot--verified { background:var(--accent); }

  .mr-badge {
    display:inline-flex; align-items:center; gap:3px;
    padding:2px 7px; border-radius:var(--radius-sm);
    font-size:0.62rem; font-weight:600; line-height:1.4;
  }
  .mr-badge--success { background:rgba(22,163,74,0.1); color:var(--success); }
  .mr-badge--warn    { background:rgba(181,138,60,0.1); color:var(--accent); }
  .mr-badge--muted   { background:var(--input-bg); color:var(--text-muted); }
  .mr-badge--danger  { background:var(--danger-light); color:var(--danger); }

  .mr-type {
    display:inline-flex; align-items:center; gap:3px;
    padding:2px 7px; border-radius:var(--radius-sm);
    font-size:0.62rem; font-weight:600; background:var(--primary-soft); color:var(--primary);
  }

  .mr-actions { display:flex; align-items:center; justify-content:center; gap:2px; opacity:0.3; transition:opacity .15s; }
  .mr-row:hover .mr-actions { opacity:1; }
  .mr-act {
    display:inline-flex; align-items:center; justify-content:center;
    width:26px; height:26px; border:none; border-radius:var(--radius-sm);
    background:transparent; color:var(--text-muted); cursor:pointer;
    text-decoration:none; transition:background .1s,color .1s;
  }
  .mr-act:hover { background:var(--input-bg); color:var(--primary); }
  .mr-act svg { width:12px; height:12px; }

  .mr-empty { text-align:center; padding:0; }
  .mr-empty-inner {
    display:flex; flex-direction:column; align-items:center;
    justify-content:center; padding:48px 24px;
  }
  .mr-empty-icon {
    width:44px; height:44px; border-radius:var(--radius-full);
    display:flex; align-items:center; justify-content:center;
    background:var(--input-bg); color:var(--text-muted); margin-bottom:10px;
  }
  .mr-empty-icon svg { width:20px; height:20px; }
  .mr-empty-title { font-size:0.82rem; font-weight:700; color:var(--text-primary); margin-bottom:3px; }
  .mr-empty-desc { font-size:0.7rem; color:var(--text-muted); max-width:260px; }

  .mr-pager {
    display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between;
    gap:8px; padding:10px 14px;
    background:#fff; border-radius:0 0 var(--mr-radius) var(--mr-radius);
    border-top:1px solid var(--border-light);
  }
  .mr-pager-info { font-size:0.68rem; color:var(--text-muted); font-weight:500; }
  .mr-pager-nav { display:flex; gap:4px; }
  .mr-pager-nav a, .mr-pager-nav span {
    display:inline-flex; align-items:center; justify-content:center;
    min-width:30px; height:30px; padding:0 6px;
    border-radius:var(--radius-sm); font-size:0.72rem; font-weight:600;
    color:var(--text-secondary); text-decoration:none;
  }
  .mr-pager-nav a:hover { background:var(--input-bg); color:var(--primary); }
  .mr-pager-nav span[aria-current=page] { background:var(--primary); color:#fff; }

  .mr-overlay {
    position:fixed; inset:0; z-index:900; background:rgba(0,0,0,0.25); backdrop-filter:blur(2px);
  }
  .mr-drawer {
    position:fixed; top:0; bottom:0; left:0; z-index:901;
    width:320px; max-width:85vw;
    background:#fff; display:flex; flex-direction:column;
    box-shadow:-4px 0 24px rgba(0,0,0,0.08);
    transition:transform .25s cubic-bezier(.22,1,.36,1);
  }
  .mr-drawer[aria-hidden=true] { transform:translateX(-105%); }
  .mr-drawer-head {
    display:flex; align-items:center; justify-content:space-between;
    padding:16px 18px 12px; border-bottom:1px solid var(--border-light);
  }
  .mr-drawer-head h3 { font-size:0.9rem; font-weight:800; color:var(--text-primary); }
  .mr-drawer-close {
    width:30px; height:30px; border:none; border-radius:var(--radius-sm);
    background:transparent; color:var(--text-muted); cursor:pointer;
    display:flex; align-items:center; justify-content:center;
  }
  .mr-drawer-close:hover { background:var(--input-bg); color:var(--text-primary); }
  .mr-drawer-close svg { width:16px; height:16px; }
  .mr-drawer-body { flex:1; overflow-y:auto; padding:16px 18px; }
  .mr-drawer-group { margin-bottom:18px; }
  .mr-drawer-label {
    display:block; font-size:0.68rem; font-weight:700; color:var(--text-secondary);
    margin-bottom:5px; text-transform:uppercase; letter-spacing:0.03em;
  }
  .mr-drawer-select {
    width:100%; padding:8px 10px; border:1.5px solid var(--border-light);
    border-radius:var(--radius-md); font-size:0.78rem; font-family:inherit;
    background:#fff; color:var(--text-primary);
  }
  .mr-drawer-select:focus { outline:none; border-color:var(--primary); }
  .mr-drawer-foot {
    display:flex; gap:8px; padding:12px 18px 16px;
    border-top:1px solid var(--border-light);
  }
  .mr-drawer-foot button {
    flex:1; padding:8px; border-radius:var(--radius-md);
    font-size:0.75rem; font-weight:700; font-family:inherit; cursor:pointer;
  }
  .mr-drawer-apply { border:none; background:var(--primary); color:#fff; }
  .mr-drawer-apply:hover { opacity:0.9; }
  .mr-drawer-reset {
    border:1.5px solid var(--border-light); background:transparent; color:var(--text-secondary);
  }
  .mr-drawer-reset:hover { border-color:var(--danger); color:var(--danger); }

  @media (max-width:768px) {
    .mr-toolbar { flex-wrap:wrap; }
    .mr-tb-search { max-width:100%; }
    .mr-drawer { width:85vw; }
  }

  .mr-results-busy { opacity:0.5; transition:opacity .15s; }
</style>

<form method="GET" action="{{ route('admin.merchants.index') }}" id="mr-filter-form" hidden aria-hidden="true">
  <input type="text" name="search"  value="{{ request('search') }}">
  <input type="text" name="type"    value="{{ request('type') }}">
  <input type="text" name="status"  value="{{ request('status') }}">
  @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
  @if(request('dir'))<input type="hidden" name="dir" value="{{ request('dir') }}">@endif
</form>

<div class="mr-scope" x-data="merchantsPage">
  {{-- ═══ Section identity card ═══ --}}
  <div class="mr-section-card">
    <div class="mr-section-start">
      <div class="mr-section-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      </div>
      <div>
        <h3 class="mr-section-title">إدارة التجار</h3>
        <p class="mr-section-desc">عرض وإدارة التجار ونظام الدفع عبر API</p>
      </div>
    </div>
    <div class="mr-section-date">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      <span>{{ $dateStr }}</span>
    </div>
  </div>

  {{-- ═══ KPI cards (shared partial) ═══ --}}
  @include('admin.partials._kpi_card_grid', ['ns' => 'merchants-kpi'])

  {{-- ═══ Filter chips ═══ --}}
  <div class="mr-chips" x-show="hasActiveFilters" x-cloak>
    <template x-for="chip in activeChips" :key="chip.key">
      <span class="mr-chip">
        <span x-text="chip.label"></span>
        <button @click="removeFilter(chip.key)" aria-label="إزالة">&times;</button>
      </span>
    </template>
    <button class="mr-chip-clear" @click="clearAllFilters()">مسح الكل</button>
  </div>

  {{-- ═══ Table toolbar ═══ --}}
  <div class="mr-toolbar">
    <div class="mr-tb-search">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
      <input type="text" x-model.debounce.300ms="query" @input.debounce.300ms="onSearch"
             placeholder="اسم المتجر، الكود، المالك، البريد، الهاتف…" autocomplete="off">
    </div>
    <div class="mr-tb-date">
      <select x-model="datePreset" @change="onDatePreset">
        <option value="">كل الأوقات</option>
        <option value="today">اليوم</option>
        <option value="7d">آخر 7 أيام</option>
        <option value="30d">آخر 30 يوم</option>
        <option value="90d">آخر 90 يوم</option>
      </select>
    </div>
    <a href="{{ route('admin.merchants.export', request()->query()) }}" id="mr-export-link" class="mr-tb-export" title="CSV">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      <span>CSV</span>
    </a>
    <button type="button" class="mr-tb-btn" :class="drawerOpen && 'mr-tb-btn--active'"
            @click="drawerOpen = !drawerOpen" aria-label="فلاتر">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="20" y2="12"/><line x1="12" y1="18" x2="20" y2="18"/></svg>
    </button>

  </div>

  {{-- ═══ Table (AJAX target) ═══ --}}
  <div id="mr-results" x-html="tableHtml" aria-live="polite" aria-busy="false">
    @include('admin.merchants.partials._table')
  </div>

  {{-- ═══ Drawer ═══ --}}
  <template x-teleport="body">
    <div>
      <div class="mr-overlay" x-show="drawerOpen" x-cloak
           x-transition:enter="transition ease-out duration-200"
           x-transition:enter-start="opacity-0"
           x-transition:enter-end="opacity-100"
           x-transition:leave="transition ease-in duration-150"
           x-transition:leave-start="opacity-100"
           x-transition:leave-end="opacity-0"
           @click="drawerOpen = false"></div>
      <div class="mr-drawer" role="dialog" aria-modal="true" aria-label="فلاتر"
           x-show="drawerOpen" x-cloak
           x-transition:enter="transition ease-out duration-250"
           x-transition:enter-start="-translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-200"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="-translate-x-full">
        <div class="mr-drawer-head">
          <h3>فلاتر البحث</h3>
          <button type="button" class="mr-drawer-close" @click="drawerOpen = false">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div class="mr-drawer-body">
          <div class="mr-drawer-group">
            <label class="mr-drawer-label">النوع</label>
            <select class="mr-drawer-select" x-model="filters.type" @change="onFilterChange">
              <option value="">الكل</option>
              <option value="physical">متجر فعلي</option>
              <option value="ecommerce">متجر إلكتروني</option>
              <option value="both">فعلي + إلكتروني</option>
            </select>
          </div>
          <div class="mr-drawer-group">
            <label class="mr-drawer-label">الحالة</label>
            <select class="mr-drawer-select" x-model="filters.status" @change="onFilterChange">
              <option value="">الكل</option>
              <option value="active">نشط</option>
              <option value="inactive">معطّل</option>
              <option value="verified">موثّق</option>
              <option value="unverified">غير موثّق</option>
            </select>
          </div>
        </div>
        <div class="mr-drawer-foot">
          <button type="button" class="mr-drawer-reset" @click="clearAllFilters()">إعادة ضبط</button>
          <button type="button" class="mr-drawer-apply" @click="drawerOpen = false">تطبيق</button>
        </div>
      </div>
    </div>
  </template>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('merchantsPage', () => ({
    query: '{{ request('search') }}',
    filters: { type: '{{ request('type') }}', status: '{{ request('status') }}' },
    sortCol: '{{ request('sort', 'created_at') }}',
    sortDir: '{{ request('dir', 'desc') }}',
    kpis: null, drawerOpen: false, datePreset: '',
    tableHtml: '',

    get hasActiveFilters() {
      return Object.values(this.filters).some(v => v !== '') || this.query !== '';
    },
    get activeChips() {
      const chips = [];
      if (this.query) chips.push({ key:'search', label:'بحث: "'+this.query+'"' });
      const valMap = { type:{ physical:'متجر فعلي', ecommerce:'متجر إلكتروني', both:'فعلي+إلكتروني' }, status:{ active:'نشط', inactive:'معطّل', verified:'موثّق', unverified:'غير موثّق' } };
      for (const [k, v] of Object.entries(this.filters)) {
        if (v === '') continue;
        chips.push({ key:k, label: (k==='type'?'النوع: ':'الحالة: ') + ((valMap[k]&&valMap[k][v])||v) });
      }
      return chips;
    },
    get kpiCards() {
      if (!this.kpis) return [];
      const k = this.kpis;
      return [
        { iconSvg:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>', bg:'rgba(107,15,36,0.08)', color:'var(--sukk-primary)', label:'إجمالي التجار', value:Number(k.total).toLocaleString()+' <span class="dash4-kpi-sub">تاجر</span>', changeText:'', changeDir:'up', sparkSvg:this._spark([47,63,38,72,55,80,68],'#6E1B2D') },
        { iconSvg:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><polyline points="20 6 9 17 4 12"/></svg>', bg:'rgba(31,157,85,0.1)', color:'var(--success)', label:'نشط', value:Number(k.active).toLocaleString()+' <span class="dash4-kpi-sub">تاجر</span>', changeText:k.active+'/'+k.total, changeDir:'up', sparkSvg:this._spark([49,63,41,58,34,49,54],'#16A34A') },
        { iconSvg:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>', bg:'rgba(181,138,60,0.1)', color:'var(--accent)', label:'KYC معلّق', value:Number(k.pending_kyc).toLocaleString()+' <span class="dash4-kpi-sub">طلب</span>', changeText:'قيد المراجعة', changeDir:'up', sparkSvg:this._spark([22,35,18,42,30,48,26],'#B58A3C') },
        { iconSvg:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>', bg:'rgba(31,157,85,0.08)', color:'var(--success)', label:'موثّق', value:Number(k.verified).toLocaleString()+' <span class="dash4-kpi-sub">تاجر</span>', changeText:'', changeDir:'up', sparkSvg:this._spark([35,52,44,68,40,58,70],'#16A34A') },
      ];
    },
    _spark(h, c) { return h.map(v => '<div class="dash4-spark-bar" style="background:'+c+';height:'+v+'%;"></div>').join(''); },

    init() {
      this.loadKpis();
      window.addEventListener('merchants-changed', () => { this.loadKpis(); this._fetchResults(); });
      this._fetchResults();
    },

    onSearch() { this._syncAllFilters(); this._fetchResults(); },
    onFilterChange() { this._syncAllFilters(); this._fetchResults(); },
    onDatePreset() {
      if (!this.datePreset) { this._syncAllFilters(); this._fetchResults(); return; }
      const now = new Date(), fmt = d => d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');
      const d = new Date(now);
      switch(this.datePreset) { case '7d': d.setDate(d.getDate()-7); break; case '30d': d.setDate(d.getDate()-30); break; case '90d': d.setDate(d.getDate()-90); break; }
      this._syncHidden('date_from', fmt(d)); this._syncHidden('date_to', fmt(now)); this._fetchResults();
    },
    removeFilter(key) {
      if (key==='search') { this.query=''; this._syncHidden('search',''); }
      else { this.filters[key]=''; this._syncHidden(key,''); }
      this._fetchResults();
    },
    clearAllFilters() {
      this.query=''; for(const k in this.filters) this.filters[k]='';
      const f=document.getElementById('mr-filter-form'); if(f) f.querySelectorAll('input,select').forEach(el=>el.value='');
      this._fetchResults();
    },
    sortBy(col) {
      if(this.sortCol===col) this.sortDir=this.sortDir==='asc'?'desc':'asc';
      else { this.sortCol=col; this.sortDir='asc'; }
      this._syncHidden('sort',this.sortCol); this._syncHidden('dir',this.sortDir);
      this._fetchResults();
    },
    sortIcon(col) { if(this.sortCol!==col) return '↕'; return this.sortDir==='asc'?'↑':'↓'; },

    async loadKpis() {
      try { const r = await fetch('{{ route('admin.merchants.kpis') }}', { headers:{ 'X-Requested-With':'XMLHttpRequest','Accept':'application/json' } }); if(!r.ok) return; this.kpis = await r.json(); } catch(e) {}
    },
    _syncHidden(key, value) { const f=document.getElementById('mr-filter-form'); if(!f) return; let el=f.querySelector(`[name="${key}"]`); if(!el){el=document.createElement('input');el.type='hidden';el.name=key;f.appendChild(el);} el.value=value; },
    _syncAllFilters() { this._syncHidden('search',this.query); for(const[k,v]of Object.entries(this.filters)) this._syncHidden(k,v); this._syncHidden('sort',this.sortCol); this._syncHidden('dir',this.sortDir); },
    _buildQs() {
      const form = document.getElementById('mr-filter-form');
      if (!form) return '';
      const data = new FormData(form);
      const params = new URLSearchParams();
      for (const [k, v] of data.entries()) if (v !== '') params.append(k, v);
      return params.toString();
    },
    _fetchResults() {
      const qs = this._buildQs();
      this._fetchUrl('{{ route('admin.merchants.index') }}' + (qs ? '?' + qs : ''));
    },
    async _fetchUrl(url) {
      const region = document.getElementById('mr-results');
      if (!region) return;
      region.setAttribute('aria-busy', 'true');

      try {
        const r = await fetch(url, {
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
        });
        if (!r.ok) { window.location.reload(); return; }
        region.innerHTML = await r.text();
        if (window.Alpine) Alpine.initTree(region);
        history.replaceState(null, '', url);
      } catch (e) { window.location.reload(); }
      finally {
        region.setAttribute('aria-busy', 'false');
        const mrExportLink = document.getElementById('mr-export-link');
        if (mrExportLink) {
            const exportUrl = new URL('{{ route('admin.merchants.export') }}', location.origin);
            mrExportLink.href = exportUrl.pathname + (qs => qs ? '?' + qs : '')(this._buildQs());
        }
      }
    },
  }));
});
</script>
@endpush
