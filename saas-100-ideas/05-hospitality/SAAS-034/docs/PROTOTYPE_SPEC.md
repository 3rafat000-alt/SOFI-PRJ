# PROTOTYPE SPEC — BookingPro (SAAS-034)
> Owner: UI/UX Designer · Gate 2

## Screen: Provider Calendar (maps to Journey Stage: Trigger → Set Availability)
- **Layout:** Full-page calendar (day/week/month toggle) with time slots grid
- **Components:** Calendar header, day/week/month tabs, time slot blocks, break period, blocked dates
- **States:** Empty (first setup) | Loading (sync) | Error (save failed) | Edge case (session lasts 4+ hours)
- **Key Interaction:** Drag to select time range → creates availability slot → click slot to edit/delete
- **Friction Resolved:** #1 — تقويم آني للتحكم بالأوقات

## Screen: Client Booking Flow (maps to Journey Stage: Browse → Pick Time → Pay)
- **Layout:** Stepper wizard — step 1 (service), step 2 (calendar), step 3 (info), step 4 (pay), step 5 (confirm)
- **Components:** Service selector, date picker with available slots highlighted, booking summary card, payment form
- **States:** Empty (no services) | Loading (slots loading) | Error (payment fail) | Edge case (timezone mismatch)
- **Key Interaction:** Pick date → see green available slots → tap slot → fill info → pay → confirm
- **Friction Resolved:** #2 — دفع مسبق

## Screen: Provider Dashboard (maps to Journey Stage: Complete → Review)
- **Layout:** Stats row (today's bookings, revenue, upcoming) + bookings list + calendar mini-view
- **Components:** Stat card, bookings table, calendar preview, payout summary
- **States:** Empty (no bookings yet) | Loading | Error | Edge case (100+ upcoming → scroll)
- **Key Interaction:** Click booking → view detail + start session button
- **Friction Resolved:** #5 — منع تعارض الحجوزات

## Screen: Booking Detail (maps to Journey Stage: Session)
- **Layout:** Left column — client info, booking details, payment status, session controls
- **Components:** Client card, booking info, payment badge, action buttons (start/complete/cancel)
- **States:** Loading | Error | Edge case (cancellation with refund)
- **Key Interaction:** Click "Start Session" → status changes to in-progress → timer starts
- **Friction Resolved:** #3 — إدارة الحجز من البداية للنهاية

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Calendar | Day/Week/Month views | default/interactive/blocked | click to select, drag range, RTL support |
| Time Slot | Available (green), Booked (gray), Break (striped) | hover/selected/disabled | 30min default interval |
| Stepper | 4-step wizard | active/complete/pending | progress bar, back/next navigation |
| Service Card | Image, name, duration, price | default/hover/selected | click to select |
| Payment Form | Card, Wallet, Cash | idle/processing/success/error | Stripe Elements, Tap SDK |
| Stat Card | Single metric with trend | default | icon + value + label + trend arrow |
| Session Control | Start, Complete, Cancel | enabled/disabled | confirm dialog on cancel |
| Badge | Paid, Pending, Refunded, Free | static | color-coded |
