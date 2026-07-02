# Carda Wallet — Admin Users Module · Prototype Spec
**Version:** 1.0 · **Date:** 2026-06-25 · **Author:** SOFI UI/UX Designer (Gate 2)
**Hand-off:** sofi-content-strategist → sofi-principal-system-architect

> Implementation-ready. Build agents implement 1:1. No interpretation needed.
> Design dials (sofi-design-taste): DESIGN_VARIANCE=high · MOTION_INTENSITY=low · VISUAL_DENSITY=medium. WCAG 2.2 AA always wins.

> ⚠️ **SCOPE CHANGE (2026-06-25, post-build) — PRIVACY-FIRST / VIEW-ONLY.** Product owner ruled admins may NOT edit user data.
> **REMOVED** from the shipped build (routes+methods+UI+tests): edit page, `update` (PUT), KYC **level** change (Modal D), balance adjust (Modal C), impersonate (Modal G + banner C-08).
> **KEPT:** view full info (read-only 6 tabs); suspend/activate via `update-status` (statuses limited to active|suspended, reason required); bulk activate/suspend; KYC **document** approve/reject (Modals E/F). Plus: instant live search/filter/sort/pagination (AJAX `_table` fragment, no submit button); unified sharp radius + 1rem padding + icon scale. Sections 3 (Edit / Modals C·D·G) and 4 (impersonate/balance/kyc-level routes) below are SUPERSEDED for those items; everything else stands.

---

## 0. Concept & Principles

**The فكرة (idea):** Replace the scattered, flat-list UI with a command-center paradigm. The admin never navigates blind — every screen surfaces the right signals (risk, KYC gap, balance anomaly) without hunting. The layout moves from "form on a page" to "contextual panel system": sticky identity strip + tabbed content well on show, progressive disclosure on edit, and a live-updating KPI strip above the list.

**Three design bets:**
1. **Signal before action.** KPI strip + risk badges are always visible. Admins see the health of the user base at a glance; they act from context, not gut.
2. **Privilege is visible.** SEC-003 controls (status, kyc_level, kyc_status) live in their own clearly marked "Privileged Actions" zone — a locked-panel pattern with explicit "unlock to change" step, never mixed with plain fields.
3. **Every mutating action is a ritual.** Trigger → modal with full context → confirm with reason capture → result toast + ActivityLog. No blind POSTs.

**What changes vs current:**
- Index: adds KPI strip, column sorting, kyc_status/2FA/risk filters, real bulk endpoint, real server-side CSV export, slide-over quick-view.
- Show: replaces flat card grid with tabbed command-center (Overview / Wallets & Cards / Transactions / KYC Review / Security & Risk / Referrals). Removes ALL fake @empty data.
- Edit: splits into two sections separated by a privilege boundary divider; guarded fields go through audited modal flow, not plain form submit.
- New modals: KYC review, balance adjustment, impersonate, bulk confirm.

---

## 1. Information Architecture & Navigation

### Route map (new routes in **bold**)

| Screen | Route name | Method | URL |
|---|---|---|---|
| Users list | `admin.users` | GET | `/admin/users` |
| User detail | `admin.users.show` | GET | `/admin/users/{user}` |
| User edit | `admin.users.edit` | GET | `/admin/users/{user}/edit` |
| User update (plain fields) | `admin.users.update` | PUT | `/admin/users/{user}` |
| Suspend | `admin.users.suspend` | POST | `/admin/users/{user}/suspend` |
| Activate | `admin.users.activate` | POST | `/admin/users/{user}/activate` |
| **KPI aggregates (JSON)** | **`admin.users.kpis`** | **GET** | **`/admin/users/kpis`** |
| **Bulk action** | **`admin.users.bulk`** | **POST** | **`/admin/users/bulk`** |
| **CSV export (server-side)** | **`admin.users.export`** | **GET** | **`/admin/users/export`** |
| **Quick-view slide-over (JSON)** | **`admin.users.quick-view`** | **GET** | **`/admin/users/{user}/quick-view`** |
| **Update status (audited)** | **`admin.users.update-status`** | **POST** | **`/admin/users/{user}/update-status`** |
| **Update KYC level (audited)** | **`admin.users.update-kyc-level`** | **POST** | **`/admin/users/{user}/update-kyc-level`** |
| **KYC doc approve** | **`admin.users.kyc.approve`** | **POST** | **`/admin/users/{user}/kyc/{doc}/approve`** |
| **KYC doc reject** | **`admin.users.kyc.reject`** | **POST** | **`/admin/users/{user}/kyc/{doc}/reject`** |
| **Balance adjust** | **`admin.users.balance-adjust`** | **POST** | **`/admin/users/{user}/balance-adjust`** |
| **Impersonate** | **`admin.users.impersonate`** | **POST** | **`/admin/users/{user}/impersonate`** |
| **Impersonate exit** | **`admin.users.impersonate-exit`** | **POST** | **`/admin/users/impersonate-exit`** |

### Breadcrumb pattern (RTL)
```
لوحة التحكم › المستخدمون › [الاسم]     (show/edit)
لوحة التحكم › المستخدمون › [الاسم] › تعديل
```

---

## 2. Component Inventory

### Existing classes — use as-is
`.card` `.card-header` `.card-body` `.card-footer` `.card-title` `.card-subtitle`
`.btn` `.btn-primary` `.btn-secondary` `.btn-success` `.btn-danger` `.btn-ghost` `.btn-sm` `.btn-lg` `.btn-icon`
`.badge` `.badge-primary` `.badge-secondary` `.badge-success` `.badge-warning` `.badge-danger`
`.input` `.input-error` `.input-group` `.label` `.label-required` `.hint`
`.table` `.table-container` `.table-empty` `.table-empty-icon`
`.divider` `.section-title`

### Extend existing token — do NOT hardcode hex
Use `var(--primary)` not `#6E1B2D`. Use `var(--accent)` not `#B58A3C`. Use `var(--radius-sm)` (= 0.2rem) on every border-radius.

### NEW components (with exact tokens)

#### C-01 · KPI Strip Card
```
.kpi-strip-card
  background: var(--surface)
  border: 1px solid var(--border)
  border-radius: var(--radius-sm)
  padding: 1rem 1.25rem
  display: flex; flex-direction: column; gap: 0.25rem
  min-width: 11rem
  box-shadow: var(--shadow-sm)

.kpi-strip-card .kpi-value
  font-size: var(--font-size-2xl); font-weight: 800
  color: var(--text-primary); direction: ltr

.kpi-strip-card .kpi-label
  font-size: var(--font-size-xs); color: var(--text-muted)

.kpi-strip-card .kpi-icon
  color: var(--primary); font-size: 1.25rem
```
States: loading = skeleton pulse on value; error = "—" value + `var(--warning)` icon.

#### C-02 · Sort-Header Button
```
<th>
  <button class="sort-header" aria-label="ترتيب حسب [العمود]"
          data-col="[field]" :data-dir="sortDir">
    [Label]
    <span class="material-icons sort-icon">unfold_more</span>  <!-- default -->
    <!-- asc: arrow_upward · desc: arrow_downward -->
  </button>
</th>

.sort-header { background: none; border: 0; cursor: pointer;
  display: inline-flex; align-items: center; gap: 0.25rem;
  font-size: var(--font-size-xs); font-weight: 700;
  color: var(--text-secondary);
  padding: 0.25rem 0; }
.sort-header:focus-visible { outline: 2px solid var(--primary); outline-offset: 2px; border-radius: var(--radius-sm); }
[aria-sort="ascending"]  .sort-icon::before { content: 'arrow_upward'; }
[aria-sort="descending"] .sort-icon::before { content: 'arrow_downward'; }
```

#### C-03 · Tab Nav (show screen)
```
.tab-nav { display: flex; border-bottom: 1px solid var(--border);
  gap: 0; overflow-x: auto; scrollbar-width: none; }
.tab-nav .tab-btn {
  padding: 0.75rem 1.25rem; font-size: var(--font-size-sm); font-weight: 600;
  color: var(--text-secondary); border: 0; background: none; cursor: pointer;
  border-bottom: 2px solid transparent; white-space: nowrap;
  transition: color var(--transition-fast), border-color var(--transition-fast); }
.tab-nav .tab-btn:hover { color: var(--primary); }
.tab-nav .tab-btn[aria-selected="true"] {
  color: var(--primary); border-bottom-color: var(--primary); font-weight: 700; }
.tab-btn:focus-visible { outline: 2px solid var(--primary); outline-offset: -2px; border-radius: var(--radius-sm) var(--radius-sm) 0 0; }
```

#### C-04 · Slide-Over Panel
```
.slide-over-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.4);
  z-index: 40; transition: opacity var(--transition-normal); }
.slide-over { position: fixed; top: 0; left: 0; height: 100vh; width: 28rem;
  max-width: 100vw; background: var(--surface); z-index: 50;
  display: flex; flex-direction: column; box-shadow: var(--shadow-xl);
  border-right: 1px solid var(--border);
  transform: translateX(-100%);
  transition: transform var(--transition-normal); }
.slide-over[aria-hidden="false"] { transform: translateX(0); }
/* RTL: panel slides in from left (logical-start side) */
.slide-over-header { padding: 1.25rem; border-bottom: 1px solid var(--border);
  display: flex; align-items: center; justify-content: space-between; }
.slide-over-body { flex: 1; overflow-y: auto; padding: 1.25rem; }
```

#### C-05 · Modal (audited actions)
```
.modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.5);
  z-index: 50; display: flex; align-items: center; justify-content: center;
  padding: 1rem; }
.modal { background: var(--surface); border-radius: var(--radius-sm);
  width: 100%; max-width: 28rem; box-shadow: var(--shadow-xl);
  border: 1px solid var(--border); }
.modal-header { padding: 1.25rem; border-bottom: 1px solid var(--border);
  display: flex; align-items: flex-start; justify-content: space-between; }
.modal-body { padding: 1.25rem; }
.modal-footer { padding: 1rem 1.25rem; border-top: 1px solid var(--border);
  display: flex; justify-content: flex-end; gap: 0.5rem; }
/* danger variant: header background var(--danger-light), title var(--danger) */
```

#### C-06 · Privilege Zone
```
.privilege-zone { border: 1px solid var(--warning); border-radius: var(--radius-sm);
  background: var(--warning-light); padding: 1rem 1.25rem; }
.privilege-zone-header { display: flex; align-items: center; gap: 0.5rem;
  color: #92400e; font-size: var(--font-size-sm); font-weight: 700; margin-bottom: 0.75rem; }
/* Contains locked read-only display. "Unlock" btn triggers modal flow. */
```

#### C-07 · Activity Timeline Item
```
.timeline { padding: 0; list-style: none; position: relative; }
.timeline::before { content: ''; position: absolute; top: 0; bottom: 0;
  right: 1.125rem; width: 1px; background: var(--border); }
.timeline-item { display: flex; gap: 0.75rem; padding: 0.75rem 0; }
.timeline-dot { width: 1.5rem; height: 1.5rem; border-radius: var(--radius-full);
  border: 2px solid var(--border); background: var(--surface);
  display: flex; align-items: center; justify-content: center;
  font-size: 0.75rem; color: var(--text-muted); flex-shrink: 0; z-index: 1; }
.timeline-dot.dot-success { border-color: var(--success); color: var(--success); }
.timeline-dot.dot-danger  { border-color: var(--danger);  color: var(--danger);  }
.timeline-dot.dot-warning { border-color: var(--warning); color: var(--warning); }
.timeline-content { flex: 1; }
```

#### C-08 · Impersonate Banner
```
.impersonate-banner { position: sticky; top: 0; z-index: 60;
  background: var(--warning); color: #1c1917;
  padding: 0.5rem 1.25rem; display: flex; align-items: center; gap: 0.75rem;
  font-size: var(--font-size-sm); font-weight: 700; }
/* Injects above layout content; always visible while session is active */
```

#### C-09 · Skeleton Loader
```
.skeleton { background: linear-gradient(90deg,
    var(--border) 25%, var(--surface-hover) 50%, var(--border) 75%);
  background-size: 200% 100%; border-radius: var(--radius-sm);
  animation: skeleton-pulse 1.4s ease-in-out infinite; }
@keyframes skeleton-pulse { 0%{background-position:200% 0} 100%{background-position:-200% 0} }
```

#### C-10 · Risk Badge
```
<!-- severity: info / warning / high / critical -->
.risk-badge { display: inline-flex; align-items: center; gap: 0.25rem;
  padding: 0.125rem 0.5rem; border-radius: var(--radius-sm); font-size: 0.7rem; font-weight: 700; }
.risk-badge.risk-info     { background: var(--info-light);    color: var(--info); }
.risk-badge.risk-warning  { background: var(--warning-light); color: #92400e; }
.risk-badge.risk-high     { background: #fee2e2;              color: #991b1b; }
.risk-badge.risk-critical { background: var(--primary);       color: #fff; }
```

---

## 3. Per-Screen Spec

---

### SCREEN 1 — Users List (index)

**Route:** `GET /admin/users`
**Alpine component:** `x-data="usersPage()"`

#### Layout (ASCII wireframe, RTL)

```
┌─────────────────────────────────────────────────────────────────┐
│ BREADCRUMB: لوحة التحكم › المستخدمون                           │
├──────────────────────────┬──────────────────────────────────────┤
│ H1: المستخدمون           │ [تصدير CSV] [btn-success]            │
│ sub: إدارة المستخدمين   │                                       │
├──────────────────────────┴──────────────────────────────────────┤
│ KPI STRIP (5 cards, flex-wrap)                                  │
│  [إجمالي المستخدمين]  [نشط]  [KYC معلّق]  [موقوف]  [إجمالي الرصيد] │
├─────────────────────────────────────────────────────────────────┤
│ FILTER BAR (card)                                               │
│  [بحث................] [الحالة ▾] [KYC Level ▾] [kyc_status ▾] │
│  [2FA ▾] [مُبلّغ عنه ▾] [من: تاريخ] [إلى: تاريخ] [بحث] [تصفير]│
├─────────────────────────────────────────────────────────────────┤
│ BULK BAR (sticky, visible only when selection > 0)              │
│  ✓ N مستخدم محدد  [تفعيل] [إيقاف]  ────────────  [X إلغاء]    │
├─────────────────────────────────────────────────────────────────┤
│ TABLE (card)                                                    │
│  [☐] المستخدم  الحالة  KYC  رصيد الكل  آخر نشاط  إجراءات     │
│  ─────────────────────────────────────────────────────────────  │
│  [☐] [avatar] الاسم / البريد  [badge]  L2 ●●○  $xxx  تاريخ  │
│       [عرض سريع] [تعديل] [إيقاف/تفعيل]                        │
│  ...                                                            │
├─────────────────────────────────────────────────────────────────┤
│ PAGINATION  + "عرض 20 من 1,234"                                 │
└─────────────────────────────────────────────────────────────────┘
```

#### KPI Strip — element → data mapping

| KPI Card | Data Source | Backend method |
|---|---|---|
| إجمالي المستخدمين | `User::count()` | `kpisAggregate()` |
| نشط | `User::where('status','active')->count()` | same |
| KYC معلّق | `User::where('kyc_status','submitted')->count()` | same |
| موقوف | `User::where('status','suspended')->count()` | same |
| إجمالي الرصيد (USD) | `Wallet::sum('balance')` where currency=USD | same — **backend must add** |

KPI strip loads via Alpine `fetch('/admin/users/kpis')` on mount. Shows C-09 skeleton during load. Error state: red border + "تعذّر التحميل" + retry link.

#### Filter bar — element → query parameter → DB column

| Control | param | WHERE clause |
|---|---|---|
| بحث (text) | `search` | `first_name LIKE` OR `last_name LIKE` OR `email LIKE` OR `phone LIKE` |
| الحالة | `status` | `users.status` |
| مستوى KYC | `kyc_level` | `users.kyc_level` |
| حالة KYC | `kyc_status` | `users.kyc_status` |
| 2FA | `two_fa` | `users.two_factor_enabled` = 1/0 |
| مُبلّغ عنه | `aml_flagged` | `EXISTS (SELECT 1 FROM aml_flags WHERE user_id=users.id AND status='pending')` |
| من تاريخ | `date_from` | `users.created_at >= date_from` |
| إلى تاريخ | `date_to` | `users.created_at <= date_to + 23:59:59` |

#### Table columns — element → data field

| Column | Field | Sort key | Notes |
|---|---|---|---|
| ☐ | row selection | — | `value="{{ $user->uuid }}"` — use UUID not PK |
| المستخدم | `first_name` + `last_name` + `email` + `avatar` | `last_name` | Avatar: initials fallback from first_name[0] + last_name[0] |
| الحالة | `status` (UserStatus enum) | `status` | `.badge-success/danger/warning` per value |
| KYC | `kyc_level` (0-3) + `kyc_status` (KycStatus enum) | `kyc_level` | Dot-progress + status badge stacked |
| رصيد الكل | `wallets->sum('balance')` | `wallets_balance_sum` (join) | `dir="ltr"` · currency symbol prefix |
| آخر نشاط | `last_login_at` | `last_login_at` | `dir="ltr"` · format Y/m/d H:i · null → "لم يسجّل دخولاً" |
| إجراءات | — | — | [عرض سريع] [تعديل] [إيقاف ÷ تفعيل] |

Sort: clicking a `.sort-header` toggles `?sort=field&dir=asc|desc`; active column sets `aria-sort` attribute. Server reads `$request->sort` / `$request->dir` with allowlist validation.

#### Row quick-actions

- **عرض سريع** `btn-ghost btn-sm` + icon `preview` → fires `openQuickView(uuid)` → loads `/admin/users/{user}/quick-view` JSON → populates C-04 Slide-Over. No page navigation.
- **تعديل** → `href="{{ route('admin.users.edit', $user->uuid) }}"`.
- **إيقاف/تفعيل** → triggers C-05 Modal (single-user confirm flow, see Section 3 Modal A).

#### States

**Default (populated):** Table renders. KPI strip populated. Pagination links shown if `hasPages()`.

**Loading:** Page load — KPI strip shows 5 C-09 skeletons (48px × 20px value block). Table tbody shows 8 skeleton rows (each: avatar circle + 5 text blocks).

**Empty (no users exist):** KPI all zeros. Table shows `.table-empty` with icon `group_off` + "لا يوجد مستخدمون بعد" + sub-text "ستظهر الحسابات هنا بعد أول تسجيل".

**Empty (filtered, no results):** Same `.table-empty` but icon `search_off` + "لا توجد نتائج" + "جرّب تعديل معايير البحث" + [تصفير الفلاتر] link.

**Error (KPIs fail):** KPI strip shows warning state (see C-01 spec). Table still renders from SSR — no cascade failure.

**Bulk bar:** `opacity: 0; pointer-events: none; transform: translateY(-0.5rem)` when no selection. Transitions to `opacity: 1; transform: translateY(0)` (using `var(--transition-fast)`) when `selectedUsers.length > 0`. `position: sticky; top: 0; z-index: 20`.

#### Micro-interactions

- Row hover: `background: var(--surface-hover)` + checkbox opacity 1 (default 0.4). Transition 120ms.
- Checkbox check: row background `var(--accent-soft)`.
- Sort header click: icon animates between states over 150ms.
- Export button: loading spinner while fetch is in progress, disabled state.

#### Responsive (mobile < 768px)

Table collapses to card-list. Each card: avatar + name/email at top; status + KYC badges; balance; action buttons (icon-only). Bulk bar uses bottom sheet pattern (slides up from bottom). KPI strip scrolls horizontally (`overflow-x: auto`).

#### RTL notes

- Chevrons point right-to-left (use `chevron_right` in RTL context for "go forward").
- Bulk bar gradient: `bg-gradient-to-r from-[--primary] to-[--primary-dark]` (LTR direction in the code reads left-to-right, which in RTL layout renders right-to-left — validate visually).
- Money/dates: always `dir="ltr"` on the cell/span, not the row.
- Action buttons cluster: `justify-content: flex-start` (RTL natural end = left side of screen).

#### A11y notes

- Table `<caption class="sr-only">قائمة المستخدمين</caption>`.
- Select-all checkbox: `aria-label="تحديد الكل"`.
- Each row checkbox: `aria-label="تحديد {{ $user->first_name }} {{ $user->last_name }}"`.
- Sort buttons: `aria-sort="ascending|descending|none"` on `<th>`.
- KPI cards: wrapped in `<dl>` with `<dt>` label + `<dd>` value.
- Bulk bar: `role="toolbar" aria-label="إجراءات المحددين"`. Announced via `aria-live="polite"` span: "تم تحديد N مستخدم".
- Status badges: don't rely on color alone — include icon or text.
- Focus order: header → KPI → filter form → bulk bar → table headers → first row checkbox → row actions → pagination.

---

### SCREEN 2 — User Detail (show)

**Route:** `GET /admin/users/{user}`
**Load:** `$user->load(['wallets','cards','transactions' => fn($q)=>$q->latest()->take(10), 'kycDocuments','devices','activityLogs' => fn($q)=>$q->latest()->take(30), 'amlFlags' => fn($q)=>$q->latest()->take(20), 'referrer','referrals'])`

#### Layout (ASCII wireframe)

```
┌───────────────────────────────────────────────────────────────────┐
│ IMPERSONATE BANNER (C-08) — only when impersonating              │
├───────────────────────────────────────────────────────────────────┤
│ BREADCRUMB                                                        │
├───────────────────────────────────────────────────────────────────┤
│ IDENTITY HEADER (card, no rounded-2xl)                           │
│  [cover strip 6rem, primary gradient]                             │
│  ┌──────────────────────────────────────────────────────────────┐ │
│  │ [avatar 5rem, initials, ring] [Name H2][email][badges strip] │ │
│  │                               [status][kyc_level][kyc_status]│ │
│  │                                                [2FA badge]   │ │
│  │                         [تعديل][تسجيل كمستخدم][إيقاف/تفعيل]│ │
│  └──────────────────────────────────────────────────────────────┘ │
├───────────────────────────────────────────────────────────────────┤
│ TAB NAV (C-03, sticky on scroll past header)                     │
│  [نظرة عامة] [المحافظ والبطاقات] [المعاملات] [KYC] [الأمان]   │
│  [الإحالات]                                                       │
├───────────────────────────────────────────────────────────────────┤
│ TAB CONTENT AREA                                                  │
│  (each tab defined below)                                        │
└───────────────────────────────────────────────────────────────────┘
```

#### Identity Header — element → data mapping

| Element | Data field | Notes |
|---|---|---|
| Initials avatar | `first_name[0]` + `last_name[0]` | Avatar: `$user->avatar` if exists → `<img>` with object-fit:cover, else initials div |
| H2 Full name | `first_name` + `last_name` | |
| Email | `email` | icon `mail` |
| Phone | `phone` | `dir="ltr"` · null → hide |
| Status badge | `status` (UserStatus) | `.badge-success/danger/warning` + icon |
| KYC level badge | `kyc_level` | `.badge` dark, "المستوى N" |
| KYC status badge | `kyc_status` (KycStatus) | color-coded |
| 2FA badge | `two_factor_enabled` | badge-success "2FA فعّال" or badge secondary "2FA معطّل" |
| Member since | `created_at` | `dir="ltr"` |
| Last login | `last_login_at` + `last_login_ip` | both `dir="ltr"` |
| تعديل btn | link to `admin.users.edit` | |
| تسجيل كمستخدم btn | triggers Impersonate Modal | btn-ghost with icon `swap_horiz` |
| إيقاف/تفعيل btn | triggers Status Modal | |

---

#### TAB 1 — نظرة عامة (Overview)

```
┌─────────────────────────────────────────┐
│  4-cell KPI grid                        │
│  [إجمالي الرصيد] [عدد البطاقات]        │
│  [عدد المعاملات] [حجم المعاملات الكلي] │
├─────────────────────────────────────────┤
│  Personal info dl (2-col, dividers)     │
│  name / email / phone / dob / gender /  │
│  country / language / timezone /        │
│  email_verified_at / phone_verified_at  │
│  referral_code                          │
└─────────────────────────────────────────┘
```

| KPI | Field/Query | Notes |
|---|---|---|
| إجمالي الرصيد | `$user->wallets->sum('balance')` grouped by currency | display each currency separately, dir=ltr |
| عدد البطاقات | `$user->cards->count()` + `$user->activeCards->count()` active | "N بطاقة (N نشطة)" |
| عدد المعاملات | `$user->transactions->count()` (pre-counted, not loaded all) — **backend must add** | |
| حجم المعاملات | `$user->transactions->sum('amount')` USD equivalent — **backend must add** | `dir="ltr"` |

Personal info section: `<dl>` with `<div class="flex justify-between py-2.5 border-b border-[var(--border)]">`. Empty field value: show `—` (em-dash), never "N/A" or blank. All dates `dir="ltr"`. Email/phone: `dir="ltr"`. Gender: translated (ذكر/أنثى/غير محدد).

Empty state (new user, no wallets/cards): KPI cards show `$0.00` / `0` — this is real data, not fake, so it is correct.

---

#### TAB 2 — المحافظ والبطاقات (Wallets & Cards)

```
┌──────────────────────┬─────────────────────────────────┐
│  WALLETS section     │  CARDS section                  │
│  (per wallet card)   │  (table)                        │
│  currency / balance  │  masked_num / type / balance    │
│  is_default badge    │  status / expiry / link         │
│  [تعديل الرصيد btn] │                                 │
└──────────────────────┴─────────────────────────────────┘
```

**Wallets:** Each wallet renders as a row in a list (not `.table` — use definition list pattern for accessibility). Columns: currency (with flag emoji if available), balance `dir="ltr"`, is_default badge, status. Balance cell has `[تعديل الرصيد]` btn-ghost btn-sm → triggers Balance Adjust Modal (Modal C).

| Wallet field | Data |
|---|---|
| Currency label | `$wallet->currency` |
| Balance | `$wallet->balance` — formatted `number_format($wallet->balance, 2)` |
| Default flag | `$wallet->is_default` → badge-primary "افتراضي" |

**Empty wallets state:** icon `account_balance_wallet` + "لا توجد محافظ لهذا المستخدم" (genuine — no fake data).

**Cards table:** Existing columns + add `card_type` icon (credit_card / contactless). No fake `@empty` data. Real empty: icon `credit_card_off` + "لا توجد بطاقات".

| Card field | Data |
|---|---|
| رقم البطاقة | `card_number_masked` · font-mono · `dir="ltr"` |
| صلاحية | `expiry` · `dir="ltr"` |
| النوع | `card_type` (virtual/physical) |
| الرصيد | `balance` · `dir="ltr"` |
| الحالة | `status` (active/frozen/cancelled) |
| رابط | `route('admin.cards.show', $card)` |

---

#### TAB 3 — المعاملات (Transactions)

```
┌─────────────────────────────────────────────────────────┐
│  [رابط: عرض كل المعاملات →]                            │
│  TABLE (last 10)                                        │
│  المرجع | النوع | المبلغ | الحالة | التاريخ           │
│  ...                                                    │
│  [عرض الكل →] link to admin.transactions?user_id=uuid  │
└─────────────────────────────────────────────────────────┘
```

| TX field | Data | Notes |
|---|---|---|
| المرجع | `reference` | mono font · dir=ltr |
| النوع | `type` enum value | translate: deposit=إيداع · withdrawal=سحب · transfer=تحويل · refund=استرداد |
| المبلغ | `amount` + `currency` | + prefix for credit types (deposit/refund), - for debit. dir=ltr. Color: green/red |
| الحالة | `status` (completed/pending/failed) | badge |
| التاريخ | `created_at` | dir=ltr Y/m/d H:i |

Empty state: icon `receipt_long` + "لا توجد معاملات".
Error state (relation not loaded): icon `error_outline` + "تعذّر تحميل المعاملات" + [إعادة المحاولة] that reloads tab content.

---

#### TAB 4 — KYC Review

```
┌──────────────────────────────────────────────────────────┐
│  KYC LEVEL CONTROL (Privilege Zone C-06)                │
│  [المستوى الحالي: 2] [kyc_status badge]                 │
│  [تعديل المستوى btn] → opens Modal D                    │
├──────────────────────────────────────────────────────────┤
│  DOCUMENT LIST                                           │
│  ┌─────────────────────────────────────────────────────┐ │
│  │  [نوع الوثيقة]  [الدولة]  [تاريخ الانتهاء]        │ │
│  │  [status badge]  [معاينة]  [قبول] [رفض]            │ │
│  │  rejection_reason if rejected                       │ │
│  └─────────────────────────────────────────────────────┘ │
│  (one card per KycDocument)                             │
└──────────────────────────────────────────────────────────┘
```

**KYC Level Control** (C-06 Privilege Zone):
- Display: current level pill (0/1/2/3) + kyc_status badge + kyc_verified_at `dir="ltr"`.
- [تعديل المستوى] btn → Modal D (KYC level change). Requires reason + confirmation.

**Document list:** each `KycDocument` renders a card with:

| Field | Data |
|---|---|
| نوع الوثيقة | `document_type` enum → translated: national_id=بطاقة هوية، passport=جواز سفر، drivers_license=رخصة قيادة، selfie=صورة شخصية، selfie_with_id=صورة مع هوية، proof_of_address=إثبات العنوان، residence_permit=إقامة |
| رقم الوثيقة | `document_number` · null → "—" |
| دولة الإصدار | `issuing_country` (ISO-3) |
| تاريخ الانتهاء | `expiry_date` · dir=ltr · null → "—" |
| الحجم | `file_size` → formatted (KB/MB) |
| Status | `status` (VerificationStatus enum) → badge: pending=warning/قيد المراجعة · approved=success/مقبول · rejected=danger/مرفوض |
| سبب الرفض | `rejection_reason` · shown only when status=rejected · in amber callout box |
| مراجع | `verified_by` → admin name · null → "—" |
| تاريخ المراجعة | `verified_at` · dir=ltr |
| معاينة | `file_path` via `route('admin.secure-file', ['path'=>encrypt($doc->file_path)])` · opens in new tab or inline img modal for images |
| [قبول] btn | btn-success btn-sm → Modal E (KYC doc approve) |
| [رفض] btn | btn-danger btn-sm → Modal F (KYC doc reject) |

Both [قبول] and [رفض] disabled (greyed) when `$doc->status === 'approved'` or `'rejected'` already. Use `aria-disabled="true"` + no-click CSS.

Empty state (no documents uploaded): icon `upload_file` + "لم يرفع المستخدم أي وثائق بعد".

---

#### TAB 5 — الأمان والمخاطر (Security & Risk)

```
┌──────────────────────────────────┬────────────────────────────────┐
│  DEVICE LIST                     │  AML FLAGS                     │
│  (each device: name, type,       │  (each flag: rule_name,        │
│   is_trusted, status, last_ip,   │   severity, flagged_at,        │
│   last_active_at, approved_at)   │   status, reviewer_notes)      │
├──────────────────────────────────┴────────────────────────────────┤
│  ACTIVITY TIMELINE (C-07)                                        │
│  loaded from ActivityLog where user_id = $user->id               │
│  last 30 entries                                                  │
├───────────────────────────────────────────────────────────────────┤
│  2FA STATUS (read-only display)                                  │
│  two_factor_enabled badge + note (cannot disable from admin)      │
└───────────────────────────────────────────────────────────────────┘
```

**Device list columns:**

| Field | Data |
|---|---|
| الجهاز | `device_name` + `device_type` icon (phone_android/computer) |
| الحالة | `status` (pending/approved/rejected) → badge |
| موثوق | `is_trusted` → yes/no icon |
| آخر IP | `last_ip` · dir=ltr |
| آخر نشاط | `last_active_at` · dir=ltr |
| مقيّد حتى | `transactions_locked_until` · null → "—" · if future, show warning badge |

Empty devices: icon `devices` + "لا توجد أجهزة مسجّلة".

**AML Flags table:**

| Field | Data |
|---|---|
| القاعدة | `rule_name` · mono |
| الخطورة | `severity` → C-10 risk-badge |
| وقت التنبيه | `flagged_at` · dir=ltr |
| الحالة | `status` → badge |
| المراجع | `reviewed_by` → admin name + `reviewed_at` |
| ملاحظات | `reviewer_notes` · truncated with expand |

Empty AML: icon `security` + "لا توجد إشارات AML لهذا المستخدم" (positive state — shown in success tint).

**Activity Timeline (C-07):** Each `ActivityLog` entry:
- Dot color: action contains 'suspend'/'ban' → dot-danger; 'approve'/'activate' → dot-success; else → dot default.
- Label: `$log->action` · humanized in view.
- Sub-line: `$log->description` + `$log->ip_address` dir=ltr.
- Timestamp: `$log->created_at` dir=ltr.
- "قام به" (performed by): `$log->admin_id` → admin name or "النظام".

**2FA Status:** Read-only tile. `two_factor_enabled` → badge. Note: "لا يمكن تعطيل 2FA من لوحة الإدارة — يجب أن يتم من قِبل المستخدم." No action button.

---

#### TAB 6 — الإحالات (Referrals)

```
┌─────────────────────────────────────────────────────────┐
│  رمز الإحالة: [referral_code] [copy btn]               │
│  أُحيل بواسطة: [referrer name + link] OR "مستخدم جديد"│
├─────────────────────────────────────────────────────────┤
│  قائمة المُحالِين (referrals relation)                 │
│  الاسم | البريد | تاريخ الانضمام | حالة الحساب         │
└─────────────────────────────────────────────────────────┘
```

| Field | Data |
|---|---|
| رمز الإحالة | `referral_code` · mono |
| أُحيل بواسطة | `referred_by` → load `referrer` relation → name + link · null → "مستخدم جديد" |
| المُحالون | `referrals` → list with name, email, created_at, status |

Empty referrals: icon `group_add` + "لم يدعو هذا المستخدم أحداً بعد".

---

#### Show screen — Responsive

Mobile: tabs become a dropdown `<select>` with same aria-selected logic, or a horizontally scrollable tab strip (tabs preferred). Identity header stacks vertically. KPI grid 2×2. Two-column sections collapse to single column.

#### Show screen — A11y

- Tabs: `role="tablist"` on nav, `role="tab"` + `aria-selected` + `aria-controls="tab-panel-N"` on each btn, `role="tabpanel"` + `id="tab-panel-N"` + `aria-labelledby="tab-N"` on panels.
- Keyboard: Arrow keys navigate tabs; Enter/Space activates. Tab key moves to panel content.
- KYC doc preview links: `aria-label="معاينة [نوع الوثيقة]"`.
- Activity log list: `<ul aria-label="سجل النشاط">`.
- Focus trap in modals (see modal specs below).

---

### SCREEN 3 — User Edit

**Route:** `GET /admin/users/{user}/edit`

#### Layout

```
┌───────────────────────────────────────────────────┐
│ BREADCRUMB                                        │
│ H1: تعديل بيانات المستخدم · sub: [full name]     │
├───────────────────────────────────────────────────┤
│ SECTION A — المعلومات الأساسية (card)             │
│  Editable via this form:                          │
│  first_name / last_name / email / phone           │
│  (2-col grid on md+)                              │
├───────────────────────────────────────────────────┤
│ SECTION B — الإعدادات الشخصية (card)              │
│  language (select: ar/en) / timezone (select)    │
│  country_code (select)                            │
├───────────────────────────────────────────────────┤
│ SECTION C — التحكم بالحساب (C-06 Privilege Zone) │
│  READ-ONLY display of:                            │
│  status / kyc_level / kyc_status / two_factor    │
│  Each has its own [تعديل] btn → Modal            │
├───────────────────────────────────────────────────┤
│ FOOTER: [إلغاء] [حفظ التغييرات]                  │
└───────────────────────────────────────────────────┘
```

#### Section A — plain editable fields → `PUT /admin/users/{user}`

| Field | Input | Validation |
|---|---|---|
| الاسم الأول | `name="first_name"` text · label-required | required string max:255 |
| الاسم الأخير | `name="last_name"` text · label-required | required string max:255 |
| البريد الإلكتروني | `name="email"` email · dir=ltr · label-required | required email unique:users,email,{id} |
| رقم الهاتف | `name="phone"` text · dir=ltr | nullable string max:20 |

#### Section B — personal settings → same PUT

| Field | Input | Notes |
|---|---|---|
| اللغة | `name="language"` select: `ar`=العربية / `en`=English | |
| المنطقة الزمنية | `name="timezone"` select (PHP timezone list) | |
| رمز الدولة | `name="country_code"` select (ISO-3166-1 alpha-2) | |

#### Section C — Privilege Zone (C-06)

READ-ONLY display panel. NOT form inputs. Three independent "unlock" buttons:

1. **الحالة:** shows current `status` badge + [تعديل الحالة btn] → Modal A (Status Change).
2. **مستوى KYC:** shows current `kyc_level` dots + badge + [تعديل المستوى btn] → Modal D (KYC Level Change).
3. **التحقق (kyc_status):** shows `kyc_status` badge + kyc_verified_at + [تعديل حالة KYC btn] → Modal D (same, variant).

This zone has a section header with icon `admin_panel_settings` + amber warning text: "هذه الإعدادات تتطلب صلاحيات مشرف وتُسجَّل في سجل الأنشطة."

#### Edit form — states

**Default:** inputs populated from `old()` with `$user` fallback. Validation errors show inline `.input-error` + red message.

**Loading (submit):** Save button shows spinner via `setLoading(this, true)`. No UX-blocking full-page loader.

**Success:** redirect to `admin.users.show` with session flash → green toast banner at top of layout.

**Error (server validation):** `@if($errors->any())` alert card at top of form, listing all errors. Individual field `.input-error` state.

#### Responsive: 2-col grid collapses to single column at md breakpoint.

#### A11y: All labels explicitly associated with inputs via `for`/`id`. Error messages linked via `aria-describedby`. Privilege Zone: `aria-live="polite"` container to announce any status changes. Form submit button `type="submit"`, not a link.

---

### MODAL A — حالة الحساب (Status Change)

**Trigger:** [إيقاف/تفعيل] in index row, show header, or edit Section C.
**Implementation:** C-05 Modal, danger variant when suspending.

```
┌────────────────────────────────────────────────┐
│ HEADER: تعديل حالة الحساب   [X]              │
│ (danger-light bg if suspending)                │
├────────────────────────────────────────────────┤
│ BODY:                                          │
│  User: [avatar] [name] [current status badge] │
│  ─────────────────────────────────────────    │
│  الحالة الجديدة: [select]                      │
│   options: active/suspended/banned/pending     │
│  سبب التغيير:* [textarea required]            │
│  hint: سيُحفظ السبب في سجل الأنشطة.          │
├────────────────────────────────────────────────┤
│ FOOTER: [إلغاء btn-secondary] [تأكيد btn-danger/success] │
└────────────────────────────────────────────────┘
```

**Flow:** Trigger → modal opens (focus auto-moves to first focusable = select) → admin fills reason → [تأكيد] → POST `/admin/users/{user}/update-status` with `{status, reason}` → response: 200 + JSON `{message}` → close modal → toast success → update badge in DOM without reload (or full reload acceptable) → ActivityLog written server-side.

**Error:** Server 422 → show inline error in modal body. Server 500 → toast error, modal stays open.

**A11y:** `role="dialog"` `aria-modal="true"` `aria-labelledby="modal-title"`. Focus trap (Tab cycles within modal). Escape closes. `aria-live="assertive"` on error region.

---

### MODAL B — إجراء جماعي (Bulk Confirm)

**Trigger:** [تفعيل] or [إيقاف] in bulk bar.

```
┌────────────────────────────────────────────────┐
│ HEADER: [تفعيل/إيقاف] N مستخدم    [X]        │
├────────────────────────────────────────────────┤
│ BODY:                                          │
│  قائمة المستخدمين المحددين (first 5 names     │
│  + "و N آخرون" if > 5)                        │
│  ─────────────────────────                    │
│  سبب الإجراء:* [textarea required]            │
├────────────────────────────────────────────────┤
│ FOOTER: [إلغاء] [تأكيد btn-primary/danger]    │
└────────────────────────────────────────────────┘
```

**Flow:** POST `/admin/users/bulk` `{action: 'activate'|'suspend', user_ids: [uuid,...], reason: string}` → 200 `{processed: N, failed: []}` → toast "تم [تفعيل/إيقاف] N مستخدم" → reload table OR update rows in DOM → clear selection.

---

### MODAL C — تعديل الرصيد (Balance Adjust)

**Trigger:** [تعديل الرصيد] in Wallets tab.

```
┌────────────────────────────────────────────────┐
│ HEADER: تعديل رصيد المحفظة — [currency]  [X] │
├────────────────────────────────────────────────┤
│ BODY:                                          │
│  الرصيد الحالي: [balance] (read-only)          │
│  الإجراء:* [credit إضافة | debit خصم] select │
│  المبلغ:* [number input, min=0.01] dir=ltr    │
│  سبب التعديل:* [textarea required]            │
│  hint: سيُسجَّل هذا التعديل في سجل الأنشطة   │
│  ──────────────────────────────               │
│  معاينة: الرصيد الجديد = [calculated] dir=ltr │
├────────────────────────────────────────────────┤
│ FOOTER: [إلغاء] [تأكيد التعديل btn-primary]  │
└────────────────────────────────────────────────┘
```

**Flow:** Admin selects direction + enters amount → live preview of new balance updates (`x-model`/`@input`) → [تأكيد] → POST `/admin/users/{user}/balance-adjust` `{wallet_id, direction: 'credit'|'debit', amount, reason}` → 200 → close modal → toast → refresh wallet balance in DOM → ActivityLog written.

**Validation:** amount > 0 · if debit: amount ≤ current balance (server enforces; client shows preview warning). reason required.

**A11y:** Preview balance: `aria-live="polite"` so screen reader announces updated value.

---

### MODAL D — تعديل مستوى KYC (KYC Level Change)

**Trigger:** [تعديل المستوى] in KYC tab or Edit Section C.

```
┌────────────────────────────────────────────────┐
│ HEADER: تعديل مستوى KYC    [X]               │
├────────────────────────────────────────────────┤
│ BODY:                                          │
│  المستوى الحالي: [N badge]                     │
│  المستوى الجديد: [select 0/1/2/3]              │
│  حالة KYC:      [select pending/submitted/     │
│                  verified/rejected]            │
│  سبب التغيير:* [textarea required]            │
│  hint: يُسجَّل هذا الإجراء باسم المشرف        │
├────────────────────────────────────────────────┤
│ FOOTER: [إلغاء] [حفظ btn-primary]             │
└────────────────────────────────────────────────┘
```

**Flow:** POST `/admin/users/{user}/update-kyc-level` `{kyc_level, kyc_status, reason}` → controller uses `forceFill(['kyc_level'=>…, 'kyc_status'=>…, 'kyc_verified_at'=>now()])->save()` → ActivityLog → toast → update badges in DOM.

---

### MODAL E — قبول وثيقة KYC (Doc Approve)

**Trigger:** [قبول] on a KycDocument card.

```
┌────────────────────────────────────────────────┐
│ HEADER: قبول الوثيقة    [X]                   │
├────────────────────────────────────────────────┤
│ BODY:                                          │
│  وثيقة: [document_type translated]            │
│  رقم: [document_number] dir=ltr               │
│  ملاحظة: قبول الوثيقة لا يرفع مستوى KYC      │
│  تلقائياً — استخدم تعديل المستوى إذا لزم.    │
├────────────────────────────────────────────────┤
│ FOOTER: [إلغاء] [قبول btn-success]            │
└────────────────────────────────────────────────┘
```

**Flow:** POST `/admin/users/{user}/kyc/{doc}/approve` → `$doc->update(['status'=>'approved', 'verified_by'=>auth()->id(), 'verified_at'=>now()])` → ActivityLog → 200 JSON → close modal → update doc card badge in DOM.

---

### MODAL F — رفض وثيقة KYC (Doc Reject)

**Trigger:** [رفض] on a KycDocument card.

```
┌────────────────────────────────────────────────┐
│ HEADER: رفض الوثيقة     [X] (danger variant)  │
├────────────────────────────────────────────────┤
│ BODY:                                          │
│  وثيقة: [document_type]                       │
│  سبب الرفض:* [textarea required]              │
│  hint: سيُخطَر المستخدم بسبب الرفض           │
├────────────────────────────────────────────────┤
│ FOOTER: [إلغاء] [رفض btn-danger]              │
└────────────────────────────────────────────────┘
```

**Flow:** POST `/admin/users/{user}/kyc/{doc}/reject` `{reason}` → `$doc->update(['status'=>'rejected', 'rejection_reason'=>$reason, 'verified_by'=>auth()->id(), 'verified_at'=>now()])` → ActivityLog → 200 JSON → update doc card badge + show reason block.

---

### MODAL G — تسجيل كمستخدم (Impersonate)

**Trigger:** [تسجيل كمستخدم] in show screen header.

```
┌────────────────────────────────────────────────┐
│ HEADER: تسجيل الدخول كمستخدم  [X]            │
│ (warning variant — amber border)               │
├────────────────────────────────────────────────┤
│ BODY:                                          │
│  تحذير: ستتمكن من الوصول إلى حساب:           │
│  [avatar] [full name] [email]                  │
│  ─────────────────────────────────            │
│  هذه العملية تُسجَّل في سجل الأنشطة.         │
│  سبب الدخول:* [textarea required]             │
│  ──────────────────────────────────           │
│  ✓ أفهم أنني سأعمل نيابةً عن المستخدم        │
│    [checkbox required]                        │
├────────────────────────────────────────────────┤
│ FOOTER: [إلغاء] [دخول btn-warning text-black] │
└────────────────────────────────────────────────┘
```

**Flow:** POST `/admin/users/{user}/impersonate` `{reason}` → server stores in session: `session(['impersonating'=>$user->id, 'impersonator_id'=>auth()->id(), 'impersonating_reason'=>$reason])` → ActivityLog → redirect to app dashboard (or admin show) → C-08 Impersonate Banner renders on every page until exit.

**Exit:** [إنهاء جلسة التقمص] in C-08 banner → POST `/admin/users/impersonate-exit` → clear session keys → ActivityLog exit → redirect back to admin show.

---

### SLIDE-OVER QUICK-VIEW (Panel Q)

**Trigger:** [عرض سريع] in index table row.
**Load:** GET `/admin/users/{user}/quick-view` returns JSON `{user, wallets, recent_txs: last3, aml_open_count, devices_count}`.

```
┌──────────────────────────────────┐
│ HEADER: [avatar] [name] [X btn] │
│ [status badge] [kyc badge]      │
├──────────────────────────────────┤
│ BODY:                            │
│  email / phone (dir=ltr)        │
│  ─────────────                  │
│  رصيد: [per wallet]             │
│  ─────────────                  │
│  آخر 3 معاملات (compact)        │
│  ─────────────                  │
│  AML مفتوح: N                   │
│  الأجهزة: N                    │
├──────────────────────────────────┤
│ FOOTER:                          │
│  [عرض الملف btn-primary]        │
│  [تعديل btn-secondary]          │
└──────────────────────────────────┘
```

Loading state: C-09 skeletons for each section. Error: "تعذّر التحميل" + [إعادة المحاولة].
Close: [X] btn, clicking backdrop, or Escape key.
A11y: `role="dialog"` `aria-modal="true"` on panel. Focus trap. Focus returns to triggering row button on close.

---

## 4. New Backend Needs

The following must be implemented by the Laravel dev. All routes go in the existing `admin` middleware group. All mutating actions write `ActivityLog::log()`.

### 4.1 Routes to add to `routes/web.php`

```php
// Users — new
Route::get('/users/kpis', [UserController::class, 'kpis'])->name('users.kpis');
Route::get('/users/export', [UserController::class, 'export'])->name('users.export');
Route::post('/users/bulk', [UserController::class, 'bulk'])->name('users.bulk');
Route::get('/users/{user}/quick-view', [UserController::class, 'quickView'])->name('users.quick-view');
Route::post('/users/{user}/update-status', [UserController::class, 'updateStatus'])->name('users.update-status');
Route::post('/users/{user}/update-kyc-level', [UserController::class, 'updateKycLevel'])->name('users.update-kyc-level');
Route::post('/users/{user}/kyc/{doc}/approve', [UserController::class, 'approveKycDoc'])->name('users.kyc.approve');
Route::post('/users/{user}/kyc/{doc}/reject', [UserController::class, 'rejectKycDoc'])->name('users.kyc.reject');
Route::post('/users/{user}/balance-adjust', [UserController::class, 'balanceAdjust'])->name('users.balance-adjust');
Route::post('/users/{user}/impersonate', [UserController::class, 'impersonate'])->name('users.impersonate');
Route::post('/users/impersonate-exit', [UserController::class, 'impersonateExit'])->name('users.impersonate-exit');
```

Note: `kpis` and `export` and `bulk` routes must be placed BEFORE `{user}` wildcard route to avoid routing collision.

### 4.2 Controller methods — `UserController.php`

| Method | Signature | Key logic |
|---|---|---|
| `kpis` | `kpis(Request $r): JsonResponse` | Return `{total, active, pending_kyc (kyc_status=submitted), suspended, total_usd_balance}`. Cache 30s (avoid N+1 on KPI strip). |
| `index` | extend existing | Add sorts: `sort` + `dir` (allowlist: `last_name,email,kyc_level,status,last_login_at`). Add filters: `kyc_status`, `two_fa`, `aml_flagged`, `date_from`, `date_to`. Eager-load wallets for balance sum. Add `withCount('transactions')` for overview KPI. Search: extend to `phone`. |
| `export` | `export(Request $r): StreamedResponse` | Apply same filter logic as `index`. Stream CSV; do NOT paginate. Columns: uuid, full name, email, phone, status, kyc_level, kyc_status, two_factor_enabled, wallets sum USD, created_at. Filename: `users_YYYY-MM-DD.csv`. Write ActivityLog `users.export`. |
| `bulk` | `bulk(Request $r): JsonResponse` | Validate: `action` in (activate, suspend), `user_ids` array of UUIDs, `reason` string. `forceFill` status on each. Write one ActivityLog per user. Return `{processed:N, failed:[]}`. |
| `quickView` | `quickView(User $user): JsonResponse` | Load wallets, last 3 transactions, count open AML flags, count devices. Return JSON. |
| `updateStatus` | `updateStatus(Request $r, User $user): JsonResponse` | Validate `status` enum, `reason` required. `$user->forceFill(['status'=>…])->save()`. ActivityLog with old/new. Return JSON. |
| `updateKycLevel` | `updateKycLevel(Request $r, User $user): JsonResponse` | Validate `kyc_level` 0-3, `kyc_status` enum, `reason`. `$user->forceFill(['kyc_level'=>…,'kyc_status'=>…,'kyc_verified_at'=>now()])->save()`. ActivityLog. Return JSON. |
| `approveKycDoc` | `approveKycDoc(Request $r, User $user, KycDocument $doc): JsonResponse` | Authorize doc belongs to user. `$doc->update(['status'=>'approved','verified_by'=>auth()->id(),'verified_at'=>now()])`. ActivityLog. Return JSON. |
| `rejectKycDoc` | `rejectKycDoc(Request $r, User $user, KycDocument $doc): JsonResponse` | Validate `reason` required. `$doc->update(['status'=>'rejected','rejection_reason'=>$reason,'verified_by'=>auth()->id(),'verified_at'=>now()])`. ActivityLog. Return JSON. |
| `balanceAdjust` | `balanceAdjust(Request $r, User $user): JsonResponse` | Validate `wallet_id`, `direction` in (credit,debit), `amount` numeric min:0.01, `reason`. Load wallet, verify belongs to user. If debit: check balance >= amount. Credit/debit accordingly via `$wallet->increment/decrement('balance', $amount)`. ActivityLog with wallet old/new balance. Return JSON `{new_balance}`. |
| `impersonate` | `impersonate(Request $r, User $user): RedirectResponse` | Validate `reason`. Store in session. ActivityLog. Redirect to admin show (web context — not full session swap; just a session flag visible to layout). |
| `impersonateExit` | `impersonateExit(Request $r): RedirectResponse` | Clear session keys. ActivityLog. Redirect to admin show of impersonated user. |
| `show` (extend) | extend | Add eager loads: `kycDocuments`, `devices`, `activityLogs` (last 30), `amlFlags` (last 20), `referrer`, `referrals`. Add `transactions()->count()` and `transactions()->sum('amount')` as computed props passed to view. |
| `update` (fix) | fix existing | **FIX DEF-1:** `$user->update($validated)` does NOT write `kyc_level` or `status` since they are guarded. Remove `kyc_level` and `status` from `update()` validation and form — those fields are now controlled exclusively via `updateStatus` and `updateKycLevel`. Update validates only: first_name, last_name, email, phone, language, timezone, country_code. |

### 4.3 Model / migration additions

| Item | Detail |
|---|---|
| `KycDocument.status` | **Already exists** in migration (`pending/approved/rejected`) — no migration needed. Cast is `VerificationStatus::class`. Confirmed. |
| `User` — tx count KPI | No model change needed; use `$user->transactions()->count()` in controller method (not relation, avoids loading all). Pass as `$txCount` to view. |
| `User` — tx volume KPI | `$user->transactions()->sum('amount')` — same pattern. Pass as `$txVolume`. |
| `Wallet` — balance adjust | No migration needed. Use `increment()`/`decrement()`. Ensure `balance` is `decimal(15,2)` — verify in migration. |
| AML model | `AmlFlag` model — verify exists at `app/Models/AmlFlag.php`. If not: **backend must create** model + add `amlFlags()` HasMany on User. Migration exists (2026_06_24). |
| User → activityLogs relation | Verify `HasMany ActivityLog` exists on User model. If not: **backend must add** `public function activityLogs(): HasMany { return $this->hasMany(ActivityLog::class); }` |
| User → amlFlags relation | **Backend must add:** `public function amlFlags(): HasMany { return $this->hasMany(AmlFlag::class); }` |
| User → devices relation | Verify `HasMany Device` exists. |

### 4.4 Fixes required (from defects audit)

| Defect | Fix |
|---|---|
| DEF-1: `update()` silently drops guarded fields | Remove `kyc_level` + `status` from edit form + update validation. They are controlled by `updateStatus`/`updateKycLevel` only. |
| DEF-2: fake wallet/card/tx data in `@empty` blocks | Replace ALL `@empty` fake data with real empty states using `.table-empty` / descriptive message. Show.blade has 3 sites: wallets, cards, transactions. |
| DEF-3: bulk action is toast-only, export is DOM-scrape | Implement `bulk()` + `export()` server methods per §4.2. |
| DEF-4: hardcoded hex + rounded-2xl | Replace all `#6E1B2D`, `#B58A3C`, `rounded-2xl/xl/full` (except `rounded-full` for radius-full circles) with CSS custom properties. |
| DEF-5: missing sorting, KPIs, KYC doc review, etc. | All specified in this spec. |

---

## 5. Traceability Table

| Screen → Element | Data Source | Backend method |
|---|---|---|
| Index → KPI: total users | `User::count()` | `UserController::kpis()` |
| Index → KPI: active | `User::where('status','active')->count()` | `UserController::kpis()` |
| Index → KPI: pending KYC | `User::where('kyc_status','submitted')->count()` | `UserController::kpis()` |
| Index → KPI: suspended | `User::where('status','suspended')->count()` | `UserController::kpis()` |
| Index → KPI: total balance | `Wallet::where('currency','USD')->sum('balance')` | `UserController::kpis()` |
| Index → Table: status badge | `users.status` (UserStatus enum) | `UserController::index()` |
| Index → Table: kyc dots | `users.kyc_level` (0-3) | `UserController::index()` |
| Index → Table: kyc_status | `users.kyc_status` (KycStatus enum) | `UserController::index()` |
| Index → Table: balance | `wallets.balance` sum per user | `UserController::index()` with eager `wallets` |
| Index → Table: last activity | `users.last_login_at` | `UserController::index()` |
| Index → Filter: aml_flagged | `aml_flags.status = 'pending'` EXISTS | `UserController::index()` |
| Index → Export | all filtered users, all columns | `UserController::export()` |
| Index → Bulk | `users.status` forceFill | `UserController::bulk()` |
| Index → Quick-view | user + wallets + last 3 tx + aml count + device count | `UserController::quickView()` |
| Show → Identity header | `first_name`, `last_name`, `email`, `phone`, `avatar`, `status`, `kyc_level`, `kyc_status`, `two_factor_enabled`, `created_at`, `last_login_at`, `last_login_ip` | `UserController::show()` |
| Show → Overview: tx count | `transactions()->count()` | `UserController::show()` passes `$txCount` |
| Show → Overview: tx volume | `transactions()->sum('amount')` | `UserController::show()` passes `$txVolume` |
| Show → Wallets | `wallets` relation: currency, balance, is_default | `UserController::show()` |
| Show → Cards | `cards` relation: card_number_masked, expiry, card_type, balance, status | `UserController::show()` |
| Show → Transactions | `transactions` latest 10 | `UserController::show()` |
| Show → KYC docs | `kycDocuments` relation: all fields | `UserController::show()` |
| Show → KYC doc approve | `kyc_documents.status`, `verified_by`, `verified_at` | `UserController::approveKycDoc()` |
| Show → KYC doc reject | `kyc_documents.status`, `rejection_reason`, `verified_by`, `verified_at` | `UserController::rejectKycDoc()` |
| Show → KYC level control | `users.kyc_level`, `users.kyc_status` via `forceFill` | `UserController::updateKycLevel()` |
| Show → Devices | `devices` relation: all fields | `UserController::show()` |
| Show → AML flags | `amlFlags` relation: rule_name, severity, status, flagged_at, reviewer_notes, reviewed_by, reviewed_at | `UserController::show()` |
| Show → Activity log | `activityLogs` latest 30: action, description, ip_address, created_at, admin_id | `UserController::show()` |
| Show → Referrals | `referral_code`, `referred_by` → `referrer`, `referrals` relation | `UserController::show()` |
| Edit → Section A | `first_name`, `last_name`, `email`, `phone` | `UserController::update()` (fixed) |
| Edit → Section B | `language`, `timezone`, `country_code` | `UserController::update()` (extended) |
| Edit → Status change | `users.status` via `forceFill` | `UserController::updateStatus()` |
| Edit → KYC level change | `users.kyc_level`, `kyc_status` via `forceFill` | `UserController::updateKycLevel()` |
| Modal C → Balance adjust | `wallets.balance` increment/decrement | `UserController::balanceAdjust()` |
| Modal G → Impersonate | session storage + ActivityLog | `UserController::impersonate()` |
| Banner → Exit impersonate | session clear + ActivityLog | `UserController::impersonateExit()` |
| All mutations → Audit | `activity_logs` table | `ActivityLog::log()` in each method |

---

## 6. WCAG 2.2 AA Matrix

| Criterion | Component / Interaction | Implementation |
|---|---|---|
| 1.1.1 Non-text Content | Avatar initials, KPI icons, status icons | All have `aria-label` or adjacent text. Decorative icons get `aria-hidden="true"`. |
| 1.3.1 Info and Relationships | Table | `<thead>/<th scope="col">/<caption>`. DL for personal info. Tab panel associations. |
| 1.3.2 Meaningful Sequence | RTL layout | DOM order = visual order. RTL is set on `<html dir="rtl">`. Money/dates wrapped in `dir="ltr"` spans, not reversed in DOM. |
| 1.3.3 Sensory Characteristics | Status badges | Never convey info by color alone — always include text label (نشط, موقوف) and icon. |
| 1.4.1 Use of Color | Risk badges, KYC dots | Text + icon used in addition to color fill. AML severity uses text label. |
| 1.4.3 Contrast (text) | All text on backgrounds | `var(--text-primary)` #2A1A1F on `var(--bg)` #F7F3EE → 13.8:1 ✓. `var(--text-secondary)` #6E5F63 on white → 5.8:1 ✓. White text on `var(--primary)` #6E1B2D → 8.4:1 ✓. Gold `var(--accent)` #B58A3C on white → 3.2:1 — use only for decorative/large-text (≥18px bold), not body text. Warning/amber text on warning-light: must verify — use #92400e on #fef3c7 → ~7.3:1 ✓. Risk badge: white on `var(--primary)` for critical ✓. |
| 1.4.4 Resize Text | All type | Base font + relative units (rem/em). No fixed px heights that clip text. |
| 1.4.10 Reflow | Index, show, edit | Table scrolls horizontally in `.table-container` — no content loss. Cards stack at 320px. Tabs scroll horizontally. |
| 1.4.11 Non-text Contrast | Focus rings, form borders, KPI icons | `var(--border)` #E7DDD2 on white: 1.7:1 — form inputs need stronger border in focus. Focus ring: `outline: 2px solid var(--primary)` provides 3:1+ against all backgrounds ✓. |
| 1.4.13 Content on Hover | Quick-view trigger, tooltips | Any tooltip shown on hover is also shown on keyboard focus. Tooltip dismissible with Escape. |
| 2.1.1 Keyboard | All interactive elements | Full keyboard path: Tab/Shift+Tab navigation. Modals: focus trap. Slide-over: focus trap. Escape closes overlay. Tab nav: Arrow keys. Table checkboxes: Space to toggle. Sort buttons: Enter/Space. |
| 2.1.2 No Keyboard Trap | Modals, slide-over | Focus cycles within modal/panel. Escape always exits overlay and returns focus to trigger. |
| 2.4.3 Focus Order | Index → filter → table → pagination | DOM order matches visual RTL flow. Tab key follows logical reading order. |
| 2.4.4 Link Purpose | Action links, "عرض الكل" | "عرض الكل" → `aria-label="عرض كل معاملات [user name]"`. Icon-only buttons: `title` + `aria-label`. |
| 2.4.6 Headings | Page titles, section titles | H1 per page. H2 identity header. H3 card titles. Section titles use `.section-title` as visual H3-equivalent; add `role="heading" aria-level="3"` where semantic heading is not used. |
| 2.4.7 Focus Visible | All focusable elements | `outline: 2px solid var(--primary); outline-offset: 2px` on `:focus-visible`. Custom checkbox: `accent-color: var(--primary)` + focus ring. Tabs: bottom border indicator + outline. |
| 2.4.11 Focus Not Obscured (2.2) | Sticky bulk bar, sticky tab nav | Sticky elements use `z-index` hierarchy. Scroll-margin-top on anchored elements. Focused element not entirely hidden behind sticky header. |
| 2.5.3 Label in Name | All buttons with icons + text | Button label contains visible text (e.g., "قبول" button has text "قبول" + aria-label matches). |
| 3.1.1 Language of Page | `<html lang="ar" dir="rtl">` | Set on layout. EN strings within the page wrapped in `<span lang="en">` where appropriate (e.g., "KYC Level"). |
| 3.2.2 On Input | Filters, sorts | Filter form: submit on form submit button, not auto-submit on select change (prevents unexpected navigation). Sort: explicit button click. |
| 3.3.1 Error Identification | Edit form, modal forms | Field-level error messages below each input. Form-level summary at top. `aria-describedby` links input to error message. `role="alert"` on error summary. |
| 3.3.2 Labels or Instructions | All form inputs | `.label` explicit `<label for="id">`. `.label-required` includes `*` with `aria-label="مطلوب"`. Hint text via `.hint`. |
| 3.3.3 Error Suggestion | Reason textareas | If reason too short: "أدخل 10 أحرف على الأقل". Email invalid: "أدخل بريداً إلكترونياً صحيحاً". |
| 4.1.2 Name, Role, Value | Tabs, checkboxes, selects, modals | All per spec above. Custom components use ARIA roles and properties. |
| 4.1.3 Status Messages | Toasts, `aria-live` | Toast container: `role="status" aria-live="polite"` for success; `role="alert" aria-live="assertive"` for errors. KPI load errors: announced. Bulk action result: announced. |

---

*End of spec. Hand off to sofi-content-strategist for Arabic/English string finalization, then sofi-principal-system-architect for system design review.*

---

## 7. Backend Build Notes — Deltas vs Spec (for Frontend Agent)

_Written by sofi-laravel-core-dev after Gate 4 implementation. Read these before wiring the frontend._

### 7.1 ActivityLog — `log()` helper exists and is used
`app/Models/ActivityLog.php` has a static `ActivityLog::log(action, user, entity, old, new, description)` helper.
The spec said "no log() helper, write a private helper" — wrong, it exists. All controllers use it directly.
The `description` column EXISTS in the fillable array (spec said it may not exist — it does).

### 7.2 AmlFlag model was MISSING — created
`app/Models/AmlFlag.php` did not exist. Created with fillable matching the `aml_flags` migration exactly.
The `flagged_at` column EXISTS in the migration (spec noted it may be `created_at` — confirmed it is a dedicated column `flagged_at`). Frontend: use `$flag->flagged_at` not `$flag->created_at` for the flag timestamp.

### 7.3 User model relations added
`activityLogs()` and `amlFlags()` HasMany relations were missing from `User.php`. Both added.

### 7.4 DEF-1 fixed in `update()`
`update()` no longer validates or accepts `kyc_level` or `status`. Edit form Section A+B now validates only:
`first_name`, `last_name`, `email`, `phone`, `language`, `timezone`, `country_code`.
Frontend edit form must NOT include `kyc_level` or `status` inputs in the `PUT /admin/users/{user}` payload.

### 7.5 Validation error format for admin web routes
`shouldRenderJsonWhen()` in `bootstrap/app.php` scopes JSON exception rendering to `api/*` only.
Result: when admin web route validation fails (missing required field), the response is a **302 redirect back** with session errors, NOT a JSON 422. Frontend Alpine.js code calling these endpoints via `fetch()` must:
- Check for `response.redirected` or status 302 and reload the page to show flash errors, OR
- The caller should set `X-Requested-With: XMLHttpRequest` and `Accept: application/json` — but this alone is not enough because `shouldRenderJsonWhen` does NOT check `expectsJson()` for non-api routes.
- **Recommended for the frontend:** after any fetch to `/admin/users/*` endpoint, if `!response.ok && response.status !== 200`, reload the page with `window.location.reload()` to surface the session validation errors in the blade flash banner. For JSON success responses the controllers explicitly return `JsonResponse`.

### 7.6 Wallet balance column type
`Wallet.balance` is cast as `decimal:8` in the Eloquent model. The `balanceAdjust` endpoint uses `increment()`/`decrement()` at the DB level (no Eloquent cast bypass). Returns `new_balance` as a float.

### 7.7 Route names — exact list of new routes
| Route name | Method | URL |
|---|---|---|
| `admin.users.kpis` | GET | `/admin/users/kpis` |
| `admin.users.export` | GET | `/admin/users/export` |
| `admin.users.bulk` | POST | `/admin/users/bulk` |
| `admin.users.impersonate-exit` | POST | `/admin/users/impersonate-exit` |
| `admin.users.quick-view` | GET | `/admin/users/{user}/quick-view` |
| `admin.users.update-status` | POST | `/admin/users/{user}/update-status` |
| `admin.users.update-kyc-level` | POST | `/admin/users/{user}/update-kyc-level` |
| `admin.users.kyc.approve` | POST | `/admin/users/{user}/kyc/{doc}/approve` |
| `admin.users.kyc.reject` | POST | `/admin/users/{user}/kyc/{doc}/reject` |
| `admin.users.balance-adjust` | POST | `/admin/users/{user}/balance-adjust` |
| `admin.users.impersonate` | POST | `/admin/users/{user}/impersonate` |

### 7.8 Route model binding key — use UUID
The `{user}` and `{doc}` route parameters use Laravel's default binding (primary key integer). Frontend must pass the integer `id` or configure the model to bind by UUID. For now the controller uses `$user->uuid` in responses but routes resolve by PK. **Frontend: when building URLs for quick-view and other {user} routes, use `$user->id` (integer) not `$user->uuid` in Blade templates.**

### 7.9 `show()` view variables
The show view now receives `$txCount` (int) and `$txVolume` (float) as separate variables — not loaded from relations to avoid pulling all transactions into memory.

### 7.10 Suspension/activation via new audited endpoints
The old `admin.users.suspend` and `admin.users.activate` routes remain for backward compatibility with existing Blade links, but now also write ActivityLog. New privileged flows should use `admin.users.update-status` with `{status, reason}` payload.
