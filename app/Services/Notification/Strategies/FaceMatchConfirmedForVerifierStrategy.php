<?php

namespace App\Services\Notification\Strategies;

use App\Models\NotificationEvent;
use App\Models\User;

class FaceMatchConfirmedForVerifierStrategy extends AbstractFaceMatchConfirmedStrategy
{
    public function render(NotificationEvent $event, User $recipient): array
    {
        $match = $this->match($event);
        $caseLabel = $this->caseLabel($match);

        return [
            'title' => 'Yêu cầu phối hợp tiếp nhận an toàn',
            'body' => "Kết quả trùng khớp của hồ sơ {$caseLabel} đã được xác nhận. Vui lòng kiểm tra và chuẩn bị phối hợp tại điểm bàn giao phụ trách.",
            'action_url' => $this->caseUrl($match),
        ];
    }
}
