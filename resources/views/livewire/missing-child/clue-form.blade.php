<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Clue;          // thay đúng model của bạn nếu khác
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithFileUploads;

    // Model binding — truyền vào từ parent (missing/found child id)
    public int $subjectId;
    public string $subjectType; // 'missing' | 'found'

    public string $description = '';
    public string $lat = '';
    public string $lng = '';
    public bool $is_anonymous = false;
    public $image;

    public function mount(int $subjectId, string $subjectType = 'missing'): void
    {
        $this->subjectId = $subjectId;
        $this->subjectType = $subjectType;
    }

    protected function rules(): array
    {
        return [
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'is_anonymous' => ['boolean'],
            'image' => ['nullable', 'file', 'image', 'max:5120'],
        ];
    }

    protected function messages(): array
    {
        return [
            'description.required' => 'Vui lòng mô tả manh mối bạn muốn gửi.',
            'description.min' => 'Mô tả cần ít nhất 10 ký tự.',
            'image.image' => 'Tệp tải lên phải là hình ảnh.',
            'image.max' => 'Ảnh tối đa 5MB.',
        ];
    }

    public function updatedImage(): void
    {
        if (!$this->image)
            return;

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($this->image->getRealPath());

        if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'])) {
            $this->addError('image', 'Chỉ chấp nhận ảnh JPG, PNG, WebP thực sự.');
            $this->image = null;
            return;
        }

        $this->validate(['image' => ['file', 'image', 'max:5120']]);
    }

    public function submit(): void
    {
        $this->validate();

        $imagePath = null;
        if ($this->image) {
            $imagePath = $this->image->storePublicly(
                "clues/{$this->subjectType}/{$this->subjectId}",
                'public'
            );
        }

        Clue::create([
            'subject_id' => $this->subjectId,
            'subject_type' => $this->subjectType,
            'user_id' => $this->is_anonymous ? null : Auth::id(),
            'description' => $this->description,
            'lat' => $this->lat !== '' ? (float) $this->lat : null,
            'lng' => $this->lng !== '' ? (float) $this->lng : null,
            'is_anonymous' => $this->is_anonymous,
            'image_path' => $imagePath,
        ]);

        // Reset form
        $this->reset(['description', 'lat', 'lng', 'is_anonymous', 'image']);

        session()->flash('clue_submitted', 'Đã gửi manh mối thành công! Cảm ơn bạn đã giúp đỡ.');

        $this->dispatch('clue-submitted'); // để parent có thể refresh danh sách
    }
};
?>

{{-- ════ Clue / Manh mối form ════ --}}
<div x-data="{ open: false }" class="space-y-4">

    {{-- Trigger button --}}
    <div class="flex items-center justify-between">
        <h4 class="text-sm font-extrabold text-slate-700">Manh mối từ cộng đồng</h4>
        <button type="button" @click="open = !open" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-2xl bg-primary text-white font-extrabold text-xs
                   hover:bg-primary-dark active:scale-95 transition-all shadow-sm shadow-primary/20">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                stroke="currentColor" class="w-4 h-4 shrink-0" x-show="!open">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                stroke="currentColor" class="w-4 h-4 shrink-0" x-show="open" x-cloak>
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
            <span x-text="open ? 'Đóng' : 'Gửi manh mối'"></span>
        </button>
    </div>

    {{-- Flash message --}}
    @if (session()->has('clue_submitted'))
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 flex items-start gap-3" x-data
            x-init="setTimeout(() => $el.remove(), 5000)">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"
                class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <p class="text-sm font-bold text-emerald-800">{{ session('clue_submitted') }}</p>
        </div>
    @endif

    {{-- Panel --}}
    <div x-show="open" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2" x-cloak>

        <div class="bg-white border border-slate-200/85 rounded-3xl p-6 shadow-premium space-y-5">

            <div class="flex items-center gap-3">
                <div class="bg-amber-50 text-amber-600 p-2 rounded-xl shrink-0 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                        stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-900">Gửi manh mối</h3>
                    <p class="text-xs text-slate-400 font-medium">Mọi manh mối đều có thể giúp bé trở về nhà.</p>
                </div>
            </div>

            {{-- Mô tả --}}
            <div class="space-y-1.5">
                <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">
                    Mô tả manh mối *
                </label>
                <textarea wire:model.blur="description" rows="4"
                    placeholder="Ví dụ: Tôi thấy một bé mặc áo xanh lá, khoảng 7–8 tuổi, đứng một mình gần chợ Bình Tây lúc 14h..."
                    class="w-full px-4 py-3 border rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-medium resize-none placeholder-slate-400
                        {{ $errors->has('description') ? 'border-red-400' : 'border-slate-200' }}"></textarea>
                @error('description')
                    <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
                @enderror
            </div>

            {{-- GPS --}}
            <div class="space-y-2">
                <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                        stroke="currentColor" class="w-4 h-4 text-slate-500">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                    </svg>
                    Vị trí phát hiện (tuỳ chọn)
                </label>

                <div x-data="{ locating: false }" class="flex items-center gap-3 flex-wrap">
                    <button type="button" @click="
                            if (!navigator.geolocation) { alert('Trình duyệt không hỗ trợ định vị.'); return; }
                            locating = true;
                            const done = () => { locating = false; };
                            navigator.geolocation.getCurrentPosition(
                                (pos) => {
                                    $wire.set('lat', pos.coords.latitude.toFixed(6));
                                    $wire.set('lng', pos.coords.longitude.toFixed(6));
                                    done();
                                },
                                (err) => { alert('Không thể lấy vị trí: ' + err.message); done(); },
                                { enableHighAccuracy: true, timeout: 10000 }
                            );
                        " :disabled="locating"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-primary/10 text-primary font-extrabold text-xs
                               hover:bg-primary/20 active:scale-95 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
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

                    @if ($lat && $lng)
                        <p class="text-xs text-success font-bold flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                                stroke="currentColor" class="w-4 h-4 shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            {{ round((float) $lat, 5) }}, {{ round((float) $lng, 5) }}
                        </p>
                    @endif
                </div>
            </div>

            {{-- Ảnh --}}
            <div class="space-y-2">
                <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">
                    Ảnh đính kèm (tuỳ chọn)
                </label>
                <div class="border-2 border-dashed border-slate-200 hover:border-primary bg-slate-50 hover:bg-white
                            rounded-3xl p-5 text-center transition-all duration-300 cursor-pointer"
                    onclick="document.getElementById('clue-image-upload').click()">
                    <div
                        class="w-9 h-9 bg-primary/10 text-primary flex items-center justify-center rounded-xl mx-auto mb-2 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                        </svg>
                    </div>
                    <p class="text-xs font-black text-primary">Ấn để chọn ảnh</p>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-1">
                        JPG, PNG, WebP · 5MB
                    </p>
                </div>
                <input type="file" id="clue-image-upload" wire:model="image" accept="image/jpeg,image/png,image/webp"
                    class="hidden" />

                @error('image')
                    <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
                @enderror

                <div wire:loading wire:target="image"
                    class="text-xs text-slate-400 font-semibold flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor" class="animate-spin w-4 h-4 text-primary">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    Đang tải ảnh lên...
                </div>

                @if ($image)
                    <div class="mt-2 relative inline-block">
                        <img src="{{ $image->temporaryUrl() }}"
                            class="max-h-40 rounded-2xl border border-slate-200 object-contain shadow-sm" />
                        <button type="button" wire:click="$set('image', null)"
                            class="absolute -top-1.5 -right-1.5 bg-rose-500 text-white rounded-full w-5 h-5
                                       flex items-center justify-center hover:bg-rose-600 transition-colors shadow-md text-xs font-black">
                            &times;
                        </button>
                    </div>
                @endif
            </div>

            {{-- Ẩn danh --}}
            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4">
                <label class="flex items-center gap-3 cursor-pointer select-none">
                    <input type="checkbox" wire:model="is_anonymous" class="w-4 h-4 accent-primary rounded shrink-0" />
                    <div>
                        <span class="text-sm font-extrabold text-slate-700">Gửi ẩn danh</span>
                        <p class="text-[11px] text-slate-400 font-medium mt-0.5">
                            Tên của bạn sẽ không hiển thị công khai cùng manh mối này.
                        </p>
                    </div>
                </label>
            </div>

            {{-- Submit --}}
            <div class="flex items-center justify-between pt-1 border-t border-slate-100">
                <button type="button" @click="open = false"
                    class="px-5 py-3 rounded-2xl border border-slate-200 text-slate-500 font-extrabold text-sm hover:bg-slate-50 transition-colors">
                    Hủy
                </button>
                <button type="button" wire:click="submit" wire:loading.attr="disabled" wire:target="submit,image" class="px-6 py-3 rounded-2xl bg-primary text-white font-extrabold text-sm hover:bg-primary-dark
                           shadow-md shadow-primary/20 transition-all flex items-center gap-2">
                    <span wire:loading wire:target="submit"
                        class="inline-block w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                        stroke="currentColor" class="w-4 h-4 shrink-0" wire:loading.remove wire:target="submit">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                    </svg>
                    Gửi manh mối
                </button>
            </div>

        </div>
    </div>
</div>