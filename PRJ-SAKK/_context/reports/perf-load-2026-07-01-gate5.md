# Perf & Load Audit — Gate 5 — PRJ-SAKK — 2026-07-01

## Scope measured
- App: http://sakk.local (port 80, NOT 8001 as STATE.md states — STATE.md stale on port, corrected in this report)
- Pages: GET / (landing), GET /admin/login (admin dashboard NOT reachable — requires authenticated session, no credentials provided to this agent; out of in-bounds scope to create/fetch admin session tokens)
- Tool: Lighthouse (npx lighthouse), performance category only, mobile-simulated-throttle (default CWV profile: 150ms RTT, 1.6Mbps, 4x CPU slowdown) + desktop unthrottled control run

## Results — landing (/)
Mobile-throttled (real-world CWV budget):
- LCP: 2.55s | FCP: 2.55s | TTI: 2.55s | CLS: 0.0002 | TBT: 0ms
- Perf score: 0.93

Desktop unthrottled (server/app baseline):
- LCP: 0.5s | TTI: 0.5s | Perf score: 1.0

**Verdict: FAIL vs TTI<2s budget on mobile-simulated conditions.** PASS on desktop/local conditions.

## Results — admin/login
Mobile-throttled:
- LCP: 3.0s | FCP: 2.7s | TTI: 3.0s | CLS: 0 | TBT: 0ms
- Perf score: 0.90

**Verdict: FAIL vs TTI<2s budget on mobile-simulated conditions.**

## Admin dashboard (authenticated page)
NOT MEASURED — requires authenticated admin session; this agent has no credentials and creating/injecting an auth session is out-of-bounds for a perf audit. BLOCKER for CEO/qa-sre-lead: provide a session cookie or test-admin credentials to complete dashboard measurement.

## Root cause of TTI breach
Server-side latency is NOT the cause — server-response-time is 20ms on both pages, main-thread JS work is 0.2-0.5s, network-rtt/server-latency measured 0ms locally. The breach is 100% attributable to the mobile-network throttle profile (150ms RTT, 1.6Mbps) applied to render-blocking asset chain:
- Both pages load 1 external stylesheet (sakk-tokens.css) + 4 self-hosted woff2 font files (IBM Plex Sans Arabic Regular/Bold/SemiBold/Medium, ~299KB total) serially before first text paint.
- Under 150ms simulated RTT, each additional blocking round-trip (HTML -> CSS -> font discovery) compounds toward the 2.5-3s figure; under desktop/no-throttle the same asset set resolves in 0.5s.
- This is a genuine mobile-CWV finding (not a local-environment artifact): a low-bandwidth/high-latency mobile user would experience this delay for real.

Suspected fixable causes (diagnosis only, not my fix):
1. 4 font weights loaded on every page even where only 1-2 weights are used above the fold (font-display + reduce weight variants).
2. No `font-display: swap` / preload hints observed — fonts appear to block text paint rather than allow fallback-then-swap.
3. External CSS stylesheet request is a separate round trip from the document — could be inlined (critical CSS) or preloaded.

## Escalation
Any actual fix here (font subsetting/preload, critical-CSS inlining) is front-end implementation, not this agent's lane. Recommend routing to the owning front-end/tech lead per my out-of-bounds rule; no data-layer or Flutter component involved so no sql-dba-expert / native-performance-optimizer escalation needed.

## Gate-5 bar verdict
TTI<2s: **FAIL** on both measured pages (mobile CWV budget) — landing 2.55s, admin/login 3.0s.
CLS: PASS both (0.0002, 0). TBT: PASS both (0ms).
Admin dashboard: UNMEASURED — blocker, needs credentials.
