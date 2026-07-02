توجيه معماري صارم إلى SOFI AI: سدّ فجوة سقوف السحب المالي (Enforcing KYC Withdrawal Limits)

مرحباً SOFI AI. رصدك الحاد للفجوة بين مساري التحويل والسحب يضع المنظومة على السلك الصحيح قبل الإنتاج. حقيقة أن TransferService يمتلك الحماية الكاملة بينما WalletService::withdraw مكشوف حسابياً تراكمياً يمثل ثغرة استنزاف سيولة (Velocity Leak) يجب سحقها فوراً قبل عبور البوابة 7.

فيما يلي خطة التنفيذ البرمجي والميكانيكي حتمية الخطوات لإنهاء هذا الخلل.

أولاً: القرار المعماري بشأن مسار التشفير (CCPayment Crypto-Withdraw)

🛑 أمر معماري قطعي: نعم حتماً. يجب أن يمر مسار السحب عبر الكريبتو (CCPayment) ومسارات سحب التجار والبطاقات عبر نفس نقطة الاختناق (WalletService::withdraw) أو عبر استدعاء نفس الـ Guard.
عزل مسار الكريبتو في دالات مستقلة دون حراسة الـ KYC يفتح نافذة تبييض أموال وتجاوز للحدود المفروضة من المصرف المركزي. القيد المالي هو قيد على "الهوية" وليس على "القناة".

ثانياً: ترتيب إجراءات التنفيذ وقفل الفجوة البرمجية
1. تعديل محرك الحساب التراكمي في الـ KycService لمنع التكرار (Anti Double-Counting Matrix)

السبب: لمنع التكرار (Double-Counting)، يجب أن يعتمد محرك الحساب على جمع كافة الأموال الخارجة من المنظومة (Outbound Value Flux) شاملة التحويلات والسحوبات معاً، وتطبيق الفحص قبل إدراج أسطر المعاملة الحالية في قاعدة البيانات (Pre-Insert Validation).

الأثر المتوقع: حساب حقيقي دقيق لثروة المستخدم الخارجة خلال الدورة الزمنية (اليوم/الشهر) بغض النظر عن القناة المستهلكة.

خطوة التنفيذ الملموسة:
تعديل الدالة الداخلية في app/Services/KycService.php (أو حيثما توجد دالة الحساب الحالية) لتصبح واعية بنوعي المعاملات:

PHP
public function assertWithinKycLimits(User $user, float $amount, string $currency, string $context = 'transfer'): void
{
    $limits = $this->limitsForUser($user); // جلب موديل KycLevel

    // 1. إنفاذ سقف السحب الفردي الحاد إذا كان السياق عملية سحب
    if ($context === 'withdrawal' && $amount > $limits->withdrawal_limit) {
        throw new KycLimitExceededException(
            "Transaction blocked: Single withdrawal limit exceeded. Max allowed per transaction: {$limits->withdrawal_limit} {$currency}."
        );
    }

    if ($amount > $limits->single_transaction_limit) {
        throw new KycLimitExceededException("Transaction blocked: Single transaction limit exceeded.");
    }

    // 2. احتساب المجاميع التراكمية (التحويلات + السحوبات معاً لمنع التهرب التبادلي)
    $dailySpent = DB::table('transactions')
        ->where('user_id', $user->id)
        ->where('currency', $currency)
        ->whereIn('type', ['transfer', 'withdrawal']) // كنس كلي للمسارين
        ->whereIn('status', ['success', 'processing', 'pending'])
        ->where('created_at', '>=', now()->startOfDay())
        ->sum('amount');

    if (($dailySpent + $amount) > $limits->daily_limit) {
        throw new KycLimitExceededException("Transaction blocked: Daily cumulative KYC limit reached.");
    }

    $monthlySpent = DB::table('transactions')
        ->where('user_id', $user->id)
        ->where('currency', $currency)
        ->whereIn('type', ['transfer', 'withdrawal'])
        ->whereIn('status', ['success', 'processing', 'pending'])
        ->where('created_at', '>=', now()->startOfMonth())
        ->sum('amount');

    if (($monthlySpent + $amount) > $limits->monthly_limit) {
        throw new KycLimitExceededException("Transaction blocked: Monthly cumulative KYC limit reached.");
    }
}

2. حقن الحارس داخل الـ WalletService::withdraw تحت القفل القائم

السبب: حماية عملية السحب من السباق اللحظي عبر إجبار الفحص على العمل بعد عزل المحفظة بـ lockForUpdate وقبل تعديل رصيد الحساب أو ضخ المعاملة للـ Gateway الخارجي.

الأثر المتوقع: تجميد فوري لطلبات السحب المتزامنة التي تحاول التلاعب بالحد اليومي المتبقي للمستخدم.

خطوة التنفيذ الملموسة:
افتح ملف app/Services/WalletService.php وانتقل إلى الدالة withdraw (عند السطر 79 وما بعده)، وقم بحقن الحارس مباشرة بعد فحص الرصيد الأساسي وقبل الانتقال لـ Phase B:

PHP
public function withdraw(User $user, float $amount, string $currency)
{
    return DB::transaction(function () use ($user, $amount, $currency) {
        // القفل الترتيبي الحتمي المعتمد في صك
        $wallet = Wallet::where('user_id', $user->id)
            ->where('currency', $currency)
            ->lockForUpdate()
            ->first();

        if (!$wallet || $wallet->balance < $amount) {
            throw new InsufficientBalanceException('رصيد غير كافٍ');
        }

        // 🔥 نقطة الحقن المعمارية الجديدة لـ SOFI AI:
        // استدعاء الحارس مع تمرير سياق 'withdrawal' لتنشيط فحص الـ withdrawal_limit المنفصل
        app(KycService::class)->assertWithinKycLimits($user, $amount, $currency, 'withdrawal');

        // الاستمرار في منطق تنفيذ Phase A (الخصم التفاؤلي وحجز المعاملة كـ PENDING)
        $wallet->decrement('balance', $amount);

        // إدراج سجل المعاملة لاحقاً هنا لمنع الـ Double-Counting أثناء الـ Sum...
    });
}

ثالثاً: معيار القبول والاختبار الصارم (Exit Acceptance Criteria)

قم بكتابة وتحديث ملف اختبارات مخصص تحت اسم tests/Feature/KycWithdrawalVelocityTest.php يحاكي الحالات التالية حتمياً:

🧪 اختبارات التحقق من الحدود (Green/Red Unit Execution)
PHP
<?php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\KycLevel;
use App\Exceptions\KycLimitExceededException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class KycWithdrawalVelocityTest extends TestCase {
    use RefreshDatabase;

    /** @test */
    public function withdrawal_within_caps_must_pass_and_debit_balance() {
        // 1. إعداد مستخدم برصيد 2,000,000 وسقف سحب فردي 500,000 وسقف يومي 1,000,000
        $user = User::factory()->create();
        $kyc = KycLevel::create(['level' => 1, 'withdrawal_limit' => 500000, 'daily_limit' => 1000000, 'monthly_limit' => 10000000, 'single_transaction_limit' => 1000000]);
        $user->kycVerification()->create(['level' => 1, 'status' => 'approved']);
        $wallet = Wallet::create(['user_id' => $user->id, 'currency' => 'SYP', 'balance' => 2000000]);

        // 2. تنفيذ سحب شرعي قيمته 400,000 (أقل من الحد الفردي واليومي)
        app(\App\Services\WalletService::class)->withdraw($user, 400000, 'SYP');

        // 3. النتيجة الحتمية: خصم الرصيد بنجاح لـ 1,600,000 دون استثناءات
        $this->assertEquals(1600000, $wallet->fresh()->balance);
    }

    /** @test */
    public function withdrawal_over_single_withdrawal_limit_must_fail_without_debit() {
        $user = User::factory()->create();
        KycLevel::create(['level' => 1, 'withdrawal_limit' => 500000, 'daily_limit' => 1000000, 'monthly_limit' => 10000000, 'single_transaction_limit' => 1000000]);
        $user->kycVerification()->create(['level' => 1, 'status' => 'approved']);
        $wallet = Wallet::create(['user_id' => $user->id, 'currency' => 'SYP', 'balance' => 2000000]);

        // محاولة سحب 600,000 دفعة واحدة (تكسر الـ withdrawal_limit البالغ 500,000)
        $this->expectException(KycLimitExceededException::class);
        
        try {
            app(\App\Services\WalletService::class)->withdraw($user, 600000, 'SYP');
        } finally {
            // معيار السلامة المالية: الرصيد يجب أن يظل ثابتاً ولم يُخصم منه شيء
            $this->assertEquals(2000000, $wallet->fresh()->balance);
        }
    }
}


توجيه أتمتة لـ SOFI AI: بمجرد تعافي خيوط تنفيذ المنصة من العطل العابر الحالي، باشر بحقن التعديلات المذكورة في ملف الـ KycService أولاً لتوسيع مرونة الفحص، ثم حقن سطر الاستدعاء التابع له في WalletService::withdraw وشغّل حزمة الفحص للتأكد من إغلاق الثغرة بشكل كامل ومطابق للمعايير.