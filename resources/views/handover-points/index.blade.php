@extends('layouts.app')

@section('title', $pageTitle)

@push('head')
    <meta name="description" content="{{ $metaDescription }}">
@endpush

@section('content')

    {{-- Page header --}}
    <section class="bg-white border-b border-slate-100">
        <div class="max-w-7xl mx-auto px-4 py-8 sm:py-10">
            <div class="max-w-3xl space-y-3">
                <div class="inline-flex items-center gap-2 rounded-full bg-primary/10 px-3 py-1.5
                            text-xs font-extrabold text-primary">
                    <x-heroicon-o-shield-check class="w-4 h-4" />

                    Điểm tiếp nhận đã được xác minh
                </div>

                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 tracking-tight">
                    {{ $pageTitle }}
                </h1>

                <p class="text-sm sm:text-base text-slate-500 font-semibold leading-7">
                    Tìm điểm tiếp nhận và bàn giao trẻ em an toàn tại công an, UBND,
                    Tổng đài 111 hoặc tổ chức có thẩm quyền.
                </p>
            </div>
        </div>
    </section>

    {{-- Safety notice --}}
    <section class="max-w-7xl mx-auto px-4 pt-6">
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 sm:p-5">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700
                            flex items-center justify-center shrink-0">
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5" />
                </div>

                <div>
                    <h2 class="font-black text-amber-900">
                        Lưu ý về bàn giao an toàn
                    </h2>

                    <p class="mt-1 text-sm font-semibold leading-6 text-amber-800">
                        Không tự ý bàn giao trẻ giữa hai cá nhân. Việc tiếp nhận và bàn giao
                        thực tế cần được thực hiện thông qua công an, UBND, Tổng đài 111 hoặc
                        tổ chức có thẩm quyền.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Province filter --}}
    <section class="max-w-7xl mx-auto px-4 py-6">
        <form method="GET"
              action="{{ route('handover-points.index') }}"
              class="bg-white rounded-3xl border border-slate-200 shadow-sm p-4 sm:p-6">

            <div class="flex flex-col lg:flex-row lg:items-end gap-4">
                <div class="flex-1">
                    <label for="province_id"
                           class="block text-sm font-black text-slate-800 mb-2">
                        Lọc theo tỉnh, thành phố
                    </label>

                    <div class="relative">
                        <x-heroicon-o-map-pin
                            class="absolute left-3.5 top-1/2 -translate-y-1/2
                                   w-5 h-5 text-slate-400 pointer-events-none" />

                        <select id="province_id"
                                name="province_id"
                                class="w-full appearance-none rounded-2xl border border-slate-200
                                       bg-white py-3 pl-11 pr-10 text-sm font-bold text-slate-700
                                       focus:border-primary focus:outline-none focus:ring-2
                                       focus:ring-primary/20">

                            <option value="">Tất cả tỉnh, thành phố</option>

                            @foreach ($provinces as $province)
                                <option value="{{ $province->id }}"
                                    @selected((string) $selectedProvinceId === (string) $province->id)>
                                    {{ $province->name }}
                                </option>
                            @endforeach
                        </select>

                        <x-heroicon-o-chevron-down
                            class="absolute right-3.5 top-1/2 -translate-y-1/2
                                   w-4 h-4 text-slate-400 pointer-events-none" />
                    </div>

                    @error('province_id')
                        <p class="mt-2 text-xs font-bold text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl
                                   bg-primary px-5 py-3 text-sm font-extrabold text-white
                                   shadow-lg shadow-primary/20 transition
                                   hover:bg-primary-dark active:scale-[0.98]">

                        <x-heroicon-o-funnel class="w-4 h-4" />
                        Áp dụng bộ lọc
                    </button>

                    @if ($selectedProvinceId)
                        <a href="{{ route('handover-points.index') }}"
                           class="inline-flex items-center justify-center gap-2 rounded-2xl
                                  border border-slate-200 bg-white px-5 py-3
                                  text-sm font-extrabold text-slate-600
                                  transition hover:bg-slate-50">

                            <x-heroicon-o-x-mark class="w-4 h-4" />
                            Xóa bộ lọc
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </section>

    {{-- Results --}}
    <main class="max-w-7xl mx-auto px-4 pb-16">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between
                    gap-2 mb-5">

            <p class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">
                {{ $handoverPoints->total() }} kết quả
            </p>

            @if ($selectedProvince)
                <div class="inline-flex items-center gap-2 self-start sm:self-auto
                            rounded-full bg-primary/10 px-3 py-1.5
                            text-xs font-extrabold text-primary">

                    <x-heroicon-o-map-pin class="w-4 h-4" />
                    {{ $selectedProvince->name }}
                </div>
            @endif
        </div>

        @if ($handoverPoints->isEmpty())
            <div class="rounded-3xl border border-slate-200 bg-white px-6 py-20 text-center">
                <div class="mx-auto w-16 h-16 rounded-2xl bg-slate-100
                            flex items-center justify-center text-slate-400">

                    <x-heroicon-o-building-office-2 class="w-8 h-8" />
                </div>

                <h2 class="mt-5 text-lg font-black text-slate-800">
                    Chưa có điểm tiếp nhận phù hợp
                </h2>

                <p class="mt-2 text-sm font-semibold text-slate-500">
                    Hiện chưa có điểm tiếp nhận đang hoạt động tại khu vực đã chọn.
                </p>

                @if ($selectedProvinceId)
                    <a href="{{ route('handover-points.index') }}"
                       class="inline-flex items-center gap-2 mt-6 rounded-xl bg-primary
                              px-4 py-2.5 text-sm font-extrabold text-white
                              hover:bg-primary-dark transition">

                        Xem tất cả khu vực
                    </a>
                @endif
            </div>
        @else
            <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-5 lg:gap-6">
                @foreach ($handoverPoints as $point)
                    @php
                        $typeInformation = match ($point->type) {
                            'POLICE_STATION' => [
                                'label' => 'Cơ quan công an',
                                'classes' => 'bg-blue-50 text-blue-700 border-blue-100',
                            ],
                            'COMMUNE_PEOPLE_COMMITTEE' => [
                                'label' => 'UBND xã/phường',
                                'classes' => 'bg-violet-50 text-violet-700 border-violet-100',
                            ],
                            'HOTLINE_111' => [
                                'label' => 'Tổng đài 111',
                                'classes' => 'bg-red-50 text-red-700 border-red-100',
                            ],
                            'NGO' => [
                                'label' => 'Tổ chức xã hội',
                                'classes' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                            ],
                            default => [
                                'label' => 'Đơn vị tiếp nhận',
                                'classes' => 'bg-slate-100 text-slate-700 border-slate-200',
                            ],
                        };

                        $mapUrl = 'https://www.google.com/maps/search/?api=1&query='
                            . urlencode($point->lat . ',' . $point->lng);
                    @endphp

                    <article class="group flex flex-col overflow-hidden rounded-3xl
                                    border border-slate-200 bg-white shadow-sm
                                    transition duration-200
                                    hover:-translate-y-1 hover:shadow-xl">

                        <div class="p-5 sm:p-6 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <span class="inline-flex items-center rounded-full border
                                             px-3 py-1 text-[11px] font-black
                                             {{ $typeInformation['classes'] }}">
                                    {{ $typeInformation['label'] }}
                                </span>

                                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary
                                            flex items-center justify-center shrink-0">

                                    <x-heroicon-o-shield-check class="w-5 h-5" />
                                </div>
                            </div>

                            <h2 class="mt-4 text-lg font-black leading-6 text-slate-900
                                       group-hover:text-primary transition-colors">
                                {{ $point->name }}
                            </h2>

                            <div class="mt-4 space-y-3">
                                <div class="flex items-start gap-3 text-sm text-slate-600">
                                    <x-heroicon-o-map-pin
                                        class="w-5 h-5 mt-0.5 text-slate-400 shrink-0" />

                                    <span class="font-semibold leading-6">
                                        {{ $point->address }}

                                        @if ($point->province)
                                            <span class="block text-xs text-slate-400">
                                                {{ $point->province->name }}
                                            </span>
                                        @endif
                                    </span>
                                </div>

                                <div class="flex items-start gap-3 text-sm text-slate-600">
                                    <x-heroicon-o-building-office
                                        class="w-5 h-5 mt-0.5 text-slate-400 shrink-0" />

                                    <div>
                                        <span class="block text-xs font-bold text-slate-400">
                                            Đơn vị phụ trách
                                        </span>

                                        <span class="font-bold leading-6">
                                            {{ $point->responsible_org_name }}
                                        </span>
                                    </div>
                                </div>

                                @if ($point->hotline_phone)
                                    <div class="flex items-start gap-3 text-sm text-slate-600">
                                        <x-heroicon-o-phone
                                            class="w-5 h-5 mt-0.5 text-slate-400 shrink-0" />

                                        <div>
                                            <span class="block text-xs font-bold text-slate-400">
                                                Số điện thoại liên hệ
                                            </span>

                                            <a href="tel:{{ preg_replace('/\s+/', '', $point->hotline_phone) }}"
                                               class="font-black text-primary hover:underline">
                                                {{ $point->hotline_phone }}
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            @if ($point->legal_authority_note)
                                <div class="mt-5 rounded-2xl bg-slate-50 p-4">
                                    <p class="text-xs font-black uppercase tracking-wide text-slate-400">
                                        Thông tin thẩm quyền
                                    </p>

                                    <p class="mt-1.5 text-sm font-semibold leading-6 text-slate-600">
                                        {{ $point->legal_authority_note }}
                                    </p>
                                </div>
                            @endif
                        </div>

                        <div class="grid {{ $point->hotline_phone ? 'grid-cols-2' : 'grid-cols-1' }}
                                    border-t border-slate-100">

                            @if ($point->hotline_phone)
                                <a href="tel:{{ preg_replace('/\s+/', '', $point->hotline_phone) }}"
                                   class="flex items-center justify-center gap-2 px-4 py-3.5
                                          text-sm font-extrabold text-primary
                                          transition hover:bg-primary/5">

                                    <x-heroicon-o-phone class="w-4 h-4" />
                                    Gọi liên hệ
                                </a>
                            @endif

                            <a href="{{ $mapUrl }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="flex items-center justify-center gap-2 px-4 py-3.5
                                      text-sm font-extrabold text-slate-700
                                      transition hover:bg-slate-50
                                      {{ $point->hotline_phone ? 'border-l border-slate-100' : '' }}">

                                <x-heroicon-o-map class="w-4 h-4" />
                                Xem bản đồ
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if ($handoverPoints->hasPages())
                <nav class="flex flex-wrap items-center justify-center gap-2 mt-12"
                     aria-label="Phân trang">

                    {{-- Previous page --}}
                    @if ($handoverPoints->onFirstPage())
                        <span class="w-10 h-10 flex items-center justify-center rounded-xl
                                     border border-slate-200 bg-white text-slate-300
                                     cursor-not-allowed">

                            <x-heroicon-o-chevron-left class="w-4 h-4" />
                        </span>
                    @else
                        <a href="{{ $handoverPoints->previousPageUrl() }}"
                           class="w-10 h-10 flex items-center justify-center rounded-xl
                                  border border-slate-200 bg-white text-slate-600
                                  shadow-sm transition hover:border-primary
                                  hover:bg-primary hover:text-white"
                           rel="prev"
                           aria-label="Trang trước">

                            <x-heroicon-o-chevron-left class="w-4 h-4" />
                        </a>
                    @endif

                    @foreach ($handoverPoints->getUrlRange(
                        max(1, $handoverPoints->currentPage() - 2),
                        min($handoverPoints->lastPage(), $handoverPoints->currentPage() + 2)
                    ) as $page => $url)
                        @if ($page === $handoverPoints->currentPage())
                            <span class="w-10 h-10 flex items-center justify-center
                                         rounded-xl bg-primary text-sm font-black text-white
                                         shadow-lg shadow-primary/20"
                                  aria-current="page">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}"
                               class="w-10 h-10 flex items-center justify-center rounded-xl
                                      border border-slate-200 bg-white text-sm font-bold
                                      text-slate-600 shadow-sm transition
                                      hover:border-primary hover:text-primary">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach

                    {{-- Next page --}}
                    @if ($handoverPoints->hasMorePages())
                        <a href="{{ $handoverPoints->nextPageUrl() }}"
                           class="w-10 h-10 flex items-center justify-center rounded-xl
                                  border border-slate-200 bg-white text-slate-600
                                  shadow-sm transition hover:border-primary
                                  hover:bg-primary hover:text-white"
                           rel="next"
                           aria-label="Trang sau">

                            <x-heroicon-o-chevron-right class="w-4 h-4" />
                        </a>
                    @else
                        <span class="w-10 h-10 flex items-center justify-center rounded-xl
                                     border border-slate-200 bg-white text-slate-300
                                     cursor-not-allowed">

                            <x-heroicon-o-chevron-right class="w-4 h-4" />
                        </span>
                    @endif
                </nav>

                <p class="mt-4 text-center text-xs font-bold text-slate-400">
                    Trang {{ $handoverPoints->currentPage() }}
                    trên {{ $handoverPoints->lastPage() }}
                </p>
            @endif
        @endif
    </main>

@endsection