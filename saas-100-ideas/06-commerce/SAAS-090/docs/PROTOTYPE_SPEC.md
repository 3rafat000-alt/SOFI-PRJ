# PROTOTYPE SPEC — FurniturePro (SAAS-090)
> Owner: UI/UX Designer · Gate 2

## Screen: AR Room Viewer (Journey Stage: Preview Furniture)
- **Layout:** Full camera view with overlay — select product → point camera at room → product appears in AR → drag to position → resize → screenshot
- **Components:** CameraView, ARModelOverlay, ProductSelector, PositionHandle, ScaleSlider, ScreenshotButton, ShareButton
- **States:** Empty (camera permission request) | Loading (model downloading) | Error (camera unavailable) | Edge (low light → flashlight prompt)
- **Key Interaction:** Tap product → appears in room → pinch to scale → drag to position → tap checkmark to add to cart
- **Friction Resolved:** #1 — visualise furniture at home

## Screen: Product Catalog (Journey Stage: Browse Products)
- **Layout:** Category grid → tap → product grid → tap → detail page with variants (color/size/fabric), photos, AR button, price
- **Components:** CategoryCard, ProductCard, VariantPicker, ImageGallery, ARButton, PriceDisplay, AddToCartButton
- **States:** Empty (no products in category) | Loading (skeleton grid) | Error (failed load) | Edge (out of stock → notify me)
- **Key Interaction:** Swipe through product photos, tap AR button to preview
- **Friction Resolved:** #4 — unlimited virtual showroom

## Screen: Delivery & Installation Board (Journey Stage: Coordinate Delivery)
- **Layout:** Calendar view (month/week/day), delivery items per day, driver/installer assignment, status tracking
- **Components:** CalendarView, DeliveryItem, DriverAvatar, InstallerAvatar, StatusTimeline, RouteMap, ProofPhoto
- **States:** Empty (no deliveries scheduled) | Loading (skeleton) | Error (sync failed) | Edge (conflict — two deliveries same time)
- **Key Interaction:** Drag delivery to time slot → assigns driver automatically
- **Friction Resolved:** #2 — organised delivery scheduling

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Button | Primary (brown), Secondary, Ghost | hover/active/disabled/loading | Ripple, 8px radius |
| ARModelOverlay | 3D model on camera | loading/placed/interacting | Pinch to scale, drag |
| CategoryCard | Image + name | default/hover | Grid layout |
| ProductCard | Image + name + price + badge | default/hover/sold-out | Badge for sale/new/sold-out |
| VariantPicker | Swatch grid (color/size/fabric) | selected/unselected | Haptic feedback |
| ImageGallery | Swipeable carousel | zoom/fullscreen | Pinch zoom |
| CalendarView | Month/week/day | default/hover/selected/occupied | Tap date shows items |
| DeliveryItem | Order + address + status | pending/assigned/delivered/installed | Drag to reschedule |
| StatusTimeline | Vertical timeline | completed/current/pending | Animated dots |
| ProofPhoto | Thumbnail of delivery proof | empty/captured/verified | Modal full view |
