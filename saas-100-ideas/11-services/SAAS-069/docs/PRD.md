# PRD: WeddingOrg (SAAS-069)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner**: منصة تنظيم حفلات الزفاف — قوائم ضيوف، ميزانية، موردون، جدول زمني، ومهام للعروسين ومنظمي الحفلات.
- **Problem statement**: تنظيم الزفاف في العالم العربي عملية معقدة تتضمن عشرات المهام (قاعة، فستان، تصوير، أكل، هدايا) بدون أداة رقمية موحدة لتنظيم الميزانية، إدارة الموردين، ومتابعة التقدم.
- **Proposed solution**: Laravel API + React Dashboard + Flutter App — منصة شاملة لتنظيم الزفاف: قوائم ضيوف ذكية، تتبع الميزانية، معرض موردين، وجدول زمني للمهام.

## 2. Market & Opportunity
- **Target market size**: سوق حفلات الزفاف ~$300B عالمياً، الشرق الأوسط ~$20B (متوسط تكلفة زفاف في الخليج $50K+).
- **Customer segment**: B2C — عرائس وعرسان (25-40 سنة), B2B — منظمي حفلات، موردي الزفاف (قاعات، مصورين، كوشة).
- **Competitor landscape**:
  1. **Zola**: أمريكي، قوائم هدايا فقط، لا يدعم العربية ولا الميزانية.
  2. **The Knot**: أكبر منصة زفاف عالمياً لكن إنجليزية، $0 فريميوم مع إعلانات.
  3. **Brides.com**: محتوى فقط، بدون أدوات تنظيمية.
  4. **WeddingWire**: منصة حجوزات أمريكية، بدون دعم عربي.
  5. **فرحة / زفاف.نت**: محتوى وأفكار عربي لكن بدون أدوات إدارة متكاملة.
- **Differentiation**: عربي كامل مع خصوصية (للمجتمعات العربية), قوائم ضيوف ذكية مع إدارة المقاعد, حاسبة ميزانية بالعملات المحلية, سوق موردين بقسائم خصم, تطبيق للعروسين + تطبيق منفصل للضيوف.

## 3. User Personas

### Primary: نوره — عروس
- **الدور**: تخطط لزواجها، تتابع 50+ مهمة لإنجاح الحفل.
- **الأهداف**: تنظيم الميزانية، اختيار الموردين، تنسيق قائمة الضيوف، متابعة التقدم.
- **نقاط الألم**: المهام كثيرة ومتفرقة، الخوف من نسيان شيء مهم، صعوبة التنسيق مع العريس والعائلة.

### Secondary: عبدالعزيز — عريس
- **الدور**: يشارك في التخطيط، يركز على قاعة الرجال والهدايا.
- **الأهداف**: متابعة الميزانية، تنسيق قاعة الرجال، إدارة هدايا العرسان.
- **نقاط الألم**: لا يعرف ما يحدث (العروس تدير كل شيء), صعوبة التنسيق مع والده.

### Admin: هدى — منظمة حفلات محترفة
- **الدور**: تنظم 20+ زفاف سنوياً، تدير فرق عمل متعددة.
- **الأهداف**: إدارة مشاريع متعددة، توثيق العقود، تنسيق الموردين.
- **نقاط الألم**: تتبع التقدم عبر إكسل غير فعال، العملاء يغيرون رأيهم كثيراً.

## 4. Features by Platform

### Laravel API (Backend)
- Core domain models: User (Bride/Groom), Planner, Wedding, Task, BudgetCategory, BudgetItem, Guest, GuestGroup, Vendor, VendorBooking, ChecklistItem, TimelineEvent
- RESTful endpoints: CRUD for all models
- Auth: Sanctum + social login
- Wedding profile: couple names, dates (engagement, wedding, bridal shower), theme, location
- Task management: milestones (engagement → wedding day → honeymoon), assign to couple/family
- Budget tracker: categories (venue, dress, catering, photography, etc), actual vs planned, overspend alerts
- Guest management: import from phone/CSV, RSVP tracking, meal preferences, seating assignments
- Vendor marketplace: category-based directory, reviews, booking requests
- Checklist: customizable templates by culture (Saudi, Egyptian, Levantine, Gulf)
- Timeline: countdown widget, event schedule for wedding day
- Table seating: virtual table designer, guest assignment
- Document storage: contracts, invoices, inspiration photos

### React Dashboard (Web)
- Dashboard: wedding countdown, tasks due this week, budget health
- Wedding planner: overview, edit details, theme/style
- Guest list: import, group (bride side/groom side), RSVP status, seating
- Budget: categories, items, actual vs planned, overspend alerts
- Vendor directory: browse, shortlist, contact, book
- Task checklist: templates, assign to, mark complete, due dates
- Seating chart: drag-drop table designer, guest placement
- Timeline: wedding day schedule (hour by hour)
- Mood board: save inspiration photos from web/mobile
- Documents: upload contracts, invoices, inspiration board

### Flutter App (Mobile)
- Couple app: checklist, budget, guest list, vendor browsing, timeline
- Guest app: receive invitation (via link/QR), RSVP, view wedding details, share photos
- Push notifications: RSVP received, task due, payment reminder, wedding countdown
- Photo sharing: guests upload wedding photos to shared album
- Offline: checklist and budget accessible offline

## 5. Data Model (MVP)

| Entity | Fields | Relationships |
|---|---|---|
| Wedding | id, bride_name, groom_name, wedding_date, engagement_date, location, theme, guest_count, budget_total | hasMany Task, BudgetItem, Guest, VendorBooking |
| Task | id, wedding_id, title, description, category, due_date, assignee, completed, notes | belongsTo Wedding |
| BudgetCategory | id, wedding_id, name_ar, name_en, budgeted_amount, sort_order | belongsTo Wedding |
| BudgetItem | id, category_id, description, vendor_name, estimated_cost, actual_cost, deposit_paid, status | belongsTo BudgetCategory |
| Guest | id, wedding_id, name, phone, side (bride/groom), group, rsvp_status (pending/accepted/declined), meal_preference, table_no | belongsTo Wedding |
| Vendor | id, name_ar, name_en, category (venue/photographer/catering/dress/flowers/makeup), phone, website, price_range, rating | — |
| VendorBooking | id, wedding_id, vendor_id, status (inquiry/shortlisted/booked/confirmed/cancelled), agreed_price, contract_url | belongsTo Wedding/Vendor |
| TimelineEvent | id, wedding_id, time, title, description, location, notes | belongsTo Wedding |

## 6. API Endpoints (MVP)

| Method | Endpoint | Description |
|---|---|---|
| POST | /api/auth/register | Register couple/planner |
| POST | /api/auth/login | Login |
| POST | /api/weddings | Create wedding profile |
| GET | /api/weddings/{id}/guests | Guest list (filterable: RSVP, side, group) |
| POST | /api/weddings/{id}/guests/import | Import guests (CSV/phone) |
| PATCH | /api/guests/{id}/rsvp | Update RSVP status |
| GET | /api/weddings/{id}/budget | Budget breakdown |
| POST | /api/weddings/{id}/budget/items | Add budget item |
| GET | /api/vendors | Vendor directory (filterable: category, city) |
| POST | /api/weddings/{id}/vendors/book | Book vendor |
| GET | /api/weddings/{id}/tasks | Task checklist |
| PATCH | /api/tasks/{id}/complete | Mark task complete |

## 7. User Interface (Screen List)

### Dashboard screens (React)
- Login
- Wedding Dashboard: countdown, budget health %, task progress
- Wedding Details: couple info, date, theme, location
- Guest Manager: list, RSVP tracker, seating chart designer
- Budget: categories expand, add items, track payments
- Vendor Marketplace: browse, filter, save favorites, book
- Task Checklist: template selection, custom tasks, assign
- Timeline: wedding day schedule editor
- Mood Board: photo gallery, upload from device/URL
- Documents: contract uploads, invoice tracking
- Settings: share with partner, privacy, notifications

### Mobile screens (Flutter)
- Couple App: Dashboard → Tasks → Budget → Guests → Vendors → Timeline
- Guest App: Invitation → RSVP → Wedding Info → Map → Photo Share
- Vendor discovery: browse by category, view profile, contact

### Screen flow (text)
```
Login → Dashboard (countdown + tasks + budget)
           ├── Wedding → Edit Details → Set Date → Choose Theme
           ├── Tasks → Select Template → Customize → Assign → Track
           ├── Budget → Categories → Add Items → Track Payments → Alerts
           ├── Guests → Import → Group → RSVP → Seating Chart
           │          → Drag Guests to Tables → Preview
           ├── Vendors → Browse → Compare → Shortlist → Request Booking
           │           → Manage Bookings → Contracts
           └── Timeline → Create Schedule → Share with Vendors
```

## 8. Business Model
- **Free**: $0 — basic checklist, 50 guests, manual budget
- **Couple Pro**: $9/month — unlimited guests, budget tracking, vendor marketplace, seating chart
- **Planner Pro**: $19/month — manage 10+ weddings, client portal, team collaboration
- **Vendor listing**: Free profile + subscription for featured listings ($29/month)
- **Free trial**: 14-day Couple Pro trial

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2)**: Laravel API — Wedding, Guest, Budget CRUD, Task checklist engine
- **Phase 2 (Weeks 3-4)**: React Dashboard — Guest manager, Budget tracker, Task board
- **Phase 3 (Weeks 5-6)**: Flutter App — Couple app, Vendor discovery, Guest app (RSVP)
- **Phase 4 (Weeks 7-8)**: Seating chart, Vendor marketplace, Timeline, Photo sharing, Testing, Deploy

## 10. Risk & Mitigation
- **Technical**: Seating chart drag-drop complexity — strategy: simplified grid layout, no complex geometry.
- **Market**: Seasonal demand — strategy: target engagement period (6-12 months before wedding), evergreen vendor subscriptions.
- **Competitive**: Free tools (Google Sheets) — strategy: rich visual experience, vendor marketplace value, automated RSVP collection.
- **Cultural**: Privacy concerns — strategy: private wedding profile by default, guest access via invite only.
