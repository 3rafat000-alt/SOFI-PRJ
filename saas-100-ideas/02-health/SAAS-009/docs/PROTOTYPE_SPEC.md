# PROTOTYPE SPEC — PetCare Vet (SAAS-009)
> Owner: UI/UX Designer · Gate 2

## Screen 1: Pet Registration (maps to: Register Pet → Find)
- **Layout:** Step form: 1) Owner info (name, phone), 2) Pet info (name, species, breed, DOB, colour, weight), 3) Photo + microchip, quick submit
- **Components:** Step indicator, Input fields, Species selector (cat/dog/other), Breed autocomplete, Weight slider, Camera/gallery upload
- **States:**
  - Empty: Fresh form
  - Loading: Saving pet
  - Error: Duplicate microchip validation
  - Edge: Multiple pets per owner, rapid registration flow for emergencies
- **Key Interaction:** Select species → filter breed list → enter name → add photo → save
- **Friction Resolved:** #3 — quick registration with smart defaults

## Screen 2: Medical Record Timeline (maps to: Examine → Diagnose)
- **Layout:** Pet header (photo, name, species, age, weight trend), Timeline of visits (reverse chronological), Each visit card: date, vet, diagnosis, treatment, attachments
- **Components:** Pet header card, Weight chart (sparkline), Visit card (expandable), Add record FAB, Search within records
- **States:**
  - Empty: "No records yet"
  - Loading: Timeline skeleton
  - Error: Record fetch error
  - Edge: 50+ visits → virtual scroll + date filter
- **Key Interaction:** Scroll timeline → tap visit → expand full details → tap "+" add new record
- **Friction Resolved:** #2 — instant access to full history

## Screen 3: Vaccination Schedule (maps to: Vaccinate → Schedule)
- **Layout:** Vaccination card per pet: vaccine name, date given, next due date, status (completed/pending/overdue), Master list: all pets grouped by vaccination status
- **Components:** Vaccine card, Schedule status badge, Overdue alert banner, "Add vaccination" button
- **States:**
  - Empty: "No vaccinations recorded"
  - Loading: Schedule loading
  - Error: Sync error
  - Edge: Species-specific vaccine schedule (dog vs cat)
- **Key Interaction:** Tap "إضافة تطعيم" → select vaccine → enter date → system auto-calculates next due
- **Friction Resolved:** #1 — auto-schedule + reminders

## Screen 4: Inventory Management (maps to: Prescribe → Invoice)
- **Layout:** Inventory table: item name, category, current stock, min threshold, expiry date, status (ok/low/critical/expired), Search bar, Add item button
- **Components:** Data table, Stock bar, Status badge, Expiry warning, Reorder button, Supplier info
- **States:**
  - Empty: "No items in inventory"
  - Loading: Table skeleton
  - Error: Fetch error
  - Edge: Barcode scanner for quick add, batch tracking
- **Key Interaction:** Search item → view stock → adjust stock → see status update
- **Friction Resolved:** #5 — stock level monitoring with alerts

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Step Form | 3-step indicator | current/done/pending | animated transitions |
| Pet Header | Card with photo | default/hover | image, name, species, weight |
| Visit Card | Timeline card | collapsed/expanded | tap to toggle details |
| Vaccine Card | Per vaccine type | completed/pending/overdue | color-coded status bar |
| Stock Row | Table row | ok/low/critical/expired | bar indicator + badge |
| Weight Chart | Sparkline | 3m/6m/12m | trend line with data points |
| Search Bar | Global, Scoped | focus/typing/results/empty | 300ms debounce |
