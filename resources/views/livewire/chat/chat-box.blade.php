<div wire:key="chat-{{ $refreshKey }}" class="w-full h-full flex flex-col min-h-0">

    {{-- Header --}}
    <div class="h-16 shrink-0 border-b bg-white px-4 flex items-center md:h-24 md:px-6">

        <img src="{{ $receiver->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($receiver->full_name) }}"
            class="h-10 w-10 rounded-full object-cover md:h-14 md:w-14">

        <div class="ml-3 min-w-0 md:ml-4">

            <div class="truncate text-base font-bold text-slate-800 md:text-2xl">
                {{ $receiver->full_name }}
            </div>

            <div class="text-xs text-green-600 md:text-base">
                ● Đang hoạt động
            </div>

        </div>
    </div>

    {{-- Messages --}}
    <div id="chat-container" x-data="{ loadingMore: false }" x-init="$el.scrollTop = $el.scrollHeight"
        @scroll.throttle.500ms="
            if ($el.scrollTop === 0 && {{ $hasMore ? 'true' : 'false' }} && !loadingMore) {
                loadingMore = true;
                $wire.loadMore().then(() => loadingMore = false);
            }
        "
        @scroll-to-bottom.window="$nextTick(() => { $el.scrollTop = $el.scrollHeight })"
        class="
        flex-1
        min-h-0
        overflow-y-auto
        bg-slate-50
        p-3
        md:p-6
    ">

        @if ($hasMore)
            <div class="text-center mb-4">
                <button type="button" wire:click="loadMore"
                    class="px-4 py-2 text-sm font-semibold text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100">
                    Tải thêm tin nhắn cũ
                </button>
            </div>
        @endif

        @forelse($messages as $message)

            <div class="mb-4
            {{ $message->from_user_id == auth()->id() ? 'text-right' : 'text-left' }}">

                <div
                    class="
                inline-block
                max-w-[85%]
                px-3
                py-2.5
                text-sm
                rounded-2xl
                break-words
                shadow-sm
                md:max-w-[75%]
                md:px-4
                md:py-3
                md:text-base
                {{ $message->from_user_id == auth()->id() ? 'bg-blue-600 text-white' : 'bg-white border' }}
                ">

                    @if ($message->attachment_type === 'IMAGE' && $message->attachment_path)
                        <a href="{{ route('chat.attachment', ['filename' => basename($message->attachment_path)]) }}"
                            target="_blank" class="block {{ $message->body ? 'mb-3' : '' }}">
                            <img
                                src="{{ route('chat.attachment', ['filename' => basename($message->attachment_path)]) }}"
                                alt="Ảnh đính kèm"
                                class="max-h-48 rounded-xl object-cover md:max-h-64 {{ $message->from_user_id == auth()->id() ? 'ml-auto' : '' }}">
                        </a>
                    @endif

                    @if ($message->body)
                        <div>{{ $message->body }}</div>
                    @endif

                    @if ($message->from_user_id == auth()->id())
                        <div class="text-xs mt-2 opacity-80">

                            @if ($message->read_at)
                                ✓✓ Đã xem
                            @else
                                ✓ Đã gửi
                            @endif

                        </div>
                    @endif

                </div>

            </div>

        @empty

            <div class="text-center text-slate-400 mt-20">

                Chưa có tin nhắn nào

            </div>

        @endforelse

    </div>

    {{-- Input --}}
    <div class="
        shrink-0
        border-t
        bg-white
        p-3
        md:p-4
    ">

        <form wire:submit.prevent="send">

            @if ($photo)
                <div class="mb-3 flex items-start gap-3">
                    <img src="{{ $photo->temporaryUrl() }}" alt="Xem trước ảnh"
                        class="h-20 w-20 rounded-xl object-cover border md:h-24 md:w-24">

                    <button type="button" wire:click="$set('photo', null)"
                        class="px-3 py-2 text-sm font-semibold text-red-600 bg-red-50 rounded-lg hover:bg-red-100">
                        Xóa ảnh
                    </button>
                </div>
            @endif

            @error('photo')
                <div class="mb-2 text-sm text-red-600">{{ $message }}</div>
            @enderror

            @error('body')
                <div class="mb-2 text-sm text-red-600">{{ $message }}</div>
            @enderror

            <div class="flex items-end gap-2 md:gap-3">

                <label
                    class="
                h-11
                w-11
                md:h-14
                md:w-14
                shrink-0
                border
                rounded-xl
                flex
                items-center
                justify-center
                text-slate-600
                hover:bg-slate-50
                cursor-pointer
                "
                    title="Đính kèm ảnh">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 md:h-6 md:w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M18.364 5.636 9.172 14.828a4 4 0 1 0 5.657 5.657l9.192-9.192a6 6 0 0 0-8.485-8.485L6.343 12a8 8 0 1 0 11.314 11.314l8.485-8.485" />
                    </svg>
                    <input type="file" wire:model="photo" accept="image/*" class="hidden">
                </label>

                <textarea wire:model="body" rows="2"
                    class="
                flex-1
                border
                rounded-xl
                p-3
                text-sm
                resize-none
                focus:outline-none
                focus:ring-2
                focus:ring-blue-500
                md:text-base
                "
                    placeholder="Nhập tin nhắn..."></textarea>

                <button type="submit"
                    class="
                h-11
                px-4
                text-sm
                font-semibold
                bg-blue-600
                text-white
                rounded-xl
                hover:bg-blue-700
                transition
                md:h-14
                md:px-6
                md:text-base
                ">
                    Gửi
                </button>

            </div>

        </form>

    </div>

</div>
