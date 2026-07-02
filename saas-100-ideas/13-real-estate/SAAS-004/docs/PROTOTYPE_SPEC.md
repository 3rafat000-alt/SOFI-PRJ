# PROTOTYPE SPEC — RentTrack (SAAS-004)
> Owner: UI/UX Designer · Gate 2

## Screen 1: Property Portfolio (maps to: Trigger → Add Unit)
- **Layout:** Top: portfolio KPI row (total units, occupied, vacant, overdue), Grid of property cards with image + address + units count + occupancy %, FAB "+" new property
- **Components:** KPI metric card, Property card (image, address, stats), Status badge (vacant/occupied), Progress bar (occupancy)
- **States:**
  - Empty: "Add your first property" CTA + tutorial
  - Loading: Card skeleton grid (6)
  - Error: Fetch error with retry
  - Edge: Pagination for 50+ units
- **Key Interaction:** Tap property card → unit list view
- **Friction Resolved:** #4 — streamlined property data entry

## Screen 2: Lease & Contract (maps to: Negotiate → Sign)
- **Layout:** Left: contract preview panel, Right: fillable form fields (tenant info, rent amount, duration, payment day), Bottom: Send for signature button
- **Components:** PDF preview embed, Form fields with validation, Date picker, Signature pad (optional), Send button
- **States:**
  - Empty: Blank contract template
  - Loading: Generating PDF spinner
  - Error: Validation error inline
  - Edge: Multi-tenant lease, renewal from existing
- **Key Interaction:** Fill fields → preview updates live → "إرسال للتوقيع"
- **Friction Resolved:** #3, #5 — smart contracts with deadline alerts

## Screen 3: Payment Dashboard (maps to: Collect)
- **Layout:** Monthly calendar grid with payment status per day (paid/pending/overdue), Tenant list with balance + pay buttons, Summary: expected vs collected
- **Components:** Calendar heatmap, Tenant row (name, unit, due amount, status), Pay button, Export ledger
- **States:**
  - Empty: "No payment data yet"
  - Loading: Calendar skeleton
  - Error: Sync error with payment gateway
  - Edge: Partial payment, late fee calculation
- **Key Interaction:** Tap tenant row → see payment history → tap "تحصيل" → payment modal
- **Friction Resolved:** #1 — automated reminders + online collection

## Screen 4: Maintenance Board (maps to: Maintain)
- **Layout:** Kanban columns: New / Assigned / In Progress / Done, Each card: issue title, unit, priority badge, time elapsed, photo thumbnail
- **Components:** Kanban column, Request card, Priority badge, Assign contractor dropdown, Status update button
- **States:**
  - Empty: "No maintenance requests. All good!"
  - Loading: Card skeletons
  - Error: Offline → queue locally
  - Edge: Urgent requests pinned to top with red border
- **Key Interaction:** Drag card between columns → status update with timestamp
- **Friction Resolved:** #2 — trackable maintenance with photo evidence

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Property Card | Grid, List | default/hover/selected | 16px radius, image cover, stats overlay |
| KPI Card | Number, Currency, Percentage | default/trend-up/trend-down | icon + value + % change |
| Contract Preview | PDF iframe, Form preview | loading/generated/signed | live-update on field change |
| Calendar Heatmap | Monthly, Yearly | paid/pending/overdue/future | color-coded squares |
| Tenant Row | Default, Overdue | default/overdue/warning | red dot for overdue |
| Maintenance Card | Default, Urgent | new/assigned/in-progress/done | priority color left border |
| Pay Button | Single, Batch | default/processing/success/error | stripe/moyasar/stc pay |
