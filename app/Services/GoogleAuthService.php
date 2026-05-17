<?php

namespace App\Services;

use App\Models\User;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class GoogleAuthService
{
    public function findOrCreate(SocialiteUser $googleUser): User
    {
        // 1. Tìm theo google_id
        $user = User::where('google_id', $googleUser->getId())->first();

        if ($user) {
            $user->update([
                'avatar_url' => $googleUser->getAvatar() ?? $user->avatar_url,
                'last_active_at' => now(),
            ]);
            return $user;
        }

        // 2. Tìm theo email — merge tài khoản cũ
        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar() ?? $user->avatar_url,
                'email_verified_at' => $user->email_verified_at ?? now(),
                'status' => $user->status === 'PENDING' ? 'ACTIVE' : $user->status,
            ]);
            return $user;
        }

        // 3. Tạo user mới
        return User::create([
            'full_name' => $googleUser->getName() ?? $googleUser->getEmail(),
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'avatar_url' => $googleUser->getAvatar(),
            'password' => null,
            'email_verified_at' => now(),
            'status' => 'ACTIVE',
            'role' => 'USER',
        ]);
    }
}