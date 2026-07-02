// FLOW — Virtual card lifecycle: issue → freeze → unfreeze, DB-verified, IDOR-protected, cleaned up.
// Local virtual card (the Stripe path is a separate /cards/stripe/issue route). ahmad (#2), wallet #2.
const db = require('../lib/db.cjs');
const api = require('../lib/api.cjs');

const cardStatus = (id) => db.tinker(`echo \\DB::table('virtual_cards')->where('id',${id})->value('status');`);

module.exports = async function flowCards(runner) {
  const ahmad = (await api.login('user1')).token;
  const sara = (await api.login('user2')).token;
  let cardId = 0;

  await runner.apiScenario(
    { id: 'flow-cards-issue', title: 'Issue a virtual card → persists', tier: 'flow', area: 'cards' },
    async ({ http, check, must, log }) => {
      const before = db.count('virtual_cards', `'user_id',2`);
      const r = await http('POST', '/cards', { token: ahmad, body: { wallet_id: 2, brand: 'visa', nickname: 'QA Card', spending_limit: 1000 } });
      must('issue accepted (200/201)', r.status === 200 || r.status === 201, 'status ' + r.status + ' :: ' + (r.text || '').slice(0, 180));
      const after = db.count('virtual_cards', `'user_id',2`);
      check('one new card row', after === before + 1, `${before}→${after}`);
      const row = JSON.parse(db.tinker(`echo json_encode(\\DB::table('virtual_cards')->where('user_id',2)->orderByDesc('id')->first(['id','status','brand','nickname','wallet_id']));`) || '{}');
      cardId = row.id;
      log(`card id=${row.id} status=${row.status} brand=${row.brand}`);
      check('brand is visa', row.brand === 'visa', 'brand=' + row.brand);
      check('linked to ahmad wallet #2', row.wallet_id === 2, 'wallet=' + row.wallet_id);
    }
  );

  await runner.apiScenario(
    { id: 'flow-cards-freeze-cycle', title: 'Freeze → unfreeze (DB status), IDOR-protected', tier: 'flow', area: 'cards' },
    async ({ http, check, must }) => {
      must('have a card', cardId > 0, 'no cardId');

      // IDOR: sara cannot freeze ahmad's card
      const bad = await http('POST', `/cards/${cardId}/freeze`, { token: sara });
      check('sara cannot freeze ahmad card (403/404)', bad.status === 403 || bad.status === 404, 'status ' + bad.status);
      check('card unaffected by foreign freeze', cardStatus(cardId) !== 'frozen', 'status=' + cardStatus(cardId));

      // owner freeze
      const f = await http('POST', `/cards/${cardId}/freeze`, { token: ahmad, body: { reason: 'qa' } });
      must('owner freeze accepted', f.status === 200, 'status ' + f.status + ' :: ' + (f.text || '').slice(0, 140));
      check('DB status = frozen', cardStatus(cardId) === 'frozen', 'status=' + cardStatus(cardId));

      // owner unfreeze
      const u = await http('POST', `/cards/${cardId}/unfreeze`, { token: ahmad });
      must('owner unfreeze accepted', u.status === 200, 'status ' + u.status);
      check('DB status no longer frozen', cardStatus(cardId) !== 'frozen', 'status=' + cardStatus(cardId));

      // cleanup — soft-delete the test card
      db.tinker(`\\DB::table('virtual_cards')->where('id',${cardId})->delete(); echo 'cleaned';`);
      check('test card cleaned up', db.count('virtual_cards', `'id',${cardId}`) === 0, 'still present');
    }
  );
};
