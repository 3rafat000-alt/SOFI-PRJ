// Aggregates scenario records into a single self-contained HTML report (embedded video per operation,
// step thumbnails, soft/hard checks, console/server errors, trace link) + a markdown summary + json.
const fs = require('fs');
const path = require('path');
const cfg = require('./config.cjs');

const esc = (s) => String(s == null ? '' : s).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
const ms = (n) => (n >= 1000 ? (n / 1000).toFixed(1) + 's' : n + 'ms');
// artifacts paths are stored relative to tests/ root; report lives in tests/reports/ => prefix with ../
const rel = (p) => (p ? '../' + p : null);

function scenarioCard(r) {
  const badge = r.status === 'pass'
    ? '<span class="b pass">PASS</span>'
    : '<span class="b fail">FAIL</span>';
  const warn = r.consoleErrors.length ? `<span class="b warn">${r.consoleErrors.length} console</span>` : '';
  const srv = r.serverErrors.length ? `<span class="b fail">${r.serverErrors.length}×5xx</span>` : '';

  const steps = r.steps.map((s) => {
    if (s.note) return `<li class="note">${esc(s.label)}</li>`;
    const shot = s.shot ? `<a href="${rel(s.shot)}" target="_blank"><img loading="lazy" src="${rel(s.shot)}"></a>` : '';
    const st = s.ok ? '✓' : '✗';
    const cls = s.ok ? 'ok' : 'bad';
    return `<li class="${cls}"><div class="sh">${shot}</div><div class="sl"><b>${st} ${esc(s.label)}</b> <span class="t">${ms(s.ms)}</span>${s.error ? `<div class="err">${esc(s.error)}</div>` : ''}</div></li>`;
  }).join('');

  const checks = r.checks.length
    ? '<table class="ck"><tr><th></th><th>check</th><th>detail</th></tr>' +
      r.checks.map((c) => `<tr class="${c.ok ? 'ok' : 'bad'}"><td>${c.ok ? '✓' : '✗'}${c.hard ? '' : '<sup>soft</sup>'}</td><td>${esc(c.label)}</td><td>${esc(c.detail || '')}</td></tr>`).join('') +
      '</table>'
    : '';

  const probs = [];
  if (r.error) probs.push(`<div class="pe">scenario error: ${esc(r.error)}</div>`);
  r.serverErrors.forEach((e) => probs.push(`<div class="pe">5xx: ${esc(e)}</div>`));
  r.pageErrors.forEach((e) => probs.push(`<div class="pe">pageerror: ${esc(e)}</div>`));
  r.failedRequests.forEach((e) => probs.push(`<div class="pw">reqfail: ${esc(e)}</div>`));
  r.consoleErrors.forEach((e) => probs.push(`<div class="pw">console: ${esc(e)}</div>`));

  const vid = r.video ? `<video controls preload="none" src="${rel(r.video)}"></video>` : '<div class="novid">no video</div>';
  const trace = r.trace ? `<a class="tr" href="${rel(r.trace)}">trace.zip</a>` : '';

  return `<section class="sc ${r.status}">
  <header onclick="this.parentNode.classList.toggle('open')">
    <span class="cid">${esc(r.id)}</span> ${badge}${srv}${warn}
    <span class="tt">${esc(r.title)}</span>
    <span class="meta">${esc(r.tier)}/${esc(r.area)} · ${ms(r.durationMs)} · ${r.steps.filter((s) => !s.note).length} steps · ${r.checks.length} checks ${trace}</span>
  </header>
  <div class="body">
    <div class="vid">${vid}</div>
    <ul class="steps">${steps}</ul>
    ${checks}
    ${probs.length ? `<div class="probs">${probs.join('')}</div>` : ''}
  </div>
</section>`;
}

function buildReport(suite, results, stampISO) {
  const total = results.length;
  const pass = results.filter((r) => r.status === 'pass').length;
  const fail = total - pass;
  const warnN = results.filter((r) => r.consoleErrors.length).length;
  const dur = results.reduce((a, r) => a + r.durationMs, 0);

  const html = `<!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>${esc(suite)} — Carda QA Report</title>
<style>
:root{--bg:#0e1116;--card:#161b22;--mut:#8b949e;--line:#222a35;--ok:#2ea043;--bad:#f85149;--warn:#d29922;--ac:#58a6ff}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:#e6edf3;font:14px/1.5 -apple-system,Segoe UI,Tahoma,sans-serif}
.wrap{max-width:1200px;margin:0 auto;padding:24px}
h1{margin:0 0 4px;font-size:22px}.sub{color:var(--mut);margin-bottom:18px}
.kpis{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:22px}
.kpi{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:14px 18px;min-width:120px}
.kpi b{display:block;font-size:26px}.kpi.p b{color:var(--ok)}.kpi.f b{color:var(--bad)}.kpi.w b{color:var(--warn)}
.kpi span{color:var(--mut);font-size:12px}
.sc{background:var(--card);border:1px solid var(--line);border-radius:12px;margin:10px 0;overflow:hidden}
.sc.fail{border-color:#5a2326}.sc.pass{border-color:#1f3d29}
.sc header{display:flex;gap:8px;align-items:center;padding:12px 16px;cursor:pointer;flex-wrap:wrap}
.sc header:hover{background:#1b222c}
.cid{font-family:ui-monospace,monospace;color:var(--mut);font-size:12px}
.tt{font-weight:600}.meta{color:var(--mut);font-size:12px;margin-inline-start:auto}
.b{font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px}
.b.pass{background:#12381f;color:#3fb950}.b.fail{background:#3d1518;color:#ff7b72}.b.warn{background:#3a2d0c;color:#e3b341}
.body{display:none;padding:0 16px 16px;border-top:1px solid var(--line)}
.sc.open .body{display:block}
.vid{margin:12px 0}.vid video{width:100%;max-width:760px;border-radius:8px;background:#000}
.novid{color:var(--mut)}
ul.steps{list-style:none;margin:0;padding:0;display:grid;grid-template-columns:1fr;gap:6px}
ul.steps li{display:flex;gap:12px;align-items:flex-start;padding:6px;border-radius:8px;background:#0f141b}
ul.steps li.bad{background:#1f1416}ul.steps li.note{background:transparent;color:var(--mut);font-size:12px;padding:2px 6px}
.sh img{height:64px;border-radius:6px;border:1px solid var(--line)}
.sl .t{color:var(--mut);font-size:11px;margin-inline-start:6px}
.err{color:#ff7b72;font-family:ui-monospace,monospace;font-size:12px;margin-top:4px}
table.ck{width:100%;border-collapse:collapse;margin-top:12px;font-size:13px}
table.ck th{color:var(--mut);text-align:start;font-weight:500;border-bottom:1px solid var(--line);padding:4px}
table.ck td{padding:4px;border-bottom:1px solid #1a2029}
table.ck tr.bad td{color:#ff7b72}table.ck tr.ok td:first-child{color:#3fb950}
table.ck sup{color:var(--mut)}
.probs{margin-top:12px;font-family:ui-monospace,monospace;font-size:12px}
.pe{color:#ff7b72;padding:3px 8px;background:#1f1416;border-radius:6px;margin:3px 0}
.pw{color:#e3b341;padding:3px 8px;background:#211c0c;border-radius:6px;margin:3px 0}
.tr{color:var(--ac);margin-inline-start:8px;font-size:11px}
.flt{margin:0 0 12px}.flt button{background:var(--card);border:1px solid var(--line);color:#e6edf3;border-radius:20px;padding:6px 14px;cursor:pointer;margin-inline-end:6px}
.flt button.on{border-color:var(--ac);color:var(--ac)}
</style></head><body><div class="wrap">
<h1>Carda QA — <span style="color:var(--ac)">${esc(suite)}</span></h1>
<div class="sub">${esc(stampISO || '')} · base ${esc(cfg.baseURL)} · locale ${esc(cfg.locale)}</div>
<div class="kpis">
  <div class="kpi"><b>${total}</b><span>scenarios</span></div>
  <div class="kpi p"><b>${pass}</b><span>passed</span></div>
  <div class="kpi f"><b>${fail}</b><span>failed</span></div>
  <div class="kpi w"><b>${warnN}</b><span>console warns</span></div>
  <div class="kpi"><b>${ms(dur)}</b><span>total time</span></div>
</div>
<div class="flt">
  <button class="on" data-f="all">all</button>
  <button data-f="fail">fail only</button>
  <button data-f="pass">pass only</button>
  <button onclick="document.querySelectorAll('.sc').forEach(s=>s.classList.add('open'))">expand all</button>
</div>
${results.map(scenarioCard).join('\n')}
</div>
<script>
document.querySelectorAll('.flt button[data-f]').forEach(b=>b.onclick=()=>{
  document.querySelectorAll('.flt button[data-f]').forEach(x=>x.classList.remove('on'));b.classList.add('on');
  const f=b.dataset.f;document.querySelectorAll('.sc').forEach(s=>{s.style.display=(f==='all'||s.classList.contains(f))?'':'none';});
});
// auto-open failures
document.querySelectorAll('.sc.fail').forEach(s=>s.classList.add('open'));
</script></body></html>`;

  ensureReports();
  const htmlPath = path.join(cfg.paths.reports, `${suite}.html`);
  const jsonPath = path.join(cfg.paths.reports, `${suite}.json`);
  const mdPath = path.join(cfg.paths.reports, `${suite}.md`);
  fs.writeFileSync(htmlPath, html);
  fs.writeFileSync(jsonPath, JSON.stringify({ suite, stampISO, total, pass, fail, results }, null, 2));
  fs.writeFileSync(mdPath, markdown(suite, results, { total, pass, fail, dur, stampISO }));

  return { html: htmlPath, json: jsonPath, md: mdPath, total, pass, fail };
}

function markdown(suite, results, s) {
  const lines = [];
  lines.push(`# Carda QA — ${suite}`);
  lines.push(`${s.stampISO || ''} · ${s.pass}/${s.total} pass · ${s.fail} fail · ${(s.dur / 1000).toFixed(1)}s\n`);
  lines.push('| status | id | title | steps | checks | issues |');
  lines.push('|---|---|---|---|---|---|');
  for (const r of results) {
    const issues = [];
    if (r.error) issues.push('err');
    if (r.serverErrors.length) issues.push(`${r.serverErrors.length}×5xx`);
    if (r.checks.filter((c) => !c.ok).length) issues.push(`${r.checks.filter((c) => !c.ok).length} bad-check`);
    if (r.consoleErrors.length) issues.push(`${r.consoleErrors.length} console`);
    lines.push(`| ${r.status === 'pass' ? '✅' : '❌'} | \`${r.id}\` | ${r.title} | ${r.steps.filter((x) => !x.note).length} | ${r.checks.length} | ${issues.join(', ') || '—'} |`);
  }
  return lines.join('\n') + '\n';
}

function ensureReports() { fs.mkdirSync(cfg.paths.reports, { recursive: true }); }

// Aggregate every reports/<suite>.json into a single index.html landing page.
function buildIndex() {
  ensureReports();
  const files = fs.readdirSync(cfg.paths.reports).filter((f) => f.endsWith('.json'));
  const suites = files.map((f) => {
    try { return JSON.parse(fs.readFileSync(path.join(cfg.paths.reports, f), 'utf8')); } catch (_) { return null; }
  }).filter(Boolean).sort((a, b) => a.suite.localeCompare(b.suite));

  const tPass = suites.reduce((a, s) => a + s.pass, 0);
  const tTotal = suites.reduce((a, s) => a + s.total, 0);
  const rows = suites.map((s) => {
    const ok = s.fail === 0;
    return `<tr class="${ok ? 'ok' : 'bad'}">
      <td><a href="${esc(s.suite)}.html">${esc(s.suite)}</a></td>
      <td>${s.pass}/${s.total}</td>
      <td>${s.fail ? '<b>' + s.fail + ' fail</b>' : 'green'}</td>
      <td>${esc((s.stampISO || '').replace('T', ' ').slice(0, 19))}</td>
    </tr>`;
  }).join('');

  const html = `<!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8">
<title>Carda QA — all suites</title><style>
body{margin:0;background:#0e1116;color:#e6edf3;font:15px/1.6 -apple-system,Segoe UI,Tahoma,sans-serif}
.w{max-width:860px;margin:0 auto;padding:32px}h1{margin:0 0 2px}.s{color:#8b949e;margin-bottom:24px}
table{width:100%;border-collapse:collapse;background:#161b22;border:1px solid #222a35;border-radius:12px;overflow:hidden}
th,td{padding:12px 16px;text-align:start;border-bottom:1px solid #222a35}th{color:#8b949e;font-weight:600}
tr.bad td b{color:#ff7b72}tr.ok td:nth-child(3){color:#3fb950}
a{color:#58a6ff;font-weight:700;text-decoration:none}a:hover{text-decoration:underline}
.k{display:inline-block;background:#161b22;border:1px solid #222a35;border-radius:10px;padding:10px 16px;margin-inline-end:10px}
.k b{font-size:22px}.k.p b{color:#3fb950}
</style></head><body><div class="w">
<h1>Carda Wallet — QA</h1><div class="s">all suites · ${esc(cfg.baseURL)}</div>
<div style="margin-bottom:20px">
  <span class="k"><b>${suites.length}</b> suites</span>
  <span class="k p"><b>${tPass}/${tTotal}</b> scenarios pass</span>
</div>
<table><tr><th>suite</th><th>pass</th><th>status</th><th>last run</th></tr>${rows}</table>
<p class="s" style="margin-top:20px">Master report: <a href="../REPORT.md">REPORT.md</a> · Findings: <a href="../FINDINGS.md">FINDINGS.md</a></p>
</div></body></html>`;
  const out = path.join(cfg.paths.reports, 'index.html');
  fs.writeFileSync(out, html);
  return out;
}

module.exports = { buildReport, buildIndex };
