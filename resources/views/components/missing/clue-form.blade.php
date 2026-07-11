{{-- ════ Clue / Manh mối form — Livewire v3 ════ --}}
<div x-data="{ open: false }" class="space-y-3">

    {{-- E-01c: hidden idempotency key — chống double-submit (double-click nhanh,
         Livewire request bị gửi lại do mất mạng...). Được sinh mới ở mount() và
         sau mỗi lần submit thành công (xem ClueForm::submit()). --}}
    <input type="hidden" wire:model="idempotencyKey" />

    {{-- ── Header row ── --}}
    <div class="flex items-center justify-between">
        <h4 class="text-sm font-extrabold text-slate-800">Manh mối từ cộng đồng</h4>
        <button type="button" @click="open = !open" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-2xl text-xs font-extrabold transition-all
                   bg-primary text-white shadow-sm hover:brightness-105 active:scale-95">
            <template x-if="!open">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24"
                    stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
            </template>
            <template x-if="open">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24"
                    stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </template>
            <span x-text="open ? 'Đóng' : 'Gửi manh mối'"></span>
        </button>
    </div>

    {{-- ── Flash success ── --}}
    @if (session()->has('clue_submitted'))
        <div class="flex items-start gap-3 rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3" x-data
            x-init="setTimeout(() => $el.remove(), 5000)">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-500 mt-0.5 shrink-0" fill="none"
                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <p class="text-sm font-bold text-emerald-800">{{ session('clue_submitted') }}</p>
        </div>
    @endif

    {{-- ── Form panel ── --}}
    <div x-show="open" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-1" x-cloak>
        <div class="bg-white border border-slate-200/85 rounded-3xl shadow-premium overflow-hidden">

            {{-- Top accent bar --}}
            <div class="h-1 w-full bg-gradient-to-r from-primary via-red-400 to-orange-400"></div>

            <div class="p-5 space-y-5">

                {{-- Eyebrow --}}
                <div class="flex items-center gap-2.5">
                    <div
                        class="w-9 h-9 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center shrink-0 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                            stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-black text-slate-900 leading-tight">Bạn có thấy bé không?</p>
                        <p class="text-xs text-slate-400 font-medium mt-0.5">Mỗi manh mối đều quý giá — hãy chia sẻ
                            ngay.</p>
                    </div>
                </div>

                {{-- ── 1. Mô tả ── --}}
                <div class="space-y-1.5">
                    <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">
                        Bạn thấy gì? <span class="text-red-500">*</span>
                    </label>
                    <textarea wire:model.blur="description" rows="4"
                        placeholder="VD: Tôi thấy một bé trai mặc áo xanh khoảng 7–8 tuổi, đứng một mình gần chợ Bình Tây lúc 14h hôm nay…"
                        class="w-full px-4 py-3 border rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white
                               transition-all font-medium resize-none placeholder-slate-400
                               {{ $errors->has('description') ? 'border-red-400' : 'border-slate-200' }}"></textarea>
                    @error('description')
                        <p class="text-xs text-red-500 font-semibold flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0" fill="none"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- ── 2. Vị trí — GPS + Map chọn bất kỳ ── --}}
                <div class="space-y-2">
                    <label
                        class="text-xs font-extrabold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400" fill="none"
                            viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                        </svg>
                        Vị trí phát hiện
                        <span class="normal-case font-medium text-slate-400">(tuỳ chọn)</span>
                    </label>
                    <p class="text-xs text-slate-400">Bấm nút bên dưới để lấy vị trí hiện tại, hoặc nhấn trực tiếp vào
                        bản đồ để ghim vị trí bất kỳ.</p>

                    <div wire:ignore x-data="{
                        map: null,
                        marker: null,
                        locating: false,
                        initLat: @js($lat ?: null),
                        initLng: @js($lng ?: null),
                        init() {
                            if (window._clueMapInstance) {
                                try { window._clueMapInstance.remove(); } catch(e) {}
                                window._clueMapInstance = null;
                            }
                            const el = document.getElementById('clue-map-picker');
                            if (el && el._leaflet_id) { delete el._leaflet_id; }
                            const hasSaved = this.initLat !== null && this.initLng !== null;
                            const center = hasSaved ? [this.initLat, this.initLng] : [10.7769, 106.7009];
                            const zoom = hasSaved ? 15 : 12;

                            this.map = L.map(el || 'clue-map-picker').setView(center, zoom);
                            window._clueMapInstance = this.map;

                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                attribution: '© OpenStreetMap'
                            }).addTo(this.map);

                            if (hasSaved) {
                                this.marker = L.marker([this.initLat, this.initLng])
                                    .addTo(this.map)
                                    .bindPopup('Vị trí đã ghim').openPopup();
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
                            const self = this;
                            self.locating = true;

                            // Hard timeout 12s — tránh spinner kẹt mãi
                            const hardTimer = setTimeout(() => {
                                if (self.locating) {
                                    self.locating = false;
                                    alert('Hết thời gian chờ định vị. Vui lòng thử lại hoặc nhấn vào bản đồ để ghim tay.');
                                }
                            }, 12000);

                            const onSuccess = (pos) => {
                                clearTimeout(hardTimer);
                                const lat = pos.coords.latitude;
                                const lng = pos.coords.longitude;
                                if (self.map) {
                                    self.map.setView([lat, lng], 16);
                                    if (self.marker) self.marker.remove();
                                    self.marker = L.marker([lat, lng])
                                        .addTo(self.map)
                                        .bindPopup('Vị trí hiện tại của bạn').openPopup();
                                }
                                $wire.set('lat', lat.toFixed(6));
                                $wire.set('lng', lng.toFixed(6));
                                self.locating = false;
                            };

                            const onError = (err) => {
                                clearTimeout(hardTimer);
                                self.locating = false;
                                const msgs = {
                                    1: 'Bạn đã từ chối quyền vị trí. Vui lòng cho phép trong cài đặt trình duyệt (🔒 → Location → Allow) rồi thử lại.',
                                    2: 'Không xác định được vị trí. Hãy thử lại hoặc nhấn vào bản đồ để ghim tay.',
                                    3: 'Hết thời gian chờ. Hãy thử lại hoặc nhấn vào bản đồ để ghim tay.',
                                };
                                alert(msgs[err.code] || 'Lỗi định vị: ' + err.message);
                            };

                            const opts = { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 };

                            // Bypass gps-guard: dùng native API nếu có, fallback thường
                            try { window.__lastUserGestureAt = Date.now(); } catch(e) {}
                            if (window.allowGeolocationForNextCall) window.allowGeolocationForNextCall();
                            if (window.__nativeGeolocationGet) {
                                window.__nativeGeolocationGet(onSuccess, onError, opts);
                            } else {
                                navigator.geolocation.getCurrentPosition(onSuccess, onError, opts);
                            }
                        }
                    }" x-init="init()">

                        {{-- GPS button — đồng bộ style với create-missing-form --}}
                        <button type="button" @click="locateMe()" :disabled="locating" class="mb-3 inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl
                                   bg-primary/10 text-primary font-extrabold text-xs
                                   hover:bg-primary/20 active:scale-95 transition-all
                                   disabled:opacity-50 disabled:cursor-not-allowed">
                            <template x-if="!locating">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none"
                                    viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                </svg>
                            </template>
                            <template x-if="locating">
                                <span
                                    class="w-3.5 h-3.5 border-2 border-primary/40 border-t-primary rounded-full animate-spin"></span>
                            </template>
                            <span x-text="locating ? 'Đang định vị…' : 'Lấy vị trí hiện tại'"></span>
                        </button>

                        {{-- Map --}}
                        <div id="clue-map-picker" class="w-full h-48 rounded-2xl border border-slate-200 z-0"></div>

                    </div>

                    @if ($lat && $lng)
                        <p class="text-xs text-success font-bold flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24"
                                stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            Đã ghim: {{ round((float) $lat, 5) }}, {{ round((float) $lng, 5) }}
                            &nbsp;·&nbsp;
                            <a href="https://www.google.com/maps/search/?api=1&query={{ $lat }},{{ $lng }}" target="_blank"
                                rel="noopener noreferrer" class="underline underline-offset-2 hover:opacity-80">
                                Xem trên Google Maps
                            </a>
                        </p>
                    @endif
                </div>

                {{-- ── 3. Ảnh ── --}}
                <div class="space-y-2">
                    <label class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">
                        Ảnh đính kèm
                        <span class="normal-case font-medium text-slate-400">(tuỳ chọn)</span>
                    </label>

                    @if ($image)
                        <div class="relative inline-block">
                            <img src="{{ $image->temporaryUrl() }}"
                                class="max-h-44 rounded-2xl border border-slate-200 object-contain shadow-sm"
                                alt="Ảnh đính kèm" />
                            <button type="button" wire:click="$set('image', null)"
                                class="absolute -top-2 -right-2 w-6 h-6 bg-rose-500 text-white rounded-full flex items-center justify-center
                                           text-xs font-black shadow-md hover:bg-rose-600 transition-colors">&times;</button>
                        </div>
                    @else
                        <div class="border-2 border-dashed border-slate-200 hover:border-primary bg-slate-50 hover:bg-white
                                       rounded-3xl p-6 text-center transition-all duration-200 cursor-pointer"
                            onclick="document.getElementById('clue-image-upload').click()">
                            <div
                                class="w-10 h-10 bg-primary/10 text-primary flex items-center justify-center rounded-2xl mx-auto mb-2 shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                                </svg>
                            </div>
                            <p class="text-xs font-black text-primary">Ấn để chọn ảnh</p>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-1">JPG, PNG, WebP ·
                                5MB</p>
                        </div>
                    @endif

                    <input type="file" id="clue-image-upload" wire:model="image"
                        accept="image/jpeg,image/png,image/webp" class="hidden" />

                    <div wire:loading wire:target="image"
                        class="flex items-center gap-2 text-xs text-slate-400 font-semibold">
                        <span
                            class="w-3.5 h-3.5 border-2 border-slate-200 border-t-primary rounded-full animate-spin"></span>
                        Đang tải ảnh lên...
                    </div>

                    @error('image')
                        <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                {{-- ── 4. Ẩn danh ── --}}
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4">
                    <label class="flex items-start gap-3 cursor-pointer select-none">
                        <input type="checkbox" wire:model="is_anonymous"
                            class="mt-0.5 w-4 h-4 accent-primary rounded shrink-0 cursor-pointer" />
                        <div>
                            <span class="text-sm font-extrabold text-slate-700">Gửi ẩn danh</span>
                            <p class="text-[11px] text-slate-400 font-medium mt-0.5">
                                Tên của bạn sẽ <strong class="font-bold text-slate-600">không hiển thị</strong> công
                                khai. Manh mối vẫn được gửi đầy đủ.
                            </p>
                        </div>
                    </label>
                </div>

                {{-- ── Actions ── --}}
                <div class="flex items-center justify-between pt-1 border-t border-slate-100">
                    <button type="button" @click="open = false"
                        class="px-5 py-3 rounded-2xl border border-slate-200 text-slate-500 font-extrabold text-sm hover:bg-slate-50 transition-colors">
                        Huỷ
                    </button>
                    <button type="button" wire:click="submit" wire:loading.attr="disabled" wire:target="submit,image"
                        class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-primary text-white font-extrabold text-sm
                               shadow-md shadow-primary/20 hover:bg-primary-dark transition-all
                               disabled:opacity-60 disabled:cursor-not-allowed active:scale-95">
                        <span wire:loading wire:target="submit"
                            class="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24"
                            stroke-width="2" stroke="currentColor" wire:loading.remove wire:target="submit">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                        </svg>
                        Gửi manh mối
                    </button>
                </div>

            </div>{{-- /p-5 --}}
        </div>
    </div>

</div>

{{-- Leaflet — load cùng trang detail --}}
@once
    @push('head')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endpush
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @endpush
@endonce