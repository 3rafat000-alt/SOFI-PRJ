# PROTOTYPE SPEC — BakeryMgt (SAAS-088)
> Owner: UI/UX Designer · Gate 2

## Screen: Recipe Manager (Journey Stage: Manage Recipes)
- **Layout:** Left sidebar: product categories. Center: recipe list. Right: full recipe detail with ingredients, steps, photos, cost breakdown
- **Components:** CategoryNav, RecipeCard, IngredientRow, StepList, PhotoGallery, CostSummary, ScaleSlider
- **States:** Empty (no recipes → import from template) | Loading (skeleton) | Error (failed load) | Edge (version history)
- **Key Interaction:** Drag slider to scale → ingredient quantities update in real-time
- **Friction Resolved:** #1 — instant recipe scaling

## Screen: Production Planner (Journey Stage: Plan Daily Production)
- **Layout:** Calendar at top, production list below grouped by day, each item shows product + quantity + status
- **Components:** CalendarView, ProductionItem, StatusBadge, ChefAssign, Checklist
- **States:** Empty ("Plan tomorrow's production") | Loading (skeleton) | Error (failed) | Edge (holiday → reduced schedule)
- **Key Interaction:** Tap day → see production list → check off items as completed
- **Friction Resolved:** #3 — organised daily production

## Screen: Online Ordering (Journey Stage: Customer Order)
- **Layout:** Product catalog grid → tap → variant picker (size/qty) → cart → checkout with date/time selection → payment
- **Components:** ProductGrid, VariantPicker, CartDrawer, DateTimePicker, PaymentForm
- **States:** Empty (cart empty) | Loading (payment processing) | Error (payment failed) | Edge (minimum order notice)
- **Key Interaction:** Select delivery date → see available time slots
- **Friction Resolved:** #4 — streamline event orders

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Button | Primary (brown), Secondary, Ghost | hover/active/disabled/loading | Ripple, 8px radius |
| ScaleSlider | Horizontal slider with quantity label | idle/dragging/calculated | Updates ingredient list live |
| RecipeCard | Image + name + cost + time | default/hover/selected | Expand to full view |
| IngredientRow | Name + quantity + unit + cost | default/low-stock | Red highlight if low stock |
| ProductionItem | Product + qty + chef + status | pending/in-progress/done | Checkbox to complete |
| CategoryNav | Vertical category list | selected/unselected | Collapsible subcategories |
| ProductGrid | Catalog grid | default/hover/selected | Heart icon for favorites |
| VariantPicker | Size/type selector | selected/unselected | Radio button style |
