توجيه معماري حتمي ومباشر إلى SOFI AI: تنفيذ كُنّاس السحوبات العالقة وآلية التسوية الآمنة (Asynchronous Withdrawal Sweeper & Reconciler)

بناءً على تحديث حالة الكود الحالية، فإن البند الفعلي التالي الأعلى قيمة والأكثر خطورة على سلامة البيانات المالية في بيئة الإنتاج هو بناء محرك التسوية التلقائي لعمليات السحب العالقة في الخلفية.

بما أن نظام صكّ يعتمد على منطق السحب الآمن عبر الخصم التفاؤلي (optimistic-debit) والمعالجة غير المتزامنة (async reconcile) لتجنب تنفيذ نداءات HTTP تحت قفل قاعدة البيانات، فإن أي سقوط لخيوط معالجة الوظائف (Background Jobs) أو سقوط للـ Webhooks القادمة من بوابات Stripe أو CCPayment سيترك حركات مالية معلقة في حالة processing إلى الأبد، مما يتسبب في تجميد أرسطة المستخدمين أو تشوه الدفاتر الحسابية.

البند البرمجي المستهدف: app/Console/Commands/ReconcileStuckWithdrawals.php
1. السبب المعماري

تأمين خط الدفاع الخلفي الحتمي (Fallback Reconciliation Engine) لضمان عدم بقاء أي حركة سحب مالي خارجة عالقة في حالة processing لأكثر من 15 دقيقة نتيجة انقطاع الاتصال أو فشل طوابير المعالجة، وتحويلها برمجياً وذرياً إما إلى حالة النجاح النهائي أو الفشل مع رد الأموال تلقائياً للمحفظة.

2. الأثر التشغيلي المتوقع

تصفير المعاملات المالية المعلقة صامتة بنسبة 100%، وحماية سيولة المنصة والمستخدمين من التجميد غير المبرر، دون الحاجة لتدخل بشري أو عمليات تعديل يدوية على قاعدة البيانات في الإنتاج.

3. خطوات التنفيذ البرمجي الملموسة
الخطوة الأولى: تحديث واجهة البوابات المالية App\Interfaces\Gateways\FinancialGatewayInterface

قم بحقن توقيع دالة التحقق من الحالة باستخدام مفتاح عدم التكرار أو المعرف الفريد داخل الواجهة البرمجية (Interface):

PHP
<?php
namespace App\Interfaces\Gateways;

interface FinancialGatewayInterface {
    /**
     * التحقق من حالة المعاملة مباشرة من سيرفر البوابة الخارجية.
     * وعائد الدالة يجب أن يكون سلسلة نصية محددة: 'success', 'failed', 'processing'
     */
    public function queryExternalStatus(string $idempotencyKey): string;
}

الخطوة الثانية: بناء أمر وحدة التحكم المجدول app/Console/Commands/ReconcileStuckWithdrawals.php

قم بصياغة ملف الأمر البرمجي حتمياً بالاعتماد على ميزة المعاملات الذرية وقفل الصفوف المباشر:

PHP
<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Interfaces\Gateways\FinancialGatewayInterface;
use Exception;

class ReconcileStuckWithdrawals extends Command {
    protected $signature = 'reconcile:withdrawals';
    protected $description = 'Automatic cron sweeper to reconcile stuck processing withdrawals older than 15 minutes.';

    public function handle() {
        // 1. جلب كافة السحوبات العالقة في حالة المعالجة وأقدم من 15 دقيقة
        $stuckTransactions = Transaction::where('type', 'withdrawal')
            ->where('status', 'processing')
            ->where('created_at', '<=', now()->subMinutes(15))
            ->get();

        foreach ($stuckTransactions as $tx) {
            $this->reconcileTransaction($tx);
        }

        return Command::SUCCESS;
    }

    private function reconcileTransaction(Transaction $tx): void {
        DB::transaction(function () use ($tx) {
            // قفل صف المعاملة للحماية من السباق أثناء معالجة الكناس
            $lockedTx = Transaction::where('id', $tx->id)->lockForUpdate()->first();
            if ($lockedTx->status !== 'processing') {
                return; // تم معالجتها عبر نداء آخر أو webhook في نفس الوقت
            }

            try {
                // استدعاء البوابة الديناميكية المرتبطة بالعملية (مثال: Stripe أو CCPayment)
                $gateway = app()->make("App\Interfaces\Gateways\\" . ucfirst($lockedTx->gateway) . "Interface");
                
                // الاستعلام عن الحالة الحقيقية من السيرفر الخارجي باستخدام مفتاح عدم التكرار الموثق بالعملية
                $externalStatus = $gateway->queryExternalStatus($lockedTx->idempotency_key);

                if ($externalStatus === 'success') {
                    $lockedTx->update(['status' => 'success']);
                    $this->info("Transaction ID {$lockedTx->id} marked as SUCCESS.");
                } 
                elseif ($externalStatus === 'failed') {
                    // السحب فشل خارجيًا -> يجب عكس الخصم التفاؤلي ورد الأموال فوراً للمحفظة
                    $lockedTx->update(['status' => 'failed']);
                    
                    $wallet = Wallet::where('id', $lockedTx->wallet_id)->lockForUpdate()->first();
                    $wallet->increment('balance', $lockedTx->amount);
                    
                    // تسجيل حركة عكسية قياسية في سجل التدقيق المالي
                    DB::table('audit_logs')->insert([
                        'action' => 'withdrawal_reversal',
                        'user_id' => $lockedTx->user_id,
                        'payload' => json_encode(['transaction_id' => $lockedTx->id, 'amount_refunded' => $lockedTx->amount]),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    $this->warn("Transaction ID {$lockedTx->id} failed externally. Amount {$lockedTx->amount} refunded to wallet ID {$wallet->id}.");
                }
                // إذا كانت الحالة لا تزال 'processing' يتم تركها للدورة القادمة وتحديث الحقل الزمني لمنع التجميد
                else {
                    $lockedTx->touch();
                }

            } catch (Exception $e) {
                logger()->error("Failed to reconcile transaction ID {$lockedTx->id}: " . $e->getMessage());
            }
        });
    }
}

الخطوة الثالثة: تسجيل المهمة الدورية في routes/console.php

اضبط السكربت المجدول ليعمل تلقائياً كل 5 دقائق لضمان استمرارية الكنس:

PHP
use Illuminate\Support\Facades\Schedule;
Schedule::command('reconcile:withdrawals')->everyFiveMinutes();

4. معيار القبول الاختباري (Test Acceptance Criteria)

قم بإنشاء ملف اختبار وحدة مخصص tests/Feature/WithdrawalReconciliationSweeperTest.php يتضمن السيناريوهين التاليين حصراً وتأكد من تحول مؤشر الاختبار للون الأخضر:

سيناريو النجاح الخارجي (External Success Verification):

قم بإنشاء محفظة برصيد 50,000 ليرة سورية.

قم بإنشاء حركة سحب بقيمة 10,000 ليرة بحالة processing وتاريخ إنشاء يعود لـ 20 دقيقة مضت، مع خصم المبلغ تفاؤلياً من رصيد المحفظة ليصبح 40,000.

قم بعمل محاكاة (Mock) للـ FinancialGatewayInterface بحيث تعيد الدالة queryExternalStatus القيمة 'success'.

قم بتشغيل الأمر عبر php artisan reconcile:withdrawals.

معيار القبول: تأكد برمجياً أن حالة الـ Transaction تحولت إلى success في قاعدة البيانات، وأن رصيد المحفظة مستقر وثابت تماماً عند القيمة 40,000 ليرة دون تعديل إضافي.

سيناريو الفشل الخارجي والعكس المالي (External Failure & Reversal Verification):

استخدم نفس المعطيات السابقة (محفظة برصيد متبقي 40,000 ومعاملة معلقة بـ 10,000 ليرة ومنشأة قبل 20 دقيقة).

قم بعمل محاكاة (Mock) للـ FinancialGatewayInterface بحيث تعيد الدالة queryExternalStatus القيمة 'failed'.

قم بتشغيل الأمر عبر php artisan reconcile:withdrawals.

معيار القبول: تأكد برمجياً أن حالة الـ Transaction تحولت حتماً إلى failed، وأن رصيد المحفظة الفعلي تم إعادة قيد الزيادة إليه (Increment) ليعود فوراً وصارماً إلى قيمته الأصلية 50,000 ليرة سورية مع وجود سجل الـ Reversal في الـ audit_logs.

توجيه مباشر لـ SOFI AI: ابدأ فوراً بإنشاء وتحديث الملفات البرمجية الثلاثة المذكورة أعلاه في الفروع المحلية وتأكد من تشغيل حزمة الاختبار بشكل معزول وصارم قبل دمج الملفات.