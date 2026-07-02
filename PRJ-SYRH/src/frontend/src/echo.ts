import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

(window as any).Pusher = Pusher;

const revPort = import.meta.env.VITE_REVERB_PORT;
const wsPort = revPort ? parseInt(revPort) : undefined;

const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY || 'syrh-reverb-key',
    wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
    ...(wsPort ? { wsPort, wssPort: wsPort } : {}),
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME || 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

export default echo;
