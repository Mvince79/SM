<?php

namespace App\Services;

use App\Models\AdminChatAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ChatAssignmentService
{
    public function __construct(private readonly int $onlineWindowMinutes = 5) {}

    /**
     * Assign a requester to the online admin with the fewest ACTIVE chats.
     * Locking the eligible admin rows serializes competing assignments and keeps
     * near-simultaneous requests from selecting the same least-loaded admin.
     */
    public function assign(User $requester): AdminChatAssignment
    {
        return DB::transaction(function () use ($requester): AdminChatAssignment {
            User::query()->whereKey($requester->getKey())->lockForUpdate()->firstOrFail();

            $existing = AdminChatAssignment::query()
                ->where('requester_user_id', $requester->getKey())
                ->where('status', AdminChatAssignment::STATUS_ACTIVE)
                ->first();

            if ($existing) {
                return $existing;
            }

            $admins = $this->onlineAdminsForUpdate();

            if ($admins->isEmpty()) {
                throw new RuntimeException('Không có quản trị viên trực tuyến để tiếp nhận cuộc chat.');
            }

            $loads = AdminChatAssignment::query()
                ->selectRaw('admin_user_id, COUNT(*) AS active_count')
                ->whereIn('admin_user_id', $admins->modelKeys())
                ->where('status', AdminChatAssignment::STATUS_ACTIVE)
                ->groupBy('admin_user_id')
                ->pluck('active_count', 'admin_user_id');

            $admin = $admins
                ->sortBy(fn (User $admin): string => sprintf(
                    '%010d-%020d',
                    (int) ($loads[$admin->getKey()] ?? 0),
                    $admin->getKey()
                ))
                ->first();

            return AdminChatAssignment::create([
                'admin_user_id' => $admin->getKey(),
                'requester_user_id' => $requester->getKey(),
                'status' => AdminChatAssignment::STATUS_ACTIVE,
                'assigned_at' => now(),
            ]);
        }, 3);
    }

    private function onlineAdminsForUpdate(): Collection
    {
        return User::query()
            ->admins()
            ->active()
            ->where('last_active_at', '>=', now()->subMinutes($this->onlineWindowMinutes))
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }
}
