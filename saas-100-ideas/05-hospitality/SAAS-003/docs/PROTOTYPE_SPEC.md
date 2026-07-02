# PROTOTYPE SPEC — MenuByte (SAAS-003)
> Owner: UI/UX Designer · Gate 2

## Screen 1: Customer Menu (maps to: Scan → Browse)
- **Layout:** Top: restaurant logo + branch name + language toggle, Grid of category cards with icons, Within category: menu item cards (image, name, price, add button)
- **Components:** QR scanner init, Category grid (2×4), Menu Item card, Floating cart icon (with count badge), Search bar, Dietary filter chips
- **States:**
  - Empty: "Menu coming soon" placeholder
  - Loading: Card skeleton with shimmer (8 items)
  - Error: "Cannot load menu. Check connection." + Retry button
  - Edge: RTL switch, vegetarian/vegan filter, allergen notice
- **Key Interaction:** Tap category → scroll items → tap "+" → item added to cart with animation
- **Friction Resolved:** #2 — instant menu load without printing

## Screen 2: Item Customization & Cart (maps to: Browse → Cart)
- **Layout:** Item detail modal: photo, description, price, modifier groups (size, extras, removal), Cart sheet: item list + quantity adjust + special instructions + total + "Place Order" CTA
- **Components:** Image carousel, Modifier chip group (pill selectors), Quantity stepper, Special instructions text area, Cart item row, Total bar, Place Order button
- **States:**
  - Empty: "Your cart is empty"
  - Loading: Saving customization
  - Error: Item unavailable → cross out with toast
  - Edge: Max modifiers (limit 5), out-of-stock items greyed out
- **Key Interaction:** Select size → add extras → see price update live → tap "أضف للسلة"
- **Friction Resolved:** #1 — accurate order reduces kitchen errors

## Screen 3: Kitchen Display (KDS) (maps to: Kitchen → Prepare)
- **Layout:** Full-screen order queue sorted by time received, each order card: order #, table #, time elapsed, items list, [Start Preparing] [Mark Ready] buttons, Alert bar for urgent/long-wait orders
- **Components:** Order card (large touch targets), Timer countdown, Status badge, Sound alert indicator
- **States:**
  - Empty: "No orders. Enjoy the calm." illustration
  - Loading: Sync indicator
  - Error: Offline fallback → queue local, sync when connected
  - Edge: Sound alert on new order (configurable), vibration mode
- **Key Interaction:** Tap order card → highlight → tap "Start" → timer begins → tap "Ready" → order moves to serve queue
- **Friction Resolved:** #3 — digital orders eliminate paper loss

## Screen 4: Order Status (Customer) (maps to: Place → Serve)
- **Layout:** Large status card with animation: Submitted → Preparing → Ready → Served, Estimated time remaining, Order items summary, Total
- **Components:** Progress stepper, Timer, Item card, Cancel button (within window)
- **States:**
  - Submitted: Pulsing "sent to kitchen"
  - Preparing: Animated cooking icon
  - Ready: Checkmark + "Your order is ready" + glow
  - Served: Completed state
- **Key Interaction:** Watch real-time progress update automatically
- **Friction Resolved:** #4 — customer knows order status without asking

## Screen 5: Payment & Checkout (maps to: Pay → Goal)
- **Layout:** Order summary (items, modifiers, subtotal, tax, total), Payment method cards (card, Apple Pay, STC Pay, cash), Tip selector (10%/15%/20%/ custom)
- **Components:** Summary card, Payment icon cards (selectable), Tip percentage row, Pay button, Receipt modal
- **States:**
  - Empty: (not applicable — always has order)
  - Loading: Processing payment spinner
  - Error: Payment declined → suggest alternative + retry
  - Edge: Split bill (multi-payer)
- **Key Interaction:** Select payment → tap Pay → receipt animation → thank you screen
- **Friction Resolved:** #5 — digital + cash fallback

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Menu Item Card | Grid, List | default/hover/out-of-stock | image 3:2, name, price, +FAB |
| Category Pill | Icon+label, Label only | default/selected/disabled | horizontal scroll, sticky |
| Modifier Chip | Single, Multi | default/selected/disabled/limit-reached | pill shape, 28px height |
| Cart Item | Default, Modifiable | default/editing/empty | qty stepper, swipe delete |
| KDS Order Card | Default, Urgent | new/preparing/ready/completed | large font, timer countup |
| Progress Stepper | 4-step order flow | current/done/pending | animated checkmarks |
| Payment Method | Card, Wallet, Cash, STC Pay | default/selected/processing | 80×48px icon cards |
| Quantity Stepper | Horizontal −/+/count | default/min/max | 32px buttons, min=1, max=20 |
