const bells = Array.from(document.querySelectorAll('[data-notification-bell]'));

if (bells.length) {
    const escapeHtml = (value = '') => String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    let unreadCount = 0;
    let dropdownRequest = null;

    const setUnreadCount = (value) => {
        unreadCount = Math.max(0, Number(value) || 0);

        bells.forEach((bell) => {
            const count = bell.querySelector('[data-notification-count]');
            const summary = bell.querySelector('[data-notification-summary]');
            const status = bell.querySelector('[data-notification-status]');

            if (count) {
                count.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
                count.classList.toggle('hidden', unreadCount === 0);
            }

            if (summary) {
                summary.classList.toggle('hidden', unreadCount === 0);
                summary.textContent = unreadCount > 0 ? 'Mới' : '';
            }

            if (status) {
                status.textContent = unreadCount > 0
                    ? `${unreadCount} thông báo chưa đọc`
                    : 'Bạn đã đọc hết thông báo';
            }
        });
    };

    const notificationContentMarkup = (payload) => `
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
            <span class="mt-1.5 block text-xs font-medium text-slate-400">${escapeHtml(payload.time || 'Vừa xong')}</span>
        </span>
        ${payload.read_at ? '' : '<span class="mt-4 h-2.5 w-2.5 shrink-0 rounded-full bg-blue-500"></span>'}
    `;

    const notificationItemMarkup = (payload) => {
        const background = payload.read_at ? 'bg-white' : 'bg-blue-50/70';
        const id = Number(payload.id);
        const idAttributes = Number.isInteger(id) && id > 0
            ? ` data-notification-item data-notification-id="${id}"`
            : '';

        return `
            <a href="${escapeHtml(payload.url || '#')}"
                class="group flex gap-3 border-b border-slate-100 px-4 py-3.5 text-sm transition hover:bg-slate-50 ${background}"
                ${idAttributes}>
                ${notificationContentMarkup(payload)}
            </a>
        `;
    };

    const emptyMarkup = `
        <div class="px-4 py-8 text-center" data-notification-empty>
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" />
                    <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                </svg>
            </div>
            <p class="mt-3 text-sm font-semibold text-slate-700">Chưa có thông báo</p>
            <p class="mt-1 text-xs text-slate-500">Khi có cập nhật mới, thông báo sẽ hiển thị ở đây.</p>
        </div>
    `;

    const errorMarkup = `
        <div class="px-4 py-8 text-center" data-notification-error>
            <p class="text-sm font-semibold text-slate-700">Không thể tải thông báo</p>
            <button type="button" class="mt-2 text-xs font-bold text-primary hover:underline" data-notification-retry>
                Thử lại
            </button>
        </div>
    `;

    const renderDropdowns = (payload) => {
        const notifications = Array.isArray(payload.notifications) ? payload.notifications : [];

        setUnreadCount(payload.unread_count);

        bells.forEach((bell) => {
            const list = bell.querySelector('[data-notification-list]');

            if (!list) {
                return;
            }

            list.innerHTML = notifications.length
                ? notifications.map(notificationItemMarkup).join('')
                : emptyMarkup;
            bell.dataset.notificationsLoaded = 'true';
        });
    };

    const renderDropdownError = () => {
        bells.forEach((bell) => {
            const list = bell.querySelector('[data-notification-list]');
            const status = bell.querySelector('[data-notification-status]');

            if (list) {
                list.innerHTML = errorMarkup;
            }

            if (status) {
                status.textContent = 'Tải thông báo chưa thành công';
            }
        });
    };

    const loadDropdownNotifications = (force = false) => {
        if (force) {
            dropdownRequest = null;
            bells.forEach((bell) => {
                delete bell.dataset.notificationsLoaded;
            });
        }

        if (dropdownRequest) {
            return dropdownRequest;
        }

        dropdownRequest = fetch('/notifications/dropdown', {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`Notification request failed with status ${response.status}`);
                }

                return response.json();
            })
            .then((payload) => {
                renderDropdowns(payload);

                return payload;
            })
            .catch((error) => {
                dropdownRequest = null;
                renderDropdownError();
                console.warn('Could not load notifications', error);

                return null;
            });

        return dropdownRequest;
    };

    const prependNotification = (payload) => {
        bells.forEach((bell) => {
            const list = bell.querySelector('[data-notification-list]');

            if (!list || bell.dataset.notificationsLoaded !== 'true') {
                return;
            }

            list.querySelector('[data-notification-empty]')?.remove();
            list.insertAdjacentHTML('afterbegin', notificationItemMarkup(payload));

            while (list.querySelectorAll('[data-notification-item]').length > 10) {
                list.querySelector('[data-notification-item]:last-of-type')?.remove();
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

            if (bell.dataset.notificationsLoaded !== 'true') {
                loadDropdownNotifications();
            }
        });

        list.addEventListener('click', async (event) => {
            const retry = event.target.closest('[data-notification-retry]');

            if (retry) {
                event.preventDefault();
                loadDropdownNotifications(true);

                return;
            }

            const item = event.target.closest('[data-notification-item]');

            if (!item || !item.dataset.notificationId) {
                return;
            }

            try {
                const response = await fetch(`/notifications/${item.dataset.notificationId}/read`, {
                    method: 'PATCH',
                    keepalive: true,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        Accept: 'application/json',
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
            setUnreadCount(payload.unread_count ?? unreadCount + 1);
            prependNotification(payload);
        };

        channel.notification(handleIncomingNotification);

        ['.FaceMatchFound', '.NewClueSubmitted', '.PostStatusChanged'].forEach((eventName) => {
            channel.listen(eventName, handleIncomingNotification);
        });
    }

    loadDropdownNotifications();
}
