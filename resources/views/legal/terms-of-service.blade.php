@extends('layouts.app')
@section('title', 'Điều khoản Sử dụng')

@section('content')
<div class="bg-white border-b border-slate-100 py-10 px-4">
    <div class="max-w-3xl mx-auto flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center shrink-0">
            <x-heroicon-o-document-text class="w-6 h-6 text-primary" />
        </div>
        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Điều khoản Sử dụng</h1>
            <p class="text-slate-400 text-xs font-bold mt-1">Cập nhật lần cuối: {{ \Carbon\Carbon::parse('2026-07-09')->translatedFormat('d/m/Y') }} — Phiên bản {{ config('legal.versions.TOS') }}</p>
        </div>
    </div>
</div>

<main class="max-w-3xl mx-auto px-4 py-10">
    <div class="bg-white border border-slate-200/85 rounded-3xl p-6 sm:p-10 shadow-premium prose prose-slate prose-sm sm:prose-base max-w-none prose-headings:font-black prose-headings:text-slate-900 prose-a:text-primary">

        <p>Chào mừng bạn đến với <strong>TìmTrẻ.vn</strong>. Khi tạo tài khoản và sử dụng nền tảng, bạn đồng ý tuân thủ các điều khoản dưới đây. Vui lòng đọc kỹ trước khi đăng tin hoặc cung cấp bất kỳ thông tin nào.</p>

        <h2>1. Mục đích của nền tảng</h2>
        <p>TìmTrẻ.vn là nền tảng phi lợi nhuận hỗ trợ cộng đồng đăng tin tìm kiếm trẻ em thất lạc, kết nối thông tin và hỗ trợ cơ quan chức năng trong quá trình xác minh, tìm kiếm. Nền tảng không thay thế cho việc trình báo với cơ quan công an — mọi trường hợp mất tích cần được trình báo trực tiếp với cơ quan công an địa phương hoặc gọi 113 trước tiên.</p>

        <h2>2. Trách nhiệm của người dùng</h2>
        <ul>
            <li>Cung cấp thông tin trung thực, chính xác khi đăng tin tìm trẻ hoặc báo tin tìm thấy.</li>
            <li>Không đăng tải thông tin giả mạo, sai sự thật, hoặc lợi dụng nền tảng cho mục đích trục lợi, lừa đảo.</li>
            <li>Chịu trách nhiệm trước pháp luật Việt Nam đối với nội dung mình cung cấp, bao gồm hình ảnh, mô tả đặc điểm và thông tin liên hệ.</li>
            <li>Không sử dụng nền tảng để quấy rối, đe dọa, hoặc xâm phạm quyền riêng tư của người khác.</li>
        </ul>

        <h2>3. Xác thực danh tính (KYC)</h2>
        <p>Để đăng tin tìm trẻ thất lạc hoặc báo tin tìm thấy, người dùng cần hoàn tất xác thực CCCD/CMND (KYC). Việc này nhằm hạn chế tin giả và tăng độ tin cậy của thông tin trên hệ thống. Ảnh giấy tờ và ảnh selfie chỉ được sử dụng cho mục đích xác thực, theo Chính sách Quyền riêng tư và Chính sách Bảo vệ Dữ liệu Trẻ em của chúng tôi.</p>

        <h2>4. Kiểm duyệt nội dung</h2>
        <p>Đội ngũ quản trị và tình nguyện viên có quyền kiểm duyệt, tạm ẩn, hoặc từ chối các tin đăng không đáp ứng tiêu chuẩn của nền tảng, bao gồm nhưng không giới hạn ở: thông tin không đầy đủ, nghi vấn giả mạo, hoặc vi phạm điều khoản này.</p>

        <h2>5. Giới hạn trách nhiệm</h2>
        <p>TìmTrẻ.vn là kênh hỗ trợ kết nối thông tin cộng đồng, không đảm bảo kết quả tìm kiếm và không chịu trách nhiệm về tính chính xác của thông tin do người dùng khác cung cấp. Mọi manh mối cần được xác minh qua cơ quan chức năng trước khi hành động.</p>

        <h2>6. Thay đổi điều khoản</h2>
        <p>Chúng tôi có thể cập nhật điều khoản này theo thời gian. Phiên bản mới sẽ được thông báo và có hiệu lực kể từ ngày đăng tải. Việc tiếp tục sử dụng nền tảng sau khi thay đổi đồng nghĩa với việc bạn chấp nhận điều khoản mới.</p>

        <h2>7. Liên hệ</h2>
        <p>Mọi thắc mắc về điều khoản, vui lòng liên hệ <a href="mailto:hotro@timtre.vn">hotro@timtre.vn</a> hoặc hotline 1800 1234 (miễn phí).</p>
    </div>
</main>
@endsection