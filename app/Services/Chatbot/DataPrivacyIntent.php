<?php

namespace App\Services\Chatbot;

class DataPrivacyIntent extends KeywordIntent
{
    public const NAME = 'DATA_PRIVACY';

    public function name(): string
    {
        return self::NAME;
    }

    protected function keywords(): array
    {
        return [
            'bao mat',
            'rieng tu',
            'lo thong tin',
            'lo du lieu',
            'so dien thoai co lo khong',
            'du lieu ca nhan',
            'xoa du lieu',
        ];
    }

    protected function responseMessage(): string
    {
        return 'TìmTrẻ.vn không bán dữ liệu cá nhân. Số CMND/CCCD được mã hóa AES-256; ảnh giấy tờ, selfie và dữ liệu đối chiếu khuôn mặt được lưu ở khu vực hạn chế, chỉ người kiểm duyệt được phân quyền mới có thể truy cập. Tin công khai không bao gồm số giấy tờ hoặc ảnh giấy tờ. Bạn có thể yêu cầu truy cập, chỉnh sửa hoặc xóa dữ liệu qua hotro@timtre.vn, trừ dữ liệu phải lưu theo nghĩa vụ pháp lý.';
    }
}
