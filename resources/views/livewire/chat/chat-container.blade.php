<div class="h-full w-full">
    <div class="grid h-full grid-cols-12">
        <div class="{{ $selectedUserId ? 'hidden md:flex' : 'flex' }} col-span-12 min-h-0 flex-col border-r border-slate-200 bg-white md:col-span-4">
            <div class="flex h-16 flex-col justify-center border-b border-slate-200 px-4 md:h-24 md:px-6">
                <h2 class="text-xl font-bold text-slate-800 md:text-3xl">
                    Tin nhắn
                </h2>

                <p class="text-xs text-slate-500 md:text-sm">
                    Cuộc trò chuyện gần đây
                </p>
            </div>

            <div class="flex-1 overflow-y-auto">
                @livewire('chat.chat-list')
            </div>
        </div>

        <div class="{{ $selectedUserId ? 'flex' : 'hidden md:flex' }} col-span-12 h-full min-h-0 flex-col md:col-span-8">
            @if ($selectedUserId)
                <div class="flex h-full min-h-0 flex-col">
                    <div class="flex h-14 shrink-0 items-center gap-3 border-b border-slate-200 bg-white px-4 md:hidden">
                        <button type="button" wire:click="closeChatThread"
                            class="flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 text-slate-600 hover:bg-slate-50"
                            aria-label="Quay lại danh sách tin nhắn">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M15 18l-6-6 6-6" />
                            </svg>
                        </button>
                        <span class="text-sm font-bold text-slate-800">Tin nhắn</span>
                    </div>

                    <div class="min-h-0 flex-1">
                        @livewire(
                            'chat.chat-box',
                            [
                                'user' => $selectedUserId,
                                'missing' => $selectedMissingId,
                            ],
                            key('chat-' . $selectedUserId)
                        )
                    </div>
                </div>
            @else
                <div class="flex h-full flex-col">
                    <div class="flex h-24 items-center border-b border-slate-200 px-6">
                        <span class="font-semibold text-slate-500">
                            Chưa chọn cuộc trò chuyện
                        </span>
                    </div>

                    <div class="flex flex-1 items-center justify-center bg-slate-50">
                        <div class="px-6 text-center">
                            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-50 text-blue-600">
                                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M21 15a4 4 0 0 1-4 4H7l-4 4V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z" />
                                </svg>
                            </div>

                            <h3 class="text-2xl font-bold text-slate-700">
                                Chọn người để bắt đầu chat
                            </h3>

                            <p class="mt-2 text-slate-500">
                                Chọn một cuộc trò chuyện bên trái.
                            </p>
                        </div>
                    </div>

                    <div class="border-t border-slate-200 bg-white p-4">
                        <div class="flex gap-3">
                            <textarea disabled rows="2" class="flex-1 resize-none rounded-xl border border-slate-200 bg-slate-100 p-3"
                                placeholder="Chọn người để bắt đầu chat..."></textarea>

                            <button disabled class="rounded-xl bg-slate-300 px-6 text-white">
                                Gửi
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
