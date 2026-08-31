import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

/**
 * Realtime badge thông báo cho panel admin (Filament).
 *
 * Khởi tạo Echo theo cùng cách module client reverb-echo.js, rồi lắng nghe sự
 * kiện `notification.received` trên kênh riêng của admin hiện tại để cập nhật
 * badge topbar NGAY LẬP TỨC — không cần F5 trang. Khi Reverb chưa cấu hình
 * (VITE_REVERB_* rỗng) hoặc Echo chưa sẵn sàng, đoạn này tự bỏ qua (graceful),
 * badge vẫn hiển thị đúng sau mỗi lần load trang.
 */
const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
const reverbHost = import.meta.env.VITE_REVERB_HOST;
const authUserId = window.authUserId;

if (reverbKey && reverbHost && authUserId) {
    try {
        if (!window.Echo) {
            window.Pusher = Pusher;

            window.Echo = new Echo({
                broadcaster: 'reverb',
                key: reverbKey,
                wsHost: reverbHost,
                wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
                wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
                forceTLS: window.location.protocol === 'https:',
                enabledTransports: ['ws', 'wss'],
            });
        }

        const channel = window.Echo.private(`user.${authUserId}`);

        channel.listen('.notification.received', (payload) => {
            const count = Math.max(0, Number(payload.unread_count ?? 0));
            const badge = document.querySelector('[data-admin-notification-badge]');

            if (!badge) {
                return;
            }

            let countEl = badge.querySelector('[data-admin-notification-count]');

            if (count > 0) {
                if (!countEl) {
                    countEl = document.createElement('span');
                    countEl.className = 'admin-notif-bell__count';
                    countEl.setAttribute('data-admin-notification-count', '');
                    badge.appendChild(countEl);
                }

                countEl.textContent = count > 99 ? '99+' : String(count);
            } else if (countEl) {
                countEl.remove();
            }

            const label = count > 0
                ? `${count} thông báo chưa đọc`
                : 'Không có thông báo mới';

            badge.setAttribute('title', label);
            badge.setAttribute('aria-label', label);
        });
    } catch (error) {
        console.warn('[realtime] Admin badge could not subscribe to notifications.', error);
    }
}
