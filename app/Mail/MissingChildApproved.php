<?php

namespace App\Mail;

use App\Models\MissingChild;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MissingChildApproved extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public MissingChild $missingChild;

    public function __construct(MissingChild $missingChild)
    {
        $this->missingChild = $missingChild;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Hồ sơ trẻ mất tích đã được duyệt',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.missing-child-approved',
        );
    }
}