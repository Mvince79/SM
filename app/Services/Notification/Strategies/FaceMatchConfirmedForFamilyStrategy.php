<?php

namespace App\Services\Notification\Strategies;

use App\Models\NotificationEvent;
use App\Models\User;

class FaceMatchConfirmedForFamilyStrategy extends AbstractFaceMatchConfirmedStrategy
{
    public function render(NotificationEvent $event, User $recipient): array
    {
        $match = $this->match($event);
        $childName = $match->missingChild?->full_name ?? $this->caseLabel($match);

        return [
            'title' => 'Đã xác nhận một kết quả trùng khớp',
            'body' => "Hệ thống đã xác nhận một trường hợp có khả năng liên quan đến hồ sơ {$childName}. Vui lòng xem chi tiết và phối hợp xác minh cùng cơ quan chức năng.",
            'action_url' => $this->caseUrl($match),
        ];
    }
}
