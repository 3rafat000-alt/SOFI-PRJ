{{--
    SAKK · صک — Dashboard v4 (Radical Redesign · Jun 2026)
    ──────────────────────────────────────────────────────
    2-Column Layout:
    Main: Welcome → KPI+Charts → Quick Actions → Transactions Table
    Side: System Stats → KYC Queue → Support Tickets
    ──────────────────────────────────────────────────────
    Glass-morphism · CSS sparklines · Chart.js · Alpine.js
    Included by admin/dashboard.blade.php (@section('content'))
--}}
<div class="dash4-space-y">

    {{-- ═══ WELCOME BANNER ═══ --}}
    @include('admin.dashboard.partials.welcome_card')

    {{-- ═══ 2-COLUMN GRID ═══ --}}
    <div class="dash4-grid">

        {{-- ── MAIN CONTENT ── --}}
        <div class="dash4-main dash4-space-y">

            {{-- KPI Grid + Charts --}}
            @include('admin.dashboard.partials.kpi_cards')

            {{-- Quick Actions Bar --}}
            @include('admin.dashboard.partials.quick_actions')

            {{-- Transactions Table --}}
            @include('admin.dashboard.partials.recent_ledger')

        </div>

        {{-- ── SIDEBAR ── --}}
        <div class="dash4-side">
            @include('admin.dashboard.partials.aside_widgets')
        </div>

    </div>

</div>
