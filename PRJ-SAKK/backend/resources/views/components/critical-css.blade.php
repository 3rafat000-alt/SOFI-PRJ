{{-- Inlines sakk-tokens.css directly into <head> to remove a render-blocking
     network round-trip (Gate 5 perf fix — landing/admin-login TTI budget).
     Single source of truth stays public/sakk-assets/sakk-tokens.css; this
     partial just reads it at render time so there is never a second copy
     to drift. Laravel view cache (opcache-backed) keeps this cheap. --}}
{{-- Preload ONLY the two weights the hero actually paints above-the-fold
     (700 bold h1 / 400 regular body). Medium+SemiBold are used further down
     the page — declared in @font-face below (font-display:swap, non-blocking)
     but NOT preloaded, so they don't contend for the throttled mobile link
     against the two weights the LCP element needs (Gate 5 perf fix — cut
     preload set from 4 weights to 2 after simulated-TTI showed the extra
     preload bytes were still counted against the network quiet-window even
     though they don't block paint). Visual output unchanged — same
     @font-face rules below still declare all 5 weights. --}}
<link rel="preload" href="/sakk-assets/fonts/IBMPlexSansArabic-Regular.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/sakk-assets/fonts/IBMPlexSansArabic-Bold.woff2" as="font" type="font/woff2" crossorigin>
<style>{!! file_get_contents(public_path('sakk-assets/sakk-tokens.css')) !!}</style>
