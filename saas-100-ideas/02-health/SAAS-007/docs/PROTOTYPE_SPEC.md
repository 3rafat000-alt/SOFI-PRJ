# PROTOTYPE SPEC — FitZone Pro (SAAS-007)
> Owner: UI/UX Designer · Gate 2

## Screen 1: Membership Plans (maps to: Browse → Signup)
- **Layout:** 3 pricing cards (Monthly/Yearly/Lifetime) side by side, features comparison, CTA button per plan, "Most popular" badge on Yearly
- **Components:** Pricing card, Feature list with checkmarks, Badge, CTA button
- **States:**
  - Empty: N/A
  - Loading: Card skeleton
  - Error: Pricing fetch failed → fallback defaults
  - Edge: Promo code field, family plan option
- **Key Interaction:** Tap plan → highlight → "اشترك الآن"
- **Friction Resolved:** #2 — yearly discount incentive

## Screen 2: Class Schedule (maps to: Class → Book)
- **Layout:** Weekly calendar grid (Mon-Sun, 6AM-10PM), each cell: class name, trainer, capacity (filled/total), Book button
- **Components:** Calendar grid, Class cell, Trainer avatar, Capacity bar, Book/Waitlist button
- **States:**
  - Empty: "No classes scheduled for this day"
  - Loading: Grid skeleton
  - Error: Schedule sync error
  - Edge: Waitlist mode when full
- **Key Interaction:** Tap time slot → view class detail → tap "حجز"
- **Friction Resolved:** #1 — waitlist with auto-notify

## Screen 3: QR Check-in (maps to: Attend)
- **Layout:** Full-screen camera viewfinder, QR code frame, Member name + photo overlay (once scanned), Entry granted/denied animation
- **Components:** Camera viewfinder, Flash toggle, Manual entry (member ID), Success/Error overlay
- **States:**
  - Loading: Camera init
  - Error: Camera permission → manual entry fallback
  - Edge: Offline mode — cached QR codes
  - Success: Green check + member name animation
- **Key Interaction:** Point camera at member QR → automatic scan → entry granted
- **Friction Resolved:** #5 — digital membership card

## Screen 4: Workout Log (maps to: Track → Progress)
- **Layout:** Date selector, Exercise list (add exercise → search from library), Sets rows (set #, weight, reps, ✓), Timer between sets, Save button
- **Components:** Exercise autocomplete, Set row (weight + reps + done), Rest timer, Volume summary
- **States:**
  - Empty: "Log your first workout"
  - Loading: Exercise library loading
  - Error: Save offline → sync later
  - Edge: Template from previous workout
- **Key Interaction:** Search exercise → add → enter weight/reps → tap ✓ → rest timer starts
- **Friction Resolved:** #4 — quick log with templates

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Pricing Card | Monthly, Yearly, Lifetime | default/selected/popular | badge highlight, annual saving label |
| Class Cell | Available, Full, Booked | default/hover/booked/waitlist | capacity bar (green→yellow→red) |
| QR Scanner | Full-screen, Mini | scanning/success/error/denied | auto-capture, vibration on success |
| Exercise Row | Card, Compact | default/done/resting | swipe delete, drag reorder |
| Set Input | Weight, Reps, Duration | focus/filled | auto-advance to next set |
| Workout Template | Default, Saved | empty/saving/saved | save as template for reuse |
| Rest Timer | Circular, Bar | running/paused/complete | auto-start after set marked done |
