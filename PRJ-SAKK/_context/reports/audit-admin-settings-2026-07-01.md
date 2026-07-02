# Audit Report — PRJ-SAKK — 2026-07-01
## Feature: `/admin/settings` (full operations)

## 1. Executive summary
Spec-review (4-pillar architect sweep, read-only) of the admin System-Settings page and its
operations: `SettingsController` (index · updateSetting · clearCache · optimizeCache) +
`index.blade.php` (1217 lines, Alpine). Seven defects found — **3🔴 2🟠 2🟡**. Top risk was
**two dead control toggles** (`maintenance_mode`, `registration_open`) that rendered in the UI
but were read by no code, giving the operator false confidence the site was closed/under
maintenance while it stayed fully live. A financial-limits page also wrote **zero audit trail**
while displaying an audit-log tab. All seven fixed and committed; one minor index deferred.
Verdict: **Admin & Ops pillar was broken → now sound (static-clean).** Live runtime verify pending.

## الملخص التنفيذي
مراجعة معمارية لصفحة إعدادات النظام (`/admin/settings`) وعملياتها. سبع عيوب — ٣ حرجة، ٢ متوسطة،
٢ طفيفة. أخطرها: **مفتاحا التحكم الميتان** (وضع الصيانة + فتح التسجيل) — يظهران بالواجهة لكن لا
يقرأهما أي كود، فيظن الأدمن النظام مغلقاً وهو حيّ. كذلك طفرات مالية بلا أي سجل تدقيق رغم وجود تبويب
«سجل النشاطات». عولجت السبعة كلها والتُزمت (commits). يتبقّى تحقق تشغيلي حيّ.

## 2. Scope & method
- **Targets:** `SettingsController.php`, `SystemSetting.php`, `AuthController.php`,
  `AdminController.php`, `resources/views/admin/settings/index.blade.php`.
- **Method:** `/sofi-spec-review` — Python `feature_scan.py` (token-frugal locate + pre-flags),
  then per-pillar semantic confirmation via targeted grep/read. Zero writes during review.
- **Fix:** `/sofi-fix` — routed to `sofi-laravel-core-dev` (backend ×5) + `sofi-blade-architect`
  (UI ×2), each checkpointed separately.

## 3. Findings

| SEV | file:line | defect | proof | fix | status |
|---|---|---|---|---|---|
| 🔴 | SettingsController.php:23 | `maintenance_mode` toggle dead | `grep SystemSetting::get('maintenance_mode')` = 0 hits; app only reads Laravel `storage/framework/down` (AdminController.php:599) | new `CheckMaintenanceMode` middleware reads DB toggle, 503 non-admin, admin routes always bypass | ✅ `187bde7` |
| 🔴 | SettingsController.php:24 | `registration_open` toggle dead | registration gated by `config('app.allow_registration')` only (AdminController.php:600); setting read nowhere | `AuthController::register` guards → 403 Arabic when false | ✅ `d68ca75` |
| 🔴 | SettingsController.php:371-435 | zero audit-log on any mutation (limits, cache, maintenance) while page shows audit tab | no `AuditLog`/`AuditLogService` write in updateSetting/clearCache/optimizeCache | reused existing `AuditLogService`: logs `settings.update` (before→after), `cache.clear`, `cache.optimize` + actor/ip | ✅ `d8f8fec` |
| 🟠 | SettingsController.php:387-393 | no cross-field validation — min > max locks users out | each decimal saved solo with only `min:0` | `MIN_MAX_PAIRS` counterpart check → 422 on invert | ✅ `d8f8fec` |
| 🟠 | SettingsController.php:36 | no upper cap — `withdrawal_fee_percent` accepts >100, bonuses unbounded | WRITABLE decimal rule was `min:0` only | `DECIMAL_MAX` map, dynamic `max:{n}` per key (percent ≤100) | ✅ `d8f8fec` |
| 🟡 | index.blade.php:1137 | `handleSubmit` disables submit button with no recovery on failed/hung POST | button stuck `.loading` forever | 15s auto-recovery timeout | ✅ `f4aa6b2` |
| 🟡 | index.blade.php:385 | maintenance toggle destructive, one-click, no confirm | `@change="save(...)"` direct | `confirmMaintenance()` Arabic prompt on ON, reverts on cancel | ✅ `f4aa6b2` |

**Pre-flags cleared as false:** N+1 on `SystemSetting::get` (per-key cached 3600s), raw-query
injection at :141/:349 (bound params). Noted, not fixed.

## 4. Remediation
- **Fixed + committed:** all 7 (commits above). `php -l` clean ×N, `view:cache` compiles.
  No `$guarded`/mass-assignment change; SYP true-scale magnitudes untouched.
- **Deferred:** audit-filter index — `AuditLog::latest()->take(50)` + `LIKE` on
  `action`/`model_type`/`ip_address` (SettingsController.php:98) is a table-scan; needs a
  covering index. Minor at current row counts. Owner: `sofi-sql-dba-expert` when audit volume grows.

## 5. Risk posture / gate
- Admin & Ops pillar: **broken → sound (static)**. Data/Logic + UI/UX: sound. Edge-cases: hardened.
- **Not runtime-verified.** Gate advance blocked until live check: toggle maintenance ON →
  public app returns 503 while `/admin/*` reachable → toggle OFF. Then `/sofi-gate`.

## 6. Next actions
1. Live verify maintenance + registration enforcement (owner: DevOps / manual tester). **← blocks gate**
2. Regression test: min>max rejection + fee cap 422 (`sofi-automated-testing-engineer`).
3. Audit-filter covering index (`sofi-sql-dba-expert`) — when volume warrants.
4. `/sofi-handoff` — record head_sha `d8f8fec` + ticket 1 above.

---
*Method: `/sofi-spec-review` → `/sofi-fix`. head_sha `d8f8fec` (branch `master`,
repo `~/Desktop/projects/PRJ-SAKK`).*
