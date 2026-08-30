<?php

namespace App\Observers;

use App\Models\FoundChild;
use App\Models\NotificationEvent;
use App\Models\User;
use App\Services\Notification\Notifier;
use App\Services\Notification\RecipientResolver;
use App\Support\CacheVersion;
use Illuminate\Support\Facades\Log;

class FoundChildObserver
{
    public function __construct(
        private readonly Notifier $notifier,
        private readonly RecipientResolver $recipients,
    ) {
    }

    private function invalidateMapCache(): void
    {
        try {
            CacheVersion::bump(CacheVersion::MAP_DATA);
        } catch (\Throwable $e) {
            Log::warning('FoundChildObserver: cache flush failed: ' . $e->getMessage());
        }
    }

    public function created(FoundChild $foundChild): void
    {
        $this->invalidateMapCache();

        // D-05 bổ sung: báo admin có tin tìm thấy trẻ mới chờ duyệt.
        try {
            $admins = $this->recipients->admins();
            if ($admins->isNotEmpty()) {
                $this->notifier->send(
                    NotificationEvent::TYPE_FOUND_SUBMITTED,
                    $foundChild,
                    $admins,
                    [],
                    null,
                );
            }
        } catch (\Throwable $e) {
            Log::warning('FOUND_SUBMITTED notification failed: ' . $e->getMessage());
        }
    }

    public function updated(FoundChild $foundChild): void
    {
        if ($foundChild->wasChanged(['status', 'lat', 'lng'])) {
            $this->invalidateMapCache();
        }

        // Duyệt tin "Trẻ đã tìm thấy" → báo người báo tin, và gia đình có hồ sơ
        // khớp (FaceMatchResult CONFIRMED) nếu có. Pattern tái dùng observer +
        // Notifier::send() + RecipientResolver::unique() (như MissingChildObserver).
        if ($foundChild->wasChanged('status') && $foundChild->status === FoundChild::STATUS_APPROVED) {
            $reporter = $foundChild->user;

            $families = $foundChild->faceMatchResults()
                ->where('status', 'CONFIRMED')
                ->with('missingChild.user')
                ->get()
                ->map(fn ($match) => $match->missingChild?->user)
                ->filter();

            $recipients = $this->recipients->unique($reporter, $families);

            if ($recipients->isNotEmpty()) {
                $this->notifier->send(
                    NotificationEvent::TYPE_FOUND_APPROVED,
                    $foundChild,
                    $recipients,
                    [],
                    $foundChild->approved_by,
                );
            }
        }

        // Từ chối tin tìm thấy → báo người báo tin (kèm lý do). D-05 bổ sung.
        if ($foundChild->wasChanged('status') && $foundChild->status === FoundChild::STATUS_REJECTED) {
            $owner = $foundChild->user;

            // Phòng vệ: getEloquentQuery() của resource có thể eager-load user chỉ
            // với vài cột (id,full_name), khiến $owner->role = null. deliver() lấy
            // $user->role đắp vào role_at_time (cột NOT NULL) → nếu null sẽ ném lỗi
            // và recipient không bao giờ được tạo (chuông admin không báo). Nạp lại
            // user đầy đủ để đảm bảo role có mặt trước khi gửi.
            if ($owner && $owner->role === null) {
                $owner = User::find($owner->getKey());
            }

            if ($owner) {
                $this->notifier->send(
                    NotificationEvent::TYPE_FOUND_REJECTED,
                    $foundChild,
                    [$owner],
                    [
                        'reason' => $foundChild->rejection_reason,
                    ],
                    null,
                );
            }
        }
    }

    public function deleted(FoundChild $foundChild): void
    {
        $this->invalidateMapCache();
    }

    public function restored(FoundChild $foundChild): void
    {
        $this->invalidateMapCache();
    }
}
