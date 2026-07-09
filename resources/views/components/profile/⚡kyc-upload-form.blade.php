<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\KycVerification;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithFileUploads;

    public $front_image = null;
    public $back_image = null;
    public $selfie = null;

    public string $id_number = '';
    public string $full_name_on_id = '';
    public string $dob_on_id = '';

    public ?KycVerification $kyc = null;

    public function mount(): void
    {
        $this->kyc = Auth::user()->kycVerification;
    }

    protected function rules(): array
    {
        return [
            'id_number' => ['required', 'string', 'regex:/^\d{9}(\d{3})?$/'],
            'full_name_on_id' => ['nullable', 'string', 'max:255'],
            'dob_on_id' => ['nullable', 'date', 'before:today'],
            'front_image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'back_image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'selfie' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ];
    }

    protected function messages(): array
    {
        return [
            'id_number.required' => 'Vui lòng nhập số CCCD.',
            'id_number.regex' => 'Số CCCD/CMND không hợp lệ.',
            'front_image.required' => 'Vui lòng tải ảnh mặt trước CCCD.',
            'back_image.required' => 'Vui lòng tải ảnh mặt sau CCCD.',
            'selfie.required' => 'Vui lòng chụp hoặc tải ảnh selfie.',
            '*.image' => 'File phải là hình ảnh.',
            '*.max' => 'Dung lượng ảnh tối đa 5MB.',
        ];
    }

    public function submit(): void
    {
        $user = Auth::user();

        if (in_array($user->kyc_status, ['PENDING', 'VERIFIED'], true)) {
            $this->addError('form', $user->kyc_status === 'PENDING' ? 'Hồ sơ KYC của bạn đang chờ xét duyệt, vui lòng đợi.' : 'Tài khoản của bạn đã được xác thực KYC.');
            return;
        }

        $validated = $this->validate();

        $frontPath = $this->front_image->store("kyc/{$user->id}", 'private');
        $backPath = $this->back_image->store("kyc/{$user->id}", 'private');
        $selfiePath = $this->selfie->store("kyc/{$user->id}", 'private');

        $this->kyc = KycVerification::create([
            'user_id' => $user->id,
            'id_number' => $validated['id_number'],
            'full_name_on_id' => $validated['full_name_on_id'] ?: null,
            'dob_on_id' => $validated['dob_on_id'] ?: null,
            'front_image_path' => $frontPath,
            'back_image_path' => $backPath,
            'selfie_path' => $selfiePath,
            'status' => 'PENDING',
        ]);

        $user->forceFill(['kyc_status' => 'PENDING'])->save();

        $this->reset(['front_image', 'back_image', 'selfie', 'id_number', 'full_name_on_id', 'dob_on_id']);
    }

    public function retry(): void
    {
        $this->reset(['front_image', 'back_image', 'selfie', 'id_number', 'full_name_on_id', 'dob_on_id']);
        Auth::user()
            ->forceFill(['kyc_status' => 'NONE'])
            ->save();
        $this->kyc = null;
    }
};
?>

<div class="bg-white border border-slate-200/85 rounded-3xl p-6 sm:p-10 shadow-premium space-y-7 max-w-xl mx-auto">

    <div class="text-center space-y-1.5">
        <div class="inline-flex bg-primary/10 text-primary p-3 rounded-2xl mb-1">
            <x-heroicon-o-identification class="w-6 h-6" />
        </div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Xác thực CCCD (KYC)</h1>
        <p class="text-slate-500 text-xs sm:text-sm font-medium">
            Cần thiết để đăng tin tìm trẻ thất lạc hoặc báo tin tìm thấy.
        </p>
    </div>

    <x-kyc-status-badge :status="auth()->user()->kyc_status" :reason="$kyc?->rejected_reason" />

    @if (in_array(auth()->user()->kyc_status, ['NONE', 'REJECTED']))
    <form wire:submit="submit" class="space-y-6">

        @error('form')
        <div class="bg-danger-light text-danger text-sm font-bold rounded-2xl p-4 flex items-center gap-2">
            <x-heroicon-o-exclamation-circle class="w-5 h-5 shrink-0" />
            <span>{{ $message }}</span>
        </div>
        @enderror

        {{-- Số CCCD --}}
        <div>
            <label class="text-xs font-black uppercase tracking-wider text-slate-500 block mb-1.5">Số
                CCCD/CMND</label>
            <input type="text" wire:model.blur="id_number" maxlength="12" placeholder="079xxxxxxxxx" class="w-full px-4 py-3 border rounded-xl text-sm font-medium bg-slate-50
              focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent
              {{ $errors->has('id_number') ? 'border-red-400' : 'border-slate-200' }}" />
            @error('id_number')
            <p class="text-danger text-xs font-bold mt-1.5 flex items-center gap-1">
                <x-heroicon-o-exclamation-circle class="w-3.5 h-3.5 shrink-0" /> {{ $message }}
            </p>
            @enderror
        </div>

        {{-- Mặt trước / mặt sau CCCD: chọn "Chụp camera" (mặc định camera sau) hoặc "Tải ảnh có sẵn" --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach ([['key' => 'front_image', 'label' => 'Mặt trước CCCD'], ['key' => 'back_image', 'label' => 'Mặt
            sau CCCD']] as $field)
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
                    {{-- Đã có ảnh (dù chụp bằng camera hay tải lên) --}}
                    <img src="{{ $this->{$field['key']}->temporaryUrl() }}" class="w-full h-full object-cover" />
                    <button type="button" @click="retake()"
                        class="absolute inset-x-0 bottom-0 bg-success/90 text-white text-[10px] font-black uppercase tracking-wider py-1.5 text-center hover:bg-success transition-colors">
                        <x-heroicon-s-check-circle class="w-3.5 h-3.5" /> Đã chọn — bấm để đổi ảnh
                    </button>
                    @else

                    {{-- Đang bật camera: video full khung + nút đỏ full-width "Chụp ảnh" --}}
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

                    {{-- Vừa chụp xong, đang xử lý & tải lên -> tránh flash về màn hình chọn --}}
                    <div x-show="stage === 'uploading'" x-cloak
                        class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-slate-50">
                        <x-heroicon-o-arrow-path class="w-6 h-6 text-primary animate-spin" />
                        <span class="text-xs font-black text-primary">Đang xử lý ảnh...</span>
                    </div>

                    {{-- Màn hình chọn: chụp bằng camera hay tải ảnh có sẵn --}}
                    <div x-show="stage === 'choice'" class="absolute inset-0 flex flex-col items-center justify-center gap-3 p-4">
                        <x-heroicon-o-identification class="w-8 h-8 text-slate-300" />

                        {{-- Trình duyệt/HĐH đã từ chối quyền camera --}}
                        <div x-show="permissionError === 'denied'" x-cloak
                            class="w-full max-w-[190px] bg-danger-light text-danger text-[10px] font-bold rounded-xl px-3 py-2 text-center leading-snug">
                            Chưa được cấp quyền camera. Vui lòng cấp quyền camera cho trình duyệt trong Cài đặt, sau đó bấm lại "Chụp bằng camera".
                        </div>

                        <div class="flex flex-col gap-2 w-full max-w-[190px]">
                            <button type="button" x-show="cameraSupported !== false" @click="openCamera()"
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
                    <span>
                        <x-heroicon-o-exclamation-circle class="w-3.5 h-3.5 shrink-0" />
                    </span>
                    {{ $message }}
                </p>
                @enderror
            </div>
            @endforeach
        </div>

        {{-- Selfie: cùng cơ chế chọn camera/tải ảnh như 2 ô CCCD, chỉ khác facing mode ('user') --}}
        <div x-data="kycImageCapture('selfie', 'user')" wire:ignore.self>
            <label class="text-xs font-black uppercase tracking-wider text-slate-500 block mb-1.5">Ảnh
                selfie</label>

            <div class="relative aspect-[4/3] rounded-2xl border-2 border-dashed overflow-hidden transition-colors
                                  {{ $this->selfie
                                      ? 'border-success/40 bg-success-light/40'
                                      : 'border-slate-200 bg-slate-50' }}
                                  @error('selfie') !border-danger bg-danger-light/40 @enderror">

                <input type="file" x-ref="fileInput" wire:model="selfie" accept="image/png,image/jpeg"
                    class="hidden" />
                <canvas x-ref="canvas" class="hidden"></canvas>

                @if ($this->selfie)
                {{-- Đã có ảnh (dù chụp bằng camera hay tải lên) --}}
                <img src="{{ $this->selfie->temporaryUrl() }}" class="w-full h-full object-cover" />
                <button type="button" @click="retake()"
                    class="absolute inset-x-0 bottom-0 bg-success/90 text-white text-[10px] font-black uppercase tracking-wider py-1.5 text-center hover:bg-success transition-colors">
                    <x-heroicon-s-check-circle class="w-3.5 h-3.5" /> Đã chọn — bấm để đổi ảnh
                </button>
                @else

                {{-- Đang bật camera: video full khung + nút đỏ full-width "Chụp ảnh" --}}
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

                {{-- Vừa chụp xong, đang xử lý & tải lên -> tránh flash về màn hình chọn --}}
                <div x-show="stage === 'uploading'" x-cloak
                    class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-slate-50">
                    <x-heroicon-o-arrow-path class="w-6 h-6 text-primary animate-spin" />
                    <span class="text-xs font-black text-primary">Đang xử lý ảnh...</span>
                </div>

                {{-- Màn hình chọn: chụp bằng camera hay tải ảnh có sẵn --}}
                <div x-show="stage === 'choice'" class="absolute inset-0 flex flex-col items-center justify-center gap-3 p-4">
                    <x-heroicon-o-user-circle class="w-8 h-8 text-slate-300" />

                    {{-- Trình duyệt/HĐH đã từ chối quyền camera --}}
                    <div x-show="permissionError === 'denied'" x-cloak
                        class="w-full max-w-[190px] bg-danger-light text-danger text-[10px] font-bold rounded-xl px-3 py-2 text-center leading-snug">
                        Chưa được cấp quyền camera. Vui lòng cấp quyền camera cho trình duyệt trong Cài đặt, sau đó bấm lại "Chụp bằng camera".
                    </div>

                    <div class="flex flex-col gap-2 w-full max-w-[190px]">
                        <button type="button" x-show="cameraSupported !== false" @click="openCamera()"
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

        <button type="submit" wire:loading.attr="disabled" wire:target="submit" class="w-full rounded-2xl bg-primary text-white font-black py-3.5 shadow-lg shadow-primary/20
                           hover:bg-primary-dark hover:shadow-primary/30 hover:-translate-y-0.5 transition-all
                           disabled:opacity-50 disabled:hover:translate-y-0">
            <span wire:loading.remove wire:target="submit">Gửi hồ sơ KYC</span>
            <span wire:loading wire:target="submit">Đang gửi...</span>
        </button>
    </form>
    @elseif(auth()->user()->kyc_status === 'PENDING')
    <p class="text-center text-sm text-slate-500 font-medium py-4">
        Hồ sơ của bạn đang chờ duyệt, thường trong vòng 24h.
    </p>
    @endif

    @script
    <script>
        // Dùng chung cho cả 3 ô: Mặt trước CCCD, Mặt sau CCCD, Selfie.
        // Cho user CHỌN giữa "Chụp bằng camera" hoặc "Tải ảnh có sẵn" thay vì tự bật camera
        // ngay khi vào trang — vì nếu cả 3 ô cùng auto-start camera cùng lúc sẽ dễ xung đột
        // (2 ô CCCD dùng camera sau 'environment' tranh nhau 1 camera vật lý trên điện thoại).
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
                        // Máy không có camera phù hợp -> ẩn hẳn nút, chỉ còn tải ảnh
                        this.cameraSupported = false;
                    } else {
                        // Mọi trường hợp còn lại (NotAllowedError, PermissionDeniedError,
                        // SecurityError, NotReadableError...) coi như bị chặn quyền -> giữ nút,
                        // hiện banner để user cấp quyền rồi bấm thử lại, không ẩn im lặng.
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

                canvas.toBlob((blob) => {
                    const file = new File([blob], fieldName + '.jpg', { type: 'image/jpeg' });
                    $wire.upload(
                        fieldName,
                        file,
                        () => { /* thành công: Livewire re-render, hiện ảnh, stage không còn quan trọng */ },
                        () => { this.stage = 'choice'; /* upload lỗi -> cho thử lại thay vì kẹt màn hình loading */ }
                    );
                }, 'image/jpeg', 0.9);
            },

            cancelCamera() {
                this.stopStream();
                this.stage = 'choice';
            },

            retake() {
                this.stage = 'choice';
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