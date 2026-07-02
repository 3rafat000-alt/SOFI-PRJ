# PERSONAS — FileVault (SAAS-043)
> Owner: UX Researcher · Gate 1

## Primary Persona: مريم — محامية (شريكة في مكتب محاماة)
- **Role:** شريكة في مكتب محاماة (15 محامياً)، تتعامل مع مستندات حساسة يومياً
- **Context:** تشارك عقوداً ومذكرات قانونية مع عملاء وشركاء، تحتاج أماناً وسرية تامين
- **Goals:** ضمان سرية المستندات، تحكم دقيق بصلاحيات الوصول، سجل تدقيق كامل لكل ملف
- **Frustrations:** Dropbox لا يوفر صلاحيات على مستوى الملف، WeTransfer غير آمن، Google Drive لا يدعم الصلاحيات الدقيقة
- **JTBD:** "When sharing confidential legal documents, I want to control exactly who can view, download, or edit each file so that client confidentiality is never compromised."
- **Digital fluency:** Medium

## Secondary Persona: سامي — مدير فريق تسويق
- **Role:** مدير إبداعي في شركة إعلانات (30 موظفاً)
- **Context:** يشارك ملفات كبيرة (صور، فيديوهات، تصاميم) مع العملاء والفرق الإبداعية
- **Goals:** إرسال روابط سريعة للعملاء للموافقة، تتبع التحميلات، صلاحية محددة للروابط
- **Frustrations:** الملفات الكبيرة (2GB+) تتعطل في الإيميل، العملاء يضيعون الروابط، لا يعرف من حمّل الملف
- **JTBD:** "When sending large creative assets to clients, I want share-once links with expiry tracking so that I know exactly when and who accessed them."
- **Digital fluency:** High

## Pain/Gain Table
| Pain | Severity | Gain of Solving | Priority |
|------|----------|-----------------|----------|
| الحلول الحالية غير آمنة للبيانات الحساسة | Critical | تشفير AES-256، روابط آمنة | P0 |
| لا يوجد صلاحيات على مستوى الملف | High | تحكم دقيق (view/download/edit/delete) | P0 |
| صعوبة إبطال الوصول بعد المشاركة | High | إبطال فوري + صلاحية زمنية | P1 |
| الملفات الكبيرة تتعطل | Medium | رفع مجزأ (chunked) حتى 5GB | P1 |
| لا يوجد سجل تدقيق | Medium | سجل كامل (من، متى، ماذا) | P2 |

## Competitor Comparison
| Competitor | Strengths | Weaknesses | Gap for Us |
|------------|-----------|------------|------------|
| Dropbox Business | تكامل واسع، موثوق | $25/شهر/مستخدم، صلاحيات محدودة | صلاحيات دقيقة، سعر أقل |
| Google Drive | مجاني، تعاوني | صلاحيات محدودة، لا تشفير طرفي | تشفير + سجل تدقيق |
| WeTransfer | بسيط، سريع | غير آمن، حد 2GB | تشفير لحد 5GB |
| Tresorit | آمن جداً، تشفير طرفي | $50/شهر، غالي جداً | أمان Tresorit بسعر نصفه |
