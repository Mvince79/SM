<x-mail::message>
# Hồ sơ trẻ đã được phê duyệt

Xin chào {{ $recipientName }},

**Vai trò nhận thông báo:** {{ $roleLabel }}

{{ $roleIntroduction }}

## {{ $notificationTitle }}

{{ $notificationBody }}

@if ($actionUrl)
<x-mail::button :url="$actionUrl">
Xem hồ sơ
</x-mail::button>
@endif

Hãy chia sẻ thông tin có trách nhiệm và không công khai dữ liệu nhạy cảm của trẻ.

Trân trọng,<br>
**TìmTrẻ.vn**
</x-mail::message>
