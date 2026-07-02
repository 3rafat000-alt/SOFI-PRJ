خطة التنفيذ المعمارية لـ SOFI AI: إغلاق إصلاحات المرحلة الثانية (SAKK)

هذا التوجيه موجه مباشرة لمحرك الأتمتة الحتمي في SOFI AI. الترتيب المعروض مصمم هندسياً لتجنب العموميات، وحماية حالة المنظومة من الانحدار (Regression)، وضمان أعلى درجات السلامة المالية قبل الانتقال للإنتاج.

مصفوفة الترتيب الآمن والتبعيات الهيكلية
الأولوية	البند البرمجي	التبعيات المستقيمة (Dependencies)	الحاجة لهجرة قاعدة البيانات (Migration)	وقت التوقف (Downtime)
1	[SEV-5] وسيط ForceJsonResponses وإصلاح الأدمن	لا يوجد (مستقل)	لا	لا
2	[SEV-4] إغلاق البوابات الصارم Fail-CLOSED	لا يوجد (مستقل)	لا	لا
3	تحسين الأداء N+1 في الـ PayrollService	بند 1 (لاكتشاف أخطاء التحقق أثناء الاختبار)	لا	لا
4	قفل أسطر أسعار الصرف حتمياً lockForUpdate	بند 3 (ضمان استقرار المعاملات الذرية)	لا	لا
5	وسيط منع التكرار الشامل X-Idempotency-Key	بند 1 وبند 4 (لحماية مسارات التحويل المقفلة)	لا	لا
6	مدقق سلامة الدفتر المالي Ledger Integrity Auditor	جميع البنود السابقة (يعتمد على استقرار البيانات)	نعم (جدول ضبط النظام)	لا
تفاصيل الخطوات البرمجية ومخاطر الانحدار
1. وسيط ForceJsonResponses وإصلاح نداءات لوحة الإدارة [SEV-5]

السبب: غياب ترويسة Accept يتسبب في إعادة توجيه 302 عند حدوث أخطاء 422، مما يعمي تطبيقات العميل والـ Fetch عن تفاصيل الفشل ويقوض جودة المراقبة التلقائية.

الأثر المتوقع: استجابات JSON حتمية وصارمة بمعدل 422 لكافة مسارات الـ API، مع تمكين الـ Flutter من التقاط الـ Payload الفعلي للخطأ.

مخاطر الانحدار: صفر مخاطر على مسارات الويب، شريطة تطبيق الفلترة الحصرية على البادئة api/*.

خطوة التنفيذ الملموسة:

قم بإنشاء ملف الوسيط في المسار الحقيقي app/Http/Middleware/ForceJsonResponses.php:

PHP
<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

class ForceJsonResponses {
    public function handle(Request $request, Closure $next) {
        if ($request->is('api/*')) {
            $request->headers->set('Accept', 'application/json');
        }
        return $next($request);
    }
}


سجّل الوسيط في ملف النواة المعاصر لـ Laravel 12 داخل bootstrap/app.php:

PHP
$withMiddleware->prepend(\App\Http\Middleware/ForceJsonResponses::class);


ابحث في ملفات لوحة التحكم (Blade + Alpine.js) عن نداءات الـ fetch التسعة (9) التي تستهدف الـ APIs، وقم بحقن الترويسة يدوياً داخل كائن الإعدادات:

JavaScript
headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' }


خطوة التحقق والاختبار أوتوماتيكياً: إرسال طلب POST عمداً ببيانات فارغة إلى مسار الـ API الموثق دون تمرير أي ترويسات، والتحقق من أن كود الاستجابة هو 422 حصراً وليس 302.

2. نمط الإغلاق الصارم Fail-CLOSED للبوابات المالية [SEV-4]

السبب: منع إحياء البوابات المالية يدوياً أو عشوائياً بواسطة قيم ملف الـ .env الاحتياطية في حال تم إلغاء تنشيطها إدارياً من قاعدة البيانات.

الأثر المتوقع: كسر خطوط الاتصال فورياً (Circuit Breaking) ومنع تسرب أي أموال عبر بوابات Stripe أو CCPayment المعطلة.

مخاطر الانحدار: تعطل مؤقت في حال كتابة اسم بوابة بشكل خاطئ ضمن مصفوفة التحقق.

خطوة التنفيذ الملموسة:
تحديث ملف مقدم الخدمات الحيوية app/Providers/ServiceConfigOverrideProvider.php كالتالي:

PHP
<?php
namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use App\Models\Integration;

class ServiceConfigOverrideProvider extends ServiceProvider {
    public function boot() {
        $financialGateways = ['ccpayment', 'stripe', 'stripe_issuing'];

        foreach ($financialGateways as $gateway) {
            $integration = Integration::where('slug', $gateway)->first();

            if (!$integration || !$integration->is_active) {
                // 1. سحق الإعدادات الحية تماماً في الـ Runtime
                config(["services.{$gateway}" => [
                    'secret' => 'FORBIDDEN_FALLBACK_BLOCKED',
                    'key' => 'FORBIDDEN_FALLBACK_BLOCKED',
                    'is_active' => false
                ]]);

                // 2. ربط الـ Interface بـ StubException يرمي استثناء قاتلاً
                $this->app->singleton("App\Interfaces\Gateways\\" . ucfirst($gateway) . "Interface", function() use ($gateway) {
                    return new class {
                        public function __call($name, $arguments) {
                            throw new \RuntimeException("Security Exception: Access to disabled financial gateway [{$gateway}] is explicitly blocked.");
                        }
                    };
                });
            }
        }
    }
}


خطوة التحقق والاختبار أوتوماتيكياً: تعيين قيمة is_active = 0 للبوابة stripe في قاعدة البيانات، ثم استدعاء المحرك المالي الخاص بها؛ يجب التأكد من قذف الاستثناء المخصص RuntimeException بنجاح واحتواء السجلات على نص رسالة الحظر الحتمي.

3. معالجة اختناق الـ N+1 في خدمة الـ PayrollService

السبب: تكرار قراءة بيانات محافظ الموظفين فرادى داخل حلقة الصرف المليونية يتسبب في استنزاف تماسك اتصالات قاعدة البيانات (DB Pool Connection Choking).

الأثر المتوقع: خفض زمن تنفيذ عمليات الصرف الجماعي للرواتب بنسبة تقارب 75% وتقليص استعلامات الـ SQL إلى استعلام دفعي موحد.

مخاطر الانحدار: صفر مخاطر بشرط صحة مصفوفة العلاقات المستدعاة.

خطوة التنفيذ الملموسة:
انتقل إلى app/Services/PayrollService.php وتحديداً دالة المعالجة الجماعية للحزمة (processBatch). قبل الدخول في حلقة الـ foreach التي تدور على أسطر الرواتب، قم بحقن السطر التالي لإجبار النظام على التحميل المسبق:

PHP
$payrollBatch->loadMissing(['items.employee.wallets']);


خطوة التحقق والاختبار أوتوماتيكياً: تشغيل الحزمة البرمجية للاختبار بالتوازي مع تفعيل سجل الاستعلامات المباشر؛ التأكد من عدم تكرار استعلام الاختيار الخاص بـ wallets لكل موظف على حدة.

4. قفل أسطر أسعار الصرف الحية lockForUpdate

السبب: الاعتماد على البيانات الكاشية أو قيم القراءة العادية يفتح نافذة زمنية ضيقة (Race Condition Windows) تتيح للمستخدمين استغلال فروقات الأسعار لتوليد أرباح غير مشروعة (Arbitrage Exploits).

الأثر المتوقع: حماية رأس المال من التلاعب والتأكد من توافق القيمة الفعلية للخصم والإيداع في نفس جزء الميكروثانية.

مخاطر الانحدار: حدوث تأخر طفيف (Latency) في عمليات التحويل المتزامنة نظراً لانتظار القفل، ولكن هذا هو السلوك الآمن والمطلوب لسلامة النقدية.

خطوة التنفيذ الملموسة:
في ملف معالجة تبديل العملات app/Services/ExchangeRateService.php (أو المتحكم ذو الصلة)، قم بإلغاء نداء الـ Cache واستبداله بقفل حتمي مغلف داخل عملية مالية موحدة (Database Transaction):

PHP
use Illuminate\Support\Facades\DB;

$finalAmount = DB::transaction(function() use ($fromCurrency, $toCurrency, $amount, $wallet) {
    $rateRow = DB::table('exchange_rates')
        ->where('from_currency', $fromCurrency)
        ->where('to_currency', $toCurrency)
        ->lockForUpdate()
        ->first();

    if (!$rateRow) {
        throw new \InvalidArgumentException("Exchange rate pair not found.");
    }

    // إكمال العمليات الحسابية والخصم الفوري من المحفظة هنا...
    return $amount * $rateRow->rate;
});


خطوة التحقق والاختبار أوتوماتيكياً: محاكاة عمليتين متزامنتين في نفس الوقت عبر اختبار الضغط (Stress Test) لتبديل نفس فئة العملة؛ التحقق من وقوف العملية الثانية في حالة انتظام صامت حتى انتهاء عملية القفل الأولى كلياً.

5. وسيط منع التكرار الشامل لعمليات المال الخارج X-Idempotency-Key

السبب: معالجة مشكلة تكرار الضغط على أزرار الدفع والسحب نتيجة ضعف شبكات المحمول المحلية في سوريا، مما يتسبب في خصم مزدوج غير مقصود من أرصدة المستخدمين والتجار.

الأثر المتوقع: حظر صارم لنداءات المعاملات المالية المكررة التي تحمل نفس بصمة النية البرمجية خلال نافذة 60 ثانية.

مخاطر الانحدار: إذا فشل تطبيق Flutter في توليد مفاتيح عشوائية فريدة لكل عملية جديدة كلياً، قد يتم حظر طلبات المستخدمين الشرعية المتتالية.

خطوة التنفيذ الملموسة:

قم بإنشاء ملف الوسيط app/Http/Middleware/EnsureIdempotentRequest.php:

PHP
<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EnsureIdempotentRequest {
    public function handle(Request $request, Closure $next) {
        if (!$request->hasHeader('X-Idempotency-Key')) {
            return response()->json(['error' => 'Header [X-Idempotency-Key] is strictly required for financial mutations.'], 422);
        }

        $key = 'idempotency:' . $request->header('X-Idempotency-Key');

        // استخدام كاش Redis لقفل المفتاح لمدة 60 ثانية ذرياً
        $lockAcquired = Cache::lock($key, 60)->get();

        if (!$lockAcquired) {
            return response()->json([
                'error' => 'Conflict: Duplicate financial request detected. Transaction is already processing.',
                'code' => 'DUPLICATE_MUTATION_BLOCKED'
            ], 409); // كود التعارض الصريح
        }

        return $next($request);
    }
}


طبّق هذا الوسيط حصرياً على مجموعات مسارات المال الخارج (Outbound Money Routing Group) في ملف الـ Routes:

PHP
Route::middleware([\App\Http\Middleware\EnsureIdempotentRequest::class])->group(function () {
    Route::post('/transfer', [TransferController::class, 'store']);
    Route::post('/merchant/withdraw', [MerchantWithdrawalController::class, 'store']);
    Route::post('/crypto/withdraw', [CryptoWithdrawalController::class, 'store']);
});


خطوة التحقق والاختبار أوتوماتيكياً: إرسال طلبين متطابقين متلاحقين بفارق زمن 100 ميكروثانية يحملان نفس قيمة رأس الـ X-Idempotency-Key؛ تأكيد نجاح الاستجابة الأولى وفشل الثانية فوراً بمعدل الخطأ 409 Conflict.

6. مهمة مدقق سلامة الدفتر المالي الأوتوماتيكية Ledger Integrity Auditor

السبب: اكتشاف حالات انحراف التوازن الحسابي الناتجة عن الانهيارات المفاجئة لمحركات الداتا أو عمليات التلاعب الداخلي بقيم الجداول بشكل صامت.

الأثر المتوقع: إغلاق آلي وفوري للمنظومة (Emergency System Freeze) كخط دفاع نهائي لحماية أصول الشركة بمجرد انحراف الدفاتر بقيمة ليرة سورية واحدة.

مخاطر الانحدار: حمل قراءة إضافي على جداول العمليات؛ يجب الاستعلام بكفاءة عالية وبناء الفهارس (Indexes) اللازمة على أعمدة المبالغ والعملات.

خطوة التنفيذ الملموسة:

بناء ملف الهجرة لإضافة جدول إعدادات قفل النظام الطارئ database/migrations/xxxx_create_system_controls_table.php:

PHP
Schema::create('system_controls', function (Blueprint $table) {
    $table->id();
    $table->string('key')->unique();
    $table->string('value');
    $table->timestamps();
});


بناء أمر وحدة التحكم المجدول app/Console/Commands/AuditLedgerIntegrity.php:

PHP
<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AuditLedgerIntegrity extends Command {
    protected $signature = 'audit:ledger';
    protected $description = 'Automated hourly double-entry validation check.';

    public function handle() {
        // 1. حساب صافي التوازن الرياضي: مجموع المدين ضد الدائن لكافة العملات
        // في نظام صك المفترض أن الصافي الإجمالي المتقاطع عبر النظام دائماً يساوي صفراً أو يتطابق
        $drift = DB::table('transactions')
            ->select(DB::raw("SUM(case when type = 'debit' then amount else -amount end) as variance"))
            ->value('variance');

        if (abs($drift) > 0.0000) {
            // تفعيل حالة الحظر الطارئ في قاعدة البيانات والكاش
            DB::table('system_controls')->updateOrInsert(['key' => 'system_disbursement_halt'], ['value' => 'true']);
            Cache::forever('system_disbursement_halt', true);

            // إطلاق إنذار الطوارئ للـ Admin Broadcast
            logger()->critical("EMERGENCY: Ledger Drift Detected [Value: {$drift} SYP]. Disbursement system locked down immediately.");
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }
}


تسجيل المهمة لتعمل رأس كل ساعة في مجدول النظام داخل ملف routes/console.php:

PHP
use Illuminate\Support\Facades\Schedule;
Schedule::command('audit:ledger')->hourly();


حقن شرط التحقق من حالة القفل Cache::get('system_disbursement_halt') داخل دالات البدء بكافة خدمات الـ Mutations للتأكد من حظر إقلاع المعاملات المالية كلياً إذا كانت القيمة مساوية لـ true.

خطوة التحقق والاختبار أوتوماتيكياً: حقن سطر وهمي منحرف في جدول العمليات يدوياً داخل بيئة الـ Staging، وتأكيد قيام الأمر المجدول بقفل المنظومة وتغيير قيمة الكاش في الدورة التالية مباشرة.

البنود الصارمة المقترح تأجيلها إلى ما بعد البوابة 7 (Production)

يوصى معمارياً بـ عدم تأجيل أي بند من هذه السلسلة السداسية لما بعد البيئة الحية البوابة 7، لأن غياب أي عنصر منها يمثل تهديداً مباشراً لأموال المنصة ومصداقيتها التقنية.

ومع ذلك، يُستثنى بند واحد فقط لتعديل سلوكه التشغيلي:

آلية الإيقاف القسري التلقائي لمدقق سلامة الدفتر (Auto-Halt Mechanism): في بيئة الـ Staging (البوابة 6)، يُنصح بجعل رد فعل السكربت يقتصر على إرسال التنبيهات والإنذارات فقط (Alert-Only mode) دون قفل قاعدة البيانات بشكل حاد، وذلك لتفادي تجميد أعمال فحص الجودة وفحص الواجهات من قِبل وكلاء فريق الاختبارات في حال تسببت الحركات البرمجية التجريبية والمزيفة في إحداث انحراف حسابي متوقع. يتم تحويل السلوك التشغيلي آلياً إلى الإغلاق الحاد والصارم (Hard-Lockdown mode) بمجرد دمج الكود داخل فرع الإنتاج الأساسي (Production Branch).

توجيه لـ SOFI AI: ابدأ فوراً بتنفيذ البند الأول [SEV-5] عبر حقن وسيط الاستجابة الموحد في فروع التطوير الحالية، وتابع التحقق من كود الاستجابة قبل الانتقال للبند الثاني.