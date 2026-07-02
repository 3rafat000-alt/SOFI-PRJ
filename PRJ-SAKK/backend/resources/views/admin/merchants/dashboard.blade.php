@extends('layouts.admin')

@section('title', 'لوحة ' . $merchant->store_name)

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="gradient-primary relative overflow-hidden" style="border-radius: var(--radius-xl);">
        <div class="absolute -top-16 -left-16 w-64 h-64 rounded-full" style="background: rgba(245,158,11,0.10);"></div>
        <div class="absolute -bottom-24 right-10 w-72 h-72 rounded-full" style="background: rgba(255,255,255,0.04);"></div>
        <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-5 p-7">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.merchants.show', $merchant) }}" class="w-10 h-10 rounded-xl flex items-center justify-center hover:bg-white/10 transition-colors">
                    <x-heroicon name="arrow_forward" class="text-white" />
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-extrabold text-white">{{ $merchant->store_name }}</h1>
                        <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-lg text-xs font-bold {{
                            $merchant->type === 'ecommerce' ? 'bg-sky-500/20 text-sky-300' :
                            ($merchant->type === 'both' ? 'bg-purple-500/20 text-purple-300' :
                            'bg-white/10 text-white/70')
                        }}">
                            {{ $merchant->typeLabel() }}
                        </span>
                    </div>
                    <p class="text-sm mt-1" style="color: rgba(255,255,255,0.6);">
                        {{ $merchant->merchant_code }}
                        @if($merchant->city) · {{ $merchant->city }} @endif
                        @if($merchant->governorate) — {{ $merchant->governorate }} @endif
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.merchants.edit', $merchant) }}" class="btn btn-secondary">
                    <x-heroicon name="edit" class="text-sm" />
                    تعديل البيانات
                </a>
                <a href="{{ route('admin.merchants.show', $merchant) }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-sm transition-all" style="background: var(--accent); color: #1a1207;">
                    <x-heroicon name="info" class="text-lg" />
                    معلومات التاجر
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">الرصيد الحالي</p>
                    <p class="stat-value" dir="ltr">&lrm;${{ number_format($merchant->balance, 2) }}</p>
                    <p class="text-sm mt-2" style="color: var(--text-muted);">متاح للسحب</p>
                </div>
                <div class="stat-icon" style="background: var(--accent-soft); color: var(--accent-dark);">
                    <x-heroicon name="account_balance_wallet" class="text-2xl" />
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">الإجمالي المكتسب</p>
                    <p class="stat-value" dir="ltr" style="color: var(--success);">&lrm;${{ number_format($merchant->total_earned, 2) }}</p>
                    <p class="stat-change up mt-2">
                        <x-heroicon name="trending_up" class="text-sm" />
                        +&lrm;${{ number_format($stats['earned_this_month'], 2) }} هذا الشهر
                    </p>
                </div>
                <div class="stat-icon" style="background: var(--success-light); color: var(--success);">
                    <x-heroicon name="payments" class="text-2xl" />
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">إجمالي المعاملات</p>
                    <p class="stat-value">{{ number_format($stats['total_transactions']) }}</p>
                    <p class="text-sm mt-2" style="color: var(--info);">+{{ $stats['transactions_this_month'] }} هذا الشهر</p>
                </div>
                <div class="stat-icon" style="background: var(--info-light); color: var(--info);">
                    <x-heroicon name="receipt_long" class="text-2xl" />
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">نسبة العمولة</p>
                    <p class="stat-value">{{ number_format($merchant->commission_rate, 1) }}%</p>
                    <p class="text-sm mt-2" style="color: var(--text-muted);">
                        @if($merchant->has_api_access)
                        <span class="badge badge-success" style="font-size: 10px;">API مفعل</span>
                        @else
                        <span class="badge badge-secondary" style="font-size: 10px;">API معطل</span>
                        @endif
                    </p>
                </div>
                <div class="stat-icon" style="background: rgba(245,158,11,0.12); color: var(--accent-dark);">
                    <x-heroicon name="commission" class="text-2xl" />
                </div>
            </div>
        </div>
    </div>

    <!-- Charts + Details -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 space-y-6">

            <!-- Earnings Chart -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">
                            <x-heroicon name="trending_up" class="text-accent" />
                            تطور الأرباح
                        </h3>
                        <p class="card-subtitle">الإيرادات خلال آخر 7 أيام</p>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="merchantChart" height="200"></canvas>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <x-heroicon name="history" class="text-accent" />
                        آخر المعاملات
                    </h3>
                    <a href="{{ route('admin.transactions') }}" class="link text-sm">عرض الكل</a>
                </div>
                <div class="card-body">
                    <div class="space-y-4">
                        @forelse($recentActivities as $activity)
                        <div class="flex items-center justify-between p-3 rounded-xl transition-colors" style="background: var(--surface-hover);">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center
                                    {{ $activity['type'] === 'payment' ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600' }}">
                                    <x-heroicon name="shopping_cart" x-show="$activity['type'] === 'payment'" class="text-lg" />
<x-heroicon name="currency_exchange" x-show="!($activity['type'] === 'payment')" class="text-lg" />
                                </div>
                                <div>
                                    <p class="text-sm font-bold" style="color: var(--text-primary);">{{ $activity['description'] }}</p>
                                    <p class="text-xs" style="color: var(--text-muted);">{{ $activity['date'] }}</p>
                                </div>
                            </div>
                            <span class="text-sm font-extrabold {{ $activity['type'] === 'payment' ? 'text-green-600' : 'text-red-500' }}" dir="ltr">
                                {{ $activity['type'] === 'payment' ? '+' : '-' }}&lrm;${{ number_format($activity['amount'], 2) }}
                            </span>
                        </div>
                        @empty
                        <div class="text-center py-8" style="color: var(--text-muted);">
                            <x-heroicon name="receipt_long" class="text-4xl mb-3" />
                            <p class="text-sm">لا توجد معاملات بعد — سيتم عرض المدفوعات هنا فور بدء النشاط</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">

            <!-- Overview Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <x-heroicon name="monitoring" class="text-accent" />
                        نظرة عامة
                    </h3>
                </div>
                <div class="card-body space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm" style="color: var(--text-secondary);">الحالة</span>
                        <span class="inline-flex items-center gap-1.5 text-sm font-bold {{ $merchant->is_active ? 'text-emerald-700' : 'text-gray-400' }}">
                            <span class="w-2.5 h-2.5 rounded-full {{ $merchant->is_active ? 'bg-emerald-500' : 'bg-gray-300' }}"></span>
                            {{ $merchant->is_active ? 'نشط' : 'معطل' }}
                        </span>
                    </div>
                    <hr style="border-color: var(--border)">
                    <div class="flex items-center justify-between">
                        <span class="text-sm" style="color: var(--text-secondary);">النوع</span>
                        <span class="text-sm font-bold" style="color: var(--text-primary);">{{ $merchant->typeLabel() }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm" style="color: var(--text-secondary);">ل.س -> دولار</span>
                        <span class="text-sm font-extrabold" style="color: var(--text-primary);">{{ $stats['syp_rate'] ?? 13000 }}</span>
                    </div>
                    <hr style="border-color: var(--border)">
                    <div class="flex items-center justify-between">
                        <span class="text-sm" style="color: var(--text-secondary);">API</span>
                        <span class="badge {{ $merchant->has_api_access ? 'badge-success' : 'badge-secondary' }}">
                            {{ $merchant->has_api_access ? 'مفعل' : 'معطل' }}
                        </span>
                    </div>
                    @if($merchant->has_api_access)
                    <div class="flex items-center justify-between">
                        <span class="text-sm" style="color: var(--text-secondary);">البيئة</span>
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-bold {{
                            $merchant->environment === 'production' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-50 text-gray-700'
                        }}">{{ $merchant->environment === 'production' ? 'إنتاجي' : 'تجريبي' }}</span>
                    </div>
                    @endif
                    <hr style="border-color: var(--border)">
                    <div class="flex items-center justify-between">
                        <span class="text-sm" style="color: var(--text-secondary);">اسم المالك</span>
                        <span class="text-sm font-bold" style="color: var(--text-primary);">{{ $merchant->owner_name ?? '—' }}</span>
                    </div>
                    @if($merchant->email)
                    <div class="flex items-center justify-between">
                        <span class="text-sm" style="color: var(--text-secondary);">البريد</span>
                        <span class="text-sm font-bold" style="color: var(--text-primary);" dir="ltr">{{ $merchant->email }}</span>
                    </div>
                    @endif
                    <div class="flex items-center justify-between">
                        <span class="text-sm" style="color: var(--text-secondary);">تاريخ التسجيل</span>
                        <span class="text-sm font-bold" style="color: var(--text-primary);" dir="ltr">{{ $merchant->created_at->format('Y/m/d') }}</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <x-heroicon name="bolt" class="text-accent" />
                        إجراءات سريعة
                    </h3>
                </div>
                <div class="card-body space-y-3">
                    <a href="{{ route('admin.merchants.edit', $merchant) }}" class="btn btn-primary w-full justify-start">
                        <x-heroicon name="edit" class="text-sm" />
                        تعديل البيانات
                    </a>
                    <a href="{{ route('admin.merchants.show', $merchant) }}" class="btn btn-secondary w-full justify-start">
                        <x-heroicon name="info" class="text-sm" />
                        صفحة التاجر
                    </a>
                    @if($merchant->has_api_access)
                    <form method="POST" action="{{ route('admin.merchants.regenerate-keys', $merchant) }}" class="w-full" onsubmit="return confirm('هل أنت متأكد من تجديد المفاتيح؟')">
                        @csrf
                        <button type="submit" class="btn btn-secondary w-full justify-start">
                            <x-heroicon name="refresh" class="text-sm" />
                            تجديد مفاتيح API
                        </button>
                    </form>
                    @endif
                    @if($merchant->website_url)
                    <a href="{{ $merchant->website_url }}" target="_blank" class="btn btn-secondary w-full justify-start">
                        <x-heroicon name="open_in_new" class="text-sm" />
                        زيارة الموقع
                    </a>
                    @endif
                </div>
            </div>

            <!-- Notes -->
            @if($merchant->notes)
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <x-heroicon name="note" class="text-accent" />
                        ملاحظات
                    </h3>
                </div>
                <div class="card-body">
                    <p class="text-sm whitespace-pre-line" style="color: var(--text-primary);">{{ $merchant->notes }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    const gridColor = 'rgba(16, 18, 22, 0.05)';
    const tickColor = '#9aa1ac';

    const ctx = document.getElementById('merchantChart').getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 220);
    gradient.addColorStop(0, 'rgba(22, 163, 74, 0.18)');
    gradient.addColorStop(1, 'rgba(22, 163, 74, 0)');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($chartData['labels'] ?? ['السبت', 'الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة']),
            datasets: [{
                label: 'الأرباح ($)',
                data: @json($chartData['values'] ?? [120, 340, 210, 580, 430, 760, 620]),
                borderColor: '#16a34a',
                backgroundColor: gradient,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#16a34a',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#15181d',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1,
                    cornerRadius: 8,
                    padding: 10,
                    displayColors: false,
                    callbacks: { label: (ctx) => '$' + ctx.parsed.y.toLocaleString() }
                }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: tickColor, callback: v => '$' + v } },
                x: { grid: { display: false }, ticks: { color: tickColor } }
            }
        }
    });
</script>
@endpush
@endsection
