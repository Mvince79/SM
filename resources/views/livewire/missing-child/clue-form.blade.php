<div class="max-w-3xl">
    <!-- Add-form style: collapsed trigger -->
    <div class="mb-4 flex items-center justify-between">
<div class="max-w-3xl">
    <!-- Add-form style: collapsed trigger -->
    <div class="mb-4 flex items-center justify-between">
        <h4 class="text-sm font-medium text-slate-700">Manh mối từ cộng đồng</h4>
            <button id="open-clue-form" class="inline-flex items-center gap-2 px-3 py-1.5 bg-primary text-white rounded-md text-sm">+ Gửi manh mối mới</button>
    </div>

    <div id="clue-form-panel" style="display:none">
    <div aria-live="polite">
        @if (session()->has('clue_submitted'))
            <div class="mb-4 rounded-md bg-green-50 border border-green-200 p-3 text-green-800">
                {{ session('clue_submitted') }}
            </div>
        @endif
    </div>

    <div class="bg-white shadow-sm rounded-lg border p-6">
        <h3 class="text-lg font-semibold mb-4">Gửi manh mối từ cộng đồng</h3>

        <form wire:submit.prevent="submit" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700">Mô tả</label>
                <textarea wire:model.defer="description" rows="4" placeholder="Mô tả ngắn (ví dụ: thấy bé mặc áo màu gì, ở đâu...)" class="mt-1 block w-full rounded-md border-gray-200 shadow-sm focus:ring-primary focus:border-primary"></textarea>
                @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-4">
                <div>
                    <button type="button" onclick="getLocation(this)" class="inline-flex items-center gap-2 px-3 py-2 bg-primary text-white rounded-md hover:opacity-95">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3"/></svg>
                        Lấy vị trí GPS
                    </button>
                </div>

                <div class="text-sm text-slate-600">
                    Lat: <span class="font-medium">{{ $lat ?: '—' }}</span>
                    &nbsp;—&nbsp;
                    Lng: <span class="font-medium">{{ $lng ?: '—' }}</span>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Ảnh (tùy chọn)</label>
                <div class="mt-1 flex items-center gap-4">
                    <input type="file" wire:model="image" accept="image/*" class="text-sm text-slate-600" />
                    <div wire:loading wire:target="image" class="text-sm text-slate-500">Đang tải ảnh…</div>
                </div>
                @error('image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

                @if ($image)
                    <div class="mt-3">
                        <p class="text-xs text-slate-500">Xem trước ảnh:</p>
                        <img src="{{ $image->temporaryUrl() }}" alt="Preview" class="mt-2 max-h-48 rounded shadow-sm object-contain" />
                    </div>
                @endif
            </div>

            <div class="flex items-center">
                <input id="anon" type="checkbox" wire:model="is_anonymous" class="h-4 w-4 text-primary border-gray-300 rounded" />
                <label for="anon" class="ml-2 text-sm text-slate-700">Gửi ẩn danh</label>
            </div>

            <div class="pt-2">
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-md" wire:loading.attr="disabled" wire:target="image,submit">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Gửi manh mối</span>
                    <span wire:loading wire:target="submit" class="ml-2 text-sm">Đang gửi…</span>
                </button>
            </div>
        </form>
    </div>

    <script>
        // Toggle visibility to behave like an "Add" form
        document.addEventListener('DOMContentLoaded', function () {
            var open = document.getElementById('open-clue-form');
            var panel = document.getElementById('clue-form-panel');
            if (open && panel) {
                open.addEventListener('click', function () {
                    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
                    // scroll into view when opening
                    if (panel.style.display === 'block') panel.scrollIntoView({behavior: 'smooth', block: 'center'});
                });
            }
        });

        function getLocation(button) {
            if (!navigator.geolocation) {
                alert('Trình duyệt không hỗ trợ Geolocation.');
                return;
            }
            const el = button;
            const oldText = el ? el.innerHTML : null;
            if (el) el.disabled = true;
            navigator.geolocation.getCurrentPosition(function(pos) {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                if (window.livewire && typeof window.livewire.emit === 'function') {
                    window.livewire.emit('setLocation', {lat: lat, lng: lng});
                }
                if (el) el.disabled = false;
            }, function(err) {
                alert('Không thể lấy vị trí: ' + err.message);
                if (el) el.disabled = false;
            }, { enableHighAccuracy: true, timeout: 10000 });
        }
    </script>
</div>
