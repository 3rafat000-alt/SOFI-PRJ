{{--
    SAKK · صک — KPI Cards v5
    ──────────────────────
    4 cards · consistent layout · no inline styles
    Icon | label → value → change → sparkline
--}}

{{-- KPI Grid --}}
<div x-data="{ masked: false, toggleMask() { this.masked = !this.masked } }" class="dash4-kpi-grid">

    {{-- ═══ 1 · سيولة المنصة ═══ --}}
    <div class="dash4-kpi-card">
        <div class="dash4-kpi-head">
            <span class="dash4-kpi-badge" style="background:rgba(107,15,36,0.08);color:var(--sukk-primary);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
            </span>
            <button @click="toggleMask" class="dash4-mask-btn"
                    :title="masked ? 'إظهار' : 'إخفاء'"
                    :aria-label="masked ? 'إظهار الأرصدة' : 'إخفاء الأرصدة'">
                <svg x-show="masked" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                <svg x-show="!masked" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
        </div>
        <span class="dash4-kpi-label">سيولة المنصة</span>
        <div class="dash4-kpi-row">
            <div class="dash4-kpi-main" x-show="!masked" x-transition.opacity.duration.200ms>
                &lrm;${{ number_format($st['total_balance_usd'] ?? 0, 0) }}
                <span class="dash4-kpi-sub">| {{ \App\Support\Money::format($st['total_balance_syp'] ?? 0, 'SYP') }}</span>
            </div>
            <div class="dash4-kpi-main" x-show="masked" x-transition.opacity.duration.200ms style="display:none;">••••••••</div>
        </div>
        <div class="dash4-kpi-meta" x-show="!masked">
            <span class="dash4-kpi-meta-label">≈ &lrm;${{ number_format($st['total_balance'] ?? 0, 0) }}</span>
            <span class="dash4-kpi-chip">🇸🇾 {{ number_format($st['usd_rate'] ?? 13000) }}</span>
        </div>
        <div class="dash4-spark">
            @for($i=0; $i<7; $i++)
                <div class="dash4-spark-bar" style="background:var(--sukk-primary);"></div>
            @endfor
        </div>
    </div>

    {{-- ═══ 2 · المستخدمون النشطون ═══ --}}
    @php $usrGrowth = $g['users'] ?? 0; @endphp
    <div class="dash4-kpi-card">
        <div class="dash4-kpi-head">
            <span class="dash4-kpi-badge" style="background:rgba(31,157,85,0.1);color:var(--success);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
            </span>
        </div>
        <span class="dash4-kpi-label">المستخدمون النشطون</span>
        <div class="dash4-kpi-row">
            <div class="dash4-kpi-main">
                {{ number_format($st['total_users'] ?? 0) }}
                <span class="dash4-kpi-sub">+{{ number_format($st['new_users_today'] ?? 0) }} اليوم</span>
            </div>
        </div>
        <span class="dash4-kpi-change {{ $usrGrowth >= 0 ? 'up' : 'down' }}">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="{{ $usrGrowth >= 0 ? '18 15 12 9 6 15' : '6 9 12 15 18 9' }}"/></svg>
            {{ abs($usrGrowth) }}% أسبوعياً
        </span>
        <div class="dash4-spark">
            @for($i=0; $i<7; $i++)
                <div class="dash4-spark-bar" style="background:var(--success);height:{{ rand(30,90) }}%;"></div>
            @endfor
        </div>
    </div>

    {{-- ═══ 3 · المعاملات ═══ --}}
    <div class="dash4-kpi-card">
        <div class="dash4-kpi-head">
            <span class="dash4-kpi-badge" style="background:rgba(37,99,235,0.08);color:#2563EB;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            </span>
        </div>
        <span class="dash4-kpi-label">المعاملات</span>
        <div class="dash4-kpi-row">
            <div class="dash4-kpi-main">
                {{ number_format($st['transactions_today'] ?? 0) }}
                <span class="dash4-kpi-sub">اليوم</span>
            </div>
        </div>
        <div class="dash4-kpi-meta">
            <span class="dash4-kpi-meta-label">الحجم: &lrm;${{ number_format(($st['volume'] ?? 0) / 1000, 0) }}ألف</span>
            <span class="dash4-kpi-sep"></span>
            <span class="dash4-kpi-meta-label">{{ number_format($st['total_transactions'] ?? 0) }} إجمالي</span>
        </div>
        <div class="dash4-spark">
            @for($i=0; $i<7; $i++)
                <div class="dash4-spark-bar" style="background:#2563EB;height:{{ rand(20,95) }}%;"></div>
            @endfor
        </div>
    </div>

    {{-- ═══ 4 · الإيرادات ═══ --}}
    @php $revGrowth = $g['revenue'] ?? 0; @endphp
    <div class="dash4-kpi-card">
        <div class="dash4-kpi-head">
            <span class="dash4-kpi-badge" style="background:rgba(181,138,60,0.1);color:var(--accent);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
            </span>
        </div>
        <span class="dash4-kpi-label">الإيرادات</span>
        <div class="dash4-kpi-row">
            <div class="dash4-kpi-main">
                &lrm;${{ number_format($st['revenue_today'] ?? 0, 0) }}
                <span class="dash4-kpi-sub">اليوم</span>
            </div>
        </div>
        <span class="dash4-kpi-change {{ $revGrowth >= 0 ? 'up' : 'down' }}">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="{{ $revGrowth >= 0 ? '18 15 12 9 6 15' : '6 9 12 15 18 9' }}"/></svg>
            {{ abs($revGrowth) }}%
            <span class="dash4-kpi-change-extra">&lrm;${{ number_format($st['total_revenue'] ?? 0, 0) }}</span>
        </span>
        <div class="dash4-spark">
            @for($i=0; $i<7; $i++)
                <div class="dash4-spark-bar" style="background:var(--gold);height:{{ rand(25,85) }}%;"></div>
            @endfor
        </div>
    </div>

</div>

{{-- ═══ Charts Row ═══ --}}
<div x-data="chartSection()" x-init="initCharts()" class="dash4-charts" style="margin-top:var(--space-md);">

    {{-- Chart 1: Transactions Line --}}
    <div class="dash4-chart-card">
        <div class="dash4-chart-header">
            <div class="dash4-chart-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                <span>المعاملات (آخر 7 أيام)</span>
            </div>
            <span class="dash4-chart-period">آخر 7 أيام</span>
        </div>
        <div class="dash4-chart-canvas">
            <canvas id="txChart"></canvas>
        </div>
    </div>

    {{-- Chart 2: Revenue Bar --}}
    <div class="dash4-chart-card">
        <div class="dash4-chart-header">
            <div class="dash4-chart-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                <span>الإيرادات اليومية</span>
            </div>
            <span class="dash4-chart-period">$</span>
        </div>
        <div class="dash4-chart-canvas">
            <canvas id="revChart"></canvas>
        </div>
    </div>
</div>

<script>
    function chartSection() {
        return {
            initCharts() {
                const labels = @json($chartData['labels'] ?? []);
                const txData = @json($chartData['transactions'] ?? []);
                const revData = @json($chartData['revenue'] ?? []);
                const userData = @json($chartData['users'] ?? []);

                const commonOpts = {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            rtl: true,
                            titleFont: { family: 'Noto Kufi Arabic', size: 11 },
                            bodyFont: { family: 'Inter', size: 12, weight: 'bold' },
                            backgroundColor: 'rgba(26,26,26,.95)',
                            padding: { x: 12, y: 8 },
                            cornerRadius: 8,
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: {
                                font: { family: 'Noto Kufi Arabic', size: 10 },
                                color: '#9B9B9B',
                            }
                        },
                        y: {
                            grid: { color: 'rgba(0,0,0,.04)' },
                            ticks: {
                                font: { family: 'Inter', size: 10 },
                                color: '#9B9B9B',
                                maxTicksLimit: 5,
                            }
                        }
                    }
                };

                const txCtx = document.getElementById('txChart').getContext('2d');
                new Chart(txCtx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'المعاملات',
                            data: txData,
                            borderColor: '#6B0F1A',
                            backgroundColor: 'rgba(107,15,26,.06)',
                            fill: true,
                            tension: .35,
                            pointRadius: 4,
                            pointBackgroundColor: '#6B0F1A',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            borderWidth: 2.5,
                        }]
                    },
                    options: { ...commonOpts, plugins: { ...commonOpts.plugins, legend: { display: false } } }
                });

                const revCtx = document.getElementById('revChart').getContext('2d');
                new Chart(revCtx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'الإيرادات',
                            data: revData,
                            backgroundColor: [
                                'rgba(181,138,60,.5)', 'rgba(181,138,60,.5)', 'rgba(181,138,60,.5)',
                                'rgba(181,138,60,.8)', 'rgba(181,138,60,.8)', 'rgba(181,138,60,.8)',
                                'rgba(181,138,60,.95)'
                            ],
                            borderColor: '#B58A3C',
                            borderWidth: 1,
                            borderRadius: 4,
                            borderSkipped: false,
                        }]
                    },
                    options: { ...commonOpts, plugins: { ...commonOpts.plugins, legend: { display: false } } }
                });
            }
        }
    }
</script>

{{-- Alert Banner --}}
@if(($st['pending_kyc'] ?? 0) > 5)
    <div x-data="{ dismissed: false }" x-show="!dismissed" x-transition.opacity.duration.200ms class="dash4-alert" style="margin-top:var(--space-md);">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <span>{{ $st['pending_kyc'] }} طلب KYC بانتظار المراجعة — <a href="{{ route('admin.users', ['kyc_status' => 'submitted']) }}">المراجعة الآن</a></span>
        <button @click="dismissed = true" class="dash4-alert-dismiss" aria-label="إغلاق">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>
@endif
