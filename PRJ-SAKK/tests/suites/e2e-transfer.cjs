// E2E CROSS-TIER — the crown-jewel test: real money moves through the API, the DB reflects it,
// and the admin panel shows it. Verifies one operation from all three angles.
//   ahmad (#2, USD wallet #2) --transfer--> sara (#3, USD wallet #4)
const db = require('../lib/db.cjs');

const AHMAD_USD = 2;   // wallet id
const SARA_USD = 4;    // wallet id
const FUND = 1000;
const AMOUNT = 137.5;  // distinctive value so we can spot it in the admin grid

module.exports = async function e2eTransfer(runner, cfg) {
  const api = require('../lib/api.cjs');

  let result = { senderBefore: 0, senderAfter: 0, recvBefore: 0, recvAfter: 0, txBefore: 0, txAfter: 0, transferOk: false };

  // 1) API + DB: fund, transfer, verify persisted balances + transaction rows.
  await runner.apiScenario(
    { id: 'e2e-transfer-money', title: 'Transfer moves money (API → DB)', tier: 'flow', area: 'money' },
    async ({ http, check, must, log }) => {
      db.setWalletBalance(AHMAD_USD, FUND);
      result.senderBefore = db.walletBalance(AHMAD_USD);
      result.recvBefore = db.walletBalance(SARA_USD);
      result.txBefore = db.count('transactions');
      log(`before: sender=${result.senderBefore} receiver=${result.recvBefore} tx=${result.txBefore}`);
      must('ahmad funded to ' + FUND, result.senderBefore === FUND, 'got ' + result.senderBefore);

      const { token } = await api.login('user1');
      must('ahmad token', !!token);

      const r = await http('POST', '/transfer', { token, body: { identifier: 'sara@test.com', amount: AMOUNT, currency: 'USD' } });
      result.transferOk = r.status === 200 && r.json && r.json.success !== false;
      must('transfer accepted (200)', r.status === 200, 'status ' + r.status + ' :: ' + (r.text || '').slice(0, 160));

      result.senderAfter = db.walletBalance(AHMAD_USD);
      result.recvAfter = db.walletBalance(SARA_USD);
      result.txAfter = db.count('transactions');
      log(`after: sender=${result.senderAfter} receiver=${result.recvAfter} tx=${result.txAfter}`);

      const senderDelta = +(result.senderAfter - result.senderBefore).toFixed(2);   // negative net (debit minus cashback)
      const recvDelta = +(result.recvAfter - result.recvBefore).toFixed(2);          // positive
      // Sender is debited `amount` then credited 1% cashback (REWARD tx) — net = -(amount - cashback).
      const cashback = +(recvDelta + senderDelta).toFixed(2);                        // = amount + senderNet
      const expectedCashback = +(Math.round(AMOUNT * 0.01 * 100) / 100).toFixed(2);  // USD: round(amount*1%,2)
      check('sender balance decreased', senderDelta < 0, 'Δ ' + senderDelta);
      check('receiver credited exactly the amount', recvDelta === AMOUNT, 'Δ ' + recvDelta);
      check('sender net debit = amount − cashback', Math.abs(senderDelta - -(AMOUNT - expectedCashback)) < 0.01, `net=${senderDelta}, expect ${-(AMOUNT - expectedCashback)}`);
      check('cashback credited at 1% (REWARD tx)', Math.abs(cashback - expectedCashback) < 0.01, `cashback=${cashback}, expect ${expectedCashback}`);
      check('exactly 3 transaction rows (out+in+reward)', result.txAfter - result.txBefore === 3, `+${result.txAfter - result.txBefore}`);
    }
  );

  // 2) ADMIN reflection: the transfer must be visible in the admin transactions grid.
  await runner.scenario(
    { id: 'e2e-transfer-admin-reflect', title: 'Transfer reflects in admin panel', tier: 'admin', area: 'transactions', auth: 'admin' },
    async ({ page, step, check, must }) => {
      let resp;
      await step('open /admin/transactions', async (p) => { resp = await p.goto(cfg.baseURL + '/admin/transactions', { waitUntil: 'networkidle' }); });
      must('transactions page 200', resp && resp.status() === 200, 'status ' + (resp && resp.status()));
      const body = (await page.locator('body').innerText().catch(() => '')) || '';
      check('grid shows the transferred amount 137.5', /137[.,]5/.test(body), 'amount not found in first page');
      check('no error trace', !/Whoops|SQLSTATE|Exception/i.test(body), '');
      await step('screenshot grid', async () => {});
    }
  );

  // 3) F-006 reproduction: confirm the transfer logged the FCM TypeError (resilient but noisy).
  await runner.apiScenario(
    { id: 'e2e-transfer-fcm-noise', title: 'F-006: FCM null-token error logged on transfer', tier: 'api', area: 'observability' },
    async ({ check, log }) => {
      const { execSync } = require('child_process');
      let logged = '';
      try {
        logged = execSync('tail -n 40 storage/logs/laravel.log', { cwd: db.BACKEND, encoding: 'utf8' });
      } catch (_) {}
      const hit = /FCMService::send\(\).*null given|Transfer sender FCM failed/.test(logged);
      log(hit ? 'F-006 reproduced in log' : 'no FCM error in recent log (sender may have a token now)');
      check('transfer still succeeded despite FCM (resilient)', result.transferOk, 'transferOk=' + result.transferOk);
      // documentary only — not a hard failure; F-006 is tracked in FINDINGS.
      check('[doc] F-006 FCM null-token noise present', hit, hit ? 'confirmed' : 'not seen this run');
    }
  );
};
