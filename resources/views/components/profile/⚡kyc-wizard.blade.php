<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\KycVerification;
use App\Jobs\ProcessKycJob;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithFileUploads;

    public int $step = 1;

    // ── Bước 1 — Loại giấy tờ ────────────────────────────────────────────
    public string $id_type = '';

    // ── Bước 2 — Ảnh 2 mặt ───────────────────────────────────────────────
    public string $id_number = '';
    public $front_image = null;
    public $back_image = null;

    // ── Bước 3 — Selfie ──────────────────────────────────────────────────
    public $selfie = null;

    public bool $selfieCapturedByCamera = false;

    public ?KycVerification $kyc = null;

    public function mount(): void
    {
        $this->kyc = Auth::user()->kycVerification;
    }

    protected function rulesStep1(): array
    {
        return [
            'id_type' => ['required', 'in:CMND_9,CCCD_12'],
        ];
    }

    protected function messagesStep1(): array
    {
        return [
            'id_type.required' => 'Vui lòng chọn loại giấy tờ.',
        ];
    }

    protected function rulesStep2(): array
    {
        return [
            'id_number' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if ($this->id_type === 'CMND_9' && !preg_match('/^\d{9}$/', $value)) {
                        $fail('CMND phải gồm đúng 9 chữ số.');
                    }
                    if ($this->id_type === 'CCCD_12' && !preg_match('/^\d{12}$/', $value)) {
                        $fail('CCCD phải gồm đúng 12 chữ số.');
                    }
                },
            ],
            'front_image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120', new \App\Rules\SecureImage(['image/jpeg', 'image/png'])],
            'back_image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120', new \App\Rules\SecureImage(['image/jpeg', 'image/png'])],
        ];
    }

    protected function messagesStep2(): array
    {
        return [
            'id_number.required' => 'Vui lòng nhập số giấy tờ.',
            'front_image.required' => 'Vui lòng chụp/tải ảnh mặt trước.',
            'back_image.required' => 'Vui lòng chụp/tải ảnh mặt sau.',
            '*.image' => 'File phải là hình ảnh.',
            '*.max' => 'Dung lượng ảnh tối đa 5MB.',
        ];
    }

    protected function rulesStep3(): array
    {
        return [
            'selfie' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120', new \App\Rules\SecureImage(['image/jpeg', 'image/png'])],
            'selfieCapturedByCamera' => ['accepted'],
        ];
    }

    protected function messagesStep3(): array
    {
        return [
            'selfie.required' => 'Vui lòng chụp ảnh selfie.',
            'selfieCapturedByCamera.accepted' => 'Ảnh selfie phải được chụp trực tiếp bằng camera.',
            '*.image' => 'File phải là hình ảnh.',
            '*.max' => 'Dung lượng ảnh tối đa 5MB.',
        ];
    }

    public function selectIdType(string $type): void
    {
        $this->id_type = $type;
    }

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

    public function submit(): void
    {
        $user = Auth::user();

        if (in_array($user->kyc_status, ['PENDING', 'VERIFIED'], true)) {
            $this->addError('form', $user->kyc_status === 'PENDING'
                ? 'Hồ sơ KYC của bạn đang chờ xét duyệt, vui lòng đợi.'
                : 'Tài khoản của bạn đã được xác thực KYC.');
            return;
        }

        $this->validate($this->rulesStep3(), $this->messagesStep3());

        $frontPath = $this->front_image->store("kyc/{$user->id}", 'private');
        $backPath = $this->back_image->store("kyc/{$user->id}", 'private');
        $selfiePath = $this->selfie->store("kyc/{$user->id}", 'private');

        $this->kyc = KycVerification::create([
            'user_id' => $user->id,
            'id_type' => $this->id_type,
            'id_number' => $this->id_number,
            'front_image_path' => $frontPath,
            'back_image_path' => $backPath,
            'selfie_path' => $selfiePath,
            'status' => 'PENDING',
        ]);

        $user->forceFill(['kyc_status' => 'PENDING'])->save();

        ProcessKycJob::dispatch($this->kyc->id);

        $this->reset(['step', 'id_type', 'id_number', 'front_image', 'back_image', 'selfie', 'selfieCapturedByCamera']);
    }
};
?>

<div class="relative bg-white border border-slate-200/85 rounded-3xl p-6 sm:p-10 shadow-premium space-y-8 max-w-xl mx-auto">

    {{-- Overlay phủ toàn bộ form khi đang gửi hồ sơ (submit chạy đồng bộ, có thể mất vài giây do OCR/face-match) --}}
    <div wire:loading wire:target="submit" wire:loading.flex
        class="hidden absolute inset-0 z-20 rounded-3xl bg-white/90 backdrop-blur-sm flex-col items-center justify-center gap-3">
        <div class="w-10 h-10 border-4 border-primary/20 border-t-primary rounded-full animate-spin"></div>
        <p class="text-sm font-black text-slate-700">Đang xử lý hồ sơ KYC...</p>
        <p class="text-xs text-slate-400 font-medium">Vui lòng đợi trong giây lát, không tắt trang này.</p>
    </div>

    <div class="text-center space-y-1.5">
        <div class="inline-flex bg-primary/10 text-primary p-3 rounded-2xl mb-1">
            <x-heroicon-o-identification class="w-6 h-6" />
        </div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Xác thực giấy tờ tuỳ thân (KYC)</h1>
        <p class="text-slate-500 text-xs sm:text-sm font-medium">
            Cần thiết để đăng tin tìm trẻ thất lạc hoặc báo tin tìm thấy.
        </p>
    </div>

    <x-kyc-status-badge :status="auth()->user()->kyc_status" :reason="$kyc?->rejected_reason" />

    @if (in_array(auth()->user()->kyc_status, ['NONE', 'REJECTED']))

        {{-- Step Progress --}}
        <div class="relative flex items-center justify-between max-w-sm mx-auto px-4">
            <div class="absolute top-4 left-0 right-0 h-0.5 bg-slate-100 z-0"></div>
            <div class="absolute top-4 left-0 h-0.5 bg-primary z-0 transition-all duration-500"
                style="width: {{ (($step - 1) / 2) * 100 }}%"></div>

            @foreach ([1 => 'Loại giấy tờ', 2 => 'Ảnh 2 mặt', 3 => 'Selfie'] as $s => $label)
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

        @error('form')
            <div class="bg-danger-light text-danger text-sm font-bold rounded-2xl p-4 flex items-center gap-2">
                <x-heroicon-o-exclamation-circle class="w-5 h-5 shrink-0" />
                <span>{{ $message }}</span>
            </div>
        @enderror

        {{-- ════ BƯỚC 1: Chọn loại giấy tờ ════ --}}
        @if ($step === 1)
            <div class="space-y-4">
                <h3 class="font-extrabold text-slate-800 text-sm text-center">Bạn đang có loại giấy tờ nào?</h3>
                <div class="grid grid-cols-2 gap-4">
                    <button type="button" wire:click="selectIdType('CMND_9')"
                        class="rounded-2xl border-2 p-5 flex flex-col items-center gap-2 transition-colors
                            {{ $id_type === 'CMND_9' ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-primary/40' }}">
                        <x-heroicon-o-identification
                            class="w-7 h-7 {{ $id_type === 'CMND_9' ? 'text-primary' : 'text-slate-400' }}" />
                        <span class="text-sm font-black text-slate-800">CMND (9 số)</span>
                    </button>
                    <button type="button" wire:click="selectIdType('CCCD_12')"
                        class="rounded-2xl border-2 p-5 flex flex-col items-center gap-2 transition-colors
                            {{ $id_type === 'CCCD_12' ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-primary/40' }}">
                        <x-heroicon-o-credit-card
                            class="w-7 h-7 {{ $id_type === 'CCCD_12' ? 'text-primary' : 'text-slate-400' }}" />
                        <span class="text-sm font-black text-slate-800">CCCD (12 số)</span>
                    </button>
                </div>
                @error('id_type')
                    <p class="text-danger text-xs font-bold text-center flex items-center justify-center gap-1">
                        <x-heroicon-o-exclamation-circle class="w-3.5 h-3.5 shrink-0" /> {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- ════ BƯỚC 2: Ảnh 2 mặt ════ --}}
        @elseif ($step === 2)
            <div class="space-y-5">
                <div>
                    <label class="text-xs font-black uppercase tracking-wider text-slate-500 block mb-1.5">
                        Số {{ $id_type === 'CMND_9' ? 'CMND' : 'CCCD' }}
                    </label>
                    <input type="text" wire:model.blur="id_number" maxlength="12"
                        placeholder="{{ $id_type === 'CMND_9' ? 'VD: 123456789' : 'VD: 079xxxxxxxxx' }}"
                        class="w-full px-4 py-3 border rounded-xl text-sm font-medium bg-slate-50
                            focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent
                            {{ $errors->has('id_number') ? 'border-red-400' : 'border-slate-200' }}" />
                    @error('id_number')
                        <p class="text-danger text-xs font-bold mt-1.5 flex items-center gap-1">
                            <x-heroicon-o-exclamation-circle class="w-3.5 h-3.5 shrink-0" /> {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach ([['key' => 'front_image', 'label' => 'Mặt trước'], ['key' => 'back_image', 'label' => 'Mặt sau']] as $field)
                        <div x-data="kycImageCapture('{{ $field['key'] }}', 'environment')" wire:ignore.self>
                            <label class="text-xs font-black uppercase tracking-wider text-slate-500 block mb-1.5">
                                {{ $field['label'] }}
                            </label>

                            <div class="relative aspect-[4/3] rounded-2xl border-2 border-dashed overflow-hidden transition-colors
                                    {{ $this->{$field['key']}
                                        ? 'border-success/40 bg-success-light/40'
                                        : 'border-slate-200 bg-slate-50' }}
                                    @error($field['key']) !border-danger bg-danger-light/40 @enderror">

                                <input type="file" x-ref="fileInput" wire:model="{{ $field['key'] }}"
                                    accept="image/png,image/jpeg" class="hidden" />
                                <canvas x-ref="canvas" class="hidden"></canvas>

                                @if ($this->{$field['key']})
                                    <img src="{{ $this->{$field['key']}->temporaryUrl() }}"
                                        class="w-full h-full object-cover" />
                                    <button type="button" @click="retake()"
                                        class="absolute inset-x-0 bottom-0 bg-success/90 text-white text-[10px] font-black uppercase tracking-wider py-1.5 text-center hover:bg-success transition-colors">
                                        <x-heroicon-s-check-circle class="w-3.5 h-3.5" /> Đã chọn — bấm để đổi ảnh
                                    </button>
                                @else
                                    <div x-show="stage === 'camera'" x-cloak class="absolute inset-0 flex flex-col">
                                        <button type="button" @click="cancelCamera()"
                                            class="absolute top-2 right-2 z-10 w-6 h-6 rounded-full bg-black/40 text-white flex items-center justify-center hover:bg-black/60 transition-colors">
                                            <x-heroicon-o-x-mark class="w-3.5 h-3.5" />
                                        </button>
                                        <video x-ref="video" autoplay playsinline muted
                                            class="flex-1 min-h-0 w-full object-cover"></video>
                                        <button type="button" @click="capture()"
                                            class="shrink-0 w-full bg-primary text-white text-sm font-black py-3 flex items-center justify-center gap-2 hover:bg-primary-dark transition-colors">
                                            <x-heroicon-o-camera class="w-4 h-4" /> Chụp ảnh
                                        </button>
                                    </div>

                                    <div x-show="stage === 'uploading'" x-cloak
                                        class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-slate-50">
                                        <x-heroicon-o-arrow-path class="w-6 h-6 text-primary animate-spin" />
                                        <span class="text-xs font-black text-primary">Đang xử lý ảnh...</span>
                                    </div>

                                    <div x-show="stage === 'choice'"
                                        class="absolute inset-0 flex flex-col items-center justify-center gap-3 p-4">
                                        <x-heroicon-o-identification class="w-8 h-8 text-slate-300" />

                                        <div x-show="permissionError === 'denied'" x-cloak
                                            class="w-full max-w-[190px] bg-danger-light text-danger text-[10px] font-bold rounded-xl px-3 py-2 text-center leading-snug">
                                            Chưa được cấp quyền camera. Vui lòng cấp quyền camera cho trình duyệt trong Cài đặt, sau đó bấm lại "Chụp bằng camera".
                                        </div>

                                        <div class="flex flex-col gap-2 w-full max-w-[190px]">
                                            <button type="button" x-show="cameraSupported !== false"
                                                @click="openCamera()"
                                                class="w-full rounded-full bg-primary/10 text-primary text-xs font-black py-2.5 flex items-center justify-center gap-2 hover:bg-primary/15 transition-colors">
                                                <x-heroicon-o-camera class="w-4 h-4" /> Chụp bằng camera
                                            </button>
                                            <button type="button" @click="$refs.fileInput.click()"
                                                class="w-full rounded-full border border-slate-200 bg-white text-slate-700 text-xs font-black py-2.5 flex items-center justify-center gap-2 hover:bg-slate-50 transition-colors">
                                                <x-heroicon-o-arrow-up-tray class="w-4 h-4" /> Tải ảnh có sẵn
                                            </button>
                                        </div>
                                        <span class="text-[11px] text-slate-400 font-semibold">JPG/PNG, tối đa 5MB</span>
                                    </div>
                                @endif

                                <div wire:loading wire:target="{{ $field['key'] }}"
                                    class="absolute inset-0 bg-white/80 flex flex-col items-center justify-center gap-1.5">
                                    <x-heroicon-o-arrow-path class="w-5 h-5 text-primary animate-spin" />
                                    <span class="text-xs font-black text-primary">Đang tải...</span>
                                </div>
                            </div>

                            @error($field['key'])
                                <p class="text-danger text-xs font-bold mt-1.5 flex items-center gap-1">
                                    <x-heroicon-o-exclamation-circle class="w-3.5 h-3.5 shrink-0" /> {{ $message }}
                                </p>
                            @enderror
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- ════ BƯỚC 3: Selfie ════ --}}
        @elseif ($step === 3)
            <div x-data="kycImageCapture('selfie', 'user')" wire:ignore.self>
                <label class="text-xs font-black uppercase tracking-wider text-slate-500 block mb-1.5">
                    Ảnh selfie đối chiếu
                </label>

                <div class="relative aspect-[4/3] rounded-2xl border-2 border-dashed overflow-hidden transition-colors
                        {{ $this->selfie
                            ? 'border-success/40 bg-success-light/40'
                            : 'border-slate-200 bg-slate-50' }}
                        @error('selfie') !border-danger bg-danger-light/40 @enderror">

                    <canvas x-ref="canvas" class="hidden"></canvas>

                    @if ($this->selfie)
                        <img src="{{ $this->selfie->temporaryUrl() }}" class="w-full h-full object-cover" />
                        <button type="button" @click="retake()"
                            class="absolute inset-x-0 bottom-0 bg-success/90 text-white text-[10px] font-black uppercase tracking-wider py-1.5 text-center hover:bg-success transition-colors">
                            <x-heroicon-s-check-circle class="w-3.5 h-3.5" /> Đã chọn — bấm để đổi ảnh
                        </button>
                    @else
                        <div x-show="stage === 'camera'" x-cloak class="absolute inset-0 flex flex-col">
                            <button type="button" @click="cancelCamera()"
                                class="absolute top-2 right-2 z-10 w-6 h-6 rounded-full bg-black/40 text-white flex items-center justify-center hover:bg-black/60 transition-colors">
                                <x-heroicon-o-x-mark class="w-3.5 h-3.5" />
                            </button>
                            <video x-ref="video" autoplay playsinline muted class="flex-1 min-h-0 w-full object-cover"></video>
                            <button type="button" @click="capture()"
                                class="shrink-0 w-full bg-primary text-white text-sm font-black py-3 flex items-center justify-center gap-2 hover:bg-primary-dark transition-colors">
                                <x-heroicon-o-camera class="w-4 h-4" /> Chụp ảnh
                            </button>
                        </div>

                        <div x-show="stage === 'uploading'" x-cloak
                            class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-slate-50">
                            <x-heroicon-o-arrow-path class="w-6 h-6 text-primary animate-spin" />
                            <span class="text-xs font-black text-primary">Đang xử lý ảnh...</span>
                        </div>

                        <div x-show="stage === 'choice'" class="absolute inset-0 flex flex-col items-center justify-center gap-3 p-4">
                            <x-heroicon-o-user-circle class="w-8 h-8 text-slate-300" />

                            <div x-show="permissionError === 'denied'" x-cloak
                                class="w-full max-w-[190px] bg-danger-light text-danger text-[10px] font-bold rounded-xl px-3 py-2 text-center leading-snug">
                                Chưa được cấp quyền camera. Vui lòng cấp quyền camera cho trình duyệt trong Cài đặt, sau đó bấm lại "Chụp bằng camera".
                            </div>

                            <div class="flex flex-col gap-2 w-full max-w-[190px]">
                                <button type="button" x-show="cameraSupported !== false" @click="openCamera()"
                                    class="w-full rounded-full bg-primary/10 text-primary text-xs font-black py-2.5 flex items-center justify-center gap-2 hover:bg-primary/15 transition-colors">
                                    <x-heroicon-o-camera class="w-4 h-4" /> Chụp bằng camera
                                </button>
                                <div x-show="cameraSupported === false" x-cloak
                                    class="rounded-xl bg-amber-50 px-3 py-2 text-center text-[10px] font-bold leading-snug text-amber-700">
                                    Thiết bị hoặc trình duyệt này không hỗ trợ camera. Vui lòng chuyển sang thiết bị có camera để tiếp tục xác thực.
                                </div>
                            </div>
                            <span class="text-center text-[11px] text-slate-400 font-semibold">Bắt buộc chụp trực tiếp bằng camera trước</span>
                        </div>
                    @endif

                    <div wire:loading wire:target="selfie"
                        class="absolute inset-0 bg-white/80 flex flex-col items-center justify-center gap-1.5">
                        <x-heroicon-o-arrow-path class="w-5 h-5 text-primary animate-spin" />
                        <span class="text-xs font-black text-primary">Đang tải...</span>
                    </div>
                </div>

                @error('selfie')
                    <p class="text-danger text-xs font-bold mt-1.5 flex items-center gap-1">
                        <x-heroicon-o-exclamation-circle class="w-3.5 h-3.5 shrink-0" /> {{ $message }}
                    </p>
                @enderror
            </div>
        @endif

        {{-- NAV BUTTONS --}}
        <div class="flex items-center justify-between pt-4 border-t border-slate-100">
            @if ($step > 1)
                <button type="button" wire:click="back"
                    class="px-5 py-3 rounded-2xl border border-slate-200 text-slate-600 font-extrabold text-sm hover:bg-slate-50 transition-colors">
                    ← Quay lại
                </button>
            @else
                <span></span>
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
                    class="px-6 py-3 rounded-2xl bg-primary text-white font-extrabold text-sm hover:bg-primary-dark shadow-md shadow-primary/20 transition-all flex items-center gap-2">
                    <span wire:loading wire:target="submit"
                        class="inline-block w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                    Gửi hồ sơ KYC
                </button>
            @endif
        </div>

    @elseif (auth()->user()->kyc_status === 'PENDING')
        <p class="text-center text-sm text-slate-500 font-medium py-4">
            Hồ sơ của bạn đang chờ duyệt, thường trong vòng 24h.
        </p>
    @endif

    @script
    <script>
        // Dùng camera cho cả 3 ảnh; riêng selfie chỉ cho phép chụp trực tiếp.
        Alpine.data('kycImageCapture', (fieldName, facingMode = 'environment') => ({
            stage: 'choice', // 'choice' | 'camera' | 'uploading'
            cameraSupported: null,
            permissionError: null, // null | 'denied'
            stream: null,

            init() {
                this.cameraSupported = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
            },

            async openCamera() {
                if (this.cameraSupported === false) return;
                this.permissionError = null;
                try {
                    this.stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode } });
                    this.cameraSupported = true;
                    this.stage = 'camera';
                    this.$nextTick(() => { if (this.$refs.video) this.$refs.video.srcObject = this.stream; });
                } catch (e) {
                    console.warn('Camera không khả dụng:', e.name, e.message);
                    if (e.name === 'NotFoundError' || e.name === 'OverconstrainedError') {
                        this.cameraSupported = false;
                    } else {
                        this.permissionError = 'denied';
                    }
                }
            },

            capture() {
                const video = this.$refs.video, canvas = this.$refs.canvas;
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                canvas.getContext('2d').drawImage(video, 0, 0);
                this.stopStream();
                this.stage = 'uploading';

                canvas.toBlob(async (blob) => {
                    const file = new File([blob], fieldName + '.jpg', { type: 'image/jpeg' });

                    if (fieldName === 'selfie') {
                        await $wire.set('selfieCapturedByCamera', true);
                    }

                    $wire.upload(
                        fieldName,
                        file,
                        () => { /* thành công: Livewire re-render, hiện ảnh */ },
                        () => { this.stage = 'choice'; }
                    );
                }, 'image/jpeg', 0.9);
            },

            cancelCamera() {
                this.stopStream();
                this.stage = 'choice';
            },

            retake() {
                this.stage = 'choice';
                if (fieldName === 'selfie') {
                    $wire.set('selfieCapturedByCamera', false);
                }
                $wire.set(fieldName, null);
            },

            stopStream() {
                this.stream?.getTracks().forEach(t => t.stop());
                this.stream = null;
            },
        }));
    </script>
    @endscript

</div>
