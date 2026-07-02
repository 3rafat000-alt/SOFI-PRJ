@extends('layouts.admin')

@section('title', 'لوحة ' . $agent->name)

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="gradient-primary relative overflow-hidden" style="border-radius: var(--radius-xl);">
        <div class="absolute -top-16 -left-16 w-64 h-64 rounded-full" style="background: rgba(245,158,11,0.10);"></div>
        <div class="absolute -bottom-24 right-10 w-72 h-72 rounded-full" style="background: rgba(255,255,255,0.04);"></div>
        <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-5 p-7">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.agents.show', $agent) }}" class="w-10 h-10 rounded-xl flex items-center justify-center hover:bg-white/10 transition-colors">
                    <x-heroicon name="arrow_forward" class="text-white" />
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-extrabold text-white">{{ $agent->name }}</h1>
                        <span class="badge {{ $agent->is_active ? 'badge-success' : 'badge-danger' }}">{{ $agent->is_active ? 'نشط' : 'معطل' }}</span>
                    </div>
                    <p class="text-sm mt-1" style="color: rgba(255,255,255,0.6);">{{ $agent->agent_code }} · {{ $agent->city }} {{ $agent->governorate ? '— '.$agent->governorate : '' }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.agents.edit', $agent) }}" class="btn btn-secondary">
                    <x-heroicon name="edit" class="text-sm" />
                    تعديل البيانات
                </a>
                <a href="{{ route('admin.agents.show', $agent) }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-sm transition-all" style="background: var(--accent); color: #1a1207;">
                    <x-heroicon name="info" class="text-lg" />
                    معلومات الوكيل
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">إجمالي المعاملات</p>
                    <p class="stat-value">{{ number_format($stats['total_transactions']) }}</p>
                    <p class="text-sm mt-2" style="color: var(--text-muted);">منذ بداية التشغيل</p>
                </div>
                <div class="stat-icon" style="background: var(--accent-soft); color: var(--accent-dark);">
                    <x-heroicon name="receipt_long" class="text-2xl" />
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">حجم التداول النقدي</p>
                    <p class="stat-value">&lrm;${{ number_format($stats['total_cash_flow'], 2) }}</p>
                    <p class="text-sm mt-2" style="color: var(--text-muted);">إيداع + سحب</p>
                </div>
                <div class="stat-icon" style="background: var(--success-light); color: var(--success);">
                    <x-heroicon name="payments" class="text-2xl" />
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">العمولات المحققة</p>
                    <p class="stat-value">&lrm;${{ number_format($stats['total_commission'], 2) }}</p>
                    <p class="text-sm mt-2" style="color: var(--info);">بنسبة {{ $agent->commission_rate }}%</p>
                </div>
                <div class="stat-icon" style="background: var(--info-light); color: var(--info);">
                    <x-heroicon name="trending_up" class="text-2xl" />
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">التقييم العام</p>
                    <p class="stat-value" style="color: var(--accent-dark);">{{ number_format($agent->rating, 1) }}</p>
                    <div class="flex items-center gap-1 mt-1">
                        @for($i = 1; $i <= 5; $i++)
                        <x-heroicon name="star" class="text-sm {{ $i <= round($agent->rating) ? 'text-amber-500' : 'text-gray-300' }}" />
                        @endfor
                        <span class="text-xs mr-1" style="color: var(--text-muted);">({{ $agent->reviews_count }})</span>
                    </div>
                </div>
                <div class="stat-icon" style="background: rgba(245,158,11,0.12); color: #d97706;">
                    <x-heroicon name="star" class="text-2xl" />
                </div>
            </div>
        </div>
    </div>

    <!-- Charts + Quick Info -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Performance Chart -->
        <div class="lg:col-span-2 space-y-6">

            <!-- أداة الرسم البياني لأداء الوكيل -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">
                            <x-heroicon name="show_chart" class="text-accent" />
                            أداء المعاملات
                        </h3>
                        <p class="card-subtitle">تطور حجم المعاملات خلال آخر 7 أيام</p>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="agentChart" height="200"></canvas>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <x-heroicon name="history" class="text-accent" />
                        آخر النشاطات
                    </h3>
                    <a href="{{ route('admin.audit.index') }}" class="link text-sm">عرض الكل</a>
                </div>
                <div class="card-body">
                    <div class="space-y-4">
                        @forelse($recentActivities as $activity)
                        <div class="flex items-center justify-between p-3 rounded-xl transition-colors" style="background: var(--surface-hover);">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center
                                    {{ $activity['type'] === 'cash_in' ? 'bg-green-50 text-green-600' : 'bg-blue-50 text-blue-600' }}">
                                    <x-heroicon name="add_circle" x-show="$activity['type'] === 'cash_in'" class="text-lg" />
<x-heroicon name="remove_circle" x-show="!($activity['type'] === 'cash_in')" class="text-lg" />
                                </div>
                                <div>
                                    <p class="text-sm font-bold" style="color: var(--text-primary);">
                                        {{ $activity['type'] === 'cash_in' ? 'إيداع' : 'سحب' }} — {{ $activity['user_name'] }}
                                    </p>
                                    <p class="text-xs" style="color: var(--text-muted);">{{ $activity['date'] }}</p>
                                </div>
                            </div>
                            <span class="text-sm font-extrabold {{ $activity['type'] === 'cash_in' ? 'text-green-600' : 'text-red-500' }}" dir="ltr">
                                {{ $activity['type'] === 'cash_in' ? '+' : '-' }}&lrm;${{ number_format($activity['amount'], 2) }}
                            </span>
                        </div>
                        @empty
                        <div class="text-center py-8" style="color: var(--text-muted);">
                            <x-heroicon name="receipt" class="text-4xl mb-3" />
                            <p class="text-sm">لا توجد معاملات بعد — ابدأ باستخدام الوكيل لإجراء المعاملات</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">

            <!-- Service Overview -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <x-heroicon name="handyman" class="text-accent" />
                        نظرة سريعة
                    </h3>
                </div>
                <div class="card-body space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm" style="color: var(--text-secondary);">الخدمات</span>
                        <div class="flex gap-1">
                            @if(in_array('cash_in', $agent->services ?? []))
                            <span class="badge badge-success">إيداع</span>
                            @endif
                            @if(in_array('cash_out', $agent->services ?? []))
                            <span class="badge badge-primary">سحب</span>
                            @endif
                        </div>
                    </div>
                    <hr class="style="border-color: var(--border)"">
                    <div class="flex items-center justify-between">
                        <span class="text-sm" style="color: var(--text-secondary);">نسبة العمولة</span>
                        <span class="text-sm font-extrabold" style="color: var(--text-primary);">{{ $agent->commission_rate }}%</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm" style="color: var(--text-secondary);">الحد الأدنى للسحب</span>
                        <span class="text-sm font-bold" style="color: var(--text-primary);" dir="ltr">&lrm;${{ number_format($agent->min_amount, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm" style="color: var(--text-secondary);">الحد الأقصى للسحب</span>
                        <span class="text-sm font-bold" style="color: var(--text-primary);" dir="ltr">&lrm;${{ $agent->max_amount ? number_format($agent->max_amount, 2) : 'غير محدد' }}</span>
                    </div>
                    <hr class="style="border-color: var(--border)"">
                    <div class="flex items-center justify-between">
                        <span class="text-sm" style="color: var(--text-secondary);">مميز</span>
                        <span class="badge {{ $agent->is_featured ? 'badge-warning' : 'badge-secondary' }}">{{ $agent->is_featured ? 'نعم' : 'لا' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm" style="color: var(--text-secondary);">موثق</span>
                        <span class="badge {{ $agent->is_verified ? 'badge-success' : 'badge-secondary' }}">{{ $agent->is_verified ? 'موثق' : 'غير موثق' }}</span>
                    </div>
                    <hr class="style="border-color: var(--border)"">
                    <div class="flex items-center justify-between">
                        <span class="text-sm" style="color: var(--text-secondary);">ساعات العمل</span>
                        <span class="text-sm font-bold" style="color: var(--text-primary);">{{ $agent->working_hours ?? 'غير محدد' }}</span>
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
                    <a href="https://www.google.com/maps?q={{ $agent->latitude }},{{ $agent->longitude }}" target="_blank" class="btn btn-secondary w-full justify-start">
                        <x-heroicon name="map" class="text-sm" />
                        عرض على الخريطة
                    </a>
                    @if($agent->phone)
                    <a href="tel:{{ $agent->phone }}" class="btn btn-secondary w-full justify-start">
                        <x-heroicon name="phone" class="text-sm" />
                        اتصال
                    </a>
                    @endif
                    <a href="{{ route('admin.agents.edit', $agent) }}" class="btn btn-primary w-full justify-start">
                        <x-heroicon name="edit" class="text-sm" />
                        تعديل البيانات
                    </a>
                    <a href="{{ route('admin.agents.show', $agent) }}" class="btn btn-secondary w-full justify-start">
                        <x-heroicon name="info" class="text-sm" />
                        صفحة الوكيل
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const gridColor = 'rgba(16, 18, 22, 0.05)';
    const tickColor = '#9aa1ac';

    const ctx = document.getElementById('agentChart').getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 220);
    gradient.addColorStop(0, 'rgba(245, 158, 11, 0.18)');
    gradient.addColorStop(1, 'rgba(245, 158, 11, 0)');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($chartData['labels'] ?? ['السبت', 'الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة']),
            datasets: [{
                label: 'حجم المعاملات ($)',
                data: @json($chartData['values'] ?? [450, 620, 380, 740, 510, 890, 695]),
                borderColor: '#f59e0b',
                backgroundColor: gradient,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#f59e0b',
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
