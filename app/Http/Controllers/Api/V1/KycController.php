<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitKycRequest;
use App\Models\KycVerification;
use Illuminate\Http\JsonResponse;

class KycController extends Controller
{
    /**
     * POST /api/v1/kyc/submit
     *
     * Nhận ảnh CCCD 2 mặt + selfie, tạo record kyc_verifications với
     * status PENDING, và set users.kyc_status = 'PENDING' ngay lập tức
     * (theo mô tả A-02: "Khi user nộp KYC, set kyc_status = 'PENDING'
     * ngay, đợi A-03 hoặc admin xử lý tiếp").
     *
     * eKYC thật (OCR + face match) là phần của A-03 - controller này CHƯA
     * gọi OCR/face match provider, chỉ lưu ảnh + tạo record PENDING.
     */
    public function store(SubmitKycRequest $request): JsonResponse
    {
        $user = $request->user();

        // Chặn nộp trùng khi đã PENDING hoặc đã VERIFIED
        if (in_array($user->kyc_status, ['PENDING', 'VERIFIED'], true)) {
            return response()->json([
                'message' => $user->kyc_status === 'PENDING'
                    ? 'Hồ sơ KYC của bạn đang chờ xét duyệt, vui lòng đợi.'
                    : 'Tài khoản của bạn đã được xác thực KYC.',
            ], 409);
        }

        // Lưu vào disk 'private' (không public) - ảnh CCCD/selfie là dữ liệu nhạy cảm
        $frontPath = $request->file('front_image')->store("kyc/{$user->id}", 'private');
        $backPath = $request->file('back_image')->store("kyc/{$user->id}", 'private');
        $selfiePath = $request->file('selfie')->store("kyc/{$user->id}", 'private');

        $kyc = KycVerification::create([
            'user_id' => $user->id,
            'id_number' => $request->validated('id_number'), // mã hóa qua Model mutator
            'full_name_on_id' => $request->validated('full_name_on_id'),
            'dob_on_id' => $request->validated('dob_on_id'),
            'front_image_path' => $frontPath,
            'back_image_path' => $backPath,
            'selfie_path' => $selfiePath,
            'status' => 'PENDING', // KycVerification thật không có hằng số STATUS_*, dùng string trực tiếp
        ]);

        $user->forceFill(['kyc_status' => 'PENDING'])->save();

        return response()->json([
            'message' => 'Đã nhận hồ sơ KYC, đang chờ xác thực.',
            'data' => [
                'id' => $kyc->id,
                'status' => $kyc->status,
            ],
        ], 201);
    }
}
