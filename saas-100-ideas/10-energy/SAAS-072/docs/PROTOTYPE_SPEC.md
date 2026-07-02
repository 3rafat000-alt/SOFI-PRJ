# PROTOTYPE SPEC — RooftopSolar (SAAS-072)
> Owner: UI/UX Designer · Gate 2

## Screen: Assessment Form (maps to Journey Stage: إدخال بيانات)
- **Layout:** Stepped form (Address → Utility → Bills → Roof)
- **Components:** AddressAutocomplete, BillUpload, RoofTypeSelector, OrientationPicker, NextButton
- **States:** Empty → Welcome screen with benefits; Loading → Address validation spinner; Error → "العنوان غير معروف" → manual entry; Edge → Apartment building (no roof access) → redirect to community solar
- **Key Interaction:** Upload bill photo → OCR extracts consumption data
- **Friction Resolved:** #1 — Don't know account number? OCR reads bill photo

## Screen: ROI Report (maps to Journey Stage: حساب ROI)
- **Layout:** Card-based report with summary hero + detail sections
- **Components:** SavingsHero (annual savings, payback years, CO2 reduction), SavingsChart (year-over-year), CostBreakdown, InstallerCTA
- **States:** Empty → N/A (always has data); Loading → Skeleton chart; Error → "تعذر حساب التوفير" → retry with different data; Edge → Very low consumption → "الطاقة الشمسية قد لا تكون مجدية حالياً"
- **Key Interaction:** Scroll sections; Tap "اطلب عروض أسعار" → Installer List
- **Friction Resolved:** #2 — Conservative estimates with disclaimer + local tariff data

## Screen: Monitoring Dashboard (maps to Journey Stage: مراقبة)
- **Layout:** Real-time metrics row + daily/weekly/monthly charts
- **Components:** LiveProductionGauge, SavingsCounter, WeatherWidget, ConsumptionChart, ExportButton
- **States:** Empty → "سيتم تفعيل المراقبة بعد التركيب"; Loading → Connecting to inverter spinner; Error → "البيانات غير متوفرة حالياً" → refresh; Edge → Inverter offline >48h → alert to contact support
- **Key Interaction:** Tap day on chart → hourly breakdown
- **Friction Resolved:** #5 — Simplified dashboard for non-technical users

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Button Primary | Full-width, icon-left | hover/active/disabled/loading | bg-yellow-500 text-black |
| SavingsCard | summary, detailed | default/pressed | bg-white, shadow, 12px radius |
| Gauge | analog, digital | normal/warning/error | Circular arc with value |
| Chart | line, bar, area | loading/loaded/empty | Animated on mount |
| Stepper | 3-step, 4-step | active/complete/pending | Numbered circles |
| UploadZone | single, multi | idle/hover/uploading/success/error | Drag-drop or tap |
| BillOCRPreview | verified, manual-entry | success/error | Shows extracted values |
