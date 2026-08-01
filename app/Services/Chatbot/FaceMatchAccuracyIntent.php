<?php

namespace App\Services\Chatbot;

class FaceMatchAccuracyIntent extends KeywordIntent
{
    public const NAME = 'FACE_MATCH_ACCURACY';

    public function name(): string
    {
        return self::NAME;
    }

    protected function keywords(): array
    {
        return [
            'ai nhan sai',
            'do chinh xac',
            'ai co dung khong',
            'face match',
            'arcface',
            'doi chieu khuon mat',
        ];
    }

    protected function responseMessage(): string
    {
        return 'TìmTrẻ.vn sử dụng ArcFace để sàng lọc và gợi ý các khuôn mặt có khả năng trùng khớp; giao diện hiện công bố tỷ lệ chính xác khoảng 97,8%. Kết quả AI không phải kết luận danh tính và có thể sai do góc chụp, ánh sáng hoặc chất lượng ảnh. Mọi kết quả vẫn phải được Admin kiểm tra thủ công và xác minh qua cơ quan có thẩm quyền trước khi hành động.';
    }
}
