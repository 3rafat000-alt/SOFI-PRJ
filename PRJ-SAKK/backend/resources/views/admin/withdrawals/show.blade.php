@extends('layouts.admin')

@section('title', 'تفاصيل طلب السحب')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-center gap-4">
        <a href="{{ route('admin.withdrawals.index') }}" class="btn btn-sukk-icon" title="عودة">
            <x-heroicon name="arrow_forward" />
        </a>
        <div class="flex-1 min-w-0">
            <h1 class="text-2xl font-extrabold" style="color: var(--text-primary); letter-spacing: -0.02em;">طلب السحب #{{ $withdrawal->id }}</h1>
            <p class="text-sm mt-0.5 font-mono" style="color: var(--text-muted);">{{ $withdrawal->sham_cash_reference }}</p>
        </div>
        <div>
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
            @endswitch
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Info --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Withdrawal Details --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <x-heroicon name="payments" style="color: var(--accent-dark);" />
                        تفاصيل السحب
                    </h3>
                </div>
                <div class="card-body space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="rounded-xl p-4" style="background: var(--surface-hover); border: none;">
                            <p class="stat-label">المبلغ</p>
                            <p class="text-2xl font-extrabold" style="color: var(--text-primary);">&lrm;${{ number_format($withdrawal->amount, 2) }}</p>
                        </div>
                        <div class="rounded-xl p-4" style="background: var(--surface-hover); border: none;">
                            <p class="stat-label">الرسوم</p>
                            <p class="text-2xl font-extrabold" style="color: var(--danger);">&lrm;${{ number_format($withdrawal->fee, 2) }}</p>
                        </div>
                        <div class="rounded-xl p-4" style="background: var(--surface-hover); border: none;">
                            <p class="stat-label">الصافي</p>
                            <p class="text-2xl font-extrabold" style="color: var(--success);">&lrm;${{ number_format($withdrawal->net_amount, 2) }}</p>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="label">المرجع</label>
                            <div class="font-mono text-sm font-semibold" style="color: var(--text-primary);">{{ $withdrawal->sham_cash_reference }}</div>
                        </div>
                        <div>
                            <label class="label">العملة</label>
                            <div class="text-sm font-semibold" style="color: var(--text-primary);">{{ $withdrawal->currency }}</div>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label">عنوان محفظة ShamCash الهدف</label>
                            <div class="flex items-center gap-2">
                                <code class="user-select-all flex-1 text-xs px-3 py-2 rounded-lg break-all" style="background: var(--surface-hover); border: none; color: var(--text-primary);">{{ $withdrawal->sham_wallet_address }}</code>
                                <button class="btn btn-sm btn-ghost" title="نسخ" onclick="navigator.clipboard.writeText('{{ $withdrawal->sham_wallet_address }}')">
                                    <x-heroicon name="content_copy" class="text-sm" />
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="label">تاريخ الإنشاء</label>
                            <div class="text-sm font-semibold" style="color: var(--text-primary);" dir="ltr">{{ $withdrawal->created_at->format('Y-m-d H:i:s') }}</div>
                        </div>
                        <div>
                            <label class="label">تاريخ الاكتمال</label>
                            <div class="text-sm font-semibold" style="color: var(--text-primary);" dir="ltr">{{ $withdrawal->completed_at?->format('Y-m-d H:i:s') ?? '-' }}</div>
                        </div>
                    </div>

                    @if($withdrawal->failure_reason)
                    <div class="rounded-xl p-4" style="background: var(--danger-light); color: var(--danger);">
                        <strong>سبب الفشل:</strong> {{ $withdrawal->failure_reason }}
                    </div>
                    @endif
                </div>
            </div>

            {{-- Actions --}}
            @if($withdrawal->status === 'pending' || $withdrawal->status === 'processing')
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <x-heroicon name="gavel" style="color: var(--accent-dark);" />
                        الإجراءات
                    </h3>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <form method="POST" action="{{ route('admin.withdrawals.approve', $withdrawal) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="label">ملاحظة الموافقة (اختياري)</label>
                                <textarea name="admin_note" class="input" rows="2"></textarea>
                            </div>
                            <button type="submit" class="btn btn-success w-full">
                                <x-heroicon name="check" class="text-sm" />
                                الموافقة على السحب
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.withdrawals.reject', $withdrawal) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="label label-required">سبب الرفض</label>
                                <textarea name="reason" class="input" rows="2" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-danger w-full">
                                <x-heroicon name="close" class="text-sm" />
                                رفض وإعادة المبلغ
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endif

            {{-- API Response --}}
            @if($withdrawal->sham_cash_response)
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <x-heroicon name="api" style="color: var(--accent-dark);" />
                        استجابة ShamCash API
                    </h3>
                </div>
                <div class="card-body">
                    <pre class="p-4 rounded-xl mb-0 text-xs overflow-auto" style="background: var(--primary); color: #e5e7eb; max-height: 200px;">{{ json_encode($withdrawal->sham_cash_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
            @endif

            {{-- Webhook Data --}}
            @if($withdrawal->webhook_data)
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <x-heroicon name="webhook" style="color: var(--accent-dark);" />
                        بيانات Webhook
                    </h3>
                </div>
                <div class="card-body">
                    <pre class="p-4 rounded-xl mb-0 text-xs overflow-auto" style="background: var(--primary); color: #e5e7eb; max-height: 200px;">{{ json_encode($withdrawal->webhook_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- User Info --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <x-heroicon name="person" style="color: var(--accent-dark);" />
                        معلومات المستخدم
                    </h3>
                </div>
                <div class="card-body">
                    @if($withdrawal->user)
                    <div class="text-center mb-4">
                        <div class="w-16 h-16 rounded-full gradient-primary flex items-center justify-center mx-auto mb-2">
                            <span class="text-white text-xl font-extrabold">{{ substr($withdrawal->user->first_name, 0, 1) }}</span>
                        </div>
                        <h5 class="font-bold" style="color: var(--text-primary);">{{ $withdrawal->user->full_name }}</h5>
                        <p class="text-sm" style="color: var(--text-muted);">{{ $withdrawal->user->email }}</p>
                    </div>
                    <div class="divider"></div>
                    <div class="grid grid-cols-2 gap-3 text-center">
                        <div>
                            <small class="block mb-1" style="color: var(--text-muted);">KYC Level</small>
                            <span class="badge {{ $withdrawal->user->kyc_level >= 2 ? 'badge-success' : 'badge-warning' }}">
                                Level {{ $withdrawal->user->kyc_level ?? 0 }}
                            </span>
                        </div>
                        <div>
                            <small class="block mb-1" style="color: var(--text-muted);">الحالة</small>
                            <span class="badge {{ $withdrawal->user->status === 'active' ? 'badge-success' : 'badge-danger' }}">
                                {{ $withdrawal->user->status }}
                            </span>
                        </div>
                    </div>
                    <div class="divider"></div>
                    <a href="{{ route('admin.users.show', $withdrawal->user) }}" class="btn btn-secondary w-full">
                        <x-heroicon name="person" class="text-sm" />
                        عرض الملف الشخصي
                    </a>
                    @else
                    <p class="text-center mb-0" style="color: var(--text-muted);">المستخدم غير موجود</p>
                    @endif
                </div>
            </div>

            {{-- Wallet Info --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <x-heroicon name="account_balance_wallet" style="color: var(--accent-dark);" />
                        معلومات المحفظة
                    </h3>
                </div>
                <div class="card-body">
                    @if($withdrawal->wallet)
                    <div class="flex justify-between items-center mb-3">
                        <span style="color: var(--text-muted);">العملة</span>
                        <strong style="color: var(--text-primary);">{{ $withdrawal->wallet->currency }}</strong>
                    </div>
                    <div class="flex justify-between items-center mb-3">
                        <span style="color: var(--text-muted);">الرصيد الحالي</span>
                        <strong style="color: var(--text-primary);">&lrm;${{ number_format($withdrawal->wallet->balance, 2) }}</strong>
                    </div>
                    <div class="flex justify-between items-center">
                        <span style="color: var(--text-muted);">الرصيد المحجوز</span>
                        <strong style="color: var(--accent-dark);">&lrm;${{ number_format($withdrawal->wallet->reserved_balance ?? 0, 2) }}</strong>
                    </div>
                    @else
                    <p class="text-center mb-0" style="color: var(--text-muted);">المحفظة غير موجودة</p>
                    @endif
                </div>
            </div>

            {{-- User Withdrawal History --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <x-heroicon name="history" style="color: var(--accent-dark);" />
                        سجل سحب المستخدم
                    </h3>
                </div>
                <div class="card-body">
                    @if($userHistory->count() > 0)
                    <div class="space-y-2">
                        @foreach($userHistory as $history)
                        <a href="{{ route('admin.withdrawals.show', $history) }}" class="flex items-center justify-between p-3 rounded-xl transition-colors" style="background: var(--surface-hover);">
                            <div>
                                <strong style="color: var(--text-primary);">&lrm;${{ number_format($history->amount, 2) }}</strong>
                                <br>
                                <small style="color: var(--text-muted);" dir="ltr">{{ $history->created_at->format('Y-m-d') }}</small>
                            </div>
                            @switch($history->status)
                                @case('completed')
                                    <span class="badge badge-success">مكتمل</span>
                                    @break
                                @case('failed')
                                    <span class="badge badge-danger">فاشل</span>
                                    @break
                                @default
                                    <span class="badge badge-secondary">{{ $history->status }}</span>
                            @endswitch
                        </a>
                        @endforeach
                    </div>
                    @else
                    <p class="text-center py-3 mb-0" style="color: var(--text-muted);">لا يوجد سجل سابق</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
