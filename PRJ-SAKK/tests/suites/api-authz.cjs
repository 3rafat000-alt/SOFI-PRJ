// API SECURITY — the fintech-critical tier. Verifies that the backend itself enforces:
//   · privilege boundaries (a normal user cannot reach admin endpoints)
//   · object ownership / IDOR (user A cannot read or move user B's money)
//   · authentication (sensitive endpoints reject anonymous calls)
//   · money invariants (no negative/zero/self/over-balance transfers)
// Pure API — no browser. Uses seeded ahmad (#2) and sara (#3).
//
// Known owned resources (seeded): ahmad wallets [3,2] · sara wallets [5,4].
const AHMAD_WALLET = 2;   // ahmad owns
const SARA_WALLET = 4;    // sara owns — ahmad must NOT touch
const okReject = (s) => s === 401 || s === 403 || s === 404 || s === 422; // any principled deny

module.exports = async function apiAuthz(runner, cfg) {
  const api = require('../lib/api.cjs');
  const ahmad = (await api.login('user1')).token;
  const sara = (await api.login('user2')).token;

  // 0) sanity — tokens acquired
  await runner.apiScenario(
    { id: 'api-authz-setup', title: 'Auth tokens for ahmad & sara', area: 'setup' },
    async ({ must }) => {
      must('ahmad token', !!ahmad);
      must('sara token', !!sara);
    }
  );

  // 1) PRIVILEGE ESCALATION — normal user must be denied admin API.
  await runner.apiScenario(
    { id: 'api-authz-privilege', title: 'Normal user blocked from admin API', area: 'authz' },
    async ({ http, check }) => {
      const eps = [
        ['GET', '/admin/wallets'],
        ['GET', '/admin/transactions'],
        ['GET', '/admin/card-inventory'],
        ['POST', '/admin/wallets/' + SARA_WALLET + '/freeze'],
      ];
      for (const [m, p] of eps) {
        const r = await http(m, p, { token: ahmad });
        check(`ahmad ${m} ${p} denied (not 200)`, r.status !== 200, 'status ' + r.status);
        check(`ahmad ${m} ${p} is 401/403`, r.status === 401 || r.status === 403, 'status ' + r.status);
      }
    }
  );

  // 2) IDOR READ — ahmad must not read sara's wallet. Positive control: his own works.
  await runner.apiScenario(
    { id: 'api-authz-idor-read', title: 'IDOR: ahmad cannot read sara wallet', area: 'authz' },
    async ({ http, check, must }) => {
      // positive control
      const own = await http('GET', '/wallets/' + AHMAD_WALLET, { token: ahmad });
      must('ahmad reads OWN wallet (200)', own.status === 200, 'status ' + own.status);

      for (const sub of ['', '/balance', '/transactions', '/stats']) {
        const r = await http('GET', '/wallets/' + SARA_WALLET + sub, { token: ahmad });
        check(`ahmad GET sara wallet${sub} denied`, okReject(r.status), 'status ' + r.status);
        const leak = r.status === 200 && /balance|amount/i.test(r.text || '');
        check(`ahmad GET sara wallet${sub} leaks NO balance`, !leak, leak ? 'LEAK 200' : 'ok');
      }
    }
  );

  // 3) IDOR WRITE — ahmad must not move money in sara's wallet.
  await runner.apiScenario(
    { id: 'api-authz-idor-write', title: 'IDOR: ahmad cannot write sara wallet', area: 'authz' },
    async ({ http, check }) => {
      const w = await http('POST', '/wallets/' + SARA_WALLET + '/withdraw', { token: ahmad, body: { amount: 10, pin: '1234' } });
      check('ahmad withdraw from sara wallet denied', okReject(w.status), 'status ' + w.status);
      check('ahmad withdraw from sara wallet not 200', w.status !== 200, 'status ' + w.status);
      const d = await http('POST', '/wallets/' + SARA_WALLET + '/deposit', { token: ahmad, body: { amount: 10 } });
      check('ahmad deposit into sara wallet denied', okReject(d.status), 'status ' + d.status);
    }
  );

  // 4) AUTHENTICATION — sensitive endpoints reject anonymous calls.
  await runner.apiScenario(
    { id: 'api-authz-unauth', title: 'Anonymous calls rejected (401)', area: 'authz' },
    async ({ http, check }) => {
      const eps = [
        ['GET', '/auth/me'],
        ['GET', '/wallets'],
        ['GET', '/transactions'],
        ['POST', '/transfer'],
        ['GET', '/cards'],
        ['GET', '/gold/wallet'],
      ];
      for (const [m, p] of eps) {
        const r = await http(m, p);
        check(`${m} ${p} without token is 401`, r.status === 401, 'status ' + r.status);
      }
    }
  );

  // 5) MONEY INVARIANTS — transfer must reject bad amounts / self-transfer.
  await runner.apiScenario(
    { id: 'api-money-transfer-edges', title: 'Transfer rejects negative/zero/self/over-balance', area: 'money' },
    async ({ http, check }) => {
      const neg = await http('POST', '/transfer', { token: ahmad, body: { identifier: 'sara@test.com', amount: -50 } });
      check('negative amount rejected (not 200)', neg.status !== 200, 'status ' + neg.status);
      check('negative amount is 422', neg.status === 422, 'status ' + neg.status);

      const zero = await http('POST', '/transfer', { token: ahmad, body: { identifier: 'sara@test.com', amount: 0 } });
      check('zero amount rejected', zero.status !== 200, 'status ' + zero.status);

      const self = await http('POST', '/transfer', { token: ahmad, body: { identifier: 'ahmad@test.com', amount: 5 } });
      check('self-transfer rejected', self.status !== 200, 'status ' + self.status);

      const over = await http('POST', '/transfer', { token: ahmad, body: { identifier: 'sara@test.com', amount: 99999999 } });
      check('over-balance transfer rejected', over.status !== 200, 'status ' + over.status);
    }
  );

  // 6) MONEY INVARIANTS — gold buy / fx convert reject non-positive amounts.
  await runner.apiScenario(
    { id: 'api-money-amount-edges', title: 'Gold/FX reject non-positive amounts', area: 'money' },
    async ({ http, check }) => {
      const gold = await http('POST', '/gold/buy', { token: ahmad, body: { amount: -1 } });
      check('gold buy negative rejected', gold.status !== 200, 'status ' + gold.status);
      const fx = await http('POST', '/exchange-rates/convert', { token: ahmad, body: { amount: -1, from: 'USD', to: 'SYP' } });
      check('fx convert negative rejected', fx.status !== 200, 'status ' + fx.status);
      const fee = await http('POST', '/fees/calculate', { token: ahmad, body: { code: 'transfer', amount: -1 } });
      check('fee calc negative rejected', fee.status !== 200, 'status ' + fee.status);
    }
  );
};
