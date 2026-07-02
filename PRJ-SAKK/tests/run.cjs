#!/usr/bin/env node
// Entry point.  Usage:  node tests/run.cjs <suite> [suite2 ...]
//   node tests/run.cjs smoke
//   node tests/run.cjs admin api flows
// Each suite module exports  async (runner, cfg) => {}  and calls runner.scenario(...) per operation.
const cfg = require('./lib/config.cjs');
const Runner = require('./lib/runner.cjs');
const { buildReport, buildIndex } = require('./lib/report.cjs');

(async () => {
  const suites = process.argv.slice(2);
  if (!suites.length) { console.error('usage: node tests/run.cjs <suite> [suite2 ...]'); process.exit(2); }

  let grandFail = 0;
  for (const name of suites) {
    let suiteFn;
    try { suiteFn = require('./suites/' + name + '.cjs'); }
    catch (e) { console.error(`! suite "${name}" not found: ${e.message}`); grandFail++; continue; }

    console.log(`\n══════ SUITE: ${name} ══════`);
    const runner = new Runner(name);
    await runner.start();
    try { await suiteFn(runner, cfg); }
    catch (e) { console.error(`suite crashed: ${e.stack || e.message}`); }
    finally { await runner.stop(); }

    const stamp = new Date().toISOString();
    const rep = buildReport(name, runner.results, stamp);
    const fail = rep.fail;
    grandFail += fail;
    console.log(`── ${name}: ${rep.pass} pass / ${fail} fail / ${rep.total} total`);
    console.log(`   report:  ${rep.html}`);
    console.log(`   summary: ${rep.md}`);
  }
  const idx = buildIndex();
  console.log(`\nindex: ${idx}`);
  process.exit(grandFail > 0 ? 1 : 0);
})().catch((e) => { console.error(e); process.exit(2); });
