/**
 * Property Suite — Property listing detail, create/edit flows.
 * Tests public property detail page + agency create/edit property.
 */
const cfg = require('../config.cjs');

module.exports = async (runner) => {
  const FRONT = cfg.frontendURL;

  // ═══════════════════════════════════════════════
  // 1. Public property detail page
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'PRP-01', title: 'صفحة تفاصيل العقار العام', tier: 'critical', area: 'property', locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      await step('الذهاب إلى صفحة العقارات', async () => {
        await human.navigate(`${FRONT}/properties`);
        await human.wait({ min: 500, max: 1000 });
      });
      await step('النقر على أول عقار', async () => {
        const firstProp = page.locator('a[href*="/properties/"], a[href*="/property/"]').first();
        if (await firstProp.count() > 0) {
          await human.click(firstProp);
          await human.wait({ min: 500, max: 1000 });
          await snap('property_detail');
          check('تم فتح صفحة تفاصيل العقار', page.url().includes('/properties/') || page.url().includes('/property/'));
        } else {
          check('لا يوجد عقارات للمعاينة', false);
          must('تم زيارة صفحة العقارات', true);
        }
      });
    }
  );

  // ═══════════════════════════════════════════════
  // 2. Agency — property create page renders
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'PRP-02', title: 'الوكالة — صفحة إضافة عقار', tier: 'critical', area: 'property', auth: 'agency', locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      await step('الذهاب إلى إضافة عقار', async () => {
        await human.navigate(`${FRONT}/dashboard/properties/create`);
        await snap('property_create');
      });
      const formFields = await page.locator('input, textarea, select').count();
      check('يوجد نموذج إضافة عقار يحتوي على حقول', formFields > 0);
    }
  );

  // ═══════════════════════════════════════════════
  // 3. Agency — property create form validation
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'PRP-03', title: 'الوكالة — التحقق من صحة نموذج العقار', tier: 'high', area: 'property', auth: 'agency', locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      await step('الذهاب إلى إضافة عقار', async () => {
        await human.navigate(`${FRONT}/dashboard/properties/create`);
      });
      await step('محاولة إرسال النموذج فارغاً', async () => {
        const submitBtn = page.locator('button[type="submit"], button:has-text("حفظ"), button:has-text("إضافة")').first();
        if (await submitBtn.count() > 0) {
          await human.click(submitBtn);
          await human.wait({ min: 500, max: 1000 });
          await snap('property_create_validation');
        }
      });
      // Check if validation errors appear
      const validationErrors = await page.locator('.text-red, .invalid-feedback, [role="alert"]').count();
      check('تظهر رسائل التحقق من صحة الحقول', validationErrors > 0);
    }
  );

  // ═══════════════════════════════════════════════
  // 4. Agency — property edit page
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'PRP-04', title: 'الوكالة — صفحة تعديل عقار', tier: 'high', area: 'property', auth: 'agency', locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      await step('الذهاب إلى قائمة العقارات', async () => {
        await human.navigate(`${FRONT}/dashboard/properties`);
      });
      await step('النقر على تعديل أول عقار', async () => {
        const editBtn = page.locator('a[href*="/edit"], button:has-text("تعديل")').first();
        if (await editBtn.count() > 0) {
          await human.click(editBtn);
          await human.wait({ min: 500, max: 1000 });
          await snap('property_edit');
          check('تم فتح صفحة تعديل العقار', page.url().includes('/edit'));
        } else {
          check('لا يوجد أزرار تعديل — قد لا توجد عقارات', false);
        }
      });
    }
  );

  // ═══════════════════════════════════════════════
  // 5. Agency — property media/images
  // ═══════════════════════════════════════════════
  await runner.scenario(
    { id: 'PRP-05', title: 'الوكالة — رفع الصور والوسائط', tier: 'medium', area: 'property', auth: 'agency', locale: 'ar' },
    async ({ page, human, step, check, must, snap }) => {
      await step('الذهاب إلى إضافة عقار', async () => {
        await human.navigate(`${FRONT}/dashboard/properties/create`);
        await snap('property_media_upload');
      });
      const fileInput = page.locator('input[type="file"]');
      const dropzone = page.locator('[data-testid="dropzone"], .dropzone, [class*="upload"]');
      check('يوجد خيار رفع الصور', await fileInput.count() > 0 || await dropzone.count() > 0);
    }
  );

  console.log(`  ✅ Property suite: ${runner.results.length} scenarios`);
};
