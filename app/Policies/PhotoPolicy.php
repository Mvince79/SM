<?php

namespace App\Policies;

use App\Models\Photo;
use App\Models\User;

class PhotoPolicy
{
    public function viewOriginal(User $user, Photo $photo): bool
    {
        return $user->isAdmin() || $user->hasVerifiedKyc();
    }

    public function viewThumbnail(User $user, Photo $photo): bool
    {
        return $user->isVerified();
    }
}
