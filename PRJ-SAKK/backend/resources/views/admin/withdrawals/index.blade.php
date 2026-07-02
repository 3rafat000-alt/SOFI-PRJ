@extends('layouts.admin')

@section('title', 'طلبات السحب')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl gradient-primary flex items-center justify-center shadow-sm">
                <x-heroicon name="account_balance_wallet" class="text-white text-2xl" />
            </div>
            <div>
                <h1 class="text-2xl font-extrabold" style="color: var(--text-primary); letter-spacing: -0.02em;">طلبات السحب</h1>
                <p class="text-sm mt-0.5" style="color: var(--text-muted);">إدارة ومراجعة طلبات السحب المعلقة</p>
            </div>
        </div>
        <a href="{{ route('admin.withdrawals.export', request()->query()) }}" class="btn btn-secondary">
            <x-heroicon name="download" class="text-sm" />
            تصدير CSV
        </a>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">معلق</p>
                    <p class="stat-value">{{ $stats['pending_count'] }}</p>
                    <p class="text-xs font-bold mt-1" style="color: var(--accent-dark);">&lrm;${{ number_format($stats['pending_amount'], 2) }}</p>
                </div>
                <div class="stat-icon" style="background: var(--accent-soft); color: var(--accent-dark);">
                    <x-heroicon name="hourglass_top" class="text-2xl" />
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">قيد المعالجة</p>
                    <p class="stat-value">{{ $stats['processing_count'] }}</p>
                    <p class="text-xs font-bold mt-1" style="color: var(--primary);">&lrm;${{ number_format($stats['processing_amount'], 2) }}</p>
                </div>
                <div class="stat-icon" style="background: rgba(18,18,18,0.06); color: var(--primary);">
                    <x-heroicon name="sync" class="text-2xl" />
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">مكتمل اليوم</p>
                    <p class="stat-value" style="color: var(--success);">&lrm;${{ number_format($stats['completed_today'], 2) }}</p>
                </div>
                <div class="stat-icon" style="background: var(--success-light); color: var(--success);">
                    <x-heroicon name="check_circle" class="text-2xl" />
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">فشل اليوم</p>
                    <p class="stat-value" style="color: var(--danger);">{{ $stats['failed_today'] }}</p>
                </div>
                <div class="stat-icon" style="background: var(--danger-light); color: var(--danger);">
                    <x-heroicon name="cancel" class="text-2xl" />
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <x-heroicon name="filter_list" />
                تصفية الطلبات
            </h3>
        </div>
        <div class="card-body">
            <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="lg:col-span-2">
                    <label class="label">البحث</label>
                    <input type="text" name="search" class="input" placeholder="مرجع، عنوان، مستخدم..." value="{{ request('search') }}">
                </div>
                <div>
                    <label class="label">الحالة</label>
                    <select name="status" class="input">
                        <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>معلق</option>
                        <option value="processing" {{ $status === 'processing' ? 'selected' : '' }}>قيد المعالجة</option>
                        <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>مكتمل</option>
                        <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>فاشل</option>
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>الكل</option>
                    </select>
                </div>
                <div>
                    <label class="label">من تاريخ</label>
                    <input type="date" name="from_date" class="input" value="{{ request('from_date') }}">
                </div>
                <div>
                    <label class="label">إلى تاريخ</label>
                    <input type="date" name="to_date" class="input" value="{{ request('to_date') }}">
                </div>
                <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-5">
                    <button type="submit" class="btn btn-primary">
                        <x-heroicon name="search" class="text-sm" />
                        بحث
                    </button>
                    <a href="{{ route('admin.withdrawals.index') }}" class="btn btn-secondary">
                        <x-heroicon name="clear" class="text-sm" />
                        إعادة تعيين
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Bulk Actions --}}
    @if($withdrawals->where('status', 'pending')->count() > 0)
    <form id="bulkForm" method="POST" action="{{ route('admin.withdrawals.bulk-approve') }}">
        @csrf
        <div class="card mb-6 hidden" id="bulkActions">
            <div class="card-body py-3">
                <div class="flex items-center gap-4">
                    <span class="text-sm font-semibold" style="color: var(--text-secondary);">تم تحديد <strong id="selectedCount" style="color: var(--text-primary);">0</strong> طلب</span>
                    <button type="submit" class="btn btn-sm btn-success">
                        <x-heroicon name="done_all" class="text-sm" />
                        موافقة جماعية
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Withdrawals Table --}}
    <div class="card">
        <div class="card-body">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            @if($withdrawals->where('status', 'pending')->count() > 0)
                            <th style="width: 40px;">
                                <input type="checkbox" class="w-4 h-4 rounded cursor-pointer" style="accent-color: var(--primary);" id="selectAll">
                            </th>
                            @endif
                            <th>المرجع</th>
                            <th>المستخدم</th>
                            <th>المبلغ</th>
                            <th>الرسوم</th>
                            <th>الصافي</th>
                            <th>العنوان الهدف</th>
                            <th>الحالة</th>
                            <th>التاريخ</th>
                            <th style="width: 120px;">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($withdrawals as $withdrawal)
                        <tr>
                            @if($withdrawals->where('status', 'pending')->count() > 0)
                            <td>
                                @if($withdrawal->status === 'pending' || $withdrawal->status === 'processing')
                                <input type="checkbox" class="withdrawal-checkbox w-4 h-4 rounded cursor-pointer" style="accent-color: var(--primary);" name="withdrawal_ids[]" value="{{ $withdrawal->id }}">
                                @endif
                            </td>
                            @endif
                            <td>
                                <span class="font-mono text-xs font-bold" style="color: var(--text-primary);">{{ $withdrawal->sham_cash_reference }}</span>
                            </td>
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full flex items-center justify-center shrink-0" style="background: var(--surface-active);">
                                        <span class="text-xs font-extrabold" style="color: var(--primary);">{{ substr($withdrawal->user?->first_name ?? 'U', 0, 1) }}</span>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-semibold truncate" style="color: var(--text-primary);">{{ $withdrawal->user?->full_name ?? 'N/A' }}</div>
                                        <small style="color: var(--text-muted);">{{ $withdrawal->user?->email }}</small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="font-extrabold" style="color: var(--text-primary);">&lrm;${{ number_format($withdrawal->amount, 2) }}</span></td>
                            <td style="color: var(--text-muted);">&lrm;${{ number_format($withdrawal->fee, 2) }}</td>
                            <td><span class="font-bold" style="color: var(--success);">&lrm;${{ number_format($withdrawal->net_amount, 2) }}</span></td>
                            <td>
                                <span class="font-mono text-xs truncate inline-block align-middle" style="max-width: 150px; color: var(--text-secondary);" title="{{ $withdrawal->sham_wallet_address }}">
                                    {{ $withdrawal->sham_wallet_address }}
                                </span>
                            </td>
                            <td>
                                @switch($withdrawal->status)
                                    @case('pending')
                                        <span class="badge badge-warning">معلق</span>
                                        @break
                                    @case('processing')
                                        <span class="badge badge-primary">قيد المعالجة</span>
                                        @break
                                    @case('completed')
                                        <span class="badge badge-success">مكتمل</span>
                                        @break
                                    @case('failed')
                                        <span class="badge badge-danger">فاشل</span>
                                        @break
                                    @default
                                        <span class="badge badge-secondary">{{ $withdrawal->status }}</span>
                                @endswitch
                            </td>
                            <td>
                                <small style="color: var(--text-muted);" dir="ltr">{{ $withdrawal->created_at->format('Y-m-d H:i') }}</small>
                            </td>
                            <td>
                                <div class="flex items-center gap-1">
                                    <a href="{{ route('admin.withdrawals.show', $withdrawal) }}" class="btn btn-sm btn-secondary" title="عرض">
                                        <x-heroicon name="visibility" class="text-sm" />
                                    </a>
                                    @if($withdrawal->status === 'pending' || $withdrawal->status === 'processing')
                                    <button type="button" class="btn btn-sm btn-success" title="موافقة" onclick="approveWithdrawal({{ $withdrawal->id }})">
                                        <x-heroicon name="check" class="text-sm" />
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" title="رفض" onclick="rejectWithdrawal({{ $withdrawal->id }})">
                                        <x-heroicon name="close" class="text-sm" />
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10">
                                <div class="table-empty">
                                    <x-heroicon name="inbox" class="table-empty-icon" />
                                    <p class="mb-2 font-bold">لا توجد طلبات سحب {{ $status !== 'all' ? 'بحالة "' . $status . '"' : '' }}</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($withdrawals->hasPages())
            <div class="mt-4">
                {{ $withdrawals->withQueryString()->links() }}
            </div>
            @endif
        </div>
    </div>
    @if($withdrawals->where('status', 'pending')->count() > 0)
    </form>
    @endif
</div>

{{-- Approve Modal --}}
<div id="approveModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="modal-overlay absolute inset-0" onclick="closeWithdrawalModal('approveModal')"></div>
    <div class="card-sukk-overlay relative w-full max-w-md">
        <form method="POST" id="approveForm">
            @csrf
            <div class="card-header">
                <h3 class="card-title">
                    <x-heroicon name="check_circle" style="color: var(--success);" />
                    تأكيد الموافقة
                </h3>
                <button type="button" class="btn btn-sukk-icon" onclick="closeWithdrawalModal('approveModal')">
                    <x-heroicon name="close" />
                </button>
            </div>
            <div class="card-body space-y-4">
                <p style="color: var(--text-secondary);">هل أنت متأكد من الموافقة على طلب السحب هذا؟</p>
                <div>
                    <label class="label">ملاحظة (اختياري)</label>
                    <textarea name="admin_note" class="input" rows="2" placeholder="ملاحظة للسجلات..."></textarea>
                </div>
            </div>
            <div class="card-footer">
                <button type="button" class="btn btn-secondary" onclick="closeWithdrawalModal('approveModal')">إلغاء</button>
                <button type="submit" class="btn btn-success">
                    <x-heroicon name="check" class="text-sm" />
                    موافقة
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Reject Modal --}}
<div id="rejectModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="modal-overlay absolute inset-0" onclick="closeWithdrawalModal('rejectModal')"></div>
    <div class="card-sukk-overlay relative w-full max-w-md">
        <form method="POST" id="rejectForm">
            @csrf
            <div class="card-header">
                <h3 class="card-title">
                    <x-heroicon name="cancel" style="color: var(--danger);" />
                    رفض طلب السحب
                </h3>
                <button type="button" class="btn btn-sukk-icon" onclick="closeWithdrawalModal('rejectModal')">
                    <x-heroicon name="close" />
                </button>
            </div>
            <div class="card-body space-y-4">
                <p class="text-sm font-semibold" style="color: var(--danger);">سيتم إعادة المبلغ إلى محفظة المستخدم.</p>
                <div>
                    <label class="label label-required">سبب الرفض</label>
                    <textarea name="reason" class="input" rows="3" required placeholder="يرجى كتابة سبب الرفض..."></textarea>
                </div>
            </div>
            <div class="card-footer">
                <button type="button" class="btn btn-secondary" onclick="closeWithdrawalModal('rejectModal')">إلغاء</button>
                <button type="submit" class="btn btn-danger">
                    <x-heroicon name="close" class="text-sm" />
                    رفض وإعادة المبلغ
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
// Select all checkboxes
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.withdrawal-checkbox').forEach(cb => cb.checked = this.checked);
    updateBulkActions();
});

// Individual checkbox change
document.querySelectorAll('.withdrawal-checkbox').forEach(cb => {
    cb.addEventListener('change', updateBulkActions);
});

function updateBulkActions() {
    const checked = document.querySelectorAll('.withdrawal-checkbox:checked').length;
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');

    if (checked > 0) {
        bulkActions?.classList.remove('hidden');
        if (selectedCount) selectedCount.textContent = checked;
    } else {
        bulkActions?.classList.add('hidden');
    }
}

function openWithdrawalModal(id) {
    const modal = document.getElementById(id);
    if (modal) { modal.classList.remove('hidden'); modal.classList.add('flex'); }
}

function closeWithdrawalModal(id) {
    const modal = document.getElementById(id);
    if (modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); }
}

function approveWithdrawal(id) {
    const form = document.getElementById('approveForm');
    form.action = `/admin/withdrawals/${id}/approve`;
    openWithdrawalModal('approveModal');
}

function rejectWithdrawal(id) {
    const form = document.getElementById('rejectForm');
    form.action = `/admin/withdrawals/${id}/reject`;
    openWithdrawalModal('rejectModal');
}
</script>
@endpush
@endsection
