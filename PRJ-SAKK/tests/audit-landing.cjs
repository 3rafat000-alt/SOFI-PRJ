/**
 * Full Playwright audit: SAKK landing page
 * Checks: console, network, buttons, scroll, design, CSS, layout, responsive
 */
const { chromium } = require('/home/es3dlll/.local/share/npm-global/lib/node_modules/playwright');

const URL = 'http://localhost:3000/';
const REPORT = { pass: [], fail: [], warn: [], data: {} };
let browser, page, ctx;

async function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

(async () => {
  browser = await chromium.launch({ headless: true, args: ['--no-sandbox'] });
  ctx = await browser.newContext({ viewport: { width: 1440, height: 900 }, locale: 'ar-SY' });
  page = await ctx.newPage();

  const errLogs = [];
  const warnLogs = [];
  const failedReqs = [];
  page.on('console', msg => {
    if (msg.type() === 'error') errLogs.push(msg.text());
    if (msg.type() === 'warning') warnLogs.push(msg.text());
  });
  page.on('pageerror', err => errLogs.push(err.message));
  page.on('requestfailed', req => failedReqs.push({ url: req.url(), err: req.failure()?.errorText }));
  page.on('response', resp => {
    if (resp.status() >= 400) failedReqs.push({ url: resp.url(), status: resp.status() });
  });

  // ─── LOAD ───
  console.log('▸ Loading page...');
  const start = Date.now();
  await page.goto(URL, { waitUntil: 'networkidle', timeout: 30000 });
  // Wait for loading screen to dismiss (~2.6s) + extra buffer
  await sleep(4000);
  const loadTime = Date.now() - start;
  console.log(`  Load: ${loadTime}ms`);

  // ─── 1. CONSOLE ───
  console.log('\n▸ CONSOLE ERRORS');
  if (errLogs.length === 0) { REPORT.pass.push('No console errors'); console.log('  ✓ Clean'); }
  else { REPORT.fail.push(`${errLogs.length} console errors`); errLogs.forEach(l => console.log(`  ✗ ${l.substring(0, 200)}`)); }
  if (warnLogs.length) warnLogs.forEach(l => console.log(`  ⚠ ${l.substring(0, 150)}`));

  // ─── 2. NETWORK ───
  console.log('\n▸ NETWORK ERRORS');
  if (failedReqs.length === 0) { REPORT.pass.push('No failed requests'); console.log('  ✓ Clean'); }
  else { REPORT.fail.push(`${failedReqs.length} failed`); failedReqs.forEach(r => console.log(`  ✗ ${r.status || 'ERR'} ${(r.url || '').substring(0, 80)}`)); }

  // ─── 3. LAYOUT SECTIONS ───
  console.log('\n▸ SECTIONS');
  const sections = await page.evaluate(() => {
    const s = document.querySelectorAll('section, [id]');
    return Array.from(s).map(el => ({
      id: el.id || '(no id)',
      tag: el.tagName,
      h: el.offsetHeight,
      visible: el.checkVisibility(),
      text: (el.textContent || '').trim().substring(0, 60),
    }));
  });
  console.log(`  Total: ${sections.length}`);
  sections.slice(0, 15).forEach(s => console.log(`  ${s.id}: h=${s.h}px visible=${s.visible}`));

  // ─── 4. INTERACTIVE ELEMENTS ───
  console.log('\n▸ BUTTONS & LINKS');
  const elems = await page.evaluate(() => {
    const all = document.querySelectorAll('button, a[href], input, select, textarea, [role="button"]');
    return Array.from(all).map(el => ({
      tag: el.tagName,
      text: (el.textContent || '').trim().substring(0, 50),
      href: el.getAttribute('href') || '',
      w: el.getBoundingClientRect().width,
      h: el.getBoundingClientRect().height,
      visible: el.checkVisibility(),
    })).filter(e => e.visible && e.w > 0);
  });
  console.log(`  Total interactive: ${elems.length}`);
  const broken = elems.filter(e => e.h < 20 || e.w < 20);
  if (broken.length) { REPORT.warn.push(`${broken.length} tiny elements`); broken.forEach(e => console.log(`  ⚠ ${e.text}: ${e.w}x${e.h}`)); }
  else console.log('  ✓ All elements properly sized');

  // ─── 5. SCROLL BEHAVIOR ───
  console.log('\n▸ SCROLL');
  const scroll = await page.evaluate(() => getComputedStyle(document.documentElement).scrollBehavior);
  console.log(`  scroll-behavior: ${scroll}`);
  if (scroll === 'smooth') REPORT.pass.push('Smooth scroll');
  else REPORT.warn.push('No smooth scroll on html');

  // ─── 6. LAYOUT OVERFLOW ───
  console.log('\n▸ LAYOUT');
  const overflow = await page.evaluate(() => {
    const issues = [];
    document.querySelectorAll('*').forEach(el => {
      if (el.scrollWidth > el.clientWidth + 2 && el !== document.body && el !== document.documentElement) {
        const tag = el.tagName + (el.id ? '#' + el.id : el.className ? '.' + el.className.slice(0, 20) : '');
        if (getComputedStyle(el).overflowX !== 'hidden') issues.push(tag);
      }
    });
    return issues.slice(0, 10);
  });
  if (overflow.length === 0) { REPORT.pass.push('No overflow'); console.log('  ✓ No overflow'); }
  else { REPORT.warn.push(`${overflow.length} overflow`); overflow.forEach(o => console.log(`  ⚠ ${o}`)); }

  // ─── 7. PADDING ASYMMETRY ───
  console.log('\n▸ SPACING');
  const spacing = await page.evaluate(() => {
    const issues = [];
    document.querySelectorAll('section').forEach(s => {
      const st = getComputedStyle(s);
      const pt = parseInt(st.paddingTop) || 0;
      const pb = parseInt(st.paddingBottom) || 0;
      if (pt > 0 && pb > 0 && Math.abs(pt - pb) > 30) issues.push(`#${s.id}: T=${pt} B=${pb}`);
    });
    return issues;
  });
  if (spacing.length) { REPORT.warn.push(`${spacing.length} spacing asymmetry`); spacing.forEach(s => console.log(`  ⚠ ${s}`)); }
  else console.log('  ✓ Symmetrical padding');

  // ─── 8. CSS CUSTOM PROPS ───
  console.log('\n▸ DESIGN TOKENS');
  const cssVars = await page.evaluate(() => {
    const r = getComputedStyle(document.documentElement);
    const vars = {};
    for (let i = 0; i < r.length; i++) if (r[i].startsWith('--')) vars[r[i]] = r.getPropertyValue(r[i]).trim();
    return vars;
  });
  const colorKeys = Object.keys(cssVars).filter(k => /color|bg|text|border/.test(k));
  console.log(`  CSS vars: ${Object.keys(cssVars).length} (${colorKeys.length} colors)`);
  colorKeys.slice(0, 25).forEach(k => console.log(`    ${k}: ${cssVars[k]}`));

  // ─── 9. FONTS ───
  console.log('\n▸ FONTS');
  const fonts = await page.evaluate(() => {
    const els = document.querySelectorAll('*');
    const families = new Set();
    els.forEach(el => families.add(getComputedStyle(el).fontFamily));
    return Array.from(families);
  });
  fonts.forEach(f => console.log(`  Font: ${f.substring(0, 60)}`));

  // ─── 10. IMAGES ───
  console.log('\n▸ IMAGES');
  const imgs = await page.evaluate(() => {
    return Array.from(document.querySelectorAll('img')).map(img => ({
      src: (img.src || '').substring(0, 60),
      alt: img.alt || '(missing)',
      w: img.naturalWidth,
      h: img.naturalHeight,
      loading: img.loading,
    }));
  });
  const missingAlt = imgs.filter(i => i.alt === '(missing)');
  console.log(`  Images: ${imgs.length}, missing alt: ${missingAlt.length}`);
  imgs.forEach(i => console.log(`  ${i.src} ${i.w}x${i.h} loading=${i.loading} alt="${i.alt.substring(0, 30)}"`));

  // ─── 11. RESPONSIVE ───
  console.log('\n▸ RESPONSIVE SCREENSHOTS');
  const vps = [
    { w: 375, h: 812, name: 'mobile' },
    { w: 768, h: 1024, name: 'tablet' },
    { w: 1440, h: 900, name: 'desktop' },
    { w: 1920, h: 1080, name: 'wide' },
  ];
  for (const vp of vps) {
    await page.setViewportSize({ width: vp.w, height: vp.h });
    await sleep(800);
    await page.screenshot({ path: `/tmp/sakk-${vp.name}.png`, fullPage: true });
    console.log(`  ✓ ${vp.name}`);
  }

  // ─── 12. PERFORMANCE ───
  console.log('\n▸ PERFORMANCE');
  const perf = await page.evaluate(() => {
    const n = performance.getEntriesByType('navigation')[0];
    return { dcl: n?.domContentLoadedEventEnd || 0, load: n?.loadEventEnd || 0, ttfb: n?.responseStart || 0 };
  });
  console.log(`  TTFB: ${perf.ttfb.toFixed(0)}ms  DCL: ${(perf.dcl / 1000).toFixed(2)}s  Load: ${(perf.load / 1000).toFixed(2)}s`);

  // ─── 13. BUTTON CLICK TEST ───
  console.log('\n▸ BUTTON CLICKS');
  const navBtnTexts = await page.locator('header button, nav button, [aria-label*="الرئيسية"]').allTextContents().catch(() => []);
  console.log(`  Nav has ${navBtnTexts.length} clickable`);
  
  // Try clicking login button
  const loginBtn = page.locator('button:has-text("تسجيل دخول"), a:has-text("تسجيل دخول")').first();
  if (await loginBtn.count() > 0) {
    await loginBtn.click();
    await sleep(1500);
    const u = page.url();
    console.log(`  Click تسجيل دخول → ${u}`);
    if (u.includes('/login')) REPORT.pass.push('Login button navigates correctly');
    else REPORT.warn.push(`Login button → ${u} (expected /login)`);
    await page.goBack();
    await sleep(2000);
  }

  // ─── 14. THREE.JS CHECK ───
  console.log('\n▸ THREE.JS');
  const three = await page.evaluate(() => {
    if (typeof THREE !== 'undefined') return 'Found (global)';
    return !document.querySelector('canvas') ? 'No canvas' : 'Has canvas';
  });
  console.log(`  ${three}`);

  // ─── SUMMARY ───
  REPORT.data = {
    sections: sections.length, elements: elems.length, imgs: imgs.length,
    cssVars: Object.keys(cssVars).length, loadTime, vps: vps.map(v => v.name),
  };

  console.log('\n═══════════ AUDIT SUMMARY ═══════════');
  REPORT.pass.forEach(m => console.log(`  ✅ ${m}`));
  REPORT.warn.forEach(m => console.log(`  ⚠️ ${m}`));
  REPORT.fail.forEach(m => console.log(`  ❌ ${m}`));
  console.log(`\nScreenshots: /tmp/sakk-{mobile,tablet,desktop,wide}.png`);

  await browser.close();
})().catch(async err => {
  console.error('FATAL:', err.message);
  if (browser) await browser.close();
  process.exit(1);
});
