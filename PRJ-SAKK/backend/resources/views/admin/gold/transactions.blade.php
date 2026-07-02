@extends('layouts.admin')

@section('title', 'معاملات الذهب')
@section('breadcrumbs')
<span class="breadcrumb-item">الذهب</span>
<span class="breadcrumb-item">المعاملات</span>
@endsection

@push('styles')
    @include('admin.gold._styles')
@endpush

@section('content')
@php
    $netGrams = (float) $stats['buy_grams'] - (float) $stats['sell_grams'];
@endphp
<div class="gold-page space-y-6">

    {{-- ===== Hero ===== --}}
    <div class="gold-hero">
        <div class="gold-hero-main">
            <div class="gold-hero-icon"><x-heroicon name="receipt" /></div>
            <div>
                <h1 class="gold-hero-title">معاملات الذهب</h1>
                <p class="gold-hero-sub">سجل كامل لعمليات شراء وبيع الذهب لجميع المستخدمين</p>
            </div>
        </div>
        <div class="gold-hero-side">
            @if($stats['pending'] > 0)
            <span class="gold-stamp" style="color: var(--warning); background: var(--warning-light); border-color: var(--warning-light);">
                <x-heroicon name="pending" />
                {{ number_format($stats['pending']) }} معلقة
            </span>
            @endif
            <a href="{{ route('admin.gold.prices') }}" class="btn btn-secondary">
                <x-heroicon name="price_change" class="text-sm" />
                إدارة الأسعار
            </a>
        </div>
    </div>

    {{-- ===== KPIs ===== --}}
    <div class="gold-kpis is-six">
        <div class="gold-kpi">
            <div>
                <p class="gold-kpi-label">إجمالي المعاملات</p>
                <p class="gold-kpi-value">{{ number_format($stats['total']) }}</p>
            </div>
            <div class="gold-kpi-icon" style="background: var(--primary-light, rgba(0,0,0,0.04)); color: var(--primary);">
                <x-heroicon name="receipt_long" />
            </div>
        </div>
        <div class="gold-kpi">
            <div>
                <p class="gold-kpi-label">عمليات شراء</p>
                <p class="gold-kpi-value" style="color: var(--success);">{{ number_format($stats['buy']) }}</p>
                <p class="gold-kpi-sub" dir="ltr">{{ number_format($stats['buy_grams'], 2) }}g</p>
            </div>
            <div class="gold-kpi-icon" style="background: var(--success-light); color: var(--success);">
                <x-heroicon name="south_west" />
            </div>
        </div>
        <div class="gold-kpi">
            <div>
                <p class="gold-kpi-label">عمليات بيع</p>
                <p class="gold-kpi-value" style="color: var(--danger);">{{ number_format($stats['sell']) }}</p>
                <p class="gold-kpi-sub" dir="ltr">{{ number_format($stats['sell_grams'], 2) }}g</p>
            </div>
            <div class="gold-kpi-icon" style="background: var(--danger-light); color: var(--danger);">
                <x-heroicon name="north_east" />
            </div>
        </div>
        <div class="gold-kpi">
            <div>
                <p class="gold-kpi-label">صافي الغرامات</p>
                <p class="gold-kpi-value" dir="ltr" style="color: {{ $netGrams >= 0 ? 'var(--success)' : 'var(--danger)' }};">{{ $netGrams >= 0 ? '+' : '' }}{{ number_format($netGrams, 2) }}</p>
                <p class="gold-kpi-sub">شراء − بيع</p>
            </div>
            <div class="gold-kpi-icon" style="background: var(--surface-hover); color: var(--text-secondary);">
                <x-heroicon name="balance" />
            </div>
        </div>
        <div class="gold-kpi">
            <div>
                <p class="gold-kpi-label">حجم التداول</p>
                <p class="gold-kpi-value" dir="ltr">&lrm;${{ number_format($stats['volume'], 2) }}</p>
                <p class="gold-kpi-sub" dir="ltr">متوسط &lrm;${{ number_format($stats['avg_ticket'], 2) }}</p>
            </div>
            <div class="gold-kpi-icon" style="background: var(--info-light); color: var(--info);">
                <x-heroicon name="payments" />
            </div>
        </div>
        <div class="gold-kpi">
            <div>
                <p class="gold-kpi-label">الرسوم المحصّلة</p>
                <p class="gold-kpi-value" dir="ltr">&lrm;${{ number_format($stats['fees'], 2) }}</p>
                <p class="gold-kpi-sub">من العمليات المكتملة</p>
            </div>
            <div class="gold-kpi-icon" style="background: var(--gold-soft); color: var(--gold-deep);">
                <x-heroicon name="savings" />
            </div>
        </div>
    </div>

    {{-- ===== Filter ===== --}}
    <div class="card">
        <div class="card-body">
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="text-xs font-bold text-gray-600 mb-1 block">النوع</label>
                    <select name="type" class="input w-40">
                        <option value="">كل الأنواع</option>
                        <option value="buy" {{ request('type') === 'buy' ? 'selected' : '' }}>شراء</option>
                        <option value="sell" {{ request('type') === 'sell' ? 'selected' : '' }}>بيع</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-bold text-gray-600 mb-1 block">الحالة</label>
                    <select name="status" class="input w-40">
                        <option value="">كل الحالات</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>مكتمل</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>معلق</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>فاشل</option>
                    </select>
                </div>
                <div class="flex-1 min-w-[14rem]">
                    <label class="text-xs font-bold text-gray-600 mb-1 block">بحث</label>
                    <input type="text" name="search" placeholder="اسم المستخدم، البريد، أو المرجع…"
                           value="{{ request('search') }}" class="input w-full">
                </div>
                <button type="submit" class="btn btn-primary">
                    <x-heroicon name="search" class="text-sm" />
                    بحث
                </button>
                @if(request()->anyFilled(['type', 'status', 'search']))
                <a href="{{ route('admin.gold.transactions') }}" class="btn btn-ghost">
                    <x-heroicon name="close" class="text-sm" />
                    مسح
                </a>
                @endif
            </form>

            @if(request()->anyFilled(['type', 'status', 'search']))
            <div class="filter-chips">
                <span class="text-xs font-bold text-gray-500">عوامل التصفية:</span>
                @if(request('type'))
                <span class="filter-chip"><x-heroicon name="swap_vert" /> {{ request('type') === 'buy' ? 'شراء' : 'بيع' }}</span>
                @endif
                @if(request('status'))
                <span class="filter-chip"><x-heroicon name="flag" /> {{ ['completed'=>'مكتمل','pending'=>'معلق','failed'=>'فاشل'][request('status')] ?? request('status') }}</span>
                @endif
                @if(request('search'))
                <span class="filter-chip"><x-heroicon name="search" /> "{{ request('search') }}"</span>
                @endif
                <span class="text-xs font-bold text-gray-500">· {{ number_format($transactions->total()) }} نتيجة</span>
            </div>
            @endif
        </div>
    </div>

    {{-- ===== Table ===== --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-container" style="border: none;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>المرجع</th>
                            <th>المستخدم</th>
                            <th>النوع</th>
                            <th>العيار</th>
                            <th>الغرامات</th>
                            <th>السعر/غرام</th>
                            <th>الإجمالي</th>
                            <th>الرسوم</th>
                            <th>الحالة</th>
                            <th>التاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $tx)
                        <tr>
                            <td><span class="font-mono font-bold text-gray-900 text-xs">{{ $tx->reference }}</span></td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="tx-avatar">{{ strtoupper(substr($tx->user->first_name ?? '?', 0, 1)) }}</div>
                                    <div class="leading-tight">
                                        <span class="text-sm font-bold text-gray-900 block">{{ $tx->user->first_name ?? '#' . $tx->user_id }} {{ $tx->user->last_name ?? '' }}</span>
                                        @if($tx->user?->email)
                                        <span class="text-xs text-gray-400" dir="ltr">{{ $tx->user->email }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="tx-type {{ $tx->type === 'buy' ? 'buy' : 'sell' }}">
                                    <x-heroicon name="south_west" x-show="$tx->type === 'buy'" />
<x-heroicon name="north_east" x-show="!($tx->type === 'buy')" />
                                    {{ $tx->type === 'buy' ? 'شراء' : 'بيع' }}
                                </span>
                            </td>
                            <td><span class="karat-tag">{{ $tx->karat }}</span></td>
                            <td class="font-bold text-gray-900" dir="ltr">{{ number_format($tx->grams, 4) }}</td>
                            <td dir="ltr">&lrm;${{ number_format($tx->price_per_gram_usd, 2) }}</td>
                            <td class="font-extrabold text-gray-900" dir="ltr">&lrm;${{ number_format($tx->total_usd, 2) }}</td>
                            <td dir="ltr" class="text-gray-500">&lrm;${{ number_format($tx->fee_usd, 2) }}</td>
                            <td>
                                <span class="badge {{ $tx->status === 'completed' ? 'badge-success' : ($tx->status === 'pending' ? 'badge-warning' : 'badge-danger') }}">
                                    {{ $tx->status === 'completed' ? 'مكتمل' : ($tx->status === 'pending' ? 'معلق' : 'فاشل') }}
                                </span>
                            </td>
                            <td>
                                <span class="text-xs text-gray-700 block" dir="ltr">{{ $tx->created_at->format('Y/m/d H:i') }}</span>
                                <span class="text-[11px] text-gray-400">{{ $tx->created_at->diffForHumans() }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10">
                                <div class="table-empty">
                                    <x-heroicon name="receipt_long" class="table-empty-icon" />
                                    <p>{{ request()->anyFilled(['type', 'status', 'search']) ? 'لا توجد معاملات مطابقة لعوامل التصفية' : 'لا توجد معاملات ذهب بعد' }}</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($transactions->hasPages())
        <div class="card-footer">
            {{ $transactions->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
