@auth
    @php
        $notifications = auth()->user()->notifications()->latest()->limit(10)->get();
        $unreadCount = auth()->user()->unreadNotifications()->count();
    @endphp

    <div class="relative" data-notification-bell>
        <button type="button"
            class="relative inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-primary hover:bg-primary/5 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
            data-notification-toggle aria-label="Thông báo" aria-expanded="false">
            <x-heroicon-o-bell class="h-5 w-5" />
            <span
                class="{{ $unreadCount > 0 ? '' : 'hidden' }} absolute -right-1 -top-1 flex min-h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-center text-[11px] font-bold leading-none text-white ring-2 ring-white"
                data-notification-count>{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
        </button>

        <div class="hidden fixed left-3 right-3 top-16 z-50 max-h-[calc(100vh-5rem)] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl sm:absolute sm:left-auto sm:right-0 sm:top-auto sm:mt-3 sm:w-96"
            data-notification-menu>
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <div>
                    <p class="text-base font-bold text-slate-900">Thông báo</p>
                    <p class="text-xs text-slate-500">
                        {{ $unreadCount > 0 ? $unreadCount . ' thông báo chưa đọc' : 'Bạn đã đọc hết thông báo' }}
                    </p>
                </div>
                <span class="{{ $unreadCount > 0 ? '' : 'hidden' }} rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-600"
                    data-notification-summary>
                    Mới
                </span>
            </div>
            <div class="max-h-[calc(100vh-12rem)] overflow-y-auto sm:max-h-[28rem]" data-notification-list>
                @forelse ($notifications as $notification)
                    <a href="{{ $notification->data['url'] ?? '#' }}"
                        class="group flex gap-3 border-b border-slate-100 px-4 py-3.5 text-sm transition hover:bg-slate-50 {{ $notification->read_at ? 'bg-white' : 'bg-blue-50/70' }}"
                        data-notification-item data-notification-id="{{ $notification->id }}">
                        <span
                            class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <x-heroicon-o-bell class="h-5 w-5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold leading-snug text-slate-900">
                                {{ $notification->data['title'] ?? 'Thông báo mới' }}
                            </span>
                            <span class="mt-1 block text-sm leading-relaxed text-slate-600">
                                {{ $notification->data['message'] ?? 'Bạn có cập nhật mới trong hệ thống.' }}
                            </span>
                            <span class="mt-1.5 block text-xs font-medium text-slate-400">
                                {{ $notification->created_at?->diffForHumans() }}
                            </span>
                        </span>
                        @unless ($notification->read_at)
                            <span class="mt-4 h-2.5 w-2.5 shrink-0 rounded-full bg-blue-500"></span>
                        @endunless
                    </a>
                @empty
                    <div class="px-4 py-8 text-center" data-notification-empty>
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                            <x-heroicon-o-bell class="h-6 w-6" />
                        </div>
                        <p class="mt-3 text-sm font-semibold text-slate-700">Chưa có thông báo</p>
                        <p class="mt-1 text-xs text-slate-500">Khi có cập nhật mới, thông báo sẽ hiển thị ở đây.</p>
                    </div>
                @endforelse
            </div>

            <a href="{{ route('notifications.center') }}"
                class="block text-center text-sm font-bold text-primary py-3 border-t border-slate-100 hover:bg-slate-50 transition-colors">
                Xem tất cả thông báo
            </a>
        </div>
    </div>
@endauth