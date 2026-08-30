@props([
    'case' => null,
])

@php
    // H-05 — Thanh trạng thái 5 bước (chuẩn mới), ánh xạ theo status THẬT lưu trong DB
    // và kết quả so khớp khuôn mặt (FaceMatchResult), KHÔNG dùng trạng thái ồn ào:
    //   1. Đã đăng tin        -> status >= APPROVED (đã duyệt, công khai)
    //   2. Nghi vấn khớp      -> có FaceMatchResult đạt ngưỡng điểm (score >= threshold)
    //   3. Admin xác nhận     -> có FaceMatchResult có status = CONFIRMED
    //   4. Đã bàn giao        -> status HANDED_OVER (hoặc REUNITED / UNREACHABLE_FAMILY)
    //   5. Đã đoàn tụ         -> status REUNITED
    //
    // "AI so khớp" KHÔNG có ô riêng — đó chỉ là quá trình xử lý ngầm (khuôn ảnh đang
    // được encode/so khớp). Khi hồ sơ đã đăng tin (APPROVED) mà CHƯA có khớp nghi vấn,
    // bước 2 được bôi pulse + hiện dòng "đang xử lý…" để thể hiện giai đoạn này.

    $status = $case?->status;
    $threshold = (float) config('face_match.score_threshold', 0.7);

    $results = $case?->faceMatchResults;
    $hasSuspectedMatch = $results
        ? $results->where('score', '>=', $threshold)->isNotEmpty()
        : false;
    $hasConfirmedMatch = $results
        ? $results->where('status', 'CONFIRMED')->isNotEmpty()
        : false;

    // Bước 1: "Đã đăng tin" chỉ hoàn tất khi hồ sơ đã được duyệt/công khai
    // (mọi status trừ PENDING đang chờ và REJECTED bị từ chối).
    $step1Done = $status && !in_array($status, ['PENDING', 'REJECTED'], true);

    $reached = 0;
    if ($step1Done) {
        $reached = 1;
    }
    if ($step1Done && $hasSuspectedMatch) {
        $reached = 2;
    }
    if ($step1Done && $hasConfirmedMatch) {
        $reached = 3;
    }
    if (in_array($status, ['HANDED_OVER', 'REUNITED', 'UNREACHABLE_FAMILY'], true)) {
        $reached = max($reached, 4);
    }
    if ($status === 'REUNITED') {
        $reached = max($reached, 5);
    }

    // Bước đang tiến trình (highlight pulse). Nếu đã tới bước 5 hoàn tất -> không còn bước hiện tại.
    $currentStep = $reached >= 5 ? 99 : $reached + 1;

    $steps = [
        ['label' => 'Đã đăng tin'],
        ['label' => 'Nghi vấn khớp'],
        ['label' => 'Admin xác nhận'],
        ['label' => 'Đã bàn giao'],
        ['label' => 'Đã đoàn tụ'],
    ];
@endphp

<div class="status-timeline bg-white border border-slate-200/80 rounded-2xl sm:rounded-3xl shadow-premium overflow-hidden">
    <div class="px-4 sm:px-6 py-4 border-b border-slate-100">
        <h3 class="text-xs font-black text-slate-500 uppercase tracking-wider">Trạng thái hồ sơ</h3>
    </div>
    <div class="p-4 sm:p-6 overflow-x-auto">
        <div class="flex items-center gap-0 min-w-[360px]">
            @foreach ($steps as $index => $step)
                @php
                    $n = $index + 1;
                    $isCompleted = $n <= $reached;
                    $isCurrent = $n === $currentStep;
                    $isPending = $n > $currentStep;
                @endphp
                <div class="flex items-center gap-0 flex-1">
                    {{-- Step circle --}}
                    <div class="flex flex-col items-center gap-1.5">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black shrink-0
                            @if($isCompleted) bg-success text-white shadow-md shadow-success/20 @endif
                            @if($isCurrent) bg-primary text-white shadow-md shadow-primary/20 pulse-dot @endif
                            @if($isPending) bg-slate-100 text-slate-400 @endif">
                            @if($isCompleted)
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            @elseif($isCurrent)
                                <span class="w-2 h-2 bg-white rounded-full"></span>
                            @else
                                <span class="w-2 h-2 bg-slate-300 rounded-full"></span>
                            @endif
                        </div>
                        <span class="text-[10px] font-bold text-center leading-tight
                            @if($isCompleted) text-success @endif
                            @if($isCurrent) text-primary @endif
                            @if($isPending) text-slate-400 @endif">
                            {{ $step['label'] }}
                        </span>
                    </div>
                    @if(!$loop->last)
                        <div class="h-0.5 flex-1 mx-1 mt-[-16px]
                            @if($isCompleted) bg-success @endif
                            @if($isCurrent) bg-primary/30 @endif
                            @if($isPending) bg-slate-100 @endif">
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Hiệu ứng "đang xử lý" giữa bước 1 và 2 (hồ sơ đã đăng tin, AI đang so khớp) --}}
        @if($currentStep === 2 && !$hasSuspectedMatch)
            <p class="mt-3 text-[11px] text-slate-400 font-medium flex items-center gap-1.5">
                <span class="inline-block w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                Hệ thống đang so khớp khuôn mặt với các hồ sơ trẻ thất lạc…
            </p>
        @endif

        {{-- H-05 — Ở bước FOUND: bước "Đã bàn giao" là bước TIẾP THEO (chưa hoàn tất).
            Thêm dòng nhắc để gia đình hiểu rõ đây là trạng thái chờ xác nhận, không
            phải đã bàn giao rồi. --}}
        @if($status === 'FOUND' && $currentStep === 4)
            <p class="mt-3 text-[11px] text-slate-500 font-medium flex items-center gap-1.5">
                <span class="inline-block w-2 h-2 rounded-full bg-brand animate-pulse"></span>
                Kết quả đã xác nhận khớp. Chờ gia đình xác nhận bé đã được bàn giao…
            </p>
        @endif
    </div>
</div>
