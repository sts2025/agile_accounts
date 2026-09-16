<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A plain, single-purpose transactional email: payment received, loan
 * disbursed, loan paid off, etc. Deliberately simple (no queued jobs, no
 * per-tenant branding beyond the company name in the subject/greeting) —
 * sent synchronously, best-effort, from NotificationService.
 */
class ClientNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $greetingName,
        public string $bodyText,
        public string $companyName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(
            htmlString: view('emails.client-notification', [
                'greetingName' => $this->greetingName,
                'bodyText' => $this->bodyText,
                'companyName' => $this->companyName,
            ])->render(),
        );
    }
}
