// FLOW — Registration onboarding: register → user persisted → login → /auth/me matches → cleanup.
const db = require('../lib/db.cjs');
const api = require('../lib/api.cjs');

module.exports = async function flowOnboarding(runner, cfg) {
  const stamp = Date.now();
  const email = `qa.reg.${stamp}@test.com`;
  const password = 'Password123!';

  await runner.apiScenario(
    { id: 'flow-onboarding-register', title: 'Register → persist → login → me', tier: 'flow', area: 'auth' },
    async ({ http, check, must, log }) => {
      const reg = await http('POST', '/auth/register', {
        body: {
          first_name: 'QA', last_name: 'Tester', email, phone: `+9639${String(stamp).slice(-8)}`,
          password, password_confirmation: password, language: 'ar', timezone: 'Asia/Damascus',
        },
      });
      must('register accepted (200/201)', reg.status === 200 || reg.status === 201, 'status ' + reg.status + ' :: ' + (reg.text || '').slice(0, 200));

      const dbCount = db.count('users', `'email','${email}'`);
      check('user row persisted', dbCount === 1, 'rows=' + dbCount);

      // Register auto-issues a token — use it (avoids hammering the throttled /auth/login).
      const newToken = (reg.json && reg.json.data && (reg.json.data.token || reg.json.data.access_token)) || null;
      must('register returns an auth token', !!newToken);

      const me = await http('GET', '/auth/me', { token: newToken });
      check('me is 200', me.status === 200, 'status ' + me.status);
      check('me email matches registration', !!(me.text && me.text.includes(email)), '');
      log(`registered + authenticated ${email}`);

      // cleanup — remove the throwaway user + its wallets
      const uid = parseInt(db.tinker(`echo \\DB::table('users')->where('email','${email}')->value('id') ?? 0;`) || '0', 10);
      if (uid) {
        db.tinker(`\\DB::table('wallets')->where('user_id',${uid})->delete(); \\DB::table('users')->where('id',${uid})->delete(); echo 'cleaned';`);
      }
      check('cleanup removed the test user', db.count('users', `'email','${email}'`) === 0, 'still present');
    }
  );
};
