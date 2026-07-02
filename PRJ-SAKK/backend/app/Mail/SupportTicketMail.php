<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Transactional mail for the support-ticket flow.
 *
 * Sent FROM the configured no-reply sender but carries a Reply-To pointing at
 * the monitored support inbox (config: mail.support_address) so any human reply
 * lands somewhere a person reads — not a black hole.
 *
 * Used both ways:
 *   - new ticket / customer reply  -> notify the support inbox
 *   - admin reply                  -> notify the customer
 */
class SupportTicketMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string       $heading       Bold title at the top of the email.
     * @param  string       $ticketNumber  Human reference, e.g. TK-260626-AB12.
     * @param  string[]     $lines         Body paragraphs, rendered in order.
     * @param  string|null  $actionUrl     Optional CTA link.
     * @param  string|null  $actionLabel   Label for the CTA button.
     */
    public function __construct(
        public string $heading,
        public string $ticketNumber,
        public array $lines,
        public ?string $actionUrl = null,
        public ?string $actionLabel = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->heading . ' — ' . $this->ticketNumber,
            replyTo: [new Address(
                (string) config('mail.support_address', 'support@zanjour.com'),
                (string) config('mail.support_name', 'دعم صكّ'),
            )],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.support-ticket',
            with: [
                'heading' => $this->heading,
                'ticketNumber' => $this->ticketNumber,
                'lines' => $this->lines,
                'actionUrl' => $this->actionUrl,
                'actionLabel' => $this->actionLabel,
            ],
        );
    }
}
