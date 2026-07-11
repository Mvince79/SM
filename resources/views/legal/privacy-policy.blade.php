@extends('layouts.app')
@section('title', 'Chính sách Quyền riêng tư')

@section('content')
<div class="bg-white border-b border-slate-100 py-10 px-4">
    <div class="max-w-3xl mx-auto flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center shrink-0">
            <x-heroicon-o-shield-check class="w-6 h-6 text-primary" />
        </div>
        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Chính sách Quyền riêng tư</h1>
            <p class="text-slate-400 text-xs font-bold mt-1">Cập nhật lần cuối: {{ \Carbon\Carbon::parse('2026-07-09')->translatedFormat('d/m/Y') }} — Phiên bản {{ config('legal.versions.PRIVACY_POLICY') }}</p>
        </div>
    </div>
</div>

<main class="max-w-3xl mx-auto px-4 py-10">
    <div class="bg-white border border-slate-200/85 rounded-3xl p-6 sm:p-10 shadow-premium prose prose-slate prose-sm sm:prose-base max-w-none prose-headings:font-black prose-headings:text-slate-900 prose-a:text-primary">

        <p>Chính sách này giải thích cách <strong>TìmTrẻ.vn</strong> thu thập, sử dụng, lưu trữ và bảo vệ thông tin cá nhân của bạn khi sử dụng nền tảng.</p>

        <h2>1. Thông tin chúng tôi thu thập</h2>
        <ul>
            <li><strong>Thông tin tài khoản:</strong> họ tên, số điện thoại, email.</li>
            <li><strong>Thông tin xác thực (KYC):</strong> số CCCD/CMND, ảnh mặt trước/sau CCCD, ảnh selfie.</li>
            <li><strong>Thông tin đăng tin:</strong> thông tin trẻ mất tích/tìm thấy, ảnh, mô tả đặc điểm, vị trí, thông tin liên hệ người báo tin.</li>
            <li><strong>Dữ liệu kỹ thuật:</strong> địa chỉ IP, thời điểm truy cập, phục vụ mục đích bảo mật và ghi nhận bằng chứng đồng ý.</li>
        </ul>

        <h2>2. Mục đích sử dụng</h2>
        <ul>
            <li>Xác thực danh tính người dùng nhằm hạn chế tin giả (KYC).</li>
            <li>Hiển thị và đối chiếu thông tin tìm kiếm trẻ em thất lạc trên nền tảng.</li>
            <li>Phân tích đối chiếu khuôn mặt AI để hỗ trợ tìm kiếm, chỉ áp dụng cho mục đích tìm trẻ thất lạc.</li>
            <li>Liên hệ, hỗ trợ người dùng qua hotline hoặc email khi cần thiết.</li>
        </ul>

        <h2>3. Mã hoá và bảo mật</h2>
        <p>Số CCCD/CMND được mã hoá (AES-256) trước khi lưu trữ, không lưu ở dạng văn bản thuần. Ảnh giấy tờ và ảnh selfie được lưu trữ trong khu vực hạn chế truy cập, chỉ đội ngũ kiểm duyệt được phân quyền mới có thể xem để xử lý hồ sơ KYC.</p>

        <h2>4. Chia sẻ thông tin</h2>
        <p>Chúng tôi <strong>không bán</strong> thông tin cá nhân cho bên thứ ba. Thông tin chỉ được chia sẻ trong các trường hợp:</p>
        <ul>
            <li>Theo yêu cầu của cơ quan công an, cơ quan nhà nước có thẩm quyền phục vụ công tác tìm kiếm, điều tra.</li>
            <li>Với đối tác cung cấp dịch vụ xác thực (OCR/eKYC), chỉ trong phạm vi cần thiết để xử lý hồ sơ xác thực.</li>
            <li>Thông tin công khai trong tin đăng (họ tên trẻ, đặc điểm, khu vực mất tích...) hiển thị cho cộng đồng nhằm phục vụ mục đích tìm kiếm — không bao gồm số CCCD hay ảnh giấy tờ tuỳ thân.</li>
        </ul>

        <h2>5. Thời gian lưu trữ</h2>
        <p>Thông tin được lưu trữ trong suốt thời gian tài khoản còn hoạt động và theo quy định pháp luật hiện hành. Bạn có thể yêu cầu xoá tài khoản và dữ liệu liên quan bằng cách liên hệ <a href="mailto:hotro@timtre.vn">hotro@timtre.vn</a>, trừ dữ liệu cần lưu giữ theo nghĩa vụ pháp lý hoặc đang phục vụ một vụ việc tìm kiếm chưa kết thúc.</p>

        <h2>6. Quyền của bạn</h2>
        <p>Bạn có quyền yêu cầu truy cập, chỉnh sửa, hoặc xoá thông tin cá nhân của mình, trừ trường hợp pháp luật yêu cầu lưu giữ. Mọi yêu cầu vui lòng gửi về <a href="mailto:hotro@timtre.vn">hotro@timtre.vn</a>.</p>

        <h2>7. Liên hệ</h2>
        <p>Nếu có thắc mắc về chính sách quyền riêng tư, vui lòng liên hệ hotline 1800 1234 (miễn phí) hoặc email <a href="mailto:hotro@timtre.vn">hotro@timtre.vn</a>.</p>
    </div>
</main>
@endsection