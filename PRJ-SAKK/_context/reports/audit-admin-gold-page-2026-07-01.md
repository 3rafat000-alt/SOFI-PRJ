# Audit Report — PRJ-SAKK — /admin/gold (admin dashboard) — 2026-07-01

Spec-review (4-pillar) + remediation of the **admin gold dashboard page** (prices + auto-sync + recent transactions). Distinct from the broader gold-feature financial audit (`audit-gold-2026-07-01.md`, mobile buy/sell). Read-only review → routed to specialists → fixed + committed.

## 1. Executive summary
The `/admin/gold` page (`GoldPriceController` + `index.blade.php`) carried two 🔴 money-correctness defects: (a) the card active-toggle reused the price-edit endpoint, silently pinning any toggled karat to `source='manual'` and detaching it from world-price auto-sync; (b) manual price edits had no `buy ≥ sell` guard, allowing a losing spread on every trade. Plus 🟠 gaps: zero audit trail on `refresh()`/`autoSettings()`, and `firstOrNew(['karat'])` racing duplicate rows with no unique constraint. All confirmed and fixed across 2 commits. **Verdict: findings closed; one host migration pending.**

## الملخص التنفيذي (AR)
صفحة `/admin/gold` كان بها عطبان حرجان: زر تفعيل العيار كان يمرّ بمسار تعديل السعر فيثبّت العيار على «يدوي» ويفصله صامتاً عن التحديث التلقائي من السوق العالمي؛ والتعديل اليدوي كان يقبل سعر شراء أقل من البيع (خسارة كل صفقة). أُضيف مسار `price.toggle` مخصّص، وحارس `buy ≥ sell`، وسجلّ تدقيق على التحديث/الإعدادات، وقيد فريد على العيار. أُصلح كله في عمليتي رفع. ⚠️ يبقى تشغيل الترحيل على الخادم.

## 2. Scope & method
- **Target:** `https://sakk.zanjour.com/admin/gold` — controller, routes, `GoldPriceService`, `GoldPrice` model, migration, Blade view + styles.
- **Method:** `/sofi-spec-review` 4-pillar (Data&Logic · Admin&Ops · UI/UX&Taste · Edge-cases); Python feature-scan pre-flag → manual file:line confirmation. Zero writes in review.
- **Remediation:** `/sofi-fix` → `sofi-laravel-core-dev` (backend) + `sofi-blade-architect` (view).

## 3. Findings
| SEV | file:line | defect | proof | fix | status |
|---|---|---|---|---|---|
| 🔴 | GoldPriceController.php:74 + index.blade.php:457 | Active-toggle reused `update()` → forced `source='manual'`, detaching karat from auto-sync | toggle form POSTed PUT w/ hidden buy/sell/is_active; `update()`:74 always sets source | new `toggleActive()` + route `admin.gold.price.toggle` (flips is_active only); view repointed | ✅ 31c69d5 / ec90e58 |
| 🔴 | GoldPriceController.php:63 | No `buy ≥ sell` guard → losing spread on manual edit | validation was `min:0.01` only | added `gte:sell_price` + AR error msg | ✅ 31c69d5 |
| 🟠 | GoldPriceController.php:95,112 | Zero audit trail on `autoSettings()`/`refresh()` (financial mutations) | only `update()` logged | `Log::info` actor·old→new·spot·margin on both | ✅ 31c69d5 |
| 🟠 | GoldPriceService.php:122 | `firstOrNew(['karat'])` races duplicate karat rows — no unique constraint | migration lacked `unique('karat')` | migration `2026_07_01_150000_add_unique_karat_to_gold_prices` (dedup + unique, reversible) | ✅ authored 31c69d5 ⚠️ not migrated |
| 🟡 | index.blade.php:457 | Karat deactivation had no confirm — one click hides a karat | — | `onclick` confirm only when active | ✅ ec90e58 |
| 🟡 | index.blade.php:448 | Prices grid had no empty-state | `@foreach` only | `@forelse`/`@empty` inbox card | ✅ ec90e58 |
| 🟡 | index.blade.php:437 | Refresh button (15s sync fetch) double-click risk | no loading guard | submit-disable + spinner on refresh + save forms | ✅ ec90e58 |
| 🟡 | GoldPrice.php:23 | `spread` column labeled «الهامش» (margin) — two values, one name | spread=(buy−sell)/sell×100 vs service margin=one side | naming unification | ⏸ deferred (design pass) |

## 4. Remediation
- **`31c69d5`** — backend: `toggleActive()` + `price.toggle` route, `buy≥sell` validation, audit logs on autoSettings/refresh, unique-karat migration. `php -l` clean, `route:list --name=gold` = 7 routes.
- **`ec90e58`** — view: toggle repoint, deactivate-confirm, grid empty-state, refresh/save loading guard. `view:cache` compiled clean.
- **Deferred:** spread-vs-margin naming (🟡 taste, no functional impact) — design decision, not a code fix.

## 5. Risk posture / gate
Money-correctness 🔴×2 closed; silent-detach and losing-spread paths eliminated. **Blocking residual:** the unique-karat migration is authored but NOT run — duplicate-karat race stays open on the live DB until `php artisan migrate` runs on the host. Not gate-clearing until migrated.

## 6. Next actions
1. **Run migration on host** — `cd backend && php artisan migrate` → `sofi-devops-cloud-lead`. Enforces `unique('karat')`.
2. **Regression test** — `toggleActive()` leaves source untouched + `buy<sell` rejected → `sofi-automated-testing-engineer` (Gate 5 Tier A money coverage).
3. **Naming unification** (spread vs margin) — `sofi-blade-architect` / `sofi-uiux-lead`, low priority.

---
Commits in PRJ-SAKK repo: 31c69d5, ec90e58 (head). Skills: /sofi-spec-review · /sofi-fix. Memory: [[sakk-gold-toggle-detached-autosync]].
