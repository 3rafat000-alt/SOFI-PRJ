/**
 * Direct visual inspection of landing page.
 * Opens headed browser, scrolls through all sections, checks errors.
 * RUN: node projects/carda-wallet/tests/inspect.cjs
 */
const { chromium } = require('/home/es3dlll/.local/share/npm-global/lib/node_modules/playwright');
const URL = 'http://localhost:3000/';
const sleep = ms => new Promise(r => setTimeout(r, ms));

(async () => {
  const browser = await chromium.launch({ headless: false, args: ['--no-sandbox'] });
  const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 }, locale: 'ar-SY' });
  const page = await ctx.newPage();
  page.setDefaultTimeout(10000);

  const errors = [];
  const warnings = [];
  const failedReqs = [];
  page.on('console', msg => {
    if (msg.type() === 'error') errors.push(msg.text());
    if (msg.type() === 'warning') warnings.push(msg.text());
  });
  page.on('pageerror', err => errors.push(err.message));
  page.on('requestfailed', req => failedReqs.push({ url: req.url(), err: req.failure()?.errorText }));
  page.on('response', resp => { if (resp.status() >= 400) failedReqs.push({ url: resp.url(), status: resp.status() }); });

  console.log('▸ LOADING PAGE...');
  await page.goto(URL, { waitUntil: 'networkidle' });

  // Wait for loading screen to dismiss
  await page.waitForFunction(() => {
    const ls = document.querySelector('.fixed.inset-0.z-\\[9999\\]');
    return !ls || ls.style.opacity === '0' || getComputedStyle(ls).opacity === '0';
  }, { timeout: 10000 }).catch(() => {});
  await sleep(3000);
  console.log('  ✓ Page loaded, loading screen dismissed');

  // ─────────── SCREENSHOT FULL ───────────
  await page.screenshot({ path: '/tmp/inspect-full.png', fullPage: true });

  // ─────────── 1. CONSOLE ERRORS ───────────
  console.log(`\n▸ CONSOLE: ${errors.length} errors, ${warnings.length} warnings`);
  errors.forEach(e => console.log(`  ✗ ${e.substring(0, 200)}`));
  warnings.forEach(w => console.log(`  ⚠ ${w.substring(0, 200)}`));

  // ─────────── 2. NETWORK ERRORS ───────────
  console.log(`\n▸ NETWORK: ${failedReqs.length} failed`);
  failedReqs.forEach(r => console.log(`  ✗ ${r.status||'ERR'} ${(r.url||'').substring(0,80)}`));

  // ─────────── 3. SECTIONS ───────────
  console.log('\n▸ SECTIONS:');
  const secs = await page.evaluate(() => {
    return Array.from(document.querySelectorAll('section')).map((s, i) => ({
      id: s.id || `section-${i}`,
      h: s.offsetHeight,
      imgs: s.querySelectorAll('img').length,
      headings: Array.from(s.querySelectorAll('h1,h2,h3')).map(h => h.textContent.trim()),
    }));
  });
  secs.forEach(s => {
    console.log(`  [${s.id}] h=${s.h}px`);
    s.headings.slice(0, 2).forEach(h => console.log(`    └ ${h.substring(0, 60)}`));
    if (s.imgs) console.log(`    images: ${s.imgs}`);
  });

  // ─────────── 4. CONTENT EXTRACT ───────────
  console.log('\n▸ ALL HEADINGS:');
  const allHeadings = await page.evaluate(() => {
    return Array.from(document.querySelectorAll('h1,h2,h3')).map(h => `${h.tagName}: ${h.textContent.trim()}`);
  });
  allHeadings.forEach(h => console.log(`  ${h}`));

  // ─────────── 5. BUTTONS & LINKS ───────────
  console.log('\n▸ INTERACTIVE ELEMENTS:');
  const btns = await page.evaluate(() => {
    return Array.from(document.querySelectorAll('button, a[href]')).filter(el => {
      const r = el.getBoundingClientRect();
      return r.width > 0 && r.height > 0 && el.checkVisibility();
    }).map(el => ({
      tag: el.tagName,
      text: (el.textContent || '').trim().substring(0, 40),
      href: el.getAttribute('href') || '',
      w: Math.round(el.getBoundingClientRect().width),
      h: Math.round(el.getBoundingClientRect().height),
    }));
  });
  btns.forEach(b => console.log(`  ${b.tag} "${b.text}" ${b.w}x${b.h}${b.href ? ' → '+b.href : ''}`));

  // ─────────── 6. DESIGN TOKENS ───────────
  console.log('\n▸ DESIGN TOKENS:');
  const vars = await page.evaluate(() => {
    const r = getComputedStyle(document.documentElement);
    const res = {};
    for (let i = 0; i < r.length; i++) {
      const p = r[i];
      if (p.startsWith('--')) res[p] = r.getPropertyValue(p).trim();
    }
    return res;
  });
  const colorVars = Object.entries(vars).filter(([k]) => /color|bg|text|border|gold|primary|accent|surface/.test(k));
  colorVars.forEach(([k, v]) => console.log(`  ${k}: ${v}`));

  // ─────────── 7. RESPONSIVE ───────────
  console.log('\n▸ RESPONSIVE SCREENSHOTS:');
  const vps = [
    { w: 375, h: 812, name: 'mobile' },
    { w: 768, h: 1024, name: 'tablet' },
    { w: 1440, h: 900, name: 'desktop' },
  ];
  for (const vp of vps) {
    await page.setViewportSize({ width: vp.w, height: vp.h });
    await sleep(500);
    await page.screenshot({ path: `/tmp/inspect-${vp.name}.png`, fullPage: true });
    console.log(`  ✓ ${vp.name}.png`);
  }
  await page.setViewportSize({ width: 1440, height: 900 });

  // ─────────── 8. SCROLL TEST ───────────
  console.log('\n▸ SCROLL TEST:');
  await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
  await sleep(2000);
  const scrollBottom = await page.evaluate(() => window.scrollY);
  const totalHeight = await page.evaluate(() => document.body.scrollHeight);
  console.log(`  Scroll height: ${totalHeight}px, reached: ${scrollBottom}px`);
  await page.evaluate(() => window.scrollTo(0, 0));
  await sleep(500);

  // ─────────── 9. THREE.JS ───────────
  console.log('\n▸ THREE.JS / CANVAS:');
  const canvas = await page.evaluate(() => {
    const c = document.querySelector('canvas');
    return c ? `${c.width}x${c.height}` : 'none';
  });
  console.log(`  Canvas: ${canvas}`);

  // ─────────── SUMMARY ───────────
  console.log('\n══════════ INSPECTION COMPLETE ══════════');
  console.log(`Sections: ${secs.length}`);
  console.log(`Interactive elements: ${btns.length}`);
  console.log(`Console errors: ${errors.length}`);
  console.log(`Failed requests: ${failedReqs.length}`);
  console.log(`Design tokens: ${Object.keys(vars).length}`);
  console.log(`Page height: ${totalHeight}px`);
  console.log(`Screenshots: /tmp/inspect-{full,mobile,tablet,desktop}.png`);

  // Keep browser open
  console.log('\nBrowser stays open. Close manually when done.');
  await sleep(120000);
  await browser.close();
})().catch(err => {
  console.error('FATAL:', err.message);
  process.exit(1);
});
