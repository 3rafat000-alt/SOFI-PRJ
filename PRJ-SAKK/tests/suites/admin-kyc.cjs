// ADMIN DEEP — KYC Levels (F-002 verification + full CRUD interaction).
// Drives the rebuilt admin.kyc.levels view exactly like a human: open, create, confirm, edit, delete.
const api = require('../lib/api.cjs');

module.exports = async function adminKyc(runner, cfg) {
  // 1) Page renders with seeded levels (this is the F-002 regression guard).
  await runner.scenario(
    { id: 'kyc-levels-render', title: 'KYC Levels page renders with seeded levels', tier: 'admin', area: 'kyc', auth: 'admin' },
    async ({ page, step, must, check }) => {
      let resp;
      await step('open /admin/kyc/levels', async (p) => { resp = await p.goto(cfg.baseURL + '/admin/kyc/levels', { waitUntil: 'networkidle' }); });
      must('http 200 (not 404/500)', resp && resp.status() === 200, 'status ' + (resp && resp.status()));
      const body = (await page.locator('body').innerText().catch(() => '')) || '';
      check('no error trace', !/Whoops|SQLSTATE|not found|Exception/i.test(body), '');
      must('heading present', /مستويات KYC/.test(body), '');
      const cards = await page.locator('.card').count();
      check('shows level cards (>=3 seeded)', cards >= 3, cards + ' cards');
      check('renders a numeric limit', /\d{1,3}(,\d{3})*\.\d{2}/.test(body), '');
    }
  );

  // 2) Create a new level via the UI, confirm it persists + reflects.
  const NEW_LEVEL = 7;
  await runner.scenario(
    { id: 'kyc-levels-create', title: 'Create KYC level via UI', tier: 'admin', area: 'kyc', auth: 'admin' },
    async ({ page, step, must, check }) => {
      await step('open page', (p) => p.goto(cfg.baseURL + '/admin/kyc/levels', { waitUntil: 'networkidle' }));
      await step('click add level', async (p) => { await p.click('button:has-text("إضافة مستوى")'); });
      await step('fill create form', async (p) => {
        await p.fill('input[name="level"]', String(NEW_LEVEL));
        await p.fill('input[name="name_ar"]', 'مستوى اختبار آلي');
        await p.fill('input[name="name"]', 'QA Auto Level');
        await p.fill('input[name="daily_limit"]', '1500');
        await p.fill('input[name="monthly_limit"]', '30000');
        await p.fill('input[name="single_transaction_limit"]', '750');
        await p.fill('input[name="withdrawal_limit"]', '500');
      });
      await step('submit', async (p) => {
        await Promise.all([
          p.waitForLoadState('networkidle'),
          p.click('button:has-text("إنشاء المستوى")'),
        ]);
      });
      const body = (await page.locator('body').innerText()) || '';
      check('new level name shows after redirect', /مستوى اختبار آلي/.test(body), '');
      // cross-check the DB via API-less truth: re-query page count grew is implicit; assert no error
      must('no server error after create', !/Whoops|SQLSTATE/i.test(body), '');
    }
  );

  // 3) Edit the level just created.
  await runner.scenario(
    { id: 'kyc-levels-edit', title: 'Edit KYC level via UI', tier: 'admin', area: 'kyc', auth: 'admin' },
    async ({ page, step, must, check }) => {
      await step('open page', (p) => p.goto(cfg.baseURL + '/admin/kyc/levels', { waitUntil: 'networkidle' }));
      // find the card containing our level and click its edit button
      await step('open edit on QA level', async (p) => {
        const card = p.locator('.card', { hasText: 'مستوى اختبار آلي' }).first();
        await card.locator('button:has(.material-icons)').first().click();
      });
      await step('change daily limit', async (p) => {
        const card = p.locator('.card', { hasText: 'مستوى اختبار آلي' }).first();
        await card.locator('input[name="daily_limit"]').fill('2222');
      });
      await step('save', async (p) => {
        const card = p.locator('.card', { hasText: 'مستوى اختبار آلي' }).first();
        await Promise.all([
          p.waitForLoadState('networkidle'),
          card.locator('button:has-text("حفظ التعديلات")').click(),
        ]);
      });
      const body = (await page.locator('body').innerText()) || '';
      check('updated limit 2,222.00 visible', /2,222\.00/.test(body), '');
    }
  );

  // 4) Delete it — clean up + verify destroy path.
  await runner.scenario(
    { id: 'kyc-levels-delete', title: 'Delete KYC level via UI', tier: 'admin', area: 'kyc', auth: 'admin' },
    async ({ page, step, must, check }) => {
      await step('open page', (p) => p.goto(cfg.baseURL + '/admin/kyc/levels', { waitUntil: 'networkidle' }));
      await step('accept confirm + delete', async (p) => {
        p.once('dialog', (d) => d.accept());
        const card = p.locator('.card', { hasText: 'مستوى اختبار آلي' }).first();
        await Promise.all([
          p.waitForLoadState('networkidle'),
          card.locator('button:has-text("حذف")').click(),
        ]);
      });
      const body = (await page.locator('body').innerText()) || '';
      check('QA level gone after delete', !/مستوى اختبار آلي/.test(body), 'still present');
    }
  );
};
