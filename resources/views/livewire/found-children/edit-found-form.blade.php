<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\FoundChild;
use App\Models\Photo;
use App\Models\Province;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use WithFileUploads;

    public FoundChild $foundChild;

    public string $province_id = '';
    public string $found_date = '';
    public string $found_time = '';
    // U-01 — ĐÃ BỎ `current_location` (free-text); vị trí chỉ đến từ map picker (lat/lng).
    public string $handover_agency = '';
    public string $lat = '';
    public string $lng = '';

    public string $gender_estimate = 'UNKNOWN';
    public string $age_estimate = '';
    public string $condition_desc = '';
    public string $clothes_desc = '';
    public string $special_marks = '';

    public array $newPhotos = [];
    public $existingPhotos;

    public $provinces;

    public function mount(FoundChild $foundChild): void
    {
        $this->foundChild = $foundChild;
        $this->provinces = Province::where('is_active', 1)->orderBy('name')->get();
        $this->existingPhotos = $foundChild->photos()->orderBy('sort_order')->get();

        $this->province_id = (string) $foundChild->province_id;
        $this->found_date = optional($foundChild->found_date)->format('Y-m-d') ?? '';
        $this->found_time = (string) ($foundChild->found_time ?? '');
        $this->handover_agency = (string) ($foundChild->handover_agency ?? '');
        $this->lat = $foundChild->lat !== null ? (string) $foundChild->lat : '';
        $this->lng = $foundChild->lng !== null ? (string) $foundChild->lng : '';
        $this->gender_estimate = $foundChild->gender_estimate ?? 'UNKNOWN';
        $this->age_estimate = $foundChild->age_estimate !== null ? (string) $foundChild->age_estimate : '';
        $this->condition_desc = (string) ($foundChild->condition_desc ?? '');
        $this->clothes_desc = (string) ($foundChild->clothes_desc ?? '');
        $this->special_marks = (string) ($foundChild->special_marks ?? '');
    }

    protected function rules(): array
    {
        return [
            'province_id' => ['required', 'exists:provinces,id'],
            'found_date' => ['required', 'date', 'before_or_equal:today'],
            'handover_agency' => ['nullable', 'string', 'max:150'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'gender_estimate' => ['required', 'in:MALE,FEMALE,UNKNOWN'],
            'age_estimate' => ['nullable', 'integer', 'min:0', 'max:120'],
            'condition_desc' => ['nullable', 'string', 'max:2000'],
            'clothes_desc' => ['nullable', 'string', 'max:1000'],
            'special_marks' => ['required', 'string', 'min:10'],
            'newPhotos' => ['nullable', 'array', 'max:5'],
            'newPhotos.*' => ['file', 'image', 'max:5120', new \App\Rules\SecureImage],
        ];
    }

    protected function messages(): array
    {
        return [
            'province_id.required' => 'Vui lòng chọn tỉnh/thành phố.',
            'found_date.required' => 'Vui lòng nhập ngày phát hiện.',
            'found_date.before_or_equal' => 'Ngày phát hiện không thể ở tương lai.',
            'special_marks.required' => 'Vui lòng mô tả dấu hiệu/đặc điểm nhận dạng.',
            'special_marks.min' => 'Mô tả cần ít nhất 10 ký tự.',
            'newPhotos.*.image' => 'Tệp tải lên phải là hình ảnh.',
            'newPhotos.*.max' => 'Mỗi ảnh tối đa 5MB.',
            'newPhotos.max' => 'Tối đa 5 ảnh mới mỗi lần.',
        ];
    }

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

    public function updatedNewPhotos(): void
    {
        foreach ($this->newPhotos as $photo) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($photo->getRealPath());
            if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'])) {
                $this->addError('newPhotos.*', 'Chỉ chấp nhận ảnh JPG, PNG, WebP thực sự.');
                $this->newPhotos = [];
                return;
            }
        }
        $this->validate([
            'newPhotos' => ['array', 'max:5'],
            'newPhotos.*' => ['file', 'image', 'max:5120', new \App\Rules\SecureImage],
        ]);
    }

    public function removeNewPhoto(int $index): void
    {
        array_splice($this->newPhotos, $index, 1);
    }

    public function setPrimary(int $photoId): void
    {
        Photo::where('model_type', FoundChild::class)
            ->where('model_id', $this->foundChild->id)
            ->update(['is_primary' => 0]);

        Photo::where('id', $photoId)->update(['is_primary' => 1]);

        $this->existingPhotos = $this->foundChild->photos()->orderBy('sort_order')->get();
    }

    public function deleteExistingPhoto(int $photoId): void
    {
        $photo = Photo::where('id', $photoId)
            ->where('model_type', FoundChild::class)
            ->where('model_id', $this->foundChild->id)
            ->first();

        if (!$photo)
            return;

        Storage::disk('public')->delete($photo->path_original);
        $wasPrimary = (bool) $photo->is_primary;
        $photo->delete();

        if ($wasPrimary) {
            $next = $this->foundChild->photos()->orderBy('sort_order')->first();
            if ($next)
                $next->update(['is_primary' => 1]);
        }

        $this->existingPhotos = $this->foundChild->photos()->orderBy('sort_order')->get();
    }

    public function save(): void
    {
        $this->validate();

        if ($this->found_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->found_date)) {
            $this->found_date = now()->toDateString();
        }

        DB::transaction(function () {
            $this->foundChild->update([
                'province_id' => (int) $this->province_id,
                'found_date' => $this->found_date,
                'found_time' => preg_match('/^\d{2}:\d{2}/', $this->found_time ?? '') ? $this->found_time : null,
                'handover_agency' => $this->handover_agency ?: null,
                'lat' => $this->lat !== '' ? (float) $this->lat : null,
                'lng' => $this->lng !== '' ? (float) $this->lng : null,
                'gender_estimate' => $this->gender_estimate,
                'age_estimate' => $this->age_estimate !== '' ? (int) $this->age_estimate : null,
                'condition_desc' => $this->condition_desc ?: null,
                'clothes_desc' => $this->clothes_desc ?: null,
                'special_marks' => $this->special_marks,
            ]);

            $startOrder = $this->foundChild->photos()->count();
            $hasAnyPhoto = $startOrder > 0;

            foreach ($this->newPhotos as $index => $photo) {
                $path = $photo->storePublicly("found-children/{$this->foundChild->id}", 'public');
                Photo::create([
                    'model_type' => FoundChild::class,
                    'model_id' => $this->foundChild->id,
                    'path_original' => $path,
                    'file_size' => $photo->getSize(),
                    'mime_type' => $photo->getMimeType(),
                    'is_primary' => !$hasAnyPhoto && $index === 0 ? 1 : 0,
                    'sort_order' => $startOrder + $index,
                ]);
            }
        });

        session()->flash('success', 'Đã lưu thay đổi hồ sơ thành công.');
        $this->redirect(route('found-children.show', $this->foundChild->id));
    }
};
?>

<div class="bg-white border border-slate-200/85 rounded-3xl p-6 sm:p-10 shadow-premium space-y-8">

    {{-- Header --}}
    <div class="flex items-center gap-3">
        <div class="bg-amber-50 text-amber-600 p-2 rounded-xl shrink-0 shadow-sm">
            <x-heroicon-o-pencil-square class="w-6 h-6" />
        </div>
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-tight">
                Sửa hồ sơ tìm được
            </h1>
            <p class="text-slate-500 text-xs sm:text-sm font-medium mt-0.5">
                Cập nhật thông tin chính xác giúp gia đình nhận diện bé nhanh hơn.
            </p>
        </div>
    </div>

    {{-- ════ Khối 1: Vị trí & thời gian ════ --}}
    <div class="space-y-5">
        <h3 class="font-extrabold text-slate-800 text-sm border-b border-slate-100 pb-2">
            Vị trí & thời gian phát hiện
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
                    <button type="button" @click="open = !open; if(open) $nextTick(() => $refs.searchEdit.focus())"
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
                            <input type="text" x-model="query" x-ref="searchEdit" placeholder="Tìm tỉnh/thành..."
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
                    Giờ phát hiện
                </label>
                <input type="time" wire:model.blur="found_time"
                    class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-bold text-slate-600" />
            </div>
            <div class="space-y-1.5">
                <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">
                    Cơ quan/Nơi bàn giao
                </label>
                <input type="text" wire:model.blur="handover_agency" placeholder="Công an, Y tế, SOS..."
                    class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-medium placeholder-slate-400" />
            </div>
        </div>

        {{-- U-01 — ĐÃ BỎ input "Vị trí hiện tại" (free-text) để form sửa không trở
        thành đường lách qua V-01/U-01. Vị trí chỉ khai báo qua map picker bên dưới. --}}

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
                        if (window._foundEditMapPickerInstance) {
                            window._foundEditMapPickerInstance.remove();
                            window._foundEditMapPickerInstance = null;
                        }
                        const hasSaved = this.initLat !== null && this.initLng !== null;
                        const center   = hasSaved ? [parseFloat(this.initLat), parseFloat(this.initLng)] : [16.0544, 108.2022];
                        const zoom     = hasSaved ? 15 : 6;

                        this.map = L.map('found-edit-map-picker').setView(center, zoom);
                        initVnSovereigntyLayer(this.map);
                        window._foundEditMapPickerInstance = this.map;

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '© OpenStreetMap'
                        }).addTo(this.map);

                        if (hasSaved) {
                            this.marker = L.marker([parseFloat(this.initLat), parseFloat(this.initLng)])
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

                {{-- [FIX] Nút GPS nằm TRÊN map — nhất quán với create & found form --}}
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

                <div id="found-edit-map-picker" class="w-full h-56 rounded-2xl border border-slate-200 z-0"></div>
            </div>

            @if ($lat && $lng)
                <p class="text-xs text-success font-bold mt-1 flex items-center gap-1.5">
                    <x-heroicon-o-check-circle class="w-4 h-4 shrink-0" />
                    <span>Đã ghim: {{ round((float) $lat, 6) }}, {{ round((float) $lng, 6) }}</span>
                </p>
            @endif
        </div>
    </div>

    {{-- ════ Khối 2: Đặc điểm nhận dạng ════ --}}
    <div class="space-y-5">
        <h3 class="font-extrabold text-slate-800 text-sm border-b border-slate-100 pb-2">
            Đặc điểm nhận dạng
        </h3>

        <div class="grid sm:grid-cols-2 gap-4">
            <div class="space-y-1.5">
                <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">
                    Giới tính ước tính
                </label>
                {{-- [FIX] Đồng nhất text "Chưa rõ" với các form khác --}}
                <select wire:model="gender_estimate"
                    class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-bold text-slate-600">
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
                    <input type="number" wire:model.blur="age_estimate" min="0" max="120" placeholder="VD: 8"
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
                placeholder="Mô tả chi tiết quần áo, màu sắc, kiểu dáng..."
                class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-medium resize-none placeholder-slate-400"></textarea>
        </div>

        <div class="space-y-1.5">
            <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">
                Dấu hiệu đặc biệt *
            </label>
            <textarea wire:model.blur="special_marks" rows="3"
                placeholder="Ghi cụ thể: vết sẹo, nốt ruồi, cách bé nói chuyện, đồ vật mang theo..." class="w-full px-4 py-3 border rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-medium resize-none placeholder-slate-400
                    {{ $errors->has('special_marks') ? 'border-red-400' : 'border-slate-200' }}"></textarea>
            @error('special_marks')
                <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- ════ Khối 3: Ảnh ════ --}}
    <div class="space-y-4">
        <h3 class="font-extrabold text-slate-800 text-sm border-b border-slate-100 pb-2">
            Ảnh hồ sơ
        </h3>

        {{-- Ảnh hiện có --}}
        @if ($existingPhotos->isNotEmpty())
            <div class="grid grid-cols-3 sm:grid-cols-5 gap-3">
                @foreach ($existingPhotos as $photo)
                    <div class="relative group">
                        <img src="{{ Storage::url($photo->path_original) }}" class="w-full aspect-square object-cover rounded-xl border-2
                                                {{ $photo->is_primary ? 'border-primary' : 'border-slate-200' }}" />
                        @if ($photo->is_primary)
                            <span
                                class="absolute top-1 left-1 bg-primary text-white text-[9px] font-black px-1.5 py-0.5 rounded-lg">
                                Chính
                            </span>
                        @else
                            <button type="button" wire:click="setPrimary({{ $photo->id }})"
                                class="absolute top-1 left-1 bg-white/90 text-slate-500 text-[9px] font-bold px-1.5 py-0.5 rounded-lg
                                                               opacity-0 group-hover:opacity-100 transition-opacity hover:text-primary">
                                Đặt chính
                            </button>
                        @endif
                        <button type="button" wire:click="deleteExistingPhoto({{ $photo->id }})"
                            wire:confirm="Xoá ảnh này khỏi hồ sơ?"
                            class="absolute -top-1.5 -right-1.5 bg-rose-500 text-white rounded-full w-5 h-5
                                                   flex items-center justify-center hover:bg-rose-600 transition-colors shadow-md z-10 text-xs font-black">
                            &times;
                        </button>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-xs text-slate-400 font-semibold italic">Hồ sơ chưa có ảnh nào.</p>
        @endif

        {{-- Upload ảnh mới --}}
        <div class="space-y-2 pt-2">
            <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">
                Thêm ảnh mới
            </label>
            <div class="border-2 border-dashed border-slate-200 hover:border-primary bg-slate-50 hover:bg-white
                        rounded-3xl p-6 text-center transition-all duration-300 cursor-pointer"
                onclick="document.getElementById('found-edit-photo-upload').click()">
                <div
                    class="w-10 h-10 bg-primary/10 text-primary flex items-center justify-center rounded-2xl mx-auto mb-2 shadow-sm">
                    <x-heroicon-o-camera class="w-5 h-5" />
                </div>
                <p class="text-xs font-black text-primary">Ấn để chọn thêm ảnh</p>
                <p class="text-xs text-slate-400 font-medium mt-1">hoặc kéo thả vào đây</p>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-1">
                    JPG, PNG, WebP · 5MB/ảnh
                </p>
            </div>
            <input type="file" id="found-edit-photo-upload" wire:model="newPhotos" multiple
                accept="image/jpeg,image/png,image/webp" class="hidden" />
        </div>

        @error('newPhotos.*')
            <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
        @enderror
        @error('newPhotos')
            <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
        @enderror

        <div wire:loading wire:target="newPhotos" class="text-xs text-slate-400 font-semibold flex items-center gap-2">
            <x-heroicon-o-arrow-path class="animate-spin w-4 h-4 text-primary" />
            Đang tải ảnh lên...
        </div>

        @if (count($newPhotos) > 0)
            <div class="grid grid-cols-3 gap-2 min-[480px]:grid-cols-4 sm:grid-cols-5 sm:gap-3 mt-1">
                @foreach ($newPhotos as $index => $photo)
                    <div class="relative group">
                        <img src="{{ $photo->temporaryUrl() }}"
                            class="w-full aspect-square object-cover rounded-xl border-2 border-emerald-300" />
                        <span
                            class="absolute top-1 left-1 bg-emerald-500 text-white text-[9px] font-black px-1.5 py-0.5 rounded-lg">
                            Mới
                        </span>
                        <button type="button" wire:click="removeNewPhoto({{ $index }})"
                            class="absolute -top-1.5 -right-1.5 bg-rose-500 text-white rounded-full w-5 h-5
                                                   flex items-center justify-center hover:bg-rose-600 transition-colors shadow-md z-10 text-xs font-black">
                            &times;
                        </button>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ════ NAV BUTTONS ════ --}}
    <div class="flex items-center justify-between pt-2 border-t border-slate-100">
        <a href="{{ route('found-children.show', $foundChild) }}"
            class="px-5 py-3 rounded-2xl border border-slate-200 text-slate-500 font-extrabold text-sm hover:bg-slate-50 transition-colors">
            Hủy
        </a>
        <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
            class="px-6 py-3 rounded-2xl bg-primary text-white font-extrabold text-sm hover:bg-primary-dark shadow-md shadow-primary/20 transition-all flex items-center gap-2">
            <span wire:loading wire:target="save"
                class="inline-block w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
            Lưu thay đổi
        </button>
    </div>
</div>

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush
@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="{{ asset('js/map/vn-sovereignty-layer.js') }}"></script>
@endpush
