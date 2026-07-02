# Logging Strategy — TaskSync Pro (SAAS-001)

> **Gate:** 8 · **Owner:** Naomi Brooks (Observability SRE) · **Date:** 2026-06-25
> **Stack:** Laravel structured logging → Docker stdout → Loki/Grafana

---

## 1. Laravel Log Channel Configuration

### 1.1 Channel Setup

```php
// config/logging.php
return [
    'default' => env('LOG_CHANNEL', 'stack'),

    'channels' => [
        // Default: daily JSON rotate (30 days retention)
        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => env('LOG_DAILY_DAYS', 30),
            'permission' => 0644,
            'tap' => [App\Logging\JsonFormatter::class],  // Structured JSON
        ],

        // Error-level → Slack notification
        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'channel' => env('LOG_SLACK_CHANNEL', '#alerts-errors'),
            'username' => 'TaskSync Logger',
            'emoji' => ':boom:',
            'level' => env('LOG_SLACK_LEVEL', 'error'),
        ],

        // Info+ → stdout (Docker: container logs)
        'stdout' => [
            'driver' => 'monolog',
            'level' => env('LOG_STDOUT_LEVEL', 'info'),
            'handler' => StreamHandler::class,
            'with' => [
                'stream' => 'php://stdout',
            ],
            'formatter' => env('LOG_STDOUT_FORMATTER', Monolog\Formatter\JsonFormatter::class),
        ],

        // Emergency → Slack (critical only)
        'emergency' => [
            'driver' => 'monolog',
            'level' => 'emergency',
            'handler' => SlackWebhookHandler::class,
            'formatter' => Monolog\Formatter\LineFormatter::class,
            'with' => [
                'webhookUrl' => env('LOG_SLACK_WEBHOOK_URL'),
            ],
        ],

        // Audit trail (separate file, append-only)
        'audit' => [
            'driver' => 'daily',
            'path' => storage_path('logs/audit.log'),
            'level' => 'info',
            'days' => 90,  // Audit logs kept 3 months
            'tap' => [App\Logging\AuditFormatter::class],
        ],

        // Stack: default output
        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', env('LOG_STACK_CHANNELS', 'daily')),
        ],
    ],
];
```

### 1.2 Environment Config

```env
# .env.production
LOG_CHANNEL=stack
LOG_STACK_CHANNELS=daily,stdout
LOG_LEVEL=info
LOG_SLACK_WEBHOOK_URL=${SLACK_WEBHOOK_URL}
LOG_SLACK_LEVEL=error
LOG_STDOUT_LEVEL=info
LOG_DAILY_DAYS=30
```

---

## 2. Structured JSON Logging

### 2.1 Custom JSON Formatter

```php
// app/Logging/JsonFormatter.php
namespace App\Logging;

use Monolog\Formatter\JsonFormatter as BaseJsonFormatter;

class JsonFormatter extends BaseJsonFormatter
{
    public function format(array $record): string
    {
        // Enrich with context
        $record['@timestamp'] = $record['datetime']->format('c');
        $record['@version'] = 1;
        $record['app'] = config('app.name');
        $record['env'] = config('app.env');
        $record['release'] = trim(exec('git log --pretty="%h" -n1 HEAD 2>/dev/null') ?: 'unknown');

        // Add request context if available
        if ($request = request()) {
            $record['request_id'] = $request->header('X-Request-Id');
            $record['route'] = $request->path();
            $record['method'] = $request->method();
            $record['user_id'] = auth()->id();
            $record['ip'] = $request->ip();
            $record['user_agent'] = $request->userAgent();
        }

        return parent::format($record);
    }
}
```

### 2.2 Example Output

```json
{
  "@timestamp": "2026-06-25T14:30:00+00:00",
  "@version": 1,
  "app": "TaskSyncPro",
  "env": "production",
  "release": "a1b2c3d",
  "message": "Task created successfully",
  "level": "info",
  "channel": "daily",
  "request_id": "req_abc123",
  "route": "api/v1/tasks",
  "method": "POST",
  "user_id": "u_01h3xz",
  "ip": "203.0.113.42",
  "user_agent": "Mozilla/5.0 ...",
  "context": {
    "task_id": "t_01h3yz",
    "project_id": "p_01h3xzz",
    "assignee_id": "u_01h3xy"
  }
}
```

---

## 3. Log Levels & Routing

| Level | Channel | Action | Sample Events |
|-------|---------|--------|---------------|
| **emergency** | Slack | Immediate human attention | DB down, queue failure, OOM |
| **alert** | Slack + Sentry | Automated response | Auth failure spike, rate limit breach |
| **critical** | Sentry | Bug fix required | Unhandled exception, 500 errors |
| **error** | Slack + Sentry | Manual review | Validation errors, 3rd party API failure |
| **warning** | daily + stdout | Monitor for trend | Slow query warning, rate limit approaching |
| **notice** | daily + stdout | INFO | Cache miss, retry attempt |
| **info** | daily + stdout | Default production | Task created, user registered, timer started |
| **debug** | daily only | Development only | SQL queries, HTTP client calls |

### 3.1 Production Config Summary

```
┌──────────────┬────────────┬──────────┬────────┐
│ Level        │ daily      │ stdout   │ Slack  │
├──────────────┼────────────┼──────────┼────────┤
│ emergency    │ ✅         │ ✅       │ ✅    │
│ alert        │ ✅         │ ✅       │ ✅    │
│ critical     │ ✅         │ ✅       │ ✅    │
│ error        │ ✅         │ ✅       │ ✅    │
│ warning      │ ✅         │ ✅       │ ❌    │
│ notice       │ ✅         │ ✅       │ ❌    │
│ info         │ ✅         │ ✅       │ ❌    │
│ debug        │ ✅         │ ❌       │ ❌    │
└──────────────┴────────────┴──────────┴────────┘
```

---

## 4. Centralized Log Aggregation

### 4.1 Option A: Loki + Grafana (Recommended, self-hosted)

Use existing Grafana instance from monitoring stack.

Add to monitoring docker-compose:

```yaml
services:
  loki:
    image: grafana/loki:3.0
    ports:
      - "3100:3100"
    volumes:
      - loki_data:/loki
    command: -config.file=/etc/loki/local-config.yaml

  promtail:
    image: grafana/promtail:3.0
    volumes:
      - /var/log:/var/log:ro
      - /var/lib/docker/containers:/var/lib/docker/containers:ro
      - ./promtail-config.yml:/etc/promtail/config.yml:ro
    command: -config.file=/etc/promtail/config.yml
```

Promtail config:

```yaml
# deploy/monitoring/promtail-config.yml
server:
  http_listen_port: 9080

positions:
  filename: /tmp/positions.yaml

clients:
  - url: http://loki:3100/loki/api/v1/push

scrape_configs:
  - job_name: docker
    docker_sd_configs:
      - host: unix:///var/run/docker.sock
        refresh_interval: 15s
    relabel_configs:
      - source_labels: ['__meta_docker_container_name']
        target_label: 'container'
      - source_labels: ['__meta_docker_container_label_com_docker_compose_service']
        target_label: 'service'
```

### 4.2 Option B: Papertrail (SaaS, simpler)

```env
# .env.production
PAPERTRAIL_HOST=logs.papertrailapp.com
PAPERTRAIL_PORT=12345

# Laravel config
LOG_PAPERTRAIL_HOST=${PAPERTRAIL_HOST}
LOG_PAPERTRAIL_PORT=${PAPERTRAIL_PORT}
```

```php
// config/logging.php
'papertrail' => [
    'driver' => 'monolog',
    'level' => env('LOG_LEVEL', 'info'),
    'handler' => SyslogUdpHandler::class,
    'handler_with' => [
        'host' => env('PAPERTRAIL_HOST'),
        'port' => env('PAPERTRAIL_PORT'),
    ],
    'formatter' => Monolog\Formatter\JsonFormatter::class,
],
```

**Recommendation:** Loki + Grafana (leverage existing Grafana; lower cost at scale).

---

## 5. Audit Logging

### 5.1 Sensitive Actions Logged

Every audit event includes: `user_id`, `action`, `resource`, `resource_id`, `timestamp`, `ip_address`.

#### Current architecture uses `spatie/activitylog` (from SECURITY.md)

```php
// Example audit log entry
activity()
    ->causedBy(auth()->user())
    ->performedOn($task)
    ->withProperties([
        'changes' => ['status' => ['todo', 'in_progress']],
        'project_id' => $task->project_id,
    ])
    ->log('task_moved');
```

### 5.2 Audit Log Channel

```php
// Log audit to dedicated file + stdout
Log::channel('audit')->info('task_moved', [
    'user_id' => $user->id,
    'action' => 'task_moved',
    'resource' => 'Task',
    'resource_id' => $task->id,
    'project_id' => $task->project_id,
    'changes' => $changes,
    'ip' => request()->ip(),
    'timestamp' => now()->toIso8601String(),
]);
```

### 5.3 Audit Events Table (from activity_log)

The immutable `activity_log` table stores all audit events:

| Column | Type | Example |
|--------|------|---------|
| `id` | BIGSERIAL | 1 |
| `log_name` | VARCHAR | `default` |
| `description` | TEXT | `task_moved` |
| `subject_type` | VARCHAR | `App\Models\Task` |
| `subject_id` | UUID | `t_01h3yz...` |
| `causer_type` | VARCHAR | `App\Models\User` |
| `causer_id` | UUID | `u_01h3xz...` |
| `properties` | JSONB | `{"changes": {...}}` |
| `created_at` | TIMESTAMP | `2026-06-25T14:30:00Z` |

### 5.4 Retention

| Log Type | Retention | Action |
|----------|-----------|--------|
| Application logs | 30 days | Compressed → S3 cold storage |
| Audit logs | 90 days | Immutable; no deletion |
| Error logs | 180 days | Extends for postmortems |
| Docker container logs | 7 days | `docker system prune` for rotation |

---

## 6. Docker Log Drivers

### 6.1 Compose Config

```yaml
# In docker-compose.prod.yml — per service
services:
  php-fpm-blue:
    logging:
      driver: "json-file"
      options:
        max-size: "10m"
        max-file: "3"
        tag: "{{.Name}}/{{.ID}}"

  postgres:
    logging:
      driver: "json-file"
      options:
        max-size: "10m"
        max-file: "3"

  # Default for all services
  nginx:
    logging:
      driver: "json-file"
      options:
        max-size: "10m"
        max-file: "3"
```

### 6.2 Log Access

```bash
# Docker logs per service
docker logs tasksync-php-fpm-blue --tail 100 -f
docker logs tasksync-nginx --tail 100 -f

# Search via journald (if using journald driver)
journalctl -u docker -t tasksync-php-fpm-blue --since "5 min ago"

# Grafana Loki query
{container="tasksync-php-fpm-blue"} |= "error"
{service="php-fpm-blue"} | json | level="error"
```

---

## 7. Implementation Checklist

- [ ] Add `JsonFormatter.php` to `app/Logging/`
- [ ] Add `AuditFormatter.php` to `app/Logging/`
- [ ] Configure `config/logging.php` with all channels
- [ ] Set `.env` log levels per environment
- [ ] Integrate Loki + Promtail in monitoring docker-compose
- [ ] Add Loki datasource to Grafana (provisioning)
- [ ] Create "Logs" panel in Grafana dashboard
- [ ] Verify structured JSON output: `docker logs tasksync-php-fpm-blue | head -5`
- [ ] Test audit log: perform action → check `activity_log` table + `storage/logs/audit.log`
- [ ] Configure log rotation: `logrotate` for daily logs, Docker `max-size` for container logs

---

*Generated by Naomi Brooks (Observability SRE) · Gate 8 · 2026-06-25*
*Consumes: SECURITY.md (activity_log), docker-compose.prod.yml (logging config)*
