<?php

namespace App\Services\Chatbot;

class ReportMissingChildIntent extends KeywordIntent
{
    public const NAME = 'REPORT_MISSING_CHILD';

    public function name(): string
    {
        return self::NAME;
    }

    protected function keywords(): array
    {
        return [
            'cach dang tin',
            'dang tin the nao',
            'bao tin mat tich',
            'bao mat tich',
            'tim con',
            'tre bi lac',
            'tre mat tich',
        ];
    }

    protected function responseMessage(): string
    {
        return 'Để báo tin trẻ mất tích: (1) đăng nhập và hoàn tất KYC; (2) bấm “+ Báo tin tìm con”, điền biểu mẫu 3 bước và tải ảnh rõ mặt; (3) gửi hồ sơ để đội ngũ kiểm duyệt phê duyệt trước khi công khai. Nếu tình huống khẩn cấp, hãy liên hệ Công an 113 hoặc Tổng đài quốc gia bảo vệ trẻ em 111 ngay, không chờ hồ sơ được duyệt.';
    }
}
