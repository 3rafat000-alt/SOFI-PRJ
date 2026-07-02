@extends('layouts.admin')

@section('title', 'مستندات الوكلاء')
@section('breadcrumbs')
<span class="breadcrumb-item">الوكلاء</span>
<span class="breadcrumb-item">المستندات</span>
@endsection

@php use App\Models\AgentDocument; @endphp

@section('content')
<div class="space-y-5">

    {{-- Page header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight" style="color:var(--text-primary);">مستندات الوكلاء</h1>
            <p class="text-sm mt-0.5" style="color:var(--text-muted);">مراجعة واعتماد مستندات تفعيل الوكلاء — اعرف عميلك (KYC)</p>
        </div>
        <a href="{{ route('admin.agents.index') }}" class="btn btn-secondary btn-sm shrink-0" aria-label="العودة لقائمة الوكلاء">
            <x-heroicon name="storefront" class="text-sm" aria-hidden="true" />
            كل الوكلاء
        </a>
    </div>

    {{-- KPI Grid --}}
    @include('admin.partials._kpi_grid', ['cards' => [
        ['icon' => 'folder_copy',  'value' => number_format($stats['total']),    'label' => 'إجمالي المستندات', 'iconBg' => 'var(--surface-hover)', 'accent' => 'var(--sukk-primary)'],
        ['icon' => 'hourglass_top','value' => number_format($stats['pending']),  'label' => 'قيد المراجعة',     'iconBg' => 'var(--warning-light)', 'accent' => 'var(--warning)'],
        ['icon' => 'verified',     'value' => number_format($stats['approved']), 'label' => 'معتمد',           'iconBg' => 'var(--success-light)', 'accent' => 'var(--success)'],
        ['icon' => 'gpp_bad',      'value' => number_format($stats['rejected']), 'label' => 'مرفوض',           'iconBg' => 'var(--danger-light)', 'accent' => 'var(--danger)'],
    ]])

    {{-- Filter Bar --}}
    @include('admin.partials._filter_bar', [
        'route'              => route('admin.agents.documents'),
        'searchValue'        => request('search'),
        'searchPlaceholder'  => 'اسم الوكيل، الكود، أو رقم المستند…',
        'hasFilters'         => request()->anyFilled(['status', 'document_type', 'search']),
        'filters'            => [
            ['name'=>'status','label'=>'الحالة','selected'=>request('status'),'options'=>['pending'=>'قيد المراجعة','approved'=>'معتمد','rejected'=>'مرفوض']],
            ['name'=>'document_type','label'=>'نوع المستند','selected'=>request('document_type'),'options'=>AgentDocument::TYPES],
        ],
    ])

    {{-- Documents table --}}
    <div class="card">
        @if($documents->count() > 0)
        <div class="card-header">
            <div class="card-title" style="font-size:.85rem;">
                <x-heroicon name="description" style="color:var(--sukk-primary);font-size:1.1rem;" />
                <span>قائمة المستندات</span>
            </div>
            <span class="text-xs font-bold" style="color:var(--text-muted);">
                عرض {{ $documents->firstItem() }}–{{ $documents->lastItem() }} من {{ number_format($documents->total()) }}
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-container" style="border:none;border-radius:0;box-shadow:none;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>الوكيل</th>
                            <th>المستند</th>
                            <th>رقم المستند</th>
                            <th>الصلاحية</th>
                            <th>الحالة</th>
                            <th>الرفع</th>
                            <th class="text-center">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($documents as $doc)
                        @php
                            $expired  = $doc->expiry_date && $doc->expiry_date->isPast();
                            $expiring = $doc->expiry_date && !$expired && $doc->expiry_date->diffInDays(now()) <= 30;
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('admin.agents.show', $doc->agent) }}" class="block font-bold" style="color:var(--text-primary);">{{ $doc->agent->name }}</a>
                                <span class="font-mono text-xs" style="color:var(--text-muted);">{{ $doc->agent->agent_code }}</span>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <x-heroicon name="description" style="color:var(--text-muted);font-size:1.1rem;" aria-hidden="true" />
                                    <div>
                                        <div class="text-sm font-bold" style="color:var(--text-primary);">{{ $doc->type_label }}</div>
                                        @if($doc->issuing_authority)
                                        <div class="text-xs" style="color:var(--text-muted);">{{ $doc->issuing_authority }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td><span class="font-mono text-xs" style="color:var(--text-secondary);">{{ $doc->document_number ?? '—' }}</span></td>
                            <td>
                                @if($doc->expiry_date)
                                <span class="badge {{ $expired ? 'badge-danger' : ($expiring ? 'badge-warning' : 'badge-secondary') }}" title="ينتهي في {{ $doc->expiry_date->format('Y/m/d') }}">
                                    <x-heroicon name="event_busy" style="font-size:0.8rem;" aria-hidden="true" x-show="$expired" />
<x-heroicon name="event_available" style="font-size:0.8rem;" aria-hidden="true" x-show="!$expired" />
                                    {{ $expired ? 'منتهٍ' : ($expiring ? 'ينتهي قريباً' : $doc->expiry_date->format('Y/m/d')) }}
                                </span>
                                @else
                                <span style="color:var(--text-muted);">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-{{ $doc->status_color }}">
                                    {{ $doc->status === 'pending' ? 'قيد المراجعة' : ($doc->status === 'approved' ? 'معتمد' : 'مرفوض') }}
                                </span>
                                @if($doc->status === 'rejected' && $doc->rejection_reason)
                                <div class="text-xs mt-1" style="color:var(--danger);max-width:160px;" title="{{ $doc->rejection_reason }}">{{ \Illuminate\Support\Str::limit($doc->rejection_reason, 40) }}</div>
                                @endif
                            </td>
                            <td class="text-xs" dir="ltr" style="color:var(--text-muted);">{{ $doc->created_at->format('Y/m/d') }}</td>
                            <td>
                                <div class="row-actions justify-center">
                                    @if($doc->file_path)
                                    <a href="{{ route('admin.secure-file', ['path' => encrypt($doc->file_path)]) }}" target="_blank" rel="noopener" class="row-action" title="عرض الملف" aria-label="عرض ملف المستند">
                                        <x-heroicon name="visibility" aria-hidden="true" />
                                    </a>
                                    @endif
                                    @if($doc->status === 'pending')
                                    <form method="POST" action="{{ route('admin.agents.documents.approve', $doc) }}"
                                          onsubmit="return confirm('اعتماد مستند « {{ $doc->type_label }} » للوكيل « {{ $doc->agent->name }} »؟')" class="inline">
                                        @csrf
                                        <button type="submit" class="row-action is-success" title="اعتماد" aria-label="اعتماد المستند">
                                            <x-heroicon name="check" aria-hidden="true" />
                                        </button>
                                    </form>
                                    <button type="button" class="row-action is-danger" title="رفض" aria-label="رفض المستند"
                                            @click="$dispatch('open-doc-reject', {
                                                rejectUrl: @js(route('admin.agents.documents.reject', $doc)),
                                                merchantName: @js($doc->agent->name),
                                                docType: @js($doc->type_label)
                                            })">
                                        <x-heroicon name="close" aria-hidden="true" />
                                    </button>
                                    @endif
                                    <a href="{{ route('admin.agents.documents.show', $doc->agent) }}" class="row-action" title="كل مستندات الوكيل" aria-label="كل مستندات الوكيل">
                                        <x-heroicon name="folder_open" aria-hidden="true" />
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7">
                                <div class="table-empty">
                                    <x-heroicon name="description" class="table-empty-icon" />
                                    <p class="font-bold" style="color:var(--text-secondary);">{{ request()->anyFilled(['status','document_type','search']) ? 'لا توجد مستندات مطابقة للفلتر' : 'لا توجد مستندات مرفوعة بعد' }}</p>
                                    <p class="text-sm mt-1">ستظهر هنا المستندات التي يرفعها الوكلاء لمراجعتها</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($documents->hasPages())
        <div class="card-footer">{{ $documents->withQueryString()->links() }}</div>
        @endif
        @else
        <div class="card-body">
            <div class="table-empty">
                <x-heroicon name="description" class="table-empty-icon" />
                <p class="font-bold" style="color:var(--text-secondary);">{{ request()->anyFilled(['status','document_type','search']) ? 'لا توجد مستندات مطابقة للفلتر' : 'لا توجد مستندات مرفوعة بعد' }}</p>
                <p class="text-sm mt-1">ستظهر هنا المستندات التي يرفعها الوكلاء لمراجعتها</p>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Reject Modal (Alpine v3) --}}
<div x-data="docRejectModal()"
     @open-doc-reject.window="open($event.detail)"
     x-show="show" x-cloak
     class="modal-backdrop" style="display:none;"
     @keydown.escape.window="close()">
    <div class="modal modal-danger" @click.outside="close()" role="dialog" aria-modal="true" aria-labelledby="ag-dr-title">
        <div class="modal-header">
            <div>
                <h3 class="modal-title font-extrabold" id="ag-dr-title">رفض المستند</h3>
                <p class="text-xs mt-1" style="color:var(--text-secondary);">
                    <span x-text="docType"></span> — <span class="font-bold" x-text="merchantName"></span>
                </p>
            </div>
            <button type="button" @click="close()" class="btn btn-sm btn-sukk-icon" aria-label="إغلاق">
                <x-heroicon name="close" class="text-sm" aria-hidden="true" />
            </button>
        </div>
        <form method="POST" :action="rejectUrl">
            @csrf
            <div class="modal-body">
                <label for="ag-dr-reason" class="label label-required">سبب الرفض</label>
                <textarea id="ag-dr-reason" name="rejection_reason" rows="3" class="input" required minlength="3"
                    x-ref="reason" placeholder="اذكر سبب رفض المستند بوضوح ليصل للوكيل…"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" @click="close()" class="btn btn-secondary">إلغاء</button>
                <button type="submit" class="btn btn-danger">
                    <x-heroicon name="gpp_bad" class="text-sm" aria-hidden="true" />
                    تأكيد الرفض
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('docRejectModal', () => ({
            show: false, rejectUrl: '', merchantName: '', docType: '',
            open(detail) {
                this.rejectUrl    = detail.rejectUrl;
                this.merchantName = detail.merchantName;
                this.docType      = detail.docType;
                this.show         = true;
                this.$nextTick(() => this.$refs.reason?.focus());
            },
            close() { this.show = false; },
        }));
    });
</script>
@endpush
