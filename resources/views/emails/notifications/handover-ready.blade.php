<x-mail::message>
# Sẵn sàng phối hợp bàn giao an toàn

Xin chào {{ $recipientName }},

**Vai trò nhận thông báo:** {{ $roleLabel }}

{{ $roleIntroduction }}

## {{ $notificationTitle }}

{{ $notificationBody }}

@if ($actionUrl)
<x-mail::button :url="$actionUrl">
Xem hướng dẫn bàn giao
</x-mail::button>
@endif

Không tự ý giao nhận trẻ giữa các cá nhân. Hãy thực hiện tại điểm tiếp nhận có thẩm quyền hoặc liên hệ Tổng đài 111.

Trân trọng,<br>
**TìmTrẻ.vn**
</x-mail::message>
