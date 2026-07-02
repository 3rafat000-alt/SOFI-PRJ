# PROTOTYPE SPEC — WaterMgt (SAAS-073)
> Owner: UI/UX Designer · Gate 2

## Screen: Network Map (maps to Journey Stage: صيانة)
- **Layout:** Full-screen GIS map with layered controls
- **Components:** MapView (Leaflet/Mapbox), ZonePolygons, AssetPins, LeakHeatmap, WorkOrderClusters, LayerToggle
- **States:** Empty → No assets loaded → import prompt; Loading → Map tiles loading; Error → "تعذر تحميل الخريطة" → check internet; Edge → GPS offline → cached tiles + manual location
- **Key Interaction:** Pinch zoom; Tap asset → detail sheet; Tap leak → dispatch work order
- **Friction Resolved:** #3 — Visual network map instead of paper list

## Screen: Meter Reading Capture (maps to Journey Stage: قراءة عدادات)
- **Layout:** Camera viewfinder + manual entry card
- **Components:** CameraViewfinder (OCR overlay), ManualEntryPad, MeterPhotoCapture, PreviousReadingCard, SubmitButton
- **States:** Empty → "وجّه الكاميرا نحو العداد"; Loading → Processing OCR; Error → "لم نتمكن من قراءة العداد" → manual entry; Edge → Meter inaccessible (locked behind wall) → flag for supervisor
- **Key Interaction:** Point camera → auto-capture (detect meter dial) → confirm reading
- **Friction Resolved:** #2 — OCR + camera capture for hard-to-reach meters

## Screen: Work Order Board (maps to Journey Stage: صيانة)
- **Layout:** Kanban board (Pending → Assigned → In Progress → Completed)
- **Components:** KanbanColumn, WorkOrderCard (type, priority, address, tech), DragHandle, FilterBar, MapToggle
- **States:** Empty → "لا توجد أوامر عمل"; Loading → Skeleton columns; Error → Sync failed → retry; Edge → Urgent leak overrides normal priority → highlight red
- **Key Interaction:** Drag order between columns; Tap card → detail sheet
- **Friction Resolved:** #5 — Digital work orders replace paper

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| MapView | full, half, mini | loading/loaded/error | Pan/zoom, pinch |
| AssetPin | water-pipe, valve, meter, hydrant | normal/alert/offline | Colored icons |
| WorkOrderCard | leak, meter, install, repair | urgent/high/normal/low | Red = urgent |
| KanbanColumn | 4 variants | has-items/empty/droppable | Drag-and-drop |
| CameraViewfinder | OCR, photo, document | idle/capturing/processing | Auto-detect dial |
| ReadingCard | previous, current | confirmed/pending/error | Compare side-by-side |
| LeakAlert | active, resolved | new/acknowledged/resolved/false-positive | Push notification |
