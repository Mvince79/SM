<?php

namespace App\Observers;

use App\Jobs\DispatchProvinceCaseApprovedNotificationsJob;
use App\Models\EscalationQueue;
use App\Models\FaceMatchResult;
use App\Models\MissingChild;
use App\Models\NotificationEvent;
use App\Models\User;
use App\Services\Notification\Notifier;
use App\Services\Notification\RecipientResolver;
use App\Support\CacheVersion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MissingChildObserver
{
    public function __construct(
        private readonly Notifier $notifier,
        private readonly RecipientResolver $recipients,
    ) {}

    /**
     * Handle the MissingChild "creating" event.
     * Chạy TRƯỚC khi INSERT → tránh race condition sinh code trùng.
     */
    public function creating(MissingChild $missingChild): void
    {
        $missingChild->case_code = $this->generateCaseCode();
    }

    /**
     * Sinh case_code duy nhất, thread-safe nhờ lockForUpdate bên trong transaction.
     */
    private function generateCaseCode(): string
    {
        return DB::transaction(function () {
            $year = now()->year;
            $prefix = "MC-{$year}-";

            $maxCode = DB::table('missing_children')
                ->where('case_code', 'like', "{$prefix}%")
                ->lockForUpdate()
                ->max('case_code');

            $sequence = $maxCode
                ? (int) substr($maxCode, -5) + 1
                : 1;

            return $prefix . str_pad($sequence, 5, '0', STR_PAD_LEFT);
        });
    }

    public function created(MissingChild $missingChild): void
    {
        $this->invalidateMapAndHomeCache();

        try {
            $qrService = app(\App\Services\QrCodeService::class);
            $detailUrl = url('/missing/' . $missingChild->case_code);
            $path = $qrService->generate($detailUrl, $missingChild->case_code);

            $missingChild->qr_code_path = $path;
            $missingChild->saveQuietly();
        } catch (\Throwable $e) {
            Log::warning('QR generation failed: ' . $e->getMessage());
        }

        // H-01 — Tiếp nhận tin: gia đình biết tin đã nhận, admin biết có tin cần duyệt.
        if ($missingChild->status === MissingChild::STATUS_PENDING) {
            $this->emitCaseSubmitted($missingChild);
        }
    }

    public function updated(MissingChild $missingChild): void
    {
        if (! $missingChild->wasChanged('status')) {
            if ($missingChild->wasChanged(['lat', 'lng'])) {
                $this->invalidateMapAndHomeCache();
            }
            return;
        }

        $this->invalidateMapAndHomeCache();
        $this->logActivity($missingChild);

        $to = $missingChild->status;

        if ($to === MissingChild::STATUS_APPROVED) {
            // Giữ nguyên: cộng đồng cùng tỉnh nhận thông báo riêng (job).
            DispatchProvinceCaseApprovedNotificationsJob::dispatch($missingChild->getKey());
            $this->emitCaseApproved($missingChild);
        } elseif ($to === MissingChild::STATUS_REJECTED) {
            $this->emitCaseRejected($missingChild);
        } elseif ($to === MissingChild::STATUS_FOUND) {
            $this->emitCaseMarkedFound($missingChild);
        } elseif ($to === MissingChild::STATUS_HANDED_OVER) {
            $this->emitHandoverReady($missingChild);
        } elseif ($to === MissingChild::STATUS_REUNITED) {
            $this->emitReunionConfirmed($missingChild);
            $this->resolveEscalations($missingChild);
        } elseif ($to === MissingChild::STATUS_UNREACHABLE_FAMILY) {
            $this->emitUnreachableFamily($missingChild);
            $this->resolveEscalations($missingChild);
        }
    }

    public function deleted(MissingChild $missingChild): void
    {
        $this->invalidateMapAndHomeCache();
    }

    public function restored(MissingChild $missingChild): void
    {
        $this->invalidateMapAndHomeCache();
    }

    // ─── Phát thông báo từng transition ────────────────────────────────────────

    private function emitCaseSubmitted(MissingChild $case): void
    {
        $family = $this->recipients->familyOf($case);

        $this->notifier->send(
            NotificationEvent::TYPE_CASE_SUBMITTED,
            $case,
            $this->recipients->unique($family, $this->recipients->admins()),
            [
                'case_code' => $case->case_code,
                'full_name' => $case->full_name,
                'source' => NotificationEvent::SOURCE_SYSTEM,
            ],
            $case->user_id,
        );
    }

    private function emitCaseApproved(MissingChild $case): void
    {
        if ($family = $this->recipients->familyOf($case)) {
            $this->notifier->send(
                NotificationEvent::TYPE_CASE_APPROVED,
                $case,
                [$family],
                [
                    'case_code' => $case->case_code,
                    'full_name' => $case->full_name,
                    'source' => NotificationEvent::SOURCE_ADMIN,
                ],
                Auth::id(),
            );
        }
    }

    private function emitCaseRejected(MissingChild $case): void
    {
        if ($family = $this->recipients->familyOf($case)) {
            $this->notifier->send(
                NotificationEvent::TYPE_CASE_REJECTED,
                $case,
                [$family],
                [
                    'case_code' => $case->case_code,
                    'full_name' => $case->full_name,
                    'reason' => $case->rejection_reason ?? 'Không đủ thông tin xác minh.',
                    'source' => NotificationEvent::SOURCE_ADMIN,
                ],
                Auth::id(),
            );
        }
    }

    private function emitCaseMarkedFound(MissingChild $case): void
    {
        if ($family = $this->recipients->familyOf($case)) {
            $this->notifier->send(
                NotificationEvent::TYPE_CASE_MARKED_FOUND,
                $case,
                [$family],
                [
                    'case_code' => $case->case_code,
                    'full_name' => $case->full_name,
                    'source' => NotificationEvent::SOURCE_ADMIN,
                ],
                Auth::id(),
            );
        }
    }

    private function emitHandoverReady(MissingChild $case): void
    {
        $family = $this->recipients->familyOf($case);
        $finder = $this->finderOf($case);

        if ($family) {
            $this->notifier->send(
                NotificationEvent::TYPE_HANDOVER_READY,
                $case,
                [$family],
                [
                    'case_code' => $case->case_code,
                    'full_name' => $case->full_name,
                    'source' => NotificationEvent::SOURCE_ADMIN,
                ],
                Auth::id(),
            );
        }

        if ($finder) {
            $this->notifier->send(
                NotificationEvent::TYPE_FOUND_CASE_HANDED_OVER,
                $case,
                [$finder],
                [
                    'case_code' => $case->case_code,
                    'full_name' => $case->full_name,
                    'source' => NotificationEvent::SOURCE_ADMIN,
                ],
                Auth::id(),
            );
        }
    }

    private function emitReunionConfirmed(MissingChild $case): void
    {
        $family = $this->recipients->familyOf($case);
        $finder = $this->finderOf($case);
        $actorId = $case->reunited_confirmed_by ?? Auth::id();

        $this->notifier->send(
            NotificationEvent::TYPE_REUNION_CONFIRMED,
            $case,
            $this->recipients->unique($family, $this->recipients->admins()),
            [
                'case_code' => $case->case_code,
                'full_name' => $case->full_name,
                'source' => $case->reunited_verification_method === MissingChild::REUNION_METHOD_FAMILY_SELF
                    ? NotificationEvent::SOURCE_FAMILY
                    : NotificationEvent::SOURCE_ADMIN,
            ],
            $actorId,
        );

        if ($finder) {
            $this->notifier->send(
                NotificationEvent::TYPE_FOUND_CASE_REUNITED,
                $case,
                [$finder],
                [
                    'case_code' => $case->case_code,
                    'full_name' => $case->full_name,
                    'source' => NotificationEvent::SOURCE_SYSTEM,
                ],
                $actorId,
            );
        }
    }

    private function emitUnreachableFamily(MissingChild $case): void
    {
        $family = $this->recipients->familyOf($case);

        $this->notifier->send(
            NotificationEvent::TYPE_CASE_UNREACHABLE_FAMILY,
            $case,
            $this->recipients->unique($family, $this->recipients->admins()),
            [
                'case_code' => $case->case_code,
                'full_name' => $case->full_name,
                'source' => NotificationEvent::SOURCE_SYSTEM,
            ],
        );
    }

    // ─── Hỗ trợ ───────────────────────────────────────────────────────────────

    /**
     * Người đăng tin tìm thấy đã khớp (qua kết quả AI CONFIRMED) — để báo khi
     * hồ sơ chuyển HANDED_OVER / REUNITED.
     */
    private function finderOf(MissingChild $case): ?User
    {
        $match = FaceMatchResult::query()
            ->where('missing_id', $case->getKey())
            ->where('status', 'CONFIRMED')
            ->first();

        return $match ? $this->recipients->finderOf($match) : null;
    }

    private function resolveEscalations(MissingChild $case): void
    {
        EscalationQueue::query()
            ->where('status', EscalationQueue::STATUS_PENDING)
            ->whereHas('notificationRecipient', function ($q) use ($case) {
                $q->whereHas('event', function ($e) use ($case) {
                    $e->where('subject_type', $case->getMorphClass())
                        ->where('subject_id', $case->getKey());
                });
            })
            ->update([
                'status' => EscalationQueue::STATUS_RESOLVED,
                'resolved_at' => now(),
                'resolved_by_admin_id' => Auth::id(),
            ]);
    }

    private function invalidateMapAndHomeCache(): void
    {
        try {
            CacheVersion::bump(CacheVersion::MAP_DATA);
            CacheVersion::bump(CacheVersion::HOME_HIGHLIGHTS);
        } catch (\Throwable $e) {
            Log::warning('MissingChildObserver: cache flush failed: ' . $e->getMessage());
        }
    }

    private function logActivity(MissingChild $missingChild): void
    {
        try {
            activity()
                ->performedOn($missingChild)
                ->event('updated')
                ->withProperties([
                    'old' => array_intersect_key($missingChild->getOriginal(), $missingChild->getChanges()),
                    'attributes' => $missingChild->getChanges(),
                ])
                ->log("Đã cập nhật tin tìm kiếm trẻ thất lạc: {$missingChild->full_name} ({$missingChild->case_code})");
        } catch (\Throwable $e) {
            Log::warning('Activity log failed: ' . $e->getMessage());
        }
    }
}
