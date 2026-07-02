# PROTOTYPE SPEC — BudgetWave (SAAS-047)
> Owner: UI/UX Designer · Gate 2

## Screen: Dashboard (maps to Journey Stage: Overview)
- **Layout:** Balance card top, spending doughnut chart, category breakdown, recent transactions, quick-add FAB
- **Components:** BalanceCard, DoughnutChart, CategoryRow, TransactionItem, FabButton
- **States:**
  - Empty: "Add your first transaction to get started" + CTA
  - Loading: Skeleton balance card + chart
  - Error: "Failed to load dashboard" + retry
  - Edge: 1000+ transactions — recent shows last 10 only
- **Key Interaction:** Tap category → see breakdown; tap FAB → add transaction; tap chart → filter
- **Friction Resolved:** [#2] تصنيف مرئي واضح

## Screen: Add Transaction (maps to Journey Stage: Add)
- **Layout:** Amount input (numeric keypad), category grid, optional note + receipt photo, save button
- **Components:** AmountInput, CategoryGrid, NoteInput, PhotoButton, SaveButton
- **States:**
  - Default: Keyboard up, cursor in amount
  - Autofill: OCR scan fills amount + merchant + category
  - Error: "Save failed — check your connection"
  - Edge: Split transaction across categories
- **Key Interaction:** Enter amount → tap category → add note → save (3 taps)
- **Friction Resolved:** [#1] إدخال سريع بثلاث نقرات

## Screen: Budget Planner (maps to Journey Stage: Budget)
- **Layout:** Pie chart 50/30/20, category sliders, remaining per category, adjust button
- **Components:** BudgetPie, CategorySlider, RemainingBadge, AutoAllocateButton
- **States:**
  - Empty: "Set up your first budget based on your income"
  - Active: Sliders with percentages, remaining amounts
  - Over budget: Red highlight on exceeded categories
  - Loading: Budget calculation skeleton
- **Key Interaction:** Drag slider → percentage updates → auto-adjust others
- **Friction Resolved:** [#4] تعديل الميزانية بسهولة

## Screen: Goals (maps to Journey Stage: Save)
- **Layout:** Goal cards with progress rings, add goal button, suggestions
- **Components:** GoalCard, ProgressRing, AddGoalForm, SuggestionChip
- **States:**
  - Empty: "Set your first savings goal" + examples
  - Active: Progress cards sorted by deadline
  - Completed: Confetti + congratulations
  - Behind: "You're behind — suggest cutback from..."
- **Key Interaction:** Tap goal → see details + add to goal now
- **Friction Resolved:** [#3] أهداف ادخار مع تتبع

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| BalanceCard | income/expense/net | default, updated (pulse) | Animated number count |
| DoughnutChart | category breakdown | interactive (hover = legend) | Tap slice to filter |
| AmountInput | currency prefix | default, focus, error | Auto-format with commas |
| CategoryGrid | icons + labels | default, selected, disabled (over-budget) | Custom sort by freq |
| BudgetSlider | percentage with label | dragging, over-limit (red), disabled | Snap to 5% increments |
| GoalCard | progress ring | on-track, behind, completed | ring colour changes (green/amber/green) |
| ProgressRing | animated arc | default, complete (full circle) | Value in centre, label below |
| TransactionItem | income/expense | default, swipe to delete | Category icon left, amount right |
