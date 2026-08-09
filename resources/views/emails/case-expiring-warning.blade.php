<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Hồ sơ sắp hết hạn</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6;">
    <h1 style="font-size: 20px;">Hồ sơ tìm kiếm sắp hết hạn</h1>

    <p>Xin chào {{ $missingChild->user->full_name }},</p>

    <p>
        Hồ sơ <strong>{{ $missingChild->case_code }} — {{ $missingChild->full_name }}</strong>
        sẽ hết hạn vào ngày <strong>{{ $missingChild->expires_at->format('d/m/Y') }}</strong>.
    </p>

    <p>Nếu việc tìm kiếm vẫn còn hiệu lực, vui lòng xác nhận để gia hạn hồ sơ thêm 2 năm.</p>

    <p style="margin: 24px 0;">
        <a href="{{ $renewalUrl }}" style="background: #2563eb; color: #ffffff; padding: 12px 18px; border-radius: 8px; text-decoration: none;">
            Xác nhận hồ sơ vẫn còn hiệu lực
        </a>
    </p>

    <p>Nếu không xác nhận, hồ sơ sẽ được chuyển sang trạng thái lưu trữ; dữ liệu không bị xóa.</p>
</body>
</html>
