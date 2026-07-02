@extends('layouts.admin')

@section('title', 'الشركات')
@section('breadcrumbs')
<span class="breadcrumb-item">الشركات</span>
<span class="breadcrumb-item">جميع الشركات</span>
@endsection

@php
  $now     = \Carbon\Carbon::now();
  $dayAr   = ['الأحد','الإثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
  $monthAr = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
  $dateStr = $dayAr[$now->dayOfWeek] . '، ' . $now->format('j') . ' ' . $monthAr[$now->month - 1] . ' ' . $now->format('Y');
@endphp

@section('content')
<style>
  .cp-scope *,
  .cp-scope *::before,
  .cp-scope *::after { box-sizing:border-box; }
  .cp-scope { --cp-radius:var(--radius-lg); }

  .cp-section-card {
    background:linear-gradient(135deg, var(--primary-dark), var(--primary));
    border-radius:var(--radius-xl); padding:18px 22px;
    display:flex; align-items:center; justify-content:space-between; gap:16px;
    flex-wrap:wrap; margin-bottom:16px; position:relative; overflow:hidden;
  }
  .cp-section-card::after {
    content:''; position:absolute; inset:0;
    background:radial-gradient(ellipse 70% 60% at 0% 100%, rgba(255,255,255,0.06) 0%, transparent 70%);
    pointer-events:none;
  }
  .cp-section-start { display:flex; align-items:center; gap:14px; position:relative; z-index:1; }
  .cp-section-icon {
    width:44px; height:44px; border-radius:var(--radius-lg); flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    background:rgba(255,255,255,0.15); color:#fff; backdrop-filter:blur(4px);
  }
  .cp-section-icon svg { width:20px; height:20px; }
  .cp-section-title { font-size:1.05rem; font-weight:800; color:#fff; margin:0; text-shadow:0 1px 2px rgba(0,0,0,0.12); }
  .cp-section-desc { font-size:0.72rem; color:rgba(255,255,255,0.7); margin:2px 0 0; }
  .cp-section-date {
    display:flex; align-items:center; gap:6px;
    font-size:0.7rem; font-weight:600; color:rgba(255,255,255,0.85);
    background:rgba(255,255,255,0.12); padding:5px 14px; border-radius:var(--radius-full);
    white-space:nowrap; backdrop-filter:blur(4px);
  }
  .cp-section-date svg { width:12px; height:12px; }

  .cp-chips { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:14px; }
  .cp-chip {
    display:inline-flex; align-items:center; gap:4px;
    padding:3px 10px; border-radius:var(--radius-2xl); font-size:0.68rem; font-weight:600;
    background:var(--primary-soft); color:var(--primary); cursor:default;
  }
  .cp-chip button { display:inline-flex;align-items:center;justify-content:center;width:14px;height:14px;border:none;border-radius:var(--radius-full);background:transparent;color:var(--primary);cursor:pointer;padding:0; }
  .cp-chip button:hover { background:var(--primary);color:#fff; }
  .cp-chip-clear { font-size:0.68rem;font-weight:600;color:var(--text-muted);background:none;border:none;cursor:pointer;font-family:inherit;padding:3px 6px;border-radius:var(--radius-sm); }
  .cp-chip-clear:hover { color:var(--danger); }

  .cp-toolbar {
    display:flex;flex-wrap:wrap;align-items:center;gap:8px;
    background:#fff;border-radius:var(--cp-radius) var(--cp-radius) 0 0;
    padding:10px 14px;border-bottom:1px solid var(--border-light);
  }
  .cp-tb-search {
    flex:1;display:flex;align-items:center;gap:6px;
    background:var(--input-bg);border-radius:var(--radius-sm);
    padding:6px 10px;min-width:140px;max-width:260px;
  }
  .cp-tb-search:focus-within { background:var(--surface); }
  .cp-tb-search svg { width:15px;height:15px;color:var(--text-muted);flex-shrink:0; }
  .cp-tb-search input { border:none;background:transparent;outline:none;font-size:0.78rem;font-family:inherit;color:var(--text-primary);width:100%; }
  .cp-tb-search input::placeholder { color:var(--text-muted); }
  .cp-tb-date select {
    appearance:none;padding:5px 28px 5px 10px;
    border:1.5px solid var(--border-light);border-radius:var(--radius-sm);
    font-size:0.72rem;font-weight:600;font-family:inherit;
    color:var(--text-secondary);background:#fff;cursor:pointer;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%23999' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat:no-repeat;background-position:left 8px center;background-size:10px;
  }
  .cp-tb-date select:hover, .cp-tb-date select:focus { border-color:var(--primary);outline:none; }
  .cp-tb-btn {
    display:inline-flex;align-items:center;justify-content:center;
    width:30px;height:30px;border:1.5px solid var(--border-light);border-radius:var(--radius-sm);
    background:#fff;color:var(--text-muted);cursor:pointer;transition:all .12s;flex-shrink:0;
  }
  .cp-tb-btn:hover { border-color:var(--primary);color:var(--primary); }
  .cp-tb-btn--active { background:var(--primary-soft);color:var(--primary);border-color:var(--primary); }
  .cp-tb-btn svg { width:14px;height:14px; }
  .cp-tb-export {
    display:inline-flex; align-items:center; gap:4px;
    padding:4px 10px; border:1.5px solid var(--border-light); border-radius:var(--radius-sm);
    font-size:0.68rem; font-weight:700; font-family:inherit;
    color:var(--text-muted); background:#fff; cursor:pointer; text-decoration:none;
    transition:all .12s; flex-shrink:0;
  }
  .cp-tb-export:hover { border-color:var(--primary); color:var(--primary); }
  .cp-tb-export svg { width:13px; height:13px; }
  .cp-table-wrap { background:#fff;border-radius:0 0 var(--cp-radius) var(--cp-radius);overflow:hidden; }
  .cp-tbl { width:100%;border-collapse:collapse; }
  .cp-tbl th { text-align:start;padding:10px 12px;font-size:0.68rem;font-weight:700;color:var(--text-muted);white-space:nowrap;user-select:none;cursor:pointer;border-bottom:1px solid var(--border-light); }
  .cp-tbl th:hover { color:var(--text-primary); }
  .cp-tbl-sort { font-size:0.55rem;margin-inline-start:3px; }
  .cp-tbl-sorted { color:var(--primary)!important; }
  .cp-row { transition:background .1s; }
  .cp-row:hover { background:var(--input-bg); }
  .cp-row td { padding:6px 12px;vertical-align:middle;border-bottom:1px solid var(--border-light); }

  .cp-user-wrap { display:flex;align-items:center;gap:10px; }
  .cp-avatar {
    width:36px;height:36px;border-radius:var(--radius-full);flex-shrink:0;
    display:flex;align-items:center;justify-content:center;
    font-size:0.72rem;font-weight:800;color:#fff;
    background:linear-gradient(135deg,var(--primary),var(--primary-dark));
  }
  .cp-name { display:block;font-size:0.8rem;font-weight:700;color:var(--text-primary);text-decoration:none;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
  .cp-name:hover { color:var(--primary); }
  .cp-sub { display:block;font-size:0.62rem;color:var(--text-muted);margin-top:1px; }

  .cp-status { display:inline-flex;align-items:center;gap:5px;font-size:0.72rem;font-weight:600;color:var(--text-secondary); }
  .cp-dot { width:7px;height:7px;border-radius:var(--radius-full);flex-shrink:0; }
  .cp-dot--active { background:var(--success); }
  .cp-dot--inactive { background:var(--danger); }

  .cp-badge { display:inline-flex;align-items:center;gap:3px;padding:2px 7px;border-radius:var(--radius-sm);font-size:0.62rem;font-weight:600;line-height:1.4; }
  .cp-badge--success { background:rgba(22,163,74,0.1);color:var(--success); }
  .cp-badge--warn { background:rgba(181,138,60,0.1);color:var(--accent); }
  .cp-badge--muted { background:var(--input-bg);color:var(--text-muted); }
  .cp-badge--danger { background:var(--danger-light);color:var(--danger); }

  .cp-actions { display:flex;align-items:center;justify-content:center;gap:2px;opacity:0.3; }
  .cp-row:hover .cp-actions { opacity:1; }
  .cp-act { display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border:none;border-radius:var(--radius-sm);background:transparent;color:var(--text-muted);cursor:pointer;text-decoration:none; }
  .cp-act:hover { background:var(--input-bg);color:var(--primary); }
  .cp-act svg { width:12px;height:12px; }

  .cp-empty { text-align:center;padding:0; }
  .cp-empty-inner { display:flex;flex-direction:column;align-items:center;justify-content:center;padding:48px 24px; }
  .cp-empty-icon { width:44px;height:44px;border-radius:var(--radius-full);display:flex;align-items:center;justify-content:center;background:var(--input-bg);color:var(--text-muted);margin-bottom:10px; }
  .cp-empty-icon svg { width:20px;height:20px; }
  .cp-empty-title { font-size:0.82rem;font-weight:700;color:var(--text-primary);margin-bottom:3px; }
  .cp-empty-desc { font-size:0.7rem;color:var(--text-muted);max-width:260px; }

  .cp-pager { display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:8px;padding:10px 14px;background:#fff;border-radius:0 0 var(--cp-radius) var(--cp-radius);border-top:1px solid var(--border-light); }
  .cp-pager-info { font-size:0.68rem;color:var(--text-muted);font-weight:500; }
  .cp-pager-nav { display:flex;gap:4px; }
  .cp-pager-nav a,.cp-pager-nav span { display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:30px;padding:0 6px;border-radius:var(--radius-sm);font-size:0.72rem;font-weight:600;color:var(--text-secondary);text-decoration:none; }
  .cp-pager-nav a:hover { background:var(--input-bg);color:var(--primary); }
  .cp-pager-nav span[aria-current=page] { background:var(--primary);color:#fff; }

  .cp-overlay { position:fixed;inset:0;z-index:900;background:rgba(0,0,0,0.25);backdrop-filter:blur(2px); }
  .cp-drawer { position:fixed;top:0;bottom:0;left:0;z-index:901;width:320px;max-width:85vw;background:#fff;display:flex;flex-direction:column;box-shadow:-4px 0 24px rgba(0,0,0,0.08);transition:transform .25s cubic-bezier(.22,1,.36,1); }
  .cp-drawer[aria-hidden=true] { transform:translateX(-105%); }
  .cp-drawer-head { display:flex;align-items:center;justify-content:space-between;padding:16px 18px 12px;border-bottom:1px solid var(--border-light); }
  .cp-drawer-head h3 { font-size:0.9rem;font-weight:800;color:var(--text-primary); }
  .cp-drawer-close { width:30px;height:30px;border:none;border-radius:var(--radius-sm);background:transparent;color:var(--text-muted);cursor:pointer;display:flex;align-items:center;justify-content:center; }
  .cp-drawer-close:hover { background:var(--input-bg);color:var(--text-primary); }
  .cp-drawer-close svg { width:16px;height:16px; }
  .cp-drawer-body { flex:1;overflow-y:auto;padding:16px 18px;overscroll-behavior:contain; }
  .cp-drawer-group { margin-bottom:18px; }
  .cp-drawer-label { display:block;font-size:0.68rem;font-weight:700;color:var(--text-secondary);margin-bottom:5px;text-transform:uppercase;letter-spacing:0.03em; }
  .cp-drawer-select { width:100%;padding:8px 10px;border:1.5px solid var(--border-light);border-radius:var(--radius-md);font-size:0.78rem;font-family:inherit;background:#fff;color:var(--text-primary); }
  .cp-drawer-select:focus { outline:none;border-color:var(--primary); }
  .cp-drawer-foot { display:flex;gap:8px;padding:12px 18px 16px;border-top:1px solid var(--border-light); }
  .cp-drawer-foot button { flex:1;padding:8px;border-radius:var(--radius-md);font-size:0.75rem;font-weight:700;font-family:inherit;cursor:pointer; }
  .cp-drawer-apply { border:none;background:var(--primary);color:#fff; }
  .cp-drawer-apply:hover { opacity:0.9; }
  .cp-drawer-reset { border:1.5px solid var(--border-light);background:transparent;color:var(--text-secondary); }
  .cp-drawer-reset:hover { border-color:var(--danger);color:var(--danger); }

  @media (max-width:768px) { .cp-toolbar { flex-wrap:wrap; } .cp-tb-search { max-width:100%; } .cp-drawer { width:85vw; } }
</style>

<form method="GET" action="{{ route('admin.companies.index') }}" id="cp-filter-form" hidden aria-hidden="true">
  <input type="text" name="search" value="{{ request('search') }}">
  <input type="text" name="status" value="{{ request('status') }}">
  @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
  @if(request('dir'))<input type="hidden" name="dir" value="{{ request('dir') }}">@endif
</form>

<div class="cp-scope" x-data="companiesPage">
  <div class="cp-section-card">
    <div class="cp-section-start">
      <div class="cp-section-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/></svg>
      </div>
      <div>
        <h3 class="cp-section-title">إدارة الشركات</h3>
        <p class="cp-section-desc">عرض وإدارة الشركات وتوزيع الرواتب</p>
      </div>
    </div>
    <div class="cp-section-date">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      <span>{{ $dateStr }}</span>
    </div>
  </div>

  @include('admin.partials._kpi_card_grid', ['ns' => 'companies-kpi'])

  <div class="cp-chips" x-show="hasActiveFilters" x-cloak>
    <template x-for="chip in activeChips" :key="chip.key">
      <span class="cp-chip"><span x-text="chip.label"></span><button @click="removeFilter(chip.key)">&times;</button></span>
    </template>
    <button class="cp-chip-clear" @click="clearAllFilters()">مسح الكل</button>
  </div>

  <div class="cp-toolbar">
    <div class="cp-tb-search">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
      <input type="text" x-model.debounce.250ms="query" @input.debounce.250ms="onSearch" placeholder="اسم الشركة، الرمز، الهاتف…" autocomplete="off">
    </div>
    <div class="cp-tb-date">
      <select x-model="datePreset" @change="onDatePreset">
        <option value="">كل الأوقات</option>
        <option value="today">اليوم</option>
        <option value="7d">آخر 7 أيام</option>
        <option value="30d">آخر 30 يوم</option>
        <option value="90d">آخر 90 يوم</option>
      </select>
    </div>
    <a href="{{ route('admin.companies.export', request()->query()) }}" id="cp-export-link" class="cp-tb-export" title="CSV">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      <span>CSV</span>
    </a>
    <button type="button" class="cp-tb-btn" :class="drawerOpen && 'cp-tb-btn--active'" @click="drawerOpen = !drawerOpen">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="20" y2="12"/><line x1="12" y1="18" x2="20" y2="18"/></svg>
    </button>

  </div>

  {{-- Company list (AJAX region) --}}
  <div id="cp-results" aria-live="polite" aria-busy="false">
    @include('admin.companies.partials._table')
  </div>

  <template x-teleport="body">
    <div>
      <div class="cp-overlay" x-show="drawerOpen" x-cloak
           x-transition:enter="transition ease-out duration-200"
           x-transition:enter-start="opacity-0"
           x-transition:enter-end="opacity-100"
           x-transition:leave="transition ease-in duration-150"
           x-transition:leave-start="opacity-100"
           x-transition:leave-end="opacity-0"
           @click="drawerOpen = false"></div>
      <div class="cp-drawer" role="dialog" aria-modal="true"
           x-show="drawerOpen" x-cloak
           x-transition:enter="transition ease-out duration-250"
           x-transition:enter-start="-translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-200"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="-translate-x-full">
        <div class="cp-drawer-head">
          <h3>فلاتر البحث</h3>
          <button type="button" class="cp-drawer-close" @click="drawerOpen = false">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div class="cp-drawer-body">
          <div class="cp-drawer-group">
            <label class="cp-drawer-label">الحالة</label>
            <select class="cp-drawer-select" x-model="filters.status" @change="onFilterChange">
              <option value="">الكل</option>
              <option value="active">نشطة</option>
              <option value="inactive">معطّلة</option>
              <option value="payroll">رواتب مفعّلة</option>
              <option value="pending">بانتظار التحقق</option>
            </select>
          </div>
        </div>
        <div class="cp-drawer-foot">
          <button type="button" class="cp-drawer-reset" @click="clearAllFilters()">إعادة ضبط</button>
          <button type="button" class="cp-drawer-apply" @click="drawerOpen = false">تطبيق</button>
        </div>
      </div>
    </div>
  </template>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('companiesPage', () => ({
    query: '{{ request('search') }}',
    filters: { status: '{{ request('status') }}' },
    sortCol: '{{ request('sort', 'created_at') }}',
    sortDir: '{{ request('dir', 'desc') }}',
    kpis: null, drawerOpen: false, datePreset: '',

    get hasActiveFilters() { return this.filters.status !== '' || this.query !== ''; },
    get activeChips() {
      const chips = [];
      if (this.query) chips.push({ key:'search', label:'بحث: "'+this.query+'"' });
      const valMap = { status:{ active:'نشطة', inactive:'معطّلة', payroll:'رواتب مفعّلة', pending:'بانتظار التحقق' } };
      for (const [k, v] of Object.entries(this.filters)) {
        if (!v) continue;
        chips.push({ key:k, label: (k==='status'?'الحالة: ':'') + ((valMap[k]&&valMap[k][v])||v) });
      }
      return chips;
    },
    get kpiCards() {
      if (!this.kpis) return [];
      const k = this.kpis;
      return [
        { iconSvg:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/></svg>', bg:'rgba(107,15,36,0.08)', color:'var(--sukk-primary)', label:'إجمالي الشركات', value:Number(k.total).toLocaleString()+' <span class="dash4-kpi-sub">شركة</span>', changeText:'', changeDir:'up', sparkSvg:this._spark([47,63,38,72,55,80,68],'#6E1B2D') },
        { iconSvg:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><polyline points="20 6 9 17 4 12"/></svg>', bg:'rgba(31,157,85,0.1)', color:'var(--success)', label:'نشطة', value:Number(k.active).toLocaleString()+' <span class="dash4-kpi-sub">شركة</span>', changeText:k.active+'/'+k.total, changeDir:'up', sparkSvg:this._spark([49,63,41,58,34,49,54],'#16A34A') },
        { iconSvg:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>', bg:'rgba(181,138,60,0.1)', color:'var(--accent)', label:'KYC معلّق', value:Number(k.pending_kyc).toLocaleString()+' <span class="dash4-kpi-sub">طلب</span>', changeText:'قيد المراجعة', changeDir:'up', sparkSvg:this._spark([22,35,18,42,30,48,26],'#B58A3C') },
        { iconSvg:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>', bg:'rgba(31,157,85,0.08)', color:'var(--success)', label:'رواتب مفعّلة', value:Number(k.payroll_enabled).toLocaleString()+' <span class="dash4-kpi-sub">شركة</span>', changeText:'', changeDir:'up', sparkSvg:this._spark([35,52,44,68,40,58,70],'#16A34A') },
      ];
    },
    _spark(h,c) { return h.map(v => '<div class="dash4-spark-bar" style="background:'+c+';height:'+v+'%;"></div>').join(''); },

    init() {
      this.loadKpis();
      window.addEventListener('users-changed',()=>{ this.loadKpis(); this._fetchResults(); });
      this._fetchResults();
      // Filter drawer: lock/unlock #mainContent scroll while open (it is
      // the app-shell's scroll container now — body itself never scrolls).
      this.$watch('drawerOpen', (open) => {
        var el = document.getElementById('mainContent') || document.body;
        el.style.overflow = open ? 'hidden' : '';
      });
    },

    onSearch() { this._syncHidden('search', this.query); this._fetchResults(); },
    onFilterChange() { this._syncAllFilters(); this._fetchResults(); },
    onDatePreset() {
      if (!this.datePreset) { this._fetchResults(); return; }
      const now=new Date(), fmt=d=>d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');
      const d=new Date(now);
      switch(this.datePreset) { case '7d': d.setDate(d.getDate()-7); break; case '30d': d.setDate(d.getDate()-30); break; case '90d': d.setDate(d.getDate()-90); break; }
      this._syncHidden('date_from', fmt(d)); this._syncHidden('date_to', fmt(now)); this._fetchResults();
    },
    removeFilter(key) { if(key==='search'){this.query='';this._syncHidden('search','');}else{this.filters[key]='';this._syncHidden(key,'');} this._fetchResults(); },
    clearAllFilters() { this.query=''; for(const k in this.filters) this.filters[k]=''; const f=document.getElementById('cp-filter-form'); if(f)f.querySelectorAll('input,select').forEach(el=>el.value=''); this._fetchResults(); },
    sortBy(col) { if(this.sortCol===col) this.sortDir=this.sortDir==='asc'?'desc':'asc'; else{this.sortCol=col;this.sortDir='asc';} this._syncHidden('sort',this.sortCol); this._syncHidden('dir',this.sortDir); this._fetchResults(); },
    sortIcon(col) { if(this.sortCol!==col) return '↕'; return this.sortDir==='asc'?'↑':'↓'; },

    async loadKpis() {
      try{const r=await fetch('{{ route('admin.companies.kpis') }}',{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}});if(!r.ok)return;this.kpis=await r.json();}catch(e){}
    },
    _syncHidden(key,value) { const f=document.getElementById('cp-filter-form'); if(!f) return; let el=f.querySelector(`[name="${key}"]`); if(!el){el=document.createElement('input');el.type='hidden';el.name=key;f.appendChild(el);} el.value=value; },
    _syncAllFilters() { this._syncHidden('search',this.query); for(const[k,v]of Object.entries(this.filters)) this._syncHidden(k,v); this._syncHidden('sort',this.sortCol); this._syncHidden('dir',this.sortDir); },
    _buildQs() { const f=document.getElementById('cp-filter-form');if(!f)return'';const d=new FormData(f),p=new URLSearchParams();for(const[k,v]of d.entries())if(v!=='')p.append(k,v);return p.toString(); },
    _fetchResults() { this._fetchUrl('{{ route('admin.companies.index') }}'+(this._buildQs()?'?'+this._buildQs():'')); },
    async _fetchUrl(url) { const r=document.getElementById('cp-results');if(!r)return;r.setAttribute('aria-busy','true');try{const res=await fetch(url,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}});if(!res.ok){window.location.reload();return;}r.innerHTML=await res.text();if(window.Alpine)Alpine.initTree(r);history.replaceState(null,'',url);}catch(e){window.location.reload();}finally{r.setAttribute('aria-busy','false');}const cpExportLink=document.getElementById('cp-export-link');if(cpExportLink){const exportUrl=new URL('{{ route('admin.companies.export') }}',location.origin);cpExportLink.href=exportUrl.pathname+(qs=>qs?'?'+qs:'')(this._buildQs()));}},
  }));
});
</script>
@endpush
