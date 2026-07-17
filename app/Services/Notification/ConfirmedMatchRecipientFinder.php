<?php

namespace App\Services\Notification;

use App\Models\FaceMatchResult;
use App\Models\ThirdPartyVerifierProfile;
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

        $verifiers = ThirdPartyVerifierProfile::query()
            ->with('user')
            ->whereNotNull('verified_at')
            ->whereHas('handoverPoint', fn ($query) => $query
                ->where('province_id', $match->missingChild?->province_id)
                ->where('is_active', true))
            ->get()
            ->pluck('user')
            ->filter(fn (?User $user): bool => $user?->role === 'THIRD_PARTY_VERIFIER'
                && $user->status === 'ACTIVE');

        $onlineAdmins = User::query()
            ->admins()
            ->active()
            ->where('last_active_at', '>=', now()->subMinutes(5))
            ->get();

        return $caseUsers
            ->merge($verifiers)
            ->merge($onlineAdmins)
            ->unique('id')
            ->values();
    }
}
