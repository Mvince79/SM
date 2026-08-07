<?php
namespace App\Services\Notification\Strategies;
use App\Models\NotificationEvent;
use App\Models\User;
use App\Services\Notification\Contracts\NotificationStrategy;
class HandoverReadyForVerifierStrategy implements NotificationStrategy
{
    public function render(NotificationEvent $event, User $recipient): array
    {
        $caseCode = $event->payload['case_code'] ?? '';
        return [
            'title' => 'Xác nhận bàn giao thành công',
            'body' => "Bạn đã xác nhận tiếp nhận hồ sơ {$caseCode} tại điểm bàn giao. Cảm ơn bạn đã hỗ trợ.",
            'action_url' => route('missing.detail', ['id' => $event->subject_id]),
        ];
    }
}