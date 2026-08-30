<?php

namespace App\Observers;

use App\Models\NotificationEvent;
use App\Models\SupportTicketReply;
use App\Services\Notification\Notifier;
use App\Services\Notification\RecipientResolver;
use Illuminate\Support\Facades\Auth;

/**
 * Phát thông báo khi có phản hồi mới cho một Support Ticket:
 *  - created + là PUBLIC reply (is_internal_note = false) -> TICKET_REPLIED cho
 *    người tạo ticket (USER), kèm chuông + email để họ quay lại xem.
 *  - created + là INTERNAL NOTE (ghi chú nội bộ giữa admin) -> KHÔNG báo user,
 *    tránh lộ thông tin nội bộ qua thông báo/email.
 *  - updated / deleted -> không phát (chỉ báo khi có phản hồi MỚI).
 *
 * Đặt ở observer (như MissingChildObserver / FoundChildObserver / ClueObserver)
 * thay vì chỉ hook CreateAction của RepliesRelationManager, để mọi đường tạo
 * reply đều được phủ (admin panel hiện tại, và luồng client thêm reply sau này
 * nếu có) và loại tự động các trường hợp sửa/xoá.
 */
class SupportTicketReplyObserver
{
    public function __construct(
        private readonly Notifier $notifier,
        private readonly RecipientResolver $recipients,
    ) {}

    public function created(SupportTicketReply $reply): void
    {
        // Ghi chú nội bộ: chỉ admin thấy, tuyệt đối không báo người dùng.
        if ($reply->is_internal_note) {
            return;
        }

        $reply->loadMissing('ticket.user');
        $ticket = $reply->ticket;

        if (! $ticket) {
            return;
        }

        $creator = $ticket->user;

        // Không báo nếu chính người tạo ticket là người trả lời (tự báo bản thân).
        if (! $creator || $reply->user_id === $creator->getKey()) {
            return;
        }

        $this->notifier->send(
            NotificationEvent::TYPE_TICKET_REPLIED,
            $ticket,
            [$creator],
            [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'source' => NotificationEvent::SOURCE_ADMIN,
            ],
            $reply->user_id,
        );
    }
}
