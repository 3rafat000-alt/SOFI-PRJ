# SECURITY — TaskSync Pro (SAAS-001)

> **Gate:** 3 · **Owner:** Security & Compliance Architect (Ruth Goldberg) · **Status:** Locked
> **Consumes:** PRD, ARCHITECTURE.md, JOURNEY_MAP, SCHEMA
> **Produces:** STRIDE threat model, auth/authorization design, OWASP mitigations, data classification, pen-test plan
> **Next:** TKT-005 ← performance-architect · TKT-006 ← data-schema-engineer · TKT-008 ← api-integration-specialist

---

## Table of Contents

1. [STRIDE Threat Model](#1-stride-threat-model)
2. [Authentication Design](#2-authentication-design)
3. [Authorization (RBAC)](#3-authorization-rbac)
4. [OWASP Top 10 Mitigations](#4-owasp-top-10-mitigations)
5. [Data Classification & Encryption](#5-data-classification--encryption)
6. [API Security](#6-api-security)
7. [Mobile Security](#7-mobile-security)
8. [Penetration Test Plan](#8-penetration-test-plan)

---

## 1. STRIDE Threat Model

### 1.1 API (Laravel 11 — PHP-FPM via Nginx)

| Category | Threat | Impact | Likelihood | Mitigation |
|---|---|---|---|---|
| **Spoofing** | Attacker forges JWT/Sanctum token via leaked secret key | Account takeover, data access | Medium | Sanctum token stored bcrypt-hashed; APP_KEY rotated on compromise; token scoped to device fingerprint |
| **Spoofing** | OAuth state parameter fixation during Google login | Hijack social auth callback | Low | Laravel Socialite state validation; PKCE enforced; redirect URI whitelist |
| **Tampering** | Request body manipulation (e.g., change `task.project_id` to cross-team) | Access/modify task in wrong workspace | High | Form Request validation; Policy checks on every resource; relational integrity via `project->team_id` chain |
| **Tampering** | Mass assignment on User model via PATCH `/api/me` | Elevate role, change email without verification | High | `$fillable` / `$guarded` on all Eloquent models; verification required for email changes |
| **Repudiation** | User deletes task, claims no action taken | No accountability, support disputes | Medium | `spatie/laravel-activitylog` on all destructive actions (delete, role change, plan change, member remove); immutable log stored separately |
| **Info Disclosure** | Error stack traces returned via API during debug | Source code, DB credentials, env vars exposed | High | `APP_DEBUG=false` in production; custom exception handler returns generic JSON errors; logs to stderr only |
| **Info Disclosure** | Unauthenticated enumeration of user accounts via login error messages | Harvest valid emails for phishing | Medium | Generic login error ("Invalid credentials" — not "User not found"); rate limit on auth endpoints |
| **DoS** | Unauthenticated high-volume requests to auth endpoints | API unavailable, auth queue backlog | High | Cloudflare WAF rate limiting; Laravel `throttle:60,1` on auth routes; Redis cache key per IP |
| **DoS** | Slow HTTP body attack on POST endpoints | PHP-FPM worker pool exhaustion | Medium | Nginx `client_body_timeout=10s`; `request_terminate_timeout=60s`; Cloudflare body size limits |
| **Elevation** | User crafts request to change own role via PATCH `/api/teams/{id}/members/{id}` | Member escalates to Owner, modifies billing | Critical | Policy `updateMemberRole()` gated to Owner only; role change logged and verified via notification |

### 1.2 Web Dashboard (Vue 3 SPA)

| Category | Threat | Impact | Likelihood | Mitigation |
|---|---|---|---|---|
| **Spoofing** | XSS steals `XSRF-TOKEN` cookie from another SPA on subdomain | CSRF bypass, impersonate session | Low | Cookie `SameSite=Strict`; `__Host-` prefix on sensitive cookies; CSP header blocks inline scripts |
| **Tampering** | DOM-based XSS via `v-html` on user comment content | Steal tokens, deface dashboard, pivot to API | Medium | Vue auto-escaping (`{{ }}`) everywhere; `v-html` banned in codebase; DOMPurify on any rendered HTML |
| **Repudiation** | Client-side event logged only in browser memory | No server-side audit trail for drag operations | Low | Drag-drop sends API request on drop; server `TaskMoved` event logged; optimistic update rolls back on API failure |
| **Info Disclosure** | Leaked API token in browser extension storage or error toast | Attacker exfiltrates token via compromised extension | Medium | Sanctum SPA uses cookie-based session (no token in JS memory); localStorage never used for secrets |
| **Info Disclosure** | Source maps deployed to production | Reverse-engineer API structure, find debug endpoints | Medium | Vite `sourcemap=false` in production build; `.env` never shipped; `APP_ENV=production` |
| **DoS** | Large Kanban board (1000+ cards) with no virtualization | Browser freeze, tab crash, poor UX | Low | Virtual scrolling (`vue-virtual-scroller`); pagination at 50 cards; debounced drag |
| **Elevation** | URL manipulation to access `/settings/billing` without subscription role | Billing UI exposed but data gated | Low | Vue router `beforeEach` guard syncs with current team role; API enforces on backend |

### 1.3 Mobile App (Flutter)

| Category | Threat | Impact | Likelihood | Mitigation |
|---|---|---|---|---|
| **Spoofing** | Token extracted from device backup or file system | Offline token replay, account access | Medium | `flutter_secure_storage` (Keychain/Keystore); biometric lock optional; token not in shared_preferences |
| **Tampering** | Hive local store modified on rooted/jailbroken device | Inject fake tasks, time entries, overflow local sync queue | Medium | Integrity check via HMAC on Hive box; server rejects entries where `created_at` outside acceptable window; device root detection |
| **Repudiation** | Offline timer entry created locally, conflicting with server record | Duplicate or missing time entries, billing disputes | Medium | Server-side dedup via `idempotency_key` (UUIDv4 per entry); last-write-wins on `updated_at`; conflict logged |
| **Info Disclosure** | Screenshot in app switcher shows task details | Sensitive project names visible on device overview | Low | `FlutterWindowManager` FLAG_SECURE enabled; app switcher blur overlay |
| **Info Disclosure** | Debug logging in release build leaks API URLs, tokens | Attacker inspects device logcat/console | Medium | `assert` blocks for logging; `flutter build --release` strips debug; Sentry breadcrumbs scrubbed of PII |
| **DoS** | Malformed push notification (FCM data payload) crashes app | App unavailable until restart | Low | Push handler try-catch around data parse; notification payload schema validated |
| **Elevation** | API endpoint called with elevated privileges via modified HTTP client | User patches task for different project | Medium | Backend Policy enforces per-request; client never sends role/permission data |

### 1.4 PostgreSQL Database

| Category | Threat | Impact | Likelihood | Mitigation |
|---|---|---|---|---|
| **Spoofing** | Weak `pg_hba.conf` allows IP-spoofed connection from non-local host | DB exposed on public network | Critical | PostgreSQL bound to `127.0.0.1`; no public port; Cloudflare Tunnel or Tailscale for remote access |
| **Tampering** | SQL injection via raw `DB::statement()` in ReportRepository | Read/write arbitrary data, extract secrets | High | Raw queries use parameterized bindings only; no string concatenation; `pg_query` param validation |
| **Tampering** | Stale or unauthenticated database migration run against production | Schema corruption, data loss | High | Migrations version-controlled; `--force` only in CI; rollback tested for each migration; deploy script requires manual approval |
| **Repudiation** | Audit log stored in same DB with no append-only protection | Admin with DB access deletes audit trail; no recovery | Medium | Audit logs written to separate table with `FORBID_DELETE` trigger; offloaded to S3 via `spatie/laravel-activitylog` cleanup job |
| **Info Disclosure** | Query logging with bound parameters in production logs | Query data (passwords, task titles) in plaintext | Medium | `DB::disableQueryLog()` in production; log level `warning`; never log query bindings |
| **Info Disclosure** | Encrypted sensitive fields exposed via backup dump | PII readable if encryption key compromised | Medium | Application-layer encryption (Laravel `encrypt` cast) before DB write; backup encrypted at rest with separate key |
| **DoS** | Unoptimized report query locks large table (`time_entries` JOIN `tasks`) | Connection pool exhausted, all requests queue | Medium | Report queries to read replica; materialized views for aggregates; query timeout 5s in config |
| **Elevation** | PostgreSQL row-level security bypass via superuser connection | Unauthorized read across team boundaries | Low | RLS policy per team only as defense-in-depth; primary authorization in Laravel Policy layer |

### 1.5 Redis (Cache + Queue + Reverb Pub/Sub)

| Category | Threat | Impact | Likelihood | Mitigation |
|---|---|---|---|---|
| **Spoofing** | Redis exposed without `AUTH` or `--protected-mode` | Any network actor can read/write keys | Critical | Redis `requirepass` set; bound to `127.0.0.1`; `rename-command FLUSHALL` + `FLUSHDB` |
| **Tampering** | Poisoned cache key injected via crafted API response | Serve stale/malicious cached data to users | Low | Cache keys namespaced; TTL capped (60-600s); no user input in cache key composition |
| **Info Disclosure** | Session data in Redis containing user PII | Email, name, locale exposed via Redis dump | Medium | Session data minimal (user_id, team_id, locale only); not PII stored in session |
| **Info Disclosure** | Reverb pub/sub channel name includes task title | WebSocket channel listener sees task names via wildcard | Medium | Channel names use UUIDs not human-readable IDs; private channels with auth callback |
| **DoS** | Redis memory exhaustion from unbounded job queue or large cache keys | OOM kills Redis, queue stalls, app degraded | Medium | `maxmemory=1GB` with `allkeys-lru` eviction; Horizon max attempts per job; queue job size limit 64KB |
| **Elevation** | Websocket auth callback bypass via forged channel name | Subscribe to another team's task updates | High | Private channels authenticated via Sanctum; `Authorization` channel callback checks team membership per channel |

### 1.6 File Storage (S3 — DigitalOcean Spaces)

| Category | Threat | Impact | Likelihood | Mitigation |
|---|---|---|---|---|
| **Spoofing** | Stolen S3 credentials (`AWS_ACCESS_KEY_ID`/`SECRET_ACCESS_KEY`) | Upload malware, download all files, run up bill | High | IAM user scoped to bucket only (no list/delete on other buckets); credentials in `.env` not in code; rotated quarterly |
| **Tampering** | Unvalidated file upload (PHP shell, SVG with XSS, large zip bomb) | Server-side malware execution, storage DoS, XSS via uploaded SVG | High | Extension + MIME validation; ClamAV scan on upload; SVG sanitization; 10MB file limit; files stored outside webroot |
| **Repudiation** | No upload audit trail | Cannot prove who uploaded a malicious attachment | Medium | `FileService` logs all uploads with user_id, filename, size, hash; activitylog entry on upload and delete |
| **Info Disclosure** | Presigned URL with excessive expiry shared | File accessible after user should have lost access | Medium | Presigned URL expiry 15 minutes; URL generation always scoped to specific object; no wildcard presigned URLs |
| **Info Disclosure** | Public bucket policy misconfigured (readable by anyone) | All attachments, avatars exposed | Critical | Bucket policy blocks public read; all access via presigned URLs; Cloudflare proxy verifies origin |
| **DoS** | Large archive extraction (zip/tar bomb) fills disk | Storage exhaustion, server crash | Medium | Archive uploads rejected at FileService; max file size (10MB) enforced at Nginx `client_max_body_size` |
| **Elevation** | Upload to another user's avatar path via path traversal | Replace someone else's avatar | Medium | Avatar path is UUID-based, not user ID; no user input in S3 key; `FileService` strips path separators |

---

## 2. Authentication Design

### 2.1 Architecture: Laravel Sanctum (Dual Mode)

TaskSync Pro uses Sanctum in hybrid mode:

| Mode | Client | Mechanism | Token Source |
|---|---|---|---|
| **SPA (stateful)** | Vue 3 Dashboard | Cookie-based session via Sanctum SPA middleware | `XSRF-TOKEN` + session cookie |
| **Token (stateless)** | Flutter mobile, Postman, integrations | Bearer token in `Authorization` header | `POST /api/auth/login` returns `plainTextToken` |

### 2.2 Registration Flow

```
Client                    Laravel API                    PostgreSQL          Mailgun
  │                           │                             │                  │
  │ POST /api/auth/register   │                             │                  │
  │ {name, email, password,   │                             │                  │
  │  locale, timezone}        │                             │                  │
  │──────────────────────────>│                             │                  │
  │                           │  Validate:                  │                  │
  │                           │  - email unique             │                  │
  │                           │  - password ≥8, mixed case  │                  │
  │                           │  - locale = ar|en           │                  │
  │                           │  - hCaptcha (production)    │                  │
  │                           │                             │                  │
  │                           │  Hash password (bcrypt)     │                  │
  │                           │  Generate email_token       │                  │
  │                           │  Create user (email_verified_at=null) │         │
  │                           │ ────────────────────────────>│                  │
  │                           │                             │                  │
  │                           │  Queue EmailVerification    │                  │
  │                           │  notification               │ ───────────────>│
  │                           │                             │                  │
  │ <── 201 Created ──────────│                             │                  │
  │     {user, message:      │                             │                  │
  │      "Verify email"}     │                             │                  │
```

**Rules:**
- Email must be verified before any team/project write operation
- Unverified accounts auto-purged after 48 hours via scheduled command
- Rate limit: 3 registrations per IP per hour (Cloudflare WAF + Laravel throttle)
- Password minimum 8 characters with at least one uppercase and one number
- Email verification link expires in 60 minutes

### 2.3 Login Flow

```
Client                    Laravel API                    PostgreSQL          Response
  │                           │                             │                  │
  │ POST /api/auth/login      │                             │                  │
  │ {email, password,         │                             │                  │
  │  device_name, fcm_token}  │                             │                  │
  │──────────────────────────>│                             │                  │
  │                           │  Check rate limit (5/min/IP, 10/min/email)     │
  │                           │                             │                  │
  │                           │  Find user by email         │                  │
  │                           │ ────────────────────────────>│                  │
  │                           │<── user (or null) ──────────│                  │
  │                           │                             │                  │
  │                           │  Verify password (bcrypt)   │                  │
  │                           │  If failed:                 │                  │
  │                           │    Increment failed_attempts│                  │
  │                           │    If >=5: lockout 15 min   │                  │
  │                           │                             │                  │
  │                           │  If success:                │                  │
  │                           │    Reset failed_attempts    │                  │
  │                           │    Generate device_token    │                  │
  │                           │    Store fingerprint:      │                  │
  │                           │     {device_name, ip,       │                  │
  │                           │      user_agent, last_login}│                  │
  │                           │                             │                  │
  │                           │  SPA mode: set XSRF-TOKEN   │                  │
  │                           │  Mobile mode: return token  │                  │
  │                           │                             │                  │
  │ <── 200 OK ──────────────│                             │                  │
  │     {token OR cookie,    │                             │                  │
  │      user, team}         │                             │                  │
```

**Device Tracking:**
- Each login creates a `personal_access_token` row with custom `device_name`, `ip`, `user_agent` metadata
- User can view and revoke sessions from Profile Settings
- Mobile token rotates on password change
- Inactive tokens auto-revoked after 90 days

### 2.4 Password Reset Flow

```
Client                    Laravel API                    Redis/Temp          Mailgun
  │                           │                             │                  │
  │ POST /api/auth/forgot-pwd │                             │                  │
  │ {email}                   │                             │                  │
  │──────────────────────────>│                             │                  │
  │                           │  Validate email exists      │                  │
  │                           │  Generate 6-digit code      │                  │
  │                           │  Store in Redis (TTL 15min) │                  │
  │                           │ ────────────────────────────>│                  │
  │                           │  Queue PasswordReset email   │                  │
  │                           │ ─────────────────────────────────────────────>│
  │ <── 200 OK (always) ─────│                             │                  │
  │     "If email exists,    │                             │                  │
  │      reset sent"         │                             │                  │
  │                           │                             │                  │
  │ POST /api/auth/reset-pwd  │                             │                  │
  │ {email, code,            │                             │                  │
  │  password, password_conf} │                             │                  │
  │──────────────────────────>│                             │                  │
  │                           │  Verify code from Redis     │                  │
  │                           │  Hash new password          │                  │
  │                           │  Update user                │                  │
  │                           │  Revoke all tokens          │                  │
  │                           │  Queue PasswordChanged      │                  │
  │                           │  notification               │                  │
  │ <── 200 OK ──────────────│                             │                  │
```

**Rules:**
- Password reset code sent to confirmed email only (no SMS for MVP)
- Code is 6-digit numeric, single-use, 15-minute TTL
- Always return 200 on forgot-password to prevent email enumeration
- After reset, all existing tokens revoked (force re-login on all devices)
- Rate limit: 3 reset attempts per email per hour

### 2.5 Session Management

| Feature | Implementation |
|---|---|
| **Token storage** | SPA: HttpOnly cookie + SameSite=Strict. Mobile: `flutter_secure_storage` (Keychain/Keystore) |
| **Token expiry** | Sanctum `expiration`: 24 hours (configurable). Refresh via re-login. |
| **Token revocation** | `$user->tokens()->delete()` on password change, logout-all, or admin force-logout |
| **Device listing** | `GET /api/auth/sessions` — returns device_name, last_used_at, ip, user_agent |
| **Force logout** | `DELETE /api/auth/sessions/{id}` — revoke specific token. Admin can revoke any team member |
| **Inactivity timeout** | SPA session: 2 hours idle → redirect to login. Token: no idle timeout (mobile expected) |
| **Concurrent limits** | Free: 3 sessions. Pro: 10 sessions. Business: unlimited |
| **Logout flow** | SPA: `POST /api/auth/logout` → delete session cookie + revoke token. Mobile: `POST /api/auth/logout` → revoke token + clear secure storage |

### 2.6 Multi-Factor Authentication (Future — Post-MVP)

- TOTP via `laragear/two-factor` or similar package
- Recovery codes (10, single-use)
- MFA required for Owner role; optional for others
- Trusted device checkbox (skip MFA for 30 days on same device)
- Backup: SMS codes via Twilio for Middle East phone numbers

---

## 3. Authorization (RBAC)

### 3.1 Workspace Roles

| Role | Permissions | Max per team |
|---|---|---|
| **Owner** | Full control: delete team, manage billing, transfer ownership, all admin actions | 1 |
| **Admin** | Manage members, project settings, labels, integrations, reports, all content CRUD | Unlimited |
| **Member** | Create/assign tasks, log time, comment, upload attachments, view reports | Unlimited |
| **Viewer** | Read-only: view tasks, projects, reports. Cannot create, edit, or delete | Unlimited |

### 3.2 Permission Matrix

| Action | Owner | Admin | Member | Viewer |
|---|---|---|---|---|
| Delete team | ✓ | ✗ | ✗ | ✗ |
| Transfer ownership | ✓ | ✗ | ✗ | ✗ |
| Manage billing/plan | ✓ | ✓ (view only) | ✗ | ✗ |
| Invite/remove members | ✓ | ✓ | ✗ | ✗ |
| Change member roles | ✓ | ✓ (except Owner) | ✗ | ✗ |
| Project CRUD | ✓ | ✓ | ✓ (create own) | ✗ |
| Task CRUD (any) | ✓ | ✓ | ✓ | ✗ |
| Task CRUD (assigned) | ✓ | ✓ | ✓ | ✗ |
| Time entry (any) | ✓ | ✓ | ✗ | ✗ |
| Time entry (own) | ✓ | ✓ | ✓ | ✗ |
| Comment on any task | ✓ | ✓ | ✓ | ✗ |
| Upload attachments | ✓ | ✓ | ✓ | ✗ |
| View reports | ✓ | ✓ | ✓ | ✓ (own time) |
| Export data | ✓ | ✓ | ✓ | ✗ |
| Manage integrations | ✓ | ✓ | ✗ | ✗ |
| Manage labels | ✓ | ✓ | ✓ | ✗ |
| Delete comments | ✓ | ✓ | Own only | ✗ |
| Archive/restore project | ✓ | ✓ | ✗ | ✗ |
| View team settings | ✓ | ✓ | ✓ (limited) | ✓ (limited) |

### 3.3 Laravel Implementation

**Policies:**

```php
// app/Policies/TaskPolicy.php
class TaskPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Task $task): bool
    {
        return $user->belongsToTeam($task->project->team)
            && $user->teamRole($task->project->team)->level() >= Role::Viewer;
    }

    public function create(User $user, int $projectId): bool
    {
        $project = Project::findOrFail($projectId);
        return $user->belongsToTeam($project->team)
            && $user->teamRole($project->team)->level() >= Role::Member
            && !$user->isEmailVerified(); // Gate requirement
    }

    public function update(User $user, Task $task): bool
    {
        return $user->belongsToTeam($task->project->team)
            && $user->teamRole($task->project->team)->level() >= Role::Member;
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->belongsToTeam($task->project->team)
            && $user->teamRole($task->project->team)->level() >= Role::Admin;
    }

    public function move(User $user, Task $task): bool
    {
        return $user->belongsToTeam($task->project->team)
            && $user->teamRole($task->project->team)->level() >= Role::Member;
    }

    public function assign(User $user, Task $task): bool
    {
        return $user->belongsToTeam($task->project->team)
            && $user->teamRole($task->project->team)->level() >= Role::Member;
    }
}

// app/Policies/TeamPolicy.php
class TeamPolicy
{
    public function delete(User $user, Team $team): bool
    {
        return $team->owner_id === $user->id;
    }

    public function manageMembers(User $user, Team $team): bool
    {
        return $user->teamRole($team)->level() >= Role::Admin;
    }

    public function changeRole(User $user, Team $team, User $targetUser): bool
    {
        // Owner can change any role; Admin can change non-owner members
        return $user->teamRole($team)->isOwner()
            || ($user->teamRole($team)->isAdmin()
                && !$team->isOwner($targetUser));
    }
}
```

**Gates:**

```php
// App\Providers\AuthServiceProvider
Gate::define('invite-members', fn (User $user, Team $team) =>
    $user->teamRole($team)->level() >= Role::Admin
);
Gate::define('manage-billing', fn (User $user, Team $team) =>
    $user->teamRole($team)->isOwner()
);
Gate::define('export-reports', fn (User $user, Team $team) =>
    $user->teamRole($team)->level() >= Role::Member
);
```

**Middleware:**

```php
// app/Http/Middleware/CheckTeamRole.php
class CheckTeamRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $team = $request->route('team') ?? $request->user()->currentTeam;
        $user = $request->user();

        abort_unless($user->teamRole($team)->level() >= Role::from($role), 403);

        return $next($request);
    }
}
```

### 3.4 IDOR Prevention Checklist (every resource):

| Endpoint | IDOR Vector | Check |
|---|---|---|
| `GET /api/projects/{project}/tasks` | Change `project` ID to other team's project | `$user->belongsToTeam($project->team)` in Policy |
| `PATCH /api/tasks/{task}` | Change `assignee_id` to non-team-member | `$user->belongsToTeam($assignee->currentTeam)` |
| `DELETE /api/teams/{team}/members/{member}` | Remove user from wrong team | `$team->users()->findOrFail($memberId)` |
| `POST /api/tasks/{task}/time-entries/start` | Start timer on task in different team | Task→Project→Team chain validated |
| `GET /api/reports/time?team_id=X` | Pass another team's ID in query | `$user->belongsToTeam($teamId)` checked |
| `PATCH /api/me` | Modify another user's profile | `Auth::id()` vs request body `id` enforced |

### 3.5 Plan Limits Enforcement

```php
// app/Http/Middleware/VerifyPlanLimit.php
class VerifyPlanLimit
{
    public function handle(Request $request, Closure $next): Response
    {
        $team = $request->user()->currentTeam;
        $plan = $team->plan;

        match ($request->route()->getName()) {
            'teams.projects.store' => throw_if(
                $team->projects()->count() >= $plan->max_projects,
                PlanLimitExceededException::class
            ),
            'projects.tasks.store' => throw_if(
                $team->tasks()->count() >= $plan->max_tasks,
                PlanLimitExceededException::class
            ),
            'teams.members.store' => throw_if(
                $team->members()->count() >= $plan->max_members,
                PlanLimitExceededException::class
            ),
            default => null,
        };

        return $next($request);
    }
}
```

---

## 4. OWASP Top 10 Mitigations

### A01:2021 — Broken Access Control

| Risk | TaskSync Pro Mitigation |
|---|---|
| IDOR via resource ID tampering | Every endpoint checks `Policy` for team-scoped access. No resource accessible without `belongsToTeam()` validation. |
| Role escalation | Role changes require Owner authorization; logged and notified to team admins. |
| CORS misconfiguration | Sanctum CORS allows only production domain. `config/cors.php` restricts origins. |
| Direct object reference in reports | Report queries scoped to `team_id` from authenticated user's current team, not from request. |

### A02:2021 — Cryptographic Failures

| Risk | TaskSync Pro Mitigation |
|---|---|
| Weak password storage | bcrypt with cost factor 12. Argon2id for new registrations (Laravel default). |
| Sensitive data in transit | TLS 1.3 enforced via Cloudflare + HSTS preload; mobile app certificate pinning. |
| PII in logs | Logging middleware scrubs `password`, `token`, `credit_card` fields; `DB::disableQueryLog()` in production. |
| Weak encryption algorithm | Laravel `encrypt` uses AES-256-CBC with APP_KEY. No custom crypto. |

### A03:2021 — Injection

| Risk | TaskSync Pro Mitigation |
|---|---|
| SQL injection | Eloquent ORM (parameterized queries). Raw queries only in `ReportRepository` with bound params only. |
| NoSQL injection | N/A — PostgreSQL only. No raw JSON queries from user input. |
| OS command injection | No `shell_exec`, `exec`, or `system` calls. File processing via Flysystem + GD/Imagick. |
| LDAP/LDAP injection | No LDAP usage. |
| Email header injection | Laravel Mail uses SMTP/API drivers; headers sanitized by Mailgun SDK. |

**Form Request Validation (all user input):**

```php
// app/Http/Requests/StoreTaskRequest.php
class StoreTaskRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255', 'sanitize:strip_tags'],
            'description' => ['nullable', 'string', 'max:10000'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'status' => ['required', 'in:todo,in_progress,done,archived'],
            'due_date' => ['nullable', 'date', 'after:now'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'estimated_minutes' => ['nullable', 'integer', 'min:1', 'max:10080'], // max 7 days
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => __('validation.required_title'),
            'due_date.after' => __('validation.due_date_future'),
        ];
    }
}
```

### A04:2021 — Insecure Design

| Risk | TaskSync Pro Mitigation |
|---|---|
| Missing rate limits on auth | Auth: 60/min. General: 300/min. Reports: 30/min. Distinct throttle keys per IP + user. |
| Missing audit trails | `spatie/laravel-activitylog` on all destructive actions; browseable in Team Settings. |
| Weak password recovery | 6-digit code via verified email only; 15-min TTL; rate limited 3/hr. |
| Plan limit bypass | Server-side enforcement in `VerifyPlanLimit` middleware; client never trusts. |

### A05:2021 — Security Misconfiguration

| Risk | TaskSync Pro Mitigation |
|---|---|
| Default credentials | `php artisan db:seed` in production blocked; no default admin users. |
| Debug enabled | `APP_DEBUG=false` enforced in deploy pipeline; CI fails if true. |
| Directory listing | Nginx `autoindex off`; Cloudflare shields origin. |
| Unused routes | Route list audited pre-deploy; admin routes prefix-restricted. |
| CORS wildcard | `config/cors.php` restricts to `https://app.tasksyncpro.com` only. |

### A06:2021 — Vulnerable & Outdated Components

| Risk | TaskSync Pro Mitigation |
|---|---|
| Composer dependencies | `composer audit` in CI; Dependabot weekly; `--no-dev` on deploy. |
| NPM dependencies | `npm audit` in CI; lockfile verified. |
| Laravel version | 11.x with security patches applied on release day. |
| Flutter packages | `pub.dev` repository trusted; SCA via GitHub Dependabot. |
| Docker/base image | Minimal base image; weekly vulnerability scan via Trivy. |

### A07:2021 — Identification & Authentication Failures

| Risk | TaskSync Pro Mitigation |
|---|---|
| Weak password policy | Min 8 chars, mixed case + number. Admin can enforce stronger policy org-wide. |
| Credential stuffing | Rate limit + Cloudflare WAF + hCaptcha on auth endpoints. |
| Session fixation | Sanctum regenerates token on login. Session ID rotated. |
| Insufficient lockout | 5 failed attempts → 15-minute lockout. Admin can manually unlock. |

### A08:2021 — Software & Data Integrity Failures

| Risk | TaskSync Pro Mitigation |
|---|---|
| CI/CD pipeline tampering | GitHub Actions with protected branches; `GITHUB_TOKEN` scoped; manual approval for production deploy. |
| Unsigned update artifacts | Flutter app updates via Play Store/App Store code signing. No sideloaded updates. |
| Dependency confusion | Private packages scoped with `@tasksyncpro`; `composer.json` repositories explicit. |
| Insecure deserialization | No `unserialize()` on user input; API uses JSON only. |

### A09:2021 — Security Logging & Monitoring Failures

| Risk | TaskSync Pro Mitigation |
|---|---|
| Missing audit on critical actions | `spatie/laravel-activitylog` on: delete, role change, plan change, member remove, invite, export. |
| Logs not monitored | Sentry for errors; Laravel Pulse for real-time metrics; PagerDuty alert on auth failure spike. |
| No alert on brute force | Rate limit breach triggers Cloudflare WAF alert + Slack notification to admin. |
| Log injection | Log messages sanitized; newlines/carriage returns stripped. |

### A10:2021 — Server-Side Request Forgery (SSRF)

| Risk | TaskSync Pro Mitigation |
|---|---|
| Webhook URL to internal service | `NotificationService` validates webhook URLs against allowlist; no `http://localhost` or RFC 1918 ranges. |
| File upload to internal storage | S3 endpoint domain validated; no user-controlled storage path. |
| Avatar URL fetching | `FileService` restricts to HTTPS; validates Content-Type response header. |

---

## 5. Data Classification & Encryption

### 5.1 Data Classification Schema

| Classification | Definition | Examples | Handling Requirements |
|---|---|---|---|
| **Critical** | Breach = severe legal/financial damage | Payment data, billing history, APP_KEY | Never logged; encrypted at rest (AES-256); PCI DSS controls; access logged and audited |
| **PII** (Personal Identifiable) | Identifies natural person | Email, name, avatar, IP, timezone | Encrypted at rest; pseudonymized in analytics; right to deletion (PDPL Art. 20); 1-year retention max |
| **Confidential** | Internal business data | Task content, project names, time entries, team settings | Access controlled by RBAC; encrypted in transit; not shared outside team |
| **Public** | Intended for general view | Application name, landing page, pricing | No special controls |

### 5.2 PII Field Inventory

| Entity | Field | Classification | Encryption | Retention | PDPL Note |
|---|---|---|---|---|---|
| `users` | `email` | PII | Laravel `encrypt` cast | Account active + 90 days | Right to erasure (Art. 20); consent required for marketing (Art. 6) |
| `users` | `name` | PII | Not encrypted (search requirement) | Account active + 90 days | Pseudonymize in analytics exports |
| `users` | `avatar` (path) | PII | S3 server-side encryption (AES-256) | Account active + 90 days | Delete on account deletion |
| `users` | `timezone` | PII (low) | Not encrypted | Account lifetime | Low sensitivity |
| `users` | `locale` | PII (low) | Not encrypted | Account lifetime | Low sensitivity |
| `users` | `ip` (login audit) | PII | Not stored in logs; truncated to `/24` in analytics | 30 days | Anonymize after 30d |
| `users` | `fcm_token` | PII | Laravel `encrypt` cast | Token validity | Deleted on token refresh |
| `invitations` | `email` | PII | Encrypted at DB level | 7 days or accepted | Auto-delete expired |
| `attachments` | filename | PII (possible) | S3 SSE | Project active + 90 days | May contain personal filenames |

### 5.3 Encryption Implementation

**At Rest:**

| Layer | Method | Key Management |
|---|---|---|
| **Database (PostgreSQL)** | Laravel `encrypt` cast on `email`, `fcm_token` fields | APP_KEY in `.env`; rotated via `php artisan key:generate` with re-encryption script |
| **Database (transparent)** | PostgreSQL TDE via filesystem encryption (LUKS on disk) | Separate LUKS passphrase from APP_KEY; stored in Vault or secret manager |
| **File storage (S3)** | Server-side encryption (AES-256) | DigitalOcean Spaces SSE-S3 (automatic); MinIO SSE for dev |
| **Redis** | No persistent sensitive data; `requirepass` for auth | Redis AUTH password from `.env` |
| **Backups** | GPG symmetric encryption before S3 upload | Separate backup encryption key; stored offline |
| **Logs** | No PII in logs. Fields scrubbed: password, token, secret, credit_card | — |

**In Transit:**

| Path | Protocol | Cipher | Notes |
|---|---|---|---|
| Browser ↔ Cloudflare | HTTPS | TLS 1.3 (TLS_AES_128_GCM_SHA256) | HSTS preload; redirect HTTP→301 |
| Cloudflare ↔ Origin Server | HTTPS (Full Strict) | TLS 1.3 | Origin certificate pinned in Cloudflare |
| Flutter App ↔ API | HTTPS | TLS 1.3 | Certificate pinning via `http_client` |
| Flutter App ↔ Reverb | WSS | TLS 1.3 | Same pinning |
| Server ↔ PostgreSQL | Unix socket (localhost) | N/A | No network exposure |
| Server ↔ Redis | `127.0.0.1` with `requirepass` | N/A | No network exposure |
| Server ↔ S3 | HTTPS | TLS 1.2+ | SDK default |

### 5.4 PDPL (Saudi Personal Data Protection Law) Compliance

| PDPL Requirement | Implementation | Status |
|---|---|---|
| **Consent** (Art. 6) | Explicit consent checkbox on registration; separate consent for marketing emails | MVP |
| **Purpose limitation** (Art. 7) | Privacy policy states data use; no secondary processing | MVP |
| **Data minimization** (Art. 8) | Minimum fields collected (email, name, password only) | ✓ |
| **Right to access** (Art. 17) | User profile page shows all personal data; data export (JSON/CSV) | MVP+1 |
| **Right to erasure** (Art. 20) | Account deletion deletes or anonymizes all PII within 30 days | MVP+1 |
| **Data breach notification** (Art. 24) | 72-hour notification to authority and affected users; automated email queue | MVP+1 |
| **Data retention** (Art. 21) | Scheduled cleanup of expired invitations (7d), unverified users (48h), inactive tokens (90d) | MVP |
| **Cross-border transfer** (Art. 29) | Data hosted in Middle East region (DigitalOcean Spaces fra1-1); no transfer outside KSA/region | MVP+1 |
| **Data Protection Officer** (Art. 30) | Contact email `dpo@tasksyncpro.com` published in privacy policy | MVP+1 |
| **Privacy by design** (Art. 26) | Encryption, access control, audit logging integrated from architecture phase | ✓ |

---

## 6. API Security

### 6.1 Rate Limiting

| Route Group | Limit | Key | Response |
|---|---|---|---|
| `auth.*` (login, register, forgot-password, reset-password) | 60 requests/min | `auth-{ip}` | 429 + `Retry-After` header |
| `api.*` (general API) | 600 requests/min | `api-{user_id}` | 429 + `Retry-After` header |
| `reports.*` | 30 requests/min | `reports-{user_id}` | 429 + `Retry-After` header |
| File uploads | 10 requests/min | `upload-{user_id}` | 429 + `Retry-After` header |
| Invite endpoint | 20 requests/min | `invite-{team_id}` | 429 + `Retry-After` header |

**Laravel Configuration:**

```php
// app/Providers/RouteServiceProvider.php
protected function configureRateLimiting(): void
{
    RateLimiter::for('auth', fn (Request $r) =>
        Limit::perMinute(60)->by($r->ip())
    );

    RateLimiter::for('api', fn (Request $r) =>
        $r->user()
            ? Limit::perMinute(600)->by($r->user()->id)
            : Limit::perMinute(60)->by($r->ip())
    );

    RateLimiter::for('reports', fn (Request $r) =>
        Limit::perMinute(30)->by($r->user()->id)
    );

    RateLimiter::for('uploads', fn (Request $r) =>
        Limit::perMinute(10)->by($r->user()->id)
    );
}
```

### 6.2 CORS Policy

```php
// config/cors.php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'broadcasting/*'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_origins' => [
        env('APP_URL', 'https://app.tasksyncpro.com'),
        env('SANCTUM_STATEFUL_DOMAINS', 'app.tasksyncpro.com'),
    ],
    'allowed_headers' => [
        'Content-Type',
        'X-Requested-With',
        'X-XSRF-TOKEN',
        'Authorization',
        'X-Locale',
    ],
    'exposed_headers' => [
        'Content-Disposition', // for file downloads
    ],
    'max_age' => 7200, // 2 hours
    'supports_credentials' => true, // SPA cookie auth
];
```

### 6.3 Input Validation Strategy

| Technique | Enforcement | Example |
|---|---|---|
| **Laravel Form Requests** | Per-endpoint validation classes | `StoreTaskRequest`, `MoveTaskRequest` |
| **Custom rules** | Arabic-aware validation | `phone:sa` for Saudi numbers, `arabic_name` regex |
| **Sanitization** | Strip HTML tags on text fields | `sanitize:strip_tags` custom rule |
| **UUID validation** | All resource IDs are UUIDs, validated as `uuid` | Route model binding with UUID |
| **Size limits** | String max lengths enforced | title: 255, description: 10000, comment: 5000 |
| **Type coercion** | JSON numeric fields forced to int/float | `priority`, `position`, `estimated_minutes` |

### 6.4 Request Logging (Audit Trail)

| Event | Logged Fields | Retention | Storage |
|---|---|---|---|
| Task created/deleted | user_id, task_id, project_id, action, IP, timestamp | 90 days | `activity_log` table + S3 archive |
| Task moved (column) | user_id, task_id, from_status, to_status, position | 90 days | `activity_log` table |
| Member invited/removed | actor_id, target_email, team_id, action | 90 days | `activity_log` table |
| Role changed | actor_id, target_user_id, from_role, to_role | 1 year | `activity_log` table |
| Plan upgraded | user_id, team_id, from_plan, to_plan | Duration of subscription | `subscription_log` table |
| Login (success/failure) | email/IP hashed, user_agent, success boolean | 30 days | Dedicated `login_attempts` table |
| Export generated | user_id, report_type, filters JSON, timestamp | 30 days | `activity_log` table |
| File uploaded/deleted | user_id, filename, size, hash, action | 90 days | `activity_log` table |

**Immutable audit log design:**

```php
// ActivityLog model — append-only
class ActivityLog extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'activity_log';

    // Prevent any updates or deletes via Eloquent
    public static function boot(): void
    {
        parent::boot();
        static::updating(fn () => throw new \LogicException('Activity log is append-only'));
        static::deleting(fn () => throw new \LogicException('Activity log is append-only'));
    }

    // Database trigger also enforces this
    // CREATE RULE activity_log_no_update AS ON UPDATE TO activity_log DO INSTEAD NOTHING;
}
```

### 6.5 Security Headers

```nginx
# Nginx config
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'nonce-${request_id}'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https://*.tasksyncpro.com; connect-src 'self' https://api.tasksyncpro.com wss://reverb.tasksyncpro.com; font-src 'self' https://fonts.gstatic.com; frame-ancestors 'none'; base-uri 'self'; form-action 'self'" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-Frame-Options "DENY" always;
add_header X-XSS-Protection "0" always;  # Deprecated, CSP handles this
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;
add_header Permissions-Policy "camera=(), microphone=(), geolocation=(self), payment=(self)" always;
```

---

## 7. Mobile Security

### 7.1 Flutter Secure Storage

| Data Item | Storage | Accessibility |
|---|---|---|
| Sanctum API token | `flutter_secure_storage` | Encrypted via Keychain (iOS) / EncryptedSharedPreferences (Android) |
| Device fingerprint | `flutter_secure_storage` | Same — stored per device for session tracking |
| FCM token | `shared_preferences` (non-sensitive) | — |
| Hive DB encryption key | `flutter_secure_storage` | Used to encrypt local time entries |
| Biometric key (optional) | `local_auth` + `flutter_secure_storage` | Face/Touch ID gate before app opens |

**Secure storage implementation notes:**
- `flutter_secure_storage` uses AES-256 on Android (EncryptedSharedPreferences) and Keychain on iOS (kSecAttrAccessible = kSecAttrAccessibleWhenUnlockedThisDeviceOnly)
- On logout: clear secure storage entirely
- On password change: server revokes old token, client deletes token from secure storage
- iOS: `NSFileProtectionCompleteUntilFirstUserAuthentication` for app container

### 7.2 Certificate Pinning

```dart
// lib/core/api/api_client.dart
import 'package:dio/dio.dart';
import 'package:dio/adapters/io_adapter.dart';

class ApiClient {
  late final Dio _dio;

  ApiClient() {
    _dio = Dio(BaseOptions(
      baseUrl: 'https://api.tasksyncpro.com',
      connectTimeout: const Duration(seconds: 10),
      receiveTimeout: const Duration(seconds: 15),
    ));

    // Certificate pinning via SHA-256 hash
    (_dio.httpClientAdapter as IOHttpClientAdapter).onHttpClientCreate = (client) {
      client.badCertificateCallback = (cert, host, port) => false; // Reject all invalid

      // Security: Set pinned certificate hashes
      // These must match the production certificate
      client.connectionFactory = (host, port, options) {
        options?.context?.setTrustedCertificates(
          'assets/ca/tasksyncpro.pem',
        );
        return null; // Use default connection factory with trusted certs
      };

      return client;
    };
  }
}
```

**Certificate pinning rules:**
- Pin the leaf certificate + intermediate (2 pins minimum)
- Include backup pin for certificate rotation (3 pins total)
- Pins updated via app update only; no remote pin configuration (bypass risk)
- Dev/staging builds skip pinning; release builds enforce

### 7.3 Code Obfuscation

| Platform | Tool | Configuration |
|---|---|---|
| **Android** | ProGuard/R8 | `minifyEnabled true` + `proguard-rules.pro` keeps model classes only |
| **iOS** | Swift Compiler optimizations | `-Osize` with whole-module optimization; symbol stripping |
| **Flutter** | `--obfuscate` + `--split-debug-info` | `flutter build apk --obfuscate --split-debug-info=build/debug-info/` |
| **API strings** | Not hardcoded — loaded from env at runtime | No API keys in source code |

**Obfuscation exclusions (proguard-rules.pro):**
```
# Keep data models for JSON serialization
-keep class com.tasksyncpro.models.** { *; }

# Keep Flutter engine classes
-dontwarn io.flutter.**
-keep class io.flutter.** { *; }

# Keep Retrofit/Dio service interfaces
-keep,allowobfuscation interface com.tasksyncpro.api.** { *; }
```

### 7.4 Other Mobile Protections

| Protection | Implementation |
|---|---|
| **Root/jailbreak detection** | `root_detector` package (optional, opt-in by workspace admin) |
| **App transport security (iOS)** | `NSAppTransportSecurity` → only `api.tasksyncpro.com` allowed; all other HTTP denied |
| **Network security config (Android)** | `network_security_config.xml` pins certificate; cleartext disabled |
| **Clipboard protection** | Task titles masked in clipboard preview; sensitive fields clear clipboard after 30s |
| **Screenshot blocking** | `WindowManager.LayoutParams.FLAG_SECURE` on task detail screen |
| **Background snapshot** | `iOS: UIScreen.main.isCaptured` listener; blur sensitive content |
| **Offline data encryption** | Hive box encrypted with key from `flutter_secure_storage` |
| **Session timeout** | Biometric re-auth after 5 minutes of inactivity (configurable) |

---

## 8. Penetration Test Plan

### 8.1 Scope

| Target | Version | Methodology |
|---|---|---|
| Laravel API (all endpoints listed in PRD §6) | MVP | OWASP Web Security Testing Guide v4.2 |
| Vue 3 SPA (dashboard, kanban, reports) | MVP | OWASP WSTG + DOM-based testing |
| Flutter mobile app (Android APK, iOS IPA) | MVP | OWASP Mobile Security Testing Guide |
| WebSocket (Laravel Reverb) | MVP | Custom WS fuzzing |
| File upload endpoint | MVP | OWASP File Upload Testing |

### 8.2 Test Cases

#### TC-01: Authentication Bypass via Token Manipulation
**Objective:** Determine if an attacker can forge or reuse another user's Sanctum token.
**Method:**
1. Intercept valid Sanctum token via proxy (Burp Suite)
2. Modify token payload in Authorization header
3. Attempt to access another user's resources
4. Submit expired/revoked token to test acceptance window
**Expected:** All tampered and reused tokens rejected with 401. Revoked token acceptance window = 0 seconds.
**Severity if fails:** Critical

#### TC-02: IDOR — Cross-Team Resource Access
**Objective:** Verify task, project, and time-entry endpoints enforce team boundaries.
**Method:**
1. Authenticate as User A (Team 1)
2. For each resource (task, project, time-entry, comment), fetch a known resource UUID from Team 2
3. Try GET, PATCH, DELETE operations
4. Repeat with Viewer, Member, Admin roles
**Expected:** All cross-team operations return 403. Error message does not reveal existence of resource.
**Severity if fails:** Critical

#### TC-03: Role Escalation via Direct API Call
**Objective:** Verify that a Member cannot escalate own role or perform admin actions.
**Method:**
1. Authenticate as Member role user
2. Send PATCH `/api/teams/{id}/members/{self_id}` with `{role: 'admin'}`
3. Attempt to invite new members, delete team, modify billing
4. Test if role change is validated server-side (not trusting client)
**Expected:** All escalation attempts return 403. Role stored in DB validated, not from request.
**Severity if fails:** Critical

#### TC-04: SQL Injection in Report Endpoint
**Objective:** Identify injection vectors in report query parameters.
**Method:**
1. Send GET `/api/reports/time?from=2024-01-01' OR '1'='1` — test string escape
2. Send GET `/api/reports/time?team_id=1; DROP TABLE tasks;--` — test query chaining
3. Send POST with crafted body containing SQL functions in `note` field
4. Send ORDER BY clause injection via `?sort=title; DESC`
**Expected:** All injection attempts return 422 validation error or safe escaped values. Raw queries use parameterized bindings only.
**Severity if fails:** Critical

#### TC-05: Rate Limit Bypass
**Objective:** Confirm rate limits are enforced and cannot be bypassed via header manipulation.
**Method:**
1. Send 70 rapid requests to POST `/api/auth/login` — verify 429 after 60
2. Send 310 requests to GET `/api/me` — verify 429 after 300
3. Add `X-Forwarded-For` header to spoof IP — verify rate limit key uses real IP (trusted proxy list configured)
4. Test concurrent (parallel) requests to check race condition on counter
**Expected:** 429 returned after limit exceeded. X-Forwarded-For spoofing detected.
**Severity if fails:** High

#### TC-06: File Upload — Malicious Content Bypass
**Objective:** Test all file upload validation barriers.
**Method:**
1. Upload `.php` file renamed to `.jpg` with `Content-Type: image/jpeg` — test extension + MIME validation
2. Upload SVG with embedded `<script>` tag — test XSS sanitization
3. Upload file >10MB — test `client_max_body_size` + application check
4. Upload file with null byte in name (`shell.php%00.jpg`) — test truncation
5. Upload zip bomb (10GB expansion) — test extraction blocking
6. Upload file to non-avatar path with path traversal (`../../../etc/passwd`) — test path sanitization
**Expected:** All malicious uploads rejected. File stored with UUID name outside webroot.
**Severity if fails:** High

#### TC-07: WebSocket Channel Authorization Bypass
**Objective:** Verify private channel auth prevents cross-team subscription.
**Method:**
1. As User A (Team 1), connect to Reverb via WSS
2. Subscribe to private channel `private-task.{Team2TaskId}` using forged channel name
3. Send crafted subscription request with User A's token
4. Test if channel auth callback validates team membership
**Expected:** Private channel subscription returns 403 for non-member. Channel names contain no guessable identifiers.
**Severity if fails:** High

#### TC-08: Mass Assignment via PATCH Endpoints
**Objective:** Identify unprotected fields that allow privilege escalation or data corruption.
**Method:**
1. For each PATCH endpoint, send extra fields not in the allowed request:
   - `PATCH /api/me` with `{role: 'admin', team_id: 999}`
   - `PATCH /api/projects/{id}` with `{team_id: 999}`
   - `PATCH /api/tasks/{id}` with `{project_id: 999}`
2. Test `$fillable` protection on all models by sending `_token`, `remember_token`, `password`, `email_verified_at`
**Expected:** Extra fields silently ignored. Models use `$fillable` whitelist. No field accessible outside defined list.
**Severity if fails:** High

#### TC-09: Sensitive Data Exposure in API Responses
**Objective:** Detect PII leakage in API payloads, error messages, and debug endpoints.
**Method:**
1. Examine all API responses for:
   - User `password` or `remember_token` fields (even if null)
   - Full credit card numbers in subscription responses
   - Database column names in error messages
   - Stack traces or file paths
2. Send malformed JSON to trigger validation errors — inspect response body
3. Access `/storage/debug.log`, `/storage/logs/laravel.log`, `/.env` directly
4. Check report exports (PDF/CSV) for internal IDs, hidden fields
**Expected:** No sensitive fields in responses. Generic error messages. `APP_DEBUG=false`. Direct log access blocked by Nginx.
**Severity if fails:** High

#### TC-10: Mobile — Token Extraction from Device Storage
**Objective:** Test the security of mobile token storage against physical device access.
**Method:**
1. On rooted Android: dump app data via `adb backup` or `/data/data/com.tasksyncpro/` extraction
2. On jailbroken iOS: dump Keychain via `keychain_dumper`
3. Check `shared_preferences` XML for token (should be absent — only in secure storage)
4. Restore device backup to another device — verify token scoped to fingerprint
**Expected:** Token stored exclusively in `flutter_secure_storage`. Device backup does not contain token. Token replayed from different device rejected by server via device fingerprint mismatch.
**Severity if fails:** Critical

### 8.3 Tooling & Approach

| Phase | Tool | Target | Duration |
|---|---|---|---|
| **Reconnaissance** | Subfinder, Amass, Nmap | Public endpoints, subdomains, open ports | 1 day |
| **Automated scan** | OWASP ZAP (CI-integrated) | All API endpoints + SPA | 1 day |
| **Manual testing** | Burp Suite Professional | Critical paths: auth, IDOR, file upload | 2 days |
| **Mobile testing** | MobSF + Objection | Flutter APK/IPA binary analysis | 1 day |
| **WebSocket testing** | wscat + custom script | Reverb auth + channel enumeration | 0.5 day |
| **Report generation** | — | Findings, severity, remediation timeline | 0.5 day |

**CI Integration:**

```yaml
# .github/workflows/security-scan.yml
name: Security Scan
on: [pull_request]
jobs:
  zap-scan:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Start application
        run: docker compose up -d --wait
      - name: Run ZAP Full Scan
        uses: zaproxy/action-full-scan@v0.10.0
        with:
          target: 'http://localhost:8000'
          cmd_options: '-a -j'
          allow_issue_writing: false
      - name: Upload ZAP Report
        uses: actions/upload-artifact@v4
        with:
          name: zap-report
          path: report.html

  trivy-scan:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Run Trivy vulnerability scanner
        uses: aquasecurity/trivy-action@master
        with:
          scan-type: 'fs'
          scan-ref: '.'
          format: 'sarif'
          output: 'trivy-results.sarif'

  gitleaks-scan:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Secret scanning with Gitleaks
        uses: gitleaks/gitleaks-action@v2
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
```

### 8.4 Remediation SLAs

| Severity | Response Time | Fix Deadline | Notification |
|---|---|---|---|
| **Critical** | < 1 hour | 24 hours | PagerDuty + Slack to on-call engineer |
| **High** | < 4 hours | 72 hours | Slack to engineering lead |
| **Medium** | < 24 hours | 2 weeks | GitHub issue with security label |
| **Low** | < 1 week | Next sprint | Backlog |

---

## 9. Security Checklist (Pre-Launch)

- [ ] `APP_DEBUG=false` in production environment
- [ ] All models use `$fillable` or `$guarded` (no mass assignment)
- [ ] All controllers use `FormRequest` for input validation
- [ ] All Policy classes registered in `AuthServiceProvider`
- [ ] Rate limiters defined and applied to route groups
- [ ] CORS origins restricted to production domain
- [ ] Sanctum stateful domains configured for SPA
- [ ] Redis `requirepass` set and bound to localhost
- [ ] PostgreSQL bound to `127.0.0.1` only
- [ ] Nginx `autoindex off`; directory listing disabled
- [ ] HTTPS enforced (Cloudflare Full Strict + HSTS)
- [ ] Content-Security-Policy header configured
- [ ] ClamAV file scanning active on upload
- [ ] Upload size limited (Nginx `client_max_body_size` + app validation)
- [ ] `ActivityLog` table created with append-only trigger
- [ ] Email verification required before write operations
- [ ] Password policy enforced (min 8 chars, mixed case + number)
- [ ] Rate limit on auth: 60/min; account lockout: 5 attempts → 15 min
- [ ] Logging: no PII or sensitive fields written to logs
- [ ] QR/2FA backup codes generated (post-MVP)
- [ ] Mobile: `flutter_secure_storage` used for tokens; shared_preferences not used for secrets
- [ ] Mobile: certificate pinning configured for release build
- [ ] Mobile: ProGuard/R8 enabled; Flutter obfuscation active
- [ ] CI: `composer audit`, `npm audit`, ZAP scan, Gitleaks, Trivy all passing
- [ ] PDPL: privacy policy published, consent checkbox active, data residency confirmed
- [ ] Penetration test completed with no critical/high findings

---

*Generated by Security & Compliance Architect · Gate 3 · 2026-06-25*
*Consumed: docs/PRD.md, docs/ARCHITECTURE.md, docs/JOURNEY_MAP.md*
*Next: TKT-005 ← performance-architect · TKT-006 ← data-schema-engineer · TKT-008 ← api-integration-specialist*
