# CONTEXT — PRJ-SYRH (durable facts; append-only)
- Fix (2026-06-29): Properties page area dropdown filters by selected governorate via `fetchAreas(governature)`. Falls back to popular_areas when no governorate. Commit 38b5c750.
- Fix (2026-06-29): Real-time chat — Echo WebSocket now routes through Caddy reverse proxy (`/app/*` → localhost:8081) instead of direct port 8081. Caddy zanjour.caddy adds `@reverb path /app/*` handler before API. echo.ts omits wsPort when VITE_REVERB_PORT unset (pusher-js defaults to 443/80). forceTLS from VITE_REVERB_SCHEME (https). .env: REVERB_HOST=syriahomes.zanjour.com, REVERB_SCHEME=https, VITE_REVERB_PORT removed.
- Fix (2026-06-29): QUEUE_CONNECTION=database + no queue worker → broadcast events never processed. Changed to sync. QuickReplyController::send() missing event(new NewMessage(...)) → user never notified via WebSocket. QuickReply::render() now strips unreplaced {placeholders}. Frontend .env VITE_REVERB_PORT=8081 removed (was overriding Echo config).
- title: Syria Homes — Real Estate Marketplace SaaS
- priority: HIGH  
- stack: Laravel 12 / PostgreSQL 18 · API /api/v1 · Sanctum · Spatie Permission · i18n ar(primary)/en
- Brand: green (#1a6b3c), gold (#c9a84c), beige/cream (#f5f0e8)
- Arabic-first RTL — all user-facing strings Arabic
- Database `syriahomes`, user `es3dlll`, PostgreSQL 18
- 38 database tables (including conversations, chat_messages)
- Installation system: 5-step wizard, `storage/installed` sentinel, InstallerGuard middleware
- Served by Caddy directly (no Vite, no artisan serve). SPA from `frontend/dist/`
- Navbar: single gold "حساب جديد" CTA for guests, avatar+initials for logged-in users
- Chat: polling-based (5s interval), Conversation + ChatMessage models, agency-side UI complete
- User-facing chat: UserChatController, UserChat.tsx user inbox, ChatWidget on property pages, all wired
- Latest commit: 1d6d900 (TKT-042: Agencies redesign — live chat only, no external contact)
- Properties listing: full filter panel (purpose, type, governorate, area, bedrooms, bathrooms, price), grid/list toggle, search with debounce, mobile drawer
- PropertyDetail: full-bleed editorial hero with gradient overlay, floating gallery thumbnails strip, 4 large stat cards, flowing single-column layout (no tabs), gold-accented section dividers, dark gradient agent section with only 'Chat on Platform' button, no phone/WhatsApp/email, sticky mobile bar with price + Chat only, guest registration prompt, ChatWidget listens for 'open-chat' event
- Communication only in-app — all external contact methods removed
- Public/storage symlink fixed: `../../backend/storage/app/public`
- Caddy /storage/* serves static files directly
- Model-role-permission: Spatie with 'admin', 'agency', 'user' roles
- Three dashboards: admin → /admin, agency → /dashboard, user → /user/dashboard
- Route guard: ProtectedRoute with optional role prop
- 56 API endpoints across 5 groups (public, auth, user, agency, admin) + 7 install = 63 total
- ChatController: 5 endpoints (conversations list, messages, send, mark read, unread count)
- All 37 translation keys in ar.ts / en.ts
- Latest commit: 1d6d900 (TKT-042: Agencies redesign — live chat only, no external contact)
- SAKK payment integration (real SAKK at sakk.zanjour.com):
  - SakkService: creates Payment Requests via SAKK API (Sanctum auth)
  - SakkWebhookController: receives `payment_request.paid` event with HMAC-SHA256 verification
  - subscribe() flow: create subscription (trial) → SAKK payment → webhook → activate
  - Merchant: سورية هومز (#2), fully active, linked to admin, webhook configured
  - Token: Sanctum 8|FNr... (wallet:read,wallet:write,transfer,profile)
  - API: https://sakk.zanjour.com/api/v1, Pay: https://sakk.zanjour.com/pay/{uuid}
  - Callback: https://syriahomes.zanjour.com/api/v1/sakk/webhook
- Property image upload: POST /agency/properties/{id}/images (multipart, max 10)
- Property image delete: DELETE /agency/properties/{id}/images/{imageId}
- Set cover image: POST /agency/properties/{id}/images/{imageId}/cover
- Property delete: DELETE /agency/properties/{id} (cascade cleans files)
- Fixed AgenciesController cover_image → uses images relationship
- Storage symlink: public/storage -> ../storage/app/public (fixed)
- Frontend rebuilt: dist/ current (index-CHm9dJAr.js 729KB)
- Latest build: 55ee152 (image upload, property delete, cover_image fix)
- TKT-034: Plan limit indicators — usage endpoint returns current counts, DashboardHome + AgencyProperties + AgencyAgents show progress bars / disabled buttons / upgrade links at plan max
- TKT-035: usage bars (properties/agents X/max) in AgencySubscription current plan card
- TKT-036: remaining days badge + auto-refresh polling + billing history placeholder in AgencySubscription
- TKT-037: billing:process Artisan command (expire, renew, schedule daily 02:00)
- TKT-038: payment history table with real data replaces placeholder
- TKT-039: SubscriptionHint onboarding component on 3 pages
- TKT-040: useSEOMeta hook + PropertyDetail SEO (OG, Twitter, JSON-LD Product/Place schema)
- TKT-042: Agencies page redesign — removed phone/email/WhatsApp from public agencies API, redesigned listing cards with live chat button + view properties, AgencyDetail dark gradient hero with gold chat CTA (no external contact), AgentMatching replaced call/WhatsApp with live chat, Home.tsx agency section links to platform chat
- ChatWidget: defaultOpen + onClose props for external control (used from agencies/agent-matching pages)
- All agency communication now in-app only — phone/email/whatsapp fields removed from public API response
- PropertyDetail redesign (TKT-043): full editorial two-column layout. Right sidebar (lg:sticky top-24) with: price card (big price + purpose badge + status), quick info (area/bed/bath/type/floor/parking/year_built/ref_code with icons per row), agent card (gradient dark, avatar, platform chat only, guest register prompt), share buttons (copy link + WhatsApp). Main content: stats → description → amenities grid → property details → location/map → trust badges. Related properties full-width below on cream bg.
- Home page SEO (TKT-041): Organization + WebSite JSON-LD schema with sameAs, SearchAction. Removed fake phone number from schema.
- Code-split (perf): React.lazy() for all 38 pages — initial JS 741KB→241KB. Each page lazy-loaded. Suspense fallback with spinner+loading text.
- Latest commit: cca7694 (code-split App.tsx)
- Latest commit: fc152d27 (chat UX refactor — inline ChatWidget → user dashboard)
- Latest commit: 8823b003 (premium featured section — 3 cards, dark gold theme, pro plan marketing)
- Latest commit: 43ead3d0 (featured redesign v2 + latest properties 4×3 grid section)
- Branch: qa/testing-baseline
- TKT-046: PropertyDetail redesign v1 — editorial two-column layout with tabs, progress bar, parallax, nearby places (replaced by cleaner v2 below)
- TKT-047: PropertyDetail redesign v2 — elegant, brand-aligned, clean

  **Design decisions (v2):**
  - Hero: green gradient overlay (`from-primary via-primary/60 to-primary/30`) replaces dark navy (#0f172a) — matches brand identity
  - Gold (#c9a84c) accent bar at hero top, gold badges for purpose (للبيع/للإيجار), gold section markers
  - No tabs, progress bar, scroll-to-top, nearby places, distance bar, trust badges, fade-up, parallax
  - Stats: 4-box grid with gold separators (`gap-px bg-gold/20`), each box has icon + value + label
  - Content flow: description → amenities (pills) → property details (alternating rows) → location (map link)
  - Sidebar: price card (gold gradient top bar), agent card (photo + name + agency + chat button), quick info (area/bed/bath + copy link)
  - Background: cream (#f5f0e8) for content area
  - ChatWidget: wired to agent agency (`agencyId`, `agencyName`, `propertyId`, `propertyTitle`)
  - PropertyDetail chunk: 26.66 kB (-35% from 41 kB) due to removed features
  - API client: added `created_at`, `views_count`, `year_built`, `floor`, `parking`, `furnished` to PropertyDetail type
  - index.css: removed floating-label, ripple-btn, will-change-transform classes (unused)
- Latest commit: 16f2d6e — PropertyDetail unified design + navbar fix
- Navbar: PropertyDetail now uses dark transparent gradient (from-black/60) like Home — NOT green gradient. Solves clash with hero image overlay.
- Agency page fix: img alt="" + onError fallback to first letter (commit a2617d9)

- Home.tsx search form: select height matches input/button (py-2→!py-3) — commit 058c9c9
- Navbar unified across ALL pages: `isLightBg = (!isHome && !isPropertyDetail) || scrolled` → `isLightBg = scrolled`. Every page starts dark-transparent, turns white at 15px scroll. About/Contact no longer show white navbar on green hero. Dead `isPropertyDetail` ternary removed.

  **PropertyDetail — unified design (2026-06-28):**
  - Hero: full-bleed image as focal point, green gradient overlay (from-primary/90)
  - Title + breadcrumb + meta (location/date/views) + gold price box bottom-overlay
  - No blobs, particles, orbs, 3D collage, scroll indicator
  - Gallery thumbs: white bg strip below hero, gold border on active
  - Sections all use `badge-gold` label + heading + `card-3d` content (unified with Home)
  - Description: card-3d with whitespace-pre-line
  - Amenities: card-3d pill chips with gold dot, hover border-primary/20
  - Details: card-3d table, divide-beige-dark rows, hover:bg-beige/50
  - Location: card-3d with map placeholder + Google Maps button
  - Sidebar (card-3d stack): price card (gold gradient bar), quick info (bordered rows), agent (green gradient + gold chat CTA), save/share CTAs
  - Related: badge-gold + heading + PropertyCard grid (no section-dividers)
  - Mobile bar: sticky bottom green-primary buttons
  - No Counter/StatCell/SectionLabel sub-components — kept minimal
  - Chunk: 25.40 kB (down from 31.38 kB)

# FEATURED V2 + LATEST SECTION (2026-06-29) — richer cards + 4×3 grid
- Featured cards redesigned v2: 3 cards in horizontal flex row, richer details (purpose badge, 3-col stat table with Arabic labels, agency logo + name, ref_code display)
- Removed featured "View All" upsell CTA button
- New "أحدث العقارات" section below featured: 4 columns × 3 rows grid, elegant white cards with purpose badges, price, stats, agency footer
- Backend: `PropertyController::index()` now eager-loads `agency` relationship
- Latest 12 properties fetched via `fetchProperties({ sort: 'newest', per_page: '12' })`
- New i18n keys: `latestBadge`, `latestTitle`, `latestSubtitle` (ar + en)
- Commit: 43ead3d0

# FEATURED REDESIGN (2026-06-29) — premium 3-card layout, high-tier marketing
- Backend: featured API limited to 3 (was 8), eager-loads `agency` relationship
- PropertyCardResource: added `agency` field (id, name, slug, logo_path)
- PropertyCard TS type: added optional `agency` field
- Home.tsx featured section: completely redesigned
  - Dark premium gradient bg (#0a2b18 → #051a0e)
  - Gold decorative elements (corner accents, circles, glow blobs)
  - Header: white/gold title + "باقة بريميوم" badge
  - 3 custom cards (not PropertyCard): 16:10 image, gold "مميز" badge, price in gold, stats row, agency logo, gold "اكتشف" link
  - Upsell CTA button: "اشترك في الباقة الممتازة" with gold gradient
- New i18n keys: featuredBadge, featuredCTA
- Commit: 8823b003

# CHAT UX REFACTOR (2026-06-29) — floating ChatWidget → user dashboard chat
- PropertyDetail: "تواصل مع الوكالة" link → button, navigates to /user/chat?agencyId=X&propertyId=Y
- AgencyDetail: same button → /user/chat?agencyId=X, removed inline ChatWidget rendering
- UserChat: reads ?agencyId query param on mount, auto-starts conversation with default message
- ChatWidget.tsx: deleted (orphaned — no more imports)
- Unauthenticated users redirected to /login first
- Commit: fc152d27

# CHAT OVERHAUL (2026-06-28) — WhatsApp-style UI + attachments + cover images

## Chat rewrite
- AgencyChat.tsx, UserChat.tsx: full rewrite — left sidebar with search/avatar/unread badges, right panel with message bubbles (rounded tails + ✓✓ read receipts), image/file inline display, paperclip attachment button, Enter-to-send
- ChatWidget.tsx: full rewrite — supports attachments, initial msg + attachment as second msg, consistent bubble UI with agency theme, login redirect if unauthenticated
- Chat routes: agency `/api/v1/agency/conversations`, user `/api/v1/user/chat/conversations`
- sendChatMessage() returns r.data; widget reads res.data as message obj
- startConversation() returns {data:{conversation:{...},message:{...}}}

## Attachments
- DB migration adds attachment_path, attachment_type, attachment_name, attachment_size to chat_messages
- ChatMessage model: fillable + $appends=['attachment_url'] + getAttachmentUrlAttribute()
- Both storeMessage() controllers accept attachment file (jpeg,png,webp,gif,pdf,doc,docx, max 10MB) via FormData
- Storage: storage/app/public/chat-attachments/
- Frontend ChatConversation/ChatMessage interfaces updated with attachment fields

## Cover image
- DB migration adds cover_path (nullable string) to agencies table
- POST /api/v1/agency/cover — accepts jpeg/png/webp (max 5MB), stores in public/agency-covers/
- Agency model: cover_path in $fillable + cover_url accessor + $appends
- AgencyProfile.tsx: cover image card — upload, preview (1200×400px hint), remove, error fallback
- AgencyDetail.tsx: hero uses cover_path first, falls back to first property cover

## Logo fix
- logo_path stored relative: '/storage/...' NOT asset('/storage/...') — asset() generates http://localhost URLs that break on production domain syriahomes.zanjour.com
- logo_path validation changed from nullable|url to nullable|string

## Critical bugs fixed
- Conversation.user_id missing from $fillable → conversations created without user_link → every API call returned 403
- Conversation.latestMessage() returns HasOne (from latestOfMany()) but declared HasMany → TypeError on conversation list
- serveSpa() returns BinaryFileResponse but declared Illuminate\Http\Response → TypeError on dashboard

## Commits
- 61c7512 — feat(chat): overhaul UI with attachments, read receipts, cover images
- 0877988 — fix(chat): Conversation.latestMessage() return type + serveSpa() BinaryFileResponse

# CHAT MAILBOX + OFFERS (2026-06-29)

## UserChat redesign — mailbox system
- 3-tab layout: Inbox / Archived / Trash with count badges per tab
- Backend: conversations.archived_at timestamp + SoftDeletes + scopes (inbox/archived/trash)
- 5 new endpoints: archive, unarchive, trash, restore, force-delete
- Hover actions per tab (archive/trash — inbox; restore/force — trash)
- Date-separated message thread with date bubbles
- Context-aware empty states per tab
- Archived badge visible in chat header
- No polling for archived/trashed convos

## Offer/Negotiation system
- 4 new endpoints per side (client + agency): send, accept, reject, counter
- Offer metadata stored as JSON in chat_messages.metadata (message_type='offer')
- Offer card rendering in both UserChat.tsx + AgencyChat.tsx:
  - Full-width centered card with status colors (pending=amber, accepted=emerald, rejected=red, countered=blue)
  - Amount + currency display, note section, status badge with icon
  - Pending offers show Accept/Counter/Reject buttons (only to recipient)
  - Counter-offer indicator (shows "Response to previous offer")
  - Timestamp + accepted_at footer
- Payment request card rendering (message_type='payment_request'):
  - Amount + status badge + Pay Now button (links to SAKK pay_url)
- Offer form modal: amount input, currency selector (USD/EUR/SAR/AED/QAR/KWD), note textarea, counter-offer indicator
- "Send Offer" amber button in chat input area (between paperclip + send)
- Zero TS errors, builds in 200ms

## USER QUICK REPLIES (2026-06-29)
- Added 5 predefined quick reply templates (user-side) for common real estate inquiries
- Quick reply button (ReplyAll icon) in chat input area → popup with templates
- Quick reply cards in empty state (no conversation selected) → click opens first conversation + fills message
- Auto-send on template click with brief fill delay
- Quick replies in both Arabic + English (matches interface language)

## DESIGN AUDIT (2026-06-29)
- Full CSS/design audit of index.css + all major pages
- **Fixed P1:** DashboardHome stat cards (blue/purple/emerald/amber → primary/gold)
- **Fixed P1:** Agencies.tsx gradient tints (violet/rose/sky → brand)
- **Fixed P2:** PropertyDetail amenity tags (rounded-full → rounded-xl)
- **Fixed P3:** Added `--font-sans` + `--color-hero` + `--color-danger` tokens to @theme
- **Fixed P4:** Removed dead CSS (hero-mesh, hero-vignette, second-gradient, gold-gradient)
- **Fixed P5:** AgencyChat border-l → border-s, UserChat left/right → RTL-aware
- **Fixed P5:** index.css scroll-progress transform-origin RTL fix
- **Remaining low-priority:** .badge class dedup, RTL slide animations, prefers-reduced-motion, inline SVG icons → lucide

# COMPREHENSIVE CSS DESIGN AUDIT (2026-06-29)

## Full project CSS redesign (25 TODOs, 5 parallel agents)
- 37+ files modified: index.css + all landing/user/admin pages + components
- index.css: dead CSS removed, duplicate shimmer merged, .badge dedup, .text-gradient/.gradient-border removed
- @theme tokens added: --radius-btn/card/bubble, --shadow-card/card-hover, --transition-bounce, --color-landing-bg/user-bg/admin-bg, --text-2xs, --animate-fadeIn/slideUp/scaleUp, --color-chat-bg
- Hardcoded colors → brand tokens: bg-[#f0f2f5]→bg-beige/30, shadow-[rgba(201,168,76,...)]→shadow-gold/60, focus-shadow→shadow-primary/6
- RTL global .lucide fix: opt-in .lucide-rtl/.lucide-flip class (not global scaleX(-1))
- Logical CSS everywhere: border-l→border-s, ml→ms, mr→me, text-left→text-start
- Zone backgrounds: admin-bg (#f5f0e8), user-bg (#faf8f4), landing-bg (#fdfbf7)
- Landing: Home hero CSS vars, About/Contact py-24, Agencies card-3d+RTL, PropertyCard shared card-3d
- User: UserChat full-screen, Profile card-3d, Register/Login RTL padding
- Admin: DashboardLayout border-s, AgencyChat ms-auto, admin text-start/logical CSS
- Navbar: gold glow shadows tokenized
- TKT-055: RTL slide animations swap, prefers-reduced-motion, .break-arabic, heading sizes (2xs/xs), animate*@theme, chat-bg token, @supports backdrop-filter

## E2E QA (TKT-054) — browser on syriahomes.zanjour.com
- 12/12 tests passed, 0 failures, 2 warnings (empty DB, mobile menu pattern diff)
- Home load, Properties, Agency login→dashboard, Chat, Quick replies, User chat tabs, Profile, Agencies, RTL all 5 pages, CSS tokens, no console errors
- Build: tsc 0 errors, vite 223ms. Chunks: AgencyChat 42.6kB, ChatWidget 24.9kB, Home 29.9kB, PropertyDetail 33.8kB, UserChat 34.2kB

# PLAYWRIGHT E2E TEST SUITE (2026-06-29) — full project coverage
- Created `tests/` directory at project root with 5 test suites + runner
- `tests/config.cjs` — centralized config: Playwright path, frontend/backend URLs, viewport (1440×900), human simulation delays (typing 60-160ms, click 100-400ms, scroll 30% chance), 3 test accounts (admin/agency/user), timeouts, artifact dirs
- `tests/suites/auth.cjs` — 9 scenarios: register (name/email/password/confirm), AgencyRegister validation & toggle, login redirect, logout, ForgotPassword (email send), ResetPassword, Profile load, wrong account handling, route guard (redirect unauthed)
- `tests/suites/landing.cjs` — 9 scenarios: Home load, nav links, properties listing, search flow, About, Contact, footer links, 404 page, console error check on all public pages
- `tests/suites/admin.cjs` — 7 scenarios: all 9 admin pages render without 5xx, users table browse, agencies browse, properties browse, areas management, plans, messages+reviews
- `tests/suites/agency.cjs` — 7 scenarios: all 9 dashboard pages render without 5xx, property listing, profile, agents management, subscription, inquiries, deals+commission
- `tests/suites/property.cjs` — 5 scenarios: public property detail, create page renders, create form validation (submit empty → check errors), edit page navigation, media upload UI
- `tests/index.cjs` — main runner: Playwright launch (headless/visible), per-scenario context, human simulation helpers (navigate/click/type/scroll/wait), auth auto-login per role, JUnit XML + HTML report generation with pass/fail icons
- Human mode "الدقيق البشري": realistic keystroke delays, mouse move (20% chance), random scroll (30% chance), hesitation before click
- Env vars: `CI=1` (disable human), `AUTH=1` (auth-only), `HEADLESS=false` (visible browser), `FRONTEND_URL`/`BASE_URL` override
- Usage: `node tests/index.cjs`, `node tests/index.cjs auth`, `node tests/index.cjs auth,landing`
- Reports: `tests/reports/index.html` + `tests/reports/junit.xml`
- Artifacts: `tests/artifacts/shots/`, `tests/artifacts/video/`, `tests/artifacts/trace/`

# REAL-TIME BROADCAST OVERHAUL (2026-06-29) — all message paths now broadcast NewMessage
- NewMessage event added to 12 previously-missing paths across UserChatController (startConversation existing+new, sendOffer, acceptOffer notification, rejectOffer notification, counterOffer) and Agency/ChatController (sendPaymentRequest, sendOffer, acceptOffer payment+acceptance, rejectOffer, counterOffer)
- 11 orphaned broadcast jobs drained from jobs table (stuck from QUEUE_CONNECTION=database era)
- REVERB_SERVER_PORT=8081 added to backend .env (matching Supervisor CLI arg)
- Frontend UserChat: stale selected.id closure fixed (captured convId in const), user-facing sendError banner added (was silent console.error), markRead now tracks actual unread count instead of hardcoded -1, URL.createObjectURL cleanup on unmount+reattach
- Frontend AgencyChat: stale selected reference fixed in Echo listener (captured convId in const)
- Everything committed as 0aa864e1

# PHP FEATURE TESTS (2026-06-29) — committed
- 49/49 feature tests passing (94 assertions)
- AuthTest (16), PublicApiTest (17), AgencyApiTest (14)
- Model factories with HasFactory on all models
- SQLite :memory: with PostgreSQL-only migration guards
- AuthController: 'success' envelope, 401 on wrong password
- Commit: 79341abe
- Fix (2026-06-29): All broadcast paths wrapped in try-catch — `tryBroadcast()` helper added to AgencyChatController + QuickReplyController (UserChatController already had it). Caddy `/apps/*` route added via admin API PATCH (was missing — broadcast HTTP POST uses `/apps/{appId}/events`). Message send returns 201, no "فشل إرسال الرسالة" error. Commit 44ab7598.
