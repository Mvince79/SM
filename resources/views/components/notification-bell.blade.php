@auth
    <div class="relative" data-notification-bell>
        <button type="button"
            class="relative inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-primary hover:bg-primary/5 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
            data-notification-toggle aria-label="Thông báo" aria-expanded="false">
            <x-heroicon-o-bell class="h-5 w-5" />
            <span
                class="hidden absolute -right-1 -top-1 flex min-h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-center text-[11px] font-bold leading-none text-white ring-2 ring-white"
                data-notification-count>0</span>
        </button>

        <div class="hidden fixed left-3 right-3 top-16 z-50 max-h-[calc(100vh-5rem)] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl sm:absolute sm:left-auto sm:right-0 sm:top-auto sm:mt-3 sm:w-96"
            data-notification-menu>
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <div>
                    <p class="text-base font-bold text-slate-900">Thông báo</p>
                    <p class="text-xs text-slate-500" data-notification-status>Đang tải thông báo...</p>
                </div>
                <span class="hidden rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-600"
                    data-notification-summary>
                    Mới
                </span>
            </div>
            <div class="max-h-[calc(100vh-12rem)] overflow-y-auto sm:max-h-[28rem]" data-notification-list>
                <x-skeletons.notification-list
                    :rows="4"
                    compact
                    data-notification-dropdown-skeleton
                />
            </div>

            <a href="{{ route('notifications.center') }}"
                class="block text-center text-sm font-bold text-primary py-3 border-t border-slate-100 hover:bg-slate-50 transition-colors">
                Xem tất cả thông báo
            </a>
        </div>
    </div>
@endauth
