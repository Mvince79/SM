<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\OtpVerificationNotification;
use Illuminate\Support\Facades\Hash;

class OtpService
{
    public function send(User $user): void
    {
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'otp_code' => Hash::make($otp),
            'otp_expires_at' => now()->addMinutes(15),
        ]);

        $user->notify(new OtpVerificationNotification($otp));
    }

    public function verify(User $user, string $otp): bool
    {
        if (!$user->otp_expires_at || now()->isAfter($user->otp_expires_at)) {
            return false;
        }

        return Hash::check($otp, $user->otp_code);
    }

    public function markVerified(User $user): void
    {
        $user->update([
            'email_verified_at' => now(),
            'status' => 'ACTIVE',
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);
    }
}