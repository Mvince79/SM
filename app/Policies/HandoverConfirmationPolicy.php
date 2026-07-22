<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

/**
 * I-03 — Verifier CHỈ có quyền xác nhận tiếp nhận trẻ (không phải là
 * quyền duyệt/từ chối tin đăng, quyền đó là của MissingChildPolicy/
 * FoundChildPolicy dành riêng cho ADMIN/MODERATOR).
 *
 * Dùng chung cho cả MissingChild lẫn FoundChild vì cả 2 bảng đều có
 * cùng bộ cột handover_point_id / handover_confirmed_at / handover_confirmed_by
 * (thêm ở H-06), và cả 2 đều có province_id.
 *
 * Đăng ký qua Gate::define() (giống pattern 'access-volunteer-features'
 * đã có trong AuthServiceProvider), KHÔNG dùng Gate::policy() vì 1 model
 * chỉ được gắn 1 policy chính, còn MissingChild đã có MissingChildPolicy rồi.
 *
 * @param  Model  $handoverable  MissingChild hoặc FoundChild
 */
class HandoverConfirmationPolicy
{
    public function confirm(User $user, Model $handoverable): Response
    {
        if (!$user->isThirdPartyVerifier()) {
            return Response::deny('Chỉ tài khoản Third-Party Verifier mới có quyền xác nhận tiếp nhận trẻ.');
        }

        if ($handoverable->status !== 'APPROVED') {
            return Response::deny('Chỉ xác nhận được hồ sơ đã được duyệt (APPROVED).');
        }

        if (!is_null($handoverable->handover_confirmed_at)) {
            return Response::deny('Hồ sơ này đã được xác nhận bàn giao trước đó.');
        }

        $profile = $user->thirdPartyVerifierProfile;

        if (!$profile || !$profile->handoverPoint) {
            return Response::deny('Tài khoản của bạn chưa được gán vào điểm bàn giao nào.');
        }

        if ((int) $handoverable->province_id !== (int) $profile->handoverPoint->province_id) {
            return Response::deny('Hồ sơ này không thuộc khu vực (tỉnh) bạn đang phụ trách.');
        }

        return Response::allow();
    }
}