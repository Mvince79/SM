<?php

namespace App\Services\Notification;

use App\Models\FaceMatchResult;
use App\Models\User;
use Illuminate\Support\Collection;

class ConfirmedMatchRecipientFinder
{
    /**
     * @return Collection<int, User>
     */
    public function find(FaceMatchResult $match): Collection
    {
        $match->loadMissing(['missingChild.user', 'foundChild.user']);

        $caseUsers = collect([
            $match->missingChild?->user,
            $match->foundChild?->user,
        ])->filter();

        // G-01 — role THIRD_PARTY_VERIFIER đã gỡ bỏ; không còn gửi cho bên thứ ba xác minh.
        $onlineAdmins = User::query()
            ->admins()
            ->active()
            ->where('last_active_at', '>=', now()->subMinutes(5))
            ->get();

        return $caseUsers
            ->merge($onlineAdmins)
            ->unique('id')
            ->values();
    }
}
