# Monitoring Stack — TaskSync Pro (SAAS-001)

> **Gate:** 8 · **Owner:** Naomi Brooks (Observability SRE) · **Date:** 2026-06-25
> **Stack:** Prometheus + Grafana + Exporters · Docker Compose monitoring services

---

## 1. Architecture

```
┌─────────────┐     ┌──────────────┐     ┌──────────────┐
│ Node        │────▶│ Prometheus   │◀────│ Grafana      │
│ Exporter    │     │ (scrape every│     │ (dashboards, │
│ (host OS)   │     │  15s)       │     │  alerting)   │
├─────────────┤     │              │     └──────────────┘
│ Redis       │     │ Scrape       │
│ Exporter    │────▶│ targets:     │
├─────────────┤     │ - node:9100  │
│ Postgres    │     │ - redis:9121 │
│ Exporter    │────▶│ - pg:9187    │
├─────────────┤     │ - php-fpm:9253│
│ PHP-FPM     │     │ - app:9000   │
│ Exporter    │────▶│ (custom)     │
├─────────────┤     │ - nginx:9113 │
│ Nginx       │     └──────────────┘
│ Exporter    │────▶
├─────────────┤
│ Reverb      │
│ (internal   │────▶ /metrics endpoint
│  metrics)   │
└─────────────┘
```

---

## 2. Prometheus

### 2.1 Scrape Config

File: `deploy/monitoring/prometheus.yml`

Key targets:
- `node_exporter:9100` — host-level CPU/RAM/disk
- `redis_exporter:9121` — Redis memory, hit rate, queue length
- `postgres_exporter:9187` — DB connections, slow queries, replication
- `php-fpm-exporter:9253` — PHP pool metrics
- `nginx-exporter:9113` — Nginx connections, request rate
- Laravel app: `/metrics` endpoint (via prometheus middleware)
- Reverb WebSocket: custom `/metrics` endpoint

### 2.2 Metric Sources

| Metric | Source | Prometheus Metric |
|--------|--------|-------------------|
| API request rate | Laravel middleware | `http_requests_total{method,route,status}` |
| API latency | Laravel middleware | `http_request_duration_seconds_bucket` |
| Queue size | Redis exporter | `redis_db_keys` on Horizon queue keys |
| Queue processed | Horizon stats | `laravel_queue_job_processed_total` |
| DB connections | postgres_exporter | `pg_stat_activity_count` |
| DB slow queries | postgres_exporter | `pg_stat_activity_max_tx_duration` |
| Redis memory | redis_exporter | `redis_memory_used_bytes` |
| Redis hit rate | redis_exporter | `redis_keyspace_hits_total / redis_keyspace_misses_total` |
| WebSocket connections | Reverb metric | `reverb_connections_active` |
| Disk usage | node_exporter | `node_filesystem_avail_bytes` |
| CPU usage | node_exporter | `node_cpu_seconds_total` |

### 2.3 Custom Laravel Metrics

Install Prometheus middleware:

```bash
composer require promphp/prometheus_client_php
```

Create middleware:

```php
// app/Http/Middleware/PrometheusMetrics.php
namespace App\Http\Middleware;

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;

class PrometheusMetrics
{
    public function __construct(
        private CollectorRegistry $registry
    ) {}

    public function handle($request, \Closure $next)
    {
        $start = microtime(true);
        $response = $next($request);
        $duration = microtime(true) - $start;

        // Request counter
        $counter = $this->registry->getOrRegisterCounter(
            'http_requests_total',
            'Total HTTP requests',
            ['method', 'route', 'status']
        );
        $counter->inc([
            $request->method(),
            $request->path(),
            $response->status(),
        ]);

        // Duration histogram
        $histogram = $this->registry->getOrRegisterHistogram(
            'http_request_duration_seconds',
            'Request duration in seconds',
            ['method', 'route', 'status'],
            [0.01, 0.05, 0.1, 0.25, 0.5, 1.0, 2.0, 5.0]
        );
        $histogram->observe($duration, [
            $request->method(),
            $request->path(),
            $response->status(),
        ]);

        return $response;
    }
}

// Metrics endpoint in routes/api.php
Route::get('/metrics', function () {
    $registry = app(CollectorRegistry::class);
    $renderer = new RenderTextFormat();
    return response($renderer->render($registry->getMetricFamilySamples()))
        ->header('Content-Type', RenderTextFormat::MIME_TYPE);
});
```

---

## 3. Grafana

### 3.1 Dashboard: TaskSync Pro Overview

File: `deploy/monitoring/grafana-dashboard.json`

Panels:

| Panel | Metric | Type |
|-------|--------|------|
| API Requests/sec | `rate(http_requests_total[5m])` | Time series |
| Latency Heatmap | `http_request_duration_seconds_bucket` | Heatmap |
| Error Rate (%) | `rate(http_requests_total{status=~"5.."}[5m]) / rate(http_requests_total[5m]) * 100` | Gauge |
| Queue Depth | `laravel_queue_size` | Stat |
| Queue Processing Time (P95) | `histogram_quantile(0.95, rate(horizon_job_processed_duration_seconds_bucket[5m]))` | Time series |
| Active Connections (Reverb) | `reverb_connections_active` | Stat |
| DB Connection Pool | `pg_stat_activity_count` | Gauge |
| Slow Queries (>1s) | `pg_stat_activity_max_tx_duration > 1` | Stat |
| Redis Memory Usage | `redis_memory_used_bytes / redis_memory_max_bytes * 100` | Gauge |
| Redis Hit Rate | `rate(redis_keyspace_hits_total[5m]) / (rate(redis_keyspace_hits_total[5m]) + rate(redis_keyspace_misses_total[5m])) * 100` | Gauge |
| Disk Usage | `(node_filesystem_avail_bytes / node_filesystem_size_bytes) * 100` | Gauge |
| SLO Burn Rate | Calculated from availability SLI | Stat |

### 3.2 Provisioning

```yaml
# grafana-datasources.yaml (manual or via Docker volume)
apiVersion: 1
datasources:
  - name: Prometheus
    type: prometheus
    url: http://prometheus:9090
    access: proxy
    isDefault: true
```

```yaml
# grafana-notifiers.yaml
apiVersion: 1
notifiers:
  - name: Slack Alerts
    type: slack
    uid: slack-alerts
    settings:
      url: ${SLACK_WEBHOOK_URL}
      uploadImage: true
```

---

## 4. Docker Compose Monitoring Services

File: `deploy/monitoring/docker-compose.monitoring.yml`

| Service | Image | Port | Purpose |
|---------|-------|------|---------|
| `prometheus` | prom/prometheus:latest | 9090 | Metric storage & alerting |
| `grafana` | grafana/grafana:latest | 3000 | Dashboards & visualization |
| `node_exporter` | prom/node-exporter:latest | 9100 | Host metrics (CPU/RAM/Disk) |
| `redis_exporter` | oliver006/redis_exporter:latest | 9121 | Redis metrics |
| `postgres_exporter` | prometheuscommunity/postgres-exporter:latest | 9187 | PostgreSQL metrics |

---

## 5. Alerting

Alert rules in `deploy/monitoring/alert-rules.yml` (Prometheus-compatible).

Alertmanager config:

```yaml
# deploy/monitoring/alertmanager.yml
route:
  receiver: 'slack-critical'
  routes:
    - match:
        severity: critical
      receiver: 'slack-critical'
    - match:
        severity: warning
      receiver: 'slack-warning'

receivers:
  - name: 'slack-critical'
    slack_configs:
      - api_url: ${SLACK_WEBHOOK_URL}
        channel: '#alerts-critical'
        title: '{{ .GroupLabels.alertname }}'
        text: '{{ .CommonAnnotations.summary }}'
        send_resolved: true

  - name: 'slack-warning'
    slack_configs:
      - api_url: ${SLACK_WEBHOOK_URL}
        channel: '#alerts-ops'
        title: '{{ .GroupLabels.alertname }}'
        text: '{{ .CommonAnnotations.summary }}'
        send_resolved: true
```

---

## 6. Run Commands

```bash
# Start monitoring stack
docker compose -f deploy/monitoring/docker-compose.monitoring.yml up -d

# Check logs
docker compose -f deploy/monitoring/docker-compose.monitoring.yml logs -f

# Stop
docker compose -f deploy/monitoring/docker-compose.monitoring.yml down

# Reload Prometheus config
kill -HUP $(docker inspect tasksync-prometheus --format '{{.State.Pid}}')

# Import Grafana dashboard via API
curl -X POST http://admin:admin@localhost:3000/api/dashboards/db \
  -H "Content-Type: application/json" \
  -d @deploy/monitoring/grafana-dashboard.json
```

---

## 7. Production Considerations

| Concern | Mitigation |
|---------|------------|
| Prometheus data persistence | Docker volume `prometheus_data` on host |
| Grafana data persistence | Docker volume `grafana_data` on host |
| Alertmanager HA | Single instance sufficient for MVP; add replicas at scale |
| Scrape interval | 15s default; reduce to 30s for node_exporter |
| Retention | `--storage.tsdb.retention.time=30d` |
| Backup | `promtool tsdb backup` to S3 weekly |

---

*Generated by Naomi Brooks (Observability SRE) · Gate 8 · 2026-06-25*
*Deployment: `docker compose -f deploy/monitoring/docker-compose.monitoring.yml up -d`*
