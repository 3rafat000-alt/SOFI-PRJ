# Redesign Report — PRJ-SAKK — /admin/gold dashboard — 2026-07-01

## 1. Executive summary
Redesigned the `/admin/gold` admin dashboard (prices · auto-sync · transactions) in two passes: a **structure/operations** pass then an **identity/taste** pass. Closed the last operational gaps left by the prior money-bug audits — a promised-but-missing add-karat control and an orphaned transactions view — and rebuilt the visual layer to premium Light-Minimal (burgundy-primary), demoting gold from a page-wide gild to a single restrained accent. **No money/validation/route logic in `update()`/`toggleActive()`/`autoSettings()`/`refresh()` was touched** (already audited clean, 14/14 gold tests). All Blade compiles; routes verified. **Verdict: page redesigned, gaps closed, functionally unchanged and safe.**

### الملخص التنفيذي (AR)
أُعيد تصميم صفحة `/admin/gold` على مرحلتين: بنية/عمليات ثم هوية/ذوق. أُغلقت الفجوتان المتبقّيتان من تدقيقات المال السابقة — زر «إضافة عيار» الموعود المفقود، وصفحة المعاملات المعزولة (كان مسارها يعيد التوجيه للرئيسية فيعطّل الفلترة والترقيم). أُعيد بناء الطبقة البصرية إلى نمط Light-Minimal بلون العنّابي الأساسي، مع تقليص الذهب من لون يعمّ الصفحة إلى لمسة واحدة مقيّدة. لم يُمَس أي منطق مالي/تحقّق/مسار (مُدقّق سابقاً، 14/14 اختبار ناجح). كل قوالب Blade تُصرَّف بنجاح والمسارات مؤكّدة.

## 2. Scope & method
- **Target:** `https://sakk.zanjour.com/admin/gold` — `GoldPriceController`, `routes/web.php` gold group, `admin/gold/{index,transactions,_styles}.blade.php`, Light-Minimal tokens in `layouts/admin.blade.php`.
- **Method:** two delegated Blade-architect passes (CEO no-write doctrine — orchestrated, did not author). Pass 1 = structure + operations; Pass 2 = identity/taste refinement. Design law: Light-Minimal, zero-raw-hex (tokens only), WCAG 2.2 AA, RTL currency-gate. Verify: `php -l`, `php artisan view:cache`, `route:list --name=gold`.
- **Out of scope (deliberately):** all money/validation logic and controller mutation paths — frozen per prior audits (`audit-gold-2026-07-01.md`, `audit-admin-gold-page-2026-07-01.md`).

## 3. Findings (gaps closed by this redesign)
| SEV | file:line | gap | fix | status |
|---|---|---|---|---|
| 🟠 | routes/web.php:227 | `admin.gold.transactions` redirected to index → orphaned working `transactions()` (filter type/status/search + paginate unreachable) | route repointed to `GoldPriceController@transactions`; index footer link «عرض كل المعاملات» | ✅ 6de828e |
| 🟠 | index.blade.php empty-state | empty-state promised "add a karat" but no add control existed | `GoldPriceController::store()` (unique karat 1-24 · buy≥sell · audit log) + route `POST admin.gold.price.store` + inline Alpine add-karat panel | ✅ 6de828e |
| 🟡 | index.blade.php KPI + card meta | «الهامش» (margin) mislabeled the spread=(buy−sell)/sell×100 value; collided with the real auto-sync margin | KPI/card → «الفارق» (spread); auto-sync's true margin relabeled «هامش المنصة» | ✅ 6de828e |
| 🟡 | index.blade.php toggles | active-toggle was a bare `<span>` inside button — no semantics/keyboard | `<button role="switch" aria-checked aria-label>` + `focus-visible` rings; checkbox switch aria-synced; `<label for>` on inputs | ✅ 6de828e |
| 🟡 | _styles.blade.php | over-gilded identity: gold gradients on hero/icon/card-bar, sell price in gold, section-head + KPI icons gold — gold read as the brand instead of burgundy | gold reduced to single accent (medallion + `.source-pill.auto` + spot-price number + fees KPI); hero/sell/section-heads/other KPI icons → burgundy/neutral tokens; sell price → `--text-primary` | ✅ c0f356d |
| 🟡 | index/transactions cards | flat hierarchy, asymmetric padding, heavy `--shadow-lg` hover | `karat-head` separator, medallion 44→42px hairline (no radial), normalized padding, `--shadow-sm`→`--shadow-md` hover, tinted `karat-edit` footer | ✅ c0f356d |

**Preserved (no change):** every form action, CSRF, `admin.gold.*` route, buy≥sell + unique-karat validation, toggle→`price.toggle` money-safety separation, `space-y-6` rhythm + responsive grids (1→2→4 col), empty/loading states, `karat_label` model accessor (renders arbitrary new karats via default match arm).

## 4. Remediation
- **`6de828e`** — `feat(admin): redesign gold dashboard, add-karat op, restore transactions view` — 3 files (+337/−491; net −154, dropped inline `<style>` onto shared `_styles`). Adds `store()`, restores transactions route, label fix, a11y toggles.
- **`c0f356d`** — `style(admin/gold): restrain gold to a single accent, redesign karat cards` — 3 files. Gold-restraint, card redesign, hero/layout refinement.
- **Deferred:** none of the scoped redesign items. Gold hex values remain only in the scoped `:root .gold-page` var block (intentional accent tokens, not raw-hex violations in markup).

## 5. Risk posture / gate
- **Functional risk: none introduced.** Controllers' money/validation/mutation paths untouched; only `store()` (add-karat, mirrors `update()` validation + audit) is new logic and is additive. Prior money audits remain valid (Gate 5 Tier-A money-safety already RATIFIED, `c186def`; project now at Gate 6 per ADR-005).
- **Proof:** `php -l` clean on controller + routes; `php artisan view:cache` compiles clean; `route:list --name=gold` = 7 routes (add-karat new, transactions restored). Working tree clean, `head_sha=c0f356d`.
- **Not re-run this pass:** automated regression on `store()` — recommended below.

## 6. Next actions
1. **Regression test `store()`** — unique-karat rejection, buy<sell rejection, audit-log write → `sofi-automated-testing-engineer` (extends existing `GoldPriceControllerTest`, Gate-5 money coverage).
2. ⚠ **Run host migration** — carried from prior audit: `php artisan migrate` on host enforces `unique('karat')` (`2026_07_01_150000_add_unique_karat_to_gold_prices`); until then the add-karat/refresh dup-race stays open on live DB → `sofi-devops-cloud-lead`.
3. **Visual QA on device** — confirm reduced-gold identity + RTL cards render as intended on `sakk.zanjour.com/admin/gold` (Playwright shot or manual) → `sofi-manual-exploratory-tester`.

---
Commits: `6de828e`, `c0f356d` (head). Skills: /sofi-report. Memory: [[sakk-gold-toggle-detached-autosync]], [[web-design-premium-bilingual]], [[carda-admin-light-minimal-redesign]].
