1. التقييم المعماري لنمط ServiceConfigOverrideProvider والمفاضلات

نمط الديناميكية وقت الإقلاع (Runtime Configuration Overriding) عبر الـ Service Provider هو النمط الأكثر أماناً وهندسة في بيئة Laravel 12 المعتمدة على Cloudflare + php-fpm، بشرط ضبطه بدقة لمنع كسر كاش الإعدادات (config:cache).

البدائل والمفاضلات المعمارية:
النمط المعماري	المزايا	العيوب	الحكم في النظام المالي (SAKK)
Runtime Override Provider (الحالي)	

• لا يكسر كاش الإعدادات.




• شفاف تماماً للمستهلكين البنية الحالية (config('services.x')).

	

• استعلام إضافي عند كل طلب (يمكن تخبئته).




• تعقيد الـ Fail-Open عند انهيار قاعدة البيانات.

	الخيار المثالي المعتمد. يحافظ على بنية الكود القديم دون تعديل طريقة عمل حزم الطرف الثالث.
Config Repository Inj. (الحقن المباشر)	• تحكم صريح على مستوى الـ Constructor للخدمة.	• يتطلب إعادة كتابة (Refactoring) لجميع الحزم والخدمات المستهلكة لاستدعاء الموديل بدل الـ Config.	مرفوض. خطر حدوث انحدارات (Regressions) عالي جداً في الأسطح المالية.
Cached Event-Driven (إعادة التحميل الحدثية)	• أداء فائق (Zero DB queries في الطلبات العادية).	• صعوبة مزامنة الكاش عبر خوادم php-fpm متعددة (Multi-node setup) بدون Redis Pub/Sub.	خيار مستقبلي للتطوير. ممتاز ولكن يتطلب بنية تحتية معقدة للمزامنة.
الخطر الكامن في الـ Fail-Open وقت الإقلاع:

بما أن الـ Provider مصمم ليكون Fail-Open عند تعطل قاعدة البيانات أو فقدان الـ APP_KEY (لضمان ألا يسقط التطبيق بالكامل)، فإن هذا يرفع خطر "الارتداد الصامت إلى بيئة التطوير" (Silent Dev-Fallback). إذا سقطت قاعدة البيانات، سيرتد النظام صامتاً لقراءة الأسرار من ملف .env؛ فلو كان ملف .env في الإنتاج يحتوي على مفاتيح بيئة التطوير (Sandbox/Stripe Test)، ستتحول العمليات الحقيقية صامتة إلى بيئة وهمية دون إطلاق إنذار.

2. توحيد الازدواجية (DRY Strategy) دون هجرة خطرة (No Big-Bang)

وجود البيانات في ثلاثة أماكن (integrations للتفعيل واللوحة، service_configs للقيم والأسرار، والـ .env كـ Fallback) يمثل بيئة خصية للـ Configuration Drift.

خطة التوحيد المتدرج الهجين (Hybrid Step-by-Step Evolution):
[لوحة التحكم / Integrations Controller]
       │
       ▼ (تحديث موحد)
┌─────────────────────────────────────────┐
│     Model: Integration (Single Source)  │
├─────────────────────────────────────────┤
│ - key (string, unique)                  │
│ - is_active (boolean)                   │
│ - credentials (encrypted:array -> text) │
└─────────────────────────────────────────┘
       │
       ▼ (Dynamic Overrider)
[config('services.provider.key') = decrypted]


الخطوة الأولى: التدمج البرمجي (Software-level Deprecation):

تثبيت جدول integrations كـ مصدر وحيد للحقيقة (Single Source of Truth) للخدمات المدارة من اللوحة، وتحويل حقل credentials إلى text مشفر بالكامل.

جعل الموديل ServiceConfig يقرأ برمجياً من جدول integrations (عبر Dynamic View أو Accessor) دون وجود جدول حقيقي له على المدى البعيد.

الخطوة الثانية: جسر الترحيل الصامت (Shadow-Read Deployment):

عند قيام الأدمن بحفظ أي إعدادات جديدة في اللوحة، يتم الكتابة في integrations وتحديث service_configs تلقائياً في نفس الترانزأكشن كحركة مؤقتة (Dual-Write).

قراءة الأسرار وقت الإقلاع تتحول لقراءة integrations؛ فلو كان السجل مفقوداً، يرتد إلى service_configs القديم (Fallback الحماية). هذا يضمن عدم الحاجة لهجرة بيانات ضخمة (Big-Bang) تسبب توقف المنصة (Downtime).

الخطوة الثالثة: تطهير الـ .env:

مسح جميع أسرار الخدمات (التي تدار من اللوحة) من ملف .env الإنتاجي فور استقرار النظام، وترك المفاتيح الأساسية فقط (مثل الـ DB_* والـ APP_KEY).

3. هندسة مفتاح القتل الذكي لـ SEV-4 (Fail-Open vs Fail-Closed)

في التطبيقات المالية، يجب أن نزن أثر التوقف (Downtime) مقابل أثر الاختراق أو تسريب الأموال.

بوابات الدفع والبطاقات (CCPayment / Stripe): يجب أن تخضع لمبدأ Fail-Closed الصارم. لو أخطأ الأدمن، يجب أن تتوقف البوابة عن استقبال المدفوعات فوراً بدلاً من تسييل أموال بطرق خاطئة أو الارتداد لـ Env قديم.

خدمات الإشعارات والرسائل (FCM / SMS): يمكن أن تخضع لمبدأ Fail-Open المريح لتجنب شلل التطبيق بالكامل لمجرد تعطل إشعار دفع (Push Notification).

التصميم البرمجي في الـ Provider:
PHP
public function boot(): void
{
    // مصفوفة الخدمات الحساسة مالياً والتي تتطلب إيقافاً صارماً بلا ارتداد
    $financialServices = ['ccpayment', 'stripe', 'stripe_issuing'];

    foreach ($financialServices as $service) {
        $integration = Integration::where('key', $service)->first();

        if ($integration) {
            if (!$integration->is_active) {
                // Fail-Closed: إيقاف صارم، تصفير الإعدادات تماماً لمنع الارتداد للـ env
                config(["services.{$service}" => [
                    'key' => null, 'secret' => null, 'active' => false
                ]]);
                continue;
            }
            
            // شحن الإعدادات المشفرة من قاعدة البيانات للرنتايم
            config(["services.{$service}" => $integration->credentials]);
        } else {
            // الصف غير موجود في قاعدة البيانات: الحماية من التدمير الذاتي
            // إذا وجد مفتاح في الـ env يعمل، إذا لم يوجد تتوقف البوابة
            if (empty(config("services.{$service}.key"))) {
                config(["services.{$service}.active" => false]);
            }
        }
    }
}


أثر هذا التصميم: إذا قام الأدمن بوضع حقل التفعيل على OFF، يتم سحق (Nullify) التكاشير في الـ Runtime تماماً، مما يمنع الـ Env Fallback من إحيائها خلف ظهر الإدارة.

4. ترتيب جولة الإصلاح (المرحلة-2) وتدقيق المخاطر
الترتيب التنفيذي المقترح للمرحلة الثانية:

إصلاح مفتاح القتل الـ SEV-4 (حرج): لأنه يمس بوابات الأموال ومسارات السحب والإيداع مباشرة، غيابه يعني فشل السيطرة على المنصة في حالات الطوارئ.

إصلاح ترويسة النداءات الـ SEV-5 (عالي): كسر الـ UX وتوليد استجابات 302 مضللة يخفي الأخطاء الحقيقية للـ Validation الـ 422، مما يجعل تتبع مشاكل الربط مستحيلاً على الأدمن.

سجل تدقيق التغييرات الـ SEV-7 (متوسط): تغيير اعتمادات بوابات الدفع بدون أثر رجعي للـ Audit Log هو ثغرة حوكمة داخلية خطيرة (Internal Fraud Risk).

تصحيح زر فحص الاتصال الـ SEV-6 (منخفض): تحويله من فحص شكلي صامت إلى Ping حقيقي يطلب الـ Balance من CCPayment أو يتأكد من الـ SMTP Handshake.

مخاطر قلّلنا من وزنها المعماري:

خطر الـ Memory Leaks تحت الـ php-fpm: الـ Provider يقوم بفك تشفير الأسرار (Crypt::decryptString) عند كل طلب لجميع الخدمات دفعة واحدة. تحت ضغط آلاف الطلبات المتزامنة عبر Cloudflare، هذا يرفع استهلاك المعالج والذاكرة (CPU/Memory overhead) بشكل حاد نتيجة لعمليات التشفير المتكررة.

الحل الوقائي: يجب إجبار الـ ServiceConfigOverrideProvider على استخدام كاش داخلي مؤقت ومحمي (Tagged Encrypted Cache) يسقط فوراً عند تحديث الأدمن لأي خدمة عبر الـ Saved Event الخاص بالموديل.