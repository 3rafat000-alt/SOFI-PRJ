{{-- ═══════════════════════════════════════════════════════════════
     D · PLATFORM BALANCES (أرصدة المنصة)
     Dual-currency display + Alpine mask toggle + micro-stats
     ═══════════════════════════════════════════════════════════════ --}}
<div x-data="balancesCard">
    {{-- Header with title + mask toggle --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-md);">
        <p style="font-size:.82rem;font-weight:800;color:var(--text-primary);margin:0;">
            <x-heroicon name="account_balance_wallet" style="color:var(--sukk-primary);font-size:1.1rem;vertical-align:middle;margin-left:var(--space-xs);" />
            أرصدة المنصة
        </p>
        <button @click="toggleMask"
                :title="masked ? 'إظهار الأرصدة' : 'إخفاء الأرصدة'"
                :aria-label="masked ? 'إظهار الأرصدة' : 'إخفاء الأرصدة'"
                class="btn-sukk-icon" style="width:32px;height:32px;color:var(--text-muted);">
            <x-heroicon name="visibility_off" style="font-size:1.2rem;" x-show="masked" />
<x-heroicon name="visibility" style="font-size:1.2rem;" x-show="!masked" />
        </button>
    </div>

    {{-- Balance cards --}}
    <div class="bc-grid">
        {{-- USD Balance --}}
        <div class="card-sukk-stat" style="flex-direction:column;align-items:stretch;gap:var(--space-xs);padding:var(--space-lg);">
            <div class="bc-currency-label">
                <x-heroicon name="attach_money" />
                دولار أمريكي
            </div>
            <div class="bc-value bc-value-usd" x-show="!masked" x-transition.opacity.duration.200ms>
                &lrm;${{ number_format($st['total_balance_usd'] ?? 0, 2) }}
            </div>
            <div class="bc-mask-placeholder" x-show="masked" x-transition.opacity.duration.200ms>
                ••••••••
            </div>
        </div>

        {{-- SYP Balance --}}
        <div class="card-sukk-stat" style="flex-direction:column;align-items:stretch;gap:var(--space-xs);padding:var(--space-lg);">
            <div class="bc-currency-label">
                <x-heroicon name="currency_exchange" />
                ليرة سورية
            </div>
            <div class="bc-value bc-value-syp" x-show="!masked" x-transition.opacity.duration.200ms>
                {!! \App\Support\Money::format($st['total_balance_syp'] ?? 0, 'SYP') !!}
            </div>
            <div class="bc-mask-placeholder" x-show="masked" x-transition.opacity.duration.200ms>
                ••••••••
            </div>
        </div>
    </div>

    {{-- Micro-stats row --}}
    <div class="bc-micro" style="margin-top:var(--space-md);padding-top:var(--space-md);border-top:1px solid var(--border-light);">
        <div class="bc-micro-item">
            <div class="bc-micro-value">&lrm;${{ number_format($st['revenue_today'] ?? 0, 2) }}</div>
            <div class="bc-micro-label">إيراد اليوم</div>
        </div>
        <div class="bc-micro-item">
            <div class="bc-micro-value">{{ number_format($st['active_cards'] ?? 0) }}</div>
            <div class="bc-micro-label">بطاقات فعالة</div>
        </div>
        <div class="bc-micro-item">
            <div class="bc-micro-value">{{ number_format($st['total_transactions'] ?? 0) }}</div>
            <div class="bc-micro-label">إجمالي المعاملات</div>
        </div>
        <div class="bc-micro-item">
            <div class="bc-micro-value">{{ number_format($st['total_users'] ?? 0) }}</div>
            <div class="bc-micro-label">إجمالي المستخدمين</div>
        </div>
    </div>
</div>
