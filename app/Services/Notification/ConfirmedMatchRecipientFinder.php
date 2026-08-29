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
        // Không lọc theo last_active_at: admin nhận thông báo khớp xác nhận dù đang
        // không "online" 5 phút gần nhất (thông báo quan trọng cần tới được người xử lý,
        // không phụ thuộc vào indicator online). Chỉ lọc active để tránh gửi cho tài
        // khoản bị khoá. Việc cập nhật last_active_at định kỳ do middleware TouchLastActive.
        $admins = User::query()
            ->admins()
            ->active()
            ->get();

        return $caseUsers
            ->merge($admins)
            ->unique('id')
            ->values();
    }
}
