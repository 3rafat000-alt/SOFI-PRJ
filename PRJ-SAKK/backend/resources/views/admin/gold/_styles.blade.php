{{-- Gold section · scoped premium styling. Layered on top of the Light-Minimal
     design system; burgundy stays the single action/identity color, gold is
     reduced to a single restrained accent (hairline + medallion + source pill).
     All tokens reference layout :root vars. --}}
<style>
    .gold-page {
        --gold: #C08A1E;
        --gold-deep: #8A610F;
        --gold-bright: #E7C66B;
        --gold-soft: rgba(192, 138, 30, 0.10);
        --gold-line: rgba(192, 138, 30, 0.28);
    }

    /* ===== Hero header ===== */
    .gold-hero {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1.25rem;
        flex-wrap: wrap;
        padding: 1.4rem 1.6rem;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
    }
    .gold-hero::before {
        content: '';
        position: absolute;
        inset: 0 0 auto 0;
        height: 3px;
        background: var(--primary);
    }
    .gold-hero-main { display: flex; align-items: center; gap: 0.9rem; }
    .gold-hero-icon {
        width: 46px; height: 46px;
        border-radius: var(--radius-lg);
        display: grid; place-items: center;
        background: var(--primary-light, rgba(0,0,0,0.04));
        color: var(--primary);
        box-shadow: inset 0 0 0 1px var(--border);
        flex: none;
    }
    .gold-hero-title { font-size: 1.45rem; font-weight: 800; color: var(--text-primary); line-height: 1.15; }
    .gold-hero-sub { font-size: 0.82rem; color: var(--text-secondary); margin-top: 0.15rem; }
    .gold-hero-side { display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap; }
    .gold-stamp {
        display: inline-flex; align-items: center; gap: 0.35rem;
        font-size: 0.74rem; font-weight: 700; color: var(--text-secondary);
        background: var(--surface-hover);
        padding: 0.4rem 0.7rem;
        border-radius: var(--radius-full);
        border: 1px solid var(--border);
    }
    .gold-stamp svg[data-slot="icon"] { font-size: 0.95rem; }

    /* ===== KPI tiles ===== */
    .gold-kpis { display: grid; gap: 0.85rem; grid-template-columns: repeat(2, 1fr); }
    @media (min-width: 768px) { .gold-kpis { grid-template-columns: repeat(4, 1fr); } }
    @media (min-width: 1280px) { .gold-kpis.is-six { grid-template-columns: repeat(6, 1fr); } }

    .gold-kpi {
        position: relative;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 1rem 1.05rem;
        display: flex; align-items: flex-start; justify-content: space-between; gap: 0.6rem;
        transition: box-shadow var(--transition-fast), transform var(--transition-fast), border-color var(--transition-fast);
    }
    .gold-kpi:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); border-color: var(--border-strong); }
    .gold-kpi-label { font-size: 0.72rem; font-weight: 700; color: var(--text-muted); }
    .gold-kpi-value { font-size: 1.4rem; font-weight: 800; color: var(--text-primary); line-height: 1.1; margin-top: 0.3rem; }
    .gold-kpi-sub { font-size: 0.68rem; font-weight: 600; color: var(--text-muted); margin-top: 0.25rem; }
    .gold-kpi-icon { width: 36px; height: 36px; border-radius: var(--radius-md); display: grid; place-items: center; flex: none; }
    .gold-kpi-icon svg[data-slot="icon"] { font-size: 1.15rem; }

    /* ===== Section heading ===== */
    .gold-section-head { display: flex; align-items: center; gap: 0.55rem; margin: 0.25rem 0; }
    .gold-section-head svg[data-slot="icon"] { color: var(--text-secondary); font-size: 1.25rem; }
    .gold-section-head h2 { font-size: 1.05rem; font-weight: 800; color: var(--text-primary); }
    .gold-section-head .count { font-size: 0.75rem; font-weight: 700; color: var(--text-muted); }

    /* ===== Karat card ===== */
    .karat-grid { display: grid; gap: 1rem; grid-template-columns: repeat(1, 1fr); }
    @media (min-width: 640px) { .karat-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 1280px) { .karat-grid { grid-template-columns: repeat(4, 1fr); } }

    .karat-card {
        position: relative;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-xl);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
        transition: box-shadow var(--transition-fast), transform var(--transition-fast), border-color var(--transition-fast);
    }
    .karat-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); border-color: var(--border-strong); }
    .karat-card.is-off { opacity: 0.68; }

    .karat-head { display: flex; align-items: center; justify-content: space-between; padding: 1.05rem 1.1rem 0.85rem; border-bottom: 1px solid var(--border-light); }
    .karat-id { display: flex; align-items: center; gap: 0.65rem; }
    .karat-medallion {
        width: 42px; height: 42px;
        border-radius: var(--radius-full);
        display: grid; place-items: center;
        font-weight: 800; font-size: 0.92rem;
        background: var(--gold-soft);
        color: var(--gold-deep);
        border: 1px solid var(--gold-line);
        flex: none;
    }
    .karat-name { font-size: 1rem; font-weight: 800; color: var(--text-primary); }
    .karat-purity-label { font-size: 0.68rem; font-weight: 600; color: var(--text-muted); }

    .karat-prices { display: grid; grid-template-columns: 1fr 1fr; gap: 0.55rem; padding: 0.9rem 1.1rem; }
    .karat-price { text-align: center; padding: 0.65rem 0.4rem; border-radius: var(--radius-md); background: var(--surface-hover); }
    .karat-price-label { font-size: 0.66rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.3rem; display: flex; align-items: center; justify-content: center; gap: 0.2rem; }
    .karat-price-label svg[data-slot="icon"] { font-size: 0.85rem; }
    .karat-price-value { font-size: 1.3rem; font-weight: 800; line-height: 1; }
    .karat-price.buy .karat-price-value { color: var(--success); }
    .karat-price.sell .karat-price-value { color: var(--text-primary); }

    .karat-meta { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; padding: 0 1.1rem 0.9rem; }
    .spread-chip {
        display: inline-flex; align-items: center; gap: 0.25rem;
        font-size: 0.72rem; font-weight: 700;
        padding: 0.22rem 0.6rem; border-radius: var(--radius-full);
        background: var(--surface-hover); color: var(--text-secondary);
        border: 1px solid var(--border-light);
    }
    .spread-chip svg[data-slot="icon"] { font-size: 0.85rem; }

    .source-pill {
        display: inline-flex; align-items: center; font-size: 0.72rem; font-weight: 700;
        padding: 0.22rem 0.6rem; border-radius: var(--radius-full);
    }
    .source-pill.auto { color: var(--gold-deep); background: var(--gold-soft); border: 1px solid var(--gold-line); }
    .source-pill.manual { color: var(--text-secondary); background: var(--surface-hover); border: 1px solid var(--border-light); }

    .purity-track { flex: 1; margin-inline-start: 0.75rem; height: 6px; border-radius: var(--radius-full); background: var(--surface-active); overflow: hidden; }
    .purity-track > span { display: block; height: 100%; border-radius: inherit; background: var(--primary); }

    .karat-edit { padding: 0.9rem 1.1rem 1.05rem; border-top: 1px dashed var(--border); background: var(--surface-hover); }

    /* native-ish toggle */
    .gold-switch { position: relative; display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; user-select: none; }
    .gold-switch input { position: absolute; opacity: 0; width: 0; height: 0; }
    .gold-switch .track { width: 38px; height: 21px; border-radius: var(--radius-full); background: var(--surface-active); transition: background var(--transition-fast); flex: none; position: relative; }
    .gold-switch .track::after { content: ''; position: absolute; top: 3px; inset-inline-start: 3px; width: 15px; height: 15px; border-radius: 50%; background: #fff; box-shadow: var(--shadow-sm); transition: transform var(--transition-fast); }
    .gold-switch input:checked + .track { background: var(--success); }
    .gold-switch input:checked + .track::after { transform: translateX(-17px); }
    [dir="ltr"] .gold-switch input:checked + .track::after { transform: translateX(17px); }
    .gold-switch input:focus-visible + .track { box-shadow: 0 0 0 3px var(--primary-ring); }

    /* ===== Transactions extras ===== */
    .tx-type { display: inline-flex; align-items: center; gap: 0.3rem; font-weight: 700; font-size: 0.76rem; padding: 0.24rem 0.6rem; border-radius: var(--radius-full); }
    .tx-type svg[data-slot="icon"] { font-size: 0.9rem; }
    .tx-type.buy { background: var(--success-light); color: #15803d; }
    .tx-type.sell { background: var(--danger-light); color: #b91c1c; }

    .tx-avatar { width: 32px; height: 32px; border-radius: var(--radius-full); display: grid; place-items: center; font-size: 0.72rem; font-weight: 800; color: var(--primary); background: var(--primary-light, var(--surface-hover)); border: 1px solid var(--border-light); flex: none; }
    .karat-tag { display: inline-flex; align-items: center; font-size: 0.72rem; font-weight: 700; color: var(--gold-deep); background: var(--gold-soft); padding: 0.12rem 0.5rem; border-radius: var(--radius-sm); }

    .filter-chips { display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap; margin-top: 0.75rem; }
    .filter-chip { display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.72rem; font-weight: 700; color: var(--text-secondary); background: var(--surface-hover); border: 1px solid var(--border); padding: 0.25rem 0.6rem; border-radius: var(--radius-full); }
    .filter-chip svg[data-slot="icon"] { font-size: 0.8rem; }
</style>
