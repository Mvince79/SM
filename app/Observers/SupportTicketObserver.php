<?php

namespace App\Observers;

use App\Models\NotificationEvent;
use App\Models\SupportTicket;
use App\Services\Notification\Notifier;
use App\Services\Notification\RecipientResolver;
use Illuminate\Support\Facades\Auth;

/**
 * Phát thông báo cho người tạo ticket khi admin thay đổi trạng thái:
 *  - status -> RESOLVED -> TICKET_RESOLVED (USER)
 *  - status -> CLOSED   -> TICKET_CLOSED   (USER)
 *
 * Chỉ báo trên 2 trạng thái "kết thúc" này. Các chuyển đổi khác (OPEN ->
 * IN_PROGRESS khi admin nhận xử lý, hay đổi người phụ trách) KHÔNG báo user,
 * tránh nhiễu. Đặt ở observer (như MissingChildObserver / FoundChildObserver /
 * SupportTicketReplyObserver) để action admin chỉ lo update, observer lo notify.
 *
 * Giá trị status là string literal ('RESOLVED'/'CLOSED') — khớp migration
 * support_tickets và ViewSupportTicket (mark_resolved / close_ticket); model
 * SupportTicket không định nghĩa hằng status riêng.
 */
class SupportTicketObserver
{
    public function __construct(
        private readonly Notifier $notifier,
        private readonly RecipientResolver $recipients,
    ) {}

    public function updated(SupportTicket $ticket): void
    {
        if (! $ticket->wasChanged('status')) {
            return;
        }

        $newStatus = $ticket->status;

        if ($newStatus === 'RESOLVED') {
            $this->notify($ticket, NotificationEvent::TYPE_TICKET_RESOLVED);
        } elseif ($newStatus === 'CLOSED') {
            $this->notify($ticket, NotificationEvent::TYPE_TICKET_CLOSED);
        }
    }

    private function notify(SupportTicket $ticket, string $eventType): void
    {
        $ticket->loadMissing('user');
        $creator = $ticket->user;

        // Không tự báo cho chính người tạo nếu họ là người thao tác (trường hợp hiếm).
        if (! $creator || Auth::id() === $creator->getKey()) {
            return;
        }

        $this->notifier->send(
            $eventType,
            $ticket,
            [$creator],
            [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'source' => NotificationEvent::SOURCE_ADMIN,
            ],
            Auth::id(),
        );
    }
}
