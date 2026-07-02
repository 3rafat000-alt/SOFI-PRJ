# /sofi-spec-review — KYC & Identity Verification System · 2026-07-02

**Classification:** Tier-A Sensitive-Data Surface (identity PII, document upload, the gate behind withdraw/payroll/cards fail-closed). Method: 4-pillar + 7 steel rules, SEV-first, read-only.

## Executive summary
**Cleanest front. No 🔴, no 🟠 — security already hardened.** The classic KYC attack surface (path traversal, arbitrary-file read, cross-user IDOR on documents) is genuinely locked. Only 🟡 hygiene remains → backlog.

## The star: `SecureFileController` (defence-in-depth)
`app/Http/Controllers/Admin/SecureFileController.php` — the single authorised egress for identity PII:
- Links carry `encrypt($relativePath)`; `decrypt` with `DecryptException → 403` (logged).
- `isSafeRelativePath` rejects `..`, null-byte, backslash, absolute paths, and scheme wrappers.
- **Prefix-allowlist** to the 6 known document dirs (kyc/, kyc-documents/, partner/merchant/agent/company-documents/) → can't be coerced into an arbitrary-file reader.
- In-controller `AdminMiddleware::authorize('secure-file.view')` re-assert (defence-in-depth beyond route middleware).
- Served from the **private disk** (never the public symlink), server-detected mime + `X-Content-Type-Options: nosniff`.
- Admin-only egress → no cross-user IDOR (admins are authorized to view all KYC).

## Pillar verdicts
- **① Data & Logic — SOUND:** upload validated `image`/`mimes:pdf,jpg,jpeg,png|max:5120` (KycController:145-190); `store('kyc/{userId}/...','private')` → random hashed filenames on private disk; client-mime inert (server-detected mime + nosniff on serve).
- **② Admin & Ops — SOUND:** `KycPolicy` all abilities `is_admin`; `approve` requires `pending`; `KycReviewRequest::authorize`=is_admin, decision validated `in:approved,rejected`, reason `required_if:rejected`; route `/kyc-verifications/{id}/review` admin-gated; `syncUserLevel` is a pure idempotent recompute (no increment, no reward → double-review can't double-grant).
- **③ UI/UX — SOUND:** mobile `kyc_repository` uses `ApiException.fromDioError` (scanner swallow-flags = false positives).
- **④ Edge/Gaps — SOUND:** fail-closed withdraw gate tested (`WithdrawKycGateTest`); policy + review + verification tested (`KycPolicyTest`, `KycReviewRequestTest`, `KycVerificationTest`).

## 🟡 Backlog (hygiene only — non-blocking)
- **K-1** · `KycService::reviewVerification:740` — no `DB::transaction`/`lockForUpdate`/status re-check. Impact benign (syncUserLevel idempotent) → redundant writes + possible duplicate notification on concurrent double-review. Fix: wrap in locked transaction + re-check PENDING + idempotent notification.
- **K-2** · `KycVerificationAgent:473` — `DB::raw("JSON_SET(...'{$uuid}'...)")` interpolates a system uuid into raw SQL + MySQL-only `JSON_SET` (fails on sqlite; agent auto-approve is secondary to the manual model). Use a binding + portable JSON update.
- **K-3** · N+1 preflags (`KycController:47` + doc controllers `->get()` without `->with()`).

## Rule scorecard
1 (422) ✅ · 2 (ApiException) ✅ · 3 (/admin + authz) ✅ policy+FormRequest+route · 4 (unique/race) ✅ review-race benign · 5 (money) ➖ N/A · 6 (contract) ✅ · 7 (Tier-A coverage) ✅ 4 test files incl fail-closed withdraw gate.

## Verdict
① sound · ② sound · ③ sound · ④ sound. No 🔴/🟠 — this front was built to standard; the file-serving seam is genuinely impenetrable.

---

## 🏰 SAKK Citadel — 6 fronts swept (2026-07-01→02)
| Front | Outcome | Key commit |
|-------|---------|-----------|
| Deposit | USDT-only lock · reference 32→64 · drop external QR · 6 tests | 580d51c / 4abd0f3 |
| Withdrawal | lock-scope refactor (optimistic-debit, no HTTP under lock) · mobile USDT | 1c1efe3 / ea47413 |
| Payroll | SOUND as-built — atomic dual-lock · DB-unique idempotency · 20 tests | (as-built) |
| Stripe Cards | real-time auth idempotency guard (C-SEV-1) | 5ae3966 |
| Exchange/Transfer | deterministic ascending-id lock (deadlock defused) · FX test suite | b2a4c26 |
| KYC | impenetrable file gate — SOUND as-built | (as-built) |

Unified steel pattern across all: `DB::transaction` + `lockForUpdate` (ascending id) + idempotency key + side-effects post-commit + no external HTTP under lock + double-sided ledger. Tooling: `sofi_automator.py` (7 steel rules, clover-authoritative rule-7) + carved `spec-review` protocol.
