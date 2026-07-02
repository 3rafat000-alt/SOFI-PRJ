# Carda-Wallet Coverage Report

**Generated:** 2026-06-24  
**Gate:** 5 — Quality  
**Author:** Automated Testing Engineer (Kwame Mensah)

---

## Summary

| Layer     | Tests | Assertions | Status |
|-----------|-------|-----------|--------|
| Backend   | 469 passed (14 skipped, 54 failed pre-existing) | 1409 | ✅ Core logic covered |
| Frontend  | 84 passed (0 failed) | — | ✅ Component + unit + E2E |
| Mobile    | 1 smoke test | — | ❌ Not started |
| **Total** | **554** | **1409+** | **Partial** |

### Pre-existing Backend Failures (54)

All are environment issues, not caused by new test code:
- **Stripe SDK missing** — `Stripe\Webhook` class not found (~30 failures)
- **SQLite `pin_code` NOT NULL** — migration constraint incompatible with SQLite
- **Decimal precision casting** — SQLite vs MySQL float differences
- **Encryption key** — APP_KEY mismatch in test env
- **Http fake clashes** — Sanctum guard config

These remain for QA/SRE lead to resolve via CI environment or mock setup.

---

## Backend Coverage

### Services (Feature Tests)

| Service | Tests | Key Coverage |
|---------|-------|-------------|
| FeeService | 16 | Create, update, delete, validation, scoping |
| ExchangeRateService | 12 | Create, update, activate/deactivate, validation |
| NotificationService | 11 | Send, sendBulk, validation |
| PinService | 3 | Hash, verify (Unit — no DB) |
| FCMService | 12 | Send to single/multiple, validate payload |
| FileValidationService | 8 | Extensions, MIME, size limits (Unit — no DB) |
| AdminNotificationService | 5 | Create, mark read, pagination |
| ReferralService | 15 | Generate code, process reward, self-referral guard, min amount |

### API Controllers

| Controller | Tests | Coverage |
|-----------|-------|---------|
| CCPaymentWebhookController | 11 | Valid signature, replay guard, invalid payload, missing fields |
| StripeIssuingWebhookController | 12 | Valid authorization, clearing, invalid event, replay |
| InstallerController | 8 | Registration, OTP verify, resend, validation |

### API Resources

| Resource | Tests | Coverage |
|----------|-------|---------|
| WalletResource | 3 | JSON structure, fields |
| CardResource | 3 | JSON structure, fields |
| TransactionResource | 4 | JSON structure, fields, related models |
| UserResource | 4 | JSON structure, fields |

### Policies

| Policy | Tests | Coverage |
|--------|-------|---------|
| TransactionPolicy | 5 | View, create, admin override |
| WalletPolicy | 5 | View, create, transfer guard |
| KycPolicy | 9 | View own, view all (admin), pending guard |
| UserPolicy | 5 | View, update, admin access |

### Factories Added (8)

| Factory | Model |
|---------|-------|
| FeeFactory | Fee |
| ExchangeRateFactory | ExchangeRate |
| ExchangeRateHistoryFactory | ExchangeRateHistory |
| AdminAlertFactory | AdminAlert |
| UserNotificationFactory | UserNotification |
| ReferralRewardFactory | ReferralReward |
| KycVerificationFactory | KycVerification |
| IntegrationFactory | Integration |

### HasFactory Trait Added (8 models)

Fee, ExchangeRate, ExchangeRateHistory, AdminAlert, UserNotification, ReferralReward, KycVerification, Integration

---

## Frontend Coverage

### Unit/Component Tests (vitest + testing-library)

| Component | Tests | Stmts | Branch | Funcs | Lines |
|-----------|-------|-------|--------|-------|-------|
| Button | 12 | 100% | 100% | 100% | 100% |
| Card | 9 | 100% | 100% | 100% | 100% |
| Nav | 11 | 89.58% | 96.87% | 73.68% | 88.09% |
| Footer | 8 | 100% | 50% | 100% | 100% |
| AuthLoginForm | 12 | 97.5% | 96.66% | 100% | 97.22% |
| AuthLayout | 5 | 100% | 100% | 100% | 100% |
| LoadingScreen | 5 | 100% | 100% | 100% | 100% |
| FeaturesSection | 3 | 100% | 50% | 100% | 100% |
| **components/auth** | **17** | **97.67%** | **96.66%** | **100%** | **97.43%** |
| **components/ui** | **21** | **100%** | **100%** | **100%** | **100%** |
| **components/landing** | **27** | **44.9%** | **61.64%** | **47.31%** | **45.78%** |

### Pure Logic (lib + stores + constants)

| Module | Tests | Coverage |
|--------|-------|---------|
| api.ts | 9 | 100% stmts, 83.33% branch |
| utils.ts (cn) | 6 | 100% |
| nav-store.ts | 4 | 100% |
| constants | — | 100% (tested via components) |

### E2E Tests (Playwright — 17 journeys)

All E2E specs in `e2e/journeys.spec.ts`:
1. Landing → scroll to features (CTA click)
2. Landing → scroll to contact via nav
3. Landing → mobile menu → navigate to services
4. Landing → register CTA → register page
5. Landing → login CTA → login page
6. Login page form renders
7. Register page form renders
8. Login form validation — empty fields
9. Login form — password toggle visibility
10. Login → forgot password link
11. Register → back to login link
12-14. Services/Blog/Contact pages load
15. Footer links navigate correctly
16. Mobile nav hamburger toggle
17. Mobile nav links accessible

---

## Gaps

### Critical
- **Flutter mobile tests** — 0% coverage. Need widget/provider/repository tests with mocktail.
- **54 pre-existing backend failures** — need environment fix (Stripe mock, SQLite migration compat)

### Component Gaps (uncovered files at 0%)
- `app/` (page routing components) — covered by E2E only
- `hooks/` — `use-gsap.ts`, `use-animations.ts`, `use-scroll.ts`
- `components/landing/sections/` — AppSection, FAQSection, etc.
- `CreditCard3D.tsx` — Three.js rendering (not testable in jsdom)
- `GeometricShapes.tsx` — gsap-dependent (partial coverage)
- `ParallaxBackground.tsx` — gsap-dependent

---

## Recommendations for QA/SRE Lead

1. **Fix CI environment** — mock Stripe SDK, use MySQL for Feature tests, set APP_KEY
2. **Flutter mobile tests** — write widget tests for top 10 screens + repository tests with mocktail
3. **Frontend hooks** — add unit tests for `use-scroll.ts`, `use-gsap.ts`
4. **Run E2E in CI** — Playwright needs dev server running on port 4449
5. **Remove `@vitest/coverage-v8` from devDeps** if not needed (added for this report)
