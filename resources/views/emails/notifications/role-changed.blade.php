<x-mail::message>
# Quyền truy cập tài khoản đã thay đổi

Xin chào {{ $recipientName }},

## {{ $notificationTitle }}

{{ $notificationBody }}

Bạn nên đăng xuất khỏi phiên hiện tại, sau đó đăng nhập lại để hệ thống áp dụng đúng quyền của vai trò mới.

@if ($actionUrl)
<x-mail::button :url="$actionUrl">
Đăng nhập lại
</x-mail::button>
@endif

Nếu bạn không biết hoặc không đồng ý với thay đổi này, vui lòng liên hệ quản trị viên của TìmTrẻ.vn.

Trân trọng,<br>
**TìmTrẻ.vn**
</x-mail::message>
