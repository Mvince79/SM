<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    use WithFileUploads;

    public $user;
    public $provinces;

    public string $full_name   = '';
    public string $phone       = '';
    public string $province_id = '';
    public $avatarUpload = null;

    public function mount($user, $provinces): void
    {
        $this->user        = $user;
        $this->provinces   = $provinces;
        $this->full_name   = $user->full_name ?? '';
        $this->phone       = $user->phone ?? '';
        $this->province_id = (string) ($user->province_id ?? '');
    }

    public function updatedAvatarUpload(): void
    {
        $this->validate([
            'avatarUpload' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ], [
            'avatarUpload.image' => 'File phải là ảnh.',
            'avatarUpload.mimes' => 'Chỉ chấp nhận JPG, PNG, WebP.',
            'avatarUpload.max'   => 'Ảnh tối đa 3MB.',
        ]);
    }

    public function save(): void
    {
        $this->validate([
            'full_name'   => ['required', 'string', 'max:100'],
            'phone'       => ['nullable', 'string', 'regex:/^(0|\+84)[0-9]{8,10}$/'],
            'province_id' => ['nullable', 'exists:provinces,id'],
        ], [
            'full_name.required' => 'Vui lòng nhập họ và tên.',
            'phone.regex'        => 'Số điện thoại không hợp lệ.',
        ]);

        $updateData = [
            'full_name'   => $this->full_name,
            'phone'       => $this->phone ?: null,
            'province_id' => $this->province_id ?: null,
        ];

        if ($this->avatarUpload) {
            $avatarPath = $this->processAvatar();
            if ($avatarPath) {
                // Xóa avatar cũ nếu là file local
                $oldAvatar = Auth::user()->getRawOriginal('avatar_url');
                if ($oldAvatar && !str_starts_with($oldAvatar, 'http')) {
                    Storage::disk('public')->delete($oldAvatar);
                }
                $updateData['avatar_url'] = $avatarPath;
            }
        }

        Auth::user()->update($updateData);

        session()->flash('success', 'Cập nhật hồ sơ thành công!');
        $this->redirect(route('profile.index'));
    }

    protected function processAvatar(): ?string
    {
        try {
            $tmpPath  = $this->avatarUpload->getRealPath();
            $mimeType = mime_content_type($tmpPath);

            $src = match($mimeType) {
                'image/jpeg' => imagecreatefromjpeg($tmpPath),
                'image/png'  => imagecreatefrompng($tmpPath),
                'image/webp' => imagecreatefromwebp($tmpPath),
                default      => throw new \Exception('Định dạng ảnh không hỗ trợ'),
            };

            $w    = imagesx($src);
            $h    = imagesy($src);
            $size = min($w, $h);
            $x    = (int)(($w - $size) / 2);
            $y    = (int)(($h - $size) / 2);

            $dst = imagecreatetruecolor(300, 300);
            imagecopyresampled($dst, $src, 0, 0, $x, $y, 300, 300, $size, $size);

            $filename = 'avatars/' . Auth::id() . '_' . time() . '.jpg';
            $fullPath = storage_path('app/public/' . $filename);

            if (!is_dir(dirname($fullPath))) {
                mkdir(dirname($fullPath), 0755, true);
            }

            imagejpeg($dst, $fullPath, 85);
            imagedestroy($src);
            imagedestroy($dst);

            return $filename;

        } catch (\Exception $e) {
            $this->addError('avatarUpload', 'Không thể xử lý ảnh. Vui lòng thử lại.');
            return null;
        }
    }
};
?>

{{-- Form nằm trong modal (index.blade.php đã tạo modal) --}}
<div class="space-y-4">

    {{-- Avatar preview + upload --}}
    <div class="flex items-center gap-4">
        <div class="relative">
            @if($avatarUpload)
                <img src="{{ $avatarUpload->temporaryUrl() }}"
                     class="w-16 h-16 rounded-full object-cover border-2 border-primary/30"/>
                <span class="absolute -top-1 -right-1 bg-primary text-white text-[9px] font-black px-1.5 py-0.5 rounded-full">Mới</span>
            @else
                <img src="{{ $user->avatar_url }}"
                     class="w-16 h-16 rounded-full object-cover border-2 border-slate-200 bg-slate-100"/>
            @endif
        </div>
        <div>
            <label class="cursor-pointer text-xs font-extrabold text-primary hover:text-primary-dark transition-colors"
                   onclick="document.getElementById('avatar-input').click()">
                📷 Đổi ảnh đại diện
            </label>
            <p class="text-[10px] text-slate-400 font-semibold mt-0.5">JPG, PNG, WebP · Tối đa 3MB · Crop 300×300</p>
            <input id="avatar-input" type="file" wire:model="avatarUpload"
                   accept="image/jpeg,image/png,image/webp" class="hidden"/>
        </div>
    </div>

    {{-- Loading avatar --}}
    <div wire:loading wire:target="avatarUpload" class="text-xs text-slate-400 font-semibold flex items-center gap-2">
        <x-heroicon-o-arrow-path class="animate-spin w-4 h-4 text-primary" />
        Đang xử lý ảnh...
    </div>

    @error('avatarUpload')
        <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
    @enderror

    {{-- Họ tên --}}
    <div class="space-y-1.5">
        <label class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Họ tên của bạn</label>
        <input type="text" wire:model="full_name" placeholder="Nguyễn Văn A"
               class="w-full px-4 py-3 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent bg-slate-50 font-medium
                      {{ $errors->has('full_name') ? 'border-red-400' : 'border-slate-200' }}"/>
        @error('full_name')<p class="text-xs text-red-500 font-semibold">{{ $message }}</p>@enderror
    </div>

    {{-- Số điện thoại --}}
    <div class="space-y-1.5">
        <label class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Số điện thoại</label>
        <input type="tel" wire:model="phone" placeholder="0905 123 456"
               class="w-full px-4 py-3 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent bg-slate-50 font-medium
                      {{ $errors->has('phone') ? 'border-red-400' : 'border-slate-200' }}"/>
        @error('phone')<p class="text-xs text-red-500 font-semibold">{{ $message }}</p>@enderror
    </div>

    {{-- Tỉnh nhận thông báo --}}
    <div class="space-y-1.5">
        <label class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Tỉnh nhận thông báo</label>
        <select wire:model="province_id"
                class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 font-bold text-slate-600">
            <option value="">-- Không nhận thông báo --</option>
            @foreach($provinces as $p)
                <option value="{{ $p->id }}">{{ $p->name }}</option>
            @endforeach
        </select>
        @error('province_id')<p class="text-xs text-red-500 font-semibold">{{ $message }}</p>@enderror
    </div>

    {{-- Nút --}}
    <div class="flex gap-2 justify-end pt-2 border-t border-slate-100">
        <button type="button" onclick="document.querySelector('[\\@click=\\'editOpen = false\\']').click()"
                class="text-xs font-bold text-slate-500 px-4 py-2.5 hover:bg-slate-100 rounded-xl">
            Hủy
        </button>
        <button type="button" wire:click="save"
                wire:loading.attr="disabled"
                class="bg-primary text-white text-xs font-extrabold px-6 py-2.5 rounded-xl hover:bg-primary-dark transition-all shadow-md shadow-primary/10 disabled:opacity-60">
            <span wire:loading.remove wire:target="save">Lưu lại</span>
            <span wire:loading wire:target="save">⏳ Đang lưu...</span>
        </button>
    </div>

</div>