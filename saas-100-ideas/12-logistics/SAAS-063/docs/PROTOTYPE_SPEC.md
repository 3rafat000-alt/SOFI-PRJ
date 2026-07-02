# PROTOTYPE SPEC — TruckNet (SAAS-063)
> Owner: UI/UX Designer · Gate 2

## Screen: Live Map View (maps to Journey Stage: تتبع مباشر)
- **Layout:** Full-screen map (Mapbox), right sidebar truck list + status filter, bottom stats bar
- **Components:** MapCanvas, TruckMarker (color-coded), TruckInfoPopup, FilterChips, StatsBar
- **States:** Empty (no active trucks) | Loading (map tiles loading) | Error (location API down) | Edge (truck lost GPS signal)
- **Key Interaction:** Click truck marker → popup with speed, driver, ETA, trip status
- **Friction Resolved:** #1 (no phone calls needed to locate trucks)

## Screen: Trip Planner (maps to Journey Stage: تخطيط الرحلة)
- **Layout:** Left column form (origin/destination/cargo/driver/truck), right column map preview with route
- **Components:** AutocompleteInput (cities), DriverSelect, TruckSelect, CargoForm, RoutePreview, ETAEstimate
- **States:** Empty | Loading (calculating route) | Error (no drivers available) | Edge (oversize cargo restrictions)
- **Key Interaction:** Select origin + destination → auto-suggest available trucks nearby
- **Friction Resolved:** #2 (manual scheduling)

## Screen: Trip Board (maps to Journey Stage: توجيه السائق)
- **Layout:** Kanban columns (Planned → Active → Completed → Delayed), cards show truck + driver + cargo
- **Components:** KanbanBoard, TripCard, StatusBadge, AssigneeAvatar, TimerBadge
- **States:** Empty (no trips today) | Loading | Error | Edge (trip delayed > 2h — red highlight)
- **Key Interaction:** Drag card to change status, click for trip detail modal
- **Friction Resolved:** #2 (visual trip management)

## Screen: Driver App — Trip View (maps to Journey Stage: بدء الرحلة + التسليم)
- **Layout:** Top strip current trip status + timer, middle map with GPS trail, bottom buttons (Start/Arrive/Complete)
- **Components:** GPSMap, StatusBar, ActionButton (large), WaypointList, ProofCapture (photo + signature)
- **States:** Empty (no trip assigned) | Loading (fetching trip) | Error (GPS denied) | Edge (offline mode — cached trip data)
- **Key Interaction:** Tap "Arrived at Waypoint" → confirm with photo → auto-advance
- **Friction Resolved:** #3 (POD capture replaces paper)

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Button | Primary #2563EB, Secondary #EA580C, Ghost | normal/hover/active/disabled/loading | 48px for mobile |
| MapCanvas | Default, Satellite, Traffic | loading/ready/error | Cluster markers > 5 trucks |
| TruckMarker | Moving/Idle/Stopped/Offline | default/selected/loading | Color-coded dot + direction arrow |
| TripCard | Planned/Active/Completed/Delayed | default/dragging/overdue | Left border colour |
| AutoComplete | City input with suggestions | default/focus/loading/no-results | Debounce 300ms |
| ActionButton | Start/Arrive/Complete/POD | default/loading/disabled/discrete | Full-width mobile |
| ProofCapture | Photo + Signature pad | empty/captured/uploading/error | Compress to 800px max |
| WaypointList | Ordered list with icons | collapsed/expanded/complete | Expand for details |
| StatsBar | 4 cards (active/en route/delayed/idle) | normal/loading | Auto-refresh 30s |
