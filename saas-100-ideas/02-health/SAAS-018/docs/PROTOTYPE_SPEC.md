# PROTOTYPE SPEC — PharmaStock (SAAS-018)
> Owner: UI/UX Designer · Gate 2

## Screen: Pharmacy Dashboard (Stage: لوحة الصيدلية)
- **Layout:** Top: stat row (total items, low stock count, expiring soon, today's sales) + notification bell; Main: activity feed (recent prescriptions, stock alerts, sales summary)
- **Components:** StatCard (with trend indicators), AlertList (expiry warning cards with days left), SalesMiniChart (last 7 days), QuickActionBar (new prescription, add stock, new purchase order), InventorySummaryTable
- **States:** Empty (first-time setup) | Loading | Active | Alert (expired items found)
- **Key Interaction:** Click alert → filtered inventory list; click stat → detailed view
- **Friction Resolved:** [#1] — expiry alerts prominently displayed

## Screen: Prescription Processing (Stage: صرف وصفة)
- **Layout:** Left: patient search + prescription details; Right: drug selection + interactions panel
- **Components:** PatientSearch (national ID/phone), PrescriptionForm (drug name, dosage, frequency, duration), DrugAutoComplete (search by brand/generic), InteractionChecker (auto-flagged conflicts with patient history), AlternativeSuggest (if prescribed not available), PrintLabelButton
- **States:** Empty (search) | Patient found (history shown) | New patient (form) | Interaction alert | Processing | Complete
- **Key Interaction:** Enter patient ID → show drug history + allergies; type drug name → auto-complete with price + insurance coverage; conflict detected → red alert
- **Friction Resolved:** [#2] — drug interaction check, patient history linkage

## Screen: Inventory Management (Stage: إدارة المخزون)
- **Layout:** Searchable/filterable table of all drug items with stock levels + expiry dates
- **Components:** InventoryTable (barcode, name, generic, manufacturer, batch#, qty, unit, expiry, price), FilterBar (category, expiry range, stock level), ExpiryBadge (green > 6mo, yellow < 3mo, red < 1mo), StockAdjustmentForm, BarcodeScannerButton
- **States:** Loading | Normal | Low stock (filtered) | Expiring soon (filtered) | Out of stock
- **Key Interaction:** Scan barcode → item detail; click cell → edit stock; filter expiry → see what's expiring
- **Friction Resolved:** [#1] — expiry badges + filters

## Screen: Purchase Orders (Stage: طلب شراء)
- **Layout:** Split view: left = PO form, right = supplier catalog or previous orders
- **Components:** SupplierSelect, POForm (item line items: drug, qty, unit price, total), SmartSuggestList (auto-suggest reorder for low-stock items), POStatusBadge (draft/sent/confirmed/partial/received), DeliveryTracking, CostSummary
- **States:** Empty | Draft | Sent | Partially received | Complete
- **Key Interaction:** Click "auto-generate" → creates PO from low stock items; scan delivery note → auto-match with PO
- **Friction Resolved:** [#3] — auto-suggest reorder quantitites based on sales velocity

## Screen: Expiry Reports & Compliance (Stage: تقارير الهيئة)
- **Layout:** Report builder: template selector (SFDA-compliant formats) + date range + export
- **Components:** ReportTemplateList (inventory valuation, expired items, sales by category, narcotics log), ExportButton (PDF/CSV/Excel), ComplianceChecklist (auto-verified), AuditLog (all inventory changes)
- **States:** Loading | No data | Report generated | Compliance: pass/fail
- **Key Interaction:** Select template → preview → export; compliance check → green/red indicator
- **Friction Resolved:** [#4] — SFDA-compliant reports ready for inspection

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Button Primary | Default, Small | hover/active/disabled/loading | bg-#00695C, white, 8px radius, 14px |
| Button Secondary | Default, Outline | hover/active/disabled | border-#004D40, text #004D40 |
| Input Field | Default, Search | focus/error/disabled | 12px padding, 8px radius |
| DrugAutoComplete | With barcode scan | typing/selected/no-results/loading | debounced 300ms, shows brand+generic |
| InventoryTable | Sortable columns | normal/hover row | sticky header, column sort, row click |
| ExpiryBadge | pill shape | >6mo #2E7D32 / <3mo #F9A825 / <1mo #C62828 |
| PrescriptionForm | drug + dosage + freq + duration | empty/draft/interaction-alert/complete | auto-calc daily dose, interaction API |
| InteractionAlert | Banner, Modal | info/warning/danger | drug name, interaction type, severity, suggestion |
| PatientCard | With history | normal/expanded | name, ID, allergies, drug history |
| POForm | Line items | draft/sent/received | add item, batch edit, auto-suggest |
| SupplierSelect | searchable | normal/loading | name, lead time, minimum order |
| AuditLog | Table, chronological | normal/filtered | action, user, timestamp, details |
| BarcodeScanner | Camera, Manual | scanning/success/error | auto-search on scan complete |
