@extends('layouts.admin')

@section('title', 'الإعدادات')

@section('breadcrumbs')
<span class="breadcrumb-item">الإعدادات</span>
@endsection

@php
    $healthOverall = $overallOnline ? 'جميع الخدمات تعمل' : 'بعض الخدمات بحاجة للانتباه';
    $healthBadge   = $overallOnline ? 'badge-success' : 'badge-warning';
    $backupCount   = $backupFiles->count();
@endphp

@section('content')
{{-- ═══ RADICAL REDESIGN — Settings (matches up/tx pattern) ═══ --}}
<style>
  .stg-scope *,.stg-scope *::before,.stg-scope *::after{box-sizing:border-box}
  .stg-scope{--stg-radius:var(--radius-lg);--stg-gap:10px;--stg-muted:#6B5F5A;--stg-border:#E0D5CA;--stg-card-shadow:0 1px 4px rgba(0,0,0,0.07);--text-muted:var(--stg-muted);--border-light:var(--stg-border)}

  /* ── Section identity card (burgundy gradient) ── */
  .stg-section-card {
    background:linear-gradient(135deg,var(--primary-dark),var(--primary));
    border-radius:var(--radius-xl);padding:18px 22px;
    display:flex;align-items:center;justify-content:space-between;gap:16px;
    flex-wrap:wrap;margin-bottom:16px;
    position:relative;overflow:hidden;
  }

  .stg-section-card::after {
    content:'';position:absolute;inset:0;
    background:radial-gradient(ellipse 70% 60% at 0% 100%,rgba(255,255,255,0.06) 0%,transparent 70%);
    pointer-events:none;
  }
  .stg-section-start{display:flex;align-items:center;gap:14px;position:relative;z-index:1}
  .stg-section-icon{
    width:44px;height:44px;border-radius:var(--radius-lg);flex-shrink:0;
    display:flex;align-items:center;justify-content:center;
    background:rgba(255,255,255,0.15);color:#fff;backdrop-filter:blur(4px);
  }
  .stg-section-icon svg{width:20px;height:20px}
  .stg-section-title{
    font-size:1.05rem;font-weight:800;color:#fff;margin:0;
    letter-spacing:-0.02em;
  }
  .stg-section-desc{
    font-size:0.72rem;color:rgba(255,255,255,0.7);margin:2px 0 0;
  }
  .stg-section-date{
    display:flex;align-items:center;gap:6px;
    font-size:0.7rem;font-weight:600;color:rgba(255,255,255,0.85);
    background:rgba(255,255,255,0.12);padding:5px 14px;border-radius:var(--radius-full);
    white-space:nowrap;position:relative;z-index:1;backdrop-filter:blur(4px);
  }
  .stg-section-date svg{width:12px;height:12px;flex-shrink:0}

  /* ── Tab nav (pills) ── */
  .stg-tabs{
    display:flex;flex-wrap:wrap;gap:5px;margin-bottom:16px;
    background:#fff;border-radius:var(--radius-xl);padding:5px;
    border:1px solid var(--stg-border);
  }
  .stg-tab{
    display:inline-flex;align-items:center;gap:6px;
    padding:7px 14px;border-radius:var(--radius-lg);font-size:0.75rem;font-weight:600;
    color:var(--text-secondary);background:transparent;border:none;
    cursor:pointer;font-family:inherit;transition:all .12s;white-space:nowrap;
  }
  .stg-tab:hover{background:var(--input-bg);color:var(--text-primary)}
  .stg-tab--active{background:var(--primary);color:#fff;box-shadow:0 4px 12px -6px rgba(110,27,45,.4);position:relative}
  .stg-tab--active::after{content:'';position:absolute;bottom:-3px;left:50%;transform:translateX(-50%);width:60%;height:2px;background:var(--gold);border-radius:2px}
  .stg-tab svg{width:13px;height:13px;opacity:.6}
  .stg-tab--active svg{opacity:1}
  .stg-tab-bdg{
    font-size:0.6rem;font-weight:800;padding:2px 6px;border-radius:999px;
    background:rgba(255,255,255,0.2);color:inherit;line-height:1;
  }
  .stg-tab--active .stg-tab-bdg{background:rgba(255,255,255,0.2);color:#fff}

  /* ── Content cards ── */
  .stg-card{
    background:#fff;border-radius:var(--radius-xl);overflow:hidden;
    box-shadow:var(--stg-card-shadow);
    border:1px solid var(--stg-border);margin-bottom:14px;
  }
  .stg-card-hdr{
    display:flex;align-items:center;justify-content:space-between;gap:12px;
    padding:14px 18px;border-bottom:1px solid var(--stg-border);
  }
  .stg-card-hdr-l{display:flex;align-items:center;gap:10px}
  .stg-card-ico{
    width:32px;height:32px;border-radius:var(--radius-md);flex-shrink:0;
    display:flex;align-items:center;justify-content:center;
    background:var(--primary-soft);color:var(--primary);
  }
  .stg-card-ico svg{width:15px;height:15px}
  .stg-card-title{font-size:0.82rem;font-weight:800;color:var(--text-primary);margin:0}
  .stg-card-sub{font-size:0.68rem;color:var(--stg-muted);margin:2px 0 0}
  .stg-card-bd{padding:14px 18px}

  /* ── Setting rows ── */
  .stg-row{
    display:flex;align-items:center;justify-content:space-between;gap:12px;
    padding:11px 0;border-bottom:1px solid var(--stg-border);
  }
  .stg-row:last-child{border-bottom:none;padding-bottom:0}
  .stg-row-meta{min-width:0}
  .stg-row-t{display:block;font-size:0.8rem;font-weight:700;color:var(--text-primary)}
  .stg-row-d{display:block;font-size:0.66rem;color:var(--stg-muted);margin-top:1px}
  .stg-row-ctl{display:flex;align-items:center;gap:8px;flex-wrap:wrap;min-width:0}
  .stg-saved{width:16px;height:16px;color:var(--success);opacity:0;transition:opacity .3s}
  .stg-saved.show{opacity:1}

  /* ── Warning banner ── */
  .stg-warn{
    display:flex;align-items:center;gap:7px;
    padding:8px 12px;margin-top:8px;margin-bottom:4px;
    border-radius:var(--radius-md);font-size:0.72rem;font-weight:600;
    background:#FEF3C7;color:#92400E;
  }
  .stg-warn svg{width:14px;height:14px;flex-shrink:0}

  /* ── Section header in card body ── */
  .stg-sub-hdr{
    display:flex;align-items:center;gap:6px;
    font-size:0.7rem;font-weight:800;color:var(--accent-dark);text-transform:uppercase;
    letter-spacing:.05em;
  }
  .stg-sub-hdr svg{width:13px;height:13px}
  .stg-hr{height:1px;background:var(--stg-border);margin:12px 0}

  /* ── Currency chips ── */
  .stg-chip{
    display:inline-flex;align-items:center;gap:4px;
    padding:4px 12px;border-radius:var(--radius-2xl);font-size:0.72rem;font-weight:700;
    border:1.5px solid var(--border-light);background:#fff;color:var(--text-secondary);
    cursor:pointer;transition:all .1s;font-family:inherit;user-select:none;
  }
  .stg-chip input{display:none}
  .stg-chip.on{border-color:var(--primary);color:var(--primary);background:var(--primary-soft)}
  .stg-chip svg{width:12px;height:12px}

  /* ── Info rows (system info) ── */
  .stg-info-row{
    display:flex;justify-content:space-between;align-items:center;
    padding:6px 0;font-size:0.75rem;border-bottom:1px solid var(--stg-border);
  }
  .stg-info-row:last-child{border-bottom:none}
  .stg-info-lbl{color:var(--text-secondary);font-weight:600}
  .stg-info-val{color:var(--text-primary);font-weight:700;font-variant-numeric:tabular-nums}

  /* ── Stat cards (backup) ── */
  .stg-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px}
  .stg-stat{
    display:flex;align-items:center;gap:10px;
    background:#fff;border-radius:var(--radius-xl);padding:14px 16px;
    border:1px solid var(--stg-border);box-shadow:var(--stg-card-shadow);
  }
  .stg-stat-ico{
    width:38px;height:38px;border-radius:var(--radius-md);flex-shrink:0;
    display:flex;align-items:center;justify-content:center;
  }
  .stg-stat-ico svg{width:17px;height:17px}
  .stg-stat-v{font-size:0.95rem;font-weight:800;color:var(--text-primary);letter-spacing:-.02em}
  .stg-stat-l{font-size:0.62rem;color:var(--stg-muted);font-weight:600;margin-top:1px}

  /* ── Health cards ── */
  .stg-health-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px}
  .stg-health-card .stg-card-hdr{padding:10px 14px;background:var(--surface-hover)}
  .stg-health-dot{width:8px;height:8px;border-radius:var(--radius-full);flex-shrink:0}
  .stg-health-dot.online{background:var(--success);box-shadow:0 0 0 4px rgba(16,185,129,0.2)}
  .stg-health-dot.offline{background:var(--danger);box-shadow:0 0 0 4px rgba(239,68,68,0.2)}
  .stg-health-name{font-size:0.78rem;font-weight:700;color:var(--text-primary)}
  .stg-health-stat{display:flex;justify-content:space-between;align-items:center;padding:4px 0}
  .stg-health-stat-l{font-size:0.65rem;font-weight:700;color:var(--stg-muted)}
  .stg-health-stat-v{font-size:0.75rem;font-weight:700;color:var(--text-primary);font-variant-numeric:tabular-nums}
  .stg-health-detail{font-size:0.68rem;color:var(--text-secondary);margin-top:6px;line-height:1.6;padding:5px 0}

  /* ── Tables ── */
  .stg-tbl-wrap{overflow-x:auto}
  .stg-tbl{width:100%;border-collapse:collapse}
  .stg-tbl th{
    text-align:start;padding:9px 12px;font-size:0.68rem;font-weight:700;
    color:var(--stg-muted);white-space:nowrap;border-bottom:1px solid var(--stg-border);
  }
  .stg-tbl td{
    padding:8px 12px;font-size:0.74rem;vertical-align:middle;
    border-bottom:1px solid var(--stg-border);color:var(--text-primary);
  }
  .stg-tbl tr:last-child td{border-bottom:none}
  .stg-tbl tr:hover td{background:var(--input-bg)}
  .stg-mono{font-family:monospace;direction:ltr;font-size:0.72rem}
  .stg-badge{
    display:inline-block;padding:1px 8px;border-radius:999px;
    font-size:0.65rem;font-weight:700;
  }
  .stg-date{font-size:0.68rem;color:var(--text-secondary);direction:ltr;white-space:nowrap}

  /* ── Toast ── */
  .stg-toast{
    display:flex;align-items:center;gap:8px;
    padding:10px 14px;border-radius:var(--radius-lg);font-size:0.75rem;font-weight:600;
    margin-bottom:14px;
  }
  .stg-toast svg{width:16px;height:16px;flex-shrink:0}
  .stg-toast-success{background:rgba(22,163,74,0.1);color:var(--success)}
  .stg-toast-error{background:rgba(220,38,38,0.1);color:var(--danger)}

  /* ── Note banner ── */
  .stg-note{
    display:flex;align-items:flex-start;gap:8px;
    padding:10px 14px;border-radius:var(--radius-lg);font-size:0.72rem;line-height:1.7;
    margin-top:10px;
  }
  .stg-note svg{width:16px;height:16px;flex-shrink:0;margin-top:2px}
  .stg-note-warn{background:#FEF3C7;color:#92400E}
  .stg-note-danger{background:#FEE2E2;color:#991B1B}

  /* ── Empty state ── */
  .stg-empty{
    display:flex;flex-direction:column;align-items:center;justify-content:center;
    padding:32px 16px;text-align:center;
  }
  .stg-empty-ico{
    width:40px;height:40px;border-radius:var(--radius-full);
    background:var(--input-bg);color:var(--text-muted);
    display:flex;align-items:center;justify-content:center;margin-bottom:8px;
  }
  .stg-empty-ico svg{width:18px;height:18px}
  .stg-empty-t{font-size:0.78rem;font-weight:700;color:var(--text-primary);margin-bottom:3px}
  .stg-empty-d{font-size:0.68rem;color:var(--stg-muted);max-width:240px}

  /* ── Form controls ── */
  .stg-fld{margin-bottom:14px}
  .stg-fld:last-child{margin-bottom:0}
  .stg-fld-lbl{display:block;font-size:0.7rem;font-weight:700;color:var(--text-secondary);margin-bottom:4px}
  .stg-fld-hint{display:block;font-size:0.64rem;color:var(--stg-muted);margin-top:3px}
  .stg-fld-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
  .stg-fld .input{width:100%}
  .stg-fld-row .label{margin-bottom:4px;font-size:0.7rem;font-weight:700;color:var(--text-secondary)}

  /* ── Toggle row ── */
  .stg-tgl{
    display:flex;align-items:center;justify-content:space-between;gap:12px;
    padding:10px 14px;border-radius:var(--radius-lg);background:var(--input-bg);
    cursor:pointer;transition:background .1s;border:1px solid transparent;
  }
  .stg-tgl:hover{background:var(--surface-hover);border-color:var(--stg-border)}
  .stg-tgl-t{display:block;font-size:0.78rem;font-weight:700;color:var(--text-primary)}
  .stg-tgl-d{display:block;font-size:0.65rem;color:var(--stg-muted);margin-top:1px}

  /* ── Action buttons ── */
  .stg-act{
    display:inline-flex;align-items:center;justify-content:center;
    width:28px;height:28px;border:none;border-radius:var(--radius-sm);
    background:transparent;color:var(--text-muted);cursor:pointer;
    text-decoration:none;transition:background .1s,color .1s;
  }
  .stg-act:hover{background:var(--input-bg);color:var(--primary)}
  .stg-act-dl:hover{color:var(--primary)}
  .stg-act-rst:hover{color:var(--accent-dark)}
  .stg-act-del:hover{color:var(--danger);background:var(--danger-light)}
  .stg-act svg{width:13px;height:13px}

  /* ── Breadcrumb style for app update ── */
  .stg-bc{display:flex;align-items:center;gap:6px;font-size:0.68rem;color:var(--stg-muted)}

  /* ── Toolbar (backup create row) ── */
  .stg-toolbar{
    display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;
  }
  .stg-toolbar-meta{min-width:0}
  .stg-toolbar-t{font-size:0.82rem;font-weight:700;color:var(--text-primary)}
  .stg-toolbar-d{font-size:0.66rem;color:var(--stg-muted);margin-top:1px}

  /* ── Search ── */
  .stg-search{
    position:relative;flex:1;min-width:200px;
  }
  .stg-search svg{
    position:absolute;top:50%;transform:translateY(-50%);right:10px;
    width:14px;height:14px;color:var(--text-muted);pointer-events:none;
  }
  .stg-search input{
    width:100%;padding:7px 32px 7px 10px;border:1.5px solid var(--border-light);
    border-radius:var(--radius-md);font-size:0.75rem;font-family:inherit;
    background:#fff;color:var(--text-primary);transition:border-color .1s;
  }
  .stg-search input:focus{outline:none;border-color:var(--primary)}

  /* ── Responsive ── */
  @media(max-width:640px){
    .stg-stats{grid-template-columns:1fr}
    .stg-fld-row{grid-template-columns:1fr}
    .stg-health-grid{grid-template-columns:1fr}
    .stg-tabs{gap:3px}
    .stg-tab{font-size:0.7rem;padding:6px 10px}
    .stg-sys-grid{grid-template-columns:1fr}
  }
</style>

{{-- Hidden filter form (hash nav) --}}
<form method="GET" action="{{ route('admin.settings') }}" id="stg-filter-form" hidden aria-hidden="true">
  <input type="text" name="audit_search" value="{{ request('audit_search') }}">
</form>

<div class="stg-scope" x-data="settingsPage">
  {{-- ═══ Section identity card ═══ --}}
  <div class="stg-section-card">
    <div class="stg-section-start">
      <div class="stg-section-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
      </div>
      <div>
        <h3 class="stg-section-title">الإعدادات</h3>
        <p class="stg-section-desc">إدارة المنصة — عام، حدود، النظام، النسخ الاحتياطية والمزيد</p>
      </div>
    </div>
    <div class="stg-section-date">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      <span>{{ \Carbon\Carbon::now()->format('Y/m/d') }}</span>
    </div>
  </div>

  {{-- ═══ Tab nav (pills) ═══ --}}
  <div class="stg-tabs" role="tablist" aria-label="أقسام الإعدادات">
    <template x-for="s in sections" :key="s.id">
      <button type="button" class="stg-tab" :class="section === s.id && 'stg-tab--active'"
              @click="section = s.id" role="tab" :aria-selected="section === s.id">
        <template x-if="s.id==='general'">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
        </template>
        <template x-if="s.id==='limits'">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v3m0 16v3M4 7l8-4 8 4M4 17l8 4 8-4M4 7v10l8 4M20 7v10l-8 4"/><path d="M8 9v6m8-6v6"/><path d="M12 7v2m0 6v2"/></svg>
        </template>
        <template x-if="s.id==='referral'">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
        </template>
        <template x-if="s.id==='system'">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        </template>
        <template x-if="s.id==='health'">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
        </template>
        <template x-if="s.id==='backup'">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/></svg>
        </template>
        <template x-if="s.id==='appupdate'">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>
        </template>
        <template x-if="s.id==='audit'">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        </template>
        <span x-text="s.label"></span>
        <span class="stg-tab-bdg" x-show="s.badge" x-text="s.badge"></span>
      </button>
    </template>
  </div>

  {{-- ═══ Content sections ═══ --}}

  {{-- ─── 1. GENERAL ─── --}}
  <section x-show="section === 'general'" x-transition:enter.duration.200ms.opacity>
    <div class="stg-card">
      <div class="stg-card-hdr">
        <div class="stg-card-hdr-l">
          <span class="stg-card-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
          </span>
          <div>
            <h3 class="stg-card-title">عام</h3>
            <p class="stg-card-sub">حالة المنصة والعملة الافتراضية</p>
          </div>
        </div>
      </div>
      <div class="stg-card-bd">
        <div class="stg-row">
          <div class="stg-row-meta">
            <span class="stg-row-t">وضع الصيانة</span>
            <span class="stg-row-d">إيقاف المنصة مؤقتاً أمام المستخدمين</span>
          </div>
          <div class="stg-row-ctl">
            <svg class="stg-saved" :class="tick==='maintenance_mode' && 'show'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            <label class="switch">
              <input type="checkbox" x-model="vals.maintenance_mode" @change="confirmMaintenance($event)" aria-label="وضع الصيانة">
              <span class="switch-track"></span><span class="switch-thumb"></span>
            </label>
          </div>
        </div>
        <div x-show="vals.maintenance_mode" x-cloak class="stg-warn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          <span>المنصة في وضع الصيانة — المستخدمون لا يستطيعون الدخول.</span>
        </div>

        <div class="stg-row">
          <div class="stg-row-meta">
            <span class="stg-row-t">التسجيل مفتوح</span>
            <span class="stg-row-d">السماح بإنشاء حسابات جديدة</span>
          </div>
          <div class="stg-row-ctl">
            <svg class="stg-saved" :class="tick==='registration_open' && 'show'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            <label class="switch">
              <input type="checkbox" x-model="vals.registration_open" @change="save('registration_open', vals.registration_open)" aria-label="التسجيل مفتوح">
              <span class="switch-track"></span><span class="switch-thumb"></span>
            </label>
          </div>
        </div>

        <div class="stg-row">
          <div class="stg-row-meta">
            <span class="stg-row-t">العملة الافتراضية</span>
            <span class="stg-row-d">العملة الأساسية لعمليات المنصة</span>
          </div>
          <div class="stg-row-ctl">
            <svg class="stg-saved" :class="tick==='default_currency' && 'show'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            <select class="input input-narrow" x-model="vals.default_currency" @change="save('default_currency', vals.default_currency)" aria-label="العملة الافتراضية">
              @foreach($currencies as $c)
              <option value="{{ $c }}">{{ $c }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="stg-row">
          <div class="stg-row-meta">
            <span class="stg-row-t">العملات المدعومة</span>
            <span class="stg-row-d">العملات المتاحة في المحفظة</span>
          </div>
          <div class="stg-row-ctl">
            <svg class="stg-saved" :class="tick==='supported_currencies' && 'show'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            <div class="flex gap-2 flex-wrap">
              @foreach($currencies as $c)
              <label class="stg-chip" :class="vals.supported_currencies?.includes('{{ $c }}') && 'on'">
                <input type="checkbox" value="{{ $c }}" x-model="vals.supported_currencies" @change="save('supported_currencies', vals.supported_currencies)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" x-show="vals.supported_currencies?.includes('{{ $c }}')"><polyline points="20 6 9 17 4 12"/></svg>
                {{ $c }}
              </label>
              @endforeach
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- ─── 2. LIMITS ─── --}}
  <section x-show="section === 'limits'" x-cloak x-transition:enter.duration.200ms.opacity>
    <div class="stg-card">
      <div class="stg-card-hdr">
        <div class="stg-card-hdr-l">
          <span class="stg-card-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v3m0 16v3M4 7l8-4 8 4M4 17l8 4 8-4M4 7v10l8 4M20 7v10l-8 4"/><path d="M8 9v6m8-6v6"/><path d="M12 7v2m0 6v2"/></svg>
          </span>
          <div>
            <h3 class="stg-card-title">الحدود والقيود</h3>
            <p class="stg-card-sub">حدود الإيداع والسحب وإنفاق البطاقة — تُحفظ تلقائياً</p>
          </div>
        </div>
      </div>
      <div class="stg-card-bd">
        <div class="stg-sub-hdr"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg> الإيداع</div>
        <div class="stg-fld-row mt-3">
          @include('admin.settings._num', ['key' => 'min_deposit', 'label' => 'الحد الأدنى للإيداع', 'unit' => '$'])
          @include('admin.settings._num', ['key' => 'max_deposit', 'label' => 'الحد الأقصى للإيداع', 'unit' => '$'])
        </div>
        <div class="stg-hr"></div>
        <div class="stg-sub-hdr"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="5 12 12 5 19 12"/></svg> السحب</div>
        <div class="stg-fld-row mt-3">
          @include('admin.settings._num', ['key' => 'min_withdrawal', 'label' => 'الحد الأدنى للسحب', 'unit' => '$'])
          @include('admin.settings._num', ['key' => 'max_withdrawal', 'label' => 'الحد الأقصى للسحب', 'unit' => '$'])
          @include('admin.settings._num', ['key' => 'limit_daily_withdrawal', 'label' => 'حد السحب اليومي', 'unit' => '$'])
          @include('admin.settings._num', ['key' => 'limit_monthly_withdrawal', 'label' => 'حد السحب الشهري', 'unit' => '$'])
        </div>
        <div class="stg-hr"></div>
        <div class="stg-sub-hdr"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg> البطاقة</div>
        <div class="stg-fld-row mt-3">
          @include('admin.settings._num', ['key' => 'limit_card_daily', 'label' => 'حد إنفاق البطاقة اليومي', 'unit' => '$'])
          @include('admin.settings._num', ['key' => 'limit_card_monthly', 'label' => 'حد إنفاق البطاقة الشهري', 'unit' => '$'])
        </div>
      </div>
    </div>
  </section>

  {{-- ─── 3. REFERRAL ─── --}}
  <section x-show="section === 'referral'" x-cloak x-transition:enter.duration.200ms.opacity>
    <div class="stg-card">
      <div class="stg-card-hdr">
        <div class="stg-card-hdr-l">
          <span class="stg-card-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
          </span>
          <div>
            <h3 class="stg-card-title">نظام الإحالة</h3>
            <p class="stg-card-sub">مكافآت دعوة المستخدمين — تُحفظ تلقائياً</p>
          </div>
        </div>
      </div>
      <div class="stg-card-bd">
        <div class="stg-row">
          <div class="stg-row-meta">
            <span class="stg-row-t">تفعيل نظام الإحالة</span>
            <span class="stg-row-d">السماح بمكافآت الدعوات</span>
          </div>
          <div class="stg-row-ctl">
            <svg class="stg-saved" :class="tick==='referral_enabled' && 'show'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            <label class="switch">
              <input type="checkbox" x-model="vals.referral_enabled" @change="save('referral_enabled', vals.referral_enabled)" aria-label="تفعيل الإحالة">
              <span class="switch-track"></span><span class="switch-thumb"></span>
            </label>
          </div>
        </div>
        <div class="stg-fld-row mt-4" :style="vals.referral_enabled ? '' : 'opacity:.4;pointer-events:none'">
          @include('admin.settings._num', ['key' => 'referral_bonus_referrer', 'label' => 'مكافأة الداعي', 'unit' => '$'])
          @include('admin.settings._num', ['key' => 'referral_bonus_referred', 'label' => 'مكافأة المدعو', 'unit' => '$'])
        </div>
      </div>
    </div>
  </section>

  {{-- ─── 4. SYSTEM ─── --}}
  <section x-show="section === 'system'" x-cloak x-transition:enter.duration.200ms.opacity>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px" class="stg-sys-grid">
      <div class="stg-card">
        <div class="stg-card-hdr">
          <div class="stg-card-hdr-l">
            <span class="stg-card-ico">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            </span>
            <h3 class="stg-card-title">معلومات النظام</h3>
          </div>
        </div>
        <div class="stg-card-bd">
          @php $sysRows = [
            ['اسم التطبيق','صكك | SAKK Wallet'],
            ['إصدار PHP', phpversion()],
            ['إصدار Laravel', app()->version()],
            ['قاعدة البيانات', config('database.default')],
            ['البيئة', app()->environment()],
          ]; @endphp
          @foreach($sysRows as $r)
          <div class="stg-info-row">
            <span class="stg-info-lbl">{{ $r[0] }}</span>
            <span class="stg-info-val">{{ $r[1] }}</span>
          </div>
          @endforeach
          <div class="stg-info-row">
            <span class="stg-info-lbl">حالة التنصيب</span>
            <span class="badge badge-success">{{ file_exists(storage_path('installed')) ? 'مثبت' : 'غير مثبت' }}</span>
          </div>
        </div>
      </div>
      <div class="stg-card">
        <div class="stg-card-hdr">
          <div class="stg-card-hdr-l">
            <span class="stg-card-ico">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
            </span>
            <h3 class="stg-card-title">إحصائيات سريعة</h3>
          </div>
        </div>
        <div class="stg-card-bd">
          <div class="stg-info-row">
            <span class="stg-info-lbl">عدد المستخدمين</span>
            <span class="stg-info-val">{{ number_format($stats['users']) }}</span>
          </div>
          <div class="stg-info-row">
            <span class="stg-info-lbl">إجمالي المعاملات</span>
            <span class="stg-info-val">{{ number_format($stats['transactions']) }}</span>
          </div>
          <div class="stg-info-row">
            <span class="stg-info-lbl">التخزين المؤقت</span>
            <span class="stg-info-val">{{ config('cache.default') }}</span>
          </div>
        </div>
      </div>
    </div>

    <div class="stg-card">
      <div class="stg-card-hdr">
        <div class="stg-card-hdr-l">
          <span class="stg-card-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
          </span>
          <h3 class="stg-card-title">التحكم في التخزين المؤقت</h3>
        </div>
      </div>
      <div class="stg-card-bd">
        <div class="flex gap-2 flex-wrap">
          <form method="POST" action="{{ route('admin.settings.cache.clear') }}" @submit="handleSubmit($event)">
            @csrf
            <button type="submit" class="btn" style="background:var(--accent);color:#fff;border-color:var(--accent);">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;display:inline;vertical-align:middle;margin-left:4px"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
              مسح التخزين المؤقت
            </button>
          </form>
          <form method="POST" action="{{ route('admin.settings.cache.optimize') }}" @submit="handleSubmit($event)">
            @csrf
            <button type="submit" class="btn btn-primary">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;display:inline;vertical-align:middle;margin-left:4px"><polyline points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
              تحسين التخزين المؤقت
            </button>
          </form>
        </div>
      </div>
    </div>
  </section>

  {{-- ─── 5. HEALTH ─── --}}
  <section x-show="section === 'health'" x-cloak x-transition:enter.duration.200ms.opacity>
    <div class="stg-card">
      <div class="stg-card-hdr">
        <div class="stg-card-hdr-l">
          <span class="stg-card-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
          </span>
          <div>
            <h3 class="stg-card-title">صحة النظام</h3>
            <p class="stg-card-sub">فحص شامل لحالة النظام والخدمات المرتبطة</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <span class="badge {{ $healthBadge }}" id="stg-health-overall">{{ $healthOverall }}</span>
          <button type="button" class="btn btn-primary btn-sm" id="stg-health-rerun"
                  data-url="{{ route('admin.system.health.checks') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;display:inline;vertical-align:middle;margin-left:4px"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>
            تشغيل الفحص
          </button>
        </div>
      </div>
    </div>

    <div class="stg-health-grid" id="stg-health-grid">
      @foreach($checks as $key => $check)
      @php $isOnline = $check['status'] === 'online'; @endphp
      <div class="stg-card stg-health-card" data-health-key="{{ $key }}">
        <div class="stg-card-hdr">
          <div class="stg-card-hdr-l">
            <span class="stg-health-dot {{ $isOnline ? 'online' : 'offline' }}" x-ref="dot-{{ $key }}"></span>
            <span class="stg-health-name">{{ $check['name'] }}</span>
          </div>
          <span class="health-status-badge" x-ref="status-{{ $key }}" style="color:{{ $isOnline ? 'var(--success)' : 'var(--danger)' }};font-weight:600;font-size:.7rem">
            {{ $isOnline ? 'متصل' : 'غير متصل' }}
          </span>
        </div>
        <div class="stg-card-bd">
          <div class="stg-health-stat">
            <span class="stg-health-stat-l">وقت الاستجابة</span>
            <span class="stg-health-stat-v">{{ $check['response_time'] }} <span style="font-weight:400;color:var(--text-muted)">ms</span></span>
          </div>
          @if(!empty($check['uptime']))
          <div class="stg-health-stat">
            <span class="stg-health-stat-l">وقت التشغيل</span>
            <span class="stg-health-stat-v">{{ $check['uptime'] }}</span>
          </div>
          @endif
          @if(!empty($check['last_checked']))
          <div class="stg-health-stat">
            <span class="stg-health-stat-l">آخر فحص</span>
            <span class="stg-health-stat-v">{{ $check['last_checked'] }}</span>
          </div>
          @endif
          <p class="stg-health-detail" dir="auto">{{ $check['details'] }}</p>
        </div>
      </div>
      @endforeach
    </div>
  </section>

  {{-- ─── 6. BACKUP ─── --}}
  <section x-show="section === 'backup'" x-cloak x-transition:enter.duration.200ms.opacity>
    @if(session('success'))
    <div class="stg-toast stg-toast-success">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      <span>{{ session('success') }}</span>
    </div>
    @endif
    @if(session('error'))
    <div class="stg-toast stg-toast-error">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
      <span>{{ session('error') }}</span>
    </div>
    @endif

    <div class="stg-stats">
      <div class="stg-stat">
        <div class="stg-stat-ico" style="background:var(--accent-soft);color:var(--accent-dark)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
        </div>
        <div>
          <div class="stg-stat-v" dir="ltr">{{ $dbConnection === 'sqlite' ? 'SQLite' : ($dbConnection === 'mysql' ? 'MySQL' : $dbConnection) }}</div>
          <div class="stg-stat-l">نوع قاعدة البيانات</div>
        </div>
      </div>
      <div class="stg-stat">
        <div class="stg-stat-ico" style="background:rgba(22,163,74,0.1);color:var(--success)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M12 4v16"/></svg>
        </div>
        <div>
          <div class="stg-stat-v">{{ $dbSize }}</div>
          <div class="stg-stat-l">حجم قاعدة البيانات</div>
        </div>
      </div>
      <div class="stg-stat">
        <div class="stg-stat-ico" style="background:rgba(245,158,11,0.1);color:#b45309">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/></svg>
        </div>
        <div>
          <div class="stg-stat-v">{{ $backupCount }}</div>
          <div class="stg-stat-l">عدد النسخ الاحتياطية</div>
        </div>
      </div>
    </div>

    <div class="stg-card">
      <div class="stg-card-bd">
        <div class="stg-toolbar">
          <div class="stg-toolbar-meta">
            <div class="stg-toolbar-t">إنشاء نسخة احتياطية جديدة</div>
            <div class="stg-toolbar-d">نسخة كاملة من قاعدة البيانات في مجلد النسخ الاحتياطية.</div>
          </div>
          <form method="POST" action="{{ route('admin.system.backup.create') }}">
            @csrf
            <button type="submit" class="btn" style="background:var(--accent);color:#fff;border-color:var(--accent);">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;display:inline;vertical-align:middle;margin-left:4px"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/></svg>
              إنشاء نسخة احتياطية
            </button>
          </form>
        </div>
      </div>
    </div>

    @if($backupCount > 0)
    @php
      $totalSize = $backupFiles->sum('size');
      $totalFormatted = '';
      if ($totalSize > 0) {
          $units = ['B', 'KB', 'MB', 'GB'];
          $pow = floor(($totalSize ? log($totalSize) : 0) / log(1024));
          $pow = min($pow, count($units) - 1);
          $totalFormatted = round($totalSize / (1 << (10 * $pow)), 2) . ' ' . $units[$pow];
      }
    @endphp
    <div class="stg-note stg-note-warn">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
      <span><strong>إجمالي مساحة النسخ الاحتياطية:</strong> {{ $totalFormatted }} — يُنصح بنقل النسخ القديمة إلى وحدة تخزين خارجية بشكل دوري.</span>
    </div>
    @endif

    <div class="stg-card">
      <div class="stg-card-hdr">
        <div class="stg-card-hdr-l">
          <span class="stg-card-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          </span>
          <h3 class="stg-card-title">النسخ الاحتياطية الموجودة</h3>
        </div>
      </div>
      @if($backupFiles->isEmpty())
      <div class="stg-card-bd">
        <div class="stg-empty">
          <div class="stg-empty-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/></svg>
          </div>
          <div class="stg-empty-t">لا توجد نسخ احتياطية بعد</div>
          <div class="stg-empty-d">قم بإنشاء أول نسخة احتياطية باستخدام الزر أعلاه.</div>
        </div>
      </div>
      @else
      <div class="stg-tbl-wrap">
        <table class="stg-tbl">
          <thead>
            <tr>
              <th>اسم الملف</th>
              <th>الحجم</th>
              <th>التاريخ</th>
              <th>الإجراءات</th>
            </tr>
          </thead>
          <tbody>
            @foreach($backupFiles as $file)
            <tr>
              <td>
                <div class="flex items-center gap-2">
                  <span style="color:var(--accent-dark);display:flex">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                  </span>
                  <span class="stg-mono">{{ $file['filename'] }}</span>
                </div>
              </td>
              <td><span class="stg-badge" style="background:var(--input-bg);color:var(--text-secondary)">{{ $file['size_formatted'] }}</span></td>
              <td><span class="stg-date">{{ $file['date_formatted'] }}</span></td>
              <td>
                <div class="flex items-center gap-1">
                  <a href="{{ route('admin.system.backup.download', $file['filename']) }}" class="stg-act stg-act-dl" title="تحميل" aria-label="تحميل">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                  </a>
                  <form method="POST" action="{{ route('admin.system.backup.restore', $file['filename']) }}" class="stg-restore-form" style="display:inline">
                    @csrf
                    <button type="button" class="stg-act stg-act-rst stg-restore-trigger" data-filename="{{ $file['filename'] }}" title="استعادة" aria-label="استعادة">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg>
                    </button>
                  </form>
                  <form method="POST" action="{{ route('admin.system.backup.delete', $file['filename']) }}" style="display:inline"
                        onsubmit="return confirm('تأكيد حذف النسخة الاحتياطية: {{ $file['filename'] }}؟')">
                    @csrf @method('DELETE')
                    <input type="hidden" name="confirm_name" value="{{ $file['filename'] }}">
                    <button type="submit" class="stg-act stg-act-del" title="حذف" aria-label="حذف">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @endif
    </div>

    <div class="stg-note stg-note-danger">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      <span><strong>تنبيه هام:</strong> استعادة قاعدة البيانات تؤدي إلى فقدان أي تغييرات تمت بعد تاريخ إنشاء النسخة الاحتياطية. يُوصى بعمل نسخة احتياطية جديدة قبل استعادة أي نسخة سابقة.</span>
    </div>
  </section>

  {{-- ─── 7. APP UPDATE ─── --}}
  <section x-show="section === 'appupdate'" x-cloak x-transition:enter.duration.200ms.opacity>
    @if(session('success'))
    <div class="stg-toast stg-toast-success">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      <span>{{ session('success') }}</span>
    </div>
    @endif
    @if(session('error'))
    <div class="stg-toast stg-toast-error">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
      <span>{{ session('error') }}</span>
    </div>
    @endif

    <div class="stg-card">
      <div class="stg-card-hdr">
        <div class="stg-card-hdr-l">
          <span class="stg-card-ico" style="background:var(--accent-soft);color:var(--accent-dark)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>
          </span>
          <div>
            <h3 class="stg-card-title">تحديث التطبيق</h3>
            <p class="stg-card-sub">إجبار المستخدمين على تحديث التطبيق — اضبط أدنى إصدار مسموح</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <span class="badge {{ ($cfg['app_update_enabled'] ?? true) ? 'badge-success' : 'badge-secondary' }}">{{ ($cfg['app_update_enabled'] ?? true) ? 'مفعّل' : 'متوقف' }}</span>
        </div>
      </div>
    </div>

    <form method="POST" action="{{ route('admin.system.app-update.update') }}">
      @csrf @method('PUT')

      <div class="stg-card">
        <div class="stg-card-hdr">
          <div class="stg-card-hdr-l">
            <span class="stg-card-ico">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="5" width="22" height="14" rx="2" ry="2"/><line x1="1" y1="9" x2="23" y2="9"/></svg>
            </span>
            <h3 class="stg-card-title">التفعيل والإجبار</h3>
          </div>
        </div>
        <div class="stg-card-bd" style="display:flex;flex-direction:column;gap:8px">
          <label class="stg-tgl">
            <span>
              <span class="stg-tgl-t">تفعيل فحص التحديث</span>
              <span class="stg-tgl-d">عند الإيقاف لن يُحجب أي مستخدم مهما كان إصداره.</span>
            </span>
            <span class="switch">
              <input type="hidden" name="app_update_enabled" value="0">
              <input type="checkbox" name="app_update_enabled" value="1" {{ ($cfg['app_update_enabled'] ?? true) ? 'checked' : '' }}>
              <span class="switch-track"></span><span class="switch-thumb"></span>
            </span>
          </label>
          <label class="stg-tgl">
            <span>
              <span class="stg-tgl-t">إجبار الجميع فوراً (طوارئ)</span>
              <span class="stg-tgl-d">يحجب كل المستخدمين بصرف النظر عن إصدارهم.</span>
            </span>
            <span class="switch">
              <input type="hidden" name="app_force_update" value="0">
              <input type="checkbox" name="app_force_update" value="1" {{ ($cfg['app_force_update'] ?? false) ? 'checked' : '' }}>
              <span class="switch-track"></span><span class="switch-thumb"></span>
            </span>
          </label>
        </div>
      </div>

      <div class="stg-card">
        <div class="stg-card-hdr">
          <div class="stg-card-hdr-l">
            <span class="stg-card-ico">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </span>
            <h3 class="stg-card-title">الإصدارات وأرقام البناء</h3>
          </div>
          <span class="stg-bc">build = versionCode</span>
        </div>
        <div class="stg-card-bd">
          <div class="stg-fld-row">
            <div class="stg-fld">
              <label class="stg-fld-lbl">أدنى رقم بناء مطلوب</label>
              <input type="number" min="1" name="app_min_build" class="input input-mono"
                     value="{{ old('app_min_build', $cfg['app_min_build'] ?? 1) }}" required>
              <span class="stg-fld-hint">من كان رقم بنائه أقل من هذا الرقم يُحجب.</span>
            </div>
            <div class="stg-fld">
              <label class="stg-fld-lbl">أدنى إصدار (للعرض)</label>
              <input type="text" name="app_min_version" class="input input-mono" dir="ltr"
                     placeholder="1.0.0" value="{{ old('app_min_version', $cfg['app_min_version'] ?? '1.0.0') }}" required>
            </div>
            <div class="stg-fld">
              <label class="stg-fld-lbl">رقم بناء أحدث إصدار</label>
              <input type="number" min="1" name="app_latest_build" class="input input-mono"
                     value="{{ old('app_latest_build', $cfg['app_latest_build'] ?? 1) }}" required>
            </div>
            <div class="stg-fld">
              <label class="stg-fld-lbl">أحدث إصدار</label>
              <input type="text" name="app_latest_version" class="input input-mono" dir="ltr"
                     placeholder="1.0.1" value="{{ old('app_latest_version', $cfg['app_latest_version'] ?? '1.0.0') }}" required>
            </div>
          </div>
        </div>
      </div>

      <div class="stg-card">
        <div class="stg-card-hdr">
          <div class="stg-card-hdr-l">
            <span class="stg-card-ico">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            </span>
            <h3 class="stg-card-title">رابط التحميل والرسالة</h3>
          </div>
        </div>
        <div class="stg-card-bd">
          <div class="stg-fld">
            <label class="stg-fld-lbl">رابط تحميل النسخة الجديدة</label>
            <input type="url" name="app_download_url" class="input input-mono" dir="ltr"
                   placeholder="https://sakk.zanjour.com/download/sakk.apk"
                   value="{{ old('app_download_url', $cfg['app_download_url'] ?? '') }}" required>
            <span class="stg-fld-hint">الزر في شاشة الحجب يفتح هذا الرابط مباشرة.</span>
          </div>
          <div class="stg-fld">
            <label class="stg-fld-lbl">عنوان شاشة التحديث</label>
            <input type="text" name="app_update_title" class="input"
                   value="{{ old('app_update_title', $cfg['app_update_title'] ?? 'تحديث مطلوب') }}" required>
          </div>
          <div class="stg-fld">
            <label class="stg-fld-lbl">نص الرسالة للمستخدم</label>
            <textarea name="app_update_message" rows="3" class="input" required>{{ old('app_update_message', $cfg['app_update_message'] ?? 'يتوفّر إصدار جديد من تطبيق صكّ. يرجى التحديث للمتابعة.') }}</textarea>
          </div>
        </div>
      </div>

      <div class="stg-card" style="padding:12px 18px;display:flex;justify-content:flex-end">
        <button type="submit" class="btn btn-primary">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;display:inline;vertical-align:middle;margin-left:4px"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
          حفظ السياسة
        </button>
      </div>
    </form>
  </section>

  {{-- ─── 8. AUDIT ─── --}}
  <section x-show="section === 'audit'" x-cloak x-transition:enter.duration.200ms.opacity>
    <div class="stg-card">
      <div class="stg-card-hdr">
        <div class="stg-card-hdr-l">
          <span class="stg-card-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
          </span>
          <div>
            <h3 class="stg-card-title">سجل النشاطات</h3>
            <p class="stg-card-sub">آخر 50 نشاط على المنصة</p>
          </div>
        </div>
        <a href="{{ route('admin.audit.index') }}" class="btn btn-ghost btn-sm">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;display:inline;vertical-align:middle;margin-left:4px"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
          الصفحة الكاملة
        </a>
      </div>
    </div>

    <div class="stg-card">
      <div class="stg-card-bd">
        <form method="GET" action="{{ route('admin.settings') }}#audit" class="flex items-center gap-2 flex-wrap">
          <div class="stg-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
            <input type="search" name="audit_search" value="{{ request('audit_search') }}"
                   placeholder="بحث بالإجراء أو النوع أو IP…" aria-label="بحث في سجل النشاطات">
          </div>
          <button type="submit" class="btn btn-primary btn-sm">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;display:inline;vertical-align:middle;margin-left:4px"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
            بحث
          </button>
          @if(request('audit_search'))
          <a href="{{ route('admin.settings') }}#audit" class="btn btn-ghost btn-sm">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;display:inline;vertical-align:middle;margin-left:4px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            مسح
          </a>
          @endif
        </form>
      </div>
    </div>

    <div class="stg-card">
      @if($auditLogs->count() > 0)
      <div class="stg-tbl-wrap">
        <table class="stg-tbl">
          <thead>
            <tr>
              <th>التاريخ</th>
              <th>المشرف</th>
              <th>الإجراء</th>
              <th>النوع</th>
              <th>المعرف</th>
              <th>IP</th>
            </tr>
          </thead>
          <tbody>
            @foreach($auditLogs as $log)
            @php
              $ab = match ($log->action) {
                  'created','create','store'      => 'success',
                  'updated','update'               => 'warning',
                  'deleted','delete','destroy'     => 'danger',
                  'login','logout'                 => 'slate',
                  'suspended','activated','banned' => 'danger',
                  default                          => 'slate',
              };
              $ms = class_basename($log->model_type ?? '');
              $an = $log->user?->first_name
                  ? $log->user->first_name . ' ' . ($log->user->last_name ?? '')
                  : ($log->user?->email ?? '—');
            @endphp
            <tr>
              <td><span class="stg-date">{{ $log->created_at?->format('Y/m/d H:i') }}</span></td>
              <td><span style="font-weight:700;font-size:0.74rem;color:var(--text-primary)">{{ $an }}</span></td>
              <td><span class="badge badge-{{ $ab }}">{{ $log->action }}</span></td>
              <td>@if($ms)<span class="badge badge-secondary">{{ $ms }}</span>@else<span style="color:var(--text-muted);font-size:.7rem">—</span>@endif</td>
              <td>@if($log->model_id)<span class="stg-mono" style="color:var(--text-secondary)">#{{ $log->model_id }}</span>@else<span style="color:var(--text-muted);font-size:.7rem">—</span>@endif</td>
              <td>@if($log->ip_address)<span class="stg-mono" style="color:var(--text-muted)">{{ $log->ip_address }}</span>@else<span style="color:var(--text-muted);font-size:.7rem">—</span>@endif</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @else
      <div class="stg-card-bd">
        <div class="stg-empty">
          <div class="stg-empty-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
          </div>
          <div class="stg-empty-t">لا توجد سجلات نشاط</div>
          <div class="stg-empty-d">لم يتم تسجيل أي نشاط حتى الآن.</div>
        </div>
      </div>
      @endif
    </div>
  </section>

</div>

{{-- ═══ RESTORE CONFIRM MODAL ═══ --}}
<div id="stg-restore-dialog" class="modal-backdrop" style="display:none" role="dialog" aria-modal="true" aria-labelledby="stg-restore-title"
     onclick="if(event.target===this)this.style.display='none'">
    <div class="modal">
        <div class="modal-header">
            <h3 id="stg-restore-title" class="card-title" style="gap:.5rem">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;color:var(--warning)"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                تأكيد استعادة قاعدة البيانات
            </h3>
        </div>
        <div class="modal-body">
            <p id="stg-restore-msg" style="color:var(--text-secondary);line-height:1.7">
                سيتم استبدال قاعدة البيانات الحالية بالكامل. هل أنت متأكد؟
            </p>
        </div>
        <div class="modal-footer">
            <button onclick="document.getElementById('stg-restore-dialog').style.display='none'" class="btn btn-secondary">إلغاء</button>
            <button id="stg-restore-btn" class="btn btn-gold">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;display:inline;vertical-align:middle;margin-left:4px"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg>
                تأكيد الاستعادة
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('settingsPage', () => ({
        section: 'general',
        csrf: document.querySelector('meta[name="csrf-token"]').content,
        tick: null,
        vals: @json($settings),
        sections: [
            { id: 'general',   label: 'عام',             badge: null },
            { id: 'limits',    label: 'الحدود',          badge: null },
            { id: 'referral',  label: 'الإحالة',         badge: null },
            { id: 'system',    label: 'النظام',          badge: null },
            { id: 'health',    label: 'صحة النظام',      badge: '{{ $overallOnline ? 'جيد' : 'تنبيه' }}' },
            { id: 'backup',    label: 'النسخ الاحتياطي',  badge: '{{ $backupCount }}' },
            { id: 'appupdate', label: 'التحديثات',       badge: null },
            { id: 'audit',     label: 'سجل النشاطات',    badge: null },
        ],
        init() {
            const h = window.location.hash.replace('#', '');
            if (h && this.sections.some(s => s.id === h)) this.section = h;
            this.$watch('section', v => history.replaceState(null, '', '#' + v));
        },
        flash(key) { this.tick = key; setTimeout(() => { if (this.tick === key) this.tick = null; }, 1600); },
        async save(key, value) {
            try {
                const r = await fetch('{{ route('admin.settings.setting.update') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify({ key, value }),
                });
                const d = await r.json().catch(() => ({}));
                if (!r.ok || !d.ok) throw new Error(d.message || 'فشل الحفظ');
                this.flash(key);
                this.toast('success', d.message || 'تم الحفظ');
            } catch (e) { this.toast('error', e.message || 'فشل الحفظ'); }
        },
        confirmMaintenance(e) {
            const turningOn = e.target.checked;
            if (turningOn && !confirm('تفعيل وضع الصيانة سيوقف وصول المستخدمين للتطبيق. متابعة؟')) {
                e.target.checked = false;
                this.vals.maintenance_mode = false;
                return;
            }
            this.save('maintenance_mode', this.vals.maintenance_mode);
        },
        toast(type, message) {
            window.dispatchEvent(new CustomEvent('toast', { detail: { type, message } }));
        },
        handleSubmit(e) {
            const btn = e.target.querySelector('button[type="submit"]');
            if (btn) {
                btn.classList.add('loading');
                btn.disabled = true;
                setTimeout(() => { btn.classList.remove('loading'); btn.disabled = false; }, 15000);
            }
            return true;
        },
    }));
});

/* ── Health AJAX ── */
(function() {
    const btn = document.getElementById('stg-health-rerun');
    if (!btn) return;
    const grid = document.getElementById('stg-health-grid');
    btn.addEventListener('click', function() {
        btn.classList.add('loading'); btn.disabled = true;
        fetch(btn.dataset.url, {
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        })
        .then(r => { if (!r.ok) throw new Error('فشل الطلب'); return r.json(); })
        .then(data => {
            if (!data.checks) return;
            Object.keys(data.checks).forEach(key => {
                const c = data.checks[key];
                const card = grid.querySelector(`[data-health-key="${key}"]`);
                if (!card) return;
                const on = c.status === 'online';
                const badge = card.querySelector('.health-status-badge');
                if (badge) { badge.textContent = on ? 'متصل' : 'غير متصل'; badge.style.color = on ? 'var(--success)' : 'var(--danger)'; }
                const vals = card.querySelectorAll('.stg-health-stat-v');
                if (vals[0]) vals[0].innerHTML = c.response_time + ' <span style="font-weight:400;color:var(--text-muted)">ms</span>';
                const det = card.querySelector('.stg-health-detail');
                if (det) det.textContent = c.details;
                const dot = card.querySelector('.stg-health-dot');
                if (dot) { dot.className = 'stg-health-dot ' + (on ? 'online' : 'offline'); }
                const ov = document.getElementById('stg-health-overall');
                if (ov) {
                    const isOk = data.overall === 'online';
                    ov.className = 'badge ' + (isOk ? 'badge-success' : 'badge-warning');
                    ov.textContent = isOk ? 'جميع الخدمات تعمل' : 'بعض الخدمات بحاجة للانتباه';
                }
            });
        })
        .catch(err => window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'فشل الفحص: ' + err.message } })))
        .finally(() => { btn.classList.remove('loading'); btn.disabled = false; });
    });
})();

/* ── Restore confirm ── */
(function() {
    document.querySelectorAll('.stg-restore-form').forEach(form => {
        form.querySelector('.stg-restore-trigger').addEventListener('click', function(e) {
            e.preventDefault();
            const fn = this.dataset.filename;
            const dlg = document.getElementById('stg-restore-dialog');
            const msg = document.getElementById('stg-restore-msg');
            const btn = document.getElementById('stg-restore-btn');
            if (!dlg || !msg || !btn) return;
            msg.innerHTML = 'سيتم استبدال قاعدة البيانات الحالية بالكامل بنسخة: <strong dir="ltr">' + fn + '</strong>.<br><br>سيتم حفظ نسخة احتياطية تلقائية من الوضع الحالي قبل الاستعادة.';
            dlg.style.display = 'flex';
            btn.onclick = () => {
                dlg.style.display = 'none';
                const f = document.createElement('form');
                f.method = 'POST';
                f.action = form.action;
                ['_token','confirm_name'].forEach(n => {
                    const i = document.createElement('input');
                    i.type = 'hidden';
                    i.name = n;
                    i.value = n === '_token' ? '{{ csrf_token() }}' : fn;
                    f.appendChild(i);
                });
                document.body.appendChild(f);
                f.submit();
            };
        });
    });
})();
</script>
@endpush
@endsection
