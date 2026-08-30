@props([
    'case',
])

@php
    // H-05 — Khối "Xác nhận bàn giao", CHỈ hiển thị cho chủ hồ sơ khi status = FOUND
    // (kết quả so khớp đã được admin xác nhận, đang chờ gia đình xác nhận bé đã
    // được bàn giao). Công an sẽ chủ động liên hệ gia đình — hệ thống chỉ lan tỏa
    // thông tin, không thay thế cơ quan chức năng.
    $point = $case->handoverPoint;
    $ownerId = auth()->id();
    $isOwner = $ownerId === $case->user_id;
@endphp

@if ($isOwner && $case->status === \App\Models\MissingChild::STATUS_FOUND)
    <div class="family-handover-card bg-white border border-slate-200/80 rounded-2xl sm:rounded-3xl shadow-premium overflow-hidden mb-3 sm:mb-6 mt-6">
        <div class="bg-brand/5 border-b border-brand/10 px-4 sm:px-6 py-3 sm:py-4">
            <h3 class="text-brand font-extrabold text-sm sm:text-base tracking-tight flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9 12.75 3 3 6-7.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                Kết quả đã xác nhận khớp
            </h3>
            <p class="text-[11px] sm:text-xs text-slate-500 mt-1">
                Cơ quan chức năng sẽ liên hệ bạn để bàn giao bé. Khi bé đã được bàn giao,
                vui lòng xác nhận bên dưới để chúng tôi cập nhật tình trạng.
            </p>
        </div>

        <div class="p-4 sm:p-6 space-y-4">
            <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-brand shrink-0 mt-0.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                </svg>
                <p class="text-slate-600">
                    Hệ thống chỉ hỗ trợ lan tỏa thông tin và kết nối — việc bàn giao do
                    công an / điểm tiếp nhận trực tiếp thực hiện. Bạn không cần tự đến
                    đón bé nếu chưa nhận được liên hệ chính thức.
                </p>
            </div>

            <div>
                <p class="font-bold text-slate-800 text-sm">Khi bé đã được bàn giao, xác nhận tại đây</p>
                <form method="POST" action="{{ route('missing.confirm-handover', $case) }}" class="mt-2">
                    @csrf
                    <button type="submit"
                            onclick="return confirm('Bạn xác nhận cơ quan chức năng đã liên hệ và bàn giao bé cho gia đình?')"
                            class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-white text-sm font-bold shadow-premium hover:bg-primary-dark transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m9 12.75 3 3 6-7.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Xác nhận bé đã được bàn giao
                    </button>
                </form>
            </div>
        </div>
    </div>
@endif
