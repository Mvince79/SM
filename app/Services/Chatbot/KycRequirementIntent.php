<?php

namespace App\Services\Chatbot;

class KycRequirementIntent extends KeywordIntent
{
    public const NAME = 'KYC_REQUIREMENT';

    public function name(): string
    {
        return self::NAME;
    }

    protected function keywords(): array
    {
        return [
            'kyc',
            'cmnd',
            'cccd',
            'xac thuc danh tinh',
            'giay to tuy than',
        ];
    }

    protected function responseMessage(): string
    {
        return 'Để đăng tin tìm trẻ, báo tin trẻ được tìm thấy hoặc sử dụng tính năng tình nguyện viên, bạn cần hoàn tất KYC. Hệ thống chấp nhận CMND 9 số hoặc CCCD 12 số, thực hiện OCR giấy tờ và đối chiếu ảnh selfie. Số giấy tờ được mã hóa AES-256 và ảnh xác thực chỉ người kiểm duyệt được phân quyền mới có thể xem. Bạn có thể bắt đầu tại trang Hồ sơ > Xác thực danh tính.';
    }
}
