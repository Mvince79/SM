<?php

namespace App\Mail;

use App\Models\MissingChild;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class CaseExpiringWarningMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public MissingChild $missingChild)
    {
        $this->onQueue('notifications');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Hồ sơ {$this->missingChild->case_code} sắp hết hạn",
        );
    }

    public function content(): Content
    {
        $linkExpiresAt = $this->missingChild->expires_at
            ->copy()
            ->addDays(MissingChild::RENEWAL_GRACE_DAYS);

        return new Content(
            view: 'emails.case-expiring-warning',
            with: [
                'renewalUrl' => URL::temporarySignedRoute(
                    'missing.renew.show',
                    $linkExpiresAt,
                    ['missingChild' => $this->missingChild->getKey()],
                ),
            ],
        );
    }
}
