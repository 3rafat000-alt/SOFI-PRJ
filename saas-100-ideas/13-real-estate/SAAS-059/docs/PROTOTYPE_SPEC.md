# PROTOTYPE SPEC — RealtyCRM (SAAS-059)
> Owner: UI/UX Designer · Gate 2

## Screen: Property Management (maps to Journey Stage: تسجيل عقار)
- **Layout:** Property list + add button + filters (type, purpose, status, city)
- **Components:** PropertyTable, FilterBar, AddPropertyFAB, StatusBadge, PropertyCard
- **States:** Empty (no properties → "أضف عقارك الأول"), Loading (skeleton table), Error, Edge (draft → yellow badge "مسودة")
- **Key Interaction:** Tap FAB → wizard: info → media → tour → publish
- **Friction Resolved:** [#1] إدخال بيانات متكرر → نموذج نشر موحد

## Screen: Add Property Wizard (maps to Journey Stage: تسجيل عقار)
- **Layout:** Multi-step: basic info → media upload → virtual tour → pricing → publish
- **Components:** StepIndicator, ImageUploader, TourEmbedder, PriceInput, PublishButton
- **States:** Empty (fresh form), Draft (auto-save), Saving, Error (upload failed → retry), Edge (invalid data → step error highlight)
- **Key Interaction:** Step by step with auto-save, optional virtual tour step

## Screen: Lead Pipeline (maps to Journey Stage: استقبال العملاء)
- **Layout:** Kanban columns: new → contacted → interested → negotiation → closed/lost
- **Components:** KanbanColumn, LeadCard, DragHandle, QuickActionMenu
- **States:** Empty (no leads → "لا يوجد عملاء جدد"), Loading, Error, Edge (lost lead → grey with reason)
- **Key Interaction:** Drag lead between stages, tap for detail
- **Friction Resolved:** [#2] متابعة العملاء يدوياً → مرئي وآلي

## Screen: Contract Builder (maps to Journey Stage: إعداد العقد)
- **Layout:** Template selector + party details + property info + terms + e-sign
- **Components:** TemplateSelector, PartyForm, PropertySummary, TermsEditor, SignaturePad
- **States:** Empty (new contract), Loading (generating PDF), Error (signature failed)
- **Key Interaction:** Select template → fill parties → add terms → send for signature

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| PropertyCard | grid, list | draft, published, rented, sold | Status color badge |
| ImageUploader | single, multi | empty, uploading, uploaded, error | Drag-drop + camera |
| KanbanColumn | lead stages | has-items, empty, drag-over | Drag-and-drop |
| LeadCard | new, contacted, negotiation | default, urgent | Priority indicator |
| SignaturePad | finger, mouse | empty, signed, verified | Capture + verify |
| TourEmbedder | 360°, video | empty, loading, embedded | Iframe embed |
