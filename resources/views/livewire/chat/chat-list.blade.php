<div>

    <div wire:loading.class.remove="hidden" class="hidden">
        @for ($i = 0; $i < 4; $i++)
            <div class="w-full border-b border-slate-200 p-4">
                <div class="flex items-center gap-3">
                    <x-ui.skeleton width="48px" height="48px" rounded="rounded-full" class="shrink-0 md:w-14 md:h-14" />

                    <div class="flex-1 min-w-0 space-y-2">
                        <div class="flex items-center gap-2">
                            <x-ui.skeleton height="18px" width="58%" />
                            <x-ui.skeleton height="22px" width="28px" rounded="rounded-full" />
                        </div>

                        <x-ui.skeleton height="14px" width="76%" />
                    </div>

                    <x-ui.skeleton width="12px" height="12px" rounded="rounded-full" />
                </div>
            </div>
        @endfor
    </div>

    <div wire:loading.class="hidden">

        @forelse($users as $user)

            <button wire:click="$dispatch('openChat', {
                userId: {{ $user->id }},
                missingId: null
            })" class="
                w-full
                border-b
                border-slate-200
                hover:bg-slate-50
                transition
                text-left
                p-3
                md:p-4
            ">

                <div class="flex items-center gap-3">

                    <img src="{{ $user->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->full_name) }}"
                        class="h-12 w-12 rounded-full object-cover md:h-14 md:w-14">

                    <div class="flex-1 min-w-0">

                        <div class="flex items-center gap-2">
                            <div class="truncate text-sm font-bold text-slate-800 md:text-base">
                                {{ $user->full_name }}
                            </div>

                            @if ($user->unread_count > 0)
                                <span
                                    class="min-w-6 h-6 px-2 rounded-full bg-red-600 text-white text-xs font-bold flex items-center justify-center">
                                    {{ $user->unread_count > 99 ? '99+' : $user->unread_count }}
                                </span>
                            @endif
                        </div>

                        <div class="truncate text-xs text-slate-500 md:text-sm">
                            Nhấn để mở cuộc trò chuyện
                        </div>

                    </div>

                    <div class="h-2.5 w-2.5 rounded-full bg-success md:h-3 md:w-3"></div>

                </div>

            </button>

        @empty

            <div class="p-6 text-center text-slate-400">

                Chưa có cuộc trò chuyện nào

            </div>

        @endforelse

    </div>

</div>