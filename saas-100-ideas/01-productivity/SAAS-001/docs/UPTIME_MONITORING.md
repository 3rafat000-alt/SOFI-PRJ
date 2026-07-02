# Uptime Monitoring — TaskSync Pro (SAAS-001)

> **Gate:** 8 · **Owner:** Naomi Brooks (Observability SRE) · **Date:** 2026-06-25
> **Strategy:** Multi-region synthetic checks + SSL monitoring + public status page

---

## 1. Provider: Better Uptime (Recommended)

### 1.1 Why Better Uptime

- All-in-one: uptime checks + status page + on-call + SLAs
- Multi-region (5+ locations)
- SSL expiry monitoring included
- Public status page with custom domain
- Incident management with automatic status page updates
- Budget-friendly for single-service monitoring ($20-30/mo)

### 1.2 Setup

1. Sign up at [betteruptime.com](https://betteruptime.com)
2. Add monitors for each endpoint
3. Configure notification routing (Slack + PagerDuty + email)
4. Publish status page at `status.tasksyncpro.com`

---

## 2. Monitor Configuration

### 2.1 Health Check Endpoints

| Monitor | URL | Frequency | Regions | Type |
|---------|-----|-----------|---------|------|
| **API Health** | `https://api.tasksyncpro.com/api/v1/health` | 1 min | 5 regions | HTTP(S) |
| **WebSocket** | `wss://ws.tasksyncpro.com/app` | 1 min | 3 regions | WebSocket |
| **Dashboard** | `https://app.tasksyncpro.com` | 1 min | 5 regions | HTTP(S) |
| **Reverb Health** | `https://ws.tasksyncpro.com/health` | 1 min | 3 regions | HTTP(S) |

### 2.2 SSL Certificate Monitoring

| Monitor | Domain | Alert Before | Frequency |
|---------|--------|-------------|-----------|
| **SSL: API** | `api.tasksyncpro.com` | 14 days | Daily |
| **SSL: App** | `app.tasksyncpro.com` | 14 days | Daily |
| **SSL: WS** | `ws.tasksyncpro.com` | 14 days | Daily |
| **SSL: Status** | `status.tasksyncpro.com` | 14 days | Daily |
| **SSL: Main** | `tasksyncpro.com` | 14 days | Daily |

### 2.3 Alert Recipients

| Priority | Channel | Recipients |
|----------|---------|------------|
| **Critical** (API down) | Push + SMS + Slack | SRE on-call + CTO |
| **Warning** (latency >1s) | Slack | #alerts-ops |
| **SSL Expiry** (<14d) | Slack | #alerts-ops + DevOps lead |

---

## 3. Synthetic Transactions

### 3.1 Login → Create Task → Timer → Logout

Simulate full user journey every 5 minutes from 2 regions.

```javascript
// Checkly / Playwright synthetic script
const { chromium } = require('playwright');

async function run() {
  const browser = await chromium.launch();
  const context = await browser.newContext({
    baseURL: 'https://app.tasksyncpro.com',
  });
  const page = await context.newPage();

  // 1. Login
  await page.goto('/login');
  await page.fill('[name="email"]', process.env.SYNTH_EMAIL);
  await page.fill('[name="password"]', process.env.SYNTH_PASSWORD);
  await page.click('[type="submit"]');
  await page.waitForURL('**/dashboard');
  console.log('✓ Login successful');

  // 2. Create task
  await page.goto('/projects/1/board');
  await page.click('[data-testid="add-task-fab"]');
  await page.fill('[name="title"]', 'Synthetic Monitor Task');
  await page.selectOption('[name="priority"]', 'high');
  await page.click('[data-testid="save-task"]');
  await page.waitForSelector('[data-testid="task-card"]');
  console.log('✓ Task created');

  // 3. Start timer
  await page.click('[data-testid="timer-toggle"]');
  await page.waitForTimeout(2000);  // run for 2s
  await page.click('[data-testid="timer-toggle"]');
  console.log('✓ Timer started/stopped');

  // 4. Logout
  await page.click('[data-testid="user-menu"]');
  await page.click('[data-testid="logout"]');
  await page.waitForURL('**/login');
  console.log('✓ Logout successful');

  await browser.close();
}

run().catch(e => {
  console.error('✗ Synthetic transaction failed:', e.message);
  process.exit(1);
});
```

### 3.2 Transaction Steps Summary

| Step | Action | Expected Result | Timeout |
|------|--------|-----------------|---------|
| 1 | Navigate to `/login` | Page loads, form visible | 10s |
| 2 | Fill email + password, click login | Redirect to `/dashboard` | 10s |
| 3 | Navigate to project board | Kanban visible | 10s |
| 4 | Click "+" to add task | Task form modal opens | 5s |
| 5 | Fill title, select priority, save | Task card appears on board | 5s |
| 6 | Click timer, wait 2s, click again | Timer starts then stops | 5s |
| 7 | Click user menu, logout | Redirect to login | 5s |

---

## 4. Status Page

### 4.1 Custom Domain

- **Domain:** `status.tasksyncpro.com`
- **Provider:** Better Uptime Status Page (or Upptime GitHub Pages)
- **CNAME:** Point `status.tasksyncpro.com` → Better Uptime provided hostname

### 4.2 Status Page Components

| Component | Description |
|-----------|-------------|
| **API** | REST API availability + latency |
| **Dashboard** | Web application availability |
| **WebSocket** | Real-time connection status |
| **Email** | Transactional email delivery |
| **Push Notifications** | FCM delivery |
| **WhatsApp** | WhatsApp Cloud API integration |

### 4.3 Incident Templates

```
# Degraded Performance
We are currently experiencing increased latency on the API.
Root cause identified: [brief]. Engineering team is deploying fix.
Estimated resolution: [time].

# Service Outage
We are investigating an issue causing errors on [component].
Users may experience [impact].
Updates every 30 minutes.

# Resolved
[Component] performance has returned to normal.
Duration: [X] minutes.
Root cause: [brief].
Preventive actions: [action items].
```

---

## 5. Alternative Providers

| Provider | Strengths | Weaknesses | Price |
|----------|-----------|------------|-------|
| **Better Uptime** | All-in-one, status page, on-call | Fewer regions than Checkly | $20-30/mo |
| **Checkly** | Playwright-native, great synthetic monitoring | No built-in status page | $30-60/mo |
| **Upptime** | Open source, GitHub Pages, free | No SMS/Push alerts | Free |
| **Pingdom** | Mature, many regions | Expensive for full set | $15-200/mo |

**Recommendation:** Better Uptime for MVP (simplest setup, built-in status page). Add Checkly later for complex Playwright monitoring.

---

## 6. Runbook for Uptime Alerts

### SSL Expiry (<14 days)

1. Verify: `openssl s_client -connect tasksyncpro.com:443 -servername tasksyncpro.com </dev/null 2>/dev/null | openssl x509 -noout -enddate`
2. Renew: `certbot renew` (or Cloudflare auto-renew)
3. Verify new cert: repeat step 1
4. Update alert: monitor should show >30d remaining within 24h

### API Down (Multi-region)

1. Check Better Uptime dashboard — is all regions affected or a single region?
2. SSH to production: `curl -f http://localhost:80/api/v1/health`
3. Check Docker: `docker ps` — are all services running?
4. Check Nginx: `docker exec tasksync-ginx nginx -t`
5. Check PHP-FPM: `docker exec tasksync-php-fpm-blue php artisan route:list | grep health`
6. Follow RUNBOOK.md 503 procedure

---

*Generated by Naomi Brooks (Observability SRE) · Gate 8 · 2026-06-25*
*Next: Configure monitor endpoints after production deployment*
