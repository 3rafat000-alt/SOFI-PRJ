#!/usr/bin/env node
/**
 * SYRH Test Runner — "الدقيق البشر" human-speed E2E runner.
 * Usage:
 *   node tests/index.cjs                         # All suites
 *   node tests/index.cjs auth                     # Single suite
 *   node tests/index.cjs auth,landing             # Multiple suites
 *   CI=1 node tests/index.cjs                     # Fast mode (no human delays)
 *   AUTH=1 node tests/index.cjs                   # Only auth-required suites
 *   HEADLESS=false node tests/index.cjs           # Visible browser
 *
 * Produces JUnit XML + HTML report in tests/reports/.
 * Screenshots in tests/artifacts/shots/.
 */
const path = require('path');
const fs = require('fs');
const cfg = require('./config.cjs');

// ── Parse CLI ──────────────────────────────────────────────
const SUITE_FILTER = (process.argv[2] || '').split(',').filter(Boolean);
const AUTH_ONLY = process.env.AUTH === '1';
const HEADLESS = process.env.HEADLESS !== 'false';

// ── Load suites dynamically ────────────────────────────────
const SUITES_DIR = path.join(__dirname, 'suites');
const allSuites = fs.readdirSync(SUITES_DIR)
  .filter(f => f.endsWith('.cjs') && !f.startsWith('_'))
  .map(f => f.replace('.cjs', ''));

const suitesToRun = SUITE_FILTER.length > 0
  ? allSuites.filter(s => SUITE_FILTER.some(f => s.includes(f)))
  : allSuites;

if (suitesToRun.length === 0) {
  console.error(`No suites match filter: ${SUITE_FILTER.join(', ')}`);
  console.error(`Available: ${allSuites.join(', ')}`);
  process.exit(1);
}

// ── Create artifact dirs ───────────────────────────────────
for (const dir of Object.values(cfg.paths)) {
  if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
}

// ── Runner factory ─────────────────────────────────────────
function createRunner() {
  const results = [];
  const errors = [];
  let consoleErrors = [];
  let scenarioIndex = 0;

  const runner = {
    results,
    errors,

    scenario: async (meta, fn) => {
      if (AUTH_ONLY && !meta.auth) return;
      scenarioIndex++;
      const id = meta.id || `SC-${scenarioIndex}`;
      const start = Date.now();
      console.log(`\n  ▶ ${id}: ${meta.title}`);

      const result = {
        id,
        title: meta.title,
        tier: meta.tier,
        area: meta.area,
        auth: meta.auth || null,
        locale: meta.locale || 'ar',
        checks: [],
        passed: true,
        duration: 0,
        error: null,
      };

      try {
        // Browser context per scenario
        const context = await browser.newContext({
          viewport: cfg.viewport,
          locale: meta.locale === 'ar' ? 'ar-SA' : 'en-US',
        });
        const page = await context.newPage();

        // Collect console errors
        const pageConsoleErrors = [];
        page.on('console', msg => {
          if (msg.type() === 'error') pageConsoleErrors.push(msg.text());
        });
        page.on('pageerror', err => pageConsoleErrors.push(err.message));

        // Human simulation helper
        const human = {
          navigate: async (url) => {
            await page.goto(url, { waitUntil: 'networkidle', timeout: cfg.timeouts.navigation });
            if (cfg.human.enabled) {
              await page.waitForTimeout(delay(cfg.human.navigationDelay));
            }
          },
          click: async (locator) => {
            if (cfg.human.enabled) await page.waitForTimeout(delay(cfg.human.clickDelay));
            await locator.scrollIntoViewIfNeeded();
            if (cfg.human.mouseMoveChance > Math.random()) {
              await page.mouse.move(10, 10);
            }
            await locator.click();
            if (cfg.human.enabled) await page.waitForTimeout(delay(cfg.human.actionDelay));
          },
          type: async (locator, text) => {
            if (cfg.human.enabled) {
              await locator.click();
              await page.waitForTimeout(delay(cfg.human.clickDelay));
              await locator.fill('');
              for (const char of text) {
                await page.keyboard.type(char);
                await page.waitForTimeout(delay(cfg.human.typingDelay));
              }
            } else {
              await locator.fill(text);
            }
            if (cfg.human.enabled) await page.waitForTimeout(delay(cfg.human.actionDelay));
          },
          scroll: async () => {
            if (cfg.human.scrollChance > Math.random()) {
              await page.evaluate(() => window.scrollTo(0, Math.floor(Math.random() * 500)));
              await page.waitForTimeout(delay(cfg.human.actionDelay));
            }
          },
          wait: async (opts) => {
            const d = opts ? delay(opts) : 500;
            await page.waitForTimeout(d);
          },
        };

        // Snapshot helper
        const snap = async (name) => {
          const timestamp = Date.now();
          const safeName = `${id}_${name}_${timestamp}`.replace(/[^a-zA-Z0-9_-]/g, '_');
          await page.screenshot({
            path: path.join(cfg.paths.shots, `${safeName}.png`),
            fullPage: true,
          });
        };

        // Check helper
        const check = (label, pass) => {
          result.checks.push({ label, passed: !!pass, expected: true, actual: pass });
          const icon = pass ? '✓' : '✗';
          console.log(`    ${icon} ${label}`);
          if (!pass) result.passed = false;
        };

        const must = (label, pass) => {
          check(label, pass);
          if (!pass) throw new Error(`Must-pass failed: ${label}`);
        };

        // Step helper
        let stepIndex = 0;
        const step = async (label, fn) => {
          stepIndex++;
          console.log(`    ~ ${label}`);
          await fn();
        };

        // Authenticate if needed
        if (meta.auth) {
          const cred = cfg.creds[meta.auth];
          if (cred) {
            // Navigate to login
            await human.navigate(`${cfg.frontendURL}/login`);
            // Fill form
            const emailInput = page.locator('input[type="email"], input[name="email"]').first();
            const passwordInput = page.locator('input[type="password"], input[name="password"]').first();
            if (await emailInput.count() > 0 && await passwordInput.count() > 0) {
              await human.type(emailInput, cred.email);
              await human.type(passwordInput, cred.password);
              const submitBtn = page.locator('button[type="submit"]').first();
              if (await submitBtn.count() > 0) {
                await human.click(submitBtn);
                await page.waitForURL('**/dashboard**', { timeout: cfg.timeouts.navigation }).catch(() => {});
              }
            }
          }
        }

        // Run scenario
        const ctx = { consoleErrors: pageConsoleErrors };
        await fn({ page, human, step, check, must, snap, ctx });

        // Collect console errors
        if (pageConsoleErrors.length > 0) {
          console.log(`    ⚠ Console errors (${pageConsoleErrors.length}):`);
          for (const err of pageConsoleErrors.slice(0, 5)) {
            console.log(`      ${err.slice(0, 200)}`);
          }
        }
        consoleErrors.push(...pageConsoleErrors);

        await context.close();
      } catch (err) {
        result.passed = false;
        result.error = err.message;
        console.log(`    ✗ ERROR: ${err.message.slice(0, 300)}`);
        errors.push(err);
      }

      result.duration = Date.now() - start;
      results.push(result);
      console.log(`    ✔ ${result.passed ? 'PASS' : 'FAIL'} (${result.duration}ms)`);
    },

    getSummary: () => {
      const total = results.length;
      const passed = results.filter(r => r.passed).length;
      return { total, passed, failed: total - passed, results, errors };
    },
  };

  return runner;
}

function delay(opts) {
  if (typeof opts === 'number') return opts;
  return Math.floor(Math.random() * (opts.max - opts.min + 1)) + opts.min;
}

// ── Main ───────────────────────────────────────────────────
(async () => {
  console.log('══════════════════════════════════════════════');
  console.log('  SYRH E2E — "الدقيق البشر"');
  console.log(`  Suites: ${suitesToRun.join(', ')}`);
  console.log(`  Headless: ${HEADLESS}`);
  console.log(`  Human mode: ${cfg.human.enabled}`);
  console.log(`  Frontend: ${cfg.frontendURL}`);
  console.log(`  Backend: ${cfg.baseURL}`);
  console.log('══════════════════════════════════════════════\n');

  const { chromium } = require('playwright');

  browser = await chromium.launch({
    headless: HEADLESS,
    args: ['--disable-dev-shm-usage'],
  });

  const runner = createRunner();

  for (const suiteName of suitesToRun) {
    console.log(`\n━━━ Suite: ${suiteName} ━━━`);
    try {
      const suiteFn = require(`./suites/${suiteName}.cjs`);
      await suiteFn(runner);
    } catch (err) {
      console.error(`\n  ✗ Suite ${suiteName} failed to load: ${err.message}`);
    }
  }

  await browser.close();

  // ── Summary ─────────────────────────────────────────────
  const summary = runner.getSummary();
  console.log('\n══════════════════════════════════════════════');
  console.log(`  Results: ${summary.passed}/${summary.total} passed`);
  if (summary.failed > 0) {
    console.log(`  Failed: ${summary.failed}`);
    for (const r of summary.results.filter(r => !r.passed)) {
      console.log(`    ✗ ${r.id}: ${r.title}`);
      if (r.error) console.log(`      ${r.error}`);
      for (const c of r.checks.filter(c => !c.passed)) {
        console.log(`      ✗ ${c.label}`);
      }
    }
  }
  console.log('══════════════════════════════════════════════\n');

  // ── Generate JUnit report ───────────────────────────────
  const junitParts = [];
  junitParts.push('<?xml version="1.0" encoding="UTF-8"?>');
  junitParts.push(`<testsuites name="syhr-e2e" tests="${summary.total}" failures="${summary.failed}">`);
  for (const r of summary.results) {
    junitParts.push(`  <testsuite name="${r.id}" tests="1" failures="${r.passed ? 0 : 1}">`);
    junitParts.push(`    <testcase name="${r.id}: ${r.title}" classname="${r.area}" time="${(r.duration / 1000).toFixed(3)}">`);
    if (!r.passed) {
      const msg = r.error || 'Check failed';
      junitParts.push(`      <failure message="${escapeXml(msg)}">`);
      for (const c of r.checks.filter(c => !c.passed)) {
        junitParts.push(`        ${escapeXml(c.label)}`);
      }
      junitParts.push('      </failure>');
    }
    junitParts.push('    </testcase>');
    junitParts.push(`  </testsuite>`);
  }
  junitParts.push('</testsuites>');
  fs.writeFileSync(path.join(cfg.paths.reports, 'junit.xml'), junitParts.join('\n'));

  // ── Generate HTML report ────────────────────────────────
  const passRate = summary.total > 0 ? Math.round((summary.passed / summary.total) * 100) : 0;
  let html = `<!DOCTYPE html><html dir="rtl" lang="ar"><head>
<meta charset="UTF-8"><title>SYRH E2E Report</title>
<style>
  body{font-family:sans-serif;max-width:1200px;margin:auto;padding:20px;background:#f5f5f5;direction:rtl}
  h1{color:#333}.summary{background:#fff;padding:20px;border-radius:8px;margin:20px 0;box-shadow:0 1px 3px rgba(0,0,0,.1)}
  .pass{color:#16a34a}.fail{color:#dc2626}.suite{margin:20px 0;background:#fff;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.1);overflow:hidden}
  .suite-header{padding:12px 20px;font-weight:bold;border-bottom:1px solid #eee}
  .scenario{padding:12px 20px;border-bottom:1px solid #f0f0f0}
  .scenario:last-child{border-bottom:none}
  .check{padding:4px 0 4px 20px;font-size:14px;color:#555}
  .check-icon{display:inline-block;width:20px}
  .badge{display:inline-block;padding:2px 8px;border-radius:4px;font-size:12px;margin:0 4px}
  .badge-critical{background:#fee2e2;color:#dc2626}
  .badge-high{background:#fef3c7;color:#d97706}
  .badge-medium{background:#dbeafe;color:#2563eb}
</style></head><body>
<h1>SYRH E2E — تقرير الاختبارات</h1>
<div class="summary">
  <h2>الملخص</h2>
  <p>المجموع: <strong>${summary.total}</strong> | الناجح: <strong class="pass">${summary.passed}</strong> | الفاشل: <strong class="fail">${summary.failed}</strong> | النسبة: <strong>${passRate}%</strong></p>
</div>`;

  for (const r of summary.results) {
    const cls = r.passed ? 'pass' : 'fail';
    html += `<div class="suite"><div class="suite-header ${cls}">
      ${r.passed ? '✓' : '✗'} ${r.id}: ${r.title} (${(r.duration/1000).toFixed(1)}ث)
      <span class="badge badge-${r.tier}">${r.tier}</span>
      <span class="badge">${r.area}</span>
    </div>`;
    for (const c of r.checks) {
      const icon = c.passed ? '✓' : '✗';
      html += `<div class="check"><span class="check-icon ${c.passed ? 'pass' : 'fail'}">${icon}</span> ${c.label}</div>`;
    }
    if (r.error) {
      html += `<div class="check fail"><span class="check-icon">✗</span> ${escapeXml(r.error)}</div>`;
    }
    html += `</div>`;
  }

  html += `<p style="color:#999;font-size:12px;text-align:center">SYRH E2E — ${new Date().toISOString()}</p></body></html>`;

  fs.writeFileSync(path.join(cfg.paths.reports, 'index.html'), html);
  console.log(`  📊 Report: ${path.join(cfg.paths.reports, 'index.html')}`);
  console.log(`  📋 JUnit:  ${path.join(cfg.paths.reports, 'junit.xml')}`);

  process.exit(summary.failed > 0 ? 1 : 0);
})();

function escapeXml(s) {
  return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
