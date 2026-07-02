// FLOW — Payment request lifecycle: create → appears in list + DB (pending) → IDOR-protected → cancel.
// ahmad (#2) is the requester; sara (#3) must not be able to cancel his request.
const db = require('../lib/db.cjs');
const api = require('../lib/api.cjs');

module.exports = async function flowPaymentRequest(runner) {
  const ahmad = (await api.login('user1')).token;
  const sara = (await api.login('user2')).token;

  let reqId = 0, routeKey = '';

  await runner.apiScenario(
    { id: 'flow-payreq-create', title: 'Create payment request → persists pending', tier: 'flow', area: 'payment-request' },
    async ({ http, check, must, log }) => {
      const before = db.count('payment_requests', `'user_id',2`);
      const r = await http('POST', '/payment-requests', { token: ahmad, body: { amount: 25, currency: 'USD', note: 'qa flow' } });
      must('create accepted (200/201)', r.status === 200 || r.status === 201, 'status ' + r.status + ' :: ' + (r.text || '').slice(0, 160));

      const after = db.count('payment_requests', `'user_id',2`);
      check('one new payment_request row', after === before + 1, `${before}→${after}`);
      // capture the new row's keys for the cancel route (model binding may be id or uuid)
      const row = JSON.parse(db.tinker(`echo json_encode(\\DB::table('payment_requests')->where('user_id',2)->orderByDesc('id')->first(['id','uuid','status','amount']));`) || '{}');
      reqId = row.id; routeKey = row.uuid || row.id;
      log(`created request id=${row.id} uuid=${row.uuid} status=${row.status} amount=${row.amount}`);
      check('new request status is pending', row.status === 'pending', 'status=' + row.status);
      check('amount stored as 25', parseFloat(row.amount) === 25, 'amount=' + row.amount);

      const list = await http('GET', '/payment-requests', { token: ahmad });
      check('request appears in owner list', list.status === 200 && /qa flow|"amount":25|25/.test(list.text || ''), 'status ' + list.status);
    }
  );

  await runner.apiScenario(
    { id: 'flow-payreq-authz-cancel', title: 'Only owner can cancel; owner cancel works', tier: 'flow', area: 'payment-request' },
    async ({ http, check, must }) => {
      must('have a request to act on', !!routeKey, 'no routeKey from create step');
      // IDOR: sara must NOT cancel ahmad's request
      const bad = await http('POST', `/payment-requests/${routeKey}/cancel`, { token: sara });
      check('sara cannot cancel ahmad request (403/404)', bad.status === 403 || bad.status === 404, 'status ' + bad.status);
      const stillPending = db.tinker(`echo \\DB::table('payment_requests')->where('id',${reqId})->value('status');`);
      check('request still pending after foreign cancel attempt', stillPending === 'pending', 'status=' + stillPending);

      // owner cancels
      const ok = await http('POST', `/payment-requests/${routeKey}/cancel`, { token: ahmad });
      must('owner cancel accepted (200)', ok.status === 200, 'status ' + ok.status + ' :: ' + (ok.text || '').slice(0, 140));
      const finalStatus = db.tinker(`echo \\DB::table('payment_requests')->where('id',${reqId})->value('status');`);
      check('request status flipped to cancelled', finalStatus === 'cancelled', 'status=' + finalStatus);
    }
  );
};
