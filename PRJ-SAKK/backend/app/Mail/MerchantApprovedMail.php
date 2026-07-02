<?php

namespace App\Mail;

use App\Models\Merchant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent once when a merchant is approved by admin.
 * Body is in Arabic and contains the web-portal login URL + the operator's email.
 */
class MerchantApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Merchant $merchant,
        public readonly string $loginUrl,
        public readonly string $loginEmail,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'صكّ | تم قبول طلبك كتاجر — بوابة التجار',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.partner-approved',
            with: [
                'entityName'  => $this->merchant->store_name,
                'portalBrand' => 'بوابة التجار',
                'loginUrl'    => $this->loginUrl,
                'loginEmail'  => $this->loginEmail,
            ],
        );
    }
}
