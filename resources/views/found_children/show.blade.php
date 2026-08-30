@extends('layouts.app')

@section('title', 'Chi tiết hồ sơ')

@section('content')
<main class="pt-20 sm:pt-24 pb-16">
    <div class="max-w-4xl mx-auto px-4">

        @if(session('success'))
            <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-sm font-medium text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- H-05 — Hướng dẫn người tìm thấy khi kết quả đã khớp: đem bé ra công an --}}
        @if($item->status === 'MATCHED' && auth()->check() && auth()->id() === $item->user_id)
            <div class="mb-4 p-4 bg-brand/5 border border-brand/20 rounded-2xl flex items-start gap-3">
                <x-heroicon-o-check-badge class="w-6 h-6 text-brand shrink-0 mt-0.5" />
                <div class="text-sm text-slate-700">
                    <p class="font-bold text-brand">Kết quả đã xác nhận khớp — cảm ơn bạn!</p>
                    <p class="mt-1">Hãy đem bé đến công an phường/xã hoặc điểm tiếp nhận gần nhất để bàn giao.
                    Cơ quan chức năng sẽ trực tiếp liên hệ gia đình đăng tin. Hệ thống sẽ tự động thông báo
                    cho gia đình khi bé được bàn giao.</p>
                </div>
            </div>
        @endif

        {{-- U-02 — Gợi ý 3 điểm tiếp nhận gần nhất theo vị trí trẻ được tìm thấy --}}
        <x-nearest-handover-points :lat="$item->lat" :lng="$item->lng" />

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mt-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900">Chi tiết hồ sơ</h1>
                <p class="mt-1 text-xs sm:text-sm text-slate-500">
                    Người báo: <span class="font-semibold text-slate-700">{{ $item->user->full_name }}</span>
                    · {{ $item->created_at->format('d/m/Y') }}
                </p>
            </div>
            @if(auth()->check() && (auth()->id() === $item->user_id || auth()->user()->isAdmin()))
            <div class="flex shrink-0 gap-2">
                <a href="{{ route('found-children.edit', $item) }}"
                   class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Sửa
                </a>
                <form method="POST" action="{{ route('found-children.destroy', $item) }}" class="inline-flex"
                      onsubmit="return confirm('Xóa hồ sơ này? Thao tác không thể hoàn tác.')">
                    @csrf
                    @method('DELETE')
                    <button class="inline-flex items-center justify-center rounded-xl border border-rose-200 bg-white px-4 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-50">
                        Xóa
                    </button>
                </form>
            </div>
            @endif
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 mt-4">
            <!-- Cột chính -->
            <div class="lg:col-span-2 space-y-4 sm:space-y-6">

                {{-- Thông tin hồ sơ (gộp cơ bản + ước tính) --}}
                <div class="bg-white border border-slate-200/80 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-premium">
                    <h2 class="font-extrabold text-slate-900 text-sm uppercase tracking-wider mb-4">Thông tin hồ sơ</h2>
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-4 sm:gap-y-5">
                        <div>
                            <dt class="text-[11px] uppercase tracking-wide font-semibold text-slate-400">Ngày phát hiện</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $item->found_date->format('d/m/Y') }}</dd>
                        </div>
                        @if($item->found_time)
                        <div>
                            <dt class="text-[11px] uppercase tracking-wide font-semibold text-slate-400">Giờ phát hiện</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $item->found_time }}</dd>
                        </div>
                        @endif
                        <div>
                            <dt class="text-[11px] uppercase tracking-wide font-semibold text-slate-400">Tỉnh/Thành</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $item->province->name }}</dd>
                        </div>
                        <div>
                            @php($status = $item->getAttribute('status'))
                            <dt class="text-[11px] uppercase tracking-wide font-semibold text-slate-400">Trạng thái</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold
                                    {{ $status == 'ACTIVE' ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ match ($status) {
                                        'PENDING' => 'Đang chờ',
                                        'APPROVED' => 'Đã duyệt',
                                        'MATCHED' => 'Đã khớp',
                                        'FOUND' => 'Đã tìm thấy',
                                        'REJECTED' => 'Bị từ chối',
                                        'ACTIVE' => 'Đang hoạt động',
                                        'RESOLVED' => 'Đã xử lý',
                                        'CLOSED' => 'Đã đóng',
                                        default => $status,
                                    } }}
                                </span>
                            </dd>
                        </div>
                        @if($item->gender_estimate)
                        <div>
                            <dt class="text-[11px] uppercase tracking-wide font-semibold text-slate-400">Giới tính</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $item->gender_estimate == 'MALE' ? 'Nam' : ($item->gender_estimate == 'FEMALE' ? 'Nữ' : 'Không xác định') }}</dd>
                        </div>
                        @endif
                        @if($item->age_estimate)
                        <div>
                            <dt class="text-[11px] uppercase tracking-wide font-semibold text-slate-400">Tuổi ước tính</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $item->age_estimate }} tuổi</dd>
                        </div>
                        @endif
                    </dl>
                </div>

                @if($item->condition_desc)
                <div class="bg-white border border-slate-200/80 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-premium">
                    <h2 class="font-extrabold text-slate-900 text-sm uppercase tracking-wider mb-3">Mô tả tình trạng</h2>
                    <p class="text-sm text-slate-600 leading-relaxed">{{ $item->condition_desc }}</p>
                </div>
                @endif

                @if($item->clothes_desc)
                <div class="bg-white border border-slate-200/80 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-premium">
                    <h2 class="font-extrabold text-slate-900 text-sm uppercase tracking-wider mb-3">Mô tả quần áo</h2>
                    <p class="text-sm text-slate-600 leading-relaxed">{{ $item->clothes_desc }}</p>
                </div>
                @endif

                @if($item->special_marks)
                <div class="bg-white border border-slate-200/80 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-premium">
                    <h2 class="font-extrabold text-slate-900 text-sm uppercase tracking-wider mb-3">Dấu hiệu đặc biệt</h2>
                    <p class="text-sm text-slate-600 leading-relaxed">{{ $item->special_marks }}</p>
                </div>
                @endif

                @if($item->photos->isNotEmpty())
                <div class="bg-white border border-slate-200/80 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-premium">
                    <h2 class="font-extrabold text-slate-900 text-sm uppercase tracking-wider mb-4">Ảnh đã tải lên</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @foreach($item->photos as $photo)
                            <a href="{{ Storage::url($photo->path_original) }}" target="_blank"
                               class="group relative block overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                                <img src="{{ Storage::url($photo->path_display ?? $photo->path_original) }}"
                                     alt="Ảnh {{ $loop->iteration }}" loading="lazy"
                                     class="aspect-[4/3] w-full object-cover transition group-hover:opacity-90" />
                                @if($photo->is_primary)
                                    <span class="absolute top-2 left-2 inline-flex items-center rounded-full bg-brand/90 px-2 py-0.5 text-[11px] font-semibold text-white shadow-sm">
                                        Ảnh chính
                                    </span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            {{-- Cột phụ (trên mobile tự xuống dưới cùng) --}}
            <aside class="space-y-4 sm:space-y-6">

                @if($item->current_location)
                <div class="bg-white border border-slate-200/80 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-premium">
                    <h3 class="font-extrabold text-slate-900 text-sm uppercase tracking-wider mb-2">Vị trí hiện tại</h3>
                    <p class="text-sm text-slate-600">{{ $item->current_location }}</p>
                </div>
                @endif

                @if($item->handover_agency)
                <div class="bg-white border border-slate-200/80 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-premium">
                    <h3 class="font-extrabold text-slate-900 text-sm uppercase tracking-wider mb-2">Bàn giao cho</h3>
                    <p class="text-sm text-slate-600">{{ $item->handover_agency }}</p>
                </div>
                @endif

                <div class="bg-white border border-slate-200/80 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-premium">
                    <h3 class="font-extrabold text-slate-900 text-sm uppercase tracking-wider mb-2">Người báo cáo</h3>
                    <p class="text-sm font-semibold text-slate-800">{{ $item->user->full_name }}</p>
                    <p class="text-xs text-slate-500 mt-1">{{ $item->user->email }}</p>
                </div>

                @can('update', $item)
                <div class="bg-white border border-slate-200/80 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-premium">
                    <h3 class="font-extrabold text-slate-900 text-sm uppercase tracking-wider mb-3">Tải ảnh lên</h3>
                    <form action="{{ route('found-children.photos.store', $item) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="file" name="photos[]" accept="image/*" multiple required
                               class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-xl file:border-0 file:bg-primary/10 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-primary hover:file:bg-primary/15" />
                        @error('photos')<span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span>@enderror
                        @error('photos.*')<span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span>@enderror
                        <label class="mt-3 flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" id="set_primary" name="set_primary" value="1" class="rounded border-slate-300 text-primary focus:ring-primary" />
                            Đặt làm ảnh chính
                        </label>
                        <button type="submit"
                                class="mt-3 inline-flex w-full items-center justify-center rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-dark">
                            Upload ảnh
                        </button>
                    </form>
                </div>
                @endcan
            </aside>
        </div>
    </div>
</main>
@endsection
