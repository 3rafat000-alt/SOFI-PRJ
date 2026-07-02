# Design System — TimeSheet Pro
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** TimeSheet Pro — صحيفة الوقت المحترفة
- **Logo concept:** دائرة ساعة توقيت مع خط بياني أخضر صاعد داخل حرف T
- **Brand personality:** بسيط، دقيق، منتج، ذكي، سريع

## Color Palette
- **Primary:** `#1976D2` — أزرق إنتاجي (أزرار، رأس الصفحة، مؤقت)
- **Secondary:** `#00ACC1` — سيان (تقارير، رسوم بيانية، أيقونات)
- **Accent:** `#43A047` — أخضر (إنتاجية، مؤقت شغال، هدف متحقق)
- **Neutral:** `#F5F5F5` (خلفيات) `#757575` (نص ثانوي) `#212121` (نص أساسي)
- **Timer Status:** Running `#43A047` · Paused `#F9A825` · Stopped `#757575`
- **Billing:** Billable `#2E7D32` · Non-billable `#757575` · Overtime `#E65100`

## Typography
- **Headings:** Inter — sizes: 24/20/18/16px (600 weight)
- **Body:** Inter — 14px (400 weight)
- **Arabic:** Noto Sans Arabic — RTL reports, Arabic day names
- **Timer display:** Inter Bold 48px (timer), JetBrains Mono 14px (durations)

## Spacing
- Base unit: 8px
- Padding: 16/24/32px
- Border radius: 50% (timer circle), 8px (cards), 6px (buttons)
- Timer area: 240px circle, centered

## Iconography
- Style: Outline
- Library: Lucide
- Key icons: Play, Square, Clock, BarChart3, Users, Download, FileText

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| TimerCircle | 240px circle, stroke 6px, time center | idle: gray stroke, running: green stroke animated, paused: yellow stroke |
| StartStopButton | 64px circle, icon only | idle: green play icon, running: red square icon, loading: spinner |
| ProjectSelect | dropdown with color indicator + client name | default: "اختر المشروع", selected: project name + color dot |
| ReportChart | bar chart with time range selector | bar hover: tooltip, click bar: drill into project tasks |
| TimeEntryRow | project + task + duration + billable toggle | hover: bg-gray-50, editing: bg-blue-50, pending save: opacity 0.7 |
| ExportButton | dropdown menu (PDF/CSV/Excel) | default: icon + "تصدير", loading: spinner, complete: check |
| DurationInput | time picker with hours/minutes | manual type or picker, validates against 24h max per entry |
