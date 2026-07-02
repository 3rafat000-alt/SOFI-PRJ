// FLOW — Gold buy & sell, verified against the DB.
//   buy: debits USD wallet by grams*buy_price*1.01 (1% fee), credits gold_wallets.balance_grams
//   sell: reverse
// ahmad (#2), USD wallet #2, pin 1234.
const db = require('../lib/db.cjs');
const api = require('../lib/api.cjs');

const USD = 2;
const KARAT = '24';
const GRAMS = 1;

const goldGrams = () => parseFloat(db.tinker(`echo \\DB::table('gold_wallets')->where('user_id',2)->value('balance_grams') ?? 0;`) || '0');

module.exports = async function flowGold(runner) {
  // setup: known pin + funded USD wallet
  db.tinker(`$u=\\App\\Models\\User::find(2);$u->pin_code=\\Hash::make('1234');$u->save();echo 'pin';`);
  db.setWalletBalance(USD, 5000);
  const buyPrice = parseFloat(db.tinker(`echo \\DB::table('gold_prices')->where('karat','${KARAT}')->where('is_active',1)->value('buy_price');`) || '0');
  const sellPrice = parseFloat(db.tinker(`echo \\DB::table('gold_prices')->where('karat','${KARAT}')->where('is_active',1)->value('sell_price');`) || '0');

  // BUY
  await runner.apiScenario(
    { id: 'flow-gold-buy', title: 'Buy gold (USD debit → grams credit)', tier: 'flow', area: 'gold' },
    async ({ http, check, must, log }) => {
      must('buy_price loaded', buyPrice > 0, 'price=' + buyPrice);
      const usdBefore = db.walletBalance(USD);
      const gramsBefore = goldGrams();
      const { token } = await api.login('user1');
      log(`before: usd=${usdBefore} grams=${gramsBefore} buy_price=${buyPrice}`);

      const r = await http('POST', '/gold/buy', { token, body: { karat: KARAT, grams: GRAMS, pin: '1234' } });
      must('buy accepted (200)', r.status === 200, 'status ' + r.status + ' :: ' + (r.text || '').slice(0, 160));

      const usdAfter = db.walletBalance(USD);
      const gramsAfter = goldGrams();
      const expectCost = +(GRAMS * buyPrice * 1.01).toFixed(2); // +1% fee
      log(`after: usd=${usdAfter} grams=${gramsAfter}`);
      check('gold grams credited by ' + GRAMS, +(gramsAfter - gramsBefore).toFixed(4) === GRAMS, `Δ ${(gramsAfter - gramsBefore).toFixed(4)}`);
      check('USD debited by cost+1% fee', Math.abs((usdBefore - usdAfter) - expectCost) < 0.02, `Δ ${(usdBefore - usdAfter).toFixed(2)} expect ${expectCost}`);
    }
  );

  // SELL (back)
  await runner.apiScenario(
    { id: 'flow-gold-sell', title: 'Sell gold (grams debit → USD credit)', tier: 'flow', area: 'gold' },
    async ({ http, check, must, log }) => {
      must('sell_price loaded', sellPrice > 0, 'price=' + sellPrice);
      const usdBefore = db.walletBalance(USD);
      const gramsBefore = goldGrams();
      const { token } = await api.login('user1');
      const r = await http('POST', '/gold/sell', { token, body: { karat: KARAT, grams: GRAMS, pin: '1234' } });
      must('sell accepted (200)', r.status === 200, 'status ' + r.status + ' :: ' + (r.text || '').slice(0, 160));

      const usdAfter = db.walletBalance(USD);
      const gramsAfter = goldGrams();
      log(`after: usd=${usdAfter} grams=${gramsAfter}`);
      check('gold grams debited by ' + GRAMS, +(gramsBefore - gramsAfter).toFixed(4) === GRAMS, `Δ ${(gramsBefore - gramsAfter).toFixed(4)}`);
      check('USD credited from the sale', usdAfter > usdBefore, `Δ ${(usdAfter - usdBefore).toFixed(2)}`);
    }
  );
};
