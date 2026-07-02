// ADMIN DEEP — real mutating admin actions, verified against the DB, then restored.
// Covers: gold price edit (PUT), fee toggle (PATCH), transaction-reverse validation guard.
const db = require('../lib/db.cjs');

module.exports = async function adminDeep(runner, cfg) {
  // 1) GOLD PRICE — edit buy/sell via the real form, confirm persisted + spread recomputed, restore.
  const gp = JSON.parse(db.tinker(`echo json_encode(\\DB::table('gold_prices')->orderBy('id')->first());`) || '{}');
  await runner.scenario(
    { id: 'admin-gold-price-edit', title: 'Edit gold price (UI → DB)', tier: 'admin', area: 'gold', auth: 'admin' },
    async ({ page, step, must, check }) => {
      await step('open /admin/gold/prices', (p) => p.goto(cfg.baseURL + '/admin/gold/prices', { waitUntil: 'networkidle' }));
      must('a gold price row exists', !!gp.id, 'no gold_prices row');
      const newBuy = 91.11, newSell = 90.0;
      await step('fill the first price form', async (p) => {
        const form = p.locator('form:has(input[name="buy_price"])').first();
        await form.locator('input[name="buy_price"]').fill(String(newBuy));
        await form.locator('input[name="sell_price"]').fill(String(newSell));
      });
      await step('submit', async (p) => {
        const form = p.locator('form:has(input[name="buy_price"])').first();
        await Promise.all([p.waitForLoadState('networkidle'), form.locator('button[type="submit"]').first().click()]);
      });
      const after = JSON.parse(db.tinker(`echo json_encode(\\DB::table('gold_prices')->where('id',${gp.id})->first());`) || '{}');
      check('buy_price persisted', parseFloat(after.buy_price) === newBuy, `db=${after.buy_price}`);
      check('sell_price persisted', parseFloat(after.sell_price) === newSell, `db=${after.sell_price}`);
      const expSpread = Math.round((newBuy - newSell) / newSell * 100 * 100) / 100;
      check('spread recomputed by controller', Math.abs(parseFloat(after.spread) - expSpread) < 0.01, `db=${after.spread} exp=${expSpread}`);
      // restore original
      db.tinker(`\\DB::table('gold_prices')->where('id',${gp.id})->update(['buy_price'=>${gp.buy_price},'sell_price'=>${gp.sell_price},'spread'=>${gp.spread}]); echo 'ok';`);
    }
  );

  // 2) FEE TOGGLE — flip is_active via the toggle form, confirm DB flips, restore.
  const fee = JSON.parse(db.tinker(`echo json_encode(\\DB::table('fees')->orderBy('id')->first(['id','code','is_active']));`) || '{}');
  await runner.scenario(
    { id: 'admin-fee-toggle', title: 'Toggle fee active state (UI → DB)', tier: 'admin', area: 'fees', auth: 'admin' },
    async ({ page, step, must, check }) => {
      must('a fee exists', !!fee.code, 'no fees row');
      const before = Number(fee.is_active);
      await step('open /admin/fees', (p) => p.goto(cfg.baseURL + '/admin/fees', { waitUntil: 'networkidle' }));
      await step('click the first toggle', async (p) => {
        const form = p.locator(`form[action*="/fees/${fee.code}/toggle"]`).first();
        await Promise.all([p.waitForLoadState('networkidle'), form.locator('button[type="submit"]').first().click()]);
      });
      const after = Number(db.tinker(`echo \\DB::table('fees')->where('code','${fee.code}')->value('is_active');`) || '0');
      check('is_active flipped in DB', after !== before, `before=${before} after=${after}`);
      // restore
      db.tinker(`\\DB::table('fees')->where('code','${fee.code}')->update(['is_active'=>${before}]); echo 'ok';`);
    }
  );

  // 3) TRANSACTION REVERSE — validation guard (obs: previously a type error on empty reason).
  await runner.scenario(
    { id: 'admin-tx-reverse-validation', title: 'Reverse rejects empty/short reason (422, not 500)', tier: 'admin', area: 'transactions', auth: 'admin' },
    async ({ page, step, check, log }) => {
      // pick a real completed transaction id
      const txId = parseInt(db.tinker(`echo \\DB::table('transactions')->where('status','completed')->value('id');`) || '0', 10);
      await step('open admin transactions', (p) => p.goto(cfg.baseURL + '/admin/transactions', { waitUntil: 'networkidle' }));
      const probe = async (reason) => page.evaluate(async ({ id, reason, base }) => {
        const token = document.querySelector('meta[name="csrf-token"]')?.content
          || document.querySelector('input[name="_token"]')?.value || '';
        const res = await fetch(`${base}/admin/transactions/${id}/reverse`, {
          method: 'POST', redirect: 'manual',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
          body: JSON.stringify({ reason }),
        });
        let body = ''; try { body = (await res.text()).slice(0, 140); } catch (_) {}
        return { status: res.status, type: res.type, body };
      }, { id: txId, reason, base: cfg.baseURL });

      check('a completed tx exists to probe', txId > 0, 'txId=' + txId);
      const s1 = await probe('');
      const rejected = (s) => s.type === 'opaqueredirect' || s.status === 422; // 302-redirect OR JSON 422
      // SAFETY (obs-583 regression guard): empty reason must be rejected, never a 500/type-error.
      check('empty reason rejected (not accepted)', s1.status !== 200, `status ${s1.status} type ${s1.type}`);
      check('empty reason does NOT 500', !(s1.status >= 500), `status ${s1.status}`);
      check('empty reason is a principled reject', rejected(s1), `status ${s1.status} type ${s1.type}`);
      const s2 = await probe('ab'); // < min:3
      check('2-char reason rejected', rejected(s2) && s2.status !== 200, `status ${s2.status} type ${s2.type}`);
      // F-008 (documented in FINDINGS): this AJAX endpoint returns JSON 200 on success but a 302 HTML
      // redirect on validation error — inconsistent contract. Not asserted as a hard fail here.
      if (s1.type === 'opaqueredirect') log('F-008 confirmed: validation error returns 302 redirect, not 422 JSON');
    }
  );
};
