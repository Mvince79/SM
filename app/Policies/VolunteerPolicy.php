<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class VolunteerPolicy
{
    /**
     * Cổng gate trung tâm cho MỌI tính năng dành cho Tình nguyện viên
     * (dashboard, nearby-clues, assignments...). D-02/D-03 sẽ dùng
     * lại đúng method này thay vì viết if rải rác ở Controller/Livewire.
     */
    public function access(User $user): Response
    {
        if ($user->isBanned()) {
            return Response::deny('Tài khoản của bạn đã bị khóa, không thể sử dụng tính năng Tình nguyện viên.');
        }

        if (! $user->isVolunteer()) {
            return Response::deny('Bạn chưa được cấp vai trò Tình nguyện viên.');
        }

        if ($user->kyc_status !== 'VERIFIED') {
            return Response::deny('Cần xác thực CCCD (KYC) trước khi sử dụng tính năng Tình nguyện viên.');
        }

        return Response::allow();
    }
}