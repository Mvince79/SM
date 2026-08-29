<x-mail::message>
# Hồ sơ chưa được phê duyệt

Xin chào {{ $recipientName }},

{{ $notificationBody }}

@if ($actionUrl)
<x-mail::button :url="$actionUrl">
Xem và chỉnh sửa hồ sơ
</x-mail::button>
@endif

Nếu bạn cần hỗ trợ để hoàn thiện hồ sơ, hãy liên hệ Trung tâm hỗ trợ — chúng tôi sẽ giúp bạn.
Trong trường hợp khẩn cấp, hãy gọi Tổng đài Quốc gia Bảo vệ Trẻ em **111** (miễn phí, 24/7).

Trân trọng,<br>
**TìmTrẻ.vn**
</x-mail::message>
