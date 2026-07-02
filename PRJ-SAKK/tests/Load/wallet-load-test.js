import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Trend, Rate, Counter } from 'k6/metrics';
import { SharedArray } from 'k6/data';

const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8000';
const API_BASE = `${BASE_URL}/api/v1`;

const credentials = [
  { email: 'ahmad@test.com', password: 'password' },
  { email: 'sara@test.com', password: 'password' },
];

const paymentRequestTrend = new Trend('payment_request_duration');
const transferTrend = new Trend('transfer_duration');
const balanceTrend = new Trend('balance_check_duration');
const successRate = new Rate('success_rate');
const paymentReqCount = new Counter('payment_requests_created');
const transferCount = new Counter('transfers_completed');
const balanceCheckCount = new Counter('balance_checks_done');

export const options = {
  thresholds: {
    payment_request_duration: ['p(95)<500'],
    transfer_duration: ['p(95)<500'],
    balance_check_duration: ['p(95)<500'],
    success_rate: ['rate>0.9'],
  },
};

function authenticate(email, password) {
  const payload = JSON.stringify({ email, password });
  const params = { headers: { 'Content-Type': 'application/json' } };
  const res = http.post(`${API_BASE}/auth/login`, payload, params);
  if (res.status !== 200) return null;
  const body = res.json();
  const token = body.data?.token || body.token || body.access_token || null;
  return token;
}

export function setup() {
  const tokens = {};
  for (let i = 0; i < credentials.length; i++) {
    const cred = credentials[i];
    const token = authenticate(cred.email, cred.password);
    if (!token) throw new Error(`setup: login failed for ${cred.email}`);
    tokens[`user${i + 1}`] = token;
  }
  return tokens;
}

export default function (tokens) {
  const vu = __VU;
  const iter = __ITER;

  group('Scenario 1: Create payment requests', function () {
    const credIdx = vu % credentials.length;
    const cred = credentials[credIdx];
    const token = tokens[`user${credIdx + 1}`];

    const payload = JSON.stringify({
      amount: (iter % 100) + 1 + vu * 0.01,
      currency: 'USD',
      note: `load-test-payreq-vu${vu}-iter${iter}`,
    });

    const params = {
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`,
      },
    };

    const res = http.post(`${API_BASE}/payment-requests`, payload, params);
    const ok = res.status === 200 || res.status === 201;
    check(res, {
      'payreq status is 200/201': () => ok,
      'payreq response time < 500ms': () => res.timings.duration < 500,
    });
    paymentRequestTrend.add(res.timings.duration);
    successRate.add(ok);
    if (ok) paymentReqCount.add(1);
  });

  sleep(1);

  group('Scenario 2: Transfer between wallets', function () {
    const senderIdx = vu % credentials.length;
    const receiverIdx = (senderIdx + 1) % credentials.length;
    const senderCred = credentials[senderIdx];
    const token = tokens[`user${senderIdx + 1}`];
    const receiverEmail = receiverIdx === 0 ? 'ahmad@test.com' : 'sara@test.com';

    const payload = JSON.stringify({
      identifier: receiverEmail,
      amount: (iter % 5) + 0.5 + vu * 0.01,
      currency: 'USD',
    });

    const params = {
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`,
      },
    };

    const res = http.post(`${API_BASE}/transfer`, payload, params);
    const ok = res.status === 200;
    check(res, {
      'transfer status is 200': () => ok,
      'transfer response time < 500ms': () => res.timings.duration < 500,
      'transfer returned success': () => {
        try { return JSON.parse(res.body).success !== false; } catch { return false; }
      },
    });
    transferTrend.add(res.timings.duration);
    successRate.add(ok);
    if (ok) transferCount.add(1);
  });

  sleep(1);

  group('Scenario 3: Burst wallet balance checks', function () {
    const credIdx = vu % credentials.length;
    const cred = credentials[credIdx];
    const token = tokens[`user${credIdx + 1}`];
    const walletId = credIdx === 0 ? 2 : 4;

    const params = {
      headers: { Authorization: `Bearer ${token}` },
    };

    const res = http.get(`${API_BASE}/wallets/${walletId}/balance`, params);
    const ok = res.status === 200;
    check(res, {
      'balance status is 200': () => ok,
      'balance response time < 500ms': () => res.timings.duration < 500,
    });
    balanceTrend.add(res.timings.duration);
    successRate.add(ok);
    if (ok) balanceCheckCount.add(1);
  });
}

export function handleSummary(data) {
  return {
    stdout: [
      '=== Wallet Load Test Results ===',
      '',
      '--- Payment Request Creation ---',
      `  avg: ${data.metrics.payment_request_duration.values.avg.toFixed(2)}ms`,
      `  p95: ${data.metrics.payment_request_duration.values['p(95)'].toFixed(2)}ms`,
      `  count: ${data.metrics.payment_requests_created.values.count}`,
      '',
      '--- Transfer ---',
      `  avg: ${data.metrics.transfer_duration.values.avg.toFixed(2)}ms`,
      `  p95: ${data.metrics.transfer_duration.values['p(95)'].toFixed(2)}ms`,
      `  count: ${data.metrics.transfers_completed.values.count}`,
      '',
      '--- Balance Check ---',
      `  avg: ${data.metrics.balance_check_duration.values.avg.toFixed(2)}ms`,
      `  p95: ${data.metrics.balance_check_duration.values['p(95)'].toFixed(2)}ms`,
      `  count: ${data.metrics.balance_checks_done.values.count}`,
      '',
      `--- Overall Success Rate: ${(data.metrics.success_rate.values.rate * 100).toFixed(1)}% ---`,
    ].join('\n'),
  };
}
