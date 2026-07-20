<x-mail::message>
# Kết quả nhận diện khuôn mặt đã được xác nhận

Xin chào {{ $recipientName }},

**Vai trò nhận thông báo:** {{ $roleLabel }}

{{ $roleIntroduction }}

## {{ $notificationTitle }}

{{ $notificationBody }}

@if ($actionUrl)
<x-mail::button :url="$actionUrl">
Xem kết quả trùng khớp
</x-mail::button>
@endif

Vì sự an toàn của trẻ, vui lòng chỉ phối hợp thông qua điểm bàn giao được xác minh.

Trân trọng,<br>
**TìmTrẻ.vn**
</x-mail::message>
