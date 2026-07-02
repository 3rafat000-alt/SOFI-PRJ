# PERFORMANCE REPORT — TaskSync Pro (SAAS-001)

> **Gate:** 5 · **Owner:** Performance & Load Analyst (Ahmed Farouk) · **Date:** 2026-06-25
> **Consumes:** ARCHITECTURE.md §8, API.md, SCHEMA.md, src/backend/*
> **Handoff to:** sofi-qa-sre-lead (TKT-013) · sofi-sql-dba-expert
> **Status:** ⚪ Planned (execution pending CI pipeline)

---

## Table of Contents

1. [Performance Budget Validation](#1-performance-budget-validation)
2. [Load Test Plan (k6)](#2-load-test-plan-k6)
3. [Database Query Optimization](#3-database-query-optimization)
4. [Caching Strategy](#4-caching-strategy)
5. [Frontend Performance](#5-frontend-performance)
6. [Mobile Performance](#6-mobile-performance)
7. [CDN & Asset Delivery](#7-cdn--asset-delivery)
8. [Load Test Results Template](#8-load-test-results-template)
9. [Breach Flag Summary](#9-breach-flag-summary)

---

## 1. Performance Budget Validation

### 1.1 API Response Time Targets

| Metric | Target | Violation If | Measurement Tool |
|--------|--------|-------------|------------------|
| **P50** | <80ms | ≥100ms | Laravel Telescope, k6 |
| **P95** | <150ms | ≥150ms | k6, New Relic |
| **P99** | <200ms | ≥200ms | k6, New Relic |
| **Error rate** | <1% | ≥1% | k6 thresholds |
| **Rate-limited responses** | 0% | Any 429s | k6 checks |

**Critical path ranking (fastest → slowest expected):**

| Endpoint | Expected P99 | Risk |
|----------|-------------|------|
| `GET /api/v1/tasks` (simple filter) | <120ms | 🟢 Low — composite index covers |
| `GET /api/v1/projects/{id}/tasks` | <150ms | 🟢 Low — index scan |
| `POST /api/v1/time-entries/start` | <100ms | 🟢 Low — simple INSERT |
| `PUT /api/v1/tasks/reorder` | <250ms | 🟡 Medium — transaction + broadcast |
| `GET /api/v1/time-entries/report` | <500ms | 🔴 High — aggregation query |
| `POST /api/v1/tasks` | <200ms | 🟡 Medium — validation + event dispatch |

### 1.2 Frontend Performance Targets

| Metric | Target | Violation If | Tool |
|--------|--------|-------------|------|
| **First Contentful Paint (FCP)** | <1.5s | ≥1.5s | Lighthouse CI |
| **Largest Contentful Paint (LCP)** | <2.5s | ≥2.5s | Lighthouse CI |
| **Time to Interactive (TTI)** | <3.0s | ≥3.0s | Lighthouse CI |
| **Cumulative Layout Shift (CLS)** | <0.1 | ≥0.1 | Lighthouse CI |
| **First Input Delay / INP** | <200ms | ≥200ms | Lighthouse CI |

### 1.3 Lighthouse Score Targets

| Category | Target | Violation If | CI Action |
|----------|--------|-------------|-----------|
| **Performance** | >90 | ≤90 | ❌ Fail CI build |
| **Accessibility** | >95 | ≤95 | ❌ Fail CI build (WCAG 2.2 AA mandatory) |
| **Best Practices** | >95 | ≤95 | ⚠️ Warning |
| **SEO** | >95 | ≤95 | ⚠️ Warning |

### 1.4 Mobile Performance Targets

| Metric | Target | Violation If | Tool |
|--------|--------|-------------|------|
| **App launch (cold start)** | <2s | ≥2s | Firebase Performance |
| **UI frame rate** | 60fps (no jank) | Dropped frames >5% | Flutter DevTools |
| **Timer start latency** | <100ms | ≥300ms | Custom trace |
| **Task list scroll** | 60fps | Jank on 500 items | Flutter DevTools |
| **Offline sync on reconnect** | <5s | ≥10s | Integration test |

---

## 2. Load Test Plan (k6)

### 2.1 Common Configuration

```javascript
// shared/options.js
export const baseOptions = {
  stages: [
    { duration: '30s', target: 50 },   // ramp up
    { duration: '2m', target: 100 },    // sustain
    { duration: '30s', target: 0 },     // ramp down
  ],
  thresholds: {
    http_req_duration: ['p(95)<500', 'p(99)<2000'],
    http_req_failed: ['rate<0.01'],
  },
  ext: {
    loadimpact: {
      project: { name: 'SAAS-001 TaskSync Pro' },
      name: 'baseline',
    },
  },
};
```

### 2.2 Script: GET /api/v1/tasks (with filters)

**Purpose:** Test task listing with query parameters — the most-hit read endpoint.

```javascript
// scenarios/task-list.js
import http from 'k6/http';
import { check, sleep } from 'k6';
import { randomItem } from 'k6/crypto';

const BASE_URL = __ENV.API_BASE_URL || 'http://localhost:8000/api/v1';
const TOKEN = __ENV.AUTH_TOKEN;

const statusFilters = ['todo', 'in_progress', 'done'];
const priorityFilters = ['urgent', 'high', 'medium', 'low'];

export default function () {
  const params = {
    headers: {
      'Authorization': `Bearer ${TOKEN}`,
      'Accept': 'application/json',
      'Accept-Language': randomItem(['ar', 'en']),
    },
  };

  const status = randomItem(statusFilters);
  const priority = randomItem(priorityFilters);
  const url = `${BASE_URL}/tasks?workspace_id=${__ENV.WORKSPACE_ID}&status=${status}&priority=${priority}&per_page=50`;

  const res = http.get(url, params);

  check(res, {
    'status 200': (r) => r.status === 200,
    'response time < 500ms': (r) => r.timings.duration < 500,
    'has data array': (r) => JSON.parse(r.body).data !== undefined,
  });

  sleep(randomItem([0.5, 1, 1.5, 2]));
}
```

### 2.3 Script: POST /api/v1/tasks (concurrent creation)

**Purpose:** Test write throughput — create tasks with concurrent users.

```javascript
// scenarios/task-create.js
import http from 'k6/http';
import { check, sleep } from 'k6';
import { randomItem } from 'k6/crypto';

const BASE_URL = __ENV.API_BASE_URL || 'http://localhost:8000/api/v1';
const TOKEN = __ENV.AUTH_TOKEN;

const titles = [
  'تصميم الواجهة الرئيسية',
  'تطوير API الإشعارات',
  'اختبار نظام التوقيت',
  'مراجعة الكود',
  'كتابة التوثيق',
  'إصلاح خلل السحب والإفلات',
];

const priorities = ['low', 'medium', 'high', 'urgent'];

export default function () {
  const params = {
    headers: {
      'Authorization': `Bearer ${TOKEN}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Idempotency-Key': `${__VU}-${__ITER}`,
    },
  };

  const payload = JSON.stringify({
    project_id: __ENV.PROJECT_ID,
    title: randomItem(titles),
    description: 'اختبار تحميل - تم الإنشاء تلقائياً',
    priority: randomItem(priorities),
    assignee_id: __ENV.ASSIGNEE_ID,
  });

  const res = http.post(`${BASE_URL}/tasks`, payload, params);

  check(res, {
    'status 201 created': (r) => r.status === 201,
    'response time < 500ms': (r) => r.timings.duration < 500,
    'has task id': (r) => JSON.parse(r.body).data.id !== undefined,
  });

  sleep(1);
}
```

### 2.4 Script: Kanban Reorder (PUT with position updates)

**Purpose:** Test bulk position update with transaction + WebSocket broadcast.

```javascript
// scenarios/kanban-reorder.js
import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = __ENV.API_BASE_URL || 'http://localhost:8000/api/v1';
const TOKEN = __ENV.AUTH_TOKEN;

export default function () {
  const params = {
    headers: {
      'Authorization': `Bearer ${TOKEN}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Idempotency-Key': `${__VU}-${__ITER}`,
    },
  };

  // Reorder 4 tasks across columns
  const payload = JSON.stringify({
    project_id: __ENV.PROJECT_ID,
    orders: [
      { id: __ENV.TASK_ID_1, status: 'todo', position: 1 },
      { id: __ENV.TASK_ID_2, status: 'in_progress', position: 1 },
      { id: __ENV.TASK_ID_3, status: 'in_progress', position: 2 },
      { id: __ENV.TASK_ID_4, status: 'done', position: 1 },
    ],
  });

  const res = http.put(`${BASE_URL}/tasks/reorder`, payload, params);

  check(res, {
    'status 200': (r) => r.status === 200,
    'response time < 800ms': (r) => r.timings.duration < 800,
    'reorder count matches': (r) => JSON.parse(r.body).data.reordered_count === 4,
  });

  sleep(2); // Kanban is less frequent than list/create
}
```

### 2.5 Script: Timer Start/Stop (high-frequency)

**Purpose:** Test the most latency-sensitive path — timer start/stop is per-user, high frequency.

```javascript
// scenarios/timer-toggle.js
import http from 'k6/http';
import { check, sleep } from 'k6';
import { randomItem } from 'k6/crypto';

const BASE_URL = __ENV.API_BASE_URL || 'http://localhost:8000/api/v1';
const TOKEN = __ENV.AUTH_TOKEN;

const taskIds = __ENV.TASK_IDS.split(',');

export default function () {
  const params = {
    headers: {
      'Authorization': `Bearer ${TOKEN}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  };

  // Phase 1: Start timer
  const startPayload = JSON.stringify({
    task_id: randomItem(taskIds),
    note: 'بدء تتبع الوقت - اختبار أداء',
  });

  let res = http.post(`${BASE_URL}/time-entries/start`, startPayload, params);

  check(res, {
    'timer start 201': (r) => r.status === 201,
    'timer start < 300ms': (r) => r.timings.duration < 300,
    'is running': (r) => JSON.parse(r.body).data.is_running === true,
  });

  // Simulate work: 3-7 seconds
  sleep(Math.random() * 4 + 3);

  // Phase 2: Stop timer
  const stopPayload = JSON.stringify({
    note: 'انتهاء تتبع الوقت - اختبار أداء',
  });

  res = http.post(`${BASE_URL}/time-entries/stop`, stopPayload, params);

  check(res, {
    'timer stop 200': (r) => r.status === 200,
    'timer stop < 300ms': (r) => r.timings.duration < 300,
    'is not running': (r) => JSON.parse(r.body).data.is_running === false,
    'has duration': (r) => JSON.parse(r.body).data.duration_minutes !== null,
  });

  sleep(2);
}
```

### 2.6 Load Test Matrix

| Script | Concurrent Users | Ramp Up | Sustain | P95 Target | Error Budget |
|--------|-----------------|---------|---------|-----------|-------------|
| `task-list` | 100 | 30s | 2min | <500ms | <1% |
| `task-create` | 50 | 30s | 2min | <500ms | <1% |
| `kanban-reorder` | 30 | 30s | 2min | <800ms | <1% |
| `timer-toggle` | 60 | 30s | 2min | <300ms | <1% |

### 2.7 Running the Tests

```bash
# Install k6
# brew install k6  # macOS
# apt install k6   # Linux

# Export environment
export API_BASE_URL="http://localhost:8000/api/v1"
export AUTH_TOKEN="1|abc123..."
export WORKSPACE_ID="w_..."
export PROJECT_ID="p_..."

# Run individual scenarios
k6 run scenarios/task-list.js --vus 100 --duration 3m
k6 run scenarios/task-create.js --vus 50 --duration 3m
k6 run scenarios/kanban-reorder.js --vus 30 --duration 3m
k6 run scenarios/timer-toggle.js --vus 60 --duration 3m

# Run all with combined report
k6 run --compatibility-mode=extended \
  -e API_BASE_URL=$API_BASE_URL \
  -e AUTH_TOKEN=$AUTH_TOKEN \
  -e WORKSPACE_ID=$WORKSPACE_ID \
  -e PROJECT_ID=$PROJECT_ID \
  scenarios/task-list.js
```

---

## 3. Database Query Optimization

### 3.1 N+1 Query Analysis

Identified eager-loading candidates from API serialization patterns:

| Endpoint | Current Risk | Eager Load Fix |
|----------|-------------|----------------|
| `GET /api/v1/tasks` (list) | `assignee` + `creator` + `tags` per task row | `->with(['assignee', 'creator', 'tags'])` |
| `GET /api/v1/projects` (list) | `taskCount` via N subqueries | `->withCount('tasks')` or cached counter |
| `GET /api/v1/tasks/{id}` (detail) | `comments.user` + `attachments` + `timeEntries` | `->with(['comments.user', 'attachments', 'timeEntries'])` |
| `GET /api/v1/dashboard/stats` | 5+ separate COUNT queries | Single aggregation query with `COUNT(CASE...)` |
| `GET /api/v1/workspaces/{id}/members` | `taskCount` per member | `->withCount(['tasks' => fn($q) => $q->where(...)])` |

### 3.2 Index Validation for Top 10 Hot Queries

| # | Query Pattern | Index | Status |
|---|--------------|-------|--------|
| 1 | `SELECT * FROM tasks WHERE project_id = ? AND status = ? ORDER BY position` | `idx_tasks_project_status_position` (project_id, status, position) | ✅ Covered |
| 2 | `SELECT * FROM tasks WHERE assignee_id = ? AND status IN (?,?)` | `idx_task_assignees_user_id` (user_id) — partial: need status filter | ⚠️ Missing composite — see recommendation |
| 3 | `SELECT * FROM time_entries WHERE user_id = ? AND started_at BETWEEN ? AND ? ORDER BY started_at DESC` | `idx_time_entries_user_started` (user_id, started_at DESC) | ✅ Covered |
| 4 | `SELECT * FROM comments WHERE task_id = ? ORDER BY created_at DESC` | `idx_comments_task_created` (task_id, created_at DESC) | ✅ Covered |
| 5 | `SELECT * FROM notifications WHERE user_id = ? AND read_at IS NULL` | `idx_notifications_user_read` (user_id, read_at) | ✅ Covered |
| 6 | `SELECT COUNT(*) FROM tasks WHERE project_id = ? GROUP BY status` | `idx_tasks_project_status_position` (project_id, status, position) — index-only scan | ✅ Covered |
| 7 | `SELECT * FROM activity_logs WHERE subject_type = ? AND subject_id = ? ORDER BY created_at DESC` | `idx_activity_logs_subject` (subject_type, subject_id) | ✅ Covered |
| 8 | `SELECT * FROM tasks WHERE due_date BETWEEN ? AND ?` | `idx_tasks_due_date` (due_date) WHERE due_date IS NOT NULL | ✅ Covered (partial) |
| 9 | `SELECT * FROM tasks WHERE to_tsvector('arabic', title || ' ' || COALESCE(description, '')) @@ plainto_tsquery('arabic', ?)` | `idx_tasks_search` GIN tsvector | ✅ Covered |
| 10 | `SELECT SUM(duration_minutes) FROM time_entries WHERE task_id = ? AND user_id = ?` | `idx_time_entries_task_user` (task_id, user_id) | ✅ Covered |

### 3.3 Missing Index Recommendations

```sql
-- RECOMMENDED: Composite index for "My Tasks" filtered by status
-- Current: idx_task_assignees_user_id covers user_id only
-- Problem: status filter causes sequential scan after user_id join
CREATE INDEX idx_tasks_assignee_status ON tasks (assignee_id, status)
WHERE assignee_id IS NOT NULL;
-- Covers: WHERE assignee_id = ? AND status IN ('todo', 'in_progress')

-- RECOMMENDED: Composite index for dashboard aggregation
-- Dashboard queries COUNT tasks grouped by status per workspace
CREATE INDEX idx_tasks_project_status ON tasks (project_id, status);
-- Supports: WHERE project_id IN (SELECT id FROM projects WHERE workspace_id = ?)
-- Without this, PG seq scans project→tasks join

-- RECOMMENDED: Covering index for time report pagination
-- Reduces heap lookups for frequently accessed columns
CREATE INDEX idx_time_entries_report_covering
  ON time_entries (user_id, started_at DESC, duration_minutes, task_id)
  INCLUDE (notes);
-- Supports index-only scans for report queries
```

### 3.4 Query Plan Analysis Targets

```sql
-- Verify these provide index-only scans:
EXPLAIN (ANALYZE, BUFFERS) 
SELECT project_id, status, COUNT(*) 
FROM tasks 
WHERE project_id = 'p_01h3xz...' 
GROUP BY status;

-- Verify sorted output uses index:
EXPLAIN (ANALYZE, BUFFERS)
SELECT * FROM tasks 
WHERE project_id = 'p_01h3xz...' AND status = 'in_progress'
ORDER BY position;

-- Check for seq scans on hot queries:
EXPLAIN (ANALYZE, BUFFERS)
SELECT * FROM time_entries 
WHERE user_id = 'u_01h3xz...' 
  AND started_at >= '2026-07-01' 
  AND started_at < '2026-07-31'
ORDER BY started_at DESC;
```

**Key PostgreSQL config (from ARCHITECTURE.md §7):**

| Setting | Value | Rationale |
|---------|-------|-----------|
| `shared_buffers` | 1GB | 25% of 4GB RAM |
| `effective_cache_size` | 3GB | OS cache estimate |
| `work_mem` | 64MB | Per-operation sort/hash |
| `maintenance_work_mem` | 256MB | For VACUUM/INDEX rebuild |
| `random_page_cost` | 1.1 | NVMe SSD — lower than default 4.0 |
| `effective_io_concurrency` | 200 | NVMe SSD parallelism |

---

## 4. Caching Strategy

### 4.1 Redis Cache Key Schema

| Cache Key Pattern | TTL | Data | Size Estimate |
|-------------------|-----|------|--------------|
| `task:project:{project_id}` | 60s | Serialized task collection | ~5KB per project |
| `task:project:{project_id}:status:{status}` | 60s | Tasks filtered by column | ~2KB per column |
| `dashboard:stats:{workspace_id}` | 120s | Aggregated dashboard counts | ~500B |
| `team:{workspace_id}:members` | 300s | Member list with roles | ~2KB per team |
| `report:time:{md5(query_params)}` | 600s | Time report aggregation | ~10KB per report |
| `user:{user_id}:profile` | 600s | User profile + settings | ~500B |
| `project:{project_id}:detail` | 120s | Project with task counts | ~1KB |

### 4.2 Cache Invalidation Rules

| Event | Cache Key to Invalidate | Mechanism |
|-------|------------------------|-----------|
| `TaskCreated` | `task:project:{project_id}:*`, `dashboard:stats:{ws_id}` | Event listener |
| `TaskMoved` | `task:project:{project_id}:*`, `dashboard:stats:{ws_id}` | Event listener |
| `TaskDeleted` | `task:project:{project_id}:*`, `dashboard:stats:{ws_id}` | Event listener |
| `TaskUpdated` (assignee change) | `task:project:{project_id}:*` | Event listener |
| `TimerStopped` | `report:time:*` (wildcard — all time reports) | Event listener |
| `MemberJoined` | `team:{workspace_id}:members` | Event listener |
| `MemberRemoved` | `team:{workspace_id}:members` | Event listener |
| `ProfileUpdated` | `user:{user_id}:profile` | Direct call in service |
| `ProjectCreated/Deleted` | `dashboard:stats:{ws_id}` | Event listener |

### 4.3 Implementation Pattern (Laravel)

```php
// In TaskService::create() — cache tags pattern
use Illuminate\Support\Facades\Cache;

public function create(array $data, User $user): Task
{
    return DB::transaction(function () use ($data, $user) {
        $task = $this->tasks->create(...);

        // Invalidate project task cache
        Cache::forget("task:project:{$task->project_id}");
        Cache::forget("dashboard:stats:{$user->current_workspace_id}");

        // If using Redis cache tags:
        Cache::tags(["project:{$task->project_id}", "workspace:{$user->current_workspace_id}"])
            ->flush();

        $this->bus->dispatch(new TaskCreated($task));
        return $task;
    });
}

// In ReportService — cache the heavy aggregation
public function timeReport(array $filters): array
{
    $cacheKey = 'report:time:' . md5(json_encode($filters));

    return Cache::remember($cacheKey, 600, function () use ($filters) {
        return $this->reportRepository->aggregateTimeEntries($filters);
    });
}
```

### 4.4 Cache Tag Strategy (Redis)

```php
// config/cache.php
'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
        // Tags enable grouped invalidation
        'tags' => 'redis',
    ],
],

// Usage:
Cache::tags(['project:' . $projectId])->remember('tasks', 60, function () {
    return Task::where('project_id', $projectId)->get();
});

// Invalidate entire project cache:
Cache::tags(['project:' . $projectId])->flush();
```

**Cache tag groups:**
- `project:{id}` — all task data for a project
- `workspace:{id}` — dashboard stats, member lists
- `user:{id}` — profile, preferences
- `report:{workspace_id}` — all report aggregations

### 4.5 TTL Rationale

| TTL | Rationale |
|-----|-----------|
| **60s** (tasks) | Kanban reorder is frequent; staleness visible. Short TTL = real-time feel |
| **120s** (dashboard) | Dashboard stats don't need second-level freshness |
| **300s** (members) | Members rarely change; 5min acceptable |
| **600s** (reports) | Reports are historical; 10min cache is fine |
| **600s** (profiles) | Profiles edited rarely; 10min cache |
| **1 year** (assets) | Vite content-hashed; cache forever, URL changes on rebuild |

---

## 5. Frontend Performance

### 5.1 Code Splitting Recommendations

| Route | Component | Split Strategy | Priority |
|-------|-----------|----------------|----------|
| `/dashboard` | `DashboardView.vue` | Eager (landing page) | P0 |
| `/projects/:id/board` | `KanbanView.vue` | Lazy `() => import(...)` | P0 |
| `/projects/:id/timeline` | `TimelineView.vue` | Lazy — heavy Gantt lib | P1 |
| `/reports/time` | `ReportsView.vue` | Lazy — chart libraries | P1 |
| `/settings/team` | `TeamSettingsView.vue` | Lazy | P2 |
| `/settings/profile` | `ProfileView.vue` | Lazy | P2 |
| `/settings/billing` | `BillingView.vue` | Lazy | P2 |

```javascript
// router/index.js — lazy load example
const routes = [
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('@/views/DashboardView.vue'),
  },
  {
    path: '/projects/:id/board',
    name: 'kanban',
    component: () => import(/* webpackChunkName: "kanban" */ '@/views/KanbanView.vue'),
  },
  {
    path: '/reports/time',
    name: 'reports',
    component: () => import(/* webpackChunkName: "reports" */ '@/views/ReportsView.vue'),
  },
];
```

### 5.2 Bundle Size Targets

| Asset | Target (gzipped) | Current Estimate | Tool |
|-------|-----------------|------------------|------|
| **Vendor JS** (Vue+VueRouter+Pinia+axios) | <200KB | ~180KB | `vite-bundle-visualizer` |
| **App JS** (all lazy-loaded) | <150KB | ~120KB | Vite build report |
| **Total JS** (initial load) | <350KB | ~300KB | Vite build report |
| **CSS** (Tailwind purged) | <30KB | ~20KB | Tailwind analyzer |
| **Font** (Arabic webfont) | <40KB | ~35KB (subset) | Google Fonts |

### 5.3 Image Optimization

| Technique | Implementation | Impact |
|-----------|---------------|--------|
| **WebP/AVIF** | Vite `@squoosh/ lib` or `<picture>` with `image/webp` | -40% size vs PNG |
| **Lazy loading** | `loading="lazy"` on all `<img>` below fold | -60% initial payload |
| **Responsive sizes** | `srcset` with breakpoints (320w, 640w, 1024w) | -50% bandwidth on mobile |
| **Avatar thumbnails** | S3 presigned with `?w=48&h=48` param | -80% avatar size |
| **Inline SVGs** | Inline for icons under 2KB | -4 HTTP requests |

```html
<!-- Example: responsive avatar with WebP fallback -->
<picture>
  <source srcset="https://storage.tasksyncpro.com/avatars/u_01h3xz.webp?w=48&h=48"
          type="image/webp"
          media="(max-width: 640px)">
  <source srcset="https://storage.tasksyncpro.com/avatars/u_01h3xz.webp?w=96&h=96"
          type="image/webp">
  <img src="https://storage.tasksyncpro.com/avatars/u_01h3xz.jpg?w=96&h=96"
       alt="صورة المستخدم"
       loading="lazy"
       width="96"
       height="96"
       class="rounded-full">
</picture>
```

### 5.4 Tailwind Purge Configuration

```javascript
// tailwind.config.js
export default {
  content: [
    './index.html',
    './src/**/*.{vue,js,ts,jsx,tsx}',
    './src/**/*.vue',
  ],
  safelist: [
    // Dynamic color classes from tag/project colors
    'bg-red-500', 'bg-blue-500', 'bg-green-500',
    'bg-yellow-500', 'bg-purple-500', 'bg-pink-500',
    'text-red-500', 'text-blue-500', 'text-green-500',
    'border-red-500', 'border-blue-500',
  ],
  theme: {
    extend: {
      fontFamily: {
        arabic: ['Cairo', 'system-ui', 'sans-serif'],
      },
    },
  },
  plugins: [],
};
```

### 5.5 Critical CSS

Extract and inline critical CSS for above-the-fold content to eliminate render-blocking:

```bash
# Using critters (Vite plugin)
npm install -D critters

# vite.config.js
import { critters } from 'critters/vite';
export default {
  plugins: [
    vue(),
    critters({
      // Inline critical CSS for first paint
      preload: 'js-lazy',          // Preload lazy JS
      preloadFonts: true,          // Preload fonts
      compress: true,              // Minify inlined CSS
    }),
  ],
};
```

---

## 6. Mobile Performance

### 6.1 Widget Rebuild Optimization

| Pattern | Implementation | Impact |
|---------|---------------|--------|
| **const constructors** | All widgets use `const` where possible | Fewer rebuilds |
| **RepaintBoundary** | Wrap `KanbanColumn`, `TaskListItem`, `TimerWidget` | Isolated repaints |
| **Selector** (Bloc) | Use `BlocSelector` instead of `BlocBuilder` for deep state | Widget rebuilds only on relevant state change |
| **const child** | Extract constant subtrees | Avoids subtree rebuild |

```dart
// Example: TaskListItem with RepaintBoundary
class TaskListItem extends StatelessWidget {
  const TaskListItem({super.key, required this.task, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return RepaintBoundary(
      child: ListTile(
        title: Text(task.title, maxLines: 2),
        trailing: PriorityBadge(task.priority),
        onTap: () => onTap(task.id),
      ),
    );
  }
}

// BlocSelector — rebuild only on specific field change
BlocSelector<TaskBloc, TaskState, List<Task>>(
  selector: (state) => state.tasks.where((t) => t.status == 'todo').toList(),
  builder: (context, todoTasks) => TaskListView(tasks: todoTasks),
);
```

### 6.2 Image Caching

```yaml
# pubspec.yaml
dependencies:
  cached_network_image: ^3.3.0
```

```dart
// Avatar with cache + placeholder + error
CachedNetworkImage(
  imageUrl: user.avatarUrl ?? defaultAvatar,
  imageBuilder: (context, imageProvider) => CircleAvatar(
    backgroundImage: imageProvider,
    radius: 24,
  ),
  placeholder: (context, url) => const CircleAvatar(
    radius: 24,
    child: CircularProgressIndicator(strokeWidth: 2),
  ),
  errorWidget: (context, url, error) => CircleAvatar(
    radius: 24,
    child: Text(user.initials, style: const TextStyle(fontSize: 14)),
  ),
  memCacheWidth: 96,   // Cache as 96px (avatar display size)
  memCacheHeight: 96,
  maxWidthDiskCache: 96, // Don't cache full-size originals
);
```

### 6.3 List View Optimization

| Technique | Implementation | Impact |
|-----------|---------------|--------|
| **ListView.builder** | `ListView.builder(itemCount: tasks.length, itemBuilder: ...)` | Virtual scrolling, not all items |
| **Pagination** | Cursor-based, Infinite scroll with `ScrollController` | 20-50 items per page |
| **Differential sync** | `GET /api/v1/tasks?since={last_sync_at}` | Only transfer changes |
| **Shimmer loading** | `shimmer` package for skeleton screens | Perceived performance |

```dart
// Paginated task list
class TaskListWidget extends StatefulWidget {
  const TaskListWidget({super.key});
  @override
  State<TaskListWidget> createState() => _TaskListWidgetState();
}

class _TaskListWidgetState extends State<TaskListWidget> {
  final _scrollController = ScrollController();
  bool _isLoadingMore = false;

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
  }

  void _onScroll() {
    if (_scrollController.position.pixels >=
        _scrollController.position.maxScrollExtent - 200) {
      if (!_isLoadingMore) {
        setState(() => _isLoadingMore = true);
        context.read<TaskBloc>().add(TaskLoadMore());
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<TaskBloc, TaskState>(
      builder: (context, state) {
        return ListView.builder(
          controller: _scrollController,
          itemCount: state.tasks.length + (state.hasMore ? 1 : 0),
          itemBuilder: (context, index) {
            if (index >= state.tasks.length) {
              return const Center(child: CircularProgressIndicator());
            }
            return TaskListItem(task: state.tasks[index]);
          },
        );
      },
    );
  }
}
```

### 6.4 Isolate Compute for Heavy Operations

```dart
// Heavy operation: filtering/searching large task lists
Future<List<Task>> searchTasks(List<Task> allTasks, String query) async {
  return compute(_searchInIsolate, _SearchParams(allTasks, query));
}

List<Task> _searchInIsolate(_SearchParams params) {
  final query = params.query.toLowerCase();
  return params.tasks.where((task) {
    return task.title.toLowerCase().contains(query) ||
        (task.description?.toLowerCase().contains(query) ?? false);
  }).toList();
}
```

### 6.5 App Startup Optimization

| Strategy | Action | Expected Gain |
|----------|--------|--------------|
| **Deferred loading** | Load non-critical features after frame | -30% cold start |
| **Pre-warm HTTP client** | Initialize Dio + interceptors in `main()` BEFORE first route | -200ms API call |
| **Hive box preload** | Open critical boxes (`tasks`, `settings`) synchronously | -100ms local reads |
| **Font preload** | Include Arabic font in bundle vs network fetch | -500ms text paint |

---

## 7. CDN & Asset Delivery

### 7.1 Static Asset CDN (Cloudflare)

| Asset Type | CDN Path | Cache TTL | Cache-Control |
|------------|----------|-----------|--------------|
| JS/CSS bundles | `https://app.tasksyncpro.com/assets/*` | 1 year | `public, max-age=31536000, immutable` |
| Avatar thumbnails | `https://storage.tasksyncpro.com/avatars/*` | 7 days | `public, max-age=604800` |
| Attachment thumbnails | `https://storage.tasksyncpro.com/thumbnails/*` | 7 days | `public, max-age=604800` |
| Font files | `https://app.tasksyncpro.com/fonts/*` | 1 year | `public, max-age=31536000, immutable` |
| Landing page images | `https://app.tasksyncpro.com/img/*` | 30 days | `public, max-age=2592000` |
| API responses (anonymous) | Edge cache via Cloudflare | 0s (dynamic) | `no-cache` |

### 7.2 Cloudflare Configuration

```
# Zone: tasksyncpro.com (proxied via Cloudflare)
# SSL/TLS: Full (Strict)
# Minimum TLS Version: 1.3
# Brotli: ON
# Auto Minify: JS, CSS, HTML
# HTTP/2: ON
# HTTP/3 (QUIC): ON
# Argo Smart Routing: ON (paid)
# Rocket Loader: OFF (conflicts with Vue SPA)
# Polish: LOSSY (for JPEG/PNG optimization)

# Page Rules:
- `app.tasksyncpro.com/assets/*` → Cache Level: Standard, Edge Cache TTL: 1 year
- `app.tasksyncpro.com/fonts/*` → Cache Level: Standard, Edge Cache TTL: 1 year
- `app.tasksyncpro.com/img/*` → Cache Level: Standard, Edge Cache TTL: 30 days
- `api.tasksyncpro.com/*` → Cache Level: Bypass (dynamic API)
```

### 7.3 Font Loading Strategy

```css
/* Preload critical Arabic font — swap display prevents invisible text */
@font-face {
  font-family: 'Cairo';
  src: url('/fonts/cairo-v28-arabic_latin-regular.woff2') format('woff2');
  font-weight: 400;
  font-style: normal;
  font-display: swap;  /* ⭐ KEY: text renders in fallback font until Cairo loads */
  unicode-range: U+0600-06FF, U+0750-077F, U+0000-00FF; /* Arabic + Latin subsets */
}

@font-face {
  font-family: 'Cairo';
  src: url('/fonts/cairo-v28-arabic_latin-700.woff2') format('woff2');
  font-weight: 700;
  font-display: swap;
  unicode-range: U+0600-06FF, U+0750-077F, U+0000-00FF;
}
```

```html
<!-- Preconnect to font origin -->
<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<!-- Preload main font variant -->
<link rel="preload"
      href="/fonts/cairo-v28-arabic_latin-regular.woff2"
      as="font"
      type="font/woff2"
      crossorigin>
```

### 7.4 Brotli Compression

```nginx
# /etc/nginx/nginx.conf — Brotli preferred over gzip
http {
    # Brotli
    brotli on;
    brotli_comp_level 6;
    brotli_static on;           # Pre-compressed .br files
    brotli_types text/plain text/css application/json application/javascript
                text/xml application/xml image/svg+xml
                application/vnd.api+json;

    # Gzip fallback (for clients without Brotli)
    gzip on;
    gzip_comp_level 5;
    gzip_types text/plain text/css application/json application/javascript
               text/xml application/xml image/svg+xml;

    # Vite content-hashed assets — immutable cache
    location /assets/ {
        root /var/www/tasksync/public;
        expires 1y;
        add_header Cache-Control "public, immutable";
        add_header X-Content-Type-Options nosniff;
    }

    # Font assets
    location /fonts/ {
        root /var/www/tasksync/public;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Storage proxy to S3
    location /storage/ {
        proxy_pass https://tasksync.s3.amazonaws.com;
        proxy_hide_header Set-Cookie;
        proxy_ignore_headers Set-Cookie;
        expires 7d;
        add_header Cache-Control "public, max-age=604800";
    }
}
```

### 7.5 Vite Build Output Strategy

```javascript
// vite.config.js
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import compress from 'vite-plugin-compression';

export default defineConfig({
  plugins: [
    vue(),
    compress({
      algorithm: 'brotliCompress',  // Generate .br files
      threshold: 1024,               // Only compress >1KB
      deleteOriginalAssets: false,   // Keep originals for gzip fallback
    }),
  ],
  build: {
    rollupOptions: {
      output: {
        manualChunks: {
          vendor: ['vue', 'vue-router', 'pinia', 'axios'],
          ui: ['@vueuse/core', 'vue-i18n', 'vuedraggable'],
          charts: ['chart.js'],  // Report views only
        },
      },
    },
    chunkSizeWarningLimit: 150,  // KB
    cssCodeSplit: false,          // Single CSS file for critical path
    sourcemap: false,             // Prod: no sourcemaps
    minify: 'esbuild',           // Fast minification
    target: 'es2020',
  },
});
```

---

## 8. Load Test Results Template

### 8.1 API Latency Template

| Endpoint | Scenario | VUs | P50 (ms) | P95 (ms) | P99 (ms) | Max (ms) | Error Rate | Pass/Fail |
|----------|----------|-----|----------|----------|----------|----------|------------|-----------|
| `GET /api/v1/tasks` | task-list | 100 | — | — | — | — | — | ⏳ |
| `POST /api/v1/tasks` | task-create | 50 | — | — | — | — | — | ⏳ |
| `PUT /api/v1/tasks/reorder` | kanban-reorder | 30 | — | — | — | — | — | ⏳ |
| `POST /.../time-entries/start` | timer-toggle | 60 | — | — | — | — | — | ⏳ |
| `POST /.../time-entries/stop` | timer-toggle | 60 | — | — | — | — | — | ⏳ |

### 8.2 Lighthouse Scores Template

| Page | FCP (s) | LCP (s) | TTI (s) | CLS | Performance | Accessibility | Best Practice | SEO |
|------|---------|---------|---------|-----|------------|--------------|---------------|-----|
| Landing | — | — | — | — | — | — | — | — |
| Login | — | — | — | — | — | — | — | — |
| Dashboard | — | — | — | — | — | — | — | — |
| Kanban Board | — | — | — | — | — | — | — | — |
| Reports | — | — | — | — | — | — | — | — |
| Settings | — | — | — | — | — | — | — | — |

### 8.3 k6 Execution Command

```bash
# Run full test suite with HTML report
k6 run \
  --out json=reports/load-test-$(date +%Y%m%d-%H%M).json \
  --out dashboard=reports/dashboard-$(date +%Y%m%d-%H%M).html \
  -e API_BASE_URL=$API_BASE_URL \
  -e AUTH_TOKEN=$AUTH_TOKEN \
  -e WORKSPACE_ID=$WORKSPACE_ID \
  -e PROJECT_ID=$PROJECT_ID \
  -e TASK_IDS=$TASK_IDS \
  -e ASSIGNEE_ID=$ASSIGNEE_ID \
  scenarios/task-list.js

# Generate summary
# jq -c 'select(.type=="Point" and .metric=="http_req_duration")' \
#   reports/load-test-*.json | tail -100
```

### 8.4 Lighthouse CI Configuration

```yaml
# lighthouserc.yaml
ci:
  collect:
    numberOfRuns: 3
    staticDistDir: ./frontend/dist
    url:
      - http://localhost:4173/
      - http://localhost:4173/login
      - http://localhost:4173/dashboard
      - http://localhost:4173/projects/1/board
  assert:
    preset: "lighthouse:recommended"
    assertions:
      # Must-have budgets
      first-contentful-paint:
        - warn: 1500
        - error: 2000
      largest-contentful-paint:
        - error: 2500
      cumulative-layout-shift:
        - error: 0.1
      interactive:
        - error: 3000
      # Score budgets
      categories:performance:
        - error: 0.9   # >90
      categories:accessibility:
        - error: 0.95  # >95
      categories:best-practices:
        - error: 0.9
      categories:seo:
        - error: 0.9
  upload:
    target: "filesystem"
    outputDir: ./reports/lighthouse
```

---

## 9. Breach Flag Summary

### Potential Breaches (pre-testing risk assessment)

| # | Component | Budget | Risk Level | Suspected Cause | Action |
|---|-----------|--------|------------|-----------------|--------|
| 1 | `GET /api/v1/time-entries/report` | P99 <500ms | 🔴 High | Aggregation with GROUP BY, date filter on large dataset | Add covering index; cache aggressively; consider summary table |
| 2 | `PUT /api/v1/tasks/reorder` | P95 <500ms | 🟡 Medium | Transaction + WebSocket broadcast + Reverb publish per row | Batch updates in single query; move WS broadcast to queue |
| 3 | `GET /api/v1/dashboard/stats` | P95 <300ms | 🟡 Medium | N+1 COUNT queries across 4 tables | Single aggregation query with CASE; cache 120s |
| 4 | Flutter cold start | <2s | 🟡 Medium | Features/libraries loaded eagerly | Deferred loading, lazy init |
| 5 | FCP >1.5s | FCP <1.5s | 🟡 Medium | Arabic font loading blocks text paint | `font-display: swap`, preconnect, WOFF2 subset |
| 6 | Bundle size >350KB | <350KB | 🟢 Low | Vite default bundles all views in one chunk | Lazy load routes, manualChunks for vendor/UI/charts |

### Escalation Path

If any breach is confirmed during testing:

1. Log breach in `_context/DECISIONS.md`
2. Flag to `sofi-qa-sre-lead` (TKT-013 gatekeeper)
3. If P99 >500ms or Error Rate >5%: **Block Gate 5 sign-off**
4. If Lighthouse Performance <90: **Block CI pipeline**
5. If App startup >3s: **Block mobile release**

---

*Generated by Performance & Load Analyst (Ahmed Farouk) · Gate 5 · 2026-06-25*
*Tools: k6, Lighthouse CI, Laravel Telescope, PostgreSQL EXPLAIN ANALYZE, Redis CLI*
*Next: TKT-013 sofi-qa-sre-lead — Gate 5 sign-off*
