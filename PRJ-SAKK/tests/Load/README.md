# Wallet Load Test

K6 load test for wallet operations (payment requests, transfers, balance checks).

## Prerequisites

- **K6 installed:** [k6.io/docs/getting-started/installation](https://k6.io/docs/getting-started/installation/)
- **App running:** Backend server at `http://127.0.0.1:8000` (or set `BASE_URL`)
- **Seeded users:** `ahmad@test.com` / `sara@test.com` (password: `password`)

## How to run

```bash
k6 run wallet-load-test.js
```

Override base URL:

```bash
k6 run -e BASE_URL=http://localhost:8000 wallet-load-test.js
```

## Scenarios

### Scenario 1 — Create payment requests
10 concurrent VUs each POST to `/api/v1/payment-requests`. Unique amounts per VU/iteration. Threshold: p95 < 500ms.

### Scenario 2 — Transfer between wallets
5 concurrent VUs each POST to `/api/v1/transfer`. Cycles sender/receiver pairs. Includes cashback verification. Threshold: p95 < 500ms.

### Scenario 3 — Burst balance checks
50 concurrent GET requests to `/api/v1/wallets/{id}/balance`. Rapid-fire read endpoint test. Threshold: p95 < 500ms.

## Metrics

| Metric                     | Type    | Description                    |
|----------------------------|---------|--------------------------------|
| `payment_request_duration` | Trend   | Payment request response time  |
| `transfer_duration`        | Trend   | Transfer response time         |
| `balance_check_duration`   | Trend   | Balance check response time    |
| `success_rate`             | Rate    | Fraction of successful ops     |
| `payment_requests_created` | Counter | Total payment requests created |
| `transfers_completed`      | Counter | Total transfers completed      |
| `balance_checks_done`      | Counter | Total balance checks performed |

## Thresholds

- p95 < 500ms for all three operation types
- Success rate > 90%
