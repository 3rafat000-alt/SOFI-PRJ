@extends('layouts.admin')

@section('title', 'تفاصيل المعاملة')

@section('breadcrumbs')
<a href="{{ route('admin.transactions') }}" class="breadcrumb-item">المعاملات</a>
<span class="breadcrumb-item">تفاصيل المعاملة</span>
@endsection

@php
    $statusVal   = $transaction->status instanceof \App\Enums\TransactionStatus ? $transaction->status->value : $transaction->status;
    $statusLabel = $transaction->status instanceof \App\Enums\TransactionStatus ? $transaction->status->labelAr() : $statusVal;
    $statusBadge = match ($statusVal) {
        'completed'             => 'badge-success',
        'pending', 'processing' => 'badge-warning',
        'failed', 'cancelled'   => 'badge-danger',
        default                 => 'badge-secondary',
    };
    $amount   = (float) $transaction->amount;
    $isCredit = $amount >= 0;
    $sym      = \App\Support\Money::symbol($transaction->currency);
    $fmtAmt   = fn ($v) => \App\Support\Money::number((float) $v, $transaction->currency);
    $isAdjustment = ($transaction->type instanceof \App\Enums\TransactionType && $transaction->type === \App\Enums\TransactionType::ADJUSTMENT)
        || ($transaction->category instanceof \App\Enums\TransactionCategory && $transaction->category === \App\Enums\TransactionCategory::ADJUSTMENT);
    $canReverse = $statusVal === 'completed' && ! $isAdjustment;
    $hasProvider = $transaction->external_reference || $transaction->provider || $transaction->tx_hash || $transaction->network;
@endphp

@section('content')
<div class="max-w-4xl mx-auto space-y-5" x-data="txShow">

    {{-- ================================================================
         PAGE HEADER
         ================================================================ --}}
    <div class="flex flex-wrap items-center gap-3">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight" style="color: var(--text-primary);">تفاصيل المعاملة</h1>
            <p class="text-sm mt-0.5" style="color: var(--text-muted);" dir="ltr">{{ $transaction->reference }}</p>
        </div>
        <div class="flex items-center gap-2 ms-auto">
            <a href="{{ route('admin.transactions.invoice', $transaction->id) }}" target="_blank" class="btn btn-secondary btn-sm">
                <x-heroicon name="picture_as_pdf" class="text-sm" aria-hidden="true" />
                فاتورة PDF
            </a>
            <a href="{{ route('admin.transactions') }}" class="btn btn-ghost btn-sm">
                <x-heroicon name="arrow_back" class="text-sm" aria-hidden="true" />
                عودة
            </a>
        </div>
    </div>

    {{-- ================================================================
         LINK BANNERS — reversal relationships
         ================================================================ --}}
    @if($reversal)
    <div class="card" style="border-color: var(--border-strong);">
        <div class="card-body" style="display: flex; align-items: center; gap: 0.75rem;">
            <x-heroicon name="undo" style="color: var(--text-secondary);" aria-hidden="true" />
            <p class="text-sm" style="color: var(--text-secondary);">
                عُكِست هذه المعاملة عبر تسوية معاكِسة:
                <a href="{{ route('admin.transactions.show', $reversal->id) }}" class="font-bold" style="color: var(--primary);" dir="ltr">{{ $reversal->reference }}</a>
            </p>
        </div>
    </div>
    @endif

    @if($original)
    <div class="card" style="border-color: var(--border-strong);">
        <div class="card-body" style="display: flex; align-items: center; gap: 0.75rem;">
            <x-heroicon name="link" style="color: var(--text-secondary);" aria-hidden="true" />
            <p class="text-sm" style="color: var(--text-secondary);">
                معاملة تسوية ناتجة عن عكس المعاملة الأصلية:
                <a href="{{ route('admin.transactions.show', $original->id) }}" class="font-bold" style="color: var(--primary);" dir="ltr">{{ $original->reference }}</a>
            </p>
        </div>
    </div>
    @endif

    {{-- ================================================================
         SUMMARY HEADER — amount + status (read-only; no fake actions)
         ================================================================ --}}
    <div class="card">
        <div class="card-body">
            <div class="flex flex-wrap items-center justify-between gap-6">
                <div>
                    <p class="stat-label">المبلغ</p>
                    <p class="text-3xl font-extrabold" dir="ltr" style="color: {{ $isCredit ? 'var(--success)' : 'var(--danger)' }}; letter-spacing: -0.02em;">
{{ $isCredit ? '+' : '−' }}{!! \App\Support\Money::format(abs($amount), $transaction->currency) !!}
                    </p>
                    <p class="text-sm mt-1" style="color: var(--text-muted);" dir="ltr">{{ $transaction->created_at->format('Y/m/d H:i:s') }}</p>
                </div>
                <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
            </div>
        </div>
    </div>

    {{-- ================================================================
         READ-ONLY DETAIL GRID
         ================================================================ --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

        {{-- Transaction info --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <x-heroicon name="receipt_long" style="color: var(--accent);" aria-hidden="true" />
                    معلومات المعاملة
                </h3>
            </div>
            <div class="card-body">
                <dl class="space-y-3">
                    <div class="flex justify-between items-center">
                        <dt class="text-sm" style="color: var(--text-muted);">المرجع</dt>
                        <dd class="text-sm font-bold" style="color: var(--text-primary);" dir="ltr">{{ $transaction->reference }}</dd>
                    </div>
                    <div class="flex justify-between items-center">
                        <dt class="text-sm" style="color: var(--text-muted);">النوع</dt>
                        <dd class="text-sm font-bold" style="color: var(--text-primary);">{{ $transaction->type instanceof \App\Enums\TransactionType ? $transaction->type->labelAr() : $transaction->type }}</dd>
                    </div>
                    <div class="flex justify-between items-center">
                        <dt class="text-sm" style="color: var(--text-muted);">التصنيف</dt>
                        <dd class="text-sm font-bold" style="color: var(--text-primary);">{{ $transaction->category instanceof \App\Enums\TransactionCategory ? $transaction->category->labelAr() : ($transaction->category ?? 'غير محدد') }}</dd>
                    </div>
                    <div class="flex justify-between items-center">
                        <dt class="text-sm" style="color: var(--text-muted);">العملة</dt>
                        <dd class="text-sm font-bold" style="color: var(--text-primary);" dir="ltr">{{ $transaction->currency }}</dd>
                    </div>
                    <div class="divider" style="margin: 0.25rem 0;"></div>
                    <div class="flex justify-between items-center">
                        <dt class="text-sm" style="color: var(--text-muted);">المبلغ</dt>
                        <dd class="text-base font-extrabold" dir="ltr" style="color: {{ $isCredit ? 'var(--success)' : 'var(--danger)' }};">{{ $isCredit ? '+' : '−' }}{!! \App\Support\Money::format(abs($amount), $transaction->currency) !!}</dd>
                    </div>
                    <div class="flex justify-between items-center">
                        <dt class="text-sm" style="color: var(--text-muted);">الرسوم</dt>
                        <dd class="text-sm font-bold" style="color: var(--text-primary);" dir="ltr">{!! \App\Support\Money::format((float) ($transaction->fee ?? 0), $transaction->currency) !!}</dd>
                    </div>
                    <div class="flex justify-between items-center">
                        <dt class="text-sm" style="color: var(--text-muted);">صافي المبلغ</dt>
                        <dd class="text-sm font-bold" style="color: var(--text-primary);" dir="ltr">{!! \App\Support\Money::format((float) ($transaction->net_amount ?? $transaction->amount), $transaction->currency) !!}</dd>
                    </div>
                    @if($transaction->balance_before !== null || $transaction->balance_after !== null)
                    <div class="divider" style="margin: 0.25rem 0;"></div>
                    <div class="flex justify-between items-center">
                        <dt class="text-sm" style="color: var(--text-muted);">الرصيد قبل</dt>
                        <dd class="text-sm font-bold" style="color: var(--text-primary);" dir="ltr">{!! \App\Support\Money::format((float) $transaction->balance_before, $transaction->currency) !!}</dd>
                    </div>
                    <div class="flex justify-between items-center">
                        <dt class="text-sm" style="color: var(--text-muted);">الرصيد بعد</dt>
                        <dd class="text-sm font-bold" style="color: var(--text-primary);" dir="ltr">{!! \App\Support\Money::format((float) $transaction->balance_after, $transaction->currency) !!}</dd>
                    </div>
                    @endif
                    @if($transaction->original_currency && $transaction->original_currency !== $transaction->currency)
                    <div class="divider" style="margin: 0.25rem 0;"></div>
                    <div class="flex justify-between items-center">
                        <dt class="text-sm" style="color: var(--text-muted);">المبلغ الأصلي</dt>
                        <dd class="text-sm font-bold" style="color: var(--text-primary);" dir="ltr">{!! \App\Support\Money::format((float) $transaction->original_amount, $transaction->original_currency) !!}</dd>
                    </div>
                    <div class="flex justify-between items-center">
                        <dt class="text-sm" style="color: var(--text-muted);">سعر الصرف</dt>
                        <dd class="text-sm font-bold" style="color: var(--text-primary);" dir="ltr">{{ number_format((float) $transaction->exchange_rate, 6) }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>

        {{-- Parties --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <x-heroicon name="people" style="color: var(--accent);" aria-hidden="true" />
                    الأطراف
                </h3>
            </div>
            <div class="card-body">
                @if($transaction->user)
                <a href="{{ route('admin.users.show', $transaction->user->id) }}" class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center shrink-0" style="background: var(--primary);" aria-hidden="true">
                        <span class="text-white font-bold">{{ mb_strtoupper(mb_substr($transaction->user->first_name, 0, 1)) }}</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-bold" style="color: var(--text-primary);">{{ $transaction->user->first_name }} {{ $transaction->user->last_name }}</p>
                        <p class="text-xs truncate" style="color: var(--text-muted);" dir="ltr">{{ $transaction->user->email }}</p>
                    </div>
                </a>
                @else
                <p class="text-sm mb-4" style="color: var(--text-muted);">مستخدم محذوف</p>
                @endif
                <dl class="space-y-3 pt-3" style="border-top: 1px solid var(--border-light);">
                    <div class="flex justify-between items-center">
                        <dt class="text-sm" style="color: var(--text-muted);">المحفظة</dt>
                        <dd class="text-sm font-bold" style="color: var(--text-primary);" dir="ltr">{{ $transaction->wallet->currency ?? 'غير محدد' }}</dd>
                    </div>
                    @if($transaction->recipient)
                    <div class="flex justify-between items-center">
                        <dt class="text-sm" style="color: var(--text-muted);">المستلم</dt>
                        <dd class="text-sm font-bold" style="color: var(--text-primary);">{{ $transaction->recipient->first_name }} {{ $transaction->recipient->last_name }}</dd>
                    </div>
                    @endif
                    @if($transaction->card)
                    <div class="flex justify-between items-center">
                        <dt class="text-sm" style="color: var(--text-muted);">البطاقة</dt>
                        <dd class="text-sm font-bold" style="color: var(--text-primary);" dir="ltr">{{ $transaction->card->card_number_masked ?? 'N/A' }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    {{-- Provider & network --}}
    @if($hasProvider)
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <x-heroicon name="hub" style="color: var(--accent);" aria-hidden="true" />
                المزوّد والشبكة
            </h3>
        </div>
        <div class="card-body">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @if($transaction->provider)
                <div class="flex justify-between items-center">
                    <dt class="text-sm" style="color: var(--text-muted);">المزوّد</dt>
                    <dd class="text-sm font-bold" style="color: var(--text-primary);">{{ $transaction->provider }}</dd>
                </div>
                @endif
                @if($transaction->external_reference)
                <div class="flex justify-between items-center">
                    <dt class="text-sm" style="color: var(--text-muted);">المرجع الخارجي</dt>
                    <dd class="text-sm font-bold" style="color: var(--text-primary);" dir="ltr">{{ $transaction->external_reference }}</dd>
                </div>
                @endif
                @if($transaction->network)
                <div class="flex justify-between items-center">
                    <dt class="text-sm" style="color: var(--text-muted);">الشبكة</dt>
                    <dd class="text-sm font-bold" style="color: var(--text-primary);" dir="ltr">{{ $transaction->network }}</dd>
                </div>
                @endif
                @if($transaction->confirmations !== null)
                <div class="flex justify-between items-center">
                    <dt class="text-sm" style="color: var(--text-muted);">التأكيدات</dt>
                    <dd class="text-sm font-bold" style="color: var(--text-primary);" dir="ltr">{{ $transaction->confirmations }}</dd>
                </div>
                @endif
                @if($transaction->tx_hash)
                <div class="flex justify-between items-center sm:col-span-2">
                    <dt class="text-sm" style="color: var(--text-muted);">Tx Hash</dt>
                    <dd class="text-xs font-mono truncate" style="color: var(--text-secondary); max-width: 60%;" dir="ltr">{{ $transaction->tx_hash }}</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>
    @endif

    {{-- Failure reason --}}
    @if($transaction->failure_reason)
    <div class="card" style="border-color: var(--danger);">
        <div class="card-header" style="background: var(--danger-light); border-bottom-color: transparent;">
            <h3 class="card-title" style="color: var(--danger);">
                <x-heroicon name="error" style="color: var(--danger);" aria-hidden="true" />
                سبب الفشل
            </h3>
        </div>
        <div class="card-body">
            <p class="text-sm" style="color: var(--danger);">{{ $transaction->failure_reason }}</p>
        </div>
    </div>
    @endif

    {{-- Metadata --}}
    @if($transaction->metadata)
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <x-heroicon name="data_object" style="color: var(--accent);" aria-hidden="true" />
                بيانات إضافية
            </h3>
            <button @click="showMetadata = !showMetadata" class="btn btn-ghost btn-sm">
                <span x-text="showMetadata ? 'إخفاء' : 'عرض'"></span>
                <x-heroicon name="expand_less" x-show="showMetadata" class="text-sm" />
<x-heroicon name="expand_more" x-show="!showMetadata" class="text-sm" />
            </button>
        </div>
        <div class="card-body">
            <pre x-show="showMetadata" x-transition class="text-sm p-4 overflow-auto" style="color: var(--text-secondary); background: var(--surface-hover); border-radius: var(--radius-sm);" dir="ltr">{{ json_encode($transaction->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    </div>
    @endif

    {{-- Audit trail --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <x-heroicon name="history" style="color: var(--accent);" aria-hidden="true" />
                سجل التدقيق
            </h3>
        </div>
        <div class="card-body" style="padding: 0;">
            @forelse($activity as $log)
            <div class="flex items-start gap-3 px-5 py-3" style="border-bottom: 1px solid var(--border-light);">
                <x-heroicon name="fiber_manual_record" class="text-sm mt-0.5" style="color: var(--text-muted);" aria-hidden="true" />
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-bold" style="color: var(--text-primary);">{{ $log->action }}</p>
                    @if($log->description)
                    <p class="text-xs mt-0.5" style="color: var(--text-secondary);">{{ $log->description }}</p>
                    @endif
                    <p class="text-xs mt-0.5" style="color: var(--text-muted);" dir="ltr">{{ $log->created_at->format('Y/m/d H:i:s') }}</p>
                </div>
            </div>
            @empty
            <div class="px-5 py-6 text-center">
                <p class="text-sm" style="color: var(--text-muted);">لا يوجد نشاط مسجّل لهذه المعاملة</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- ================================================================
         REVERSE DANGER ZONE — the only admin mutation on this surface.
         Shown only for COMPLETED, non-adjustment transactions.
         ================================================================ --}}
    @if($canReverse)
    <div class="card" style="border-color: var(--danger);">
        <div class="card-header" style="background: var(--danger-light); border-bottom-color: transparent;">
            <h3 class="card-title" style="color: var(--danger);">
                <x-heroicon name="undo" style="color: var(--danger);" aria-hidden="true" />
                منطقة العكس
            </h3>
        </div>
        <div class="card-body">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm" style="color: var(--text-secondary);">
                    عكس المعاملة يُعلّم الأصل كـ«معكوس» ويُنشئ معاملة تسوية معاكِسة مُدقّقة. لا يمكن التراجع.
                </p>
                <button
                    type="button"
                    @click="$dispatch('open-reverse-modal', {
                        reference: @js($transaction->reference),
                        amount: {{ $amount }},
                        symbol: @js($sym),
                        reverseUrl: '{{ route('admin.transactions.reverse', $transaction->id) }}'
                    })"
                    class="btn btn-danger btn-sm shrink-0"
                >
                    <x-heroicon name="undo" class="text-sm" aria-hidden="true" />
                    عكس المعاملة
                </button>
            </div>
        </div>
    </div>
    @endif

</div>

{{-- Reverse modal --}}
@include('admin.transactions.partials._modals')

@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('reverseModal', () => ({
            show: false, loading: false,
            reference: '', amount: 0, symbol: '$', reverseUrl: '',
            reason: '', errors: {}, errorMsg: '',

            open(d) {
                Object.assign(this, {
                    reference: d.reference,
                    amount:    d.amount,
                    symbol:    d.symbol,
                    reverseUrl: d.reverseUrl,
                    reason: '', errors: {}, errorMsg: '', show: true
                });
                this.$nextTick(() => this.$refs.firstFocus?.focus());
            },
            close() { this.show = false; },
            async submit() {
                this.errors = {}; this.errorMsg = '';
                if (!this.reason || this.reason.length < 3) {
                    this.errors.reason = 'أدخل سبباً لا يقل عن 3 أحرف.'; return;
                }
                this.loading = true;
                try {
                    const r = await fetch(this.reverseUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ reason: this.reason })
                    });
                    const d = await r.json().catch(() => ({}));
                    if (!r.ok) {
                        this.errorMsg = d.message || 'تعذّر عكس المعاملة.';
                        this.loading = false;
                        return;
                    }
                    this.close();
                    window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: d.message || 'تم عكس المعاملة' } }));
                    setTimeout(() => window.location.reload(), 900);
                } catch (e) {
                    this.errorMsg = 'حدث خطأ — يرجى المحاولة مجدداً.';
                    this.loading = false;
                }
            }
        }));
    });
</script>
@endpush
