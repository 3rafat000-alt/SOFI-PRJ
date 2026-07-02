# CONTENT-AUDIT: UX Copy Audit — PRJ-SAKK Admin Panel

**Date:** 2026-06-29  
**Scope:** All admin Blade views under `backend/resources/views/admin/`  
**Reference:** `docs/carda-wallet_Copy.json` (v1.0.0, 828 string pairs)  
**Brand:** صكك (SAKK) · Damascene Burgundy (#6E1B2D) + gold accent  
**Locale:** ar (RTL, فصحى مبسطة) · Tone: professional-warm (مهني دافئ)

---

## 1. Summary

| Metric | Score |
|--------|-------|
| Arabic quality | 8/10 — fluent فصحى مبسطة, consistent RTL |
| Terminology consistency | 6/10 — English loanwords (KYC, API, Sandbox) mixed in |
| Missing translations | 5/10 — no `lang/` directory, all text hardcoded |
| Empty/error/loading states | 7/10 — good on user/transaction pages, missing elsewhere |
| Overall maturity | 6.5/10 — functional but needs i18n foundation |

---

## 2. Arabic Quality Score

**Good:**
- All page `<title>` tags are Arabic (`@section('title', 'العنوان العربي')`)
- All KPI labels, filter labels, table headers, buttons in Arabic
- Error pages (403/404/419/429/500/503) fully Arabic via `errors.partials.shell`
- Legal pages (terms, privacy) bilingual with Arabic primary
- Login page is fully Arabic, brand-accurate
- Breadcrumbs and sidebar navigation all Arabic
- Merchant, agent, company CRUD forms all Arabic
- Chat interface, notification system all Arabic

**Issues:**
- Some `<p>` descriptions use English loanwords without explanation
- `نظام الدفع عبر API` — acceptable but `API` stays in Latin
- `اعرف عميلك (KYC)` — good practice of Arabic + English in parens
- `التحقق KYC` — inconsistent: sometimes `KYC` precedes Arabic, sometimes follows
- `تجريبي (Sandbox)` / `إنتاجي (Production)` — inconsistent pattern with English parens

---

## 3. Terminology Consistency

### Approved terminology (matches Copy.json):
| Arabic | Copy.json | Used in |
|--------|-----------|---------|
| مستخدم | user | users/index, show, sidebar |
| وكيل | agent | agents/index, show |
| شركة | company | companies/* |
| تاجر | merchant | merchants/* |
| معاملة | transaction | transactions/* |
| محفظة | wallet | users/show, dashboard |
| ذهب | gold | gold/prices, gold/transactions |
| رسوم | fee | fees/index |
| إيداع | deposit | transactions, users |
| سحب | withdrawal | withdrawals/* |
| سحب | withdrawal | withdrawals/* |
| تحويلة | transfer | transactions |
| بطاقة | card | sidebar, settings |
| رصيد | balance | all KPI strips |
| مسحوبات | withdrawal | withdrawals/index |
| جهة الاتصال | contact | support |

### Inconsistencies found:

| Issue | File(s) | Current | Suggested |
|-------|---------|---------|-----------|
| `KYC` raw in visible labels | 20+ files (sidebar, tables, KPIs, modals) | `KYC معلّق`, `حالة KYC`, `مستوى KYC` | `التحقق KYC` or `اعرف عميلك` consistently |
| `API` raw in headings | merchants/show, third-party | `API Key`, `API Secret` | `مفتاح API`, `الرمز السري API` |
| `Sandbox`/`Production` English in parens | merchants/create, merchants/edit | `تجريبي (Sandbox)` | `تجريبي` only or `Sandbox` dropped |
| `Sandbox`/`Production` English-only in select | integrations/show | `Production`, `Sandbox` options | `إنتاجي`, `تجريبي` only |
| `Webhook` English-only | merchants/show, third-party | `Webhook URL`, `Webhook Secret` | `رابط الويبهوك`, `سر الويبهوك` |
| `User Agent` mixed | audit/show | `وكيل المتصفح (User Agent)` | `وكيل المتصفح` only |
| English `status` raw displayed | companies/documents | `{{ $doc->status }}` | Arabic mapped via label |
| `KYC Level` English | withdrawals/show | `KYC Level` label | `مستوى التحقق` |
| `Test Mode` English | third-party | `Test Mode` in label | `وضع اختبار` |
| `Debug` English | third-party | `Debug` in label | `التشخيص` (current) inconsistent |

---

## 4. Missing Translations / No `lang/` Directory

**Status:** ❌ No `backend/resources/lang/` directory exists.

All translation is **hardcoded inline** in Blade views via:
- Direct Arabic strings in HTML
- `@php` maps with Arabic values (e.g. `$stMap`, gender maps)
- Inline `{{ $var === 'x' ? 'عربي' : 'عربي' }}` ternaries

**Impact:**
- Impossible to add English (or any second language) without touching every Blade file
- Legal pages' `?lang=en` toggle works but is reinventing `__()` — it manually switches hardcoded text blocks
- No `@lang()`, `__()`, or JSON translation files anywhere

**Critical gap:** Any new admin feature requires manual Arabic copy, risking drift from Copy.json.

---

## 5. Empty, Error, and Loading States

### Pages WITH proper empty states:
- **Users index** — `components/admin/empty-state` component, KPIs have skeletons, slide-over has skeleton
- **Transactions index** — data-table `$empty` slot, KPI skeletons
- **Merchants index** — inline `table-empty` div with contextual message, CTA
- **Merchants documents** — inline `table-empty` with contextual message
- **Merchants dashboard** — inline empty state for recent activity
- **Chat** — `لا توجد محادثات.` inline
- **Notifications** — inline empty state
- **Withdrawals index** — contextual empty state
- **Support index** — contextual empty state
- **Settings** — `لا توجد إعدادات` via subscribe
- **System messages** — inline empty with seeder hint
- **System channels** — inline empty with seeder hint
- **System backup** — full empty state with icon + description + CTA

### Pages MISSING empty states:
- **Dashboard** — no empty state for KYC requests, recent users, or chart data
- **Audit index** — inline `لا توجد نتائج` but no component reuse, no empty icon
- **Agents index** — inline but no component reuse
- **Companies index** — `لا شركات بعد.` — minimal, no icon/CTA
- **Companies show** — `لا دفعات.` — minimal text
- **Companies documents** — `لا مستندات.` — minimal text
- **Companies documents-show** — `لا مستندات مرفوعة.` — minimal
- **Gold transactions** — check needed
- **Gold prices** — check needed

### Pages WITH loading/skeleton states:
- **Users index** — KPIs load via Alpine fetch(), skeleton shown during load
- **Transactions index** — same pattern
- **Users slide-over** — skeleton, error state, retry
- **Transactions slide-over** — skeleton, error state
- **Gold prices** — KPIs via Alpine fetch()
- **Dashboard** — KPIs via Alpine fetch() but NO skeletons shown during load
- **Merchants dashboard** — no skeleton for chart/recent activity

### Pages MISSING loading states:
- **Dashboard** — KPIs fetched but no loading indicator
- **Merchants dashboard** — chart renders immediately (hardcoded fallback data)
- **Companies index** — no async data loading
- **Agents index** — no async data loading
- **Support index** — no async data loading

---

## 6. Page Group Findings

### 6a. Auth Pages (`admin/auth/`)
- **Login** — fully Arabic, standalone HTML, no layout reuse
- **Issue:** `btn-loading` CSS class used for loading state but no skeleton
- Copy accurate to brand

### 6b. Dashboard (`dashboard.blade.php`)
- KPI labels all Arabic ✅
- Sidebar link `مراجعة KYC` — KYC in Latin ❌
- Chart labels hardcoded Arabic day names `['السبت', 'الأحد', ...]`
- No loading skeleton on KPI fetch ❌
- Quick action cards use proper Arabic
- Sidebar counter badges for KYC pending — `KYC` Latin ❌

### 6c. Users (`users/`)
- ✅ Best-in-class: KPIs, filters, table, slide-over, modals all Arabic
- ✅ Loading skeletons on KPI strip and slide-over
- ✅ Error states + retry
- ✅ Contextual empty states
- ❌ `KYC` in column headers, filter labels, modal titles
- ❌ `KYC Level` in slide-over badge
- `API` appears in user detail context
- Gender/dox type maps in `@php` — Arabic values correct
- `modal` partials use `KYC` in English for doc approve/reject

### 6d. Transactions (`transactions/`)
- ✅ Arabic KPIs, filters, table, slide-over
- ✅ Loading skeletons
- ❌ `KYC` appears in transaction show page (context of sender)
- Currency `ل.س` for SYP correctly displayed
- Status badges mapped to Arabic

### 6e. Fees (`fees/index.blade.php`)
- ✅ Arabic labels, tab labels, fee type names
- ❌ No empty state component reuse
- Toggle switches use Tailwind `-translate-x` classes

### 6f. Gold (`gold/`)
- ✅ Arabic KPI labels, karat labels, pricing table
- ✅ Loading on gold prices KPI
- ❌ `Gold` mixed with `ذهب` in some `@php` map keys

### 6g. Agents (`agents/`)
- ✅ Arabic KPI labels, table headers, form labels
- ❌ `KYC` in header badge (`KYC: {{ $agent->kyc_status ? $agent->kyc_status_label : 'غير محدد' }}`)
- ❌ `حالة التحقق (KYC)` — English in parens
- ❌ Documents page uses `اعرف عميلك (KYC)` — parentheses style inconsistent
- KPI strip not reused from component

### 6h. Merchants (`merchants/`)
- ✅ Best Arabic copy in this group
- ✅ Proper contextual empty states with CTA
- ❌ `KYC معلّق` in KPI label
- ❌ `KYC` table header
- ❌ `API Key`, `API Secret`, `Webhook URL` labels English
- ❌ `تجريبي (Sandbox)` / `إنتاجي (Production)` — English parens
- `إنتاج` vs `إنتاجي` — inconsistent between index and show/dashboard

### 6i. Companies (`companies/`)
- ✅ Arabic page titles, labels, form fields
- ❌ **Critical:** `{{ $doc->status }}` raw English value displayed on documents page
- ❌ Minimal empty states — `لا شركات بعد.`, `لا دفعات.`, `لا مستندات.`
- ❌ No loading states
- ❌ Different styling convention from merchants (inline styles, no component reuse)
- `@section('breadcrumbs', ...)` syntax is a single string instead of multi-line

### 6j. Settings (`settings/index.blade.php`)
- ✅ Arabic rail navigation, section labels, field labels
- ✅ Toggle labels Arabic
- ❌ `$` unit symbol used in labels (`الحد الأدنى للإيداع`, unit `$`) — acceptable for USD context
- No empty state on cache section
- Arabic footnote text on rate management

### 6k. Withdrawals (`withdrawals/`)
- ✅ Arabic KPI labels, table headers, status badges
- ❌ `KYC Level` in withdrawals show page
- ❌ `Webhook` in show page section heading `بيانات Webhook`

### 6l. Support (`support/index.blade.php`)
- ✅ Arabic labels, status map, category map
- No async loading
- Proper empty state

### 6m. Chat (`chat/`)
- ✅ Arabic interface (tabs, buttons, placeholder text)
- ✅ Polling for real-time updates
- ❌ Heavy inline styles, no component reuse
- ❌ No empty state component reuse (`لا توجد محادثات.` inline)

### 6n. Audit (`audit/`)
- ✅ Arabic labels, breadcrumbs
- ❌ `وكيل المتصفح (User Agent)` — English parens
- ❌ No component reuse (empty-state, data-table)
- Standalone CSS, different style from core admin

### 6o. Notifications (`notifications/index.blade.php`)
- ✅ Arabic labels, audience labels
- `kyc_verified` audience label includes `(KYC)` — mix
- Push grid supports Arabic form labels

### 6p. System (`system/`)
- ✅ Arabic across all pages (backup, channels, messages, maintenance, app-update, support, third-party, health)
- ✅ Good error handling with Arabic messages
- ✅ Consistent `_shell.blade.php` shared styles
- ❌ Third-party page: English labels (`API Key`, `Project ID`, `App ID`, `Site Key`, `Secret Key`, `Twilio SID`, `Twilio Auth Token`, `MAIL HOST`, `PORT`, `USERNAME`, `PASSWORD`)
- ❌ Stripe section: English terms `Secret Key`, `Publishable Key` in label but Arabic parentheses `المفتاح السري — Secret Key`
- ❌ CCPayment: `Base URL`, `Debug` in labels
- ❌ Telegram setup: `env`, `TELEGRAM_BOT_TOKEN` in message
- Backup page: excellent Arabic — full SAKK brand styling

### 6q. Integrations (`integrations/`)
- ✅ Arabic heading, filter buttons
- ❌ **Environment select options are English-only** (`Production`, `Sandbox` without Arabic)
- ❌ `API` in comment `/* اشارة الربط — حقول API Key */`
- English `active`/`inactive` class names (structural, not visual)

### 6r. Legal (`legal/`)
- ✅ Bilingual (ar/en via `?lang=`)
- ✅ Arabic primary with Cairo font
- Terms and Privacy fully translated
- No localization framework — inline `@if($lang === 'en')` ternaries

### 6s. Error Pages (`errors/`)
- ✅ Fully Arabic titles, messages, RTL shell
- ✅ Tone-based color palette (403=warning, 404=neutral, 419=sessions, 429=rate-limit, 500=error, 503=maintenance)
- ✅ Support contact in fallback

---

## 7. Reusable Component Usage

| Component | Used by | Missing from |
|-----------|---------|--------------|
| `empty-state` | users, transactions (via data-table) | audit, agents, companies, companies-show, companies-documents |
| `data-table` | users, transactions, merchants, support, agents | audit, companies |
| `skeleton` | users (slide-over), transactions (slide-over) | dashboard, companies, agents |
| `confirm-modal` | users partials | merchants (inline modal), companies (no modal) |

---

## 8. Recommendations (Priority Order)

### P0 — Critical
1. **Add Laravel `lang/` directory** with Arabic JSON translation file. Migrate all inline hardcoded strings to `__()` helpers over time.
2. **Fix raw English `$doc->status` display** on `companies/documents.blade.php:36` — map through `$doc->status_label` or `badge-{{ $doc->status_color }}` with Arabic text.

### P1 — High (terminology)
3. **Standardize `KYC` → `التحقق`** or `اعرف عميلك` consistently across all views (sidebar, KPIs, table headers, filter labels, modals). Current mix of `KYC`, `التحقق KYC`, `اعرف عميلك (KYC)` is confusing.
4. **Translate remaining English labels to Arabic:**
   - `API Key` → `مفتاح API` (merchants/show)
   - `API Secret` → `المفتاح السري` (merchants/show)
   - `Webhook URL` → `رابط الويبهوك` (merchants/show, third-party)
   - `User Agent` → `وكيل المتصفح` (audit/show) — drop English parens
5. **Fix `integrations/show.blade.php`** environment select — change `Production`/`Sandbox` to `إنتاجي`/`تجريبي` (remove English-only)
6. **Standardize environment labels** across all views: use `إنتاجي`/`تجريبي` consistently (currently `إنتاج` in merchants/index vs `إنتاجي` elsewhere)

### P2 — Medium (UX consistency)
7. **Add loading skeletons** to dashboard KPI fetch (Alpine fetch exists but no skeleton shown)
8. **Add empty-state component** to audit, agents, companies views
9. **Align companies views** styling with merchants convention (use card/data-table/empty-state components)
10. **Add CTA to all empty states** — when no data, offer user a clear next action

### P3 — Low (polish)
11. **Normalize breadcrumbs syntax** across views — some use `@section('breadcrumbs')<span>...</span>@endsection`, others `@section('breadcrumbs', 'string')`
12. **Drop Latin parentheticals** where Arabic stands alone: `تجريبي (Sandbox)` → `تجريبي`
13. **Consolidate sidebar `KYC`** — sidebar uses `التحقق KYC` (line 59) and `KYC` (mobile, line 402) — both should match
14. **Fix gold `@php` map keys** — ensure consistent `ذهب` usage in controller-side maps
15. **Add meta descriptions** (`<meta name="description">`) to admin pages for accessibility

---

## 9. File-by-File Audit Log

Files fully reviewed (36 of 19 admin subdirs, ~65 Blade files):
- `admin/auth/login.blade.php` ✅ all Arabic
- `admin/dashboard.blade.php` ❌ KYC in sidebar, no loading skeleton
- `admin/layouts/admin.blade.php` ✅ RTL, brand tokens
- `admin/partials/sidebar.blade.php` ❌ `KYC` label
- `admin/partials/navbar.blade.php` ✅ all Arabic
- `admin/users/index.blade.php` ✅ best practice — skeleton, empty state
- `admin/users/show.blade.php` ❌ `KYC` section, `API` in title
- `admin/transactions/index.blade.php` ✅ skeleton + empty state
- `admin/transactions/show.blade.php` ✅ Arabic
- `admin/fees/index.blade.php` ✅ Arabic
- `admin/gold/prices.blade.php` ✅ Arabic, skeleton
- `admin/gold/transactions.blade.php` ✅ Arabic
- `admin/agents/index.blade.php` ❌ KYC column header, no skeleton
- `admin/agents/show.blade.php` ❌ `KYC:`, `حالة التحقق (KYC)`
- `admin/companies/index.blade.php` ❌ minimal empty state, no skeleton
- `admin/companies/show.blade.php` ❌ `لا دفعات.` minimal empty
- `admin/companies/documents.blade.php` ❌❌ raw English status
- `admin/merchants/index.blade.php` ✅ contextual empty states
- `admin/merchants/show.blade.php` ❌ `API Key`, `API Secret` English
- `admin/merchants/create.blade.php` ❌ `تجريبي (Sandbox)` parens
- `admin/merchants/edit.blade.php` ❌ `تجريبي (Sandbox)` parens
- `admin/merchants/dashboard.blade.php` ✅ Arabic
- `admin/merchants/documents.blade.php` ✅ Arabic
- `admin/withdrawals/index.blade.php` ✅ Arabic
- `admin/withdrawals/show.blade.php` ❌ `KYC Level`, `بيانات Webhook`
- `admin/support/index.blade.php` ✅ Arabic
- `admin/audit/index.blade.php` ❌ no component reuse
- `admin/audit/show.blade.php` ❌ `وكيل المتصفح (User Agent)`
- `admin/notifications/index.blade.php` ❌ `(KYC)` in audience label
- `admin/settings/index.blade.php` ✅ Arabic
- `admin/chat/index.blade.php` ✅ Arabic
- `admin/chat/show.blade.php` ✅ Arabic
- `admin/system/backup.blade.php` ✅ excellent Arabic
- `admin/system/channels.blade.php` ✅ Arabic
- `admin/system/messages.blade.php` ✅ Arabic
- `admin/system/third-party.blade.php` ❌ English labels in field map
- `admin/system/maintenance.blade.php` ✅ Arabic
- `admin/system/app-update.blade.php` ✅ Arabic
- `admin/system/support.blade.php` ✅ Arabic
- `admin/system/health.blade.php` ✅ Arabic
- `admin/integrations/overview.blade.php` ✅ Arabic
- `admin/integrations/show.blade.php` ❌❌ English-only Production/Sandbox
- `errors/*.blade.php` ✅ fully Arabic
- `legal/terms.blade.php` ✅ bilingual
- `legal/privacy.blade.php` ✅ bilingual
- `components/admin/empty-state.blade.php` ✅ Arabic default
- `components/admin/data-table.blade.php` ✅ Arabic search placeholder
- `components/admin/skeleton.blade.php` ✅ design-only
- `components/admin/confirm-modal.blade.php` ✅ check needed

---

## 10. String Count Summary

| Type | Approx count | Notes |
|------|-------------|-------|
| Total Arabic strings in admin | ~1,200+ | Hardcoded across all views |
| English-only visible strings | ~45 | Mostly KYC, API, Webhook, Sandbox |
| Mixed en/ar labels | ~30 | `KYC معلّق`, `API Key`, etc. |
| Raw backend values displayed | ~5 | `$doc->status`, env labels |
| Missing empty states | ~10 pages | |
| Missing loading states | ~5 pages | |

---

*Audit completed 2026-06-29. Generated by gstack /investigate after reading all admin Blade views, components, legal pages, and error pages.*
