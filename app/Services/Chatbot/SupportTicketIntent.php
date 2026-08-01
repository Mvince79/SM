<?php

namespace App\Services\Chatbot;

class SupportTicketIntent extends KeywordIntent
{
    public const NAME = 'SUPPORT_TICKET';

    public function name(): string
    {
        return self::NAME;
    }

    protected function keywords(): array
    {
        return [
            'lien he admin',
            'gui yeu cau',
            'ho tro',
            'support ticket',
            'ticket',
            'khieu nai',
            'bao loi',
        ];
    }

    protected function responseMessage(): string
    {
        return 'Bạn có thể đăng nhập, mở Trung tâm hỗ trợ và chọn “Gửi yêu cầu hỗ trợ”. Hãy chọn loại yêu cầu, mức ưu tiên và mô tả chi tiết; sau khi gửi, bạn có thể theo dõi trạng thái và phản hồi tại lịch sử ticket. Đội ngũ TìmTrẻ.vn sẽ phản hồi sớm nhất có thể. Trường hợp liên quan đến an toàn khẩn cấp của trẻ, hãy gọi 111 hoặc 113 thay vì chờ ticket.';
    }
}
