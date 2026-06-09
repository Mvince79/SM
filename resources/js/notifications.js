const bells = Array.from(document.querySelectorAll('[data-notification-bell]'));

if (bells.length) {
    const escapeHtml = (value = '') => String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    let unreadCount = Math.max(
        0,
        ...bells.map((bell) => Number(
            bell.querySelector('[data-notification-count]')?.textContent.replace(/\D/g, '') || 0,
        )),
    );

    const setUnreadCount = (value) => {
        unreadCount = Math.max(0, Number(value) || 0);

        bells.forEach((bell) => {
            const count = bell.querySelector('[data-notification-count]');
            const summary = bell.querySelector('[data-notification-summary]');

            if (count) {
                count.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
                count.classList.toggle('hidden', unreadCount === 0);
            }

            if (summary) {
                summary.classList.toggle('hidden', unreadCount === 0);
                summary.textContent = unreadCount > 0 ? 'Mới' : '';
            }
        });
    };

    const notificationMarkup = (payload) => `
        <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" />
                <path d="M13.73 21a2 2 0 0 1-3.46 0" />
            </svg>
        </span>
        <span class="min-w-0 flex-1">
            <span class="block text-sm font-semibold leading-snug text-slate-900">${escapeHtml(payload.title || 'Thông báo mới')}</span>
            <span class="mt-1 block text-sm leading-relaxed text-slate-600">${escapeHtml(payload.message || 'Bạn có cập nhật mới trong hệ thống.')}</span>
            <span class="mt-1.5 block text-xs font-medium text-slate-400">Vừa xong</span>
        </span>
        <span class="mt-4 h-2.5 w-2.5 shrink-0 rounded-full bg-blue-500"></span>
    `;

    const prependNotification = (payload) => {
        bells.forEach((bell) => {
            const list = bell.querySelector('[data-notification-list]');

            if (!list) {
                return;
            }

            list.querySelector('[data-notification-empty]')?.remove();

            const item = document.createElement('a');
            item.href = payload.url || '#';
            item.className = 'group flex gap-3 border-b border-slate-100 bg-blue-50/70 px-4 py-3.5 text-sm transition hover:bg-slate-50';
            item.innerHTML = notificationMarkup(payload);
            list.prepend(item);

            while (list.querySelectorAll('a').length > 10) {
                list.querySelector('a:last-of-type')?.remove();
            }
        });
    };

    bells.forEach((bell) => {
        const toggle = bell.querySelector('[data-notification-toggle]');
        const menu = bell.querySelector('[data-notification-menu]');
        const list = bell.querySelector('[data-notification-list]');

        if (!toggle || !menu || !list) {
            return;
        }

        toggle.addEventListener('click', (event) => {
            event.stopPropagation();

            bells.forEach((otherBell) => {
                if (otherBell !== bell) {
                    otherBell.querySelector('[data-notification-menu]')?.classList.add('hidden');
                    otherBell.querySelector('[data-notification-toggle]')?.setAttribute('aria-expanded', 'false');
                }
            });

            menu.classList.toggle('hidden');
            toggle.setAttribute('aria-expanded', menu.classList.contains('hidden') ? 'false' : 'true');
        });

        list.addEventListener('click', async (event) => {
            const item = event.target.closest('[data-notification-item]');

            if (!item || !item.dataset.notificationId) {
                return;
            }

            try {
                const response = await fetch(`/notifications/${item.dataset.notificationId}/read`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });

                if (response.ok) {
                    const data = await response.json();
                    setUnreadCount(data.unread_count);
                    document
                        .querySelectorAll(`[data-notification-id="${item.dataset.notificationId}"]`)
                        .forEach((matchingItem) => {
                            matchingItem.classList.remove('bg-blue-50/70');
                            matchingItem.classList.add('bg-white');
                            matchingItem.querySelector('.bg-blue-500')?.remove();
                        });
                }
            } catch (error) {
                console.warn('Could not mark notification as read', error);
            }
        });
    });

    document.addEventListener('click', (event) => {
        bells.forEach((bell) => {
            if (!bell.contains(event.target)) {
                bell.querySelector('[data-notification-menu]')?.classList.add('hidden');
                bell.querySelector('[data-notification-toggle]')?.setAttribute('aria-expanded', 'false');
            }
        });
    });

    if (window.Echo && window.authUserId) {
        const channel = window.Echo.private(`user.${window.authUserId}`);
        const handleIncomingNotification = (payload) => {
            setUnreadCount(unreadCount + 1);
            prependNotification(payload);
        };

        channel.notification(handleIncomingNotification);

        ['.FaceMatchFound', '.NewClueSubmitted', '.PostStatusChanged'].forEach((eventName) => {
            channel.listen(eventName, handleIncomingNotification);
        });
    }
}
