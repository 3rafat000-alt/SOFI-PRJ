بروتوكول التدقيق المعماري الصارم: توجيه عملي مباشر إلى SOFI AI

مرحباً SOFI AI. رصدك الدقيق لبنية Phase A/Phase B المعتمدة في الملف الحقيقي ReconcilePendingWithdrawals.php يثبت كفاءة نظام التحقق قبل التنفيذ (Verify-First Protocol). تدارك وجود الكنّاس المالي المتقدم وتكييف سيناريوهات الفحص كـ WithdrawalReconciliationSweeperTest هو السلوك الهندسي الصحيح لحماية المنظومة من التكرار البرمجي.

بناءً على نضج المنظومة الحالي وخلوها من الثغرات التقليدية، نقوم الآن بالانتقال إلى مستوى الأمان المالي المتقدم (Tier-A Advanced Risk Management) عبر إدخال حارس حماية حتمي لم يذكر في الـ 57 موديل أو الخدمات المرجعية مسبقاً: حارس حدود السيولة وحجم العمليات المتزامنة المبني على مستويات التحقق (KYC-Based Financial Velocity Guard).

البند المستهدف: حارس سقوف العمليات الذري (FinancialLimitGuard)
1. بروتوكول تحقق الوجود السريع (Verify-First Execution Command)

قبل كتابة أي كود، قم بتشغيل الأوامر التالية في بيئة العمل للتأكد من عدم وجود منطق مشابه يربط مستويات الـ KYC بسقوف سحب يومية/شهرية تراكمية:

Bash
# تحقق من وجود أي خدمة مخصصة لإدارة الحدود المالية
find app/ -name "*Limit*" -o -name "*Velocity*"

# ابحث عن استدعاءات لموديل الـ KYC داخل خدمات النقل أو السحب
grep -ri "KycLevel" app/Services/TransferService.php
grep -ri "KycLevel" app/Services/WithdrawalService.php


إستراتيجية الخروج: إذا وجدت أن النظام يمنع العمليات بالفعل بناءً على تجميع حركات الـ SQL التراكمية اليومية لكل مستوى KYC تحت قفل قاعدة البيانات، قم بإلغاء المهمة فوراً. إذا كانت النتيجة صفراً، ابدأ التنفيذ المباشر أدناه.

2. السبب المعماري

تفتقر المنصة حالياً إلى آلية ذرية (Atomic) لمنع المستخدمين من تجاوز السقوف المالية المسموحة لمستويات التحقق الخاصة بهم (KycLevel). غياب هذا الحارس يسمح بحدوث ثغرة التسابق اللحظي (Velocity Race Condition): إذا قام مستخدم من المستوى 1 (حدّه اليومي 1,000,000 ليرة مثلاً) بإرسال 5 طلبات تحويل متزامنة في نفس الميكروثانية بقيمة 600,000 ليرة لكل طلب، ستمر جميعها بنجاح لأن الفحص البرمجي العادي لا يرى العمليات الأخرى حتى يتم عمل commit لها، مما يكسر معايير الامتثال المالي (Compliance Caps).

3. الأثر التشغيلي المتوقع

حظر مطلق وصارم لأي محاولة لتجاوز الحدود المالية التراكمية (اليومية/الشهرية) المربوطة بمستويات الـ KYC، مع حماية خيوط المعالجة المتزامنة من الالتفاف حول السقوف المالية للمنصة.

4. خطوات التنفيذ البرمجي الملموسة
الخطوة الأولى: إنشاء ملف الإعدادات الحتمي config/limits.php

قم بصياغة المصفوفة التي تحدد السقوف المالية لكل مستوى تحقق (بالعملة المحلية SYP):

PHP
<?php
return [
    'kyc_caps' => [
        1 => ['daily' => 1000000.00, 'monthly' => 10000000.00], // المستوى 1
        2 => ['daily' => 5000000.00, 'monthly' => 50000000.00], // المستوى 2
        3 => ['daily' => 25000000.00, 'monthly' => 250000000.00], // المستوى 3
    ]
];

الخطوة الثانية: بناء الخدمة الحارسة App\Services\FinancialLimitGuard

يجب تشغيل حساب المجموع التراكمي حصرًا بعد إطلاق أمر قفل المحفظة lockForUpdate() لمنع التسابق المتزامن:

PHP
<?php
namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use App\Exceptions\FinancialLimitExceededException;
use Illuminate\Support\Facades\DB;

class FinancialLimitGuard {
    
    /**
     * التحقق الصارم من السقوف المالية للمستخدم خلال الدورة الزمنية الحية.
     * يجب استدعاؤها داخل Transaction Block وبعد عمل lockForUpdate للمحفظة.
     */
    public function enforceLimits(User $user, float $requestedAmount, string $currencyCode): void {
        // 1. جلب مستوى الـ KYC الحالي للمستخدم (الافتراضي 1 إذا لم يوجد)
        $kycLevel = $user->kycVerification?->level ?? 1;
        $caps = config("limits.kyc_caps.{$kycLevel}");

        if (!$caps) {
            throw new \RuntimeException("Security Exception: Undefined KYC financial caps for level {$kycLevel}.");
        }

        // 2. حساب ما تم صرفه فعلياً اليوم (ناجح + قيد المعالجة) للعملة المحددة
        $dailySpent = DB::table('transactions')
            ->where('user_id', $user->id)
            ->where('currency', $currencyCode)
            ->whereIn('type', ['transfer', 'withdrawal'])
            ->whereIn('status', ['success', 'processing', 'pending'])
            ->where('created_at', '>=', now()->startOfDay())
            ->sum('amount');

        if (($dailySpent + $requestedAmount) > $caps['daily']) {
            throw new FinancialLimitExceededException(
                "Transaction blocked: Daily limit exceeded for KYC Level {$kycLevel}. Max allowed daily: {$caps['daily']} {$currencyCode}."
            );
        }

        // 3. حساب ما تم صرفه فعلياً خلال الشهر الحالي
        $monthlySpent = DB::table('transactions')
            ->where('user_id', $user->id)
            ->where('currency', $currencyCode)
            ->whereIn('type', ['transfer', 'withdrawal'])
            ->whereIn('status', ['success', 'processing', 'pending'])
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('amount');

        if (($monthlySpent + $requestedAmount) > $caps['monthly']) {
            throw new FinancialLimitExceededException(
                "Transaction blocked: Monthly limit exceeded for KYC Level {$kycLevel}. Max allowed monthly: {$caps['monthly']} {$currencyCode}."
            );
        }
    }
}

الخطوة الثالثة: حقن الحارس داخل الـ TransferService و WithdrawalService

انتقل إلى دالة التنفيذ المالي، وقم بحقن الحارس مباشرة بعد السطر المسؤول عن قفل المعاملة التصاعدي:

PHP
// داخل دالة التبادل المالي في TransferService مثلاً:
DB::transaction(function () use ($sender, $receiver, $amount) {
    // القفل المزدوج المعتمد أصلاً في صك لمنع الـ Deadlock
    $firstWallet = Wallet::where('id', min($sender->id, $receiver->id))->lockForUpdate()->first();
    $secondWallet = Wallet::where('id', max($sender->id, $receiver->id))->lockForUpdate()->first();

    // استدعاء الحارس الذري فوراً تحت الحماية الكاملة للقفل
    app(FinancialLimitGuard::class)->enforceLimits($sender, $amount, 'SYP');
    
    // إكمال منطق النقل الفعلي...
});

5. معيار القبول الاختباري (Test Acceptance Criteria)

قم بصياغة ملف اختبار مخصص باسم tests/Feature/FinancialLimitGuardTest.php للتحقق من السلوك التالي:

اختبار النجاح تحت السقف (Within Limits): مستخدم بمستوى KYC = 1 يمتلك رصيد كافٍ، يطلب تحويل مبلغ 500,000 ليرة سورية؛ يجب أن يعبر الطلب بنجاح ويتم تسجيل المعاملة كـ success.

اختبار كسر السقف اليومي (Daily Limit Breach): نفس المستخدم يحاول في طلب تالي تحويل مبلغ 600,000 ليرة سورية في نفس اليوم (المجموع 1,100,000 وهو أكبر من السقف اليومي المسموح 1,000,000)؛ يجب أن يرتد النظام فوراً ويقذف استثناء من نوع FinancialLimitExceededException وتفشل المعاملة دون خصم أي ليرة من المحفظة.

توجيه أتمتة لـ SOFI AI: نفّذ بروتوكول تحقق الوجود أولاً عبر الـ Grep داخل المستودع المحلي؛ إذا تأكدت من خلو الكود من حارس سقوف متزامن، باشر ببناء الملفات البرمجية الثلاثة واختبر فاعليتها فوراً لتأمين البوابة 6 كلياً.