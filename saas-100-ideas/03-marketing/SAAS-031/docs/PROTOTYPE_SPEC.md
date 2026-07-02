# PROTOTYPE SPEC — SurveyCraft (SAAS-031)
> Owner: UI/UX Designer · Gate 2

## Screen: Landing / Signup (maps to Journey Stage: Discover → Register)
- **Layout:** Centered card layout, brand hero illustration, social proof (logos, stats)
- **Components:** Hero section, feature grid, pricing table, signup form
- **States:** Empty (first visit) | Loading (form submit) | Error (invalid email) | Edge case (existing email → redirect login)
- **Key Interaction:** User fills email → selects plan → submits → OTP verification
- **Friction Resolved:** #1 — قلة الخيارات العربية موضحة باللغة العربية منذ البداية

## Screen: Survey Builder (maps to Journey Stage: Build)
- **Layout:** 3-column — left (question types palette), center (canvas/preview), right (properties panel)
- **Components:** Drag-drop question blocks, property editor, logic rule builder, theme selector
- **States:** Empty (no questions yet) | Loading (saving) | Error (condition conflict detected) | Edge case (100+ questions → virtual scroll)
- **Key Interaction:** Drag question type → drop on canvas → configure in right panel → set conditions
- **Friction Resolved:** #2 — واجهة بصرية لقواعد If/Then

## Screen: Response Dashboard (maps to Journey Stage: Collect → Analyze)
- **Layout:** Top stats bar (total responses, completion rate, avg time) + chart grid below
- **Components:** Chart cards (bar, pie, line, heatmap), filter bar, data table toggle
- **States:** Empty (no responses) | Loading (chart rendering) | Error (data load failure) | Edge case (50k+ responses → paginated charts)
- **Key Interaction:** Click chart segment → drill-down to individual responses
- **Friction Resolved:** #3 — تحليلات مرئية مع cross-tabulation

## Screen: Share Modal (maps to Journey Stage: Distribute)
- **Layout:** Modal overlay with tabbed options — Link, QR, Embed, Email, WhatsApp
- **Components:** Copy button, QR code preview, embed code block
- **States:** Empty (first share) | Loading (generating QR) | Error (QR generation failed) | Edge case (link expired)
- **Key Interaction:** Click copy icon → link copied toast → paste anywhere
- **Friction Resolved:** #4 — مشاركة عبر قنوات متعددة

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Button Primary | Default, Small, Large, Icon-only | hover/active/disabled/loading | bg-orange-500, white text, 8px radius, loading spinner |
| Button Secondary | Default, Small | hover/active/disabled | bg-white, border orange-500, orange text |
| Input Field | Text, Email, Password, Number | focus/error/disabled/success | border 1px gray-300, 12px padding, error border red-500 |
| Question Block | 12 types (MCQ, Likert, Rating, etc.) | default/hover/drag/active | drag handle left, question title, options below |
| Chart Card | Bar, Pie, Line, Heatmap | loading/empty/error/data | responsive SVG, tooltip on hover, legend |
| Modal | Share, Preview, Export | open/close/loading | backdrop blur, centered, close on Escape |
| Toast | Success, Error, Warning, Info | show/hide | auto-dismiss 4s, slide from top-right |
