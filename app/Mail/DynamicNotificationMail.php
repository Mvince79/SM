<?php

namespace App\Mail;

use App\Models\NotificationEvent;
use App\Models\NotificationRecipient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use InvalidArgumentException;

class DynamicNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public NotificationRecipient $recipient)
    {
        $this->onQueue('notifications');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->recipient->rendered_title);
    }

    public function content(): Content
    {
        $this->recipient->loadMissing(['event', 'user']);
        $roleContent = $this->roleContent();

        return new Content(
            markdown: $this->markdownView(),
            with: [
                'recipientName' => $this->recipient->user?->full_name ?: 'bạn',
                'roleLabel' => $roleContent['label'],
                'roleIntroduction' => $roleContent['introduction'],
                'notificationTitle' => $this->recipient->rendered_title,
                'notificationBody' => $this->recipient->rendered_body,
                'actionUrl' => $this->recipient->action_url,
            ],
        );
    }

    private function markdownView(): string
    {
        $eventType = strtoupper((string) $this->recipient->event?->event_type);

        return match ($eventType) {
            NotificationEvent::TYPE_FACE_MATCH_CONFIRMED => 'emails.notifications.face-match-confirmed',
            NotificationEvent::TYPE_CASE_APPROVED,
            NotificationEvent::TYPE_CASE_APPROVED_IN_PROVINCE => 'emails.notifications.case-approved',
            NotificationEvent::TYPE_CLUE_VERIFIED => 'emails.notifications.clue-verified',
            NotificationEvent::TYPE_HANDOVER_READY => 'emails.notifications.handover-ready',
            NotificationEvent::TYPE_ROLE_CHANGED => 'emails.notifications.role-changed',
            default => throw new InvalidArgumentException(
                "Không có email template cho notification event [{$eventType}]."
            ),
        };
    }

    /**
     * @return array{label: string, introduction: string}
     */
    private function roleContent(): array
    {
        return match (strtoupper((string) $this->recipient->role_at_time)) {
            'ADMIN', 'MODERATOR' => [
                'label' => 'Quản trị viên',
                'introduction' => 'Bạn nhận thông báo này để kiểm tra và điều phối bước xử lý tiếp theo.',
            ],
            'VOLUNTEER' => [
                'label' => 'Tình nguyện viên',
                'introduction' => 'Bạn nhận thông báo này để hỗ trợ xác minh và chia sẻ thông tin đúng phạm vi được phân công.',
            ],
            default => [
                'label' => 'Gia đình / Người dùng',
                'introduction' => 'Thông tin dưới đây được cập nhật cho hồ sơ mà bạn đang quan tâm.',
            ],
        };
    }
}
