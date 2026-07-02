# PROTOTYPE SPEC — SalonPro (SAAS-008)
> Owner: UI/UX Designer · Gate 2

## Screen 1: Services & Stylists (maps to: Browse → Select Stylist)
- **Layout:** Top: category tabs (Hair, Nails, Skin, Makeup), Service cards with name, duration, price, Stylist selection row with photos, ratings
- **Components:** Category pills, Service card (image, name, duration, price), Stylist avatar row, Book button
- **States:**
  - Empty: "Loading services..."
  - Loading: Card skeleton
  - Error: Fetch error → retry
  - Edge: Filter by stylist or service, search bar
- **Key Interaction:** Tap service → see details → select stylist → "التالي: اختر الوقت"
- **Friction Resolved:** #3 — clear service offering with stylist profiles

## Screen 2: Calendar & Booking (maps to: Pick Time → Confirm)
- **Layout:** Date picker (horizontal scroll), Time slot grid (30min intervals, colour-coded: available/booked/selected), Appointment summary card, Confirm button
- **Components:** Date strip, Time slot (pill), Summary card (service, stylist, time, price), Confirm CTA
- **States:**
  - Loading: Slots loading skeleton
  - Error: Slot fetch fail
  - Edge: Multi-service booking (stacked appointments)
  - Empty: "No available slots for this date — try another day"
- **Key Interaction:** Select date → slots load → tap time → summary appears → confirm
- **Friction Resolved:** #1 — conflict-free booking with live availability

## Screen 3: Commission Dashboard (Admin) (maps to: Pay → Goal)
- **Layout:** Top: period selector (week/month), Employee performance cards (name, services done, revenue, commission earned), Detailed table: each appointment with commission breakdown
- **Components:** Period tabs, Employee card (avatar, stats), Commission table (appointment-level), Export button
- **States:**
  - Empty: "No data for this period"
  - Loading: Card skeleton
  - Error: Data fetch error
  - Edge: Tips included/excluded toggle
- **Key Interaction:** Tap employee card → detail drill-down
- **Friction Resolved:** #2 — automatic commission calculation

## Screen 4: Loyalty Program (Customer) (maps to: Points → Goal)
- **Layout:** Loyalty card with points balance and tier (Bronze/Silver/Gold), Points history, Available rewards (discounts, free services), QR code for in-store scan
- **Components:** Tier badge, Points progress bar, Reward card, Earn history list, Scan QR button
- **States:**
  - Empty: "Book your first service to earn points"
  - Loading: Points balance loading
  - Error: Points fetch error
  - Edge: Birthday bonus notification, points expiry
- **Key Interaction:** Tap reward → "استبدل" → confirmation
- **Friction Resolved:** #4 — visible points + rewards drive loyalty

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Service Card | With image, Compact | default/hover/selected | image aspect 3:2, price tag |
| Stylist Avatar | Photo, Initials | available/busy/offline | green dot = available |
| Time Slot | 30min, 60min | available/booked/selected | tap to select, disabled if past |
| Booking Summary | Card | default/confirmed | service, stylist, time, price |
| Employee Card | Performance | default/hover/selected | avatar, name, revenue, commission |
| Commission Table | Week, Month | loading/data/empty/export | sortable columns |
| Loyalty Card | Tier card | bronze/silver/gold | gradient per tier, points number |
| Reward Card | Discount, Free service | available/redeemed/expired | redeem CTA button |
