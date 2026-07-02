# BLADE AUDIT — SAKK Admin Panel

**Auditor:** Nguyen Van Minh (Blade Architect, Tier 2)
**Date:** 2026-06-29
**Scope:** All Blade views in `resources/views/` — layout structure, sidebar, navbar, forms, components, RTL

---

## Summary

**Total findings: 18** (3 Critical, 8 Major, 7 Minor)

### Top 3 Structural Issues

1. **DUAL SIDEBAR + NAVBAR SYSTEMS** — `layouts/admin.blade.php` has inline sidebar+navbar (Alpine.js, Material Icons, light theme) BUT `admin/partials/sidebar.blade.php` + `admin/partials/navbar.blade.php` are also present (SVG icons, dark theme, separate CSS files). Confirmed in CONTEXT.md: partials are **orphaned** — admin UI runs entirely on the inline layout. This is dead code that confuses maintenance.

2. **SIDEBAR DRIFT** — Two sidebar implementations have **different menu items**. Inline sidebar (the live one) is missing: KYC, Fees, Support Tickets, Withdrawals, Audit Log, and has no "الحساب" section. The orphaned sidebar has all of these. Live sidebar also missing section label for the "العمليات" group.

3. **COMPONENT BYPASS** — 25 Blade components exist (`components/admin/`) including `<x-admin.card>`, `<x-admin.form>`, `<x-admin.input>`, `<x-admin.button>`, `<x-admin.modal>` but both `dashboard.blade.php` and `settings/index.blade.php` use raw HTML/CSS for everything — inconsistent with the design system.

---

## 1. Layout Structure (`layouts/admin.blade.php`)

| Item | Status | Finding |
|------|--------|---------|
| `@stack('styles')` | ✅ | Line 1013 — rendered in `<head>` |
| `@stack('scripts')` | ✅ | Line 1600 — rendered before `</body>` |
| `@yield('content')` | ✅ | Line 1428 — in `<main>` |
| `@yield('title')` | ✅ | Lines 7, 1262 |
| `@yield('breadcrumbs')` | ✅ | Line 1265 |
| `@section('topbar-actions')` | ✅ | Line 1281 — page-specific topbar buttons |
| `x-data="sidebarLayout"` | ✅ | Line 2 — persists sidebar state to localStorage |
| Toast system | ✅ | Lines 1018–1031 — Alpine.js toast container |
| Confirm modal | ✅ | Lines 1034–1049 |
| Keyboard shortcuts modal | ✅ | Lines 1052–1070 |

**F-01 [MAJOR]** `@hasSection('topbar-actions')` uses `@endif` (line 1284) instead of `@endhasSection`. Blade compiles both forms, but `@endif` is semantically wrong for `@hasSection`.

**F-02 [MAJOR]** No `@section('footer')` or `@stack('footer')`. Entire admin panel has **no footer**. Missing: copyright, version, "All rights reserved", system status info.

---

## 2. Sidebar Audit — Inline (LIVE) vs Partial (ORPHANED)

### 2.1 Live Sidebar (`layouts/admin.blade.php` lines 1072–1250)

Sections and items present:

```
الرئيسية → لوحة التحكم
الإدارة → المستخدمون, المعاملات
(❌ NO LABEL) → الإشعارات والتسويق, الدردشة الحية, الوكلاء, التجار, الشركات, الذهب
النظام → إعدادات النظام (→ الطرف الثالث, القنوات, القوالب, الصيانة, التحديث, الدعم), الإعدادات
```

### 2.2 Orphaned Sidebar (`admin/partials/sidebar.blade.php`)

Sections and items:

```
الرئيسية → لوحة المعلومات
الإدارة → المستخدمون, التحقق KYC, المعاملات, المحافظ والسحوبات, الذهب والأسعار, الرسوم والحدود, الدعم الفني
شركاء الأعمال → التجار, الوكلاء, الشركات
التقنية → التكاملات, النظام (→ الطرف الثالث, القنوات, القوالب, الصيانة, البيانات, التحديث, صحة النظام, النسخ الاحتياطي)
الحساب → سجل النشاطات, الإعدادات
```

### 2.3 Sidebar Comparison — Items in Orphaned but MISSING from Live

| Menu Item | Orphaned Sidebar | Live Sidebar | Severity |
|-----------|-----------------|--------------|----------|
| التحقق KYC | ✅ `admin.kyc.index` | ❌ | CRITICAL — admins can't access KYC queue |
| المحافظ والسحوبات | ✅ `admin.withdrawals.index` | ❌ | MAJOR — can't view/manage withdrawals |
| الرسوم والحدود | ✅ `admin.fees.index` | ❌ | MAJOR — can't configure fees |
| الدعم الفني | ✅ `admin.support.index` | ❌ | MAJOR — can't access support tickets |
| التكاملات | ✅ `admin.integrations.overview` | ❌ | MAJOR — can't manage Stripe/etc. |
| سجل النشاطات | ✅ `admin.audit.index` | ❌ | MAJOR — no audit trail access |
| صحة النظام | ✅ `admin.system.health` | ❌ | MAJOR |
| النسخ الاحتياطي | ✅ `admin.system.backup` | ❌ | MAJOR |

**F-03 [CRITICAL]** Live sidebar is **missing 8 menu items** that exist in the orphaned sidebar. Admin cannot navigate to KYC, withdrawals, fees, support tickets, integrations, audit log, health, or backup pages. The orphaned sidebar had the correct item set — the inline rewrite lost them.

**F-04 [MAJOR]** Live sidebar has no section label for the "العمليات" group (comment at line 1112 but no `<p class="sidebar-label">` element). Items appear ungrouped between الإدارة and النظام.

### 2.4 Sidebar Item Order — Assessment

Live sidebar ordering is **logically flawed**:
- "الإشعارات والتسويق" and "الدردشة الحية" are under the implicit "العمليات" group but semantically belong under "الإدارة"
- Users and Transactions are under "الإدارة" but Notifications/Chat/Agents/Merchants/Companies are in a different unlabeled group — inconsistent
- No "شركاء الأعمال" section (Agents/Merchants/Companies dumped under "العمليات")
- No "الحساب" section (no Audit Log, Settings is orphaned under "النظام")

### 2.5 Mobile Bottom Nav

`admin/partials/sidebar.blade.php` lines 370–412 has a mobile bottom nav (5 items: Dashboard, Users, Transactions, KYC, More) — this is **ALSO orphaned** since the partial isn't loaded.

---

## 3. Navbar Audit

### 3.1 Live Navbar (`layouts/admin.blade.php` lines 1255–1418)

| Feature | Status |
|---------|--------|
| Sidebar toggle | ✅ Line 1258 |
| Page title + breadcrumbs | ✅ Lines 1262–1266 |
| Global search | ✅ Lines 1271–1277 |
| `@yield('topbar-actions')` | ✅ Lines 1281–1284 |
| Notification bell | ✅ Lines 1287–1344 (inline Alpine) |
| Keyboard shortcuts button | ✅ Line 1346 |
| Profile dropdown | ✅ Lines 1360–1416 |

**F-05 [MAJOR]** Two notification systems exist: layout inline (lines 1287–1344, Alpine.js) AND `admin/partials/navbar.blade.php` (lines 154–239, vanilla JS). Live layout loads both `$__unread` and `AdminAlert::forAdmin()` queries independently. The partial is not loaded, but the duplicated DB query in the live layout runs on **every admin page** (no caching).

### 3.2 Orphaned Navbar (`admin/partials/navbar.blade.php`)

**F-06 [MINOR]** The orphaned navbar is a complete second implementation with:
- Separate CSS file (`sakk-admin/css/navbar.css`)
- Create dropdown (User/Merchant/Agent/KYC)
- Global search with Ctrl+K
- Notification dropdown with dismiss/mark-all-read
- Profile dropdown with avatar
- Sidebar mobile toggle
- Also 2 notification DB queries (lines 165–171 and 185–187)

This is **dead code** that wastes 457 lines and 2 CSS/JS files.

---

## 4. Footer Audit

**F-02 [MAJOR]** No footer exists anywhere in the admin layout. Missing elements:
- Copyright line ("© 2026 صكك | SAKK Wallet")
- App version
- Support link / contact
- System status indicator
- The orphaned sidebar has a user card + logout (line 335–358), but no copyright

Compare: `layouts/admin.blade.php` has no footer, while `layouts/portal.blade.php` has minimal footer (line 82: `صكّ — منصّة المدفوعات · {{ now()->year }}`). `layouts/company.blade.php` also has no footer.

---

## 5. Component Library Assessment

### 5.1 Existing Components (`components/admin/` — 25 files)

| Component | Usage Count | Notes |
|-----------|------------|-------|
| `card.blade.php` | ❌ Used 0 times in admin views | Dashboard uses raw `<div class="card">` |
| `button.blade.php` | ❌ Used 0 times in admin views | All buttons are raw `<button class="btn btn-primary">` |
| `form.blade.php` | ❌ Used 0 times in admin views | Settings uses raw `<form>` |
| `input.blade.php` | ❌ Used 0 times in admin views | Settings uses raw `<input class="input">` |
| `modal.blade.php` | ❌ Used 0 times in admin views | Layout has inline modal |
| `stat.blade.php` | ❌ Used 0 times | Dashboard uses raw `.stat-card` |
| `tabs.blade.php` | ❌ Used 0 times | Dashboard uses raw `.tab` buttons |
| `badge.blade.php` | ❌ Used 0 times | Raw `.badge` spans everywhere |
| `icon.blade.php` | ❌ Used 0 times | Raw `<span class="material-icons">` everywhere |

**F-07 [CRITICAL]** All 25 Blade components are **completely unused** in admin views. The component system is perfectly built but every admin view bypasses it with raw HTML. This defeats the purpose of having a design system — changes to `card.blade.php` won't propagate because views don't use it.

### 5.2 Views That Directly Use @include Instead of Components

- `dashboard.blade.php` — raw HTML for stat cards, tables, buttons, tabs
- `settings/index.blade.php` — raw HTML for all sections, uses `@include('admin.settings._num')` for number fields
- `agents/create.blade.php`, `agents/edit.blade.php` — likely raw HTML
- `merchants/*` — likely raw HTML

**F-08 [MAJOR]** Inconsistent approach: `@include('admin.settings._num')` is used for number inputs settings (settings/index.blade.php:257) but the `_num` partial still uses raw HTML. No view uses `<x-admin.input type="number">`.

---

## 6. Form Consistency

| Pattern | Status | Location |
|---------|--------|----------|
| `@csrf` in all POST forms | ✅ | Always present |
| Validation errors displayed | ✅ | Flash partial handles this |
| `novalidate` on forms | ⚠️ Partially | `layouts/admin.blade.php` has no `novalidate`; `<x-admin.form>` has it |
| `old()` values preserved | ⚠️ Partially | Login uses `old('email')`; other forms unknown |
| `.input-error` red border | ✅ | Defined in admin layout CSS |
| `aria-invalid` | ⚠️ Partially | Login page JS uses it; component does too |

**F-09 [MINOR]** No consistent form error display pattern. Admin layout has flash-based errors (dispatch toast events). Login page shows inline `.alert` div. Component uses `.field-error`. Three different patterns for the same thing.

**F-10 [MINOR]** Some views use `@if(session('success'))` in the layout (line 1422), while `admin/partials/flash.blade.php` uses `data-flash-type` sentinel elements — two approaches. The flash partial exists but may not be `@include`'d consistently.

---

## 7. Auth Pages (`admin/auth/`)

| File | Status | Notes |
|------|--------|-------|
| `login.blade.php` | ✅ | Premium split-screen design, standalone (no @extends) |

**F-11 [MAJOR]** Only 1 auth view exists. Missing:
- Password reset (`forgot-password.blade.php`, `reset-password.blade.php`)
- 2FA verification (`two-factor-challenge.blade.php`)
- Confirm password (`confirm-password.blade.php`)
- These may be handled by Laravel's default auth scaffolding, but need verification

**F-12 [MINOR]** Login page font includes weight 900 (`admin/auth/login.blade.php:15`) while the admin layout stops at 800. Inconsistent.

**F-13 [MINOR]** Login page is standalone (full `<html>`) — no shared auth layout. Company login also standalone. Could benefit from `layouts/auth.blade.php`.

---

## 8. Company Portal Views

| File | Status | Notes |
|------|--------|-------|
| `company/auth/login.blade.php` | ✅ | Minimal standalone |
| `company/dashboard.blade.php` | ✅ | Extends `layouts.portal` |
| `company/employees/` | ✅ | CRUD views |
| `company/payroll/` | ✅ | Payroll views |
| `company/wallet/` | ✅ | Wallet views |

**F-14 [MINOR]** Company login is visually **completely different** from admin login — no SAKK brand identity (no burgundy/gold, no IBM Plex Sans Arabic). Company dashboard uses `layouts.portal` which is clean. Inconsistency between the two login pages.

**F-15 [MINOR]** Company portal uses emoji in brand text: `🏢` in `company.blade.php` line 47 and `company/auth/login.blade.php` line 20. Not consistent with the text-only SAKK design system.

---

## 9. `@extends`, `@include`, `@component` Usage Patterns

| Pattern | Usage | Findings |
|---------|-------|----------|
| `@extends('layouts.admin')` | Dashboard, Settings, Transactions, Users, etc. | ✅ Standard |
| `@extends('layouts.company')` | Company views | ✅ Standard |
| `@extends('layouts.portal')` | Agent/Merchant portals | ✅ Standard |
| `@include('admin.settings._num')` | Settings (raw HTML partial) | ⚠️ Should be component |
| `@include('admin.transactions.partials._table')` | Transactions | ✅ OK for shared view fragments |
| `<x-admin.*>` | **None found in admin views** | ❌ Never used |
| `@push('styles')` | Settings, Dashboard | ✅ |
| `@push('scripts')` | Dashboard | ✅ |

**F-16 [MAJOR]** `@component` and `<x-admin.*>` are **never invoked** despite 25 components being defined. All admin views use raw HTML + utility classes exclusively.

---

## 10. RTL Correctness

| Feature | Status | Notes |
|---------|--------|-------|
| `dir="rtl"` on `<html>` | ✅ | All layouts |
| `dir="rtl"` on `<body>` | ✅ | admin.blade.php line 1015 |
| `inset-inline-start/end` | ✅ | Used in CSS for sidebar, modals, timeline |
| `border-inline-start/end` | ✅ | Used throughout |
| `margin-inline-start/end` | ✅ | Sidebar sub-items |
| `padding-inline-start/end` | ✅ | Select inputs |
| `left/right` in inline styles | ⚠️ Some | Need audit |
| Material Icons RTL | ✅ | Icons are symmetric or use RTL-safe names |

**F-17 [MINOR]** Some inline styles use `left`/`right` instead of `inset-inline-start/end`:
- `layouts/admin.blade.php` line 815: `left: 0;` (slide-over)
- Line 825: `transform: translateX(-100%);` (should be RTL-aware)
- Line 1018: `top-5 left-5` (toast position)
- Line 1035: `absolute inset-0` (modal overlay — OK, full)

These work because the full layout is RTL (browser swaps `left`/`right` meaning), but mixed `left` with `inset-inline-start` creates confusion.

---

## 11. Component Extraction Opportunities

### High-Value Candidates

| Pattern | Location | Times Repeated | Component |
|---------|----------|---------------|-----------|
| Stat card (icon + value + label + delta) | `dashboard.blade.php:76-104` | 6 | `<x-admin.stat-card>` |
| Quick action grid item | `dashboard.blade.php:222-241` | 6 | `<x-admin.quick-action>` |
| KPI strip card | `admin.blade.php CSS:708-738` | Defined but unused | `<x-admin.kpi-strip>` |
| Table row with status badge | Every index page | ∞ | `<x-admin.table-row>` |
| Needs-attention item | `dashboard.blade.php:250-268` | 2+ | `<x-admin.attention-item>` |
| Switch/toggle | `settings/index.blade.php:179-183` | 8 | Already unused `<x-admin.toggle>` |
| Section header with icon | `settings/index.blade.php:166-168` | 8 | `<x-admin.section-header>` |
| Alert item row | `admin.blade.php:1312-1327` | Event-driven | OK |
| Breadcrumb nav | `admin.blade.php:1263-1266` | All pages | `<x-admin.breadcrumbs>` |

---

## 12. Dead/Orphaned Files

**F-18 [MAJOR]** Confirmed dead/orphaned files:

| File | Lines | Status |
|------|-------|--------|
| `admin/partials/sidebar.blade.php` | 458 | NOT INCLUDED anywhere |
| `admin/partials/navbar.blade.php` | 457 | NOT INCLUDED anywhere |
| `public/sakk-admin/css/sidebar.css` | ~200+ | Only referenced by orphaned partial |
| `public/sakk-admin/css/navbar.css` | ~200+ | Only referenced by orphaned partial |
| `public/sakk-admin/admin.js` | ~300+ | ORPHANED per CONTEXT.md (admin runs on Alpine) |

Total: 5 files, ~1,600+ lines of dead code.

---

## Recommendations

### P0 — Fix Now

1. **Merge sidebar items** — Add missing items (KYC, Withdrawals, Fees, Support, Integrations, Audit, Health, Backup) to the live inline sidebar in `layouts/admin.blade.php`. Remove orphaned `admin/partials/sidebar.blade.php`.

2. **Add section label** for "العمليات" group (line 1112, admin.blade.php) — currently just a comment with no `<p class="sidebar-label">`.

3. **Fix `@hasSection` / `@endif`** at admin.blade.php:1284.

### P1 — Strongly Recommended

4. **Start using `<x-admin.*>` components** in new/modified views. Prioritize: `dashboard.blade.php` and `settings/index.blade.php` for migration to components.

5. **Add admin footer** with copyright, version, support link.

6. **Delete orphaned files** or deliberately wire them if the inline implementation should be migrated to partials.

7. **Fix sidebar order**: Group items logically:
   - الرئيسية → Dashboard
   - الإدارة → Users, KYC, Transactions, Notifications, Chat
   - العمليات → Withdrawals, Gold, Fees
   - شركاء الأعمال → Agents, Merchants, Companies
   - التقنية → Integrations, System Settings
   - الحساب → Audit Log, Settings

### P2 — Nice to Have

8. **Create shared auth layout** (`layouts/auth.blade.php`) for both admin and company login pages.

9. **Replace emoji** `🏢` in company portal with SVG/Material icon.

10. **Remove duplicated DB queries** — cache `$__unread` and `AdminAlert::forAdmin()` counts, or deduplicate the two notification implementations.

11. **Standardize form error display** — choose one pattern (component `.field-error` or Alpine toast or inline `.alert`) and use consistently.

12. **Replace `left`/`right`** with `inset-inline-start/end` in admin layout inline styles for full RTL correctness.
