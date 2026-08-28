<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\FaceMatchResult;
use App\Models\FoundChild;
use App\Models\MissingChild;
use App\Models\Photo;
use App\Models\Province;
use App\Models\UserConsent;
use App\Services\GeoBoundaryService;
use App\Traits\HasIdempotency;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

new class extends Component {
    use HasIdempotency;
    use WithFileUploads;

    public int $step = 1;

    // ── Bước 1 — Vị trí & thời gian phát hiện ───────────────────────────────
    public string $province_id = '';
    public string $found_date = '';
    public string $found_time = '';
    // U-01 — ĐÃ BỎ `current_location` (free-text). Vị trí phát hiện chỉ đến từ
    // map picker (lat/lng); cấp hành chính do reverse-geocode nội bộ suy ra.
    // `handover_agency` được giữ lại vì là thông tin cơ quan tiếp nhận, không phải địa chỉ trẻ.
    public string $handover_agency = '';
    public string $lat = '';
    public string $lng = '';

    // ── V-02 — Kết quả reverse-geocode (chỉ đọc, KHÔNG cho người dùng nhập) ──
    // Hành chính VN 2025: chỉ 2 cấp Tỉnh → Xã/Phường (cấp Huyện/Quận đã bãi bỏ).
    // '' = chưa ghim | PENDING | OK | NOT_FOUND (ngoài lãnh thổ VN) | ERROR
    public string $geoStatus = '';
    public ?string $geoProvince = null;
    public ?string $geoWard = null;

    // ── Bước 2 — Đặc điểm nhận dạng & Ảnh ────────────────────────────────────
    public string $gender_estimate = 'UNKNOWN';
    public string $age_estimate = '';
    public string $condition_desc = '';
    public string $clothes_desc = '';
    public string $special_marks = '';
    public array $photos = [];

    // ── Bước 3 — Xác nhận & Cam kết ──────────────────────────────────────────
    public bool $agreed = false;
    public bool $agreedDataPolicy = false;
    // C-01 — Checkbox cam kết giới hạn sử dụng dữ liệu.
    public bool $dataUseConsent = false;
    public bool $isSubmitting = false;
    public string $idempotencyKey = '';

    public $provinces;

    // ── S-03 — Hồ sơ mất tích được liên kết sẵn từ thẻ kết quả tìm kiếm bằng ảnh ──
    // Người dùng bấm "Báo tin ngay" trên 1 thẻ gợi ý → tới đây kèm
    // `?match=MC-YYYY-NNNNN`. Liên kết được giữ xuyên suốt 3 bước và ghi lại khi
    // gửi hồ sơ, để admin/gia đình biết tin báo này liên quan tới hồ sơ nào.
    //
    // CHỦ Ý: chỉ điền sẵn LIÊN KẾT, KHÔNG tự điền giới tính/tuổi/đặc điểm theo hồ
    // sơ mất tích. Người báo tin phải tự mô tả em bé đang ở trước mắt họ — nếu
    // auto-fill, tin báo sẽ trông như đã "xác nhận" khớp dù họ chưa hề đối chiếu
    // (trái tinh thần S-04: không khẳng định chắc chắn).
    public ?int $matchedMissingId = null;
    public ?string $matchedCaseCode = null;
    public ?string $matchedChildName = null;

    // ── Mount ────────────────────────────────────────────────────────────────
    public function mount(): void
    {
        $this->authorize('create', FoundChild::class);

        $this->provinces = Province::where('is_active', 1)->orderBy('name')->get();
        $this->found_date = now()->toDateString();
        $this->idempotencyKey = $this->generateIdempotencyKey();

        $this->applyMatchedMissingChild(request()->query('match'));
    }

    /**
     * S-03 — Nạp hồ sơ mất tích từ query `?match=<case_code>`.
     * Bỏ qua im lặng nếu mã sai định dạng / không tồn tại / hồ sơ đã đóng —
     * form vẫn dùng được bình thường như khi vào trực tiếp.
     */
    protected function applyMatchedMissingChild(mixed $caseCode): void
    {
        if (! is_string($caseCode) || ! preg_match('/^MC-\d{4}-\d{5}$/', $caseCode)) {
            return;
        }

        $child = MissingChild::where('case_code', $caseCode)->first();

        if (! $child || in_array($child->status, MissingChild::CLOSED_STATUSES, true)) {
            return;
        }

        $this->matchedMissingId = $child->id;
        $this->matchedCaseCode = $child->case_code;
        $this->matchedChildName = $child->full_name;
    }

    /** S-03 — Người báo tin xác định không phải hồ sơ này → bỏ liên kết. */
    public function clearMatchedMissingChild(): void
    {
        $this->matchedMissingId = null;
        $this->matchedCaseCode = null;
        $this->matchedChildName = null;
    }

    /**
     * S-03 — Ghi liên kết "người báo tin cho rằng tin này liên quan hồ sơ X".
     *
     * Dùng bảng `face_match_results` sẵn có với `source = MANUAL`, `status =
     * PENDING`, `score = 0`: đây là PHỎNG ĐOÁN của người báo tin, không phải kết
     * luận của AI, nên không được tính là điểm khớp và vẫn phải qua xác minh.
     * (Observer chỉ gửi thông báo khi status đổi sang CONFIRMED → tạo bản ghi
     * PENDING ở đây không làm gia đình nhận thông báo sai.)
     */
    protected function linkReportedMissingChild(FoundChild $found): void
    {
        if ($this->matchedMissingId === null) {
            return;
        }

        // Prop public có thể bị sửa từ phía client → xác thực lại trước khi ghi.
        $child = MissingChild::find($this->matchedMissingId);

        if (! $child || in_array($child->status, MissingChild::CLOSED_STATUSES, true)) {
            return;
        }

        try {
            FaceMatchResult::firstOrCreate(
                [
                    'missing_id' => $child->id,
                    'found_id' => $found->id,
                    'source' => 'MANUAL',
                ],
                [
                    'score' => 0,
                    'is_matched' => 0,
                    'confidence_level' => 'LOW',
                    'status' => 'PENDING',
                ],
            );
        } catch (\Throwable $e) {
            // Không để việc ghi liên kết làm hỏng luồng gửi hồ sơ chính.
            Log::warning('S-03: không ghi được liên kết hồ sơ mất tích: ' . $e->getMessage());
        }
    }

    // ── Validate từng bước ───────────────────────────────────────────────────
    protected function rulesStep1(): array
    {
        return [
            'province_id' => ['required', 'exists:provinces,id'],
            // F-trim — Người đi đường báo ngay lúc thấy → gần như luôn là hôm nay.
            // Chuyển thành không bắt buộc, mặc định hôm nay (mount + submit fallback).
            'found_date' => ['nullable', 'date', 'before_or_equal:today'],
            'handover_agency' => ['nullable', 'string', 'max:150'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            // V-02 — chặn server-side khi điểm ghim nằm ngoài lãnh thổ Việt Nam.
            'geoStatus' => ['not_in:NOT_FOUND'],
        ];
    }

    protected function messagesStep1(): array
    {
        return [
            'geoStatus.not_in' => 'Vị trí đã ghim nằm ngoài lãnh thổ Việt Nam. Vui lòng ghim lại một điểm trong lãnh thổ Việt Nam.',
            'province_id.required' => 'Vui lòng chọn tỉnh/thành phố.',
            'found_date.before_or_equal' => 'Ngày phát hiện không thể ở tương lai.',
        ];
    }

    protected function rulesStep2(): array
    {
        return [
            // F-trim — Giới tính để optional: AI đối chiếu KHÔNG dùng trường này
            // (chỉ dùng ảnh). Mặc định UNKNOWN; sau này có thể auto-fill từ
            // detected_gender của ProcessPhotoJob. Giữ manual chọn tay (không bắt buộc).
            'gender_estimate' => ['nullable', 'in:MALE,FEMALE,UNKNOWN'],
            // F-05: trước đây hardcode max:120 — không khớp với form đăng tin
            // tìm trẻ (dùng config('app.child_age_limit'), đúng Luật Trẻ em
            // 2016 = dưới 16 tuổi). Đồng bộ lại theo cùng 1 nguồn config.
            'age_estimate' => ['nullable', 'integer', 'min:0', 'max:' . (config('app.child_age_limit') - 1)],
            // F-trim — Gộp tình trạng/quần áo/dấu hiệu vào 1 ô mô tả tự do duy nhất
            // (special_marks). condition_desc & clothes_desc không còn nhập từ form,
            // giữ nullable trong DB cho tương thích ngược với dữ liệu/admin cũ.
            'special_marks' => ['nullable', 'string', 'max:2000'],
            'photos' => ['required', 'array', 'min:1', 'max:5'],
            'photos.*' => ['file', 'image', 'max:5120', new \App\Rules\SecureImage],
        ];
    }

    protected function messagesStep2(): array
    {
        return [
            'age_estimate.max' => 'Hệ thống chỉ tiếp nhận báo tin cho trẻ dưới ' . config('app.child_age_limit') . ' tuổi theo quy định của Luật Trẻ em 2016.',
            'photos.required' => 'Vui lòng tải lên ít nhất 1 ảnh để cộng đồng nhận diện.',
            'photos.min' => 'Vui lòng tải lên ít nhất 1 ảnh.',
            'photos.*.image' => 'Tệp tải lên phải là hình ảnh.',
            'photos.*.max' => 'Mỗi ảnh tối đa 5MB.',
            'photos.max' => 'Tối đa 5 ảnh.',
        ];
    }

    protected function rulesStep3(): array
    {
        return [
            'agreed' => ['accepted'],
            'agreedDataPolicy' => ['accepted'],
            'dataUseConsent' => ['accepted'],
        ];
    }

    protected function messagesStep3(): array
    {
        return [
            'agreed.accepted' => 'Vui lòng xác nhận cam kết trước khi gửi hồ sơ.',
            'agreedDataPolicy.accepted' => 'Vui lòng đồng ý với Chính sách Bảo vệ Dữ liệu Trẻ em.',
            'dataUseConsent.accepted' => 'Vui lòng đồng ý với điều khoản giới hạn sử dụng dữ liệu.',
        ];
    }

    // ── V-02 — Reverse-geocode nội bộ ────────────────────────────────────────

    /**
     * Nhận kết quả tra cứu từ JS (POST /api/geo/lookup) để render field CHỈ ĐỌC.
     *
     * CHỈ dùng cho HIỂN THỊ. Server luôn tự giải lại toạ độ trong
     * syncGeoResolution() trước khi validate/lưu, nên client không thể giả mạo
     * tên tỉnh/xã hay vượt qua chặn "ngoài lãnh thổ Việt Nam".
     */
    public function applyGeoResult(?string $status = null, ?string $province = null, ?string $ward = null): void
    {
        $this->geoStatus = in_array($status, ['PENDING', 'OK', 'NOT_FOUND', 'UNVERIFIED', 'ERROR'], true) ? $status : '';
        $this->geoProvince = $this->geoStatus === 'OK' ? ($province ?: null) : null;
        $this->geoWard = $this->geoStatus === 'OK' ? ($ward ?: null) : null;

        // V-02b — Tự động chọn tỉnh trong dropdown từ kết quả định vị (bất chấp
        // khác biệt tiền tố tên giữa GeoJSON và DB). Tên tỉnh ở đây đến từ endpoint
        // nội bộ /api/geo/lookup (server tính point-in-polygon) nên tin cậy.
        if ($this->geoStatus === 'OK' && $this->geoProvince) {
            $matched = \App\Models\Province::resolveByName($this->geoProvince);
            if ($matched) {
                $this->province_id = (string) $matched->id;
            }
        }
    }

    /**
     * Giải lại lat/lng → {Tỉnh, Xã/Phường} bằng GeoBoundaryService (100% nội bộ,
     * point-in-polygon, không gọi API ngoài). Đây là nguồn SỰ THẬT dùng để
     * validate và lưu DB — chạy trước mọi lần validate.
     *
     * Hành chính VN 2025 chỉ có 2 cấp Tỉnh → Xã/Phường; không có cấp Huyện/Quận.
     */
    protected function syncGeoResolution(): void
    {
        if ($this->lat === '' || $this->lng === '' || ! is_numeric($this->lat) || ! is_numeric($this->lng)) {
            $this->geoStatus = '';
            $this->geoProvince = null;
            $this->geoWard = null;

            return;
        }

        $result = app(GeoBoundaryService::class)->lookup((float) $this->lat, (float) $this->lng);

        $status = $result['status'] ?? 'NOT_FOUND';

        // UNVERIFIED = dataset ranh giới còn là placeholder → KHÔNG chặn người dùng.
        // Chỉ NOT_FOUND (ngoài lãnh thổ VN, dữ liệu thật) mới bị chặn.
        $this->geoStatus = in_array($status, ['OK', 'UNVERIFIED'], true) ? $status : 'NOT_FOUND';
        $this->geoProvince = $this->geoStatus === 'OK' ? ($result['province'] ?? null) : null;
        $this->geoWard = $this->geoStatus === 'OK' ? ($result['ward'] ?? null) : null;

        // V-02b — Giải lại province_id từ toạ độ (nguồn sự thật, chạy trước save).
        // Đảm bảo dropdown Tỉnh luôn khớp ranh giới dù user không ghim lại.
        if ($this->geoStatus === 'OK' && $this->geoProvince) {
            $matched = \App\Models\Province::resolveByName($this->geoProvince);
            if ($matched) {
                $this->province_id = (string) $matched->id;
            }
        }
    }

    // ── Navigation ───────────────────────────────────────────────────────────
    public function next(): void
    {
        $this->syncGeoResolution();

        match ($this->step) {
            1 => $this->validate($this->rulesStep1(), $this->messagesStep1()),
            2 => $this->validate($this->rulesStep2(), $this->messagesStep2()),
            default => null,
        };

        if ($this->step < 3) {
            $this->step++;
        }
    }

    public function back(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    // ── Guard: input HTML date/time đôi khi gửi lên text rác → reset về rỗng ──
    public function updatedFoundDate(): void
    {
        if ($this->found_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->found_date)) {
            $this->found_date = '';
        }
    }

    public function updatedFoundTime(): void
    {
        if ($this->found_time && !preg_match('/^\d{2}:\d{2}$/', $this->found_time)) {
            $this->found_time = '';
        }
    }

    // ── Upload ảnh ───────────────────────────────────────────────────────────
    public function updatedPhotos(): void
    {
        foreach ($this->photos as $photo) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($photo->getRealPath());
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];

            if (!in_array($mimeType, $allowed)) {
                $this->addError('photos.*', 'Chỉ chấp nhận ảnh JPG, PNG, WebP thực sự.');
                $this->photos = [];
                return;
            }
        }

        $this->validate(
            [
                'photos' => ['array', 'max:5'],
                'photos.*' => ['file', 'image', 'max:5120', new \App\Rules\SecureImage],
            ],
            [
                'photos.*.max' => 'Mỗi ảnh tối đa 5MB.',
                'photos.max' => 'Tối đa 5 ảnh.',
            ],
        );
    }

    public function removePhoto(int $index): void
    {
        array_splice($this->photos, $index, 1);
    }

    // ── Submit ───────────────────────────────────────────────────────────────
    public function submit(): void
    {
        $this->authorize('create', FoundChild::class);

        if ($this->isSubmitting) {
            return;
        }

        $this->isSubmitting = true;

        try {
            // V-02 — Giải lại toạ độ ở biên tin cậy: không tin geoStatus/geoProvince/
            // geoWard do client gửi lên, tự tra cứu nội bộ rồi mới validate.
            // Chặn hồ sơ có điểm ghim ngoài lãnh thổ Việt Nam.
            $this->syncGeoResolution();

            $this->validate(
                ['geoStatus' => ['not_in:NOT_FOUND']],
                ['geoStatus.not_in' => 'Vị trí đã ghim nằm ngoài lãnh thổ Việt Nam. Vui lòng ghim lại một điểm trong lãnh thổ Việt Nam.'],
            );

            $this->validate($this->rulesStep3(), $this->messagesStep3());

            if ($this->found_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->found_date)) {
                $this->found_date = now()->toDateString();
            }

            if ($this->idempotencyKey === '') {
                $this->idempotencyKey = $this->generateIdempotencyKey();
            }

            $result = $this->resolveIdempotent($this->idempotencyKey, FoundChild::class, Auth::id(), function () {
                $item = FoundChild::create([
                    'user_id' => Auth::id(),
                    'province_id' => (int) $this->province_id,
                    'found_date' => $this->found_date ?: now()->toDateString(),
                    'found_time' => preg_match('/^\d{2}:\d{2}/', $this->found_time ?? '') ? $this->found_time : null,
                    'gender_estimate' => $this->gender_estimate ?: 'UNKNOWN',
                    'age_estimate' => $this->age_estimate !== '' ? (int) $this->age_estimate : null,
                    // U-01/V-02 — `ward` KHÔNG còn là free-text: giá trị duy nhất được
                    // chấp nhận là kết quả reverse-geocode nội bộ từ điểm ghim trên bản đồ.
                    // (`current_location` free-text đã bị loại bỏ khỏi form.)
                    'ward' => $this->geoWard ?: null,
                    'handover_agency' => $this->handover_agency ?: null,
                    'lat' => $this->lat !== '' ? (float) $this->lat : null,
                    'lng' => $this->lng !== '' ? (float) $this->lng : null,
                    'condition_desc' => $this->condition_desc ?: null,
                    'clothes_desc' => $this->clothes_desc ?: null,
                    'special_marks' => $this->special_marks,
                    'status' => 'PENDING',
                ]);

                // F-05: trước đây form báo tin tìm thấy không ghi UserConsent nào
                // cả — thêm để khớp với những gì child-data-policy Điều 7 đã cam
                // kết áp dụng cho luồng này. 'agreed' (đúng sự thật + chịu trách
                // nhiệm pháp luật) tương ứng TOS, giống create-missing-form.
                foreach (['TOS', 'PRIVACY_POLICY', 'CHILD_DATA_POLICY'] as $type) {
                    UserConsent::create([
                        'user_id' => Auth::id(),
                        'consent_type' => $type,
                        'version' => config("legal.versions.$type"),
                        'accepted_at' => now(),
                        'ip_address' => request()->ip(),
                    ]);
                }

                // C-01 — Ghi 1 bản ghi cam kết giới hạn sử dụng dữ liệu (chỉ khi user tick).
                if ($this->dataUseConsent) {
                    UserConsent::create([
                        'user_id' => Auth::id(),
                        'consent_type' => 'DATA_USE_LIMITATION',
                        'version' => config('legal.versions.DATA_USE_LIMITATION', '1.0'),
                        'accepted_at' => now(),
                        'ip_address' => request()->ip(),
                    ]);
                }

                foreach ($this->photos as $index => $photo) {
                    $path = $photo->storePublicly("found-children/{$item->id}", 'public');
                    Photo::create([
                        'model_type' => FoundChild::class,
                        'model_id' => $item->id,
                        'path_original' => $path,
                        'file_size' => $photo->getSize(),
                        'mime_type' => $photo->getMimeType(),
                        'is_primary' => $index === 0 ? 1 : 0,
                        'sort_order' => $index,
                    ]);
                }

                // S-03 — Giữ lại liên kết tới hồ sơ mất tích mà người báo tin đã
                // chọn từ thẻ kết quả tìm kiếm bằng ảnh (nếu có).
                $this->linkReportedMissingChild($item);

                return $item;
            });

            if (! $result['was_duplicate']) {
                session()->flash('success', 'Đã gửi hồ sơ tìm được thành công! Hồ sơ đang chờ xét duyệt.');
            }

            $this->idempotencyKey = $this->generateIdempotencyKey();
            $this->redirect(route('found-children.show', $result['resource_id']));
        } finally {
            $this->isSubmitting = false;
        }
    }
};
?>

<div>
    <div class="bg-white border border-slate-200/85 rounded-3xl p-6 sm:p-10 shadow-premium space-y-8">

        {{-- Header --}}
        <div class="flex flex-col items-center text-center space-y-2.5">
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 mb-2">
                <div class="bg-emerald-50 text-emerald-600 p-2 rounded-xl shrink-0 shadow-sm">
                    <x-heroicon-o-hand-raised class="w-6 h-6" />
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-tight self-center">
                    Báo tin tìm được — Giúp bé sớm về nhà
                </h1>
            </div>
            <p class="text-slate-500 text-xs sm:text-sm max-w-md mx-auto font-medium">
                Bạn vừa gặp/đang giữ một em bé chưa rõ danh tính? Hãy điền nhanh các thông tin dưới đây, hệ thống AI
                sẽ tự đối chiếu với các hồ sơ mất tích để tìm gia đình giúp bé.
            </p>
            <p class="text-slate-400 text-[11px] sm:text-xs max-w-md mx-auto font-medium">
                Bạn chỉ cần báo tin — hệ thống gợi ý điểm tiếp nhận gần nhất và nối với gia đình. Bạn không cần quản lý hồ sơ sau khi báo.
            </p>
        </div>

        {{-- S-03 — Hồ sơ mất tích được liên kết sẵn từ kết quả tìm kiếm bằng ảnh.
             Hiển thị ở cả 3 bước để người báo tin luôn thấy mình đang báo tin cho
             hồ sơ nào, và có thể bỏ liên kết nếu thấy không phải.
             S-04 — ngôn từ trung tính: "nghi vấn trùng khớp", không khẳng định. --}}
        @if ($matchedCaseCode)
            <div class="bg-brand-light border border-brand/20 rounded-2xl p-4 space-y-2">
                <div class="flex items-start gap-2.5">
                    <x-heroicon-o-link class="w-5 h-5 text-brand shrink-0 mt-px" />
                    <div class="flex-1 min-w-0 space-y-1">
                        <p class="text-xs font-black text-brand-dark">
                            Tin báo này đang được liên kết với hồ sơ
                            <a href="{{ url('/missing/' . $matchedCaseCode) }}" target="_blank"
                                class="underline decoration-brand/40">{{ $matchedCaseCode }}</a>
                            @if ($matchedChildName)
                                — {{ $matchedChildName }}
                            @endif
                        </p>
                        <p class="text-[11px] text-slate-600 font-medium leading-relaxed">
                            Đây là <span class="font-bold">nghi vấn trùng khớp do AI gợi ý</span>, chưa phải kết luận.
                            Vui lòng mô tả em bé bạn đang gặp theo đúng những gì bạn quan sát được — đội xác minh sẽ
                            đối chiếu lại với hồ sơ trên.
                        </p>
                    </div>
                </div>
                <button type="button" wire:click="clearMatchedMissingChild"
                    class="text-[11px] font-bold text-slate-500 hover:text-primary underline ml-7">
                    Không phải hồ sơ này — bỏ liên kết
                </button>
            </div>
        @endif

        {{-- Step Progress --}}
        <div class="relative flex items-center justify-between max-w-md mx-auto px-4 pt-2">
            <div class="absolute top-4 left-0 right-0 h-0.5 bg-slate-100 z-0"></div>
            <div class="absolute top-4 left-0 h-0.5 bg-primary z-0 transition-all duration-500"
                style="width: {{ (($step - 1) / 2) * 100 }}%"></div>

            @foreach ([1 => 'Vị trí & thời gian', 2 => 'Đặc điểm & ảnh', 3 => 'Xác nhận'] as $s => $label)
                <div class="relative z-10 flex flex-col items-center gap-1.5">
                    <div
                        class="w-8 h-8 rounded-full font-extrabold text-xs flex items-center justify-center transition-colors duration-500
                                    {{ $step > $s ? 'bg-success text-white shadow-md shadow-success/20' : ($step === $s ? 'bg-primary text-white shadow-md shadow-primary/20' : 'bg-slate-100 text-slate-400') }}">
                        {{ $step > $s ? '✓' : $s }}
                    </div>
                    <span
                        class="text-[10px] font-black uppercase tracking-wide
                                    {{ $step > $s ? 'text-success' : ($step === $s ? 'text-primary' : 'text-slate-400') }}">
                        {{ $label }}
                    </span>
                </div>
            @endforeach
        </div>

        {{-- ════ BƯỚC 1 — Vị trí & thời gian ════ --}}
        @if ($step === 1)
            <div class="space-y-5 pt-4">
                <h3 class="font-extrabold text-slate-800 text-sm border-b border-slate-100 pb-2">
                    Bước 1: Vị trí & thời gian phát hiện
                </h3>

                <div class="grid sm:grid-cols-2 gap-4">
                    {{-- Tỉnh/Thành — searchable combobox --}}
                    <div class="space-y-1.5" x-data="{
                                        open: false,
                                        query: '',
                                        selectedLabel: @js(optional($provinces->firstWhere('id', $province_id))->name ?? ''),
                                        options: @js($provinces->map(fn($p) => ['id' => (string) $p->id, 'name' => $p->name])->values()),
                                        get filtered() {
                                            if (!this.query) return this.options;
                                            const q = this.query.toLowerCase();
                                            return this.options.filter(o => o.name.toLowerCase().includes(q));
                                        },
                                        select(opt) {
                                            this.selectedLabel = opt.name;
                                            this.query = '';
                                            this.open = false;
                                            $wire.set('province_id', opt.id);
                                        },
                                        init() {
                                            // V-02b — Khi định vị tự điền province_id (từ server
                                            // applyGeoResult/syncGeoResolution), cập nhật nhãn hiển thị
                                            // của combobox cho khớp, dù user chưa tự chọn.
                                            if (window.Livewire && this.$wire) {
                                                this.$wire.$watch('province_id', (val) => {
                                                    if (val === '' || val === null) {
                                                        this.selectedLabel = '';
                                                        return;
                                                    }
                                                    const o = this.options.find(o => String(o.id) === String(val));
                                                    this.selectedLabel = o ? o.name : '';
                                                });
                                            }
                                        }
                                    }" @click.outside="open = false">
                        <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">
                            Tỉnh/Thành phố *
                        </label>
                        <div class="relative">
                            <button type="button" @click="open = !open; if(open) $nextTick(() => $refs.search.focus())"
                                class="w-full px-4 py-3 border rounded-2xl text-sm text-left bg-slate-50 focus:bg-white transition-all font-bold flex items-center justify-between gap-2
                                            {{ $errors->has('province_id') ? 'border-red-400' : 'border-slate-200' }}">
                                <span x-text="selectedLabel || 'Chọn tỉnh/thành...'"
                                    :class="selectedLabel ? 'text-slate-700' : 'text-slate-400 font-medium'"></span>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                                    stroke="currentColor" class="w-4 h-4 text-slate-400 shrink-0 transition-transform"
                                    :class="open ? 'rotate-180' : ''">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>
                            <div x-show="open" x-transition x-cloak
                                class="absolute z-30 mt-2 w-full bg-white border border-slate-200 rounded-2xl shadow-xl overflow-hidden">
                                <div class="p-2 border-b border-slate-100">
                                    <input type="text" x-model="query" x-ref="search" placeholder="Tìm tỉnh/thành..."
                                        class="w-full px-3 py-2 text-sm rounded-xl bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary" />
                                </div>
                                <ul class="max-h-56 overflow-y-auto">
                                    <template x-for="opt in filtered" :key="opt.id">
                                        <li @click="select(opt)"
                                            class="px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-primary/5 hover:text-primary cursor-pointer"
                                            x-text="opt.name"></li>
                                    </template>
                                    <li x-show="filtered.length === 0" class="px-4 py-3 text-xs text-slate-400 text-center">
                                        Không tìm thấy tỉnh/thành phù hợp
                                    </li>
                                </ul>
                            </div>
                        </div>
                        @error('province_id')
                            <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">
                            Ngày phát hiện
                        </label>
                        <input type="date" wire:model="found_date" class="w-full px-4 py-3 border rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-bold text-slate-600
                                        {{ $errors->has('found_date') ? 'border-red-400' : 'border-slate-200' }}" />
                        <p class="text-[11px] text-slate-400 font-medium">Để trống = hôm nay (bạn đang báo ngay lúc thấy bé).</p>
                        @error('found_date')
                            <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- F-trim — Giờ phát hiện & Cơ quan bàn giao là thông tin phụ, không
                phục vụ đối chiếu khuôn mặt cũng không liên hệ gia đình → ẩn dưới mục
                "Thêm chi tiết (không bắt buộc)" để giảm thao tác cho người đi đường. --}}
                <details class="group rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 px-4 py-3">
                    <summary class="cursor-pointer text-xs font-extrabold text-slate-500 uppercase tracking-wider flex items-center gap-2 list-none select-none">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                            stroke="currentColor" class="w-4 h-4 text-slate-400 transition-transform group-open:rotate-90">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                        Thêm chi tiết (không bắt buộc)
                    </summary>
                    <div class="grid sm:grid-cols-2 gap-4 pt-4">
                        <div class="space-y-1.5">
                            <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">
                                Giờ phát hiện (nếu biết)
                            </label>
                            <input type="time" wire:model.blur="found_time"
                                class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-white focus:bg-white transition-all font-bold text-slate-600" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">
                                Cơ quan/Nơi bàn giao
                            </label>
                            <input type="text" wire:model.blur="handover_agency" placeholder="Nơi bạn đã giao bé, nếu có (VD: đội công an phường, nhà thiếu nhi, bệnh viện…)."
                                class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-white focus:bg-white transition-all font-medium placeholder-slate-400" />
                        </div>
                    </div>
                </details>

                {{-- U-01 — ĐÃ BỎ input "Vị trí hiện tại" (free-text). Vị trí phát hiện
                chỉ được khai báo qua map picker bên dưới; Tỉnh/Thành và Xã/Phường do
                reverse-geocode nội bộ suy ra và hiển thị ở chế độ chỉ đọc. --}}

                {{-- Map picker --}}
                <div class="space-y-2">
                    <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                        <x-heroicon-o-map-pin class="w-4 h-4 text-slate-500" />
                        Ghim vị trí phát hiện trên bản đồ
                    </label>
                    <p class="text-xs text-slate-400">
                        Đây là cách DUY NHẤT để khai báo nơi phát hiện. Bấm nút bên dưới để lấy vị trí hiện tại, hoặc
                        click trực tiếp vào bản đồ để ghim tay — hệ thống sẽ tự xác định Tỉnh/Thành và Xã/Phường.
                    </p>

                    <div wire:ignore x-data="{
                                        map: null,
                                        marker: null,
                                        locating: false,
                                        initLat: @js($lat ?: null),
                                        initLng: @js($lng ?: null),
                                        init() {
                                            if (window._foundMapPickerInstance) {
                                                window._foundMapPickerInstance.remove();
                                                window._foundMapPickerInstance = null;
                                            }
                                            const hasSaved = this.initLat !== null && this.initLng !== null;
                                            const center   = hasSaved ? [this.initLat, this.initLng] : [16.0544, 108.2022];
                                            const zoom     = hasSaved ? 15 : 6;

                                            this.map = L.map('found-map-picker').setView(center, zoom);
                                            initVnSovereigntyLayer(this.map);
                                            window._foundMapPickerInstance = this.map;

                                            // keepBuffer + updateWhenZooming:false → kéo/zoom tới lui mượt.
                                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                                attribution: '© OpenStreetMap',
                                                keepBuffer: 2,
                                                updateWhenZooming: false
                                            }).addTo(this.map);

                                            if (hasSaved) {
                                                this.marker = L.marker([this.initLat, this.initLng])
                                                    .addTo(this.map)
                                                    .bindPopup('Vị trí đã chọn').openPopup();
                                                // V-02 — hiển thị lại Tỉnh/Xã cho điểm đã ghim trước đó.
                                                window.vnGeoLookupAndSync($wire, this.initLat, this.initLng);
                                            }

                                            this.map.on('click', (e) => {
                                                const { lat, lng } = e.latlng;
                                                if (this.marker) this.marker.remove();
                                                this.marker = L.marker([lat, lng])
                                                    .addTo(this.map)
                                                    .bindPopup('Vị trí đã ghim tay').openPopup();
                                                $wire.set('lat', lat.toFixed(6));
                                                $wire.set('lng', lng.toFixed(6));
                                                // V-02 — reverse-geocode nội bộ → Tỉnh/Xã chỉ đọc.
                                                window.vnGeoLookupAndSync($wire, lat, lng);
                                            });

                                            setTimeout(() => this.map.invalidateSize(), 150);
                                        },
                                        locateMe() {
                                            if (!navigator.geolocation) {
                                                alert('Trình duyệt không hỗ trợ định vị.');
                                                return;
                                            }
                                            this.locating = true;
                                            const onDone    = () => { this.locating = false; };
                                            const onSuccess = (pos) => {
                                                const lat = pos.coords.latitude;
                                                const lng = pos.coords.longitude;
                                                this.map.setView([lat, lng], 16);
                                                if (this.marker) this.marker.remove();
                                                this.marker = L.marker([lat, lng])
                                                    .addTo(this.map)
                                                    .bindPopup('Vị trí hiện tại của bạn').openPopup();
                                                $wire.set('lat', lat.toFixed(6));
                                                $wire.set('lng', lng.toFixed(6));
                                                // V-02 — reverse-geocode nội bộ → Tỉnh/Xã chỉ đọc.
                                                window.vnGeoLookupAndSync($wire, lat, lng);
                                                onDone();
                                            };
                                            const onError = (err) => {
                                                onDone();
                                                alert('Không thể lấy vị trí: ' + (err.message || 'Lỗi không xác định'));
                                            };
                                            const opts = { enableHighAccuracy: true, timeout: 10000 };
                                            try { window.__lastUserGestureAt = Date.now(); } catch (e) {}
                                            if (window.allowGeolocationForNextCall) window.allowGeolocationForNextCall();
                                            if (window.__nativeGeolocationGet) {
                                                window.__nativeGeolocationGet(onSuccess, onError, opts);
                                            } else {
                                                navigator.geolocation.getCurrentPosition(onSuccess, onError, opts);
                                            }
                                        }
                                    }" x-init="init()">

                        {{-- [FIX 1] Nút nằm TRÊN map — đồng nhất với create-missing-form --}}
                        <button type="button" @click="locateMe()" :disabled="locating" class="mb-3 w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-2xl
                                           bg-primary/10 text-primary font-extrabold text-xs
                                           hover:bg-primary/20 active:scale-95 transition-all
                                           disabled:opacity-50 disabled:cursor-not-allowed">
                            <template x-if="!locating">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                                    stroke="currentColor" class="w-4 h-4 shrink-0">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                </svg>
                            </template>
                            <template x-if="locating">
                                <span
                                    class="inline-block w-3.5 h-3.5 border-2 border-primary/40 border-t-primary rounded-full animate-spin"></span>
                            </template>
                            <span x-text="locating ? 'Đang định vị…' : 'Lấy vị trí hiện tại'"></span>
                        </button>

                        <div id="found-map-picker" class="w-full h-56 rounded-2xl border border-slate-200 z-0"></div>

                    </div>

                    @if ($lat && $lng)
                        <p class="text-xs text-success font-bold mt-1 flex items-center gap-1.5">
                            <x-heroicon-o-check-circle class="w-4 h-4 shrink-0" />
                            <span>Đã ghim: {{ round((float) $lat, 6) }}, {{ round((float) $lng, 6) }}</span>
                        </p>
                    @endif

                    {{-- V-02 — Đơn vị hành chính suy ra từ điểm ghim (CHỈ ĐỌC).
                    Hành chính VN từ 01/07/2025 chỉ còn 2 cấp: Tỉnh → Xã/Phường. --}}
                    <div class="mt-3 p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                        <p class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">
                            Đơn vị hành chính (tự động xác định — không thể sửa)
                        </p>

                        <div class="grid sm:grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Tỉnh/Thành
                                    phố</label>
                                <input type="text" readonly tabindex="-1" value="{{ $geoProvince ?? '' }}"
                                    placeholder="Ghim vị trí để xác định"
                                    class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-100 text-slate-600 font-semibold cursor-not-allowed placeholder-slate-400" />
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Xã/Phường</label>
                                <input type="text" readonly tabindex="-1" value="{{ $geoWard ?? '' }}"
                                    placeholder="Ghim vị trí để xác định"
                                    class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-100 text-slate-600 font-semibold cursor-not-allowed placeholder-slate-400" />
                            </div>
                        </div>

                        @if ($geoStatus === 'PENDING')
                            <p class="text-xs text-slate-500 font-semibold flex items-center gap-1.5">
                                <span
                                    class="inline-block w-3 h-3 border-2 border-slate-300 border-t-slate-500 rounded-full animate-spin"></span>
                                Đang xác định đơn vị hành chính…
                            </p>
                        @elseif ($geoStatus === 'NOT_FOUND')
                            <p class="text-xs text-red-600 font-bold flex items-start gap-1.5">
                                <x-heroicon-o-exclamation-circle class="w-4 h-4 shrink-0 mt-px" />
                                <span>Vị trí đã ghim nằm NGOÀI lãnh thổ Việt Nam. Vui lòng ghim lại một điểm trong lãnh
                                    thổ Việt Nam — không thể gửi hồ sơ với vị trí này.</span>
                            </p>
                        @elseif ($geoStatus === 'UNVERIFIED')
                            <p class="text-xs text-amber-600 font-bold">
                                Hệ thống chưa cài bộ ranh giới hành chính 2025 nên chưa thể xác định Tỉnh/Xã từ toạ độ.
                                Vị trí bạn ghim vẫn được ghi nhận đầy đủ.
                            </p>
                        @elseif ($geoStatus === 'ERROR')
                            <p class="text-xs text-amber-600 font-bold">
                                Không xác định được đơn vị hành chính lúc này. Vị trí vẫn được ghi nhận; hệ thống sẽ kiểm
                                tra lại khi bạn gửi hồ sơ.
                            </p>
                        @endif

                        @error('geoStatus')
                            <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- V-04 — Gợi ý điểm tiếp nhận gần nhất theo vị trí ghim.
                    Tự động tính khoảng cách (reactive theo lat/lng Livewire). --}}
                    <x-nearest-handover-points :lat="$lat" :lng="$lng" reactive="true"
                        title="Điểm tiếp nhận gần vị trí này" />

                </div>
            </div>

            {{-- ════ BƯỚC 2 — Đặc điểm & Ảnh ════ --}}
        @elseif ($step === 2)
            <div class="space-y-5 pt-4">
                <h3 class="font-extrabold text-slate-800 text-sm border-b border-slate-100 pb-2">
                    Bước 2: Đặc điểm nhận dạng & Ảnh
                </h3>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">
                            Giới tính ước tính <span class="text-slate-400 font-medium normal-case">(không bắt buộc)</span>
                        </label>
                        <select wire:model="gender_estimate"
                            class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-bold text-slate-600">
                            {{-- [FIX 4] Đồng nhất text với missing-form --}}
                            <option value="UNKNOWN">Chưa rõ</option>
                            <option value="MALE">Nam</option>
                            <option value="FEMALE">Nữ</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">
                            Tuổi ước tính
                        </label>
                        <div class="relative">
                            <input type="number" wire:model.blur="age_estimate" min="0" max="{{ config('app.child_age_limit') - 1 }}" placeholder="VD: 8"
                                class="w-full px-4 py-3 pr-14 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-medium placeholder-slate-400" />
                            <span
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-xs text-slate-400 font-semibold pointer-events-none">tuổi</span>
                        </div>
                    </div>
                </div>

                {{-- F-trim — Gộp "tình trạng / quần áo / dấu hiệu đặc biệt" (vốn 3 ô
                bắt/optional rời rạc) thành 1 ô mô tả tự do duy nhất → giảm số ô phải
                điền cho người đi đường. Lưu vào special_marks; condition_desc &
                clothes_desc không còn nhập từ form (giữ nullable trong DB). --}}
                <div class="space-y-1.5">
                    <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">
                        Mô tả nhận dạng <span class="text-slate-400 font-medium normal-case">(không bắt buộc)</span>
                    </label>
                    <textarea wire:model.blur="special_marks" rows="4"
                        placeholder="Mô tả tự do: tình trạng sức khỏe, quần áo, dấu hiệu nhận dạng (vết sẹo, nốt ruồi, đồ vật mang theo…). Chi tiết hơn giúp gia đình nhận ra bé nhanh hơn."
                        class="w-full px-4 py-3 border rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-medium resize-none placeholder-slate-400
                                    {{ $errors->has('special_marks') ? 'border-red-400' : 'border-slate-200' }}"></textarea>
                    @error('special_marks')
                        <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Upload ảnh --}}
                <div class="space-y-3">
                    <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">
                        Ảnh của bé * <span class="text-slate-400 font-medium normal-case">(tối thiểu 1, tối đa 5)</span>
                    </label>

                    <div class="border-2 border-dashed rounded-3xl p-8 text-center transition-all duration-300 cursor-pointer
                                        {{ $errors->has('photos') ? 'border-red-300 bg-red-50' : 'border-slate-200 hover:border-primary bg-slate-50 hover:bg-white' }}"
                        onclick="document.getElementById('found-photo-upload').click()">
                        <div
                            class="w-12 h-12 bg-primary/10 text-primary flex items-center justify-center rounded-2xl mx-auto mb-3 shadow-sm">
                            <x-heroicon-o-camera class="w-6 h-6" />
                        </div>
                        <p class="text-xs font-black text-primary">Ấn để chọn ảnh</p>
                        <p class="text-xs text-slate-400 font-medium mt-1">hoặc kéo thả tập tin vào đây</p>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-2">
                            Hỗ trợ JPG, PNG, WebP · Tối đa 5 ảnh · 5MB/ảnh
                        </p>
                    </div>

                    <input type="file" id="found-photo-upload" wire:model="photos" multiple
                        accept="image/jpeg,image/png,image/webp" class="hidden" />

                    @error('photos')
                        <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
                    @enderror
                    @error('photos.*')
                        <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
                    @enderror

                    <div wire:loading wire:target="photos"
                        class="text-xs text-slate-400 font-semibold flex items-center gap-2">
                        <x-heroicon-o-arrow-path class="animate-spin w-4 h-4 text-primary" />
                        Đang tải ảnh lên...
                    </div>

                    @if (count($photos) > 0)
                        <div class="grid grid-cols-3 gap-2 min-[480px]:grid-cols-4 sm:grid-cols-5 sm:gap-3 mt-2">
                            @foreach ($photos as $index => $photo)
                                <div class="relative group">
                                    <img src="{{ $photo->temporaryUrl() }}"
                                        class="w-full aspect-square object-cover rounded-xl border-2
                                                                            {{ $index === 0 ? 'border-primary' : 'border-slate-200' }}" />
                                    @if ($index === 0)
                                        <span
                                            class="absolute top-1 left-1 bg-primary text-white text-[9px] font-black px-1.5 py-0.5 rounded-lg">
                                            Chính
                                        </span>
                                    @endif
                                    <button type="button" wire:click="removePhoto({{ $index }})"
                                        class="absolute -top-1.5 -right-1.5 bg-rose-500 text-white rounded-full w-5 h-5
                                                                       flex items-center justify-center hover:bg-rose-600 transition-colors shadow-md z-10 text-xs font-black">
                                        &times;
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- ════ BƯỚC 3 — Xác nhận & cam kết ════ --}}
        @elseif ($step === 3)
            <div class="space-y-5 pt-4">
                <h3 class="font-extrabold text-slate-800 text-sm border-b border-slate-100 pb-2">
                    Bước 3: Xem lại thông tin & Cam kết
                </h3>

                {{-- Review card --}}
                <div class="bg-slate-50 rounded-2xl p-5 border border-slate-200 space-y-3">
                    <div class="flex items-center gap-2 border-b border-slate-100 pb-2.5 mb-4">
                        <x-heroicon-o-clipboard-document-check class="w-5 h-5 text-primary shrink-0" />
                        <h3 class="text-sm sm:text-base font-black text-slate-900">
                            Xem lại thông tin trước khi gửi
                        </h3>
                    </div>
                    <div class="grid grid-cols-2 gap-x-6 gap-y-2 text-xs">
                        <div class="col-span-2">
                            <span class="text-slate-400 font-bold">Tỉnh/Thành:</span>
                            <span class="font-extrabold text-slate-800 ml-1">
                                {{ optional($provinces->firstWhere('id', $province_id))->name ?? '—' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-400 font-bold">Ngày phát hiện:</span>
                            <span class="font-extrabold text-slate-800 ml-1">{{ $found_date }}</span>
                        </div>
                        @if ($found_time)
                            <div>
                                <span class="text-slate-400 font-bold">Giờ phát hiện:</span>
                                <span class="font-extrabold text-slate-800 ml-1">{{ $found_time }}</span>
                            </div>
                        @endif
                        <div>
                            <span class="text-slate-400 font-bold">Giới tính:</span>
                            <span class="font-extrabold text-slate-800 ml-1">
                                {{ ['MALE' => 'Nam', 'FEMALE' => 'Nữ', 'UNKNOWN' => 'Chưa rõ'][(string) $gender_estimate] ?? '' }}
                            </span>
                        </div>
                        @if ($age_estimate)
                            <div>
                                <span class="text-slate-400 font-bold">Tuổi ước tính:</span>
                                <span class="font-extrabold text-slate-800 ml-1">{{ $age_estimate }} tuổi</span>
                            </div>
                        @endif
                        @if ($special_marks)
                            <div class="col-span-2">
                                <span class="text-slate-400 font-bold">Mô tả nhận dạng:</span>
                                <span class="font-extrabold text-slate-800 ml-1">{{ $special_marks }}</span>
                            </div>
                        @endif
                        @if ($lat && $lng)
                            <div class="col-span-2">
                                <span class="text-slate-400 font-bold">Toạ độ:</span>
                                <span class="font-extrabold text-success ml-1">
                                    ✓ Đã ghim bản đồ ({{ round((float) $lat, 5) }}, {{ round((float) $lng, 5) }})
                                </span>
                            </div>
                        @endif
                        <div>
                            <span class="text-slate-400 font-bold">Số ảnh:</span>
                            <span class="font-extrabold text-slate-800 ml-1">{{ count($photos) }} ảnh</span>
                        </div>
                    </div>
                </div>

                {{-- Preview ảnh mini --}}
                @if (count($photos) > 0)
                    <div class="flex gap-2 flex-wrap">
                        @foreach ($photos as $photo)
                            <img src="{{ $photo->temporaryUrl() }}"
                                class="w-14 h-14 object-cover rounded-xl border border-slate-200" />
                        @endforeach
                    </div>
                @endif

                {{-- Cam kết pháp lý --}}
                <div class="bg-primary/5 border border-primary/20 p-4 rounded-2xl">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="agreed" class="mt-0.5 w-4 h-4 accent-primary rounded shrink-0" />
                        <span class="text-[11px] text-slate-600 leading-relaxed font-bold">
                            Tôi cam kết các thông tin khai báo trên đây là đúng sự thật, được ghi nhận từ việc trực
                            tiếp gặp/tiếp nhận em bé, và chịu trách nhiệm trước pháp luật Việt Nam về tính xác thực
                            của thông tin, theo
                            <a href="{{ route('legal.terms') }}" target="_blank" class="text-primary underline">Điều khoản Sử dụng</a>.
                        </span>
                    </label>
                    @error('agreed')
                        <p class="text-xs text-red-500 font-semibold mt-2">{{ $message }}</p>
                    @enderror
                </div>

                {{-- F-05: checkbox riêng cho Chính sách Quyền riêng tư + Bảo vệ Dữ liệu Trẻ em,
                     cùng cơ chế với create-missing-form.blade.php --}}
                <div class="bg-primary/5 border border-primary/20 p-4 rounded-2xl mt-3">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="agreedDataPolicy" class="mt-0.5 w-4 h-4 accent-primary rounded shrink-0" />
                        <span class="text-[11px] text-slate-600 leading-relaxed font-bold">
                            Tôi đã đọc và đồng ý với
                            <a href="{{ route('legal.privacy') }}" target="_blank" class="text-primary underline">Chính sách Quyền riêng tư</a>
                            và
                            <a href="{{ route('legal.child-data') }}" target="_blank" class="text-primary underline">Chính sách Bảo vệ Dữ liệu Trẻ em</a>.
                        </span>
                    </label>
                    @error('agreedDataPolicy')
                        <p class="text-xs text-red-500 font-semibold mt-2">{{ $message }}</p>
                    @enderror
                </div>

                {{-- C-01 — Checkbox cam kết giới hạn sử dụng dữ liệu --}}
                <div class="bg-blue-50 border border-blue-200 p-4 rounded-2xl mt-3">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="dataUseConsent" class="mt-0.5 w-4 h-4 accent-blue-600 rounded shrink-0" />
                        <span class="text-[11px] text-slate-600 leading-relaxed font-bold">
                            Tôi đồng ý dữ liệu chỉ được dùng cho mục đích
                            <span class="font-bold">tìm kiếm trẻ thất lạc</span>, không chia sẻ cho bên
                            thứ ba ngoài phạm vi hệ thống.
                            <a href="{{ route('legal.child-data') }}" target="_blank" class="text-blue-700 underline">Xem chính sách dữ liệu trẻ em</a>.
                        </span>
                    </label>
                    @error('dataUseConsent')
                        <p class="text-xs text-red-500 font-semibold mt-2">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        @endif

        {{-- ════ NAV BUTTONS ════ --}}
        <div class="flex items-center justify-between pt-2 border-t border-slate-100">
            @if ($step > 1)
                <button type="button" wire:click="back"
                    class="px-5 py-3 rounded-2xl border border-slate-200 text-slate-600 font-extrabold text-sm hover:bg-slate-50 transition-colors">
                    ← Quay lại
                </button>
            @else
                <a href="{{ route('found-children.index') }}"
                    class="px-5 py-3 rounded-2xl border border-slate-200 text-slate-500 font-extrabold text-sm hover:bg-slate-50 transition-colors">
                    Hủy
                </a>
            @endif

            {{-- V-02 — Chặn client-side: điểm ghim ngoài lãnh thổ VN thì không cho đi tiếp/gửi. --}}
            @if ($step < 3)
                <button type="button" wire:click="next" wire:loading.attr="disabled" wire:target="next"
                    @disabled($geoStatus === 'NOT_FOUND')
                    class="px-6 py-3 rounded-2xl bg-primary text-white font-extrabold text-sm hover:bg-primary-dark shadow-md shadow-primary/20 transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-primary">
                    <span wire:loading wire:target="next"
                        class="inline-block w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                    Tiếp theo →
                </button>
            @else
                <button type="button" wire:click="submit" wire:loading.attr="disabled" wire:target="submit"
                    @disabled($isSubmitting || $geoStatus === 'NOT_FOUND')
                    class="px-6 py-3 rounded-2xl bg-primary text-white font-extrabold text-sm hover:bg-primary-dark shadow-md shadow-primary/20 transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-primary">
                    <span wire:loading wire:target="submit"
                        class="inline-block w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                    Gửi hồ sơ
                </button>
            @endif
        </div>

    </div>
</div>

{{-- Leaflet — chỉ load ở trang này --}}
@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush
@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="{{ asset('js/map/vn-sovereignty-layer.js') }}"></script>
    {{-- V-02 — reverse-geocode nội bộ (POST /api/geo/lookup), không gọi API ngoài --}}
    <script src="{{ asset('js/map/geo-lookup.js') }}"></script>
@endpush