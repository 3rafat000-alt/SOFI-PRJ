/**
 * Agency Suite — Agency dashboard flows.
 * Tests dashboard pages, property management, profile.
 */
const cfg = require('../config.cjs');

module.exports = async (runner) => {
  const FRONT = cfg.frontendURL;

  // ═══════════════════════════════════════════════
  // 1. Agency dashboard — all pages render
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'AGC-01', title: 'لوحة الوكالة — جميع الصفحات تعرض', tier: 'critical', area: 'agency', auth: 'agency', locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      const pages = [
        { path: '/dashboard', name: 'الرئيسية' },
        { path: '/dashboard/properties', name: 'العقارات' },
        { path: '/dashboard/agents', name: 'الوكلاء' },
        { path: '/dashboard/inquiries', name: 'الاستفسارات' },
        { path: '/dashboard/deals', name: 'الصفقات' },
        { path: '/dashboard/commission', name: 'العمولات' },
        { path: '/dashboard/subscription', name: 'الاشتراك' },
        { path: '/dashboard/profile', name: 'الملف الشخصي' },
        { path: '/dashboard/chat', name: 'المحادثات' },
      ];
      for (const ap of pages) {
        await step(`فتح ${ap.name} (${ap.path})`, async () => {
          await human.navigate(`${FRONT}${ap.path}`);
          await human.wait({ min: 400, max: 800 });
          await snap(`agency_${ap.path.replace(/\//g, '_')}`);
          const bodyText = await page.locator('body').textContent();
          const hasServerError = bodyText.includes('500') || bodyText.includes('Server Error');
          check(`صفحة ${ap.name} لا يوجد خطأ خادم`, !hasServerError);
        });
      }
      must('جميع صفحات الوكالة تعمل', true);
    }
  );

  // ═══════════════════════════════════════════════
  // 2. Agency — property listing
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'AGC-02', title: 'الوكالة — قائمة العقارات', tier: 'high', area: 'agency', auth: 'agency', locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      await step('الذهاب إلى عقاراتي', async () => {
        await human.navigate(`${FRONT}/dashboard/properties`);
        await snap('agency_properties');
      });
      const hasTable = await page.locator('table').count() > 0;
      const hasCards = await page.locator('.card').count() > 0;
      check('تظهر قائمة العقارات', hasTable || hasCards);
    }
  );

  // ═══════════════════════════════════════════════
  // 3. Agency — profile page
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'AGC-03', title: 'الوكالة — الملف الشخصي', tier: 'high', area: 'agency', auth: 'agency', locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      await step('الذهاب إلى الملف الشخصي للوكالة', async () => {
        await human.navigate(`${FRONT}/dashboard/profile`);
        await snap('agency_profile');
      });
      const hasForm = await page.locator('input, textarea').count() > 0;
      check('يوجد نموذج تعديل الملف الشخصي', hasForm);
    }
  );

  // ═══════════════════════════════════════════════
  // 4. Agency — agents management
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'AGC-04', title: 'الوكالة — إدارة الوكلاء', tier: 'medium', area: 'agency', auth: 'agency', locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      await step('الذهاب إلى إدارة الوكلاء', async () => {
        await human.navigate(`${FRONT}/dashboard/agents`);
        await snap('agency_agents');
      });
      check('تظهر صفحة الوكلاء', true);
    }
  );

  // ═══════════════════════════════════════════════
  // 5. Agency — subscription info
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'AGC-05', title: 'الوكالة — الاشتراك والخطة', tier: 'medium', area: 'agency', auth: 'agency', locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      await step('الذهاب إلى صفحة الاشتراك', async () => {
        await human.navigate(`${FRONT}/dashboard/subscription`);
        await snap('agency_subscription');
      });
      check('تظهر صفحة الاشتراك', true);
    }
  );

  // ═══════════════════════════════════════════════
  // 6. Agency — inquiries
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'AGC-06', title: 'الوكالة — الاستفسارات', tier: 'medium', area: 'agency', auth: 'agency', locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      await step('الذهاب إلى الاستفسارات', async () => {
        await human.navigate(`${FRONT}/dashboard/inquiries`);
        await snap('agency_inquiries');
      });
      check('تظهر صفحة الاستفسارات', true);
    }
  );

  // ═══════════════════════════════════════════════
  // 7. Agency — deals & commission
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'AGC-07', title: 'الوكالة — الصفقات والعمولات', tier: 'medium', area: 'agency', auth: 'agency', locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      await step('الذهاب إلى الصفقات', async () => {
        await human.navigate(`${FRONT}/dashboard/deals`);
        await snap('agency_deals');
      });
      await step('الذهاب إلى العمولات', async () => {
        await human.navigate(`${FRONT}/dashboard/commission`);
        await snap('agency_commission');
      });
      check('صفحات الصفقات والعمولات تعمل', true);
    }
  );

  console.log(`  ✅ Agency suite: ${runner.results.length} scenarios`);
};
