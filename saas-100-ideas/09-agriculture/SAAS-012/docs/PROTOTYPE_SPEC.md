# PROTOTYPE SPEC — FarmTech (SAAS-012)
> Owner: UI/UX Designer · Gate 2

## Screen: Farm Dashboard (Stage: لوحة المزرعة)
- **Layout:** Header (farm name, weather widget, date) + stat cards row (total area, active crops, water usage, pending tasks) + map view + recent activity feed
- **Components:** StatCard (area m², crop count, water m³, tasks), MiniMap (satellite view with parcels), ActivityFeed, WeatherWidget (API-powered 7-day), CropStatusGrid
- **States:** Empty (first farm setup CTA) | Loading (skeleton) | Partial data (no weather) | Error
- **Key Interaction:** Click parcel on map → opens parcel detail; weather click → expands forecast
- **Friction Resolved:** [#5] — mobile-first responsive, large touch targets

## Screen: Irrigation Scheduler (Stage: جدولة الري)
- **Layout:** Top: selected parcel name + crop info; Main: schedule table (days × parcels); Bottom: add new schedule form
- **Components:** ScheduleGrid (time slots × parcels), CropSelect, DurationInput (minutes), FrequencySelect (daily/alternate/custom), WaterUsageChart (projected vs actual), WeatherIntegrationToggle
- **States:** Empty (no schedules) | Loading | Auto-schedule generated | Conflict (overlap with another parcel)
- **Key Interaction:** Drag to set duration on grid; toggle auto-schedule uses AI weather integration
- **Friction Resolved:** [#1] — one-click auto-schedule based on crop type, [#3] — weather API toggle

## Screen: Production Log (Stage: تسجيل الإنتاج)
- **Layout:** Top: date picker + parcel selector; Main: harvest log table (date, parcel, crop, quantity, quality grade, worker); Bottom: quick-add form
- **Components:** DatePicker, ParcelSelect, CropAutoComplete, QuantityInput (kg/tons/units), QualityBadge (A/B/C), WorkerSelect, SubmitButton
- **States:** Empty (no harvests) | Loading | Submission (auto-saves draft) | Error (duplicate entry detected)
- **Key Interaction:** Quick-add from bottom without page reload; batch entry for multiple parcels same day
- **Friction Resolved:** [#2] — unit standardization + quick-add

## Screen: Inventory (Stage: إدارة المخزون)
- **Layout:** Left: category sidebar (seeds, fertilizers, pesticides, equipment); Right: inventory table + low-stock alerts
- **Components:** CategoryNav, InventoryTable (item, quantity, unit, expiry date, supplier), LowStockBadge, AddStockModal, OrderButton
- **States:** Empty (no inventory) | Loading | Low stock (highlighted in red) | Expired items flagged
- **Key Interaction:** Click "order" button → pre-filled supplier order form
- **Friction Resolved:** [#4] — stock alerts + supplier integration

## Screen: Reports (Stage: تقارير الإنتاج)
- **Layout:** Filter bar (date range, parcel, crop type) + chart area (bar/line/pie toggle) + export buttons + summary stats
- **Components:** FilterBar, ChartContainer (Chart.js), SummaryStat (total production, avg yield/m², revenue, cost), ExportButton (PDF/CSV), CompareToggle (vs last period)
- **States:** Empty (no data) | Loading | No results for filter | Data available
- **Key Interaction:** Click chart segment to drill down; export one-click download
- **Friction Resolved:** [#5] — pre-aggregated charts for fast load

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Button Primary | Default, Small | hover/active/disabled/loading | bg-#2D5016, white text, 8px radius, 14px |
| Button Secondary | Default, Outline | hover/active/disabled | border-#8B7355, text #8B7355 |
| Input Field | Default, WithUnit (kg/m²) | focus/error/disabled | 12px padding, 1px #D1D5DB, 8px radius |
| Select | Default, Searchable | focus/error/disabled | native with search for long lists |
| StatCard | WithIcon, Mini | normal/hover | bg-white, shadow-sm, 16px padding, 8px radius |
| ParcelCard | MapLinked, Detail | normal/hover/selected | mini map preview, status color border |
| WeatherWidget | Compact, Full | loading/error/data | 7-day forecast, current conditions |
| ScheduleGrid | Daily, Weekly, Monthly | editable/read-only | drag to set, auto-save on change |
| Chart | Bar, Line, Pie | loading/data/empty/error | Chart.js, responsive, tooltip on hover |
| Alert | LowStock, Weather, Task | dismissible/actionable | color-coded by type, icon + message + action |
