@extends('layouts.app')

@section('title', 'Tìm kiếm bằng ảnh — TìmTrẻ.vn')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-6 sm:py-10">
    <div class="text-center mb-6">
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 mb-2">Tìm kiếm bằng ảnh</h1>
        <p class="text-xs text-slate-500 font-semibold">Chụp hoặc tải ảnh lên để AI so khớp với toàn bộ hồ sơ trẻ mất tích</p>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200 shadow-premium p-5 sm:p-8">
        <form id="photo-search-form" class="space-y-5">
            <div>
                <label class="block text-xs font-extrabold text-slate-500 uppercase tracking-wider mb-2">
                    Ảnh trẻ cần tìm
                </label>
                <div id="drop-zone"
                     class="relative border-2 border-dashed border-slate-200 hover:border-primary bg-slate-50 hover:bg-white rounded-3xl p-8 text-center transition-all duration-300 cursor-pointer">
                    <input type="file" id="photo-input" accept="image/*" capture="environment"
                           class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                    <div id="upload-placeholder" class="space-y-3">
                        <div class="w-14 h-14 bg-primary/10 text-primary flex items-center justify-center rounded-2xl mx-auto shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                 stroke="currentColor" class="w-7 h-7">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z"/>
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="text-sm font-black text-primary">Chụp ảnh hoặc tải ảnh lên</span>
                            <p class="text-[11px] text-slate-400 font-medium mt-1">Hỗ trợ JPG, PNG, WebP · Tối đa 5MB</p>
                        </div>
                    </div>
                    <div id="upload-preview" class="hidden">
                        <img id="preview-img" src="" alt="Preview"
                             class="max-h-64 mx-auto rounded-2xl border border-slate-200 shadow-sm" />
                        <button type="button" id="remove-photo"
                                class="mt-3 text-xs font-bold text-primary hover:text-primary-dark underline">
                            Xóa ảnh, chọn ảnh khác
                        </button>
                    </div>
                </div>
                <p id="photo-error" class="hidden text-xs text-primary font-semibold mt-2" role="alert"></p>
            </div>

            <button type="submit" id="search-btn"
                    class="w-full py-3.5 rounded-2xl bg-primary text-white font-black text-sm hover:bg-primary/90 active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-primary/20">
                Tìm kiếm
            </button>
        </form>

        {{-- S-03 — UI kết quả (loading / carousel / no-results / warning) được tách
             sang photo-search/results.blade.php để tái sử dụng & dễ bảo trì. --}}
        @include('photo-search.results')
    </div>
</div>
@endsection
