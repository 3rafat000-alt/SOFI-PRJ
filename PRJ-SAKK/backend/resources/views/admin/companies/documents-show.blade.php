@extends('layouts.admin')

@section('title', 'مستندات ' . $company->name)

@section('breadcrumbs')
<a href="{{ route('admin.companies.index') }}" class="breadcrumb-item">الشركات</a>
<a href="{{ route('admin.companies.documents') }}" class="breadcrumb-item">المستندات</a>
<span class="breadcrumb-item">{{ $company->name }}</span>
@endsection

@section('content')
<div class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.companies.show', $company) }}" class="btn btn-sukk-icon" aria-label="رجوع للشركة">
                <x-heroicon name="arrow_forward" aria-hidden="true" />
            </a>
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight" style="color:var(--text-primary);">{{ $company->name }}</h1>
                <p class="text-sm mt-0.5" style="color:var(--text-muted);">مستندات التفعيل — {{ $company->company_code }}</p>
            </div>
        </div>
        <span class="badge badge-{{ $company->kyc_status_color }}" style="font-size:0.8rem;padding:var(--space-xs) var(--space-md);">
            {{ $company->kyc_status_label }}
        </span>
    </div>

    {{-- KYC rejection banner --}}
    @if($company->kyc_rejection_reason)
    <div class="card" style="border-color:var(--danger);background:var(--danger-light);">
        <div class="card-body flex items-start gap-2" style="color:var(--danger);">
            <x-heroicon name="warning" class="text-sm" aria-hidden="true" />
            <span class="text-sm font-bold">{{ $company->kyc_rejection_reason }}</span>
        </div>
    </div>
    @endif

    {{-- Document list using split-view viewer --}}
    @forelse($documents as $doc)
        @include('admin.companies.partials._document_viewer', [
            'doc'    => $doc,
            'entity' => $company,
        ])
    @empty
        <div class="card">
            <div class="card-body">
                <div class="table-empty">
                    <x-heroicon name="description" class="table-empty-icon" />
                    <p class="font-bold" style="color:var(--text-secondary);">لا توجد مستندات</p>
                    <p class="text-sm mt-1">لم ترفع هذه الشركة أي مستندات بعد</p>
                </div>
            </div>
        </div>
    @endforelse
</div>

{{-- Reject Modal (Alpine v3) --}}
<div x-data="docRejectModal()"
     @open-doc-reject.window="open($event.detail)"
     x-show="show" x-cloak
     class="modal-backdrop" style="display:none;"
     @keydown.escape.window="close()">
    <div class="modal modal-danger" @click.outside="close()" role="dialog" aria-modal="true" aria-labelledby="cos-dr-title">
        <div class="modal-header">
            <div>
                <h3 class="modal-title font-extrabold" id="cos-dr-title">رفض المستند</h3>
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
                <label for="cos-dr-reason" class="label label-required">سبب الرفض</label>
                <textarea id="cos-dr-reason" name="rejection_reason" rows="3" class="input" required minlength="3"
                    x-ref="reason" placeholder="اذكر سبب رفض المستند بوضوح ليصل للشركة…"></textarea>
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

@push('scripts')
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
