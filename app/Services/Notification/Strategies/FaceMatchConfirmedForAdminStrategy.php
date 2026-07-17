<?php

namespace App\Services\Notification\Strategies;

use App\Models\NotificationEvent;
use App\Models\User;

class FaceMatchConfirmedForAdminStrategy extends AbstractFaceMatchConfirmedStrategy
{
    public function render(NotificationEvent $event, User $recipient): array
    {
        $match = $this->match($event);
        $caseLabel = $this->caseLabel($match);
        $score = number_format((float) $match->score * 100, 2);

        return [
            'title' => 'Kết quả AI đã được xác nhận',
            'body' => "Hồ sơ {$caseLabel} đã được xác nhận trùng khớp với độ tương đồng {$score}%. Hãy theo dõi quá trình phối hợp và bàn giao.",
            'action_url' => $this->caseUrl($match),
        ];
    }
}
