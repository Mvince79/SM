@if (session('success') || request()->query('created'))
    <div class="max-w-3xl mx-auto px-4 pt-6">
        <div class="bg-success-light border border-success/30 text-success font-bold text-sm px-5 py-4 rounded-2xl">
            {{ session('success') ?? '🎉 Đăng tin thành công! Hồ sơ đang chờ duyệt và sẽ được hiển thị sau khi được phê duyệt.' }}
        </div>
    </div>
@endif