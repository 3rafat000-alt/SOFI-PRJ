# PROTOTYPE SPEC — Eventify (SAAS-013)
> Owner: UI/UX Designer · Gate 2

## Screen: Dashboard (Stage: لوحة الفعاليات)
- **Layout:** Top navbar + sidebar (events, tickets, speakers, sponsors, reports) + grid of event cards
- **Components:** EventCard (image, title, date, status, tickets sold progress bar), CreateButton (FAB), StatRow (total events, tickets sold, revenue, avg rating), NotificationBell
- **States:** Empty ("أنشئ فعاليتك الأولى") | Loading (skeleton cards) | List with data | Error (API)
- **Key Interaction:** Click event card → event detail; FAB → create wizard
- **Friction Resolved:** [#5] — virtual scrolling for large lists

## Screen: Event Creation Wizard (Stage: إنشاء فعالية)
- **Layout:** Multi-step wizard (5 steps: basic info → date/venue → speakers → tickets → publish)
- **Components:** StepIndicator, TextInput, DatePicker (Gregorian+Hijri), LocationInput (map), SpeakerForm (name, bio, photo, time slot), TicketTypeForm (type, price, qty, max per person), PublishToggle
- **States:** Step in-progress | Step complete | Validation (per step) | Draft auto-save | Preview mode
- **Key Interaction:** Navigate back/forth without losing data; preview event page before publish
- **Friction Resolved:** [#3] — discount codes, early bird tiers, group pricing support

## Screen: Speaker Schedule (Stage: إضافة المتحدثين)
- **Layout:** Left: speakers list (drag reorderable); Right: timeline grid (time × room/stage)
- **Components:** SpeakerCard (avatar, name, organization, talk title), TimeSlotBlock, DragHandle, ConflictAlert (red highlight), RoomSelect
- **States:** Empty (no speakers) | Loading | Schedule with conflicts | Schedule validated
- **Key Interaction:** Drag speaker to time slot; conflict auto-detected and highlighted; propose alternative
- **Friction Resolved:** [#1] — conflict detection + auto-suggest relocation

## Screen: Ticket Management (Stage: التذاكر والتسعير)
- **Layout:** Table of ticket types (name, price, qty sold, qty remaining, revenue) + actions
- **Components:** TicketTypeRow, AddTicketButton, DiscountCodeForm, SalesChart (mini sparkline), StatusToggle (active/paused/sold out), QrCodePreview, CheckInWidget
- **States:** Empty (no tickets) | Loading | Active sales | Sold out | Paused
- **Key Interaction:** Click ticket type → edit modal; toggle active/pause; check-in from mobile
- **Friction Resolved:** [#2] — real-time sales dashboard with auto-refresh

## Screen: On-site Check-in (Stage: إدارة الحضور)
- **Layout:** Full-screen scanner with camera view + manual entry fallback + attendance count
- **Components:** CameraViewfinder (QR scanner), ManualCodeInput, AttendeeList (scanned today), StatsBar (checked in, total, remaining), SyncIndicator
- **States:** Scanning | Manual entry | Success feedback (green flash) | Error (invalid code) | Offline mode
- **Key Interaction:** Point camera at QR → beep → attendee marked; offline queue → sync when online
- **Friction Resolved:** [#1] — offline QR caching + instant feedback

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Button Primary | Default, Small, Icon | hover/active/disabled/loading | bg-#C0392B, white, 8px radius, 14px |
| Button Secondary | Default, Outline | hover/active/disabled | border-#E67E22, text #E67E22 |
| EventCard | Default, Mini, Featured | normal/hover | 12px radius, shadow, image ratio 16:9 |
| Input Field | Default, WithIcon | focus/error/disabled | 12px padding, 8px radius |
| Select | Default, Searchable | focus/error | dropdown with search |
| StepIndicator | Horizontal, Vertical | completed/active/pending | numbered steps with connector line |
| SpeakerCard | Drag, Static | drag/normal | avatar 48px, name, title, drag handle |
| QRScanner | Full, Compact | active/error/success | camera feed + bounding box |
| PriceTier | Single, Bulk | selected/available/sold out | radio card + price + remaining |
| TicketTypeRow | Default | active/paused/sold out | sparkline + edit/pause/delete |
| Modal | Default | open/close | backdrop blur, ESC close |
| Table | Striped, Sortable | hover row | sort indicators, sticky header |
