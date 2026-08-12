{{--
Gợi ý điểm bàn giao gần nhất cho hồ sơ mất tích này.
Dùng trong resources/views/missing/detail.blade.php:
@livewire('missing.handover-suggestion-card', ['caseId' => $case->id])
--}}

@php
    // Cùng bảng màu theo token thương hiệu như trang /diem-ban-giao:
    // brand (navy) = công an, primary (carmine) = khẩn cấp/hotline, success (forest) = tổ chức hỗ trợ.
    $typeInfoMap = [
        'POLICE_STATION' => [
            'label' => 'Cơ quan công an',
            'classes' => 'bg-brand/10 text-brand border-brand/20',
        ],
        'COMMUNE_PEOPLE_COMMITTEE' => [
            'label' => 'UBND xã/phường',
            'classes' => 'bg-[#8a6d3b]/10 text-[#8a6d3b] border-[#8a6d3b]/20',
        ],
        'HOTLINE_111' => [
            'label' => 'Tổng đài 111',
            'classes' => 'bg-primary/10 text-primary border-primary/20',
        ],
        'NGO' => [
            'label' => 'Tổ chức xã hội',
            'classes' => 'bg-success-light text-success border-success/35',
        ],
    ];
@endphp

<div class="bg-white border border-slate-200/80 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-premium space-y-4">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-brand/10 rounded-xl flex items-center justify-center shrink-0">
            <x-heroicon-o-building-office-2 class="w-5 h-5 text-brand" />
        </div>
        <div>
            <h3 class="font-black text-slate-900 text-sm sm:text-base tracking-tight">
                Điểm tiếp nhận bàn giao gần đây
            </h3>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">
                Nơi bàn giao trẻ an toàn, đúng quy trình
            </p>
        </div>
    </div>

    @if ($points->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-4 text-center space-y-2">
            <p class="text-xs font-semibold text-slate-500">
                Chưa xác định được điểm tiếp nhận gần khu vực này.
            </p>
            <a href="{{ route('handover-points.index') }}"
                class="inline-block text-xs font-black text-primary hover:underline">
                Xem toàn bộ danh sách điểm tiếp nhận →
            </a>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($points as $point)
                @php
                    $typeInfo = $typeInfoMap[$point->type] ?? [
                        'label' => 'Đơn vị tiếp nhận',
                        'classes' => 'bg-slate-100 text-slate-700 border-slate-200',
                    ];
                @endphp

                <div class="rounded-2xl border border-slate-150 bg-slate-50 p-3.5 space-y-2">
                    <div class="flex items-start justify-between gap-2">
                        <span
                            class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-black {{ $typeInfo['classes'] }}">
                            {{ $typeInfo['label'] }}
                        </span>

                        @if (isset($point->distance))
                            <span class="text-[10px] font-black text-slate-400 shrink-0">
                                ~{{ round($point->distance, 1) }} km
                            </span>
                        @endif
                    </div>

                    <p class="text-sm font-extrabold text-slate-900 leading-snug">
                        {{ $point->name }}
                    </p>

                    <p class="text-xs font-semibold text-slate-500 leading-relaxed">
                        {{ $point->address }}
                        @if ($point->province)
                            · {{ $point->province->name }}
                        @endif
                    </p>

                    @if ($point->hotline_phone)
                        <a href="tel:{{ preg_replace('/\s+/', '', $point->hotline_phone) }}"
                            class="inline-flex items-center gap-1.5 text-xs font-black text-primary hover:underline">
                            <x-heroicon-o-phone class="w-3.5 h-3.5" />
                            {{ $point->hotline_phone }}
                        </a>
                    @endif
                </div>
            @endforeach
        </div>

        <a href="{{ route('handover-points.index') }}"
            class="block text-center text-xs font-black text-primary hover:underline pt-1">
            Xem tất cả điểm tiếp nhận →
        </a>
    @endif
</div>