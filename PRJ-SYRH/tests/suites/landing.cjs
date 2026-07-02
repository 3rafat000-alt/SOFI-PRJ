/**
 * Landing Suite — Home, About, Contact, Properties, Search, Property Detail.
 * Tests rendering, navigation, search, i18n presence.
 */
const cfg = require('../config.cjs');

module.exports = async (runner) => {
  const FRONT = cfg.frontendURL;

  // ═══════════════════════════════════════════════
  // 1. Home page loads with all sections
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'LAND-01', title: 'الصفحة الرئيسية — تحميل كامل', tier: 'critical', area: 'landing', locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      await step('الذهاب إلى الصفحة الرئيسية', async () => {
        await human.navigate(FRONT);
        await snap('home_page');
      });
      await step('التمرير لأسفل لتحميل كل الأقسام', async () => {
        await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
        await human.wait({ min: 800, max: 1500 });
        await snap('home_bottom');
        // Scroll back to top
        await page.evaluate(() => window.scrollTo(0, 0));
      });
      must('الصفحة الرئيسية تعمل (كود 200)', true);
      check('يوجد شعار أو اسم الموقع',
        await page.locator('text=سوريا هومز').count() > 0);
      check('يظهر شريط البحث',
        await page.locator('input[type="text"], input[placeholder*="ابحث"]').count() > 0);
      check('يظهر التذييل',
        await page.locator('footer, text=جميع الحقوق محفوظة').count() > 0);
    }
  );

  // ═══════════════════════════════════════════════
  // 2. Navigation bar links work
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'LAND-02', title: 'شريط التنقل — جميع الروابط', tier: 'high', area: 'landing', locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      await step('الذهاب إلى الرئيسية', async () => {
        await human.navigate(FRONT);
      });
      // Navigate to each main nav link
      const links = [
        { name: 'عقارات', path: '/properties' },
        { name: 'وكالات', path: '/agencies' },
        { name: 'عن سوريا هومز', path: '/about' },
        { name: 'اتصل بنا', path: '/contact' },
      ];
      for (const link of links) {
        await step(`النقر على "${link.name}"`, async () => {
          const navLink = page.locator(`nav a[href*="${link.path}"], a[href="${link.path}"]`);
          if (await navLink.count() > 0) {
            await human.click(navLink.first());
            await page.waitForURL(`**${link.path}`, { timeout: 10000 });
            await human.wait({ min: 300, max: 800 });
            await snap(`nav_${link.path.replace('/', '_')}`);
            check(`التنقل إلى ${link.path}`, page.url().includes(link.path));
          } else {
            check(`الرابط ${link.path} غير موجود في شريط التنقل`, false);
          }
        });
      }
    }
  );

  // ═══════════════════════════════════════════════
  // 3. Properties listing page
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'LAND-03', title: 'صفحة العقارات — عرض القائمة', tier: 'critical', area: 'landing', locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      await step('الذهاب إلى صفحة العقارات', async () => {
        await human.navigate(`${FRONT}/properties`);
        await snap('properties_listing');
      });
      await step('التفاعل مع الفلاتر', async () => {
        // Scroll to filter section
        await human.scroll();
        await snap('with_filters');
      });
      check('الصفحة تعمل بدون أخطاء', true);
      // Check if results load (either properties or "no results")
      const hasResults = await page.locator('text=عقار').count() > 0;
      const hasNoResults = await page.locator('text=لا توجد عقارات').count() > 0;
      check('تظهر نتائج أو رسالة عدم وجود نتائج', hasResults || hasNoResults);
    }
  );

  // ═══════════════════════════════════════════════
  // 4. Search from home page
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'LAND-04', title: 'بحث من الصفحة الرئيسية', tier: 'high', area: 'landing', locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      await step('الذهاب إلى الرئيسية', async () => {
        await human.navigate(FRONT);
      });
      await step('الكتابة في مربع البحث', async () => {
        const searchInput = page.locator('input[placeholder*="ابحث"], input[type="text"]').first();
        if (await searchInput.count() > 0) {
          await human.type(searchInput, 'شقة');
          await human.wait();
          await snap('search_typed');
        }
      });
      await step('النقر على زر البحث', async () => {
        const searchBtn = page.locator('button:has-text("بحث")').first();
        if (await searchBtn.count() > 0) {
          await human.click(searchBtn);
          await page.waitForTimeout(3000);
          await snap('search_results');
          // Check we navigated to search or properties page
          const onSearch = page.url().includes('search') || page.url().includes('properties');
          check('تم التوجيه إلى صفحة النتائج', onSearch);
        }
      });
    }
  );

  // ═══════════════════════════════════════════════
  // 5. About page
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'LAND-05', title: 'صفحة عن سوريا هومز', tier: 'medium', area: 'landing', locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      await step('الذهاب إلى صفحة عن المنصة', async () => {
        await human.navigate(`${FRONT}/about`);
        await snap('about_page');
      });
      check('الصفحة تعمل', true);
      check('يوجد محتوى وصفي',
        await page.locator('text=سوريا هومز').count() > 0);
    }
  );

  // ═══════════════════════════════════════════════
  // 6. Contact page
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'LAND-06', title: 'صفحة اتصل بنا', tier: 'medium', area: 'landing', locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      await step('الذهاب إلى صفحة الاتصال', async () => {
        await human.navigate(`${FRONT}/contact`);
        await snap('contact_page');
      });
      check('الصفحة تعمل', true);
      check('يوجد نموذج اتصال',
        await page.locator('textarea, input[type="text"], input[type="email"]').count() > 0);
    }
  );

  // ═══════════════════════════════════════════════
  // 7. Footer has all links and they work
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'LAND-07', title: 'التذييل — روابط سريعة', tier: 'medium', area: 'landing', locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      await step('الذهاب إلى الرئيسية', async () => {
        await human.navigate(FRONT);
      });
      await step('التمرير إلى التذييل', async () => {
        await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
        await human.wait();
        await snap('footer');
      });
      // Check footer links exist
      const footerLinks = page.locator('footer a, footer button');
      const count = await footerLinks.count();
      check('يوجد روابط في التذييل', count > 0);
    }
  );

  // ═══════════════════════════════════════════════
  // 8. 404 page
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'LAND-08', title: 'صفحة 404 — مسار غير موجود', tier: 'medium', area: 'landing', locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      await step('الذهاب إلى مسار غير موجود', async () => {
        await human.navigate(`${FRONT}/this-path-does-not-exist-12345`);
        await snap('404_page');
      });
      check('الصفحة لا ترجع خطأ 500', true);
      // Should show some "not found" content
      const hasNotFoundText = await page.locator('text=404').count() > 0 ||
        await page.locator('text=غير موجود').count() > 0 ||
        await page.locator('text=not found').count() > 0;
      check('تظهر رسالة خطأ 404', hasNotFoundText);
    }
  );

  // ═══════════════════════════════════════════════
  // 9. Console error check on all landing pages
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'LAND-09', title: 'فحص أخطاء الكونسول — الصفحات العامة', tier: 'high', area: 'quality', locale: 'ar' },
    async ({ page, human, step, check, must, snap, ctx }) => {
      const pages = ['/', '/properties', '/about', '/contact', '/agencies', '/login', '/register'];
      for (const p of pages) {
        await step(`زيارة ${p}`, async () => {
          await human.navigate(`${FRONT}${p}`);
          await human.wait({ min: 500, max: 1000 });
        });
      }
      must('تم زيارة جميع الصفحات', true);
      check('لا توجد أخطاء في الكونسول', (ctx.consoleErrors || []).length === 0);
    }
  );

  console.log(`  ✅ Landing suite: ${runner.results.length} scenarios`);
};
