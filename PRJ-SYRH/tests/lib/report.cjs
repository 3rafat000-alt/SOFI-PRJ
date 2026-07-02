/**
 * Report Builder — generates HTML + Markdown per-suite reports.
 * Embeds screenshots, video references, check tables.
 */
const cfg = require('../config.cjs');
const path = require('path');
const fs = require('fs');

function build(runner, suiteName) {
  const results = runner.results;
  const summary = runner.summary();

  // ── Markdown report ──
  let md = `# ${suiteName} — Test Report\n\n`;
  md += `**${summary.passed}/${summary.total} scenarios passed** | `;
  md += `${summary.passChecks}/${summary.totalChecks} checks | `;
  md += `Duration: ${(summary.duration / 1000).toFixed(1)}s\n\n`;
  if (summary.consoleErrors > 0) md += `⚠️ ${summary.consoleErrors} console errors\n`;
  if (summary.networkErrors > 0) md += `⚠️ ${summary.networkErrors} network errors\n`;
  md += '\n';

  for (const r of results) {
    const icon = r.passed ? '✅' : '❌';
    md += `## ${icon} ${r.title} (\`${r.scenarioId}\`)\n\n`;
    md += `Duration: ${(r.duration / 1000).toFixed(1)}s | `;
    md += `Checks: ${r.checks.pass}/${r.checks.pass + r.checks.fail} passed\n\n`;

    if (r.error) md += `**Error:** ${r.error}\n\n`;

    // Steps
    if (r.steps && r.steps.length > 0) {
      md += '### Steps\n\n';
      for (const s of r.steps) {
        const sIcon = s.status === 'pass' ? '✅' : s.status === 'fail' ? '❌' : '⏳';
        md += `- ${sIcon} ${s.name}`;
        if (s.duration > 0) md += ` _(${(s.duration / 1000).toFixed(1)}s)_`;
        if (s.error) md += ` — ${s.error}`;
        md += '\n';
      }
      md += '\n';
    }

    // Screenshots
    if (r.screenshots && r.screenshots.length > 0) {
      md += '### Screenshots\n\n';
      for (const shot of r.screenshots) {
        md += `- [${shot.label}](${shot.file})\n`;
      }
      md += '\n';
    }

    // Console errors
    if (r.consoleErrors && r.consoleErrors.length > 0) {
      md += '### Console Errors\n\n';
      for (const e of r.consoleErrors) md += `- \`${e}\`\n`;
      md += '\n';
    }

    // Network errors
    if (r.networkErrors && r.networkErrors.length > 0) {
      md += '### Network Errors\n\n';
      for (const e of r.networkErrors) md += `- \`${e.url}\` — ${e.failure}\n`;
      md += '\n';
    }

    md += '---\n\n';
  }

  // Write markdown
  const mdPath = path.join(cfg.paths.reports, `${suiteName}.md`);
  fs.writeFileSync(mdPath, md);
  console.log(`📄 Report: ${mdPath}`);

  // ── HTML report ──
  let html = `<!DOCTYPE html><html dir="rtl" lang="ar"><head>
<meta charset="UTF-8"><title>${suiteName} — SYRH Tests</title>
<style>
  body { font-family: system-ui, sans-serif; max-width: 960px; margin: 2rem auto; padding: 0 1rem; background: #f8f5f0; color: #1c1917; }
  h1 { color: #8B5E3C; border-bottom: 2px solid #D4A574; padding-bottom: 0.5rem; }
  .summary { background: white; border-radius: 16px; padding: 1.5rem; box-shadow: 0 1px 4px rgba(0,0,0,0.06); margin-bottom: 2rem; }
  .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1rem; text-align: center; }
  .stat { background: #f8f5f0; border-radius: 12px; padding: 1rem; }
  .stat .num { font-size: 1.8rem; font-weight: 700; }
  .stat .label { font-size: 0.75rem; color: #78716c; }
  .pass { color: #059669; } .fail { color: #dc2626; }
  .scenario { background: white; border-radius: 16px; padding: 1.5rem; margin-bottom: 1rem; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
  .scenario.pass { border-right: 4px solid #059669; }
  .scenario.fail { border-right: 4px solid #dc2626; }
  .scenario h2 { margin: 0 0 0.5rem; font-size: 1.1rem; }
  .meta { font-size: 0.8rem; color: #78716c; margin-bottom: 0.75rem; }
  .steps { list-style: none; padding: 0; margin: 0; }
  .steps li { padding: 0.3rem 0; font-size: 0.9rem; }
  .steps .error { color: #dc2626; font-size: 0.8rem; }
  .shots { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.75rem; }
  .shots a { font-size: 0.75rem; color: #8B5E3C; background: #f8f5f0; padding: 0.25rem 0.5rem; border-radius: 6px; text-decoration: none; }
  .shots a:hover { background: #D4A574; color: white; }
  .console-err { background: #fef2f2; border-radius: 8px; padding: 0.5rem; margin-top: 0.5rem; font-size: 0.75rem; color: #991b1b; max-height: 100px; overflow-y: auto; }
  .badge { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.65rem; font-weight: 600; }
  .badge.pass { background: #ecfdf5; color: #059669; }
  .badge.fail { background: #fef2f2; color: #dc2626; }
</style></head><body>
<h1>${suiteName}</h1>
<div class="summary">
  <div class="summary-grid">
    <div class="stat"><div class="num ${summary.failed > 0 ? 'fail' : 'pass'}">${summary.passed}/${summary.total}</div><div class="label">السيناريوهات</div></div>
    <div class="stat"><div class="num pass">${summary.passChecks}</div><div class="label">فحوصات ناجحة</div></div>
    <div class="stat"><div class="num ${summary.failChecks > 0 ? 'fail' : 'pass'}">${summary.failChecks}</div><div class="label">فحوصات فاشلة</div></div>
    <div class="stat"><div class="num">${(summary.duration / 1000).toFixed(1)}s</div><div class="label">المدة</div></div>
    <div class="stat"><div class="num ${summary.consoleErrors > 0 ? 'fail' : 'pass'}">${summary.consoleErrors}</div><div class="label">أخطاء الكونسول</div></div>
  </div>
</div>`;

  for (const r of results) {
    html += `<div class="scenario ${r.passed ? 'pass' : 'fail'}">`;
    html += `<h2>${r.passed ? '✅' : '❌'} ${r.title}</h2>`;
    html += `<div class="meta"><span class="badge ${r.passed ? 'pass' : 'fail'}">${r.passed ? 'نجاح' : 'فشل'}</span> `;
    html += `(${(r.duration / 1000).toFixed(1)}s) | ${r.checks.pass}/${r.checks.pass + r.checks.fail} فحص</div>`;

    if (r.error) html += `<div class="console-err">${r.error}</div>`;

    if (r.steps && r.steps.length > 0) {
      html += '<ul class="steps">';
      for (const s of r.steps) {
        const sIcon = s.status === 'pass' ? '✅' : s.status === 'fail' ? '❌' : '⏳';
        html += `<li>${sIcon} ${s.name}`;
        if (s.duration > 0) html += ` <span class="meta">(${(s.duration / 1000).toFixed(1)}s)</span>`;
        if (s.error) html += `<div class="error">${s.error}</div>`;
        html += '</li>';
      }
      html += '</ul>';
    }

    if (r.screenshots && r.screenshots.length > 0) {
      html += '<div class="shots">';
      for (const shot of r.screenshots) {
        html += `<a href="../artifacts/shots/${shot.file}" target="_blank">📷 ${shot.label}</a>`;
      }
      html += '</div>';
    }

    if (r.consoleErrors && r.consoleErrors.length > 0) {
      html += `<div class="console-err"><strong>Console:</strong> ${r.consoleErrors.join('; ')}</div>`;
    }

    html += '</div>';
  }

  html += '</body></html>';

  const htmlPath = path.join(cfg.paths.reports, `${suiteName}.html`);
  fs.writeFileSync(htmlPath, html);
  console.log(`📄 HTML:   ${htmlPath}`);

  return { mdPath, htmlPath };
}

module.exports = { build };
