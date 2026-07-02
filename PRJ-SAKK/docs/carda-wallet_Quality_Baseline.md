# Quality Baseline — carda-wallet (SAKK)
**Date:** 2026-06-24
**Auditor:** Barbara Jensen (qa-sre-lead · Tier-3)
**Gate:** 5 (Integration & Quality)
**Stack:** Laravel 12 + Sanctum · Next.js 16 / TS · Flutter 3.29 / Riverpod

---

## 1. Coverage Assessment

### Backend (Laravel/PHP — Pest)
| Metric | Count |
|--------|-------|
| Source files (`app/`) | 160 |
| Test files (`tests/`) | 47 |
| Test-to-source ratio | 29.4% |
| Test suites | Unit (10 files) + Feature (37 files) |
| Security tests | 13 files (auth bypass, IDOR, webhook sig, rate limit, mass assignment) |
| E2E tests | 2 files (AuthWallet, Security) |
| Dependencies | vendor/ missing (composer install needed) |

**Coverage estimate:** ~30% line coverage (based on file ratio). PRD target = 80%. Gate 5 minimum = 90%. **FAIL.**

**Gaps:**
- No tests for: FeeService, ExchangeRateService, NotificationService, PinService, FCMService, FileValidationService, AdminNotificationService, ReferralService
- No coverage for Webhook controllers (Stripe, CCPayment)
- No coverage for Installer controller
- No coverage for Resource classes (WalletResource, CardResource, TransactionResource, UserResource)
- No coverage for Policy classes (TransactionPolicy, WalletPolicy, KycPolicy, UserPolicy)

### Frontend (Next.js/React — Playwright)
| Metric | Count |
|--------|-------|
| Source files (tsx/ts) | 47 |
| Test files | 1 (e2e/sakk-e2e.spec.ts) |
| Test-to-source ratio | 2.1% |
| Unit tests | 0 |
| E2E tests | 1 spec, 8 test cases (landing page) |

**Coverage estimate:** <5%. **FAIL.**

**Gaps:**
- Zero unit/component tests for 40+ components
- E2E covers only landing page, not auth flows, dashboard, services
- No frontend test runner configured in CI
- Framework is `next dev` only — no build verification script for tests

### Mobile (Flutter/Dart)
| Metric | Count |
|--------|-------|
| Source files (`lib/`) | 104 |
| Widget test files | 1 (`widget_test.dart`) |
| Integration test files | 1 (`app_e2e_test.dart` — 8 test cases) |
| Test-to-source ratio | <2% |
| Golden tests | 0 (golden_toolkit in dev deps but unused) |

**Coverage estimate:** <2%. **FAIL.**

**Gaps:**
- Single smoke test for app launch — no provider/state management tests
- No repository/API client unit tests
- No feature-level widget tests
- Integration tests are fragile (rely on widget type matching, no mock backend)
- Golden toolkit available but no golden tests written

### Coverage Verdict: ✗ FAIL
| Layer | Source | Tests | Coverage | Gate 5 bar |
|-------|--------|-------|----------|------------|
| Backend | 160 | 47 | ~30% | 90% ✗ |
| Frontend | 47 | 1 | ~2% | 90% ✗ |
| Mobile | 104 | 2 | ~2% | 90% ✗ |

---

## 2. Dependency & Secret Scan

### Secrets Scan
| Check | Result |
|-------|--------|
| `.env` committed? | ✗ No — only `.env.example` + `.env.vault` with placeholders |
| Real API keys/tokens? | ✗ None found — all placeholders (`__YOUR_*__`) |
| Hardcoded passwords? | ✗ None in backend/frontend production code |
| SSH keys / certs? | ✗ None found |
| `vendor/`, `node_modules/` committed? | ✗ Deleted on import (as expected) |

**Secret hygiene: PASS** — template-based env files, no real secrets in repo.

### Dependency Audit

**Backend (composer.json)**
| Package | Version | Notes |
|---------|---------|-------|
| laravel/framework | ^12.0 | Current LTS, active security support |
| laravel/sanctum | ^4.0 | Current |
| pestphp/pest | ^3.0 | Current |
| pragmarx/google2fa | ^9.0 | Well-maintained 2FA library |
| simplesoftwareio/simple-qrcode | ^4.2 | Active |
| larastan/larastan | ^3.0 | Static analysis available |

**Frontend (package.json)**
| Package | Version | Notes |
|---------|---------|-------|
| next | 16.2.9 | Latest major — heavy bundle |
| react / react-dom | 19.2.4 | Latest |
| @react-three/fiber + drei | ^9.6 / ^10.7 | **Heavy** — adds ~200KB to bundle |
| gsap | ^3.15 | Animation lib — 130KB |
| framer-motion | ^12.40 | 50KB |
| lenis | ^1.3 | Smooth scroll — 15KB |
| three | ^0.184 | 3D engine — 700KB+ |
| @radix-ui/* | various | Accessible headless UI (good) |
| @playwright/test | ^1.61 | E2E testing (good) |
| tailwindcss | ^4 | Current |

**Mobile (pubspec.yaml)**
| Package | Version | Notes |
|---------|---------|-------|
| flutter_riverpod | ^2.6 | State management |
| go_router | ^14.8 | Navigation |
| dio | ^5.4 | HTTP client |
| flutter_secure_storage | ^9.2 | Encrypted storage |
| local_auth | ^2.3 | Biometrics |
| firebase_messaging | ^15.0 | Push notifications |
| mobile_scanner | ^7.0 | QR scanning |
| nfc_manager | ^3.5 | NFC tap-to-pay |
| mocktail | ^1.0 | Test mocking |

**Vulnerability concern:** `simple-qrcode` v4.2 depends on `bacon/bacon-qr-code` which had past CVEs — verify current version (2.0.8 in lockfile). Run `composer audit` after vendor install.

**Dependency Verdict: ⚠ Moderate concern** — no known critical vulns, but heavy frontend JS deps (Three.js + GSAP + Framer Motion = 1MB+ raw) will hurt LCP/INP.

### Known Security Issues (Swarm Audit 2026-06-24)
Pre-existing 193-inspector swarm audit found 42 **adversarially confirmed** real findings:

| Severity | Count | Top Issues |
|----------|-------|------------|
| **Critical** | 6 | Sanctum tokens never expire; Full PAN+CVV in API response; TOCTOU double-spend (WalletService, CardService, GoldSavings, CCPayment, PaymentRequest); KYC bypass (documents auto-approved); Auth bypass (frontend login stubs); Impersonation token wildcard |
| **High** | 25 | Password reset token in API response; Default PIN 123456; PIN verification bypass; No idempotency on webhooks; Race on KYC limits; Hardcoded biometric token; Plaintext HTTP URL in mobile; Mass assignment on sensitive fields (kyc_status, balance, is_admin); Float money casts |
| **Medium** | 11 | Device status defaults to 'approved'; Missing ownership checks (IDOR); Path traversal in card import; PIN plaintext in migration comment; Password stored in mobile secure storage |

**Full details:** `/docs/SWARM-AUDIT.md` (614 findings total, 42 confirmed real)

---

## 3. Performance Budget

Server not running — measured by code inspection against PERF_BUDGET.md thresholds.

| Metric | Budget (Good) | Code Assessment | Verdict |
|--------|---------------|-----------------|---------|
| **LCP** | ≤ 2.5s | Next/Image with AVIF/WebP configured ✓. But: 3D canvas (Three.js) in hero + loading screen delays content paint. GSAP/Framer Motion/Lenis add JS parse time. Dynamic CreditCard3D lazy-loaded ✓. Estimated 2-4s on mid-tier device. | ⚠ Needs work |
| **INP** | ≤ 200ms | 3 JS animation frameworks (GSAP 130KB + Framer Motion 50KB + Three.js 700KB). No code-splitting visible beyond dynamic import. 3D scene interactions likely >200ms on mobile. | ✗ Likely fail |
| **CLS** | ≤ 0.1 | All images unhardcode via CSS vars via inline SVGs. No unconstrained media. Loading screen prevents layout shift. RTL layout stable. | ✓ Good |
| **TTFB** | ≤ 200ms | Laravel API with SQLite (dev) — fast. No Redis cache configured in .env.vault (commented). Production MySQL will need query optimization. | ⚠ Needs work |
| **TTI** | **< 2s** | Loading screen blocks interaction for ~2s + 3D scene boot. Three.js + GSAP parse + React hydration. Estimated 2.5-4s TTI. | ✗ Likely fail |

**Bundle size concerns (frontend):**
- `three` + `@react-three/fiber` + `drei` ≈ 700-900KB uncompressed
- `gsap` ≈ 130KB
- `framer-motion` ≈ 50KB
- `@radix-ui/*` ≈ 20KB total
- `@heroicons/react` ≈ 30KB
- Total JS: ~1-1.2MB raw → ~300-400KB gzip — still heavy for TTI <2s

**Mobile cold start:** Integration test targets <2000ms. Achievable on modern devices.

**Perf Verdict: ⚠ Likely block** — TTI and INP thresholds will likely fail on mid-tier hardware. Need bundle audit + code splitting.

---

## 4. Design Audit

### PRD Design Spec vs Implementation

**Reference:** PRD (`docs/01-PRD.md`) section 1.1 — Visual Identity

| Design Element | PRD Spec | Implementation | Verdict |
|----------------|----------|----------------|---------|
| Primary color | #6E1B2D (Damascene Burgundy) | `--color-primary: #6E1B2D` ✓ | ✓ Match |
| Accent color | #B58A3C (Antique Gold) | `--color-accent: #B58A3C` ✓ | ✓ Match |
| Background | #F7F3EE (Warm Marble) | `--color-background: #F7F3EE` ✓ | ✓ Match |
| Font (Mobile) | IBM Plex Sans Arabic | `--font-ibm-plex-sans-arabic` in layout ✓ | ✓ Match |
| Font (Admin) | Cairo | `--font-cairo` in layout ✓ | ✓ Match |
| Language | Arabic (RTL) primary, English secondary | `<html lang="ar" dir="rtl">` ✓ | ✓ Match |
| Dark mode | Not supported (light-only identity) | `ThemeMode.light` only ✓ | ✓ Match |
| Card gradients | Velvet wine, gold, warm stone | `cardGradientVisa/Mastercard/Gold/Platinum` ✓ | ✓ Match |
| Text primary | Warm dark (#2A1A1F) | `--color-text-primary: #2A1A1F` ✓ | ✓ Match |
| State colors | Green/red for success/error | `#1F9D55` success, `#C0392B` error ✓ | ✓ Match |

### Design System Consistency

**Strengths:**
- Comprehensive CSS custom property system (globals.css) — single source of truth for colors, spacing, shadows, animations
- Glass-morphism design language consistently applied across components
- No gradient text (explicitly removed per brand spec — comment says "removed per brand spec (solid colors only)")
- Damascene geometric patterns (arabesque, damask, geometric) in utility classes
- Noise overlay for texture
- Consistent spacing tokens (--spacing-section, --spacing-card, --spacing-element, --spacing-grid)
- Consistent animation patterns (ease-out-expo primary, stagger-* classes for reveals)
- RTL-aware utility classes
- 3D card component dynamically loaded (ssr: false) — good for performance
- Loading screen for initial load — branded experience

**Issues Found:**
| # | Issue | Severity | Location |
|---|-------|----------|----------|
| 1 | `text=500` check in Playwright e2e is fragile — not in source code but test concern | Low | e2e/sakk-e2e.spec.ts:96 |
| 2 | Merchant/Agent/Company login pages have **TODO stubs** — redirect without auth | **Critical** | frontend/src/app/{merchant,agent,user,company}/login/page.tsx |
| 3 | Mobile `gold_page.dart` hardcodes biometric token as string `'biometric'` | **Critical** | mobile/lib/features/gold/presentation/pages/gold_page.dart:500 |
| 4 | Mobile `auth_repository.dart` stores password in secure storage | High | mobile/lib/features/auth/data/repositories/auth_repository.dart:94-95 |
| 5 | Mobile API base URL is LAN HTTP (192.168.10.158:8000) — production URL commented | **Critical** | mobile/lib/core/constants/api_constants.dart:7 |

### Design Audit Verdict: ⚠ Needs Work
Visual identity faithfully implemented per PRD. Design system is mature and consistent. **But** critical auth bypass issues in frontend login pages and mobile biometric bypass undermine the product regardless of visual polish.

---

## 5. Overall Verdict

### ❌ BLOCKED — Cannot pass Gate 5

**Blocking reasons:**

| # | Condition | Bar | Actual | Status |
|---|-----------|-----|--------|--------|
| 1 | Code coverage >90% | 90% | ~30% backend, <5% frontend, <2% mobile | **✗ BLOCK** |
| 2 | Critical + High issues = 0 | 0 | 31 confirmed (6 critical + 25 high) | **✗ BLOCK** |
| 3 | Perf budget passes all metrics | All ✓ | TTI/INP likely fail, LCP borderline | **✗ BLOCK** |
| 4 | No secrets committed | Pass | Pass (templates only) | ✓ Pass |
| 5 | Design matches spec | All | All major elements match | ✓ Pass |

**Critical issues blocking release:**
1. **Coverage below 90% across all 3 layers** — cannot ship untested fintech code
2. **Sanctum tokens never expire** — indefinite token validity (SWARM-AUDIT confirmed)
3. **Full PAN + CVV returned in API response** — PCI-DSS violation
4. **TOCTOU double-spend races** — WalletService, CardService, GoldSavings, CCPayment, PaymentRequest
5. **KYC auto-approval** — documents approved on submission without review
6. **Frontend login stubs** — merchant/agent/company/user login pages redirect without any auth
7. **Default PIN 123456** — every new user gets same predictable PIN
8. **Password reset token in API response** — no auth required to read it
9. **No idempotency on webhooks** — double-credit/refund possible on replay
10. **Mobile HTTP plaintext API URL** — LAN IP, no TLS

### Recommended Actions (before re-audit)

**Immediate (before any frontend work):**
1. Fix 6 critical auth bypasses (login stubs + biometric + default PIN)
2. Add token expiry to Sanctum config
3. Strip PAN/CVV from API response (return last4 only)
4. Add `lockForUpdate()` to all wallet debit paths
5. Set KYC documents to `pending` on submission (remove auto-approve)
6. Remove password reset token from response body
7. Add idempotency keys to webhook handlers
8. Switch mobile API URL to HTTPS production endpoint
9. Remove password from mobile secure storage

**Short-term (coverage):**
10. Add Pest tests for uncovered services (Fee, ExchangeRate, Pin, Notification, Referral)
11. Write component tests for frontend (vitest + react-testing-library)
12. Write unit tests for mobile providers/repositories (mocktail)
13. Target 80% line coverage minimum before moving to Gate 6

**Medium-term (performance):**
14. Replace Three.js hero with CSS/Canvas alternative or defer to interaction
15. Remove GSAP/Framer Motion duplication — pick one animation framework
16. Configure proper code splitting for Next.js pages
17. Add CDN + Redis caching for API responses

---

## 6. Report Artifacts
- Swarm audit: `docs/SWARM-AUDIT.md` (614 findings, 42 confirmed real)
- CSS audit: `backend/css-audit-report.json` (if exists)
- E2E test results: `frontend/test-results/` (Playwright traces)
- Design system: `frontend/src/app/globals.css` (CSS custom properties)
- Mobile theme: `mobile/lib/core/theme/app_colors.dart`

---

*Report generated by Barbara Jensen (qa-sre-lead) · Gate 5 · 2026-06-24*
*Handoff: BLOCKED → escalate to ceo-sofi for remediation plan before proceeding to Gate 6-7.*
