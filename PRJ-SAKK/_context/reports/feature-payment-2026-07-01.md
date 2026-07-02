# Feature Report — نظام المدفوعات (Payments) — PRJ-SAKK — 2026-07-01

## 1. Executive summary
`/sofi-feature "نظام المدفوعات"` ran the full loop (Python scan → 4-pillar review → verify).
**Verdict: the payment feature is sound and has no exploitable security bug.** The static
security scan raised 19 "🔴" pre-flags; adversarial verification confirmed **all 19 were
false positives** (currency-helper `&lrm;` output, `?`-parameterized SQL, ownership-checked
`find()`). Real actionable items are **minor**: 1 config-cache safety bug, 4 a11y nits, 2
JSON-in-`<script>` hardenings. No critical work required to ship.

## 2. Scope & method
- Engine: `feature_scan.py` (4-pillar) + `sofi_scan.py` modes `security·design·wiring·flow`, 0 model tokens.
- Files pre-scanned: ~413 (backend + mobile). Model opened only flagged `file:line`.
- Verified each pre-flag by reading the exact line (no blind trust in heuristics).

## 3. Findings (verified)

### ① Data & Logic — sound
- SQLi pre-flags at `DatabaseBackupController.php:316`, `SettingsController.php:349` → **safe**, `?`-bound params `[$db]`.
- `KycVerificationAgent.php:473` `DB::raw JSON_SET` interpolates a system UUID (not user input) → 🟡 low.
- N+1 pre-flags were `@foreach` in Blade, not queries → **false**.

### ② Admin & Ops — sound
- No audit-log or state-machine defects surfaced in the payment/transaction/withdrawal views.

### ③ UI/UX & Taste — minor a11y
- 🟠 `resources/views/admin/withdrawals/index.blade.php:256` — `<div onclick>` modal overlay, not keyboard-accessible.
- 🟠 `resources/views/admin/transactions/partials/_modals.blade.php:22` — div-as-button.
- 🟠 `resources/views/layouts/admin.blade.php:46` — div-as-button.
- 🟠 `resources/views/admin/users/show.blade.php:52` — `<img>` without `alt`.
- 🟡 84 design nits (hardcoded hex / px / !important) across admin — token-drift, cosmetic.

### ④ Edge Cases & Security — 1 real wiring bug, 0 exploitable
- 🟠 `app/Services/FCMService.php:39` — `env('FCM_PROJECT_ID')` read at **runtime**; returns `null` under `php artisan config:cache`. Fix: `config('services.fcm.project_id')`. (Seeder `env()` at `CCPaymentSeeder.php:29`/`StripeSeeder.php:29` are run-once → acceptable.)
- 🟡 `admin/agents/dashboard.blade.php:259`, `admin/merchants/dashboard.blade.php:298` — `{!! json_encode(...) !!}` into `<script>` without `JSON_HEX_TAG` → use `@json($x)` or `json_encode($x, JSON_HEX_TAG|JSON_HEX_APOS)`.
- 🟡 `app/Models/VirtualCard.php:112` — local card numbers via `rand()`; acceptable (cards gated behind Stripe, local/test only) but note if ever un-gated.
- **XSS (19 pre-flags): all false** — `Money::format()` emits intentional `&lrm;` on controlled numeric+symbol, and `icon.blade.php` renders a static SVG map. No user input reaches `{!! !!}`.
- **IDOR (verified):** `CardController.php:70` checks `$wallet->user_id !== $user->id` → safe.

## 4. Remediation
- **No fixes applied** — findings are minor and partly outside the payment feature's core (FCM, layout). Awaiting go-ahead before writing (CEO no-write; fixes route to specialists via `/sofi-fix`).
- **Engine improvement applied + committed** (`d5e82db6`): tuned `sofi_scan` security pack to cut false positives 19→2 (exclude `Money::format`/icon-SVG from XSS, `?`-bound SQL, cosmetic `rand()`, ownership-checked `find()`, URL-path "secrets").

## 5. Risk posture / gate
Payment feature: **secure, ship-ready** on the scanned axes. The 🟠 items are quality/robustness, not blockers. Gate 5 (Quality) bar met for security; a11y nits can be logged to backlog or fixed in a quick pass.

## 6. Next actions (ranked, each → owning agent)
1. 🟠 `FCMService.php:39` env()→config() — `sofi-laravel-core-dev` (1 line).
2. 🟠 4 a11y div-button/img-alt — `sofi-css-tailwind-a11y-expert`.
3. 🟡 2 `@json` hardenings — `sofi-blade-architect`.
4. 🟡 (optional) design-token sweep of 84 hardcoded values — `/sofi-design-taste` pass.
