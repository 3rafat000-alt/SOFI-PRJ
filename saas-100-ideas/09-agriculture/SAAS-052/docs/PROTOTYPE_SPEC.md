# PROTOTYPE SPEC — OlivePress (SAAS-052)
> Owner: UI/UX Designer · Gate 2

## Screen: Season Dashboard (maps to Journey Stage: بداية الموسم)
- **Layout:** Season status banner + stats cards (farmers, olive kg, oil L) + quick actions
- **Components:** SeasonBanner, StatsCard, QuickActionGrid, ProgressRing
- **States:** Empty (no active season → "ابدأ موسم جديد"), Loading (skeleton), Error (fetch failed), Edge (season closed → summary view, no edit)
- **Key Interaction:** Tap "بدء موسم جديد" → season creation form
- **Friction Resolved:** [#3] صعوبة تتبع المزارعين → رؤية فورية للإحصائيات

## Screen: Olive Intake (maps to Journey Stage: وزن الزيتون)
- **Layout:** Farmer selector + weight input + quality grade + submit
- **Components:** FarmerDropdown, WeightInput (keypad), QualityChip, SubmitButton
- **States:** Empty (no farmers registered → "سجل مزارع أولاً"), Loading (saving), Error (save failed), Edge (offline → saved locally, sync later)
- **Key Interaction:** Select farmer → enter weight → select grade → submit → print receipt
- **Friction Resolved:** [#1] نزاعات الحصص → تسجيل رقمي دقيق، إيصال للمزارع

## Screen: Production Batch (maps to Journey Stage: إنتاج الزيت)
- **Layout:** Batch number + delivery reference + oil output + duration
- **Components:** BatchForm, OilOutputSlider, Timer, OperatorSelect
- **States:** Empty (no active batches), Loading, Error, Edge (batch in progress → timer running)
- **Key Interaction:** Start batch → timer → enter output → complete
- **Friction Resolved:** [#2] ضياع السجلات → توثيق كل دفعة إنتاج

## Screen: Farmer Share (maps to Journey Stage: حساب الحصة)
- **Layout:** Farmer list with balance + share percentage + settlement button
- **Components:** FarmerBalanceTable, ShareProgressBar, SettleButton
- **States:** Empty (no shares calculated yet), Loading, Error, Edge (settled → "تم التسوية")

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| SeasonBanner | active, closed | normal, warning (low yield) | Tap → season detail |
| FarmerDropdown | searchable, recent-first | normal, empty, error | Search + select |
| WeightInput | kg, digital-scale | input, verified, error | Keypad entry, scale API |
| ProgressRing | percentage | 0-100% | Animated fill |
| ShareProgressBar | oil-due, received | pending, partial, complete | Visual balance |
