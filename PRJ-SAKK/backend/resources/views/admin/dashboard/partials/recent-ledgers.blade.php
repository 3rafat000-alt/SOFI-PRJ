{{-- ═══════════════════════════════════════════════════════════════
     C · RECENT LARGE OPERATIONS LEDGER (آخر العمليات المالية)
     Multi-currency financial movements with status badges
     ═══════════════════════════════════════════════════════════════ --}}
<div class="card-sukk-main">
    <div class="card-header">
        <div class="card-title" style="font-size:.85rem;">
            <x-heroicon name="account_balance" style="color:var(--sukk-primary);font-size:1.2rem;" />
            <span>آخر العمليات المالية الكبيرة</span>
        </div>
        <a href="{{ route('admin.transactions') }}"
           style="font-size:.7rem;font-weight:700;color:var(--sukk-primary);text-decoration:none;display:inline-flex;align-items:center;gap:var(--space-xs);">
            عرض الكل
            <x-heroicon name="chevron_left" style="font-size:.9rem;" />
        </a>
    </div>
    <div>
        @forelse($recentLargeOps ?? [] as $tx)
            @php
                $isSyp = ($tx->currency ?? 'USD') === 'SYP';
                $amount = abs((float)($tx->amount ?? 0));
                $formatted = \App\Support\Money::format($amount, $tx->currency ?? 'USD');
                $userName = optional($tx->user)->first_name . ' ' . optional($tx->user)->last_name;
                $userName = trim($userName) ?: 'مستخدم';
            @endphp
            <div class="rl-item">
                <div class="rl-icon">
                    <x-heroicon name="currency_exchange" style="font-size:1.2rem;" x-show="$isSyp" />
<x-heroicon name="attach_money" style="font-size:1.2rem;" x-show="!$isSyp" />
                </div>
                <div style="flex:1;min-width:0;">
                    <div class="rl-amount {{ $isSyp ? 'rl-currency-syp' : 'rl-currency-usd' }}">
                        +{!! $formatted !!}
                    </div>
                    <div class="rl-user">
                        <x-heroicon name="person" />
                        {{ $userName }}
                    </div>
                </div>
                <div style="text-align:left;flex-shrink:0;">
                    <div class="rl-time">
                        {{ optional($tx->created_at)->format('Y/m/d · H:i') }}
                    </div>
                    <div class="rl-badge rl-badge-success" style="margin-top:var(--space-xs);">
                        <x-heroicon name="check_circle" style="font-size:.6rem;" />
                        ناجحة
                    </div>
                </div>
            </div>
        @empty
            <div class="rl-empty">
                <x-heroicon name="receipt_long" />
                <p>لا توجد عمليات كبيرة بعد</p>
            </div>
        @endforelse
    </div>
</div>
