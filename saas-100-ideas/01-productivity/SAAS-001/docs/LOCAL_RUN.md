# LOCAL RUN — TaskSync Pro (SAAS-001)

> How to boot the full stack on localhost (sqlite, zero infra). Verified 2026-06-25.

## Prerequisites
- PHP 8.3+ (tested 8.5), Composer 2.x
- Node 20+ (tested 24), npm
- No PostgreSQL/Redis needed for local dev — sqlite + database/file drivers.

## Backend (Laravel API) — port 8000

```bash
cd src/backend

# deps (already vendored; run if vendor/ missing)
composer install

# env: .env already present with APP_KEY + DB_CONNECTION=sqlite

# fresh database + demo data
touch database/database.sqlite
php artisan migrate:fresh --seed

# serve on the port the frontend proxy expects
php artisan serve --host=127.0.0.1 --port=8000
```

API base: `http://127.0.0.1:8000/api/v1`
Health: `GET /api/v1/health` → `{"status":"ok"}`

### Demo logins (password = `password`)
| Email | Role |
|-------|------|
| sara@example.com  | owner |
| layla@example.com | admin |
| ahmed@example.com | member |

### Smoke-test (curl)
```bash
curl -s -X POST http://127.0.0.1:8000/api/v1/auth/login \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"email":"sara@example.com","password":"password"}'
# → returns data.token + data.user.current_workspace_id

# authenticated calls need:  Authorization: Bearer <token>
# workspace-scoped endpoints also need ?workspace_id=<current_workspace_id>
```

## Frontend (Vue 3 dashboard) — port 5173

```bash
cd src/frontend
npm install            # node_modules already present
npm run dev            # Vite dev server on :5173
```

- `vite.config.js` proxies `/api`, `/storage`, `/broadcasting` → `http://localhost:8000`,
  so the backend MUST run on **8000** for dev. No frontend `.env` required.
- To point at a different API, set `VITE_API_URL` (consumed in `src/services/api.js`).
- Production build: `npm run build` → `dist/`.

## What is NOT wired locally (by design)
- **WebSockets (Reverb):** `BROADCAST_CONNECTION=log` in `.env` — real-time events are
  logged, not pushed. Start Reverb + set `BROADCAST_CONNECTION=reverb` for live updates.
- **Queues/Horizon:** `QUEUE_CONNECTION=database` (sync-ish). Run `php artisan queue:work`
  if you want async jobs (webhooks, notifications) processed.
- **Mail:** `MAIL_MAILER=log` — emails written to `storage/logs/laravel.log`.
- **Full-text search:** PostgreSQL GIN/tsvector indexes are pgsql-only; sqlite falls back
  to ILIKE (migrations guard the raw SQL by driver).

## Prod parity
For PostgreSQL 16 + Redis + Reverb (matches `docker-compose.prod.yml`), switch `.env`
`DB_CONNECTION=pgsql`, `CACHE_STORE=redis`, `BROADCAST_CONNECTION=reverb` and run the
compose stack in `deploy/`.
