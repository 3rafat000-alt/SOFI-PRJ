@extends('layouts.admin')

@section('title', 'سجل النشاطات')

@section('breadcrumb')
<nav class="sakk-breadcrumb" aria-label="مسار التنقل">
    <a href="{{ route('admin.dashboard') }}" class="sakk-breadcrumb__item">الرئيسية</a>
    <span class="sakk-breadcrumb__sep" aria-hidden="true">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    </span>
    <span class="sakk-breadcrumb__item sakk-breadcrumb__item--active">سجل النشاطات</span>
</nav>
@endsection

@section('content')

<div class="sakk-wrap">

    {{-- ── HEADER ── --}}
    <div class="sakk-page-hdr">
        <div class="sakk-page-hdr__left">
            <div class="sakk-page-hdr__icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
            </div>
            <div>
                <h1 class="sakk-page-hdr__title">سجل النشاطات</h1>
                <p class="sakk-page-hdr__sub">تتبع جميع الإجراءات التي قام بها المشرفون على المنصة</p>
            </div>
        </div>
        <span class="sakk-kpi-pill">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <span class="sakk-kpi-pill__value">{{ number_format($logs->total()) }}</span> سجل
        </span>
    </div>

    {{-- ── FILTER CARD ── --}}
    <div class="sakk-card">
        <div class="sakk-card__body">
            <form method="GET" action="{{ route('admin.audit.index') }}" class="sakk-filter-row" role="search" aria-label="بحث في سجل النشاطات">
                <div class="sakk-filter-field sakk-filter-field--grow">
                    <label for="audit-search" class="sakk-filter-label">بحث</label>
                    <div class="sakk-input-wrap">
                        <span class="sakk-input-icon">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        </span>
                        <input id="audit-search" type="search" name="search" value="{{ request('search') }}"
                               placeholder="بحث بالإجراء أو النوع أو IP…"
                               class="sakk-input sakk-input--with-icon" autocomplete="off" aria-label="بحث في سجل النشاطات">
                    </div>
                </div>
                <div class="sakk-filter-field">
                    <label for="audit-action" class="sakk-filter-label">الإجراء</label>
                    <select id="audit-action" name="action" class="sakk-filter-select" aria-label="تصفية بالإجراء">
                        <option value="">كل الإجراءات</option>
                        @foreach($actions as $a)
                            <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>{{ $a }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sakk-filter-field">
                    <label for="audit-model" class="sakk-filter-label">نوع السجل</label>
                    <select id="audit-model" name="model_type" class="sakk-filter-select" aria-label="تصفية بنوع السجل">
                        <option value="">جميع الأنواع</option>
                        @foreach($modelTypes as $mt)
                            <option value="{{ $mt }}" {{ request('model_type') === $mt ? 'selected' : '' }}>{{ class_basename($mt) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sakk-filter-field">
                    <label for="audit-date-from" class="sakk-filter-label">من تاريخ</label>
                    <input id="audit-date-from" type="date" name="date_from" value="{{ request('date_from') }}" class="sakk-input" aria-label="من تاريخ">
                </div>
                <div class="sakk-filter-field">
                    <label for="audit-date-to" class="sakk-filter-label">إلى تاريخ</label>
                    <input id="audit-date-to" type="date" name="date_to" value="{{ request('date_to') }}" class="sakk-input" aria-label="إلى تاريخ">
                </div>
                <div class="sakk-filter-actions">
                    <button type="submit" class="sakk-btn sakk-btn--primary" aria-label="تطبيق التصفية">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        بحث
                    </button>
                    @if(request()->hasAny(['search','action','model_type','date_from','date_to','user_id']))
                    <a href="{{ route('admin.audit.index') }}" class="sakk-btn sakk-btn--ghost" aria-label="مسح الفلاتر">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        مسح
                    </a>
                    @endif
                    @if($logs->total() > 0)
                    <a href="{{ route('admin.audit.export', request()->query()) }}" class="sakk-btn sakk-btn--ghost" aria-label="تصدير كـ CSV" target="_blank">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        تصدير CSV
                    </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- ── LOGS TABLE ── --}}
    <div class="sakk-card">
        <div class="sakk-card__body--nopad">
            @if($logs->count() > 0)
            <div class="sakk-gold-table-wrap">
                <table class="sakk-gold-table" id="audit-table" aria-label="جدول سجل النشاطات">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>المشرف</th>
                            <th>الإجراء</th>
                            <th>نوع السجل</th>
                            <th>المعرف</th>
                            <th>IP</th>
                            <th style="text-align:center">تفاصيل</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        @php
                            $actionBadge = match ($log->action) {
                                'created', 'create', 'store'        => 'success',
                                'updated', 'update'                 => 'warning',
                                'deleted', 'delete', 'destroy'      => 'danger',
                                'login', 'logout'                   => 'slate',
                                'suspended', 'activated', 'banned'  => 'danger',
                                default                             => 'slate',
                            };
                            $modelShort = class_basename($log->model_type ?? '');
                            $adminName  = $log->user?->first_name
                                ? $log->user->first_name . ' ' . ($log->user->last_name ?? '')
                                : ($log->user?->email ?? '—');
                            $initials   = $log->user
                                ? mb_strtoupper(mb_substr($log->user->first_name ?? '?', 0, 1))
                                : '?';
                            $logId = $log->id ?? $loop->index;
                        @endphp
                        <tr id="audit-row-{{ $logId }}">
                            {{-- Timestamp --}}
                            <td>
                                <span class="sakk-ts" title="{{ $log->created_at?->format('Y/m/d H:i:s') }}">
                                    {{ $log->created_at?->format('Y/m/d H:i') }}
                                </span>
                            </td>

                            {{-- Admin --}}
                            <td>
                                <div class="sakk-user-cell">
                                    <div class="sakk-user-avatar" aria-hidden="true">{{ $initials }}</div>
                                    <span class="sakk-user-name">{{ $adminName }}</span>
                                </div>
                            </td>

                            {{-- Action badge --}}
                            <td>
                                <span class="sakk-action-badge sakk-action-badge--{{ $actionBadge }}">
                                    @if(in_array($actionBadge, ['success','warning','danger']))
                                    <span style="width:5px;height:5px;border-radius:50%;display:inline-block;background:currentColor;"></span>
                                    @endif
                                    {{ $log->action }}
                                </span>
                            </td>

                            {{-- Model type --}}
                            <td>
                                @if($modelShort)
                                <span class="sakk-model-tag" title="{{ $log->model_type }}">{{ $modelShort }}</span>
                                @else
                                <span style="color:var(--text-secondary);font-size:0.72rem;">—</span>
                                @endif
                            </td>

                            {{-- Model ID --}}
                            <td>
                                @if($log->model_id)
                                <span class="sakk-model-id">#{{ $log->model_id }}</span>
                                @else
                                <span style="color:var(--text-secondary);font-size:0.72rem;">—</span>
                                @endif
                            </td>

                            {{-- IP --}}
                            <td>
                                @if($log->ip_address)
                                <span class="sakk-ts">{{ $log->ip_address }}</span>
                                @else
                                <span style="color:var(--text-secondary);font-size:0.72rem;">—</span>
                                @endif
                            </td>

                            {{-- Details expand --}}
                            <td style="text-align:center">
                                @if($log->properties || $log->details)
                                <button type="button" class="sakk-detail-btn"
                                        onclick="sakkToggleJson('{{ $logId }}')"
                                        title="عرض التفاصيل" aria-label="عرض التفاصيل"
                                        aria-expanded="false"
                                        id="detail-btn-{{ $logId }}">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                                @else
                                <span style="color:var(--border-light);font-size:0.72rem;">—</span>
                                @endif
                            </td>
                        </tr>

                        {{-- Expandable JSON row --}}
                        <tr id="audit-json-{{ $logId }}" class="sakk-json-row">
                            <td colspan="7" class="sakk-json-cell">
                                @php
                                    $properties = $log->properties ?? $log->details ?? null;
                                @endphp
                                @if($properties)
                                <pre class="sakk-json-pre">{{ is_string($properties) ? $properties : json_encode($properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                @else
                                <span style="color:var(--text-secondary);font-size:0.78rem;">لا توجد تفاصيل إضافية</span>
                                @endif
                            </td>
                        </tr>

                        @empty
                        <tr>
                            <td colspan="7">
                                <div class="sakk-empty">
                                    <div class="sakk-empty__icon">
                                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--border-light)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                                    </div>
                                    <p class="sakk-empty__title">
                                        {{ request()->hasAny(['search','action','model_type','date_from','date_to']) ? 'لا توجد نتائج' : 'لا توجد سجلات' }}
                                    </p>
                                    <p class="sakk-empty__desc">
                                        {{ request()->hasAny(['search','action','model_type','date_from','date_to']) ? 'لم تتطابق أي سجلات مع معايير البحث.' : 'لم يتم تسجيل أي نشاط حتى الآن.' }}
                                    </p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
            <div class="sakk-pagination-wrap">
                {{ $logs->links() }}
            </div>
            @endif

            @else
            <div class="sakk-empty">
                <div class="sakk-empty__icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--border-light)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                </div>
                <p class="sakk-empty__title">لا توجد سجلات</p>
                <p class="sakk-empty__desc">لم يتم تسجيل أي نشاط حتى الآن.</p>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    'use strict';
    window.sakkToggleJson = function(logId) {
        var row = document.getElementById('audit-json-' + logId);
        var btn = document.getElementById('detail-btn-' + logId);
        if (!row) return;
        var isVisible = row.classList.contains('is-visible');
        if (isVisible) {
            row.classList.remove('is-visible');
            if (btn) {
                btn.setAttribute('aria-expanded', 'false');
                btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
            }
        } else {
            row.classList.add('is-visible');
            if (btn) {
                btn.setAttribute('aria-expanded', 'true');
                btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
            }
        }
    };
})();
</script>
@endpush
@endsection
