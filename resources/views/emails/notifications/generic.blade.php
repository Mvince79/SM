<x-mail::message>
# {{ $notificationTitle }}

Xin chào {{ $recipientName }},

{{ $notificationBody }}

@if ($actionUrl)
<x-mail::button :url="$actionUrl">
Xem chi tiết
</x-mail::button>
@endif

Trân trọng,<br>
**TìmTrẻ.vn**
</x-mail::message>
