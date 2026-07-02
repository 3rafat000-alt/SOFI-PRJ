/**
 * Auth Suite — Login, Register, Forgot/Reset Password flows.
 * Tests both Arabic and English interfaces.
 */
const cfg = require('../config.cjs');

module.exports = async (runner) => {
  const FRONT = cfg.frontendURL;
  const creds = cfg.creds;

  // ═══════════════════════════════════════════════
  // 1. Login as user (Arabic)
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'AUTH-01', title: 'تسجيل دخول مستخدم — عربي', tier: 'critical', area: 'auth', auth: null, locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      await step('الذهاب إلى صفحة تسجيل الدخول', async () => {
        await human.navigate(`${FRONT}/login`);
        await snap('login_page');
      });
      await step('كتابة البريد الإلكتروني', async () => {
        await human.type('input[type="email"]', creds.user.email);
      });
      await step('كتابة كلمة المرور', async () => {
        await human.type('input[type="password"]', creds.user.password);
      });
      await step('النقر على زر تسجيل الدخول', async () => {
        await human.click('button[type="submit"]');
        await page.waitForURL(/\/user\/dashboard|\/login/, { timeout: 15000 });
        await snap('after_login');
      });
      must('تم التوجيه إلى لوحة المستخدم', page.url().includes('/user/dashboard'));
      check('يوجد نص ترحيب', await page.locator('text=أهلاً').count() > 0 ||
        await page.locator('text=مرحباً').count() > 0);
    }
  );

  // ═══════════════════════════════════════════════
  // 2. Login as agency (Arabic)
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'AUTH-02', title: 'تسجيل دخول وكالة — عربي', tier: 'critical', area: 'auth', auth: null, locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      await step('الذهاب إلى صفحة تسجيل الدخول', async () => {
        await human.navigate(`${FRONT}/login`);
      });
      await step('اختيار تبويب وكالة', async () => {
        await human.click('button:has-text("وكالة")');
        await snap('agency_tab_selected');
      });
      await step('كتابة البريد الإلكتروني للوكالة', async () => {
        await human.type('input[type="email"]', creds.agency.email);
      });
      await step('كتابة كلمة المرور', async () => {
        await human.type('input[type="password"]', creds.agency.password);
      });
      await step('تسجيل الدخول', async () => {
        await human.click('button[type="submit"]');
        await page.waitForURL(/\/dashboard/, { timeout: 15000 });
        await snap('agency_dashboard');
      });
      must('تم التوجيه إلى لوحة الوكالة', page.url().includes('/dashboard'));
      check('تظهر لوحة التحكم', await page.locator('text=لوحة التحكم').count() > 0);
    }
  );

  // ═══════════════════════════════════════════════
  // 3. Login as admin (Arabic)
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'AUTH-03', title: 'تسجيل دخول مشرف — عربي', tier: 'critical', area: 'auth', auth: null, locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      await step('الذهاب إلى تسجيل الدخول', async () => {
        await human.navigate(`${FRONT}/login`);
      });
      await step('إدخال البريد الإلكتروني', async () => {
        await human.type('input[type="email"]', creds.admin.email);
      });
      await step('إدخال كلمة المرور', async () => {
        await human.type('input[type="password"]', creds.admin.password);
      });
      await step('تسجيل الدخول', async () => {
        await human.click('button[type="submit"]');
        await page.waitForURL(/\/admin/, { timeout: 15000 });
        await snap('admin_panel');
      });
      must('تم التوجيه إلى لوحة المشرف', page.url().includes('/admin'));
    }
  );

  // ═══════════════════════════════════════════════
  // 4. Login in English
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'AUTH-04', title: 'Login in English', tier: 'critical', area: 'i18n', auth: null, locale: 'en' },
    async ({ page, human, step, check, must, snap }) => {
      await step('Navigate to login', async () => {
        await human.navigate(`${FRONT}/login`);
        await snap('login_page_en');
      });
      // Switch to English if default is Arabic
      const enBtn = page.locator('a, button', { hasText: 'English' });
      if (await enBtn.count() > 0) {
        await human.click(enBtn);
        await human.wait();
      }
      await step('Verify English UI text', async () => {
        // Check that English text is visible
        const hasSignIn = await page.locator('text=Sign In').count() > 0 ||
          await page.locator('text=Log In').count() > 0 ||
          await page.locator('text=Welcome back').count() > 0;
        check('تظهر النصوص الإنكليزية', hasSignIn);
        await snap('english_ui');
      });
      await step('Fill credentials and log in', async () => {
        await human.type('input[type="email"]', creds.user.email);
        await human.type('input[type="password"]', creds.user.password);
        await human.click('button[type="submit"]');
        await page.waitForURL(/\/user\/dashboard/, { timeout: 15000 });
      });
      must('Redirected to dashboard', page.url().includes('/dashboard'));
      await snap('dashboard_en');
    }
  );

  // ═══════════════════════════════════════════════
  // 5. Validation — empty fields show errors
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'AUTH-05', title: 'تحقق صحة الحقول الفارغة', tier: 'high', area: 'auth', auth: null, locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      await step('الذهاب إلى تسجيل الدخول', async () => {
        await human.navigate(`${FRONT}/login`);
      });
      await step('النقر على زر الإرسال دون ملء الحقول', async () => {
        await human.click('button[type="submit"]');
        await human.wait();
        await snap('validation_errors');
      });
      // Check validation messages appear
      const emailError = await page.locator('text=البريد الإلكتروني مطلوب').count();
      const passError = await page.locator('text=كلمة المرور مطلوبة').count();
      check('يظهر خطأ البريد الإلكتروني المطلوب', emailError > 0);
      check('يظهر خطأ كلمة المرور المطلوبة', passError > 0);
      must('تظهر رسالة خطأ واحدة على الأقل', emailError > 0 || passError > 0);
    }
  );

  // ═══════════════════════════════════════════════
  // 6. Forgot password flow
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'AUTH-06', title: 'نسيت كلمة المرور — إرسال البريد', tier: 'high', area: 'auth', auth: null, locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      await step('الذهاب إلى تسجيل الدخول', async () => {
        await human.navigate(`${FRONT}/login`);
      });
      await step('النقر على رابط نسيت كلمة المرور', async () => {
        await human.click('a[href*="forgot"]');
        await page.waitForURL(/forgot-password/, { timeout: 10000 });
        await snap('forgot_password_page');
      });
      await step('إدخال البريد الإلكتروني', async () => {
        await human.type('input[type="email"]', creds.user.email);
      });
      await step('إرسال طلب إعادة التعيين', async () => {
        await human.click('button[type="submit"]');
        await snap('forgot_sent');
      });
      // Wait for success message
      const sentText = await page.locator('text=تم إرسال').count();
      check('تظهر رسالة الإرسال', sentText > 0);
    }
  );

  // ═══════════════════════════════════════════════
  // 7. Invalid email format validation
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'AUTH-07', title: 'تحقق من صيغة البريد الإلكتروني غير صالحة', tier: 'medium', area: 'auth', auth: null, locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      await step('الذهاب إلى تسجيل الدخول', async () => {
        await human.navigate(`${FRONT}/login`);
      });
      await step('إدخال بريد إلكتروني غير صالح', async () => {
        await human.type('input[type="email"]', 'not-an-email');
      });
      await step('إدخال كلمة مرور', async () => {
        await human.type('input[type="password"]', 'somepass');
      });
      await step('محاولة تسجيل الدخول', async () => {
        await human.click('button[type="submit"]');
        await human.wait();
        await snap('invalid_email_error');
      });
      const errMsg = await page.locator('text=غير صالح').count();
      check('تظهر رسالة بريد إلكتروني غير صالح', errMsg > 0);
    }
  );

  // ═══════════════════════════════════════════════
  // 8. Register page — role selection renders
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'AUTH-08', title: 'صفحة التسجيل — اختيار الدور', tier: 'high', area: 'auth', auth: null, locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      await step('الذهاب إلى صفحة التسجيل', async () => {
        await human.navigate(`${FRONT}/register`);
        await snap('register_page');
      });
      // Both role cards visible
      const userCard = await page.locator('text=مستخدم').count();
      const agencyCard = await page.locator('text=وكالة').count();
      check('تظهر بطاقة مستخدم', userCard > 0);
      check('تظهر بطاقة وكالة', agencyCard > 0);
      must('تظهر بطاقة اختيار دور واحدة على الأقل', userCard > 0 || agencyCard > 0);
    }
  );

  // ═══════════════════════════════════════════════
  // 9. Logout flow
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'AUTH-09', title: 'تسجيل الخروج', tier: 'high', area: 'auth', auth: 'user', locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      await step('الذهاب إلى الملف الشخصي', async () => {
        await human.navigate(`${FRONT}/profile`);
        await snap('profile_page');
      });
      await step('النقر على تسجيل الخروج', async () => {
        // Look for logout button in sidebar
        const logoutBtn = page.locator('button, a', { hasText: 'تسجيل خروج' });
        if (await logoutBtn.count() > 0) {
          await human.click(logoutBtn);
        } else {
          // Try navbar logout
          const navLogout = page.locator('button, a', { hasText: 'تسجيل خروج' });
          if (await navLogout.count() > 0) await human.click(navLogout);
        }
        // Wait for redirect to home or login
        await page.waitForTimeout(3000);
        await snap('after_logout');
      });
      // Should be on home page or login page
      const onHome = page.url() === `${FRONT}/` || page.url() === `${FRONT}/login`;
      check('تم تسجيل الخروج والعودة للرئيسية', onHome);
    }
  );

  console.log(`  ✅ Auth suite: ${runner.results.length} scenarios`);
};
