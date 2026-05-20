@extends('layouts.app')

@section('title', 'Hồ sơ cá nhân')

@section('content')
<main class="min-h-screen pt-24 pb-16 bg-slate-50 px-4">
    <div class="max-w-5xl w-full bg-white rounded-3xl shadow-xl shadow-slate-100 p-8 border border-slate-100 mx-auto">
        <div class="flex items-center justify-between mb-6">
            <div class="space-y-1 text-left">
                <h1 class="text-2xl font-black text-slate-800">Hồ sơ cá nhân</h1>
                <p class="text-slate-500 font-medium">Cập nhật thông tin liên hệ và cấu hình nhận thông báo.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-xl p-3">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-rose-50 border border-rose-100 text-rose-700 rounded-xl p-3">{{ session('error') }}</div>
        @endif

        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="w-full">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50 rounded-xl p-6">
                <div class="flex flex-col items-center justify-center gap-4 p-4">
                    <img src="{{ $user->avatar_url }}" alt="avatar" class="w-32 h-32 rounded-full object-cover border" />
                    <div class="text-center">
                        <h3 class="font-bold text-slate-800">{{ $user->full_name }}</h3>
                        <p class="text-sm text-slate-500">{{ $user->email }}</p>
                    </div>
                    <div class="w-full mt-2">
                        <label class="block text-sm font-medium text-slate-700">Ảnh đại diện</label>
                        <div class="mt-2">
                            <label for="avatarInput" class="group cursor-pointer block w-full rounded-lg border-2 border-dashed border-slate-200 bg-white hover:border-primary/70 focus-within:ring-2 focus-within:ring-primary/30 p-4 flex flex-col items-center justify-center text-center">
                                <div id="avatarPreviewWrapper" class="flex flex-col items-center gap-2">
                                    <img id="avatarPreview" src="{{ $user->avatar_url }}" alt="avatar" class="w-24 h-24 rounded-full object-cover border mx-auto" />
                                    <div class="text-sm text-slate-600 group-hover:text-primary">Nhấn để thay đổi ảnh (jpg, png, webp)</div>
                                </div>
                                <input id="avatarInput" type="file" name="avatar" accept="image/*" class="sr-only" />
                            </label>
                            @error('avatar') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="p-4">
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Họ và tên</label>
                            <input name="full_name" value="{{ old('full_name', $user->full_name) }}" class="w-full mt-1 px-3 h-12 border rounded-lg" />
                            @error('full_name') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Số điện thoại</label>
                            <input name="phone" value="{{ old('phone', $user->phone) }}" class="w-full mt-1 px-3 h-12 border rounded-lg" />
                            @error('phone') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Tỉnh/Thành</label>
                            <select name="province_id" class="w-full mt-1 px-3 h-12 border rounded-lg">
                                <option value="">-- Chọn --</option>
                                @foreach($provinces as $p)
                                    <option value="{{ $p->id }}" {{ (old('province_id', $user->province_id) == $p->id) ? 'selected' : '' }}>{{ $p->name }}</option>
                                @endforeach
                            </select>
                            @error('province_id') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Bán kính nhận thông báo (km)</label>
                            <input name="notify_radius_km" value="{{ old('notify_radius_km', $user->notify_radius_km) }}" type="number" min="0" class="w-full mt-1 px-3 h-12 border rounded-lg" />
                            @error('notify_radius_km') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <a href="{{ route('home') }}" class="text-sm text-slate-600 px-4 py-2 rounded-lg border">Hủy</a>
                        <button type="submit" class="bg-primary text-white font-bold py-2 px-6 rounded-2xl hover:bg-primary-dark hover:shadow-lg transition-all">Lưu thay đổi</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('avatarInput');
    const preview = document.getElementById('avatarPreview');
    const wrapper = document.getElementById('avatarPreviewWrapper');

    if (input) {
        input.addEventListener('change', function (e) {
            const file = e.target.files && e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function (ev) {
                preview.src = ev.target.result;
                // add highlight
                wrapper.classList.add('ring-2', 'ring-primary/40');
            };
            reader.readAsDataURL(file);
        });
    }

    // Close user menu when clicking outside
    document.addEventListener('click', function (e) {
        const menu = document.getElementById('user-menu');
        const toggle = e.target.closest('[onclick]');
        if (!menu) return;
        if (toggle && toggle.contains(e.target)) return;
        if (!menu.contains(e.target)) menu.classList.add('hidden');
    });
});
</script>
@endpush
