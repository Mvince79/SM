import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
});

let chatRefreshQueued = false;

const dispatchMessageReceived = () => {
    if (chatRefreshQueued) {
        return;
    }

    chatRefreshQueued = true;

    window.setTimeout(() => {
        chatRefreshQueued = false;
        window.Livewire?.dispatch('message-received');
    }, 25);
};

if (window.authUserId) {
    window.Echo
        .private(`private-chat.${window.authUserId}`)
        .listen('.message.sent', dispatchMessageReceived);

    const assignmentIds = Array.isArray(window.adminChatAssignmentIds)
        ? [...new Set(window.adminChatAssignmentIds.map(Number).filter(Number.isInteger))]
        : [];

    assignmentIds.forEach((assignmentId) => {
        window.Echo
            .private(`admin-chat-assignment.${assignmentId}`)
            .listen('.admin.chat.message.sent', dispatchMessageReceived);
    });
}
