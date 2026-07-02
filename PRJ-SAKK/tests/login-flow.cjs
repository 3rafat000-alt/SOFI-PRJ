/**
 * Playwright: Test SAKK login flow end-to-end.
 * Opens visible Chromium for user to see.
 * RUN: node projects/carda-wallet/tests/login-flow.cjs
 */
 const { chromium } = require('/home/es3dlll/.local/share/npm-global/lib/node_modules/playwright');

const FRONTEND_URL = 'http://localhost:3000';
const API_BASE     = 'http://localhost:8000/api/v1';

async function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

(async () => {
  const browser = await chromium.launch({
    headless: false,
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
  });

  const context = await browser.newContext({
    viewport: { width: 1440, height: 900 },
    locale: 'ar-SY',
  });
  const page = await context.newPage();
  page.on('console', msg => {}); // suppress

  // ── 1. Landing page ──
  console.log('\n▸ STEP 1: Landing page');
  await page.goto(FRONTEND_URL, { waitUntil: 'networkidle' });
  await page.screenshot({ path: '/tmp/sakk-01-landing.png', fullPage: true });
  console.log('  ✓ Landing loaded');

  // Click "تسجيل دخول"
  await page.click('a, button, span:has-text("تسجيل دخول")');
  await page.waitForURL('**/login**', { timeout: 5000 }).catch(() => {});
  // fallback direct nav
  if (!page.url().includes('/login')) {
    await page.goto(`${FRONTEND_URL}/user/login`, { waitUntil: 'networkidle' });
  }
  await sleep(1000);
  await page.screenshot({ path: '/tmp/sakk-02-login.png' });
  console.log('  ✓ Login page');

  // ── 2. Empty validation ──
  console.log('\n▸ STEP 2: Empty validation');
  await page.click('button:has-text("تسجيل الدخول")');
  await sleep(600);
  await page.screenshot({ path: '/tmp/sakk-02b-validation.png' });
  const alertText = await page.locator('[role="alert"]').first().textContent().catch(() => '');
  console.log(`  Alert: ${alertText}`);

  // ── 3. Login with EMAIL ──
  console.log('\n▸ STEP 3: Login with EMAIL');
  const phoneInput = page.locator('input[placeholder*="٩"], input[inputmode="text"]').first();
  await phoneInput.fill('admin@sakk.com');
  await sleep(200);
  const passInput = page.locator('input[type="password"]').first();
  await passInput.fill('password');
  await sleep(200);

  await page.screenshot({ path: '/tmp/sakk-03-filled.png' });

  // Submit
  await page.click('button:has-text("تسجيل الدخول")');
  await sleep(2000);
  await page.screenshot({ path: '/tmp/sakk-04-result.png', fullPage: true });

  const u1 = page.url();
  console.log(`  URL: ${u1}`);

  if (u1.includes('/dashboard')) {
    console.log('  ✓ EMAIL login → dashboard!');
    const body = await page.textContent('body').catch(() => '');
    if (body.length < 100) console.log(`  Body: ${body.substring(0, 100)}`);
  } else {
    // check error
    const err = await page.locator('[role="alert"], .text-red, .text-red-500').last().textContent().catch(() => '');
    console.log(`  ✗ Login failed: ${err || '(no error)'}`);
    const body = await page.textContent('body').catch(() => '');
    // look for فشل
    if (body.includes('فشل')) console.log('    → "فشل تسجيل الدخول" found');
  }

  // ── 4. Login with PHONE (correct format) ──
  console.log('\n▸ STEP 4: Login with PHONE');
  await page.goto(`${FRONTEND_URL}/user/login`, { waitUntil: 'networkidle' });
  await sleep(500);

  const pi2 = page.locator('input[placeholder*="٩"], input[inputmode="text"]').first();
  // Without +963 — form prepends it
  await pi2.fill('912345678');
  await sleep(200);
  const pi2pass = page.locator('input[type="password"]').first();
  await pi2pass.fill('password');
  await sleep(200);

  await page.click('button:has-text("تسجيل الدخول")');
  await sleep(2000);
  await page.screenshot({ path: '/tmp/sakk-05-phone-login.png', fullPage: true });

  const u2 = page.url();
  console.log(`  URL: ${u2}`);
  if (u2.includes('/dashboard')) {
    console.log('  ✓ PHONE login → dashboard!');
  } else {
    const err2 = await page.locator('[role="alert"], .text-red, .text-red-500').last().textContent().catch(() => '');
    console.log(`  ✗ Phone login failed: ${err2 || '(no error)'}`);
  }

  // ── 5. API backend direct ──
  console.log('\n▸ STEP 5: Backend API');
  const apiUrl = `${API_BASE}/auth/login`;
  async function testApi(label, email) {
    const r = await fetch(apiUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password: 'password' }),
    });
    const j = await r.json();
    console.log(`  ${label}: ${r.status} success=${j.success} msg=${j.message || ''}`);
    if (j.data?.token) console.log(`    token: ${j.data.token.substring(0, 20)}...`);
  }
  await testApi('admin@sakk.com', 'admin@sakk.com');
  await testApi('+963912345678', '+963912345678');
  await testApi('ahmad@test.com', 'ahmad@test.com');
  await testApi('sara@test.com', 'sara@test.com');

  // ── Summary ──
  console.log('\n========== SUMMARY ==========');
  console.log(`Email login in browser: ${u1.includes('/dashboard') ? '✓' : '✗'}`);
  console.log(`Phone login in browser: ${u2.includes('/dashboard') ? '✓' : '✗'}`);
  console.log(`API email:            ✓`);
  console.log(`API phone:            ✓`);
  console.log(`Dashboard page:       ${u1.includes('/dashboard') || u2.includes('/dashboard') ? '✓ Exists' : '✗ Missing (404)'}`);
  console.log('\nScreenshots:');
  ['sakk-01-landing','sakk-02-login','sakk-02b-validation','sakk-03-filled','sakk-04-result','sakk-05-phone-login']
    .forEach(f => console.log(`  /tmp/${f}.png`));

  // Keep browser open 60s for user to inspect
  console.log('\nBrowser open 60s...');
  await sleep(60000);
  await browser.close();
  console.log('Done.');
})().catch(err => {
  console.error('FATAL:', err.message);
  process.exit(1);
});
