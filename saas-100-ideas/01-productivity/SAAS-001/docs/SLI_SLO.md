# SLI/SLO — TaskSync Pro (SAAS-001)

> **Gate:** 8 · **Owner:** Naomi Brooks (Observability SRE) · **Date:** 2026-06-25
> **Consumes:** ARCHITECTURE.md, PERFORMANCE_REPORT.md, docker-compose.prod.yml
> **Next:** Weekly report to `sofi-ceo`; auto-file PRJ-scoped issue on SLO breach

---

## 1. Service Level Indicators (SLIs)

### 1.1 API Availability

| Field | Value |
|-------|-------|
| **SLI** | Health check pass rate |
| **Definition** | `successful_health_checks / total_health_checks` over window |
| **Measurement** | Prometheus `probe_success` from Blackbox exporter; Nginx `http_requests_total` status=2xx vs total |
| **Target (SLO)** | **99.9%** |
| **Window** | 30d rolling |
| **Burn Rate Warning** | <99.9% over 7d → ticket |
| **Slack** | 5% error budget consumed/week |

### 1.2 API Latency

| Field | P95 | P99 |
|-------|-----|-----|
| **SLI** | Request duration (server processing) | Request duration (server processing) |
| **Definition** | `histogram_quantile(0.95, rate(http_request_duration_seconds_bucket[5m]))` | `histogram_quantile(0.99, rate(http_request_duration_seconds_bucket[5m]))` |
| **Measurement** | Laravel middleware → Prometheus histogram; Nginx `$request_time` |
| **Target (SLO)** | **<500ms** | **<2s** |
| **Window** | 5m | 5m |
| **Source** | ARCHITECTURE.md §8: API P99 <200ms (stricter for budgets); this SLO allows operational margin | SLO aligned with k6 thresholds from PERFORMANCE_REPORT.md |

### 1.3 WebSocket Uptime

| Field | Value |
|-------|-------|
| **SLI** | Connection success rate |
| **Definition** | `connection_attempts_succeeded / connection_attempts_total` |
| **Measurement** | Reverb health check endpoint; client-side Echo `connected` vs `disconnected` events |
| **Target (SLO)** | **99.5%** |
| **Window** | 30d rolling |

### 1.4 WebSocket Latency

| Field | Value |
|-------|-------|
| **SLI** | Event delivery time (server publish → client receive) |
| **Definition** | `histogram_quantile(0.95, rate(websocket_event_delivery_seconds_bucket[5m]))` |
| **Measurement** | Custom metric: Reverb middleware timestamps event publish → client ACK |
| **Target (SLO)** | **<200ms (P95)** |
| **Window** | 5m |

### 1.5 Queue Processing Time

| Field | Value |
|-------|-------|
| **SLI** | Job dispatch → processed time |
| **Definition** | `histogram_quantile(0.95, rate(horizon_job_processed_time_seconds_bucket[5m]))` |
| **Measurement** | Horizon metrics (Redis queue → job processed); Prometheus `laravel_job_processed_duration_seconds` |
| **Target (SLO)** | **<10s (P95)** |
| **Window** | 5m |
| **Notes** | High-tier (notifications) expected <2s; Low-tier (exports) may reach 30s — monitor per queue |

### 1.6 Error Budget Burn Rate

| Field | Value |
|-------|-------|
| **SLI** | SLO vs actual consumption |
| **Definition** | `(1 - actual_availability / SLO_target) * 100` per month |
| **Measurement** | Calculated in Grafana from SLI time series |
| **Target** | **<10%/month** budget consumed |
| **Action** | >10% → weekly review; >20% → incident; >50% → freeze deploys |

---

## 2. SLO Summary Table

| SLI | Target | Window | Measurement Method | Instrumentation |
|-----|--------|--------|-------------------|-----------------|
| API availability | 99.9% | 30d rolling | `probe_success` / `http_requests_total{status=~"2.."}` | Blackbox + Nginx metrics |
| API latency P95 | <500ms | 5m | `histogram_quantile(0.95, ...)` | Laravel Prometheus middleware |
| API latency P99 | <2s | 5m | `histogram_quantile(0.99, ...)` | Laravel Prometheus middleware |
| WebSocket uptime | 99.5% | 30d | `reverb_connections_active / reverb_connections_total` | Reverb metrics endpoint |
| WebSocket latency P95 | <200ms | 5m | `websocket_event_delivery_seconds` | Reverb custom middleware |
| Queue processing P95 | <10s | 5m | `horizon_job_processed_duration_seconds` | Horizon Prometheus exporter |
| Error budget burn | <10%/mo | 30d | Grafana calculation | Derived from above SLIs |

---

## 3. Multi-Window Burn Rate Policy

| Burn Rate | Time Window | Action |
|-----------|-------------|--------|
| **2x** (error budget consumed 2x faster than expected) | 1h | Page SRE (PagerDuty alert) |
| **2x** | 6h | Page SRE |
| **2x** | 3d | File incident — investigate root cause |
| **5x** | 30m | Critical page — wake-up call |
| **5x** | 2h | Emergency — rollback or feature disable |
| **10x** | 10m | Maximum urgency — escalate to CTO |

### Multi-Window Approach

Use **both** short and long windows to avoid false positives:

```
ALERT HighErrorBurnRate_1h
  IF rate(http_requests_total{status=~"5.."}[1h]) / rate(http_requests_total[1h]) > 0.02
  FOR 5m
  LABELS { severity: critical }

ALERT HighErrorBurnRate_3d
  IF rate(http_requests_total{status=~"5.."}[3d]) / rate(http_requests_total[3d]) > 0.005
  FOR 5m
  LABELS { severity: warning }
```

---

## 4. SLO Reporting

### Weekly Report to `sofi-ceo`

Template:

```
# Weekly SLO Report — SAAS-001 (TaskSync Pro)
Period: YYYY-MM-DD to YYYY-MM-DD

## SLO Status
- API Availability:   99.XX% (target 99.9%) ✅/❌
- API Latency P95:    XXXms (target <500ms) ✅/❌
- API Latency P99:    XXXms (target <2s) ✅/❌
- WebSocket Uptime:  99.XX% (target 99.5%) ✅/❌
- WebSocket Latency:  XXXms (target <200ms) ✅/❌
- Queue Processing:   XXXms (target <10s) ✅/❌
- Error Budget Used:  XX% (target <10%) ✅/❌

## Top 3 Incidents
1. [severity] — description — resolution — duration

## Trailing 30d Trend
[Grafana screenshot link]

## Conversion/Drop-off by Journey Stage
| Stage | Visitors | Conversion | Drop-off |
|-------|----------|------------|----------|
| Landing | X | - | Y% |
| Signup | X | Z% | Y% |
| Workspace Setup | X | Z% | Y% |
| Active (create task) | X | Z% | Y% |
| Retained (3d later) | X | Z% | Y% |

## Action Items
- [ ] item
```

### Auto-File Issue on SLO Breach

When SLO breached or error spike detected:
1. Grafana alert fires → webhook → GitHub Issue created
2. Issue title: `[SLO Breach] {component} — {SLI} below target`
3. Labels: `slo-breach`, `gate-1-return`
4. Assign to component owner
5. Issue re-enters **Gate 1 (Discovery)** for that component
6. Root cause analysis required before Gate 3 re-entry

---

## 5. Journey Stage Conversion Tracking

| Stage | Metric | SLI Proxy | Target |
|-------|--------|-----------|--------|
| Landing → Signup | Signup form views → submissions | `POST /api/auth/register` success rate | >80% conversion |
| Signup → Workspace Setup | Registration → first workspace created | `POST /api/teams` after auth | >70% |
| Workspace → Active | Workspace created → first task created | `POST /api/projects/{id}/tasks` | >60% |
| Active → Retained | Task created → returns within 3 days | Login event → GET /api/tasks | >40% |

Each stage tracked via Prometheus counter + Grafana dashboard panel.

---

*Generated by Naomi Brooks (Observability SRE) · Gate 8 · 2026-06-25*
*Consumes: ARCHITECTURE.md §8 Performance Budget, PERFORMANCE_REPORT.md*
*Next: Weekly SLO report to sofi-ceo*
