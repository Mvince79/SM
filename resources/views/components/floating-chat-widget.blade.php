{{--
┌─────────────────────────────────────────────────────────────────────────┐
│ @component('components.floating-chat-widget')                           │
│ M-06 — Widget chat nổi (floating) + hotline khẩn cấp                    │
│                                                                           │
│ • Hiện ở mọi trang (đặt 1 lần trong layouts/app.blade.php).             │
│ • Alpine.js quản lý trạng thái mở/thu gọn — lưu ở sessionStorage        │
│   (KHÔNG dùng localStorage) để tránh vướng giới hạn CSP/artifact.       │
│ • Hotline khẩn cấp (111 — Tổng đài Quốc gia Bảo vệ Trẻ em) luôn hiển    │
│   thị kể cả khi widget đang thu gọn.                                    │
│ • Cửa sổ chat nhanh gọi thẳng API chatbot công khai, không cần đăng     │
│   nhập: POST /api/v1/chatbot/message.                                  │
└─────────────────────────────────────────────────────────────────────────┘
--}}

<div
    x-data="floatingChatWidget()"
    x-init="init()"
    class="fixed bottom-5 right-5 z-[9997] flex flex-col items-end gap-3"
>
    {{-- ── Cửa sổ chat nhanh (mở rộng) ── --}}
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 scale-95"
        class="flex h-[min(560px,calc(100dvh-11rem))] w-[min(360px,calc(100vw-2.5rem))] flex-col overflow-hidden
               rounded-3xl border border-slate-200/85 bg-white"
        style="box-shadow: var(--shadow-premium);"
        role="dialog"
        aria-label="Cửa sổ chat hỗ trợ nhanh"
    >
        {{-- Header --}}
        <div class="flex shrink-0 items-center justify-between gap-3 bg-gradient-to-r from-primary to-primary-dark px-4 py-3.5">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/20">
                    <x-heroicon-o-sparkles class="h-4 w-4 text-white" />
                </div>
                <div class="min-w-0">
                    <p class="truncate text-sm font-extrabold text-white">Trợ lý TìmTrẻ.vn</p>
                    <p class="flex items-center gap-1 text-[11px] font-semibold text-white/80">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-300"></span>
                        Sẵn sàng hỗ trợ 24/7
                    </p>
                </div>
            </div>
            <button type="button" @click="toggle()"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-white/90 transition-colors hover:bg-white/15"
                aria-label="Thu gọn khung chat">
                <x-heroicon-o-chevron-down class="h-4.5 w-4.5" />
            </button>
        </div>

        {{-- Hotline luôn hiển thị trong panel chat --}}
        <a href="tel:111"
            class="flex shrink-0 items-center justify-between gap-2 border-b border-slate-100 bg-red-50/70 px-4 py-2.5 transition-colors hover:bg-red-50">
            <span class="flex items-center gap-2 text-xs font-bold text-red-600">
                <x-heroicon-s-phone class="h-3.5 w-3.5" />
                Khẩn cấp — Tổng đài 111 (miễn phí)
            </span>
            <span class="text-[11px] font-extrabold text-red-600">Gọi ngay →</span>
        </a>

        {{-- Danh sách tin nhắn --}}
        <div x-ref="messagesBox" class="flex-1 space-y-3 overflow-y-auto px-4 py-4">
            <template x-for="(msg, index) in messages" :key="index">
                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div class="max-w-[85%] space-y-2">
                        <div
                            :class="msg.role === 'user'
                                ? 'bg-primary text-white rounded-2xl rounded-br-md'
                                : 'bg-slate-100 text-slate-700 rounded-2xl rounded-bl-md'"
                            class="px-3.5 py-2.5 text-[13px] font-medium leading-relaxed whitespace-pre-line">
                            <span x-text="msg.content"></span>
                        </div>

                        {{-- Gợi ý điểm bàn giao (nếu có) --}}
                        <template x-if="msg.handover_points && msg.handover_points.length">
                            <div class="space-y-2">
                                <template x-for="point in msg.handover_points" :key="point.id">
                                    <a :href="point.directions_url" target="_blank" rel="noopener"
                                        class="block rounded-xl border border-slate-200 bg-white px-3 py-2.5 transition-colors hover:border-primary/40 hover:bg-primary-light">
                                        <p class="text-xs font-extrabold text-slate-800" x-text="point.name"></p>
                                        <p class="mt-0.5 text-[11px] font-medium text-slate-500" x-text="point.address"></p>
                                        <p class="mt-1 flex items-center gap-1.5 text-[11px] font-bold text-primary">
                                            <span x-text="point.distance_km + ' km'"></span>
                                            <span class="text-slate-300">•</span>
                                            <span x-text="'Hotline ' + point.hotline_phone"></span>
                                        </p>
                                    </a>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Đang xử lý --}}
            <div x-show="loading" x-cloak class="flex justify-start">
                <div class="flex items-center gap-1.5 rounded-2xl rounded-bl-md bg-slate-100 px-4 py-3">
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-400" style="animation-delay:0ms"></span>
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-400" style="animation-delay:120ms"></span>
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-400" style="animation-delay:240ms"></span>
                </div>
            </div>
        </div>

        {{-- Gợi ý nhanh --}}
        <div class="shrink-0 border-t border-slate-100 px-4 py-2.5">
            <div class="flex flex-wrap gap-1.5">
                <button type="button" @click="sendQuick('Tôi nên đưa bé đến đâu?')"
                    class="rounded-full border border-slate-200 px-3 py-1.5 text-[11px] font-bold text-slate-600 transition-colors hover:border-primary/40 hover:text-primary">
                    Tôi nên đưa bé đến đâu?
                </button>
                <button type="button" @click="sendQuick('KYC cần giấy tờ gì?')"
                    class="rounded-full border border-slate-200 px-3 py-1.5 text-[11px] font-bold text-slate-600 transition-colors hover:border-primary/40 hover:text-primary">
                    KYC cần gì?
                </button>
                <button type="button" @click="sendQuick('Tôi đăng tin trẻ mất tích như thế nào?')"
                    class="rounded-full border border-slate-200 px-3 py-1.5 text-[11px] font-bold text-slate-600 transition-colors hover:border-primary/40 hover:text-primary">
                    Cách đăng tin
                </button>
            </div>
        </div>

        {{-- Ô nhập --}}
        <form @submit.prevent="send()" class="flex shrink-0 items-center gap-2 border-t border-slate-100 p-3">
            <input type="text" x-model="draft" :disabled="loading" maxlength="1000"
                placeholder="Nhập câu hỏi của bạn…"
                class="flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm font-medium
                       placeholder-slate-400 transition-all focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary disabled:opacity-60">
            <button type="submit" :disabled="loading || !draft.trim()"
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary text-white transition-all hover:bg-primary-dark disabled:cursor-not-allowed disabled:opacity-40"
                aria-label="Gửi tin nhắn">
                <x-heroicon-o-paper-airplane class="h-4 w-4" />
            </button>
        </form>
    </div>

    {{-- ── Thanh thu gọn: luôn hiển thị hotline + nút mở chat ── --}}
    <div class="flex items-center gap-2">
        {{-- Hotline khẩn cấp — LUÔN hiển thị dù widget mở hay thu gọn --}}
        <a href="tel:111" aria-label="Gọi hotline khẩn cấp 111 — Tổng đài Quốc gia Bảo vệ Trẻ em"
            class="group flex items-center gap-2 rounded-full border-2 border-red-100 bg-white px-4 py-3 transition-all
                   hover:border-red-200 hover:-translate-y-0.5"
            style="box-shadow: var(--shadow-premium);">
            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-red-500">
                <x-heroicon-s-phone class="h-3.5 w-3.5 text-white" />
            </span>
            <span class="text-xs font-black text-slate-800 whitespace-nowrap">
                111 <span class="hidden font-bold text-slate-400 sm:inline">· Khẩn cấp</span>
            </span>
        </a>

        {{-- Nút mở / thu gọn chat --}}
        <button type="button" @click="toggle()"
            aria-label="Mở khung chat hỗ trợ nhanh"
            :aria-expanded="open.toString()"
            class="flex h-[52px] w-[52px] shrink-0 items-center justify-center rounded-full bg-slate-900 text-white
                   transition-all hover:-translate-y-0.5 hover:bg-slate-700 active:scale-95"
            style="box-shadow: var(--shadow-premium);">
            <x-heroicon-o-chat-bubble-left-right x-show="!open" class="h-5 w-5" />
            <x-heroicon-o-chevron-down x-show="open" x-cloak class="h-5 w-5" />
        </button>
    </div>
</div>

<script>
    function floatingChatWidget() {
        const STORAGE_KEYS = {
            open: 'ttv_chat_widget_open',
            token: 'ttv_chat_session_token',
            messages: 'ttv_chat_widget_messages',
        };

        // Vị trí mặc định nếu trình duyệt từ chối/không hỗ trợ định vị (trung tâm Hà Nội).
        const DEFAULT_COORDS = { lat: 21.0278, lng: 105.8342 };

        return {
            open: false,
            loading: false,
            sessionToken: null,
            draft: '',
            coords: { lat: null, lng: null },
            messages: [],

            init() {
                this.open = this.readSession(STORAGE_KEYS.open) === '1';
                this.sessionToken = this.readSession(STORAGE_KEYS.token);

                const savedMessages = this.readSession(STORAGE_KEYS.messages);
                if (savedMessages) {
                    try {
                        this.messages = JSON.parse(savedMessages);
                    } catch (e) {
                        this.messages = [];
                    }
                }

                if (!this.messages.length) {
                    this.messages.push({
                        role: 'assistant',
                        content: 'Xin chào! Mình là trợ lý ảo của TìmTrẻ.vn. Bạn có thể hỏi về điểm bàn giao an toàn, KYC, AI đối chiếu khuôn mặt, bảo mật dữ liệu, cách đăng tin hoặc liên hệ hỗ trợ. Trường hợp khẩn cấp, hãy gọi ngay hotline 111 bên dưới.',
                    });
                }

                this.locateUser();
                this.$nextTick(() => this.scrollToBottom());
            },

            toggle() {
                this.open = !this.open;
                this.writeSession(STORAGE_KEYS.open, this.open ? '1' : '0');
                if (this.open) this.$nextTick(() => this.scrollToBottom());
            },

            locateUser() {
                if (!navigator.geolocation) {
                    this.coords = { ...DEFAULT_COORDS };
                    return;
                }

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        this.coords = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude,
                        };
                    },
                    () => {
                        this.coords = { ...DEFAULT_COORDS };
                    },
                    { timeout: 5000, maximumAge: 300000 },
                );
            },

            sendQuick(text) {
                this.draft = text;
                this.send();
            },

            async send() {
                const text = this.draft.trim();
                if (!text || this.loading) return;

                this.messages.push({ role: 'user', content: text });
                this.draft = '';
                this.loading = true;
                this.persistMessages();
                this.$nextTick(() => this.scrollToBottom());

                try {
                    const response = await fetch('/api/v1/chatbot/message', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            message: text,
                            lat: this.coords.lat ?? DEFAULT_COORDS.lat,
                            lng: this.coords.lng ?? DEFAULT_COORDS.lng,
                            session_token: this.sessionToken ?? undefined,
                        }),
                    });

                    if (!response.ok) {
                        throw new Error('Chatbot request failed');
                    }

                    const json = await response.json();
                    const data = json.data ?? {};

                    if (data.session_token) {
                        this.sessionToken = data.session_token;
                        this.writeSession(STORAGE_KEYS.token, this.sessionToken);
                    }

                    this.messages.push({
                        role: 'assistant',
                        content: data.message ?? 'Xin lỗi, mình chưa có câu trả lời phù hợp.',
                        handover_points: data.handover_points ?? null,
                    });
                } catch (error) {
                    this.messages.push({
                        role: 'assistant',
                        content: 'Xin lỗi, có lỗi khi gửi tin nhắn. Vui lòng thử lại hoặc gọi hotline 111 bên dưới nếu cần hỗ trợ khẩn cấp.',
                    });
                } finally {
                    this.loading = false;
                    this.persistMessages();
                    this.$nextTick(() => this.scrollToBottom());
                }
            },

            persistMessages() {
                this.writeSession(STORAGE_KEYS.messages, JSON.stringify(this.messages.slice(-20)));
            },

            scrollToBottom() {
                const el = this.$refs.messagesBox;
                if (el) el.scrollTop = el.scrollHeight;
            },

            // sessionStorage (KHÔNG dùng localStorage) — bọc try/catch vì có thể bị chặn
            // bởi chế độ duyệt web riêng tư hoặc chính sách CSP nghiêm ngặt. Khi bị chặn,
            // widget vẫn hoạt động bình thường, chỉ không nhớ trạng thái giữa các lượt tải trang.
            readSession(key) {
                try {
                    return sessionStorage.getItem(key);
                } catch (e) {
                    return null;
                }
            },

            writeSession(key, value) {
                try {
                    sessionStorage.setItem(key, value);
                } catch (e) {
                    // Bỏ qua — chỉ mất trạng thái ghi nhớ, không ảnh hưởng chức năng chat.
                }
            },
        };
    }
</script>
