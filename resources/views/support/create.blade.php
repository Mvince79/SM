@extends('layouts.app')
@section('title', 'Gửi yêu cầu hỗ trợ')

@section('content')

    {{-- Page header --}}
    <section class="bg-white border-b border-slate-100">
        <div class="max-w-2xl mx-auto px-4 py-8 sm:py-10">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div class="max-w-lg space-y-3">
                    <div class="inline-flex items-center gap-2 rounded-full bg-primary/10 px-3 py-1.5
                                text-xs font-extrabold text-primary">
                        <x-heroicon-o-lifebuoy class="w-4 h-4" />
                        Trung tâm hỗ trợ
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                        Gửi yêu cầu hỗ trợ
                    </h1>

                    <p class="text-sm sm:text-base text-slate-500 font-semibold leading-7">
                        Gặp lỗi, cần khiếu nại hay chỉ muốn góp ý? Cho chúng tôi biết, đội ngũ TìmTrẻ.vn sẽ phản hồi
                        sớm nhất.
                    </p>
                </div>

                <a href="{{ route('support.index') }}"
                    class="inline-flex items-center gap-1.5 border border-slate-200 text-slate-700 text-sm font-extrabold
                           px-5 py-3 rounded-2xl hover:bg-slate-50 transition-colors shrink-0">
                    <x-heroicon-o-clock class="w-4 h-4" />
                    Xem lịch sử ticket
                </a>
            </div>
        </div>
    </section>

    <main class="max-w-2xl mx-auto px-4 py-8 sm:py-10">

        <div class="bg-white border border-slate-200/85 rounded-3xl shadow-premium overflow-hidden">
            <div class="h-1 w-full bg-gradient-to-r from-primary via-red-400 to-orange-400"></div>

            <form method="POST" action="{{ route('support.store') }}" class="p-5 sm:p-6 space-y-6"
                x-data="{ category: '{{ old('category', '') }}', priority: '{{ old('priority', 'MEDIUM') }}', submitting: false }"
                @submit="submitting = true">
                @csrf

                {{-- Category — button group --}}
                <div class="space-y-2">
                    <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">
                        Loại yêu cầu <span class="text-red-500">*</span>
                    </label>
                    <input type="hidden" name="category" x-model="category">

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        @foreach ([
                            'BUG' => ['Báo lỗi', 'heroicon-o-bug-ant'],
                            'COMPLAINT' => ['Khiếu nại', 'heroicon-o-exclamation-circle'],
                            'FEEDBACK' => ['Góp ý', 'heroicon-o-light-bulb'],
                            'FRAUD_SUSPECT' => ['Nghi lừa đảo', 'heroicon-o-shield-exclamation'],
                        ] as $value => [$label, $icon])
                            <button type="button" @click="category = '{{ $value }}'"
                                :class="category === '{{ $value }}'
                                    ? 'border-primary bg-primary/5 text-primary'
                                    : 'border-slate-200 text-slate-500 hover:border-slate-300'"
                                class="flex flex-col items-center justify-center gap-1.5 border rounded-2xl py-3.5 px-2 transition-colors">
                                <x-dynamic-component :component="$icon" class="w-5 h-5" />
                                <span class="text-[11px] font-extrabold text-center leading-tight">{{ $label }}</span>
                            </button>
                        @endforeach
                    </div>
                    @error('category')
                        <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Priority — button group --}}
                <div class="space-y-2">
                    <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">
                        Mức độ ưu tiên
                    </label>
                    <input type="hidden" name="priority" x-model="priority">

                    <div class="grid grid-cols-3 gap-2">
                        @foreach ([
                            'LOW' => 'Thấp',
                            'MEDIUM' => 'Trung bình',
                            'HIGH' => 'Cao',
                        ] as $value => $label)
                            <button type="button" @click="priority = '{{ $value }}'"
                                :class="priority === '{{ $value }}'
                                    ? 'border-primary bg-primary/5 text-primary'
                                    : 'border-slate-200 text-slate-500 hover:border-slate-300'"
                                class="border rounded-2xl py-2.5 text-xs font-extrabold transition-colors">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                    @error('priority')
                        <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Subject --}}
                <div class="space-y-1.5">
                    <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">
                        Tiêu đề <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="subject" value="{{ old('subject') }}" maxlength="255"
                        placeholder="VD: Không tải được ảnh lên hồ sơ"
                        class="w-full px-4 py-3 border rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white
                               transition-all font-medium placeholder-slate-400
                               {{ $errors->has('subject') ? 'border-red-400' : 'border-slate-200' }}">
                    @error('subject')
                        <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Description --}}
                <div class="space-y-1.5">
                    <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">
                        Mô tả chi tiết <span class="text-red-500">*</span>
                    </label>
                    <textarea name="description" rows="6" maxlength="5000"
                        placeholder="Mô tả chi tiết vấn đề bạn gặp phải để đội ngũ hỗ trợ xử lý nhanh hơn…"
                        class="w-full px-4 py-3 border rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white
                               transition-all font-medium resize-none placeholder-slate-400
                               {{ $errors->has('description') ? 'border-red-400' : 'border-slate-200' }}">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    class="w-full flex items-center justify-center gap-2 bg-primary text-white font-extrabold text-sm py-3.5 rounded-2xl
                           hover:bg-primary-dark transition-all shadow-md shadow-primary/10">
                    <x-heroicon-o-paper-airplane class="w-4 h-4" />
                    Gửi yêu cầu
                </button>

            </form>
        </div>

    </main>

@endsection