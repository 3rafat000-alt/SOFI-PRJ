// Core test engine. No @playwright/test — we drive the Playwright lib directly so we own
// the per-operation recording pipeline: video + step screenshots + trace + console/network capture.
//
// Each scenario runs in its OWN browser context => isolated video, isolated cookies.
// Authenticated scenarios reuse a saved storageState (login once, replay everywhere) for speed.
const fs = require('fs');
const path = require('path');
const cfg = require('./config.cjs');
const { chromium } = require(cfg.PW);

const ensure = (d) => fs.mkdirSync(d, { recursive: true });
const slug = (s) => String(s).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '').slice(0, 60);

class AssertionError extends Error {}

class Runner {
  constructor(suiteName) {
    this.suite = suiteName;
    this.results = [];
    this.browser = null;
    this.states = {}; // who -> storageState file
  }

  async start() {
    [cfg.paths.video, cfg.paths.shots, cfg.paths.trace, cfg.paths.state, cfg.paths.reports].forEach(ensure);
    this.browser = await chromium.launch({
      headless: cfg.headless,
      args: ['--no-sandbox', '--disable-dev-shm-usage'],
    });
  }

  async stop() {
    if (this.browser) await this.browser.close();
  }

  // Log in once per role, persist the session, reuse it across scenarios.
  async authState(who) {
    if (this.states[who]) return this.states[who];
    const c = cfg.creds[who];
    const file = path.join(cfg.paths.state, `${who}.json`);
    const ctx = await this.browser.newContext({ baseURL: cfg.baseURL, locale: cfg.locale, viewport: cfg.viewport });
    const page = await ctx.newPage();
    await page.goto(cfg.baseURL + '/admin/login', { waitUntil: 'networkidle' });
    await page.fill('input[name="email"]', c.email);
    await page.fill('input[name="password"]', c.password);
    await Promise.all([
      page.waitForLoadState('networkidle'),
      page.click('#loginForm button[type="submit"], button[type="submit"]'),
    ]).catch(() => {});
    const ok = !/\/admin\/login/.test(page.url());
    await ctx.storageState({ path: file });
    await ctx.close();
    if (!ok) throw new Error(`authState(${who}) failed — still on login page`);
    this.states[who] = file;
    return file;
  }

  // meta: { id, title, tier, area, auth? ('admin'|'user1'...) }
  // fn:  async ({ page, context, step, check, must, log, snap, api })
  async scenario(meta, fn) {
    const id = meta.id;
    const vdir = path.join(cfg.paths.video, id);
    const sdir = path.join(cfg.paths.shots, id);
    ensure(vdir); ensure(sdir);

    const rec = {
      id, title: meta.title, tier: meta.tier || 'admin', area: meta.area || '',
      status: 'pass', startedAt: Date.now(), endedAt: null, durationMs: 0,
      steps: [], checks: [],
      consoleErrors: [], pageErrors: [], failedRequests: [], serverErrors: [],
      video: null, trace: null, error: null,
    };

    const ctxOpts = {
      viewport: cfg.viewport, locale: cfg.locale, baseURL: cfg.baseURL,
      recordVideo: { dir: vdir, size: cfg.viewport },
      ignoreHTTPSErrors: true,
    };
    if (meta.auth) ctxOpts.storageState = await this.authState(meta.auth);

    const context = await this.browser.newContext(ctxOpts);
    await context.tracing.start({ screenshots: true, snapshots: true, sources: true }).catch(() => {});
    const page = await context.newPage();
    const video = page.video();

    let shotN = 0;
    page.on('console', (m) => { if (m.type() === 'error') rec.consoleErrors.push(m.text()); });
    page.on('pageerror', (e) => rec.pageErrors.push(e.message));
    page.on('requestfailed', (r) => {
      const u = r.url();
      if (/\.(png|jpg|jpeg|webp|svg|woff2?|ico)$/i.test(u)) return; // ignore asset flakes
      rec.failedRequests.push(`${r.method()} ${u} :: ${r.failure() && r.failure().errorText}`);
    });
    page.on('response', (r) => { if (r.status() >= 500) rec.serverErrors.push(`${r.status()} ${r.request().method()} ${r.url()}`); });

    const snap = async (label) => {
      const f = path.join(sdir, `${String(++shotN).padStart(2, '0')}-${slug(label)}.png`);
      try { await page.screenshot({ path: f }); } catch (_) {}
      return path.relative(cfg.paths.root, f);
    };
    const step = async (label, action) => {
      const t0 = Date.now();
      const s = { label, ok: true, ms: 0, shot: null, error: null };
      try {
        await action(page);
        s.shot = await snap(label);
      } catch (e) {
        s.ok = false; s.error = e.message; s.shot = await snap('FAIL-' + label);
        s.ms = Date.now() - t0; rec.steps.push(s);
        throw e;
      }
      s.ms = Date.now() - t0; rec.steps.push(s);
    };
    const check = (label, cond, detail) => { // soft: records, never throws
      const ok = !!cond; rec.checks.push({ label, ok, hard: false, detail: detail || null });
      return ok;
    };
    const must = (label, cond, detail) => { // hard: aborts scenario on fail
      const ok = !!cond; rec.checks.push({ label, ok, hard: true, detail: detail || null });
      if (!ok) throw new AssertionError(`MUST: ${label}${detail ? ' :: ' + detail : ''}`);
      return ok;
    };
    const log = (msg) => rec.steps.push({ label: '· ' + msg, ok: true, ms: 0, shot: null, note: true });

    try {
      await fn({ page, context, step, check, must, log, snap });
    } catch (e) {
      rec.status = 'fail';
      rec.error = e.message;
    }

    // Verdict rollup: any failed check, 500, or uncaught page error => fail.
    if (rec.checks.some((c) => !c.ok)) rec.status = 'fail';
    if (rec.serverErrors.length || rec.pageErrors.length) rec.status = 'fail';
    rec.endedAt = Date.now(); rec.durationMs = rec.endedAt - rec.startedAt;

    const tracePath = path.join(cfg.paths.trace, `${id}.zip`);
    try { await context.tracing.stop({ path: tracePath }); rec.trace = path.relative(cfg.paths.root, tracePath); } catch (_) {}

    await context.close(); // finalizes the video file
    try {
      const vpath = video ? await video.path() : null;
      if (vpath && fs.existsSync(vpath)) {
        const dest = path.join(vdir, `${id}.webm`);
        try { fs.renameSync(vpath, dest); rec.video = path.relative(cfg.paths.root, dest); }
        catch (_) { rec.video = path.relative(cfg.paths.root, vpath); }
      }
    } catch (_) {}

    fs.writeFileSync(path.join(sdir, 'result.json'), JSON.stringify(rec, null, 2));
    this.results.push(rec);
    const mark = rec.status === 'pass' ? 'PASS' : 'FAIL';
    const warn = rec.consoleErrors.length ? ` ⚠${rec.consoleErrors.length}console` : '';
    console.log(`[${mark}] ${id} — ${rec.title}${warn}${rec.error ? '  (' + rec.error + ')' : ''}`);
    return rec;
  }

  // Headless API scenario — no browser/video. For contract, authz, and money-edge checks.
  // fn: async ({ check, must, log, http })  where http(method, path, opts) records the call.
  async apiScenario(meta, fn) {
    const api = require('./api.cjs');
    const rec = {
      id: meta.id, title: meta.title, tier: meta.tier || 'api', area: meta.area || '',
      status: 'pass', startedAt: Date.now(), endedAt: null, durationMs: 0,
      steps: [], checks: [],
      consoleErrors: [], pageErrors: [], failedRequests: [], serverErrors: [],
      video: null, trace: null, error: null,
    };
    const http = async (method, p, opts = {}) => {
      const r = await api.req(method, p, opts);
      const tag = `${method} ${p} → ${r.status}`;
      rec.steps.push({ label: tag, ok: r.status < 500, ms: 0, shot: null, note: true });
      if (r.status >= 500) rec.serverErrors.push(`${r.status} ${method} ${p}`);
      return r;
    };
    const check = (label, cond, detail) => { const ok = !!cond; rec.checks.push({ label, ok, hard: false, detail: detail || null }); return ok; };
    const must = (label, cond, detail) => { const ok = !!cond; rec.checks.push({ label, ok, hard: true, detail: detail || null }); if (!ok) throw new AssertionError(`MUST: ${label}${detail ? ' :: ' + detail : ''}`); return ok; };
    const log = (m) => rec.steps.push({ label: '· ' + m, ok: true, ms: 0, shot: null, note: true });

    try { await fn({ check, must, log, http, api }); }
    catch (e) { rec.status = 'fail'; rec.error = e.message; }
    if (rec.checks.some((c) => !c.ok)) rec.status = 'fail';
    if (rec.serverErrors.length) rec.status = 'fail';
    rec.endedAt = Date.now(); rec.durationMs = rec.endedAt - rec.startedAt;

    const sdir = path.join(cfg.paths.shots, rec.id); ensure(sdir);
    fs.writeFileSync(path.join(sdir, 'result.json'), JSON.stringify(rec, null, 2));
    this.results.push(rec);
    const mark = rec.status === 'pass' ? 'PASS' : 'FAIL';
    console.log(`[${mark}] ${rec.id} — ${rec.title}${rec.error ? '  (' + rec.error + ')' : ''}`);
    return rec;
  }
}

module.exports = Runner;
module.exports.AssertionError = AssertionError;
