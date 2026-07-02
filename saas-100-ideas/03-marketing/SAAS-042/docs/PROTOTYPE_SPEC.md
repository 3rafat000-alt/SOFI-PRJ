# PROTOTYPE SPEC — PollPro (SAAS-042)
> Owner: UI/UX Designer · Gate 2

## Screen: Poll Builder (maps to Journey Stage: Create)
- **Layout:** Step wizard (type → questions → options → settings), live preview sidebar
- **Components:** WizardStepper, PollTypeCard, QuestionForm, OptionList, PreviewPane
- **States:**
  - Empty: "Choose a poll type to start" with 4 type cards
  - Loading: Saving spinner
  - Error: "Failed to save draft" + retry
  - Edge: 20+ questions — scrollable, reorderable list
- **Key Interaction:** Select type → questions appear → add options via "Add option" button
- **Friction Resolved:** [#2] تقليل النقرات لإنشاء تصويت

## Screen: Projector View (maps to Journey Stage: Launch)
- **Layout:** Fullscreen with large QR code, poll title, live bar/column chart, vote count
- **Components:** QRDisplay, LiveChart, CountBadge, FullscreenToggle
- **States:**
  - Loading: "Waiting for first vote..."
  - Active: Live chart animating with each vote
  - Error: "Connection lost — reconnecting..."
  - Edge: 1000+ votes — throttled animation (batch updates)
- **Key Interaction:** QR auto-updates, chart animated with WebSocket data
- **Friction Resolved:** [#1] نتائج فورية بتأخير <500ms

## Screen: Mobile Join (maps to Journey Stage: Join)
- **Layout:** Camera scanner OR enter 6-digit code, then question display
- **Components:** QRScanner, CodeInput, QuestionCard, OptionButton, SubmitButton
- **States:**
  - Empty: Scan QR or enter code
  - Loading: Connecting to poll...
  - Error: "Invalid code" + shake animation
  - Edge: No camera — fallback code entry
- **Key Interaction:** Scan → auto-join poll → show question
- **Friction Resolved:** [#3] دخول فوري بدون حساب

## Screen: Mobile Vote (maps to Journey Stage: Vote)
- **Layout:** Question text at top, option cards (large touch targets), submit button
- **Components:** OptionCard (selected/unselected), ProgressBar, SubmitButton
- **States:**
  - Voting: Options displayed, one selected
  - Submitted: "Vote recorded!" with checkmark
  - Error: "Failed to submit — tap to retry"
  - Edge: Offline — queued locally, submit when online
- **Key Interaction:** Tap option → highlight → tap Submit → animated checkmark
- **Friction Resolved:** [#4] تجربة تصويت سريعة بلمسة واحدة

## Screen: Results (maps to Journey Stage: Results)
- **Layout:** Animated chart (bar/pie), vote count, share button
- **Components:** AnimatedBarChart, VoteCount, ShareButton, QuestionSwitcher
- **States:**
  - Live: Real-time animation
  - Final: "Poll closed" banner, export button
  - Empty: "No votes yet"
- **Key Interaction:** Chart animates with new votes in real-time
- **Friction Resolved:** [#1] نتائج فورية

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| PollTypeCard | multiple/single/rating/text | default, selected, hover | Icon + label, click to select |
| OptionCard | default, selected, correct, wrong | default, selected, disabled | Touch ripple on mobile |
| LiveChart | bar, column, pie, doughnut | animating, paused, empty | WebSocket-driven transitions |
| QRDisplay | default, large (projector) | active, expired | Auto-refresh every 30s |
| CodeInput | 6-digit | default, error, success | Auto-advance, shake on error |
| QuestionCard | single, multi, rating | unanswered, answered | Swipeable for multi-question |
