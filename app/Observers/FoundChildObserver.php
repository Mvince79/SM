<?php

namespace App\Observers;

use App\Models\FoundChild;
use App\Models\NotificationEvent;
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
