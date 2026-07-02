# PROTOTYPE SPEC — HotelEase (SAAS-014)
> Owner: UI/UX Designer · Gate 2

## Screen: Hotel Dashboard (Stage: لوحة الفندق)
- **Layout:** Top: date + occupancy gauge + revenue mini-graph; Main: room grid (visual map of hotel floors with room status colors); Sidebar: recent check-ins/outs, pending requests
- **Components:** OccupancyGauge (percentage ring), RevenueSparkline, RoomGrid (floor legend: green=available, red=occupied, yellow=cleaning, gray=maintenance), AlertBar (checkouts today, arrivals today), QuickActionPanel (check-in, booking, room status change)
- **States:** Empty (no rooms configured) | Loading (skeleton) | Full data | Error
- **Key Interaction:** Click room in grid → room detail modal; drag room icon to change status
- **Friction Resolved:** [#2] — real-time room status visibility

## Screen: New Booking / Reservation (Stage: حجز جديد)
- **Layout:** Left: date range picker + guest count filters; Right: available room cards list
- **Components:** DateRangePicker (Gregorian), GuestCountStepper, RoomCard (type, price, amenities, photo), FilterChips (room type, view, floor), PriceSummary
- **States:** Empty (no availability) | Loading | Results | No results (show alternatives)
- **Key Interaction:** Select dates → rooms load; click room → expand details; confirm → guest info form
- **Friction Resolved:** [#1] — instant availability check

## Screen: Check-in (Stage: تسكين النزيل)
- **Layout:** Search guest by name/phone/booking-id + pre-filled form + room assignment + key encoding
- **Components:** GuestSearch (autocomplete from booking DB), CheckInForm (auto-filled: name, phone, ID, nationality), RoomAssign (dropdown of available rooms of same type), IDScanner (camera for passport scan), KeyEncoderWidget, PaymentTerminal (optional)
- **States:** Empty (search) | Guest found (pre-filled) | Guest not found (manual entry) | Loading | Submit success
- **Key Interaction:** Scan booking QR → auto-fill all fields; scan passport → OCR name/number; assign room → print key
- **Friction Resolved:** [#1] — pre-filled forms + ID scanning

## Screen: Guest Stay Management (Stage: إقامة النزيل)
- **Layout:** Guest info header + tabs (folio, requests, housekeeping, history)
- **Components:** GuestProfileCard, FolioTable (charges: room, minibar, services), ServiceRequestButton, HousekeepingStatusBadge, ExtendStayButton, CheckoutButton
- **States:** Loading | Active (with charges) | Checkout pending
- **Key Interaction:** Add charge to folio; request service; extend stay (auto-calculate additional charge)
- **Friction Resolved:** [#3] — housekeeping coordination, [#1] — extend stay recalculates automatically

## Screen: Check-out & Billing (Stage: إنهاء الإقامة)
- **Layout:** Invoice preview (logo, items, taxes, total) + payment method selector + email receipt
- **Components:** InvoicePreview (editable: add/remove charges), PaymentMethodSelect (cash, card, Mada, STC Pay), SplitPaymentOption, ReceiptEmailField, PrintButton
- **States:** Loading | Invoice generated | Payment processing | Payment success | Partial payment
- **Key Interaction:** Review charges (add missing items) → select payment method → process → receipt sent
- **Friction Resolved:** [#4] — auto-invoice with accurate pro-ration for early/late checkout

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Button Primary | Default, Small | hover/active/disabled/loading | bg-#B8860B, white, 8px radius, 14px |
| Button Secondary | Default, Outline | hover/active/disabled | border-#8B4513, text #8B4513 |
| Input Field | Default, Search | focus/error/disabled | 12px padding, 8px radius |
| RoomCard | Horizontal, Vertical | normal/hover/selected/occupied | status color left border, rate, amenities |
| RoomGridCell | By status | available/occupied/cleaning/maintenance | color coded, clickable for detail |
| GuestProfileCard | Default | normal/expanded | avatar, name, room#, check-in date, status |
| IDScanner | Camera, Manual | scanning/success/error/offline | OCR preprocessing, manual fallback |
| InvoiceLineItem | Editable, Read-only | default/edited | description, qty, unit price, total |
| DateRangePicker | Single, Range | focus/disabled | Gregorian calendar, min/max dates |
| ServiceRequest | Default, Urgent | pending/in-progress/completed | category, timestamp, assigned to |
