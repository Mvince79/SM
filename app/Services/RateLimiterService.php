<?php

namespace App\Services;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class RateLimiterService
{
    public function tooManyLoginAttempts(string $email, string $ip): bool
    {
        return RateLimiter::tooManyAttempts($this->loginKey($email, $ip), 5);
    }

    public function hitLoginAttempt(string $email, string $ip): void
    {
        RateLimiter::hit($this->loginKey($email, $ip), 60);
    }

    public function clearLoginAttempts(string $email, string $ip): void
    {
        RateLimiter::clear($this->loginKey($email, $ip));
    }

    public function loginAvailableIn(string $email, string $ip): int
    {
        return RateLimiter::availableIn($this->loginKey($email, $ip));
    }

    public function tooManyOtpResends(int $userId): bool
    {
        return RateLimiter::tooManyAttempts($this->otpKey($userId), 3);
    }

    public function hitOtpResend(int $userId): void
    {
        RateLimiter::hit($this->otpKey($userId), 60);
    }

    public function otpResendAvailableIn(int $userId): int
    {
        return RateLimiter::availableIn($this->otpKey($userId));
    }

    private function loginKey(string $email, string $ip): string
    {
        return 'login:' . Str::lower($email) . '|' . $ip;
    }

    private function otpKey(int $userId): string
    {
        return 'otp-resend:' . $userId;
    }
}