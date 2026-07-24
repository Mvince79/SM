<div
    x-data="{ show: !sessionStorage.getItem('handover_banner_dismissed') }"
    x-show="show"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 -translate-y-1"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 -translate-y-1"
    class="shrink-0 border-b border-amber-300 bg-amber-50 px-3 py-2.5 md:px-6 md:py-3"
>
    <div class="flex items-start gap-2.5 md:items-center md:gap-3">
        <x-heroicon-o-exclamation-triangle class="h-5 w-5 shrink-0 text-amber-600 md:h-6 md:w-6" />

        <p class="flex-1 text-xs font-semibold leading-snug text-amber-800 md:text-sm">
            Không tự ý giao nhận trẻ trực tiếp. Việc bàn giao chỉ được thực hiện qua
            điểm bàn giao / bên thứ ba có thẩm quyền (Công an, Trung tâm CTXH...).
        </p>

        <button
            type="button"
            @click="show = false; sessionStorage.setItem('handover_banner_dismissed', '1')"
            class="shrink-0 text-amber-500 transition-colors hover:text-amber-700"
            title="Đóng thông báo"
        >
            <x-heroicon-o-x-mark class="h-4 w-4 md:h-5 md:w-5" />
        </button>
    </div>
</div>