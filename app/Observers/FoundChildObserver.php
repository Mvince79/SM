<?php

namespace App\Observers;

use App\Models\FoundChild;
use App\Models\NotificationEvent;
use App\Services\Notification\Notifier;
use App\Services\Notification\RecipientResolver;
use App\Support\CacheVersion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FoundChildObserver
{
    public function __construct(
        private readonly Notifier $notifier,
        private readonly RecipientResolver $recipients,
    ) {}

    public function created(FoundChild $foundChild): void
    {
        $this->invalidateMapCache();

        // H-01 — Tiếp nhận tin: người báo biết tin đã nhận, admin biết có tin cần duyệt.
        if ($foundChild->status === FoundChild::STATUS_PENDING) {
            $this->emitFoundSubmitted($foundChild);
        }
    }

    public function updated(FoundChild $foundChild): void
    {
        if (! $foundChild->wasChanged('status')) {
            if ($foundChild->wasChanged(['lat', 'lng'])) {
                $this->invalidateMapCache();
            }
            return;
        }

        $to = $foundChild->status;

        if ($to === FoundChild::STATUS_APPROVED) {
            $this->emitFoundApproved($foundChild);
        } elseif ($to === FoundChild::STATUS_REJECTED) {
            $this->emitFoundRejected($foundChild);
        } else {
            $this->invalidateMapCache();
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

    private function emitFoundSubmitted(FoundChild $foundChild): void
    {
        $finder = $foundChild->user;

        $this->notifier->send(
            NotificationEvent::TYPE_FOUND_SUBMITTED,
            $foundChild,
            $this->recipients->unique($finder, $this->recipients->admins()),
            [
                'source' => NotificationEvent::SOURCE_SYSTEM,
            ],
            $finder?->getKey(),
        );
    }

    private function emitFoundApproved(FoundChild $foundChild): void
    {
        if ($finder = $foundChild->user) {
            $this->notifier->send(
                NotificationEvent::TYPE_FOUND_APPROVED,
                $foundChild,
                [$finder],
                [
                    'source' => NotificationEvent::SOURCE_ADMIN,
                ],
                Auth::id(),
            );
        }
    }

    private function emitFoundRejected(FoundChild $foundChild): void
    {
        if ($finder = $foundChild->user) {
            $this->notifier->send(
                NotificationEvent::TYPE_FOUND_REJECTED,
                $foundChild,
                [$finder],
                [
                    'reason' => $foundChild->rejection_reason ?? 'Không đủ thông tin xác minh.',
                    'source' => NotificationEvent::SOURCE_ADMIN,
                ],
                Auth::id(),
            );
        }
    }

    private function invalidateMapCache(): void
    {
        try {
            CacheVersion::bump(CacheVersion::MAP_DATA);
        } catch (\Throwable $e) {
            Log::warning('FoundChildObserver: cache flush failed: ' . $e->getMessage());
        }
    }
}
