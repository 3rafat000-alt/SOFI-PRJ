// BASELINE SMOKE — proves the harness end-to-end and gives a fast health picture of all three tiers.
//  · admin: log in, open every section, assert no 5xx / no page crash / heading rendered
//  · api:   health of the core public + authed endpoints
// Each scenario records its own video + step screenshots + checks.
const api = require('../lib/api.cjs');

// Real admin landing pages (GET, no params) — from `php artisan route:list`.
const ADMIN_PAGES = [
  ['dashboard', '/admin'],
  ['users', '/admin/users'],
  ['transactions', '/admin/transactions'],
  ['cards', '/admin/cards'],
  ['gold-prices', '/admin/gold/prices'],
  ['gold-transactions', '/admin/gold/transactions'],
  ['fees', '/admin/fees'],
  ['kyc', '/admin/kyc'],
  ['kyc-levels', '/admin/kyc/levels'],
  ['agents', '/admin/agents'],
  ['agents-create', '/admin/agents/create'],
  ['merchants', '/admin/merchants'],
  ['merchants-create', '/admin/merchants/create'],
  ['integrations', '/admin/integrations'],
  ['settings', '/admin/settings'],
  ['system-channels', '/admin/system/channels'],
  ['system-maintenance', '/admin/system/maintenance'],
  ['system-messages', '/admin/system/messages'],
  ['system-third-party', '/admin/system/third-party'],
];

module.exports = async function smoke(runner, cfg) {
  // ── TIER 1: ADMIN AUTH ────────────────────────────────────────────────
  await runner.scenario(
    { id: 'smoke-admin-login', title: 'Admin login flow', tier: 'admin', area: 'auth' },
    async ({ page, step, must, check }) => {
      await step('open /admin/login', (p) => p.goto(cfg.baseURL + '/admin/login', { waitUntil: 'networkidle' }));
      check('login form present', await page.locator('input[name="email"]').count() > 0);
      await step('type credentials', async (p) => {
        await p.fill('input[name="email"]', cfg.creds.admin.email);
        await p.fill('input[name="password"]', cfg.creds.admin.password);
      });
      await step('submit', async (p) => {
        await Promise.all([
          p.waitForLoadState('networkidle'),
          p.click('#loginForm button[type="submit"], button[type="submit"]'),
        ]);
      });
      must('redirected off login page', !/\/admin\/login\b/.test(page.url()), page.url());
      check('dashboard reachable', /\/admin/.test(page.url()), page.url());
    }
  );

  // ── TIER 1: ADMIN PAGES (one scenario each, pre-authenticated) ─────────
  for (const [name, url] of ADMIN_PAGES) {
    await runner.scenario(
      { id: 'smoke-admin-' + name, title: 'Admin page: ' + name, tier: 'admin', area: 'page', auth: 'admin' },
      async ({ page, step, must, check }) => {
        let resp;
        await step('navigate ' + url, async (p) => { resp = await p.goto(cfg.baseURL + url, { waitUntil: 'networkidle' }); });
        must('http < 400', resp && resp.status() < 400, 'status ' + (resp && resp.status()));
        check('not redirected to login', !/\/admin\/login\b/.test(page.url()), page.url());
        const bodyText = (await page.locator('body').innerText().catch(() => '')) || '';
        check('page has visible content', bodyText.trim().length > 40, bodyText.length + ' chars');
        check('no Laravel error trace', !/Whoops|Stack trace|SQLSTATE|Exception/i.test(bodyText), '');
        await step('scroll to bottom', async (p) => { await p.evaluate(() => window.scrollTo(0, document.body.scrollHeight)); });
      }
    );
  }

  // ── TIER 2: API HEALTH ─────────────────────────────────────────────────
  await runner.scenario(
    { id: 'smoke-api-health', title: 'API health: public + authed core', tier: 'api', area: 'health' },
    async ({ log, check, must }) => {
      // public
      const types = await api.req('GET', '/transactions/types');
      check('GET /transactions/types is 200', types.status === 200, 'got ' + types.status);
      const cats = await api.req('GET', '/transactions/categories');
      check('GET /transactions/categories is 200', cats.status === 200, 'got ' + cats.status);

      // auth
      const { token, res } = await api.login('user1');
      must('login returns 200', res.status === 200, 'got ' + res.status);
      must('login returns a token', !!token, token ? 'ok' : 'no token field in payload');
      log('token acquired for ahmad@test.com');

      const me = await api.req('GET', '/auth/me', { token });
      check('GET /auth/me authed is 200', me.status === 200, 'got ' + me.status);
      check('me payload has email', !!(me.json && JSON.stringify(me.json).includes('ahmad@test.com')), '');

      // authz negative — no token must be rejected
      const noAuth = await api.req('GET', '/auth/me');
      check('GET /auth/me WITHOUT token is 401', noAuth.status === 401, 'got ' + noAuth.status);
    }
  );
};
