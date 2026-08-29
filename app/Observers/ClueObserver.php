<?php

namespace App\Observers;

use App\Models\Clue;
use App\Models\NotificationEvent;
use App\Services\Notification\Notifier;
use App\Services\Notification\RecipientResolver;
use Illuminate\Support\Facades\Auth;

/**
 * Phát thông báo cho luồng manh mối:
 *  - created            → CLUE_SUBMITTED (gia đình + admin)
 *  - is_verified chuyển false→true → CLUE_VERIFIED (gia đình) và
 *                           CLUE_VERIFIED_FOR_REPORTER (người gửi manh mối)
 *
 * Đặt ở observer thay vì từng nút bấm (CluesTable, CluesRelationManager) để mọi
 * đường xác minh đều được phủ, và tránh việc nút "Xác minh" hiện tại im lặng.
 */
class ClueObserver
{
    public function __construct(
        private readonly Notifier $notifier,
        private readonly RecipientResolver $recipients,
    ) {}

    public function created(Clue $clue): void
    {
        $this->emitSubmitted($clue);
    }

    public function updated(Clue $clue): void
    {
        if ($clue->wasChanged('is_verified') && $clue->is_verified) {
            $this->emitVerified($clue);
        }
    }

    private function emitSubmitted(Clue $clue): void
    {
        $clue->loadMissing('missingChild.user');
        $family = $clue->missingChild?->user;

        $this->notifier->send(
            NotificationEvent::TYPE_CLUE_SUBMITTED,
            $clue,
            $this->recipients->unique($family, $this->recipients->admins()),
            [
                'case_code' => $clue->missingChild?->case_code,
                'full_name' => $clue->missingChild?->full_name,
                'source' => NotificationEvent::SOURCE_COMMUNITY,
            ],
            $clue->user_id,
        );
    }

    private function emitVerified(Clue $clue): void
    {
        $clue->loadMissing('missingChild.user');
        $family = $clue->missingChild?->user;
        $reporter = $this->recipients->clueReporter($clue)->first();

        if ($family) {
            $this->notifier->send(
                NotificationEvent::TYPE_CLUE_VERIFIED,
                $clue,
                [$family],
                [
                    'case_code' => $clue->missingChild?->case_code,
                    'full_name' => $clue->missingChild?->full_name,
                    'source' => NotificationEvent::SOURCE_ADMIN,
                ],
                Auth::id(),
            );
        }

        if ($reporter) {
            $this->notifier->send(
                NotificationEvent::TYPE_CLUE_VERIFIED_FOR_REPORTER,
                $clue,
                [$reporter],
                [
                    'case_code' => $clue->missingChild?->case_code,
                    'full_name' => $clue->missingChild?->full_name,
                    'source' => NotificationEvent::SOURCE_ADMIN,
                ],
                Auth::id(),
            );
        }
    }
}
