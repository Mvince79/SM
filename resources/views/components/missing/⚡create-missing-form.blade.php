<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\MissingChild;
use App\Models\Photo;
use App\Models\Province;
use App\Jobs\ProcessPhotoJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

new class extends Component {
    use WithFileUploads;

    public int $step = 1;

    // ── Bước 1 — Thông tin cơ bản ─────────────────────────────────────────
    public string $full_name = '';
    public string $dob = '';
    public string $age_estimate = '';
    public string $gender = 'MALE';
    public string $missing_date = '';
    public string $missing_time = '';
    public string $province_id = '';
    public string $district = '';
    public string $ward = '';
    public string $address_detail = '';
    public string $lat = '';
    public string $lng = '';
    public string $circumstances = '';
    public string $urgency = 'urgent';

    // ── Bước 2 — Đặc điểm & ảnh ──────────────────────────────────────────
    public string $skin_tone = '';
    public string $height_cm = '';
    public string $weight_kg = '';
    public string $clothes_desc = '';
    public string $clothes_color = '';
    public string $special_marks = '';
    public array $photos = [];

    // ── Bước 3 — Liên hệ ─────────────────────────────────────────────────
    public string $contact_name = '';
    public string $contact_relation = 'Mẹ';
    public string $contact_phone = '';
    public string $contact_address = '';
    public bool $agreed = false;

    public $provinces;

    // ── Mount ─────────────────────────────────────────────────────────────
    public function mount(): void
    {
        $this->provinces = Province::where('is_active', 1)->orderBy('name')->get();
        $this->missing_date = now()->toDateString();
    }

    // ── Validate từng bước ────────────────────────────────────────────────
    protected function rulesStep1(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:100'],
            'gender' => ['required', 'in:MALE,FEMALE,UNKNOWN'],
            'missing_date' => ['required', 'date', 'before_or_equal:today'],
            'province_id' => ['required', 'exists:provinces,id'],
            'dob' => ['nullable', 'date', 'before:today'],
            'age_estimate' => ['nullable', 'integer', 'min:0', 'max:18'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    protected function messagesStep1(): array
    {
        return [
            'full_name.required' => 'Vui lòng nhập họ và tên của bé.',
            'gender.required' => 'Vui lòng chọn giới tính.',
            'missing_date.required' => 'Vui lòng nhập ngày mất tích.',
            'missing_date.before_or_equal' => 'Ngày mất tích không thể ở tương lai.',
            'province_id.required' => 'Vui lòng chọn tỉnh/thành phố.',
        ];
    }

    protected function rulesStep2(): array
    {
        return [
            'special_marks' => ['required', 'string', 'min:20'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['file', 'max:5120'],
        ];
    }

    protected function messagesStep2(): array
    {
        return [
            'special_marks.required' => 'Vui lòng mô tả đặc điểm nhận dạng.',
            'special_marks.min' => 'Mô tả cần ít nhất 20 ký tự.',
            'photos.*.max' => 'Mỗi ảnh tối đa 5MB.',
            'photos.max' => 'Tối đa 5 ảnh.',
        ];
    }

    protected function rulesStep3(): array
    {
        return [
            'contact_name' => ['required', 'string', 'max:100'],
            'contact_phone' => ['required', 'string', 'regex:/^(0|\+84)[0-9]{8,10}$/'],
            'agreed' => ['accepted'],
        ];
    }

    protected function messagesStep3(): array
    {
        return [
            'contact_name.required' => 'Vui lòng nhập tên người liên hệ.',
            'contact_phone.required' => 'Vui lòng nhập số điện thoại.',
            'contact_phone.regex' => 'Số điện thoại không hợp lệ.',
            'agreed.accepted' => 'Vui lòng đồng ý cam kết trước khi gửi.',
        ];
    }

    // ── Navigation ────────────────────────────────────────────────────────
    public function next(): void
    {
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

    // ── Guard: nếu missing_time bị gán nhầm text → clear về '' ──────────
    public function updatedMissingTime(): void
    {
        if ($this->missing_time && !preg_match('/^\d{2}:\d{2}$/', $this->missing_time)) {
            $this->missing_time = '';
        }
    }

    // ── Guard: dob phải là định dạng ngày hợp lệ (YYYY-MM-DD) ────────────
    public function updatedDob(): void
    {
        if ($this->dob && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->dob)) {
            $this->dob = '';
        }
    }

    // ── Guard: missing_date phải là định dạng ngày hợp lệ ───────────────
    public function updatedMissingDate(): void
    {
        if ($this->missing_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->missing_date)) {
            $this->missing_date = '';
        }
    }

    // ── Leaflet bridge: Alpine → Livewire ─────────────────────────
    // setLocation() giữ nguyên — nhưng view sẽ dùng wire:ignore + x-init
    // thay vì x-data init() để map không bị destroy khi Livewire re-render.
    public function setLocation(float $lat, float $lng): void
    {
        $this->lat = (string) round($lat, 7);
        $this->lng = (string) round($lng, 7);
    }

    // ── Upload ảnh ────────────────────────────────────────────────────────
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
                'photos.*' => ['file', 'max:5120'],
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

    // ── Generate case_code ────────────────────────────────────────────────
    protected function generateCaseCode(): string
    {
        $year = now()->year;
        $count = MissingChild::whereYear('created_at', $year)->count() + 1;
        return 'MC-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    // ── [FIX 3] Submit — thêm đầy đủ skin_tone, height_cm, weight_kg ──────
    public function submit(): void
    {
        $this->validate($this->rulesStep3(), $this->messagesStep3());

        // ── Sanitize: đảm bảo các field date không chứa text bất hợp lệ ───
        if ($this->dob && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->dob)) {
            $this->dob = '';
        }
        if ($this->missing_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->missing_date)) {
            $this->missing_date = now()->toDateString();
        }

        $case = DB::transaction(function () {
            $case = MissingChild::create([
                'user_id'          => Auth::id(),
                'case_code'        => $this->generateCaseCode(),
                'full_name'        => $this->full_name,
                'dob'              => $this->dob ?: null,
                'age_estimate'     => $this->age_estimate !== '' ? (int) $this->age_estimate : null,
                'gender'           => $this->gender,
                'skin_tone'        => $this->skin_tone ?: null,
                'height_cm'        => $this->height_cm !== '' ? (int) $this->height_cm : null,
                'weight_kg'        => $this->weight_kg !== '' ? (float) $this->weight_kg : null,
                'province_id'      => (int) $this->province_id,
                'district'         => $this->district ?: null,
                'ward'             => $this->ward ?: null,
                'address_detail'   => $this->address_detail ?: null,
                'lat'              => $this->lat !== '' ? (float) $this->lat : null,
                'lng'              => $this->lng !== '' ? (float) $this->lng : null,
                'missing_date'     => $this->missing_date ?: now()->toDateString(),
                'missing_time'     => preg_match('/^\d{2}:\d{2}/', $this->missing_time ?? '') ? $this->missing_time : null,
                'clothes_desc'     => $this->clothes_desc ?: null,
                'clothes_color'    => $this->clothes_color ?: null,
                'special_marks'    => $this->special_marks,
                'circumstances'    => $this->circumstances ?: null,
                'contact_name'     => $this->contact_name,
                'contact_phone'    => $this->contact_phone,
                'contact_relation' => $this->contact_relation,
                'status'           => 'PENDING',
            ]);

            foreach ($this->photos as $index => $photo) {
                $path = $photo->storePublicly("missing/{$case->id}", 'public');
                Photo::create([
                    'model_type'     => MissingChild::class,
                    'model_id'       => $case->id,
                    'path_original'  => $path,
                    'file_size'      => $photo->getSize(),
                    'mime_type'      => $photo->getMimeType(),
                    'is_primary'     => $index === 0 ? 1 : 0,
                    'sort_order'     => $index,
                ]);
                // [FIX 2] Dispatch job — job sẽ tạo path_thumb & path_blurred
                ProcessPhotoJob::dispatch($case->id, $path);
            }

            return $case;
        });

        session()->flash('success', 'Đăng tin thành công! Hồ sơ đang chờ xét duyệt.');
        $this->redirect(route('missing.detail', (int) $case->id));
    }
};
?>

<div>
    <div class="bg-white border border-slate-200/85 rounded-3xl p-6 sm:p-10 shadow-premium space-y-8">

        <div class="text-center space-y-2.5">
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                📢 Báo tin khẩn cấp — Giúp con về nhà
            </h1>
            <p class="text-slate-500 text-xs sm:text-sm max-w-md mx-auto font-medium">
                Bố mẹ vui lòng bình tĩnh và điền đầy đủ các thông tin. Chúng tôi sẽ bảo mật và kiểm chứng nhanh chóng
                nhất trong 30 phút.
            </p>
        </div>

        {{-- Step Progress --}}
        <div class="relative flex items-center justify-between max-w-md mx-auto px-4 pt-2">
            <div class="absolute top-4 left-0 right-0 h-0.5 bg-slate-100 z-0"></div>
            <div class="absolute top-4 left-0 h-0.5 bg-primary z-0 transition-all duration-500"
                style="width: {{ (($step - 1) / 2) * 100 }}%"></div>

            @foreach ([1 => 'Thông tin bé', 2 => 'Đặc điểm & Ảnh', 3 => 'Liên hệ'] as $s => $label)
                <div class="relative z-10 flex flex-col items-center gap-1.5">
                    <div
                        class="w-8 h-8 rounded-full font-extrabold text-xs flex items-center justify-center transition-colors duration-500
                    {{ $step > $s
                        ? 'bg-success text-white shadow-md shadow-success/20'
                        : ($step === $s
                            ? 'bg-primary text-white shadow-md shadow-primary/20'
                            : 'bg-slate-100 text-slate-400') }}">
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

        {{-- ════ BƯỚC 1 ════ --}}
        @if ($step === 1)
            <div class="space-y-5 pt-4">
                <h3 class="font-extrabold text-slate-800 text-sm border-b border-slate-100 pb-2">
                    Bước 1: Thông tin cơ bản của trẻ
                </h3>

                {{-- Tên + Tuổi --}}
                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Họ và tên của con
                            *</label>
                        <input type="text" wire:model.blur="full_name" placeholder="Ví dụ: Nguyễn Văn An"
                            class="w-full px-4 py-3 border rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent bg-slate-50 focus:bg-white transition-all font-medium placeholder-slate-400
                                  {{ $errors->has('full_name') ? 'border-red-400' : 'border-slate-200' }}" />
                        @error('full_name')
                            <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Tuổi (hoặc năm
                            sinh) *</label>
                        <input type="number" wire:model.blur="age_estimate" placeholder="Ví dụ: 8" min="0"
                            max="18"
                            class="w-full px-4 py-3 border rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent bg-slate-50 focus:bg-white transition-all font-medium placeholder-slate-400
                                  {{ $errors->has('age_estimate') ? 'border-red-400' : 'border-slate-200' }}" />
                        @error('age_estimate')
                            <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- DOB + Giới tính --}}
                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Ngày sinh (nếu
                            biết)</label>
                        <input type="date" wire:model="dob"
                            class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-medium" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Giới tính
                            *</label>
                        <select wire:model="gender"
                            class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-bold text-slate-600">
                            <option value="MALE">Nam</option>
                            <option value="FEMALE">Nữ</option>
                            <option value="UNKNOWN">Chưa rõ</option>
                        </select>
                        @error('gender')
                            <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Ngày mất tích + Giờ --}}
                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Ngày mất tích
                            *</label>
                        <input type="date" wire:model="missing_date"
                            class="w-full px-4 py-3 border rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-bold text-slate-600
                                  {{ $errors->has('missing_date') ? 'border-red-400' : 'border-slate-200' }}" />
                        @error('missing_date')
                            <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Giờ mất tích (nếu
                            biết)</label>
                        <input type="time" wire:model.blur="missing_time"
                            class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-bold text-slate-600" />
                    </div>
                </div>

                {{-- Tỉnh / Huyện / Xã --}}
                <div class="grid sm:grid-cols-3 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Tỉnh/Thành phố
                            *</label>
                        <select wire:model="province_id"
                            class="w-full px-4 py-3 border rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-bold text-slate-600
                                   {{ $errors->has('province_id') ? 'border-red-400' : 'border-slate-200' }}">
                            <option value="">Chọn tỉnh...</option>
                            @foreach ($provinces as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                        @error('province_id')
                            <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Quận/Huyện</label>
                        <input type="text" wire:model.blur="district" placeholder="Quận 6"
                            class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-medium placeholder-slate-400" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Phường/Xã</label>
                        <input type="text" wire:model.blur="ward" placeholder="Phường 2"
                            class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-medium placeholder-slate-400" />
                    </div>
                </div>

                {{-- Địa chỉ + Hoàn cảnh --}}
                <div class="space-y-1.5">
                    <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Địa chỉ cụ thể nơi mất
                        tích</label>
                    <input type="text" wire:model.blur="address_detail" placeholder="Ví dụ: Chợ Bình Tây, Quận 6, TP.HCM"
                        class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-medium placeholder-slate-400" />
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Hoàn cảnh mất
                        tích</label>
                    <textarea wire:model.blur="circumstances" rows="2" placeholder="Mô tả chi tiết hoàn cảnh bé bị mất tích..."
                        class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-medium resize-none placeholder-slate-400"></textarea>
                </div>

                {{--
                    [FIX 1] Leaflet Map Picker — dùng wire:ignore để Livewire không
                    destroy/re-render div này khi re-render component sau setLocation().
                    Alpine x-init chạy 1 lần duy nhất khi mount, map không bị trắng.
                    Dùng $wire.setLocation() qua Alpine để gửi tọa độ về Livewire.
                    Hiển thị lat/lng đã chọn qua span riêng bên ngoài wire:ignore.
                --}}
                <div class="space-y-1.5">
                    <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">
                        📍 Ghim vị trí trên bản đồ (tuỳ chọn)
                    </label>
                    <p class="text-xs text-slate-400">Click vào bản đồ để ghim vị trí bé bị mất tích</p>

                    {{-- wire:ignore ngăn Livewire re-render div map → map không bị trắng --}}
                    <div wire:ignore
                        x-data="{}"
                        x-init="
                            $nextTick(() => {
                                if (window._mapPickerInstance) {
                                    window._mapPickerInstance.remove();
                                    window._mapPickerInstance = null;
                                }
                                const map = L.map('map-picker').setView([16.0544, 108.2022], 6);
                                window._mapPickerInstance = map;
                                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                    attribution: '© OpenStreetMap'
                                }).addTo(map);
                                let marker = null;
                                map.on('click', (e) => {
                                    const { lat, lng } = e.latlng;
                                    if (marker) marker.remove();
                                    marker = L.marker([lat, lng]).addTo(map)
                                        .bindPopup('Vị trí đã chọn').openPopup();
                                    $wire.setLocation(lat, lng);
                                });
                            });
                        ">
                        <div id="map-picker" class="w-full h-56 rounded-2xl border border-slate-200 z-0 mt-1"></div>
                    </div>

                    {{-- Phần hiển thị lat/lng đặt ngoài wire:ignore để Livewire update được --}}
                    @if ($lat && $lng)
                        <p class="text-xs text-success font-bold mt-1">
                            ✓ Đã ghim: {{ $lat }}, {{ $lng }}
                        </p>
                    @endif
                </div>
            </div>

            {{-- ════ BƯỚC 2 ════ --}}
        @elseif($step === 2)
            <div class="space-y-5 pt-4">
                <h3 class="font-extrabold text-slate-800 text-sm border-b border-slate-100 pb-2">
                    Bước 2: Đặc điểm nhận dạng & Tải ảnh
                </h3>

                {{-- Upload ảnh --}}
                <div class="space-y-2">
                    <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">
                        Ảnh đại diện chân dung rõ mặt của con
                    </label>
                    <div class="border-2 border-dashed border-slate-200 hover:border-primary bg-slate-50 hover:bg-white rounded-3xl p-8 text-center transition-all duration-300 cursor-pointer"
                        onclick="document.getElementById('photo-upload').click()">
                        <div class="space-y-2.5">
                            <div
                                class="w-14 h-14 bg-primary/10 text-primary text-2xl flex items-center justify-center rounded-2xl mx-auto shadow-sm">
                                📷</div>
                            <div>
                                <span class="text-xs font-black text-primary">Ấn để chọn ảnh</span>
                                <span class="text-xs text-slate-400 font-medium"> hoặc kéo thả tập tin vào đây</span>
                            </div>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">
                                Hỗ trợ JPG, PNG, WebP · Tối đa 5 ảnh · 5MB/ảnh
                            </p>
                        </div>
                        <input type="file" id="photo-upload" wire:model="photos" multiple
                            accept="image/jpeg,image/png,image/webp" class="hidden" />
                    </div>

                    @error('photos.*')
                        <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
                    @enderror
                    @error('photos')
                        <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
                    @enderror

                    <div wire:loading wire:target="photos"
                        class="text-xs text-slate-400 font-semibold flex items-center gap-2">
                        <x-heroicon-o-arrow-path class="animate-spin w-4 h-4 text-primary" />
                        Đang tải ảnh lên...
                    </div>

                    @if (count($photos) > 0)
                        <div class="grid grid-cols-5 gap-3 mt-3">
                            @foreach ($photos as $index => $photo)
                                <div class="relative group">
                                    <img src="{{ $photo->temporaryUrl() }}"
                                        class="w-full aspect-square object-cover rounded-xl border border-slate-200" />
                                    @if ($index === 0)
                                        <span
                                            class="absolute top-1 left-1 bg-primary text-white text-[9px] font-black px-1.5 py-0.5 rounded-lg">Chính</span>
                                    @endif
                                    <button type="button" wire:click="removePhoto({{ $index }})"
                                        class="absolute top-1 right-1 w-5 h-5 bg-red-500 text-white rounded-full text-xs flex items-center justify-center font-black hover:bg-red-600 opacity-0 group-hover:opacity-100 transition-opacity">×</button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Màu da + Chiều cao + Cân nặng --}}
                <div class="grid grid-cols-3 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Màu da</label>
                        <select wire:model="skin_tone"
                            class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-bold text-slate-600">
                            <option value="">Chọn...</option>
                            <option value="LIGHT">Sáng</option>
                            <option value="MEDIUM">Trung bình</option>
                            <option value="DARK">Ngăm</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Chiều cao
                            (cm)</label>
                        <input type="number" wire:model.blur="height_cm" min="50" max="200"
                            placeholder="120"
                            class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-medium placeholder-slate-400" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Cân nặng
                            (kg)</label>
                        <input type="number" wire:model.blur="weight_kg" min="5" max="100" step="0.1"
                            placeholder="22"
                            class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-medium placeholder-slate-400" />
                    </div>
                </div>

                {{-- Trang phục + Màu --}}
                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Trang phục khi bị
                            lạc mất</label>
                        <input type="text" wire:model.blur="clothes_desc"
                            placeholder="Ví dụ: Áo thun xanh lá sọc trắng, quần đen"
                            class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-medium placeholder-slate-400" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Màu quần áo chủ
                            đạo</label>
                        <input type="text" wire:model.blur="clothes_color" placeholder="Xanh lá, đen"
                            class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-medium placeholder-slate-400" />
                    </div>
                </div>

                {{-- Đặc điểm nhận dạng --}}
                <div class="space-y-1.5">
                    <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">
                        Mô tả đặc điểm nhận dạng chi tiết *
                    </label>
                    <textarea rows="4" wire:model.blur="special_marks"
                        placeholder="Ghi cụ thể các chi tiết khác (Ví dụ: Bé nói giọng miền Nam, có vết sẹo nhỏ ở chân mày phải, nhút nhát khi gặp người lạ...)"
                        class="w-full px-4 py-3 border rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-medium resize-none placeholder-slate-400
                                 {{ $errors->has('special_marks') ? 'border-red-400' : 'border-slate-200' }}"></textarea>
                    @error('special_marks')
                        <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- ════ BƯỚC 3 ════ --}}
        @elseif($step === 3)
            <div class="space-y-5 pt-4">
                <h3 class="font-extrabold text-slate-800 text-sm border-b border-slate-100 pb-2">
                    Bước 3: Thông tin liên hệ của gia đình
                </h3>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Họ tên người báo
                            tin *</label>
                        <input type="text" wire:model.blur="contact_name" placeholder="Ví dụ: Nguyễn Văn Bình"
                            class="w-full px-4 py-3 border rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-medium placeholder-slate-400
                                  {{ $errors->has('contact_name') ? 'border-red-400' : 'border-slate-200' }}" />
                        @error('contact_name')
                            <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Mối quan hệ với
                            trẻ</label>
                        <select wire:model="contact_relation"
                            class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-bold text-slate-600">
                            <option>Cha</option>
                            <option>Mẹ</option>
                            <option>Ông/Bà</option>
                            <option>Người giám hộ hợp pháp</option>
                            <option>Người phát hiện trẻ đi lạc</option>
                        </select>
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Số điện thoại
                            liên lạc *</label>
                        <input type="tel" wire:model.blur="contact_phone" placeholder="Ví dụ: 0905 123 456"
                            class="w-full px-4 py-3 border rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-medium placeholder-slate-400
                                  {{ $errors->has('contact_phone') ? 'border-red-400' : 'border-slate-200' }}" />
                        @error('contact_phone')
                            <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Địa chỉ liên hệ
                            hiện tại</label>
                        <input type="text" wire:model.blur="contact_address" placeholder="Ví dụ: 123 Lê Lợi, Đà Nẵng"
                            class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-medium placeholder-slate-400" />
                    </div>
                </div>

                {{-- Preview toàn bộ thông tin --}}
                <div class="bg-slate-50 rounded-2xl p-5 border border-slate-200 space-y-3">
                    <h4 class="font-extrabold text-slate-700 text-sm">📋 Xem lại thông tin trước khi gửi</h4>
                    <div class="grid grid-cols-2 gap-x-6 gap-y-2 text-xs">
                        <div><span class="text-slate-400 font-bold">Tên bé:</span> <span
                                class="font-extrabold text-slate-800">{{ $full_name }}</span></div>
                        <div><span class="text-slate-400 font-bold">Giới tính:</span> <span
                                class="font-extrabold text-slate-800">{{ ['MALE' => 'Nam', 'FEMALE' => 'Nữ', 'UNKNOWN' => 'Chưa rõ'][(string)$gender] ?? '' }}</span>
                        </div>
                        @if ($age_estimate)
                            <div><span class="text-slate-400 font-bold">Tuổi:</span> <span
                                    class="font-extrabold text-slate-800">{{ $age_estimate }} tuổi</span></div>
                        @endif
                        <div><span class="text-slate-400 font-bold">Ngày mất tích:</span> <span
                                class="font-extrabold text-slate-800">{{ $missing_date }}</span></div>
                        <div class="col-span-2"><span class="text-slate-400 font-bold">Khu vực:</span> <span
                                class="font-extrabold text-slate-800">{{ collect([$ward, $district, $provinces->firstWhere('id', $province_id)?->name])->filter()->implode(', ') }}</span>
                        </div>
                        @if ($lat && $lng)
                            <div class="col-span-2"><span class="text-slate-400 font-bold">Toạ độ:</span> <span
                                    class="font-extrabold text-success">✓ Đã ghim bản đồ ({{ $lat }},
                                    {{ $lng }})</span></div>
                        @endif
                        @if ($clothes_desc)
                            <div class="col-span-2"><span class="text-slate-400 font-bold">Trang phục:</span> <span
                                    class="font-extrabold text-slate-800">{{ $clothes_desc }}</span></div>
                        @endif
                        <div class="col-span-2"><span class="text-slate-400 font-bold">Số ảnh:</span> <span
                                class="font-extrabold text-slate-800">{{ count($photos) }} ảnh</span></div>
                    </div>
                </div>

                {{-- Cam kết --}}
                <div class="bg-primary/5 border border-primary/20 p-4 rounded-2xl">
                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" wire:model="agreed" class="mt-1 accent-primary" />
                        <span class="text-[11px] text-slate-600 leading-relaxed font-bold">
                            Tôi hoàn toàn cam kết các thông tin khai báo trên đây là hoàn toàn đúng sự thật và chịu mọi
                            trách nhiệm trước pháp luật Việt Nam về tính xác thực của thông tin. Tôi đồng ý để ban quản
                            trị TìmTrẻ.vn liên hệ xác minh.
                        </span>
                    </label>
                    @error('agreed')
                        <p class="text-xs text-red-500 font-semibold mt-2">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        @endif

        {{-- Nút điều hướng --}}
        <div class="flex items-center justify-between pt-4 border-t border-slate-100">
            @if ($step > 1)
                <button type="button" wire:click="back"
                    class="text-slate-500 hover:text-slate-800 text-sm font-bold py-2.5 px-4 rounded-xl border border-slate-200 hover:bg-slate-50 transition-colors">
                    Quay lại
                </button>
            @else
                <span></span>
            @endif

            <span class="text-xs text-slate-400 font-bold">Bước {{ $step }} trên 3</span>

            @if ($step < 3)
                <button type="button" wire:click="next"
                    class="bg-primary text-white font-extrabold text-sm px-8 py-3 rounded-xl hover:bg-primary-dark transition-all shadow-md shadow-primary/10">
                    Tiếp theo
                </button>
            @else
                <button type="button" wire:click="submit" wire:loading.attr="disabled"
                    class="bg-success text-white font-extrabold text-sm px-8 py-3 rounded-xl hover:bg-success-dark transition-all shadow-md shadow-success/10 disabled:opacity-60">
                    <span wire:loading.remove wire:target="submit">Gửi báo tin khẩn</span>
                    <span wire:loading wire:target="submit">⏳ Đang xử lý...</span>
                </button>
            @endif
        </div>

    </div>

    {{-- Leaflet — chỉ load ở trang này --}}
    @push('head')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endpush
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @endpush
</div>