/**
 * Test Runner — Core engine that creates isolated browser contexts,
 * captures video + trace + per-step screenshots, and runs scenarios
 * with soft (check) and hard (must) assertions.
 *
 * "الدقيق البشري" — each scenario runs at human speed with realistic delays.
 */
const { chromium } = require(require('../config.cjs').PW);
const cfg = require('../config.cjs');
const { createHuman } = require('./human.cjs');
const { timestamp, sleep } = require('./utils.cjs');
const path = require('path');
const fs = require('fs');

class ScenarioError extends Error {
  constructor(msg, { check, detail } = {}) {
    super(msg);
    this.checkName = check;
    this.detail = detail;
  }
}

class Runner {
  constructor(suiteName) {
    this.suiteName = suiteName;
    this.browser = null;
    this.results = [];
    this.startTime = Date.now();
    this.consoleErrors = [];
    this.networkErrors = [];
  }

  async init() {
    this.browser = await chromium.launch({
      headless: process.env.HEADED ? false : true,
      args: ['--no-sandbox', '--disable-setuid-sandbox'],
    });
  }

  async destroy() {
    if (this.browser) await this.browser.close();
  }

  /**
   * Run a browser-based scenario with full instrumentation.
   * @param {object} meta - { id, title, tier, area, auth?, locale? }
   * @param {function} fn - async ({ page, human, step, check, must, log, snap, ctx }) => {}
   */
  async scenario(meta, fn) {
    const ctx = {
      scenarioId: meta.id,
      title: meta.title,
      startTime: Date.now(),
      steps: [],
      checks: { pass: 0, fail: 0 },
      screenshots: [],
      consoleErrors: [],
      networkErrors: [],
      passed: true,
    };

    const context = await this.browser.newContext({
      viewport: cfg.viewport,
      locale: meta.locale || cfg.locale,
      timezoneId: 'Asia/Damascus',
      storageState: meta.auth ? await this._getState(meta.auth) : undefined,
      recordVideo: { dir: cfg.paths.video, size: cfg.viewport },
    });

    // Capture console errors
    context.on('console', msg => {
      if (msg.type() === 'error') ctx.consoleErrors.push(msg.text());
    });

    // Capture network failures
    context.on('requestfailed', req => {
      ctx.networkErrors.push({ url: req.url(), failure: req.failure()?.errorText });
    });

    const page = await context.newPage();
    const human = createHuman(cfg, page);

    // Set default timeout
    page.setDefaultTimeout(cfg.timeouts.element);

    // Start tracing
    await context.tracing.start({
      screenshots: true,
      snapshots: true,
      name: meta.id,
    });

    const shotCounter = { n: 0 };

    try {
      await fn({
        page,
        human,
        ctx,
        /** Name a logical step in the scenario */
        step: async (name, stepFn) => {
          const stepStart = Date.now();
          ctx.steps.push({ name, status: 'running', duration: 0 });
          try {
            await stepFn(page);
            ctx.steps[ctx.steps.length - 1].status = 'pass';
          } catch (e) {
            ctx.steps[ctx.steps.length - 1].status = 'fail';
            ctx.steps[ctx.steps.length - 1].error = e.message;
            ctx.passed = false;
            throw e;
          } finally {
            ctx.steps[ctx.steps.length - 1].duration = Date.now() - stepStart;
          }
        },

        /** Soft check — records pass/fail without aborting */
        check: (name, condition, detail) => {
          if (condition) {
            ctx.checks.pass++;
          } else {
            ctx.checks.fail++;
            ctx.passed = false;
          }
        },

        /** Hard assertion — aborts scenario on failure */
        must: (name, condition, detail) => {
          if (!condition) {
            ctx.passed = false;
            throw new ScenarioError(`Must: ${name}`, { check: name, detail });
          }
          ctx.checks.pass++;
        },

        /** Take a screenshot at this moment */
        snap: async (label) => {
          shotCounter.n++;
          const filename = `${meta.id}_${String(shotCounter.n).padStart(3, '0')}_${label.replace(/[^a-z0-9]/gi, '_')}.png`;
          const filepath = path.join(cfg.paths.shots, filename);
          await page.screenshot({ path: filepath, fullPage: true });
          ctx.screenshots.push({ label, file: filename, path: filepath });
        },

        /** Log a text note */
        log: (msg) => {
          ctx.steps.push({ name: `📝 ${msg}`, status: 'info', duration: 0 });
        },
      });

      // If scenario had steps and no step threw, it passed
      if (ctx.steps.filter(s => s.status === 'running').length > 0) {
        // Mark remaining running steps as pass
      }
      ctx.passed = ctx.checks.fail === 0;

    } catch (e) {
      ctx.passed = false;
      ctx.error = e.message;
      // Take failure screenshot
      try {
        const failShot = `${meta.id}_FAIL_${timestamp()}.png`;
        await page.screenshot({ path: path.join(cfg.paths.shots, failShot), fullPage: true });
        ctx.screenshots.push({ label: 'FAILURE', file: failShot });
      } catch (_) {}
    } finally {
      ctx.duration = Date.now() - ctx.startTime;

      // Stop tracing
      try {
        const tracePath = path.join(cfg.paths.trace, `${meta.id}_${timestamp()}.zip`);
        await context.tracing.stop({ path: tracePath });
        ctx.traceFile = tracePath;
      } catch (_) {}

      // Close context (saves video)
      await context.close();

      // Store result
      this.results.push(ctx);
    }

    return ctx;
  }

  /**
   * Run an API-only scenario (no browser).
   */
  async apiScenario(meta, fn) {
    const ctx = {
      scenarioId: meta.id,
      title: meta.title,
      startTime: Date.now(),
      checks: { pass: 0, fail: 0 },
      passed: true,
    };

    try {
      await fn({
        ...require('./api.cjs'),
        check: (name, condition, detail) => {
          if (condition) ctx.checks.pass++;
          else { ctx.checks.fail++; ctx.passed = false; }
        },
        must: (name, condition, detail) => {
          if (!condition) throw new ScenarioError(`Must: ${name}`, { check: name, detail });
          ctx.checks.pass++;
        },
        log: (msg) => {},
      });
    } catch (e) {
      ctx.passed = false;
      ctx.error = e.message;
    } finally {
      ctx.duration = Date.now() - ctx.startTime;
      this.results.push(ctx);
    }

    return ctx;
  }

  /** Get or create storage state for a role */
  async _getState(role) {
    const statePath = path.join(cfg.paths.state, `${role}.json`);
    if (fs.existsSync(statePath)) return statePath;

    // Need to login and save state
    const cred = cfg.creds[role];
    if (!cred) throw new Error(`Unknown role: ${role}`);

    const context = await this.browser.newContext({ viewport: cfg.viewport });
    const page = await context.newPage();
    const human = createHuman(cfg, page);

    try {
      await page.goto(`${cfg.frontendURL}/login`, { waitUntil: 'networkidle' });
      await human.type('input[type="email"]', cred.email);
      await human.wait();
      await human.type('input[type="password"]', cred.password);
      await human.wait();
      await human.click('button[type="submit"]');
      await page.waitForURL(/dashboard|\/user\//, { timeout: cfg.timeouts.navigation * 2 });
      await page.waitForTimeout(1000);
      await context.storageState({ path: statePath });
    } finally {
      await context.close();
    }

    return statePath;
  }

  /** Summary of all results */
  summary() {
    const total = this.results.length;
    const passed = this.results.filter(r => r.passed).length;
    const failed = this.results.filter(r => !r.passed).length;
    const totalChecks = this.results.reduce((s, r) => s + r.checks.pass + r.checks.fail, 0);
    const passChecks = this.results.reduce((s, r) => s + r.checks.pass, 0);
    const failChecks = this.results.reduce((s, r) => s + r.checks.fail, 0);
    const duration = Date.now() - this.startTime;
    const consoleErrors = this.results.reduce((s, r) => s + (r.consoleErrors?.length || 0), 0);
    const networkErrors = this.results.reduce((s, r) => s + (r.networkErrors?.length || 0), 0);

    return {
      total, passed, failed,
      totalChecks, passChecks, failChecks,
      duration,
      consoleErrors, networkErrors,
    };
  }
}

module.exports = Runner;
