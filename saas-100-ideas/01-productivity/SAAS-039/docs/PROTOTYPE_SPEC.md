# PROTOTYPE SPEC — FormBuilder (SAAS-039)
> Owner: UI/UX Designer · Gate 2

## Screen: Form Builder (maps to Journey Stage: Build)
- **Layout:** 3-column — left (element palette), center (canvas/live preview), right (properties panel)
- **Components:** Element palette (text, email, number, file, MCQ, checkbox, dropdown, rating, matrix), canvas, properties
- **States:** Empty (blank canvas) | Loading (auto-save) | Error (save fail) | Edge case (100+ elements → virtual scroll)
- **Key Interaction:** Drag element → canvas → click element → edit properties in right panel
- **Friction Resolved:** #1 — بناء النموذج بالسحب والإفلات

## Screen: Condition Builder (maps to Journey Stage: Conditions)
- **Layout:** Modal with visual rule builder — "If [question] [operator] [value] then [action]"
- **Components:** Rule row (question dropdown, operator, value input, action dropdown), AND/OR connector, preview highlight
- **States:** Empty (no rules) | Editing | Valid (no conflicts) | Conflict (overlapping rules) | Edge case (20+ rules on one element)
- **Key Interaction:** Select trigger question → choose operator → set value → choose action (show/hide/skip) → live test
- **Friction Resolved:** #1 — شروط منطقية بصرية

## Screen: Submissions Dashboard (maps to Journey Stage: Collect → Analyze)
- **Layout:** Stats bar (total, today, completion rate) + table view + individual response view toggle
- **Components:** Stats card, response table (respondent, date, status), individual response view, chart toggle
- **States:** Empty (no responses) | Loading | Error | Edge case (10k+ responses → paginated)
- **Key Interaction:** Click row → see individual responses → click "View as PDF"
- **Friction Resolved:** #2 — مركزية الردود

## Screen: Form Preview / Test (maps to Journey Stage: Preview)
- **Layout:** Exact rendering of the published form — responsive (mobile/tablet/desktop toggle)
- **Components:** Rendered form with all conditions active, progress bar, submit button
- **States:** Preview (default) | Testing (submit test data) | Submitted (success message)
- **Key Interaction:** Fill form as respondent → conditions trigger → see dynamic behavior → submit
- **Friction Resolved:** #3 — اختبار سريع للتجربة

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Element Palette | 10 element types | default/hover/dragging | drag to canvas, tooltip on hover |
| Canvas Element | All form input types | default/hover/selected/focus | click to select, drag to reorder |
| Rule Row | If/Then condition | default/active/conflict | operator dropdown, value input |
| Stats Card | Total, Today, Rate | default | number + label + icon |
| Response Row | Email + status + date | default/hover/selected | click to expand individual view |
| Progress Bar | Form completion | filling/complete | percentage + step count |
| Preview Toggle | Mobile/Tablet/Desktop | selected/not | responsive width simulation |
| Element Properties | Side panel | open/closed | auto-update canvas on change |
