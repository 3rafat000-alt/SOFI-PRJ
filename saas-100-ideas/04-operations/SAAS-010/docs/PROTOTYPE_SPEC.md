# PROTOTYPE SPEC — GarageMaster (SAAS-010)
> Owner: UI/UX Designer · Gate 2

## Screen 1: Job Order Creation (maps to: Register → Inspect)
- **Layout:** Left: customer/vehicle search section, Right: job details form (complaint, services, estimated time), Bottom: photos attachment
- **Components:** Search customer by phone, Vehicle info card (plate → auto-fill), Service checklist, Photo upload, Save + Print buttons
- **States:**
  - Empty: Scan vehicle plate or search customer
  - Loading: Fetching vehicle history
  - Error: Plate not found → manual entry fallback
  - Edge: Walk-in (no prior customer record), fleet customer
- **Key Interaction:** Type plate number → vehicle auto-fills → select services → estimate generates → save
- **Friction Resolved:** #3 — plate-based quick lookup

## Screen 2: Job Board Kanban (maps to: Assign → Work)
- **Layout:** 5 Kanban columns: Pending / In Progress / Quality Check / Done / Delivered, Cards: vehicle plate, services summary, tech name, time elapsed, priority flag
- **Components:** Kanban column, Job card (plate, service, tech, timer), Column header with count, Filter by tech
- **States:**
  - Empty: "No jobs today"
  - Loading: Cards skeleton
  - Error: Sync error
  - Edge: Overdue jobs highlighted red, urgent pinning
- **Key Interaction:** Drag card → update status → timer tracks time in each column
- **Friction Resolved:** #4 — visual job tracking for owner

## Screen 3: Parts Inventory (maps to: Parts)
- **Components:** Searchable table: part name, SKU, stock, min threshold, price, supplier, Reorder button
- **States:**
  - Empty: "No parts in inventory"
  - Loading: Table skeleton
  - Error: Fetch error
  - Edge: Barcode scanner, bulk adjust, supplier orders
- **Key Interaction:** Search part → view stock → tap deduct when used in job → auto-update
- **Friction Resolved:** #1 — real-time inventory with auto-reorder alerts

## Screen 4: Invoice Generation (maps to: Complete → Invoice)
- **Layout:** Invoice preview: services table (name, hours, labor cost), parts table (name, qty, unit price, total), Summary (subtotal, tax, discount, grand total), Payment method selector, Send/Save/Print buttons
- **Components:** Service line item, Part line item, Tax input, Discount input, Payment method chips, Print PDF, WhatsApp send
- **States:**
  - Loading: Generating PDF
  - Error: Tax calculation error
  - Edge: Partial payment, warranty notes, VAT zero-rated
- **Key Interaction:** Add services/parts → totals auto-calculate → "إرسال الفاتورة" → WhatsApp
- **Friction Resolved:** #5 — accurate digital invoice

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Job Card | Default, Urgent, Overdue | pending/in-progress/QC/done/delivered | colourized left border |
| Vehicle Search | Plate, Phone, Name | default/loading/result/not-found | 200ms debounce, auto-fill |
| Service Line | With time, Fixed price | default/selected | labor rate × hours |
| Parts Line | With SKU | in-stock/low-stock/out-of-stock | stock indicator |
| Kanban Column | Default, Header count | droppable-hover | card count in header |
| Invoice Preview | Standard, VAT | editing/preview/sent/paid | live calculation on change |
| Status Timeline | 5-step vertical | current/done/pending | animated progress through stages |
