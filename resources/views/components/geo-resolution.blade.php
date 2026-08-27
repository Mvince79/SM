{{--
    V-02 — Kết quả reverse-geocode NỘI BỘ hiển thị ở dạng input DISABLED (U-01 nhóm 2).

    Hành chính Việt Nam từ 01/07/2025 (Nghị quyết 202/2025/QH15) chỉ còn 2 cấp:
    Tỉnh/Thành phố → Xã/Phường. Cấp Huyện/Quận đã BÃI BỎ nên khối này CỐ Ý không
    có ô "Quận/Huyện".

    Người dùng KHÔNG nhập được 2 ô này — giá trị duy nhất được chấp nhận là kết quả
    point-in-polygon từ điểm ghim trên bản đồ (GeoBoundaryService, không gọi API ngoài).

    $status: '' (chưa ghim) | PENDING | OK | NOT_FOUND | UNVERIFIED | ERROR
      • NOT_FOUND  → ngoài lãnh thổ VN, CHẶN gửi (rule not_in:NOT_FOUND ở component cha)
      • UNVERIFIED → dataset ranh giới còn là placeholder, KHÔNG chặn (fail-safe có chủ đích)
--}}
@props([
    'status' => '',
    'province' => null,
    'ward' => null,
    'errorKey' => 'geoStatus',
])

<div class="mt-3 p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
    <p class="form-label">
        Đơn vị hành chính (tự động xác định — không thể sửa)
    </p>

    <div class="grid sm:grid-cols-2 gap-3">
        <div class="space-y-1.5">
            <label class="form-label text-slate-400">Tỉnh/Thành phố</label>
            <input type="text" disabled value="{{ $province ?? '' }}" placeholder="Ghim vị trí để xác định"
                class="form-input disabled:bg-slate-100 disabled:text-slate-500 disabled:cursor-not-allowed font-semibold" />
        </div>
        <div class="space-y-1.5">
            <label class="form-label text-slate-400">Xã/Phường</label>
            <input type="text" disabled value="{{ $ward ?? '' }}" placeholder="Ghim vị trí để xác định"
                class="form-input disabled:bg-slate-100 disabled:text-slate-500 disabled:cursor-not-allowed font-semibold" />
        </div>
    </div>

    @if ($status === 'PENDING')
        <p class="text-xs text-slate-500 font-semibold flex items-center gap-1.5">
            <span class="inline-block w-3 h-3 border-2 border-slate-300 border-t-slate-500 rounded-full animate-spin"></span>
            Đang xác định đơn vị hành chính…
        </p>
    @elseif ($status === 'NOT_FOUND')
        <p class="text-xs text-red-600 font-bold flex items-start gap-1.5">
            <x-heroicon-o-exclamation-circle class="w-4 h-4 shrink-0 mt-px" />
            <span>Vị trí đã ghim nằm NGOÀI lãnh thổ Việt Nam. Vui lòng ghim lại một điểm trong lãnh
                thổ Việt Nam — không thể gửi hồ sơ với vị trí này.</span>
        </p>
    @elseif ($status === 'UNVERIFIED')
        <p class="text-xs text-amber-600 font-bold">
            Hệ thống chưa cài bộ ranh giới hành chính 2025 nên chưa thể xác định Tỉnh/Xã từ toạ độ.
            Vị trí bạn ghim vẫn được ghi nhận đầy đủ.
        </p>
    @elseif ($status === 'ERROR')
        <p class="text-xs text-amber-600 font-bold">
            Không xác định được đơn vị hành chính lúc này. Vị trí vẫn được ghi nhận; hệ thống sẽ kiểm
            tra lại khi bạn gửi hồ sơ.
        </p>
    @endif

    @error($errorKey)
        <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
    @enderror
</div>
