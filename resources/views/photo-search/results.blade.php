{{--
    S-03 — Khối kết quả tìm kiếm bằng ảnh (PARTIAL, không phải page).

    LƯU Ý: file này được `@include` từ `photo-search/index.blade.php` nên KHÔNG
    được khai báo `@extends` / `@section('content')`. Trước đây file có khai báo
    `@extends('layouts.app')` + `@section('content')`: khi include, section
    'content' của index bị ghi đè khiến TOÀN BỘ form upload biến mất khỏi trang.

    S-04 — Dòng cảnh báo độ tin cậy AI được render TĨNH, luôn hiển thị, không có
    class `hidden` và KHÔNG có handle JS nào → không thể tắt/ẩn.
--}}

{{-- ── S-04 — Cảnh báo cố định: luôn hiển thị, không thể ẩn ───────────────── --}}
<div class="mt-6 p-3 bg-brand-light border border-brand/20 rounded-2xl">
    <p class="text-[11px] font-bold text-brand-dark leading-relaxed flex items-start gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
             stroke="currentColor" class="w-4 h-4 shrink-0 mt-px" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
        </svg>
        {{-- S-04 — Giữ NGUYÊN 1 dòng liền mạch (không ngắt dòng) để nội dung cảnh
             báo bắt buộc này luôn khớp khi kiểm tra tự động/soát thủ công. --}}
        <span>Kết quả do AI gợi ý, có thể không chính xác 100% — vui lòng xác minh qua kênh chính thức trước khi hành động.</span>
    </p>
</div>

{{-- ── Loading — .skeleton-shimmer + dòng chữ động (.scanning-text) ───────── --}}
<div id="loading-state" class="hidden mt-5" aria-live="polite" aria-busy="true">
    <p class="scanning-text text-xs font-bold text-brand text-center">
        Đang quét và so khớp với hồ sơ trẻ mất tích
    </p>

    <div class="mt-4 flex gap-4 overflow-hidden" aria-hidden="true">
        @for ($i = 0; $i < 2; $i++)
            <div class="flex-none w-[88%] sm:w-[22rem]">
                <div class="bg-white rounded-3xl border border-slate-200 overflow-hidden">
                    <div class="skeleton-shimmer w-full aspect-[4/3] rounded-none"></div>
                    <div class="p-4 space-y-2.5">
                        <div class="skeleton-shimmer h-4 w-2/3"></div>
                        <div class="skeleton-shimmer h-3 w-1/2"></div>
                        <div class="flex gap-2 pt-1.5">
                            <div class="skeleton-shimmer h-9 flex-1"></div>
                            <div class="skeleton-shimmer h-9 flex-1"></div>
                        </div>
                    </div>
                </div>
            </div>
        @endfor
    </div>
</div>

{{-- ── Kết quả ───────────────────────────────────────────────────────────── --}}
<div id="results-area" class="hidden mt-5">
    <div id="results-header" class="hidden items-center justify-between mb-3">
        <h2 class="text-sm font-black text-slate-800">Kết quả gợi ý</h2>
        <span id="results-count" class="text-[10px] font-bold text-slate-400 uppercase tracking-wider"></span>
    </div>

    {{-- Carousel: `.result-carousel` (app.css) lo scroll-snap + đà cuộn mobile.
         Âm margin bằng đúng padding của card cha để track rộng hết khổ mà không
         gây tràn ngang trang; padding trong giữ lề cho thẻ đầu/cuối. --}}
    <div id="results-carousel"
         class="result-carousel -mx-5 px-5 pb-4 sm:-mx-8 sm:px-8"
         role="region" aria-label="Danh sách hồ sơ AI gợi ý, cuộn ngang để xem thêm"
         tabindex="0">
    </div>

    {{-- Không có kết quả → 1 thẻ trung tính duy nhất, không bao giờ là màn hình trống --}}
    <div id="no-results" class="hidden">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-premium p-6 text-center">
            <div class="w-16 h-16 bg-brand-light text-brand flex items-center justify-center rounded-full mx-auto mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                     stroke="currentColor" class="w-8 h-8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
            </div>
            <p id="no-results-title" class="text-sm font-black text-slate-700 mb-1.5">Chưa tìm thấy hồ sơ tương đồng</p>
            <p id="no-results-message" class="text-xs text-slate-500 font-medium leading-relaxed mb-4">
                Điều này không có nghĩa là hồ sơ không tồn tại — ảnh có thể chụp ở góc khó, thiếu sáng,
                hoặc gia đình chưa kịp đăng tin. Bạn vẫn nên báo tin để hệ thống tiếp tục đối chiếu tự động
                với các hồ sơ mới.
            </p>
            <a href="{{ route('found-children.create') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-primary text-white text-xs font-black hover:bg-primary-dark transition-all shadow-lg shadow-primary/20">
                Báo tin thấy trẻ
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('photo-search-form');
    if (!form) return;

    const input = document.getElementById('photo-input');
    const preview = document.getElementById('upload-preview');
    const placeholder = document.getElementById('upload-placeholder');
    const previewImg = document.getElementById('preview-img');
    const removeBtn = document.getElementById('remove-photo');
    const searchBtn = document.getElementById('search-btn');
    const loading = document.getElementById('loading-state');
    const resultsArea = document.getElementById('results-area');
    const resultsHeader = document.getElementById('results-header');
    const resultsCount = document.getElementById('results-count');
    const carousel = document.getElementById('results-carousel');
    const noResults = document.getElementById('no-results');
    const noResultsTitle = document.getElementById('no-results-title');
    const noResultsMessage = document.getElementById('no-results-message');
    const photoError = document.getElementById('photo-error');

    // Text từ API/DB được nhúng qua innerHTML → phải escape để chặn XSS.
    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    })[c]);

    const NO_RESULT_TITLE_DEFAULT = noResultsTitle.textContent.trim();
    const NO_RESULT_DEFAULT = noResultsMessage.textContent.trim();

    let selectedFile = null;

    input.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;
        if (!file.type.startsWith('image/')) {
            showPhotoError('Vui lòng chọn file ảnh hợp lệ.');
            return;
        }
        selectedFile = file;
        previewImg.src = URL.createObjectURL(file);
        placeholder.classList.add('hidden');
        preview.classList.remove('hidden');
        hidePhotoError();
    });

    removeBtn.addEventListener('click', () => {
        selectedFile = null;
        input.value = '';
        preview.classList.add('hidden');
        placeholder.classList.remove('hidden');
        hidePhotoError();
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!selectedFile) {
            showPhotoError('Vui lòng chọn ảnh trước khi tìm kiếm.');
            return;
        }
        hidePhotoError();

        const formData = new FormData();
        formData.append('photo', selectedFile);

        searchBtn.disabled = true;
        loading.classList.remove('hidden');
        resultsArea.classList.add('hidden');

        try {
            const res = await fetch('{{ route("api.photo-search") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const data = await res.json();
            loading.classList.add('hidden');
            resultsArea.classList.remove('hidden');

            const results = Array.isArray(data.results) ? data.results : [];

            if (data.status === 'OK' && results.length > 0) {
                renderResults(results);
            } else if (data.status === 'NO_FACE') {
                showNoResults(data.message, 'Không nhận diện được khuôn mặt trong ảnh');
            } else if (data.status === 'OK') {
                showNoResults();
            } else {
                // Lỗi phía dịch vụ so khớp — vẫn đưa người dùng tới lối đi tiếp,
                // không bao giờ để trống màn hình.
                showNoResults(data.message, 'Chưa thể so khớp lúc này');
            }
        } catch (err) {
            loading.classList.add('hidden');
            resultsArea.classList.remove('hidden');
            showNoResults(
                'Không thể kết nối tới dịch vụ so khớp lúc này. Bạn vẫn có thể báo tin để hệ thống đối chiếu tự động sau.',
                'Chưa thể so khớp lúc này',
            );
        } finally {
            searchBtn.disabled = false;
        }
    });

    function renderResults(results) {
        noResults.classList.add('hidden');
        resultsHeader.classList.remove('hidden');
        resultsHeader.classList.add('flex');
        resultsCount.textContent = results.length + ' kết quả';

        carousel.innerHTML = results.map((item) => {
            const score = parseFloat(item.score) || 0;
            // Chỉ dùng 3 token màu: Carmine (primary) / Navy (brand) / Forest (success).
            const badgeClass = score >= 80 ? 'bg-primary' : 'bg-brand';
            const name = esc(item.name);
            const code = esc(item.case_code);

            return `
                <article class="result-card">
                    <div class="h-full flex flex-col bg-white rounded-3xl border border-slate-200 shadow-premium overflow-hidden">
                        <div class="relative bg-slate-100">
                            <img src="${esc(item.photo_url)}" alt="Ảnh hồ sơ ${name}"
                                 loading="lazy" decoding="async"
                                 class="w-full aspect-[4/3] object-cover" />
                            <div class="absolute top-3 right-3 ${badgeClass} text-white text-xs font-black px-3 py-1.5 rounded-full shadow-lg">
                                ${score.toFixed(1)}% tương đồng
                            </div>
                        </div>
                        <div class="p-4 flex flex-col flex-1 gap-3">
                            <div>
                                <h3 class="text-base font-black text-slate-900">${name}</h3>
                                <p class="text-xs text-slate-500 font-semibold mt-0.5">
                                    ${item.age ? esc(item.age) + ' tuổi' : 'Tuổi không rõ'} · Mã: ${code}
                                </p>
                            </div>
                            <div class="mt-auto grid grid-cols-2 gap-2">
                                <a href="{{ url('/missing') }}/${encodeURIComponent(item.case_code)}"
                                   class="text-center py-2.5 rounded-2xl bg-brand-light text-brand-dark text-xs font-black hover:bg-brand/15 transition-all">
                                    Xem chi tiết
                                </a>
                                {{-- S-04 — nhãn trung tính, KHÔNG khẳng định chắc chắn ("đây chính là bé"). --}}
                                <a href="{{ route('found-children.create') }}?match=${encodeURIComponent(item.case_code)}"
                                   class="text-center py-2.5 rounded-2xl bg-primary text-white text-xs font-black hover:bg-primary-dark transition-all shadow-md">
                                    Báo tin ngay
                                </a>
                            </div>
                        </div>
                    </div>
                </article>
            `;
        }).join('');

        carousel.scrollLeft = 0;
    }

    function showNoResults(message, title) {
        carousel.innerHTML = '';
        resultsHeader.classList.add('hidden');
        resultsHeader.classList.remove('flex');
        resultsCount.textContent = '';
        noResultsTitle.textContent = title ? String(title) : NO_RESULT_TITLE_DEFAULT;
        noResultsMessage.textContent = message ? String(message) : NO_RESULT_DEFAULT;
        noResults.classList.remove('hidden');
    }

    function showPhotoError(msg) {
        photoError.textContent = msg;
        photoError.classList.remove('hidden');
    }

    function hidePhotoError() {
        photoError.classList.add('hidden');
        photoError.textContent = '';
    }
});
</script>
@endpush
