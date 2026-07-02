# PROTOTYPE SPEC — PrintHub (SAAS-084)
> Owner: UI/UX Designer · Gate 2

## Screen: Order Pipeline Kanban (Journey Stage: Track All Orders)
- **Layout:** Horizontal kanban columns — Pending, Design, Proof, Production, Finishing, Delivery, Completed
- **Components:** KanbanColumn, OrderCard, AvatarGroup, TimerBadge, PriorityFlag
- **States:** Empty ("No orders today") | Loading (skeleton columns) | Error (failed load) | Edge (overdue orders highlighted red)
- **Key Interaction:** Drag card between columns → auto-update status + notify customer
- **Friction Resolved:** #1 — centralised order view

## Screen: Proof Viewer (Journey Stage: Approve Design)
- **Layout:** Full-page PDF viewer (left), annotation sidebar (right), approval buttons (bottom)
- **Components:** PdfViewer, AnnotationTool, ApprovalButton (Approve/Reject), CommentThread
- **States:** Empty (no proof uploaded) | Loading (PDF rendering) | Error (file corrupt) | Edge (auto-approve countdown timer)
- **Key Interaction:** Tap "Approve" → moves to production. Tap "Reject" → comment required → notifies designer
- **Friction Resolved:** #2 — faster proof turnaround

## Screen: New Order Form (Journey Stage: Submit Order)
- **Layout:** Multi-step wizard — Step 1: Product Type, Step 2: Specs (size/paper/qty), Step 3: File Upload, Step 4: Review & Confirm
- **Components:** Stepper, ProductSelector, SpecInput, FileUploader (chunked), PriceSummary
- **States:** Empty (step 1) | Loading (uploading) | Error (file too large, invalid format) | Edge (chunked upload progress)
- **Key Interaction:** Drag & drop files, real-time price calculation
- **Friction Resolved:** #3 — structured order with validation

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Button | Primary (indigo), Secondary, Ghost | hover/active/disabled/loading | Ripple, 8px radius |
| KanbanColumn | Header with count | default/hover/drag-over | Accepts drop, animates |
| OrderCard | Compact order info | default/hover/dragging | Shadow on drag, smooth |
| PdfViewer | Full screen, sidebar | loading/loaded/error/zoom | Page turn, annotation overlay |
| FileUploader | Single, Multi, Chunked | empty/uploading/done/error | Drag zone, progress bar |
| ProductSelector | Grid of product cards | selected/unselected/hover | Radio + image |
| SpecInput | Dynamic per product type | valid/invalid | Price updates instantly |
