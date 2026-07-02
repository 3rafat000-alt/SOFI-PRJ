# PROTOTYPE SPEC — TextilePro (SAAS-083)
> Owner: UI/UX Designer · Gate 2

## Screen: Factory Dashboard (Journey Stage: Monitor All)
- **Layout:** KPI bar (output today, efficiency %, downtime), Gantt chart for active orders, machine status grid, quality gauge
- **Components:** KpiCard, GanttChart, MachineGrid, QualityGauge, AlertStream
- **States:** Empty (setup wizard) | Loading (skeleton) | Error (retry) | Edge (night shift reduced view)
- **Key Interaction:** Tap Gantt bar → order detail modal
- **Friction Resolved:** #1 — see all active orders timeline

## Screen: Stage Pipeline (Journey Stage: Track Production)
- **Layout:** Horizontal pipeline with 4 stages (Spinning → Weaving → Dyeing → Finishing), each stage shows active orders
- **Components:** PipelineStage, OrderCard, BottleneckWarning, Timer
- **States:** Empty (no active orders) | Loading (skeleton) | Error (disconnected) | Edge (bottleneck highlighted red)
- **Key Interaction:** Drag order card to next stage → confirm output
- **Friction Resolved:** #2 — detect bottlenecks visually

## Screen: Machine Monitor (Journey Stage: Monitor Machines)
- **Layout:** Grid of machine cards, each showing name, current job, status (running/idle/breakdown), runtime
- **Components:** MachineCard, StatusDot, TimerCounter, MaintenanceBadge
- **States:** Empty (no machines configured) | Loading (skeletons) | Error (connection lost) | Edge (breakdown flash red + sound)
- **Key Interaction:** Tap machine → detail with history chart
- **Friction Resolved:** #3 — real-time machine status

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Button | Primary (indigo), Secondary, Ghost | hover/active/disabled/loading | Ripple, 8px radius |
| KpiCard | Value + label + trend arrow | default/positive/negative | Trend arrow green/red |
| GanttChart | Order bars by stage | default/hover/selected | Horizontal scroll, zoom |
| PipelineStage | 4-stage horizontal | empty/running/completed/blocked | Progress bar per stage |
| MachineCard | Status colored border | running(green)/idle(yellow)/breakdown(red) | Pulse animation on breakdown |
| QualityGauge | Arc gauge 0-100% | pass(>90%)/warning(70-90%)/fail(<70%) | Needle animation |
| AlertStream | Scrollable alert list | info/warning/critical | Auto-scroll, dismiss |
