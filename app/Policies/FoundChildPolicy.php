<?php

namespace App\Policies;

use App\Models\FoundChild;
use App\Models\User;

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

    public function create(User $user): bool
    {
        return $user->isVerified();
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
