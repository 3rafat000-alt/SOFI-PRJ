# Carda Wallet — Admin Users Module Redesign · Shared Context Brief

> Single source of truth for the design+build squad. Read this BEFORE touching code.
> Manager (orchestrator): grounded this from live code on 2026-06-25. Verify against source; do not invent.

## Mission
Complete rewrite of the admin **Users** module — concept, UX, visuals, and operations — to a
professional, premium, smooth, exceptional-UX standard. Build ON the existing maroon+gold identity
and the established design system (below). RTL Arabic primary; bilingual-ready (EN strings allowed).

## Approved scope (operations the new module must support)
Beyond current view / edit / suspend / activate:
1. **KYC documents review** — list a user's `kycDocuments`, preview each, approve/reject with reason, set kyc_level.
2. **Activity timeline + login history + 2FA** — `ActivityLog` feed, `Device` login/IP history, 2FA status, AML/risk flags.
3. **Real bulk actions + real export** — server-side bulk activate/suspend endpoint; CSV export of the *filtered* set (not DOM scrape).
4. **Manual balance/wallet adjustment + impersonation** — audited balance adjustment; web "login-as" (impersonate exists in API only).

## Design system (USE THESE — do not hardcode hex or Tailwind rounded-* that conflict)
Defined in `resources/views/layouts/admin.blade.php` `<style>` + `:root`. CSS custom properties:
- Brand: `--primary:#6E1B2D` `--primary-dark:#4A1320` `--primary-light:#8E2A3D` · `--accent:#B58A3C` (gold) `--accent-soft:rgba(181,138,60,.14)`
- Surfaces: `--bg:#F7F3EE` (warm ivory) `--surface:#fff` `--surface-hover:#F3EDE6` `--border:#E7DDD2`
- Text: `--text-primary:#2A1A1F` `--text-secondary:#6E5F63` `--text-muted:#A99FA2`
- Status: `--success:#16a34a` `--warning:#f59e0b` `--danger:#ef4444` `--info:#2563eb` (+ `-light` variants)
- Sidebar: `--sidebar-bg:#4A1320`
- **Radius is SHARP: all `--radius-*` = 0.2rem** (except `--radius-full`). Do NOT reintroduce `rounded-2xl/xl/full`.
Ready component classes: `.card .card-header .card-body .card-footer .card-title .card-subtitle` ·
`.btn .btn-primary .btn-secondary .btn-success .btn-danger .btn-ghost .btn-sm .btn-lg .btn-icon` ·
`.input .input-error .input-group .label .label-required .hint` · `.badge .badge-primary/secondary/success/warning/danger` ·
`.table .table-container .table-empty .table-empty-icon` · `.divider .section-title`. Icons: Material Icons.

## Data model (real, buildable — field names verified)
- **User**: uuid, first_name, last_name, email, phone, email_verified_at, phone_verified_at, avatar,
  date_of_birth, gender, country_code, language, timezone, `kyc_status`∈{pending,submitted,verified,rejected},
  kyc_level (0–3), kyc_verified_at, kyc_data(json), `status`∈{active,suspended,banned,pending}, is_active,
  two_factor_enabled, referral_code, referred_by, last_login_at, last_login_ip, stripe_*, deletion_reason,
  deleted_requested_at, softDeletes. Casts: status→UserStatus enum, kyc_status→KycStatus enum.
  **🔒 SEC-003: kyc_level, kyc_status, status, two_factor_enabled, is_admin are GUARDED (not $fillable).**
  Mass-assign via update()/create() SILENTLY DROPS them — must `forceFill()->save()`.
- **Relations**: wallets, defaultWallet, usdWallet, cards, activeCards, transactions, kycDocuments,
  referrer, referrals, devices.
- **Wallet**: currency, balance, is_default. **VirtualCard**: card_number_masked, expiry, card_type(virtual/physical), balance, status(active/frozen/cancelled).
- **Transaction**: reference, type, amount, currency, status(completed/pending/failed), created_at.
- **KycDocument**: document_type, file_path, file_name, file_type, file_size, document_number, issuing_country, issue_date, expiry_date (+status — verify in migration).
- **ActivityLog**: user_id, admin_id, action, entity_type, entity_id, old_values, new_values, ip_address.
- **Device**: device_name, device_type, is_trusted, status, last_ip, last_active_at, last_used_at, approved_at.
- **aml_flags**: rule_name, severity(info/warning/high/critical), status(pending/approved/rejected/manual_review), rule_context(json), reviewer_notes, reviewed_by, reviewed_at.

## Current files
- Controller: `app/Http/Controllers/Admin/UserController.php` (index/show/edit/update/suspend/activate)
- Views: `resources/views/admin/users/{index,show,edit}.blade.php`
- Web routes: `routes/web.php` admin group → `admin.users`, `.show`, `.edit`, `.update`, `.suspend`, `.activate`
- Layout: `resources/views/layouts/admin.blade.php` (extends `layouts.admin`; sections: title, breadcrumbs, content; `@push('scripts')`)

## Defects to fix in the rewrite (Design-is-Truth audit)
1. 🔴 `update()` writes guarded kyc_level+status via mass-assign → silently dropped. Form lies. Use forceFill.
2. 🔴 show.blade `@empty` blocks render FAKE wallet/card/transaction data ($10,000 etc) — remove; real empty states.
3. 🟠 Bulk actions are fake (toast only, no backend). Export scrapes DOM (1 page only). Make both real + server-side.
4. 🟡 Hardcoded hex + `rounded-2xl/full` break the unified design language. Use tokens/classes.
5. 🟡 Missing: sorting, KPIs, KYC doc review, activity/login/2FA panel, risk flags, audited balance adjust, impersonate.

## Non-negotiables
- Migrations need rollback. Coverage <90% rejected. TTI <2s. WCAG 2.2 AA (keyboard, ARIA, contrast, focus).
- Every UI element maps to a real data field — no fabricated values, ever (fintech).
- Security/audit: every mutating admin action (suspend, KYC approve, balance adjust, impersonate) writes ActivityLog.
