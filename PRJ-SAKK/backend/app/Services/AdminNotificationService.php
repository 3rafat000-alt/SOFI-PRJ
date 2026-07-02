<?php

namespace App\Services;

use App\Models\AdminAlert;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class AdminNotificationService
{
    /**
     * Safely generate an admin route URL or null when the route is undefined.
     */
    private static function safeRoute(string $name, mixed $parameters = []): ?string
    {
        return Route::has($name) ? route($name, $parameters) : null;
    }

    /**
     * Send alert to all admins (or specific admin).
     */
    public static function notify(
        string $title,
        string $message,
        string $type = 'info',
        ?string $link = null,
        ?int $adminId = null
    ): AdminAlert {
        return AdminAlert::create([
            'admin_id' => $adminId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'link' => $link,
        ]);
    }

    /**
     * New user registered.
     */
    public static function userRegistered(User $user): void
    {
        self::notify(
            'مستخدم جديد',
            "تم تسجيل مستخدم جديد: {$user->first_name} {$user->last_name}",
            'info',
            self::safeRoute('admin.users.show', $user),
        );
    }

    /**
     * Pending KYC requires admin review.
     */
    public static function pendingKyc(User $user, string $type): void
    {
        self::notify(
            'طلب تحقق KYC جديد',
            "طلب تحقق {$type} من {$user->first_name} {$user->last_name} بانتظار المراجعة",
            'warning',
            self::safeRoute('admin.users.show', $user),
        );
    }

    /**
     * Withdrawal requested — needs admin processing/approval.
     */
    public static function withdrawalRequested(User $user, float $amount, string $currency = 'USD'): void
    {
        self::notify(
            'طلب سحب جديد',
            "طلب سحب بقيمة {$amount} {$currency} من {$user->first_name} {$user->last_name} بانتظار المعالجة",
            'warning',
            self::safeRoute('admin.withdrawals.index'),
        );
    }

    /**
     * Agent/merchant application submitted — needs document review.
     */
    public static function partnerApplicationSubmitted(User $user, string $type): void
    {
        $label = $type === 'agent' ? 'وكيل' : 'تاجر';
        $route = $type === 'agent' ? 'admin.agents.documents' : 'admin.merchants.documents';

        self::notify(
            "طلب {$label} جديد",
            "تقدّم {$user->first_name} {$user->last_name} بطلب {$label} — بانتظار مراجعة المستندات",
            'info',
            self::safeRoute($route),
        );
    }

    public static function companyApplicationSubmitted(User $user, string $companyName): void
    {
        self::notify(
            'طلب شركة جديد',
            "تقدّم {$user->first_name} {$user->last_name} بطلب تسجيل شركة «{$companyName}» — بانتظار مراجعة المستندات",
            'info',
            self::safeRoute('admin.companies.documents'),
        );
    }

    /**
     * Transaction failed.
     */
    public static function transactionFailed(string $reference, string $reason, float $amount): void
    {
        self::notify(
            'معاملة فاشلة',
            "فشلت المعاملة {$reference} بقيمة " . \App\Support\Money::format($amount, 'SYP') . " — {$reason}",
            'error',
            self::safeRoute('admin.transactions'),
        );
    }

    /**
     * Card dispute opened (Stripe Issuing) — needs admin follow-up.
     */
    public static function cardDisputeCreated(string $disputeId, float $amount, string $reason): void
    {
        self::notify(
            'نزاع بطاقة جديد',
            "تم فتح نزاع على بطاقة (#{$disputeId}) بقيمة {$amount} — السبب: {$reason}",
            'warning',
            self::safeRoute('admin.transactions'),
        );
    }

    /**
     * System error or health degradation.
     */
    public static function systemError(string $component, string $details): void
    {
        self::notify(
            'خطأ في النظام',
            "خلل في {$component}: {$details}",
            'error',
            self::safeRoute('admin.system.health'),
        );
    }

    /**
     * A customer opened a new support ticket.
     */
    public static function supportTicketOpened(\App\Models\SupportTicket $ticket): void
    {
        self::notify(
            'تذكرة دعم جديدة',
            "تذكرة جديدة ({$ticket->ticket_number}) من {$ticket->user?->first_name} {$ticket->user?->last_name}: {$ticket->subject}",
            $ticket->priority === 'urgent' ? 'error' : 'warning',
            self::safeRoute('admin.support.show', $ticket),
        );
    }

    /**
     * A customer replied on an existing ticket.
     */
    public static function supportTicketReplied(\App\Models\SupportTicket $ticket): void
    {
        self::notify(
            'رد جديد على تذكرة',
            "رد جديد من العميل على التذكرة ({$ticket->ticket_number}): {$ticket->subject}",
            'info',
            self::safeRoute('admin.support.show', $ticket),
        );
    }
}
