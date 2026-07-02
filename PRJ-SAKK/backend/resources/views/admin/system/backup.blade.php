@extends('layouts.admin')

@section('title', 'النسخ الاحتياطي لقاعدة البيانات')
@section('breadcrumb')
<nav class="sakk-breadcrumb" aria-label="مسار التنقل">
    <a href="{{ route('admin.dashboard') }}" class="breadcrumb-item">الرئيسية</a>
    <a href="{{ route('admin.settings') }}" class="breadcrumb-item">إعدادات النظام</a>
    <span class="breadcrumb-item">النسخ الاحتياطي</span>
</nav>
@endsection

@section('content')
<div class="space-y-5">

    {{-- ── HEADER ── --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span class="stat-icon" style="background:var(--primary);color:#fff;width:44px;height:44px;font-size:1.25rem">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                </span>
                <div>
                    <div>النسخ الاحتياطي لقاعدة البيانات</div>
                    <p class="card-subtitle">إنشاء وإدارة النسخ الاحتياطية. <strong style="color:var(--danger)">الاستعادة ستستبدل قاعدة البيانات الحالية بالكامل.</strong></p>
                </div>
            </div>
        </div>
    </div>

    {{-- ── FLASH ── --}}
    @if(session('success'))
    <div class="sys-note" style="--note:var(--success)">
        <x-heroicon name="check_circle" />
        <div class="bd">{{ session('success') }}</div>
    </div>
    @endif
    @if(session('error'))
    <div class="sys-note" style="--note:var(--danger)">
        <x-heroicon name="error" />
        <div class="bd">{{ session('error') }}</div>
    </div>
    @endif

    {{-- ── DB INFO ── --}}
    <div class="card">
        <div class="card-body">
            <div class="card-row">
                <div class="stat-card" style="padding:1rem">
                    <div class="stat-label">نوع قاعدة البيانات</div>
                    <div class="stat-value" dir="ltr" style="font-size:1.15rem">
                        {{ $dbConnection === 'sqlite' ? 'SQLite' : ($dbConnection === 'mysql' ? 'MySQL' : $dbConnection) }}
                    </div>
                </div>
                <div class="stat-card" style="padding:1rem">
                    <div class="stat-label">حجم قاعدة البيانات</div>
                    <div class="stat-value" style="font-size:1.15rem">{{ $dbSize }}</div>
                </div>
                <div class="stat-card" style="padding:1rem">
                    <div class="stat-label">عدد النسخ الاحتياطية</div>
                    <div class="stat-value" style="font-size:1.15rem">{{ $files->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── CREATE BACKUP ── --}}
    <div class="card">
        <div class="card-body">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h3 class="font-bold" style="color:var(--text-primary)">إنشاء نسخة احتياطية جديدة</h3>
                    <p style="font-size:var(--font-size-sm);color:var(--text-muted)">سيتم إنشاء نسخة كاملة من قاعدة البيانات في مجلد النسخ الاحتياطية.</p>
                </div>
                <form method="POST" action="{{ route('admin.system.backup.create') }}">
                    @csrf
                    <button type="submit" class="btn btn-gold" style="padding:0.6rem 1.5rem;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        إنشاء نسخة احتياطية
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ── TOTAL SIZE WARNING ── --}}
    @if($files->count() > 0)
    @php
        $totalSize = $files->sum('size');
        $totalFormatted = '';
        if ($totalSize > 0) {
            $units = ['B', 'KB', 'MB', 'GB'];
            $pow = floor(($totalSize ? log($totalSize) : 0) / log(1024));
            $pow = min($pow, count($units) - 1);
            $totalFormatted = round($totalSize / (1 << (10 * $pow)), 2) . ' ' . $units[$pow];
        }
    @endphp
    <div class="sys-note" style="--note:var(--warning)">
        <x-heroicon name="info" />
        <div class="bd">
            <strong>إجمالي مساحة النسخ الاحتياطية:</strong> {{ $totalFormatted }}
            — يُنصح بنقل النسخ القديمة إلى وحدة تخزين خارجية بشكل دوري.
        </div>
    </div>
    @endif

    {{-- ── BACKUPS TABLE ── --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <x-heroicon name="history" />
                النسخ الاحتياطية الموجودة
            </div>
        </div>
        <div class="card-body" style="padding:0">
            @if($files->isEmpty())
            <div class="table-empty">
                <x-heroicon name="backup" class="table-empty-icon" />
                <p>لا توجد نسخ احتياطية بعد</p>
                <p style="color:var(--text-muted);font-size:var(--font-size-sm)">قم بإنشاء أول نسخة احتياطية باستخدام الزر أعلاه.</p>
            </div>
            @else
            <div class="table-container" style="border:none;box-shadow:none">
                <table class="table">
                    <thead>
                        <tr>
                            <th>اسم الملف</th>
                            <th class="text-center">الحجم</th>
                            <th class="text-center">التاريخ</th>
                            <th class="text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($files as $file)
                        <tr>
                            <td>
                                <div class="flex items-center gap-2">
                                    <span style="color:var(--accent-dark);display:flex;align-items:center">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                                    </span>
                                    <span style="font-family:ui-monospace,monospace;font-size:var(--font-size-sm);direction:ltr;text-align:start">{{ $file['filename'] }}</span>
                                </div>
                            </td>
                            <td class="text-center"><span class="badge badge-secondary">{{ $file['size_formatted'] }}</span></td>
                            <td class="text-center" style="color:var(--text-muted);font-size:var(--font-size-sm)">{{ $file['date_formatted'] }}</td>
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-1">
                                    {{-- Download --}}
                                    <a href="{{ route('admin.system.backup.download', $file['filename']) }}"
                                       class="btn btn-primary btn-sm"
                                       title="تحميل" aria-label="تحميل">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                    </a>

                                    {{-- Restore --}}
                                    <form method="POST" action="{{ route('admin.system.backup.restore', $file['filename']) }}"
                                          class="sakk-restore-form" style="display:inline;">
                                        @csrf
                                        <button type="submit"
                                                class="btn btn-gold btn-sm restore-trigger"
                                                data-filename="{{ $file['filename'] }}"
                                                title="استعادة" aria-label="استعادة">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                                        </button>
                                    </form>

                                    {{-- Delete --}}
                                    <form method="POST" action="{{ route('admin.system.backup.delete', $file['filename']) }}"
                                          onsubmit="return confirm('تأكيد حذف النسخة الاحتياطية: {{ $file['filename'] }}؟')"
                                          style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="confirm_name" value="{{ $file['filename'] }}">

                                        <button type="submit"
                                                class="btn btn-danger btn-sm"
                                                title="حذف" aria-label="حذف">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
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
    </div>

    {{-- ── RESTORE WARNING ── --}}
    <div class="sys-note" style="--note:var(--danger)">
        <x-heroicon name="security" />
        <div class="bd">
            <strong style="color:var(--danger);">تنبيه هام:</strong>
            استعادة قاعدة البيانات تؤدي إلى فقدان أي تغييرات تمت بعد تاريخ إنشاء النسخة الاحتياطية.
            يُوصى بعمل نسخة احتياطية جديدة قبل استعادة أي نسخة سابقة.
        </div>
    </div>
</div>

{{-- ── RESTORE CONFIRM MODAL ── --}}
<div id="restore-confirm-dialog" class="modal-backdrop" style="display:none" role="dialog" aria-modal="true" aria-labelledby="restore-modal-title"
     onclick="if(event.target===this) this.style.display='none'">
    <div class="modal">
        <div class="modal-header">
            <h3 id="restore-modal-title" class="card-title">
                <x-heroicon name="warning" style="color:var(--warning)" />
                تأكيد استعادة قاعدة البيانات
            </h3>
        </div>
        <div class="modal-body">
            <p id="restore-confirm-msg" style="color:var(--text-secondary);line-height:1.7">
                سيتم استبدال قاعدة البيانات الحالية بالكامل. هل أنت متأكد؟
            </p>
        </div>
        <div class="modal-footer">
            <button onclick="document.getElementById('restore-confirm-dialog').style.display='none'"
                    class="btn btn-secondary">إلغاء</button>
            <button id="restore-confirm-btn" class="btn btn-gold">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                تأكيد الاستعادة
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';
    var restoreForms = document.querySelectorAll('.sakk-restore-form');
    restoreForms.forEach(function(form) {
        form.querySelector('.restore-trigger').addEventListener('click', function(e) {
            e.preventDefault();
            var filename = this.getAttribute('data-filename');
            var dialog = document.getElementById('restore-confirm-dialog');
            var msg = document.getElementById('restore-confirm-msg');
            var btn = document.getElementById('restore-confirm-btn');
            if (!dialog || !msg || !btn) return;

            var safeName = document.createTextNode(filename);
            msg.innerHTML = 'سيتم استبدال قاعدة البيانات الحالية بالكامل بنسخة: <strong dir="ltr"></strong>.<br><br>سيتم حفظ نسخة احتياطية تلقائية من الوضع الحالي قبل الاستعادة.';
            msg.querySelector('strong').appendChild(safeName);
            dialog.style.display = 'flex';

            btn.onclick = function() {
                dialog.style.display = 'none';
                var hiddenForm = document.createElement('form');
                hiddenForm.method = 'POST';
                hiddenForm.action = form.action;
                var csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = '{{ csrf_token() }}';
                hiddenForm.appendChild(csrf);
                var confirmName = document.createElement('input');
                confirmName.type = 'hidden';
                confirmName.name = 'confirm_name';
                confirmName.value = filename;
                hiddenForm.appendChild(confirmName);
                document.body.appendChild(hiddenForm);
                hiddenForm.submit();
            };
        });
    });
})();
</script>
@endsection
