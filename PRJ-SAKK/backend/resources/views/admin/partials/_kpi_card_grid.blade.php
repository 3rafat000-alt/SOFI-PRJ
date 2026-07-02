{{--
    SAKK · صک — Shared KPI Card Grid
    ─────────────────────────────────
    Renders an Alpine x-for loop over `kpiCards` with sparklines.
    Expects: Alpine `kpis` data, `kpiCards` computed, `x-data` on parent.

    Usage:
        <div x-data="companiesPage()">
            @include('admin.partials._kpi_card_grid', ['ns' => 'shared-kpi'])
        </div>
--}}
<div class="shared-kpi-grid" x-show="kpis" x-cloak>
    <template x-for="(card, idx) in kpiCards" :key="idx">
        <div class="dash4-kpi-card">
            <div class="dash4-kpi-head">
                <span class="dash4-kpi-badge" :style="'background:'+card.bg+';color:'+card.color">
                    <div x-html="card.iconSvg" style="display:flex;"></div>
                </span>
            </div>
            <span class="dash4-kpi-label" x-text="card.label"></span>
            <div class="dash4-kpi-row">
                <div class="dash4-kpi-main" x-html="card.value"></div>
            </div>
            <span class="dash4-kpi-change" :class="card.changeDir" x-show="card.changeText" x-text="card.changeText"></span>
            <div class="dash4-spark" x-html="card.sparkSvg"></div>
        </div>
    </template>
</div>
