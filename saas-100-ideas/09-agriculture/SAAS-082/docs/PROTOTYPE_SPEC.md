# PROTOTYPE SPEC — HoneyFarm (SAAS-082)
> Owner: UI/UX Designer · Gate 2

## Screen: Apiary Dashboard (Journey Stage: Monitor Apiaries)
- **Layout:** Top stats (total hives, healthy %, production this month), map of apiaries, recent inspections feed
- **Components:** StatCard, ApiaryMap, InspectionCard, AlertBanner
- **States:** Empty (first apiary setup wizard) | Loading (skeleton) | Error (retry) | Edge (winter: low activity message)
- **Key Interaction:** Tap apiary on map → opens hive list
- **Friction Resolved:** #1 offline access cached map

## Screen: Hive Inspection (Journey Stage: Inspect Hives)
- **Layout:** Form with tabs — Health, Brood, Food Stores, Pests. Photo upload per section
- **Components:** TabBar, RatingScale, PhotoCapture, Checklist, NotesInput
- **States:** Empty (new inspection) | Loading (saving) | Error (validation) | Edge (offline → queue sync)
- **Key Interaction:** Rate queen health with emoji scale, photo evidence
- **Friction Resolved:** #2 — catch diseases early

## Screen: Treatment Calculator (Journey Stage: Treat Disease)
- **Layout:** Select treatment type → enter hive frames → auto-calculates dosage → confirm log
- **Components:** Picker, CalculatorResult, DatePicker, ConfirmButton
- **States:** Empty (no active treatments) | Loading (saving) | Error (invalid dosage) | Edge (withholding period alert)
- **Key Interaction:** Enter frames count → see exact ml dosage
- **Friction Resolved:** #4 — accurate dosage calculation

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Button | Primary (amber), Secondary, Ghost | hover/active/disabled/loading | Ripple, 8px radius |
| StatCard | Green/amber/red variants | default/hover | Subtle lift shadow |
| ApiaryMap | Map with cluster pins | loading/loaded/no-gps | Pin color by health status |
| PhotoCapture | Single, Multi, Gallery | empty/captured/uploading | Compress before upload |
| Inspection Checklist | Pass/Warning/Fail per item | unchecked/checked/na | Tap to cycle states |
| Treatment Calculator | Dosage form | idle/calculated/confirmed | Shows withholding days |
| SyncBadge | Online/Syncing/Offline | visible on mobile | Taps to show queue count |
