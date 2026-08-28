@props([
    'case',
])

@php
    $ownerId = auth()->id();
    $isOwner = $ownerId === $case->user_id;
    $status = $case->status;
    $isHandedOver = $status === \App\Models\MissingChild::STATUS_HANDED_OVER;
    // Hiển thị khối "Việc cần làm" + nút xác nhận cho CHỦ hồ sơ khi hồ sơ đang
    // active (chưa đóng). Mục tiêu: phụ huynh có thể tự "Xác nhận đã nhận lại con"
    // bất kỳ lúc nào — không phụ thuộc admin (luồng chính, theo thiết kế Nhóm H).
    $isActive = in_array($status, [
        \App\Models\MissingChild::STATUS_APPROVED,
        \App\Models\MissingChild::STATUS_FOUND,
        \App\Models\MissingChild::STATUS_HANDED_OVER,
    ], true);
    $handoverPoint = $case->handoverPoint;
@endphp

@if ($isOwner && $isActive)
    <div class="bg-white border border-slate-200/80 rounded-2xl sm:rounded-3xl shadow-premium overflow-hidden mt-6">
        <div class="px-4 sm:px-6 py-4 border-b border-slate-100 bg-success/5">
            <h3 class="text-xs font-black text-success uppercase tracking-wider flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                Việc cần làm
            </h3>
            <p class="text-[11px] text-slate-500 font-semibold mt-1">
                @if ($isHandedOver)
                    Trẻ đã được bàn giao cho cơ quan chức năng. Vui lòng thực hiện các bước sau để nhận lại con:
                @else
                    Nếu bạn đã đưa bé về nhà an toàn, hãy xác nhận để hệ thống cập nhật và đóng hồ sơ:
                @endif
            </p>
        </div>

        <div class="p-4 sm:p-6 space-y-4">
            @if ($isHandedOver)
                {{-- Bước 1 --}}
                <div class="flex gap-3">
                    <div class="w-7 h-7 rounded-full bg-primary text-white flex items-center justify-center text-xs font-black shrink-0">1</div>
                    <div>
                        <p class="text-sm font-extrabold text-slate-800">Đến điểm tiếp nhận</p>
                        @if($handoverPoint)
                            <p class="text-xs text-slate-600 font-medium mt-0.5">{{ $handoverPoint->name }} — {{ $handoverPoint->address }}</p>
                            <a href="tel:{{ $handoverPoint->hotline }}" class="inline-flex items-center gap-1 text-xs font-bold text-primary hover:underline mt-1">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.04 12.04 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.211l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                                </svg>
                                Gọi hotline: {{ $handoverPoint->hotline }}
                            </a>
                        @else
                            <p class="text-xs text-slate-500 font-medium mt-0.5">Vui lòng liên hệ admin để biết điểm tiếp nhận cụ thể.</p>
                        @endif
                    </div>
                </div>

                {{-- Bước 2 --}}
                <div class="flex gap-3">
                    <div class="w-7 h-7 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center text-xs font-black shrink-0">2</div>
                    <div>
                        <p class="text-sm font-extrabold text-slate-800">Chuẩn bị giấy tờ</p>
                        <p class="text-xs text-slate-500 font-medium mt-0.5">CCCD/Giấy khai sinh, ảnh trước khi mất tích, và giấy tờ tùy thân của người nhận.</p>
                    </div>
                </div>

                {{-- Bước 3 --}}
                <div class="flex gap-3">
                    <div class="w-7 h-7 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center text-xs font-black shrink-0">3</div>
                    <div>
                        <p class="text-sm font-extrabold text-slate-800">Liên hệ công an phường/xã nơi cư trú</p>
                        <p class="text-xs text-slate-500 font-medium mt-0.5">Cung cấp mã hồ sơ <span class="font-mono font-black text-slate-700">{{ $case->case_code }}</span> để được hỗ trợ nhanh.</p>
                    </div>
                </div>

                {{-- Bước 4 --}}
                <div class="flex gap-3">
                    <div class="w-7 h-7 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center text-xs font-black shrink-0">4</div>
                    <div>
                        <p class="text-sm font-extrabold text-slate-800">Gọi hotline xác nhận thời gian đến nhận</p>
                        <p class="text-xs text-slate-500 font-medium mt-0.5">Kiểm tra giờ làm việc và mang đủ giấy tờ theo yêu cầu của điểm tiếp nhận.</p>
                    </div>
                </div>

                {{-- Bước 5: xác nhận (nút hiển thị chung ở cuối khối) --}}
                <div class="flex gap-3">
                    <div class="w-7 h-7 rounded-full bg-primary text-white flex items-center justify-center text-xs font-black shrink-0">5</div>
                    <div>
                        <p class="text-sm font-extrabold text-slate-800">Xác nhận đã nhận lại con</p>
                        <p class="text-xs text-slate-500 font-medium mt-0.5">Sau khi đã nhận con an toàn, bấm nút bên dưới để đóng hồ sơ.</p>
                    </div>
                </div>
            @else
                <div class="flex gap-3">
                    <div class="w-7 h-7 rounded-full bg-primary text-white flex items-center justify-center text-xs font-black shrink-0">1</div>
                    <div>
                        <p class="text-sm font-extrabold text-slate-800">Xác nhận đã nhận lại con</p>
                        <p class="text-xs text-slate-500 font-medium mt-0.5">Khi bé đã ở nhà an toàn cùng gia đình, bấm nút bên dưới. Hồ sơ sẽ được cập nhật và không còn hiển thị công khai. Hệ thống chỉ ghi nhận theo xác nhận của bạn, không thay thế cơ quan chức năng.</p>
                    </div>
                </div>
            @endif

            {{-- Nút xác nhận đoàn tụ (luôn hiện với chủ hồ sơ khi đang active) --}}
            <div class="@if($isHandedOver) mt-2 @else border-t border-slate-100 pt-4 @endif">
                <form method="POST" action="{{ route('missing.confirm-reunited', $case) }}" class="mt-3">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-success text-white text-xs font-black hover:bg-success/90 transition-all shadow-lg shadow-success/20"
                        onclick="return confirm('Xác nhận bạn đã nhận lại con an toàn? Hành động này không thể hoàn tác.')">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Xác nhận đã nhận lại con
                    </button>
                </form>
            </div>
        </div>
    </div>
@endif
