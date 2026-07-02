# PROTOTYPE SPEC — StampMe (SAAS-046)
> Owner: UI/UX Designer · Gate 2

## Screen: Check-In/Out (maps to Journey Stage: Check-In)
- **Layout:** Single screen with large circular button (CHECK IN / CHECK OUT), user photo, time, date, location
- **Components:** CheckButton (big), UserAvatar, TimeDisplay, LocationBadge, FacePreview
- **States:**
  - Not checked in: Big "CHECK IN" button (green), grey "CHECK OUT"
  - Checked in: "CHECKED IN" label, big "CHECK OUT" button (red)
  - Loading: Scanning GPS + face...
  - Error: "Location out of range" + exception request link
- **Key Interaction:** Tap button → GPS check → face scan → success haptic
- **Friction Resolved:** [#1, #2] زر كبير 80px، دعم إضاءة منخفضة

## Screen: Face Scan (maps to Journey Stage: Face Scan)
- **Layout:** Camera viewfinder (oval frame), instructions text, lighting indicator
- **Components:** CameraView, FaceGuide, LightingIndicator, Countdown, RetryButton
- **States:**
  - Ready: Viewfinder with face outline
  - Scanning: Oval pulses, "Hold still..."
  - Success: Green checkmark + haptic
  - Fail: "Face not recognized" + retry button
  - Low light: Flash toggle + "Move to brighter area"
- **Key Interaction:** Look at camera → auto-capture when face aligned → verify
- **Friction Resolved:** [#2] إضاءة منخفضة — تفعيل flash

## Screen: Attendance Calendar (maps to Journey Stage: View Records)
- **Layout:** Monthly calendar with color-coded days (green/amber/red), daily summary below
- **Components:** Calendar, DayCell, DailySummary, MonthNavigation, StatsRow
- **States:**
  - Loading: Calendar skeleton
  - Empty: "No records for this month"
  - Error: "Failed to load records"
  - Edge: 3 years of data — lazy load months
- **Key Interaction:** Tap day → see check-in/out times below
- **Friction Resolved:** [#3] تقويم مرئي سريع

## Screen: Reports (maps to Journey Stage: Report)
- **Layout:** Filter bar (month/department/employee), summary cards, export button, data table
- **Components:** FilterBar, StatCard, DataTable, ExportButton, Chart
- **States:**
  - Empty: "Select a month to view report"
  - Loading: Skeleton
  - Error: "Report generation failed"
- **Key Interaction:** Select month + department → table populates → export PDF/CSV
- **Friction Resolved:** [#4] تقارير جاهزة للرواتب

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| CheckButton | in/out | default, loading, success, error | 80px circle, pulse animation |
| CameraView | default, scanning, success, error | auto-capture when aligned | Oval guide overlay |
| FaceGuide | oval frame | ready, scanning, success, fail | Green border on success |
| Calendar | month view, color dots | today selected, has data | Swipe to change month |
| DayCell | default, today, absent, late, present | selected, has data | Color dot indicator |
| StatCard | present/late/absent/overtime | default, highlight | Animated number count |
| FilterBar | month/department/employee | active filter, dropdown | Chips + date picker |
