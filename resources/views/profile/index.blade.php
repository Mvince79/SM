@extends('layouts.app')
@section('title', $user->full_name . ' - Hồ sơ cá nhân')

@section('content')

<main class="max-w-5xl mx-auto px-4 py-10 space-y-8">
    {{-- ── Cover Header ── --}}
    <div class="bg-white border border-slate-200/80 rounded-3xl overflow-hidden shadow-premium relative"
         x-data="{ editOpen: false }">

        {{-- Cover gradient --}}
        <div class="h-32 sm:h-44 bg-gradient-to-r from-brand/80 to-indigo-600 relative">
            <div class="absolute inset-0 bg-black/10"></div>
        </div>

        {{-- Profile summary --}}
        <div class="px-6 sm:px-8 pb-6 relative">
            <div class="flex flex-col sm:flex-row items-center sm:items-end justify-between gap-4 -mt-12 sm:-mt-16 border-b border-slate-100 pb-6">

                <div class="flex flex-col sm:flex-row items-center sm:items-end gap-4 text-center sm:text-left">
                    {{-- Avatar --}}
                    <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-full border-4 border-white overflow-hidden bg-slate-100 shadow-md flex-shrink-0 relative z-20">
                        <img src="{{ $user->avatar_url }}"
                             alt="{{ $user->full_name }}"
                             class="w-full h-full object-cover"/>
                    </div>

                    <div class="space-y-1.5">
                        <div class="flex items-center gap-2 justify-center sm:justify-start flex-wrap">
                            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                                {{ $user->full_name }}
                            </h2>
                            @if($user->isVerified())
                                <span class="bg-success-light text-success border border-success/35 text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full">
                                    Đã xác minh ✓
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-400 font-bold uppercase tracking-wider">
                            Thành viên từ: <span class="text-slate-600">{{ $user->created_at->format('m/Y') }}</span>
                        </p>
                    </div>
                </div>

                {{-- Nút chỉnh sửa — mở modal Alpine --}}
                <button @click="editOpen = true"
                        class="bg-slate-100 text-slate-700 text-xs font-extrabold px-5 py-3 rounded-2xl hover:bg-slate-200 transition-colors border border-slate-200 shadow-sm uppercase tracking-wider">
                    ⚙️ Chỉnh sửa thông tin
                </button>
            </div>

            {{-- Stats --}}
            <div class="grid grid-cols-3 gap-4 pt-6 max-w-lg text-center sm:text-left">
                <div>
                    <div class="text-2xl sm:text-3xl font-black text-primary tracking-tight">
                        {{ $user->missingChildren->where('status', 'APPROVED')->count() }}
                    </div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Tin đang đăng</div>
                </div>
                <div>
                    <div class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                        {{ $user->missingChildren->where('status', 'FOUND')->count() }}
                    </div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Ca đã đoàn tụ</div>
                </div>
                <div>
                    <div class="text-2xl sm:text-3xl font-black text-brand tracking-tight">
                        {{ $user->clues->count() }}
                    </div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Manh mối nhận được</div>
                </div>
            </div>
        </div>

        {{-- ── Edit Modal (Alpine x-show, không reload trang) ── --}}
        <div x-show="editOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4"
             style="display: none;">
            <div @click.away="editOpen = false"
                 class="bg-white border border-slate-200 rounded-3xl max-w-md w-full p-6 space-y-5 shadow-2xl">

                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-black text-slate-900 text-base">Chỉnh sửa thông tin cá nhân</h3>
                    <button @click="editOpen = false"
                            class="text-slate-400 hover:text-primary transition-colors text-xl font-black px-1">×</button>
                </div>

                {{-- Livewire form nằm trong modal --}}
                @livewire('profile.edit-profile-form', [
                    'user'      => $user,
                    'provinces' => $provinces
                ])

            </div>
        </div>

    </div>

    {{-- ── Tab System ── --}}
    <div class="space-y-6" x-data="{ tab: 'posts' }">

        {{-- Tab buttons --}}
        <div class="border-b border-slate-200 flex gap-6 text-sm font-bold">
            <button @click="tab = 'posts'"
                    :class="tab === 'posts' ? 'py-3 text-primary border-b-2 border-primary relative z-10' : 'py-3 text-slate-400 hover:text-slate-700 transition-colors'">
                Tin của tôi ({{ $user->missingChildren->count() }})
            </button>
            <button @click="tab = 'clues'"
                    :class="tab === 'clues' ? 'py-3 text-primary border-b-2 border-primary relative z-10' : 'py-3 text-slate-400 hover:text-slate-700 transition-colors'">
                Manh mối đã gửi ({{ $user->clues->count() }})
            </button>
            <button @click="tab = 'settings'"
                    :class="tab === 'settings' ? 'py-3 text-primary border-b-2 border-primary relative z-10' : 'py-3 text-slate-400 hover:text-slate-700 transition-colors'">
                Cài đặt tài khoản
            </button>
        </div>

        {{-- Tab: Tin của tôi (T-20 / PB-PAGE-09) — Livewire component --}}
        <div x-show="tab === 'posts'" x-transition>
            @livewire('profile.my-posts')
        </div>

        {{-- Tab: Manh mối --}}
        <div x-show="tab === 'clues'" x-transition>
            @if($user->clues->isEmpty())
                <div class="text-center py-14 space-y-4">
                    <div class="text-4xl"><x-heroicon-o-magnifying-glass class="w-10 h-10 mx-auto text-slate-300" /></div>
                    <h3 class="font-extrabold text-slate-700 text-base">Bạn chưa gửi manh mối nào</h3>
                    <p class="text-xs text-slate-400 max-w-xs mx-auto font-semibold">
                        Các manh mối bạn đóng góp sẽ hiển thị tại đây để bạn tiện theo dõi trạng thái kiểm chứng.
                    </p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($user->clues()->latest()->get() as $clue)
                        <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-premium">
                            <p class="text-sm font-semibold text-slate-700">{{ $clue->content }}</p>
                            <p class="text-[10px] text-slate-400 font-bold mt-2">{{ $clue->created_at->diffForHumans() }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Tab: Cài đặt tài khoản --}}
        <div x-show="tab === 'settings'" x-transition
             class="bg-white border border-slate-200/80 rounded-3xl p-6 sm:p-8 space-y-6 shadow-premium">
            <h3 class="font-extrabold text-slate-800 text-sm border-b border-slate-100 pb-2">
                Cấu hình bảo mật thông tin
            </h3>
            <div class="space-y-4 text-xs font-bold">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" checked class="accent-primary w-4 h-4"/>
                    <div>
                        <div class="text-slate-800">Nhận thông báo khi có manh mối mới</div>
                        <div class="text-[10px] text-slate-400 font-normal mt-0.5">
                            Hệ thống gửi tin nhắn khi có người báo tọa độ bé
                        </div>
                    </div>
                </label>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" checked class="accent-primary w-4 h-4"/>
                    <div>
                        <div class="text-slate-800">Ẩn thông tin số điện thoại cá nhân</div>
                        <div class="text-[10px] text-slate-400 font-normal mt-0.5">
                            Bảo vệ bạn khỏi các cuộc gọi spam hoặc tống tiền giả mạo
                        </div>
                    </div>
                </label>
            </div>
            {{-- Tỉnh nhận thông báo --}}
            <div class="pt-4 border-t border-slate-100 space-y-1.5">
                <label class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">
                    Tỉnh/Thành nhận thông báo hồ sơ mới
                </label>
                <p class="text-[10px] text-slate-400">
                    Hiện tại: <strong class="text-slate-700">{{ $user->province?->name ?? 'Chưa chọn' }}</strong>
                    — để thay đổi, dùng nút "Chỉnh sửa thông tin" ở trên.
                </p>
            </div>
        </div>

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