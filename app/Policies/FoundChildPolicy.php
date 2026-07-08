<?php

namespace App\Policies;

use App\Models\FoundChild;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FoundChildPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, FoundChild $foundChild): bool
    {
        return true;
    }

    public function create(User $user): Response
    {
        if (!$user->isVerified()) {
            return Response::deny('Vui lòng xác thực email trước khi đăng tin.');
        }

        if ($user->kyc_status !== 'VERIFIED') {
            return Response::deny('Cần xác thực CCCD (KYC) trước khi đăng tin trẻ đã tìm thấy.');
        }

        return Response::allow();
    }

    public function update(User $user, FoundChild $foundChild): bool
    {
        return $user->id === $foundChild->user_id || $user->isAdmin();
    }

    public function delete(User $user, FoundChild $foundChild): bool
    {
        return $user->id === $foundChild->user_id || $user->isAdmin();
    }
}
