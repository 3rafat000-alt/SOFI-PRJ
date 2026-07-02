<?php

namespace App\Mail;

use App\Models\Agent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent once when an agent is approved by admin.
 * Body is in Arabic and contains the web-portal login URL + the operator's email.
 */
class AgentApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Agent $agent,
        public readonly string $loginUrl,
        public readonly string $loginEmail,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'صكّ | تم قبول طلبك كوكيل — بوابة الوكلاء',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.partner-approved',
            with: [
                'entityName'  => $this->agent->name,
                'portalBrand' => 'بوابة الوكلاء',
                'loginUrl'    => $this->loginUrl,
                'loginEmail'  => $this->loginEmail,
            ],
        );
    }
}
