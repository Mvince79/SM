<?php
namespace App\Services\Notification\Strategies;

use App\Models\NotificationEvent;
use App\Models\User;
use App\Services\Notification\Contracts\NotificationStrategy;

class HandoverReadyForFamilyStrategy implements NotificationStrategy
{
    public function render(NotificationEvent $event, User $recipient): array
    {
        $caseCode = $event->payload['case_code'] ?? '';

        return [
            'title' => 'Đã tiếp nhận trẻ an toàn',
            'body' => "Hồ sơ {$caseCode} đã được xác nhận tiếp nhận tại điểm bàn giao. Vui lòng liên hệ để được hướng dẫn tiếp theo.",
            'action_url' => route('missing.detail', ['id' => $event->subject_id]),
        ];
    }
}