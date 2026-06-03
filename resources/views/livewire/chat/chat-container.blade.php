<div class="w-full h-full">

    <div class="grid grid-cols-12 h-full">

        {{-- Sidebar --}}
        <div class="col-span-4 border-r bg-white flex flex-col min-h-0">

            <div class="h-24 border-b px-6 flex flex-col justify-center">

                <h2 class="text-3xl font-bold text-slate-800">
                    💬 Tin nhắn
                </h2>

                <p class="text-sm text-slate-500">
                    Cuộc trò chuyện gần đây
                </p>

            </div>

            <div class="flex-1 overflow-y-auto">

                @livewire('chat.chat-list')

            </div>

        </div>

        {{-- Chat Content --}}
        <div class="col-span-8 h-full min-h-0">

            @if ($selectedUserId)
                @livewire(
                    'chat.chat-box',
                    [
                        'user' => $selectedUserId,
                        'missing' => $selectedMissingId,
                    ],
                    key('chat-' . $selectedUserId)
                )
            @else
                <div class="h-full flex flex-col">

                    <div class="h-24 border-b flex items-center px-6">

                        <span class="font-semibold text-slate-500">
                            Chưa chọn cuộc trò chuyện
                        </span>

                    </div>

                    <div
                        class="
                    flex-1
                    flex
                    items-center
                    justify-center
                    bg-slate-50
                    ">

                        <div class="text-center">

                            <div class="text-7xl mb-4">
                                💬
                            </div>

                            <h3 class="text-2xl font-bold text-slate-700">
                                Chọn người để bắt đầu chat
                            </h3>

                            <p class="text-slate-500 mt-2">
                                Chọn một cuộc trò chuyện bên trái.
                            </p>

                        </div>

                    </div>

                    <div class="border-t p-4 bg-white">

                        <div class="flex gap-3">

                            <textarea disabled rows="2" class="flex-1 border rounded-xl p-3 bg-slate-100"
                                placeholder="Chọn người để bắt đầu chat..."></textarea>

                            <button disabled class="px-6 bg-slate-300 text-white rounded-xl">
                                Gửi
                            </button>

                        </div>

                    </div>

                </div>
            @endif

        </div>

    </div>

</div>
