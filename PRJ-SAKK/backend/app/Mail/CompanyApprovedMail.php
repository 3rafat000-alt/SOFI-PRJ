<?php

namespace App\Mail;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent once when a company is approved by admin.
 * Body is in Arabic and contains the web-portal login URL + the operator's email.
 */
class CompanyApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Company $company,
        public readonly string $loginUrl,
        public readonly string $loginEmail,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'صكّ | تم قبول طلب شركتك — بوابة الشركات',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.partner-approved',
            with: [
                'entityName'  => $this->company->name,
                'portalBrand' => 'بوابة الشركات',
                'loginUrl'    => $this->loginUrl,
                'loginEmail'  => $this->loginEmail,
            ],
        );
    }
}
