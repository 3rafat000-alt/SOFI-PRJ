@extends('layouts.admin')

@section('title', 'صحة النظام')
@section('breadcrumbs')
<span class="breadcrumb-item">النظام</span>
<span class="breadcrumb-item">صحة النظام</span>
@endsection

@section('content')
<div class="space-y-6">
    {{-- ════════ HEADER ════════ --}}
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">
                    <x-heroicon name="monitor_heart" />
                    صحة النظام
                </div>
                <p class="card-subtitle" style="margin-top:0.35rem">
                    فحص شامل لحالة النظام والخدمات المرتبطة
                </p>
            </div>

            <div class="flex items-center gap-3">
                @php
                    $overallOnline = collect($checks)->every(fn ($c) => $c['status'] === 'online');
                    $overallStatus = $overallOnline ? 'online' : 'degraded';
                    $overallLabel = $overallOnline ? 'جميع الخدمات تعمل' : 'بعض الخدمات بحاجة للانتباه';
                @endphp

                <span class="badge {{ $overallStatus === 'online' ? 'badge-success' : 'badge-warning' }}">
                    {{ $overallLabel }}
                </span>

                <button type="button"
                        class="btn btn-primary"
                        id="health-run-checks"
                        data-url="{{ route('admin.system.health.checks') }}">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="23 4 23 10 17 10"/>
                        <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
                    </svg>
                    تشغيل الفحص
                </button>
            </div>
        </div>
    </div>

    {{-- ════════ HEALTH CARDS GRID ════════ --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="health-grid">
        @foreach($checks as $key => $check)
            @php
                $statusClass = $check['status'] === 'online' ? 'badge-success' : 'badge-danger';
                $statusLabel = $check['status'] === 'online' ? 'متصل' : 'غير متصل';
            @endphp
            <div class="card" data-health-key="{{ $key }}">
                <div class="card-header">
                    <div class="card-title">
                        <span class="stat-icon" style="background:var(--accent-soft);color:var(--accent);width:36px;height:36px;font-size:1rem">
                            @switch($check['icon'] ?? 'info')
                                @case('storage')
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                                @break
                                @case('speed')
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a10 10 0 0 0-7.07 17.07l1.41-1.41A8 8 0 1 1 12 2z"/><path d="M12 6v6l3 3"/></svg>
                                @break
                                @case('queue')
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                                @break
                                @case('folder')
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                                @break
                                @case('schedule')
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/><path d="M21 3l-3 3"/></svg>
                                @break
                                @default
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                            @endswitch
                        </span>
                        {{ $check['name'] }}
                    </div>

                    <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </div>

                <div class="card-body space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="stat-label" style="margin-bottom:0">وقت الاستجابة</span>
                        <span class="font-bold" style="color:var(--text-primary)">{{ $check['response_time'] }} ms</span>
                    </div>
                    @if(!empty($check['uptime']))
                    <div class="flex justify-between items-center">
                        <span class="stat-label" style="margin-bottom:0">وقت التشغيل</span>
                        <span class="font-bold" style="color:var(--text-primary)">{{ $check['uptime'] }}</span>
                    </div>
                    @endif
                    @if(!empty($check['last_checked']))
                    <div class="flex justify-between items-center">
                        <span class="stat-label" style="margin-bottom:0">آخر فحص</span>
                        <span class="font-bold" style="color:var(--text-primary)">{{ $check['last_checked'] }}</span>
                    </div>
                    @endif
                    <p class="text-sm" style="color:var(--text-secondary)" dir="auto">{{ $check['details'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ════════ INFO FOOTER ════════ --}}
    <div class="sys-note" style="--note:var(--accent)">
        <x-heroicon name="info" />
        <div class="bd">
            <strong>معلومات:</strong>
            يتم فحص الخدمات والإضافات في كل مرة يتم فيها تحميل الصفحة.
            زر "تشغيل الفحص" يعيد تنفيذ الفحوصات عبر AJAX دون إعادة تحميل الصفحة.
        </div>
    </div>
</div>

{{-- ════════ HEALTH CHECK AJAX ════════ --}}
@once
@push('scripts')
<script>
(function () {
    'use strict';

    var runBtn = document.getElementById('health-run-checks');
    if (!runBtn) return;

    var grid = document.getElementById('health-grid');

    runBtn.addEventListener('click', function () {
        runBtn.classList.add('loading');
        runBtn.disabled = true;

        var url = runBtn.getAttribute('data-url');

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
        })
        .then(function (r) {
            if (!r.ok) throw new Error('فشل طلب الفحص');
            return r.json();
        })
        .then(function (data) {
            updateCards(data.checks);
            updateOverall(data.overall);
        })
        .catch(function (err) {
            console.error('Health check error:', err);
            showToast('فشل الفحص: ' + err.message, 'error');
        })
        .finally(function () {
            runBtn.classList.remove('loading');
            runBtn.disabled = false;
        });
    });

    function updateCards(checks) {
        if (!grid) return;

        Object.keys(checks).forEach(function (key) {
            var check = checks[key];
            var card = grid.querySelector('[data-health-key="' + key + '"]');
            if (!card) return;

            var statusClass = check.status === 'online' ? 'online' : 'offline';
            var statusLabel = check.status === 'online' ? 'متصل' : 'غير متصل';

            var indicator = card.querySelector('.health-card__indicator');
            if (indicator) indicator.className = 'health-card__indicator ' + statusClass;

            var icon = card.querySelector('.health-card__icon');
            if (icon) icon.className = 'health-card__icon ' + statusClass;

            var badge = card.querySelector('.health-status-badge');
            if (badge) {
                badge.className = 'health-status-badge ' + statusClass;
                var dot = badge.querySelector('.health-status-dot');
                if (dot) dot.className = 'health-status-dot ' + statusClass;
                badge.childNodes.forEach(function (n) {
                    if (n.nodeType === 3) n.textContent = statusLabel;
                });
            }

            var statValues = card.querySelectorAll('.health-card__stat-value');
            if (statValues.length > 0) {
                statValues[0].textContent = check.response_time + ' ms';
            }
            if (statValues.length > 1 && check.uptime) {
                statValues[1].textContent = check.uptime;
            }
            if (statValues.length > 2 && check.last_checked) {
                statValues[2].textContent = check.last_checked;
            }

            var details = card.querySelector('.health-card__details');
            if (details) details.textContent = check.details;
        });
    }

    function updateOverall(overall) {
        var badge = document.querySelector('.health-overall-badge');
        if (!badge) return;

        var isOnline = overall === 'online';
        badge.className = 'health-overall-badge ' + (isOnline ? 'online' : 'warning');

        var dot = badge.querySelector('.health-status-dot');
        if (dot) dot.className = 'health-status-dot ' + (isOnline ? 'online' : 'warning');

        badge.childNodes.forEach(function (n) {
            if (n.nodeType === 3) {
                n.textContent = isOnline ? 'جميع الخدمات تعمل' : 'بعض الخدمات بحاجة للانتباه';
            }
        });
    }

    function showToast(msg, type) {
        window.dispatchEvent(new CustomEvent('toast', {detail: {type: type === 'error' ? 'error' : 'success', message: msg}}));
    }
})();
</script>
@endpush
@endonce
@endsection
