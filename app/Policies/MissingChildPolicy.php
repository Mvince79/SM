<?php

namespace App\Policies;

use App\Models\MissingChild;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MissingChildPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, MissingChild $missingChild): Response
    {
        // Anyone can view approved reports
        if ($missingChild->status === 'APPROVED') {
            return Response::allow();
        }

        // For non-approved reports, only owner or admin can view
        if ($user && ($user->isAdmin() || $missingChild->user_id === $user->id)) {
            return Response::allow();
        }

        return Response::deny('Bạn không có quyền xem hồ sơ chưa được duyệt này.');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): Response
    {
        if ($user->isBanned()) {
            return Response::deny('Tài khoản của bạn đã bị khóa, không thể tạo tin mới.');
        }

        if ($user->kyc_status !== 'VERIFIED') {
            return Response::deny('Cần xác thực CCCD (KYC) trước khi đăng tin tìm trẻ thất lạc.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MissingChild $missingChild): Response
    {
        if ($user->isBanned()) {
            return Response::deny('Tài khoản của bạn đã bị khóa.');
        }

        if ($user->isAdmin() || $missingChild->user_id === $user->id) {
            return Response::allow();
        }

        return Response::deny('Bạn không phải là chủ sở hữu của tin đăng này và không phải là quản trị viên.');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MissingChild $missingChild): Response
    {
        if ($user->isBanned()) {
            return Response::deny('Tài khoản của bạn đã bị khóa.');
        }

        if ($user->isAdmin() || $missingChild->user_id === $user->id) {
            return Response::allow();
        }

        return Response::deny('Bạn không có quyền xóa tin đăng này.');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MissingChild $missingChild): Response
    {
        if ($user->isAdmin() || $missingChild->user_id === $user->id) {
            return Response::allow();
        }

        return Response::deny('Bạn không có quyền khôi phục tin đăng này.');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MissingChild $missingChild): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        return Response::deny('Chỉ có quản trị viên mới có thể xóa vĩnh viễn tin đăng này.');
    }
}
