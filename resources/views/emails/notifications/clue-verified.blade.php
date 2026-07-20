<x-mail::message>
# Manh mối đã được xác minh

Xin chào {{ $recipientName }},

**Vai trò nhận thông báo:** {{ $roleLabel }}

{{ $roleIntroduction }}

## {{ $notificationTitle }}

{{ $notificationBody }}

@if ($actionUrl)
<x-mail::button :url="$actionUrl">
Xem manh mối
</x-mail::button>
@endif

Vui lòng tiếp tục bảo mật danh tính người cung cấp tin và dữ liệu riêng tư của trẻ.

Trân trọng,<br>
**TìmTrẻ.vn**
</x-mail::message>
