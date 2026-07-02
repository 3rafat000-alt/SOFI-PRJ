# 🎨 SAKK Admin Panel — Comprehensive Visual Design Audit

**Auditor:** Daniel "Dan" Kim · UI/UX Designer (Tier 1 · Design Squad)
**Date:** 2026-06-29
**Scope:** Admin Blade views + CSS + Mobile theme alignment
**Reference:** `docs/DESIGN.md` (SSOT), `sakk-tokens.css`, `admin.css`, `app_colors.dart`, `app_theme.dart`

---

## Executive Summary

**Overall Score: 5.7/10**

The admin panel suffers from **CSS token fragmentation** — three competing design systems (admin.css, admin.blade.php inline `<style>`, sakk-tokens.css) define different values for the same tokens. The inline `<style>` in `admin.blade.php` **overrides the canonical admin.css** at render time, meaning the actual UI uses a completely different color palette, font, spacing, and radii than DESIGN.md specifies.

Critical drift: Gold accent (`#B58A3C`) is **missing** from the rendered panel (replaced by burgundy everywhere). Font is **Cairo** instead of **IBM Plex Sans Arabic**. Sidebar is **light white** instead of **wine-dark**. These are foundational identity violations.

| Dimension | Score | Key Issue |
|-----------|-------|-----------|
| Color | 4/10 | Gold accent absent, semantic colors wrong (#16a34a vs #1F9D55), overlay cool-toned |
| Typography | 3/10 | Wrong font (Cairo), IBM Plex Sans Arabic loaded but unused, no tabular numerals |
| Spacing | 5/10 | 4px base system loose; inline `<style>` uses different scale; card padding inconsistent |
| Cards | 6/10 | Radius too small (14px vs 24px), hover states work, header/body padding drift |
| Buttons | 5/10 | Wrong padding proportions, no gold variant in blade, heights inconsistent |
| Tables | 7/10 | Functional, good hover, consistent structure, header style matches spec |
| Forms | 6/10 | Styling functional, no RTL direction on base input class, validation states present |
| Modals | 5/10 | Radius too small (8px vs 20-24px), wrong overlay color, z-index too low (50 vs 500) |
| Sidebar | 3/10 | Light instead of dark-wine, gold active pill replaced by burgundy, lost brand signal |
| Navbar | 6/10 | Height correct (72px vs 64px spec), search functional, glass effect absent |
| RTL | 7/10 | Mostly correct, logical properties used in inline styles, some `margin-left`/`right` in admin.css |
| Mobile Alignment | 8/10 | Flutter correctly mirrors sakk-tokens.css; IBM Plex Sans Arabic, correct radii, correct colors |
| **Overall** | **5.7/10** | Fragmented token system → identity drift |

---

## 1. Color Audit — 4/10

### Golden Rule Violation: Gold is decorative-only per DESIGN.md, but here it's MISSING entirely

| Token | DESIGN.md | admin.css | admin.blade (rendered) | Flutter | Verdict |
|-------|-----------|-----------|----------------------|---------|---------|
| Primary `#6E1B2D` | ✅ | ✅ `--wine` | ✅ `--primary` | ✅ | **PASS** |
| Primary Dark `#4A1320` | ✅ | ✅ `--wine-dark` | ✅ `--primary-dark` | ✅ | **PASS** |
| Gold / Accent `#B58A3C` | ✅ | ✅ `--gold` | ❌ `--accent: #6E1B2D` | ✅ | **FAIL** — gold replaced by burgundy |
| Gold Bright `#C9A24B` | ✅ | ✅ `--gold-light` | ❌ missing | ✅ | **FAIL** |
| Background `#F7F3EE` | ✅ | ✅ `--marble` | ❌ `#F6F6F5` | ✅ `#F7F3EE` | **FAIL** — wrong hex |
| Surface `#FFFFFF` | ✅ | ✅ | ✅ | ✅ | **PASS** |
| Text Primary `#2A1A1F` | ✅ | ✅ `--ink` | ❌ `#18181B` | ✅ | **FAIL** — cooler, harder |
| Text Secondary `#6E5F63` | ✅ | ✅ `--ink2` | ❌ `#57575C` | ✅ | **FAIL** |
| Success `#1F9D55` | ✅ | ✅ | ❌ `#16a34a` | ✅ | **FAIL** |
| Error `#C0392B` | ✅ | ✅ | ❌ `#ef4444` | ✅ | **FAIL** |
| Warning `#B58A3C` | ✅ | ✅ `--warning: #D97706` | ❌ `#f59e0b` | ✅ | **FAIL** — 3 different values |

**Impact:** The rendered admin panel has no gold accent. The sidebar active indicator, CTAs, badges, and gold pill are all burgundy instead of gold. This removes the visual hierarchy between primary (burgundy) and accent (gold).

### Sidebar Color Cliff

| Token | DESIGN.md | admin.css | admin.blade (rendered) | Verdict |
|-------|-----------|-----------|----------------------|---------|
| Sidebar BG | `#4A1320` wine-dark | ✅ `--sidebar-bg: #4A1320` | ❌ **`--sidebar-bg: #ffffff`** | **FAIL** |
| Sidebar text | `rgba(247,243,238,0.60)` | ✅ | ❌ `#57575C` | **FAIL** |
| Active bg | `rgba(181,138,60,0.18)` gold-tinted | ✅ | ❌ `rgba(110,27,45,0.07)` burgundy | **FAIL** |
| Active text | `#C9A24B` gold | ✅ | ❌ `var(--accent)` = `#6E1B2D` | **FAIL** |

**Before (spec):** Dark wine sidebar (#4A1320) with gold active pills — signals "admin power zone"
**After (current):** Pure white sidebar with burgundy active tint — indistinguishable from surface. The only dark surface in the product is now light.

### Modal Overlay
- DESIGN.md: `rgba(42,26,31,0.48)` warm burgundy-tinted, `backdrop-filter: blur(4px)`
- admin.blade (line 534): `rgba(10,11,13,0.55)` cool gray, `backdrop-filter: blur(6px)`
- **FAIL** — wrong color temperature

---

## 2. Typography Audit — 3/10

### Font Stack
| Source | Font | Verdict |
|--------|------|---------|
| DESIGN.md | **IBM Plex Sans Arabic** | ✅ Specification |
| sakk-tokens.css | IBM Plex Sans Arabic (self-hosted woff2) | ✅ Loaded |
| admin.css | IBM Plex Sans Arabic + Tajawal + Cairo fallback | ✅ Correct |
| **admin.blade `body` (rendered)** | **Cairo** from CDN | ❌ **FAIL** |
| **login.blade `body` (rendered)** | **Cairo** from CDN | ❌ **FAIL** |

**Root Cause:** `admin.blade.php` line 112-113 sets `font-family: 'Cairo', sans-serif;` which overrides the external `admin.css` declaration of IBM Plex Sans Arabic. The inline `<style>` has specificity precedence.

### CDN Dependency
- IBM Plex Sans Arabic is self-hosted at `/sakk-assets/fonts/` (offline-first ✅)
- Cairo is loaded from `cdn.jsdelivr.net` (breaks offline ❌)
- **Performance:** Extra DNS lookup + SSL + download for a font that shouldn't be used

### Font Weights
- DESIGN.md: 300 removed per a11y audit; 400/500/600/700 used
- sakk-tokens.css: Loads 300/400/500/600/700 (300 still loaded but unused ✅)
- Cairo loaded at 5 weights (400, 500, 600, 700, 800) — plus 900 on login page

### Tabular Numerals
- DESIGN.md line 181: **Mandatory** `font-variant-numeric: tabular-nums` on all money/figures
- admin.blade stat values (line 357): `letter-spacing: -0.02em` but **no tabular-nums**
- dashboard balances: no `tnum` class applied
- **FAIL** — all financial figures should use `tabular-nums` for alignment

### Type Scale
| Token | DESIGN.md | admin.blade (rendered) | Verdict |
|-------|-----------|----------------------|---------|
| `--fs-xs` | 12px | `--font-size-xs: 0.75rem` (12px) | ✅ |
| `--fs-sm` | 14px | `--font-size-sm: 0.875rem` (14px) | ✅ |
| `--fs-base` | 16px | `--font-size-base: 1rem` (16px) | ✅ |
| `--fs-lg` | 18px | `--font-size-lg: 1.125rem` (18px) | ✅ |
| `--fs-xl` | 20px | `--font-size-xl: 1.25rem` (20px) | ✅ |
| `--fs-2xl` | 24px | `--font-size-2xl: 1.5rem` (24px) | ✅ |
| `--fs-3xl` | 32px | ❌ not defined in blade | ❌ |
| `--fs-4xl`+ | 40-72px | ❌ not defined | ❌ (admin may not need these) |

Type scale tokens match for available sizes. Scale incomplete for hero/large needs.

---

## 3. Spacing Audit — 5/10

### Base System
- DESIGN.md: 4px base system (`--sp-1: 4px`, `--sp-2: 8px`, etc.)
- admin.css: ✅ Mirrors 4px scale (`--sp-1: 0.25rem`, `--sp-6: 1.5rem`, etc.)
- admin.blade (`<style>`): ❌ **Different scale** — only 6 values at 4px intervals but with different names

| DESIGN.md | admin.blade | Match |
|-----------|-------------|-------|
| `--sp-1` 4px | `--spacing-xs: 0.25rem` | ✅ |
| `--sp-2` 8px | `--spacing-sm: 0.5rem` | ✅ |
| `--sp-4` 16px | `--spacing-md: 1rem` | ✅ |
| `--sp-6` 24px | `--spacing-lg: 1.5rem` | ✅ |
| `--sp-8` 32px | `--spacing-xl: 2rem` | ❌ 32px vs 32px ✅ actually matches but named differently |
| `--sp-12` 48px | `--spacing-2xl: 3rem` | ✅ |

Spacing scale values actually match despite different naming. However, the blade's scale is truncated (only 6 values vs 10+ in spec).

### Card Padding Inconsistency
- DESIGN.md: Card body `padding: 1.5rem` (24px)
- admin.blade (line 298): `.card-body { padding: 1rem }` (16px) — **too tight**
- admin.css (line 833): `.card-body { padding: var(--sp-6) }` = 24px ✅
- **Blade overrides admin.css** → cards feel cramped

**Before (spec):** Card body 24px padding, generous breathing room
**After (current):** Card body 16px padding, content touches edges

### Section Padding
- `main-content`: admin.blade uses inline `p-6` (Tailwind 1.5rem) ✅
- admin.css line 729: `.main-content { padding: var(--sp-6) }` (1.5rem) ✅
- DESIGN.md: `--sp-6` = 24px ✅

---

## 4. Component Audit

### 4.1 Cards — 6/10

| Property | DESIGN.md | admin.blade (rendered) | Verdict |
|----------|-----------|----------------------|---------|
| Border radius | 24px (`--r-2xl`) | `var(--radius-lg)` = **14px** | ❌ **Wrong radius** |
| Background | white | white | ✅ |
| Border | `#E8DED6` | `var(--border)` = `#EAE8E6` | ❌ Slight drift |
| Box-shadow | `0 1px 3px rgba(42,26,31,0.04)` | `var(--shadow-sm)` cool-toned | ❌ Wrong shadow color |
| Hover | shadow-md | ✅ shadow-md | ✅ |
| Padding body | 24px | **16px** | ❌ Too tight |
| Padding header | 16px inline 24px sides | **16px uniform** | ❌ Wrong |
| Padding footer | 16px inline 24px sides | **16px uniform** | ❌ Wrong |

**Card radius comparison:**
- DESIGN.md: `rounded-2xl` = 24px (soft, premium)
- admin.blade: `.card { border-radius: var(--radius-lg) }` = 14px (medium-soft)
- admin.blade also redefines `rounded-2xl` = 20px via CSS override (line 258)
- **Visual result:** Cards look noticeably less premium than designed

### 4.2 Stat Cards — 5/10

| Property | DESIGN.md | admin.blade | Verdict |
|----------|-----------|-------------|---------|
| Radius | 24px | 14px (`--radius-lg`) | ❌ |
| Padding | 20px (1.25rem) | 20px (1.25rem) ✅ | ✅ |
| Value font-size | 1.875rem (30px) | **1.75rem (28px)** | ❌ Smaller |
| Value weight | 700 | **800** | ❌ Wrong weight |
| Indicator width | 3px right (RTL) | 3px **left** (`inset-inline-start`) | ✅ Correct for RTL |
| Indicator color | Gold `#B58A3C` | **Primary `#6E1B2D`** | ❌ Wrong color |

### 4.3 Buttons — 5/10

| Property | DESIGN.md | admin.blade | Verdict |
|----------|-----------|-------------|---------|
| Default padding | `0.5rem 1.125rem` | `0.75rem 0.75rem` | ❌ **12px vertical / 12px horizontal** (too chunky, too narrow) |
| Font size | 14px | 14px | ✅ |
| Font weight | 700 | 700 | ✅ |
| Radius | 12px | `--radius-md` = 10px | ❌ Smaller |
| Primary hover | bg `#4A1320`, translateY(-1) | ✅ | ✅ |
| Gold variant | ✅ listed | ❌ **Missing** | ❌ No gold CTA button class |
| Ghost variant | ✅ | ✅ | ✅ |
| Focus-visible | outline 2px wine | box-shadow ring | ⚠️ Different but acceptable |
| Loading state | spinner, opacity 0.75 | ✅ | ✅ |

**Before (spec):** Button `0.5rem 1.125rem` — horizontal emphasis, comfortable width
**After (current):** Button `0.75rem 0.75rem` — vertical chunkiness, too square

### 4.4 Tables — 7/10

| Property | DESIGN.md | admin.blade | Verdict |
|----------|-----------|-------------|---------|
| Wrapper | border `#E8DED6`, radius 24px | border `var(--border)`, radius 14px | ❌ Wrong radius |
| Header th padding | `0.75rem 1.25rem` | `0.875rem 1.25rem` | ✅ Close |
| Header font | 12px, 700, `#A0909A` | 12px, 800, `var(--text-muted)` | ⚠️ Weight 800 vs 700, color drift |
| Header bg | `#F2ECE5` | `var(--surface-hover)` = `#F4F4F3` | ❌ Wrong bg |
| Body td padding | `0.875rem 1.25rem` | `0.9rem 1.25rem` | ✅ Close |
| Body font | 14px, `#6E5F63`, 500 | 14px, `var(--text-secondary)` | ⚠️ Color drifts with text-secondary |
| Row hover | `#F2ECE5` | `var(--surface-hover)` = `#F4F4F3` | ❌ Wrong color |
| Empty state | padding `3rem 1.5rem` | padding 3rem | ✅ |
| Sortable th hover | `#6E1B2D` | `var(--wine)` | ⚠️ Works if --wine is correct |

Tables are relatively well-structured but suffer from the general color drift. The table radius being 14px vs 24px makes tables feel sharper than intended.

### 4.5 Forms — 6/10

| Property | DESIGN.md | admin.blade | Verdict |
|----------|-----------|-------------|---------|
| Input padding | `0.625rem 0.875rem` | `0.75rem 0.75rem` | ❌ Chunkier |
| Input font | 14px, 500 | 14px, 500 | ✅ |
| Input bg | `#F2ECE5` | `var(--surface)` = **white** | ❌ **Wrong** |
| Input border | `#E8DED6` | `var(--border)` = `#EAE8E6` | ⚠️ Slight drift |
| Focus state | bg white, border `#6E1B2D`, ring | ✅ | ✅ |
| Error state | border `#C0392B`, ring | border `var(--danger)` = `#ef4444` | ❌ Wrong red |
| Label | 14px, 700, `#2A1A1F` | 14px, 700, `var(--text-primary)` | ⚠️ Color drifts |
| Helper text | 12px, `#6E5F63` | `--font-size-xs`, `--text-muted` | ❌ Wrong color |
| Error message | 12px, `#C0392B`, 600, with icon | 12px, `--danger`, 600 | ❌ Wrong color |
| RTL direction | `direction: rtl` | **Not set** on base `.input` | ❌ **Missing** |

**Critical:** Input background defaults to pure white (`var(--surface)`) instead of `#F2ECE5` (warm fill). This reduces the visual distinction between input fields and their containers.

### 4.6 Modals — 5/10

| Property | DESIGN.md | admin.blade | Verdict |
|----------|-----------|-------------|---------|
| Backdrop | `rgba(42,26,31,0.48)`, blur(4px) | `rgba(10,11,13,0.55)`, blur(6px) | ❌ Wrong color/temp |
| Modal radius | 24px (`--r-2xl`) | **8px** (`--radius-sm`) | ❌ **FAIL** |
| Max-width | `max-width: 28rem` (for small) | max-width 28rem | ✅ |
| Title font | 18px, 700 | `text-base` = 16px, 800 | ❌ Smaller |
| Body padding | 24px | 16px | ❌ Too tight |
| Footer bg | `#F2ECE5` | `var(--surface-hover)` | ⚠️ Drifts |
| Close button | icon button | btn-ghost btn-icon | ✅ |
| Z-index | 500 | 50 | ❌ Too low |
| Confirm variant header | bg `#FBEAE8`, title `#922B21` | ✅ on KYC reject modals | ✅ |

**Modal radius comparison:**
- DESIGN.md: 24px — soft, premium dialog feel
- admin.blade: `border-radius: var(--radius-sm)` = 8px — sharp, feels basic
- Inline confirm modal (line 1036): correctly uses `border-radius: var(--radius-xl)` — **inconsistent within same layout**

---

## 5. Navigation Audit

### 5.1 Sidebar — 3/10

**The biggest design deviation in the entire panel.**

| Property | DESIGN.md | admin.css | admin.blade (rendered) | Verdict |
|----------|-----------|-----------|----------------------|---------|
| Background | `#4A1320` wine-dark | ✅ `--sidebar-bg: #4A1320` | ❌ **white `#ffffff`** | **FAIL** |
| Text color | `rgba(247,243,238,0.60)` | ✅ | ❌ `#57575C` | **FAIL** |
| Text strong | `#F7F3EE` marble | ✅ | ❌ `#18181B` | **FAIL** |
| Active bg | `rgba(181,138,60,0.18)` gold | ✅ | ❌ `rgba(110,27,45,0.07)` burgundy | **FAIL** |
| Active text | `#C9A24B` gold | ✅ | ❌ `#18181B` | **FAIL** |
| Active indicator | 3px gold bar right (RTL) | ✅ `inset-inline-end: 0` | ❌ **left** side, burgundy | **FAIL** |
| Hover bg | `rgba(247,243,238,0.07)` | ✅ | ❌ `#F4F4F3` | **FAIL** |
| Nav label text | uppercase, muted | ✅ | ✅ | ✅ |

**The sidebar reads as a regular content panel, not as a distinct navigation zone.** This violates DESIGN.md §2.8: "The wine-dark sidebar is the ONLY dark surface in the product. It signals 'admin power' and never carries user-facing content."

**Before (spec):** Dark wine sidebar with gold active indicators — clear visual separation, premium feel
**After (current):** White sidebar with subtle burgundy active — blends with main content, no hierarchy

### Collapsed State
- admin.blade: `w-[272px]` → `w-[80px]` on collapse
- DESIGN.md: `--sidebar-w: 272px`, collapsed 72px
- ✅ Widths match

### 5.2 Navbar / Topbar — 6/10

| Property | DESIGN.md | admin.blade | Verdict |
|----------|-----------|-------------|---------|
| Height | 64px | **72px** | ❌ 8px too tall |
| Background | white | white | ✅ |
| Border-bottom | `1px solid #E8DED6` | `1px solid var(--border)` | ⚠️ Slight drift |
| Glass effect | glass bg (blur) | solid white | ❌ Missing glass |
| Search width | 256px default → 320px focus | max-width 480px | ⚠️ Different but OK |
| Search border | wine on focus | accent (burgundy) | ✅ |
| Icon buttons | 38×38px, radius 12px | 40×40px, radius md | ⚠️ Slightly different |
| Avatar | 36px | 38px | ⚠️ Slight drift |

**Search bar radius:**
- admin.css (line 669): `border-radius: var(--radius-md)` = 12px ✅
- admin.blade (line 617-621): no explicit radius, inherits from card-like styling

---

## 6. RTL Audit — 7/10

### Strengths
- All layouts use `dir="rtl"` ✅
- Logical properties used extensively (`inset-inline-start/end`, `padding-inline-start/end`) ✅
- Breadcrumbs, sidebar, modals all RTL-aware ✅
- Avatar stack uses `row-reverse` ✅
- Toast animation RTL-correct (slides out to left = viewport exit) ✅

### Issues
- admin.blade `.input` base class **does not set `direction: rtl`** — relies on global `body` direction
- Some hardcoded `left`/`right` in admin.css (e.g., `.slide-over` line 815 uses `left: 0`)
- admin.css `.slide-over` comment says "RTL: logical-start = right side" but then uses physical `left: 0` — confusing
- `margin-left` used in admin.blade (line 519: `margin-left: 0.25rem`) instead of `margin-inline-start`
- `border-left`/`border-right` used in input-group styling instead of logical properties

---

## 7. Mobile Alignment — 8/10

### app_colors.dart vs sakk-tokens.css
| Token | Flutter | CSS tokens | Match |
|-------|---------|------------|-------|
| Primary `#6E1B2D` | ✅ | ✅ | ✅ |
| Primary Dark `#4A1320` | ✅ | ✅ | ✅ |
| Background `#F7F3EE` | ✅ | ✅ | ✅ |
| Text Primary `#2A1A1F` | ✅ | ✅ | ✅ |
| Text Secondary `#6E5F63` | ✅ | ✅ | ✅ |
| Gold `#B58A3C` | ✅ | ✅ | ✅ **Better than admin!** |
| Success `#1F9D55` | ✅ | ✅ | ✅ |
| Error `#C0392B` | ✅ | ✅ | ✅ |

**The mobile Flutter app is more aligned with the design system than the admin panel.**

### app_theme.dart (Flutter) vs DESIGN.md
| Token | Flutter | DESIGN.md | Match |
|-------|---------|-----------|-------|
| Font | IBM Plex Sans Arabic | IBM Plex Sans Arabic | ✅ |
| Card radius | 16px | 16px (`--r-lg`) | ✅ |
| Button radius | 12px | 12px (`--r-md`) | ✅ |
| Input radius | 12px | 12px | ✅ |
| Dialog radius | 20px | 20px (`--r-xl`) | ✅ |
| Input bg | `#F2ECE5` | `#F2ECE5` | ✅ |
| Bottom nav | fixed, 12px labels | fixed, 12px labels | ✅ |
| AppBar elevation | 0 | 0 | ✅ |

**Flutter deviates where it copies DESIGN.md correctly but the admin panel doesn't.**

---

## 8. Priority Findings

### P0 — Must Fix (Identity)

**1. Font: Cairo → IBM Plex Sans Arabic (ALL rendered panels)**
- Files: `admin.blade.php` line 113, `login.blade.php` line 28
- Fix: Change `font-family: 'Cairo'` → `font-family: 'IBM Plex Sans Arabic'` in `<style>` blocks
- Remove CDN Cairo imports (lines 13-17 in admin.blade)
- IBM Plex Sans is already self-hosted at `/sakk-assets/fonts/` — just reference it

**2. Sidebar: White → Wine-Dark (#4A1320)**
- File: `admin.blade.php` lines 1073-1074, styles section
- Fix: Set `--sidebar-bg: #4A1320` in the `:root` block and remove the overriding `--sidebar-bg: #ffffff`
- Restore gold active pill: `.sidebar-link.active { background: rgba(181,138,60,0.18); color: #C9A24B; }`

**3. Gold Accent: Restore `#B58A3C` / `--gold` throughout**
- File: `admin.blade.php` `<style>` block
- Fix: Add `--gold: #B58A3C` tokens. Replace `--accent: #6E1B2D` with proper accent values
- `--accent` should map to gold, not burgundy. Create separate token names to avoid confusion.

### P1 — High Impact

**4. Card/Modal Radius: 14px → 24px**
- File: `admin.blade.php` `<style>` block
- Fix: Change `--radius-lg: 0.875rem` → `--radius-lg: 1rem` (16px) and `--radius-2xl: 1.25rem` → `--radius-2xl: 1.5rem` (24px)
- `.card { border-radius: var(--radius-2xl) }` = 24px per spec
- `.modal { border-radius: var(--radius-xl) }` = 20px per spec

**5. Semantic Colors: Sync to DESIGN.md values**
| Token | Current (wrong) | Spec (correct) |
|-------|----------------|----------------|
| `--success` | `#16a34a` | `#1F9D55` |
| `--danger` | `#ef4444` | `#C0392B` |
| `--warning` | `#f59e0b` | `#B58A3C` |
| `--bg` | `#F6F6F5` | `#F7F3EE` |
| `--text-primary` | `#18181B` | `#2A1A1F` |
| `--text-secondary` | `#57575C` | `#6E5F63` |

**6. Tabular Numerals on all money/balance elements**
- Add `font-variant-numeric: tabular-nums` to stat values, table amounts, KPI values
- Either restore the `.tnum` / `.money` utility classes from `sakk-tokens.css` or add `font-feature-settings: "tnum" 1` to money elements

### P2 — Medium Impact

**7. Input Background: white → `#F2ECE5`**
- admin.blade line 202: Change `.input { background: var(--surface) }` → `background: var(--input-bg, #F2ECE5)`
- Consider adding `--input-bg: #F2ECE5` token

**8. Button Padding: `0.75rem 0.75rem` → `0.5rem 1.125rem`**
- admin.blade line 143: `.btn { padding: 0.5rem 1.125rem }`
- This gives buttons the correct horizontal emphasis

**9. Card Body Padding: 16px → 24px**
- admin.blade line 298: `.card-body { padding: 1.5rem }`
- Also fix card-header and card-footer to match spec

**10. Modal Overlay Color: cool gray → warm burgundy**
- admin.blade line 534: `background: rgba(42,26,31,0.48)` with `backdrop-filter: blur(4px)`
- Change z-index from 50 to 500

**11. Reduce CSS fragmentation: Merge admin.blade `<style>` into admin.css**
- The inline `<style>` creates a parallel design system
- Either extract to admin.css OR ensure inline style references the same token names and values
- Remove duplicate declarations

**12. Add `direction: rtl` to base `.input` class**
- admin.blade: Add `direction: rtl` to `.input` styles

### P3 — Polish

**13. Replace physical properties with logical ones in admin.css**
- `left: 0` → `inset-inline-start: 0` in `.slide-over`
- `margin-left` → `margin-inline-start` in tab/input-group CSS

**14. Add `prefers-reduced-motion: reduce` to admin.blade `<style>`**
- Mirror admin.css lines 996-1004

**15. Navbar height: 72px → 64px**
- Align with DESIGN.md `--navbar-h: 64px`

**16. Remove unused Cairo CDN imports after font fix**
- admin.blade lines 13-17: 6 CSS imports for Cairo
- login.blade lines 10-15: 7 CSS imports for Cairo (including 900 weight)
- Replace with the already-loaded IBM Plex Sans Arabic from sakk-tokens.css

**17. Stat card indicator: burgundy → gold**
- admin.blade line 337: `.stat-card::before { background: var(--primary) }` → `background: var(--gold, #B58A3C)`
- Match DESIGN.md stat card spec (gold right-edge indicator)

---

## 9. Fix Recommendations Summary

### Quick Wins (1-2 hours)
1. Change body font from Cairo → IBM Plex Sans Arabic (edit 2 lines)
2. Restore gold accent token `--gold: #B58A3C`
3. Fix semantic colors (6 hex values)
4. Add `direction: rtl` to input base class

### Structural Fixes (4-6 hours)
5. Merge admin.blade `<style>` tokens → unified with admin.css
6. Refactor sidebar from white → wine-dark with gold active pill
7. Fix card/modal radius values
8. Add tabular numerals to financial elements

### Design System Unification (8-12 hours)
9. Eliminate the admin.blade inline `<style>` as a source of truth — reference admin.css or sakk-tokens.css
10. Create a single admin theme CSS file that imports tokens from sakk-tokens.css
11. Audit all 70+ Blade files for inline style inconsistencies
12. Add visual regression tests (Percy/Chromatic) to prevent token drift

---

## Appendix: File-by-File Token Drift Map

| Token | sakk-tokens.css | admin.css | admin.blade `<style>` | Winning value |
|-------|----------------|-----------|----------------------|---------------|
| Primary | `--wine: #6E1B2D` | `--wine: #6E1B2D` | `--primary: #6E1B2D` | #6E1B2D ✅ |
| Primary Dark | `--wine-dark: #4A1320` | `--wine-dark: #4A1320` | `--primary-dark: #571421` | #4A1320 ✅ |
| Background | `--marble: #F7F3EE` | `--bg: #F7F3EE` | `--bg: #F6F6F5` | #F7F3EE ✅ |
| Gold | `--gold: #B58A3C` | `--gold: #B58A3C` | ❌ missing | #B58A3C ✅ |
| Text Primary | `--ink: #2A1A1F` | `--ink: #2A1A1F` | `--text-primary: #18181B` | #2A1A1F ✅ |
| Error | `--error: #C0392B` | `--danger: #C0392B` | `--danger: #ef4444` | #C0392B ✅ |
| Success | `--success: #1F9D55` | `--success: #1F9D55` | `--success: #16a34a` | #1F9D55 ✅ |
| Font | IBM Plex Sans Arabic | IBM Plex Sans Arabic | Cairo ❌ | IBM Plex Sans Arabic ✅ |
| Card radius | `--r-xl: 24px` | `--radius-2xl: 24px` | `--radius-lg: 14px` ❌ | 24px ✅ |
| Modal radius | `--r-xl: 24px` | `--radius-xl: 20px` | `--radius-sm: 8px` ❌ | 20-24px ✅ |
| Input bg | — | `--input-bg: #F2ECE5` | white ❌ | #F2ECE5 ✅ |
| Sidebar bg | — | `--sidebar-bg: #4A1320` | white ❌ | #4A1320 ✅ |
| Overlay color | — | `rgba(42,26,31,0.48)` | `rgba(10,11,13,0.55)` ❌ | warm burgundy ✅ |
| Spacing scale | 4px-based | 4px-based | 4px-based (diff names) | Aligned ✅ |

---

*Audit complete. All findings read-only — no files modified.*
