<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\FoundChild;
use App\Models\Photo;
use App\Models\Province;
use App\Models\UserConsent;
use App\Traits\HasIdempotency;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use HasIdempotency;
    use WithFileUploads;

    public int $step = 1;

    // ── Bước 1 — Vị trí & thời gian phát hiện ───────────────────────────────
    public string $province_id = '';
    public string $found_date = '';
    public string $found_time = '';
    public string $current_location = '';
    public string $handover_agency = '';
    public string $lat = '';
    public string $lng = '';

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
    public bool $isSubmitting = false;
    public string $idempotencyKey = '';

    public $provinces;

    // ── Mount ────────────────────────────────────────────────────────────────
    public function mount(): void
    {
        $this->authorize('create', FoundChild::class);

        $this->provinces = Province::where('is_active', 1)->orderBy('name')->get();
        $this->found_date = now()->toDateString();
        $this->idempotencyKey = $this->generateIdempotencyKey();
    }

    // ── Validate từng bước ───────────────────────────────────────────────────
    protected function rulesStep1(): array
    {
        return [
            'province_id' => ['required', 'exists:provinces,id'],
            'found_date' => ['required', 'date', 'before_or_equal:today'],
            'current_location' => ['nullable', 'string', 'max:255'],
            'handover_agency' => ['nullable', 'string', 'max:150'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    protected function messagesStep1(): array
    {
        return [
            'province_id.required' => 'Vui lòng chọn tỉnh/thành phố.',
            'found_date.required' => 'Vui lòng nhập ngày phát hiện.',
            'found_date.before_or_equal' => 'Ngày phát hiện không thể ở tương lai.',
        ];
    }

    protected function rulesStep2(): array
    {
        return [
            'gender_estimate' => ['required', 'in:MALE,FEMALE,UNKNOWN'],
            // F-05: trước đây hardcode max:120 — không khớp với form đăng tin
            // tìm trẻ (dùng config('app.child_age_limit'), đúng Luật Trẻ em
            // 2016 = dưới 16 tuổi). Đồng bộ lại theo cùng 1 nguồn config.
            'age_estimate' => ['nullable', 'integer', 'min:0', 'max:' . (config('app.child_age_limit') - 1)],
            'condition_desc' => ['nullable', 'string', 'max:2000'],
            'clothes_desc' => ['nullable', 'string', 'max:1000'],
            'special_marks' => ['required', 'string', 'min:10'],
            'photos' => ['required', 'array', 'min:1', 'max:5'],
            'photos.*' => ['file', 'image', 'max:5120', new \App\Rules\SecureImage],
        ];
    }

    protected function messagesStep2(): array
    {
        return [
            'age_estimate.max' => 'Hệ thống chỉ tiếp nhận báo tin cho trẻ dưới ' . config('app.child_age_limit') . ' tuổi theo quy định của Luật Trẻ em 2016.',
            'special_marks.required' => 'Vui lòng mô tả dấu hiệu/đặc điểm nhận dạng để gia đình dễ nhận ra bé.',
            'special_marks.min' => 'Mô tả cần ít nhất 10 ký tự.',
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
        ];
    }

    protected function messagesStep3(): array
    {
        return [
            'agreed.accepted' => 'Vui lòng xác nhận cam kết trước khi gửi hồ sơ.',
            'agreedDataPolicy.accepted' => 'Vui lòng đồng ý với Chính sách Bảo vệ Dữ liệu Trẻ em.',
        ];
    }

    // ── Navigation ───────────────────────────────────────────────────────────
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
                    'current_location' => $this->current_location ?: null,
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
        </div>

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
                            Ngày phát hiện *
                        </label>
                        <input type="date" wire:model="found_date" class="w-full px-4 py-3 border rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-bold text-slate-600
                                        {{ $errors->has('found_date') ? 'border-red-400' : 'border-slate-200' }}" />
                        @error('found_date')
                            <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">
                            Giờ phát hiện (nếu biết)
                        </label>
                        <input type="time" wire:model.blur="found_time"
                            class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-bold text-slate-600" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">
                            Cơ quan/Nơi bàn giao
                        </label>
                        <input type="text" wire:model.blur="handover_agency" placeholder="VD: Công an phường"
                            class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-medium placeholder-slate-400" />
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">
                        Vị trí hiện tại
                    </label>
                    <input type="text" wire:model.blur="current_location" placeholder="Địa chỉ, tên địa điểm..."
                        class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-medium placeholder-slate-400" />
                </div>

                {{-- Map picker --}}
                <div class="space-y-2">
                    <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                        <x-heroicon-o-map-pin class="w-4 h-4 text-slate-500" />
                        Ghim vị trí trên bản đồ (tuỳ chọn)
                    </label>
                    <p class="text-xs text-slate-400">
                        Bấm nút bên dưới để lấy vị trí hiện tại, hoặc click trực tiếp vào bản đồ để ghim tay.
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

                                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                                attribution: '© OpenStreetMap'
                                            }).addTo(this.map);

                                            if (hasSaved) {
                                                this.marker = L.marker([this.initLat, this.initLng])
                                                    .addTo(this.map)
                                                    .bindPopup('Vị trí đã chọn').openPopup();
                                            }

                                            this.map.on('click', (e) => {
                                                const { lat, lng } = e.latlng;
                                                if (this.marker) this.marker.remove();
                                                this.marker = L.marker([lat, lng])
                                                    .addTo(this.map)
                                                    .bindPopup('Vị trí đã ghim tay').openPopup();
                                                $wire.set('lat', lat.toFixed(6));
                                                $wire.set('lng', lng.toFixed(6));
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
                            Giới tính ước tính *
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

                <div class="space-y-1.5">
                    <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">
                        Mô tả tình trạng
                    </label>
                    <textarea wire:model.blur="condition_desc" rows="3"
                        placeholder="Mô tả chi tiết tình trạng sức khỏe, dấu hiệu khác thường..."
                        class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-medium resize-none placeholder-slate-400"></textarea>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">
                        Mô tả quần áo
                    </label>
                    <textarea wire:model.blur="clothes_desc" rows="2"
                        placeholder="Mô tả chi tiết quần áo, tính chất đặc biệt..."
                        class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-medium resize-none placeholder-slate-400"></textarea>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">
                        Dấu hiệu đặc biệt *
                    </label>
                    <textarea wire:model.blur="special_marks" rows="3"
                        placeholder="Ghi cụ thể: vết sẹo, nốt ruồi, cách bé nói chuyện, đồ vật mang theo... — chi tiết hơn giúp gia đình nhận ra bé nhanh hơn"
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

            @if ($step < 3)
                <button type="button" wire:click="next" wire:loading.attr="disabled" wire:target="next"
                    class="px-6 py-3 rounded-2xl bg-primary text-white font-extrabold text-sm hover:bg-primary-dark shadow-md shadow-primary/20 transition-all flex items-center gap-2">
                    <span wire:loading wire:target="next"
                        class="inline-block w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                    Tiếp theo →
                </button>
            @else
                <button type="button" wire:click="submit" wire:loading.attr="disabled" wire:target="submit"
                    @disabled($isSubmitting)
                    class="px-6 py-3 rounded-2xl bg-primary text-white font-extrabold text-sm hover:bg-primary-dark shadow-md shadow-primary/20 transition-all flex items-center gap-2">
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
@endpush