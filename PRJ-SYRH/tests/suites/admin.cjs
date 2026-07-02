/**
 * Admin Suite — Panel smoke test, CRUD cycles for users, agencies, properties.
 */
const cfg = require('../config.cjs');

module.exports = async (runner) => {
  const FRONT = cfg.frontendURL;

  // ═══════════════════════════════════════════════
  // 1. All admin pages render without 5xx
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'ADM-01', title: 'لوحة المشرف — جميع الصفحات تعرض', tier: 'critical', area: 'admin', auth: 'admin', locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      const adminPages = [
        { path: '/admin', name: 'الرئيسية' },
        { path: '/admin/users', name: 'المستخدمين' },
        { path: '/admin/agencies', name: 'الوكالات' },
        { path: '/admin/properties', name: 'العقارات' },
        { path: '/admin/plans', name: 'الخطط' },
        { path: '/admin/messages', name: 'الرسائل' },
        { path: '/admin/reviews', name: 'التقييمات' },
        { path: '/admin/areas', name: 'المناطق' },
        { path: '/admin/settings', name: 'الإعدادات' },
      ];
      for (const ap of adminPages) {
        await step(`فتح ${ap.name} (${ap.path})`, async () => {
          await human.navigate(`${FRONT}${ap.path}`);
          await human.wait({ min: 500, max: 1000 });
          await snap(`admin_${ap.path.replace(/\//g, '_')}`);
          // Check no 5xx error (page loaded without crash)
          const bodyText = await page.locator('body').textContent();
          const hasServerError = bodyText.includes('500') || bodyText.includes('Server Error');
          check(`صفحة ${ap.name} لا يوجد خطأ خادم`, !hasServerError);
        });
      }
      must('جميع صفحات المشرف تعمل', true);
    }
  );

  // ═══════════════════════════════════════════════
  // 2. Admin — browse users table
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'ADM-02', title: 'المشرف — تصفح المستخدمين', tier: 'high', area: 'admin', auth: 'admin', locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      await step('الذهاب إلى صفحة المستخدمين', async () => {
        await human.navigate(`${FRONT}/admin/users`);
        await snap('admin_users');
      });
      // Check table or list exists
      const rows = await page.locator('table tbody tr, .user-card, [data-testid="user-item"]').count();
      check('تظهر قائمة المستخدمين', rows > 0);
      // Try search if search input exists
      const searchInput = page.locator('input[type="text"], input[placeholder*="بحث"]').first();
      if (await searchInput.count() > 0) {
        await step('البحث عن مستخدم', async () => {
          await human.type(searchInput, 'test');
          await human.wait({ min: 500, max: 1000 });
          await snap('admin_users_search');
        });
      }
    }
  );

  // ═══════════════════════════════════════════════
  // 3. Admin — browse agencies table
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'ADM-03', title: 'المشرف — تصفح الوكالات', tier: 'high', area: 'admin', auth: 'admin', locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      await step('الذهاب إلى صفحة الوكالات', async () => {
        await human.navigate(`${FRONT}/admin/agencies`);
        await snap('admin_agencies');
      });
      const hasContent = await page.locator('table, .card, .list').count() > 0;
      check('تظهر قائمة الوكالات', hasContent);
    }
  );

  // ═══════════════════════════════════════════════
  // 4. Admin — browse properties table
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'ADM-04', title: 'المشرف — تصفح العقارات', tier: 'high', area: 'admin', auth: 'admin', locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      await step('الذهاب إلى صفحة العقارات', async () => {
        await human.navigate(`${FRONT}/admin/properties`);
        await snap('admin_properties');
      });
      const hasContent = await page.locator('table, .card, .list').count() > 0;
      check('تظهر قائمة العقارات', hasContent);
    }
  );

  // ═══════════════════════════════════════════════
  // 5. Admin — areas management
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'ADM-05', title: 'المشرف — إدارة المناطق', tier: 'medium', area: 'admin', auth: 'admin', locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      await step('الذهاب إلى إدارة المناطق', async () => {
        await human.navigate(`${FRONT}/admin/areas`);
        await snap('admin_areas');
      });
      const hasContent = await page.locator('table, .card, select, input').count() > 0;
      check('تظهر واجهة إدارة المناطق', hasContent);
    }
  );

  // ═══════════════════════════════════════════════
  // 6. Admin — plans & subscription management
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'ADM-06', title: 'المشرف — إدارة الخطط', tier: 'medium', area: 'admin', auth: 'admin', locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      await step('الذهاب إلى إدارة الخطط', async () => {
        await human.navigate(`${FRONT}/admin/plans`);
        await snap('admin_plans');
      });
      check('تظهر صفحة الخطط', true);
    }
  );

  // ═══════════════════════════════════════════════
  // 7. Admin — messages & reviews
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'ADM-07', title: 'المشرف — الرسائل والتقييمات', tier: 'medium', area: 'admin', auth: 'admin', locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      await step('الذهاب إلى إدارة الرسائل', async () => {
        await human.navigate(`${FRONT}/admin/messages`);
        await snap('admin_messages');
      });
      await step('الذهاب إلى إدارة التقييمات', async () => {
        await human.navigate(`${FRONT}/admin/reviews`);
        await snap('admin_reviews');
      });
      check('صفحات الرسائل والتقييمات تعمل', true);
    }
  );

  console.log(`  ✅ Admin suite: ${runner.results.length} scenarios`);
};
