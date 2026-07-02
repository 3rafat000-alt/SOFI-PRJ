# PROTOTYPE SPEC — MicroFund (SAAS-053)
> Owner: UI/UX Designer · Gate 2

## Screen: Loan Application (maps to Journey Stage: تقديم طلب قرض)
- **Layout:** Multi-step form: client info → loan details → documents → review
- **Components:** StepProgress, ClientForm, LoanAmountSlider, DocumentUpload, ReviewCard
- **States:** Empty (new form), Loading (saving), Error (validation failed), Edge (draft saved → resume)
- **Key Interaction:** Step by step wizard with validation, save draft
- **Friction Resolved:** [#3] أوراق كثيرة → تقسيم إلى خطوات بسيطة

## Screen: Client Evaluation (maps to Journey Stage: تقييم الطلب)
- **Layout:** Client profile summary + credit score + loan recommendation + approve/reject buttons
- **Components:** ClientSummaryCard, CreditScoreGauge, RiskBadge, DecisionButtons
- **States:** Empty (no client selected), Loading (scoring), Error (scoring failed), Edge (manual override → "تقييم يدوي")
- **Key Interaction:** Review data → automated scoring → decision
- **Friction Resolved:** [#1] نقص معلومات التقييم → تقارير ائتمانية آلية

## Screen: Payment Collection (maps to Journey Stage: تحصيل الأقساط)
- **Layout:** Client search + balance due + payment entry + receipt generation
- **Components:** ClientQuickSearch, BalanceCard, PaymentKeypad, ReceiptPreview
- **States:** Empty (no clients due today → "جميع العملاء مسددون"), Loading, Error (payment failed → retry), Edge (partial payment → balance remaining)
- **Key Interaction:** Search client → view balance → enter amount → generate receipt
- **Friction Resolved:** [#2] تحصيل الأقساط ميدانياً → تطبيق جوال مع إيصال فوري

## Screen: Portfolio Dashboard (maps to Journey Stage: متابعة المحفظة)
- **Layout:** PAR charts + collection rate + active loans + aging analysis
- **Components:** PARChart, CollectionRateRing, ActiveLoanCounter, AgingTable
- **States:** Loading (chart placeholders), Error, Empty (no loans yet)

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| StepProgress | 3-step, 4-step | active, completed, pending | Animated transitions |
| CreditScoreGauge | low, medium, high | 0-100 | Color-coded gauge |
| BalanceCard | current, overdue | normal, warning, critical | Red border if overdue |
| PaymentKeypad | numeric, with decimal | input, confirming, success | Receipt auto-generate |
| PARChart | 1-30, 31-90, 90+ | daily, weekly, monthly | Interactive bars |
