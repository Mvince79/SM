<div>

<div wire:loading.class.remove="hidden" class="hidden">
    @for ($i = 0; $i < 4; $i++)
        <div class="w-full border-b p-4">
            <div class="flex items-center gap-3">
                <x-ui.skeleton width="56px" height="56px" rounded="rounded-full" class="shrink-0" />

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

    <button
        wire:click="$dispatch('openChat', {
            userId: {{ $user->id }},
            missingId: null
        })"
        class="
            w-full
            border-b
            hover:bg-blue-50
            transition
            text-left
            p-4
        "
    >

        <div class="flex items-center gap-3">

            <img
                src="{{ $user->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->full_name) }}"
                class="w-14 h-14 rounded-full object-cover"
            >

            <div class="flex-1 min-w-0">

                <div class="flex items-center gap-2">
                    <div class="font-bold text-slate-800 truncate">
                        {{ $user->full_name }}
                    </div>

                    @if ($user->unread_count > 0)
                        <span
                            class="min-w-6 h-6 px-2 rounded-full bg-red-600 text-white text-xs font-bold flex items-center justify-center">
                            {{ $user->unread_count > 99 ? '99+' : $user->unread_count }}
                        </span>
                    @endif
                </div>

                <div class="text-sm text-slate-500 truncate">
                    Nhấn để mở cuộc trò chuyện
                </div>

            </div>

            <div
                class="w-3 h-3 rounded-full bg-green-500"
            ></div>

        </div>

    </button>

@empty

    <div class="p-6 text-center text-slate-400">

        Chưa có cuộc trò chuyện nào

    </div>

@endforelse

</div>

</div>
