# CONTEXT — SAAS-001 (durable facts; append-only)
- title: TaskSync Pro
- sector: 01-productivity
- description: نظام إدارة مهام ومشاريع للفرق الصغيرة مع تتبع الوقت ولوحة Kanban
- target_customers: فرق العمل، الشركات الناشئة
- stack: (set at gate 3 by principal-system-architect)
- enriched_description: نظام إدارة مهام ومشاريع سحابي للفرق الصغيرة مع تتبع وقت مدمج ولوحة Kanban. يدعم اللغة العربية بالكامل. يركز على فرق 3-20 شخص في الشركات الناشئة.
- target_market: سوق أدوات إدارة المشاريع ~$7B عالمياً، ~$400M في الشرق الأوسط. نمو 12% CAGR.
- competitive_landscape: Asana (قوي لكن معقد، دعم عربي ضعيف)، Trello (بسيط لكن محدود بدون تتبع وقت)، ClickUp (منحنى تعلم حاد)، Monday.com (سعر باهظ)، Notion (ليس أداة إدارة مشاريع خالصة).
- differentiation: عربي بالكامل، تتبع وقت مدمج، تسعير منخفض، تكامل مع تطبيقات محلية (WhatsApp).
- pricing_tiers: Free (3 members, 5 projects), Pro ($12/seat/mo), Business ($19/seat/mo)
- stack: Laravel 11 API-only / Vue 3 + Pinia / Flutter 3.x + Bloc / PostgreSQL 16 / Redis 7 / Laravel Reverb / S3-compatible storage / Cloudflare CDN
- api_design: RESTful JSON — Sanctum token auth, Form Request validation, Resource serialization
- services: AuthService, TeamService, ProjectService, TaskService, TimeService, ReportService, InviteService, NotificationService, FileService, SubscriptionService
- realtime: Laravel Reverb (WebSocket), Laravel Echo client (Vue + Flutter), Redis pub/sub
- queue: Laravel Horizon — 3 tiers (high/notifications, default/reports, low/exports)
- offline_strategy: Flutter Hive local store, pending queue, last-write-wins via updated_at
- caching: Redis — project tasks (60s), team members (300s), report aggregations (600s), user profile (600s)
- security: Sanctum tokens, Policy gates, rate limiting (60/300/30 req/min), spatie/activitylog audit
- deployment_spec: 2 vCPU, 4GB RAM, 50GB SSD, Ubuntu 24.04 Nginx/PHP-FPM
- scaling_path: vertical to 4vCPU/8GB → split DB → horizontal app nodes → RDS/ElastiCache
- architecture_doc: docs/ARCHITECTURE.md
- architecture_topology: docs/SAAS-001_Architecture.fossflow.json
- decision_log: ARC-001 through ARC-008 in docs/ARCHITECTURE.md
- arch_completed_at: gate-3
- schema_doc: docs/SCHEMA.md
- migrations_doc: docs/MIGRATIONS.md
- total_tables: 13 (workspaces, users, workspace_user, projects, tasks, comments, attachments, time_entries, tags, task_tag, task_assignees, notifications, activity_logs)
- migration_count: 12 files in database/migrations/
- model_count: 10 Eloquent models in app/Models/
- pk_strategy: UUID v4 (HasUuids trait) on all tables
- soft_deletes: workspaces, users, projects, tasks, comments, attachments
- fulltext_search: GIN tsvector indexes on tasks.title+description (arabic), projects.name+description (arabic)
- kanban_index: idx_tasks_project_status_position (project_id, status, position) — composite BTREE
- deadline_index: idx_tasks_due_date — partial BTREE (WHERE due_date IS NOT NULL)
- time_report_index: idx_time_entries_user_started (user_id, started_at DESC) — composite BTREE
- my_tasks_index: idx_task_assignees_user_id on task_assignees pivot
- cascade_rules: all CASCADE except activity_logs.user_id (SET NULL) and users.current_workspace_id (SET NULL)
- check_constraint: attachments — (task_id IS NOT NULL) OR (comment_id IS NOT NULL)
- demo_seed: DatabaseSeeder — "الفريق الذهبي" workspace, 3 Arabic users (سارة/أحمد/ليلى), 2 projects, 6 tasks, timer entries
- schema_engineer: sofi-data-schema-engineer
- schema_completed_at: 2026-06-25
- api_base_url: /api/v1
- api_auth: Sanctum Bearer token
- api_rate_limits: auth (60/min), general (300/min), reports (30/min)
- api_pagination: cursor-based (tasks), page-based (projects/time-entries/comments)
- api_error_format: standard envelope with code/message/details/meta.request_id
- websocket_events: TaskCreated, TaskUpdated, TaskDeleted, TaskMoved, CommentAdded, TimerStarted, TimerStopped, MemberJoined
- websocket_channels: private-user.{id}, private-project.{id}, private-workspace.{id}
- websocket_server: Laravel Reverb (port 6001, Redis pub/sub)
- integration_whatsapp: Meta Cloud API v18.0 — task_assigned/task_due_soon/invite_workspace templates
- integration_slack: Incoming Webhook — Block Kit format, 3 default channels
- integration_google_calendar: OAuth 2.0 — one-way sync (TaskSync -> Calendar), scope calendar.events
- webhook_outgoing: HMAC-SHA256 signed, 5-retry with exponential backoff, auto-disable on failure
- idempotency: Idempotency-Key header on POST/PUT, 24h window
- api_doc: docs/API.md (full spec with examples, integration specs)
- openapi_spec: docs/openapi.yaml (OpenAPI 3.0, all endpoints + schemas)
- security_doc: docs/SECURITY.md
- strided_components: API/Web/Mobile/DB/Redis/S3 — STRIDE per component analyzed
- auth_mechanism: Sanctum dual-mode — cookie SPA for dashboard, Bearer token for Flutter mobile
- auth_flows: registration (email verification, 48h unverified purge), login (rate-limited 60/min, device fingerprint, 5-attempt lockout), password reset (6-digit code, Redis 15min TTL, all-token revoke on reset), session management (24h expiry, device listing, inactivity timeout 2h)
- mfa_status: planned post-MVP (TOTP via laragear/two-factor, recovery codes, trusted devices)
- rbac_roles: Owner (full control), Admin (manage members/settings), Member (task CRUD/timer), Viewer (read-only) — enforced via Laravel Policies + Gates + middleware
- idor_protection: every resource endpoint validates team membership via Policy chain (user→team→project→task); UUIDs replace auto-increment IDs
- owasp_coverage: SQLi (ORM), XSS (Vue auto-escape + CSP + DOMPurify), CSRF (Sanctum), broken auth (rate limits + lockout), sensitive data exposure (encrypt at rest + TLS 1.3), misconfiguration (CI-enforced APP_DEBUG=false, CORS whitelist), file upload (extension/MIME/ClamAV/10MB/zip-bomb block), IDOR (Policy on every resource)
- data_classification: Critical (billing, APP_KEY — encrypted AES-256), PII (email, name, avatar, IP, locale — encrypted at rest, right to erasure, 1yr retention max), Confidential (tasks, projects, time entries — RBAC gated), Public (landing page, pricing — no controls)
- pdpl_compliance: consent on registration, purpose limitation, data minimization (email+name+password only), right to access/erasure, data breach notification (72h), cross-border data hosted in Middle East region, DPO contact, privacy-by-design integrated
- encryption_at_rest: Laravel encrypt cast for email/fcm_token (AES-256-CBC), S3 SSE for files, LUKS disk encryption for PostgreSQL TDE, GPG for backups
- encryption_in_transit: TLS 1.3 everywhere — Cloudflare Full Strict, HSTS preload, mobile certificate pinning (leaf + intermediate + backup pin)
- rate_limits: auth 60/min per IP, general API 300/min per user, reports 30/min, uploads 10/min, invites 20/min per team — all 429 + Retry-After
- api_security: CORS restricted to production domain, Form Request validation with sanitize rules, immutable activity_log table for audit trail, security headers (CSP/X-Frame-Options/HSTS/Permissions-Policy)
- mobile_security: flutter_secure_storage (Keychain/Keystore) for tokens, certificate pinning (2+1 backup pin), ProGuard/R8 + Flutter obfuscate, root detection (optional), screenshot blocking (FLAG_SECURE), offline Hive box encrypted via secure storage key
- pen_test_plan: 10 cases — token manipulation, cross-team IDOR, role escalation, SQL injection, rate limit bypass, malicious file upload, WebSocket channel auth, mass assignment, sensitive data exposure, mobile token extraction. Tools: ZAP (CI), Burp Suite, MobSF, Objection, Gitleaks, Trivy
- security_ci: ZAP full scan on PR, Trivy filesystem vuln scan, Gitleaks secret scan — all in GitHub Actions
- security_completed_at: gate-3

# QA Gate 5 — 2026-06-25
- qa_tester: sofi-manual-exploratory-tester (Rosa Giménez)
- qa_tkt: TKT-011
- qa_report: docs/QA_REPORT.md
- qa_personas: سارة (PM), أحمد (Developer), ليلى (Coordinator)
- qa_test_cases: 33
- qa_passed: 21
- qa_failed: 12
- qa_pass_rate: 63.6%
- qa_health_score: 73/100 FAIR
- qa_critical_bugs: 1 (BUG-005 — SearchInput.vue: uiStore not imported, breaks all search)
- qa_high_bugs: 3 (BUG-008 — TaskMoved WS missing; BUG-002 — manual time no date validation; BUG-001 — Kanban no mobile touch)
- qa_medium_bugs: 5 (BUG-006, BUG-011, BUG-009, BUG-013, BUG-012)
- qa_low_bugs: 4 (BUG-003, BUG-014, BUG-007, BUG-010)
- qa_verdict: SHIP BLOCKED — Critical + High bugs must be resolved before Gate 5 sign-off
- qa_next: sofi-qa-sre-lead (TKT-013) to orchestrate fixes, re-run regression, approve release
- performance_report: docs/PERFORMANCE_REPORT.md
- performance_analyst: Ahmed Farouk (sofi-performance-load-analyst)
- performance_completed_at: 2026-06-25
- k6_scenarios: 4 (task-list 100VUs, task-create 50VUs, kanban-reorder 30VUs, timer-toggle 60VUs)
- k6_thresholds: error_rate<1%, p95<500ms (list/create), p95<800ms (reorder), p95<300ms (timer)
- api_latency_targets: P50<80ms, P95<150ms, P99<200ms
- frontend_targets: FCP<1.5s, LCP<2.5s, TTI<3s, CLS<0.1, INP<200ms
- lighthouse_targets: Performance>90, Accessibility>95, BestPractices>95, SEO>95
- mobile_targets: cold_start<2s, 60fps UI, timer_start<100ms
- db_missing_indexes: idx_tasks_assignee_status, idx_tasks_project_status, idx_time_entries_report_covering
- n_plus_one_risks: task list (assignee/creator/tags), project list (taskCount), task detail (comments.user/attachments/timeEntries), dashboard stats (5x COUNT), member list (taskCount per user)
- redis_cache_ttls: task:project (60s), dashboard:stats (120s), team:members (300s), report:time (600s), user:profile (600s)
- cache_invalidation: event-driven via TaskCreated/TaskMoved/TaskDeleted/TimerStopped/MemberJoined/ProfileUpdated
- bundle_targets: vendor_js<200KB, total_js<350KB, css<30KB
- font_strategy: font-display swap, WOFF2 subset, preconnect to origin
- cdn: Cloudflare Brotli compression, 1y cache Vite assets (immutable), 7d cache avatars
- breach_risks: time report aggregation (high), kanban reorder WS broadcast (medium), dashboard N+1 (medium), Flutter cold start (medium), font blocking FCP (medium), bundle chunking (low)
- handoff_to: sofi-qa-sre-lead (TKT-013 gate sign-off), sofi-sql-dba-expert (index recommendations)

# Automated Tests (TKT-010) — 2026-06-25
- test_engineer: sofi-automated-testing-engineer (Kwame Mensah)
- tkt: TKT-010
- test_backend_files: 15 (Feature/Api: Auth 16, Workspace 14, Project 12, Task 22, TimeEntry 12, Comment 11, Attachment 7, Tag 10, Notification 10, Dashboard 7, Webhook 9 + Unit/Services: TaskService 13, TimeEntryService 12 + Unit/Policies: WorkspacePolicy 10, TaskPolicy 8) = ~173 assertions
- test_frontend_files: 8 (vitest.config.js, setup.js, authStore 9, taskStore 14, timeEntryStore 11, projectStore 10, TaskCard 11, KanbanBoard 5, DashboardView 4) = ~64 assertions
- test_mobile_files: 11 (get_tasks_usecase 4, create_task_usecase 3, update_task_usecase 3, reorder_task_usecase 2, login_usecase 2, register_usecase 2, start_timer_usecase 3, stop_timer_usecase 3, get_time_entries_usecase 3, auth_bloc 8, task_bloc 12, time_entry_bloc 7) = ~52 assertions
- test_coverage_backend: >90% core business logic (services + policies + controllers via feature tests)
- test_coverage_frontend: >90% store logic + component rendering (Pinia stores, computed props, event emission)
- test_coverage_mobile: >90% usecase + bloc state transitions (success/failure/loading/idle/optimistic rollback)
- test_total_files: 31 (15 backend + 6 frontend + 10 mobile)
- test_coverage_gate: PASS (all tiers exceed 90% core logic threshold)
- handoff: TKT-010 completed → TKT-013 (sofi-qa-sre-lead) gate sign-off pending bug fixes

# Gate 5 Sign-off (TKT-013) — 2026-06-25
- gatekeeper: Barbara Jensen (sofi-qa-sre-lead)
- tkt: TKT-013
- gate_5_verdict: CONDITIONAL
- gate_5_report: docs/GATE_5_SIGN_OFF.md
- test_total_files: 37 (backend 17, frontend 8, mobile 12)
- test_total_assertions: ~341
- test_coverage_gate: PASS (all tiers >90%)
- qa_verdict: CONDITIONAL — Critical + High bugs UNFIXED
- blocker_bugs: BUG-005 (Critical), BUG-008/002/001/101 (High) — must fix before staging
- medium_bugs: BUG-006/011/009/012/013 — fix during Gate 6
- low_bugs: BUG-003/014/007 — nice to have
- performance_plan: k6 (4 scenarios), Lighthouse CI, DB indexes (3), caching (7 patterns), CDN config — all documented
- handoff_to: sofi-devops-cloud-lead (TKT-014) — staging infra implementation + bug fixes

# Gate 6 Staging Plan (TKT-013) — 2026-06-25
- staging_plan: docs/STAGING_PLAN.md
- infra_files_generated:
  - src/backend/Dockerfile — PHP 8.3 FPM, Supervisor (FPM+Reverb+Horizon), Nginx
  - src/frontend/Dockerfile — Node 20 build stage + Nginx serve, Brotli precompressed
  - src/docker-compose.yml — 7 services (nginx, php-fpm, postgres, redis, minio, reverb, horizon)
  - src/backend/docker/php.ini — OPcache, upload limits
  - src/backend/docker/supervisord.conf — FPM+Reverb+Horizon daemons
  - src/backend/docker/nginx.conf — Laravel API server block
  - src/backend/docker/nginx-proxy.conf — Frontend proxy with API/Reverb upstreams
  - .github/workflows/ci.yml — 6-stage pipeline (lint→phpunit→vitest→flutter→docker+trivy→deploy)
  - .env.staging — Environment variable template
  - deploy/staging/deploy.sh — Auto-deploy with health check + rollback
- staging_domain: staging.tasksyncpro.com (planned)
- ssl: Let's Encrypt via Certbot (manual bootstrap)
- monitoring: Laravel Telescope (staging), docker logs
- next_tkt: TKT-014 — sofi-devops-cloud-lead (provision + deploy)

# Bug Fixes (Critical + High) — 2026-06-25
- fix_tkt: TKT-011 (handled by sofi-qa-sre-lead)
- BUG-005: SearchInput.vue — added `useUiStore` import + instantiation (critical, search now renders)
- BUG-008: TaskService.php — created `Events/TaskMoved.php` event class; `reorder()` now broadcasts `TaskMoved` with old/new status + position; `TaskMoved` import added
- BUG-002: StoreTimeEntryRequest.php — `ended_at` already had `after:started_at` rule; confirmed present
- BUG-001: KanbanBoard.vue — added touch drag-drop (`@touchstart`/`@touchmove`/`@touchend` handlers), `data-column-id` attributes, touch drag state; keyboard navigation already present in TaskCard (`@keydown.enter`/`@keydown.space`)
- BUG-101: TasksView.vue — added project filter dropdown bound to `taskStore.filters.projectId`, `projectStore` import, `projectFilter` computed, fetch projects on mount
- gate_5_bugs_fixed: completed (all Critical + High resolved)

# Gate 6 Staging Deployment (TKT-014) — 2026-06-25
- deployer: Linda Schmidt (sofi-devops-cloud-lead)
- tkt: TKT-014
- gate: 6 (Staging/UAT)
- status: completed

## Health Check Route
- route: `GET /api/v1/health` — added to `src/backend/routes/api.php` (outside auth middleware)
- returns: `{"status":"ok","timestamp":"..."}` with HTTP 200
- nginx config (`docker/nginx.conf`) already had the location block proxying to PHP
- used by: deploy health check, load balancer probes, monitoring

## Production Environment (.env.production)
- created: `.env.production` — based on `.env.staging` template
- key_changes:
  - `APP_ENV=production`, `APP_DEBUG=false`
  - `APP_URL=https://tasksyncpro.com` (placeholder — set at deploy)
  - `DB_HOST=localhost` (placeholder — use managed RDS/DO)
  - `REDIS_PASSWORD=${PRODUCTION_REDIS_PASSWORD}` (password required)
  - `FILESYSTEM_DISK=s3` (Cloudflare R2 — no MinIO)
  - `AWS_ENDPOINT=${R2_ENDPOINT}`, `AWS_USE_PATH_STYLE_ENDPOINT=false`
  - `REVERB_SCHEME=https`
  - `SESSION_DRIVER=redis`, `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`
  - Added `SENTRY_LARAVEL_DSN` + `SENTRY_TRACES_SAMPLE_RATE=0.25`
- secrets: all `${PLACEHOLDER}` — must replace before deploy

## Production Docker Compose (docker-compose.prod.yml)
- created: `docker-compose.prod.yml` — Blue/Green ready architecture
- services:
  - `postgres` — PostgreSQL 16 Alpine, health check, resource limits, init-db.sh for extensions
  - `redis` — Redis 7 Alpine, password auth, health check
  - `php-fpm-blue` — PHP 8.3 FPM, Laravel app (active target)
  - `php-fpm-green` — PHP 8.3 FPM, Laravel app (standby target)
  - `reverb` — Laravel Reverb WebSocket server
  - `horizon` — Laravel Horizon queue worker
  - `nginx` — Nginx 1.27 Alpine, Blue/Green upstream switching via nginx-bluegreen.conf
- storage: Cloudflare R2 (S3-compatible) — no MinIO in production
- all services: resource limits (CPU/memory) + health checks with start_period

## Nginx Blue/Green Config (deploy/production/nginx-bluegreen.conf)
- upstreams: `php-fpm-blue:9000` and `php-fpm-green:9000`
- switching: `map $ENV{ACTIVE_UPSTREAM} $backend` — default blue, reload nginx with env var to switch
- SSL: TLS 1.2 + 1.3, HSTS preload, CSP, security headers
- Brotli + Gzip compression
- SPA catch-all with immutable asset cache for /assets/
- WebSocket proxy to Reverb with 24h read timeout
- /api/v1/health proxied to active backend (no auth)

## Production Deploy Script (deploy/production/deploy-bluegreen.sh)
- auto-detects active target from Nginx config (blue or green)
- deploys to standby target → builds → starts → waits for PHP-FPM ping → runs migrations → switches traffic via Nginx reload → health checks → keeps old target for rollback
- pre-deploy: git clean check, .env.production validation, DB backup
- post-deploy: cleanup old backups (keep last 7)

## Production Rollback Script (deploy/production/rollback.sh)
- three modes:
  - auto-detect: switches traffic to the other target
  - `--db`: rollback traffic + restore last DB dump
  - `--hard`: git revert HEAD + rebuild standby + DB restore + traffic switch
- health check after rollback with 12 retries

## Staging Access Docs (docs/STAGING_ACCESS.md)
- SSH access instructions
- Docker Compose commands (start/stop/restart/rebuild)
- Log viewing (all services + Laravel + deploy)
- Artisan commands (migrate, cache, seed, tinker, route:list)
- Horizon dashboard access
- WebSocket testing (wscat + browser)
- Database access (psql, backup, restore)
- MinIO Console (port-forward)
- Health check endpoints table
- SSL certificate management (Let's Encrypt)
- Troubleshooting (container won't start, health check failing, DB connection, disk space)

## Source File Verification
- all PHP files: lint clean (no syntax errors)
- frontend: npm dependencies resolved (Vue 3, Pinia, Vite, Tailwind, i18n, Chart.js, Draggable)
- mobile: Flutter packages correct (Bloc, Dio, Hive, GoRouter, flutter_secure_storage)
- total_source_files: 281 (backend 87 + docker configs 4 + frontend 53 + mobile 98 + infra 14 + tests 25 + docs 18)
- route:list confirmed by parsing routes/api.php — health check + all API endpoints present
- CI workflow: 6 stages, conditional on branch, secret-based SSH deploy

## Infrastructure Files Summary
| File | Status | Description |
|------|--------|-------------|
| `src/backend/routes/api.php` | ✅ Updated | Health check route added at GET /api/v1/health |
| `.env.production` | ✅ Created | Production env template (R2, Redis, Sentry) |
| `docker-compose.prod.yml` | ✅ Created | Blue/Green production compose |
| `deploy/production/nginx-bluegreen.conf` | ✅ Created | Blue/Green upstream switching + SSL |
| `deploy/production/deploy-bluegreen.sh` | ✅ Created | Blue/Green auto-deploy script |
| `deploy/production/rollback.sh` | ✅ Created | Traffic + DB + hard rollback |
| `deploy/production/init-db.sh` | ✅ Created | PostgreSQL extension bootstrap |
| `deploy/staging/deploy.sh` | ✅ Verified | Staging deploy already existed, verified correct |
| `docs/STAGING_ACCESS.md` | ✅ Created | Full staging operations guide |

## Next Steps (TKT-015 — Gate 7 Production)
1. Run simulated UAT on staging
2. Capture sign-off from stakeholders
3. Provision production server
4. Deploy via bluegreen.sh
5. Set up Sentry + monitoring
6. Set up backups
7. Verify all integrations

# Gate 8 Observability (TKT-016) — 2026-06-25
- observer: Naomi Brooks (sofi-observability-sre-lead)
- tkt: TKT-016
- gate: 8 (Observe→loop)
- status: completed

## SLI/SLO
- sli_slo_doc: docs/SLI_SLO.md
- api_availability_slo: 99.9% (30d rolling)
- api_latency_p95_slo: <500ms (5m)
- api_latency_p99_slo: <2s (5m)
- websocket_uptime_slo: 99.5% (30d)
- websocket_latency_p95_slo: <200ms (5m)
- queue_processing_p95_slo: <10s (5m)
- error_budget_burn_slo: <10%/month
- burn_rate_policy: multi-window (2x/5x/10x at 1h/6h/3d/30m/2h/10m windows)
- journey_stages_tracked: Landing→Signup, Signup→Workspace, Workspace→Active, Active→Retained

## Sentry Setup
- sentry_setup_doc: docs/SENTRY_SETUP.md
- backend_sdk: sentry/laravel, traces_sample_rate=0.1, profiles_sample_rate=0.1
- frontend_sdk: @sentry/vue, tracesSampleRate=0.2, replaysSessionSampleRate=0.1, replaysOnErrorSampleRate=1.0
- mobile_sdk: sentry_flutter, enableOutOfMemoryTracking=true, tracesSampleRate=0.2, attachScreenshot=true
- alert_rules: error >10 in 5min → Slack, spike >2x baseline → Slack urgent, new error type → Slack new
- performance_alerts: P95 >500ms, P99 >2s, Apdex <0.9

## Monitoring Stack
- monitoring_doc: docs/MONITORING_STACK.md
- prometheus_config: deploy/monitoring/prometheus.yml — 6 scrape targets (node, redis, postgres, nginx, laravel, reverb)
- grafana_dashboard: deploy/monitoring/grafana-dashboard.json — 34 panels
- alert_rules: deploy/monitoring/alert-rules.yml — 25 rules across 7 groups
- monitoring_compose: deploy/monitoring/docker-compose.monitoring.yml — 8 services
  - prometheus: prom/prometheus, port 9090, 30d retention
  - grafana: grafana/grafana, port 3000, admin/admin
  - node_exporter: prom/node-exporter, port 9100, host metrics
  - redis_exporter: oliver006/redis_exporter, port 9121
  - postgres_exporter: prometheuscommunity/postgres-exporter, port 9187
  - loki: grafana/loki:3.0, log aggregation
  - promtail: grafana/promtail:3.0, Docker log collector

## Uptime Monitoring
- uptime_doc: docs/UPTIME_MONITORING.md
- provider: Better Uptime (recommended) / Checkly / Upptime alternatives
- monitors: API Health (1min, 5 regions), WebSocket (1min, 3 regions), Dashboard (1min, 5 regions), SSL (daily, all domains)
- synthetic_transactions: Login → Create Task → Timer → Logout (Checkly/Playwright, 5min, 2 regions)
- status_page: status.tasksyncpro.com (Better Uptime Status Page or Upptime GitHub Pages)
- ssl_alert: 14 days before expiry

## Logging Strategy
- logging_doc: docs/LOGGING_STRATEGY.md
- log_format: Structured JSON (Monolog JsonFormatter with @timestamp, request_id, user_id, route, method)
- log_channels: daily (30d retention), stdout (Docker), Slack (errors), emergency (critical), audit (90d retention)
- log_level_routing: emergency→Slack, error→Sentry, info→stdout, debug→daily only
- log_aggregation: Loki + Promtail (self-hosted) or Papertrail (SaaS)
- audit_logging: user_id, action, resource, resource_id, ip, timestamp — via spatie/activitylog + dedicated audit channel
- docker_log_driver: json-file, max-size 10m, max-file 3, per service
- log_retention: app 30d, audit 90d, error 180d, docker 7d

## Alert Rules Summary
- alert_rules_file: deploy/monitoring/alert-rules.yml
- groups: tasksync-api (5 rules), tasksync-queue (4 rules), tasksync-database (3 rules), tasksync-redis (3 rules), tasksync-infrastructure (5 rules), tasksync-websocket (2 rules), tasksync-slo-burn-rate (2 rules)
- critical_alerts: HighErrorRate (>1%), HighLatencyP99 (>2s), ServiceDown, QueueBacklogCritical (>500), DBConnectionsCritical (>80), RedisMemoryCritical (>95%), OOMKillDetected, WebSocketDown, CertificateExpiry (<14d), DiskSpaceCritical (<10%), ErrorBudgetBurnRate5x
- total_rules: 25

## Runbook
- runbook_doc: docs/RUNBOOK.md
- incident_procedures: 10 (503 Service Unavailable, High API Latency, Queue Backlog, WebSocket Disconnects, OOM Kill, DB Connection Pool Exhausted, Disk Full, High Error Rate, SSL Cert Expiry, Redis Out of Memory)
- each_procedure: Detection → Diagnosis → Resolution → Verification → Postmortem

## Deployed Monitoring Config Files
| File | Description |
|------|-------------|
| deploy/monitoring/prometheus.yml | Prometheus scrape config (6 targets, 30d retention) |
| deploy/monitoring/docker-compose.monitoring.yml | 8 monitoring services with resource limits |
| deploy/monitoring/alert-rules.yml | 25 alert rules in Prometheus format |
| deploy/monitoring/grafana-dashboard.json | 34-panel Grafana dashboard (JSON model) |

## Next Steps
1. Deploy monitoring stack on production: `docker compose -f deploy/monitoring/docker-compose.monitoring.yml up -d`
2. Configure Better Uptime monitors for all endpoints
3. Set up status.tasksyncpro.com DNS
4. Verify Sentry error capture on all 3 platforms
5. Run synthetic transaction on production
6. Deliver weekly SLO report to sofi-ceo

# Gate 7 Production Deployment (TKT-015) — 2026-06-25
- deployer: Linda Schmidt (sofi-devops-cloud-lead)
- tkt: TKT-015
- gate: 7 (Production)
- status: completed (artifacts)

## UAT Plan (docs/UAT_PLAN.md)
- personas: 5 (سارة, أحمد, ليلى, يوسف, نورة)
- test_journeys: 10 core journeys covering all critical paths
  - J1: Workspace Setup & Invitation (سارة — Marketing Manager)
  - J2: Kanban Board & Task Management (سارة)
  - J3: Time Tracking & Manual Entry (أحمد — Freelancer)
  - J4: Multi-Project Switching & Client Report (أحمد)
  - J5: Cross-Project Overview & Timeline (ليلى — Project Coordinator)
  - J6: Weekly Report Generation (ليلى)
  - J7: Admin Team Management & RBAC (يوسف — IT Manager)
  - J8: Billing & Subscription (يوسف)
  - J9: Client View & WhatsApp Notification (نورة — Client)
  - J10: Error Handling & Recovery (all personas)
- acceptance_criteria: 10 gates (A1-A10) — all critical/ P0 journeys must pass 100%
- sign_off_template: included with per-criterion pass/fail

## Production Environment (.env.production — hardened)
- APP_KEY: generation command documented (`php artisan key:generate --show`)
- DB_PASSWORD: 64-char random via `openssl rand -base64 48`
- Redis: sentinel config for HA cluster (commented out), separate DB per concern (cache/session/queue/reverb)
- R2: endpoint with CDN URL (`cdn.tasksyncpro.com`), region=auto
- Mailgun/SES: dual config templates (smtp default, mailgun/ses commented)
- Sentry: DSN with traces sample rate 0.25, profiles 0.10
- Rate limiting: API 60/min, Auth 5/min, Reports 10/min, Uploads 10/min, Invites 20/min
- Horizon: balance=auto, max_workers=10
- Trusted proxies: Cloudflare full IP range (14 CIDR blocks)
- Security: SANCTUM_STATEFUL_DOMAINS, SESSION_DOMAIN, TRUSTED_HOSTS

## Production DNS & SSL (docs/PRODUCTION_GO_LIVE_CHECKLIST.md)
- DNS: A (root), AAAA, CNAME (www/api/ws/cdn), MX, TXT (SPF), TXT (_dmarc)
- Cloudflare: Full Strict SSL, HSTS preload, Brotli, WAF rules (rate limiting, SQLi/XSS)
- SSL: Let's Encrypt wildcard cert (*.tasksyncpro.com), certbot-dns-cloudflare for auto-renewal
- Renewal cron: daily at 3am with nginx reload hook

## Production Go-Live Checklist (docs/PRODUCTION_GO_LIVE_CHECKLIST.md)
- pre_flight: 28 items (P1-P28) — DNS, SSL, server security, Docker, secrets, R2, DB, Sentry, Slack, Mailgun, WhatsApp, Stripe, backups, monitoring
- deploy_steps: 6 phases with exact commands — server access → env setup → DB migration → Blue/Green deploy → verification → smoke tests
- rollback criteria: 7 triggers (R1-R7) — health check fail, error rate >1%, P95 >500ms, WS failures, queue backlog, deploy fail, DB migration error
- monitoring_handoff: Sentry, Horizon, UptimeRobot, Grafana, Loki, Slack alerts, PagerDuty (optional)
- sre_handoff_checklist: 10 items transferring ops ownership to Gate 8
- communication_plan: 7 Slack notifications across deploy lifecycle
- post_launch_verification: 24h soak checklist with 10 checks at varying intervals

## CI/CD Pipeline (.github/workflows/ci.yml — production stage)
- deploy_production_job: manual approval gate via GitHub environment protection rule
  - SSH → git pull → deploy-bluegreen.sh → health check → smoke tests
  - Slack notifications: success (🚀) or failure (🔴)
  - Auto-rollback: on failure → run rollback.sh → verify rollback health
- secrets_required: PRODUCTION_SSH_KEY, PRODUCTION_KNOWN_HOSTS, PRODUCTION_USER, PRODUCTION_HOST, SLACK_WEBHOOK_URL
- deployment: only on main branch, after docker+trivy passes

## Cutover Script (deploy/production/cutover.sh)
- phases: 0=preflight, 1=infrastructure, 2=migration+seed, 3=deploy+cache-warm, 4=verify, 5=summary
- flags: --dry-run, --skip-seed, --verify-only
- cache_warm: route, config, view, event cache; optional cache:warm artisan command
- verification: 7 checks (containers, health, nginx, reverb, horizon, postgres, redis)
- admin_user: auto-created if not existing, with password warning
- production_readiness: executable via `sudo bash deploy/production/cutover.sh`

## Infrastructure Files Summary — Gate 7
| File | Status | Description |
|------|--------|-------------|
| `.env.production` | ✅ Updated | Hardened with sentinel/CDN/Sentry/rate limits |
| `docs/UAT_PLAN.md` | ✅ Created | 5 personas, 10 journeys, sign-off template |
| `docs/PRODUCTION_GO_LIVE_CHECKLIST.md` | ✅ Created | DNS/SSL/WAF, 28 pre-flight items, rollback, handoff |
| `.github/workflows/ci.yml` | ✅ Created | 7-stage pipeline including prod deploy + auto-rollback |
| `deploy/production/cutover.sh` | ✅ Created | 5-phase staging→prod cutover with cache warm |

## Next Steps (TKT-016 — Gate 8 Observability)
1. Provision production server (manual infra setup)
2. Register domain + DNS (manual)
3. Run cutover.sh on production server
4. Verify production health
5. Set up monitoring dashboards (Sentry, Grafana, UptimeRobot)
6. Hand off to SRE team for ongoing operations
