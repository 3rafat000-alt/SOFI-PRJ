# PROTOTYPE SPEC — WeddingOrg (SAAS-069)
> Owner: UI/UX Designer · Gate 2

## Screen: Wedding Dashboard (maps to Journey Stage: بدء التخطيط)
- **Layout:** Top countdown widget, below progress rings (tasks/budget/guests/vendors), bottom timeline of upcoming milestones
- **Components:** CountdownWidget, ProgressRing, MilestoneTimeline, QuickActionGrid, MoodBoardPreview
- **States:** Empty (new wedding) | Loading | Error | Edge (T-7 days — urgent mode with red highlights)
- **Key Interaction:** Tap progress ring → opens detail for that category
- **Friction Resolved:** #1 (central command center)

## Screen: Guest List Manager (maps to Journey Stage: قائمة الضيوف)
- **Layout:** Top search + import buttons, main table with name/phone/side (bride/groom)/RSVP/meal/table, right sidebar guest detail
- **Components:** GuestTable, RSVPBadge, SideFilter (bride/groom/both), ImportCSVButton, TableAssignDropdown, MealPreferenceChip
- **States:** Empty (no guests) | Loading (importing) | Error (CSV parse error) | Edge (over 500 guests — virtual scroll)
- **Key Interaction:** Click RSVP cell → quick toggle (pending/accepted/declined)
- **Friction Resolved:** #4 (guest list management)

## Screen: Budget Tracker (maps to Journey Stage: الميزانية)
- **Layout:** Top summary bar (planned vs actual vs remaining), below category cards expandable to items
- **Components:** BudgetSummaryBar, CategoryCard, BudgetItemRow, OverspendAlert, DepositProgress
- **States:** Empty (no budget set) | Loading | Error | Edge (overspent — red category card)
- **Key Interaction:** Tap category → expand to see itemized costs
- **Friction Resolved:** #2 (budget tracking with alerts)

## Screen: Vendor Marketplace (maps to Journey Stage: اختيار الموردين)
- **Layout:** Category grid (venue/photographer/catering/dress/flower/makeup), card shows name/price/rating/quick contact
- **Components:** VendorCard, CategoryGrid, RatingStars, PriceRangeBadge, ShortlistButton, BookingRequestForm
- **States:** Empty (no vendors in area) | Loading (searching) | Error | Edge (vendor fully booked — show alternatives)
- **Key Interaction:** Save to shortlist → compare 3-4 vendors side by side
- **Friction Resolved:** #3 (vendor discovery and comparison)

## Screen: Seating Chart (maps to Journey Stage: توزيع المقاعد)
- **Layout:** Top toolbar (add table, zoom), canvas area with round/rectangle tables, guest avatars drag-drop
- **Components:** TableShape (round/rect), GuestAvatarDraggable, TableLabel, UnassignedGuestBar, AutoArrangeButton
- **States:** Empty (no tables) | Loading | Error | Edge (table overflow — add table prompt)
- **Key Interaction:** Drag guest from unassigned bar onto table
- **Friction Resolved:** #5 (visual seating arrangement)

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Button | Primary #E11D48, Secondary #D97706, Ghost | hover/active/disabled/loading | 44px |
| CountdownWidget | Large/Compact | days/hours/minutes remaining | Animated flip |
| ProgressRing | Tasks/Budget/Guests/Vendors | 0-100% | Colour transitions green→amber→red |
| GuestTable | Searchable, sortable, filterable | empty/loading/populated | Virtual scroll > 500 |
| RSVPBadge | Pending/Accepted/Declined | — | Yellow/green/red pill |
| CategoryCard | Venue/Catering/Photo/etc | expanded/collapsed | Expandable item list |
| VendorCard | Compact/Detailed | default/shortlisted/booked | Heart icon for shortlist |
| TableShape | Round/Rectangle | empty/full/highlighted | Drag-drop guest to table |
| TimelineEvent | Milestone/Task/Appointment | completed/pending/overdue | Timeline connector left |
