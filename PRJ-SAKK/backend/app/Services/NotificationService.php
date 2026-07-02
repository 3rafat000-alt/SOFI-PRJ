<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserNotification;
use App\Models\Transaction;
use App\Support\Money;
use Illuminate\Support\Str;

class NotificationService
{
    public function __construct(private readonly FCMService $fcm)
    {
    }

    public function transferReceived(User $user, Transaction $transaction, float $amount, string $fromName): void
    {
        $this->createAndSend(
            $user,
            'تحويل مستلم',
            "استلمت " . Money::format($amount, 'SYP') . " من {$fromName}",
            'p2p_received',
            ['transaction_id' => (string) $transaction->id, 'reference' => $transaction->reference],
        );
    }

    public function transferSent(User $user, Transaction $transaction, float $amount, string $toName): void
    {
        $this->createAndSend(
            $user,
            'تحويل مرسل',
            "أرسلت " . Money::format($amount, 'SYP') . " إلى {$toName}",
            'p2p_sent',
            ['transaction_id' => (string) $transaction->id, 'reference' => $transaction->reference],
        );
    }

    public function paymentRequestReceived(User $user, float $amount, string $fromName, string $requestUuid): void
    {
        $this->createAndSend(
            $user,
            'طلب دفع جديد',
            "طلب منك {$fromName} مبلغ " . Money::format($amount, 'SYP'),
            'payment_request',
            ['payment_request_uuid' => $requestUuid],
        );
    }

    public function paymentRequestAccepted(User $user, float $amount, string $byName, string $requestUuid): void
    {
        $this->createAndSend(
            $user,
            'تم قبول طلب الدفع',
            "قام {$byName} بقبول طلب الدفع بقيمة " . Money::format($amount, 'SYP'),
            'payment_request_accepted',
            ['payment_request_uuid' => $requestUuid],
        );
    }

    public function paymentRequestRejected(User $user, float $amount, string $byName, string $requestUuid): void
    {
        $this->createAndSend(
            $user,
            'تم رفض طلب الدفع',
            "قام {$byName} برفض طلب الدفع بقيمة " . Money::format($amount, 'SYP'),
            'payment_request_rejected',
            ['payment_request_uuid' => $requestUuid],
        );
    }

    public function kycLevelUpgraded(User $user, int $newLevel): void
    {
        $this->createAndSend(
            $user,
            'تم رفع مستوى التحقق',
            "تم ترقية مستوى التحقق الخاص بك إلى المستوى {$newLevel}",
            'kyc_level_upgrade',
            [],
        );
    }

    public function kycDocumentVerified(User $user, string $documentType): void
    {
        $this->createAndSend(
            $user,
            'تم توثيق المستند',
            "تم التحقق من مستند {$documentType} بنجاح",
            'document_verified',
            [],
        );
    }

    public function kycRejected(User $user, string $reason): void
    {
        $this->createAndSend(
            $user,
            'تم رفض التحقق',
            "لم يتم قبول مستندات التحقق: {$reason}",
            'kyc_rejected',
            [],
        );
    }

    public function deviceApproved(User $user, string $deviceName): void
    {
        $this->createAndSend(
            $user,
            'تم الموافقة على الجهاز',
            "تمت الموافقة على جهاز {$deviceName} ويمكنك استخدامه بعد ٤٨ ساعة",
            'device_approved',
            [],
        );
    }

    public function deviceRejected(User $user, string $deviceName): void
    {
        $this->createAndSend(
            $user,
            'تم رفض الجهاز',
            "تم رفض جهاز {$deviceName} تلقائياً بسبب عدم تأكيد الموافقة لمدة ٧٢ ساعة",
            'device_rejected',
            [],
        );
    }

    public function cashbackEarned(User $user, float $amount, string $source): void
    {
        $this->createAndSend(
            $user,
            'استرداد نقدي',
            "حصلت على " . Money::format($amount, 'SYP') . " استرداد نقدي من {$source}",
            'cashback_earned',
            [],
        );
    }

    public function depositReceived(User $user, float $amount, string $walletName): void
    {
        $this->createAndSend(
            $user,
            'إيداع مستلم',
            "تم إيداع " . Money::format($amount, 'SYP') . " في محفظة {$walletName}",
            'deposit_received',
            [],
        );
    }

    private function createAndSend(User $user, string $title, string $body, string $templateCode, array $meta = []): void
    {
        try {
            UserNotification::create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $user->id,
                'title' => $title,
                'body' => $body,
                'template_code' => $templateCode,
                'channel' => 'push',
                'data' => $meta,
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            if ($user->fcm_token) {
                $this->fcm->send(
                    $user->fcm_token,
                    $title,
                    $body,
                    array_merge($meta, ['type' => $templateCode]),
                );
            }
        } catch (\Throwable $e) {
            logger()->error('NotificationService: send failed', [
                'user_id' => $user->id,
                'template' => $templateCode,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Support agent replied to the customer's ticket.
     */
    public function supportTicketReplied(User $user, \App\Models\SupportTicket $ticket): void
    {
        $this->createAndSend(
            $user,
            'رد من الدعم الفني',
            "وصلك رد جديد على تذكرتك ({$ticket->ticket_number}): {$ticket->subject}",
            'support_reply',
            ['ticket_uuid' => (string) $ticket->uuid, 'ticket_number' => $ticket->ticket_number],
        );
    }
}
