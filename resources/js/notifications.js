const bells = Array.from(document.querySelectorAll('[data-notification-bell]'));

if (bells.length) {
    const escapeHtml = (value = '') => String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    // Biểu tượng + màu theo loại sự kiện (đồng bộ với notification-center.blade.php).
    const EVENT_ICONS = {
        FACE_MATCH_CONFIRMED:
            '<path d="M15 9a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path d="M17.5 11.5a5.5 5.5 0 0 0-11 0v3a5.5 5.5 0 0 0 11 0v-3Z"/><path d="M12 17.5c2 0 3.5.8 4.5 1.6"/>',
        HANDOVER_READY:
            '<path d="M20 6 9 17l-5-5"/>',
        CASE_APPROVED:
            '<path d="M9 12.5 11 14.5 15.5 10"/><path d="M14 3.5 20 3.5 20 9.5"/><path d="M20 13.5v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h8"/>',
        CLUE_VERIFIED:
            '<path d="M9 18h6"/><path d="M10 22h4"/><path d="M12 2a7 7 0 0 0-4 12.7c.6.5 1 1.2 1 2V17h6v-.3c0-.8.4-1.5 1-2A7 7 0 0 0 12 2Z"/>',
        REUNION_FAMILY_REMINDER:
            '<path d="M21 11.5a8.4 8.4 0 0 1-9 8.3L3 21l1.2-3.9A8.4 8.4 0 1 1 21 11.5Z"/>',
        REUNION_ADMIN_ESCALATION:
            '<path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 3.3 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.3a2 2 0 0 0-3.4 0Z"/>',
        CASE_UNREACHABLE_FAMILY:
            '<path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 3.3 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.3a2 2 0 0 0-3.4 0Z"/>',
    };

    const EVENT_COLORS = {
        FACE_MATCH_CONFIRMED: 'bg-emerald-500/10 text-emerald-600',
        HANDOVER_READY: 'bg-primary/10 text-primary',
        CASE_APPROVED: 'bg-sky-500/10 text-sky-600',
        CASE_APPROVED_IN_PROVINCE: 'bg-sky-500/10 text-sky-600',
        CLUE_VERIFIED: 'bg-amber-500/10 text-amber-600',
        REUNION_FAMILY_REMINDER: 'bg-primary/10 text-primary',
        REUNION_ADMIN_ESCALATION: 'bg-rose-500/10 text-rose-600',
        CASE_UNREACHABLE_FAMILY: 'bg-rose-500/10 text-rose-600',
    };

    const iconSvg = (eventType) => {
        const inner = EVENT_ICONS[eventType] ?? EVENT_ICONS.default
            ?? '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>';

        return `<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${inner}</svg>`;
    };

    const colorClass = (eventType) => EVENT_COLORS[eventType] ?? 'bg-primary/10 text-primary';

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

    const sourceChipMarkup = (payload) => {
        const label = payload.source_label;

        if (!label) {
            return '';
        }

        return `<span class="mt-1.5 inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500">${escapeHtml(label)}</span>`;
    };

    const notificationContentMarkup = (payload) => `
        <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-full ${colorClass(payload.event_type)}">
            ${iconSvg(payload.event_type)}
        </span>
        <span class="min-w-0 flex-1">
            <span class="block text-sm font-semibold leading-snug text-slate-900">${escapeHtml(payload.title || 'Thông báo mới')}</span>
            <span class="mt-1 block text-sm leading-relaxed text-slate-600">${escapeHtml(payload.message || 'Bạn có cập nhật mới trong hệ thống.')}</span>
            <span class="mt-1.5 flex flex-wrap items-center gap-x-2">
                <span class="block text-xs font-medium text-slate-400">${escapeHtml(payload.time || 'Vừa xong')}</span>
                ${sourceChipMarkup(payload)}
            </span>
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
                    <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
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

    const markAllRead = () => {
        fetch('/notifications/read-all', {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                Accept: 'application/json',
            },
        })
            .then((response) => (response.ok ? response.json() : null))
            .then((data) => {
                setUnreadCount(data?.unread_count ?? 0);

                bells.forEach((bell) => {
                    const list = bell.querySelector('[data-notification-list]');

                    if (!list) {
                        return;
                    }

                    list.querySelectorAll('[data-notification-item]').forEach((item) => {
                        item.classList.remove('bg-blue-50/70');
                        item.classList.add('bg-white');
                        item.querySelector('.bg-blue-500')?.remove();
                    });
                });
            })
            .catch((error) => {
                console.warn('Could not mark all notifications as read', error);
            });
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

        const markAll = bell.querySelector('[data-notification-mark-all]');

        if (markAll) {
            markAll.addEventListener('click', (event) => {
                event.preventDefault();
                markAllRead();
            });
        }

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

        // Chỉ lắng nghe sự kiện realtime hợp nhất (NotificationCountUpdated) — nó đã
        // mang sẵn payload thẻ thông báo và unread_count. Không còn các nhánh hệ cũ
        // (channel.notification(), .FaceMatchFound, .NewClueSubmitted, .PostStatusChanged).
        channel.listen('.notification.received', (payload) => {
            setUnreadCount(payload.unread_count ?? unreadCount);

            if (payload.notification) {
                prependNotification(payload.notification);
            }
        });
    }

    loadDropdownNotifications();
}
