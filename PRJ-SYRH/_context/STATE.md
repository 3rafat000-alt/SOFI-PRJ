# STATE — PRJ-SYRH (Syria Homes)
title: Syria Homes — Real Estate Marketplace SaaS
local_domain: http://prj-syrh.local
public_url: https://syriahomes.zanjour.com
gate: 7 (Production Rollout) — active
status: gate7_production — SAKK_live + Reverb_deploy + APP_URL_prod
priority: HIGH
blockers: none
branch: qa/testing-baseline
head_sha: 44ab7598
last_deploy: live (syriahomes.zanjour.com) — code served directly from disk
last_route: sonnet · high · normal
stack: Laravel 12 / PostgreSQL 18 · API /api/v1 · Sanctum auth · Spatie roles · i18n ar(primary)/en
created: 2026-06-27
updated_by: ceo-sofi

## Real-time broadcast overhaul (2026-06-29)
- NewMessage event added to ALL 12 previously-missing message-creating paths
- 11 orphaned broadcast jobs drained from DB
- REVERB_SERVER_PORT=8081 added to .env
- UserChat: stale closure fix, sendError banner, markRead actual count, URL cleanup
- AgencyChat: stale closure fix
- Commit: 0aa864e1
- Caddy `/apps/*` route added (was missing — broadcast HTTP API uses `/apps/{appId}/events`). Reloaded via admin API PATCH.
- `tryBroadcast()` helper added to AgencyChatController + QuickReplyController (was only UserChatController). All `event(new NewMessage(...))` calls protected. Commit: 44ab7598
- Verified: send message returns 201 ✅, no broadcast warnings in log ✅, Reverb /apps endpoint returns 401 (correct — Caddy proxies to Reverb).

## Pages (20 total)
- Public: Home, Properties, PropertyDetail, About, Contact, Agencies, AgencyDetail, SearchResults, NotFound
- Auth: Login, Register, AgencyRegister, ForgotPassword, ResetPassword, AgentMatching, Profile
- Admin (9): AdminLayout, AdminHome, AdminUsers, AdminAgencies, AdminProperties, AdminPlans, AdminMessages, AdminReviews, AdminSettings
- Agency/Dashboard (12): DashboardLayout, DashboardHome, AgencyProperties, PropertyCreate, PropertyEdit, AgencyAgents, AgencyInquiries, AgencyDeals, AgencyCommission, AgencySubscription, AgencyProfile, AgencyChat
- User (6): UserLayout, UserHome, UserFavorites, UserInquiries, UserSearches, UserProfile
- Install (1): InstallWizard

## Components built
- Navbar (redesigned: icons, gold CTA, avatar, accent line, mobile menu)
- Footer, PropertyCard, SelectField, ProtectedRoute (role-based guard)
- AuthContext (token management, login/logout/me)

## Backend API (57+ endpoints)
- Public: 19 (properties, locations, agents, agencies, stats, reviews, contact, settings, search)
- Auth: 7 (register, login, logout, me, profile, forgot/reset password)
- User: 6 (favorites toggle, saved searches CRUD, dashboard, inquiries)
- Agency: 19 (dashboard stats, properties CRUD, agents CRUD, inquiries, subscription, deals, commission report, profile, chat CRUD, unread count)
- Admin: 16 (dashboard, users, agencies, properties moderation, plans CRUD, messages, reviews approval, settings)
- Install: 7 (requirements, database check/store, admin create, settings store, complete)

## Key features built
- Installation system (5-step wizard with sentinel file guard)
- Smart role routing after login (admin→/admin, agency→/dashboard, user→/user/dashboard)
- Chat system (Conversation + ChatMessage models, agency-side UI, polling)
- Role-based dashboards with full CRUD operations
- Agent matching wizard (5-step)
- Navbar redesign with single gold CTA

## Recent additions
- Image upload: `POST /agency/properties/{property}/images` (multipart, up to 10 files)
- Image delete: `DELETE /agency/properties/{property}/images/{image}`
- Set cover: `POST /agency/properties/{property}/images/{image}/cover`
- Property delete: `DELETE /agency/properties/{property}` (cascade cleans files)
- SakkService + SakkWebhookController: real SAKK API at sakk.zanjour.com
- Full payment flow tested: subscribe → SAKK pay → webhook → activate subscription
- Storage symlink fixed: `public/storage -> ../storage/app/public`
- Frontend rebuilt: `dist/` current

## Recent additions
- Plan limit enforcement: `storeProperty` + `storeAgent` check `canAddProperty()`/`canAddAgent()`
- Arabic error messages when limits exceeded
- Fixed ref_code generation (4-digit padding)
- Plan limit indicators (TKT-034): usage in subscription API + progress bars on DashboardHome stat cards + AgencyProperties/AgencyAgents limit bars with disabled add buttons and upgrade links
- Subscription usage bars (TKT-035): UsageBar component in AgencySubscription current plan card
- Subscription page enhancements (TKT-036): remaining days badge, auto-refresh polling after payment, billing history placeholder
- Recurring billing (TKT-037): billing:process Artisan command with SAKK renewal payments, daily schedule at 02:00
- Payment history (TKT-038): real payment table in subscription page + GET /agency/payments endpoint
- Onboarding hint (TKT-039): SubscriptionHint component on 3 pages with localStorage dismissal
- Property SEO (TKT-040): useSEOMeta hook with OG/Twitter/JSON-LD on PropertyDetail
- Agencies redesign (TKT-042): no external contact — live chat only on listing + detail + matching + home pages
- PropertyDetail redesign (TKT-043): editorial two-column layout with sticky sidebar — price card, quick info, agent card with platform chat, share buttons
- Home page SEO (TKT-041): Organization + WebSite JSON-LD schema, sameAs array, SearchAction

## QA Results (TKT-045 — phase 1, 2026-06-28)
- All public routes 200 ✅
- PropertyDetail sidebar (lg:sticky lg:top-24) present, no phone/email/WhatsApp leaks ✅
- No external contact on public agency pages (TKT-042 verified) ✅
- WhatsApp fields only in internal agency dashboard ✅
- Contact page intentionally shows info@syriahomes.sy ✅
- Frontend build 186ms, 0 errors (JS 241KB code-split from 741KB) ✅
- Code-split confirmed: PropertyDetail lazy-loads as 30KB chunk ✅
- 37 responsive classes in PropertyDetail ✅
- Phase 2 needed: live browser QA on syriahomes.zanjour.com

## SAKK merchant + company for بيطار العقارية (2026-06-28)
- SAKK merchant created on sakk.zanjour.com (MCH-BPBLEZXF)
  - api_key: 0wbmMp6qJw5oFW2u4KK01l98cYIevJiP
  - api_secret: GWli5I2p25obJ9Jcwmoipe4Qws4lnJIEbowTjUEfq4Cnwr1f9RLiOPn5o2csUgjG
  - Webhook: https://syriahomes.zanjour.com/api/v1/sakk/webhook
  - Environment: production, verified
- SAKK company created (CO-869850) with payroll enabled for salary distribution
- Merchant + Company both linked to أسعد صوفي (user ID:2, es3dlll@gmail.com)
  - merchant.user_id = 2, company.user_id = 2
  - أسعد صوفي added as company employee (مدير)
  - merchant.user_id was NULL before — now properly linked
- Merchant linked to PRJ-SYRH agency ID:4 (شركة بيطار العقارية)
  - sakk_merchant_id = MCH-BPBLEZXF
  - sakk_api_key stored encrypted
  - sakk_verified = true

## Logo + delete Test Agency (2026-06-28)
- Created SVG logo for شركة بيطار العقارية: `/storage/agencies/sharkat-bitar.svg`
  - Dark navy bg + gold house icon + gold Arabic text "بيطار | العقارية"
  - Uploaded to storage/app/public/agencies/ (symlink fixed)
  - Set as logo_path on agency ID:4
- Deleted Test Agency (ID:5) — no properties/agents/subscriptions, clean delete
- Created `public/.gitignore` to exclude storage symlink from commits
- Commit: 7e7ad62

## Profile sidebar (2026-06-28)
- Profile page restructured to sidebar layout: avatar card + tab navigation (info/security/favorites/searches)
- Tab: Personal Info — edit name/phone (email disabled), save button
- Tab: Security — change password with show/hide toggle, 8-char validation
- Tab: Favorites — property card grid with remove button
- Tab: Saved Searches — list with delete button

## Chat + Cover Image (2026-06-28)
- Chat complete overhaul: WhatsApp-style UI for AgencyChat, UserChat, ChatWidget
- Message attachments (images jpeg/png/webp/gif, docs pdf/doc/docx, max 10MB)
- Cover image: cover_path migration, upload endpoint, accessor
- Logo fix: relative /storage/ paths instead of asset() URLs
- Bugfix: Conversation.latestMessage() return type HasMany→HasOne
- Bugfix: serveSpa() BinaryFileResponse union type
- Bugfix: UserChatController `logo`→`logo_path` column crash (conversation send)
- Bugfix: AgencyChatController `avatar`→`avatar_url` column crash (conversation list)
- Commit: 61c7512 (chat overhaul), 0877988 (bugfixes)

## Quick replies — قوالب الردود الجاهزة (2026-06-28)
- DB: `quick_replies` table (agency_id, property_id nullable, title, content, placeholders JSON, sort_order, is_active)
- API: 7 endpoints (index, store, show, update, destroy, preview, send)
- `QuickReply::render(array $values)`: replaces {placeholders} with actual values
- Auto-fills conversation context: client_name, property_title, price, area, bedrooms, location
- Frontend: AgencyQuickReplies manage page (dashboard/quick-replies) — create, edit, delete, preview with sample data
- Frontend: AgencyChat quick reply picker (ReplyAll icon next to attachment) — popup filtered by conversation property, sends with context substitution
- Seeded 6 templates for شركة بيطار العقارية
- Commit: 9fff6c2

## SAKK full linking + escrow test (2026-06-28)
- Merchant MCH-BPBLEZXF + Company CO-869850 linked to أسعد صوفي (SAKK user ID:2)
  - merchant.user_id=2, company.user_id=2, company employee added
  - Asaad's SAKK account activated: status=active, KYC=verified, kyc_level=3
  - Wallets funded: USD 10,000 + SYP 5,000,000 + Company wallet 50,000 USD
  - Limits raised: daily=50,000, monthly=500,000
  - Full API token created for user 2 (all abilities)
- Bugfix: ChatController sendPaymentRequest — notes passed as json_encode(string) broke array cast
  - Fixed to pass array directly, cast handles JSON serialization
- TEST: Full escrow flow verified end-to-end:
  1. POST /api/v1/payment-requests → SAKK API → 201 (uuid created) ✅
  2. Payment record + ChatMessage created (type: payment_request, status: pending) ✅
  3. Webhook POST (HMAC signed) → payment.status=paid, escrow held ✅
  4. escrow:release command → payment.status=released, chat updated ✅
  - Test payment: 800 USD, rent escrow (14d hold), released manually for verification
- Commit: (pending — includes ChatController fix)

## Comprehensive CSS Design Audit (2026-06-29)
- Full project CSS audit: 37+ files modified across landing/user/admin zones
- index.css cleanup: dead CSS removed, duplicate @keyframes shimmer merged, .badge deduplicated
- @theme tokens: added --radius-btn, --radius-card, --radius-bubble, --shadow-card, --shadow-card-hover, --transition-bounce, --color-landing-bg, --color-user-bg, --color-admin-bg, --text-2xs, --animate-fadeIn/slideUp/scaleUp, --color-chat-bg
- Hardcoded color sweep: all `bg-[#f0f2f5]` → `bg-beige/30`, focus rings → `shadow-primary/6`, gold glows → `shadow-gold/60`, etc
- RTL icon fix: global `.lucide` scaleX(-1) → opt-in `.lucide-rtl`/`.lucide-flip` class on directional icons only
- Logical CSS: border-l→border-s, border-r→border-e, ml→ms, mr→me, text-left→text-start, text-right→text-start
- Zone backgrounds: admin-bg (#f5f0e8), user-bg (#faf8f4), landing-bg (#fdfbf7)
- Landing pages: Home hero → CSS vars, About/Contact spacing py-24, Agencies/AgencyDetail card-3d + RTL, Properties/SearchResults rounded-full inputs, PropertyCard → card-3d shared
- User pages: UserChat full-screen (flex-1 h-full bg-white, rounded-full input, RTL bubbles), Profile card-3d, Register/Login RTL padding
- Admin pages: DashboardLayout border-s, AgencyChat ms-auto RTL fix, admin pages text-start/logical CSS
- TKT-055 polish: RTL slide animations, prefers-reduced-motion, .break-arabic, heading sizes normalized, animate-*@theme, chat-bg token, @supports backdrop-filter

## E2E QA Results (2026-06-29)
- Browser E2E tests on syriahomes.zanjour.com:
  - ✅ Home page loads (RTL, Arabic title, hero)
  - ✅ Properties listing visible
  - ✅ Agency login → dashboard redirect
  - ✅ Dashboard sidebar/content visible
  - ✅ Agency chat page loads with conversations
  - ✅ Quick replies management page loads
  - ✅ User login → chat with tabs (inbox/archive/trash)
  - ✅ Profile page loads
  - ✅ Agencies page loads with content
  - ✅ RTL dir=rtl on all 5 public pages
  - ✅ CSS brand color tokens present (primary #1a6b3c, gold #c9a84c, beige #f5f0e8, hero #0a2b18)
  - ✅ No console errors
  - ⚠ No property links (DB empty — expected)
  - ⚠ Mobile menu detection ambiguous (diff pattern)
- Build: tsc 0 errors, vite 223ms
- Chunk sizes: AgencyChat 42.6kB, ChatWidget 24.9kB, Home 29.9kB

## Laravel Reverb + Echo (2026-06-29) ✅
- Installed `laravel/reverb` v1.10.2 via composer
- Published broadcasting config + Reverb config
- Created `app/Events/NewMessage.php` — `ShouldBroadcast` event on `conversation.{id}` channel
- Created `routes/channels.php` — auth guard (agency staff / conversation participant)
- Fired `NewMessage` events in both `UserChatController::storeMessage` and `AgencyChatController::storeMessage`
- Replaces 5s polling: AgencyChat, UserChat, ChatWidget now listen via `echo.channel().listen('.new-message')`
- Frontend: installed `laravel-echo` + `pusher-js`, created `src/echo.ts`, wired into all 3 chat components
- Echo chunk: 74kB / 21kB gzip (lazy-loaded on chat pages only)
- Fallback: unread count still polls at 30s interval (low traffic, acceptable)
- `.env`: `BROADCAST_CONNECTION=reverb`, REVERB_* + VITE_REVERB_* vars configured
- Build: tsc 0 errors, vite 203ms

## Database seeding (2026-06-29) ✅
- Ran `php artisan db:seed --force` — all seeders idempotent
- 24 published properties (10 featured, slugs like `/properties/lavish-villa-mazzeh-garden-pool`)
- 3 agencies (بيوت الشام العقارية, دار الياسمين, نخبة العقارية)
- 5 agents, 14 governorates, ~200 areas, 5 property types, 8 amenities
- Verification: Home page shows property grid, Properties page shows 12 per page, Property detail loads

## SAKK live verification (2026-06-29) ✅
- `SakkService.php` hardcodes `https://sakk.zanjour.com/api/v1` — already production
- `sakk_sandbox` setting in DB is cosmetic (read but never used to switch URL)
- Mock mode only activates when `sakk_api_key` is empty
- Escrow flow tested end-to-end (2026-06-28): payment request → webhook → paid → released
- SAKK credentials inserted in DB: `sakk_api_key`, `sakk_webhook_secret`, `sakk_merchant_id`
- `SakkService::isConfigured()` = YES — mock mode disabled, real API calls will flow
- Producing SAKK merchant (بيطار العقارية, MCH-BPBLEZXF) already linked, verified, with KYC level 3
- SAKK wallets funded: USD 10,000 + SYP 5,000,000

## Deploy helpers (2026-06-29) ✅
- `deploy/reverb-supervisor.conf` — Supervisor program config for `php artisan reverb:start`
- `deploy/start-reverb.sh` — executable helper to daemonize Reverb
- Install supervisor: `sudo cp deploy/reverb-supervisor.conf /etc/supervisor/conf.d/syrh-reverb.conf && sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start syrh-reverb:*`
- Reverb already running on `tcp://0.0.0.0:8080` (EADDRINUSE on test — port occupied)
- APP_URL set to `https://syriahomes.zanjour.com` (SAKK webhook callbackUrl correct)

## PHP Unit Tests (2026-06-29) ✅
- 49/49 feature tests pass (94 assertions) — full API test suite
- AuthTest: 16 tests — register (5), login (3), logout (2), me (2), profile (1), change-password (2), wrong-current (1)
- PublicApiTest: 17 tests — property types (1), locations (2), properties (5), agencies (3), agents (2), testimonials (1), stats (1), settings (1), 404s (2)
- AgencyApiTest: 14 tests — auth guard (1), dashboard (1), properties (2), agents (2), subscription (1), profile (2), deals (1), payments (1), conversations (1), unread (1), quick-replies (1)
- Model factories: Property, Agency, Agent, Governorate, Area, PropertyType (all with HasFactory trait)
- Fixed factory enum mismatches (status: available, currency: USD/SYP, purpose: sale/rent, rent_period: month/year)
- Fixed AuthController: 'success' envelope + 401 on wrong password
- Fixed PropertyTypeFactory slug uniqueness (suffix with bothify)
- SQLite :memory: database with RefreshDatabase — PostgreSQL guard in migrations
- Commit: 79341abe

## Gate 7 (Production Rollout) — active

## QA Infrastructure (TKT-058) — 2026-06-29 ✅
- Playwright E2E test suite: `tests/` at project root
- 5 suites, 37 scenarios across auth/landing/admin/agency/property
- Human "الدقيق البشري" mode with realistic delays
- HTML + JUnit reports, screenshots in artifacts
- Headless/CI mode via env vars
- Commit: a5b90b98

## Fix: governorate-area filter (2026-06-29)
- Area dropdown now filters by selected governorate using `fetchAreas(governature)`
- Falls back to `popular_areas` when no governorate selected
- Clears area on governorate change (existing behavior preserved)
- Commit: 38b5c750
- **Race condition fix:** two `updateFilter()` calls in same handler (governorate onChange + chip remove) caused stale closure — governorate never persisted. Replaced with single `setGovernorate()` using one `setSearchParams` call.
- Commit: 78281a70

## PropertyDetail redesign (2026-06-29)
- **QuickInquiryModal**: new component — form modal sends direct message via `startConversation()` without leaving page. Guest users redirected to login. Success feedback + auto-close.
- **Agent card redesigned**: dual CTAs — white "Chat with Agent" (filled) + ghost "Quick Inquiry" (outline).
- **Mobile bar upgraded**: dual CTAs — Inquiry (outline) + Chat (filled primary).
- **Content sections refined**: stone-400 section labels replace gold, cleaner cards/whitespace, 4-col nearby grid, comfortable typography.
- Commit: 614dac78 (+313 lines, PropertyDetail chunk 36.98 kB)

## PropertyCard redesign (2026-06-29)
- **PropertyCard** fully redesigned to match Properties page GridCard premium style:
  - Clean white card, border, hover shadow lift
  - Purpose badge (green/blue) top-right, Featured badge (gold) top-left
  - Price overlay (white/90 backdrop-blur) on image bottom
  - Compact stats row (area, beds, baths), title + location line-clamp
- **New `showInquiry` prop**: renders "Quick Inquiry" button at card bottom
  - Button → `/user/chat?agencyId=X&propertyId=Y`
  - Guests → `/login?redirect=...`
- **Related section** passes `showInquiry` to all cards
- Commit: 01e7b1db (-1 line net, build 229ms, 0 errors)

## Gallery + Modal + Highlights (2026-06-29)
- **Hero gallery nav**: left/right chevron arrows overlay on image (desktop, show on hover). Thumbnail strip below hero — horizontal scroll, gold border active indicator.
- **QuickInquiryModal polished**: gold top bar, property preview card (cover + title + price), MessageCircle icon + softer form, animate-slideUp + fadeIn backdrop, cleaner success state.
- **Highlights section**: 2×3 grid of selling point cards between Location and sidebar. Conditionally shows: year_built, bedrooms≥3, area≥150, furnished, parking, featured, hot_deal — each with gold icon box + hover lift.
- Commit: 83183ace (+79 lines, PropertyDetail 42.26 kB, build 202ms)

## Chat system critical bugfixes (2026-06-29)
- **UserChat.tsx** (7 fixes): quick reply lang bug, missing .catch() on loadConversations/loadMessages, search input RTL, avatar onError, startConversation re-fire guard, tab effect race condition
- **AgencyChat.tsx** (6 fixes): missing .catch() on loads, property card image onError, search input RTL, user-facing error banner for send/payment/offer failures
- Commit: 31b25b01 (+32 lines across 2 files, build 205ms)

## Remaining (low priority)
- Add real PHP unit/feature tests for API controllers
- Verify cover image upload end-to-end
- Verify attachment download (inline vs download link)

## Accounts
- Admin: admin@syriahomes.sy / admin123
- Agency Owner: owner@byout-al-sham.com / owner123
- Test: test@test.com / password123
- Test User: test@user.com / password123
- Test Agency: agency@test.com / password123 (agency 2 — سورية هومز العقارية)

## Real-time chat fix — 2026-06-29
- Echo WebSocket now routes through Caddy (`/app/*` → reverse_proxy localhost:8081) instead of direct port 8081
- Production connection: `wss://syriahomes.zanjour.com/app/syrh-reverb-key` → Cloudflare → Caddy → Reverb
- echo.ts: conditional wsPort (omit for prod, pusher-js defaults to 443/80), forceTLS from VITE_REVERB_SCHEME
- Caddy zanjour.caddy: added `@reverb path /app/*` → `reverse_proxy localhost:8081` before API/SPA handlers
- .env: REVERB_HOST/VITE_REVERB_HOST → syriahomes.zanjour.com, scheme → https, removed VITE_REVERB_PORT
- PropertyDetail TS fixes: Send icon import, QuickInquiryModal price/isRent scope, agent null checks
- Verfied: WebSocket connects through both Reverb direct (8081) and Caddy proxy (80)

## Real root cause — queue + missing event — 2026-06-29
- **QUEUE_CONNECTION=database** + no `queue:work` running → broadcast events queued but never processed. Changed to `QUEUE_CONNECTION=sync` so events fire immediately.
- **QuickReplyController::send()** created ChatMessage but never dispatched `NewMessage` event → user never notified via WebSocket. Added `event(new NewMessage(...))`.
- **Frontend .env** had `VITE_REVERB_PORT=8081` overriding Echo config (not updated in previous fix). Removed.
- Commit: 2ba22741

## Comprehensive test data — Agency 2 (سورية هومز العقارية) — 2026-06-29
- **Agency profile**: description_ar/en, WhatsApp, license_no, status=active, verified_at set, logo_path + cover_path (SVG)
- **8 new properties** (IDs 21-28): Damascus/Aleppo/Latakia, sale+rent, apartments+villas+commercial, each with 4 Unsplash images
- **46 property images** total (all with alt_ar/alt_en, sort order, is_cover per property)
- **2 agents**: أحمد الخطيب (expert in Damascus luxury), سارة الحسن (coastal specialist)
- **5 quick replies**: Greeting, Visit Request, Price Negotiation, Property Questions, Urgent Interest
- **Owner**: agency@test.com linked to agency 2

  - 6 older properties (IDs 9-14, no titles) remain from earlier seed — not displayed in UI
- Commit: 7f5ad763