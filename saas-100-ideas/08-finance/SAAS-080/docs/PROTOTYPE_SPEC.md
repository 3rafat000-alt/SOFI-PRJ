# PROTOTYPE SPEC — BankMicro (SAAS-080)
> Owner: UI/UX Designer · Gate 2

## Screen: KYC Registration (maps to Journey Stage: KYC)
- **Layout:** Step-by-step identity verification (ID capture → Selfie → Address Proof → Review)
- **Components:** IDScanner (front/back), SelfieCapture, AddressProofUpload, NFCReader (for e-ID), ProgressIndicator, SubmitButton
- **States:** Empty → "ابدأ التحقق من هويتك"; Loading → Uploading documents; Error → "الصورة غير واضحة، حاول مرة أخرى" → retry; Edge → ID number already registered → "هذا الرقم مسجل مسبقاً" → login or contact support
- **Key Interaction:** Hold ID in frame → auto-capture when aligned
- **Friction Resolved:** #1 — Guided capture with AI quality check

## Screen: Account Dashboard (maps to Journey Stage: استقبال دفعات)
- **Layout:** Balance hero + quick action buttons + transaction feed
- **Components:** BalanceCard (show/hide toggle), QuickActionGrid (Transfer, Pay Bills, QR Pay, Jameya), TransactionFeed (type, amount, date, status icon), ChartSparkline
- **States:** Empty → "مرحباً! حسابك جاهز"; Loading → Balance skeleton; Error → "تعذر تحديث الرصيد" → pull to refresh; Edge → Balance near zero → "رصيدك منخفض" suggestion to deposit
- **Key Interaction:** Tap transaction → detail; Long-press balance → copy IBAN
- **Friction Resolved:** #2 — Simple, fast dashboard for non-tech users

## Screen: Jameya (Group Savings) (maps to Journey Stage: جمعية)
- **Layout:** My groups list + group detail with cycle tracker + member contributions
- **Components:** GroupCard (name, cycle progress, next payout, my contribution), CycleTimeline, MemberContributionList, PayoutCountdownWidget, InviteMemberSheet
- **States:** Empty → "لم تنضم لأي جمعية بعد" → start one; Loading → Group data loading; Error → "فشل تحميل بيانات الجمعية" → retry; Edge → Member missed payment → "تأخر العضو فلان" → reminder options
- **Key Interaction:** Tap contribute → quick payment; Swipe cycle timeline → see past/future payouts
- **Friction Resolved:** #3 — Automated Jameya with reminders and tracking

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| BalanceCard | visible, hidden | normal/negative | Tap eye icon to toggle |
| QuickAction | 4-grid, 6-grid | enabled/disabled | Ripple on press |
| TransactionRow | incoming, outgoing, pending | confirmed/pending/failed | Green/red/amber arrow |
| IDScanner | front, back, selfie | idle/capturing/verified | Auto-capture on stabilise |
| JameyaCard | active, completed | member/admin | Crown icon for admin |
| CycleTimeline | 10-cycle, 20-cycle | paid/due/future | Coloured dots |
| QrCode | generate, scan | idling/scanning/paid | Flashlight toggle |
| AgentLocator | map, list | loading/loaded | Nearest agent first |
