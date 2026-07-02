{{-- ═══════════════════════════════════════════════════════════════
     E · QUICK ACTIONS GRID (إجراءات سريعة)
     Borderless 3×2 tile grid with Material Icons + labels.
     ═══════════════════════════════════════════════════════════════ --}}
<div class="card-sukk-main">
    <div class="card-header">
        <div class="card-title" style="font-size:.82rem;">
            <x-heroicon name="bolt" style="color:var(--sukk-primary);font-size:1.1rem;" />
            <span>إجراءات سريعة</span>
        </div>
    </div>
    <div class="card-body">
        <div class="qa-grid">
            @foreach([
                ['التحقق','verified_user',route('admin.users',['kyc_status'=>'submitted'])],
                ['الرسوم','percent',route('admin.fees.index')],
                ['الوكلاء','storefront',route('admin.agents.index')],
                ['التجار','store',route('admin.merchants.index')],
                ['الذهب','monetization_on',route('admin.gold.prices')],
                ['الإعدادات','settings',route('admin.settings')],
            ] as [$l,$ic,$h])
            <a href="{{ $h }}" class="qa-tile">
                <x-heroicon :name="$ic" />
                {{ $l }}
            </a>
            @endforeach
        </div>
    </div>
</div>
