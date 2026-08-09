import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
const reverbHost = import.meta.env.VITE_REVERB_HOST;

// Realtime is an optional enhancement. A missing deployment variable must not
// abort the whole application bundle (navigation, dark mode, maps, ...).
if (reverbKey && reverbHost) {
    try {
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: reverbKey,
            wsHost: reverbHost,
            wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
            wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
            forceTLS: window.location.protocol === 'https:',
            enabledTransports: ['ws', 'wss'],
        });
    } catch (error) {
        console.warn('[realtime] Reverb could not be initialized.', error);
    }
} else {
    console.warn('[realtime] Reverb is disabled because its deployment configuration is incomplete.');
}

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

if (window.authUserId && window.Echo) {
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
