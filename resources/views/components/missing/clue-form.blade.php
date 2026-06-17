<div class="max-w-xl mx-auto p-4 bg-white rounded-lg shadow">
    @php $isGuest = auth()->guest(); @endphp
    @if (session()->has('clue_submitted'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 p-3 text-green-800">{{ session('clue_submitted') }}</div>
    @endif

    @if ($isGuest)
        <div class="mb-4 rounded-md bg-blue-50 border border-blue-100 p-3 text-blue-800">
            Bạn cần <a href="{{ url('/auth/login') }}" class="font-semibold underline">đăng nhập</a> để gửi manh mối.
        </div>
    @endif

    <form wire:submit.prevent="submit" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-slate-700">Mô tả</label>
            <textarea wire:model.defer="description" rows="4" required placeholder="Mô tả chi tiết (ví dụ: thấy bé mặc áo xanh, ở quận X...)" class="mt-1 block w-full rounded-md border border-slate-200 bg-white px-3 py-2 shadow-sm focus:ring-2 focus:ring-primary focus:border-primary" @disabled($isGuest)></textarea>
            @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-4">
            <button type="button" onclick="getLocation(this)" class="inline-flex items-center gap-2 px-3 py-2 bg-primary text-white rounded-md shadow-sm hover:opacity-95" @disabled($isGuest)>
                <x-heroicon-o-clock class="h-4 w-4" />
                Lấy vị trí GPS
            </button>
            <input type="hidden" id="clue-lat-input" wire:model="lat" />
            <input type="hidden" id="clue-lng-input" wire:model="lng" />
            <div class="text-sm text-slate-600">
                @if($lat && $lng)
                    @php $mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . $lat . ',' . $lng; @endphp
                    Lat/Lng: <a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer" class="font-medium text-primary underline">{{ number_format($lat, 6) }}, {{ number_format($lng, 6) }}</a>
                @else
                    Lat: <span class="font-medium">{{ $lat ?: '—' }}</span>
                    &nbsp;—&nbsp;
                    Lng: <span class="font-medium">{{ $lng ?: '—' }}</span>
                @endif
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700">Ảnh (tùy chọn)</label>
            <div class="mt-2">
                <input type="file" wire:model="image" accept="image/*" class="block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-slate-100 file:text-slate-700" @disabled($isGuest) />
                <div wire:loading wire:target="image" class="mt-2 text-xs text-slate-500">Đang tải…</div>
                @error('image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex items-center">
            <input id="anon" type="checkbox" wire:model="is_anonymous" class="h-4 w-4 text-primary border-gray-300 rounded" @disabled($isGuest) />
            <label for="anon" class="ml-2 text-sm text-slate-700">Gửi ẩn danh</label>
        </div>

        <div class="pt-2 flex items-center gap-3">
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-md shadow-sm" wire:loading.attr="disabled" wire:target="image,submit" @disabled($isGuest)>
                <x-heroicon-o-check class="h-4 w-4" />
                <span>Gửi manh mối</span>
                <span wire:loading wire:target="submit" class="ml-2 text-sm">Đang gửi…</span>
            </button>
        </div>
    </form>

    <script>
        function showGeolocationDeniedHelp() {
            alert('Quyền vị trí bị chặn. Vui lòng cho phép vị trí trong cài đặt trình duyệt (Site settings → Location) và thử lại. Trình duyệt cần HTTPS để cấp quyền vị trí.');
        }

        function getLocation(button) {
            // mark a user gesture now to ensure allow helper recognizes it
            try { window.__lastUserGestureAt = Date.now(); } catch(e) {}
            if (!navigator.geolocation) {
                alert('Trình duyệt không hỗ trợ Geolocation.');
                return;
            }

            const el = button;
            if (el) el.disabled = true;

            // Check permission state if Permissions API available
            if (navigator.permissions && navigator.permissions.query) {
                navigator.permissions.query({ name: 'geolocation' }).then(function(result) {
                    if (result.state === 'denied') {
                        if (el) { try { el.disabled = false; } catch(e){} }
                        showGeolocationDeniedHelp();
                        return;
                    }
                    // state 'prompt' or 'granted' — try to get position
                    // allow next geolocation call (user gesture)
                    if (window.allowGeolocationForNextCall) window.allowGeolocationForNextCall();
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
                }).catch(function(){
                    // if Permissions API fails, fallback to direct geolocation call
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
                });
            } else {
                // No Permissions API — call directly
                    navigator.geolocation.getCurrentPosition(function(pos) {
                        const lat = pos.coords.latitude;
                        const lng = pos.coords.longitude;
                        if (window.livewire && typeof window.livewire.emit === 'function') {
                            window.livewire.emit('setLocation', {lat: lat, lng: lng});
                        }

                        // Removed automatic immediate POST sharing — only emit Livewire and let user submit.
                        if (el) el.disabled = false;
                    }, function(err) {
                        alert('Không thể lấy vị trí: ' + err.message);
                        if (el) el.disabled = false;
                    }, { enableHighAccuracy: true, timeout: 10000 });
            }
        }
    </script>
</div>
