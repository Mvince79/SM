@php
    // H-02 — Khối "Việc cần làm" 5 bước, CHỈ hiển thị cho chủ hồ sơ khi status = HANDED_OVER.
    // Mọi bước đều là hành động cụ thể, không có câu chung chung.
    $point = $case->handoverPoint;
@endphp

<div class="next-steps-card bg-white border border-slate-200/80 rounded-2xl sm:rounded-3xl shadow-premium overflow-hidden mb-3 sm:mb-6">
    <div class="bg-brand/5 border-b border-brand/10 px-4 sm:px-6 py-3 sm:py-4">
        <h3 class="text-brand font-extrabold text-sm sm:text-base tracking-tight flex items-center gap-2">
            <x-heroicon-o-clipboard-document-check class="w-5 h-5" />
            Việc cần làm để nhận lại con
        </h3>
        <p class="text-[11px] sm:text-xs text-slate-500 mt-1">
            Hồ sơ đã được bàn giao cho cơ quan chức năng. Dưới đây là các bước cụ thể bạn cần thực hiện.
        </p>
    </div>

    <ol class="p-4 sm:p-6 space-y-4 sm:space-y-5 counter-reset-step">
        {{-- Bước 1 — Địa chỉ + hotline điểm giữ trẻ --}}
        <li class="next-step flex gap-3 sm:gap-4">
            <span class="next-step-num">1</span>
            <div class="min-w-0 flex-1">
                <p class="font-bold text-slate-800 text-sm">Xem địa chỉ và gọi hotline điểm đang giữ bé</p>
                @if ($point)
                    <div class="mt-2 rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm">
                        <p class="font-semibold text-slate-800">{{ $point->name }}</p>
                        <p class="text-slate-600 mt-0.5">
                            {{ collect([$point->address, $point->province?->name])->filter()->implode(', ') ?: '—' }}
                        </p>
                        @if ($point->hotline_phone)
                            <a href="tel:{{ $point->hotline_phone }}"
                               class="mt-2 inline-flex items-center gap-1.5 rounded-lg bg-success px-3 py-1.5 text-white text-xs font-bold hover:opacity-90 transition-opacity">
                                <x-heroicon-o-phone class="w-4 h-4" /> Gọi {{ $point->hotline_phone }}
                            </a>
                        @endif
                    </div>
                @else
                    <p class="mt-1 text-xs text-slate-500">Thông tin điểm tiếp nhận chưa được cập nhật.</p>
                @endif
            </div>
        </li>

        {{-- Bước 2 — Giấy tờ --}}
        <li class="next-step flex gap-3 sm:gap-4">
            <span class="next-step-num">2</span>
            <div class="min-w-0 flex-1">
                <p class="font-bold text-slate-800 text-sm">Chuẩn bị giấy tờ trước khi đến</p>
                <ul class="mt-1.5 list-disc pl-5 text-xs sm:text-sm text-slate-600 space-y-0.5">
                    <li>CCCD/CMND của người đi nhận</li>
                    <li>Giấy khai sinh của trẻ (nếu có)</li>
                    <li>Ảnh của trẻ trước khi mất tích để đối chiếu</li>
                </ul>
            </div>
        </li>

        {{-- Bước 3 — Công an phường/xã --}}
        <li class="next-step flex gap-3 sm:gap-4">
            <span class="next-step-num">3</span>
            <div class="min-w-0 flex-1">
                <p class="font-bold text-slate-800 text-sm">Liên hệ công an phường/xã nơi bạn cư trú</p>
                <p class="mt-1 text-xs sm:text-sm text-slate-600">
                    Nếu bạn không cùng tỉnh với điểm tiếp nhận, hãy cung cấp mã hồ sơ để được phối hợp hỗ trợ.
                </p>
                <p class="mt-2 inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 font-mono text-xs font-bold text-slate-700">
                    Mã hồ sơ: {{ $case->case_code }}
                </p>
            </div>
        </li>

        {{-- Bước 4 — Gọi xác nhận --}}
        <li class="next-step flex gap-3 sm:gap-4">
            <span class="next-step-num">4</span>
            <div class="min-w-0 flex-1">
                <p class="font-bold text-slate-800 text-sm">Gọi hotline ở bước 1 xác nhận thời gian đến nhận</p>
                <p class="mt-1 text-xs sm:text-sm text-slate-600">
                    Hẹn trước giúp điểm tiếp nhận chuẩn bị hồ sơ và bé sẵn sàng bàn giao.
                </p>
            </div>
        </li>

        {{-- Bước 5 — Xác nhận đã nhận lại --}}
        <li class="next-step flex gap-3 sm:gap-4">
            <span class="next-step-num">5</span>
            <div class="min-w-0 flex-1">
                <p class="font-bold text-slate-800 text-sm">Sau khi đã nhận được bé, xác nhận lại tại đây</p>
                @if (auth()->check() && auth()->id() === $case->user_id)
                    <form method="POST" action="{{ route('missing.confirm-reunited', $case) }}" class="mt-2">
                        @csrf
                        <button type="submit"
                                onclick="return confirm('Bạn xác nhận đã nhận lại được con và muốn đóng hồ sơ này?')"
                                class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-white text-sm font-bold shadow-premium hover:bg-primary-dark transition-colors">
                            <x-heroicon-o-check-circle class="w-5 h-5" />
                            Xác nhận đã nhận lại con
                        </button>
                    </form>
                @else
                    <p class="mt-1 text-xs text-slate-500">Chỉ chủ hồ sơ mới có thể thực hiện bước này.</p>
                @endif
            </div>
        </li>
    </ol>
</div>
