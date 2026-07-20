<?php

namespace App\Services\Notification\Strategies;

use App\Models\MissingChild;
use App\Models\NotificationEvent;
use App\Models\User;
use App\Services\Notification\Contracts\NotificationStrategy;
use UnexpectedValueException;

class CaseApprovedInProvinceStrategy implements NotificationStrategy
{
    public function render(NotificationEvent $event, User $recipient): array
    {
        $case = $event->subject;

        if (! $case instanceof MissingChild) {
            throw new UnexpectedValueException(
                'CASE_APPROVED_IN_PROVINCE yêu cầu subject là MissingChild.'
            );
        }

        $case->loadMissing('province');
        $caseLabel = $case->case_code ?: "#{$case->getKey()}";
        $provinceName = $case->province?->name ?? 'khu vực của bạn';

        return [
            'title' => "Có hồ sơ mới tại {$provinceName}",
            'body' => "Hồ sơ {$caseLabel} vừa được phê duyệt tại {$provinceName}. Hãy xem chi tiết và chia sẻ nếu bạn có thông tin hữu ích.",
            'action_url' => route('missing.detail', ['id' => $case->getKey()]),
        ];
    }
}
