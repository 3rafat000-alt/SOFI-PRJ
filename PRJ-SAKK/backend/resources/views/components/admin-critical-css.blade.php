{{-- Inlines the admin design-system CSS (tokens+base+utilities) directly into
     <head> for pages that must paint fast pre-auth (login) — removes 3
     render-blocking round-trips. Only use on unauthenticated/first-paint
     pages; authenticated admin pages keep the external <link> files so the
     browser cache pays off across the session instead of re-sending this
     payload inline on every request. Single source of truth stays
     public/css/admin/*.css; read at render time so there is never a second
     copy to drift.
     TRIED-AND-REVERTED (Gate 5, 2026-07-01): a preload+onload-swap variant
     that kept base.css/utilities.css external (to cut document weight for
     lantern's simulated-TTI model) was measured and REJECTED — it produces
     a real FOUC on throttled connections (devtools-throttled CLS jumped
     from 0 to 0.789, login-page classes live in base.css so the form
     renders unstyled until the swap fires). Full-inline stays the only
     variant that keeps CLS=0; the ~245KB inline payload is the accepted
     tradeoff. See STATE.md / this component's git history for the
     measurement. --}}
<style>{!! file_get_contents(public_path('css/admin/tokens.css')) !!}</style>
<style>{!! file_get_contents(public_path('css/admin/base.css')) !!}</style>
<style>{!! file_get_contents(public_path('css/admin/utilities.css')) !!}</style>
