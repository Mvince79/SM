@props([
    'case',
])

@php
    // H-05 — Khối "Xác nhận bàn giao", CHỈ hiển thị cho chủ hồ sơ khi status = FOUND
    // (kết quả so khớp đã được admin xác nhận, đang chờ gia đình xác nhận bé đã
    // được bàn giao) HOẶC status = HANDED_OVER (gia đình đã bấm xác nhận bàn giao,
    // nay có thêm tuỳ chọn "Không phải con tôi" để phủ nhận nếu phát hiện nhầm).
    // Công an sẽ chủ động liên hệ gia đình — hệ thống chỉ lan tỏa
    // thông tin, không thay thế cơ quan chức năng.
    $point = $case->handoverPoint;
    $ownerId = auth()->id();
    $isOwner = $ownerId === $case->user_id;
    $isFound = $case->status === \App\Models\MissingChild::STATUS_FOUND;
    $isHandedOver = $case->status === \App\Models\MissingChild::STATUS_HANDED_OVER;
@endphp

@if ($isOwner && ($isFound || $isHandedOver))
    <div class="family-handover-card bg-white border border-slate-200/80 rounded-2xl sm:rounded-3xl shadow-premium overflow-hidden mb-3 sm:mb-6 mt-6">
        <div class="bg-brand/5 border-b border-brand/10 px-4 sm:px-6 py-3 sm:py-4">
            <h3 class="text-brand font-extrabold text-sm sm:text-base tracking-tight flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9 12.75 3 3 6-7.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                {{ $isHandedOver ? 'Đã xác nhận bàn giao — vui lòng kiểm tra lại' : 'Kết quả đã xác nhận khớp' }}
            </h3>
            <p class="text-[11px] sm:text-xs text-slate-500 mt-1">
                @if ($isFound)
                    Cơ quan chức năng sẽ liên hệ bạn để bàn giao bé. Khi bé đã được bàn giao,
                    vui lòng xác nhận bên dưới để chúng tôi cập nhật tình trạng.
                @else
                    Bạn đã xác nhận cơ quan chức năng đã bàn giao bé. Nếu phát hiện đây là nhầm lẫn
                    (không phải con bạn), hãy chọn "Không phải con tôi" để hệ thống gỡ kết quả đối chiếu.
                @endif
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

            @if ($isFound || $isHandedOver)
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
            @endif

            @if ($isHandedOver)
                <div class="border-t border-slate-100 pt-4">
                    <p class="font-bold text-slate-800 text-sm">Không phải con bạn?</p>
                    <form method="POST" action="{{ route('missing.deny-handover', $case) }}" class="mt-2">
                        @csrf
                        <button type="submit"
                                onclick="return confirm('Bạn xác nhận hồ sơ này KHÔNG PHẢI con bạn?\n\nKết quả đối chiếu sẽ bị gỡ, hồ sơ quay lại trạng thái tìm kiếm công khai và thông tin sẽ tiếp tục được lan tỏa. Hành động này không thể hoàn tác bằng nút.')"
                                class="inline-flex items-center gap-2 rounded-xl bg-rose-500 px-4 py-2.5 text-white text-sm font-bold shadow-premium hover:bg-rose-600 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                            Không phải con tôi
                        </button>
                    </form>
                    <p class="mt-2 text-[11px] text-slate-400">
                        Chỉ chọn khi bạn chắc chắn đây là nhầm lẫn. Nếu bàn giao thật sự đang diễn ra,
                        vui lòng chờ liên hệ chính thức từ cơ quan chức năng.
                    </p>
                </div>
            @endif
        </div>
    </div>
@endif
