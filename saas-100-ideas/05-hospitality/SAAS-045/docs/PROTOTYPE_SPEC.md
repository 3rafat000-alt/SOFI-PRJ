# PROTOTYPE SPEC — KioskPro (SAAS-045)
> Owner: UI/UX Designer · Gate 2

## Screen: Home / Menu Categories (maps to Journey Stage: Browse)
- **Layout:** Category cards (large touch targets, food images) in a grid, language toggle top
- **Components:** CategoryCard, LanguageToggle, CartBadge, SettingsButton
- **States:**
  - Loading: Skeleton cards × 6 with pulse
  - Error: "Menu unavailable — call staff"
  - Empty: "No categories available"
  - Edge: 15+ categories — scrollable horizontal rows
- **Key Interaction:** Tap category → items list slide in (left-to-right)
- **Friction Resolved:** [#3] أزرار لمس كبيرة 64px+

## Screen: Menu Items (maps to Journey Stage: Select)
- **Layout:** Grid of item cards with image, name, price, add button
- **Components:** ItemCard, AddButton, ImageWithFallback, PriceBadge
- **States:**
  - Loading: Skeleton grid
  - Error: "Failed to load items"
  - Empty: "This category is empty"
  - Edge: 50+ items — paginated, search
- **Key Interaction:** Tap item → customizer popup OR tap + to add directly
- **Friction Resolved:** [#1] صور خفيفة (WebP, lazy load)

## Screen: Item Customizer (maps to Journey Stage: Customize)
- **Layout:** Bottom sheet with item image, modifiers grouped, quantity selector, add to cart button
- **Components:** ModifierGroup, QuantityStepper, AddToCartButton, OptionChip
- **States:**
  - Default: Standard options pre-selected
  - Modified: Selected options highlighted
  - Sold Out: Option greyed out with badge
- **Key Interaction:** Tap modifier → toggle selected → tap add to cart → sheet closes
- **Friction Resolved:** [#2] customizer بسيط وواضح

## Screen: Cart & Checkout (maps to Journey Stage: Checkout)
- **Layout:** Item list with quantities and prices, modifiers summary, total, checkout button
- **Components:** CartItem, QuantityStepper, TotalCard, CheckoutButton
- **States:**
  - Empty: "Your cart is empty" + browse button
  - Active: Items listed with totals
  - Error: "Item unavailable" removed from cart
- **Key Interaction:** Adjust quantity → see total update → tap checkout
- **Friction Resolved:** [#4] checkout مباشر

## Screen: Order Status (maps to Journey Stage: Wait)
- **Layout:** Large order number, status timeline (received → preparing → ready), estimated time
- **Components:** StatusTimeline, TimeBadge, OrderNumber, QRCode
- **States:**
  - Received: "Order received" + time
  - Preparing: "Being prepared" + progress
  - Ready: "Ready!" + vibration
  - Error: "Order cancelled" + call staff button
- **Key Interaction:** Show QR to staff for pickup verification
- **Friction Resolved:** [#5] إرسال عبر واتساب

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| CategoryCard | image+text, large | default, hover (glow), selected | Min 80px height, touch feedback |
| ItemCard | grid/list | default, sold-out, low-stock | Add animation |
| ModifierGroup | single/multi-select | default, selected, sold-out | Price delta shown |
| QuantityStepper | horizontal | min (1), max (99), disabled | Long-press to change |
| CheckoutButton | enabled/disabled | default, loading (payment), success | Full-width, 56px height |
| StatusTimeline | 3-step (received/preparing/ready) | active/ completed/ pending | Step pulse animation |
| TouchTarget | all interactive | min 48px (WCAG), preferred 64px | Ripple feedback |
