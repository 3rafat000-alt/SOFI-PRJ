const { chromium } = require('/home/es3dlll/.local/share/npm-global/lib/node_modules/playwright');
const URL = 'http://localhost:8000/admin/login';
const sleep = ms => new Promise(r => setTimeout(r, ms));

(async () => {
  const browser = await chromium.launch({ headless: true, args: ['--no-sandbox'] });
  const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 }, locale: 'ar-SY' });
  const page = await ctx.newPage();

  const consoleErrors = [];
  const failedReqs = [];
  const consoleWarnings = [];
  page.on('console', msg => {
    if (msg.type() === 'error') consoleErrors.push(msg.text());
    if (msg.type() === 'warning') consoleWarnings.push(msg.text());
  });
  page.on('pageerror', err => consoleErrors.push(err.message));
  page.on('requestfailed', req => failedReqs.push({ url: req.url().substring(0,100), err: req.failure()?.errorText }));
  page.on('response', resp => {
    if (resp.status() >= 400)
      failedReqs.push({ url: resp.url().substring(0,100), status: resp.status() });
  });

  // 1. Check login page
  console.log('=== LOGIN PAGE ===');
  await page.goto(URL, { waitUntil: 'networkidle', timeout: 15000 }).catch(e => console.log('TIMEOUT:', e.message));
  await sleep(2000);

  console.log(`Title: ${await page.title()}`);
  console.log(`Font: ${await page.evaluate(() => getComputedStyle(document.body).fontFamily)}`);
  console.log(`Console errors: ${consoleErrors.length}`);
  console.log(`Console warnings: ${consoleWarnings.length}`);
  console.log(`Failed requests: ${failedReqs.length}`);

  console.log('\n--- Console Errors ---');
  consoleErrors.forEach(e => console.log(`  ✗ ${e.substring(0, 200)}`));
  console.log('\n--- Console Warnings ---');
  consoleWarnings.forEach(w => console.log(`  ⚠ ${w.substring(0, 200)}`));
  console.log('\n--- Failed Requests ---');
  failedReqs.forEach(r => console.log(`  ✗ ${r.status || 'ERR'} ${r.url}`));

  // Check if logo loads
  const logo = await page.$('img[alt="صكك"]');
  if (logo) {
    const src = await logo.getAttribute('src');
    const naturalW = await logo.evaluate(el => el.naturalWidth);
    console.log(`\nLogo: ${src} (${naturalW}x${await logo.evaluate(el => el.naturalHeight)})`);
  }

  // Check icons
  const icons = await page.evaluate(() => {
    const els = document.querySelectorAll('.material-icons');
    return els.length > 0 ? `${els.length} icons found` : 'NO material-icons found';
  });
  console.log(`Icons: ${icons}`);

  // Check CSS
  const cssLoaded = await page.evaluate(() => {
    const sheets = document.styleSheets;
    for (let s of sheets) {
      try { if (s.cssRules && s.cssRules.length > 0 && s.cssRules[0].cssText.includes('Cairo')) return true; } catch(e) {}
    }
    return false;
  });
  console.log(`Cairo font loaded via CSS: ${cssLoaded}`);

  // 2. Try login
  console.log('\n=== TRY LOGIN ===');
  await page.fill('input[name="email"]', 'admin@sakk.com');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/admin**', { timeout: 8000 }).catch(() => {});
  await sleep(2000);
  console.log(`After login URL: ${page.url()}`);
  console.log(`Title: ${await page.title()}`);

  // Check dashboard
  const dashErrors = [];
  const dashFailed = [];
  page.on('console', msg => { if (msg.type() === 'error') dashErrors.push(msg.text()); });
  page.on('requestfailed', req => dashFailed.push(req.url().substring(0,100)));
  page.on('response', resp => { if (resp.status() >= 400) dashFailed.push(resp.url().substring(0,100) + ':' + resp.status()); });

  await page.waitForSelector('.stat-card', { timeout: 8000 }).catch(() => {});
  await sleep(2000);

  console.log(`\nDashboard errors: ${dashErrors.length}`);
  dashErrors.forEach(e => console.log(`  ✗ ${e.substring(0,200)}`));
  console.log(`Dashboard failed: ${dashFailed.length}`);
  dashFailed.forEach(r => console.log(`  ✗ ${r}`));

  // Check sidebar
  const sidebarLinks = await page.evaluate(() => {
    return Array.from(document.querySelectorAll('.sidebar-link')).map(a => a.textContent.trim()).filter(Boolean);
  });
  console.log(`\nSidebar links (${sidebarLinks.length}):`);
  sidebarLinks.slice(0, 8).forEach(l => console.log(`  • ${l}`));

  // Count visible icons
  const visibleIcons = await page.evaluate(() => {
    const icons = document.querySelectorAll('.material-icons');
    let ok = 0, bad = 0;
    icons.forEach(ic => {
      const w = ic.offsetWidth;
      if (w === 0 || (ic.textContent || '').trim() === '') bad++;
      else ok++;
    });
    return { ok, bad, total: icons.length };
  });
  console.log(`\nIcons: ${visibleIcons.ok} OK, ${visibleIcons.bad} broken, ${visibleIcons.total} total`);

  await browser.close();
  console.log('\n=== DONE ===');
})().catch(e => { console.error('FATAL:', e.message); process.exit(1); });
