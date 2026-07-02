# PROTOTYPE SPEC — RideShare (SAAS-054)
> Owner: UI/UX Designer · Gate 2

## Screen: Search Rides (maps to Journey Stage: البحث عن رحلة)
- **Layout:** From/to inputs + date picker + seats + search button
- **Components:** LocationInput, DatePicker, SeatCounter, SearchButton, RecentSearches
- **States:** Empty (no recent searches → placeholder illustration), Loading, Error, Edge (no results → "لا توجد رحلات بهذا الاتجاه")
- **Key Interaction:** Enter cities → select date → search → results list
- **Friction Resolved:** [#3] صعوبة إيجاد رحلات → بحث ذكي

## Screen: Ride Results (maps to Journey Stage: اختيار رحلة)
- **Layout:** Filter bar + ride cards with driver info, price, departure, seats
- **Components:** FilterChips, RideCard, DriverAvatar, PriceTag, SeatIndicator
- **States:** Empty (no rides → "لا توجد رحلات متاحة"), Loading (skeleton cards), Error (retry)
- **Key Interaction:** Apply filters → tap ride → detail view
- **Friction Resolved:** [#2] كثرة الخيارات → فلترة ومقارنة سهلة

## Screen: Book Ride (maps to Journey Stage: حجز مقعد)
- **Layout:** Trip summary + driver profile + select seats + payment method + confirm
- **Components:** TripSummaryCard, DriverProfileMini, SeatSelector, PaymentOptions, ConfirmButton
- **States:** Loading (booking), Error (booking failed → retry), Edge (driver cancelled → refund + search alternatives)
- **Key Interaction:** Confirm booking → payment → confirmation screen
- **Friction Resolved:** [#1] إلغاء الحجز → دفع مسبق، حجز مؤكد

## Screen: Live Tracking (maps to Journey Stage: تتبع الرحلة)
- **Layout:** Map with driver location + ETA + driver contact + SOS button
- **Components:** MapView, DriverMarker, ETACard, ContactButtons, SOSButton
- **States:** Loading (GPS acquiring), Error (no GPS → "تعذر تحديد الموقع"), Edge (trip ended → rating prompt)
- **Key Interaction:** Watch live location, tap SOS for emergency

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| RideCard | default, compact | normal, booked, full | Swipe for details |
| LocationInput | from, to | empty, selected, error | Autocomplete with map |
| DriverAvatar | with badge | verified, not-verified, loading | Badge if ID verified |
| MapView | rider, driver | tracking, idle | Real-time GPS update |
| SeatSelector | 1-6 seats | available, selected, disabled | Stepper control |
| SOSButton | emergency | normal, triggered | Alert + share location |
