@extends('layouts.app')
@section('title', 'Chính sách Bảo vệ Dữ liệu Trẻ em')

@section('content')
<div class="bg-white border-b border-slate-100 py-10 px-4">
    <div class="max-w-3xl mx-auto flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center shrink-0">
            <x-heroicon-o-heart class="w-6 h-6 text-primary" />
        </div>
        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Chính sách Bảo vệ Dữ liệu Trẻ em</h1>
            <p class="text-slate-400 text-xs font-bold mt-1">Cập nhật lần cuối: {{ \Carbon\Carbon::parse('2026-07-09')->translatedFormat('d/m/Y') }} — Phiên bản {{ config('legal.versions.CHILD_DATA_POLICY') }}</p>
        </div>
    </div>
</div>

<main class="max-w-3xl mx-auto px-4 py-10">
    <div class="bg-white border border-slate-200/85 rounded-3xl p-6 sm:p-10 shadow-premium prose prose-slate prose-sm sm:prose-base max-w-none prose-headings:font-black prose-headings:text-slate-900 prose-a:text-primary">

        <p>Trẻ em là đối tượng cần được bảo vệ đặc biệt. Chính sách này quy định cách <strong>TìmTrẻ.vn</strong> thu thập, xử lý và bảo vệ thông tin, hình ảnh của trẻ em xuất hiện trong các tin đăng tìm kiếm và báo tin tìm thấy trên nền tảng.</p>

        <h2>1. Nguyên tắc vì lợi ích tốt nhất của trẻ</h2>
        <p>Mọi thông tin, hình ảnh liên quan đến trẻ em chỉ được thu thập và hiển thị nhằm mục đích duy nhất: hỗ trợ tìm kiếm, đoàn tụ trẻ với gia đình. Chúng tôi không sử dụng hình ảnh, thông tin của trẻ cho mục đích quảng cáo, thương mại, hoặc bất kỳ mục đích nào khác ngoài phạm vi này.</p>

        <h2>2. Thông tin trẻ em được thu thập</h2>
        <ul>
            <li>Họ tên, ngày sinh hoặc độ tuổi ước tính, giới tính.</li>
            <li>Ảnh chụp, đặc điểm nhận dạng (chiều cao, cân nặng, đặc điểm cơ thể, trang phục lúc mất tích).</li>
            <li>Thời gian, địa điểm mất tích hoặc được tìm thấy.</li>
            <li>Dữ liệu vector khuôn mặt (face embedding) phục vụ đối chiếu AI giữa hồ sơ tìm trẻ và hồ sơ trẻ được tìm thấy — không dùng cho mục đích nhận diện nào khác.</li>
        </ul>

        <h2>3. Ai được xem thông tin</h2>
        <ul>
            <li>Thông tin cơ bản và ảnh trẻ trong tin đăng đã duyệt: hiển thị công khai cho cộng đồng nhằm phục vụ tìm kiếm.</li>
            <li>Dữ liệu vector khuôn mặt và kết quả đối chiếu AI: chỉ đội ngũ kiểm duyệt/quản trị được phân quyền mới truy cập được.</li>
            <li>Thông tin liên hệ chi tiết của gia đình: chỉ hiển thị giới hạn, tránh lộ thông tin nhạy cảm có thể bị lợi dụng.</li>
        </ul>

        <h2>4. Kiểm duyệt trước khi công khai</h2>
        <p>Mọi tin đăng có thông tin, hình ảnh trẻ em đều phải qua bước kiểm duyệt trước khi hiển thị công khai, nhằm ngăn chặn thông tin giả mạo, hình ảnh không phù hợp, hoặc nội dung có thể gây hại cho trẻ.</p>

        <h2>5. Gỡ bỏ thông tin sau khi đoàn tụ</h2>
        <p>Khi một trường hợp đã được xác nhận đoàn tụ thành công, thông tin và hình ảnh công khai của trẻ sẽ được ẩn khỏi kết quả tìm kiếm công khai trong thời gian sớm nhất, chỉ lưu trữ nội bộ phục vụ thống kê và đối chiếu khi cần.</p>

        <h2>6. Không dung thứ hành vi lạm dụng</h2>
        <p>Nghiêm cấm mọi hành vi sử dụng nền tảng để tiếp cận, liên hệ, hoặc thu thập thông tin trẻ em nhằm mục đích xâm hại, bóc lột, hoặc gây hại dưới bất kỳ hình thức nào. Tài khoản vi phạm sẽ bị khóa vĩnh viễn và thông tin có thể được chuyển giao cho cơ quan chức năng.</p>

        <h2>7. Đồng ý xử lý dữ liệu</h2>
        <p>Khi đăng tin tìm trẻ hoặc báo tin tìm thấy, người dùng xác nhận đã đọc và đồng ý với chính sách này, đồng thời cam kết chỉ cung cấp thông tin, hình ảnh trẻ em vì mục đích tìm kiếm chính đáng.</p>

        <h2>8. Liên hệ</h2>
        <p>Nếu phát hiện nội dung nghi ngờ vi phạm chính sách bảo vệ trẻ em trên nền tảng, vui lòng báo ngay qua <a href="mailto:hotro@timtre.vn">hotro@timtre.vn</a>, hotline 1800 1234 (miễn phí), hoặc liên hệ Công an 113 nếu có dấu hiệu nguy hiểm khẩn cấp.</p>
    </div>
</main>
@endsection